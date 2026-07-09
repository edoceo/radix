<?php
/**
 * Wrapper for libsodium
 *
 * SPDX-License-Identifier: MIT
 */

namespace Edoceo\Radix;

class Sodium
{
	private $pk;

	private $sk;

	/**
	 * Decrypt Data with Secret-Key from Public-Key
	 * @param string $ncbox Binary String Data of NONCE . CRYPT Thing
	 * @param string $sk Secret Key of Recipient
	 * @param string $pk Public Key of Sender
	 * @return string the decrypted data from the box (NONCE . CRYPT)
	 */
	static function decrypt(string $ncbox, string $sk, string $pk)
	{
		if (strlen($sk) == 43) {
			$sk = self::b64decode($sk);
		}

		if (strlen($pk) == 43) {
			$pk = self::b64decode($pk);
		}

		$kp    = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $pk);
		$nonce = substr($ncbox, 0, SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt = substr($ncbox, SODIUM_CRYPTO_BOX_NONCEBYTES);
		$plain = sodium_crypto_box_open($crypt, $nonce, $kp);

		return $plain;

	}

	/**
	 * Encrypt Plain from Public-Key to Secret-Key
	 * @param string $sk Secret Key for Sender
	 * @param string $pk Public Key of Recipient
	 * @return string (binary) NONCE . CRYPT
	 */
	static function encrypt(string $plain, string $sk, string $pk)
	{
		if (strlen($sk) == 43) {
			$sk = self::b64decode($sk);
		}

		if (strlen($pk) == 43) {
			$pk = self::b64decode($pk);
		}

		$kp    = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $pk);
		$nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt = sodium_crypto_box($plain, $nonce, $kp);
		$box   = $nonce . $crypt;

		return $box;

	}

	/**
	 *
	 */
	function __construct(string $pk, ?string $sk=null)
	{
		$this->setPublicKey($pk);
		if ( ! empty($sk)) {
			$this->setSecretKey($sk);
		}

		// Determine Things by strlen?

	}

	// function createEncryptPair($seed);
	// function createSignPair($seed);

	protected function setPublicKey(string $pk)
	{
		$this->pk = self::b64decode($pk);
	}

	protected function setSecretKey(string $sk)
	{
		$this->sk = self::b64decode($sk);
	}

	/**
	 * @param string $crypt the data to decrypt
	 * @param string $nonce the Random Bytes
	 * @param string $spkey senders public key
	 */
	function decrypt_x(string $crypt, string $nonce, string $spkey) : string
	{
		$crypt = self::b64decode($crypt);

		// Get the Client Somethign?
		$rsk = $this->sk;
		$spk = self::b64decode($spkey);
		$rskey = sodium_crypto_box_keypair_from_secretkey_and_publickey($rsk, $spk);
		$nonce = self::b64decode($nonce);
		$plain = sodium_crypto_box_open($crypt, $nonce, $rskey);

		return $plain;

	}

	/**
	 * @param string $plain the Plain Text
	 * @param $rpk The Recipient Public Key
	 * @return string
	 */
	function encrypt_x($plain, ?string $rpk) : string
	{
		if (is_array($plain)) {
			$plain = json_encode($plain);
		} elseif (is_object($plain)) {
			$plain = json_encode($plain);
		}

		// Sender Secret Key
		$ssk = $this->sk;

		// Recipient Public Key
		$rpk = self::b64decode($rpk);
		if (empty($rpk)) {
			$rpk = $this->pk;
		}

		$key = sodium_crypto_box_keypair_from_secretkey_and_publickey($ssk, $rpk);
		$nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt = sodium_crypto_box($plain, $nonce, $key);

		$b64_nonce = self::b64encode($nonce);
		$b64_crypt = self::b64encode($crypt);

		$ret = sprintf('%s.%s', $b64_nonce, $b64_crypt);

		return $ret;
	}

	/**
	 *
	 */
	function sign($plain)
	{
		// Make sure the keys are the correct type
		// $signed = sodium_crypto_sign($this->sk);
	}

	/**
	 * base64 decode helper
	 */
	static function b64decode($x)
	{
		if (preg_match('/[\w\-\+\=]+/', $x)) {
			return sodium_base642bin($x, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
		}
		return $x;
	}

	/**
	 * base64 encode helper
	 */
	static function b64encode($x)
	{
		return sodium_bin2base64($x, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
	}

}
