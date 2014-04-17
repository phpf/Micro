<?php

namespace Phpf\Route;

abstract class Controller
{

	/**
	 * Get an object property.
	 */
	public function get($var) {
		return isset($this->$var) ? $this->$var : null;
	}

	/**
	 * Set an object property
	 */
	public function set($var, $value) {
		$this->$var = $value;
		return $this;
	}

	/**
	 * Attach an object by name.
	 *
	 * A glorified set() method, where $name is the classname
	 * unless otherwise specified.
	 *
	 * Useful to decorate "Request" and "Response" objects.
	 */
	public function attach($object, $name = '') {

		if (empty($name)) {
			$name = get_class($object);
		}

		return $this->set($name, $object);
	}
	
	/**
	 * Attaches Request and Response to controller.
	 */
	public function init(\Phpf\Request $request, \Phpf\Response $response) {
		$this->set('request', $request);
		$this->set('response', $response);
		return $this;
	}
	
	/**
	 * Transfer the current object's properties to another controller.
	 */
	public function transfer(Controller &$controller, array $exclude = null) {
		
		foreach(get_object_vars($this) as $k => &$v) {
				
			if (isset($exclude) && in_array($k, $exclude, true)) {
				continue;
			}
			
			$controller->set($k, $v);
		}
		
		return $this;
	}
	
	/**
	 * Forwards the request by calling another controller method.
	 * 
	 * @param Controller $controller Controller
	 * @param string $method Controller method name
	 * @param array $args Arguments passed to controller method.
	 */
	public function forward(Controller $controller, $method, array $args = array()) {
		
		$this->transfer($controller);
		
		if (is_callable(array($controller, $method))) {
			return call_user_func_array(array($controller, $method), $args);
		}
	}
	
}
