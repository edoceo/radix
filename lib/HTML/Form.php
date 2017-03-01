<?php
/**
    @file
    @brief Draws an HTML Calendar
*/

namespace Edoceo\Radix\HTML;

class Form
{
	private static $_idx = 0;
	private static $_use_list = array();

	/**
		Returns the HTML
	*/
	private static function _element($arg, $opt = null)
	{
		// Merge Options
		if (is_array($opt)) $arg = array_merge($arg, $opt);

		// Update Arguments
		$arg['value'] = htmlspecialchars($arg['value'], ENT_QUOTES, 'utf-8', false);

		$ret = '<input ' . self::_element_arg($arg) . '>';

		return $ret;
	}

	private static function _element_arg($arg)
	{
		$ret = null;
		if (empty($arg['id'])) $arg['id'] = self::_element_nid($arg['name']);
		ksort($arg);
		foreach ($arg as $k=>$v) {
			$ret.= sprintf('%s="%s" ', $k, $v);
		}
		return trim($ret);
	}

	/**
	*/
	private static function _element_nid($n)
	{
		if (!empty(self::$_use_list[$n])) {
			$n = sprintf('%s_%d', $n, self::$_idx);
		}
		self::$_idx++;
		return $n;
	}

	/**
		@param $nid Name/ID
		@param $val Value
		@param $opt Options, LIke Placeholder
		@return HTML
	*/
	static function button($nid, $val, $opt=null)
	{
		$arg = array(
			'type'  => 'button',
			'id'    => $nid,
			'name'  => $nid,
			'value' => $val,
		);
		return self::_element($arg, $opt);
	}

	/**
		@return HTML
	*/
	static function date($n,$v, $opt = null)
	{
		$arg = array(
			'type'  => 'date',
			'id'    => $n,
			'name'  => $n,
			'value' => $v,
		);
		return self::_element($arg, $opt);
	}

	/**
		@return HTML
	*/
	static function checkbox($n,$v, $opt = null)
	{
		$arg = array(
			'type'  => 'checkbox',
			'id'    => $n,
			'name'  => $n,
			'value' => $v,
		);
		return self::_element($arg, $opt);
	}

	/**
		@param $nid Name/ID
		@param $val Value
		@param $opt Options, LIke Placeholder
		@return HTML
	*/
	static function hidden($nid, $val, $opt=null)
	{
		$arg = array(
			'type'  => 'hidden',
			'id'    => $nid,
			'name'  => $nid,
			'value' => $val,
		);
		return self::_element($arg, $opt);
	}

	/**
		@return HTML
		@see https://blog.isotoma.com/2012/03/html5-input-typenumber-and-decimalsfloats-in-chrome/
	*/
	static function number($n,$v, $opt = null)
	{
		$arg = array(
			'type'  => 'number',
			'id'    => $n,
			'name'  => $n,
			'step'  => 'any',
			'value' => $v,
		);
		return self::_element($arg, $opt);
	}

	/**
		@return HTML
	*/
	static function password($n,$v, $opt = null)
	{
		$arg = array(
			'type'  => 'password',
			'id'    => $n,
			'name'  => $n,
			'value' => $v,
		);
		return self::_element($arg, $opt);
	}

	/**
		@return HTML
	*/
	static function radio($n,$v, $opt = null)
	{
		$arg = array(
			'type'  => 'radio',
			'id'    => $n,
			'name'  => $n,
			'value' => $v,
		);
		return self::_element($arg, $opt);
	}

	/**
		@param $nid Name and ID
		@param $def Default Value
		@param $list List of Values
		@param $opt Arguments
		@return HTML
	*/
	static function select($nid, $def, $list=null, $opt=null)
	{
		// $arg = self::_element_arg($arg);
		if (null == $opt) {
			$opt = array();
		} elseif (!is_array($opt)) {
			$opt = array($opt);
		}
		$opt['id'] = $nid;
		$opt['name'] = $nid;
		ksort($opt);

		$ret = '<select';
		foreach ($opt as $k=>$v) {
			$ret.= sprintf(' %s="%s"', $k, $v);
		}
		$ret.= '>';

		if (!empty($list) && is_array($list)) {
			foreach ($list as $k=>$v) {
				$ret.= '<option ';
				if ($def == $k) $ret.= 'selected ';
				$ret.= 'value="' . $k . '">' . $v . '</option>';
			}
		}
		$ret.= '</select>';

		self::$_idx++;
		self::$_use_list[$nid] = true;

		return $ret;
	}

	/**
		@return <input type="submit"
	*/
	static function submit($n, $v, $opt = null)
	{
		$arg = array(
			'type'  => 'submit',
			'id'    => $n,
			'name'  => $n,
			'value' => $v,
		);
		return self::_element($arg, $opt);
	}

	/**
		@return HTML
	*/
	static function text($n,$v, $opt = null)
	{
		$arg = array(
			'type'  => 'text',
			'id'    => $n,
			'name'  => $n,
			'value' => $v,
		);
		return self::_element($arg, $opt);
	}

	/**
		@param $n Name/ID
		@param $v Value
		@param $opt Array of Attriutes
		@return HTML
	*/
	static function textarea($n,$v, $opt = null)
	{
		if (empty($opt)) $opt = array();

		$arg = array(
			'id' => self::_element_nid($n),
			'name'=> self::_element_nid($n),
		);
		$opt = array_merge($arg, $opt);

		$r = '<textarea ' . self::_element_arg($opt) . '>';
		$r.= htmlspecialchars($v, ENT_QUOTES, 'utf-8', false);
		$r.= '</textarea>';

		return $r;
	}

	static function time($n,$v, $opt = null)
	{
		$arg = array(
			'type'  => 'time',
			'id'    => $n,
			'name'  => $n,
			'value' => $v,
		);
		return self::_element($arg, $opt);
	}

	/**
		@param $chk false|string to test
		@return HTML String on creation, true on success
	*/
	static function csrf($chk=false)
	{
		if (empty($_SESSION['_radix']['_csrf'])) {
			$_SESSION['_radix']['_csrf'] = array();
		}
		// @todo Somethign
	}
}
