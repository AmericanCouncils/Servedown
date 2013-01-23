<?php

namespace AC\Servedown;

/**
 * Like a directory, but acts as a configurable factory for arbitrary contained paths.  It also
 * provides extra functionality for using the files on the web, for example, breadcrumbs.
 *
 * @package Servedown
 * @author Evan Villemez
 */
class Repository extends Directory
{
    private $cache = array();
    
    public function __construct($path, $repoConfig = array(), $dirBehavior = array())
    {
        $f = new \SplFileObject($path);
        if (!$f->isDir()) {
            throw new \InvalidArgumentException(sprintf("Repository roots must be a directory"));
        }
        
        parent::__construct($f);
    }
    
    /**
     * Factory method for files in a path.  Will check containing
     * directories to properly cascade configuration.
     *
     * @param  string $path Relative path to file from root of repository
     * @return File
     */
    public function getPath($path)
    {
        $path = $this->validatePath($path);
        
        $file = new File($path);
        
        
        
        //TODO: load containing directories and merge configs

        return $file;
    }

    /**
     * This is the default method used to create human readable titles
     * for breadcrumbs and contained files when no `title` config is present,
     * override it if need be
     *
     * @param string $path 
     * @return string
     */
    public function getDefaultTitleForItem($path)
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

    
    public function getBreadcrumbsForItem()
    {
        
    }
    
}
