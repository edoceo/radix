<?php
/**
	Interact with the Intercom.IO Service
*/

namespace Edoceo\Radix\Service;

class IntercomIO extends \Radix\Service
{
	const API_BASE = 'https://api.intercom.io';

	private $_user = null;
	private $_pass = null;

	function __construct($u,$p)
	{
		$this->_user = $u;
		$this->_pass = $p;
	}

	public function getSegments()
	{
		$res = self::_api('/segments');
		print_r($res);
		return $res['segments'];
	}


	public function getAllTags()
	{
		$res = self::_api('/tags');
		return $res['tags'];
	}

	/**
		This chews up too much memory on each run, so we should do something smart here
	*/
	public function getAllUsers()
	{
		$ret = array();
		$next = '/users?per_page=60'; // 60 == emperical max
		do {
			$res = self::_api($next);
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
			return self::_api('/users?user_id=' . rawurlencode($x));
		}

		return self::_api('/users?email=' . rawurlencode($x));
	}

	public function getUsersBySegement($id)
	{
		$ret = array();
		$next = '/users?segement_id=' . $id;
		do {
			$res = self::_api($next);
			$next = null;
			if (!empty($res['pages']['next'])) {
				$next = str_replace(self::API_BASE, null, $res['pages']['next']);
			}
			$ret = array_merge($ret, $res['users']);
		} while (!empty($next));

		return $ret;
	}

	public function getUsersByTag($id)
	{
		$ret = array();
		$next = '/users?tag_id=' . $id;
		do {
			$res = self::_api($next);
			$next = null;
			if (!empty($res['pages']['next'])) {
				$next = str_replace(self::API_BASE, null, $res['pages']['next']);
			}
			$ret = array_merge($ret, $res['users']);
		} while (!empty($next));

		return $ret;
	}


	public function putUser($x)
	{
		$uri = self::API_BASE . '/users';
		$ch = $this->_curl_init($uri);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Content-Type: application/json',
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS , json_encode($x));

		$res = $this->_curl_exec($ch);
		switch ($res['info']['http_code']) {
		case 200:
			return json_decode($res['body'], true);
		}

		return $res;

	}

	protected function _curl_init($uri)
	{
		$ch = parent::_curl_init($uri);

		$head = array();
		$head[] = 'Accept: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->_user, $this->_pass));

		return $ch;
	}

	protected function _api($uri)
	{
		$uri = self::API_BASE . $uri;
		$ch = $this->_curl_init($uri);

		$res = $this->_curl_exec($ch);
		switch ($res['info']['http_code']) {
		case 200:
			return json_decode($res['body'], true);
		}

		return $res;

	}

}