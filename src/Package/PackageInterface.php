<?php

namespace Phpf\Package;

interface PackageInterface {
	
	public function getType();
	
	public function getId();
	
	public function getPath();
	
	public function getUid();
	
	public function isLoaded();
	
}
