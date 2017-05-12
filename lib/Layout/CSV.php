<?php
/**
	CSV Output Tool
*/

namespace Edoceo\Radix\Layout;

class CSV
{
	protected $_data;
	protected $_cols;

	/**
		@param $data Indexed Array of hash-Arrays
		@param $cols Column Spec
	*/
	function __construct($data=null, $cols=null)
	{
		if (!is_array($data)) {
			throw new \Exception('Invalid data to CSV');
		}
		if (is_array($cols)) {
			$this->_cols = $cols;
		} else {
			$this->_cols = array_fill(0, count($data[0]), function() {
				return 'Col';
			});
		}
	}

	/**
		Output HTTP Header
	*/
	function output_head($fn=null, $dl=true)
	{
		if (empty($fn)) {
			$fn = basename($_SERVER['REQUEST_URI']);
		}
		if (empty($fn)) {
			$fn = 'csv_data';
		}

        header('Cache-Control: must-revalidate');
        header('Content-Description: CSV Download');
        // Force Download
        if ($dl) {
			header(sprintf('Content-Disposition: attachment; filename="%s"', $fn));
		}
		header('Content-Transfer-Encoding: binary');
        header('Content-Type: text/csv');
        // header('Pragma: no-cache');
	}

	/**
		Output the CSV
		@param fs Field Separator
	*/
	public function output($fs=',')
	{

        $fh = tmpfile();
        fputcsv($fh, array_values($this->_cols));

		foreach ($this->_data as $rec) {

			$out = array();

			foreach ($this->_cols as $k => $x) {
				$out[] = $rec[$k];
			}

			fputcsv($fh, array_values($out), $fs);
		}

		fseek($fh, 0);
		fpassthru($fh);

	}

}
