<?php
/**
    @file
    @brief A Caching Interface for Filesystem or Memcache

    @copyright 2005 Edoceo, Inc.
    @package radix

    @see http://pecl.php.net/memcached (aka: dev-php5/pecl-memcached)
    @see http://php.net/manual/en/book.memcached.php

*/

namespace Edoceo\Radix;

/**
    @brief A Caching Interface for File or Memory caching
*/

class Cache
{
    private static $_kind; //! dir|mem
    private static $_path;

    private static $_mem;  //! Handle for Memcache
    private static $_host = 'locahost';
    private static $_port = 11211;

    private static $_time = 3600; // Default Storage Time
    private static $_stat;

    /**
        @param $opt array of options: kind, path, host, port, time
    */
    public static function init($opt=null)
    {
        // Default Kind of Cache
        if (empty($opt['kind'])) {
            $opt['kind'] = 'dir';
        }

        // Initialise Connetion
        switch ($opt['kind']) {
        case 'dir':
        case 'file':
        case 'path':
            self::$_kind = 'dir';
            if (!is_dir($opt['path'])) {
                mkdir($opt['path']);
            }
            self::$_path = $opt['path'];
            break;
        case 'mem':
        case 'memcache':
        case 'memcached':
        case 'ram':
            if (empty($opt['host'])) $opt['host'] = 'localhost';
            if (empty($pot['port'])) $opt['port'] = 11211;
            self::$_kind = 'mem';
            if (empty(self::$_mem)) {
                self::$_mem = new Memcached();
                // self::$_mem->setOption(Memcached::SERIALIZER_IGBINARY,true);
                self::$_mem->addServer($opt['host'],$opt['port']);
            }
            break;
        }

        // Update Default TTL
        if (!empty($opt['time']) && intval($opt['time']) > 0 ) {
            self::$_time = $opt['time'];
        }
    }
    /**
        @todo want to be able to make an instance of this thing too
    */
    public function __construct()
    {
        
    }
    /**
        Reads the item from cache
        @param $name of the item to fetch
        @param $miss value to return on cache-miss
        @todo do we want to log cache misses?
        @return data|$miss
    */
    public static function get($name,$miss=false)
    {
        $r = $miss;
        switch (self::$_kind) {
        case 'dir':
            $file = self::_make_path($name);
            if (is_file($file)) {
                if (filemtime($file) > time()) {
                    $r = unserialize(file_get_contents($file));
                } else {
                    unlink($file);
                }
            }
            break;
        case 'mem':
            $r = self::$_mem->get(sha1($name));
        }
        return($r);
    }
    /**
        Saves items in cache for at most one day (86400) (half 43200)
        @param $name name of cache item
        @param $data automatically serialized
        @param $time duration to cache, in seconds, default 3600
        @return true|false
    */
    public static function put($name,$data,$time=false)
    {
        $r = false;
        if ($time === false) $time = self::$_time;
        switch (self::$_kind) {
        case 'dir':
            $file = self::_make_path($name);
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file),0766,true);
            }
            $r = file_put_contents($file,serialize($data));
            $r = touch($file,time()+$time);
            break;
        case 'mem':
            // if (strpos($name,'RAND')) {
            //     $time = 30; // Results of RAND are cached only for 30s
            // }
            $r = self::$_mem->set(sha1($name),$data,$time);
            if ($r !== 0) {
                self::$_stat = sprintf('Error: %d %s',self::$_mem->getResultCode(),self::$_mem->getResultMessage());
            }
        }
        return $r;
    }
    /**
        Remove an Item from the Cache
    */
    public static function del($name)
    {
        $r = false; // Return Value
        switch (self::$_kind) {
        case 'dir':
            $file = self::_make_path($name);
            if (is_file($file)) {
                unlink($file);
                $r = true;
            }
            break;
        case 'mem':
            $r = self::$_mem->delete(sha1($name));
            if ($r !== 0) {
                self::$_stat = sprintf('Error: %d %s',self::$_mem->getResultCode(),self::$_mem->getResultMessage());
            }
        }
        return $r;
    }
    /**
        Creates a directory path tree from the key
        @param $x Cache Key
        @return a full path
    */
    private static function _make_path($x)
    {
        return self::$_path . '/' . preg_replace('/([0-9a-f]{4})/','/$1',sha1($x));
    }
}
