<?php
/**
	Radix ULID Generator

	@see https://github.com/alizain/ulid/
	@see https://github.com/bk/Data-ULID/blob/master/lib/Data/ULID.pm
	@see http://php.net/manual/en/function.base-convert.php
	@see https://github.com/bbars/utils/blob/master/php-base32-encode-decode/Base32.php
*/

namespace Edoceo\Radix;

class ULID
{
	// @see https://en.wikipedia.org/wiki/Base32
	const ENCODING = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
	const ENCODING_LEN = 32;

	// const TIME_MAX = 281474976710655;
	const TIME_LEN = 10;

	const RAND_LEN = 16;

	/**
		@param $tms Timestamp, in milliseconds
		@param $max Max Length, in Characters
	*/
	static function encodeTime($tms=null, $len=10)
	{
		if (empty($tms)) {
			$tms = microtime(true);
			$tms = floor($tms * 1000);
		}

		$ret = array();

		for ($idx = $len; $idx > 0; $idx--) {

			$mod = $tms % self::ENCODING_LEN;
			$chr = substr(self::ENCODING, $mod, 1);

			array_unshift($ret, $chr);
			$tms = ($tms - $mod) / self::ENCODING_LEN;
		}

		return implode('', $ret);
	}

	/**
		Encode a Random Length of
		@param $max The Max Length of the Random Data in Bytes
	*/
	static function encodeRandom($max=16)
	{
		$rnd = 0;
		$ret = array();

		for ($idx=0; $idx < $max; $idx++) {

			$rnd0 = mt_rand() / mt_getrandmax();
			$rnd1 = floor(self::ENCODING_LEN * $rnd0);
			$chr = substr(self::ENCODING, $rnd1, 1);

			array_unshift($ret, $chr);

		}

		return implode('', $ret);
	}

	/**
		Generate a ULID and return in Base32
	*/
	static function generate($tms=null, $r=null)
	{
		$t = self::encodeTime($tms);
		$r = self::encodeRandom();
		return sprintf('%s%s', $t, $r);
	}

}
