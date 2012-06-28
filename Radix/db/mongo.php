<?php

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
        $this->_m = new Mongo();
        $this->_d = $this->_m->selectDB($opt['database']);
    }
    /**
    */
    function create($c)
    {
        $this->_c = $this->_d->createCollection($c);
    }
    function delete($c,$a,$o=null)
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->remove($a,$o);
        return $r;
    }
    /**
    */
    function find($c,$a=null)
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->find($a);
        return $r;
    }
    function find_one($c,$a)
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->findOne($a);
        return $r;
    }
    /**
    */
    function insert($c,$a)
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->insert($a);
        return $r;
    }
    /**
    */
    function update($c,$a)
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->update(array('_id'=>$a['_id']),$a);
        return $r;
    }
}
