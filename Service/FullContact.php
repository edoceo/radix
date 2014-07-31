<?php
/**
	Interact with the FullContact Service
*/

namespace Radix\Service;

class FullContact extends \Radix\Service
{
	const API_BASE = 'https://api.fullcontact.com/v2';

	/**
		@param $key Your API Key
	*/
	function __construct($key)
	{
		$this->_key = $key;
	}

	/**
		Normalise the Name
		@param $x The Name String
		@return Array
	*/
	function getIcon() {
	
	}

	/**
		Find Person by Email
		@param $e Email Address
		@return Array
	*/
	function getPerson($e)
	{
		$uri = self::API_BASE . '/person.json?email=' . rawurlencode($e) . '&apiKey=' . $this->_key;
		$res = $this->_get($uri);
		return $res;
	}

	/**
		Normalise the Location
		@param $x The Location String
		@return Array
	*/
	function normalizeLocation($x)
	{
		$uri = self::API_BASE . '/address/locationNormalizer.json?place=' . rawurlencode($x) . '&apiKey=' . $this->_key;
		$res = $this->_get($uri);
		return $res;
	}

	/**
		Normalise the Name
		@param $x The Name String
		@return Array
	*/
	function normalizeName($x)
	{
		$uri = self::API_BASE . '/name/normalizer.json?q=' . rawurlencode($x) . '&apiKey=' . $this->_key;
		$res = $this->_get($uri);
		return $res;
	}

}