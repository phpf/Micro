<?php

namespace Phpf\Common;

interface iEventable {
	
	public function on($event, $callback);
	
	public function trigger($event);
	
}
