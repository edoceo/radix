<?php
/**
    A Shopping Cart Class

    @version $Id$
    @package Radix

*/

/**
    The Cart, Implements an Iterator
    Stores itself in the $_SESSION['_radix']['_cart']

*/
class Radix_Cart implements ArrayAccess, Iterator
{
    protected $_list;
    protected $_sales_tax = 0;
    protected $_ship_cost = 0;
    protected $_ship_name = 0;

    function __construct()
    {
        $this->_list = array();
    }
    /**
        Add Item to the Cart
        @param $p string or array('name','size','rate','data')
        @return current quantity
    */
    function add($p,$q=1)
    {
        // It's only a Key, Promote
        if (is_string($p)) {
            $p = array(
                'name' => $p,
                'size' => $q,
                'rate' => 1,
                'data' => null,
            );
        }
        $k = sprintf('%08x',crc32($p['name'].$p['data']));

        if (empty($p['size'])) {
            if ($q === 1) { // Key String, Q default means to +1
                $q += intval($this->_list[$k]['size']);
            }
            $p['size'] = $q;
        }
        
        //if ($q === false) {
        //    unset($this->_list[$k]);
        //} else {
        //}
        // Would be Cool to Sort Here?
        $this->_list[$k] = $p;

        // Then Build Totals?
        return ($this->_list[$k]['size']);
    }
    /**
        Poorly Thought Out Shipping Thing
    */
    function getShip()
    {
        return floatval($this->_ship_cost);
    }
    /**
        Returns Formatted Tax Value
    */
    function getTax($fmt='%0.2f%%')
    {
        return sprintf($fmt,$this->_sales_tax * 100);
    }
    /**
        Does we has a Ship Charge?
    */
    function hasShip()
    {
        if (!empty($this->_ship_cost)) {
            return true;
        }
    }
    /**
        Does we has a Tax?
    */
    function hasTax()
    {
        if (!empty($this->_sales_tax)) {
            return true;
        }
    }
    /**
        Sets the Cost of the Shipping
    */
    function setShip($ship)
    {
        if (is_array($ship)) {
            $this->_ship_cost = $ship['cost'];
            $this->_ship_name = $ship['name'];
        } else {
            $this->_ship_cost = $ship;
            $this->_ship_name = 'Shipping Charge';
        }
    }
    /**
        Sets tax to the fractional value, like 0.095
        @param full or fractional value like 9.5 or 0.095
    */
    function setTax($tax)
    {
        if ($tax > 1) {
            $this->_sales_tax = $tax / 100;
        } else {
            $this->_sales_tax = $tax;
        }
    }
    /**
        The the tax sub-Total
    */
    function subTax($sum=null)
    {
        $ret = 0;
        if ($sum === null) {
            $sum = $this->subTotal();
        }
        if (!empty($this->_sales_tax)) {
            $ret = ( $sum * $this->_sales_tax);
        }
        return $ret;
    }
    /**
        Get's the pre-Tax total
    */
    function subTotal()
    {
        $sum = 0;
        foreach ($this->_list as $x) {
          // Quantity
          $q = 1;
          if (!empty($x['size'])) {
              $q = floatval($x['size']);
          }
          // Price
          $p = 0;
          if (!empty($x['rate'])) {
              $p = floatval($x['rate']);
          }
          $sum += ($q * $p);
        }
        return $sum;
    }
    /**
        Return Total of Cart
    */
    function sumTotal()
    {
        $sum = $this->subTotal();
        $sum += $this->subTax($sum);
        $sum += floatval($this->_ship_cost);
        return $sum;
    }
    /**
        Implement Array Interface
    */
    function offsetExists($k) { return isset($this->_list[$k]); }
    function offsetGet($k) { return $this->_list[$k]; }
    function offsetSet($k,$v) { return $this->_list[$k] = $v; }
    function offsetUnset($k) { unset($this->_list[$k]); }
    /**
        Implement Iterator Interface
    */
    function count() { return count(array_keys($this->_list)); }
    function current() { return current($this->_list); }
    function key() { return key($this->_list); }
    function next() { return next($this->_list); }
    function rewind() { return reset($this->_list); }
    function seek() { return seek($this->_list); }
    function valid() { $x = current($this->_list); return (!empty($x)); }
}
