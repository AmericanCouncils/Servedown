# Servedown #

This is a simple little utility to aid in serving markdown content on the web.  It provides code abstractions
for directories of markdown files, each of which may optionally contain a *YAML* configuration header.

There are only two classes to be aware of: `Repository` and `File`.  A `File` just encapsulates a file on disc,
and allows you to interact with the data in the configuration header (if present) independently of the main
content.  The `Repository` acts as a configurable factory for `File` objects under some base path.  It will take
care of making sure that configuration defined in parent directories cascades down to files.

The library **does not** convert the file contents into actual markdown - use whichever tool you prefer for doing that.

## Example Usage ##

Here is some basic example usage.

    <?php
    
    //define repo at root directory of content, with some global configuration
    //that will filter down to all contained pages/directories
    $repo = new AC\Servedown\Repository(__DIR__."/blog", 'http://example.com/blog', array(
        'file_extensions' => array("md", "markdown", "textile", "txt"),
        'hide_prefixes' => array("_"),
        'cascade_config' => true,
        'allow_index' => true,
        'index_file_name' => 'index',
    ));
    
    //get a specific file
    $file = $repo->getPath("2012/using-servedown.md");
    
    //get directory
    $files = $repo->getFilesInPath("2012/");
    
    //get breadcrumb info
    $breadcrumbData = $file->getBreadcrumbData();
    foreach ($breadcrumbData as $title => $url) {
        //use this in a template or something to build navigation
    }
    
    //get some optional metadata, can contain whatever values you want
    //to keep track of on a per-file basis
    $title = $page->get('title', "Default Page Title");
    
    //use some markdown parser to parse the page contents
    $html = $yourFavoriteMarkdownParser->parse($page);

If you are using the library to expose the content to the web, note that you will need to make the directory
web-accesible if you want to images to work properly.  Or, you can write more code, or implement custom server configs.

### Repository Configuration ###

Each repository has several options that can alter how the repository behaves, and what it is allowed to do.  These
options and their values can be passed in the constructor for the repository.

    //TODO

* **foo** - 
* **foo** - 
* **foo** - 
* **foo** - 

### Individual File Usage ###

You can use files apart from the repository construct, but they will not be able
to inherit configuration from containing directories as they would in the examples above.

    <?php
    
    $page = new AC\Servedown\File(__DIR__."/blog/2012/using-servedown.md");
    $title = $page->get('title', "Default Title");
    
    //will return an empty array, because it is not aware
    //of it's containing directories
    $breadcrumbData = $page->getBreadcrumbData();

## Example Content ##

Here a few examples of content and configuration.

### Example Page ###



### Example Directory Override ###

You can also configure directories by specifying an `index` file.