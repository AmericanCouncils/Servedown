<?php

namespace AC\Servedown\Tests;

use AC\Servedown\Repository;
use AC\Servedown\Directory;
use AC\Servedown\File;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{

    public function testInstantiate()
    {
        $r = new Repository(__DIR__."/mock_content");
        $this->assertNotNull($r);
        $this->assertTrue($r instanceof Repository);
    }
    
    public function testGetRelativePath()
    {
        $r = new Repository(__DIR__."/mock_content");
        $expected = "nested";
        $this->assertSame($expected, $r->getRelativePath('nested'));
        $this->assertSame($expected, $r->getRelativePath(__DIR__.'/mock_content/nested'));
        $this->assertSame($expected, $r->getRelativePath(__DIR__.'/mock_content/nested/more/../'));
        $this->assertSame($expected, $r->getRelativePath('nested/more/../'));
    }
	
	public function testGetItem()
    {
        $r = new Repository(__DIR__."/mock_content");
        $f = $r->getItem('nested/test.md');
        $this->assertTrue($f instanceof File);
        $this->assertTrue($f['published']);
        
        $r = new Repository(__DIR__."/mock_content", array(), array(
            'config_cascade_whitelist' => array('published')
        ));
        $f = $r->getItem('nested/test.md');
        $this->assertTrue($f instanceof File);
        $this->assertFalse($f['published']);
        
        $r = new Repository(__DIR__."/mock_content");
        $f = $r->getItem('nested/more/');
        $this->assertTrue($f instanceof Directory);
        $this->assertFalse($f->hasIndex());
        
        $r = new Repository(__DIR__."/mock_content");
        $f = $r->getItem('nested/index.md');
        $this->assertTrue($f instanceof File);
        $this->assertTrue($f->isIndex());
        
        $r = new Repository(__DIR__."/mock_content");
        $f = $r->getItem('nested');
        $this->assertTrue($f instanceof Directory);
        $this->assertTrue($f->hasIndex());
        
        $r = new Repository(__DIR__."/mock_content");
        $f = $r->getItem('nested/more/../..');
        $this->assertTrue($f instanceof Directory);
        $this->assertFalse($f->hasIndex());
    }

    public function testAsDirectory()
    {
        $r = new Repository(__DIR__."/mock_content/nested");
        $this->assertTrue($r->hasIndex());
        
    }

    public function testGetBreadcrumb()
    {
        $r = new Repository(__DIR__."/mock_content", array(
            'base_url' => 'http://localhost/foo'
        ));
        
        $f = $r->getItem('nested/test.md');
        $expected = array(
            array(
                'title' => "Mock Content",
                'url' => 'http://localhost/foo/',
            ),
            array(
                'title' => "Example Directory",
                'url' => 'http://localhost/foo/nested/'
            ),
            array(
                'title' => 'Test',
                'url' => 'http://localhost/foo/nested/test.md'
            )
        );
        $this->assertSame($expected, $r->getBreadcrumbsForItem($f->getPath()));
        
        $f = $r->getItem('nested/more/test.md');
        $expected = array(
            array(
                'title' => "Mock Content",
                'url' => 'http://localhost/foo/',
            ),
            array(
                'title' => "Example Directory",
                'url' => 'http://localhost/foo/nested/'
            ),
            array(
                'title' => 'More',
                'url' => 'http://localhost/foo/nested/more/'
            ),
            array(
                'title' => 'Test',
                'url' => 'http://localhost/foo/nested/more/test.md'
            )
        );
        $this->assertSame($expected, $r->getBreadcrumbsForItem($f->getPath()));
        
        //same assertion, retrieved from absolute path
        $f = $r->getItem(__DIR__."/mock_content/nested/more/test.md");
        $this->assertSame($expected, $r->getBreadcrumbsForItem($f->getPath()));
        
        //same assertions with no base url
        $r = new Repository(__DIR__."/mock_content");
        $f = $r->getItem('nested/more/test.md');
        $expected = array(
            array(
                'title' => "Mock Content",
                'url' => '',
            ),
            array(
                'title' => "Example Directory",
                'url' => 'nested/'
            ),
            array(
                'title' => 'More',
                'url' => 'nested/more/'
            ),
            array(
                'title' => 'Test',
                'url' => 'nested/more/test.md'
            )
        );
        $this->assertSame($expected, $r->getBreadcrumbsForItem($f));
    }

}
