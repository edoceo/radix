<?php
/**
 * Tools for interacting with Twitter

    @package radix

    @see https://dev.twitter.com/docs/twitter-libraries
    @see https://github.com/jdp/twitterlibphp/blob/master/twitter.lib.php
    @see https://github.com/abraham/twitteroauth
    @see https://github.com/jmathai/twitter-async
    @see http://pear.php.net/package/Services_Twitter

*/

namespace Edoceo\Radix\Service

class Twitter
{
    const API_URI = 'http://twitter.com';
    const AUTH_URI = 'http://twitter.com/oauth/authenticate';
    const URI_AUTHORIZE = 'http://api.twitter.com/oauth/authorize';
    const TOKEN_REQUEST_URI = 'http://api.twitter.com/oauth/request_token';
    const TOKEN_ACCESS_URI = 'http://api.twitter.com/oauth/access_token';
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
        Get the URI from Twitter
    */
    public function getAuthenticateURI()
    {
        $x = $this->getRequestToken();
        parse_str($x['body'],$t);
        return self::AUTH_URI . '?oauth_token=' . $t['oauth_token'];
    }

    /**
        @param $a = somethign?
    */
    public function getAuthorizeURI($a=null)
    {
        $x = $this->getRequestToken($a);
        parse_str($x['body'],$t);
        return self::URI_AUTHORIZE . '?oauth_token=' . $t['oauth_token'];
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
        Returns a Request Token
        @param $a = something?
    */
    private function getRequestToken($a=null)
    {
        $r = $this->_curl('POST',self::TOKEN_REQUEST_URI,$a);
        return $r;
    }

    /**
        @deprecated
    */
    public function __call($name, $params = null)
    {
        $parts  = explode('_', $name);
        $method = strtoupper(array_shift($parts));
        $parts  = implode('_', $parts);
        $path   = '/' . preg_replace('/[A-Z]|[0-9]+/e', "'/'.strtolower('\\0')", $parts) . '.json';
        if(!empty($params))
          $args = array_shift($params);

        // intercept calls to the search api
        if(preg_match('/^(search|trends)/', $parts)) {
          $query = isset($args) ? http_build_query($args) : '';
          $url = "{$this->searchUrl}{$path}?{$query}";
          $ch = curl_init($url);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

          return new EpiTwitterJson(EpiCurl::getInstance()->addCurl($ch));
        }
        // Radix::dump($args);

        $r = $this->_curl($method, self::API_URI . $path, $args);
        $r = json_decode($r['body']);
        return $r; // new EpiTwitterJson(call_user_func(array($this, 'httpRequest'), $method, "{$this->apiUrl}{$path}", $args));
    }

    /**
        Do a Tweet, Updates the authenticated user's status.

        @param string $t Text of the status, no URL encoding necessary
        @return string
    */
    // function updateStatus($status, $reply_to = null, $format = 'xml') {
    public function tweet($t)
    {
        return $this->_curl('POST','http://api.twitter.com/1/statuses/update.json',array('status' => $t));
    }

    /**
        Gets the User Time Line
    */
    public static function getUserTimeline($user,$size=10)
    {
        $uri = sprintf('http://api.twitter.com/1/statuses/user_timeline/%s.json?count=%d',$user,$size);
        $feed = Radix_HTTP::get($uri);
        $json = json_decode($feed['body']);
        return $json;
    }

    /**
        Twitter Link
        @return <a> tag
    */
    public static function share_link($text,$url,$via,$dc='vertical')
    {
        $ret = '<a href="https://twitter.com/share" class="twitter-share-button"';
        $ret.= ' data-count="' . $dc . '"';
        $ret.= ' data-text="' . $text. '"'; // Blstr is a awesome tool for social status broadcasting"
        $ret.= ' data-url="' . $url . '"'; // http://blstr.co
        $ret.= ' data-via="' . $via . '" data-related="' . $via . '"';
        $ret.= ' data-hashtags="' . $tag . '">Tweet</a>';
        return $ret;
    }

    /**
        @param $verb GET, POST, DELETE
        @param $uri
        @param $args array of arguments
    */
    public function _curl($verb,$uri=null,$args=null)
    {
        // echo "_curl($verb,$uri,$args)\n";
        $verb = strtoupper($verb);

        $post_args = $args;
        $sign_args = array();

        // Sign Args Factors All Params
        $sign_args['oauth_consumer_key'] = $this->_consumer_key;
        $sign_args['oauth_nonce'] = '1234567890'; // md5(openssl_random_pseudo_bytes(128));
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
        ksort($sign_args);
        //Radix::dump($sign_args);

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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
        if ($verb == 'POST') {
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
        if(!empty($uri['query'])) {
          $ret .= "?{$uri['query']}";
        }

        return $ret;
    }

    /**
        @param $verb
        @param $uri
        @param $args
        @return base64 string
    */
    private function _makeSignature($verb,$uri,$args)
    {
        ksort($args);
        $base = strtoupper($verb) . '&';
        $base.= rawurlencode($this->_cleanURI($uri)) . '&';
        foreach($args as $k=>$v) {
            $buf[] = sprintf('%s=%s',rawurlencode($k),rawurlencode($v));
        }
        $base.= rawurlencode(implode('&',$buf));
        // echo "base:$base\n";
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
        // echo "skey:$k\n";
        $r = base64_encode(hash_hmac('sha1', $s, $k, true));
        return $r;
    }
}
