<?php
/**
    @file
    @brief oAuth Library for FourSquare

    @see https://developer.foursquare.com/overview/auth.html
*/

class radix_auth_foursquare
{
    const AUTHENTICATE_URI = 'https://foursquare.com/oauth2/authenticate';
    const TOKEN_ACCESS_URI = 'https://foursquare.com/oauth2/access_token';

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
