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

    /**
     * Internal cache of directory configs, to avoid hitting
     * the disc too much
     *
     * @var array
     */
    private $dirCache = array();
    
    private $titleTransformer;

    /**
     * Construct needs the base directory, and optionally some
     * configuration overrides.
     *
     * @param string $basePath Absolute path to root directory
     * @param array $config Optional array of configuration overrides
     */
    public function __construct($basePath, $config = array())
    {
        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("Repository root must be a directory.");
        }

        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->config = array_merge($this->getDefaultConfig(), $config);
        
        // this is the default method used to create human readable titles
        // for breadcrumbs and contained files when no `title` config is present,
        // override it if need be
        $this->setTitleTransformer(function($path) {
            $exp = explode(DIRECTORY_SEPARATOR, $path);
            $end = end($exp);
            $exp = explode(".", $end);
            if (count($exp) > 1) {                
                array_pop($exp);
            }
            array_walk($exp, function($val) { return ucfirst(strtolower($val)); });
            return implode(" ", $exp);
        });
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

    public function getFilesInDirectory($path)
    {
        $path = $this->validatePath($path);
        $files = array();

        foreach ($this->createFinderForPath($path) as $file) {
            $files[] = $this->getFile($file->getRealpath());
        }

        return $files;
    }
    
    public function setTitleTransformer(\Closure $func)
    {
        $this->titleTransformer = $func;
    }
    
    public function getTitleTransformer()
    {
        return $this->titleTransformer;
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
        $transformer = $this->getTitleTransformer();

        return array(
            'title' => $transformer($this->basePath),
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
    
    protected function createFinderForDirectory($path, $recurse = false)
    {
        $finder = new Finder();
        $finder->in($path);
        
        //ignore hidden directories
        foreach ($this->get('hidden_directory_prefixes', array()) as $prefix) {
            $finder->notName(sprintf("%s*", $prefix));
        }

        //only get allowed extensions
        foreach ($this->get('file_extensions', array()) as $ext) {
            $finder->name(sprintf("*.%s", $ext));
        }
        
        if (!$recurse) {
            $finder->depth("== 0");
        }
        
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
