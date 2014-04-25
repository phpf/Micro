<?php
/**
 * @package Phpf\Common
 */
namespace Phpf\Common;

/**
 * PSR-0 autoloader.
 */
class Autoloader
{

	/**
	 * Namespace for the autoloader instance.
	 * @var string
	 */
	protected $namespace;
	
	/**
	 * Directory path for the autoloader instance.
	 * @var string
	 */
	protected $path;
	
	/**
	 * Namespace separator for the instance.
	 * @var string
	 */
	protected $namespaceSeparator = '\\';

	/**
	 * Whether the autoloader is registered.
	 * @var boolean
	 */
	protected $isRegistered = false;
	
	/**
	 * Whether the autoloader is using PSR-4.
	 * @var boolean
	 */
	protected $isPsr4 = false;
	
	/**
	 * Whether to check if files exist before including them.
	 * @var boolean
	 */
	protected $checkFilesExist = false;
	
	/**
	 * The number of characters in the namespace.
	 * @var int
	 */
	protected $namespaceStrlen;
	
	/**
	 * Instances of this class.
	 * @var array
	 */
	protected static $instances = array();
	
	/**
	 * Returns autoloader instance for given namespace.
	 * 
	 * @param string $namespace Namespace
	 * @return \Phpf\Common\Autoloader
	 */
	public static function instance($namespace) {
		if (! isset(static::$instances[$namespace]))
			static::$instances[$namespace] = new static($namespace);
		return static::$instances[$namespace];
	}
	
	/**
	 * Returns all autoloader instances.
	 * 
	 * @return array Autoloader instances.
	 */
	public static function getInstances() {
		return static::$instances;
	}
	
	/**
	 * Constructs autoloader for given namespace.
	 * 
	 * @param string $namespace Namespace.
	 * @return void
	 */
	protected function __construct($namespace) {
		$this->setNamespace($namespace);
	}
	
	/**
	 * Sets the autoloader namespace.
	 * 
	 * @param string $namespace Namespace.
	 * @return $this
	 */
	public function setNamespace($namespace) {
		$this->namespace = ltrim($namespace, '\\_');
		$this->namespaceStrlen = strlen($this->namespace);
		return $this;
	}
	
	/**
	 * Returns the autoloader instance's namespace.
	 * 
	 * @return string Namespace for autoloader instance.
	 */
	public function getNamespace() {
		return $this->namespace;
	}
	
	/**
	 * Sets the path from which to load classes of the instance's namespace.
	 * 
	 * @param string $dirpath Absolute path to directory.
	 * @return $this
	 */
	public function setPath($dirpath) {
		$this->path = rtrim($dirpath, '/\\');
		return $this;
	}
	
	/**
	 * Returns the directory path for the autoloader instance.
	 * 
	 * @return string Directory path.
	 */
	public function getPath() {
		return $this->path;
	}
	
	/**
	 * Sets the namespace separator - one of "\" (default) or "_".
	 * 
	 * @param string $sep Namespace separator.
	 * @return $this
	 */
	public function setNamespaceSeparator($sep) {
		$this->namespaceSeparator = $sep;
		return $this;
	}
	
	/**
	 * Sets whether to check if files exist before including them.
	 * 
	 * @param boolean $value True to check if files exist, false to not check.
	 * @return $this
	 */
	public function setCheckFilesExist($value) {
		$this->checkFilesExist = (bool)$value;
		return $this;
	}
	
	/**
	 * Set whether the autoloader should use PSR-4 rather than PSR-0.
	 * @param boolean $value True to use PSR-4, or false to use PSR-0 (default).
	 * @return $this
	 */
	public function setPsr4($value) {
		$this->isPsr4 = (bool)$value;
		return $this;
	}
	
	/**
	 * Registers the autoloader using spl_autoload_register().
	 * 
	 * @throws RuntimeException if no path is set.
	 * @return $this
	 */
	public function register() {
		
		if (! isset($this->path)) {
			throw new \RuntimeException("No path set - cannot register autoloader.");
			return $this;
		}
		
		spl_autoload_register(array($this, 'load'));
		$this->isRegistered = true;
		return $this;
	}
	
	/**
	 * Unregisters the autoloader with spl_autoload_unregister().
	 * 
	 * @return $this
	 */
	public function unregister() {
		spl_autoload_unregister(array($this, 'load'));
		$this->isRegistered = false;
		return $this;
	}
	
	/**
	 * Whether the autoloader instance is registered.
	 * 
	 * @return boolean True if registered, otherwise false.
	 */
	public function isRegistered() {
		return $this->isRegistered;
	}
	
	/**
	 * Finds and loads a class (or interface or trait) in the namespace.
	 * 
	 * @param string $class Classname to load.
	 * @return void
	 */
	public function load($class) {
		
		$class = trim($class, $this->namespaceSeparator);
		
		if (0 !== stripos($class, $this->namespace)) {
			return;
		}
		
		if ($this->isPsr4) {
			// strip namespace
			$class = substr($class, $this->namespaceStrlen + 1);
		}
		
		$file = '';
		$localNs = '';

		if ($lastNsPos = strrpos($class, $this->namespaceSeparator)) {

			$localNs = substr($class, 0, $lastNsPos);
			$class = substr($class, $lastNsPos + 1);

			$file = str_replace($this->namespaceSeparator, DIRECTORY_SEPARATOR, $localNs).DIRECTORY_SEPARATOR;
		}

		$file .= str_replace('_', DIRECTORY_SEPARATOR, $class).'.php';
		
		$file = $this->path.DIRECTORY_SEPARATOR.$file;
			
		if (! $this->checkFilesExist || file_exists($file)) {
			include $file;
		}
	}
	
}
