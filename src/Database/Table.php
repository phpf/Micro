<?php

namespace Phpf\Database;

use Phpf\Database;

class Table {
	
	protected $db;
	
	protected $schema;
	
	/**
	 * Sets Schema and Database objects as properties.
	 */
	final public function __construct(Schema $schema, Database &$db){
		$this->schema = $schema;
		$this->db =& $db;
	}
	
	/**
	 * Returns Schema instance.
	 * @return Table\Schema
	 */
	final public function schema(){
		return $this->schema;	
	}
	
	/**
	* Select a row or (columns from a row) from the table.
	*
	* @see Database::select()
	*/
	public function select( $where, $select = '*' ){
		return $this->db->select($this->schema->table, $where, $select);
	}
	
	/**
	* Insert a row into the table.
	*
	* @see Database::insert()
	*/
	public function insert( $data ){
		return $this->db->insert($this->schema->table, $data);
	}

	/**
	* Update a row in the table
	*
	* @see Database::update()
	*/	
	public function update( $data, $where ){
		return $this->db->update($this->schema->table, $data, $where);
	}
	
	/**
	* Delete a row in the table
	*
	* @see Database::delete()
	*/
	public function delete( $where ) {
		return $this->db->delete($this->schema->table, $where);
	}
	
	/**
	* Update a single row column value in the table
	*
	* @see Database::update()
	*/
	public function updateField( $name, $value, array $where ){
		return $this->update(array($name => $value), $where);
	}
	
	/**
	 * Performs a query directly on PDO
	 * 
	 * @see Database::query()
	 */
	public function query( $query ){
		return $this->db->query($query);	
	}
	
}
