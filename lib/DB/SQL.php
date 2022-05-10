<?php
/**
	@file
	@brief The SQL Handling Code of Radix

	@copyright 2004 Edoceo, Inc.
	@package radix
*/

namespace Edoceo\Radix\DB;

/**
	@brief SQL Database Interface Wrapper, internally PDO
*/
class SQL
{
	private $_pdo;
	private $_pdo_type;

	private $_sql_stat = array();
	private $_sql_tick = 0;

	private static $__me;

	function __construct($dsn=null, $user=null, $pass=null, $opts=null)
	{
		$this->_pdo = new \PDO($dsn, $user, $pass, $opts);

		$this->_pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
		$this->_pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
		$this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$this->_pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_EMPTY_STRING);

		$this->_pdo_type = $this->_pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

	}

	/**
		Initialize a Database Connection, same args as PDO::__construct()
		@param $dsn array ('dsn'=>$,
		@param $user Username
		@param $pass Password
		@param $opts Options
		@return void
	*/
	public static function init($dsn,$user=null,$pass=null,$opts=null)
	{
		self::$__me = new self($dsn, $user, $pass, $opts);
	}

	/**
		Handles/maps the deprected static callers
		@param $f Function
		@param $a Arguments
	*/
	public static function __callStatic($f, $a)
	{
		switch ($f) {
		case 'delete':
			return self::$__me->_delete($a[0], $a[1]);
		case 'fetch':
			return self::$__me->_fetch($a[0], $a[1]);
		case 'fetch_all':
			return self::$__me->fetchAll($a[0], $a[1]);
		case 'fetch_mix':
			return self::$__me->fetchMix($a[0], $a[1]);
		case 'fetch_one':
			return self::$__me->fetchOne($a[0], $a[1]);
		case 'fetch_row':
			return self::$__me->fetchRow($a[0], $a[1]);
		case 'insert':
			return self::$__me->_insert($a[0], $a[1]);
		case 'query':
			return self::$__me->_query($a[0], $a[1]);
		case 'update':
			return self::$__me->_update($a[0], $a[1], $a[2]);
		}

		throw new \Exception(sprintf('Undefined Static Function "%s" on %s', $f, __CLASS__));

	}

	/**
		Handles/maps the conflict for name called on object
		@param $f Function
		@param $a Arguments
	*/
	function __call($f, $a)
	{
		switch ($f) {
		case 'delete':
			return $this->_delete($a[0], $a[1]);
		case 'fetch':
			return $this->_fetch($a[0], $a[1]);
		case 'fetch_all':
			return $this->fetchAll($a[0], $a[1]);
		case 'fetch_mix':
			return $this->fetchMix($a[0], $a[1]);
		case 'fetch_row':
			return $this->fetchRow($a[0], $a[1]);
		case 'insert':
			return $this->_insert($a[0], $a[1]);
		case 'query':
			return $this->_query($a[0], $a[1]);
		case 'update':
			return $this->_update($a[0], $a[1], $a[2]);
		}

		throw new \Exception(sprintf('Undefined Function "%s" on %s', $f, __CLASS__));

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
	public function _fetch($sql,$arg=null)
	{
		$res = $this->_sql_query($sql,$arg);
		return $res;
	}

	/**
		fetch_all

		@param $sql SQL
		@param $arg bindable array
		@return array of rows
	*/
	public function fetchAll($sql,$arg=null)
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
	public function fetchMap($sql,$arg=null)
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
		fetch_one()

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
	public function _query($sql,$arg=null)
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
		if (empty($arg)) {
			$arg = array();
		}
		$res = $this->_pdo->prepare($sql,$arg);
		return $res;
	}

	/**
		Insert Data using PDO Query
	*/
	public function _insert($t,$r)
	{
		$col_name = array();
		$col_data = array();
		$col_hold = array();
		foreach ($r as $k=>$v) {
			$col_name[] = $k;
			$col_data[] = $v;
			$col_hold[] = '?';
		}

		switch ($this->_pdo_type) {
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
			// radix::dump($col_data);
			throw new \Exception('RDS#251: ' . $this->lastError());
		}

		switch ($this->_pdo_type) {
		case 'mssql':
			return $this->_sql_query('SELECT @@IDENTITY',null);
		case 'pgsql':
			return $res->fetchColumn(0);
		case 'sqlite':
			return $this->_pdo->lastInsertId();
		default:
			throw new \Exception("RDS#265: Unhandled Driver: $drv");
		}
	}

	/**
		Update an SQL Record using PDO

		@param $t = Table
		@param $r = Record Data Array
		@param $w = WHERE clause string
	*/
	public function _update($t,$r,$w)
	{
		$arg = array();
		foreach ($r as $k=>$v) {
			$col[]= sprintf('%s = ?',$k);
			$arg[] = $v;
		}

		// Expand WHERE?
		if (is_array($w)) {
			// rebuild Where
			$tmp = array();
			foreach ($w as $k => $v) {
				$tmp[] = sprintf('%s = ?', $k);
				$arg[] = $v;
			}
			$w = implode(' AND ', $tmp);
		}

		$sql = sprintf('UPDATE %s SET %s WHERE (%s)', $t, implode(',', $col), $w);
		$res = $this->_sql_query($sql,$arg);
		return $res->rowCount();
	}

	/**
		Delete from a Table
	*/
	public function _delete($t,$w)
	{
		$arg = array();

		if (is_array($w)) {
			// rebuild Where
			$tmp = array();
			foreach ($w as $k => $v) {
				$tmp[] = sprintf('%s = ?', $k);
				$arg[] = $v;
			}
			$w = implode(' AND ', $tmp);
		}

		$sql = sprintf('DELETE FROM %s WHERE (%s)', $t, $w);

		return $this->_sql_query($sql, $arg);
	}

	public function shut()
	{
		$this->_pdo = null;
	}

	/**
		@param $sql string
		@param $arg array
	*/
	function _sql_debug($sql,$arg)
	{
		$out = $sql;

		// Very Dummy Replacemnet
		foreach ($arg as $k => $v) {
			if (':' == substr($k, 0, 1)) {
				$out = str_replace($k, "'$v'", $out);
			} else {
				$out = preg_replace('/\?/', "'$v'", $out, 1);
			}
		}

		return sprintf('%s;', $out);
	}

	/**
		@param $sql string
		@param $arg array
	*/
	private function _sql_query($sql,$arg)
	{
		$res = null;
		if (empty($this->_pdo)) {
			return $res;
		}
		$t0 = microtime(true);
		// Query
		if (!empty($arg)) { // got parameters
			if (!is_array($arg)) {
				$arg = array($arg); // Promote
			}
			$res = $this->_pdo->prepare($sql);
			if (empty($res)) {
				die($this->lastError());
			}
			$res->execute($arg);
		} else { // straight SQL
			$res = $this->_pdo->query($sql);
		}
		// Counter
		$t1 = (microtime(true) - $t0);
		$this->_sql_tick++;
		if (empty($this->_sql_stat[$sql])) {
			 $this->_sql_stat[$sql] = array('exec' => 1, 'time' => $t1);
		} else {
			 $this->_sql_stat[$sql]['exec'] = $this->_sql_stat[$sql]['exec'] + 1;
			 $this->_sql_stat[$sql]['time'] = $this->_sql_stat[$sql]['time'] + $t1;
		}

		// Some Logging Hook?
		//if (!empty($this->_sql_dump)) {
		//	if (is_resource($this->_sql_dump)) {
		//		if ('stream' == get_resource_type($this->_sql_dump)) {
		//
		//		}
		//	}
		//}

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
		switch ($this->_pdo_type) {
		case 'mssql':
			// return $this->_sql_query('SELECT @@IDENTITY',null);
			break;
		case 'mysql':
			return $this->_pdo->fetchAll('SELECT * FROM mysql.user');
		case 'pgsql':
			return $this->_pdo->fetchAll('SELECT * FROM mysql.user');
		case 'sqlite':
			return $this->_pdo->fetchAll('SELECT * FROM mysql.user');
		default:
			throw new \Exception("RDS#414: Unhandled Driver: $drv");
		}
	}

}
