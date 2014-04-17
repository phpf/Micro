<?php

namespace Phpf\Html;

class Table extends Element {
	
	protected $rows = array();
	
	protected $head_row;
	
	protected $foot_row;
	
	public function __construct($attributes = array()) {
		parent::__construct('table');
		$this->setAttributes($attributes);
	}
	
	public function row() {
		return $this->rows[] = new TableRow;
	}
	
	public function head() {
		return $this->head_row = new TableRow('th');
	}
	
	public function foot() {
		return $this->foot_row = new TableRow('th');
	}
	
	public function addRow(array $cells, $attributes = array()){
			
		$row = new TableRow;
		
		$row->addCells($cells);
		$row->setAttributes($attributes);
		
		$this->rows[] = $row;
		
		return $this;
	}
	
	public function addHead(array $cells, $attributes = array()) {
			
		$row = new TableRow('th');
		
		$row->addCells($cells);
		
		$row->setAttributes($attributes);
		
		$this->head_row = $row;
		
		return $this;
	}
	
	public function addFoot(array $cells = array(), $attributes = array()) {
			
		$row = new TableRow('th');
		
		if (empty($cells) ) {
				
			if (! isset($this->head_row)) {
				trigger_error("Cannot add tfoot - no cells passed and no thead set.");
				return null;
			}
			
			$cells = $this->head_row->getCells();
			
			if (empty($cells)) {
				trigger_error("Cannot add tfoot - no cells passed and thead has none.");
				return null;
			}
		}
		
		$row->addCells($cells);
			
		$row->setAttributes($attributes);
		
		$this->foot_row = $row;
			
		return $this;
	}
	
	protected function prepare() {
		
		$s = '';
		
		if (isset($this->head_row)) {
			$s .= '<thead>'. $this->head_row->render() .'</thead>';
		}
		
		$s .= '<tbody>';
		
		foreach($this->rows as $row) {
			$s .= $row->render();
		}
		
		$s .= '</tbody>';
		
		if (isset($this->foot_row)) {
			$s .= '<tfoot>' . $this->foot_row->render() . '</tfoot>';
		}
		
		$this->setContent($s);
	}
	
}

class TableRow extends Element {
	
	protected $cell_tag;
	
	protected $cells = array();
	
	protected $default_cell_attributes;
	
	public function __construct($cell_tag = 'td'){
		parent::__construct('tr');
		if ('td' === $cell_tag || 'th' === $cell_tag) {
			$this->cell_tag = $cell_tag;
		}
	}
	
	public function setDefaultCellAttributes($attrs) {
		$this->default_cell_attributes = $attrs;
		return $this;
	}
	
	public function getCells() {
		return $this->cells;
	}
	
	public function addCells( array $cells ) {
		array_walk($cells, function($val) {
			if (is_string($val)) {
				$this->addCell($val);
			} elseif (isset($val['attributes'])) {
				$this->addCell($val['content'], $val['attributes']);	
			} else {
				$this->addCell($val['content']);
			}
		});
	}
	
	public function addCell($content, $attributes = array() ){
		
		$cell = new TableCell($this->cell_tag);
		$cell->setContent($content);
		
		if (isset($this->default_cell_attributes)) {
			$attributes += $this->default_cell_attributes;
		}
		
		$cell->setAttributes($attributes);
		
		$this->cells[] = $cell;
		
		return $this;
	}
	
	public function cell() {
		
		$cell = new TableCell($this->cell_tag);
		
		if (isset($this->default_cell_attributes)) {
			$cell->setAttributes($this->default_cell_attributes);
		}
		
		return $this->cells[] = $cell;
	}
	
	protected function prepare() {
		
		$s = '';
		
		foreach($this->cells as $cell) {
			$s .= $cell->render();
		}
		
		$this->setContent($s);
	}
	
}

class TableCell extends Element {
	
	public function __construct( $tag = 'td' ) {
		if ('th' === $tag) {
			parent::__construct($tag);
		} else {
			parent::__construct('td');
		}
	}
	
}
