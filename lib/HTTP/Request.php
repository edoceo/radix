<?php
/**
 *
 */

namespace Edoceo\Vena\HTTP;

class Request
{
	private $attr = [];

	private $path;

	private $path_full = '';

	private $hash;

	private $verb;

	private $_req_head;

	function __construct($path='', $verb='GET')
	{
		$this->verb = $verb;

		if (empty($path)) {
			$path = $_SERVER['REQUEST_URI'];
		}

		$this->hash = sha1($path . $verb);


		$path = strtok($path, '?');
		$path = ltrim($path, '/');
		$this->path_full = $path;

		$path_enc = rawurlencode($path);
		$path = explode('/', $path);

		// $repo = array_shift($path);

		$req_head = [];
		foreach ($_SERVER as $k => $v) {
			if (preg_match('/^HTTP_(.+)$/', $k, $m)) {
				$k = strtolower($m[1]);
				$k = str_replace('_', '-', $k);
				$req_head[$k] = $v;
			}
		}
		unset($req_head['host']);
		unset($req_head['via']);
		unset($req_head['x-forwarded-for']);
		unset($req_head['x-forwarded-host']);
		unset($req_head['x-forwarded-proto']);
		ksort($req_head);

		$this->_req_head = $req_head;

	}

	function getAttribute($k)
	{
		return $this->attr[$k] ?? '';
	}

	function getHash()
	{
		return $this->hash;
	}

	function getHeaders()
	{
		return $this->_req_head;
	}

	function getPath()
	{
		return $this->path_full;
	}

	function getVerb()
	{
		return $this->verb;
	}

	function setAttribute($k, $v)
	{
		$this->attr[$k] = $v;
	}

	function save()
	{
		$req_file = sprintf('%s/req/%s-%s-req.json', APP_DATA, $path_enc, $this->hash);
		$out_path = dirname($req_file);
		if ( ! is_dir($out_path)) {
			mkdir($out_path, 0755, true);
		}

		$req_data = json_encode([
			'REQ' => sprintf('%s %s', $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']),
			'HEAD' => $this->_req_head,
			'_SERVER' => $_SERVER,
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		file_put_contents($req_file, $req_data);

	}
}
