<?php
/**

*/

class Radix_Curl
{
	private $_ch;
	private $_url;

	function __construct($x)
	{
		$ch = curl_init($x);

		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_CRLF, false);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_FILETIME, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		// curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_NETRC, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

		curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		// curl_setopt(self::$_ch, CURLOPT_SSLVERSION, 3); // 2, 3 or GnuTLS

		// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 16);
		// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		// curl_setopt($ch, CURLOPT_TIMEOUT, 0);
		// curl_setopt($ch, CURLOPT_TIMEOUT, 16);

		curl_setopt($ch, CURLOPT_USERAGENT, 'Edoceo Radix (+http://radix.edoceo.com/)');

		curl_setopt($ch, CURLOPT_VERBOSE, false);
		// curl_setopt($ch, CURLOPT_STDERR, fopen(sprintf('/tmp/curl%s.log', $_SERVER['UNIQUE_ID']), 'a'));

		$this->_ch = $ch;

	}

	function exec()
	{
		$this->_res = curl_exec($this->_ch);
		return $this->_res;
	}

	function head()
	{
        $ret = array();

        curl_setopt($this->_ch, CURLOPT_HEADER, true);
        curl_setopt($this->_ch, CURLOPT_NOBODY, true);
        $buf = curl_exec($this->_ch);
        $buf = explode("\n", $buf);
        foreach ($buf as $hln) {
                if (preg_match('/^([\w\-]+):(.+)$/', $hln, $m)) {
                        $ret[trim($m[1])] = trim($m[2]);
                }
        }

        return (count($ret) ? $ret : null);
	}

	/**
		@param $post Array or String to POST
	*/
	function post($post)
	{
		// if (is_array($post)) {
		//
		// }

		$this->opt(CURLOPT_POST, true);
		$this->opt(CURLOPT_POSTFIELDS, $post);
		$this->opt(CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
		));

		return $this->exec();

	}

	/**
		@param $post Array or Object or String to send as JSON
	*/
	function postJSON($post)
	{
		if (is_array($post)) {
			$post = json_encode($post);
		} elseif (is_object($post)) {
			$post = json_encode($post);
		}

		$this->opt(CURLOPT_POST, true);
		$this->opt(CURLOPT_POSTFIELDS, $post);
		$this->opt(CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
		));

		return $this->exec();
	}


	/**
	*/
	function info()
	{
		return curl_getinfo($this->_ch);
	}

	function opt($k,$v)
	{
		switch ($k) {
		case 'User-Agent':
		case CURLOPT_USERAGENT:
			curl_setopt($this->_ch, CURLOPT_USERAGENT, $v);
			break;
		default:
			curl_setopt($this->_ch, $k, $v);
		}
	}

	function download($out=null)
	{
        // echo "_curl_fetch($uri, $out=null)\n";
        if (null == $out) {
			$out = tempnam('/tmp', 'curl');
        }

        curl_setopt($this->_ch, CURLOPT_FILE, fopen($out, 'w'));
        curl_exec($this->_ch);
        curl_close($this->_ch);

        return $out;
	}

	/**

	*/
	function setHeader($k, $v)
	{
		$this->_head[] = sprintf('%s: %s', $k, $v);
	}

}