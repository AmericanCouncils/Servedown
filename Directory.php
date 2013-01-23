<?php

namespace AC\Servedown;

use Symfony\Component\Finder\Finder;

/**
 * A root entry point for a markdown content directory.  Serves as a configurable
 * factory for file objects contained within.  Will take care of cascading configuration
 * defined in an index file, if present.
 *
 * A directory with an index file is considered a directory with contained files, even though
 * that index file is technically a file on disc just like any other.
 *
 * Directories will cascade their behavioral configuration to contained directories.
 *
 * @package Servedown
 * @author Evan Villemez
 */
class Directory extends File
{
    private $hasIndex = false;
    private $indexFile;    
    private $behaviors;
    private $containedFiles = array();
    
    /**
     * Construct needs the base directory, and optionally some
     * configuration overrides.
     *
     * @param string $basePath Absolute path to root directory
     * @param array $behavior Optional array of configuration overrides
     */
    public function __construct($info, $behaviors = array())
    {   
        if (!$info instanceof \SplFileObject) {
            $info = new \SplFileObject($info);
        }

        $this->behaviors = array_merge($this->getDefaultBehaviors(), $behaviors);
        $this->path = $info->getRealPath();
        
        //if there's a file at this path...
        if (!$info->isDir() && !$this->getBehavior('allow_directory_index')) {
            throw new \InvalidArgumentException(sprintf("This path is not a directory and index files are disallowed."));
        }
        
        //check for index file, optionally override path
        if (!$info->isDir()) {
            $this->indexFile = $this->validateIndexFile($info);
            $this->indexFile->setParent($this);
            $this->hasIndex = true;
            $this->path = dirname($this->indexFile->getRealPath());
        }
        //or search for an idex file
        elseif ($this->getBehavior('allow_directory_index', false) && $indexFileName = $this->getBehavior('index_name', false)) {
            foreach ($this->getBehavior('file_extensions') as $ext) {
                foreach (scandir($this->path) as $item) {
                    $indexFilePath = $this->path.DIRECTORY_SEPARATOR.$indexFileName.".".$ext;
                    if (file_exists($indexFilePath)) {
                        $this->indexFile = new File($indexFilePath);
                        $this->indexFile->setParent($this);
                        $this->hasIndex = true;
                    }
                }
            }
        }
    }
    
    protected function validateIndexFile(\SplFileObject $file)
    {
        $filename = $file->getFilename();
        $dir = dirname($filename);
        
        $exp = explode(".", $filename);
        $ext = end($exp);
        if (!in_array(strtolower($ext, $this->getBehavior('file_extensions')))) {
            throw new \InvalidArgumentException(sprintf("The file %s is not a valid directory index file.", $file->getRealPath()));
        }
                
        return new File($file);
    }
    
    public function getConfig()
    {
        if ($this->indexFile) {
            return $this->indexFile->getConfig();
        } else {
            return parent::getConfig();
        }
    }
    
    public function setConfig(array $config)
    {
        if ($this->indexFile) {
            $this->indexFile->setConfig($config);
        } else {
            parent::setConfig($config);
        }
        
    }
        
    public function hasIndex()
    {
        return $this->hasIndex;
    }
    
    public function getIndexFile()
    {
        //todo: return File instance of index file
    }
    
    /**
     * Get array of contained files and directories.
     *
     * @param boolean $includeIndex Whether or not to include the directory index file, if present
     * @return array
     */
    public function getFiles($includeIndex = false)
    {
        $paths = array();
        $files = array();
        
        $finder = $this->createFinderForDirectory($this->path);
        foreach (iterator_to_array($finder) as $item) {
            if ($item->isDir()) {
                $dir = new Directory($item, $this->getBehaviors());
                $this->processConfigForFile($dir);
                $dir->setParent($this);
                $files[] = $dir;
            } else {
                $file = new File($info);
                $this->processConfigForFile($file);
                $file->setParent($this);
                $files[] = $file;
            }
        }
        
        if($includeIndexFile) {
            $files[] = $this->indexFile;
        }
        
        return $files;
    }
    
    protected function processConfigForFile(File $file)
    {
        if ($this->getBehavior('config_cascade', false)) {
            foreach ($this->getBehavior('config_cascade_whitelist', array()) as $key) {
                $file->set($key, $this->get($key));
            }
        }
    }
    
    public function isDirectory()
    {
        return true;
    }
    
    public function getBehaviors()
    {
        return $this->behaviors;
    }
    
    public function setBehaviors(array $data)
    {
        $this->behaviors = $data;
    }
    
    public function getBehavior($key, $default = null)
    {
        return isset($this->behaviors[$key]) ? $this->behaviors[$key] : $default;
    }
    
    public function setBehavior($key, $val)
    {
        $this->behaviors[$key] = $val;
    }
    
    protected function getDefaultBehaviors()
    {
        return array(
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
