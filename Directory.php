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
 * Behaviors are different from Config, if present.  Config is generally values that pertain
 * in some way to the content of the actual file.  Directory behaviors, on the other hand, determine
 * how the directory behaves, for example: which types of files should be hidden, which types of files
 * are allowed to count as a directory index file.
 *
 * @package Servedown
 * @author Evan Villemez
 */
class Directory extends File implements \IteratorAggregate, \Countable
{
    private $indexFile;
    private $indexFileName;
    private $behaviors;
    private $containedFiles = array();
    private $loaded = false;

    /**
     * Construct needs the base directory, and optionally some
     * configuration overrides.
     *
     * The constructor also takes care of checking for whether or not
     * there is a directory index file.
     *
     * @param string $basePath Absolute path to root directory
     * @param array  $behavior Optional array of configuration overrides
     */
    public function __construct($info, $behaviors = array())
    {
        if (!$info instanceof \SplFileInfo) {
            $info = new \SplFileInfo($info);
        }

        $this->behaviors = array_merge($this->getDefaultBehaviors(), $behaviors);
        $this->path = $info->getRealPath();
        
        //check for proper index file
        if (!$info->isDir()) {
            if (!$this->getBehavior('allow_index', false)) {
                throw new \InvalidArgumentException(sprintf("This path is not a directory and index files are disallowed."));
            }
            
            if ($this->isPathDirectoryIndex($this->path)) {
                $this->indexFile = new File($this->path);
            } else {
                throw new \InvalidArgumentException("Tried creating a directory on a non-index file.");
            }
        } elseif ($this->getBehavior('allow_index', false) && $path = $this->findDirectoryIndexPath()) {
            $this->indexFile = new File($path);
        }

        //configure self and index file
        if ($this->indexFile) {
            $this->path = dirname($this->indexFile->getPath());
            $this->indexFile->setParent($this);
            $this->indexFile->setIsIndex(true);
            $this->indexFileName = basename($this->indexFile->getPath());
            $this->content = $this->indexFile->getContent();
            $this->setConfig($this->indexFile->getConfig());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        if ($this->indexFile) {
            return $this->indexFile->getConfig();
        } else {
            return parent::getConfig();
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function set($key, $val)
    {
        if(in_array($key, $this->getBehavior('config_cascade_whitelist', array()))) {
            foreach ($this->containedFiles as $file) {
                $file->set($key, $val);
            }
        }
        
        parent::set($key, $val);
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config)
    {
        if ($this->indexFile) {
            $this->indexFile->setConfig($config);
        }
        
        parent::setConfig($config);
    }

    /**
     * Return whether or not this directory contains an index file.
     *
     * @return boolean
     */
    public function hasIndex()
    {
        return $this->indexFile ? true : false;
    }
    
    /**
     * Return instance of index file, if present
     *
     * @return File|false
     */
    public function getIndexFile()
    {
        return $this->indexFile;
    }
    
    /**
     * Get contained file by name.  This will return either a Directory or File
     * instance.
     *
     * @param string $name 
     * @return File|Directory
     */
    public function getFile($name)
    {
        $name = trim($name, '/');
        
        if (!isset($this->containedFiles[$name])) {
            if ($this->indexFileName && basename($name) === $this->indexFileName) {
                return $this->indexFile;
            } else {
                if (!$this->loadFile($name)) {
                    return false;
                }
            }
        }

        return $this->containedFiles[$name];
    }
    
    /**
     * Return whether or not the directory contains the given file.
     *
     * @param string $name 
     * @return boolean
     */
    public function hasFile($name)
    {
        return ($this->loadFile($name)) ? true : false;
    }
    
    protected function findDirectoryIndexPath()
    {
        $base = $this->path.DIRECTORY_SEPARATOR.$this->getBehavior('index_name');
        
        foreach ($this->getBehavior('file_extensions', array()) as $extension) {
            $path = $base.".".$extension;
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return false;
    }
    
    protected function isPathDirectoryIndex($path)
    {
        $fileName = basename($path);
        $indexName = $this->getBehavior('index_name');

        if (false !== strpos($fileName, $indexName)) {
            foreach ($this->getBehavior('file_extensions', array()) as $ext) {
                if ($fileName === $indexName.".".$ext) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get array of contained files and directories.
     *
     * @param  boolean $includeIndex Whether or not to include the directory index file, if present
     * @return array
     */
    public function getFiles($includeIndex = false)
    {
        $this->loadFiles();
        
        $files = $this->containedFiles;
        
        if ($includeIndex) {
            $files[] = $this->indexFile;
        }
        
        return $files;
    }

    protected function loadFiles()
    {
        if (!$this->loaded) {
            $finder = $this->createFinderForDirectory($this->path, false);

            foreach ($finder as $item) {
                $this->loadFile($item->getFilename());
            }

            $this->loaded = true;
        }
    }

    protected function loadFile($name)
    {
        $name = trim($name, '/');

        if (isset($this->containedFiles[$name])) {
            return true;
        }

        $path = $this->path.DIRECTORY_SEPARATOR.$name;
        
        if (!file_exists($path)) {
            return false;
        }
        
        $file = (is_dir($path) || $this->isPathDirectoryIndex($path)) ? new Directory($path, $this->getBehaviors()) : new File($path);
        $this->processConfigForFile($file);
        $file->setParent($this);
        $this->containedFiles[$name] = $file;
        
        return true;
    }

    protected function processConfigForFile(File $file)
    {
        foreach ($this->getBehavior('config_cascade_whitelist', array()) as $key) {
            if (isset($this[$key])) {
                $file->set($key, $this[$key]);
            }
        }
    }
    
    protected function createFinderForDirectory($path, $recursive = false)
    {
        //ignore certain prefixes
        $f1 = new Finder();
        $f1->directories()->in($path)->ignoreDotFiles(true);
        foreach ($this->getBehavior('hidden_file_prefixes', array()) as $prefix) {
            $f1->notName("/^".$prefix."/");
        }
        
        //only get files with allowed extensions, ignoring index file
        $f2 = new Finder();
        $f2->files()->in($path)->ignoreDotFiles(true);
        foreach ($this->getBehavior('file_extensions', array()) as $ext) {
            $f2->name("*.".$ext);
        }
        foreach ($this->getBehavior('hidden_file_prefixes', array()) as $prefix) {
            $f2->notName("/^".$prefix."/");
        }
        if ($this->getBehavior('allow_index', false)) {
            $f2->notName("/".$this->getBehavior('index_name')."/");
        }

        if (!$recursive) {
            $f1->depth("== 0");
            $f2->depth("== 0");
        }
        
        $f1->append($f2);        

        return $f1;
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory()
    {
        return true;
    }
    
    /**
     * Get array of all directory behaviors.
     *
     * @return array
     */
    public function getBehaviors()
    {
        return $this->behaviors;
    }

    /**
     * Set directory behaviors.
     *
     * @param array $data 
     */
    public function setBehaviors(array $data)
    {
        $this->behaviors = $data;
    }

    /**
     * Get value for a specific behavior if present, return a default
     * value otherwise
     *
     * @param string $key 
     * @param mixed $default 
     * @return mixed
     */
    public function getBehavior($key, $default = null)
    {
        return isset($this->behaviors[$key]) ? $this->behaviors[$key] : $default;
    }

    /**
     * Set value for a specific behavior.
     *
     * @param string $key 
     * @param mixed $val 
     */
    public function setBehavior($key, $val)
    {
        $this->behaviors[$key] = $val;
    }
    
    /**
     * @see \IteratorAggregate
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getFiles());
    }

    /**
     * @see \Countable
     */
    public function count()
    {
        $this->loadFiles();

        return count($this->containedFiles);
    }

    protected function getDefaultBehaviors()
    {
        return array(
            'allow_index' => true,
            'hidden_file_prefixes' => array("_"),
            'index_name' => 'index',
            'file_extensions' => array('markdown','md','textile','txt')
        );
    }
    
    /**
     * Get default file finder, configured according to the directory's behaviors.  You can
     * use this to get instances of \SplFileInfo, rather than File.
     *
     * @return \Symfony\Component\Finder\Finder
     */
    public function getFinder($recursive = false)
    {
        return $this->createFinderForDirectory($this->path, $recursive);
    }

}
