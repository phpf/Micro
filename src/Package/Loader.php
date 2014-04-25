<?php

namespace Phpf\Package;

use Phpf\App;

class Loader
{
	
	/**
	 * Application instance.
	 * @var \Phpf\App
	 */
	protected $app;

	/**
	 * Package loader configuration settings.
	 * @var array
	 */
	protected $config;
	
	/**
	 * Package loader configuration defaults.
	 * @var array
	 */
	protected $default_config = array(
		'dirs' => array(
			'views' => 'views',
		), 
		'files' => array(
			'config/routes.php', 
			'config/tables.php', 
		), 
	);
	
	/**
	 * Constructs the loader by setting application as property (by reference)
	 * and configuring the package loading settings from the "packages-config"
	 * item of the app config.
	 * 
	 * @param \Phpf\App &$app Application instance.
	 * @return void
	 */
	public function __construct(App &$app) {
		$this->app = &$app;
		$this->configure($app->get('config')->get('packages-config'));
	}

	/**
	 * Loads a package object.
	 * 
	 * @param PackageInterface $package Package object to load.
	 * @return $this
	 */
	public function load(PackageInterface $package) {

		$path = rtrim($package->getPath(), '/\\') . '/';
		
		// Include a file in base directory with same name as package, if it exists
		\Phpf\App::includeInScope($path.basename($path).'.php', true);
		
		// Include a 'bootstrap.php' file if exists
		\Phpf\App::includeInScope($path.'bootstrap.php', true);
		
		// Include config files provided by package
		foreach ( $this->config['files'] as $file ) {
			\Phpf\App::includeInScope($path.ltrim($file, '/\\'), true);
		}
		
		// Add directories provided by package
		foreach ( $this->config['dirs'] as $group => $dir ) {
			$dir = trim($dir, '/\\');
			if (is_dir($path.$dir)) {
				$this->app->get('filesystem')->add($path.$dir.'/', $group);
			}
		}
		
		// Set the package to loaded
		$package->setLoaded(true);

		return $this;
	}
	
	/**
	 * Merges package loading configuration settings from app with defaults.
	 * 
	 * Array is set as '$config' property.
	 * 
	 * @param array|ArrayAccess $config Configuration object/array ('packages-config' item).
	 * @return void
	 */
	protected function configure($config) {

		if (isset($config['dirs'])) {
			$dirs = $config['dirs'];
		} else {
			$dirs = $this->default_config['dirs'];
		}

		if (isset($config['files'])) {
			$files = $config['files'];
		} else {
			$files = $this->default_config['files'];
		}

		$this->config = array('dirs' => $dirs, 'files' => $files);
	}

}
