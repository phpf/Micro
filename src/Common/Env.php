<?php
/**
 * @package Phpf\Common
 */
namespace Phpf\Common;

class Env {
	
	/**
	 * Root filesystem path.
	 * @var string
	 */
	public $root;
	
	/**
	 * System charset.
	 * @var string
	 */
	public $charset = 'UTF-8';
	
	/**
	 * Date default timezone.
	 * @var string
	 */
	public $timezone = 'UTC';
	
	/**
	 * Whether we're developing.
	 * @var boolean
	 */
	public $debug = false;
	
	/**
	 * Application directories
	 * @var array
	 */
	public $directories;
	
	/**
	 * ini settings.
	 * @var array
	 */
	public $ini;
	
	/**
	 * Construct env using root path.
	 * 
	 * @param string $root_path Filesystem path to root of application.
	 * @return void
	 */
	public function __construct($root_path) {
		$this->root = rtrim($root_path, '/\\').'/';
		$this->directories = array();
		$this->ini = array();
	}
	
	/**
	 * Set the charset.
	 * @param string $charset Charset to use.
	 * @return $this
	 */
	public function setCharset($charset) {
		$this->charset = $charset;
		return $this;
	}
	
	/**
	 * Set the date default timezone.
	 * @param string $timezone Timezone to use in date_default_timezone_set().
	 * @return $this
	 */
	public function setTimezone($timezone) {
		$this->timezone = $timezone;
		return $this;
	}
	
	/**
	 * Set if debugging.
	 * @param boolean $bool True if debugging, false if not.
	 * @return $this
	 */
	public function setDebug($bool) {
		$this->debug = (bool) $bool;
		return $this;
	}
	
	/**
	 * Set the ini configuration settings.
	 * @param array $ini Associative array of ini settings.
	 * @return $this
	 */
	public function setIni(array $ini) {
		$this->ini = $ini;
		return $this;
	}
	
	/**
	 * Adds an application directory from a path relative to root.
	 * 
	 * Optionally defines a constant with given name, value set to absolute path.
	 * 
	 * @param string $dirpath Relative directory path.
	 * @param string|null $name Name to assign to directory; if null, taken from basename().
	 * @param boolean $define_constant Whether to define a constant for dir. Default false.
	 * @return $this
	 */
	public function addDirectory($dirpath, $name = null, $define_constant = false) {
		
		if (! isset($name)) {
			$name = basename($dirpath);
		}
		
		$dirpath = trim($dirpath, '/\\') . '/';
		
		$this->directories[$name] = $this->root . $dirpath;
		
		if ($define_constant) {
			/** @ignore Constant defined from variable */
			define(strtoupper($name), $this->directories[$name]);
		}
		
		return $this;
	}
	
	/**
	 * Returns an absolute path for the given directory name.
	 * 
	 * @param string $name Directory name, added with addDirectory().
	 * @return string Absolute path to directory, or null if not found.
	 */
	public function getDirectoryPath($name) {
		return isset($this->directories[$name]) ? $this->directories[$name] : null;
	}
	
	/**
	 * Sets error_reporting(), ini values, and date_default_timezone_set() using the
	 * object's properties.
	 * 
	 * @return void
	 */
	public function configurePHP() {
		
		/** Set error reporting */
		if ($this->debug) {
				
			error_reporting(E_ALL);
			$this->ini['display_errors'] = 1;
			
			if (defined('DEBUG_LOG_PATH')) {
				$this->ini['log_errors'] = 1;
				$this->ini['error_log'] = DEBUG_LOG_PATH;
			}
			
		} else {
			error_reporting(E_ALL ^E_STRICT);
			$this->ini['display_errors'] = 0;
		}
		
		/** Set ini settings */
		foreach($this->ini as $varname => $value) {
			ini_set($varname, $value);
		}
		
		/** Set default timezone */
		date_default_timezone_set($this->timezone);
	}
	
}
