<?php
/**
    @file
    @brief Outputs a Codabar, Code25, Code39, Code128,  Barcode

    @author Edoceo, Inc
    @copyright Edoceo, Inc, released under BSD license
    
    @see http://www.davidscotttufts.com/2009/03/31/how-to-create-barcodes-in-php/
    @see http://www.adams1.com/pub/russadam/128code.html


*/

class radix_image_barcode
{
    // http://en.wikipedia.org/wiki/Two-out-of-five_code
    protected static $_code_ni25_list;
    
    // @see http://en.wikipedia.org/wiki/Interleaved_2_of_5
    protected static $_code_i25_list = array(
        
    );
    // Must not change order of array elements as the checksum depends on the array's key to validate final code
    // http://en.wikipedia.org/wiki/Code_128
    protected static $_code_128_list = array(
        ' ' =>'212222',
        '!' =>'222122',
        '\' '=>'222221',
        '#' =>'121223',
        '$' =>'121322',
        '%' =>'131222',
        '&' =>'122213',
        '\''=>'122312',
        '(' =>'132212',
        ')' =>'221213',
        '*' =>'221312',
        '+' =>'231212',
        ',' =>'112232',
        '-' =>'122132',
        '.' =>'122231',
        '/' =>'113222',
        '0' =>'123122',
        '1' =>'123221',
        '2' =>'223211',
        '3' =>'221132',
        '4' =>'221231',
        '5' =>'213212',
        '6' =>'223112',
        '7' =>'312131',
        '8' =>'311222',
        '9' =>'321122',
        ':' =>'321221',
        ';' =>'312212',
        '<' =>'322112',
        '=' =>'322211',
        '>' =>'212123',
        '?' =>'212321',
        '@' =>'232121',
        'A' =>'111323',
        'B' =>'131123',
        'C' =>'131321',
        'D' =>'112313',
        'E' =>'132113',
        'F' =>'132311',
        'G' =>'211313',
        'H' =>'231113',
        'I' =>'231311',
        'J' =>'112133',
        'K' =>'112331',
        'L' =>'132131',
        'M' =>'113123',
        'N' =>'113321',
        'O' =>'133121',
        'P' =>'313121',
        'Q' =>'211331',
        'R' =>'231131',
        'S' =>'213113',
        'T' =>'213311',
        'U' =>'213131',
        'V' =>'311123',
        'W' =>'311321',
        'X' =>'331121',
        'Y' =>'312113',
        'Z' =>'312311',
        '[' =>'332111',
        '\\'=>'314111',
        ']' =>'221411',
        '^' =>'431111',
        '_' =>'111224',
        '\`'=>'111422',
        'a' =>'121124',
        'b' =>'121421',
        'c' =>'141122',
        'd' =>'141221',
        'e' =>'112214',
        'f' =>'112412',
        'g' =>'122114',
        'h' =>'122411',
        'i' =>'142112',
        'j' =>'142211',
        'k' =>'241211',
        'l' =>'221114',
        'm' =>'413111',
        'n' =>'241112',
        'o' =>'134111',
        'p' =>'111242',
        'q' =>'121142',
        'r' =>'121241',
        's' =>'114212',
        't' =>'124112',
        'u' =>'124211',
        'v' =>'411212',
        'w' =>'421112',
        'x' =>'421211',
        'y' =>'212141',
        'z' =>'214121',
        '{' =>'412121',
        '|' =>'111143',
        '}' =>'111341',
        '~' =>'131141',
        'DEL'=>'114113',
        'FNC 3'=>'114311',
        'FNC 2'=>'411113',
        'SHIFT'=>'411311',
        'CODE C'=>'113141',
        'FNC 4'=>'114131',
        'CODE A'=>'311141',
        'FNC 1'=>'411131',
        'Start A'=>'211412',
        'Start B'=>'211214',
        'Start C'=>'211232',
        'Stop'=>'2331112'
    );
    /**
        Encode the Text to a Barcode Code Ouputs
    */
    public static function code25($text)
    {
        
    }
    /**
    */
    public static function code128($text)
    {
        if (!is_string($text)) {
            throw new Exception('Invalid Text');
        }

        // int => string of friendly names
        $code_key_set = array_keys(self::$_code_128_list);
        // print_r($code_keys);
        // Map goes array(string => int)
        $code_key_map = array_flip($code_key_set);
        // print_r($code_values);

        $c = strlen($text);

        $chk = $code_key_map['Start B']; // Mod 103 Checksum Start?
        // echo "$chk=$chk\n";
        $ret = '';

        for($i=0; $i<$c; $i++) {
            $chr = $text[$i];
            $ret .= self::$_code_128_list[$chr]; // What if it's not in this list?
            $val = $code_key_map[$chr] * ($i+1);
            // echo $code_key_map[$chr] . "\n"; // "$chk=$chk\n";
            $chk += $val;
            // echo "$val => $chk\n";
        }
        $div = ($chk % 103);
        // echo "$div\n";
        // $code_string .= $code_array[$code_keys[($chksum - (intval($chksum / 103) * 103))]];
        // Append a Checksum Code?
        // $ret .= self::$_code_128_list[$code_key_set[ ($chk - (intval($chk / 103) * 103)) ]];
        // Prepend / Append these Known Values
        $ret .= self::$_code_128_list[ $code_key_set[ $div ]];

        return (self::$_code_128_list['Start B'] . $ret . self::$_code_128_list['Stop']);

    }
    /**
        Codeabar, Ames Code, NW-7, Monarch, Code 2 of 7, Rationalized Codabar, ANSI/AIM BC3-1995 or USD-4.
        @see http://en.wikipedia.org/wiki/Codabar
        @param $text
    */
    public static function codabar($text)
    {
        
    }
    
    /**
        @param $code is the list of 1, 2, 3, 4 numbers that should be encoded
    */
    public static function image($code,$size=64)
    {
        // if(strtolower($orientation) == "horizontal") {
        //     $img_width = $code_length;
        //     $img_height = $size;
        // } else {
        //     $img_width = $size;
        //     $img_height = $code_length;
        // }

        // Length of Code

        $h = $size; // Pixels
        $w = 0; // Padding in Pixels // strlen($code);
        $c = strlen($code);
        for ($i=0;$i<$c;$i++) {
            $w += (intval($code[$i]) * 2);
        }

        $img = imagecreatetruecolor($w, $h);
        $blk = imagecolorallocate ($img, 0, 0, 0);
        $wht = imagecolorallocate ($img, 255, 255, 255);
        imagefill($img, 0, 0, $wht);

        // $location = 10;
        $x = 0;
        // for($position = 1 ; $position <= strlen($code_string); $position++)
        // {
        //     $cur_size = $location + ( substr($code_string, ($position-1), 1) );
        //     if(strtolower($orientation) == "horizontal")
        //         imagefilledrectangle( $image, $location, 0, $cur_size, $img_height, ($position % 2 == 0 ? $white : $black) );
        //     else
        //         imagefilledrectangle( $image, 0, $location, $img_width, $cur_size, ($position % 2 == 0 ? $white : $black) );
        //     $location = $cur_size;
        // }

        // Draw Bars
        $c = strlen($code);
        for ($i=0;$i<$c;$i++) {
            $w = (intval($code[$i]) * 2);
            // echo "imagefilledrectangle($img,$x,0,$x + $w,$h, ( ($i % 2 == 0) ? $w : $b));\n";
            imagefilledrectangle($img,$x,0,$x + $w,$h, ( ($i % 2 == 0) ? $blk : $wht));
            $x += $w;
        }

        // $img = imagerotate($img,90,$wht);

        // Draw barcode to the screen
        header('Cache-Control: no-cache');
        header('Content-type: image/png');
        imagepng($img);
        imagedestroy($img);

    }
}