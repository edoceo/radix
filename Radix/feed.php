<?php
/**
    @file
    @brief A Tool for Consuming Atom/RSS Feeds
    
    @see http://blog.sherifmansour.com/?p=302
    @see http://stackoverflow.com/questions/595616/what-is-the-correct-mime-type-to-use-for-an-rss-feed
*/

class radix_feed implements arrayaccess, iterator
{
    const MIME_ATOM = 'application/atom+xml';
    const MIME_RSS  = 'application/rss+xml';

    // private $_feed;
    // private $_info;
    public $_list;
    public $_meta;

    private $_idx;
    private $_max;
    private $_uri;

    public $xml;

    /**
        Construct based on URI
    */
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

        $type = $this->_type();
        switch ($type) {
        case self::MIME_ATOM:
            $this->xml = simplexml_load_string($this->_body);
            $this->_meta['title'] = strval($this->_xml->title);
            $this->_meta['time_updated'] = strtotime($this->_xml->updated);
            $this->_meta['author'] = strval($this->_xml->author->name);
            break;
        case self::MIME_RSS:
            $this->xml = simplexml_load_string($this->_body);
            $this->_meta['name'] = strval($this->xml->channel->title);
            $this->_meta['note'] = strval($this->xml->channel->description);
            $this->_meta['link'] = strval($this->xml->channel->link);
            $this->_meta['time_updated'] = strtotime($this->xml->channel->pubDate);
            foreach ($this->xml->channel->item as $item) {
                // radix::dump($item);
                $this->_list[] = array(
                    'name' => strval($item->title),
                    'note' => strval($item->description),
                    'link' => strval($item->link),
                    'time_updated' => strval($item->pubDate),
                );
            }
            break;
        default:
            throw new Exception(__CLASS__ . " Cannot Handle: $type");
        }

        // @todo should parse to common thing here?

        $this->_idx = 0;
        // $this->_max = count($this->_list);
        // $this->_max = count($this->_feed);
        return true;
    }

    /**
        Parse Feed to Common Format
    */
    function parse()
    {
        
        return array(
            'meta' => $this->_meta,
            'feed' => $this->_feed,
        );
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
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_CRLF, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NETRC, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        // curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 16);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 16);
        curl_setopt($ch, CURLOPT_USERAGENT,'Edoceo Radix Feed v2013.19');

        $this->_body = curl_exec($ch);
        $this->_info = curl_getinfo($ch);

        return $this->_info['http_code'];
    }

    /**
        @return canonical mime type for feed
    */
    public function _type()
    {
        $type = strtolower(strtok($this->_info['content_type'],';'));
        switch ($type) {
        case 'application/atom+xml': // Canonical
        case 'atom':
            // $this->_data['title'] = strval($this->_xml->title);
            // $this->_data['updated'] = strval($this->_xml->updated);
            // $this->_data['author'] = strval($this->_xml->author->name);
            // $this->_idx = 0;
            // $this->_max = count($xml->entry);
            return self::MIME_ATOM;
            break;
        case 'application/rss+xml': // Canonical
        case 'application/xml':
        case 'text/xml':
        case 'rss':
            return self::MIME_RSS;
            break;
        case 'text/html':
            // radix::dump($this->_body);
            $lead = strtolower(trim(substr($this->_body,0,4)));
            switch ($lead) {
            case '<rss':
                // radix::dump( str_replace('><',">\n<",$this->_body) );
                return self::MIME_RSS;
                break;
            case '<!do':
                // HTML, ignore here, handled by exception below
                $this->_body = null;
                $this->_uri = null;
                break;
            default:
                //radix::dump($this->_body);
                //  die("Canot read " . html($lead));
            }
            break;
        }
        throw new Exception("Cannot read type: $type, perhaps not an Atom or RSS feed?",__LINE__);
        return false;
    }

}