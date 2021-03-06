<?php

namespace Phpf\Route\Exception;

class HttpMethodNotAllowed extends \RuntimeException {
	
	public function setRequestedMethod( $method ){
		$this->requested_method = $method;
	}
	
	public function setAllowedMethods( array $methods ){
		$this->allowed_methods = $methods;
	}
	
	public function getRequestedMethod(){
		return isset($this->requested_method) ? $this->requested_method : null;
	}
	
	public function getAllowedMethods(){
		return isset($this->allowed_methods) ? $this->allowed_methods : null;
	}
	
	public function getAllowedMethodsString(){
		
		if ( ! isset($this->allowed_methods) ){
			return '';
		}
		
		return implode(', ', $this->getAllowedMethods());
	}
		
	public function __toString(){
			
		$msg = "HTTP method {$this->getRequestedMethod()} is not permitted for this route. ";
		
		if ( isset($this->allowed_methods) ){
			$msg .= "\nAllowed methods for this route: " . $this->getAllowedMethodsString();
		}
		
		return $msg;
	}
	
}
