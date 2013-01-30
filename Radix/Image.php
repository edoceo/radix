<?php
/**
    Tools for working with Images

    Various Utility functions that didn't fit anywhere else
*/

class Radix_Image
{
    static function toPNG($src,$dst)
    {
        $er = error_reporting(0);
        list($img_w, $img_h, $type, $attr) = getimagesize($src);
        switch ($type) {
        case IMAGETYPE_GIF:
            $src_i = imagecreatefromgif($src);
            break;
        case IMAGETYPE_JPEG:
            $src_i = imagecreatefromjpeg($src);
            break;
        case IMAGETYPE_PNG:
            $src_i = imagecreatefrompng($src);
            break;
        default:
            error_reporting($er);
            throw new Exception("Invalid Image Type: $type",__LINE__);
        }
        imagealphablending($src_i, true);

        $dst_i = imagecreatetruecolor($img_w,$img_h);
        // And Make a Transparent Background
        imagealphablending($dst_i, false);
        imagesavealpha($dst_i, true);
        $tbg = imagecolorallocatealpha($dst_i, 255, 255, 255, 127);
        imagefilledrectangle($dst_i, 0, 0, $img_w, $img_h, $tbg);
        // Copy & Center
        imagecopyresampled($dst_i,$src_i,0,0,0,0,$img_w,$img_h,$img_w,$img_h);
        $ret = imagepng($dst_i,$dst);
        imagedestroy($src_i);
        imagedestroy($dst_i);
    }

    /**
    */
    static function makeThumb($src,$dst,$dst_w,$dst_h)
    {
        $er = error_reporting(0);
        list($src_w, $src_h, $type, $attr) = getimagesize($src);
        switch ($type) {
        case IMAGETYPE_GIF:
            $src_i = imagecreatefromgif($src);
            break;
        case IMAGETYPE_JPEG:
            $src_i = imagecreatefromjpeg($src);
            break;
        case IMAGETYPE_PNG:
            $src_i = imagecreatefrompng($src);
            break;
        default:
            error_reporting($er);
            throw new Exception("Invalid Image Type: $type",__LINE__);
        }
        imagealphablending($src_i, true);

        //Radix::dump("Source: $src_w x $src_h");
        //Radix::dump("Target: $dst_w x $dst_h");

        // Image Must be Sized to Fit in the $w, $h box
        if (($src_w > $dst_w) || ($src_h > $dst_h)) {
            $new_size = max($src_w / $dst_w,$src_h / $dst_h); //  / max($dst_w,$dst_h);
            //Radix::dump("Scale: $new_size");
            $new_w = min($dst_w,ceil($src_w / $new_size));
            $new_h = min($dst_h,ceil($src_h / $new_size));
            //Radix::dump("Result: $new_w x $new_h");

            // Calculate Centered Location
            $off_x = ($dst_w - $new_w) / 2;
            $off_y = ($dst_h - $new_h) / 2;
            //Radix::dump("Offset: $off_x x $off_y");

            // $dst_i = imagecreatetruecolor($new_w,$new_h);
            $dst_i = imagecreatetruecolor($dst_w,$dst_h);
            // And Make a Transparent Background
            imagealphablending($dst_i, false);
            imagesavealpha($dst_i, true);
            $tbg = imagecolorallocatealpha($dst_i, 255, 255, 255, 127);
            imagefilledrectangle($dst_i, 0, 0, $dst_w, $dst_h, $tbg);
            // Copy & Center
            imagecopyresampled($dst_i,$src_i,$off_x,$off_y,0,0,$new_w,$new_h,$src_w,$src_h);
            $ret = imagepng($dst_i,$dst);
            imagedestroy($src_i);
            imagedestroy($dst_i);
            if ($ret !== true) {
                $x = error_get_last();
                throw new Exception($x['message'],__LINE__);
            }
        }
    }
}
