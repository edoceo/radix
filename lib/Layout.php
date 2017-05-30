<?php
/**
	Tools for working with Layouts
	Add Scripts and Styles
*/

namespace Edoceo\Radix;

class Layout
{
	private static $_script_head;
	private static $_script_tail;

	private static $_style_head;

	/**
		@param $src Source Link or Source Code
		@param $pos Position, 'head' or null
	*/
	static function addScript($src, $pos='tail')
	{
		// Init
		if (empty(self::$_script_head)) {
			self::$_script_head = array();
		}

		if (empty(self::$_script_tail)) {
			self::$_script_tail = array();
		}

		$k = self::_addScript_Kind($src);

		switch ($pos) {
		case 'head':
			self::$_script_head[] = array(
				'kind' => $k,
				'data' => $src,
			);
			break;
		case 'tail':
		default:
			self::$_script_tail[] = array(
				'kind' => $k,
				'data' => $src,
			);
			break;
		}

	}

	/**
		Determines if the source is Code, a Link or a full HTML Node

		If $src starts with 'h' or '/', it's a link
		If it it starts with '<' it's a Node
		Else Code

		@param $src The Source String
		@return "code" | "link" | "node"
	*/
	private static function _addScript_Kind($src)
	{
		$src = trim($src);
		$clt = null; // Code or Link or full Tag

		switch (substr($src, 0, 1)) {
		case 'h':
		case '/':
			$clt = 'link';
			break;
		case '<':
			$clt = 'node';
			break;
		default:
			$clt = 'code';
			break;
		}

		return $clt;

	}

	/**
		Return the JS to Render
	*/
	static function getScript($pos='tail')
	{
		$ret = array();

		switch ($pos) {
		case 'head':

			if (empty(self::$_script_head)) {
				return null;
			}

			foreach (self::$_script_head as $i => $s) {
				$ret[] = self::_getScript_HTML($s);
			}

			break;

		case 'tail':
		default:

			if (empty(self::$_script_tail)) {
				return null;
			}

			foreach (self::$_script_tail as $i => $s) {
				$ret[] = self::_getScript_HTML($s);
			}

			break;
		}

		return trim(implode("\n", $ret));

	}

	/**
		@param $s a Script Descriptor
		@return $s as HTML
	*/
	private static function _getScript_HTML($s)
	{
		switch ($s['kind']) {
		case 'code':
			return sprintf('<script>%s</script>', $s['data']);
		case 'link':
			return sprintf('<script src="%s"></script>', $s['data']);
		case 'node':
			return $s['data'];
		}
	}

	/**
		Add CSS as Link or Text
	*/
	static function addStyle($css)
	{
		// Init
		if (empty(self::$_style_head)) {
			self::$_style_head = array();
		}

		$k = self::_addStyle_Kind($css);

		self::$_style_head[] = array(
			'kind' => $k,
			'data' => $css,
		);

	}

	/**
		Determines if the source is Code, a Link or a full HTML Node

		If $src starts with 'http' or '/', it's a link
		If it it starts with '<' it's a Node
		Else Code

		@param $src The Source String
		@return "code" | "link" | "node"
	*/
	private static function _addStyle_Kind($src)
	{
		$src = trim($src);
		$clt = null; // Code or Link or full Tag

		$c4 = substr($src, 0, 4);

		// This is too foolish
		switch ($c4) {
		case 'http':
			return 'link' ;
		case '<sty':
			return 'node';
		}

		$c2 = substr($src, 0, 2);
		switch ($c2) {
		case '//':
			return 'link';
		case '/*':
			return 'code';
		}

		$c1 = substr($src, 0, 1);
		switch ($c1) {
		case '/':
			return 'link';
			break;
		}

		return 'code';

	}

	/**
		@return the Style Sheets and Code
	*/
	static function getStyle()
	{
		$ret = array();

		if (empty(self::$_style_head)) {
			return null;
		}

		foreach (self::$_style_head as $i => $s) {
			$ret[] = self::_getStyle_HTML($s);
		}

		return trim(implode("\n", $ret));
	}

	/**
		@param $s a Style Descriptor
		@return $s as HTML
	*/
	private static function _getStyle_HTML($s)
	{
		switch ($s['kind']) {
		case 'code':
			return sprintf('<style>%s</style>', $s['data']);
		case 'link':
			return sprintf('<link href="%s" rel="stylesheet">', $s['data']);
		case 'node':
			return $s['data'];
		}
	}

}
