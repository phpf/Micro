<?php
/**
 * FluentPDO is simple and smart SQL query builder for PDO
 *
 * For more information @see readme.md
 *
 * @link http://github.com/lichtner/fluentpdo
 * @author Marek Lichtner, marek@licht.sk
 * @copyright 2012 Marek Lichtner
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
 */

namespace FluentPDO;

use PDO;

class FluentPDO {

	public $pdo;
	
	protected $structure;

	/** @var boolean|callback */
	public $debug;

	function __construct( PDO $pdo, Fluent\Structure $structure = null) {
		$this->pdo = $pdo;
		if (!$structure) {
			$structure = new Fluent\Structure;
		}
		$this->structure = $structure;
	}

	/** Create SELECT query from $table
	 * @param string $table  db table name
	 * @param integer $primaryKey  return one row by primary key
	 * @return FluentPDO\Query\Select
	 */
	public function from($table, $primaryKey = null) {
			
		$query = new Query\Select($this, $table);
		if ($primaryKey) {
			$tableTable = $query->getFromTable();
			$tableAlias = $query->getFromAlias();
			$primaryKeyName = $this->structure->getPrimaryKey($tableTable);
			$query = $query->where("$tableAlias.$primaryKeyName", $primaryKey);
		}
		return $query;
	}

	/** Create INSERT INTO query
	 *
	 * @param string $table
	 * @param array $values  you can add one or multi rows array @see docs
	 * @return FluentPDO\Query\Insert
	 */
	public function insertInto($table, $values = array()) {
		
		$query = new Query\Insert($this, $table, $values);
		return $query;
	}

	/** Create UPDATE query
	 *
	 * @param string $table
	 * @param array|string $set
	 * @param string $primaryKey
	 *
	 * @return FluentPDO\Query\Update
	 */
	public function update($table, $set = array(), $primaryKey = null) {

		$query = new Query\Update($this, $table);
		$query->set($set);
		if ($primaryKey) {
			$primaryKeyName = $this->getStructure()->getPrimaryKey($table);
			$query = $query->where($primaryKeyName, $primaryKey);
		}
		return $query;
	}

	/** Create DELETE query
	 *
	 * @param string $table
	 * @param string $primaryKey  delete only row by primary key
	 * @return FluentPDO\Query\Delete
	 */
	public function delete($table, $primaryKey = null) {

		$query = new Query\Delete($this, $table);
		if ($primaryKey) {
			$primaryKeyName = $this->getStructure()->getPrimaryKey($table);
			$query = $query->where($primaryKeyName, $primaryKey);
		}
		return $query;
	}

	/** Create DELETE FROM query
	 *
	 * @param string $table
	 * @param string $primaryKey
	 * @return FluentPDO\Query\Delete
	 */
	public function deleteFrom($table, $primaryKey = null) {
		$args = func_get_args();
		return call_user_func_array(array($this, 'delete'), $args);
	}

	/** @return \PDO
	 */
	public function getPdo() {
		return $this->pdo;
	}

	/** @return \FluentStructure
	 */
	public function getStructure() {
		return $this->structure;
	}
}
