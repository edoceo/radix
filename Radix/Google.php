<?php
/**
    Google Interface

*/

class Radix_Google
{
    private static $_opts;
    public static $API_KEY;

    /**
    */
    static function geoAddress($geo)
    {
        $uri = 'http://maps.googleapis.com/maps/api/geocode/json';
        $uri.= '?';
        $uri.= 'latlng=' . rawurlencode($geo);

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
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        //if ( (!empty(self::$_opts['verbose'])) && (is_resource(self::$_opts['verbose'])) ) {
        //    curl_setopt(self::$_ch, CURLOPT_VERBOSE, true);
        //    curl_setopt(self::$_ch, CURLOPT_STDERR, self::$_opts['verbose']);
        //}
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::$_opts['user-agent']);

        // if ( (!empty(self::$_opts['head'])) ) {
        //     curl_setopt(self::$_ch, CURLOPT_HTTPHEADER, self::$_opts['head']);
        // }

        // if (!empty(self::$_opts['cookie'])) {
        //     curl_setopt(self::$_ch, CURLOPT_COOKIEFILE, self::$_opts['cookie']);
        //     curl_setopt(self::$_ch, CURLOPT_COOKIEJAR, self::$_opts['cookie']);
        // }

        // curl_setopt(self::$_ch, CURLOPT_HEADERFUNCTION, array('self','_curl_head'));
        $res = curl_exec($ch);
        return $ch;

    }
    /**
    */
    static function mapAddress($address)
    {
        // Initialize delay in geocode speed
        // $delay = 0;
        $base = 'http://maps.google.com/maps/geo';
        //$args['key'] = self::$_opts['api_key'];
        //$args['output'] = 'xml';
        $args['q'] = $address;
        // $geocode_pending = true;
        //while ($geocode_pending) {
        //$id = $row["id"];
        // $url = $base_url . "&q=" . urlencode($address);
        //$xml = simplexml_load_file($url) or die("url not loading");
        //$http = new Zend_Http_Client($url);
        //$hres = $http->request();
        $page = Radix_HTTP::get( sprintf('%s?%s',$base,http_build_query($args)) );
        //Radix::dump($page);
        //$xml = simplexml_load_string($page['body']);
        return json_decode($page['body']);
        // return $xml->Response;
        // $status = $xml->Response->Status->code;
        // if (strcmp($status, "200") == 0) {
        // // Successful geocode
        // $geocode_pending = false;
        // $ret = $xml->Response->Placemark->Point->coordinates;
        // return $ret;
        /*
        // Format: Longitude, Latitude, Altitude
        $lat = $coordinatesSplit[1];
        $lng = $coordinatesSplit[0];

        $query = sprintf("UPDATE markers " .
           " SET lat = '%s', lng = '%s' " .
           " WHERE id = '%s' LIMIT 1;",
           mysql_real_escape_string($lat),
           mysql_real_escape_string($lng),
           mysql_real_escape_string($id));
        $update_result = mysql_query($query);
        if (!$update_result) {
        die("Invalid query: " . mysql_error());
        }
        } else if (strcmp($status, "620") == 0) {
        // sent geocodes too fast
        $delay += 100000;
        } else {
        // failure to geocode
        $geocode_pending = false;
        echo "Address " . $address . " failed to geocoded. ";
        echo "Received status " . $status . "\n";
        }
        */
    }
    /**
        Page Rank
        @see https://github.com/phurix/pagerank/
        @param $u the URI
        @return Page Rank of the Page
    */
    static function pageRank($u)
    {
        $hash = self::_pr_hash($u);
        $ch = self::_pr_hash_check($hash);

		require_once('Radix/HTTP.php');
		$page = Radix_HTTP::get("http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=$ch&features=Rank&q=info:$u");

		if (preg_match('/Rank_1:1:(\d+)/',$page['body'],$m)) {
		    return intval($m[1]); //  . '/10';;
		}
		if (preg_match('/Rank_1:2:(\d+)/',$page['body'],$m)) {
		    return intval($m[1]); //  . '/10';;
		}
        return -1;
    }
    /**
        Perform a Google Search
        @return array of results
    */
    static function search($q)
    {
        $base = 'http://ajax.googleapis.com/ajax/services/search/web';
        $args = array(
            'q' => $q,
            'v' => '1.0',
            'key' => self::$API_KEY,
            'rsz' => 'large',
            'start' => 0,
        );
        $page = Radix_HTTP::get( sprintf('%s?%s',$base,http_build_query($args)) );
        $json = json_decode($page['body']);
        return $json;
        // Radix::dump($json,true);
        // if (!empty($json->responseData->cursor->estimatedResultCount)) {
        //     $c = intval($json->responseData->cursor->estimatedResultCount);
        // }
        // $list = array();
        // if ( (!empty($json->responseData->results)) && (is_array($json->responseData->results)) ) {
        //     foreach ($json->responseData->results as $i=>$x) {
        //         $item = array(
        //             'q' => $q,
        //             'uri' => $x->url,
        //             'src' => 'google',
        //             'g-name' => $x->titleNoFormatting,
        //             'g-note' => $x->content,
        //             'g-rank' => (self::$_google_off + $i + 1),
        //         );
        //         self::_page_save($item);
        //         self::$_google_off++;
        //     }
        // }
    }
    /**
        Does some magic I saw on the internet
    */
    private static function _pr_str2num($Str,$Check,$Magic)
    {
		$Int32Unit = 4294967296;  // 2^32

		$length = strlen($Str);
		for ($i = 0; $i < $length; $i++) {
			$Check *= $Magic;
			//If the float is beyond the boundaries of integer (usually +/- 2.15e+9 = 2^31),
			//  the result of converting to integer is undefined
			//  refer to http://www.php.net/manual/en/language.types.integer.php
			if ($Check >= $Int32Unit) {
				$Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
				//if the check less than -2^31
				$Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
			}
			$Check += ord($Str{$i});
		}
		return $Check;
    }
    /**
        Does some magic I saw on the internet
    */
    private static function _pr_hash($String)
    {
		$Check1 = self::_pr_str2num($String, 0x1505, 0x21);
		$Check2 = self::_pr_str2num($String, 0, 0x1003F);

		$Check1 >>= 2;
		$Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
		$Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
		$Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);

		$T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) <<2 ) | ($Check2 & 0xF0F );
		$T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );

		return ($T1 | $T2);
    }
    /**
        Does some magic I saw on the internet
    */
    private static function _pr_hash_check($Hashnum)
    {
		$CheckByte = 0;
		$Flag = 0;

		$HashStr = sprintf('%u', $Hashnum) ;
		$length = strlen($HashStr);

		for ($i = $length - 1;  $i >= 0;  $i --) {
			$Re = $HashStr{$i};
			if (1 === ($Flag % 2)) {
				$Re += $Re;
				$Re = (int)($Re / 10) + ($Re % 10);
			}
			$CheckByte += $Re;
			$Flag ++;
		}

		$CheckByte %= 10;
		if (0 !== $CheckByte) {
			$CheckByte = 10 - $CheckByte;
			if (1 === ($Flag % 2) ) {
				if (1 === ($CheckByte % 2)) {
					$CheckByte += 9;
				}
				$CheckByte >>= 1;
			}
		}
		return '7'.$CheckByte.$HashStr;
    }
}
