<?php

namespace Phpf\Log\Driver;

class File implements LogDriverInterface
{

	public $file;

	public function __construct($file) {

		if (! is_writable($file)) {
			throw new \InvalidArgumentException("Unwritable file cannot be used as log '$file'.");
		}
		
		$this->file = $file;
	}

	public function log($message, $severity) {
			
		$message = date('Y-m-d H:i:s')."\t$severity\t$message\n";
		
		file_put_contents($this->file, $message, FILE_APPEND | LOCK_EX);
		
		return true;
	}

}
