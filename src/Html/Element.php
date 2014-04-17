<?php

namespace Phpf\Html;

/**
 * The base HTML element class
 *
 * @package Phpf.Html
 */
class Element extends Attributes {

	protected $tag;

	protected $content = null;

	protected $wrap_before;

	protected $wrap_after;
	
	protected $default_tag = 'div';

	static protected $styles = array();

	static protected $isHtml5 = true;

	static protected $selfClosingTags = array('hr', 'br', 'input', 'meta', 'base', 'basefont', 'col', 'frame', 'link', 'param');
	
	/**
	 * Adds a style that can be applied to elements.
	 * 
	 * Styles are an array of tags as keys, where the
	 * value is an assoc. array of the default attributes
	 * for that tag.
	 */
	public static function addStyle($style, array $args) {
		static::$styles[$style] = $args;
		return $this;
	}
	
	/**
	 * Returns an opening HTML tag, (possibly) with attributes.
	 * 
	 * @param string $tag The HTML tag (default: 'div')
	 * @param array|string $attributes The as an assoc. array. (optional)
	 * @return string The opening HTML tag string.
	 */
	public static function open( $tag, $attributes = array() ){
		return '<' . $tag . (empty($attributes) ? '' : Attributes::toString($attributes)) . '>';
	}
	
	/**
	 * Returns a closing HTML tag.
	 * 
	 * @param string $tag The HTML tag (default: 'div')
	 * @return string The closing HTML tag string.
	 */
	public static function close( $tag ){
		return '</' . $tag . '>';	
	}
	
	/**
	 * Returns an HTML tag with given content.
	 * 
	 * @param string $tag The HTML tag (default: 'div')
	 * @param array $attributes The as an assoc. array. (optional)
	 * @param string $content The content to place inside the tag.
	 * @return string The HTML tag wrapped around the given content.
	 */
	public static function tag( $tag, $attributes = array(), $content = '' ){
		return static::open($tag, $attributes) . $content . static::close($tag);
	}
	
	/*
	 * Sets tag if given.
	 */
	public function __construct($tag = null) {
		if ( null !== $tag )
			$this->setTag($tag);
	}

	/**
	 * Sets tag to use for element.
	 */
	public function setTag($tag) {
		$this->tag = $tag;
		return $this;
	}

	/**
	 * Returns true if element is a self-closing tag.
	 */
	public function isSelfClosing() {
		return in_array($this->tag, static::$selfClosingTags);
	}

	/** 
	 * Should we use HTML5 spec?
	 */
	public function isHtml5() {
		return static::$isHtml5;
	}

	/**
	 * Sets element content as string.
	 */
	public function setContent($str) {
			
		if (! $this->isSelfClosing()) {
			
			if ($str instanceof Element)
				$str = $str->__toString();
			
			$this->content = $str;
		}
		
		return $this;
	}
	
	/**
	 * Appends a string to existing content.
	 */
	public function addContent($str) {
			
		if (! $this->isSelfClosing()) {
			
			if ($str instanceof Element)
				$str = $str->__toString();
			
			$this->content .= $str;
		}
		
		return $this;
	}

	/**
	 * Returns true if content is not empty.
	 */
	public function hasContent() {
		return !empty($this->content);
	}

	/**
	 * Returns element content string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Wraps the element in another element.
	 */
	public function wrap( $tag, $attributes = array() ) {

		$this->wrap_before = static::open($tag, $attributes);

		$this->wrap_after = static::close($tag);

		return $this;
	}
	
	/**
	 * Sets the tag to use when none is set.
	 */
	public function setDefaultTag( $tag ){
		$this->default_tag = $tag;
		return $this;
	}
	
	/**
	 * Returns the default tag.
	 */
	public function getDefaultTag(){
		return $this->default_tag;
	}
	
	/**
	 * Sets the style to apply to the element.
	 * Not the 'style' attribute.
	 */
	public function setStyle($style) {
		
		if (! isset(self::$styles[$style])){
			trigger_error("Unknown style '$style'.");
			return null;
		}
		
		$this->style = $style;
		
		return $this;
	}

	/**	__toString
	 *
	 *	Generates string from object (and children)
	 *
	 *	prepare() sets up element (usually its content).
	 *	before() returns string, prepended to element.
	 *	after() returns string, appended to element.
	 *	if $isHtml5 = true, self-closing tags close with '>' rather than '/>'.
	 */
	public function __toString() {
		$s = '';

		$this->applyStyles($this->tag);

		$this->prepare();
		
		$s .= $this->before();

		if ( isset($this->wrap_before) ) {
			$s .= $this->wrap_before;
		}

		if ( ! isset($this->tag) )
			$this->tag = $this->getDefaultTag();

		$s .= '<' . $this->tag;

		if ( $this->hasAttributes() )
			$s .= $this->getAttributesString();

		if ( $this->isSelfClosing() ) {
			$s .= $this->isHtml5() ? '>' : ' />';
		} else {
			$s .= '>';

			if ( $this->hasContent() )
				$s .= $this->getContent();

			$s .= '</' . $this->tag . '>';
		}

		$s .= $this->after();

		if ( isset($this->wrap_after) ) {
			$s .= $this->wrap_after;
		}

		return $s;
	}

	/**
	 *	Returns element as string via __toString().
	 */
	public function render() {
		return $this->__toString();
	}
	
	/**
	 * Allows subclasses to prep element or children prior
	 * to rendering.
	 */
	protected function prepare() {
		return $this;
	}

	/**
	 * Adds content (string) before element.
	 * This will also enclose anything given in wrap()
	 */
	protected function before() {
		return '';
	}
	
	/**
	 * Adds content string after element.
	 * Encloses anything given in wrap()
	 */
	protected function after() {
		return '';
	}
	
	/**
	 * Adds styles for the element before rendering.
	 */
	protected function applyStyles($tag) {
		
		if (! isset($this->style))
			return;
		
		if (! isset(self::$styles[$this->style]))
			return;
		
		$styleset = self::$styles[$this->style];
		
		if (! isset($styleset[$tag]))
			return;
		
		$this->addAttributes($styleset[$tag]);
	}
	
}
