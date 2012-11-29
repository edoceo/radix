<?php
/**
    @file
    @brief Auth/Twitter - Tools for Google OAuth2

    @see https://developers.google.com/accounts/docs/OAuth2
*/

class radix_auth_twitter
{
    private static $__client_id;
    private static $__client_sk;
    private $_client_id; // OAuth2 ID
    private $_client_sk; // Secret Key

    static function init($id,$sk)
    {
        self::$__client_id = $id;
        self::$__client_sk = $sk;
    }
    /**
    */
    public function __construct($id=null,$sk=null)
    {
        if (empty($id)) $id = self::$__client_id;
        if (empty($sk)) $sk = self::$__client_sk;
        $this->_client_id = $id;
        $this->_client_sk = $sk;
    }
    /**
        Get the OAuth2.0 Login Link
        @param array $arg

        @see https://developers.google.com/accounts/docs/OAuth2Login
    */
    function auth_link($arg)
    {
        $one = array(
            // 'approval_prompt' => 'force', // Forces user to re-auth the application
            'client_id' => null, // $this->_client_id, // From Google
            'redirect_uri' => null, // Must be in lst on https://code.google.com/apis/console/
            'scope' => null, // 'https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.email+https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.profile',
            'response_type' => 'code', // Code or Token
            'state' => 'radix', // Round-Trip Parameter
        );
        if (empty($arg['client_id'])) {
            $arg['client_id'] = $this->_client_id;
        }

        $arg = array_merge($one,$arg);

        $uri = 'https://accounts.google.com/o/oauth2/auth?';
        $uri.= http_build_query($arg);

        return $uri;
    }
    /**
        @param $code the OAuth2 Code
        @param $page the Redirect to URI
        @see https://developers.google.com/accounts/docs/OAuth2WebServer
    */
    function auth_token($code,$page)
    {
        $uri = 'https://accounts.google.com/o/oauth2/token';
        $arg = array(
            'code' => $code,
            'client_id' => $this->_client_id,
            'client_secret' => $this->_client_sk,
            'redirect_uri' => $page,
            'grant_type' => 'authorization_code',
        );

        // POST it
        $ret = radix_http::post($uri,$arg);
        return $ret;

    }
}