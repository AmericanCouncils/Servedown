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
    
    public function testGetDefaultTitle()
    {
        $r = new Repository(__DIR__."/mock_content");
        $this->assertSame("Mock Content", $r->getDefaultTitle(__DIR__."/mock_content.md"));
        $this->assertSame("Mock Content", $r->getDefaultTitle(__DIR__."/mock_content"));
        $this->assertSame("Test Path", $r->getDefaultTitle(__DIR__."/test.path.md"));
        $this->assertSame("Testpath Again", $r->getDefaultTitle(__DIR__."/testPath_again.md"));
    }
    
    public function testIgnoresHiddenDirectories()
    {
        $r = new Repository(__DIR__."/mock_content");
        foreach ($r->getFilesInDirectory(__DIR__."/mock_content") as $file) {
            $this->assertTrue(false === strpos($file->getPath(), "_hidden"));
        }
    }
    
    public function testIgnoresFileExtensions()
    {
        $r = new Repository(__DIR__."/mock_content");
        foreach ($r->getFilesInDirectory(__DIR__."/mock_content") as $file) {
            $this->assertTrue(false === strpos($file->getPath(), ".rtf"));
        }
    }
    
    public function testIgnoresIndexFile()
    {
        $r = new Repository(__DIR__."/mock_content");
        $files = $r->getFilesInDirectory('nested');
        $this->assertSame(4, count($files));
        foreach ($r->getFilesInDirectory(__DIR__."/mock_content") as $file) {
            $this->assertTrue(false === strpos($file->getPath(), "index"));
        }
    }
        
    public function testIndexFileIsDirectory()
    {
        $r = new Repository(__DIR__."/mock_content");
        
        $f1 = $r->getFile('nested/index.md');
        $this->assertTrue($f1->isDirectory());
        $this->assertTrue($f1->isIndex());
        
        $f2 = $r->getFile('nested');
        $this->assertTrue($f2->isDirectory());
        $this->assertTrue($f2->isIndex());
        
        $this->assertEquals($f1, $f2);

        $f3 = $r->getfile('nested/more');
        $this->assertTrue($f3->isDirectory());
        $this->assertFalse($f3->isIndex());

    }

    public function testConfigCascadesFromDirectoryIndexFile()
    {
        $r = new Repository(__DIR__."/mock_content");
//        $f = START HERE
    }
    
    public function testConfigCascadesFromContainedDirectories()
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
}
