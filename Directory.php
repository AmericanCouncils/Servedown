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
            $this->setConfig($this->indexFile->getConfig());
        }
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
        return $this->indexFile ? true : false;
    }

    public function getIndexFile()
    {
        return $this->indexFile;
    }

    public function getFile($name)
    {
        $name = rtrim("/", $name);
        
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
    
    public function hasFile($name)
    {
        return ($this->loadFile($name)) ? true : false;
    }

    protected function loadFile($name)
    {
        $name = rtrim('/', $name);
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
            $paths = array();
            $files = array();

            $finder = $this->createFinderForDirectory($this->path);
            foreach (iterator_to_array($finder) as $item) {
                $this->loadFile($item);
            }

            $this->containedFiles = $files;
            $this->loaded = true;
        }
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

    public function getIterator()
    {
        return new \ArrayIterator($this->getFiles());
    }

    public function count()
    {
        $this->loadFiles();

        return count($this->files);
    }

    protected function getDefaultBehaviors()
    {
        return array(
            'allow_index' => true,
            'hidden_directory_prefixes' => array("_"),
            'index_name' => 'index',
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

}
