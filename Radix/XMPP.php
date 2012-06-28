<?php
/**
    @file
    @brief Contains the XMPP Client Library Code
    $Id: XMPP.php 2016 2012-02-19 22:04:05Z code@edoceo.com $

    @author http://edoceo.com/
    @copyright 2009 edoceo, inc.
    @package Radix

    @see http://xmpp.org/rfcs/rfc6120.html
    
*/

class Radix_XMPP
{
    private $_s; // Socket

    private $_xml_parser;
    private $_xml_path; // Array of Element Names

    private $_hostname; //  = 'tcp://talk.google.com:5222'; ;
    private $_username; //  = 'larry.page@google.com';
    private $_password; //  = 'I-track-users';
    private $_resource = 'Radix_XMPP';

    private $_tasklist; // List of things to do after processing the XML
    private $_xmppbind; // Bound Session ID
    private $_xmpphost; //  XMPP Host (aka Realm), (to,from)
    private $_xmppinfo = null; // Data Array about Stream
    private $_xmpprecv; // Recieved Data Array

    /**
    */
    function __construct($arg=null)
    {
        $this->_task_list = array();
        $this->_xml_path = array();

        if (!empty($arg['hostname'])) {
            $this->_hostname = $arg['hostname'];
        }

        if (!empty($arg['username'])) {
            $this->_username = $arg['username'];
        }

        if (!empty($arg['password'])) {
            $this->_password = $arg['password'];
        }

        if (!empty($arg['xmpphost'])) {
            $this->_xmpphost = $arg['xmpphost'];
        } else {
            // Parse xmpphost from username
            if (preg_match('/@(.+)$/',$this->_username,$m)) {
                $this->_xmpphost = $m[1];
            }
        }

        $this->_connect();

    }

    /**
        Authenticate on the Stream
    */
    function auth()
    {
        $ret = false;
        $auth = base64_encode("\x00" . $this->_username . "\x00" . $this->_password);
        $this->_send('<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="PLAIN">' . $auth  . '</auth>');
        $res = $this->_recv();

        if (!empty($res['STREAM:STREAM/SUCCESS'])) {
            $this->_xmppauth = true;
            $this->_xmppinfo['features']['auth'] = true;

            // Reset Stream
            $this->_xmppInit();
            // Asked to Bind?
            //print_r($this->_xmpprecv);
            if ($this->_xmpprecv['STREAM:STREAM/IQ']['attr']['TYPE'] == 'result') {
                if (!empty($this->_xmpprecv['STREAM:STREAM/IQ/BIND/JID']['text'])) {
                    $this->_xmppbind = $this->_xmpprecv['STREAM:STREAM/IQ/BIND/JID']['text'];
                }
            }
            if ($this->_xmppbind) {
                $this->_send('<iq xmlns="jabber:client" type="set" id="2"><session xmlns="urn:ietf:params:xml:ns:xmpp-session" /></iq>');
                $x = $this->_recv();
                // print_r($x);
            }
            // <iq xmlns='jabber:client' type='set' id='2'><session xmlns='urn:ietf:params:xml:ns:xmpp-session' /></iq>
            $ret = true;
        }

        return $ret;
    }

    /**
        Set an XMPP Presence
    */
    public function presence()
    {
        //     if($to) $out .= " to=\"$to\"";
        //     if($type) $out .= " type='$type'";
        //     if($show == 'available' and !$status) {
        //         $out .= "/>";
        //     } else {
        //         $out .= ">";
        //         if($show != 'available') $out .= "<show>$show</show>";
        //         if($status) $out .= "<status>$status</status>";
        //         if($priority) $out .= "<priority>$priority</priority>";
        //         $out .= "</presence>";
        //     }
        $this->_send('<presence type="available" />');

    }

    /**
        Send an XMPP Message
    */
    public function message($rcpt,$body)
    {
        $this->_send('<message from="' . $this->_xmppbind . '" to="' . $rcpt . '" type="chat"><body>' . htmlspecialchars($body,ENT_QUOTES,'UTF-8',false) . '</body></message>');
    }

    /**
        Shortcut to Send Quick Messages
        @param $host the XMPP Host to Connect to
        @param $user the username
        @param $pass the password
        @param $rcpt who to send to
        @param $body the message
        @return true
    */
    public static function sendMessage($host,$user,$pass,$rcpt,$body)
    {
        $arg = array(
            'hostname' => $host,
            'username' => $user,
            'password' => $pass,
        );

        $xmpp = new self($arg);
        $xmpp->auth();
        // $xmpp->presence('<presence type=\"available\" />');
        $xmpp->message($rcpt,$body);
    }

    /**
        Connect to Serrver
    */
    private function _connect()
    {
        $eno = null;
        $esz = null;
        $this->_s = stream_socket_client($this->_hostname, $eno, $esz); // , $timeout, $conflag);
        stream_set_blocking($this->_s, true);
        $this->_xmppInit();
    }

    /**
        Send the Buffer on the Wire
    */
    private function _send($buf)
    {
        // echo "_send($buf)\n";
        $ret = fwrite($this->_s, $buf);
        return $ret;
    }

    /**
        XMPP works on Events, So Wait for the one you like...
    */
    private function _recv()
    {
        // echo "_recv() ";
        $this->_xmpprecv = array();
        // Error Check
        if (!is_resource($this->_s)) {
            // echo " = false\n";
            return false;
        }
        if (feof($this->_s)) {
            // echo " = EOF\n";
            return false;
        }

        // Read Socket into $xml
        $xml = null;
        do {
            $r = array($this->_s);
            $w = null;
            $e = null;
            $c = stream_select($r,$w,$e,1);
            // If our one socket is ready
            if ($c == 1) {
                // Stream Select may think something is there, but then fgets fails WTF?
                $buf = fread($this->_s,1024);
                if ($buf === false) {
                    $c = false;
                    break;
                }
                // echo "_recv($buf)\n";
                // Parse out SMTP Response Lines
                $xml.= $buf;
            }
        } while ($c != false);
        // echo " = '$xml'\n";

        // Parse what should be XML
        $r = xml_parse($this->_xml_parser, $xml, false);
        if ($r == 0) {
            $this->_xmpprecv = null;
            die(xml_error_string(xml_get_error_code($this->_xml_parser)));
        }

        // Return the array the parser made
        return $this->_xmpprecv;
    }

    /**
        Wraps Send and Recv & Automatically Handle
    */
    private function _sendrecv($s)
    {
        // echo "function _sendrecv()\n";
        $this->_send($s);
        $msg = $this->_recv();
        // print_r($msg);

        // Tasks to Do?
        while ($t = array_shift($this->_task_list)) {
            // echo "task:$t\n";
            switch ($t) {
            case 'feature_bind':
                $this->_sendrecv('<iq xmlns="jabber:client" type="set" id="1"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"><resource>' . $this->_resource . '</resource></bind></iq>');
                break;
            // case 'sasl_success':
            //     $this->_xmppInit();
            //     break;
            case 'tls_start':
                // Tell them I'm ready to Start TLS
                $this->_sendrecv('<starttls xmlns="urn:ietf:params:xml:ns:xmpp-tls"><required /></starttls>');
                break;
            case 'tls_enable':
                // Actually Start TLS
                stream_socket_enable_crypto($this->_s, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT);
                $this->_xmppInit();
                break;
            default:
                die($t);
            }
        }
        // Return Array of XML Built?
        return $msg;
    }
    /**
        Initialize the XML Parser & XMPP Stream
    */
    private function _xmppInit()
    {
        // echo "function _xmppInit()\n";

        // Initialize the XML Parser
        $this->_xml_path = array();
		$this->_xml_parser = xml_parser_create('UTF-8');
		xml_parser_set_option($this->_xml_parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parser_set_option($this->_xml_parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
		xml_set_object($this->_xml_parser, $this);
		// xml_set_start_namespace_decl_handler ($this->_xml_parser , '_xmlNS' );
		xml_set_element_handler($this->_xml_parser, '_xmlAlpha', '_xmlOmega');
		xml_set_character_data_handler($this->_xml_parser, '_xmlText');

		// Init the XMPP Stream
        $this->_sendrecv('<stream:stream to="' . $this->_xmpphost . '" xmlns:stream="http://etherx.jabber.org/streams" xmlns="jabber:client" version="1.0">');
    }

    // /**
    //     XML NS Parser
    // */
    // private function _xmlNS($xml,$ns,$uri)
    // {
    //     // echo "function _xmlNS(\$xml,$ns,$uri)\n";
    //     return true;
    // }

    /**
        XML Start Tag Parser
    */
    private function _xmlAlpha($xml,$name,$attr)
    {
        array_push($this->_xml_path,$name);
        // echo "_xmlAlpha(\$xml,$name,\$attr)\n";
        $path = implode('/',$this->_xml_path);
        $this->_xmpprecv[$path] = array('attr'=>$attr);
        // echo ">$path\n";

        // Should be Done in _xmlOmega?
        switch ($path) {
        //case 'STREAM:STREAM':
        case 'STREAM:STREAM/PROCEED':
            if ($attr['XMLNS'] == 'urn:ietf:params:xml:ns:xmpp-tls') {
                array_push($this->_task_list,'tls_enable');
            }
            break;
        case 'STREAM:FEATURES':
            $this->_xmppmode = $name;
            $this->_xmppinfo['features'] = array();
            break;
        case 'STREAM:STREAM/STREAM:FEATURES/BIND':
            // Bind to Resource
            $this->_xmppinfo['features']['bind'] = false;
            array_push($this->_task_list,'feature_bind');
            break;
        case 'STREAM:STREAM/STREAM:FEATURES/STARTTLS/REQUIRED':
            $this->_xmppinfo['features']['starttls'] = true;
            array_push($this->_task_list,'tls_start');
            break;
        case 'STREAM:STREAM/STREAM:FEATURES/MECHANISMS/MECHANISM':
            // Some Other Required Somehow
            break;
        }
    }

    /**
        XML Text Processor
    */
    private function _xmlText($xml,$text)
    {
        // echo "_xmlText(\$xml,$text)\n";
        $text = trim($text);
        if (!empty($text)) {
            $path = implode('/',$this->_xml_path);
            $this->_xmpprecv[$path]['text'] = $text;
            // echo "_xmlText(\$xml,$text)\n";
        }
        return true;
    }

    /**
        XML End Tag
    */
    private function _xmlOmega($xml,$name)
    {
        // echo "_xmlOmega(\$xml,$name)\n";
        // echo '<', implode('/',$this->_xml_path), "\n";
        array_pop($this->_xml_path);
    }
}

