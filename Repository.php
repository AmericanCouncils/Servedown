<?php

namespace AC\Servedown;

use Symfony\Component\Finder\Finder;

/**
 * A root entry point for a markdown content directory.  Serves as a configurable
 * factory for file objects contained within.
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
     * Hash of config for how the repo should behave.
     *
     * @var string
     */
    private $config;

    public function __construct($basePath, $config = array())
    {
        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("Repository root must be a directory.");
        }

        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Factory method for files in a path.  Will check containing
     * directories to properly cascade configuration.
     *
     * @param  string $path
     * @return File
     */
    public function getFile($path)
    {
        $path = $this->validatePath($path);

        $file = new File($path);

        //TODO: load containing directories and merge configs

        return $file;
    }

    public function getFilesInPath($path)
    {
        $path = $this->validatePath($path);
        $files = array();

        //load files this repo is allowed to see

        return $files;
    }
    
    
    public function getPathContents($path)
    {
        $path = $this->validatePath($path);
        
        foreach (scandir($path) as $item) {
            
        }
    }

    public function getConfig()
    {
        return $this->config;
    }
    
    public function setConfig(array $config)
    {
        $this->config = $config;
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
            'hidden_directory_prefixes' => array("_"),
            'index_file_name' => 'index',
            'file_extensions' => array('markdown','md','textile','txt')
        );
    }

    protected function createFileForPath($path)
    {
        $path = $this->validatePath($path);
        
        $f = new File($path);
    }
    
    protected function createFinderForDirectory($path)
    {
        $finder = new Finder();
        $finder->in($path);
        
        foreach ($this->get('file_extensions') as $ext) {
            $finder->files()->name("*.$ext");
        }
        
        foreach ($this->get('hidden_directory_prefixes') as $prefix) {
            $finder->files->notName(sprintf("%s*", $prefix));
        }
        
        $finder->depth("== 0");
        
        return $finder;
    }

    protected function getTitleForPath($path)
    {
        return $path;
    }

    protected function validatePath($path)
    {
        $path = $this->path.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
        
        //TODO: check if path is "servable"
        
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf("File not found: [%s]", $path));
        }

        return $path;
    }

}
