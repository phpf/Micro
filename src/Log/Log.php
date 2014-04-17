<?php

namespace Phpf\Log;

class Log {
	
	const ERROR = 1;
	
	const INFO = 2;
	
	const DEPRECATED = 4;
	
	protected $driver;
	
	public function setDriver(LogDriverInterface $driver) {
		$this->driver = $driver;
		return $this;
	}
	
	public function log($message, $severity) {
		$this->driver->log($message, $this->getSeverityString($severity));
		return $this;
	}
	
	public function error($message) {
		return $this->log($message, static::ERROR);
	}

	public function info($message) {
		return $this->log($message, static::INFO);
	}

	public function deprecated($message) {
		return $this->log($message, static::DEPRECATED);
	}
	
	protected function getSeverityString($severity) {
		$str = '';
		if ($severity & static::ERROR) {
			$str .= 'ERROR ';
		}
		if ($severity & static::INFO) {
			$str .= 'INFO ';
		}
		if ($severity & static::DEPRECATED) {
			$str .= 'DEPRECATED ';
		}
		return $str;
	}
	
}
