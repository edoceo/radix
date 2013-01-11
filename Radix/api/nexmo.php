<?php
/**
    @file
    @brief Nexmo API Interface
    @note does not support sending binary or WAP push

    @see http://www.nexmo.com/documentation/libs/index.html
*/

class radix_api_nexmo
{
    const URI_BASE = 'https://rest.nexmo.com/sms/json';
    const UA = 'Radix Nexmo API v2012.45';

    private $_apikey; // api_key
    private $_secret; // api_secret

    /**
        Create the Object
        @param $a API key
        @param $b API secret
    */
    public function __construct($a,$b)
    {
        $this->_apikey = $a;
        $this->_secret = $b;
    }

    /**
       Send a text message
    */
    function sendText($from,$to,$text)
    {
        $post = array(
            'api_key' => $this->_apikey,
            'api_secret' => $this->_secret,
            'from' => '',
            'to' => '',
            'text' => '',
            'type' => 'unicode',
        );

        $ch = self::_curl_init($uri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $ret = self::_curl_exec($ch);
        print_r($ch);
    }

    /**
    */

    /**
        @param $apiep API endpoint
        @return properly formatted URI
    */
    private function _uri($apiep)
    {
        $uri = sprintf(self::URI_BASE,$this->_auth_id,$this->_auth_tk);
        $uri = trim($uri,'/');
        $uri.= '/';
        $uri.= trim(trim($apiep,'/'));
        $uri.= '/'; // Needs Trailing Slash
        return $uri;
    }

    /**
        Initialise the Curl
    */
    private static function _curl_init($uri)
    {
        $ch = curl_init($uri);

        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NETRC, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 16);
        curl_setopt($ch, CURLOPT_USERAGENT, self::UA);

        return $ch;
    }

    /**
        Exectue the Curl Request
    */
    private static function _curl_exec($ch,$async=false)
    {
        $r = array(
            'body' => curl_exec($ch),
            'info' => curl_getinfo($ch),
        );
        if (curl_errno($ch)) {
            $r['fail'] = sprintf('%d:%s',curl_errno($ch),curl_error($ch));
        }
        return $r;
    }
}
