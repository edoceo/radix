<?php
/**
    @file
    @brief Radix Mongo Database Interface

    @copyright 2011 Edoceo, Inc.
    @package radix
*/

namespace radix;

class radix_db_redis extends \Redis
{
    private static $_opt = array(
        'hostname' => 'localhost',
        'password' => '',
        'database' => 0,
        'port' => '',
        'timeout' => 5,
    );

    private $_c;

    /**
    */
    static function init($args)
    {
        self::$_opt = $args;
    }

    /**
    */
    function __construct($opt=null)
    {
        if ($opt === null) $opt = self::$_opt;
        // $this->_c = new \Redis();
        $this->connect($opt['hostname']);
        // $this->_d = $this->_m->selectDB($opt['database']);
        if (!empty($opt['password'])) {
            $this->auth($opt['password']);
        }
        $this->select(0);
    }

    /**
        Subscribe which takes Channel and promotes to Array if String
        @param $ch Channel or Array of Channels
        @param $cb Callback String or array($this,'cbMethod');
    */
    function subscribe($ch,$cb)
    {
        if (is_string($ch)) $ch = array($ch);
        return parent::subscribe($ch,$cb);
    }
}
