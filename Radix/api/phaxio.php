<?php
/**

*/

class radix_api_phaxio
{
    const UA = 'Radix Phaxio API v2012.28';

    private $_apiuri = 'https://api.phaxio.com/v1';

    private $_apikey;
    private $_secret;

    public function __construct($k,$s)
    {
        $this->_apikey = $k;
        $this->_secret = $s;
    }
    
    /**
    */
    public function api($api,$post)
    {
        $uri = $this->_apiuri . $api;

        $post['api_key'] = $this->_apikey;
        $post['api_secret'] = $this->_secret;

        // Clean Empties cause their API don't like them
        foreach (array_keys($post) as $x) {
            if (empty($post[$x])) unset($post[$x]);
        }

        $ch = self::_curl_init($uri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        return self::_curl_exec($ch);
    }
    
    /**
        @param $id fax ID
        @param $ft File Type s => 129x167 jpeg, l => 300x388jpeg, p => full PDF
    */
    public function faxFile($id,$ft='s')
    {
        $pd = array(
            'id' => $id,
            'type' => $ft,
        );
        return $this->api('/faxFile',$pd);
    }

    /**
        Retrieve Number List
        @param $ad Alpha Date - Start Timestamp of Query
        @param $od Omega Date - End of List
    */
    public function faxList($ad=null,$od=null)
    {
        $pd = array(
            'start' => $ad,
            'end' => $od,
        );
        return $this->api('/faxList',$pd);
    }
    
    /**
    */
    public function faxStatus($id)
    {
        $pd = array('id' => $id);
        return $this->api('/faxStatus',$pd);
    }
    
    /**
        Retrieve Number List
        @param $ac
        @param $pn
    */
    public function numberList($ac=null,$pn=null)
    {
        $pd = array(
            'area_code' => $ac,
            'number' => $pn,
        );
        $ret = $this->api('/numberList',$pd);
        if ($ret['info']['http_code']==200) {
            $ret = json_decode($ret['body'],true);
        }
        return $ret;
    }

    /**
    */
    public function provisionNumber($ac,$cb=null)
    {
        $pd = array(
            'area_code' => $ac,
            'callback_url' => $cb,
        );
        return $this->api('/provisionNumber',$pd);
    }

    /**
        Sends a Fax
    */
    public function send($rcpt,$file)
    {
        $post = array(
            'to' => $rcpt,
            'filename' => sprintf('@%s',ltrim($file,'@')),
            'string_data' => null,
            'string_data_type' => null, // html | url | text
            'batch' => false,
            'batch_delay' => 600, // Time in Seconds
            'batch_collision_avoidancd' => true,
            'callback_url' => null, 
        );

        return $this->api('/send',$post);

    }

    public function testReceive($from,$rcpt,$file)
    {
        $post = array(
            'from_number' => $from,
            'to_number' => $rcpt,
            'filename' => sprintf('@%s',ltrim($file,'@')),
        );
        return $this->api('/testReceive',$post);
    }

    /**
        Executes the Single or Multiple Requests
    */
    private static function _curl_init($uri)
    {
        $ch = curl_init($uri);
        // Booleans
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_CRLF, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NETRC, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt(self::$_ch, CURLOPT_SSLVERSION, 3); // 2, 3 or GnuTLS
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::UA);

        // curl_setopt(self::$_ch, CURLOPT_HEADERFUNCTION, array('self','_curl_head'));

        return $ch;
    }

    /**
    */
    private static function _curl_exec($ch,$async=false)
    {
        $r = array(
            'body' => curl_exec($ch),
            'info' => curl_getinfo($ch),
        );
        if (curl_errno($ch)) {
            $r['fail'] = sprintf('%d:%s',curl_errno($ch),curl_error($ch));
        }
        return $r;
    }
}