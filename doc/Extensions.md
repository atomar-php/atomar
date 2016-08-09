Extensions
---
Extensions are a powerful part of Atomar that allow developers to easily reuse code between projects. Atomar also comes with an extension wizard that makes creating new extensions a breeze.

### REST APIs (processes)

URLs of the form `example.com/!/[extension_name]/[process_name]` are reserved for extension APIs. This enables an extension to set up it's own processes. These urls differ from the urls for [System APIs](/admin/documentation/core/APIs) which are of the form `example.com/!/[process_name]`. 

####Creating an API
**Quick Note:** If you use the extension wizard a REST API is generated for you.

In order to comply with this standard you will need to add an extra entry in your `hook_url()` function. The url should be formated as described above and be prepared to collect the process name to execute as well as any url parameters using regular expressions.

 Here is an example: `/!/my\_extension/(?P<api>[a-zA-Z\_-]+)/?(\?.*)?`

You will notice in the example above that we use a combination of named and unamed capture groups. The named one in this example is "api". Here is a complete example

    function my_extension_url() {
      return array(
        ...
        '/!/my_extension/(?P<api>[a-zA-Z\_-]+)/?(\?.*)?'=>'CMyExtensionAPI',
        ...
      );
    }

Then you will need to ensure the class `CMyExtensionAPI` which extends the `ApiController `exists in `includes/extensions/my_extension/controllers/CMyExtensionAPI.php`

### PHP APIs

Extensions can register a library by implimenting the `hook_libraries()` hook. By returning an array of relative paths you can give third party extensions access to functionality of your extension (assuming you package away functionality in the API). This allows the development of more advanced features which are not suited for REST API integration.

It is completely resonable to create a extension that only provides an API without any additional pages or menus (though you should still always include some documentation). If your extension is just providing an API you are encourage (as with all extensions) to name it appropriately so that third party developers can easily discern what the extension is.

### Supporting other extensions

It is important to make sure your extensions can be easily used by other extensions. For this reason we highly recommend setting up an API in your extension for basic CRUD operations. This will allow third party extensions to impliment it's features without having to understand how things work underneath.

Please be sure to document your API well so that other extension developers can easily understand how to use it.


###Migration
Extensions can be easily migrated by updating the extension install script.

>TODO: explain migration.

###Routing

>TODO:: explain routing