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

    /**
     * Constructor, builds a page object around a path to a real file.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }
    
    public function __toString()
    {
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
    
    public function getBreadcrumbData()
    {
        return $this->breadcrumb;
    }
    
    public function setBreadcrumbData(array $data)
    {
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
        $exp = explode(DIRECTORY_SEPARATOR, $this->path);

        return ('index.md' === end($exp) || is_dir($this->path));
    }

    protected function load()
    {
        if (!$this->loaded && !$this->isDirectory()) {
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
