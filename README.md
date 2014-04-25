Micro
=====

PHP micro-framework using Phpf components. 

####Goals

 * Be ridiculously easy to understand and use
 * Excellent baseline performance
 * Minimal dependencies

####Requirements

 * PHP 5.3+
 * PDO extension
 * Phpf/HttpUtil
 * wells5609/PHP-Util

###Overview

"Micro" (working title) is a minimal framework for small yet potentially complex web applications. PHP doesn't exactly need another framework, "micro" or otherwise, yet I found the currently available options too limited or prescriptive.

####Philosophy
The main points:
 * _Write PHP like PHP_ - a framework should not feel like another language
 * _Short_ - a bigger codebase does not help you write better code
 * _Simple_ - developers should spend their time developing, not learning a framework

###Getting Started

Like most frameworks, you'll need the appropriate rewrite rules for your server so that all requests are routed to one file (e.g. `index.php`).

####1. Config

With the `.htaccess` or `web.config` or what-have-you, you'll need an application bootstrap. Bootstrapping starts with a user configuration file, which must return an associative array. 

Pass the file path to the `Phpf\App::configure()` method, and it returns an array with default values for missing keys filled in. Do some stuff with the config array (e.g. create some constants, etc.) or just carry on. 

```php
// configuration settings
$conf = \Phpf\App::configure(__DIR__.'/user_config.php');
```

####2. Create app

Next, spawn the app by passing the array to the `Phpf\App::createFromArray()` method:

```php
$app = \Phpf\App::createFromArray($conf);
```

The returned object, `$app`, is an instance of `Phpf\App`. At this point, the object has the following properties set, which were gathered from the configuration array:
 1. **`id`** (string) - ID of the application instance (`Phpf\App` is a multiton).
 2. **`namespace`** (string) - Namespace for application resources (e.g. models, controlllers, etc.). You create all these classes.
 3. **`components['env']`** (`Phpf\Common\Env`) - Object holding environment information, including charset, timezone, and debug settings. This information is used to set error reporting, the default date timezone, and mbstring extension encodings, if enabled. It also creates full file paths (and by default, constants using the directory name) from any directories listed in the configuration array under key `dirs`.
 4. **`components['aliaser']`** (`Phpf\Common\ClassAliaser`) - Object used to lazily create class aliases. The aliases are created from a nested array in the configuration array (key `aliases`) of alias / real class (fully-resolved) name.
 5. **`components['config']`** (Default: `Phpf\Config`) - Object holding the application configuration settings. By default, the object is an instance of `Phpf\Config`, but this can be changed by setting a class alias `Config`.
 6. A PSR-0 autoloader is created for the application namespace with the base path set to 1 level above the path given by the `dirs['app']` configuration setting.


####3. Set Components
To set components, use the app object's `set()` method, where the first parameter is the name, and the second parameter is the object or class name (string) - if given a class, the component is interpreted as a singleton and will be returned using `$class::instance()` (hence, it must implement the `instance()` method statically to return the object instance). Otherwise, the component is simply stored as an object.

######The Cache!
The above holds true for all components except the cache. The cache requires a driver (one for XCache comes pre-packaged), which the user can provide by setting the configuration item `cache['driver-class']` (e.g. `$config['cache']['driver-class'] = 'My_SomeCacheEngine_Driver'`). The driver is instantiated when the cache is started using `$app->startCache()`. If you'd like to use your own (singleton) cache instead of `Phpf\Cache`, pass the string name (or just use an alias `Cache`) to the `startCache()` method.

Use the aliases to instantiate component object (see `Phpf\App::configure()` for a listing of default component aliases). This allows for easy swapping of component classes without changing a bunch of files.

A component-loading procedure might look this:

```php
$app->startCache('Cache'); // string => singleton ('Cache' is an alias for 'Phpf\Cache').

$app->set('session', $session = new \Session);
$session->setDriver(new \Phpf\Session\Driver\Native)->start(); // a handler for native PHP sessions is pre-packaged

$app->set('events', $events = new \Events);

$app->set('router', new \Router($events)); // Router uses Events to call pre- and post-dispatch actions

$app->set('filesystem', $filesystem = new \Filesystem(APP)); // APP was set by Env via 'app' dir in config

$app->set('packageManager', $pkgMgr = new \Phpf\PackageManager($app));
$pkgMgr->init(); // loads packages specified in user config

$app->set('request', $request = \Request::createFromGlobals());
$request->setSession($session);

$app->set('response', $response = new \Response);
$response->setRequest($request); // Response uses request to negotiate content type

$app->set('viewManager', $viewMgr = new \Phpf\ViewManager($filesystem)); // ViewManager uses filesystem to locate views
$viewMgr->setEvents($events); // ViewManager uses events to call actions before rending views.

// [Optional] add filesystem directories
// 'VIEWS' is set in Env
// 'views' is group name, referenced when finding files
// '2' is recursion depth to use when searching for files in the directory.
$filesystem->add(VIEWS, 'views', 2);
```

####4. Set up
Now you'll need to include some files to operate on the components (for example, adding routes, setting database table schemas, etc.). For now, refer to each component's documentation.

####5. Route
After including these files, dispatch the request:

```php
$app->router->dispatch($app['request'], $app['response']);
```

You may notice that the application components can be accessed using object (`->`) or array (`[]`) syntax. You can also use the `get()` method, which will return a component of the name given, if it exists.

When the router finds a matching route, it instantiates the route controller and provides the controller with the Request and Response objects (and any others you add via `dispatch` events) as properties ('request' and 'response', respectively). It then calls the route controller's callback method, in which it populates the response body.

With the response populated, we simply send it to the user:
```php
$app->response->send();
```

And that's it.
