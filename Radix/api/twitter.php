<?php
/**

    // https://dev.twitter.com/discussions/11494

    @file
    @brief Tools for interacting with Twitter

    @todo would be cool to get listed on https://dev.twitter.com/docs/twitter-libraries
    
    @package radix

    @see https://dev.twitter.com/docs/twitter-libraries
    @see https://github.com/jdp/twitterlibphp/blob/master/twitter.lib.php
    @see https://github.com/abraham/twitteroauth
    @see https://github.com/jmathai/twitter-async
    @see http://pear.php.net/package/Services_Twitter

    @see https://dev.twitter.com/docs/auth/implementing-sign-twitter

*/

class radix_api_twitter
{
    const API_URI = 'https://api.twitter.com/1.1/';
    const AUTHENTICATE_URI = 'https://twitter.com/oauth/authenticate';
    const AUTHORIZE_URI = 'https://api.twitter.com/oauth/authorize';
    const TOKEN_REQUEST_URI = 'https://api.twitter.com/oauth/request_token';
    const TOKEN_ACCESS_URI = 'https://api.twitter.com/oauth/access_token';
    const USER_AGENT = 'Edoceo Radix Twitter';

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

    private $_format = 'json';

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
        @param $a the token passed back from the oAuth Provider, typically $_GET['oauth_token']
    */
    public function getAccessToken($a=null)
    {
        if (empty($a)) {
            $a = array();
        }
        if (empty($a['oauth_token']))    $a['oauth_token'] =    $_GET['oauth_token'];
        if (empty($a['oauth_verifier'])) $a['oauth_verifier'] = $_GET['oauth_verifier'];
        $r = $this->_curl('post',self::TOKEN_ACCESS_URI,$a);
        parse_str($r['body'],$t);
        return $t;
    }

    /**
        Get the URI from Twitter
        @param $a = array('oauth_callback' => URI, &c)
        @return URI on Twitter
    */
    public function getAuthenticateURI($a=null)
    {
        $x = $this->getRequestToken($a);
        parse_str($x['body'],$t);
        return self::AUTHENTICATE_URI . '?oauth_token=' . $t['oauth_token'];
    }

    /**
        getAuthorizeURI()
        @param $a = array('oauth_callback' => URI, &c)
        @return URI on Twitter
    */
    public function getAuthorizeURI($a=null)
    {
        $x = $this->getRequestToken($a);
        parse_str($x['body'],$t);
        return self::AUTHORIZE_URI . '?' . http_build_query(array(
            'oauth_callback' => $a['oauth_callback'],
            'oauth_token' => $t['oauth_token'],
        ));
    }

    /**
        Returns a Request Token
        @param $a = args
    */
    public function getRequestToken($a=null)
    {
        $r = $this->_curl('POST',self::TOKEN_REQUEST_URI,$a);
        return $r;
    }

    /**
        Do a Tweet, Updates the authenticated user's status.

        @param string $t Text of the status, no URL encoding necessary
        @return string
    */
    public function tweet($t)
    {
        return $this->_curl('POST','https://api.twitter.com/1.1/statuses/update.json',array('status' => $t));
    }

    /**
        Gets the User Time Line
    */
    public static function getUserTimeline($user,$size=10)
    {
        $uri = sprintf('https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=%s&count=%d',$user,$size);
        $feed = Radix_HTTP::get($uri);
        $json = json_decode($feed['body']);
        return $json;
    }
    
    /**
        Call Any API
        @param $uri to GET or POST
        @param $arg POST array data
        @return data array
    */
    public function api($uri,$arg=null)
    {   
        $uri = self::API_URI . trim($uri,'/');
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
        Twitter Link
        @param $t text to share
        @param $uri link to share
        @param $via a via option
        @param $dc = horizontal|vertical
        @param $tag hashtag?
        @return <a> tag
    */
    public static function share_link($t,$uri,$via,$dc='vertical')
    {
        $ret = '<a href="https://twitter.com/share" class="twitter-share-button"';
        $ret.= ' data-count="' . $dc . '"';
        $ret.= ' data-text="' . $t . '"'; // Blstr is a awesome tool for social status broadcasting"
        $ret.= ' data-url="' . $uri . '"'; // http://radix.edoceo.com
        $ret.= ' data-via="' . $via . '" data-related="' . $via . '"';
        $ret.= ' data-hashtags="' . $tag . '">Tweet</a>';
        return $ret;
    }

    /**
        @param $verb GET, POST, DELETE
        @param $uri
        @param $args array of POST arguments
    */
    private function _curl($verb,$uri,$args=null)
    {
        // echo "_curl($verb,$uri,$args)\n";
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
        // Have to Strip 'user_id' parameter from the URI
        $sign_args['oauth_signature'] = $this->_makeSignature($verb, $uri, $sign_args);
        // ksort($sign_args);
        // print_r($sign_args);

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
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
        if ($verb == 'post') {
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
        @see https://dev.twitter.com/docs/auth/creating-signature
        @param $verb
        @param $uri
        @param $args
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
        // print_r($args);
        
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
