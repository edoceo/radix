<?php
/**
    @file
    @brief Plivo API Interface

    @see http://www.plivo.com/docs/api
*/

class radix_api_plivo
{
    const URI_BASE = 'https://%s:%s@api.plivo.com/v1/Account/%s/';
    const UA = 'Radix Plivo API v2012.44';

    private $_auth_id; // Authe ID
    private $_auth_tk; // Auth Token

    //
    // https://api.plivo.com/v1/Account/{auth_id}/Call
    /**
        @param $a AUTH ID
        @param $b AUTH TOKEN
    */
    public function __construct($a,$b)
    {
        $this->_auth_id = $a;
        $this->_auth_tk = $b;
    }

    function api($cmd,$arg=null)
    {
        $uri = self::_uri('/Account/' . $this->_auth_id . '/' . $cmd . '/');
        $uri.= '?' . http_build_query($arg);
        $ch = self::_curl_init($uri);
        $ret = self::_curl_exec($ch);
        if (($ret['info']['http_code'] == 200) && ($ret['info']['content_type'] == 'application/json')) {
            $ret = json_decode($ret['body'],true);
        }
        return $ret;
    }

    /**
    */
    function auth()
    {
        $uri = self::_uri('/Account/' . $this->_auth_id);
        $ch = self::_curl_init($uri);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $ret = self::_curl_exec($ch);
        if (($ret['info']['http_code'] == 200) && ($ret['info']['content_type'] == 'application/json')) {
            $ret = json_decode($ret['body'],true);
        }
        return $ret;
    }

    /**
        @param $arg array fr, to, answer_url
    */
    function callInit($arg)
    {
        // https://api.plivo.com/v1/Account/{auth_id}/Call/
        $uri = self::_uri('Call');
        $arg = json_encode($arg);
        $ch = self::_curl_init($uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($arg))
        );
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arg);
        $ret = self::_curl_exec($ch);
        if (($ret['info']['http_code'] == 200) && ($ret['info']['content_type'] == 'application/json')) {
            $ret = json_decode($ret['body'],true);
        }
        return $ret;

    }
    function callStat()
    {
        // https://api.plivo.com/v1/Account/{auth_id}/Call/

        // https://api.plivo.com/v1/Account/{auth_id}/Call/{call_uuid}/
    }

    /**
    */
    // https://api.plivo.com/v1/Account/{auth_id}/Call/?status=live

    // https://api.plivo.com/v1/Account/{auth_id}/Call/{call_uuid}/?status=live

    // https://api.plivo.com/v1/Account/{auth_id}/Call/{call_uuid}/
    /**
        List of Texts
        @param $l 20
        @param $o 0
        @return array of

    */
    function textList($l=20,$o=0)
    {
        $uri = self::_uri('Message');
        $arg = array(
            'limit' => $l,
            'offset' => $o,
        );
        $uri.= '?' . http_build_query($arg);
        $ch = self::_curl_init($uri);
        $ret = self::_curl_exec($ch);
        if (($ret['info']['http_code'] == 200) && ($ret['info']['content_type'] == 'application/json')) {
            $ret = json_decode($ret['body'],true);
        }
        return $ret;
    }

    /**
        Send a Text Message
    */
    function textSend($arg)
    {
        $uri = self::_uri('Message');
        $arg = json_encode($arg);
        $ch = self::_curl_init($uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($arg))
        );
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arg);
        $ret = self::_curl_exec($ch);
        if (($ret['info']['http_code'] == 200) && ($ret['info']['content_type'] == 'application/json')) {
            $ret = json_decode($ret['body'],true);
        }
        return $ret;
    }

    /**
        @param $apiep like /
    */
    private function _uri($apiep)
    {
        $uri = sprintf(self::URI_BASE,$this->_auth_id,$this->_auth_tk,$this->_auth_id);
        $uri = trim($uri,'/');
        $uri.= '/';
        $uri.= trim(trim($apiep,'/'));
        $uri.= '/'; // Needs Trailing Slash
        return $uri;
    }

    /**
        Executes the Single or Multiple Requests
        @param $uri URI to
        @return Curl Handle
    */
    private static function _curl_init($uri)
    {
        $ch = curl_init($uri);
        // Booleans
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
        Internal Curl Executor
        @param $ch Curl Handle
        @return array of body, info, fail
    */
    private static function _curl_exec($ch)
    {
        $r = array(
            'body' => curl_exec($ch),
            'info' => curl_getinfo($ch),
        );
        if ($x = curl_errno($ch)) {
            $r['fail'] = sprintf('%d:%s',$x,curl_error($ch));
        }
        return $r;
    }
}