# Servedown #

This is a simple little utility to aid in serving markdown content on the web.  It provides code abstractions
for directories of markdown files, each of which may optionally contain a *YAML* configuration header.

There are only three classes to be aware of: `Repository`, `Directory` and `File`.  A `File` just encapsulates a file on disc,
and allows you to interact with the data in the configuration header (if present) independently of the main
content.  The `Directory` acts as a configurable factory for `File` objects under some base path.  It will take
care of making sure that configuration defined in parent directories cascades down to files.  A `Repository` is also
a directory, but you can request arbitraty paths contained under it, and it will make sure that containing directories
are loaded as needed in order to allow the configuration to properly cascade.

The library **does not** convert the file contents into actual markdown - use whichever tool you prefer for doing that.

## Example Usage ##

Here is some basic example usage.

    <?php
    
    //define repo at root directory of content, with some global configuration
    //that will filter down to all contained pages/directories
    $repo = new AC\Servedown\Repository(__DIR__."/blog", array('base_url' => 'http://example.com/blog), array(
        'file_extensions' => array("md", "markdown", "textile", "txt"),
        'hide_prefixes' => array("_"),
        'config_cascade_whitelist' => array('published'),
        'allow_index' => true,
        'index_name' => 'index',
    ));
    
    //get a specific file
    $item = $repo->getPath("2012/01/example-page.md");
    
    //or a directory
    $dir = $repo->getItem('2012/01);
    foreach ($dir as $item) {
        if ($item->isDirectory()) {
            //load other containing files or something
        }
    }

    //get breadcrumb info, ideally to be used in a template
    $breadcrumbData = $repo->getBreadcrumbData($item);
    foreach ($breadcrumbData as $item) {
        $content .= sprintf("<a href='%s'>%s</a>", $item['url'], $item['title']);
    }
    
    //get some optional metadata, can contain whatever values you want
    //to keep track of on a per-file basis
    $title = $item->get('title', "Default Page Title");
    
    //use some markdown parser to parse the page contents
    $html = $yourFavoriteMarkdownParser->parse($item);

If you are using the library to expose the content to the web, note that you will need to make the directory
web-accesible if you want to images to work properly.  Or, you can write more code, or implement custom server configs.

### Repository Configuration ###

Each repository has various options that can alter how the repository behaves, and what it is allowed to do.  These
options and their values can be passed in the constructor for the repository.

* `base_url` - If provided, breadcrumbs will contain absolute urls to the referenced files.

> Note that when requesting breadcrumb data for a contained item, the Repository will automatically look for a `title` config for each file.  If not present, it will translate the filepath into a more human-readable format.

### Directory Configuration ###

Directories can be configured as well.  When a directory loads a containing directory, it's directory behaviors cascade
down to child directories.  These are the available options:

* `file_extensions` - Array of file extensions considered to be valid.  Files with extensions not in the list are ignored. **Default**: `array('md','markdown','txt','textile')`
* `hide_prefixes` - Array of prefixes to ignore.  Any files *and* directories that begin with the given prefix will be ignored. **Default**: `array('_')`
* `config_cascade_whitelist` - Array of configuraiton keys which, if present in a directory index file, will cascade into all children. **Default**: `empty`
* `allow_index` - If true, this will allow files to act as index files for a directory, which means that directory level files may contain content like any other page, and potentially configuration that will cascade down to other files contained in the same directory. **Default**: `true`
* `index_name` - The name of the file that should act as the index.  The extension can be any valid extension from the `file_extensions` config.  **Default**: `index`

## Example Content ##

Here a few examples of content and configuration.

### Example Page ###

Each page can contain a header of metadata in *YAML* format.  Here's an example markdown page with configuration.

    ````
    title: An Example Page
    published: true
    ````
    # Hello World #
    
    This is your typical markdown content.

### Example Directory Override ###

You can also configure directories by specifying an `index` file.  For example, this could be specified in an `index.md`, and all files
contained in that, and contained directories, would return `false` from `$page->get('published');`.  The directories don't need to specify
any actual content, though you can if you wish.

    ````
    published: false
    ````
    
## Potential TODOs ##

* hard vs soft config cascading