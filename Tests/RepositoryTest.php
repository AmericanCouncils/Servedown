<?php

namespace AC\Servedown\Tests;

use AC\Servedown\File;
use AC\Servedown\Repository;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{

    public function testInstantiate()
    {
        $r = new Repository(__DIR__."/mock_content");
        $this->assertNotNull($r);
        $this->assertTrue($r instanceof Repository);
    }

    public function testDefaultConfig()
    {
        $r = new Repository(__DIR__."/mock_content");
        $expected = array(
            'title' => "Mock Content",
            'allow_directory_index' => true,
            'hidden_directory_prefixes' => array("_"),
            'index_file_name' => 'index',
            'file_extensions' => array('markdown','md','textile','txt')
        );
        $this->assertSame($expected, $r->getConfig());
    }

    public function testConfigOverrides()
    {
        $overrides = array(
            'file_extensions' => array('md')
        );
        $r = new Repository(__DIR__."/mock_content", $overrides);
        $expected = array(
            'title' => "Mock Content",
            'allow_directory_index' => true,
            'hidden_directory_prefixes' => array("_"),
            'index_file_name' => 'index',
            'file_extensions' => array('md')
        );
        $this->assertSame($expected, $r->getConfig());
    }

    public function testGetAndSetConfig()
    {
        $r = new Repository(__DIR__."/mock_content");
        $this->assertTrue($r->get('allow_directory_index'));
        $r->set('allow_directory_index', false);
        $this->assertFalse($r->get('allow_directory_index'));
        $this->assertSame('test', $r->get('foo', 'test'));
    }

    public function testGetFile()
    {
        $r = new Repository(__DIR__."/mock_content");
        $f = $r->getFile('test.md');
        $this->assertNotNull($f);
        $this->assertTrue($f instanceof File);
        $this->assertFalse($f->isDirectory());
        
        $f = $r->getFile('nested/index.md');
        $this->assertTrue($f->isDirectory());
        
    }

    public function testGetFilesInDirectory()
    {
        $r = new Repository(__DIR__."/mock_content");
        $files = $r->getFilesInDirectory('nested/');
        
        $this->assertSame(5, count($files));
        
        foreach ($files as $file) {
            $this->assertTrue($f instanceof File);
            $this->assertTrue(file_exists($file->getPath()));
            if ($file->isDirectory()) {
                $this->assertTrue(is_dir($file->getPath()));
            }
        }
    }
    
    public function testDefaultTitleTransformer()
    {
        $r = new Repository(__DIR__."/mock_content");
        $transformer = $r->getTitleTransformer();
        $this->assertSame("Mock Content", $transformer("mock_content"));
    }
    
    public function testIgnoresHiddenDirectories()
    {
        
    }
    
    public function testIgnoresFileExtensions()
    {
        
    }
    
    public function testCreatesBreadcrumb()
    {
        
    }
    
    public function testCreatesBreadcrumbWithIndexOverride()
    {
        
    }
    
    public function testCreatesBreadcrumbWithAbsoluteUrls()
    {
        
    }
    
    public function testIndexFileIsDirectory()
    {
        
    }

    public function testCascadingConfig()
    {
        $this->assertTrue(file_exists(__DIR__));
    }
}
