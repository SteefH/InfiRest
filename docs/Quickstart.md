InfiRest - Quickstart
=====================

InfiRest is primarily developed for making it easy to expose Doctrine 2 ORM
Entities through a REST interface, so this quickstart document will focus on
that. 

Setting up your project
-----------------------

To get started with InfiRest, setup your Zend Framework project, and make the
InfiRest library available in the autoloader path.

For example, if you have the InfiRest and InfiRestDoctrine libraries available
in the `vendor/InfiRest` and `vendor/InfiRestDoctrine` directories in your
project root directory, you can use the following in your `application.ini`:

```ini
; Add the vendor directory to the include paths
includePaths.vendor                   = APPLICATION_PATH "/../vendor"
; Register the InfiRest_ and InfiRestDoctrine_ namespaces with the Zend
; Framework autoloader
autoloadernamespaces.infirest         = "InfiRest_"
autoloadernamespaces.infirestdoctrine = "InfiRestDoctrine_"
```

Next up, the framework must be told where it can find the Infirest resource
plugin:

```ini
; parent directory of InfiRest_Application_Resource_Infirest
pluginpaths.InfiRest_Application_Resource = APPLICATION_PATH "/../vendor/InfiRest/Application/Resource"
```

Finally, the actual InfiRest configuration. Suppose you want your REST interface
to live at the `api/v1/` URI of your application, and want to expose two
endpoints at `api/v1/blog-post/` and `api/v1/user/`. The following lines in
`application.ini` will accomplish that:

```ini
resources.infirest.default.baseUrl            = "api/v1"
resources.infirest.default.endpoints.blogpost = "blog-post"
resources.infirest.default.endpoints.user     = "user"
```

The `default` part of the configuration keys determine the name of the REST
interface. A REST interface is tied to a module in your ZF application, in this
case it's the default module.

For each of the `resources.infirest.[interfaceName].endpoints.[endpointName]`
configuration keys, the actual `[endpointName]` part is ignored, and only the
configuration value (eg. `"blog-post"`) is used.

Creating endpoints
------------------

The InfiRest endpoint naming convention closely follows that of the controller
classes of your Zend Framework application, with the difference that "Endpoint"
is used for file and class names instead of "Controller". In our example, the
endpoint named "blog-post" refers to an endpoint class named `BlogPostEndpoint`,
and that class resides in the file `application/endpoints/BlogPostEndpoint.php`.

In the module directory of the module with the same name as your REST interface,
create a directory `endpoints`. So with the configuration above, this directory
would be `application/endpoints`.

Now, for each endpoint you have added to the REST interface at 
`resources.infirest.default.endpoints`, you can create an endpoint class, like
so:

```php
<?php /* application/endpoints/BlogPostEndpoint.php */

class BlogPostEndPoint
extends InfiRestDoctrine_Endpoint {
	/**
	 * The Doctrine 2 Entity class this endpoint exposes
	 */
	protected $_objectClass = 'ExampleApp\Orm\Entities\BlogPost';
	
	/**
	 * Get a reference to the \Doctrine\ORM\EntityManager that manages
	 * entities of the class in $_objectClass
	 */
	public function getEntityManager() {
		$entityManager = ... // eg. get it from the Zend_Registry
		return $entityManager;
	}

	/**
	 * Get an array of fields that should be excluded by the endpoint.
	 * 
	 * For instance, hiding password fields in your API is a pretty sane
	 * thing to do...
	 */
	protected function _getExcludes() {
		// No fields are excluded by this endpoint
		return array(); 
	}
}
```

```php
<?php /* application/endpoints/UserEndpoint.php */

class UserEndpoint
extends InfiRestDoctrine_Endpoint {
	/**
	 * The Doctrine 2 Entity class this endpoint exposes
	 */
	protected $_objectClass = 'ExampleApp\Orm\Entities\User';

	/**
	 * Get a reference to the \Doctrine\ORM\EntityManager that manages
	 * entities of the class in $_objectClass
	 */
	public function getEntityManager() {
		$entityManager = ... // eg. get it from the Zend_Registry
		return $entityManager;
	}

}
```

To keep your code [DRY][], you could place the `getEntityManager()` method in
a base class that is used by all of your endpoints, and make your endpoints
subclasses of that base class. For instance:

```php
<?php

class BaseEndpoint
extends InfiRestDoctrine_Endpoint {

	/**
	 * Get a reference to the \Doctrine\ORM\EntityManager
	 */
	public function getEntityManager() {
		$entityManager = ... // eg. get it from the Zend_Registry
		return $entityManager;
	}

}
```

```php
class UserEndpoint extends BaseEndpoint {
	/**
	 * The Doctrine 2 Entity class this endpoint exposes
	 */
	protected $_objectClass = 'ExampleApp\Orm\Entities\User';

}
```

```php
class BlogPostEndPoint extends BaseEndpoint {
	/**
	 * The Doctrine 2 Entity class this endpoint exposes
	 */
	protected $_objectClass = 'ExampleApp\Orm\Entities\BlogPost';

}
```

[DRY]: http://en.wikipedia.org/wiki/DRY "Don't Repeat Yourself"