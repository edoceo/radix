<?php
/**

*/

class radix_api_enom
{
    const BASE = 'http://reseller.enom.com/interface.asp?';
    const UA = 'Edoceo Radix Enom API Interface v2000';

    private $_user;
    private $_pass;

    function __construct($user,$pass)
    {
        $this->_user = $user;
        $this->_pass = $pass;
    }
    /**
        @param $cmd command to execute
        @param $arg argument array
    */
    function api($cmd,$arg=null)
    {
        $uri = self::BASE;
        $uri.= http_build_query(array('uid'=>$this->_user,'pw'=>$this->_pass,'command'=>$cmd));
        if (is_array($arg)) {
            $uri.= '&' . http_build_query($arg);
        }
        $ret = $this->_api($uri);
        return $ret;
    }
    /**
        @return a list of domains!
    */
    function getDomains()
    {
        $res = $this->api('getdomains',array('display'=>100));
        return $res;
    }
    /**
        @param $d domain to get hosts in 
    */
    function getHosts($d)
    {
        list($sld,$tld) = explode('.',$d);
        return $this->api('gethosts',array('sld'=>$sld,'tld'=>$tld));
    }
    /**
        Executes the HTTP request, returns formatted data
        @param $uri full enom URI to query
        @return response converted to data array
    */
    private function _api($uri)
    {
        $ret = array(); // Return Value
        $ch = curl_init($uri);
        // Booleans
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_CRLF, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NETRC, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        // if ( (!empty(self::$_opts['verbose'])) && (is_resource(self::$_opts['verbose'])) ) {
        //     curl_setopt(self::$_ch, CURLOPT_VERBOSE, true);
        //     curl_setopt(self::$_ch, CURLOPT_STDERR, self::$_opts['verbose']);
        // }
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::UA);

        // @note the API always returns an HTTP 200 code; even on error
        $body = curl_exec($ch);
        $line_list = explode("\n",$body);
        foreach ($line_list as $line) {
            if (preg_match('/^([A-Za-z]+)(\d+)=(.+)$/',$line,$m)) {
                // print_r($m);
                // $show_tick = max($show_list[$m[1]],$m[2]);
                // $show_list[$m[1]] = $show_tick;
                $ret['response'][ intval($m[2]) ][ strtolower(trim($m[1])) ] = trim($m[3]);
            } elseif (preg_match('/^([A-Za-z]+)=(.+)$/',$line,$m)) {
                $ret[ strtolower(trim($m[1])) ] = trim($m[2]);
            }
        }
        return $ret;
    }

}

