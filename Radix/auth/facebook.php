<?php
/**
    @file
    @brief Auth/Facebook - Tools for Google OAuth2

    @see https://developers.google.com/accounts/docs/OAuth2
    @see https://developers.facebook.com/docs/howtos/login/server-side-login/
*/

class radix_auth_facebook
{
    const API_URI = 'https://graph.facebook.com/';
    const AUTHENTICATE_URI = 'https://www.facebook.com/dialog/oauth/';
    const ACCESS_TOKEN_URI = 'https://graph.facebook.com/oauth/access_token';
    const USER_AGENT = 'Edoceo radix_auth_facebook v2013.14';

    private $_access_token;
    private $_oauth_client_id;
    private $_oauth_client_secret;

    /**
        Create an Instance
        @param $a Consumer Key
        @param $b Consumer Secret
    */
    public function __construct($a,$b)
    {
        $this->_oauth_client_id = $a;
        $this->_oauth_client_secret = $b;
        $this->_oauth = new OAuth($a,$b,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
        $this->_oauth->enableDebug();
    }

    /**
        @param $arg
        @return URI on 4Sq
    */
    public function getAuthenticateURI($opt)
    {
        $uri = self::AUTHENTICATE_URI;
        $arg = array(
            'client_id' => $this->_oauth_client_id,
            'state' => md5(serialize($_SESSION).microtime()),
            'response_type' => 'code',
        );
        $arg = array_merge($arg,$opt);

        $ret = $uri . '?' . http_build_query($arg);
        return $ret;

    }

    /**
        @param $a the token passed back from the oAuth Provider, typically $_GET['oauth_token']
    */
    public function getAccessToken($opt)
    {
        $uri = self::ACCESS_TOKEN_URI;
        $arg = array(
            'client_id' => $this->_oauth_client_id,
            'client_secret' => $this->_oauth_client_secret,
            'code' => $opt['code'],
            'redirect_uri' => $opt['redirect_uri'],
        );

        if (empty($arg['code'])) $arg['code'] = $_GET['code'];

        $uri = $uri . '?' . http_build_query($arg);

        $ret = false;
        try {
            $ret = $this->_oauth->getAccessToken($uri);
            $this->_access_token = $ret['access_token'];
        } catch (Exception $e) {
            radix::dump($this->_oauth->debugInfo);
        }
        return $ret;
    }

    /**
    */
    function setAccessToken($a)
    {
        return $this->_oauth->setToken($a,null);
    }

    /**
        Easy Wrapper for Fetch
    */
    function api($uri,$post=null,$head=null)
    {
        $verb = 'GET';
        $post = array();

        $post = array(
            'format' => 'json',
        );

        $uri = self::API_URI . ltrim($uri,'/') . '?access_token=' . $this->_access_token;
        $ret = $this->fetch($uri,$post,$verb,$head);
        return $ret;

    }

    /**
    */
    function fetch($uri,$post=null,$verb=null,$head=null)
    {
        if (empty($post)) $post = array();
        if (empty($verb)) $verb = 'GET';
        if (empty($head)) $head = array(
            'User-Agent' => USER_AGENT,
        );
        try {
            // $ret = $this->_oauth->getAccessToken($uri);
            $this->_oauth->fetch($uri,$post,$verb,$head);
            // radix::dump($this->_oauth->debugInfo);
            // $inf = $this->_oauth->getLastResponseInfo();
            $res = $this->_oauth->getLastResponse();
            return json_decode($res,true);
        } catch (Exception $e) {
            radix::dump($this->_oauth->debugInfo);
        }

    }

    // function getLastResponse() { $this->_oauth->getLastResponse(); }
    //
    // function getLastResponseInfo() { $this->_oauth->getLastResponseInfo(); }

}