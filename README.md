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
 4. `components['aliaser']` (`Phpf\Common\ClassAliaser`) - Object used to lazily create class aliases. This means you can declare a bunch of class aliases, but the files won't be loaded until needed (unlike using `class_alias()`). The aliases are created from a nested array in the configuration array (key `alias`), where for each alias, the real (fully-resolved) class name is the value, and the alias is the key.
 5. `components['config']` (Default: `Phpf\Config`) - Object holding the application configuration settings. By default, the object is an instance of `Phpf\Config`, but this can be changed by setting a class alias `Config`.
 6. An autoloader is created for the application namespace, following the PSR-0 convention, with the base path set to 1 level above the path given by the `dirs['app']` configuration setting.

