<?php
/**
    @file
    @brief Radix MRU (Most Recently Used)
    $Id$
*/

/**
    @brief An MRU Interface, implements Iterator (foreach)
*/
class Radix_MRU implements Iterator
{
    protected $_key_list; //!< Items in Order
    protected $_mru_list; //!< Items Data
    protected $_max; //!< Max Size of MRU

    /**
        Instantiate
        @param $max int maximum size of MRU
    */
    function __construct($max=5)
    {
        $this->_max = $max;
        $this->_mru_list = array();
        $this->_key_list = array();
    }
    /**
        Add Item to the front of the MRU
    */
    function add($x)
    {
        $key = crc32(serialize($x));

        if (in_array($key,$this->_key_list)) {
            // Exists? Then Drop
            $this->_key_list = array_diff($this->_key_list,array($key));
        }
        // Add to Fron
        array_unshift($this->_key_list,$key);
        $this->_mru_list[$key] = $x;

        // Trim After Max
        while (count($this->_key_list) > $this->_max) {
            $k = array_pop($this->_key_list);
            unset($this->_mru_list[$k]);
        }
    }
    /**
        Return size of MRU
    */
    function count() { return count($this->_mru_list); }
    /**
        Implement Iterator Interface
    */
    function current()
    {
        $k = current($this->_key_list);
        return $this->_mru_list[$k];
    }
    function key() { return key($this->_key_list); }
    function next()
    {
        $k = next($this->_key_list);
        return isset($this->_mru_list[$k]) ? $this->_mru_list[$k] : null;
    }
    function rewind() { return reset($this->_key_list); }
    function seek() { return seek($this->_key_list); }
    function valid() { $x = current($this->_key_list); return (!empty($x)); }
    /**
        Magic
        @todo does this need to be here, or is it automatic?
    */
    function __sleep() { return array('_key_list','_max','_mru_list'); }
}
