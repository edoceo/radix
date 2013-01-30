<?php
/**
    @file
    @brief Radix Mongo Database Interface

    @copyright 2011 Edoceo, Inc.
    @package radix
*/


class radix_db_mongo
{
    private $_c; // Collection
    private $_d; // Database
    private $_m; // Mongo

    private static $_opt; // Hostname, username, password, database

    /**
    */
    static function init($args)
    {
        self::$_opt = $args;
        // self::$_m = new Mongo();
        // self::$_d = self::$_m->selectDB($args['database']);
    }

    /**
    */
    function __construct($opt=null)
    {
        if ($opt === null) $opt = self::$_opt;
        // $this->_h = $opt['hostname'];
        // $this->setAuth($opt['username'],$opt['password']);
        if (!empty($opt['logs'])) {
            // Save for Later
        }
        $this->_m = new Mongo($opt['hostname'],array('connect'=>false));
        $this->_d = $this->_m->selectDB($opt['database']);
    }

    /**
        Create a Collection
    */
    function create($c)
    {
        $this->_c = $this->_d->createCollection($c);
    }

    /**
        Remove a Record
    */
    function delete($c,$a,$o=null)
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->remove($a,$o);
        return $r;
    }

    /**
        Run Command
        @param $c code
        @param $a args
    */
    function command($c,$a=null)
    {
        if ($a == null) $a = array();
        $r = $this->_d->command($c,$a);
        return $r;
    }

    /**
        Execute on Database
        @param $c code
        @param $a args
    */
    function execute($c,$a=null)
    {
        if ($a == null) $a = array();
        $r = $this->_d->execute($c,$a);
        return $r;
    }

    /**
        Find a Set of Records
        @param $c collection name
        @param $q query parameters
        @param $f fields to return, defaults to all
    */
    function find($c,$q=null,$f=array())
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->find($q,$f);
        return $r;
    }

    /**
        Find and Return One
        @param $c collection name
        @param $q query parameters
        @param $f fields to return, defaults to all
    */
    function find_one($c,$q,$f=array())
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->findOne($q,$f);
        return $r;
    }

    /**
        Insert a Record
        @param $c collection name
        @param $a the data array to insert
    */
    function insert($c,$a,$opt=null)
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->insert($a,$opt);
        return $r;
    }

    /**
        Update a Record
        @param $c collection name
        @param $a the data array to upgrade
        @param $q query array for records to match, makes default _id
    */
    function update($c,$a,$q=null,$o=null)
    {
        $c = $this->_d->selectCollection($c);
        if (empty($q)) $q = array('_id'=>$a['_id']);
        $r = $c->update($q,$a,$o=null);
        return $r;
    }
}
