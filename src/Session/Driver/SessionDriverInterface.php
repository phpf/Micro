<?php

namespace Phpf\Session\Driver;

interface SessionDriverInterface {
	
	public function get($var, $default = null);
	
	public function set($var, $val);
	
	public function exists($var);
	
	public function remove($var);
	
	public function count();
	
	public function start();
	
	public function destroy();
	
	public function isStarted();
	
	public function getId();
	
	public function setId($id);
	
	public function getName();
	
	public function setName($name);
	
}
