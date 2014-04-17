<?php

namespace Phpf\View;

use Phpf\Util\Str;
use Phpf\Util\Html;

class Assets {
	
	protected $assets = array();
	
	protected $enqueued = array();
	
	protected $rendered = array();
	
	public function register($handle, $uri, $attrs = array(), $enqueue = false){
		$this->assets[$handle] = array($uri, $attrs);
		if ($enqueue) {
			$this->enqueue($handle);
		}
		return $this;
	}
	
	public function isRegistered($handle) {
		return isset($this->assets[$handle]);
	}
	
	public function isEnqueued($handle) {
		return isset($this->enqueued[$handle]);
	}
	
	public function isRendered($handle) {
		return isset($this->rendered[$handle]);
	}
	
	public function enqueue($handle) {
		
		if (! $this->isRegistered($handle)) {
			trigger_error("Cannot enqueue unknown asset '$handle'.", E_USER_NOTICE);
			return $this;
		}
		
		$this->enqueued[$handle] = $handle;
		
		return $this;
	}
	
	public function dequeue($handle) {
			
		if ($this->isRegistered($handle)) {
			unset($this->enqueued[$handle]);
		}
		return $this;
	}
	
	public function render($handle){
		
		if ($this->isRendered($handle)) {
			return '';
		}
		
		if (! $this->isRegistered($handle)) {
			trigger_error("Cannot render asset '$handle' - not set.", E_USER_NOTICE);
			return '';
		}
		
		// get url & attributes
		list($url, $attrs) = $this->assets[$handle];
		
		$attrs['id'] = $handle;
		
		if (0 !== strpos($url, '/') && false === strpos($url, '://')) {
			$url = \Phpf\Util\Path::url($url);
		}
		
		$html = '';
		
		if (Str::endsWith($url, '.css')) {
			$html = Html::link($url, $attrs);
		} elseif (Str::endsWith($url, '.js')) {
			$html = Html::script($url, $attrs);
		}
		
		$this->rendered[$handle] = $handle;
		
		return $html;
	}
	
	public function renderEnqueued() {
		$s = '';
		foreach($this->enqueued as $handle) {
			$s .= $this->render($handle);
		}
		return $s;
	}
	
}
