<?php
/**
	Interact with the Intercom.IO Service
*/

namespace Radix\Service;

class IntercomIO extends \Radix\Service
{
	const API_BASE = 'https://api.intercom.io';

	private $_user = '';
	private $_pass = '';

	function __construct($u,$p)
	{
		$this->_user = $u;
		$this->_pass = $p;
	}
	
	public function getAllTags()
	{
		$res = self::_get('/tags');
		return $res['tags'];
	}

	public function getAllUsers()
	{
		$ret = array();
		$next = '/users?per_page=60'; // 60 == emperical max
		do {
			$res = self::_get($next);
			$next = null;
			if (!empty($res['pages']['next'])) {
				$next = str_replace(self::API_BASE, null, $res['pages']['next']);
			}
			$ret = array_merge($ret, $res['users']);
		} while (!empty($next));

		return $ret;
	}

	public function getUser($x)
	{
		if (is_integer($x)) {
			return self::_get('/users?user_id=' . rawurlencode($x));
		}

		return self::_get('/users?email=' . rawurlencode($x));
	}

	public function getUsersByTag($id)
	{
		$ret = array();
		$next = '/users?tag_id=' . $id;
		do {
			$res = self::_get($next);
			$next = null;
			if (!empty($res['pages']['next'])) {
				$next = str_replace(self::API_BASE, null, $res['pages']['next']);
			}
			$ret = array_merge($ret, $res['users']);
		} while (!empty($next));

		return $ret;
	}

	protected function _get($uri)
	{
		$uri = self::API_BASE . $uri;
		$ch = $this->_curl_init($uri);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->_user, $this->_pass));

	}

}