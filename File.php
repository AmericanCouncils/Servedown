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
class File
{
    protected $file;
    protected $path;
    protected $breadcrumb = array();
    protected $isDirectory = false;
    protected $isIndex = false;

    private $raw = null;
    private $config = array();
    private $content = null;
    private $loaded = false;
    private $parent = null;

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
    public function pasParent()
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

            $this->raw = implode('', $data);
            $this->content = implode('', $content);
            $this->config = !empty($header) ? Yaml::parse(implode('', $header)) : array();
        }

        $this->loaded = true;
    }
}
