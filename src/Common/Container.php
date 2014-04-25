<?php
/**
 * @package Phpf\Common
 */
namespace Phpf\Common;

use ArrayAccess;
use Countable;
use IteratorAggregate;

class Container implements ArrayAccess, Countable, IteratorAggregate, iContainer
{

	/**
	 * Magic __set()
	 *
	 * @param string $var Property name.
	 * @param mixed $val Property value.
	 * @return void
	 */
	public function __set($var, $val) {
		$this->$var = $val;
	}

	/**
	 * Magic __get()
	 *
	 * @param string $var Property name.
	 * @return mixed Property value if set, otherwise null. If the value is a
	 * closure, it will be executed and the results will be returned.
	 */
	public function __get($var) {
		return isset($this->$var) ? $this->result($this->$var) : null;
	}

	/**
	 * Magic __isset()
	 *
	 * @param string $var Property name.
	 * @return boolean True if property exists and is not null, otherwise false.
	 */
	public function __isset($var) {
		return isset($this->$var);
	}

	/**
	 * Magic __unset()
	 *
	 * @param string $var Property name.
	 * @return void
	 */
	public function __unset($var) {
		unset($this->$var);
	}

	/**
	 * Sets a property value.
	 * [iContainer]
	 *
	 * @param string $var Property name.
	 * @param mixed $val Property value.
	 * @return $this
	 */
	public function set($var, $val) {
		$this->$var = $val;
		return $this;
	}

	/**
	 * Returns a property value.
	 * [iContainer]
	 *
	 * @param string $var Property name.
	 * @return mixed Property value if set, otherwise null. If the value is a
	 * closure, it will be executed and the results will be returned.
	 */
	public function get($var) {
		return isset($this->$var) ? $this->result($this->$var) : null;
	}

	/**
	 * Returns true if a property exists.
	 * [iContainer]
	 *
	 * @param string $var Property name.
	 * @return boolean True if property exists and is not null, otherwise false.
	 */
	public function exists($var) {
		return isset($this->$var);
	}

	/**
	 * Unsets a property.
	 * [iContainer]
	 *
	 * @param string $var Property name.
	 * @return $this
	 */
	public function remove($var) {
		unset($this->$var);
		return $this;
	}

	/**
	 * Sets a property value.
	 * [ArrayAccess]
	 *
	 * @param string $var Property name.
	 * @param mixed $val Property value.
	 * @return void
	 */
	public function offsetSet($index, $newval) {
		$this->$index = $newval;
	}

	/**
	 * Returns a property value.
	 * [ArrayAccess]
	 *
	 * @param string $var Property name.
	 * @return mixed Property value if set, otherwise null. If the value is a
	 * closure, it will be executed and the results will be returned.
	 */
	public function offsetGet($index) {
		return isset($this->$index) ? $this->result($this->$index) : $this->__offsetGet($index);
	}

	/**
	 * Returns true if a property exists.
	 * [ArrayAccess]
	 *
	 * @param string $var Property name.
	 * @return boolean True if property exists and is not null, otherwise false.
	 */
	public function offsetExists($index) {
		return isset($this->$index);
	}

	/**
	 * Unsets a property.
	 * [ArrayAccess]
	 *
	 * @param string $var Property name.
	 * @return void
	 */
	public function offsetUnset($index) {
		unset($this->$index);
	}

	/**
	 * Returns number of data items.
	 * [Countable]
	 *
	 * @return int Number of container items.
	 */
	public function count() {
		return count($this);
	}

	/**
	 * Returns iterator.
	 * [IteratorAggregate]
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this);
	}

	/**
	 * Returns raw value if set. Does not execute if value is a closure.
	 *
	 * @param string $var Property name.
	 * @return mixed Value if set, otherwise null.
	 */
	public function raw($var) {
		return isset($this->$var) ? $this->$var : null;
	}

	/**
	 * Imports an array or object containing data as properties.
	 * [iContainer]
	 *
	 * @param array|object $data Array or object containing properties to import.
	 * @return $this
	 */
	public function import($data) {

		if (! is_array($data) && ! $data instanceof \Traversable) {
			$data = (array)$data;
		}

		foreach ( $data as $k => $v ) {
			$this->set($k, $v);
		}

		return $this;
	}

	/**
	 * Returns object properties as array.
	 * [iContainer]
	 *
	 * @param boolean $indexed If true, returns indexed array (otherwise
	 * associative). Default false.
	 * @return array Object as array.
	 */
	public function toArray($indexed = false) {
		return iterator_to_array($this, ! $indexed);
	}

	/**
	 * Executes callable properties - i.e. closures or invokable objects.
	 *
	 * Allows container to have property-bound methods.
	 *
	 * @throws BadMethodCallException if function is not a callable property.
	 */
	public function __call($func, $params) {

		if (isset($this->$func) && is_callable($this->$func)) {

			$call = $this->$func;

			switch(count($params)) {
				case 0 :
					return $call();
				case 1 :
					return $call($params[0]);
				case 2 :
					return $call($params[0], $params[1]);
				case 3 :
					return $call($params[0], $params[1], $params[2]);
				case 4 :
					return $call($params[0], $params[1], $params[2], $params[3]);
				default :
					return call_user_func_array($call, $params);
			}
		}

		throw new \BadMethodCallException("Unknown method '$func'.");
	}

	/**
	 * If value is a closure, executes it before returning. Otherwise returns
	 * original value.
	 *
	 * @param mixed $var
	 * @return mixed Original value or result of closure.
	 */
	protected function result($var) {
		return ($var instanceof \Closure) ? $var() : $var;
	}

	/**
	 * __get()-like magic method for ArrayAccess.
	 *
	 * Subclasses could use this to, for example, allow access
	 * to protected or private properties.
	 *
	 * This function also works as a setter, e.g.
	 * $object['nonexistant'] = 'this works'
	 *
	 * @param mixed $index Offset index
	 * @return mixed
	 */
	protected function __offsetGet($index) {
		return null;
	}

}
