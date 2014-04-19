<?php
/**
 * PSR-0 autoloader.
 */

namespace Phpf\Common;

class Autoloader
{

	public $namespace;

	public $path;

	public $namespaceSeparator = '\\';

	public $isRegistered = false;
	
	protected $checkFilesExist = false;

	protected $namespaceStrlen;

	protected static $instances = array();

	public static function instance($namespace, $path = null) {
		if (! isset(static::$instances[$namespace]))
			static::$instances[$namespace] = new static($namespace, $path);
		return static::$instances[$namespace];
	}

	protected function __construct($namespace, $path = null) {
		$this->setNamespace($namespace);
		$this->setPath($path);
	}

	public function setNamespace($namespace) {
		$this->namespace = ltrim($namespace, '\\_');
		$this->namespaceStrlen = strlen($this->namespace);
		return $this;
	}

	public function setPath($dirpath) {
		$this->path = rtrim($dirpath, '/\\');
		return $this;
	}

	public function setNamespaceSeparator($sep) {
		$this->namespaceSeparator = $sep;
		return $this;
	}

	public function setCheckFilesExist($value) {
		$this->checkFilesExist = (bool)$value;
		return $this;
	}

	public function isRegistered() {
		return $this->isRegistered;
	}
	
	/**
	 * Finds and loads a class (or interface or trait).
	 */
	public function load($class) {

		if (0 !== stripos($class, $this->namespace)) {
			return;
		}

		$file = '';
		$fullNs = '';

		if ($lastNsPos = strrpos($class, $this->namespaceSeparator)) {

			$fullNs = substr($class, 0, $lastNsPos);
			$class = substr($class, $lastNsPos + 1);

			$file = str_replace($this->namespaceSeparator, DIRECTORY_SEPARATOR, $fullNs).DIRECTORY_SEPARATOR;
		}

		$file .= str_replace('_', DIRECTORY_SEPARATOR, $class).'.php';
		
		if ($this->checkFilesExist) {
			if (file_exists($file)) {
				include $this->path.DIRECTORY_SEPARATOR.$file;
			}
		} else {
			include $this->path.DIRECTORY_SEPARATOR.$file;
		}
	}

	public function register() {
		spl_autoload_register(array($this, 'load'));
		$this->isRegistered = true;
		return $this;
	}

	public function unregister() {
		spl_autoload_unregister(array($this, 'load'));
		$this->isRegistered = false;
		return $this;
	}

	public function getInstances() {
		return self::$_instances;
	}

}
