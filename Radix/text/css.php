<?php
/**
    @file
    @brief A CSS Minifier
    @see https://github.com/mrclay/minify
*/


class radix_text_css
{
    public static function minify($css)
    {
        // Compact the Whitespace
        $css = preg_replace('/\/\*.+?\*\//ms',null,$css); // Multi-line Comments
        $css = preg_replace('/^\s+/m',null,$css); // Strip Leading Spaces
        $css = preg_replace('/(\w): /','$1:',$css); // "x: " to "x:"
        $css = preg_replace('/{\s+/m','{',$css); // Strip Leading Spaces
        $css = preg_replace('/(\w);\s+/m','$1;',$css); // "; " => ";"
        $css = str_replace("\n",'',$css);
        return $css;
    }
}
