<?php
/**
    @file
    @brief Tools for interacting with Tumblr
*/

class radix_api_tumblr // extends OAuth
{
    private static $_oauth_keys = array(
        'oauth_callback',
        'oauth_consumer_key',
        'oauth_nonce',
        'oauth_signature',
        'oauth_signature_method',
        'oauth_timestamp',
        'oauth_token',
        'oauth_version'
    );
    
    const API_URI = 'https://api.tumblr.com/v2';
    const REQUEST_TOKEN_URI = 'https://www.tumblr.com/oauth/request_token';
    const AUTHORIZE_URI = 'https://www.tumblr.com/oauth/authorize';
    const ACCESS_TOKEN_URI = 'https://www.tumblr.com/oauth/access_token';
    const USER_AGENT = 'Edoceo Radix Auth Tumbler v2013.14';

    private $_consumer_key;
    private $_consumer_secret;

    private $_oauth_token;
    private $_oauth_token_secret;

    /**
        Create an Instance
        @param $c_key Consumer Key
        @param $c_secret Consumer Secret
    */
    public function __construct($c_key,$c_secret)
    {
        $this->_consumer_key = $c_key;
        $this->_consumer_secret = $c_secret;
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
        Returns a Request Token
        @param $a = array('oauth_callback'=>URI)
    */
    public function getRequestToken($a=null)
    {
        $r = $this->_curl('POST',self::REQUEST_TOKEN_URI,$a);
        if ($r['info']['http_code'] == 200) {
            $x = null;
            parse_str($r['body'],$x);
            if (!empty($x['oauth_token']) && !empty($x['oauth_token_secret'])) {
                $r = $x;
            }
        }
        return $r;
    }

    /**
        Get the URI
        @param $a = array('oauth_callback' => URI, &c)
        @return URI on Tumblr
    */
    public function getAuthorizeURI($a=null)
    {
        $ret = array();
        $ret['tok'] = $this->getRequestToken($a);
        $ret['uri'] = self::AUTHORIZE_URI . '?oauth_token=' . $ret['tok']['oauth_token'];
        return $ret;
    }

    /**
        @param $a the token passed back from the oAuth Provider, typically $_GET['oauth_token']
    */
    public function getAccessToken($a=null)
    {
        if (empty($a)) {
            $a = array();
        }
        if (empty($a['oauth_token']))    $a['oauth_token'] =    $_GET['oauth_token'];
        if (empty($a['oauth_verifier'])) $a['oauth_verifier'] = $_GET['oauth_verifier'];
        $r = $this->_curl('post',self::ACCESS_TOKEN_URI,$a);
        parse_str($r['body'],$t);
        return $t;
    }

    /**
        Call Any API
        @param $uri to GET or POST
        @param $arg POST array data
        @return data array
    */
    public function api($uri,$arg=null)
    {
        $uri = self::API_URI . $uri;
        // if ('.json' != substr($uri,-5)) $uri .= '.json';
        $v = ( (!empty($arg) && is_array($arg)) ? 'post' : 'get');
        $r = $this->_curl($v,$uri,$arg);
        // print_r($r);
        if ($r['info']['http_code'] == 200) {
            $r = json_decode($r['body'],true);
        }
        return $r;
    }

    /**
        @param $verb GET, POST, DELETE
        @param $uri
        @param $args array of POST arguments
    */
    private function _curl($verb,$uri,$args=null)
    {
        $verb = strtolower($verb);

        $post_args = $args;
        $sign_args = array();

        // Sign Args Factors All Params
        $sign_args['oauth_consumer_key'] = $this->_consumer_key;
        $sign_args['oauth_nonce'] = md5(openssl_random_pseudo_bytes(128));
        $sign_args['oauth_signature_method'] = 'HMAC-SHA1';
        $sign_args['oauth_timestamp'] = $_SERVER['REQUEST_TIME'];
        if (!empty($this->_oauth_token)) $sign_args['oauth_token'] = $this->_oauth_token;
        $sign_args['oauth_version'] = '1.0';
        // Add in Request Args
        if (is_array($args)) {
            foreach ($args as $k=>$v) {
                if (empty($sign_args[$k])) $sign_args[$k] = $v;
            }
        }
        $sign_args['oauth_signature'] = $this->_makeSignature($verb, $uri, $sign_args);

        // Create Headers
        $head = array('Expect:');
        $buf = array();
        foreach(self::$_oauth_keys as $k) {
            if (!empty($sign_args[$k])) {
                $buf[] = sprintf('%s="%s"',$k,rawurlencode($sign_args[$k]));
            }
            unset($post_args[$k]);
        }
        $head = array(
            'Authorization: OAuth ' . implode(', ',$buf),
            'Expect:',
        );

        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
        if ( ($verb == 'post') && (is_array($post_args)) ) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_args));
        }
        $ret['body'] = curl_exec($ch);
        $ret['info'] = curl_getinfo($ch);
        curl_close($ch);
        return $ret;
    }

    /**
        @param $uri
        @return sanatized URI
    */
    private function _cleanURI($uri)
    {
        $uri = parse_url($uri);
        $uri['scheme'] = strtolower($uri['scheme']);
        $uri['host']   = strtolower($uri['host']);
        if (empty($uri['port'])) {
            $uri['port'] = null;
        }

        $ret = "{$uri['scheme']}://{$uri['host']}";
        if ($uri['port']) {
            if ( ($uri['scheme'] === 'http' && $uri['port'] !== 80) || ($uri['scheme'] === 'https' && $uri['port'] !== 443) ) {
               $ret .= ":{$uri['port']}";
            }
        }
        $ret .= $uri['path'];
        return $ret;
    }

    /**
        @param $verb HTTP Verb: GET|POST
        @param $uri URI we're requesting (will sanatize internally)
        @param $args POST arguments
        @return base64 string
    */
    private function _makeSignature($verb,$uri,$post=null)
    {
        $base = strtoupper($verb) . '&';
        $base.= rawurlencode($this->_cleanURI($uri)) . '&';

        // Create the Args List
        $x = parse_url($uri);
        $args = array();
        if (!empty($x['query'])) {
            parse_str($x['query'],$buf);
            foreach ($buf as $k=>$v) {
                $args[rawurlencode($k)] = rawurlencode($v);
            }
        }
        if ( !empty($post) && (count($post)) ) {
            foreach ($post as $k=>$v) {
                $args[rawurlencode($k)] = rawurlencode($v);
            }
        }
        ksort($args);

        // @see https://dev.twitter.com/docs/auth/creating-signature / Creating the signature base string / Step 5
        $buf = array();
        foreach($args as $k=>$v) {
            $buf[] = sprintf('%s=%s',$k,$v);
        }
        // Step 5
        $base.= rawurlencode(implode('&',$buf));
        return $this->_sign($base);
    }

    /**
        Sign the String with the current Secrets & Keys
        @param $s the string to sign
        @return base64 string
    */
    private function _sign($s)
    {
        $k = (rawurlencode($this->_consumer_secret) . '&' . rawurlencode($this->_oauth_token_secret));
        $r = base64_encode(hash_hmac('sha1', $s, $k, true));
        return $r;
    }
}
