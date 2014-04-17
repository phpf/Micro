<?php
/**
 * @package Phpf.Database
 * @subpackage Database
 */

namespace Phpf;

use PDO;
use OutOfBoundsException;

class Database implements Common\iSingleton
{

	/**
	 * Whether to debug queries.
	 * @var boolean
	 */
	public $debug;

	/**
	 * Number of queries run
	 * @var int
	 */
	public $num_queries;

	/**
	 * FluentPDO object
	 * @var FluentPDO
	 */
	protected $fpdo;

	/**
	 * Database Name
	 * @var string
	 */
	protected static $name;

	/**
	 * Database Host
	 * @var string
	 */
	protected static $host;

	/**
	 * Database User
	 * @var string
	 */
	protected static $user;

	/**
	 * Database Password
	 * @var string
	 */
	protected static $pass;

	/**
	 * Database Driver
	 * @var string
	 */
	protected static $driver;

	/**
	 * Table prefix
	 * @var string
	 */
	protected static $prefix;

	/**
	 * Whether currently connected
	 * @var boolean
	 */
	protected static $connected;

	/**
	 * Singleton instance.
	 * @var Database
	 */
	protected static $instance;

	/**
	 * Returns singleton.
	 */
	public static function instance() {
		if (! isset(static::$instance))
			static::$instance = new static();
		return static::$instance;
	}

	/**
	 * Rests $num_queries and $connected
	 */
	private function __construct() {

		$this->num_queries = 0;
		static::$connected = false;

		// FluentPDO autoloader
		spl_autoload_register(function($class) {
			if (0 === strpos($class, 'FluentPDO')) {
				include __DIR__.'/Database/'.str_replace('\\', '/', $class).'.php';
			}
		});
	}

	/**
	 * Sets database connection settings as properties.
	 */
	public static function init($dbName, $dbHost, $dbUser, $dbPass, $tablePrefix = '', $dbDriver = 'mysql') {

		static::$name = $dbName;
		static::$host = $dbHost;
		static::$user = $dbUser;
		static::$pass = $dbPass;
		static::$prefix = $tablePrefix;

		// Skip driver check if reinitializing with same driver.
		if (isset(static::$driver) && $dbDriver == static::$driver)
			return;

		$drivers = PDO::getAvailableDrivers();

		if (! in_array($dbDriver, $drivers, true)) {
			throw new OutOfBoundsException("Invalid database driver $dbDriver.");
		}

		static::setDriver($dbDriver);
	}

	/**
	 * Sets the database driver (string).
	 */
	public static function setDriver($driver) {
		static::$driver = strtolower($driver);
	}

	/**
	 * Returns database driver string.
	 */
	public static function getDriver() {
		return static::$driver;
	}

	/**
	 * Connects to the database using settings from init().
	 */
	public static function connect() {

		$dsn = static::getDriver().':dbname='.static::$name.';host='.static::$host;

		static::instance()->fpdo = new \FluentPDO\FluentPDO(new PDO($dsn, static::$user, static::$pass));

		static::$connected = true;
	}

	/**
	 * Destroys the current database connection.
	 */
	public static function disconnect() {
		$_this = static::instance();
		unset($_this->fpdo);
		static::$connected = false;
	}

	/**
	 * Returns true if connected to the database.
	 */
	public function isConnected() {
		return (bool) static::$connected;
	}

	/**
	 * Sets the database table prefix.
	 *
	 * If already connected, disconnects and reinitializes using current
	 * connection settings, then reconnects.
	 */
	public function setPrefix($prefix) {

		if ($this->isConnected()) {
			static::disconnect();
			static::init(static::$name, static::$host, static::$user, static::$pass, static::$prefix, static::$driver);
			static::connect();
		} else {
			static::$prefix = $prefix;
		}

		return $this;
	}

	/**
	 * Returns the database's table prefix.
	 */
	public function getPrefix() {
		return static::$prefix;
	}

	/**
	 * Whether to debug FluentPDO
	 */
	public function setDebug($value) {

		$this->debug = (bool)$value;

		if ($this->isConnected()) {
			$this->fpdo->debug = $this->debug;
		}

		return $this;
	}

	/**
	 * Tries to convert a table's basename to a full (prefixed) table name.
	 *
	 * Does not perform any validation.
	 */
	public function filterTableName($table) {

		if (isset($this->tables[$table]))
			return $table;

		return $this->getPrefix().$table;
	}

	/**
	 * Registers a database table schema.
	 *
	 * Schemas allows us to create tables (both PHP object representations
	 * and actual SQL tables), which can then be used, e.g. by models.
	 * The schema object resides within its table object after creation.
	 *
	 * @param	Table\Schema	$schema		Table schema object
	 * @return	$this
	 */
	public function registerSchema(Database\Schema $schema) {

		$schema->table = $this->getPrefix().$schema->table_basename;

		$this->tables[$schema->table] = new Database\Table($schema, $this);

		return $this;
	}

	public function getTables() {
		return $this->tables;
	}

	/**
	 * Return a registered Table object.
	 *
	 * @param	string 	$table	Table name
	 * @return	Table			Table object
	 */
	public function table($table) {

		$table = $this->filterTableName($table);

		if (! isset($this->tables[$table])) {
			return null;
		}

		return $this->tables[$table];
	}

	/**
	 * Return a registered table's Schema object.
	 *
	 * @param	string 		$table		Table name
	 * @return	Schema		Table schema object
	 */
	public function schema($table) {

		$table = $this->table($table);

		if (empty($table))
			return null;

		return $table->schema();
	}

	/**
	 * Returns the PDO instance.
	 */
	public function pdo() {

		if (! $this->isConnected()) {
			static::connect();
		}

		return $this->fpdo->pdo;
	}

	/**
	 * Returns the FluentPDO instance.
	 */
	public function fluent() {

		if (! $this->isConnected()) {
			static::connect();
		}

		return $this->fpdo;
	}

	/**
	 * Performs a database query using PDO's query() method directly.
	 */
	public function query($sql) {
		return $this->pdo()->query($sql);
	}

	/**
	 * Performs a select query using FluentPDO
	 */
	public function select($table, $where, $select = '*', $asObjects = true) {

		if (! $this->isConnected()) {
			static::connect();
		}

		if ('*' !== $select) {
			$result = $this->fpdo->from($table)->where($where)->asObject($asObjects)->fetch($select);
		} else {
			$result = $this->fpdo->from($table)->where($where)->asObject($asObjects)->fetchAll();
		}

		$this->num_queries++;

		return $result;
	}

	/**
	 * Performs an insert query using FluentPDO
	 */
	public function insert($table, array $data) {

		if (! $this->isConnected()) {
			static::connect();
		}

		$this->num_queries++;

		return $this->fpdo->insertInto($table)->values($data)->execute();
	}

	/**
	 * Performs an update query using FluentPDO
	 */
	public function update($table, array $data, $key, $keyValue = null) {

		if (! $this->isConnected()) {
			static::connect();
		}

		$this->num_queries++;

		return $this->fpdo->update($table)->set($data)->where($key, $keyValue)->execute();
	}

	/**
	 * Performs a delete query using FluentPDO
	 */
	public function delete($table, $key, $keyValue = null) {

		if (! $this->isConnected()) {
			static::connect();
		}

		$this->num_queries++;

		return $this->fpdo->deleteFrom($table)->where($key, $keyValue)->execute();
	}

	/**
	 * Returns indexed array of installed table names.
	 */
	public function getInstalledTables() {

		$array = $this->pdo()->query('show tables')->fetchAll();

		$tables = array();

		foreach ( $array as $arr ) {
			$tables[] = $arr[0];
		}

		$this->num_queries++;

		return $tables;
	}

	/**
	 * Returns true if a table exists in the db.
	 *
	 * Useful for checking if create/delete queries worked.
	 *
	 * @param	string 	$table	Table name
	 * @return	bool			True if table actually exists
	 */
	public function isTableInstalled($table) {

		$table = $this->filterTableName($table);

		foreach ( $this->getInstalledTables() as $tablename ) {
			if ($tablename == $table) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Attempts to forward calls to FluentPDO
	 */
	function __call($func, $args) {

		if (is_callable(array($this->fpdo, $func))) {
			return call_user_func_array(array($this->fpdo, $func), $args);
		}
	}

}
