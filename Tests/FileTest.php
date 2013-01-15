<?php

namespace AC\Servedown\Tests;

use AC\Servedown\File;

class FileTest extends \PHPUnit_Framework_TestCase
{

    public function testInstantiate()
    {
        $f = new File(__DIR__."/mock_content/test.md");
        $this->assertNotNull($f);
        $this->assertTrue($f instanceof File);
    }

    public function testGetAndSetConfig()
    {
        $f = new File(__DIR__."/mock_content/test.md");
        $this->assertSame(array(), $f->getConfig());
        $defaultTitle = "nothing";
        $this->assertSame($defaultTitle, $f->get('title', $defaultTitle));

        $f = new File(__DIR__."/mock_content/test_with_config.md");
        $this->assertSame("Test File", $f->get('title'));
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

    public function testGetContent()
    {
        $f = new File(__DIR__."/mock_content/test.md");
        $expected = <<<EOF
# Test #

This is test content.
EOF;
        $this->assertSame($expected, $f->getContent());
        $this->assertSame($expected, (string) $f);
    }

    public function testGetRaw()
    {
        $f = new File(__DIR__."/mock_content/test_with_config.md");

        $expected = file_get_contents($f->getPath());
        $this->assertSame($expected, $f->getRaw());
    }

    public function testGetAndSetBreadcrumbData()
    {
        $f = new File(__DIR__."/mock_content/test.md");
        $this->assertSame(array(), $f->getBreadcrumbData());
        $expected = array(
            'title' => "Foo",
            'url' => "Foo",
        );
        $f->setBreadcrumbData($expected);
        $this->assertSame($expected, $f->getBreadcrumbData());
    }

    public function testIsDirectory()
    {
        $f = new File(__DIR__."/mock_content/test.md");
        
        $this->assertFalse($f->isDirectory());
        $f->setIsDirectory(true);
        $this->assertTrue($f->isDirectory());
    }
    
    public function testGetDirectory()
    {
        $f = new File(__DIR__."/mock_content");
        $this->assertTrue($f->isDirectory());
        $this->assertEmpty($f->getContent());
    }
}
