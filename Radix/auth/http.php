<?php
/**
    @file
    @brief A Method to Support HTTP Basic

    @see http://en.wikipedia.org/wiki/Basic_access_authentication

    @copyright 2006 Edoceo, Inc.
    @package radix

*/

class radix_auth_http
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
            radix_session::flash('fail','Access Denied');
        }

        return false;
    }

    /**
        Auth Check the HTTP Based Auth (basic only)
        @return true/false
    */
    public static function basic($r)
    {
        if (empty($_SERVER['PHP_AUTH_USER'])) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="' . $r . '"');
            exit(0);
        }

        $u = strtolower($_SERVER['PHP_AUTH_USER']);
        $p = $_SERVER['PHP_AUTH_PW'];
        return array($u,$p);

        if (self::$_auth[$u] == $p) {
            return $u;
        }

        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="' . $r . '"');
        if (class_exists('Radix_Session')) {
            Radix_Session::flash('fail','Access Denied');
        }

        return false;
    }

    /**
        Digest Authentication Handler
    */
    static function digest()
    {
        $auth = $_SERVER['PHP_AUTH_DIGEST'];
        if (empty($auth)) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Digest realm="isvaliduser",qop="auth",nonce="'.uniqid().'",opaque="'.md5('isvaliduser').'"');
            exit;
        }
    }

    /**
        Parses Digest Data
        @see http://php.net/manual/en/features.http-auth.php
    */
    static function digest_parse()
    {
        $txt = $_SERVER['PHP_AUTH_DIGEST'];
        // protect against missing data
        $needed_parts = array(
            'nonce'=>1,
            'nc'=>1,
            'cnonce'=>1,
            'qop'=>1,
            'username'=>1,
            'uri'=>1,
            'response'=>1
        );
        $data = array();

        $keys = implode('|', array_keys($needed_parts));

        preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
        // radix::dump($matches);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[3] ? $m[3] : $m[4];
            unset($needed_parts[$m[1]]);
        }
    
        return $needed_parts ? false : $data;

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
