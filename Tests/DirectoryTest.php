<?php

namespace AC\Servedown\Tests;

use AC\Servedown\File;
use AC\Servedown\Directory;

class DirectoryTest extends \PHPUnit_Framework_TestCase
{

    public function testInstantiate()
    {
        $d = new Directory(__DIR__."/mock_content");
        $this->assertNotNull($d);
        $this->assertTrue($d instanceof Directory);
    }

    public function testDefaultBehaviors()
    {
        $d = new Directory(__DIR__."/mock_content");
        $expected = array(
            'allow_directory_index' => true,
            'hidden_directory_prefixes' => array("_"),
            'index_file_name' => 'index',
            'file_extensions' => array('markdown','md','textile','txt')
        );
        $this->assertSame($expected, $d->getBehaviors());
    }

    public function testConfigBehaviors()
    {
        $overrides = array(
            'file_extensions' => array('md')
        );
        $d = new Directory(__DIR__."/mock_content", $overrides);
        $expected = array(
            'allow_directory_index' => true,
            'hidden_directory_prefixes' => array("_"),
            'index_file_name' => 'index',
            'file_extensions' => array('md')
        );
        $this->assertSame($expected, $d->getBehaviors());
    }

    public function testGetAndSetBehavior()
    {
        $d = new Directory(__DIR__."/mock_content");
        $this->assertTrue($d->getBehavior('allow_directory_index'));
        $d->setBehavior('allow_directory_index', false);
        $this->assertFalse($d->getBehavior('allow_directory_index'));
        $this->assertSame('test', $d->getBehavior('foo', 'test'));
    }

    public function testGetPath()
    {
        $d1 = new Directory(__DIR__."/mock_content/nested");
        $d2 = new Directory(__DIR__."/mock_content/nested/index.md");
        $this->assertSame($d1->getPath(), $d2->getPath());
    }

    public function testExceptionOnInvalidIndex()
    {
        $this->setExpectedException("InvalidArgumentException");
        $d = new Directory(__DIR__."/mock_content/nested/test.md");
    }

    public function testGetConfig()
    {
        $d = new Directory(__DIR__."/mock_content");
        $expected = "Default Title";
        $this->assertSame($expected, $d->get('title', $expected));

        $d = new Directory(__DIR__."/mock_content/nested");
        $expected = "Example Directory";
        $this->assertSame($expected, $d->get('title', false));
    }

    public function testOverridableIndexBehavior()
    {
        $d = new Directory(__DIR__."/mock_content/nested", array('allow_index' => false));
        $expected = "Default Title";
        $this->assertSame($expected, $d->get('title', $expected));
        $this->assertFalse($d->hasIndex());
    }

    public function testGetConfigWithIndex()
    {
        $d = new Directory(__DIR__."/mock_content/nested");
        $this->assertSame("Example Directory", $d->get('title', "Default Title"));
        $d = new Directory(__DIR__."/mock_content/nested/index.md");
        $this->assertSame("Example Directory", $d->get('title', "Default Title"));
    }

    public function testGetContentWithIndex()
    {
        $d1 = new Directory(__DIR__."/mock_content");
        $this->assertNull($d1->getContent());

        $d2 = new Directory(__DIR__."/mock_content/nested");

        $expected =
<<<END
# Content #

This directory has content!
END;

        $this->assertSame($expected, $d2->getContent());
    }

    public function testGetAndSetConfig()
    {
        $f = new Directory(__DIR__."/mock_content/");
        $this->assertSame(array(), $f->getConfig());
        $defaultTitle = "nothing";
        $this->assertSame($defaultTitle, $f->get('title', $defaultTitle));

        $f = new Directory(__DIR__."/mock_content/nested");
        $this->assertTrue($f->get('title'));
        $f->set('title', "changed");
        $this->assertSame('changed', $f->get('title'));
        $this->assertFalse($f->get('foo', false));

        $f->setConfig(array(
            'title' => 'changed again',
            'foo' => true
        ));
        $this->assertSame('changed again', $f->get('title'));
        $this->assertTrue($f->get('foo', false));
    }

    public function testGetFile()
    {
        $d = new Directory(__DIR__."/mock_content");
        $f = $d->getFile('test.md');
        $this->assertNotNull($f);
        $this->assertTrue($f instanceof File);
        $this->assertFalse($f->isDirectory());
        $this->assertFalse($f->isIndex());
    }

    public function testGetFiles()
    {
        $d = new Directory(__DIR__."/mock_content");
        $files = $d->getFiles();
        $this->assertSame(3, count($files));
        $this->assertFalse($d->hasIndex());
        foreach ($files as $file) {
            $this->assertTrue($f instanceof File);
            $this->assertTrue($f->hasParent());
            $this->assertSame($d, $f->getParent());
            $this->assertFalse($f->isIndex());
        }

        $nested = $d->getFile("nested");
        $this->assertTrue($d instanceof Directory);
        $this->assertTrue($d->isDirectory());
        $this->assertTrue($d->hasIndex());
        $files = $nested->getFiles(true);
        $this->assertSame(5, count($files));
        foreach ($files as $file) {
            $this->assertTrue($f instanceof File);
            $this->assertTrue($f->hasParent());
            $this->assertSame($nested, $f->getParent());
        }
        $f = $nested->getFile('index.md');
        $this->assertTrue($f->isIndex());
    }

    public function testIterate()
    {
        $d = new Directory(__DIR__."/mock_content");
        foreach ($d as $file) {
            $this->assertTrue($file instanceof File);
        }
    }

    public function testCountable()
    {
        $d = new Directory(__DIR__."/mock_content");
        $this->assertSame(3, count($d));
    }

    public function testGetParent()
    {
        $d = new Directory(__DIR__."/mock_content");
        foreach ($d as $file) {
            $this->assertSame($d, $file->getParent());
        }

        $nested = $d->get('nested');
        foreach ($nested as $file) {
            $this->assertSame($nested, $file->getParent());
        }
    }

    public function testHasIndex()
    {
        $d = new Directory(__DIR__."/mock_content");
        $this->assertFalse($d->hasIndex());
        $nested = $d->getFile('nested');
        $this->assertTrue($nested->hasIndex());
    }

    public function testIsIndex()
    {
        $d = new Directory(__DIR__."/mock_content");
        foreach ($d as $file) {
            $this->assertFalse($file->isIndex());
        }
        $nested = $d->getFile('nested');
        foreach ($nested as $file) {
            if (false !== strpos($file->getPath(), 'index.md')) {
                $this->assertTrue($file->isIndex());
            } else {
                $this->assertFalse($file->isIndex());
            }
        }
    }
    
    //start here
    public function testConfigCascade()
    {
        $d = new Directory(__DIR__."/mock_content");
//        $f = 
        //WITHOUT Whitelist
        //test config in contained directory
        //test config in nested directory
        //test setting cascading configs

        //WITH Whitelist
        //test config in contained directory
        //test config in nested directory
        //test setting cascading configs
    }

}
