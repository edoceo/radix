<?php
/**
	Interact with the Close.IO Service
*/

namespace Edoceo\Radix\Service;

class CloseIO extends \Radix\Service
{
	const API_BASE = 'https://app.close.io/api/v1';

	private $_auth = null;
	
	function __construct($a)
	{
		$this->_auth = $a;
	}
	
	public function getLead($x)
	{
		$res = self::_get('/lead/?query=email_address%3A' . rawurlencode($x));
		return $res;
	}

	public function saveUser($update_json="", $request_type="GET", $function="me/", $query="", $id="")
	{
		echo "Service_CloseIO::saveUser(" . var_export($update_json, true) . ", $request_type, $function, $query, $id);\n";
		return self::closeIO_request($update_json, $request_type, $function, $query, $id);
	}

	protected function _get($uri)
	{
		$uri = self::API_BASE . $uri;
		$ch = $this->_curl_init($uri);

		$head = array();
		$head[] = 'Accept: application/json';

        curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:', $this->_auth));

		$buf = curl_exec($ch);
		$inf = curl_getinfo($ch);
		switch ($inf['http_code']) {
		case 200:
		case 201:
			// OK
			$buf = json_decode($buf, true);
			break;
		default:
		}

		return $buf;
	}
}
