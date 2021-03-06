<?php
/**
    @file
    @brief oAuth Library for BitBucket

    @see https://confluence.atlassian.com/display/BITBUCKET/OAuth+on+bitbucket
*/

class radix_auth_bitbucket
{
    const API_URI = 'https://api.bitbucket.org/1.0';
    const AUTHENTICATE_URI  = 'https://bitbucket.org/!api/1.0/oauth/authenticate';
    const REQUEST_TOKEN_URI = 'https://bitbucket.org/!api/1.0/oauth/request_token';
    const ACCESS_TOKEN_URI  = 'https://bitbucket.org/!api/1.0/oauth/access_token';
    const USER_AGENT = 'Edoceo radix_auth_bitbucket v2013.19';

    private $_oauth;
    private $_oauth_client_id;
    private $_oauth_client_secret;

    // private $_oauth_token;
    // private $_oauth_token_secret;

    /**
        Create an Instance
        @param $a Consumer Key
        @param $b Consumer Secret
    */
    function __construct($a,$b)
    {
        $this->_oauth_client_id = $a;
        $this->_oauth_client_secret = $b;
        $this->_oauth = new OAuth($a,$b,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
        $this->_oauth->enableDebug();
    }

    /**
    */
    function getRequestToken($cb)
    {
        try {
            $uri = self::REQUEST_TOKEN_URI;
            $tok = $this->_oauth->getRequestToken(self::REQUEST_TOKEN_URI,$cb);
            radix::dump($tok);
        } catch (Exception $e) {
            radix::dump($this->_oauth->debugInfo);
            return false;
        }
        return $tok;
    }

    /**
        @param $tok Token Data
        @return URI on 4Sq
    */
    public function getAuthenticateURI($tok)
    {
        $uri = self::AUTHENTICATE_URI;
        $arg = array(
            'oauth_token' => $tok['oauth_token'],
        );
        $ret = ($uri . '?' . http_build_query($arg));
        return $ret;
    }

    /**
        @param $a the token passed back from the oAuth Provider, typically $_GET['oauth_token']
    */
    public function getAccessToken()
    {
        $uri = self::ACCESS_TOKEN_URI;
        // $arg = array(
        //     'client_id' => $this->_oauth_client_id,
        //     'client_secret' => $this->_oauth_client_secret,
        //     'grant_type' => 'authorization_code',
        //     'redirect_uri' => $a['redirect_uri'],
        //     'code' => $a['code'],
        // );
        // $res = radix_http::get($uri . '?' . http_build_query($arg));
        // radix::dump($res);
        // $ret = json_decode($res['body'],true);
        try {
            $res = $this->_oauth->getAccessToken($uri);
            $this->_oauth->setToken($res['oauth_token'],$res['oauth_token_secret']);
            // radix::dump($res);
            // exit;
            return $res;
        } catch (Exception $e) {
            radix::dump($this->_oauth->debugInfo);
            return false;
        }
        return $ret;
    }

    /**
    */
    function setToken($a,$b)
    {
        $this->_oauth->setToken($a,$b);
    }

    /**
        Easy Wrapper for Fetch
    */
    function api($uri,$post=null,$head=null)
    {
        $verb = 'GET';
        // $post = array();

        // $post = array(
        //     'format' => 'json',
        // );

        $uri = self::API_URI . ltrim($uri,'/') . '?access_token=' . $this->_access_token;
        // $ret = $this->fetch($uri,$post,$verb,$head);
        if (!empty($post)) {
            die('I do not handle this yet');
        }
        $res = radix_http::get($uri);
        if ($res['info']['content_type'] == 'application/json') {
            $res = json_decode($res['body'],true);
        }
        return $res;
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

}
