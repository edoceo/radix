<?php
/**
    @file
    @brief Plivo API Interface

    @see http://www.plivo.com/docs/api
*/

class radix_api_plivo
{
    const URI_BASE = 'https://%s:%s@api.plivo.com/v1/Account/%s/';
    const UA = 'Radix Plivo API v2013.25';

    private static $__init = false;
    private static $__user;
    private static $__auth;

    private $_auth_id; // Auth ID
    private $_auth_tk; // Auth Token

    /**
        Init the Static World
        @param $u Twilio Account SID
        @param $a Twilio Auth Token
    */
    public static function init($u,$a)
    {
        self::$__user = $u;
        self::$__auth = $a;
        $b = sprintf(self::URI_BASE ,$u,$a,$u);
        if (strlen($b) > strlen(self::URI_BASE)) {
            self::$__init = true;
        }
    }

    /**
        @param $u AUTH ID
        @param $a AUTH TOKEN
    */
    public function __construct($u=null,$a=null)
    {
        if (null===$u && null===$a && self::$__init) {
            $u = self::$__user;
            $a = self::$__auth;
        }
        $this->_auth_id = $u;
        $this->_auth_tk = $a;
    }
    
    /**
        @param $cmd Command
        @param $arg Arguments for POST
        @return Data Array
    */
    function api($cmd,$arg=null)
    {
        $cmd = trim(trim($cmd,'/'));
        $uri = $this->_uri('/' . $cmd . '/');
        if (!empty($arg)) {
            $uri.= '?' . http_build_query($arg);
        }
        $ch = self::_curl_init($uri);
        $ret = self::_curl_exec($ch);
        if (($ret['info']['http_code'] == 200) && ($ret['info']['content_type'] == 'application/json')) {
            $ret = json_decode($ret['body'],true);
        }
        return $ret;
    }

    /**
        Checks it
    */
    function auth()
    {
        $uri = $this->_uri('');
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
        List of the Phaxio Numbers
    */
    public function listNumbers()
    {
        return $this->api('Number');
    }


    /**
        @param $arg array fr, to, answer_url
    */
    function callInit($arg)
    {
        // https://api.plivo.com/v1/Account/{auth_id}/Call/
        $uri = $this->_uri('Call');
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
        @param $page
        @param $size = 20
    */
    function listCalls($page=0,$size=20)
    {
        return $this->api('Call',array(
            'offset' => 0,
            'limit' => $size,
        ));
    }

    function callStat()
    {
        // https://api.plivo.com/v1/Account/{auth_id}/Call/
        // https://api.plivo.com/v1/Account/{auth_id}/Call/{call_uuid}/
        // https://api.plivo.com/v1/Account/{auth_id}/Call/?status=live
        // https://api.plivo.com/v1/Account/{auth_id}/Call/{call_uuid}/?status=live
        // https://api.plivo.com/v1/Account/{auth_id}/Call/{call_uuid}/
    }

    /**
        List of Texts, Oldest First
        @param $o 0
        @param $l 20
        @return array of

    */
    function textList($o=0,$l=20)
    {
        $uri = $this->_uri('Message');
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
        Read a Message by UUID
        @return Text Object
    */
    function textRead($id)
    {
        $uri = self::_uri('Message/' . $id);
        $ch = self::_curl_init($uri);
        $ret = self::_curl_exec($ch);
        radix::dump($ret);
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
        $uri = $this->_uri('Message');
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
        @param $apiep like Number or
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