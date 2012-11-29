<?php
/**
    @file
    @brief Implements an IRC Connection

    @copyright 2004-2012 Edoceo, Inc.
    $Id$

    @see http://www.faqs.org/rfcs/rfc1459.html
    @see http://www.faqs.org/rfcs/rfc2812.html
*/

/**
    @brief the Radix IRC Client Class

    @todo make non-static only, instantate
    @todo one static, fast_send($host,$nick,$room,$text,$opts);
*/

class radix_irc
{
    const RPL_WELCOME   = '001'; // "Welcome to the Internet Relay Network <nick>!<user>@<host>"
    const RPL_YOURHOST  = '002'; // "Your host is <servername>, running version <ver>"
    const RPL_CREATED   = '003'; // "This server was created <date>"
    const RPL_MYINFO    = '004'; // "<servername> <version> <available user modes> <available channel modes>"
    const RPL_BOUNCE    = '005'; // "Try server <server name>, port <port number>"
    const RPL_ENDOFMOTD = '376'; // ":End of MOTD command"

    private $_irc; // Array of Handles

    // For the Static Stuff
    private static $_host;
    private static $_nick;
    private static $_room;
    private static $_yell; //!< Socket Handle of Yeller

    private $_msg_read_tick = 0; // Messages Read Count
    private $_msg_spin_tick = 0; // Messages Spin Read Count
    private $_hook_list = array();

    /**
        Joins a Specific Server & Channel
        @param $host to connect to (ssl:// to get a ssl or tls based host
        @param $nick name of the user
        @param $opts array(
            $port of server, default 6667
            // server = this server name
            // nick = your nick name
            // real = your real name, default nick
            // user = username, default nick
    */
    public function __construct($host,$nick,$opts=null)
    {
        if (empty($opts['port'])) {
            $opts['port'] = 6667;
        }
        if (empty($opts['real'])) {
            $opts['real'] = $nick;
        }
        if (empty($opts['user'])) {
            $opts['user'] = substr($nick,0,9);
        }
        $this->_irc = fsockopen($host, $opts['port']);

        // fputs(self::$_irc,sprintf("PASS %s\r\n",$arg['nick']));
        // $this->wait('255');
        if (!empty($opts['pass'])) $this->_send(sprintf('PASS %s',$opts['pass']));
        $this->_send(sprintf('NICK %s',$nick));
        // $this->_send(sprintf('USER %s %s %s :%s',$opts['user'],parse_url($host,PHP_URL_HOST),parse_url($host,PHP_URL_HOST),$opts['real']));
        $this->_send(sprintf('USER %s %s %s :%s',$opts['user'],$host,$host,$opts['real']));
        // $this->_send('USER '.$this->_username.' '.$usermode.' '.SMARTIRC_UNUSED.' :'.$this->_realname, SMARTIRC_CRITICAL);
    }

    /**
        My Destructor
    */
    function __destruct()
    {
        $this->quit();
    }

    /**
        Join a Specific Channel
    */
    function join($spec)
    {
        // $this->wait('/255/'); // Signals we're ready to issue JOINs
        $this->_send(sprintf('JOIN %s',$spec));
        // $this->wait('/353/');
        return $this->wait(); // Wait for next line
    }

    /**
        Quit the IRC Session
    */
    public function quit($text=null)
    {
        if (empty($this->_irc)) {
            return(0);
        }
        $cmd = 'QUIT';
        if (!empty($text)) $cmd = sprintf('QUIT :%s',$text);
        $this->_send($text);
        fclose($this->_irc);
        $this->_irc = null;

    }

    /**
        Spin Loop
    */
    public function spin($cnt=0)
    {
        while (!empty($this->_irc)) {
            while ($line = $this->_read()) {
                $this->_msg_spin_tick++;
                $this->_hook_proc($line);
                if ( ($cnt) && ($idx > $cnt) ) {
                    return;
                }
            }
        }
    }

    /**
        Add a Function to the Hook List
        @param $line pattern to match pre, cmd, arg
        @param $func the call back
    */
    public function hook($line,$func)
    {
        // echo "hook(\$opts,\$func)\n";
        $this->_hook_list[] = array(
            'line' => $line,
            'func' => $func,
        );
        // print_r($this->_hook_list);
    }

    /**
        Process the Recieved Messages agains the Hooks
        @param $line the parsed line we are parsing
        @return true|false
    */
    protected function _hook_proc($line)
    {
        // echo "_hook_proc(" . print_r($line,true) . ")\n";
        foreach ($this->_hook_list as $hook) {
            // print_r($hook);
            if ($hook['line']['cmd'] == $line['cmd']) {
                $x = call_user_func($hook['func'],$line);
                if ($x) {
                    print_r($line);
                    print_r($hook);
                    die("This userfunc Said True");
                }
                continue;
            }
        }
        // 
        // Default Handlers
        // foreach ($this->_hook_base as $hook) {
        // print_r($line);
        //     //     //case 'PING':
        //     //     //    $this->_send('PONG ' . $msg['arg']);
        //     //     //    continue;
        //     //     //}
    }

    /**
        Wait for a Specific IRC Condition

        @param $cmd is a regex match of any command that would trigger it
        @param $cmd or an array of regex to match in that order, all must be true for success
        @param $max is how many lines to wait for this truth
        @return true on success, false on failure
    */
    public function wait($pat=null,$max=100)
    {
        // echo "wait($pat,$max=100)\n";

        $cur = 0;
        // $pattern_count = count($pat);
        $line_list  = array();

        while ($line = $this->_read()) {
            if (empty($pat)) {
                return $line;
            }
            $line_list[] = $line;
            // echo "Match $pat, Line: {$line['cmd']}\n";
            // if (is_array($pat)) {
            //     // On Array Parameters we grab the first, if it matches
            //     // Then we increment a counter and shift this one off the front
            //     //if (count($pat) > 0) {
            //     //    $arg
            //     die("We don't handle array parameters yet :(\n");
            // } else {
                if (preg_match($pat,implode(' ',$line))) {
                    return $line_list;
                }
            // }
            if ($cur >= $max) {
                return false;
            }
        }
        return false;
    }

    /**
        Inititlaize Radix_IRC for Yelling
    */
    public static function init($host,$nick,$room)
    {
        if (is_object(self::$_yell)) {
            self::$_yell->quit();
            self::$_yell = null;
        }
        self::$_host = $host;
        self::$_nick = substr($nick,0,9);
        // Fixup $room
        if (substr($room,0,1) != '#') {
            $room = "#$room";
        }
        self::$_room = $room;
    }

    /**
        Quickly Send a Message to a Host and Channel
        @param $host hostname
        @param $nick your nick name
        @param $room the room to talk to
        @param $text message to send, does not need 'PRIVMSG #room' - automatically prefixes if necessary
          If it starts with PRIVMSG then we'll use as-is
    */
    public static function yell($text,$opts=null)
    {
        if (empty(self::$_yell)) {
            self::$_yell = new self(self::$_host,self::$_nick,$opts);
            self::$_yell->join(self::$_room);
        }
        // Fixup $text
        if (substr($text,0,7) != 'PRIVMSG') {
            $text = sprintf('PRIVMSG %s :%s',strtok(self::$_room,' '),$text);
        }
        self::$_yell->_send($text);
    }

    /**
        Read Message into Parts
        Waits for and Reads data from the Socket
        @return Parsed IRC Server Response
    */
    protected function _read()
    {
        if (feof($this->_irc)) {
            fclose($this->_irc);
            $this->_irc = null;
        }
        $ret = null;
        $buf = trim(fgets($this->_irc));
        // echo "_read({$this->_msg_read_tick}) = $buf\n";
        if (preg_match('/^:(\S+) (\w+|\d{3}) (.*)$/',$buf,$m)) {
            // Message with Prefix (common)
            $ret = array(
                'pre' => $m[1],
                'cmd' => $m[2],
                'arg' => $m[3]
            );
        } elseif (preg_match('/^(\w+) (:\S+)$/',$buf,$m)) {
            // ### xxxx
            $ret = array(
                'pre' => null,
                'cmd' => $m[1],
                'arg' => $m[2]
            );
        }
        // Match Pre?
        if (preg_match('/^(\S+)!(\S+)@(\S+)$/',$ret['pre'],$m)) {
            $ret['nick'] = $m[1];
            $ret['user'] = $m[2];
            $ret['host'] = $m[3];
        }
        // Handled Commands
        switch ($ret['cmd']) {
        // case 'JOIN':
        //     if (preg_match('/^:(\S+)$/',$ret['arg'],$m)) {
        //         $ret['room'] = $m[1];
        //     }
        //     break;
        case 'NOTICE':
        case 'PRIVMSG':
            if (preg_match('/^(\#\!\w+) :(.+)$/',$ret['arg'],$m)) {
                $ret['room'] = $m[1];
                $ret['text'] = $m[2];
            }
            break;
        }
        $this->_msg_read_tick++;
        // if (empty($ret['cmd'])) {
        //     die("Failed to Parse: $buf\n");
        // }
        return $ret;
    }

    /**
        Send a Message on the Socket
        @return void
    */
    protected function _send($msg)
    {
        $msg = trim($msg);
        // echo "_send($msg)\n";
        $msg.= "\r\n";
        fputs($this->_irc,$msg);
        // socket_write($this->_socket, $data.SMARTIRC_CRLF);
    }
}
