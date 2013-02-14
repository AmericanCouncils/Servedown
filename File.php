<?php

namespace AC\Servedown;

use Symfony\Component\Yaml\Yaml;

/**
 * A simple class for representing markdown files.  Markdown files can contain an optional
 * metadata header written in YAML.  This class allows you to return the raw content, markdown
 * content, or metadata structure independently.
 *
 * @package Servedown
 * @author Evan Villemez
 */
class File implements \ArrayAccess
{
    protected $path;
    protected $isIndex = false;
    protected $config = array();
    protected $raw = null;
    protected $content = null;

    private $loaded = false;
    private $parent = false;

    /**
     * Constructor, builds a page object around a path to a real file.
     *
     * @param string|SplFileObject $info
     */
    public function __construct($info)
    {
        if (!$info instanceof \SplFileInfo) {
            $info = new \SplFileInfo($info);
        }

        if ($info->isDir()) {
            throw new \InvalidArgumentException(sprintf("Path %s is a directory", $info->getRealPath()));
        }

        $this->path = $info->getRealPath();

    }

    public function __toString()
    {
        $this->load();

        return $this->content;
    }

    /**
     * Get the path to the file for this object.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return the pages metadata structure.
     *
     * @return array
     */
    public function getConfig()
    {
        $this->load();

        return $this->config;
    }

    /**
     * Set entire configuration array for file.
     *
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->load();

        $this->config = $config;
    }

    /**
     * Get the parent directory of this file, if availble
     *
     * @return Directory|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the parent directory of this file
     *
     * @param Directory $dir
     */
    public function setParent(Directory $dir)
    {
        $this->parent = $dir;
    }

    /**
     * Return whether or not this file has a parent directory.
     *
     * @return boolean
     */
    public function hasParent()
    {
        return ($this->parent) ? true : false;
    }

    /**
     * Get a specific property from the page metadata, returning a default
     * value if it does not exist
     *
     * @param  string $prop
     * @param  mixed  $default
     * @return mixed
     */
    public function get($prop, $default = null)
    {
        $this->load();

        return (isset($this->config[$prop])) ? $this->config[$prop] : $default;
    }

    /**
     * Explicitly set a page metadata value.  This could be useful for event listeners
     * to modify page settings dynamically at runtime.
     *
     * @param string $prop
     * @param mixed  $val
     */
    public function set($prop, $val)
    {
        $this->load();

        $this->config[$prop] = $val;
    }
    
    /**
     * Remove a config value
     *
     * @param string $key 
     */
    public function remove($key)
    {
        if (isset($this->config[$key])) {
            unset($this->config[$key]);
        }
    }

    /**
     * Return the markdown content of the file.
     *
     * @return string
     */
    public function getContent()
    {
        $this->load();

        return $this->content;
    }
    
    /**
     * Return whether or not the file contains any real content (other than configuration)
     *
     * @return boolean
     */
    public function hasContent()
    {
        $c = $this->getContent();
        return (!empty($c));
    }

    /**
     * Return the raw data for the file, including the YAML
     * header, if there.
     *
     * @return string
     */
    public function getRaw()
    {
        $this->load();

        return $this->raw;
    }

    /**
     * Return true/false for if this file is a directory.  Files always
     * return false
     *
     * @return boolean
     */
    public function isDirectory()
    {
        return false;
    }

    /**
     * Set whether or not this file is also a directory index file.
     *
     * @param boolean $bool
     */
    public function setIsIndex($bool)
    {
        $this->isIndex = (bool) $bool;
    }

    /**
     * Get whether or not this file is a directory index.
     *
     * @return boolean
     */
    public function isIndex()
    {
        return $this->isIndex;
    }

    /**
     * @see \ArrayAccess
     */
    public function offsetExists($key)
    {
        return isset($this->config[$key]);
    }

    /**
     * @see \ArrayAccess
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * @see \ArrayAccess
     */
    public function offsetSet($key, $val)
    {
        $this->set($key, $val);
    }

    /**
     * @see \ArrayAccess
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * This actually loads and parses file contents from disc.  It would probably
     * be much more efficient to implement this in a regex.
     */
    protected function load()
    {
        if (!$this->loaded && !is_dir($this->path)) {
            $data = file($this->path);
            $inHeader = false;
            $header = array();
            $content = array();
            foreach ($data as $line) {
                if ($line == "````\n") {
                    $inHeader = !$inHeader;
                    continue;
                }

                if ($inHeader) {
                    $header[] = $line;
                } else {
                    $content[] = $line;
                }
            }
            $c = trim(implode('', $content));
            
            $this->raw = implode('', $data);
            $this->content = empty($c) ? null : $c;
            $this->config = !empty($header) ? Yaml::parse(implode('', $header)) : array();
        }

        $this->loaded = true;
    }
}
