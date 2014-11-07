<?php
/**
    @file
    @brief Wraps the Native PHP IMAP functions

    @package radix
*/


class radix_mail_imap
{
    private $_c; // Connection Handle
    private $_c_host; // Server Part {}
    private $_c_list; // List of Mailboxes
    private $_uri;

    private $_folder_list;
    private $_folder_name;
    private $_folder_stat;

    const E_NO_MAILBOX = -404;

    /**
    */
    function __construct($uri)
    {
        $this->_uri = parse_url($uri);
        $this->_init($this->_uri);
    }
    /**
    */
    private function _init($uri)
    {
        $this->_c = null;
        if (empty($uri['host'])) {
            $uri['host'] = 'localhost';
        }
        $this->_c_uri = $uri;
        $this->_c_host = sprintf('{%s',$uri['host']);
        if (!empty($uri['port'])) {
            $this->_c_host.= sprintf(':%d',$uri['port']);
        }
        switch (strtolower(@$uri['scheme'])) {
        case 'imap-ssl':
            $this->_c_host.= '/ssl/novalidate-cert';
            break;
        case 'imap-tls':
            $this->_c_host.= '/tls/novalidate-cert';
            break;
        case 'imap':
            $this->_c_host.= '/notls';
        default:
        }
        //$this->_c_host.= '/debug';
        $this->_c_host.= '}';

        $c_str = $this->_c_host;
        // Append Path?
        if (!empty($uri['path'])) {
            $x = ltrim($uri['path'],'/');
            if (!empty($x)) {
                $c_str.= $x;
            } else {
                //$c_str.= 'INBOX';
            }
        }
        // echo "Connect: imap_open($c_str,{$uri['user']},{$uri['pass']},OP_HALFOPEN|OP_DEBUG,1);\n";
        $this->_c = imap_open($c_str,$uri['user'],$uri['pass'],OP_HALFOPEN|OP_DEBUG,1);
        // if ($this->_c) {
        //     // $this->_c_stat = imap_mailboxmsginfo($this->_c);
        //     // $this->_c_list = imap_getmailboxes($this->_c, $this->_c_host, '*');
        // }
        
    }

    function loadHeaders($i)
    {
        return imap_headerinfo($this->_c,$i,1024,1024);
    }

    /**
    */
    function loadMessage($m)
    {
        $i = intval($m->Msgno);
        $b = imap_fetchbody($this->_c,$i,null,FT_PEEK);
        $x = $this->stat();
        if (preg_match('/Could not parse command/',$x)) {
            return null;
        }
        if (preg_match('/connection broken/',$x)) {
            imap_close($this->_c);
            $this->_init($this->_uri);
        }
        if (!empty($x)) {
            echo "\nimap_fetchbody(" . print_r($m,true) . "=>$i): $x\n";
        }
        return $b;
    }

    /**
        @param $pat '*' for all folders, '%' for current folder and below
        @return array of folder names
    */
    function listFolders($pat='*')
    {
		// $this->_open();
		$ret = array();
		$list = imap_getmailboxes($this->_c, $this->_c_host, $pat);
		foreach ($list as $x) {
			$ret[] = array(
				'name' => $x->name,
				'attribute' => $x->attributes,
				'delimiter' => $x->delimiter,
			);
		}
		return $ret;
    }

    /**
    */
    function listMessage()
    {

    }
    /**
    */
    function makeFolder($f)
    {
        if (is_object($f)) {
            $f = self::folderName($f->name);
        }
        $name = sprintf('%s%s',$this->_c_host,$f);
        //echo "New Name: $name\n";
        @imap_createmailbox($this->_c,$name);
        return $this->stat();
    }
    /**
    */
    function nextMessage()
    {
        $r = null;
        if ( ($this->_message_max) && ($this->_message_cur < $this->_message_max) ) {
            //$x = imap_fetch_overview($this->_c,$this->_message_cur);
            //if ( (is_array($x)) && (count($x) == 1) ) {
            //    $r['stat'] = $x[0];
            //}
            //$r['info'] = imap_headerinfo($this->_c,$this->_message_cur,1024,1024);
            $r = imap_headerinfo($this->_c,$this->_message_cur,1024,1024);
        }
        if ($this->_message_cur < $this->_message_max) {
            $this->_message_cur++;
        }
        return $r;
    }

    /**
    */
    function openFolder($f,$stat='count')
    {
        if (is_object($f)) {
            //$x = $f;
            //$f = new stdClass();
            //$f->name = $this->_c_host . $x;
            $f = $this->_c_host . self::folderName($f->name);
        }

        // prepend host if not found
        if (strpos($f,$this->_c_host) === false) {
            $f = $this->_c_host . self::folderName($f);
        }

        echo "\nopenFolder($f,\$stat=$stat)\n";
        // Reset
        $this->_folder_stat = null;
        $this->_message_cur = 1;
        $this->_message_max = 0;
        // Open
        imap_check($this->_c);
        imap_reopen($this->_c,$f,0,1);
        //$this->_c_stat = imap_status($this->_c);
        $x = $this->stat();
        if (empty($x)) {
            $this->_folder_name = $f;
            $this->_message_cur = 1;
            switch ($stat) {
            case 'count':
                $this->_message_max = imap_num_msg($this->_c);
                return $this->_message_max;
            case 'stat':
                $this->_folder_stat = imap_status($this->_c,$f,SA_ALL);
                print_r($this->_folder_stat);
                die('stat');
                return $this->_folder_stat;
            case 'info':
                $this->_folder_stat = imap_mailboxmsginfo($this->_c);
                $this->_message_max = $this->_folder_stat->Nmsgs;
            }
            return $this->_folder_stat;

            $x0 = self::folderName($f);
            $x1 = self::folderName($this->_folder_stat->Mailbox);
            if ($x0 == $x1) {
                return $this->_folder_stat;
            }
        } else {
            if (preg_match('/no such mailbox/',$x)) {
                return self::E_NO_MAILBOX;
            }
            if (preg_match('/Unknown Mailbox/',$x)) {
                return self::E_NO_MAILBOX;
            }
            die("\nimap_reopen($this->_c_host,$f) failed: $x\n");
        }
    }
    /**
    */
    function putMessage($mail,$flag=null,$date=null)
    {
        if ( (empty($date)) && (preg_match('/Date: (.+)$/m',$m,$x)) ) {
            $date = strftime('%d-%b-%Y %H:%M:%S %z',strtotime($x[1]));
        }
        //echo "putMessage(\$mail,$flag,$date)\n";
        imap_append($this->_c,$this->_folder_name,$mail,$flag,$date);
        $x = $this->stat();
        if (!empty($x)) {
            print_r($x);
            return false;
        }
        return true;
    }
    /**
        Get Status Messages
    */
    function stat()
    {
        $r = null;

        if ($this->_c) imap_check($this->_c);

        $x = imap_alerts();
        if (!empty($x)) {
            $r.= "Alerts:\n  " . implode('; ',$x) . "\n";
        }
        $x = imap_errors();
        if (!empty($x)) {
            $r.= "Errors:\n  " . implode('; ',$x) . "\n";
        }
        return $r;
    }
    /**
    */
    function wipeMessage($x)
    {
        imap_delete($this->_c,intval($x));
        imap_expunge($this->_c);
    }
    /**
        Returns the Base Folder Name, w/o the Server part
    */
    static function folderName($f)
    {
        if (is_object($f)) {
            $f = $f->name;
        }
        // Remove the HOST spec in some names
        if (preg_match('/}(.+)$/',$f,$x)) {
            $f = trim($x[1]);
        }
        return $f;
    }
}