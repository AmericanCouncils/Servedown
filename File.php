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
    private $path;
    private $raw = null;
    private $config = array();
    private $content = null;
    private $loaded = false;
    private $breadcrumb = array();
    private $isDirectory = false;

    /**
     * Constructor, builds a page object around a path to a real file.
     *
     * @param string|SplFileObject $info
     */
    public function __construct($info)
    {
        $this->path = (is_string($info)) ? $info : $info->getRealpath();

        if (!file_exists($this->path)) {
            throw new \InvalidArgumentException(sprintf("Path does not exist: [%s]!", $path));
        }
        
        if (is_dir($this->path)) {
            $this->setIsDirectory(true);
        }
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

    public function setConfig(array $config)
    {
        $this->load();
        
        $this->config = $config;
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
     * Return array of breadcrumb data.
     *
     * @return array
     */
    public function getBreadcrumbData()
    {
        $this->load();

        return $this->breadcrumb;
    }

    /**
     * Set array of breadcrumb data, format is array of hashes, example below:
     *
     *  array(
     *      'title' => "Human Readable String",
     *      'url' => "http://example.com"
     *  )
     *
     *
     * @param array $data 
     */
    public function setBreadcrumbData(array $data)
    {
        $this->load();

        $this->breadcrumb = $data;
    }

    /**
     * Return true/false for if this "page" is actually
     * a directory index file.
     *
     * @return boolean
     */
    public function isDirectory()
    {
        return $this->isDirectory;
    }
    
    /**
     * Set whether or not this file is also a directory reference.  This must be
     * set by the Repository, as it is configurable.
     *
     * @param boolean $isDir 
     */
    public function setIsDirectory($isDir)
    {
        $this->isDirectory = (bool) $isDir;
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
