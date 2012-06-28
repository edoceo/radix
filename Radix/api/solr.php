<?php
/**

*/

class radix_api_solr
{
    private $_ch;
    private $_host = 'http://localhost:8983/solr';
    private $_page; // Most Recent Page Buffer
    private $_stat; // Last Status

    public function __construct($host=null)
    {
        if ($host) $this->_host = $host;
    }
    
    
    public function search($arg)
    {
        if (is_string($arg)) {
            $arg['q'] = $arg;
        }

        // http://ils-server:8983/solr/select/?q=*%3A*&version=2.2&start=0&rows=10&indent=on
        $this->_curl_exec($this->_host . '/select/?q=*%3A*&version=2.2&start=0&rows=10&indent=on');
        radix::dump($this);
    
    }
    
    static function _curl_exec($uri)
    {
        $ch = curl_init($uri);
        // Booleans
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_CRLF, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NETRC, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_USERAGENT,'Edoceo Radix Solr Client v2012.10');

        $this->_page = curl_exec($ch);
        $this->_stat = curl_getinfo($ch);

        return $ch;
    }
 
}