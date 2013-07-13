<?php
/**
    @file
    @brief oAuth Library for Geeklist

    @see http://geekli.st/applications
*/

class radix_auth_geeklist
{
    const AUTHORIZE_URI = 'https://geekli.st/oauth/authorize';
    const REQUEST_TOKEN_URI = 'http://api.geekli.st/v1/oauth/request_token';
    const ACCESS_TOKEN_URI = 'http://api.geekli.st/v1/oauth/access_token';

    private $_oauth_client_id;
    private $_oauth_client_secret;

    // private $_oauth_token;
    // private $_oauth_token_secret;

    /**
        Create an Instance
        @param $a Consumer Key
        @param $b Consumer Secret
    */
    public function __construct($a,$b)
    {
        $this->_oauth_client_id = $a;
        $this->_oauth_client_secret = $b;
    }

    /**
        @param $t Token
        @param $s Secret
    */
    public function setToken($t,$s)
    {
        $this->_oauth_token = $t;
        $this->_oauth_token_secret = $s;
    }

    /**
        @param $cb_uri Callback URI
        @return URI on 4Sq
    */
    public function getAuthenticateURI($cb_uri)
    {
        $uri = self::AUTHENTICATE_URI;
        $arg = array(
            'client_id' => $this->_oauth_client_id,
            'response_type' => 'code',
            'redirect_uri' => $cb_uri,
        );
        $ret = $uri . '?' . http_build_query($arg);
        return $ret;
    }

    /**
        @param $a the token passed back from the oAuth Provider, typically $_GET['oauth_token']
    */
    public function getAccessToken($a=null)
    {
        $uri = self::TOKEN_ACCESS_URI;
        $arg = array(
            'client_id' => $this->_oauth_client_id,
            'client_secret' => $this->_oauth_client_secret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $a['redirect_uri'],
            'code' => $a['code'],
        );
        $res = radix_http::get($uri . '?' . http_build_query($arg));
        radix::dump($res);
        $ret = json_decode($res['body'],true);
        return $ret;
    }
}
