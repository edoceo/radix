<?php
/**
    Radix Tools

    Various Utility functions that didn't fit anywhere else
*/

class Radix_Tool
{
    /**
        @return normalized/validated email
    */
    static function fixEmail($x)
    {
        // Match Email Address Pattern
        if (preg_match('/^([\w\.\%\+\-]+)@([a-z0-9\.\-]+\.[a-z]{2,6})$/i',trim($x),$m)) {
            // More strict is to only check MX, we do both in example
            if ( (checkdnsrr($m[2],'MX') == true) || (checkdnsrr($m[2],'A') == true) ) {
                return sprintf('%s@%s',$m[1],$m[2]);
            }
        }
    }
    /**
        @return normalized URI
    */
    static function fixURI($uri)
    {
        $buf = @parse_url($uri);
        if (empty($buf['scheme'])) $buf['scheme'] = 'http';
        if (empty($buf['host'])) {
            if (preg_match('/^[\w\.]+$/',$buf['path'])) {
                $buf['host'] = $buf['path'];
                $buf['path'] = '/';
            }
        }
        // Here and empty means not valid, give null
        if (empty($buf['host'])) {
            return null;
        }
        if (empty($buf['path'])) {
            $buf['path'] = '/';
        }
        // Fix double slash
        $buf['path'] = preg_replace('/\/+/','/',$buf['path']);
        // Base
        $uri = sprintf('%s://%s%s',$buf['scheme'],$buf['host'],$buf['path']);
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
    /**
        @return text input with non-uri friendly stuff removed
    */
    static function makeStub($x)
    {
        $x = strtolower($x);
        $x = preg_replace('/[^\w\-]/','-',$x); // replace non words with dash
        $x = preg_replace('/\-+/','-',$x); // replace multi-dash with single dash
        $x = trim($x,'-');
        return trim($x);
    }
}
