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
    private $cached = array();
    protected $repoConfig;

    public function __construct($path, $repoConfig = array(), $dirBehavior = array())
    {
        $f = new \SplFileInfo($path);
        if (!$f->isDir()) {
            throw new \InvalidArgumentException(sprintf("Repository roots must be a directory"));
        }
        
        $this->repoConfig = $repoConfig;
        
        $dirBehavior = array_merge($this->getDefaultBehaviors(), $dirBehavior);

        parent::__construct($f, $dirBehavior);
    }
    
    public function getRepoConfig()
    {
        return $this->repoConfig;
    }
    
    public function setRepoConfig(array $config)
    {
        $this->repoConfig = $config;
    }

    /**
     * Factory method for files in a path.  Will check containing
     * directories to properly cascade configuration.
     *
     * @param  string $path Relative path to file from root of repository
     * @return File
     */
    public function getItem($path)
    {
        $path = $this->getRelativePath($path);

        //check the cache first
        if (isset($this->cached[$path])) {
            return $this->cached[$path];
        }
        
        //or load the file (plus containing files)
        $items = explode(DIRECTORY_SEPARATOR, $path);
        $filepath = '';
        $first = true;
        foreach ($items as $item) {
            if ($first) {
                $filepath = $item;
                $file = $this->getFile($filepath);
            } else {
                $parent = $this->cached[$filepath];
                $file = $parent->getFile($item);                
                $filepath .= DIRECTORY_SEPARATOR . $item;
            }

            $this->cached[$filepath] = $file;
            $first = false;
        }
        
        if (!$file) {
            throw new \Exception(sprintf("No file for path [%s].", $path));
        }

        return $file;
    }

    /**
     * Returns array of breadcrumb data for the specific item.  Data is in the format of
     * an array of hashes, each of which looks like this:
     *
     *      array(
     *          'title' => 'Title text',   //If the file does not have a title config, a default one based on the file path is used
     *          'url' => 'url'             //If the repo is configured with a `base_url`, the urls will be absolute, otherwise relative
     *      );
     *
     * @param string|File $item 
     * @return array
     */
    public function getBreadcrumbsForItem($item)
    {
        
        //TODO: START HERE, MORE TESTS
        
        $path = ($item instanceof File) ? $item->getPath() : $item;
        $path = $this->getRelativePath($path);
        
        $items = explode(DIRECTORY_SEPARATOR, $path);
        $baseUrl = (isset($this->repoConfig['base_url'])) ? $this->repoConfig['base_url'] : '';
        $breadcrumbs = array();
                
        //add root node (this)
        $breadcrumbs[] = array(
            'title' => $this->get('title', $this->getDefaultTitleForItem($this->getPath())),
            'url' => empty($baseUrl) ? '' : $baseUrl."/"
        );
        
        //loop through contained paths
        $filepath = '';
        foreach ($items as $item) {
            $filepath .= "/".$item;
            $f = $this->getItem($filepath);
            
            $urlEnd = ($f->isDirectory()) ? "/" : '';
            
            $url = (empty($baseUrl)) ? ltrim($baseUrl.$filepath.$urlEnd, "/") : $baseUrl.$filepath.$urlEnd;
            
            $breadcrumbs[] = array(
                'title' => $f->get('title', $this->getDefaultTitleForItem($f->getPath())),
                'url' => $url
            );
        }
                
        return $breadcrumbs;
    }

    /**
     * This is the default method used to create human readable titles
     * for breadcrumbs and contained files when no `title` config is present,
     * override it if need be.
     *
     * @param  string|File $item
     * @return string
     */
    public function getDefaultTitleForItem($item)
    {
        $name = ($item instanceof File) ? basename($item->getPath()) : basename($item);
        
        $exp = explode(".", $name);
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
     * Return the path as it should be relative to the repository root.
     *
     * @param string $path 
     * @return string
     */
    public function getRelativePath($path)
    {
        rtrim($path, "/");
        
        if (0 !== strpos($path, DIRECTORY_SEPARATOR)) {
            $path = $this->path.DIRECTORY_SEPARATOR.$path;
        }
        
        return substr(realpath($path), strlen($this->getPath()) + 1);
    }
    
}
