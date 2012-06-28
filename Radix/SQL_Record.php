<?php
/**
    @file
    @brief this is the Active Record type inteface for Radix
    @version $Id: SQL_Record.php 2113 2012-03-26 04:08:02Z code@edoceo.com $

    @copyright 2008 Edoceo, Inc
    @package Radix

*/
class Radix_SQL_Record implements ArrayAccess, Iterator
{
    protected $_data; //! < Data of Row
    protected $_table; //! < Extenders should define this
    protected $_pkcol = 'id';
    protected $_pkval;

    /**
        Radix_SQL_Record Model Constructor
    */
    function __construct($t=null,$k=null,$v=null)
    {
        // Do Nothing
        if ($t==null) {
            return;
        }

        // Copy Properties from Array Keys
        if ( (is_array($t)) && ($k==null) ) {
            $this->_data = $t;
            return;
        }
        
        if ( (is_numeric($t)) && ($k == null) && ($v == null) ) {
            $v = $t; // Move the ID to Value
            $t = $this->_table;
            $k = $this->_pkcol;
        }

        $this->_table = $t;
        $this->_pkcol = $k;
        $this->_pkval = $v;

        // Detect Object Properties from Table if not Specified
        // if (!isset($this->_properties)) {
        //     $this->_properties = array();
        //     $d = $db->describeTable($this->_table);
        //     foreach ($d as $k=>$v) {
        //         $this->_properties[] = $k;
        //         if (!isset($this->$k)) {
        //             $this->$k = null;
        //         }
        //     }
        // }

        // Load Database Record
        if ((is_numeric($this->_pkval)) && (intval($this->_pkval)>0)) {
            //$db = Zend_Registry::get('db');
            // $this->id = intval($x);
            // $sql = sprintf("select * from \"%s\" where id='%d'",$this->_table,intval($x));
            // $x = $db->fetchRow($sql);
            // if (is_object($x)) {
            //     $p = get_object_vars($x);
            //     foreach ($p as $k=>$v) {
            //         $this->$k = stripslashes($x->$k);
            //     }
            // }
            $sql = "SELECT * FROM {$this->_table} WHERE {$this->_pkcol} = ?";
            // Radix::dump($sql);
            $this->_data = Radix_SQL::fetch_row($sql,array($this->_pkval));
        }

        // Copy properties from Given object to me!
        // if (is_object($x)) {
        //     $p = get_object_vars($x);
        //     foreach ($p as $k=>$v) {
        //         $this->$k = $x->$k;
        //     }
        //     return;
        // }
    }
    /**
        Destroy this object and it's index
    */
    function delete()
    {
        $sql = "DELETE FROM {$this->_table} WHERE {$this->_pkcol} = ?";
        Radix_SQL::query($sql,array($this->_pkval));

    }
    /**
        Model Save
    */
    function save()
    {
        // Update or Insert
        $r = null;
        if (!empty($this->_data['id'])) {
            $r = Radix_SQL::update($this->_table,$this->_data,"id={$this->_data['id']}");
        } else {
            unset($this->_data['id']);
            $r = $this->_data['id'] = Radix_SQL::insert($this->_table,$this->_data);
        }
        return $r;
    }
    /**
        Flag Handling
    */
    function delFlag($f) { $this->_data['flag'] = (intval($this->_data['flag']) & ~$f); }
    function hasFlag($f) { return (intval($this->_data['flag']) & $f); }
    function getFlag($fmt='d')
    {
        switch($fmt) {
        case 'b': // Binary
            return sprintf('0b%032s',decbin($this->_data['flag']));
        case 'd': // Decimal
            return sprintf('%u',$this->_data['flag']);
        case 's': // String
            $rc = new ReflectionClass($this);
            $set = $rc->getConstants();
            $ret = array();
            foreach ($set as $k=>$v) {
              if ((preg_match('/^FLAG_/',$k)) && ($this->hasFlag($v))) {
                $ret[] = $k;
              }
            }
            return implode(',',$ret);
        case 'x': // Hex
            return sprintf('0x%08x',$this->_data['flag']);
        }
    }
    function setFlag($f) { $this->_data['flag'] = (intval($this->_data['flag']) | $f); }
    /**
        Array Access Functions
    */
    function offsetSet($k, $v) { $this->_data[$k] = $v; }
    function offsetExists($k) { return isset($this->_data[$k]); }
    function offsetUnset($k) { unset($this->_data[$k]); }
    function offsetGet($k) { return isset($this->_data[$k]) ? $this->_data[$k] : null; }
    /**
        Iterator Access Functions
    */
    function current() { return current($this->_data); }
    function next() { return next($this->_data); }
    function key() { return key($this->_data); }
    function valid() { return (is_array($this->_data) ? (current($this->_data)!==false) : false); }
    function rewind() { return reset($this->_data); }
}
