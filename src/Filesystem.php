<?php

namespace Phpf;

use RuntimeException;
use InvalidArgumentException;

class Filesystem
{

	protected $basepath;
	
	protected $groups = array();
	
	protected $globs = array();

	protected $found = array();
	
	protected $working_group;

	protected $default_search_depth = 3;
	
	protected $group_default_depths = array();

	public function __construct($path) {
			
		$this->basepath = cleanpath($path);
	}
	
	/**
	 * Adds a directory path to a group.
	 * 
	 * @param string $path Directory path.
	 * @param string $group Group name to add dirpath to.
	 * @param int $depth Maximum recursion depth to use for this path. Default is 3.
	 * @return $this
	 */
	public function add($path, $group = null, $depth = null) {

		if (isset($this->working_group)) {
			$group = $this->working_group;
		} else if (! isset($group)) {
			throw new RuntimeException("Must set group or working group to add directory.");
		}

		if (! isset($this->groups[$group])) {
			$this->groups[$group] = array();
		}
		
		if (! isset($depth)) {
			if (isset($this->group_default_depths[$group])) {
				$depth = $this->group_default_depths[$group];
			} else {
				$depth = $this->default_search_depth;
			}
		}

		$this->groups[$group][cleanpath($path)] = $depth;

		return $this;
	}
	
	/**
	 * Attempts to locate a file in a given group's directories.
	 * 
	 * @param string $file File name to find (with or without extension).
	 * @param string $group Group name of directories to search within.
	 * @return string|null Filepath if found, otherwise null.
	 */
	public function locate($file, $group = null) {

		if (isset($this->working_group)) {
			$group = $this->working_group;
		} else if (! isset($group)) {
			throw new RuntimeException("Must set group or working group to add directory.");
		}
		
		if (isset($this->found[$group][$file])) {
			return $this->found[$group][$file];
		}

		if (! isset($this->groups[$group])) {
			throw new InvalidArgumentException("Unknown filesystem group $group.");
		}

		foreach ( $this->groups[$group] as $path => $depth ) {
			
			if ($found = $this->search($path, $file, $depth)) {
				return $this->found[$group][$file] = $found;
			}
		}
		
		return null;
	}
	
	/**
	 * Searches for a file by globbing recursively until the file is found,
	 * or until the maximum recusion depth is reached.
	 * 
	 * @param string $dir Path to the directory to search within.
	 * @param string $file File name to find (with or without extension).
	 * @param int $depth Maximum recursion depth for this search.
	 * @param int $depth_now Used interally.
	 * @return string|null Filepath if found, otherwise null.
	 */
	public function search($dir, $file, $depth = null, $depth_now = 1) {
		
		if (! isset($depth)) {
			$depth = $this->default_search_depth;
		}
		
		foreach( $this->glob($dir) as $item ) {
			if (false !== strpos($item, $file)) {
				// found file
				return $item;
			} else if ($depth_now <= $depth && DIRECTORY_SEPARATOR === substr($item, -1)) { // dirs marked
				// recurse some more
				if ($found = $this->search($item, $file, $depth, $depth_now+1)) {
					// found in subdir
					return $found;
				}
			}
		}
		
		return null;
	}
	
	/**
	 * Gets a glob of a directory.
	 * 
	 * @param string $dir Directory path to glob.
	 * @return array Glob of directory.
	 */
	public function glob($dir) {
		$dir = cleanpath($dir);
		if (isset($this->globs[$dir])) {
			return $this->globs[$dir];
		}
		return $this->globs[$dir] = glob($dir.'/*', GLOB_MARK|GLOB_NOSORT|GLOB_NOESCAPE);
	}
	
	/**
	 * Sets the recursive search depth for a given group.
	 * 
	 * @param string $group Name of the group.
	 * @param int $depth Default depth to search dirs recursively.
	 * @return $this
	 */
	public function setGroupDefaultSearchDepth($group, $depth) {
		$this->group_default_depths[$group] = $depth;
		return $this;
	}
	
	/**
	 * Sets the recursive search depth to use when none is set.
	 * 
	 * @param int $depth Default depth to search dirs recursively.
	 * @return $this
	 */
	public function setDefaultSearchDepth($depth) {
		$this->default_search_depth = (int)$depth;
		return $this;
	}
	
	/**
	 * Set the current working group. Allows you to omit the 'group'
	 * parameter in add() and locate().
	 * 
	 * @param string $group Group name.
	 * @return $this
	 */
	public function setWorkingGroup($group) {
		$this->working_group = $group;
		return $this;
	}

	/**
	 * Get the current working group.
	 * 
	 * @return string|null Group name, if set, otherwise null.
	 */
	public function getWorkingGroup() {
		return isset($this->working_group) ? $this->working_group : null;
	}

	/**
	 * Reset the current working group.
	 * 
	 * @return $this
	 */
	public function resetWorkingGroup() {
		unset($this->working_group);
		return $this;
	}
	
	/**
	 * Returns the basepath.
	 * 
	 * @return string Basepath of this Filesystem instance.
	 */
	public function getBasepath() {
		return $this->basepath;
	}
	
}
