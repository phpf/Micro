<?php

namespace Phpf\Common;

interface iContainer {
	
	public function get($var);
	
	public function set($var, $val);
	
	public function exists($var);
	
	public function remove($var);
	
	public function import($data);
	
	public function toArray();
	
}
