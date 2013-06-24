<?php
/**
    @file
    @brief Provides and Interface to Phaxio
*/

class radix_api_phaxio
{
    const UA = 'Radix Phaxio API v2012.28';

    private static $__init = false;
    private static $__user;
    private static $__auth;

    private $_apiuri = 'https://api.phaxio.com/v1';

    private $_apikey;
    private $_secret;

    /**
        Init the Static World
        @param $u Twilio Account SID
        @param $a Twilio Auth Token
    */
    public static function init($u,$a)
    {
        self::$__user = $u;
        self::$__auth = $a;
        // $b = sprintf(self::URI_BASE . self::URI_PATH,$u,$a);
        // if (strlen($b) > strlen(self::URI_BASE)) {
        // }
        self::$__init = true;
    }

    /**
        Create an Instance Object, if $u and $a are not provied use the static ones
        @param $u Twilio Account SID
        @param $a Twilio Auth Token
    */
    public function __construct($u=null,$a=null)
    {
        if (null===$u && null===$a && self::$__init) {
            $u = self::$__user;
            $a = self::$__auth;
        }
        $this->_apikey = $u;
        $this->_secret = $a;
    }

    /**
        Make the Actual API Call
        @param $api the path, like /faxFile
        @param $post Post Data as array
    */
    public function api($api,$post)
    {
        $uri = $this->_apiuri . $api;

        $post['api_key'] = $this->_apikey;
        $post['api_secret'] = $this->_secret;

        // Clean Empties cause their API don't like them
        foreach (array_keys($post) as $x) {
            if (empty($post[$x])) unset($post[$x]);
        }

        $ch = self::_curl_init($uri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        return self::_curl_exec($ch);
    }

    /**
        Send a Fax
        @param $id fax ID
        @param $ft File Type s => 129x167 jpeg, l => 300x388jpeg, p => full PDF
    */
    public function faxFile($id,$ft='s')
    {
        $pd = array(
            'id' => $id,
            'type' => $ft,
        );
        return $this->api('/faxFile',$pd);
    }

    /**
        Retrieve Fax List
        @param $ad Alpha Date - Start Timestamp of Query
        @param $od Omega Date - End of List
    */
    public function faxList($ad=null,$od=null)
    {
        $pd = array(
            'start' => $ad,
            'end' => $od,
        );
        $r = $this->api('/faxList',$pd);
        return $r;
    }

    /**
        Status of Fax
        @param $id of Fax
        @return API Data Array
    */
    public function faxStatus($id)
    {
        $pd = array('id' => $id);
        $r = $this->api('/faxStatus',$pd);
        return $r;
    }

    /**
        Retrieve Number List
        @param $ac
        @param $pn
        @return Array of Number Arrays
    */
    public function listNumbers($ac=null,$pn=null)
    {
        $pd = array(
            'area_code' => $ac,
            'number' => $pn,
        );
        $r = $this->api('/numberList',$pd);
        if ($r['info']['http_code']==200) {
            $r = json_decode($r['body'],true);
        }
        return $r;
    }

    /**
        Allocate a New Number
        @param $ac Area Code
        @param $cb Call Back URI
        @return API Data Array
    */
    public function provisionNumber($ac,$cb=null)
    {
        $pd = array(
            'area_code' => $ac,
            'callback_url' => $cb,
        );
        return $this->api('/provisionNumber',$pd);
    }

    /**
        Sends a Fax
        @param $from From Number, eg: 12062826500
        @param $rcpt Recipient Number, eg: 12025551212
        @param $file A file on the Local Filesystem
        @return API Data Array
    */
    public function send($from,$rcpt,$file)
    {
        $post = array(
            'caller_id' => $from,
            'to' => $rcpt,
            'filename' => sprintf('@%s',ltrim($file,'@')),
            'string_data' => null,
            'string_data_type' => null, // html | url | text
            'batch' => false,
            'batch_delay' => 600, // Time in Seconds
            'batch_collision_avoidancd' => true,
            'callback_url' => null,
        );
        return $this->api('/send',$post);

    }

    public function testReceive($from,$rcpt,$file)
    {
        $post = array(
            'from_number' => $from,
            'to_number' => $rcpt,
            'filename' => sprintf('@%s',ltrim($file,'@')),
        );
        return $this->api('/testReceive',$post);
    }

    /**
        Executes the Single or Multiple Requests
        @param $uri
        @return CURL Handle
    */
    private static function _curl_init($uri)
    {
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
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt(self::$_ch, CURLOPT_SSLVERSION, 3); // 2, 3 or GnuTLS
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::UA);

        // curl_setopt(self::$_ch, CURLOPT_HEADERFUNCTION, array('self','_curl_head'));

        return $ch;
    }

    /**
        Execute the CURL request
        @param $ch CURL Handle
        @return API data
    */
    private static function _curl_exec($ch)
    {
        $res = array(
            'body' => curl_exec($ch),
            'info' => curl_getinfo($ch),
        );
        $ret = $res;
        $ret['code'] = 200;

        if (curl_errno($ch)) {
            return array(
                'success' => false,
                'message' => sprintf('%d:%s',curl_errno($ch),curl_error($ch)),
            );
        }
        // radix::dump($r);

        if ('application/json' == $res['info']['content_type']) {
            $ret = json_decode($res['body'],true);
        }

        if (200 != $res['info']['http_code']) {
            $ret['code'] = $res['info']['http_code'];
        }

        return $ret;
    }
}