<?php
/**
	The SQL Handling Code of Radix
	SQL Database Interface Wrapper, internally PDO

	@copyright 2004 Edoceo, Inc.
	@package radix
*/

namespace Edoceo\Radix\DB;

/**

*/
class SQL2
{
	private $_dsn;
	private $_user;
	private $_pass;
	private $_opts;

	private $_pdo;
	private $_kind;
	private $_sql_stat = array();
	private $_sql_tick = 0;

	/**
		Initialize a Database Connection, same args as PDO::__construct()

		@param $dsn array ('dsn'=>$,
		@param $user Username
		@param $pass Password
		@param $opts Options
	*/
	function __construct($dsn=null, $user=null, $pass=null, $opts=null)
	{
		$this->_dsn = $dsn;
		$this->_user = $user;
		$this->_pass = $pass;
		$this->_opts = $opts;
	}

	/**
		Actually Connect to the DB
	*/
	protected function connect()
	{
		if (empty($this->_pdo)) {
			$this->_pdo = new \PDO($this->_dsn, $this->_user, $this->_pass, $this->_opts);
			$this->_pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
			$this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->_pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			$this->_kind = $this->_pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
		}
	}

	/**
		Get most Recent Error

		@return null or text string like: #%d:%s
	*/
	public function lastError()
	{
		if (empty($this->_pdo)) {
			return null;
		}

		$info = $this->_pdo->errorInfo();
		if (!empty($info[2])) {
			return sprintf('%s (Guru Meditation: #%s.%s)',$info[2],$info[0],$info[1]);
		}

	}

	/**
		@return Status Information Array
	*/
	public function stat()
	{
		$ret = array(
			'query-tick' => $this->_sql_tick,
			'query-stat' => $this->_sql_stat,
		);
		return $ret;
	}

	/**
		Fetch

		@param $sql String SQL Statement
		@param $arg array bindable parameters, no escaping
		@return Object PDO Statment
	*/
	public function fetch($sql,$arg=null)
	{
		$res = $this->_sql_query($sql,$arg);
		return $res;
	}

	/**
		fetchAll

		@param $sql SQL
		@param $arg bindable array
		@return array of rows
	*/
	public function fetchAll($sql, $arg=null)
	{
		$ret = null;
		if ($res = $this->_sql_query($sql,$arg)) {
			$ret = $res->fetchAll();
			$res->closeCursor();
		}
		return $ret;
	}

	/**
		@param $sql
		@param $arg bindable array
		@return array keys are first column, value is record object
	*/
	public function fetchMap($sql, $arg=null)
	{
		$res = $this->_sql_query($sql,$arg);
		$ret = array();
		while ($rec = $res->fetch(\PDO::FETCH_BOTH)) {
			$ret[ $rec[0] ] = $rec;
		}
		return $ret;
	}

	/**
		@param $sql
		@param $arg bindable array
		@return array, 0 column is key, 1 column is value
	*/
	public function fetchMix($sql,$arg=null)
	{
		$res = $this->_sql_query($sql,$arg);
		$ret = array();
		while ($rec = $res->fetch(\PDO::FETCH_NUM)) {
			$ret[ $rec[0] ] = $rec[1];
		}
		return $ret;
	}

	/**
		Fetch the first column from the first row

		@param $sql SQL
		@param $arg bindable array
		@return single scalar variable
	*/
	public function fetchOne($sql,$arg=null)
	{
		$res = $this->_sql_query($sql,$arg);
		if ($res) {
			$rec = $res->fetch(\PDO::FETCH_NUM);
			$res->closeCursor();
			if ($rec !== false) {
				return $rec[0];
			}
		}
		return null;
	}

	/**
		fetch_row

		@param $sql SQL
		@param $arg bindable array
		@return one row as associative arary
	*/
	public function fetchRow($sql,$arg=null)
	{
		$res = $this->_sql_query($sql,$arg);
		if ($res) {
			$rec = $res->fetch();
			$res->closeCursor();
			if ($rec !== false) {
				return $rec;
			}
		}
		return null;
	}

	/**
		Query

		@param $sql string of SQL
		@param $arg array of bindable parameters
		@return number of affected rows
	*/
	public function query($sql, $arg=null)
	{
		if ($r = $this->_sql_query($sql,$arg)) {
			return $r->rowCount();
		}
		return false;
	}

	/**
		Prepare
		@param $sql string of SQL
		@param $arg array of prepare parameters
		@return PDO Statment Handle
	*/
	public function prepare($sql,$arg=null)
	{
		$this->connect();

		if (empty($arg)) {
			$arg = array();
		}

		$res = $this->_pdo->prepare($sql,$arg);

		return $res;
	}

	/**
		Insert Data using PDO Query
	*/
	public function insert($t, $r)
	{
		$col_name = array();
		$col_data = array();
		$col_hold = array();
		foreach ($r as $k=>$v) {
			$col_name[] = $k;
			$col_data[] = $v;
			$col_hold[] = '?';
		}

		switch ($this->_kind) {
		case 'mysql':
			$sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',$t,implode(',',$col_name),implode(',',$col_hold));
			break;
		case 'pgsql':
			$sql = sprintf('INSERT INTO %s (%s) VALUES (%s) RETURNING id',$t,implode(',',$col_name),implode(',',$col_hold));
			break;
		case 'sqlite':
			$sql = sprintf('INSERT INTO %s (%s) VALUES (%s); SELECT last_insert_rowid()',$t,implode(',',$col_name),implode(',',$col_hold));
			break;
		}

		$res = $this->_sql_query($sql,$col_data);
		if (0 == $res->rowCount()) {
			throw new \Exception('RDS#249: ' . $this->lastError());
		}

		switch ($this->_kind) {
		case 'mssql':
			return $this->_sql_query('SELECT @@IDENTITY',null);
		case 'pgsql':
			return $res->fetchColumn(0);
		case 'sqlite':
			return $this->_pdo->lastInsertId();
		default:
			throw new \Exception("RDS#260: Unhandled Driver: {$this->_kind}");
		}
	}

	/**
		Update an SQL Record using PDO

		@param $t = Table
		@param $r = Record Data Array
		@param $w = WHERE clause string
	*/
	public function update($t,$r,$w)
	{
		$arg = array();
		foreach ($r as $k=>$v) {
			$col[]= sprintf('%s = ?',$k);
			$arg[] = $v;
		}
		$sql = sprintf('UPDATE %s SET %s WHERE (%s)', $t, implode(',', $col), $w);
		$res = $this->_sql_query($sql,$arg);
		return $res->rowCount();
	}

	/**
		Delete from a Table
		@param $t Table Name
		@param $w Where Clause
	*/
	public function delete($t, $w)
	{
		$sql = 'DELETE FROM ' . $this->_pdo->quote($t);
		$sql.= ' WHERE (' . $w . ')';
		return $this->_sql_query($sql);
	}

	public function shut()
	{
		$this->_pdo = null;
	}

	/**
		@param $sql string
		@param $arg array
	*/
	private function _sql_query($sql,$arg)
	{
		$this->connect();

		$res = null;
		if (empty($this->_pdo)) {
			return $res;
		}
		$t = microtime(true);

		// Query
		if (!empty($arg)) { // got parameters
			if (!is_array($arg)) {
				$arg = array($arg); // Promote
			}
			$res = $this->_pdo->prepare($sql);
			//if (empty($res)) {
			//	die($this->lastError());
			//}
			$res->execute($arg);
		} else { // straight SQL
			$res = $this->_pdo->query($sql);
		}

		// Counter
		$this->_sql_tick++;
		if (empty($this->_sql_stat[$sql])) {
			 $this->_sql_stat[$sql] = array(
			 	 'exec' => 1,
			 	 'time' => (microtime(true) - $t)
			 );
		} else {
			 $this->_sql_stat[$sql]['exec'] = $this->_sql_stat[$sql]['exec'] + 1;
			 $this->_sql_stat[$sql]['time'] = $this->_sql_stat[$sql]['time'] + (microtime(true) - $t);
		}
		return $res;
	}

	/**
		Describe the Tables or one Table

		@param $t specific Table, null or empty for list of views
	*/
	public function describeTable($t=null)
	{
		// For PostgreSQL 8 & 9
		$sql = 'SELECT n.nspname as "Schema", ';
		$sql.= ' c.relname as "Name", ';
		$sql.= " CASE c.relkind WHEN 'r' THEN 'table' WHEN 'v' THEN 'view' WHEN 'i' THEN 'index' WHEN 'S' THEN 'sequence' WHEN 's' THEN 'special' END as \"Type\", ";
		$sql.= ' pg_catalog.pg_get_userbyid(c.relowner) as "Owner" ';
		$sql.= ' FROM pg_catalog.pg_class c ';
		$sql.= ' LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace ';
		$sql.= " WHERE c.relkind IN ('v','s','') ";
		$sql.= " AND n.nspname !~ '^pg_toast' ";
		$sql.= " AND n.nspname ~ '^(ca)$' ";
		$sql.= ' ORDER BY 1,2; ';
	}

	/**
		List of Views

		@param $v specific view, null or empty for list of views
	*/
	public function describeView($v=null)
	{
		// For PostgreSQL 8 & 9
		$sql = 'SELECT n.nspname as "Schema", ';
		$sql.= ' c.relname as "Name", ';
		$sql.= " CASE c.relkind WHEN 'r' THEN 'table' WHEN 'v' THEN 'view' WHEN 'i' THEN 'index' WHEN 'S' THEN 'sequence' WHEN 's' THEN 'special' END as \"Type\", ";
		$sql.= ' pg_catalog.pg_get_userbyid(c.relowner) as "Owner" ';
		$sql.= ' FROM pg_catalog.pg_class c ';
		$sql.= ' LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace ';
		$sql.= " WHERE c.relkind IN ('v','s','') ";
		$sql.= " AND n.nspname !~ '^pg_toast' ";
		$sql.= " AND n.nspname ~ '^(ca)$' ";
		$sql.= ' ORDER BY 1,2; ';
	}

	/**
	*/
	public function describeColumn($t,$c)
	{
		$sql = 'SELECT * ';
		$sql.= 'FROM pg_attribute ';
		$sql.= 'WHERE attrelid = ( ';
		  $sql.= 'SELECT oid FROM pg_class ';
		  $sql.= " WHERE relname = '$t' ";
		$sql.= ") AND attname = '$c'";
	}

	function showTables()
	{

	}

	/**

	*/
	function showUsers()
	{
		switch ($this->_kind) {
		case 'mssql':
			// return $this->_sql_query('SELECT @@IDENTITY',null);
			break;
		case 'mysql':
			return $this->_pdo->fetchAll('SELECT * FROM mysql.user');
		case 'pgsql':
			return $this->_pdo->fetchAll('');
		case 'sqlite':
			return array();
		default:
			throw new Exception("RDS#414: Unhandled Driver: $drv");
		}
	}

}
