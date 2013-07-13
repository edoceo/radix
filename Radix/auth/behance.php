<?php
/**
    @file
    @brief oAuth Library for Behance
*/

class radix_auth_behance
{
    const AUTHENTICATE_URI  = 'https://www.behance.net/v2/oauth/authenticate';
    // const REQUEST_TOKEN_URI = 'https://www.behance.net/v2/oauth/token';
    const ACCESS_TOKEN_URI  = 'https://www.behance.net/v2/oauth/token';

    private $_oauth;
    private $_oauth_client;
    private $_oauth_client_secret;

    /**
        Create an Instance
        @param $a Consumer Key
        @param $b Consumer Secret
    */
    function __construct($a,$b)
    {
        $this->_oauth_client = $a;
        $this->_oauth_client_secret = $b;
        $this->_oauth = new OAuth($a,$b,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
        $this->_oauth->enableDebug();
    }

    /**
        getAuthenticateURI
        @see http://www.behance.net/dev/authentication#scopes
        @param $arg Token Data
        @return URI on BeHance
    */
    public function getAuthenticateURI($opt)
    {
        $uri = self::AUTHENTICATE_URI;
        $arg = array(
            'client_id' => $this->_oauth_client,
            'scope' => 'activity_read|collection_read|wip_read|project_read|invitations_read',
            'state' => md5(openssl_random_pseudo_bytes(128)),
            'redirect_uri' => $opt['redirect_uri'],
        );
        $ret = ($uri . '?' . http_build_query($arg));
        return $ret;
    }

    /**
    */
//    function getAccessToken($cb)
//    {
//        // try {
//        //     $uri = self::REQUEST_TOKEN_URI;
//        //     $tok = $this->_oauth->getRequestToken(self::REQUEST_TOKEN_URI,$cb);
//        //     radix::dump($tok);
//        // } catch (Exception $e) {
//        //     radix::dump($this->_oauth->debugInfo);
//        //     return false;
//        // }
//        // return $tok;
//    }

    /**
        @param $a the token passed back from the oAuth Provider, typically $_GET['oauth_token']
    */
    public function getAccessToken($arg)
    {
        $uri = self::ACCESS_TOKEN_URI;

//        try {
//            $res = $this->_oauth->getAccessToken($uri);
//            // radix::dump($res);
//        } catch (Exception $e) {
//            radix::dump($this->_oauth->debugInfo);
//            return false;
//        }
//        return $res;

        $arg = http_build_query(array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->_oauth_client,
            'client_secret' => $this->_oauth_client_secret,
            'code' => $_GET['code'],
            'redirect_uri' => $arg['redirect_uri'],
        ));

        $res = radix_http::post($uri,$arg);
        $res = json_decode($res['body'],true);
        // if ($res['info']['http_code'] == 200) {
        // } else
        return $res;
    }

    /**
    */
    function setToken($a,$b)
    {
        $this->_oauth->setToken($a,$b);
    }
}
