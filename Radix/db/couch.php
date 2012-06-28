<?php
/**
    @file
    @brief Radix CouchDB Interface

    @see http://wiki.apache.org/couchdb/Getting_started_with_PHP
    @see http://www.osterman.com/wordpress/2007/06/13/php-curl-put-string

    @author code@edoceo.com
    @copyright 2011 Edoceo, Inc.
    @package Radix
    @version $Id$

*/

class radix_db_couch
{
    const USER_AGENT = 'Edoceo Radix CouchDB v2012.12';

    private static $_cdb;
    private static $_opt;
    private $_host;
    private $_port;
    private $_auth; // Authentication
    private $_base; // Base of Database to Query
    private $_stat; // Status of Last Query

    /**
        Get an Instance
        @param options array
    */
    function __construct($opt=null)
    {
        if ($opt === null) $opt = self::$_opt;

        $this->_host = $opt['host'];
        $this->setAuth($opt['user'],$opt['pass']);
        if (!empty($opt['logs'])) {
            // Save for Later
        }
        $this->_base = trim($opt['host'],'/') . '/' . trim($opt['base'],'/');
    }
    public static function easy($verb,$uri,$opt=null)
    {
        if (empty(self::$_cdb)) {
            self::$_cdb = new self(self::$_opt);
        }
        switch (strtolower($verb)) {
        case 'get':
            return self::$_cdb->get($uri);
        case 'post':
            return self::$_cdb->post($uri,$opt);
        case 'put':
            return self::$_cdb->put($uri,$opt);
        }
    }
    /**
        Sets static defaults
    */
    public static function init($opt)
    {
        self::$_opt = $opt;
    }
    /**
        Sets auth information
    */
    function setAuth($u,$p)
    {
        $this->_auth = 'Authorization: Basic ' . base64_encode(sprintf('%s:%s',$u,$p));
    }
    /**
        Gets list of All Databases
    */
    function all_dbs()
    {
        $b = $this->_base;
        $this->_base = null;
        $ret = $this->get('/_all_dbs');
        $this->_base = $b;
        return $ret;
    }
    function all_docs()
    {
         $ret = $this->get('/_all_docs');
         return $ret;
    }
    /**
        Delete an Object
        @return an object
    */
    function delete($uri,$rev)
    {
        $uri = trim($this->_base,'/') . '/' . trim($uri,'/');
        $uri.= '?rev=' . $rev;
        // echo "$uri\n";
        $ch = self::_curl_init($uri);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'DELETE');
        return self::_curl_exec($ch);
    }
    /**
        Get an Object
    */
    function get($uri)
    {
        $uri = $this->_base . '/' . trim($uri,'/');
        // $req = "GET /$uri HTTP/1.1\r\nHost: $this->_host\r\n";
        // if ($this->_auth) $req.= "$this->_auth\r\n";
        // $req.= "Content-Type: application/json\r\nConnection: close\r\n";
        // $req.= "\r\n";
        // $ret = $this->_exec($req);
        $ch = $this->_curl_init($uri);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array(
            'Content-Type: application/json',
        ));
        return $this->_curl_exec($ch);
    }
    function get_attachment($doc,$name)
    {
        
    }
    /**
        @return an Array of Rows or False
    */
    function get_all($uri)
    {
        $uri = $this->_base . '/' . trim($uri,'/');
        $ch = $this->_curl_init($uri);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array(
            'Content-Type: application/json',
        ));
        $ret = curl_exec($ch);
        $this->_stat = curl_getinfo($ch);
        $ret = json_decode($ret,true);
        $ret = $ret['rows'];
        return $ret;
    }
    /**
        Return a Single Document
    */
    function get_one($uri)
    {
        $res = $this->get($uri);
        if (!empty($res['_id'])) {
            return $res;
        }
        if (!empty($res['rows'][0]['value'])) {
            $res = $res['rows'][0]['value'];
            return $res;
        }
        return null;
    }
    function post($uri,$obj)
    {
        $uri = $this->_base . '/' . trim($uri,'/');
        $ch = $this->_curl_init($uri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($obj));
        curl_setopt($ch,CURLOPT_HTTPHEADER,array(
            $this->_auth,
            'Content-Type: application/json',
        ));
        $ret = curl_exec($ch);
        $this->_stat = curl_getinfo($ch);
        $ret = json_decode($ret,true);
        return $ret;
    }
    /**
        Put an Object
    */
    function put($uri,$obj=null)
    {
        // CURLOPT_PUT
        if (!is_string($obj)) {
            $obj = json_encode($obj);
        }

        $uri = trim($this->_base,'/') . '/' . trim($uri,'/');

        $ch = $this->_curl_init($uri);
        curl_setopt($ch,CURLOPT_PUT,true);
        if (strlen($obj)) {
            $fh = fopen('php://memory', 'rw');
            fwrite($fh, $obj);
            rewind($fh);
            curl_setopt($ch,CURLOPT_INFILE,$fh);
            curl_setopt($ch,CURLOPT_INFILESIZE,strlen($obj));
        }
        curl_setopt($ch,CURLOPT_HTTPHEADER,array(
            $this->_auth,
            'Content-Type: application/json',
        ));
        return $this->_curl_exec($ch);
    }
    /**
        Uploads a File to the Database
    */
    function put_attachment($doc,$name,$file,$rev=null)
    {
        $uri = trim($this->_base,'/') . '/' . trim($doc,'/') . '/' . trim($name,'/');
        if (empty($rev)) {
            $ch = $this->_curl_init($uri);
            curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'HEAD');
            $buf = $this->_curl_exec($ch);
            print_r($buf);
            print_r($this);
            $rev = $buf;
        }
        if (empty($rev)) {
            $rev = 1;
        }

        if (!empty($rev)) $uri .= '?rev=' . $rev;
        echo "_curl_init($uri);\n";

        $ch = $this->_curl_init($uri);
        curl_setopt($ch,CURLOPT_PUT,true);
        if ( (strlen($file)<256) && (is_file($file)) ) {
            $fh = fopen('php://memory', 'rw');
            fwrite($fh, $file);
            rewind($fh);
            $size = strlen($file);
        } else {
            $fh = fopen($file,'r');
            $size = filesize($file);
        }
        curl_setopt($ch,CURLOPT_INFILE,$fh);
        curl_setopt($ch,CURLOPT_INFILESIZE,$size);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array(
            $this->_auth,
            'Content-Type: application/octet-stream',
        ));
        $ret = $this->_curl_exec($ch);
        print_r($ret);
        print_r($this);
        die("Uri");
    }
    /**
        Execute a CURL
    */
    protected function _curl_init($uri)
    {
        // radix::dump("curl_init($uri);");
        $ch = curl_init($uri);
        // Booleans
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_CRLF, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NETRC, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);

        // $ret = array(
        //     'body' => curl_exec($ch),
        //     'fail' => sprintf('%d:%s',curl_errno($ch),curl_error($ch)),
        //     'info' => curl_getinfo($ch),
        // );

        return $ch;

    }
    /**
    
    */
    function _curl_exec($ch)
    {
        $buf = curl_exec($ch);
        $this->_stat = curl_getinfo($ch);
        $ret = json_decode($buf,true);
        if (empty($ret)) {
            $ret = $buf;
        }
        return $ret;
    }
}
