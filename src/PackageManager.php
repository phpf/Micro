<?php

namespace Phpf;

use Phpf\Package\Loader;
use Phpf\Package\PackageFunctions;
use Phpf\Package\PackageInterface;

class PackageManager implements Common\iManager
{

	const DEFAULT_LIBRARY_CLASS = 'Phpf\\Package\\Library';

	const DEFAULT_MODULE_CLASS = 'Phpf\\Package\\Module';

	protected $packages = array();

	protected $loader;

	protected $config;

	protected $functional = array();

	protected $paths = array();

	protected $classes = array();

	/**
	 * Constructor
	 * 
	 * @param \Phpf\App $app Application instance.
	 * @return void
	 */
	public function __construct(App $app) {

		$this->app = $app;

		$this->loader = new Loader($app);

		$this->config = $app->get('config')->get('packages');
	}
	
	/**
	 * Parses and loads (some) packages specified in config.
	 * 
	 * 'functions' are loaded first;
	 * 'preload' packages are loaded next; 
	 * 'conditional' packages are then loaded, depending on criteria.
	 * 
	 * @return $this
	 */
	public function init() {

		if (! empty($this->config['functions'])) {

			foreach ( $this->config['functions'] as $namespace => $packages ) {

				$this->initFunctions($namespace);

				foreach ( $packages as $package ) {
					$this->loadFunctions($namespace, $package);
				}
			}
		}

		if (! empty($this->config['preload'])) {
			$this->addPackages($this->config['preload'], true);
		}

		if (! empty($this->config['ondemand'])) {
			$this->addPackages($this->config['ondemand'], false);
		}

		if (! empty($this->config['conditional'])) {
			$this->parseConditionalPackages($this->config['conditional']);
		}

		return $this;
	}
	
	/**
	 * Ssets the classname to use for packages of a given type.
	 * 
	 * @param string $package_type Type of package.
	 * @param string $class Class to use for packages of given type.
	 * @return $this
	 */
	public function setClass($package_type, $class) {
		$this->classes[$package_type] = $class;
		return $this;
	}

	/**
	 * Returns the classname for packages of given type.
	 * 
	 * @param string $package_type Type of package.
	 * @return string Classname for given package type if set, otherwise user-level warning triggered.
	 */
	public function getClass($package_type) {

		if (isset($this->classes[$package_type])) {
			return $this->classes[$package_type];
		}

		$const = 'DEFAULT_'.strtoupper($package_type).'_CLASS';

		if (defined("static::$const")) {
			return constant("static::$const");
		}

		trigger_error("No class set for package type $package_type.");
	}
	
	/**
	 * Sets a directory path for packages of given type.
	 * 
	 * @param string $package_type Type of package.
	 * @param string $path Directory path in which to find packages of this type.
	 * @return $this
	 */
	public function setPath($package_type, $path) {
		$this->paths[$package_type] = rtrim($path, '/\\').'/';
		return $this;
	}
	
	/**
	 * Returns directory path to packages of given type.
	 * 
	 * @param string $package_type Type of package.
	 * @return string Filesystem path if set, otherwise a user-level warning is triggered.
	 */
	public function getPath($package_type) {

		if (isset($this->paths[$package_type]))
			return $this->paths[$package_type];

		$paths = $this->app->get('env')->directories;
		
		if (isset($paths[$package_type])) {
			return $paths[$package_type];
		} else if (isset($paths[$package_type.'s'])) {
			return $paths[$package_type.'s'];
		}

		trigger_error("No path set for package type $package_type.");
	}

	/**
	 * Implement iManager
	 * Manages 'packages'
	 * 
	 * @return string 'packages'
	 */
	final public function manages() {
		return 'packages';
	}

	/**
	 * Returns a package given its UID, or type and ID.
	 * 
	 * @param string $pkg Package UID, or Type if ID given as 2nd param.
	 * @param string|null Package ID if Type given as first parameter, otherwise null.
	 * @return PackageInterface Package if exists, otherwise null.
	 */
	public function get($uid /* | $type, $id */ ) {

		list($type, $id) = $this->parseUid(func_get_args());

		return isset($this->packages[$type][$id]) ? $this->packages[$type][$id] : null;
	}

	/**
	 * Returns boolean, whether a package exists given its UID, or type and ID.
	 * 
	 * @param string $pkg Package UID, or Type if ID given as 2nd param.
	 * @param string|null Package ID if Type given as first parameter, otherwise null.
	 * @return boolean True if given package exists, otherwise false.
	 */
	public function exists($uid /* | $type, $id */ ) {

		list($type, $id) = $this->parseUid(func_get_args());

		return isset($this->packages[$type][$id]);
	}

	/**
	 * Unsets a package given its UID, or type and ID.
	 * Note: This will not "disable" the package if it has been loaded.
	 * 
	 * @param string $pkg Package UID, or Type if ID given as 2nd param.
	 * @param string|null Package ID if Type given as first parameter, otherwise null.
	 * @return void
	 */
	public function remove($uid /* | $type, $id */ ) {

		list($type, $id) = $this->parseUid(func_get_args());

		unset($this->packages[$type][$id]);
	}

	/**
	 * Adds a package object.
	 * 
	 * @param PackageInterface $package Package object.
	 * @return $this
	 */
	public function add(PackageInterface $package) {
		$this->packages[$package->getType()][$package->getId()] = $package;
		return $this;
	}

	/**
	 * Adds a module by name.
	 * 
	 * @param string $mod Module name
	 * @return $this
	 */
	public function addModuleByName($mod) {
		$modClass = $this->getClass('module');
		$this->add(new $modClass($mod, rtrim($this->getPath('module'), '/\\').'/'.ucfirst($mod)));
	}

	/**
	 * Adds a library by name.
	 * 
	 * @param string $lib Library name
	 * @return $this
	 */
	public function addLibraryByName($lib) {
		$libClass = $this->getClass('library');
		$this->add(new $libClass($lib, rtrim($this->getPath('library'), '/\\').'/'.ucfirst($lib)));
	}

	/**
	 * Adds multiple packages by UID and optionally loads them.
	 * 
	 * @param array $packages Indexed array of package UID's.
	 * @param boolean $load [Optional] Whether to load the given packages. Default: false.
	 * @return void
	 */
	public function addPackages(array $packages, $load = false) {

		foreach ( $packages as $package ) {
			if (0 === strpos($package, 'library.')) {
				$lib = substr($package, 8);
				$this->addLibraryByName($lib);
				if ($load) {
					$this->load('library.'.$lib);
				}
			} else if (0 === strpos($package, 'module.')) {
				$mod = substr($package, 7);
				$this->addModuleByName($mod);
				if ($load) {
					$this->load('module.'.$mod);
				}
			}
		}
	}

	/**
	 * Loads a package given an Object, UID, or Type and ID.
	 * 
	 * @param string|PackageInterface $pkg Package object, UID, or Type if ID given as 2nd param.
	 * @param string|null Package ID if Type given as first parameter, otherwise null.
	 * @return $this
	 */
	public function load($pkg /* | $type, $id */ ) {

		$args = func_get_args();

		if ($args[0] instanceof PackageInterface) {
			$package = &$args[0];
		} else if (isset($args[1])) {
			$package = $this->get($args[0], $args[1]);
		} else {
			$package = $this->get($args[0]);
		}

		if (empty($package)) {
			throw new Exception\Unknown("Empty package given.");
		}

		if (! $package instanceof PackageInterface) {
			throw new Exception\Invalid("Invalid package - packages must implement PackageInterface.");
		}

		if ($package->isLoaded()) {
			throw new Exception\Loaded(ucfirst($package->getType())." '$package->getId()' is already loaded.");
		}

		$this->loader->load($package);

		$this->app->get('events')->trigger($package->getUid().'.load', $package);

		return $this;
	}

	/**
	 * Returns boolean, whether a package is loaded given its UID.
	 * 
	 * @param string $uid Package UID.
	 * @return boolean True if package is known and loaded, otherwise false.
	 */
	public function isLoaded($uid) {

		$pkg = $this->get($uid);

		if (empty($pkg) || ! $pkg instanceof PackageInterface) {
			return false;
		}

		return $pkg->isLoaded();
	}

	/**
	 * Sets up functions control for namespace.
	 * 
	 * @param string $namespace Package namespace
	 * @return $this
	 */
	public function initFunctions($namespace) {
		$this->functional[$namespace] = new PackageFunctions($namespace);
		return $this;
	}

	/**
	 * Loads functions for a package.
	 *
	 * @param string Package name
	 * @return boolean Whether functions were loaded.
	 */
	public function loadFunctions($namespace, $package) {

		if (! isset($this->functional[$namespace])) {
			$this->initFunctions($namespace);
		}

		return $this->functional[$namespace]->load($package);
	}

	/**
	 * Returns true if functions are loaded for a package,
	 * otherwise returns false.
	 *
	 * @param string Package name
	 * @return boolean True if functions loaded, otherwise false.
	 */
	public function functionsLoaded($namespace, $package) {

		if (! isset($this->functional[$namespace])) {
			return false;
		}

		return $this->functional[$namespace]->loaded($package);
	}

	/**
	 * Loads all packages of given type.
	 * 
	 * @param string Package Type to load.
	 * @return $this
	 */
	public function loadAllOfType($type) {

		$all = $this->getAllOfType($type);

		if (! empty($all)) {
			foreach ( $all as $pkg ) {
				$this->load($pkg);
			};
		}

		return $this;
	}

	/**
	 * Returns all package objects of given type.
	 * 
	 * @param string Package Type.
	 * @return array Packages of given type, or empty array if none exist.
	 */
	public function getAllOfType($type) {
		return empty($this->packages[$type]) ? array() : $this->packages[$type];
	}

	/**
	 * Returns indexed array of package type strings.
	 * 
	 * @return array Indexed array of package types.
	 */
	public function getTypes() {
		return array_keys($this->packages);
	}
	
	/**
	 * Parses a conditional package string and extracts the delimeter and value.
	 * 
	 * @param string $str Conditional string (e.g. "php<5.5" -> "PHP versions less than 5.5")
	 * @param scalar &$value Set to value found (e.g. in example above, value is "5.5")
	 * @return string|null Operator string if found, otherwise null.
	 */
	protected function findDelimeter($str, &$value = null) {
			
		$operators = array('<=', '>=', '!=', '<', '=', '>');
		
		foreach ( $operators as $op ) {
			if (false !== $pos = strpos($str, $op)) {
				$value = substr($str, $pos + strlen($op));
				return $op;
			}
		}
		
		return null;
	}

	/**
	 * Parses array of conditionally loaded packages from config array.
	 * 
	 * @param array $packages Array of package settings from config.
	 * @return void
	 */
	protected function parseConditionalPackages(array $packages) {

		foreach ( $packages as $condition => $_packages ) {

			$oper = $this->findDelimeter($condition, $value);

			if (null === $oper) {
				continue;
			}

			switch(substr($condition, 0, 3)) {

				case 'php' :
					
					if (version_compare(PHP_VERSION, $value, $oper) > 0) {
						// PHP version is outside given range
						$this->addPackages($_packages, true);
					}
					break;

				case 'ext' :
					
					if ('!=' === $oper && ! extension_loaded($value)) {
						// Extension is not loaded
						$this->addPackages($_packages, true);
					}
					break;
			}
		}
	}

	/**
	 * Parses a UID into a 2-element aray of Type and ID.
	 *
	 * @param array $args	Arguments.	If only 1 element is present, it(the string) is parsed
	 * 									as a dot-separated type/ID pair. Otherwise, the first
	 * 									two items will be used as the type and ID, respectively.
	 * @return array Indexed array of package Type and ID.
	 */
	protected function parseUid(array $args) {

		if (! isset($args[1])) {
			return explode('.', $args[0]);
		}

		return $args;
	}

}
