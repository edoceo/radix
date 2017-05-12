<?php
/**

*/

namespace Edoceo\Radix\Config;

class INI
{
	/**
		@param $f File
		@return Config Array
	*/
	static function parse($f)
	{
		// App Defaults
		$ini_file = $f;
		if (!is_file($ini_file)) {
			return(0);
		}

		$ini_data = parse_ini_file($ini_file, true);
		$ini_data = array_change_key_case($ini_data);

		// Reduce to Singular Values
		foreach ($ini_data as $k0=>$opt) {
			foreach ($opt as $k1=>$x) {
				if (is_array($_ENV[$k0][$k1])) {
					$ini_data[$k0][$k1] = array_pop($ini_data[$k0][$k1]);
				}
			}
		}

		if (!empty($ini_data['application']['zone'])) {
			ini_set('date.timezone', $ini_data['application']['zone']);
			date_default_timezone_set($ini_data['application']['zone']);
		}

	}

}
