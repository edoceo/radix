<?php
/**
    @file
    @brief Twilio JSON Interface

    @see http://www.twilio.com/docs/api/rest
    @todo bring in a direct CURL call
*/

require_once('Radix/HTTP.php');

class radix_api_twilio
{
    const URI_BASE = 'https://%s:%s@api.twilio.com';
    const URI_PATH = '/2010-04-01';
    const UA = 'Radix Twilio API v2013.25';

    private static $__init = false;
    private static $__user;
    private static $__auth;
    private $_base;
    private $_stat;

    public $use_cache = true;

    /**
        Init the Static World
        @param $u Twilio Account SID
        @param $a Twilio Auth Token
    */
    public static function init($u,$a)
    {
        self::$__user = $u;
        self::$__auth = $a;
        $b = sprintf(self::URI_BASE . self::URI_PATH,$u,$a);
        if (strlen($b) > strlen(self::URI_BASE)) {
            self::$__init = true;
        }
    }

    /**
        @param $u account id
        @param $a auth token
    */
    public function __construct($u=null,$a=null)
    {
        if (null===$u && null===$a && self::$__init) {
            $u = self::$__user;
            $a = self::$__auth;
        }
        $this->_stat = array(
            'get' => 0,
            'get-hit' => 0,
            'post' => 0,
        );
        $this->_user = $u;
        $this->_auth = $a;
        $this->_base = sprintf(self::URI_BASE,$u,$a);
        $this->_base.= sprintf(self::URI_PATH . '/Accounts/%s',$u);
    }
    /**
        Not implemented
    */
    public function getAccounts()
    {

    }

    /**
        List of Calls by Page
        @param $page Page Number, 0
        @param $size Page Size, 100
    */
    public function listCalls($page=0,$size=100)
    {
        $api = sprintf('Calls.json?Page=%d&PageSize=%d',$page,$size);
        return $this->api($api);
    }

    /**
        List of Texts
        @param $page Page Number, 0
        @param $size Page Size, 100
    */
    public function textList($page=0,$size=100)
    {
        $api = sprintf('SMS/Messages.json?Page=%d&PageSize=%d',$page,$size);
        return $this->api($api);
    }
    
    /**
        Send
    */
    public function textSend($a)
    {
        // $api = sprintf('SMS/Messages/%s.json',$sid);
        $r = $this->post('SMS/Messages.json',$a);
        radix::dump($r);
        return $r;
    }

    /**
        Stat One Message or Stat List
        @param $sid the message to get
        @return message
    */
    public function textStat($sid=null)
    {
        if ($sid === null) {
            $api = sprintf('SMS/Messages.json?Page=0&PageSize=1');
        } else {
            $api = sprintf('SMS/Messages/%s.json',$sid);
        }
        return $this->api($api);
    }
    /**
        List of the Twilio Applications
    */
    public function listApplications($arg=null)
    {
        // if ($arg === null) $arg =
        return $this->api('Applications.json',$arg);
    }
    /**
        List of the Twilio Numbers
    */
    public function listNumbers($args=null)
    {
        return $this->api('IncomingPhoneNumbers.json');
    }

    /**
        Twilio GET API
    */
    public function api($api,$arg=null)
    {
        // Special Case the nocache Argument
        $this->_stat['get']++;
        $uri = $this->fixURI($api,$arg);
        $res = radix_http::get($uri);
        $ret = json_decode($res['body'],true);
        if (!$ret) {
            $ret = $res;
            echo json_last_error();
        }
        return $ret;
    }

    /**
        Execute POST
    */
    public function post($api,$arg)
    {
        // Patchup URI
        $uri = $this->fixURI($api,$arg);
        $this->_stat['post']++;
        $res = radix_http::post($uri,$arg);
        // radix::dump($res);
        $ret = json_decode($res['body'],true);
        // radix::dump($ret);
        return $ret;
    }
    
    /**
        Execute DELETE
    */
    public function delete($api)
    {
        $uri = $this->fixURI($api);
        $res = radix_http::delete($uri);
        $ret = json_decode($res['body'],true);
        return $ret;
    }
    /**
    */
    public function stat()
    {
        return $this->_stat;
    }
    /**
        
    */
    public function statCall($sid=null)
    {
        // $id == null and show all active calls
        if ($sid === null) {
            $api = sprintf('/Calls.json?Page=0&PageSize=1');
        } else {
            $api = sprintf('/Calls/%s.json',$sid);
        }
        $res = radix_http::get($this->_base . $api);
        $ret = json_decode($res['body'],true);
        //radix::dump($res);
        return $ret;
    }
    public function statApplication($sid)
    {
        $api = sprintf('/Applications/%s.json',$sid);
        $res = radix_http::get($this->_base . $api);
        $ret = json_decode($res['body'],true);
        return $ret;
    }
    /**
    */
    public function fixURI($api,$arg=null)
    {
        // Patchup URI
        if (substr($api,0,1)=='/') {
            $uri = sprintf(self::URI_BASE,$this->_user,$this->_auth) . $api;
        } else {
            $uri = $this->_base . '/' . $api;
        }
        if ($arg) {
            $uri.= ('?' . http_build_query($arg));
        }
        return $uri;
    }

    /**
        Drop an Existing Caller ID
        @param $sid of the Caller ID
    */
    function dropCallerId($sid)
    {
        $r = $this->delete(sprintf('OutgoingCallerIds/%s.json',$sid));
        return $r;
    }

    /**
        Ask Twilio for a new Caller ID
        @param $arg = number or argument array
        @return status object from Twilio
    */
    function initCallerId($arg)
    {
        if (is_string($arg)) {
            $arg = array('num' => $arg);
        }

        if (empty($arg['num'])) {
            return false;
        }
        // if (empty($arg['name']))

        $p = array(
            'PhoneNumber' => $arg['num'],
            'FriendlyName' => $arg['name'],
            'CallDelay' => 0,
            'Extension' => $arg['ext'],
            'StatusCallback' => $arg['uri'],
            'StatusCallbackMethod' => 'POST',
        );
        $r = $this->post('OutgoingCallerIds.json',$p);
        return $r;
    }

    /**
        List the Caller IDs
    */
    function listCallerIds()
    {
        return $this->api('OutgoingCallerIds.json');
    }

    /**
        Stat the Caller ID
        @param $sid of the Caller ID
    */
    function statCallerId($sid)
    {
        return $this->api('OutgoingCallerIds.json/'. $sid);
    }

    /**
    */
    public static function errorText($x)
    {
        switch ($x) {
        case '10001': return 'Account is not active';
        case '10002': return 'Trial account does not support this feature';
        case '10003': return 'Incoming call rejected due to inactive account';
        case '11100': return 'Invalid URL format';
        case '11200': return 'HTTP retrieval failure';
        case '11205': return 'HTTP connection failure';
        case '11206': return 'HTTP protocol violation';
        case '11210': return 'HTTP bad host name';
        case '11215': return 'HTTP too many redirects';
        case '12100': return 'Document parse failure';
        case '12101': return 'Invalid Twilio Markup XML version';
        case '12102': return 'The root element must be Response';
        case '12200': return 'Schema validation warning';
        case '12300': return 'Invalid Content-Type';
        case '12400': return 'Internal Failure';
        case '13201': return 'Dial: Cannot Dial out from a Dial Call Segment';
        case '13210': return 'Dial: Invalid method value';
        case '13212': return 'Dial: Invalid timeout value';
        case '13213': return 'Dial: Invalid hangupOnStar value';
        case '13214': return 'Dial: Invalid callerId value';
        case '13215': return 'Dial: Invalid nested element';
        case '13216': return 'Dial: Invalid timeLimit value';
        case '13221': return 'Dial->Number: Invalid method value';
        case '13222': return 'Dial->Number: Invalid sendDigits value';
        case '13223': return 'Dial: Invalid phone number format';
        case '13224': return 'Dial: Invalid phone number';
        case '13225': return 'Dial: Forbidden phone number';
        case '13230': return 'Dial->Conference: Invalid muted value';
        case '13231': return 'Dial->Conference: Invalid endConferenceOnExit value';
        case '13232': return 'Dial->Conference: Invalid startConferenceOnEnter value';
        case '13233': return 'Dial->Conference: Invalid waitUrl';
        case '13234': return 'Dial->Conference: Invalid waitMethod';
        case '13235': return 'Dial->Conference: Invalid beep value';
        case '13236': return 'Dial->Conference: Invalid Conference Sid';
        case '13237': return 'Dial->Conference: Invalid Conference Name';
        case '13238': return 'Dial->Conference: Invalid Verb used in waitUrl TwiML';
        case '13310': return 'Gather: Invalid finishOnKey value';
        case '13312': return 'Gather: Invalid method value';
        case '13313': return 'Gather: Invalid timeout value';
        case '13314': return 'Gather: Invalid numDigits value';
        case '13320': return 'Gather: Invalid nested verb';
        case '13321': return 'Gather->Say: Invalid voice value';
        case '13322': return 'Gather->Say: Invalid loop value';
        case '13325': return 'Gather->Play: Invalid Content-Type';
        case '13410': return 'Play: Invalid loop value';
        case '13420': return 'Play: Invalid Content-Type';
        case '13510': return 'Say: Invalid loop value';
        case '13511': return 'Say: Invalid voice value';
        case '13520': return 'Say: Invalid text';
        case '13610': return 'Record: Invalid method value';
        case '13611': return 'Record: Invalid timeout value';
        case '13612': return 'Record: Invalid maxLength value';
        case '13613': return 'Record: Invalid finishOnKey value';
        case '13614': return 'Record: Invalid transcribe value';
        case '13615': return 'Record: maxLength too high for transcription';
        case '13616': return 'Record: playBeep must be true or false';
        case '13710': return 'Redirect: Invalid method value';
        case '13910': return 'Pause: Invalid length value';
        case '14101': return 'Invalid \'To\' attribute';
        case '14102': return 'Invalid \'From\' attribute';
        case '14103': return 'Invalid Body';
        case '14104': return 'Invalid Method attribute';
        case '14105': return 'Invalid statusCallback attribute';
        case '14106': return 'Document retrieval limit reached';
        case '14107': return 'SMS send rate limit exceeded';
        case '14108': return 'From phone number not SMS capable';
        case '14109': return 'SMS Reply message limit exceeded';
        case '14110': return 'Invalid Verb for SMS Reply';
        case '14111': return 'Invalid To phone number for Trial mode';
        case '20001': return 'Unknown parameters';
        case '20002': return 'Invalid FriendlyName';
        case '20003': return 'Permission Denied';
        case '20004': return 'Method not allowed';
        case '20005': return 'Account not active';
        case '21201': return 'No Called number specified';
        case '21202': return 'Called number is a premium number';
        case '21203': return 'International calling not enabled';
        case '21205': return 'Invalid URL';
        case '21206': return 'Invalid SendDigits';
        case '21207': return 'Invalid IfMachine';
        case '21208': return 'Invalid Timeout';
        case '21209': return 'Invalid Method';
        case '21210': return 'Caller phone number not verified';
        case '21211': return 'Invalid Called Phone Number';
        case '21212': return 'Invalid Caller Phone Number';
        case '21213': return 'Caller phone number is required';
        case '21220': return 'Invalid call state';
        case '21401': return 'Invalid Phone Number';
        case '21402': return 'Invalid Url';
        case '21403': return 'Invalid Method';
        case '21404': return 'Inbound Phone number not available to trial account';
        case '21405': return 'Cannot set VoiceFallbackUrl without setting Url';
        case '21406': return 'Cannot set SmsFallbackUrl without setting SmsUrl';
        case '21407': return 'This Phone Number type does not support SMS';
        case '21450': return 'Phone number already validated on your account';
        case '21451': return 'Invalid area code';
        case '21452': return 'No phone numbers found in area code';
        case '21453': return 'Phone number already validated on another account';
        case '21454': return 'Invalid CallDelay';
        case '21501': return 'Resource not available';
        case '21502': return 'Invalid callback url';
        case '21503': return 'Invalid transcription type';
        case '21504': return 'RecordingSid is required';
        case '21601': return 'Phone number is not a valid SMS-capable inbound phone number';
        case '21602': return 'Message body is required';
        case '21603': return 'The source \'from\' phone number is required to send an SMS';
        case '21604': return 'The destination \'to\' phone number is required to send an SMS';
        case '21605': return 'Maximum SMS body length is 160 characters';
        case '21606': return 'The \'From\' phone number provided is not a valid, SMS-capable inbound phone number for your account';
        case '21608': return 'The Sandbox number can send messages only to verified numbers';
        default: return 'Unknown Error';
        }
    }
}

