<?php
/**
 * RFC3161 Timestamp Authority Client
 *
 *
 */

namespace Edoceo\Radix\RFC3161;

class Client
{
	private $file;

	private $hash;

	private $tsq;

	private $tsr;

	private $url;

	/**
	 * @param string $url the endpoint of the TSA server.
	 */
	function __construct(string $url)
	{
		$this->url = $url;
	}

	function getQuery()
	{
		return $this->tsq;
	}

	function getReply()
	{
		return $this->tsr;
	}

	/**
	 *
	 */
	function stampData(string $data)
	{
		$hash = hash('sha512', $data, true);
		return $this->stampHash($hash);
	}

	/**
	 *
	 */
	function stampFile(string $file)
	{
		if ( ! is_file($file)) {
			return false;
		}

		$hash = hash_file('sha512', $file, true);

		return $this->stampHash($hash);

	}

	/**
	 *
	 */
	function stampHash(string $hash)
	{
		$this->query($hash);
		$this->reply();
		return $this;
	}

	/**
	 * Create the Query
	 * @param string $file The File to Get Signed
	 */
	function query(string $hash) : string
	{
		$asn1 = [];
		// DER SEQUENCE
		$asn1[0] = chr(0x00) . chr(0x00); // Replace w/0x30 and then LENGTH
		// TimeStampRequest Version (INTEGER v1)
		$asn1[1] = chr(0x02) . chr(0x01) . chr(0x01); // INTEGER + Length + Value
		// Message Imprint
		$asn1[2] = chr(0x30) . chr(0x00); // SEQUENCE OF + Length (TBD)
		$asn1[3] = chr(0x30) . chr(0x0d); // SEQUENCE OF + Length (0x0d == 13)
		// Message Imprint / Object ID, Length 0x09
		$asn1[4] = chr(0x06) . chr(0x09) // OBJECT IDENTIFIER (length 9 bytes)
			 . chr(0x60) // 2 . 16
			 . chr(0x86) . chr(0x48) // 134 . 75
			 . chr(0x01) . chr(0x65) // 1 . 101
			 . chr(0x03) . chr(0x04) // 3 . 4
			 . chr(0x02) . chr(0x03) // 2 . 3
			 . chr(0x05) . chr(0x00); // OID Terminator == NULL + Length (0x00)

		// $asn1[6] = chr(0x04) . chr(0x20); // OCTET STRING 0x20 == 32 Bytes (SHA256)
		$asn1[5] = chr(0x04) . chr(0x40); // OCTET STRING 0x40 == 64 Bytes (SHA512)
		$asn1[6] = $hash;

		// Nonce
		$want_rand = false;
		if ($want_rand) {
			$rand = random_bytes(16);
			$asn1[] = chr(0x02) // INTEGER
				. chr(0x10) // Length (16 bytes)
				. $rand; //  nonce value
		}

		// Apple Timestamp Authority Requires This One
		// Then Download Certificates and Convert them to use OpenSSL
		$want_cert = true;
		if ($want_cert) {
			$asn1[] = chr(0x01) . chr(0x01) . chr(0xff);
		}

		$len_3456 = strlen(implode('', array_slice($asn1, 3, 4)));
		$asn1[2] = chr(0x30) . chr($len_3456);

		$len_1234567 = strlen(implode('', array_slice($asn1, 1)));
		$asn1[0] = chr(0x30) . chr($len_1234567);

		$this->tsq = implode('', $asn1);

		return $this->tsq;

	}

	/**
	 * Send the Reuqest and get the Reply
	 */
	function reply() : ?string
	{
		if (empty($this->tsq)) {
			return false;
		}

		$this->tsr = null;

		$url = $this->url;
		$req = curl_init($url);
		curl_setopt($req, CURLOPT_AUTOREFERER, true);
		curl_setopt($req, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($req, CURLOPT_COOKIESESSION, false);
		curl_setopt($req, CURLOPT_CRLF, false);
		curl_setopt($req, CURLOPT_FAILONERROR, false);
		curl_setopt($req, CURLOPT_FILETIME, true);
		curl_setopt($req, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($req, CURLOPT_FORBID_REUSE, false);
		curl_setopt($req, CURLOPT_FRESH_CONNECT, false);
		curl_setopt($req, CURLOPT_HEADER, false);
		curl_setopt($req, CURLOPT_NETRC, false);
		curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($req, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($req, CURLINFO_HEADER_OUT,true);

		curl_setopt($req, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($req, CURLOPT_MAXREDIRS, 0);
		curl_setopt($req, CURLOPT_TIMEOUT, 60);

		curl_setopt($req, CURLOPT_USERAGENT, 'Edoceo/Radix/RFC3161 v0.23.198 (http://edoceo.com/dev/radix)');

		curl_setopt($req, CURLOPT_POST, true);
		curl_setopt($req, CURLOPT_POSTFIELDS, $this->tsq);
		curl_setopt($req, CURLOPT_HTTPHEADER, [ 'content-type: application/timestamp-query' ]);

		$res = curl_exec($req);
		$this->inf = curl_getinfo($req);

		curl_close($req);

		$code = $this->inf['http_code'];
		$type = strtolower(strtok($this->inf['content_type'], ';'));

		if ((200 == $code) && ('application/timestamp-reply' == $type)) {
			$this->tsr = $res;
		}

		return $this->tsr;
	}

}
