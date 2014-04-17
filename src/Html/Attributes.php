<?php
/**
 * @package Html
 */

namespace Phpf\Html;

use ArrayAccess;
use Phpf\Util\Str;

/**
 * Attributes is just an object with 1 variable called $attributes.
 */
class Attributes implements ArrayAccess
{

	protected $attributes = array();

	/**
	 * Returns array or string of attribute(s) as a string.
	 *
	 * This is the preferred method of rendering attributes.
	 *
	 * @param string|array $args The attributes as a string or assoc. array.
	 * @return string The attribute string with a leading space.
	 */
	public static function toString($args) {
		return self::arrayString(self::parse($args));
	}

	/**
	 * Parses a string of html attributes into a (potentially) nested array.
	 *
	 * @param string $attrStr HTML attribute string to parse
	 * @return array Single or multi-dimensional assoc. array.
	 */
	public static function parse($str) {

		if (! is_string($str))
			return $str;

		// only 1 attribute => prefix with space
		if (false === strpos($str, '" '))
			$str = ' '.$str;

		// split at double quote followed by whitespace
		$array = explode('" ', $str);

		$return = array();
		foreach ( $array as $attr ) {

			// split name and value
			$keyVals = explode('="', $attr);

			$key = trim(array_shift($keyVals));

			// $keyVals is now just values
			foreach ( $keyVals as $val ) {

				// remove quotes from value
				$val = trim($val, '" ');

				// if spaces, multiple values (e.g. class)
				if (false !== strpos($val, ' '))
					$val = explode(' ', $val);

				$return[$key] = $val;
			}
		}

		return $return;
	}

	/**
	 * Returns multiple attribute name/value pairs as a single string.
	 *
	 * @param array $attributes Assoc. array of name/value pairs.
	 * @return string The attributes string with a leading space.
	 */
	public static function arrayString($attributes) {

		if (empty($attributes))
			return '';

		$s = '';

		if (! is_array($attributes) && ! $attributes instanceof \Traversable) {
			$attributes = (array)$attributes;
		}

		foreach ( $attributes as $attr => $value ) {
			$s .= static::string($attr, $value);
		}

		return ' '.ltrim($s);
	}

	/**
	 * Returns an attribute name/value pair as a string.
	 *
	 * @param string $attr The attribute name.
	 * @param string|array $value The attr value. If array, it is delimited by
	 * whitespace.
	 * @return string The attribute string with a leading space.
	 */
	public static function string($attr, $value) {
		if (is_array($value)) {
			$value = implode(' ', $value);
		}
		return ' '.$attr.'="'.static::escape($value).'"';
	}

	/**
	 * Escapes an attribute value.
	 *
	 * Note htmlentities() is applied with ENT_QUOTES in order to avoid
	 * XSS through single-quote injection. However, it does not prevent strings
	 * containing javascript within single quotes on certain attributes like 'href'.
	 * Hence the strict option.
	 */
	public static function escape($str, $strict = false) {
		$str = htmlentities(Str::esc(trim($str)), ENT_QUOTES);
		return $strict ? str_replace(array('javascript:', 'document.write'), '', $str) : $str;
	}

	/**
	 * Sets an attribute
	 */
	public function setAttribute($name, $value) {

		if (is_string($value)) {

			$value = trim($value);

			if (false !== strpos($value, ' '))
				$value = explode(' ', $value);
		}

		$this->attributes[$name] = $value;

		return $this;
	}

	/**
	 * Sets a data-* attribute.
	 */
	public function setDataAttribute($name, $value) {

		if (0 !== strpos($name, 'data-')) {
			$name = 'data-'.$name;
		}

		return $this->setAttribute($name, $value);
	}

	/**
	 * Sets an attribute only if it doesn't exist.
	 */
	public function addAttribute($name, $value) {

		if (! isset($this->attributes[$name])) {
			$this->setAttribute($name, $value);
		}

		return $this;
	}

	/**
	 * Returns attribute if set, otherwise null.
	 */
	public function getAttribute($name) {
		return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
	}

	/**
	 * Whether element has a certain attribute.
	 */
	public function hasAttribute($name) {
		return isset($this->attributes[$name]);
	}

	/**
	 * Removes an attribute.
	 */
	public function removeAttribute($name) {
		unset($this->attributes[$name]);
		return $this;
	}

	/**
	 * Sets element attributes given array or string.
	 *
	 *	e.g.		$attrs	=	array('id' => 'this', 'href' => 'that');
	 *	same as:	$attrs	=	'id="this" href="that"'
	 */
	public function setAttributes($attrs = null) {

		if (empty($attrs))
			return;

		if (is_string($attrs))
			$attrs = $this->parseAttributes($attrs);

		foreach ( $attrs as $k => $v ) {
			$this->setAttribute($k, $v);
		}

		return $this;
	}

	/**
	 * Adds attributes given array or string
	 */
	public function addAttributes($attrs) {

		if (is_string($attrs))
			$attrs = $this->parseAttributes($attrs);

		foreach ( $attrs as $k => $v ) {
			$this->addAttribute($k, $v);
		}

		return $this;
	}

	/**
	 * Returns all set attributes
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 *	Whether element has any attributes
	 */
	public function hasAttributes() {
		return ! empty($this->attributes);
	}

	/**
	 * Parse string of attributes to array.
	 * OO version of self::parse();
	 */
	public function parseAttributes($attrs) {
		return self::parse($attrs);
	}

	// addClass
	public function addClass($name) {

		if (isset($this->attributes['class']) && is_string($this->attributes['class'])) {
			$this->attributes['class'] = array($this->attributes['class']);
		}

		$this->attributes['class'][] = $name;

		return $this;
	}

	// hasClass
	public function hasClass($name) {
		return ! empty($this->attributes['class']) && in_array($name, $this->attributes['class']);
	}

	// getClasses
	public function getClasses() {
		return $this->getAttribute('class');
	}

	// addClasses
	public function addClasses($classes) {

		if (is_array($classes)) {
			foreach ( $classes as $class ) {
				$this->addClass($class);
			}
		} else {
			$this->addClass($classes);
		}

		return $this;
	}

	// hasClasses
	public function hasClasses() {
		return ! empty($this->attributes['class']);
	}

	/**
	 * Returns formatted string value of a single attribute.
	 */
	public function getAttributeString($attr) {

		if (! $value = $this->getAttribute($attr))
			return '';

		return self::string($attr, $value);
	}

	/**
	 * Returns string value of attributes, except those in $exclude array.
	 */
	public function getAttributesString() {

		return self::arrayString($this->attributes);
	}

	/**
	 * @alias setAttribute()
	 */
	public function setAttr($name, $value) {
		return $this->setAttribute($name, $value);
	}

	/**
	 * @alias addAttribute()
	 */
	public function addAttr($name, $value) {
		return $this->addAttribute($name, $value);
	}

	/**
	 * @alias getAttribute()
	 */
	public function getAttr($name) {
		return $this->getAttribute($name);
	}

	/**
	 * @alias hasAttribute()
	 */
	public function hasAttr($name) {
		return $this->hasAttribute($name);
	}

	// ArrayAccess

	public function offsetGet($index) {
		return $this->attributes[$index];
	}

	public function offsetSet($index, $newval) {
		$this->attributes[$index] = $newval;
	}

	public function offsetExists($index) {
		return isset($this->attributes[$index]);
	}

	public function offsetUnset($index) {
		unset($this->attributes[$index]);
	}

}
