<?php
/**
    @file
    @brief A Tool for Consuming Feeds
    $Id$
*/

class radix_feed implements arrayaccess, iterator
{
    private $_feed;
    private $_idx;
    private $_max;
    private $_uri;

    public $xml;

    function __construct($uri)
    {
        $this->_uri = $uri;
    }
    /**
        Load the Feed
        @return true|false
    */
    function load()
    {
        $res = $this->_curl();
        $xml = simplexml_load_string($res['body']);
        // Radix::dump($xml);

        switch (strtolower($xml->getName())) {
        case 'atom':
            // $this->_data['title'] = strval($this->_xml->title);
            // $this->_data['updated'] = strval($this->_xml->updated);
            // $this->_data['author'] = strval($this->_xml->author->name);
            $this->_idx = 0;
            $this->_max = count($this->_xml->entry);
            break;
        case 'rss':
            $this->_data['name'] = strval($xml->channel->title);
            $this->_data['note'] = strval($xml->channel->description);
            $this->_data['link'] = strval($xml->channel->link);
            $this->_data['date'] = strval($xml->channel->pubDate);
            foreach ($xml->channel->item as $item) {
                // radix::dump($item);
                $this->_feed[] = array(
                    'name' => strval($item->title),
                    'note' => strval($item->description),
                    'link' => strval($item->link),
                    'date' => strval($item->pubDate),
                );
            }
            break;
        }
        $this->_idx = 0;
        $this->_max = count($this->_feed);
        $this->xml = $xml;
    }

    /**
        Array Access Functions
    */
    function offsetSet($k, $v) { $this->_data[$k] = $v; }
    function offsetExists($k) { return isset($this->_data[$k]); }
    function offsetUnset($k) { unset($this->_data[$k]); }
    function offsetGet($k) { return isset($this->_data[$k]) ? $this->_data[$k] : null; }
    /**
        Iterator Access Functions
    */
    function current() { return $this->_feed[$this->_idx]; }
    function next() { $r = $this->_feed[$this->_idx]; $this->_idx++; }
    function key() { return $this->_idx; }
    function valid() { return $this->_idx < $this->_max; }
    function rewind() { return $this->_idx = 0; }
    /**
    
    */
    private function _curl()
    {
        $ch = curl_init($this->_uri);
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
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT,'Edoceo Radix Feed v2012.08');

        $ret = array(
            'body' => curl_exec($ch),
            'info' => curl_getinfo($ch),
        );

        return $ret;
    }

}