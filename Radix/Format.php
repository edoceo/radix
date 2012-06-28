<?php
/**
    @file
    @brief Radix Output Formatting Routines
    $Id: Format.php 2314 2012-06-27 05:11:14Z code@edoceo.com $

    @see http://en.wikipedia.org/wiki/Local_conventions_for_writing_telephone_numbers
    @package Radix
*/

/**
    A collection of Static Methods
*/
class Radix_Format
{
    /**
        niceDate
        @param $date - date to get formatted "nicely"
        @return some HTML to display.
    */
    static function niceDate($date)
    {
		// Determines how long ago the date was/is and then how to display it nicely
		// Not smart for determining time factors like time-zone, daylight-standard, leap-year, etc
		if (empty($date)) {
		    return 'Never';
		}
        $ts_cmp = strtotime($date);
        if (($ts_cmp <= 0) && ($date > 0) ) {
            $ts_cmp = $date;
        }
		$ts_now = time();

		// past or future doesn't matter, just the difference
		$span = abs($ts_now - $ts_cmp);

		$nice = null;
		$full = strftime('%a %b(%m) %d, %Y',$ts_cmp);
        if ($span <= 86400) { // Day
            $nice = 'Today';
            // return strftime('%H:%M',$ts_cmp);
        } elseif ($span <= 172800) { // 2 Days
            $nice = 'Yesterday';
        } elseif ($span <= 604800) { // 7 Days
            $nice = strftime('Last %a',$ts_cmp); // Day ##
        } elseif ($span <= 2592000) { // 30 Days
            $nice = strftime('%b %d',$ts_cmp); // Mon ##
        } elseif ($span <= 31536000) { // 365 Days
            $nice = strftime('%m/%d',$ts_cmp);
        } else {
            $nice = strftime('%m/%d/%y',$ts_cmp);
        }
        return '<span title="' . $full . '">' . $nice . '</span>';
    }
    /**
        Returns a nicely formatted time, like what Google Mail (and many others) do
        @return nicely formatted string
    */
    static function niceTime($time)
    {
		if (empty($time)) {
		    return 'Never';
		}
        $ts_cmp = strtotime($time);
        if (($ts_cmp <= 0) && ($time > 0) ) {
            $ts_cmp = $time;
        }
		$ts_now = time();

		// past or future doesn't matter, just the difference
		$span = $ts_now - $ts_cmp;
		$nice = null;
		$full = strftime('%a %b(%m) %d, %Y',$ts_cmp);
		if ($span <= 30) { // 30 Seconds
		    return 'a few seconds ago';
		}
		if ($span <= 300) { // Five Minutes
		    return 'a few minutes ago';
		}

        if ($span <= 3600) {// One Hour
            return 'about ' . floor($span / 300 * 5) . ' minutes ago';
        }
		
		return self::niceDate($time);
    }
    /**
        Formats a decimal number into 1024 base sizes to "binary prefix
        @see http://en.wikipedia.org/wiki/Binary_prefix
        @param $size large number
        @return formatted string like, 21MiB
    */
    static function niceSize($size,$fmt='%d %s')
    {
        $sizes = array('YiB', 'ZiB', 'EiB', 'PiB', 'TiB', 'GiB', 'MiB', 'KiB', 'B');
        $total = count($sizes);

        while($total-- && $size > 1024) $size /= 1024;
        return sprintf($fmt, $size,$sizes[$total]);

        // $ret = $x;
        // foreach (array('KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB') as $k) {
        //   if ($x > 1024) {
        //     $x = $x / 1024;
        //     $ret = intval($x) . ' ' . $k;
        //   } else {
        //     break;
        //   }
        // }
        // return $ret;
    }

    /**
        Formats a Telephone Number
        @param $p phone number
        @param $iso2 country ISO code for formatting
    */
    static function phone($p,$iso2=null)
    {
        $ext = null;
        $num = preg_replace('/[^x,\d]+/',null,$p);

        if (preg_match('/^(\d+)x(\d+)$/',$num,$m)) {
            $num = $m[1];
            $ext = $m[2];
        }
        switch (strlen($num)) {
        case 6:
            $ret = sprintf('%d-%d',substr($num,0,3),substr($num,3,3));
            break;
        case 7:
            $ret = sprintf('%d-%04d',substr($num,0,3),substr($num,3,4));
            break;
        case 8:
            $ret = sprintf('%04d-%04d',substr($num,0,4),substr($num,4,4));
            break;
        case 10:
            switch (strtolower($iso2)) {
            case 'us':
            default:
                // $ret = '(' . substr($num, 0, 3) . ') ' . substr($num, 3, 3) . "-".substr($num, 6, 4);
                $ret = sprintf('%d-%d-%04d',
                    substr($num,0,3),
                    substr($num,3,3),
                    substr($num,6,4));
                break;
            }
        default:
            $ret = $num;
        }
        // Prepare Return Value
        // $ret = $num;
        // Add Extension
        $ret.= (!empty($ext) ? "x$ext" : null);
        return $ret;

        /*
      // Phones with no extension
      switch(strlen($phone))
      {
        case 7:
          $ret = substr($phone, 0, 3)."-".substr($phone, 0, -3);
          break;
        case 8:
          $ret = substr($phone, 0, 4)."-".substr($phone, 0, -4);
          break;
        case 10:
          $ret = "(".substr($phone, 0, 3).") ".substr($phone, 3, 3)."-".substr($phone, 6, 4);
          break;
        default:
          $ret = $phone;
      }
      return $ret.$ext;
    */
        return $num;
    }

}
