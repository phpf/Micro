<?php

namespace Phpf\Common;

class Env {
	
	/**
	 * @var string
	 */
	public $root;
	
	/**
	 * @var string
	 */
	public $charset = 'UTF-8';
	
	/**
	 * @var string
	 */
	public $timezone = 'UTC';
	
	/**
	 * @var boolean
	 */
	public $debug = false;
	
	/**
	 * @var array
	 */
	public $directories;
	
	public function __construct($root_path) {
		$this->root = rtrim($root_path, '/\\').'/';
		$this->directories = array();
	}
	
	public function setCharset($charset) {
		$this->charset = $charset;
		return $this;
	}
	
	public function setTimezone($timezone) {
		$this->timezone = $timezone;
		return $this;
	}
	
	public function setDebug($bool) {
		$this->debug = (bool) $bool;
		return $this;
	}
	
	public function addDirectory($dirpath, $name = null, $define_constant = false) {
		
		if (! isset($name)) {
			$name = basename($dirpath);
		}
		
		$dirpath = trim($dirpath, '/\\') . '/';
		
		$this->directories[$name] = $this->root . $dirpath;
		
		if ($define_constant) {
			define(strtoupper($name), $this->directories[$name]);
		}
		
		return $this;
	}
	
	public function getDirectoryPath($name) {
		return isset($this->directories[$name]) ? $this->directories[$name] : null;
	}
	
	public function configurePHP() {
		
		/** Set default timezone */
		date_default_timezone_set($this->timezone);
		
		// set mb encodings if loaded
		if (extension_loaded('mbstring')) {
			mb_internal_encoding($this->charset);
			mb_regex_encoding($this->charset);
			mb_http_input($this->charset);
			mb_http_output($this->charset);
		}
		
		/** Set error reporting */
		if ($this->debug) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
			if (defined('DEBUG_LOG_PATH')) {
				ini_set('log_errors', 1);
				ini_set('error_log', DEBUG_LOG_PATH);
			}
		} else {
			ini_set('display_errors', 0);
			error_reporting(E_ALL ^E_STRICT);
		}
	}
	
}
