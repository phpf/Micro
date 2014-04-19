<?php
/**
 * @package Phpf.Micro
 * @version 0.1
 * 
 * About
 * ======================
 * This file contains functions that use various Phpf components.
 * 
 * Functions are organized in two sections:
 * 
 * 1. Components: Functions that operate on a specific component or group of components.
 * 		A. Application/environment
 * 		B. Session
 * 		C. Registry
 * 		D. Events
 * 		E. Routes
 * 		F. Database
 * 		G. Cache
 * 
 * 2. Utility: General-use functions that perform a generic type of operation.
 * 		A. Strings
 * 		B. Paths
 * 		C. Security
 * 		D. Arrays
 * 		E. HTML
 */

/** ================================================
				1. COMPONENTS
================================================= */

/** ================================
	A. Application/environment
================================= */

/**
 * Retrieve an application component.
 */
function component($name) {
	return \Phpf\App::instance()->get($name);
}

/**
 * Create a (lazy) class alias.
 */
function alias($class, $alias) {
	\Phpf\App::instance()->alias($class, $alias);
}

/**
 * Retrieve a package object.
 */
function package($uid) {
	return \Phpf\App::instance()->get('packageManager')->get($uid);
}

/**
 * Registers an autoloader for given namespace.
 */
function autoloader_register($namespace, $path) {
	$loader = \Phpf\Common\Autoloader::instance($namespace);
	$loader->setPath($path);
	$loader->register();
}

/**
 * Returns an autoloader instance for a namespace.
 */
function autoloader($namespace) {
	return \Phpf\Common\Autoloader::instance($namespace);
}

/** ============================
	B. Session
============================= */

/**
 * Sets a session variable.
 */
function session_set($var, $val) {
	$session = \App::instance()->get('session');
	return $session->set($var, $val);
}

/**
 * Returns a session variable.
 */
function session_get($var) {
	$session = \App::instance()->get('session');
	return $session->get($var);
}

/** ============================
	C. Registry
============================= */

/**
 * Registers an object with Registry.
 */
function registry_set($key, $object) {
	\Phpf\Common\StaticRegistry::set($key, $object);
}

/**
 * Returns an object from Registry given its key.
 */
function registry_get($key) {
	return \Phpf\Common\StaticRegistry::get($key);
}

/** ============================
	D. Events
============================= */

/**
 * Bind a callback to an event.
 */
function bind($event, $callback, $priority = 10) {
	\Phpf\App::instance()->get('events')->on($event, $callback, $priority);
}

/**
 * Trigger an event.
 */
function trigger($event/**[, $arg1 [, ...]]*/) {
	$args = func_get_args();
	array_shift($args);
	\Phpf\App::instance()->get('events')->triggerArray($event, $args);
}

/** ============================
	E. Routes
============================= */

/**
 * Add a route.
 */
function route($uri, $controller, $action, array $methods = array('GET', 'HEAD', 'POST', 'PUT', 'DELETE')) {
	\Phpf\App::instance()->get('router')
		->addRoute($uri, array('controller' => $controller, 'action' => $action, 'methods' => $methods));
}

/**
 * Add a route endpoint.
 */
function endpoint($path, Closure $closure) {
	\Phpf\App::instance()->get('router')->endpoint($path, $closure);
}

/** ============================
	F. Database
============================= */

/**
 * Creates and registers a database table schema.
 */
function db_table_schema($name, array $columns, $primary_key = 'id', array $unique_keys = null, array $keys = null){
	
	$schema = array(
		'table_basename' => $name,
		'columns' => array(),
		'primary_key' => $primary_key,
		'unique_keys' => array(),
		'keys' => array()
	);
	
	foreach($columns as $col => $dtype){
		$schema['columns'][$col] = strtolower($dtype);
	}
	
	if ( isset($unique_keys) ){
		foreach($unique_keys as $idx => $key){
			if (is_numeric($idx)){
				$schema['unique_keys'][$key] = $key;
			} else {
				$schema['unique_keys'][$idx] = $key;
			}
		}
	}
	
	if ( isset($keys) ){
		foreach($keys as $idx => $key){
			if (is_numeric($idx)){
				$schema['keys'][$key] = $key;
			} else {
				$schema['keys'][$idx] = $key;
			}
		}
	}
	
	return db_register_schema($schema);
}
		
/**
 * Creates and registers a table schema.
 */
function db_register_schema( array $data ){
	if ($schema = new \Phpf\Database\Schema($data)) {
		\Phpf\Database::instance()->registerSchema($schema);
		return true;
	}
	return false;
}
	
/**
 * Returns a Schema instance
 */
function db_get_schema($name) {
	return \Phpf\Database::instance()->schema($name);	
}

/**
 * Returns a Table instance
 */
function db_get_table($name) {
	return \Phpf\Database::instance()->table($name);	
}

/**
 * Returns array of installed table names.
 */
function db_get_installed_tables() {
	return \Phpf\Database::instance()->getInstalledTables();
}

/**
 * Returns number of queries run during current request.
 */
function db_get_query_count() {
	return \Phpf\Database::instance()->num_queries;
}

/** ============================
	G. Cache
============================= */

/**
 * Returns a cached value.
 */
function cache_get($id, $group = \Phpf\Cache::DEFAULT_GROUP) {
	return \Phpf\Cache::instance()->get($id, $group);
}

/**
 * Sets a cache value.
 */
function cache_set($id, $value, $group = \Phpf\Cache::DEFAULT_GROUP, $ttl = \Phpf\Cache::DEFAULT_TTL) {
	return \Phpf\Cache::instance()->set($id, $value, $group, $ttl);
}

/**
 * Returns whether a givne key is cached.
 */
function cache_isset($id, $group = \Phpf\Cache::DEFAULT_GROUP) {
	return \Phpf\Cache::instance()->exists($id, $group);
}

/**
 * Deletes a cache value.
 */
function cache_unset($id, $group = \Phpf\Cache::DEFAULT_GROUP) {
	return \Phpf\Cache::instance()->delete($id, $group);
}

/**
 * &Alias of cache_unset()
 */
function cache_delete($id, $group = \Phpf\Cache::DEFAULT_GROUP) {
	return \Phpf\Cache::instance()->delete($id, $group);
}

/**
 * Increments a cache value.
 */
function cache_incr($id, $value = 1, $group = \Phpf\Cache::DEFAULT_GROUP, $ttl = \Phpf\Cache::DEFAULT_TTL) {
	return \Phpf\Cache::instance()->incr($id, $group);
}

/**
 * Decrements a cache value.
 */
function cache_decr($id, $value = 1, $group = \Phpf\Cache::DEFAULT_GROUP, $ttl = \Phpf\Cache::DEFAULT_TTL) {
	return \Phpf\Cache::instance()->decr($id, $group);
}

/**
 * Flushes all values from a cache group.
 */
function cache_flush_group($group) {
	return \Phpf\Cache::instance()->flushGroup($group);
}

/**
 * Flushes the entire cache, or only a group if given.
 */
function cache_flush($group = null) {
	if (! empty($group))
		return \Phpf\Cache::instance()->flushGroup($group);
	return \Phpf\Cache::instance()->flush();
}

/** ================================================
				2. Utility
================================================= */

/** ============================
	A. Strings
============================= */

/**
 * Escape a string using fairly aggressive rules.
 * Strips all tags and converts to html entities.
 *
 * @param string $string The string to sanitize.
 * @param string $flag Strip or do nothing with high ASCII chars. (default:
 * strip)
 * @return string Sanitized string.
 */
function str_esc($string, $flag = Str::ESC_ASCII) {
	return \Phpf\Util\Str::esc($string, $flag);
}

/**
 * Strips non-alphanumeric characters from a string.
 * Add characters to $extras to preserve those as well.
 * Extra chars should be escaped for use in preg_*() functions.
 */
function str_esc_alnum($str, array $extras = null) {
	return \Phpf\Util\Str::escAlnum($str, $extras);
}

/**
 * Escapes text for SQL LIKE special characters % and _.
 *
 * @param string $text The text to be escaped.
 * @return string text, safe for inclusion in LIKE query.
 */
function str_esc_sql_like($string) {
	return \Phpf\Util\Str::escSqlLike($string);
}

/**
 * Formats a string by injecting non-numeric characters into
 * the string in the positions they appear in the template.
 *
 * @param string $string The string to format
 * @param string $template String format to apply
 * @return string Formatted string.
 */
function str_format($string, $template) {
	return \Phpf\Util\Str::format($string, $template);
}

/**
 * Generate a random string from one of several of character pools.
 *
 * @param int $length Length of the returned random string (default 16)
 * @param string $charlist Type of characters - {@see Phpf\Util\Rand} constants.
 * @return string A random string
 */
function str_rand($length = 16, $charlist = 'alnum') {
	return \Phpf\Util\Rand::str($length, $charlist);
}

/**
 * Converts a string to a PEAR-like class name. (e.g. "View_Template_Controller")
 */
function str_pearclass($str) {
	return \Phpf\Util\Str::pearClass($str);
}

/**
 * Converts a string to "snake_case"
 */
function str_snakecase($str) {
	return \Phpf\Util\Str::snakeCase($str);
}

/**
 * Converts a string to "StudlyCaps"
 */
function str_studlycaps($str) {
	return \Phpf\Util\Str::studlyCaps($str);
}

/**
 * Converts a string to "camelCase"
 */
function str_camelcase($str) {
	return \Phpf\Util\Str::camelCase($str);
}

/**
 * Formats a phone number based on string lenth.
 */
function phone_format($phone) {
	return \Phpf\Util\Str::formatPhone($phone);
}

/**
 * Formats a hash/digest based on string length.
 */
function hash_format($hash) {
	return \Phpf\Util\Str::formatHash($hash);
}

/**
 * Serialize data, if needed.
 *
 * @param mixed $data Data that might be serialized.
 * @return mixed A scalar data
 */
function maybe_serialize($data) {
	return \Phpf\Util\Str::maybeSerialize($data);
}

/**
 * Unserialize value only if it was serialized.
 *
 * @param string $value Maybe unserialized original, if is needed.
 * @return mixed Unserialized data can be any type.
 */
function maybe_unserialize($value) {
	return \Phpf\Str::maybeUnserialize($value);
}

/**
 * Check value to find if it was serialized.
 *
 * @param mixed $data Value to check to see if was serialized.
 * @param bool $strict Optional. Whether to be strict about the end of the
 * string. Defaults true.
 * @return bool False if not serialized and true if it was.
 */
function is_serialized($data, $strict = true) {
	return \Phpf\Util\Str::isSerialized($data, $strict);
}

/** ============================
	B. Paths
============================= */

/**
 * Url-safe Base64 encode.
 */
function base64_encode_urlsafe($str) {
	return \Phpf\Util\Path::safeBase64Encode($str);
}

/**
 * Url-safe Base64 decode.
 */
function base64_decode_urlsafe($str) {
	return \Phpf\Util\Path::safeBase64Decode($str);
}

/**
 * Converts a path to URL
 */
function path_url($path, $protocol = 'http') {
	return \Phpf\Util\Path::url($path, $protocol);
}

/** ============================
	C. Security
============================= */

/**
 * Generates a random string with given number of bytes.
 */
function rand_bytes($length = 12, $strong = true) {
	return \Phpf\Util\Rand::bytes($length, $strong);
}

/**
 * Generate a UUID
 * 32 characters (a-f and 0-9) in format 8-4-4-12.
 */
function generate_uuid() {
	return \Phpf\Util\Security::generateUuid();
}

/**
 * Generates a 32-byte base64-encoded random string.
 */
function generate_csrf_token() {
	return \Phpf\Util\Security::generateCsrfToken();
}

/** ============================
	D. Arrays
============================= */

/**
 * Retrieves a value from $array given its path in dot notation
 */
function array_get(array &$array, $dotpath) {
	return \Phpf\Util\Arr::dotGet($array, $dotpath);
}

/**
 * Sets a value in $array given its path in dot notation.
 */
function array_set(array &$array, $dotpath, $value) {
	return \Phpf\Util\Arr::dotSet($array, $dotpath, $value);
}

/**
 * Merge user defined arguments into defaults array.
 * 
 * Like the WordPress function wp_parse_args().
 *
 * @param string|array $args Value to merge with $defaults
 * @param array $defaults Array that serves as the defaults.
 * @return array Merged user defined values with defaults.
 */
function parse_args($args, $defaults = '') {
	return \Phpf\Util\Arr::parse($args, $defaults);
}

/**
 * Filters a list of objects, based on a set of key => value arguments.
 *
 * @param array $list An array of objects to filter
 * @param array $args An array of key => value arguments to match against each
 * object
 * @param string $operator The logical operation to perform:
 *    'AND' means all elements from the array must match;
 *    'OR' means only one element needs to match;
 *    'NOT' means no elements may match.
 *   The default is 'AND'.
 * @return array
 */
function list_filter($list, $args = array(), $operator = 'AND', $keys_exist_only = false) {
	return \Phpf\Util\Arr::filter($list, $args, $operator, $keys_exist_only);
}

/**
 * Pluck a certain field out of each object in a list.
 *
 * @param array $list A list of objects or arrays
 * @param int|string $field A field from the object to place instead of the
 * entire object
 * @return array
 */
function list_pluck($list, $field) {
	return \Phpf\Util\Arr::pluck($list, $field);
}

/**
 * Pluck a certain field out of each object in a list by reference.
 * This will change the values of the original array/object list.
 *
 * @param array $list A list of objects or arrays
 * @param int|string $field A field from the object to place instead of the
 * entire object
 * @return array
 */
function list_pluck_ref(&$list, $field) {
	return \Phpf\Util\Arr::pluckRef($list, $field);
}

/** ============================
	E. HTML
============================= */

/**
 * Returns an HTML tag with given content.
 * 
 * @param string $tag The HTML tag (default: 'div')
 * @param array $attributes The as an assoc. array. (optional)
 * @param string $content The content to place inside the tag.
 * @return string The HTML tag wrapped around the given content.
 */
function html( $tag, $attributes = array(), $content = '' ){
	return \Phpf\Html\Element::open($tag, $attributes) . $content . '</'.$tag.'>';
}

/**
 * Returns a "<a>" HTML element.
 */
function html_a($content, $href, $attributes = array()) {
	return \Phpf\Util\Html::a($content, $href, $attributes);
}

/**
 * Returns a "<script>" HTML element.
 */
function html_script($url, $attrs = array()) {
	return \Phpf\Util\Html::script($url, $attrs);
}

/**
 * Returns a "<link>" HTML element.
 */
function html_link($url, $attrs = array()) {
	return \Phpf\Util\Html::link($url, $attrs);
}

/**
 * Returns a "<input>", "<select>", or "<textarea>" HTML element.
 */
function html_input( $type, $attributes = array(), $content = '' ){
	$el = new \Phpf\Html\Input($type);
	$el->setAttributes($attributes);
	$el->setContent($content);
	return $el;
}

/**
 * Returns a "<script>" HTML element which sets JS var $varname to JSON-encoded data.
 */
function html_json($varname, $data) {
	$varname = str_esc_alnum($varname);
	return "<script>var $varname=". json_encode($data, JSON_FORCE_OBJECT) .';</script>';
}

/**
 * Returns a "<ul>" HTML element.
 */
function html_ul( array $items, $ul_attrs = array(), $li_attrs = array() ){
	$s = \Phpf\Html\Element::open('ul', $ul_attrs);
	foreach($items as $item){
		$s .= \Phpf\Html\Element::open('li', $li_attrs) . $item['text'] . '</li>';
	}
	$s .= '</ul>';
	return $s;
}
