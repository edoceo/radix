<?php
/**
    @file
    @brief An interface to Amazon S3
*/

class radix_aws_s3 // S3Connection
{
    private $_access_key_id;
    private $_access_key_secret;
    private $_config = array(
        'cache_flush'=>5,
        'curl_progress' => 1048576,
        's3_retry'=>3,
    );
    private $_file_pipe;
    private $_file_resource;
    private $_head;
    private $_hmac;
    private $_http_request_verb;
    // no trailing slash, change for ssl if needed
    private $_s3_url_base = 'http://s3.amazonaws.com';

	private $_s3_stats = array(
		'get' => 0,
		'ls' => 0,
		'put' => 0,
		'rm' => 0,
		'stat' => 0,
	);

  // func: __construct($k,$s)
  function __construct($k,$s,$p=null)
  {
    $this->_access_key_id = $k;
    $this->_access_key_secret = $s;

		if (!is_array($p)) $p = array();
		foreach ($this->_config as $k=>$v) $this->_config[$k] = isset($p[$k]) ? $p[$k] : $v;

    $this->_hmac = new Crypt_HMAC($this->_access_key_secret, 'sha1');

		// This is so we only have to execute `file` once.
		// The script was die()ing after about 1020 calls to exec()
		$ds = array(
			 0 => array('pipe','r'),  // stdin
			 1 => array('pipe','w'),  // stdout
			 2 => array('pipe','w')   // stderr
		);
		$this->_file_resource = proc_open('/usr/bin/file -bin -f-',$ds,$op);
		if (is_resource($this->_file_resource)) $this->_file_pipe = $op;
  }

    /**
        Get a _curl Handle for the URI
        @param $uri
    */
    function _curl($uri)
    {

        $ch = curl_init($uri); // todo: should set URL later with CURLOPT_URL
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_CRLF, false);
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_NETRC, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384);  // bigger?
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // Don't verify, 1=verify, 2=verify & match name
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Edoceo Radix AWS/S3 2012.49');
        // Set my CallBack functions
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(&$this,'_curl_head'));
        //curl_setopt($ch, CURLOPT_READFUNCTION, array(&$this,'_curl_read'));
        //curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, '_curl_write'));
        return $ch;
    }

    // func: _curl_header($ch,$buf) - Curl Header Callback
    function _curl_head($ch,$buf)
    {
        // echo "S3Connection::_curl_head($ch,$buf)\n";
        $ret = strlen($buf);
        /*
        if (preg_match('/^HTTP\/1\.1 (\d{3}) (.+)/',$buf,$m))
        {
            $this->_status_code = $m[1];
            $this->_status_mesg = $m[2];
        }
        else
        */
        if (preg_match('/^(.+?):(.+)/',$buf,$m)) $this->_head[strtolower(trim($m[1]))] = trim($m[2]);
        // note: HTTP 1.1 (rfc2616) says that 404 and HEAD should not have response.
        // note:  CURL will hang if we don't force close by return 0 here
        // note: http://developer.amazonwebservices.com/connect/thread.jspa?messageID=40930
        // Last Line of Headers
        if (($ret==2) && ($this->_http_request_verb=='HEAD')) return 0;
        return $ret;
    }

  // func: _curl_read($ch,$buf) - $ch = Curl Handle, $buf = Data in from the Socket
  function _curl_read($ch,$buf)
  {
    // echo "S3Connection::_curl_read($ch,$buf)\n";
    return 0;
  }

  // func: _curl_write($ch,$buf) - $ch = Curl Handle, $buf = Data in from the Socket
  private function _curl_write($ch,$buf)
  {
    $ret = strlen($buf);
    // echo "S3Connection::_curl_write($ch,".substr($buf,0,32)."...($ret));\n";
    if ($this->response_head['content-type']=='application/xml')
      $this->response_body.= $buf;
    else
		{
			// note: this should never execute cause when getting we have curl do that
      echo "Should write these ".strlen($buf)." bytes to some file\n";
			echo "--] $buf [--\n";
		}
    return $ret;
  }

  // func: hex2b64($str) - from the Amazon S3 examples
  // argv: $str - is a hash, converted to binary value and base64 encoded
  private function _hex2b64($s)
  {
		$c = strlen($s);
    $r = null;
    for ($i=0; $i<$c; $i+=2) $r.= chr(hexdec(substr($s, $i, 2)));
    return base64_encode($r);
  }

	// func: _mime_type($path) - returns mime type using previously opened `file` process
	private function _mime_type($path)
	{
		fwrite($this->_file_pipe[0],"$path\n");
		$buf = fgets($this->_file_pipe[1],128);
		return trim($buf);
	}


  // func: _request($verb,$s3path,$lhsrc=null)
  private function _request($verb,$s3path,$lhsrc=null,$meta=null)
  {
    // echo "S3Connection::_request($verb,$s3path,$lhsrc,$meta)\n";
		// Refresh Internals
    $this->_head = array();
		$this->_http_request_verb = $verb;

		$s3url = $this->_s3_url_base;
		$s3url.= $s3path;

    $http_date = gmdate(DATE_RFC2822);
    $content_len = $content_md5 = $content_type = null;
    if (($verb=='PUT') && (is_file($lhsrc)))
    {
			$content_len = filesize($lhsrc);
      $content_md5 = $this->_hex2b64(md5_file($lhsrc)) or die("MD5 Error");
      $content_type = $this->_mime_type($lhsrc);
    }
		// Canonicalized Headers
		$metadata = null;
		$head = array();
		//if ($verb=='PUT') $head[] = "Content-Length: $content_len";
    $head[] = "Content-MD5: $content_md5";
    $head[] = "Content-Type: $content_type";
    $head[] = "Date: $http_date";
		if (is_array($meta))
		{
			ksort($meta);
			foreach ($meta as $k=>$v)
			{
				$x = rawurlencode(trim($v));
				$metadata.= sprintf("x-amz-meta-%s:%s\n",$k,$x);
				$head[] = "x-amz-meta-$k: $x";
			}
		}
		// Canonicalized Resource
		$s3path_c = parse_url($s3url,PHP_URL_PATH);

    $canonicalized_headers = "$verb\n$content_md5\n$content_type\n$http_date\n$metadata$s3path_c";
    // echo "Canonicalized:$canonicalized_headers\n";

    $signature = $this->_hex2b64($this->_hmac->hash($canonicalized_headers));
    $head[] = "Authorization: AWS $this->_access_key_id:$signature";

    $ch = $this->_curl($s3url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $head);

    // Set Specific Options
    if ($verb=='DELETE') curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'DELETE');
    elseif ($verb=='HEAD') curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'HEAD');
    elseif ($verb=='GET')
    {
      curl_setopt($ch,CURLOPT_HTTPGET,true);
      if ($lhsrc)
      {
        $fh = fopen($lhsrc,'w');
        curl_setopt($ch,CURLOPT_FILE,$fh);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,false);
      }
    }
    elseif ($verb=='PUT')
    {
      curl_setopt($ch,CURLOPT_PUT,true);
			// note: Show progress on large files
			if ($content_len > $this->_config['curl_progress']) curl_setopt($ch, CURLOPT_NOPROGRESS, false);
			curl_setopt($ch,CURLOPT_INFILESIZE,$content_len);
			curl_setopt($ch,CURLOPT_INFILE,fopen($lhsrc,'r')); // load the file in by its resource handle
    }

		// Return Response
		$buf = curl_exec($ch);
		if (($buf === true) || ($buf === false)) $buf = null;
		$inf = curl_getinfo($ch);
		$msg = curl_error($ch);
    $res = curl_errno($ch);
		curl_close($ch);
		$s3r = new S3Response($inf,$this->_head,$buf);
		return $s3r;
  }
	
	function auth($s3path,$expires=300)
	{
		$time = time() + $expires;
		if (substr($s3path,0,1)!='/') $s3path = "/$s3path";
		$signature = rawurlencode($this->_hex2b64($this->_hmac->hash("GET\n\n\n$time\n$s3path")));
		print_r($this);
		echo $this->_access_key_id."\n";
		$s3url = sprintf('%s%s?AWSAccessKeyId=%s&Expires=%u&Signature=%s',$this->_s3_url_base,$s3path,$this->_access_key_id,$time,$signature);
		return $s3url;
	}

  // func: gets the object to a local file
  function get($src,$dst)
  {
		$this->_s3_stats['get']++;
    $url = $this->path_fix($src);
    $s3r = $this->_request('GET',$url,$dst);
		return $s3r;
  }

  // func: ls($p,$m=null,$d='/') - Returns a List of Items
  function ls($p,$m=null,$d='/')
  {
		//printf("%s::%s(%s,%s,%s)\n",__CLASS__,__FUNCTION__,$p,$m,$d);
		$this->_s3_stats['ls']++;
		list($b,$p) = $this->path_split($p);

		$s3url = '/';
		if (strlen($b)) $s3url.= "$b/";
		if ($p=='/') $p = null;
		$s3url.= '?';
		if (strlen($d)) $s3url.= sprintf('delimiter=%s&',rawurlencode($d));
		if (strlen($m)) $s3url.= sprintf('marker=%s&',rawurlencode($m));
		$s3url.= sprintf('prefix=%s',rawurlencode($p));
		$s3r = $this->_request('GET',$s3url);
    return $s3r;
  }

	function path_encode($path)
	{
		// printf("%s::%s(%s)\n",__CLASS__,__FUNCTION__,$path);

		$pa = preg_split('/\//',$path,-1,PREG_SPLIT_NO_EMPTY);
		$lc = substr($path,-1); // If the last char is a delimiter we should save it
		$b = isset($pa[0]) ? array_shift($pa) : null;
		$p = null;
		foreach ($pa as $x) $p.= '/'.rawurlencode($x);
		if ($lc=='/') $p.= $lc; // save $lc for use here if needed
		if (strlen($p)==1) $p = null;
		return str_replace('//','/',"/$b/$p");
	}

	// func: path_split($path,$part=null)
	function path_split($path,$part=null)
	{
		// printf("%s::%s(%s,%s)\n",__CLASS__,__FUNCTION__,$path,$part);

		$pa = preg_split('/\//',$path,-1,PREG_SPLIT_NO_EMPTY);
		$lc = substr($path,-1); // If the last char is a delimiter we should save it
		$b = isset($pa[0]) ? array_shift($pa) : null;
		$p = implode('/',$pa); // Reuse $p!
		if ($lc=='/') $p.= $lc; // save $lc for use here if needed
		if (strlen($p)==1) $p = null;
		if ($part=='bucket') return $b;
		elseif ($part=='prefix') return $p;
		else return array($b,$p);
	}

	// func: path_fix($p) - removes double // and prepends / if necessary
	function path_fix($p)
	{
		// printf("%s::%s(%s)\n",__CLASS__,__FUNCTION__,$p);
		$p = str_replace('//','/',$p);
		if (substr($p,0,1)!='/') return "/$p";
		return $p;
	}

  // func: puts $l ocalfile to $r emote location
	// spec: if $r no given and $l is only one item, like put('curo.bucket'); it will create a bucket
  // spec: to create bucket say name only, no data
  // spec: to put file make $data the buffer or a file handle
  function put($src,$dst,$meta=null) // Put Local File to Remote Object
  {
		// echo "S3Connection->put($src,$dst,$meta);\n";
		$this->_s3_stats['put']++;
		// Handle Source
		$meta['oname'] = $src;

		$ft = filetype($src);
		if ($ft=='dir') die("Cannot PUT a dir");
		elseif ($ft=='link')
		{
			$meta['file_link'] = readlink($src);
			$x = realpath($src);
			if (strlen($x)) $meta['real_path'] = $x;
			$src = null;
		}
		elseif ($ft=='file')
		{
			/* Nothing */
		}
		//else die("Unhandled File Type: $ft");

		// Handle Destination
		$dst = $this->path_encode($dst);
		for ($i=0;$i<5;$i++)
		{
			$s3r = $this->_request('PUT',$dst,$src,$meta);
			if ($s3r->is_success) return $s3r;
			elseif (($s3r->error_code >= 400) && ($s3r->error_code <= 499)) return $s3r;
			echo "Retrying...\n";
		}
		return $s3r;
  }

  // func: rm($key) - deletes an object
  function rm($s3path)
  {
    // printf("%s::%s(%s)\n",__CLASS__,__FUNCTION__,$s3path);
		$this->_s3_stats['rm']++;
    $s3r = $this->_request('DELETE',$s3path);
    return $s3r;
  }

	// func: stat($s3path)
	function stat($s3path)
	{
		// printf("%s::%s(%s)\n",__CLASS__,__FUNCTION__,$s3path);
		$this->_s3_stats['stat']++;
		$s3path = $this->path_encode($s3path);
		$s3r = $this->_request('HEAD',$s3path);
		return $s3r;
	}
}

// cobj: S3Response
class S3Response
{
	private $_code;
	private $_head;
	private $_xml;

	function __construct($inf,$head,$body)
	{
		//echo "S3Response->__construct($inf,$head,".substr($body,0,32)."...)\n";
		$this->_inf = $inf;
		$this->_head = $head;
		// todo: Remove this Dependency and use preg_match patterns
		if (strlen($body)) $this->_xml = new SimpleXMLElement($body);
		if (!$this->is_success)
		{
			$this->error_code = intval($this->_inf['http_code']);
			if (is_object($this->_xml)) $this->error_message = strval($this->_xml->Error->Message)."\n".print_r($this);
			else $this->error_message = 'Unknown';
		}
	}

	function __get($key)
	{
		if ($key=='as_string')
		{
			if ($this->is_success) return 'S3 Response Success';
			else return "S3 Error: #$this->error_code; $this->error_message".print_r($this,true);
		}
		elseif ($key=='bucket') return strval($this->_xml->Name);
		elseif ($key=='content_type') return $this->_head['content-type'];
		elseif ($key=='content_length') return $this->_head['content-length'];
		elseif (substr($key,0,5)=='head_') return $this->_head[substr($key,5)];
		elseif ($key=='is_directory')
		{
			$x = $this->entries;
			return count($this->entries) > 0;
		}
		elseif ($key=='is_success') return (($this->_inf['http_code'] >= 200) && ($this->_inf['http_code'] <= 299));
		elseif ($key=='is_truncated') return ($this->_xml->IsTruncated=='true');
		elseif ($key=='marker') return strval($this->_xml->Marker);
		elseif ($key=='next_marker') return strval($this->_xml->NextMarker);
		elseif ($key=='meta')
		{
			$meta = array();
			if (!is_array($this->_head)) return $meta;
			foreach ($this->_head as $k=>$v)
			{
				if (substr($k,0,11)=='x-amz-meta-') $meta[substr($k,11)] = $v;
			}
			return $meta;
		}
		elseif ($key=='prefix') return strval($this->_xml->Prefix);
		elseif ($key=='entries')
		{
			$ls = array();
			// Find the Buckets (if Any)
			if ($this->_xml->getName()=='ListAllMyBucketsResult')
			{
				$c = count($this->_xml->Buckets->Bucket);
				for ($i=0;$i<$c;$i++)
				{
					$x = $this->_xml->Buckets->Bucket[$i];
					$e = new stdClass();
					$e->bucket = $e->path = $e->name = strval($x->Name);
					$e->date = strval($x->CreationDate);
					$e->size = 0;
					$e->type = 'b';
					$ls[$e->name] = $e;
				}
				return $ls;
			}

			// Add the CommonPrefixes First this one (directories)
			$c = count($this->_xml->CommonPrefixes);
			for ($i=0;$i<$c;$i++)
			{
				$x = $this->_xml->CommonPrefixes[$i];
				$e = new stdClass();
				$e->bucket = $this->bucket;
				$e->path = $this->prefix;
				$e->name = strval($x->Prefix);
				$e->date = null;
				$e->size = 0;
				$e->type = 'p';
				if (substr($e->name,-1)!='/') $e->name.='/';
				$ls[$e->name] = $e;
			}
			// Add the Contents (files)
			$c = count($this->_xml->Contents);
			for ($i=0;$i<$c;$i++)
			{
				$x = $this->_xml->Contents[$i];
				$e = new stdClass();
				$e->bucket = $this->bucket;
				$e->path = $this->prefix;
				$e->name = strval($x->Key);
				$e->date = strval($x->LastModified);
				$e->size = intval($x->Size);
				$e->type = 'o';
				$ls[$e->name] = $e;
			}
			ksort($ls);
			return $ls;
		}
		elseif ($key=='type') return $this->_xml->getName();
		trigger_error("Invalid Property: $key",E_USER_ERROR);
	}
}

// cobj: S3Cache
class S3Cache
{
	private $_cache;
	private $_file;
	private $_added;
	private $_expire = 86400; // 24 Hour Cache Life

	function __construct($file)
	{
		//printf("%s::%s(%s)\n",__CLASS__,__FUNCTION__,$file);

		$this->_cache = array();
		$this->_file = $file;
		$time = time();
		if (is_file($this->_file))
		{
			if (!is_readable($this->_file)) die("Can't Read Cache Data");
			$fh = fopen($this->_file,'r');
			while ($buf=fgets($fh))
			{
				list($key,$mbuf) = explode("\x03",trim($buf));
				$meta = unserialize($mbuf);
				if (!isset($meta['s3cache_expires'])) $meta['s3cache_expires'] = $time + $this->_expire;
				/*
				if ($meta['s3cache_expires'] < $time)
				{
					echo "Cache::rm($key)\n";
					print_r($meta);
					exit;
					continue; // Skip Expired Stuffs
				}
				*/
				$this->_cache[$key] = $meta;
			}
		}
	}

	function add($key,$meta)
	{
		//printf("%s::%s(%s,%s)\n",__CLASS__,__FUNCTION__,$key,$meta);
		$meta['s3cache_expires'] = time() + $this->_expire;
		if (!isset($this->_cache[$key])) $this->_added++;
		$this->_cache[$key] = $meta;
		if (($this->_added % 5) == 0) $this->save();
	}

	function delete($key) { unset($this->_cache[$key]); }

	function hit($key)
	{
		if (isset($this->_cache[$key]))
		{
			$this->_cache[$key]['s3cache_expires'] = time() + $this->_expire;
			return $this->_cache[$key];
		}
		return false;
	}

	function save()
	{
		//printf("%s::%s(%s)\n",__CLASS__,__FUNCTION__,$this->_file);
		$fh = fopen($this->_file,'w');
		foreach ($this->_cache as $key=>$meta) fwrite($fh,sprintf("%s\x03%s\n",$key,serialize($meta)));
		fclose($fh);
		return true;
	}
}
