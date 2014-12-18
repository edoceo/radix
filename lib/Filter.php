<?php
/**
    @file
    @brief Input Data Filter

    @package Radix

*/

namespace Edoceo\Radix;

class Filter
{
    /**
        @param $x the email address data
        @param $d default if not valid
        @return normalized/validated email, false on failure
        @todo want to use the imap_parser and have optional array return or out var
    */
    static function email($x,$d=false)
    {
        // Match Email Address Pattern
        $x = strtolower(trim($x));
        if (preg_match('/^([\w\.\%\+\-]+[a-z0-9])@([a-z0-9][a-z0-9\.\-]*[a-z0-9]\.[a-z]{2,6})$/',$x,$m)) {
            // More strict is to only check MX, we do both in example
            if ( (checkdnsrr($m[2],'MX') == true) || (checkdnsrr($m[2],'A') == true) ) {
                return sprintf('%s@%s',$m[1],$m[2]);
            }
        }
        return false;
    }

    /**
        Extracts and returns match(es)
        @param $pat the pattern of characters to accept
        @param $val the value to filter
        @param $def default
        @return sanatized value
    */
    static function match($pat,$val,$def=false)
    {
        $ret = $def;
        if (substr($pat,0,1)!='/') {
            $pat = '/(' . $pat . ')/i';
        }
        if (preg_match($pat,$val,$m)) {
            array_shift($m);
            if (count($m)==1) {
                $ret = $m[0]; // Single value
            } else {
                $ret = $m; // Array w/o full match
            }
        }
        return $ret;
    }

    /**
        Returns a String with only the specified characters
        @param $pat the pattern of characters to accept
        @param $val the value to filter
    */
    static function only($pat,$val)
    {
        if (substr($pat,0,1)!='/') {
            $pat = '/[^' . $pat . ']/i';
        }
        $ret = preg_replace($pat,null,$val);
        return $ret;
    }

    /**
        @param $val string to read
        @param $pat pattern to match to, use ()
        @param $def default value if no match
        @return captured string from regex | array of matches | null
    */
    static function regex($val,$pat,$def=null)
    {
        $ret = $def;
        if (preg_match($pat,$val,$m)) {
            switch (count($m)) {
            case 0:
                // Ignore
                break;
            case 1: // Never Happens
            case 2:
                $ret = $m[1];
                break;
            default:
                // array_shift($m);
                $ret = $m;
            }
        }
        return $ret;
    }

    /**
        Makes a stub from the text
        @param $t input text
        @param $s seperator, default '_'
    */
    static function stub($t,$s='_')
    {
        $t = preg_replace('/[^a-z0-9\-' . preg_quote($s) . ' ]+/ims',null,trim($t)); // non-safe chars => null
        // Radix::dump("1:$t");
        $t = preg_replace('/[^a-z0-9]+/ims',$s,$t); // non-word => $s 
        // Radix::dump("2:$t");
        // $t = preg_replace('/\b([a-z0-9])' . preg_quote($s) . '/ims','$1',$t);
        // Radix::dump("r:$t");
        $t = trim($t);
        $t = strtolower($t);
        return $t;
    }

    /**
        @see http://stackoverflow.com/questions/1547899/which-characters-make-a-url-invalid
        @return normalized URI, false on failure
    */
    static function uri($uri,$def=false)
    {
        if (!preg_match('/^([\w\-]{2,8}):\/\//',$uri)) {
            $uri = "http://$uri";
        }
        $buf = parse_url($uri);
        if (empty($buf['scheme'])) $buf['scheme'] = 'http';
        if (empty($buf['host'])) {
            if (preg_match('/^[\w\.]+$/',$buf['path'])) {
                $buf['host'] = $buf['path'];
                $buf['path'] = '/';
            }
        }
        // Here and empty means not valid, give null
        if (empty($buf['host'])) {
            return $def;
        }
        if (empty($buf['path'])) {
            $buf['path'] = '/';
        }
        // Fix double slash
        $buf['path'] = preg_replace('/\/+/','/',$buf['path']);
        // Base
        $uri = sprintf('%s://%s%s',strtolower($buf['scheme']),strtolower($buf['host']),$buf['path']);
        // Query String?
        if (!empty($buf['query'])) {
            $uri.= '?' . $buf['query'];
        }
        // Fragment?
        if (!empty($buf['fragment'])) {
            $uri.= '#' . $buf['fragment'];
        }
        return $uri;
    }
}
