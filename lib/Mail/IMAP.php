<?php
/**
	@file
	@brief Wraps the Native PHP IMAP functions

	@package radix
*/

namespace Edoceo\Radix\Mail;


class IMAP
{
	private $_c; // Connection Handle
	private $_c_host; // Server Part {}
	private $_c_list; // List of Mailboxes
	private $_uri;

	private $_folder_list;
	private $_folder_name;
	private $_folder_stat;

	const E_NO_MAILBOX = -404;

	const TRY_OPEN = 2;

	/**
		@param URI to Mailbox
	*/
	function __construct($uri)
	{
		$this->_uri = parse_url($uri);
		$this->_init($this->_uri);
	}

	/**
		@param $uri as array to mailbox imap|imap-ssl|imap-tls|pop3|pop3-ssl|pop3-tls://host:port/INBOX
	*/
	private function _init($uri)
	{
		$this->_c = null;

		if (empty($uri['host'])) {
			$uri['host'] = 'localhost';
		}

		$host = $uri['host'];
		$port = null;
		$flag = array();

		// Connection Type
		switch (strtolower($uri['scheme'])) {
		case 'ssl':
		case 'imap-ssl':
			$flag[] = 'imap';
			$flag[] = 'ssl';
			$flag[] = 'novalidate-cert';
			$port = '993';
			break;
		case 'tls':
		case 'imap-tls':
			$flag[] = 'imap';
			$flag[] = 'tls';
			$port = '993';
			break;
		case 'imap':
		case 'tcp':
			$flag[] = 'imap';
			$flag[] = 'notls';
			$port = '143';
			break;
		case 'pop3':
			$flag[] = 'pop3';
			$port = '110';
			break;
		case 'pop3-ssl':
			$flag[] = 'pop3';
			$flag[] = 'ssl';
			$port = '995';
			break;
		default:
			throw new \Exception("Invalid Mail Scheme: {$uri['scheme']}");
		}

		// Override Default
		if (!empty($uri['port'])) {
			$port = $uri['port'];
		}

		$this->_c_host = sprintf('{%s:%d/%s}', $host, $port, implode('/', $flag));

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

		//var_dump($c_str);
		//var_dump($uri);

		$this->_c = imap_open($c_str, $uri['user'], $uri['pass'], OP_HALFOPEN, self::TRY_OPEN);
		$err =  imap_last_error();
		// var_dump($err);
		// var_dump(imap_alerts());
		// var_dump(imap_errors());

		// exit;

		// if ($this->_c) {
		//	 // $this->_c_stat = imap_mailboxmsginfo($this->_c);
		//	 // $this->_c_list = imap_getmailboxes($this->_c, $this->_c_host, '*');
		// }

	}

	/**
		Immediately Delete and Expunge the message
	*/
	function mailDelete($m, $flush=false)
	{

		$x = imap_delete($this->_c, $m, FT_UID);

		if ($flush) {
			imap_expunge($this->_c);
		}

		return $x;

	}

	/**
		Loads message by Message ID
	*/
	function mailGet($m, $part='1')
	{
		$i = intval($m);
		$b = imap_fetchbody($this->_c, $i, $part, FT_INTERNAL|FT_PEEK|FT_UID);
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

	function mailGetPart($i, $part='1', $file=null)
	{
		$this->_open();
		return imap_savebody($this->_c, $file ,$mnum , $part, FT_INTERNAL|FT_PEEK);
	}

	/**
		Fetch Message Headers
	*/
	function mailHead($i)
	{
		// $t0 = microtime(true);

		$x = imap_fetchheader($this->_c, $i, FT_INTERNAL|FT_UID);

		$x = str_replace("\r\n", "\n", $x); // Fix Line Endings
		$x = preg_replace('/\n\s+/ms', ' ', $x); // Un-Fold

		// Sorts
		// $x = explode("\n", $x);
		// sort($x);
		// $x = implode("\n", $x);
		// $stat['head'] = "$x\nTime-Load: " .  (microtime(true) - $t0);
		return $x;
	}
	// function loadHeaders($i)
	// {
	// 	return imap_headerinfo($this->_c,$i,1024,1024);
	// }

	function mailList()
	{
		// FT_UID imap_headers
		$ret = imap_headers($this->_c);
		var_dump($ret);
		return $ret;
	}


	/**
	 * [mailMove description]
	 * @param [type] $m [description]
	 * @param [type] $f [description]
	 * @return [type] [description]
	 */
	function mailMove($m, $f)
	{
		$r = imap_mail_move($this->_c, $m, $f, CP_UID);
		imap_expunge($this->_c);
		return $r;
	}


	/**
		Put a Message to the Server
	*/
	function mailPut($mail, $flag=null, $date=null)
	{
		// Parse date from message?
		if ( (empty($date)) && (preg_match('/^Date: (.+)$/m', $mail, $x)) ) {
			$date = strftime('%d-%b-%Y %H:%M:%S %z',strtotime($x[1]));
		}

		// $stat = $this->pathStat();
		// $ret = imap_append($this->_c,$stat['check_path'], $mail, $flag, $date);

		$ret = imap_append($this->_c, $this->_folder_name, $mail, $flag, $date);

		$x = $this->stat();
		if (!empty($x)) {
			print_r($x);
			return false;
		}

		return true;
	}

	function mailStat($x, $y=null)
	{
		$uid = $x;
		if (!empty($y)) {

		}
		$ret = imap_fetchstructure($this->_c, $uid, FT_UID);
		$ret = json_decode(json_encode($ret), true);
		// $ret['msgno'] = imap_msgno($this->_c, $m);
		return $ret;

	}

	/**
		@param $pat '*' for all folders, '%' for current folder and below
		@return array of folder names
	*/
	function pathList($pat='*')
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

	function pathOpen($p, $stat='stat')
	{
		// Open a Folder
		if (is_object($p)) {
			//$x = $f;
			//$f = new stdClass();
			//$f->name = $this->_c_host . $x;
			$p = $this->_c_host . self::folderName($p->name);
		}

		// prepend host if not found
		if (strpos($p, $this->_c_host) === false) {
			$p = $this->_c_host . self::folderName($p);
		}

		// Reset
		$this->_folder_stat = null;
		$this->_message_cur = 1;
		$this->_message_max = 0;

		// Open
		imap_check($this->_c);
		imap_reopen($this->_c, $p, 0, 1);
		//$this->_c_stat = imap_status($this->_c);
		$x = $this->stat();
		if (empty($x)) {
			$this->_folder_name = $p;
			$this->_message_cur = 1;
			// switch ($stat) {
			// case 'count':
			// 	$this->_message_max = imap_num_msg($this->_c);
			// 	return $this->_message_max;
			// case 'stat':
			// 	$this->_folder_stat = imap_status($this->_c, $p, SA_ALL);
			// 	print_r($this->_folder_stat);
			// 	die('stat');
			// 	return $this->_folder_stat;
			// case 'info':
			// 	$this->_folder_stat = imap_mailboxmsginfo($this->_c);
			// 	$this->_message_max = $this->_folder_stat->Nmsgs;
			// }
			// return $this->_folder_stat;

			// $x0 = self::folderName($p);
			// $x1 = self::folderName($this->_folder_stat->Mailbox);
			// if ($x0 == $x1) {
			// 	return $this->_folder_stat;
			// }
		} else {
			if (preg_match('/no such mailbox/', $x)) {
				return self::E_NO_MAILBOX;
			}
			if (preg_match('/Unknown Mailbox/', $x)) {
				return self::E_NO_MAILBOX;
			}
			die("\nimap_reopen($this->_c_host, $p) failed: $x\n");
		}

	}

	function pathStat($p)
	{
		$ret = array();
		$ret['path_stat'] = imap_status($this->_c, $p, SA_ALL);
		$ret['msg_max'] = imap_num_msg($this->_c);
		$ret['mail_info'] = imap_mailboxmsginfo($this->_c);
		$ret['mail_list'] = imap_fetch_overview($this->_c, sprintf('1:%d', $ret['msg_max']), 0);

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
		@imap_createmailbox($this->_c, $name);
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
			//	$r['stat'] = $x[0];
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
		Just pings the connection
	*/
	function ping()
	{
		// $this->_open();
		return imap_ping($this->_c);
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
