<?php
/**
    @file
    @brief A Method to Support HTTP Basic

    @see http://en.wikipedia.org/wiki/Basic_access_authentication

    @copyright 2006 Edoceo, Inc.
    @package radix

*/

class Radix_Auth_HTTP
{
    /**
        Initialise the Auth Settings
        @return void
    */
    private static $_auth;
    private static $_realm;

    public static function init($opts=null)
    {
        if ($opts === null) {
            $opts = array();
        }
        if (empty($opts['realm'])) {
            $opts['realm'] = $_SERVER['SERVER_NAME'];
        }
        // @todo should set the user/pass list that is acceptable?
        // @todo point to a database?
        // @todo point to a callback?
        // if (is_array($opts['userlist'])) {
        //     if (!empty($user['username'])) {
        //         self::$_auth[ strtolower($user['username']) ] = $user['password'];
        //     }
        // }
        
        self::$_realm = $opts['realm'];
    }
    /**
        Auth Check the HTTP Based Auth (basic only)
        @return true/false
    */
    public static function auth()
    {
        if (empty($_SERVER['PHP_AUTH_USER'])) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="' . self::$_realm . '"');
            exit(0);
        }

        $u = strtolower($_SERVER['PHP_AUTH_USER']);
        $p = $_SERVER['PHP_AUTH_PW'];

        if (self::$_auth[$u] == $p) {
            return $u;
        }

        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="' . self::$_realm . '"');
        if (class_exists('Radix_Session')) {
            Radix_Session::flash('fail','Access Denied');
        }

        return false;
    }
    /**
        Add a User
        @param $u username
        @param $p password
    */
    public static function user($u,$p)
    {
        $u = strtolower($u);
        self::$_auth[$u] = $p;
    }
}