<?php
/**
    @file
    @brief API Interaction with Clickatell

    @author code@edoceo.com
    @copyright 2009 edoceo, inc
    @package radix
    @version $Id$

    @see https://www.clickatell.com/central/user/products/product_form.php

*/


class radix_api_clickatell
{
    const USER_AGENT = 'Edoceo Radix Clickatell API v2011.23';

    protected $_a; // AppIDKey
    protected $_u; // Username
    protected $_p; // Password
    protected $_s; // Session ID

    /**
        @param $a app key
        @param $u username
        @param $p password
    */
    function __construct($a,$u,$p)
    {
        $this->_a = $a;
        $this->_u = $u;
        $this->_p = $p;
    }
    /**
        @param $dial the number
        @param $text the text to send
    */
    function sms($dial,$text)
    {
        $this->_auth();
        $dial = preg_replace('/[^\d]+/',null,$dial);
        $text = trim(substr($text,0,140));
        return $this->_sendmsg($dial,$text);
    }
    /**
        Authenticate the Session
    */
    protected function _auth()
    {
        if (empty($this->_s)) {
            $uri = 'http://api.clickatell.com/http/auth?';
            $uri.= http_build_query(array(
                'user' => $this->_u,
                'password' => $this->_p,
                'api_id' => $this->_a,
            ));
            $buf = $this->_curl_exec($uri);
            if (preg_match('/^OK: ([0-9a-f]{32})$/',$buf['body'],$m)) {
                $this->_s = $m[1];
            }
        }
        return $this->_s;
    }
    /**
        SendMsg API Call
    */
    protected function _sendmsg($n,$t)
    {
        $uri = 'http://api.clickatell.com/http/sendmsg?';
        $uri.= http_build_query(array(
            'session_id' => $this->_s,
            'to' => $n,
            'text' => $t,
        ));
        $buf = $this->_curl_exec($uri);
        print_r($buf);

        $ret = array();
        if (preg_match('/^OK: ([0-9a-f]{32})$/',$buf['body'],$m)) {

        } elseif (preg_match('/^ERR: (\d+), (.+)$/',$buf['body'],$m)) {
            $ret = array(
                'code' => $m[1],
                'text' => $m[2],
            );
        }
        return $ret;
    }
    /**
        Execute a CURL
    */
    protected function _curl_exec($uri)
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
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);

        $ret = array(
            'body' => curl_exec($ch),
            'fail' => sprintf('%d:%s',curl_errno($ch),curl_error($ch)),
            'info' => curl_getinfo($ch),
        );

        return $ret;

    }

}