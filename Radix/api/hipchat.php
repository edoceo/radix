<?php
/**
	@file
	@brief A Small Tool to read/write to HipChat

	@see https://github.com/tobeychris/hipchat-room-message-APIv2/
*/

class radix_api_hipchat
{
	protected $_base = 'https://api.hipchat.com/v2';
	public $_auth;

	function __construct($u, $p=null)
	{
		$this->_auth = $u;
	}

	function getRooms()
	{
		$uri = $this->_base . '/room';
		return $this->_api($uri);
	}

	/**
		@return Array of WebHook Objects
	*/
	function getWebhooks($r)
	{
		return $this->_api($this->_base . '/room/' . rawurlencode($r) . '/webhook');
		
	}
	

	function message($room, $msg)
	{
		$uri = $this->_base . '/room/' . $room . '/notification';
		if (is_array($msg)) {
			$req = $msg;
		} else {
			$req = array(
				'message' => $msg,
				'notify' => false,
				'message_format' => 'text',
				// 'color', // yellow, red, green, purple, gray, random (default: 'yellow')
			);
		}

		$this->_api($uri, $req);
	}

	private function _api($uri, $req=null)
	{
		$ch = $this->_curl_init($uri);

		$head = array(
			'Authorization: Bearer ' . $this->_auth,
		);

		if (!empty($req)) {
			if (is_array($req)) {
				$req = json_encode($req);
			}
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $req);

			$head[] = 'Content-Type: application/json';
			$head[] = 'Content-Length: ' . strlen($req);

		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $head);

		return $this->_curl_exec($ch);
	}

	/**
		Curl Init
	*/
    private static function _curl_init($uri)
    {
        $ch = curl_init($uri);
        // Booleans
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_CRLF, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NETRC, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // curl_setopt(self::$_ch, CURLOPT_SSLVERSION, 3); // 2, 3 or GnuTLS
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Edoceo Radix HipChat Interface');

        // curl_setopt(self::$_ch, CURLOPT_HEADERFUNCTION, array('self','_curl_head'));

        return $ch;
    }

    /**
        Execute the CURL request
        @param $ch CURL Handle
        @return API data
    */
    private static function _curl_exec($ch)
    {
        $res = array(
            'body' => curl_exec($ch),
            'info' => curl_getinfo($ch),
        );
        $ret = $res;

        if (curl_errno($ch)) {
            return array(
                'success' => false,
                'message' => sprintf('%d:%s',curl_errno($ch),curl_error($ch)),
            );
        }
        // radix::dump($r);

        if ('application/json' == $res['info']['content_type']) {
            $ret = json_decode($res['body'],true);
            $ret['code'] = 200;
        }

        if (200 != $res['info']['http_code']) {
            $ret['code'] = $res['info']['http_code'];
        }

        return $ret;
    }

}
