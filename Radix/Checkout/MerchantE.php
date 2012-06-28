<?php
/**
    @file
    @brief Class to Communicate with the Merchant E Solutions Gateway
    @version $Id: MerchantE.php 2113 2012-03-26 04:08:02Z code@edoceo.com $

    @package radix
    @see http://resources.merchante-solutions.com/
*/

class radix_checkout_merchante
{
    const API_URI = 'https://test.merchante-solutions.com/mes-api/tridentApi';

    private $_profile_id;
    private $_profile_key;

    private $_parameter_list;
    
    function __construct($id,$key)
    {
        $this->_profile_id = $id;
        $this->_profile_key = $key;
        $this->_resetParameters();
    }
    /**
    */
    //function isApproved()
    //{
    //    $errorCode = $this->getResponseField('error_code');
    //    $retVal = FALSE;
    //    if ( $errorCode == '000' ) {
    //      $retVal = TRUE;
    //    } else if ( $errorCode == '085' && $this->TranType == 'A' ) {
    //      $retVal = TRUE;
    //    }
    //    return($retVal);
    //}
    function preAuth($req)
    {
      $this->_resetParameters();
      $this->_mergeParameters($req);
      $this->_parameter_list['transaction_type'] = 'P';
      return $this->_execute();
    }
  /**
    Store Card in MeS
  */
  function storeCard($req)
  {
      $this->_resetParameters();
      $this->_mergeParameters($req);
      $this->_parameter_list['transaction_type'] = 'T';
      return $this->_execute();
  }
    /**
        Actually Executes the Request
    */
    private function _execute()
    {
        if (empty($this->_parameter_list['transaction_id'])) {
            $this->_parameter_list['transaction_id'] = md5(serialize($this));
        }

        ksort($this->_parameter_list);
        $post = http_build_query($this->_parameter_list);

        $ch = curl_init(self::API_URI);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        //curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Edoceo Radix Checkout MerchantE/2007.42');
        // curl_setopt($ch, CURLOPT_VERBOSE,true);
        // curl_setopt($ch, CURLOPT_STDERR, fopen('php://stdout','a'));
        $buf = curl_exec($ch);
        // Zend_Debug::dump($buf); exit;
        $pgr = new MerchantE_Response($buf);
        //exit;
        return $pgr;
    }
    /**
    */
    private function _mergeParameters($req)
    {
      foreach ($req as $k=>$v) {
          if ($v === false) {
              unset($this->_parameter_list[$k]);
          } else {
              $this->_parameter_list[$k] = $v;
          }
      }
    }
    /**
    */
    private function _resetParameters()
    {
        $this->_parameter_list = array();
        $this->_parameter_list['profile_id'] = $this->_profile_id;
        $this->_parameter_list['profile_key'] = $this->_profile_key;
    }
}


class MerchantE_Response
{
  private $data = null;
  
  function __construct($data)
  {
    parse_str($data,$this->data);
  }
  
  function __get($k)
  {
    if (isset($this->data[$k])) return $this->data[$k];
  }

  function as_string($raw=false)
  {
    if ($this->is_success())
    {
      $text.= "TPG Success Response:\n";
      foreach ($this->data as $k=>$v) $text.= "$k = $v\n";
    }
    else
    {
      $text.= "TPG Error Response: Error: #$this->error_code; $this->auth_response_text";
      if (isset($this->data['avs_result'])) $text.= "; AVS: $this->avs_result";
      if (isset($this->data['cvv2_result'])) $text.= "; CVV2: $this->cvv2_result";
      if ($raw)
      {
        $text.= "\n  ";
        foreach ($this->data as $k=>$v) $text.= "$k=$v; ";
      }
    }
    return "$text\n";
  }
  
  function is_success()
  {
    $ec = intval($this->error_code);
    if ($ec == 0) return true;
    else return false;
  }
}

