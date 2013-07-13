<?php
/**
    @file
    @brief oAuth Library for AngelList

    @see https://angel.co/api/oauth/faq
*/
require_once('Radix/HTTP.php');

class radix_auth_angellist
{
    const API_URI = 'https://api.angel.co/1';
    const AUTHORIZE_URI = 'https://angel.co/api/oauth/authorize';
    const ACCESS_TOKEN_URI = 'https://angel.co/api/oauth/token';
    const USER_AGENT = 'Edoceo radix_auth_angellist v2013.20';

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
            // 'redirect_uri' => $opt['redirect_uri'],
            'response_type' => 'code',
            'scope' => $opt['scope'],
        );
        // $arg = array_merge($arg,$opt);
        $ret = $uri . '?' . http_build_query($arg);
        return $ret;

    }

    /**
        @param $a the token passed back from the oAuth Provider, typically $_GET['oauth_token']
    */
    public function getAccessToken($opt=null)
    {
        // $uri = self::ACCESS_TOKEN_URI;
        if (empty($opt['code'])) $opt['code'] = $_GET['code'];

        $arg = array(
            'client_id' => $this->_oauth_client_id,
            'client_secret' => $this->_oauth_client_secret,
            'grant_type' => 'authorization_code',
            'code' => $opt['code'],
            // 'redirect_uri' => $opt['redirect_uri'],
        );

        $res = radix_http::post(self::ACCESS_TOKEN_URI,$arg,array('User-Agent: ' . self::USER_AGENT));
        $type = strtok($res['info']['content_type'],';');
        if ($type == 'application/json') {
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
    function api($uri,$post=null)
    {
        $uri = sprintf('%s/%s',self::API_URI,trim($uri,'/'));
        if (!empty($post)) {
            die('I do not handle this yet');
        }
        $res = radix_http::get($uri,array('Authorization: ' . $this->_access_token));
        $type = strtok($res['info']['content_type'],';');
        if ($type == 'application/json') {
            $res = json_decode($res['body'],true);
        }
        return $res;
    }
}