<?php
/**
	SQL Query Builder
*/

namespace Edoceo\Radix\DB;

class SQL_Query
{
	private $_col; // Columns for SELECT, INSERT, UPDATE
	private $_arg; // Query Parameters

	private $_tab = array(); // Tables to Query

	private $_cmd = 'SELECT';
	private $_sql_where = array();
	private $_sql_group = array();
	private $_sql_order = array();

	/**
		@param $cmd SELECT, INSERT, UPDATE, DELETE
	*/
	function __construct($cmd, $tab=null, $col=null)
	{
		$this->_cmd = strtoupper(trim($cmd));

		switch ($this->_cmd) {
		case 'SELECT':
			if (empty($col)) {
				$col = '*';
			}
			break;
		case 'INSERT':
		case 'UPDATE':
		case 'DELETE':
			break;
		}

		if (is_string($col)) {
			$col = explode(',', $col);
		}

		$this->_tab[] = $tab;
		$this->_col = $col;

	}

	private function _where($op, $col, $val)
	{
		$key = sprintf(':a%08x', crc32($col.$val));

		$this->_sql_where[] = array(
			'op' => $op,
			'key' => $key,
			'col' => $col,
			'val' => $val
		);

	}

	/**
	*/
	function andWhere($col, $val)
	{
		$this->_where('AND', $col, $val);
	}

	/**
	*/
	function orWhere($col, $val)
	{
		$this->_where('OR', $col, $val);
	}

	/**
		@param $col 'col'
		@return void
	*/
	function groupBy($col)
	{
		$this->_sql_group[] = $col;
	}

	/**
		@param $col 'col' or 'col ASC' or 'col DESC'
		@return void
	*/
	function orderBy($col)
	{
		$this->_sql_order[] = $col;
	}

	/**
		@return Parameterized SQL
	*/
	function getSQL()
	{
		$sql = $this->_cmd;
		$sql.= ' ' . implode(', ', $this->_col);
		$sql.= ' FROM ' . implode(', ', $this->_tab);

		if (count($this->_sql_where)) {
			$sql.= ' WHERE';
			foreach ($this->_sql_where as $idx => $wcv) {
				if ($idx > 0) {
					$sql.= sprintf(' %s', $wcv['op']);
				}
				$sql.= sprintf(' %s %s', $wcv['col'], $wcv['key']);
			}
		}

		if (count($this->_sql_group)) {
			$sql.= ' GROUP BY ' . implode(', ', $this->_sql_group);
		}

		if (count($this->_sql_order)) {
			$sql.= ' ORDER BY ' . implode(', ', $this->_sql_order);
		}

		return $sql;
	}

	/**
		@return K=>V Array of Arguments
	*/
	function getParameters()
	{
		$ret = array();
		foreach ($this->_sql_where as $x) {
			$ret[ $x['key'] ] = $x['val'];
		}
		return $ret;
	}

	/**
		Mashes the SQL and Parameters together without any escaping
		Do not use this to feed directly to your SQL server
	*/
	function __toString()
	{
		$sql = $this->getSQL();
		$arg = $this->getParameters();
		foreach ($arg as $k => $v) {
			$sql = str_replace($k, "'$v'", $sql);
		}
		return $sql;
	}

}
