<?php
/**
	Interface to Torrent Cloud
*/

namespace Edoceo\Radix\Service;

// use Edoceo\Radix\Curl;

class TorrentCloud
{
	private $_base;
	private $_doc;

	// public $wget = 'wget --background --execute robots=off --limit-rate=128k --mirror --no-host-directories --no-parent --no-verbose --progress=dot:mega';
	public $wget = 'wget --mirror --no-host-directories --no-parent --progress=dot:mega';

	function __construct($u, $p)
	{
		$this->_base = sprintf('https://%s:%s@www.torrentcloud.eu/cp/', $u, $p);
		// $this->_base = sprintf('https://%s:%s@www.torrentcloud.eu/cpv2/', $u, $p);
	}

	/**
		@return Array of Torrent descriptors
	*/
	function getTorrents()
	{
		if (empty($this->_doc)) {
			$buf = $this->_get($this->_base);
			$this->_doc = $this->_dom($buf);
		}

		$ret = array();

		$node_list = $this->_doc->xpath('//*[@id="content"]/table/tr');
		foreach ($node_list as $node) {
			if ($node['class'] != 'enl') continue;

			$rec = array();
			$rec['code'] = strval($node->td[0]->b);
			// $rec['note'] = trim(html_entity_decode(strip_tags( $node->td[0]->div->asXML() )));
			$rec['note'] = strip_tags($node->td[0]->div->asXML());
			$rec['note'] = str_replace(array(chr(0xc2), chr(0xa0)), null, $rec['note']);
			$rec['note'] = trim($rec['note']);
			$rec['stat'] = trim(html_entity_decode(strip_tags ($node->td[1]->asXML() )));
			$rec['slot'] = intval(trim($node->td[2]));

			// Start or Stop
			$rec['link_exec'] = strval($node->td[3]->a[0]['href']);
			$rec['link_stop'] = strval($node->td[3]->a[0]['href']);
			$rec['link_magnet'] = strval($node->td[3]->a[1]['href']);
			// Parse Magnet?
			if (preg_match('/^magnet:\?(.+)$/', $rec['link_magnet'], $m)) {
				$arg = null;
				parse_str($m[1], $arg);
				if (!empty($arg['dn'])) {
					$rec['name'] = html_entity_decode(trim($arg['dn']));
				}
			}

			if (preg_match('/btih:([0-9a-f]+)/', $rec['link_magnet'], $m)) {
				$rec['hash'] = $m[1];
			} else {
				die("NO MAG");
			}

			$rec['link_delete'] = strval($node->td[3]->a[2]['href']);
			// May or May not be Present
			$rec['link_download'] = strval($node->td[3]->a[3]['href']);

			$ret[ $rec['code'] ] = $rec;
		}

		// Sort
		uasort($ret, function($a, $b) {
			if ($a['slot'] == $b['slot']) {
				return strcmp($a['name'], $b['name']);
			}
			return ($a['slot'] < $b['slot']);
		});

		return $ret;
	}

	/**
		Magnet Link
	*/
	function addMagnet($mag)
	{
		if (!preg_match('/^magnet:.*dn=([^&]+)&/', $mag, $m)) {
			die("Invalid Magnet\n");
		}

		// print_r($m);
		$ch = new \Edoceo\Radix\Curl($this->_base);
		$ch->opt(CURLOPT_POSTFIELDS, array(
			'torrenturl' => null,
			'torrentmagnet' => $mag,
			'name' => uniqid(),
			'slots' => '0',
			'cmdSend' => 'Run',
		));
		$buf = $ch->exec();
		// Not Sure how to Check for Errors
		return $buf;
	}

	function addTorrent()
	{

	}

	function delete($l)
	{
		$buf = $this->_get($l);
		$this->_doc = $this->_dom($buf);
		print_r($this->_doc->div);
	}

	function getDownload($l)
	{
		$buf = $this->_get($l);
		$this->_doc = $this->_dom($buf);

		$x = $this->_doc->xpath('//*[@id="content"]/fieldset[2]');
		if (is_array($x) && (1 == count($x))) {
			$node = $x[0];

			$link = $node->a['href'];

			return "$link/";
		}

	}

	/**

	*/
	function exec($l)
	{
		$buf = $this->_get($l);
		$this->_doc = $this->_dom($buf);
		// print_r($this->_doc->asXML());
	}

	/**
		Status
	*/
	function stat()
	{
		if (empty($this->_doc)) {
			$buf = $this->_get();
			$this->_doc = $this->_dom($buf);
		}

		$node_list = $this->_doc->xpath('//*[@id="site"]/div[2]');
		// print_r($node_list);
		$ret = array(
			'full' => intval($node_list[0]->b[0]),
			'used' => intval($node_list[0]->b[1]),
		);

		return $ret;
	}

	/**
		Stop the Torrent
	*/
	function stop($l)
	{
		$ret = $this->_get($l);
		return $ret;
	}

    /**
        Turn the Response to XML
    */
	private function _dom($buf)
	{
		$buf = preg_replace('/>\s+</ms', '><', $buf);
        $dom = new \DOMDocument('1.0','UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->strictErrorChecking = false;
        $dom->validateOnParse = false;
        $er = error_reporting(0);
        $dom->loadHTML($buf);
        error_reporting($er);

		$xml = simplexml_import_dom($dom->documentElement);

		return $xml;

	}

	/**
		// https://www.torrentcloud.eu/cp/?_LIST_TORRENTS&SEARCH=
	*/
	private function _get($l=null)
	{

		$ch = new \Edoceo\Radix\Curl($this->_base . $l);
		$ret = $ch->exec();

		return $ret;
	}
}
