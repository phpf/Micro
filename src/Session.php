<?php

namespace Phpf;

use ArrayAccess;
use Countable;
use Phpf\Session\Driver\SessionDriverInterface;

class Session implements ArrayAccess, Countable {
	
	protected $driver;
	
	/**
	 * Constructor
	 * @param iSessionDriver|null
	 */
	public function __construct(SessionDriverInterface $driver = null) {
		if (isset($driver)) {
			$this->setDriver($driver);
		}
	}
	
	/**
	 * Sets the session driver
	 * 
	 * @param iSessionDriver $driver
	 * @return $this
	 */
	public function setDriver(SessionDriverInterface $driver) {
		$this->driver = $driver;
		return $this;
	}
	
	/**
	 * Start the session.
	 * @return boolean
	 */
	public function start() {
		return $this->driver->start();
	}
	
	/**
	 * Whether session is started.
	 * @return boolean
	 */
	public function isStarted() {
		return $this->driver->isStarted();
	}
	
	/**
	 * Destroy the session.
	 * @return boolean
	 */
	public function destroy() {
		return $this->driver->destroy();
	}
	
	/**
	 * Get the session ID.
	 * @return string
	 */
	public function getId() {
		return $this->driver->getId();
	}
	
	/**
	 * Set the session ID.
	 * @param string $id
	 * @return $this
	 */
	public function setId($id) {
		$this->driver->setId($id);
		return $this;
	}
	
	/**
	 * Get the session name.
	 * @return string
	 */
	public function getName() {
		return $this->driver->getName();
	}
	
	/**
	 * Set the session name.
	 * @param string $name
	 * @return string
	 */
	public function setName($name) {
		$this->driver->setName($name);
		return $this;
	}
	
	/**
	 * Get a session variable.
	 * @param string $var
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($var, $default = null) {
		return $this->driver->get($var, $default);
	}
	
	/**
	 * Set a session variable.
	 * @param string $var
	 * @param mixed $val
	 * @return $this
	 */
	public function set($var, $val) {
		$this->driver->set($var, $val);
		return $this;
	}
	
	/**
	 * Whether a session variable exists.
	 * @param string $var
	 * @return boolean
	 */
	public function exists($var) {
		return $this->driver->exists($var);
	}
	
	/**
	 * Remove/delete a session variable.
	 * @param string $var
	 * @return $this
	 */
	public function remove($var) {
		$this->driver->remove($var);
		return $this;
	}
	
	/**
	 * @param $index 
	 * @param $newval 
	 * @return void
	 */
	public function offsetSet($index, $newval) {
		$this->driver->set($index, $newval);
	}

	/**
	 * @param $index 
	 * @return mixed
	 */
	public function offsetGet($index) {
		return $this->driver->get($index);
	}

	/**
	 * @param $index 
	 * @return void
	 */
	public function offsetUnset($index) {
		$this->driver->remove($index);
	}

	/**
	 * @param $index 
	 * @return boolean
	 */
	public function offsetExists($index) {
		return $this->driver->exists($index);
	}
	
	/**
	 * @return integer
	 */
	public function count() {
		return $this->driver->count();
	}
	
	public function __get($var) {
		return $this->get($var);
	}
	
	public function __set($var, $val) {
		$this->set($var, $val);
	}
	
	public function __isset($var) {
		return $this->exists($var);
	}
	
	public function __unset($var) {
		$this->remove($var);
	}
	
}
