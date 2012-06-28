<?php
/**
    @file
    @brief General Conversion Routeines
    $Id: Convert.php 2110 2012-03-20 01:53:01Z code@edoceo.com $

    Converts Decimal number to different alphabetic numeric base, like 2, 8, 16, 32, 62 or 73
    Converts Measurements
    Converts time sec2dhms()

    @author code@edoceo.com
    @copyright Edoceo, Inc.
    @package Radix

    @see http://javaconfessions.com/2008/09/convert-between-base-10-and-base-62-in.html
    @see http://kevin.vanzonneveld.net/techblog/article/create_short_ids_with_php_like_youtube_or_tinyurl/
    @see http://www.blooberry.com/indexdot/html/topics/urlencoding.htm

*/

/**
    Radix_Convert
*/

class Radix_Convert
{    
    //! List of Base Characters to convert to, scramble as necessary
    public static $base_list = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!$\'()*+,-._';
    // Have to manually move to $base_list if you want it, here for reference
    public static $safe_base = '0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ';

    //! helper for base62 conversion
    public static function toBase62($i)
    {
        return self::_dec2any(62,$x);
    }
    /**
        Convert from BaseX to Decimal numbers
        That is, Alphabetic-Integers (Antegers) to Integers
        @param $ant the Anteger value
        @param $base the length of $base_list to use, null==max
    */
    public static function ant2int($ant,$base=null) // int base, String number
    {
        $ant = strval($ant);
        $base = intval($base) ? intval($base) : strlen(self::$base_list);
        if ( (intval($base)) > strlen(self::$base_list) ) {
            return $ant;
        }
//        int iterator = number.length();
//        int returnValue = 0;
//        int multiplier = 1;
//
//        while( iterator > 0 ) {
//            $c = number.substring( iterator - 1, iterator );
//            returnValue = returnValue + ( baseDigits.indexOf( $c ) * multiplier );
//            multiplier = multiplier * base;
//            --iterator;
//        }
//        return returnValue;
        $i = strlen($ant);
        $r = 0;
        $m = 1;
        while ($i > 0) {
            // Current Value to Read
            $c = substr($ant,$i-1,1); //   number.substring( iterator - 1, iterator );
            // echo "+Cur: $c\n";
            // Map to Base
            $b = strpos(self::$base_list,$c);
            // echo "+Map: $b\n";
            $r = $r + ( $b * $m);
            $m = $m * $base;
            --$i;
        }
        return $r;
    }
    /**
        Convert from Decimal to BaseX Anteger numbers 
        @param $int the Integer value
        @param $base the length of $base_list to use, null==max
    */
    public static function int2ant($int,$base=null)
    {
        $base = intval($base) ? intval($base) : strlen(self::$base_list);
        if ( (intval($base)) > strlen(self::$base_list) ) {
            return $int;
        }
//        String tempVal = decimalNumber == 0 ? "0" : "";
//        int mod = 0;
//
//        while( decimalNumber != 0 ) {
//            mod = decimalNumber % base;
//            tempVal = baseDigits.substring( mod, mod + 1 ) + tempVal;
//            decimalNumber = decimalNumber / base;
//        }
//
//        return tempVal;
        // $ret = strval($dec);
        $ant = null;
        $m = 0;
        while ($int > 0) {
            $m = $int % $base;
            //echo "$m\n";
            //if ($m != 0) {
                $x = substr(self::$base_list,$m,1);
                $ant = ($x  . $ant );
            //}
            $int = $int / $base;
        }
        // strip leading conceptual-zeros
        $pat = sprintf('/^%s+/',self::$base_list[0]);
        return preg_replace($pat,null,$ant);
    }
    /**
        Convert Metric to Imperial
        @param float Metric
        @return float Imperial
    */
    public static function c2f($m) { return ( (9/5*$m) + 32 ); }
    public static function cl2pt($m) { return ($m * 0.0211337642); }
    public static function cm2in($m) { return ($m * 0.0393700787); }
    public static function l2qt($m) { return ($m * 1.05668821); }
    public static function m2ft($m) { return ($m * 3.2808399); }
    public static function km2mi($m) { return ($m * 0.621371192); }
    /**
        Convert Imperial to Metric
    */
    public static function ft2m($ft) { return ($ft * 0.3048); }
    public static function in2cm($in) { return ($in * 2.54); }
    /**
        Convert Imperial to Imperial
        @param Imperial Measure
        @param other Imperial Measure
    */
    public static function f2c($m) { return ( 5 / 9 * ( $f + 32 ) ); }
    public static function ft2in($ft) { return ($ft * 12); }
    public static function ft2mi($ft) { return ($ft * 0.000189393939); }
    public static function ft2yd($ft) { return ($ft * 0.333333); }
    public static function g2pt($g) { return ($g * 8); }
    public static function g2qt($g) { return ($g * 4); }
    public static function mi2ft($mi) { return ($mi * 5280); }
    public static function mi2yd($mi) { return ($mi * 1760); }
    public static function mi2fur($mi) { return ($mi * 8); }
    public static function mi2ch($mi) { return ($mi * 80); }
    public static function pt2qt($pt) { return ($pt * 0.5); }
    public static function qt2pt($qt) { return ($qt * 2); }
    /**
        Time Routines
        @param $s number of seconds
        @param $j join with this character
        @return %d d 
    */
    function sec2dhms($s,$j=' ')
    {
        $r = array();
        foreach (array('d'=>86400,'h'=>3600,'m'=>60,'s'=>1) as $k=>$v) {
            if ($s >= $v) {
                $r[] = floor($s/$v).$k;
                $s = $s % $v;
            }
        }
        return $r;
    }
}

/*
if ($scale == "celcius") {
    print "<table border><tr><th colspan=2> Conversion Results</th></tr><tr><td>$degree</td><td>celsius</td></tr>";
    
    $c_2_f = $degree*9/5+32;
    
    print "<tr><td>$c_2_f</td><td>fahrenheit</td></tr>";
    
    $c_2_k = $degree+273.15;
    
    print "<tr><td>$c_2_k </td><td>kelvin</td></tr>";
    
    $c_2_r = $c_2_f+459.6;

    print "<tr><td>$c_2_r</td><td>rankine</td></tr></table>";
}
*/

//     public static String toBase62( int decimalNumber ) {
//         return fromDecimalToOtherBase( 62, decimalNumber );
//     }
// 
//     public static String toBase36( int decimalNumber ) {
//         return fromDecimalToOtherBase( 36, decimalNumber );
//     }
// 
//     public static String toBase16( int decimalNumber ) {
//         return fromDecimalToOtherBase( 16, decimalNumber );
//     }
// 
//     public static String toBase8( int decimalNumber ) {
//         return fromDecimalToOtherBase( 8, decimalNumber );
//     }
// 
//     public static String toBase2( int decimalNumber ) {
//         return fromDecimalToOtherBase( 2, decimalNumber );
//     }
// 
//     public static int fromBase62( String base62Number ) {
//         return fromOtherBaseToDecimal( 62, base62Number );
//     }
// 
//     public static int fromBase36( String base36Number ) {
//         return fromOtherBaseToDecimal( 36, base36Number );
//     }
// 
//     public static int fromBase16( String base16Number ) {
//         return fromOtherBaseToDecimal( 16, base16Number );
//     }
// 
//     public static int fromBase8( String base8Number ) {
//         return fromOtherBaseToDecimal( 8, base8Number );
//     }
// 
//     public static int fromBase2( String base2Number ) {
//         return fromOtherBaseToDecimal( 2, base2Number );
//     }
