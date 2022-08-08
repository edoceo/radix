<?php
/**
    Radix Telnet Toolkit

    @copyright Edoceo, Inc - 2010

    @note having issue with commands, perhaps our responses to DO/DONT are not functional?

    @see http://www.faqs.org/rfcs/rfc318.html
    @see http://www.faqs.org/rfcs/rfc764.html
    @see http://www.faqs.org/rfcs/rfc854.html

    @see http://cvs.adfinis.ch/co.php/phpStreamcast/telnet.class.php?Horde=c999ccb49c17f45e8dd6e86fd037cf7d&r=1.2
    @see http://bytes.com/topic/php/answers/511422-telnet-response-via-php-sockets
    @see http://px.sklar.com/code.html?id=634
    @see http://www.verticalevolution.com/blog/index.php?/pages/PHP-Client.html
    @see http://us3.php.net/manual/en/function.socket-connect.php#93310

*/

class Radix_Telnet
{
    private $_s; // Socket Connexion
    private $_dc1; // Device Control One
    private $_iac; // Interpret as Command byte
    private $_nul; // Null Byte

    /**
        Create an instance of a Telnet connection
        @param $host - hostname, IPv4 or IPv6 address
        @param $port - defaults to 23
    */
    function __construct($host,$port=23)
    {
        $this->_dc1 = chr(0x11);
        $this->_iac = chr(0xff);
        $this->_nul = chr(0x00);

        // $ecode = $etext = null;
        // $time = 10; // Timeout in Seconds
        // $this->_s = fsockopen($host,$port,$ecode,$etext,$time);
        // if (!$this->_s){
        //     // $this->error = "unable to open a telnet connection: " . socket_strerror($this->socket) . "\n";
        //     throw new Exception('Cannot Connect:' . $etext,$ecode);
        // }
        // socket_set_timeout($this->_s,10,0);

        $this->_s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        // // socket_set_nonblock($this->_s);
        // if (!preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host)) {
        //     $host = gethostbyname($host);
        // }
        if (!socket_connect($this->_s,$host,$port)) {
            $e = socket_last_error();
            throw new Exception(socket_strerror($e),$e);
        }
        // socket_set_nonblock($this->_s);
        // echo $this->recv(null);
        // echo "Connected!\n";
    }
    /**
        Recieve all the data in the buffer
        @return bytes
    */
    public function recv()
    {
        $DONT = chr(254);
        $DO = chr(253);
        $WONT = chr(252);
        $WILL = chr(251);

        $data = null; // Bytes Read
        $mode = 'read';
        $send = null; // Bytes to Send as Reply

        $r = array($this->_s);
        $w = null;
        $e = null;
        // switch (stream_select($r,$w,$e,0,500)) {
        switch ($s = socket_select($r,$w,$e,0,500)) {
        case 1:
            // Read
            socket_recv($this->_s, $data, 1024, 0);
        case 0:
            // Done
        default:
            die("Unknown $s");
        }



//        while ($byte = $this->_recv()) {
//            // echo '.';
//            switch ($byte) {
//            case $this->_dc1:
//                echo "dc1==ignore\n";
//                break;
//            case $this->_nul:
//                echo "nul==ignore\n";
//                break;
//            case $this->_iac:
//                // $mode = ($mode == 'read') ? 'ctrl' : 'read';
//                // echo "Switch Mode To: $mode\n";
//                continue 2;
//                break;
//            case $DO:
//            case $DONT:
//                // $mode = 'dont'
//                $send .= $this->_iac;
//                $send .= $WONT;
//                $send .= $this->_recv();
//                continue 2;
//                break;
//            case $WILL:
//            case $WONT:
//                echo "WILL/WONT\n";
//                $send .= $this->_iac;
//                $send .= $DONT;
//                $send .= $this->_recv();
//                continue 2;
//                break;
//            default:
//                // echo "Mode: $mode; Byte: 0x" . bin2hex($byte) . "\n";
//            }

            // It's a Telnet Command
            // if ($byte == $this->_iac) {
            //     // Read next byte and see what to do
            //     $byte = fgetc($this->_s);
            //     if ($byte != $this->_iac) {
            //         // If we get a DO or DONT
            //         if (($byte == $DO) || ($byte == $DONT)) {
            //             $opt = fgetc($this->_s);
            //             // $opt = socket_read($this->_s,1);
            //             echo "Other Side said DO/DONT: " . bin2hex($opt) . " so we WONT\n";
            //             $res = $this->_iac . $WONT . $opt;
            //             echo "Send: " . bin2hex($res) . "\n";
            //             fwrite($this->_s,$res);
            //             // socket_write($this->_s, $buf, 3);
            //         } else if (($byte == $WILL) || ($byte == $WONT)) {
            //             $opt = fgetc($this->_s);
            //             // $opt = socket_read($this->_s,1);
            //             echo "Other Side said WILL/WONT: ".bin2hex($opt)." so we DONT\n";
            //             $res = $this->_iac.$DONT.$opt;
            //             echo "Send: " . bin2hex($res) . "\n";
            //             fwrite($this->_s,$res);
            //             // socket_write($this->_s, $buf, 3);
            //         } else {
            //             die('Unknown 0x' . bin2hex($byte) . "\n");
            //         }
            //         continue;
            //     }
            // }
            // echo '+';
//            $data .= $byte;
//        }

        if (!empty($send)) {
            echo "To Send: " . bin2hex($send);
            fwrite($this->_s,$send);
        }

        // echo "Proper Return\n";
        // echo bin2hex($data) . "\n";
        return $data;
    }
    /**
        Send a bunch of data to the Telnet server
        @param $data is the bytes to send
        @return number of bytes written
    */
    public function send($data)
    {
        return fwrite($this->_s,$data,strlen($data));
        // return socket_write($this->_s,$data);
    }
    /**
        Get's one Byte
    */
    public function _recv()
    {
        // echo '.';
        $byte = false;
        // Use select to ensure we won't block
        $r = array($this->_s);
        $w = null;
        $e = null;
        // switch (stream_select($r,$w,$e,0,500)) {
        switch (socket_select($r,$w,$e,0,500)) {
        case 0:
            // Out of bytes to read
            $byte = false;
            break;
        case 1:
            // Read Socket
            $byte = fgetc($this->_s);

            // $byte = socket_read($this->_s,1); // Read one Byte
            // $byte = null;
            // $c = socket_recv($this->_s,$byte,1,MSG_DONTWAIT);
            // echo $c;
            // echo '0x' .  bin2hex($byte);
            // if ($c == 0) {
            //     return $buf;
            // }
            break;
        default:
            die('Unknown');
        }
        return $byte;
    }
}
