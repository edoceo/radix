<?php
/**
	Other Service Inherit from This 
	Services are methods for Radix to communicate with external service providers
*/

namespace Radix;

class Service
{
	protected function _curl_init($uri)
	{
		$ch = curl_init($uri);

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
        // curl_setopt($ch, CURLOPT_TIMEOUT, 16);

        curl_setopt($ch, CURLOPT_USERAGENT, 'Radix Service (+http://edoceo.com/radix)');

        // curl_setopt($ch, CURLOPT_VERBOSE, true);
        // curl_setopt($ch, CURLOPT_STDERR, fopen(sprintf('/tmp/curl%s.log', $_SERVER['UNIQUE_ID']), 'a'));

        return $ch;
	}
	
	protected function _curl_exec($ch)
	{
		$buf = curl_exec($ch);
		$inf = curl_getinfo($ch);
		return array(
			'body' => $buf,
			'info' => $inf,
		);
	}

	protected function _get($uri)
	{
		$ch = $this->_curl_init($uri);

		$head = array();
		$head[] = 'Accept: application/json';

        // if (!empty($update_json)){
        // 	$header = array('Content-Type: application/json');
        // 	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 	curl_setopt($ch, CURLOPT_POSTFIELDS, $update_json);
        // 	echo "\ncloseIO_request(" . print_r($update_json, true) . ")\n";
        // 	exit;
        // }

        // curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
		// curl_setopt($ch, CURLOPT_VERBOSE, true);
		// curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		// curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', self::API_USER, self::API_PASS));

		$buf = curl_exec($ch);
		$inf = curl_getinfo($ch);
		switch ($inf['http_code']) {
		case 200:
		case 201:
			// OK
			$buf = json_decode($buf, true);
			break;
		default:
			print_r($buf);
			print_r($inf);
			die("Failed\n");
		}

		return $buf;
	}
	
	protected function _post($uri, $post)
	{
		
	}
	
	protected function _put($uri, $body)
	{
		
	}

}