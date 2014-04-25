<?php
/**
 * @package Phpf\Common
 */
namespace Phpf\Common;

/**
 * Static object registry.
 */
class StaticRegistry
{

	protected static $data = array();

	/**
	 * Sets an object by dot-notated path.
	 * Each dot ('.') indicates a child array dimension.
	 */
	public static function set($dotpath, $object) {

		if (false === strpos($dotpath, '.')) {
			return static::$data[$dotpath] = $object;
		}

		return \Phpf\Util\Arr::dotSet(static::$data, $dotpath, $object);
	}

	/**
	 * Returns a registered object by its dot-notated path.
	 */
	public static function get($dotpath) {

		if (false === strpos($dotpath, '.')) {
			return isset(static::$data[$dotpath]) ? static::$data[$dotpath] : null;
		}

		return \Phpf\Util\Arr::dotGet(static::$data, $dotpath);
	}

	/**
	 * Returns true if a object exists, given by its dot-notated path.
	 */
	public static function exists($dotpath) {
		return (bool)static::get($dotpath);
	}

	/**
	 * Returns all registered objects.
	 * Mostly for debugging.
	 */
	public static function getAll() {
		return static::$data;
	}

	/**
	 * Adds an object to a group.
	 *
	 * Optionally specify a $uid to access it by such (Default: classname).
	 *
	 * This is basically identical to: Registry::set( '{{group}}.{{uid}}', $object );
	 */
	public static function addToGroup($group, $object, $uid = null) {

		if (empty($uid))
			$uid = get_class($object);

		static::$data[$group][$uid] = $object;
	}

	/**
	 * Returns an object by uid/class in a particular group.
	 *
	 * Same as: Registry::get( '{{group}}.{{uid}}' );
	 */
	public static function getFromGroup($group, $uid) {
		return isset(static::$data[$group][$uid]) ? static::$data[$group][$uid] : null;
	}

	/**
	 * Returns array of objects registered to a particular group.
	 */
	public static function getGroup($group) {
		return static::$data[$group];
	}

}
