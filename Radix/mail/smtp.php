<?php
/**
    @file
    @brief Provides an SMTP Interface to a specific server
    @version $Id$

    @see http://www.dcsc.utfsm.cl/redes/webhosting/class.smtp.php.txt
    @see http://www.linuxscope.net/articles/mailAttachmentsPHP.html
    @see http://cr.yp.to/smtp/ehlo.html
    @see http://www.ietf.org/rfc/rfc2821.txt
    @see http://www.ietf.org/rfc/rfc2554.txt
    @see http://www.dcsc.utfsm.cl/redes/webhosting/class.smtp.php.txt

    @todo Use same Streaming Logic in SMTP, IRC, FreeSWTICH - but not! Shared Socket

*/

class radix_mail_smtp
{
    const AUTH_READY = 334; // See rfc2554
    const MAIL_ADDR = '/([\w\.\%\+\-]+@[a-z0-9\.\-]+\.[a-z]{2,6})/i';
    private $_s;
    private $_ext; // Array of Extensions

    /**
        @param $host hostname or tcp://host:25, ssl://hostname:465 or tls://hostname:587
    */
    function __construct($host)
    {
        $eno = null;
        $esz = null;
        $this->_s = stream_socket_client($host, $eno, $esz); // , $timeout, $conflag);
        if (empty($this->_s)) {
            // echo "$eno:$esz\n";
        }
        // Non Blocking IO
        stream_set_blocking($this->_s, false);
    }
    /**
        Sends EHLO
        @param hostname, auto-detected
        @return array of response line data
    */
    function ehlo($hostname=null)
    {
        if (empty($hostname)) {
            // @note may not be fqdn
            // Over 5.3 has gethostname(void)
            $hostname = php_uname('n');
        }
        $this->_send("EHLO $hostname\r\n");
        $res = $this->_recv();
        // These Responses Will Show Extensions so record them
        // foreach ($res as $buf) {
        //     $cap = explode(' ',$buf['text']);
        //     // $this->_ext
        // }
        return $res;
    }
    /**
        Uses AUTH LOGIN to authenticate
        @return array of response line data
    */
    function auth($username,$password)
    {
        $this->_send("AUTH LOGIN\r\n");
        $res = $this->_recv();
        // print_r($res);
        if ($res[0]['code'] != self::AUTH_READY) {
            return $res;
        }

        $this->_send(base64_encode($username) . "\r\n");
        $res = $this->_recv();
        // print_r($res);
        if ($res[0]['code'] != self::AUTH_READY) {
            return $res;
        }

        $this->_send(base64_encode($password) . "\r\n");
        $res = $this->_recv();
        if ($res[0]['code'] != self::AUTH_READY) {
            return $res;
        }
        return $res;

    }
    /**
        @param $e email address, hopefully properly formatted for SMTP
        @return array of response line data
    */
    function mailFrom($e)
    {
        // try to parse email from string
        if (preg_match(self::MAIL_ADDR,trim($e),$m)) {
            $e = $m[1];
        }
        $this->_send("MAIL FROM:<$e>\r\n");
        $res = $this->_recv();
        return $res;
    }
    /**
        @param $e email address, hopefully properly formatted for SMTP
        @return array of response line data
    */
    function rcptTo($e)
    {
        // try to parse email from string
        if (preg_match(self::MAIL_ADDR,trim($e),$m)) {
            $e = $m[1];
        }
        $this->_send("RCPT TO:<$e>\r\n");
        $res = $this->_recv();
        return $res;
    }
    /**
        @param $d string data which is hopefully properly formatted for SMTP
        @return array of response line data

        @todo normalise newlines in $d
        @todo ensure that $d has no lines longer than 1000 (see rfc821)
        @todo ensure that no lines start with "."
    */
    function data($d)
    {
        $ret = array();

        // Init Data
        $this->_send("DATA\r\n");
        if ($res = $this->_recv()) {
            $ret = array_merge($ret,$res);
        }

        // Send Data
        $this->_send($d);
        if ($res = $this->_recv()) {
            $ret = array_merge($ret,$res);
        }

        // Terminate Data
        $this->_send("\r\n.\r\n");
        if ($res = $this->_recv()) {
            $ret = array_merge($ret,$res);
        }

        return $ret;
    }
    /**
        Terminate the connection
        @return true
    */
    function quit()
    {
        $this->_send("QUIT\r\n");
        $ret = $this->_recv();
        stream_socket_shutdown($this->_s,STREAM_SHUT_RDWR);
        $this->_s = null;
        return $ret;
    }
    /**
        Send data to Server
        @return bytes written
    */
    private function _send($data)
    {
        //echo "_send($data)\n";
        if (empty($this->_s)) {
            error_log("SMTP Shutdown $data?");
        }
        
        $ret = fwrite($this->_s,$data);
        return $ret;
    }
    /**
        Read Data
        @return false | data array
    */
    private function _recv()
    {
        // Error Check
        if (!is_resource($this->_s)) {
            return false;
        }
        if (feof($this->_s)) {
            return false;
        }

        $ret = array();

        do {
            $r = array($this->_s);
            $w = null;
            $e = null;
            $c = stream_select($r,$w,$e,1);
            // If our one socket is ready
            if ($c == 1) {
                // Stream Select may think something is there, but then fgets fails WTF?
                $buf = fgets($this->_s,1024);
                if ($buf === false) {
                    $c = false;
                    break;
                }
                // echo "_recv($buf)\n";
                // Parse out SMTP Response Lines
                if (preg_match('/^(\d{3})([ \-])(.+)/',$buf,$m)) {
                    $ret[] = array(
                        'code' => $m[1],
                        'text' => $m[3],
                    );
                }
            }
        } while ($c != false);
        // echo "_recv()=$ret\n";
        return $ret;
    }
    /**
        Does an MX Lookup for the Given Email Address
        @param $e email address
        @return array of sorted MX records, false on failure
    */
    static function getMX($e)
    {
        if (preg_match('/([\w\.\%\+\-]+)@([a-z0-9\.\-]+\.[a-z]{2,6})/i',trim($e),$m)) {
            // More strict is to only check MX, we do both in example
            // if ( (checkdnsrr($m[2],'MX') == true) || (checkdnsrr($m[2],'A') == true) ) {
            //     return sprintf('%s@%s',$m[1],$m[2]);
            // }
            $host = null;
            $sort = null;
            if (getmxrr($m[2],$host,$sort)) {
                // $ret = array();
                $c = count($sort);
                for ($i=0;$i<$c;$i++) {
                    $ret[ $host[$i] ] = $sort[$i];
                }
                asort($ret);
                return $ret;
            }
        }
        return false;
    }
    /**
        Send using our Mail Server
    */
    static function send($host,$from,$rcpt,$mail)
    {
        $ret = array();

        if (is_string($host)) {
            // @todo parse as URI?
            $host['hostname'] = $host;
        }

        $smtp = new self($host['hostname']);
        if ($res = $smtp->ehlo()) {
            $ret = array_merge($ret,$res);
        }
        if (!empty($host['username'])) {
            if ($res = $smtp->auth($host['username'],$host['password'])) {
                $ret = array_merge($ret,$res);
            }
        }
        if ($res = $smtp->mailFrom($from)) {
            $ret = array_merge($ret,$res);
        }
        if ($res = $smtp->rcptTo($rcpt)) {
            $ret = array_merge($ret,$res);
        }
        if ($res = $smtp->data($mail)) {
            $ret = array_merge($ret,$res);
        }
        if ($res = $smtp->quit()) {
            $ret = array_merge($ret,$res);
        }

        return $ret;
    }
    /**
        Sends directly to the Recipient MX, using $rctp MX for their domain
        @param $rcpt = Recipeint
        @param $from Sender (MAIL FROM)
        @param $mail the Message Body
    */
    static function sendMX($from,$rcpt,$mail)
    {
        $ret = array();
        $res = self::getMX($rcpt);
        $mxs = array_keys($res);
        $sent = false;
        while ($sent == false) {
            // foreach ($res as $host) {
            $host = array_shift($mxs);
            $smtp = new self("tcp://$host:25");
            if ($res = $smtp->ehlo()) {
                $ret = array_merge($ret,$res);
            }
            if ($res = $smtp->mailFrom($from)) {
                $ret = array_merge($ret,$res);
            }
            if ($res = $smtp->rcptTo($rcpt)) {
                $ret = array_merge($ret,$res);
            }
            if ($res = $smtp->data($mail)) {
                $ret = array_merge($ret,$res);
            }
            if ($res = $smtp->quit()) {
                $ret = array_merge($ret,$res);
            }
            $sent = true;
        }
        return $ret;
    }
}
