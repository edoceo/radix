<?php
/**
    @file
    @brief Radix CURL Interface
    @package Radix

    If you need a higher level HTTP interface look elsewhere
    This is simply a utility wrapper around curl

    @see http://www.php.net/manual/en/function.curl-multi-exec.php
    @see http://semlabs.co.uk/journal/object-oriented-curl-class-with-multi-threading

*/

class Radix_HTTP
{
    private static $_ch; // Curl Handle Array
    private static $_ch_head;
    private static $_mc; // Multi-Curl Handle
    private static $_mc_exec; // Count of Executing Handles
    private static $_mc_info; // Info Array
    private static $_mc_list; // Array of Handles

    private static $_opts;
    /**
        Sets the Default Options
    */
    static function init($opts=null)
    {
        self::$_opts = array(
            'head'     => array(),
            'async'    => false,
            'cookie'   => null, // Path to Cookie File
            'referrer' => null,
            'timeout'  => 30, // Seconds
            'user-agent' => 'Edoceo Radix/HTTP 0.2',
            'verbose'  => null, // Verbose Log File Handle
        );
        if (!is_array($opts)) {
            $opts = array($opts);
        }
        self::$_opts = array_merge(self::$_opts,$opts);
    }

    /**
        Performs HTTP GET on the URI
        @param $uri
        @return Response Array
    */
    static function get($uri)
    {
        $ch = self::_curl_init($uri);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        return self::_curl_exec($ch);
    }

    /**
        Performs HTTP HEAD on the URI
        @param $uri
        @return Response Array
    */
    static function head($uri)
    {
        $ch = self::_curl_init($uri);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
        return self::_curl_exec($ch);
    }

    /**
        Performs HTTP POST on the URI
        @param $uri
        @param $post array or data string
        @return Response Array
    */
    static function post($uri,$post)
    {
        $ch = self::_curl_init($uri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        return self::_curl_exec($ch);
    }

    /**
        Executes HTTP DELETE to the specified URI
        @param $uri the URI to delete
        @return Response Array
    */
    static function delete($uri)
    {
        $ch = self::_curl_init($uri);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        return self::_curl_exec($ch);
    }

    /**
        Get's info from the Multi CURL or Single Curl
    */
    static function info($ch=null)
    {
        echo "info($ch=null)<br />";
        if (!empty(self::$_mc)) {
            // @todo Multi
            // @note This is not working at all, just returns null
            $x = curl_multi_info_read(self::$_mc);
            echo gettype($x);
            var_dump($x);
            print_r($x);
            return $x;
        } elseif (!empty($ch)) {
            //echo sprintf('%s:%d',__FILE__,__LINE__);
            return curl_getinfo($ch);
        } elseif (!empty(self::$_ch)) {
            //echo sprintf('%s:%d',__FILE__,__LINE__);
            return curl_getinfo(self::$_ch);
        }
        echo "info($ch=null) => null<br />";
        return null;
    }
    /**
        Wait's for all the HTTP Sessions to Complete
        Then Returns a Big Informational Array

        @todo when handle finished, capture it's data (info/body) and close()
    */
    static function wait($ch=null)
    {
        echo "wait($ch=null)<br />";
        for ($try_i = 0; $try_i < 50; $try_i++) {
            self::$_mc_exec = 0;
            curl_multi_select(self::$_mc); // Wait for Activity
            switch (curl_multi_exec(self::$_mc,self::$_mc_exec)) {
            case CURLM_CALL_MULTI_PERFORM:
                // What does this Mean?
                echo "CURLM_CALL_MULTI_PERFORM<br />";
                // Still Working
                break;
            case CURLM_OK:
                echo "CURLM_OK<br />";
                // What does this mean?
                //
                print_r(curl_multi_info_read(self::$_mc));
                $try_i = 51;
                break;
            default:
                echo "Unhandled: {self::$_mc_exec} active\n";
            }
        }
        return null;
    }

    /**
        Executes the Single or Multiple Requests
    */
    private static function _curl_init($uri)
    {
        self::$_ch = curl_init($uri);
        self::$_ch_head = null;
        // Booleans
        curl_setopt(self::$_ch, CURLOPT_AUTOREFERER, true);
        curl_setopt(self::$_ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt(self::$_ch, CURLOPT_COOKIESESSION, false);
        curl_setopt(self::$_ch, CURLOPT_CRLF, false);
        curl_setopt(self::$_ch, CURLOPT_FAILONERROR, false);
        curl_setopt(self::$_ch, CURLOPT_FILETIME, true);
        curl_setopt(self::$_ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt(self::$_ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt(self::$_ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt(self::$_ch, CURLOPT_HEADER, false);
        curl_setopt(self::$_ch, CURLOPT_NETRC, false);
        curl_setopt(self::$_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(self::$_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(self::$_ch, CURLOPT_VERBOSE, false);
        if ( (!empty(self::$_opts['verbose'])) && (is_resource(self::$_opts['verbose'])) ) {
            curl_setopt(self::$_ch, CURLOPT_VERBOSE, true);
            curl_setopt(self::$_ch, CURLOPT_STDERR, self::$_opts['verbose']);
        }

        curl_setopt(self::$_ch, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt(self::$_ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt(self::$_ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt(self::$_ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt(self::$_ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt(self::$_ch, CURLOPT_SSLVERSION, 3); // 2, 3 or GnuTLS
        curl_setopt(self::$_ch, CURLOPT_TIMEOUT, self::$_opts['timeout']);
        curl_setopt(self::$_ch, CURLOPT_USERAGENT, self::$_opts['user-agent']);

        if ( (!empty(self::$_opts['head'])) ) {
            curl_setopt(self::$_ch, CURLOPT_HTTPHEADER, self::$_opts['head']);
        }

        if (!empty(self::$_opts['cookie'])) {
            curl_setopt(self::$_ch, CURLOPT_COOKIEFILE, self::$_opts['cookie']);
            curl_setopt(self::$_ch, CURLOPT_COOKIEJAR, self::$_opts['cookie']);
        }

        curl_setopt(self::$_ch, CURLOPT_HEADERFUNCTION, array('self','_curl_head'));

        return self::$_ch;
    }

    /**
        @param $ch Curl Handle
        @param $async do an async HTTP?
    */
    private static function _curl_exec($ch,$async=false)
    {
        if (self::$_opts['async']) {
            self::$_mc_exec = 0;
            if (empty(self::$_mc)) {
                self::$_mc = curl_multi_init();
            }
            curl_multi_add_handle(self::$_mc,$ch);
            curl_multi_exec(self::$_mc,self::$_mc_exec);
            self::$_mc_list[] = $ch;
            return self::$_mc_exec;
        } else {
            return array(
                'body' => curl_exec($ch),
                'fail' => sprintf('%d:%s',curl_errno($ch),curl_error($ch)),
                'info' => curl_getinfo($ch),
                'head' => self::$_ch_head,
            );
        }
    }

    /**
    */
    private static function _curl_head($ch,$line)
    {
        // echo "Radix_HTTP::_curl_head($ch,$line)\n";
        $ret = strlen($line);
        /*
        if (preg_match('/^HTTP\/1\.1 (\d{3}) (.+)/',$buf,$m))
        {
            $this->_status_code = $m[1];
            $this->_status_mesg = $m[2];
        }
        else
        */
        if (preg_match('/^(.+?):(.+)/',$line,$m)) {
            self::$_ch_head[strtolower(trim($m[1]))] = trim($m[2]);
        }
        // note: HTTP 1.1 (rfc2616) says that 404 and HEAD should not have response.
        // note:  CURL will hang if we don't force close by return 0 here
        // note: http://developer.amazonwebservices.com/connect/thread.jspa?messageID=40930
        // Last Line of Headers
        // if (($ret==2) && ($this->_http_request_verb=='HEAD')) return 0;
        return $ret;
    }
}
