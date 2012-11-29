<?php
/**
    @file
    @brief Tools for interacting with Facebook
    $Id$

    This file took major influence from the Facebook provided PHP libs

    @see http://www.php.net/manual/en/function.mcrypt-create-iv.php
    @see http://stackoverflow.com/questions/1220751/how-to-choose-an-aes-encryption-mode-cbc-ecb-ctr-ocb-cfb

    @package Radix
*/

class radix_api_facebook
{
    const USER_AGENT = 'Edoceo Radix Facebook v2012.31';

    private static $__app_id;
    private static $__secret;

    private $_access_token = null;

    // private $_auth;
    protected $_app_id;
    protected $_secret;
    protected $_user;
    protected $_pass;
    protected $_format = 'json';

    /**
        Maps aliases to Facebook domains.
    */
    public static $domain_map = array(
        'api'      => 'https://api.facebook.com/',
        'api_read' => 'https://api-read.facebook.com/',
        'graph'    => 'https://graph.facebook.com/',
        'www'      => 'https://www.facebook.com/',
    );

    /**
        Init the Static Object
    */
    public static function init($app=null,$key=null)
    {
        self::$__app_id = $app;
        self::$__secret = $key;
    }

    /**
        Create an Instance
        @param $app_id
        @param $secret
    */
    public function __construct($app=null,$key=null)
    {
        $this->_app_id = $app;
        $this->_secret = $key;
    }

    /**
    */
    public static function fql($fql)
    {
        $fb = new Facebook(array(
            'appId'  => self::$_app_id,
            'secret' => self::$_secret,
            'cookie' => true,
        ));
        // Radix::dump($fb);
        $uri = sprintf('https://graph.facebook.com/oauth/access_token?client_id=%s&client_secret=%s&grant_type=client_credentials',
            rawurlencode(self::$_app_id),rawurlencode(self::$_secret));
        $ret = self::_curl($uri);
        if (preg_match('/access_token=(.+)/',$ret['body'],$m)) {
            $fb->setAccessToken($m[1]);
        }
        $ret = $fb->api(array(
            'method' => 'fql.query',
            'query'  => $fql,
        ));
        return $ret;
    }

    /**
        Make an API call.
        @return mixed The decoded response
    */
    public function graph($uri,$act='GET',$opt) /* polymorphic */
    {
        // $args = func_get_args();
        // if (is_array($args[0])) {
        //     return $this->_restserver($args[0]);
        // } else {
        //     return call_user_func_array(array($this, '_graph'), $args);
        // }
        $uri = 'https://graph.facebook.com/' . ltrim($uri,'/');

    }

    /**
        Graph Call
    */
    public function getGraph($uri)
    {
        if (empty($this->_access_token)) {
            return false;
        }
        $uri = ltrim($uri,'/');
        $arg = array(
            'access_token' => $this->_access_token,
        );
        $arg = http_build_query($arg);

        $ret = $this->_curl('https://graph.facebook.com/' . $uri . '?' . $arg);
        if ($ret['info']['http_code'] == 200) {
            return json_decode($ret['body']);
        }
        return $ret;
    }

    /**
        Internal CURL Request
    */
    private static function _curl($uri)
    {
        $ch = curl_init($uri);
//        $_ch_head = null;
        // Booleans
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_CRLF, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NETRC, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        // if ( (!empty(self::$_opts['verbose'])) && (is_resource(self::$_opts['verbose'])) ) {
        //     curl_setopt($_ch, CURLOPT_VERBOSE, true);
        //     curl_setopt($_ch, CURLOPT_STDERR, self::$_opts['verbose']);
        // }
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);

        // if ( (!empty(self::$_opts['head'])) ) {
        //     curl_setopt(self::$_ch, CURLOPT_HTTPHEADER, self::$_opts['head']);
        // }

        // if (!empty(self::$_opts['cookie'])) {
        //     curl_setopt(self::$_ch, CURLOPT_COOKIEFILE, self::$_opts['cookie']);
        //     curl_setopt(self::$_ch, CURLOPT_COOKIEJAR, self::$_opts['cookie']);
        // }
        // curl_setopt(self::$_ch, CURLOPT_HEADERFUNCTION, array('self','_curl_head'));

        return array(
            'body' => curl_exec($ch),
            'fail' => sprintf('%d:%s',curl_errno($ch),curl_error($ch)),
            'info' => curl_getinfo($ch),
        );
    }

    /**

    */
    private function _signRequest()
    {

    }

}
