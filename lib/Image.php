<?php
/**
    @file
    @brief Tools for working with Images
*/

namespace Edoceo\Radix;

class Image
{
    /**
        Converts Images to PNG w/Transparent Background
        @param $src Source Image - GIF, JPEG, PNG
        @param $dst Target Image - PNG
    */
    static function toPNG($src,$dst)
    {
        // $er = error_reporting(0);
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
            throw new \Exception("Invalid Image Type: $type",__LINE__);
        }
        imagealphablending($src_i, true);

        $dst_i = imagecreatetruecolor($img_w,$img_h);

        // Make a Transparent Background
        imagealphablending($dst_i, false);
        imagesavealpha($dst_i, true);
        $tbg = imagecolorallocatealpha($dst_i, 255, 255, 255, 127);
        imagefilledrectangle($dst_i, 0, 0, $img_w, $img_h, $tbg);

        // Copy
        imagecopyresampled($dst_i,$src_i,0,0,0,0,$img_w,$img_h,$img_w,$img_h);
        imagedestroy($src_i);

        $ret = imagepng($dst_i,$dst);
        imagedestroy($dst_i);

        return $ret;
    }

    /**
    */
    static function makeThumb($src,$dst,$dst_w,$dst_h)
    {
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
            throw new \Exception("Invalid Image Type: $type",__LINE__);
        }
        imagealphablending($src_i, true);

        //Radix::dump("Source: $src_w x $src_h");
        //Radix::dump("Target: $dst_w x $dst_h");

        // Image Must be Sized to Fit in the $w, $h box
        // if (($src_w > $dst_w) || ($src_h > $dst_h)) {
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
                throw new \Exception($x['message'],__LINE__);
            }
        // }

        return $ret;
    }

    /**
        Scale Image

        @param $src Source Image File
        @param $dst Destination File
        @param $dst_w Destination Width
        @param $dst_h Destination Height

        @see https://blogs.oracle.com/oswald/entry/scaling_images_in_php_done
    */
    public static function scale($src,$dst,$dst_w=null,$dst_h=null)
    {
        // Check Inputs
        if (empty($dst_w) && empty($dst_h)) {
            throw new \Exception("One of Target Width or Height must be provided: $type",__LINE__);
        }

        // Open Image
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
            throw new Exception("Invalid Image Type: $type",__LINE__);
        }
        imagealphablending($src_i, true);

        if ($dst_w == -1) {
            $dst_w = floor($src_w * $src_h / $dst_h);
        }
        if ($dst_h == -1) {
            $dst_h = floor($src_h * $dst_w / $src_w);
        }

        $off_x = $off_y = 0;

        $dst_i = imagecreatetruecolor($dst_w,$dst_h);
        // And Make a Transparent Background
        imagealphablending($dst_i, false);
        imagesavealpha($dst_i, true);
        $tbg = imagecolorallocatealpha($dst_i, 255, 255, 255, 127);
        imagefilledrectangle($dst_i, 0, 0, $dst_w, $dst_h, $tbg);
        // Copy/Scale
        imagecopyresampled($dst_i,$src_i,$off_x,$off_y,0,0,$dst_w,$dst_h,$src_w,$src_h);
        $ret = imagepng($dst_i,$dst);
        imagedestroy($src_i);
        imagedestroy($dst_i);
        if ($ret !== true) {
            $x = error_get_last();
            throw new \Exception($x['message'],__LINE__);
        }

        return $ret;
    }

    /**
		@param $src Source File
		@param $dst Target File
		@param $x1 Top, Left X
		@param $y1 Left Side Y
		@param $x2
		@param $y2 Bottom
	*/
    public static function crop($src, $dst, $x1, $y1, $x2, $y2)
    {
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
            throw new \Exception("Invalid Image Type: $type",__LINE__);
        }
        imagealphablending($src_i, true);

        $dst_w = $x2 - $x1;
        $dst_h = $y2 - $y1;

        if (($dst_w <= 0) || ($dst_h <= 0)) {
        	return false;
        }

        // Radix::dump("Target: $dst_w x $dst_h");
        $dst_i = imagecreatetruecolor($dst_w,$dst_h);
        // And Make a Transparent Background
        imagealphablending($dst_i, false);
        imagesavealpha($dst_i, true);

        $tbg = imagecolorallocatealpha($dst_i, 255, 255, 255, 127);
        imagefilledrectangle($dst_i, 0, 0, $dst_w, $dst_h, $tbg);
        // Copy/Scale
        imagecopyresampled($dst_i,$src_i, 0, 0, $x1, $y1, $dst_w, $dst_h, $dst_w, $dst_h);

        imagedestroy($src_i);
        $ret = imagepng($dst_i,$dst);
        imagedestroy($dst_i);
        if ($ret !== true) {
            $x = error_get_last();
            throw new \Exception($x['message'],__LINE__);
        }

        return true;
    }

}
