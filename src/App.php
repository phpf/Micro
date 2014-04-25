<?php

namespace Phpf;

use ArrayAccess;
use Countable;
use RuntimeException;

class App implements ArrayAccess, Countable {
	
	const DEFAULT_ID = 'app';
	
	/**
	 * Application namespace.
	 * @var string
	 */
	public $namespace;
	
	/**
	 * Application component objects/classes.
	 * @var array
	 */
	protected $components = array();
	
	/**
	 * App instances.
	 * @var array
	 */
	protected static $instances = array();
	
	/**
	 * Returns object instance.
	 * 
	 * @throws RuntimeException if no instance with given ID
	 * @param string $id Application ID
	 * @return \App
	 */
	public static function instance($id = self::DEFAULT_ID) {
			
		if (! isset(static::$instances[$id])) {
			throw new RuntimeException("No application set.");
		}
		
		return static::$instances[$id];
	}
	
	/**
	 * Set up configuration settings.
	 * 
	 * @param string $root Root filesystem directory for entire application.
	 * @param string $conf_file User config file.
	 * @return array Configuration settings.
	 */
	public static function configure($root, $config_file = null) {
		
		$root = rtrim(realpath($root), '/\\') . DIRECTORY_SEPARATOR;
		
		if (! isset($config_file)) {
			$config_file = $root.'config.php';
		}
		
		$userConfig = require $config_file;
		
		if (! is_array($userConfig)) {
			throw new RuntimeException("User configuration file must return array.");
		}
		
		// Merge user config and defaults
		$config = array_replace_recursive(array(
			'root'		=> './', // want this at the top
			'namespace'	=> 'App',
			'charset'	=> 'UTF-8',	// @used-by \PhpfCommon\Env
			'timezone'	=> 'UTC',	// @used-by \PhpfCommon\Env
			'debug' 	=> false, 	// @used-by \PhpfCommon\Env
			'ini' => array( 		// @used-by \PhpfCommon\Env
				/**
				 * This ini setting makes sure that objects get unserialized with methods 
				 * when its class has not yet been (auto)loaded. It simply calls 
				 * spl_autoload_call() with the class of the object being unserialized.
				 * 
				 * Note this is potentially dangerous (i.e. may produce errors) if not all your 
				 * classes are autoloaded, as it's invoked for all (object) unserialize operations.
				 */
				'unserialize_callback_func' => 'spl_autoload_call',
			),
			'dirs' => array( // @used-by \PhpfCommon\Env
				'app'		=> 'app', // application path, relative to root
				'library'	=> 'app/library',
				'module'	=> 'app/modules',
				'views'		=> 'app/views',
				'assets'	=> 'app/public',
				'resources' => 'app/data',
			),
			'aliases' => array(
				'App'			=> 'Phpf\App',
				'Config'		=> 'Phpf\Config',
				'Cache'			=> 'Phpf\Cache', // hardcoded in functions.php
				'Session'		=> 'Phpf\Session',
				'Request'		=> 'Phpf\Request',
				'Response'		=> 'Phpf\Response',
				'Router'		=> 'Phpf\Router',
				'Database'		=> 'Phpf\Database',
				'Filesystem'	=> 'Phpf\Filesystem',
				'Events'		=> 'Phpf\EventContainer',
				'Views'			=> 'Phpf\ViewManager',
				'Packages'		=> 'Phpf\PackageManager',
				'Helper'		=> 'Phpf\Common\Helper',
				'Registry' 		=> 'Phpf\Common\StaticRegistry', // might be unnecessary...
				'Log'			=> 'Phpf\Log\Log', // might be unnecessary...
			),
		), $userConfig);
		
		// set root filesystem path
		$config['root'] = $root;
		
		return $config;
	}

	/**
	 * Create a new application instance.
	 * 
	 * @param array|ArrayAccess $config Configuration array/object.
	 * @throws RuntimeException If $config is not array or ArrayAccess, or if 
	 * 							App instance with given ID already exists.
	 * @return \Phpf\App
	 */
	public static function createFromArray($config) {
		
		if (! is_array($config) && ! $config instanceof ArrayAccess) {
			throw new RuntimeException("Config must be array or instance of ArrayAccess.");
		}
		
		if (! isset($config['id'])) {
			$config['id'] = self::DEFAULT_ID;
		}
		
		if (isset(static::$instances[$config['id']])) {
			throw new RuntimeException('Application already exists.');
		}
		
		// create new instance
		return (static::$instances[$config['id']] = new static($config));
	}
	
	/**
	 * Includes framework functions.
	 * @return void
	 */
	public static function loadFunctions() {
		require_once __DIR__ . '/functions.php'; // file should be on same dir level
	}
	
	/**
	 * Include a file with the application instance within the local scope.
	 * 
	 * The application will be available in the file as the variable "$app".
	 * This method is static to avoid having to use a closure in an object 
	 * context (which runs about twice as slow).
	 * 
	 * @param string $file File to include.
	 * @param boolean $check_exists Whether to check if the file exists prior to loading. Default true.
	 * @return boolean True if file was included, false if it was not.
	 */
	public static function includeInScope($file, $check_exists = true) {
		if (! $check_exists || file_exists($file)) {
			$app = static::instance();
			include $file;
			return true;
		}
		return false;
	}
	
	/**
	 * Construct the application
	 * 
	 * @param array|ArrayAccess Config array/object
	 * @return void
	 */
	protected function __construct($config) {
			
		// set app namespace
		$this->namespace = trim($config['namespace'], '\\');
		
		/**
		 * Namespace for application resources (e.g. models, controllers, etc.)
		 * @var string
		 */
		define('APP_NAMESPACE', $this->namespace);
		
		// Env
		$this->set('env', $env = new Common\Env($config['root']));
		
		$env->setCharset($config['charset']);
		$env->setTimezone($config['timezone']);
		$env->setDebug($config['debug']);
		$env->setIni($config['ini']);
		
		foreach($config['dirs'] as $name => $dir) {
			$env->addDirectory($dir, $name, true);
		}
		
		$env->configurePHP();
		
		// class aliaser
		$this->set('aliaser', $aliaser = new Common\ClassAliaser);
		$aliaser->addAliases($config['aliases'])->register();
				
		// set Config object
		$this->set('config', new \Config($config));
		
		$autoloader = Common\Autoloader::instance($this->namespace);
		$autoloader->setPath(dirname(APP));
		$autoloader->register();
	}
	
	/**
	 * Returns the application namespace.
	 * 
	 * @return string Application namespace
	 */
	public function getNamespace() {
		return $this->namespace;
	}
	
	/**
	 * Returns path to given arg or base application path.
	 * 
	 * @param null|string $to Name of path to get. Default null
	 * @return string Path to given arg or base path.
	 */
	public function getPath($to = null) {
			
		$paths = $this->get('config')->get('paths');
		
		if (isset($to)) {
			return isset($paths[$to]) ? $paths[$to] : null;
		}
		
		return $paths['app'];
	}
	
	/**
	 * Adds a (lazy) class alias.
	 * 
	 * @param string $from Fully resolved class to alias.
	 * @param string $to The class alias.
	 * @return $this
	 */
	public function alias($from, $to) {
		$this->aliaser->alias($from, $to);
		return $this;
	}
	
	/**
	 * Returns a component.
	 * 
	 * @param string $name Component name.
	 * @return object Component object if exists, otherwise null.
	 */
	public function get($name) {
		
		if (! isset($this->components[$name]))
			return null;
		
		if (is_string($this->components[$name])) {
			$class = $this->components[$name];
			return $class::instance();
		}
		
		return $this->components[$name];
	}
	
	/**
	 * Adds a component.
	 * 
	 * If $object is a string, the component will be added and 
	 * called as a singleton.
	 * 
	 * @param string $name Case-sensitive component name.
	 * @param object|string $object Component object or classname.
	 * @return $this
	 */
	public function set($name, $object) {
		
		if (is_string($object)) {
			return $this->setSingleton($name, $object);
		} 
		
		if (! is_object($object)) {
			$type = gettype($object);
			trigger_error("Non-singleton components must be objects - $type given for $name.");
			return null;
		}
		
		$this->components[$name] = $object;
		
		return $this;
	}
	
	/**
	 * Set a singleton component.
	 * 
	 * The given class must be fully resolved (or aliased) and
	 * it must have a static instance() method that returns the object.
	 * 
	 * @param string $name Case-sensitive component name.
	 * @param string $class Component class implementing singleton.
	 * @return $this
	 */
	public function setSingleton($name, $class) {
		
		if (! method_exists($class, 'instance')) {
			trigger_error("Singletons must have 'instance()' method - Class $class does not.");
			return null;
		}
		
		if (is_object($class)) {
			$class = get_class($class);
		}
		
		$this->components[$name] = $class;
		
		return $this;
	}
	
	/**
	 * Sets cache driver based on config values, or lack thereof.
	 * 
	 * @param string $cache Classname to use for Cache (singleton).
	 * @return \Cache
	 */
	public function startCache($cache = 'Cache') {
		
		$this->setSingleton('cache', $cache); // set the class
		$cache = $this->get('cache'); // get the object
		$conf = $this->get('config')->get('cache');
		
		// get driver class
		if (isset($conf['driver-class'])) {
			$class = $conf['driver-class'];
		} else if (isset($conf['driver'])) {
			$class = 'Phpf\\CacheDriver\\'.ucfirst($conf['driver']).'Driver';
		}
		
		if (isset($class) && class_exists($class, true)) {
			$cache->setDriver(new $class);
		} else {
			// set fallback driver
			$cache->setDriver(new \Phpf\CacheDriver\StaticDriver);
		}
		
		return $cache;
	}
	
	/**
	 * Countable
	 */
	public function count(){
		return count($this->components);
	}
	
	/**
	 * ArrayAccess
	 */
	public function offsetGet( $index ){
		return $this->get($index);
	}
	
	/**
	 * ArrayAccess
	 */
	public function offsetSet( $index, $newval ){
		$this->set($index, $newval);
	}
	
	/**
	 * ArrayAccess
	 */
	public function offsetUnset( $index ){
		trigger_error('Cannot unset application components.', E_USER_NOTICE);
	}
	
	/**
	 * ArrayAccess
	 */
	public function offsetExists( $index ){
		return isset($this->components[$var]);
	}
	
	/**
	 * Magic __get()
	 */
	public function __get($var) {
		return $this->get($var);
	}
	
	/**
	 * Magic __set()
	 */
	public function __set($var, $val) {
		$this->set($var, $val);
	}
	
	/**
	 * Magic __isset()
	 */
	public function __isset($var) {
		return isset($this->components[$var]);
	}
	
	/**
	 * Magic __unset()
	 */
	public function __unset($var) {
		$this->offsetUnset($var);
	}
	
	/**
	 * Returns a component that matches the called method.
	 * @deprecated
	 */
	public function __call( $func, $args ){
		
		trigger_error("Calling components as methods is deprecated on Phpf\App - use properties instead.", E_USER_DEPRECATED);
		
		if (isset($this->components[$func])) {
			return $this->get($func);
		}
	}
	
}
