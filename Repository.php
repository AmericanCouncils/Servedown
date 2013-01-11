<?php

namespace AC\Servedown;

/**
 * A root entry point for a markdown content directory
 *
 * @package Servedown
 * @author Evan Villemez
 */
class Repository
{
    /**
     * The base path to the directory of content.
     *
     * @var string
     */
    private $basePath;
    
    /**
     * Base web url corresponding to the content directory.
     *
     * @var string
     */
    private $baseUrl;
    
    /**
     * Hash of config for how the repo should behave.
     *
     * @var string
     */
    private $config;
    
	public function __construct($basePath, $baseUrl = null, $config = array())
    {
        if(!is_dir($basePath)) {
            throw new \InvalidArgumentException("Repository root must be a directory.");
        }
        
        $this->basePath = $basePath;
        $this->baseUrl = $baseUrl;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }
        
    /**
     * Factory method for files in a path.  Will check containing
     * directories to properly cascade configuration.
     *
     * @param string $path 
     * @return File
     */
    public function getPath($path)
    {
        $path = $this->validatePath($path);
        
        $file = new File($path);
        
        //TODO: load containing directories and merge configs
        
        return $file;
    }
    
    public function getFilesInPath($path)
    {
        $path = $this->validatePath($path);
        $files = array()
        //load files this repo is allowed to see
        
        return $files;
    }
    
    public function get($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    public function set($key, $val)
    {
        $this->config[$key] = $val;
    }
    
    protected function getDefaultConfig()
    {
        return array(
            'title' => $this->getTitleForPath($this->basePath),
            'allow_directory_index' => true,
            'index_file_name' => 'index',
            'file_extensions' => array('markdown','md','textile','txt'),
        );
    }

    protected function getTitleForPath($path)
    {
        return $path;
    }

    protected function validatePath($path)
    {
        $path = $this->path.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf("File not found: [%s]", $path));
        }
        
        return $path;
    }
    
}
