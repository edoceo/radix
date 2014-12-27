<?php
/**
	An Interface to Yo
*/

namespace Radix\Service;

class Yo
{
	const API_URL = 'https://api.justyo.co/';

	private $_api_key;

	function __construct($k)
	{
		$this->_api_key = $k;
	}

	/**
		Static Helper
		@param $yoak Yo API Key
		@param $user User to Yo to, Null for All
		@param $link Optional Link
	*/
	public static function yo($yoak, $user=null, $link=null)
	{
		$x = new self($yoak);
		return $x->send($user, $link);
	}

	/**
		@param $user if null, send to all
		@param $link The Link to attach
	*/
	function send($user=null, $link=null)
	{
		$post = array(
			'api_token' => $this->_api_key,
		);
		if (!empty($link)) {
			$post['link'] = $link;
		}

		$call = self::API_URL;
		if (!empty($user)) {
			$post['username'] = $user;
			$call.= 'yo/';
		} else {
			$call.= 'yoall/';
		}

		return self::_curl_post($call, $post);

	}

	private static function _curl_post($url, $arg)
	{
		$ch = curl_init($url);

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

		curl_setopt($ch, CURLOPT_USERAGENT, 'Edoceo Radix (+http://radix.edoceo.com)');

		curl_setopt($ch, CURLOPT_VERBOSE, false);
		// curl_setopt($ch, CURLOPT_STDERR, fopen(sprintf('/tmp/curl%s.log', $_SERVER['UNIQUE_ID']), 'a'));

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $arg);

		$res = curl_exec($ch);
		$inf = curl_getinfo($ch);
		curl_close($ch);

		return array(
			'code' => $inf['http_code'],
			'body' => $res
		);

	}

}