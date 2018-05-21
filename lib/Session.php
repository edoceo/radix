<?php
/**
    @file
    @brief Basic "secure" session

    @package radix
    @see http://stackoverflow.com/questions/10561280/fast-and-efficient-php-session
*/

namespace Edoceo\Radix;

/**
    @brief Ensures integrity of session, browser sig, etc
*/

class Session
{
    // @todo Tune These
    private static $_opts = array(
        'auto_start' => false,
        'bug_compat_42'             => null,
        'bug_compat_warn'           => null,
        'cache_expire'              => null,
        'save_path'                 => null,
        'name'                      => null,
        'save_handler'              => null,
        'gc_probability'            => null,
        'gc_divisor'                => null,
        'gc_maxlifetime'            => null,
        'serialize_handler'         => null,
        'cookie_lifetime'           => null,
        'cookie_path'               => null,
        'cookie_domain'             => null,
        'cookie_secure'             => null,
        'cookie_httponly'           => null,
        'use_cookies'               => true,
        'use_only_cookies'          => true,
        'referer_check'             => null,
        'entropy_file'              => null,
        'entropy_length'            => null,
        'cache_limiter'             => null,
        'use_trans_sid'             => null,
        'hash_function'             => null,
        'hash_bits_per_character'   => null,
    );

    /**
    	@param $opts
    */
    static function init($opts=null)
    {
        // Check for Existing Session
        $x = session_id();
        if (empty($x)) {
            if (!is_array($opts)) {
                $opts = array();
            }

            // Set Options
            foreach (self::$_opts as $k => $v) {

                // Merge Passed Options
                if (isset($opts[$k])) {
                    self::$_opts[$k] = $opts[$k];
                }

                if (isset(self::$_opts[$k])) {
                    ini_set("session.$k", self::$_opts[$k]);
                }
            }

            // Start
            session_name(self::$_opts['name']);
            session_start();
        }

        // Match Session to Browser or Reset
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (empty($_SESSION['_radix']['_ua'])) {
            $_SESSION['_radix']['_ua'] = $ua;
        }
        if ($_SESSION['_radix']['_ua'] != $ua) {
            self::kill();
        }

        // Expire Stuff
        if (!empty($_SESSION['_radix']['_expires'])) {

            // Array of Keys which were expired
            $_SESSION['_radix']['_expired'] = array();

            foreach ($_SESSION['_radix']['_expires'] as $key => $chk) {

                $xtime = $chk['xtime'];

                // Map Relative Value to Absolute Time
                // if (preg_match('/^\+(\d+)(s|m|h|d)$/',$xtime,$m)) {
                //     $num = $m[1];
                //     $mod = $m[2];
                //     // It's a Relative Thing (if the data is the same)
                //     $md5 = md5(serialize( @$_SESSION[ $k ] ));
                //     if ($chk['md5'] == $md5) {
                //         $_SESSION['warn'][] = 'Relative Expires, Same Data';
                //         $xtime = $num;
                //         switch ($mod) {
                //         case 'd':
                //             $xtime = $num * 86400;
                //             break;
                //         case 'h':
                //             $xtime = $num * 3600;
                //             break;
                //         case 'm':
                //             $xtime = $num * 60;
                //             break;
                //         case 's':
                //         default:
                //             $xtime = $num;
                //         }
                //     } else {
                //         $_SESSION['warn'][] = 'Relative Expires, Fresh Data, Fresh mtime';
                //         $_SESSION['_radix']['_expires'][$k]['mtime'] = time();
                //     }
                //
                // }

                if (preg_match('/^(\d+)$/',$xtime)) {
                    // It's an Absolute Thing
                    if ($_SERVER['REQUEST_TIME'] >= $chk['xtime']) {
                        // Expire the Data
                        $_SESSION['_radix']['_expired'][] = $key;
                        unset($_SESSION[ $key ]);
                    }
                }

            }
        }
    }

    /**
        Completely kills the session
        Removes all $_SESSION data
        Regenerates ID
        Flushes Cookie
    */
	static function kill()
	{
		//session_regenerate_id(true);

		if (PHP_SESSION_ACTIVE == session_status()) {

			$cp = session_get_cookie_params();
			$sn = session_name();

			// Wipe Vars
			foreach ($_SESSION as $k=>$v) {
				unset($_SESSION[$k]);
			}
			session_destroy();

			setcookie($sn, false, 1, $cp['path'], $cp['domain'], $cp['secure']);

		}

	}

    /**
	    Set a specific piece of data to expire at a specific time

		@param $name String then name of the session key ($_SESSION[ $this_one ]) to expire at time
        @param $time unix timestamp or date or the pattern "+(\d+)(s|m|h|d)" to expire if unchanged for that amount of time
    */
    static function expire($name,$time='+30m')
    {
        if (empty($_SESSION['_radix']['_expires'])) {
            $_SESSION['_radix']['_expires'] = array();
        }

        $_SESSION['_radix']['_expires'][ $name ] = array(
            'md5' => md5(serialize( $_SESSON[ $name ] ) ),
            'mtime' => time(),
            'xtime' => $time,
        );

    }

    /**
        Add to Warning Messages
        @param $what is the div class to flash in
        @param $html is the message to display
        flash() returns the message
        flash($what) resets $what
        flash($what,$html) appends a message to that class
    */
    static function flash($what=null,$html=null)
    {
    	if (empty($_SESSION['_radix']['_flash'])) {
			$_SESSION['_radix']['_flash'] = array();
		}

        // Add Message
        if ( ($what !== null) && ($html !== null) ) {
            $_SESSION['_radix']['_flash'][$what][] = $html;
            return true;
        }

        // Or Clear it Out
        if ( ($what !== null) && ($html === null) ) {
            $_SESSION['_radix']['_flash'][$what] = array();
            return true;
        }

        // Or Output
        $out = null;
        $keys = array_keys($_SESSION['_radix']['_flash']);
        foreach ($keys as $key) {
            // Skip?
            if (empty($_SESSION['_radix']['_flash'][$key])) {
                continue;
            }
            if (count($_SESSION['_radix']['_flash'][$key])==0) {
                continue;
            }
            // Convert single item array to string
            $buf = $_SESSION['_radix']['_flash'][$key];
            if ( (is_array($buf)) && (count($buf)==1) ) {
                $buf = $buf[0];
            }
            // Do Output
            $out.= sprintf('<div class="%s">',$key);
            if (is_array($buf)) {
                $out.= '<ul>';
                foreach ($buf as $msg) {
                    $out.= '<li>' . $msg .'</li>';
                }
                $out.= '</ul>';
            } elseif ((is_string($buf)) && (strlen($buf))) {
                $out.= sprintf('<p>%s</p>',$buf);
            } elseif (is_object($buf)) {
                if ($buf instanceof Exception) {
                    $out.= sprintf('#%s: %s',$buf->getCode(),$buf->getMessage());
                } else {
                    die(print_r($buf,true));
                }
            }
            $out.= '</div>';
        }
        unset($_SESSION['_radix']['_flash']);
        return $out;
    }
}
