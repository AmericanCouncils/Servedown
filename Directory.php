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
class Directory extends File
{
    /**
     * Internal cache of directory configs, to avoid hitting
     * the disc too much
     *
     * @var array
     */
    private $dirCache = array();
    
    /**
     * Instance of index file, if present
     *
     * @var File
     */
    private $indexFile;
    
    /**
     * Construct needs the base directory, and optionally some
     * configuration overrides.
     *
     * @param string $basePath Absolute path to root directory
     * @param array $config Optional array of configuration overrides
     */
    public function __construct($basePath, $config = array())
    {
        parent::__construct($basePath);
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Factory method for files in a path.  Will check containing
     * directories to properly cascade configuration.
     *
     * @param  string $path Relative path to file from root of repository
     * @return File
     */
    public function getFile($path)
    {
        $path = $this->validatePath($path);
        
        $file = new File($path);
        
        
        
        //TODO: load containing directories and merge configs

        return $file;
    }

    public function getFilesInDirectory($path, $includeIndex = false, $includeHiddenDirs = false)
    {
        $file = $this->createFileForPath($path);
        $files = array();

        foreach ($this->createFinderForDirectory($path) as $file) {
            $files[] = $this->createFileForPath($file->getRealpath());
        }

        return $files;
    }
    
    /**
     * This is the default method used to create human readable titles
     * for breadcrumbs and contained files when no `title` config is present,
     * override it if need be
     *
     * @param string $path 
     * @return string
     */
    public function getDefaultTitle($path)
    {
        $exp = explode(DIRECTORY_SEPARATOR, $path);
        $end = end($exp);
        $exp = explode(".", $end);
        if (count($exp) > 1) {                
            array_pop($exp);
        }
        $exp = explode("_", implode("_", $exp));
        array_walk($exp, function(&$val) {
            $val = ucfirst(strtolower($val));
        });
        return implode(" ", $exp);
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
            'title' => $this->getDefaultTitle($this->basePath),
            'allow_directory_index' => true,
            'hidden_directory_prefixes' => array("_"),
            'index_file_name' => 'index',
            'file_extensions' => array('markdown','md','textile','txt')
        );
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

    /**
     * Creates the file for a given path, checking for whether or not it is a 
     * directory index file.
     *
     * @param string $path 
     * @return string
     */
    protected function createFileForPath($path)
    {
        $path = (0 === strpos($path, DIRECTORY_SEPARATOR)) ? $this->basePath.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : $path;
        
        //check for directory index file
        if (is_dir($path) && $this->get('allow_directory_index', true)) {
            foreach ($this->get('file_extensions', array()) as $ext) {
                $p = $path.DIRECTORY_SEPARATOR.$this->get('index_name', 'index').".".$ext;
                if (file_exists($p)) {
                    $file = new File($p);
                    $file->setIsDirectory(true);
                    return $file;
                }
            }
        }

        return new File($path);
    }

}
