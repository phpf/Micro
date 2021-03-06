<?php

namespace Phpf\Route;

class Route
{
	/**
	 * @var string
	 */
	public $uri;

	/**
	 * @var array
	 */
	public $methods;

	/**
	 * @var callable
	 */
	public $callback;

	/**
	 * @var boolean
	 */
	public $initOnMatch;

	public static $defaultMethods = array(
		HTTP_METH_GET, 
		HTTP_METH_POST, 
		HTTP_METH_HEAD, 
	);

	public function __construct($uri, array $args) {

		$this->uri = $uri;

		foreach ( $args as $k => $v ) {
			$this->$k = $v;
		}

		if (empty($this->methods)) {
			$this->methods = self::$defaultMethods;
		}

		// Change to keys to use isset() instead of in_array()
		$this->methods = array_fill_keys($this->methods, true);

		if (! isset($this->initOnMatch)) {
			$this->initOnMatch = true;
		}
	}

	public function getUri() {
		return $this->uri;
	}

	public function getMethods() {
		return array_keys($this->methods);
	}

	public function isMethodAllowed($method) {
		return isset($this->methods[$method]);
	}

	public function getCallback() {

		static $built;

		if (! isset($built))
			$built = false;

		if ($built)
			return $this->callback;

		// Instantiate the controller on route match.
		// This means don't have to create a bunch of objects in order to
		// receive the request in an object context.
		if ($this->initOnMatch && isset($this->controller)) {

			$class = $this->controller;

			if (isset($this->action)) {
				$this->callback = array(new $class, $this->action);
			} else if (isset($this->callback) && is_string($this->callback)) {
				$this->callback = array(new $class, $this->callback);
			} else {
				throw new \RuntimeException("Cannot create callback.");
			}

			if (isset($this->endpoint)) {
				$name = $this->endpoint;
			} else {
				$name = substr($this->uri, strrpos($this->uri, '/'));
			}

			#\Registry::set('controller.'.$name, $this->callback[0]);
		}

		$built = true;

		return $this->callback;
	}

}
