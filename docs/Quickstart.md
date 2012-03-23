InfiRest - Quickstart
=====================

Setting up your project
-----------------------

To get started with InfiRest, setup your Zend Framework project, and make the
InfiRest library available in the autoloader path.

For example, if you have the InfiRest library available in the `vendor/InfiRest`
directory in your project root directory, you can use the following in your
`application.ini`:

	; Add the vendor directory to the include paths
	includePaths.vendor		= APPLICATION_PATH "/../vendor"
	; Register the InfiRest_ namespace with the autoloader
	autoloadernamespaces.infirest = "InfiRest_"

Next up, the framework must be told where it can find the Infirest resource
plugin:

	pluginpaths.InfiRest_Application_Resource = APPLICATION_PATH "/../vendor/InfiRest/Application/Resource"

Finally, the actual InfiRest configuration.
Suppose you want your REST interface to live at the `api/v1/` URI of your
application, and want to expose two endpoints at `api/v1/blog-post/` and
`api/v1/user/`. The following lines in `application.ini` will accomplish that:

	resources.infirest.default.baseUrl            = "api/v1"
	resources.infirest.default.endpoints.blogpost = "blog-post"
	resources.infirest.default.endpoints.user     = "user"

The `default` part of the configuration keys determine the name of the REST
interface. For each of the `resources.infirest.[interfaceName].endpoints.[endpointName]`
configuration keys, the actual `[endpointName]` part is ignored, and only the
configuration value (eg. `"blog-post"`) is used.

Creating endpoints
------------------
...