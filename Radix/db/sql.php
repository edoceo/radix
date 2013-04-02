<?php
/**
    @file
    @brief The SQL Handling Code of Radix

    @copyright 2004 Edoceo, Inc.
    @package radix
*/

/**
    @brief SQL Database Interface Wrapper, internally PDO
*/
class radix_db_sql
{
    private static $_pdo;
    private static $_sql_stat = array();
    private static $_sql_tick = 0;

    /**
        Initialize a Database Connection, same args as PDO::__construct()
        @param $dns array ('dsn'=>$,
        @param $user Username
        @param $pass Password
        @param $opts Options
        @return void
    */
    public static function init($dsn,$user=null,$pass=null,$opts=null)
    {
        self::$_pdo = new PDO($dsn,$user,$pass,$opts);
    }

    /**
        Get most Recent Error

        @return null or text string like: #%d:%s
    */
    public static function lastError()
    {
        if (empty(self::$_pdo)) {
            return null;
        }
        $info = self::$_pdo->errorInfo();
        if (!empty($info[2])) {
            return sprintf('%s (Guru Meditation: #%s.%s)',$info[2],$info[0],$info[1]);
        }
    }

    /**
        @return Status Information Array
    */
    public static function stat()
    {
        $ret = array(
            'query-tick' => self::$_sql_tick,
            'query-stat' => self::$_sql_stat,
        );
        return $ret;
    }

    /**
        Fetch

        @param $sql String SQL Statement
        @param $arg array bindable parameters, no escaping
        @return Object PDO Statment
    */
    public static function fetch($sql,$arg=null)
    {
        $res = self::_sql_query($sql,$arg);
        $res->setFetchMode(PDO::FETCH_ASSOC);
        return $res;
    }

    /**
        fetch_all

        @param $sql SQL
        @param $arg bindable array
        @return array of rows
    */
    public static function fetch_all($sql,$arg=null)
    {
        $ret = null;
        if ($res = self::_sql_query($sql,$arg)) {
            $res->setFetchMode(PDO::FETCH_ASSOC);
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
    public static function fetch_map($sql,$arg=null)
    {
	    $res = self::_sql_query($sql,$arg);
	    $ret = array();
	    while ($rec = $res->fetch(PDO::FETCH_BOTH)) {
	        $ret[ $rec[0] ] = $rec;
	    }
	    return $ret;
    }

    /**
        @param $sql
        @param $arg bindable array
        @return array, 0 column is key, 1 column is value
    */
    public static function fetch_mix($sql,$arg=null)
    {
	    $res = self::_sql_query($sql,$arg);
	    $ret = array();
	    while ($rec = $res->fetch(PDO::FETCH_NUM)) {
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
    public static function fetch_one($sql,$arg=null)
    {
        $res = self::_sql_query($sql,$arg);
        if ($res) {
            $rec = $res->fetch(PDO::FETCH_NUM);
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
    public static function fetch_row($sql,$arg=null)
    {
        $res = self::_sql_query($sql,$arg);
        if ($res) {
            $rec = $res->fetch(PDO::FETCH_ASSOC);
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
    public static function query($sql,$arg=null)
    {
        if ($r = self::_sql_query($sql,$arg)) {
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
    public static function prepare($sql,$arg=null)
    {
        if (empty($arg)) {
            $arg = array();
        }
        $res = self::$_pdo->prepare($sql,$arg);
        return $res;
    }

    /**
        Insert Data using PDO Query
    */
    public static function insert($t,$r)
    {
        $col_name = array();
        $col_data = array();
        $col_hold = array();
        foreach ($r as $k=>$v) {
            $col_name[] = $k;
            $col_data[] = $v;
            $col_hold[] = '?';
        }
        // @todo add 'returning id' only if it's PGSQL?
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',$t,implode(',',$col_name),implode(',',$col_hold));
        $res = self::_sql_query($sql,$col_data);
        // For MS-SQL
        // $res = self::_sql_query('SELECT @@IDENTITY',null);
        return $res->fetchColumn(0);
    }

    /**
        Update an SQL Record using PDO

        @param $t = Table
        @param $r = Record Data Array
        @param $w = WHERE clause string
    */
    public static function update($t,$r,$w)
    {
        $arg = array();
        foreach ($r as $k=>$v) {
            $col[]= sprintf('%s = ?',$k);
            $arg[] = $v;
        }
        $sql = sprintf('UPDATE %s SET %s WHERE (%s)',$t,implode(',',$col),$w);
        $res = self::_sql_query($sql,$arg);
        return $res->rowCount();
    }

    /**
        Delete from a Table
    */
    public static function delete($t,$w)
    {
        $sql = 'DELETE FROM ' . self::$_pdo->quote($t);
        $sql.= ' WHERE (' . $w . ')';
        return self::_sql_query($sql);
    }

    public static function shut()
    {
        self::$_pdo = null;
    }

    /**
        @param $sql string
        @param $arg array
    */
    private static function _sql_query($sql,$arg)
    {
        $res = null;
        if (empty(self::$_pdo)) {
            return $res;
        }
        $t = microtime(true);
        // Query
        if (!empty($arg)) { // got parameters
            if (!is_array($arg)) {
                $arg = array($arg); // Promote
            }
            $res = self::$_pdo->prepare($sql);
            if (empty($res)) {
                die(self::lastError());
            }
            $res->execute($arg);
        } else { // straight SQL
            $res = self::$_pdo->query($sql);
        }
        // Counter
        self::$_sql_tick++;
        if (empty(self::$_sql_stat[$sql])) {
             self::$_sql_stat[$sql] = array('exec' => 1, 'time' => (microtime(true) - $t));
        } else {
             self::$_sql_stat[$sql]['exec'] = self::$_sql_stat[$sql]['exec'] + 1;
             self::$_sql_stat[$sql]['time'] = self::$_sql_stat[$sql]['time'] + (microtime(true) - $t);
        }
        return $res;
    }

    /**
        Describe the Tables or one Table

        @param $t specific Table, null or empty for list of views
    */
    public static function describeTable($t=null)
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

        @param $v, specific view, null or empty for list of views
    */
    public static function describeView($v=null)
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
    public static function describeColumn($t,$c)
    {
        $sql = 'SELECT * ';
        $sql.= 'FROM pg_attribute ';
        $sql.= 'WHERE attrelid = ( ';
          $sql.= 'SELECT oid FROM pg_class ';
          $sql.= " WHERE relname = '$t' ";
        $sql.= ") AND attname = '$c'";
    }
}
