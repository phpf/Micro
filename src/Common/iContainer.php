<?php
/**
 * @package Phpf\Common
 */
namespace Phpf\Common;

interface iContainer {
	
	/**
	 * Get an entry.
	 * 
	 * @param string $var
	 * @return mixed
	 */
	public function get($var);
	
	/**
	 * Set an entry.
	 * 
	 * @param string $var
	 * @param mixed $val
	 * @return $this
	 */
	public function set($var, $val);
	
	/**
	 * Whether an entry exists.
	 * 
	 * @param string $var
	 * @return boolean
	 */
	public function exists($var);
	
	/**
	 * Remove an entry.
	 * 
	 * @param string $var
	 * @return $this
	 */
	public function remove($var);
	
	/**
	 * Import entries from an array or object.
	 * 
	 * @param array|object $data
	 * @return $this
	 */
	public function import($data);
	
	/**
	 * Get the entries as an array, optionally indexed.
	 * 
	 * @param boolean $indexed Default false.
	 * @return array Entries as assoc. (default) or indexed array.
	 */
	public function toArray($indexed = false);
	
}
