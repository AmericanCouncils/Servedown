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
            'allow_index' => true,
            'hidden_file_prefixes' => array("_"),
            'index_name' => 'index',
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
            'allow_index' => true,
            'hidden_file_prefixes' => array("_"),
            'index_name' => 'index',
            'file_extensions' => array('md')
        );
        $this->assertSame($expected, $d->getBehaviors());
    }

    public function testGetAndSetBehavior()
    {
        $d = new Directory(__DIR__."/mock_content");
        $this->assertTrue($d->getBehavior('allow_index'));
        $d->setBehavior('allow_index', false);
        $this->assertFalse($d->getBehavior('allow_index'));
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
        $this->assertSame("Example Directory", $f->get('title'));
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
    
    public function testGetDirectory()
    {
        $d = new Directory(__DIR__."/mock_content");
        $f = $d->getFile('nested');
        $this->assertTrue($f instanceof Directory);
        $this->assertTrue($f->isDirectory());
    }
    
    public function testGetDirectoryIfIndex()
    {
        $d = new Directory(__DIR__."/mock_content");
        $f = $d->getFile('nested')->getFile('index.md');
        $this->assertTrue($f instanceof File);
        $this->assertTrue($f->isIndex());
    }
    
    public function testGetFinder()
    {
        $d = new Directory(__DIR__."/mock_content");
        $f = $d->getFinder();
        $files = iterator_to_array($f);
        $this->assertSame(3, count($files));
    }

    public function testGetFiles()
    {
        $d = new Directory(__DIR__."/mock_content");
        $files = $d->getFiles();
        $this->assertSame(3, count($files));
        $this->assertFalse($d->hasIndex());
        foreach ($files as $file) {
            $this->assertTrue($file instanceof File);
            $this->assertTrue($file->hasParent());
            $this->assertSame($d, $file->getParent());
            $this->assertFalse($file->isIndex());
        }

        $nested = $d->getFile("nested");
        $this->assertTrue($nested instanceof Directory);
        $this->assertTrue($nested->isDirectory());
        $this->assertTrue($nested->hasIndex());
        $files = $nested->getFiles();

        $this->assertSame(4, count($files));        //HERE: Why does this fail?
        foreach ($files as $file) {
            $this->assertTrue($file instanceof File);
            $this->assertTrue($file->hasParent());
            $this->assertSame($nested, $file->getParent());
        }
        
        $files = $nested->getFiles(true);
        $this->assertSame(5, count($files));
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
        
        $d = $d->getFile('nested');
        $this->assertSame(4, count($d));
    }

    public function testGetParent()
    {
        $d = new Directory(__DIR__."/mock_content");
        foreach ($d as $file) {
            $this->assertSame($d, $file->getParent());
        }

        $nested = $d->getFile('nested');
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

        $d = new Directory(__DIR__."/mock_content", array(
            "allow_index" => false
        ));
        $this->assertFalse($d->hasIndex());
        $nested = $d->getFile('nested');
        $this->assertFalse($nested->hasIndex());
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
    
    public function testHiddenPrefixes()
    {
        $d = new Directory(__DIR__."/mock_content");
        $files = $d->getFiles();
        $this->assertSame(3, count($files));
        
        $d = new Directory(__DIR__."/mock_content", array('hidden_file_prefixes' => array()));
        $files = $d->getFiles();
        $this->assertSame(4, count($files));
    }
    
    public function testConfigCascade()
    {
        //no whitelist
        $d = new Directory(__DIR__."/mock_content");
        $f = $d->getFile('test_with_config.md');
        $this->assertFalse(isset($f['published']));
        $nested = $d->getFile('nested');
        $this->assertFalse($nested->get('published'));
        $f = $nested->getFile('test.md');
        $this->assertTrue($f['published']);
        
        //with whitelist
        $d = new Directory(__DIR__."/mock_content", array(
            'config_cascade_whitelist' => array('published')
        ));
        $f = $d->getFile('test_with_config.md');
        $this->assertFalse(isset($f['published']));
        $d2 = $d->getFile('nested');
        $this->assertFalse($d2['published']);
        $f2 = $d2->getFile('test.md');
        $this->assertFalse($f2['published']);
    }
    
    public function testConfigSetWithCascade()
    {
        $d = new Directory(__DIR__."/mock_content");
        $f1 = $d->getFile('nested')->getFile('test.md');
        $this->assertTrue($f1->get('published'));
        $f2 = $d->getFile('nested')->getFile('more')->getFile('test.md');
        $this->assertTrue($f2->get('published'));
        $d->set('published', false);
        $this->assertTrue($f1->get('published'));
        $this->assertTrue($f2->get('published'));
        
        $d = new Directory(__DIR__."/mock_content", array(
            'config_cascade_whitelist' => array('published')
        ));
        $f = $d->getFile('nested')->getFile('test.md');
        $this->assertFalse($f->get('published'));
        $f2 = $d->getFile('nested')->getFile('more')->getFile('test.md');
        $this->assertFalse($f2->get('published'));
        
        $d = new Directory(__DIR__."/mock_content", array(
            'config_cascade_whitelist' => array('published', 'foo')
        ));
        $f = $d->getFile('nested')->getFile('test.md');
        $this->assertFalse($f->get('published'));
        $this->assertFalse(isset($f['foo']));
        $d->set('published', true);
        $d->set('foo', 'bar');
        $this->assertTrue($f->get('published'));
        $this->assertSame('bar', $f['foo']);
    }

}
