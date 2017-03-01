<?php
/**
    @file
    @brief Radix Mongo Database Interface

    @copyright 2011 Edoceo, Inc.
    @package radix
*/

namespace Edoceo\Radix\DB;

class Mongo
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
        $this->_m = new \MongoClient($opt['hostname'], array('connect'=>false));
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
    function delete($c,$a,$o=array())
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
        @param $c collection
        @param $q Query
        @param $u Update Data
        @param $f fields to return
        @param $o options
    */
    function find_and_modify($c,$q,$u,$f=null,$o=null)
    {
        $c = $this->_d->selectCollection($c);
        if ($o == null) $o = array();
        $r = $c->findAndModify($q,$u,$f,$o);
        return $r;
    }

    /**
        Insert a Record
        @param $c collection name
        @param $a the data array to insert
        @param $opt options
    */
    function insert($c,$a,$opt=array())
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->insert($a,$opt);
        return $r;
    }

	function save($c, $a)
	{
		$c = $this->_d->selectCollection($c);
		$r = $c->save($a);
		return $r;
	}


    /**
        Update a Record
        @param $c collection name
        @param $a the data array to upgrade
        @param $q query array for records to match, makes default _id
        @param $o options
    */
    function update($c,$a,$q=null,$o=array())
    {
        $c = $this->_d->selectCollection($c);
        if (empty($q)) $q = array('_id'=>$a['_id']);
        $r = $c->update($q,$a,$o);
        return $r;
    }
    
    /**
        Return Distinct List
        @param $c Collection
        @param $k Key
        @param $q Optional Query Parameters
        @return Array
    */
    function distinct($c,$k,$q=null)
    {
        $c = $this->_d->selectCollection($c);
        $r = $c->distinct($k,$q);
        return $r;
    }
    
    /**
        @param $file the local file to insert
        @param $meta meta-data array
    */
    function grid_insert($file,$meta)
    {
        $g = $this->_d->getGridFS();
        // radix::dump($g);
        $f = $g->storeFile($file,$meta);
        // radix::dump($f);
        return $f;
    }
    
    /**
        From Upload Form
    */
    function grid_upload($name,$meta)
    {
        $g = $this->_d->getGridFS();
        $f = $g->storeUpload($name,$meta);
        return $f;
    }

    /**
        Find and Return a File
        @param $f File ID
        @return null|File Result
    */
    function grid_find($f)
    {
        $g = $this->_d->getGridFS();
        $r = $g->find($f);
        return $r;
    }
    
    /**
        Basically teh Same a Fetch?
        @param $q Query
    */
    function grid_find_one($q)
    {
        $g = $this->_d->getGridFS();
        $r = $g->findOne($q);
        return $r;
    }
    
    /**
        Delete a Grid FS
        @param $f File ID
    */
    function grid_delete($f)
    {
        $g = $this->_d->getGridFS();
        $r = $g->delete($f);
        return $r;
    }
}
