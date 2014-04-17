<?php

namespace Phpf\Html;

/**
 * Creates inputs for forms
 *
 * @package Html
 */
class Input extends Element {
	
	/**
	 * Accepted input types
	 * @var string
	 */
	protected static $valid_input_types = array(
		'textarea', 
		'text', 
		'select', 
		'checkbox', 
		'hidden', 
		'email', 
		'password'
	);

	/**
	 * Input types that are actually elements themselves.
	 * @var array
	 */
	protected static $input_elements = array(
		'select', 
		'textarea'
	);
	
	/**
	 * Input type
	 * @var string
	 */
	protected $type;
	
	/**
	 * Input label
	 * @var string
	 */
	protected $label;
	
	/**
	 * Label attributes
	 * @var array
	 */
	protected $label_attributes;
	
	/**
	 * Input placeholder text.
	 * @var string
	 */
	protected $placeholder;
	
	/**
	 * Help text
	 * @var string
	 */
	protected $help_text;
	
	/**
	 * Whether input is in a group.
	 * @var boolean
	 */
	protected $in_group = false;
	
	/**
	 * Whether has multiple choices.
	 * @var boolean
	 */
	protected $is_multiple = false;
	
	/**
	 * Whether input is required.
	 * @var boolean
	 */
	protected $is_required = false;
	
	/**
	 * Selected input option.
	 * @var string
	 */
	protected $selected;
	
	/**
	 * Input options
	 * @var array
	 */
	protected $options = array();
	
	/**
	 * Input errors.
	 * @var array
	 */
	protected $errors = array();
	
	/**
	 * Whether to use $_REQUEST to try to find 
	 * the selected input option.
	 * @var boolean
	 */
	protected $use_request_find_selected = true;
	
	/**
	 * Constructor
	 */
	public function __construct( $type = 'text' ) {
		
		if (! $this->isValidInputType($type)){
			trigger_error("Invalid input type $type.");
			return null;
		} elseif ($this->isInputTypeElement($type)) {
			parent::__construct($type);
		} else {
			parent::__construct('input');
			$this->setAttribute('type', $type);
		}

		$this->type = $type;

		if ( $this->isType('hidden') ){
			$this->addClass('hidden');
		}
	}

	public function __get($var) {
		
		if ( 'name' === $var || 'value' === $var ){
			return $this->getAttribute($var);
		} else {
			return $this->$var;
		}
	}

	public function __set($var, $value) {
		
		if ( 'name' === $var || 'value' === $var ){
			$this->setAttribute($var, $value);
		} else {
			$this->$var = $value;
		}
	}
	
	public function __isset($var) {
		
		if ( 'name' === $var || 'value' === $var ){
			return $this->hasAttribute($var);
		} else {
			return isset($this->$var);
		}
	}
	
	public function __unset($var) {
		
		if ( 'name' === $var || 'value' === $var ){
			$this->removeAttribute($var);
		} else {
			unset($this->$var);
		}
	}
	
	public function get($var) {
		return $this->__get($var);
	}
	
	public function set($var, $val) {
		$this->__set($var, $val);
		return $this;
	}
	
	public function exists($var) {
		return $this->__isset($var);
	}
	
	public function remove($var) {
		$this->__unset($var);
		return $this;
	}

	public function isType( $type ){
		return $type === $this->type;
	}
	
	public function isValidInputType($type) {
		return in_array($type, static::$valid_input_types, true);
	}
	
	public function isInputTypeElement($type) {
		return in_array($type, static::$input_elements, true);
	}
	
	public function setLabel($str){
		$this->label = $str;
		return $this;
	}
	
	public function hasLabel(){
		return !empty($this->label);
	}
	
	public function getLabel(){
		return $this->label;
	}

	public function setPlaceholder($str) {
		$this->placeholder = $str;
		return $this;
	}
	
	public function getPlaceholder(){
		return $this->placeholder;
	}
	
	public function hasPlaceholder(){
		return !empty($this->placeholder);
	}
	
	public function addError($errorStr){
		$this->errors[] = $errorStr;
		return $this;
	}
	
	public function hasErrors(){
		return !empty($this->errors);
	}
	
	public function getErrors(){
		return $this->errors;
	}

	public function setOptions(array $options) {
		$this->set('options', $options);
		return $this;
	}
	
	public function addOptions(array $options) {
		$this->options = array_merge($this->options, $options);
		return $this;
	}
	
	public function setInGroup($bool) {
		$this->in_group = (bool)$bool;
		return $this;
	}
	
	public function isInGroup(){
		return $this->in_group;
	}
	
	public function setIsMultiple($boolVal) {
		$this->is_multiple = (bool) $boolVal;
		return $this;
	}
	
	public function isMultiple(){
		return $this->is_multiple;
	}
	
	public function setIsRequired( $val ){
		$this->is_required = (bool) $val;
		return $this;
	}
	
	public function isRequired(){
		return $this->is_required;
	}
	
	public function useRequestFindSelected($boolval = null){
			
		if ( !isset($boolval) ) {
			return $this->use_request_find_selected;
		}
		
		$this->use_request_find_selected = (bool)$boolval;
		
		return $this;
	}
	
	// Helpers
	
	public function required(){
		return $this->setIsRequired(true);
	}
	
	public function label( $text ){
		return $this->setLabel($text);
	}
	
	public function placeholder( $text ){
		return $this->setPlaceholder($text);
	}
	
	// Protected methods
	
	protected function getSelected() {

		if (! empty($this->selected)) {
			return $this->selected;
		} 
		
		if ($this->useRequestFindSelected() && isset($_REQUEST[$this->get('name')])) {
			return $_REQUEST[$this->get('name')];
		}
	}

	protected function prepare() {

		if ($this->isType('textarea')) {
				
			if (! $this->hasAttribute('cols') ){
				$this->setAttribute('cols', '5');
			}
			
			if (! $this->hasAttribute('rows') ){
				$this->setAttribute('rows', '6');
			}
		
		} elseif ($this->isType('select')) {
			
			$selected = $this->getSelected();
			
			$this->setAttribute('value', $selected);
			
			if ($this->isMultiple()) {
				$this->setAttribute('multiple', 'multiple');
				$this->setAttribute('name', $this->get('name').'[]');
			}
	
			if ($this->exists('placeholder')) {
				$title = $this->get('placeholder');
			} elseif ($this->exists('label')) {
				$title = 'Select ' . $this->get('label');
			} else {
				$title = '-- Select --';
			}
			
			$s = '<optgroup>';
			$s .= '<option value="0">' . $title . '</option>';
			
			foreach ($this->getOptions() as $option => $value) {
				
				$s .= '<option value="' . $value . '"';
				
				if ($value == $selected) {
					$s .= ' selected="selected"';
				}
				
				$s .= '>' . $option . '</option>';
			}
			
			$s .= '</optgroup>';
			
			$this->setContent($s);
		}
		
	}

	protected function before() {
		
		$s = '';

		$this->addClass('form-control');
		
		if ($this->isRequired()) {
			$this->setAttribute('required', 'required');
		}
		
		if ($this->hasPlaceholder()) {
			$this->setAttribute('placeholder', $this->placeholder);
		}

		if ($this->isInGroup()) {
			$s .= '<div class="form-group' . ($this->hasErrors() ? ' has-error' : '') . '">';
		}

		if ($this->hasLabel()) {

			$s .= '<label for="' . $this->get('name') . '"';

			if (isset($this->label_attributes)) {

				$parsed = $this->parseAttributes($this->label_attributes);

				if (is_array($parsed['class'])) {
					$parsed['class'][] = 'control-label';
				} elseif (is_string($parsed['class'])) {
					$parsed['class'] .= ' control-label';
				}

				$s .= static::toString($parsed);
			}

			$s .= '>'. $this->get('label');
			
			if ($this->isRequired()) {
				$s .= '<span class="required-indicator">*</span>';
			}
			
			$s .= '</label>';
		}

		return $s;
	}

	protected function after() {
		
		$s = '';

		if ($this->hasErrors()) {
			$s .= '<span class="help-block">'. $this->get('error') .'</span>';
		} 
		
		if ($this->exists('help_text')) {
			$s .= '<span class="help-block">'. $this->get('help_text') .'</span>';
		}

		if ($this->isInGroup()) {
			$s .= '</div>';
		}

		return $s;
	}

}
