<?php
/**
    @file
    @brief Auth/Instagram
    
    @see http://instagram.com/developer/authentication/
*/

require_once('Radix/HTTP.php');

class radix_auth_instagram
{
    const API_URI = 'https://api.instagram.com/v1/';
    const AUTHORIZE_URI = 'https://api.instagram.com/oauth/authorize/';
    const ACCESS_TOKEN_URI = 'https://api.instagram.com/oauth/access_token';
    const USER_AGENT = 'Edoceo radix_auth_instagram v2013.16';

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
        // $this->_oauth = new OAuth($a,$b,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
        // $this->_oauth->enableDebug();
    }

    /**
        @param $arg 
        @return URI on 4Sq
    */
    public function getAuthorizeURI($opt)
    {
        $uri = self::AUTHORIZE_URI;
        $arg = array(
            'client_id' => $this->_oauth_client_id,
            'redirect_uri' => $opt['redirect_uri'],
            'response_type' => 'code',
        );
        // $arg = array_merge($arg,$opt);
        $ret = $uri . '?' . http_build_query($arg);
        return $ret;

    }

    /**
        @param $a the token passed back from the oAuth Provider, typically $_GET['oauth_token']
    */
    public function getAccessToken($opt)
    {
        // $uri = self::ACCESS_TOKEN_URI;
        if (empty($opt['code'])) $opt['code'] = $_GET['code'];

        $arg = array(
            'client_id' => $this->_oauth_client_id,
            'client_secret' => $this->_oauth_client_secret,
            'grant_type' => 'authorization_code',
            'code' => $opt['code'],
            'redirect_uri' => $opt['redirect_uri'],
        );

        // $uri = $uri; . '?' . http_build_query($arg);
        $res = radix_http::post(self::ACCESS_TOKEN_URI,$arg,array('User-Agent: ' . self::USER_AGENT));
        if ($res['info']['content_type'] == 'application/json') {
            $res = json_decode($res['body'],true);
            if (!empty($res['access_token'])) {
                $this->_access_token = $res['access_token'];
            }
        }
        return $res;
    }

    /**
    */
    function setAccessToken($a)
    {
        return $this->_access_token = $a;
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
}