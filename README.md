Micro
=====

PHP micro-framework using Phpf components. 

####Goals

 * Be ridiculously easy to understand and use
 * Minimize dependencies
 * Excellent "out-of-the-box" performance

####Requirements

 * PHP 5.3+
 * PDO extension
 * Phpf/HttpUtil
 * wells5609/PHP-Util

###Overview

Micro is a minimal "full-stack" framework for small yet potentially complex web applications. While PHP certainly doesn't need another framework ("micro" or otherwise), I found the options currently available either too functionally limited or too architecturally prescriptive.

You don't need a degree in software engineering to understand Micro (or any degree, for that matter). The names used for classes, methods, functions, variables, and constants are meant to clearly convey their meaning, so you won't find any obscure `iterateSubInvokable()` or any such nonsense.


###Getting Started

Micro needs the appropriate rewrite rules for your server so that all requests are routed to one file (e.g. `index.php`), just like most others.

####Bootstrapping

Once you've created the `.htaccess` or `web.config` or what-have-you, you'll need to create an application bootstrap. Bootstrapping in Micro starts with a user configuration file which returns an associative array. Pass the file path to the `Phpf\App::configure()` method, and it returns an array with default values for missing keys filled in. You can do some stuff with the config array (e.g. create some constants, etc.) or just carry on. 

```php
// configuration settings
$conf = \Phpf\App::configure(__DIR__.'/user_config.php');
```

Next, we'll spawn the app. Simply pass the configuration array to the `Phpf\App::createFromArray()` method like so:

```php
$app = \Phpf\App::createFromArray($conf);
```

The returned object, `$app`, is an instance of `Phpf\App`. At this point, the object has the following properties set, which were gathered from the configuration array:
 1. `id` (string) - ID of the application instance (`Phpf\App` is a multiton).
 2. `namespace` (string) - Namespace for application resources (e.g. models, controlllers, etc.). You create all these classes.
 3. `components['env']` (`Phpf\Common\Env`) - Object holding environment information, including charset, timezone, and debug settings. This information is used to set error reporting, the default date timezone, and mbstring extension encodings, if enabled. It also creates full file paths (and by default, constants using the directory name) from any directories listed in the configuration array under key `dirs`.
 4. `components['aliaser']` (`Phpf\Common\ClassAliaser`) - Object used to lazily create class aliases. This means you can declare a bunch of class aliases, but the files won't be loaded until needed (unlike using `class_alias()`). The aliases are created from a nested array in the configuration array (key `aliases`), where for each alias, the real (fully-resolved) class name is the value, and the alias is the key.
 5. `components['config']` (Default: `Phpf\Config`) - Object holding the application configuration settings. By default, the object is an instance of `Phpf\Config`, but this can be changed by setting a class alias `Config`.
 6. An autoloader is created for the application namespace, following the PSR-0 convention, with the base path set to 1 level above the path given by the `dirs['app']` configuration setting.

Now we need to populate the application object with components. To set components, use the app object's `set()` method, where the first parameter is the name, and the second parameter is the object or class name (string) - if given a class, the component is interpreted as a singleton and will be returned using `$class::instance()` (hence, it must implement the `instance()` method statically to return the object instance). Otherwise, the component is simply stored as an object.

The above holds true for all components except the cache. The cache requires a driver (one for XCache comes pre-packaged), which the user can provide by setting the configuration item `cache['driver-class']` (e.g. `$config['cache']['driver-class'] = 'My_SomeCacheEngine_Driver'`). The driver is instantiated when the cache is started using `$app->startCache()`. If you'd like to use your own (singleton) cache instead of `Phpf\Cache`, pass the string name (or just use an alias `Cache`!) to the `startCache()` method (e.g. `$app->startCache('My_Cache')`).

Use the aliases to instantiate component object (see `Phpf\App::configure()` for a listing of default component aliases). This allows for easy swapping of component classes without changing a bunch of files.

A component-loading procedure might look this:

```php
// in some file using $app...

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
