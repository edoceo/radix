<?php
/**
    @file
    @brief Class to Communicate with the Merchant E Solutions Gateway
    @version $Id: VirtualMerchant.php 2113 2012-03-26 04:08:02Z code@edoceo.com $

    @package radix

*/

/*
Data string sent to processxml.do
.../VirtualMerchant/processxml.do?xmldata=<txn><ssl_merchant_ID>123456</ssl_merchant_ID><ssl_us
er_id>123456</ssl_user_id><ssl_pin>V6NJ3A</ssl_pin><ssl_transaction_type>ccsale</ssl_transaction_t
ype><ssl_card_number>1111111111111111</ssl_card_number><ssl_exp_date>1210</ssl_exp_date><s
sl_amount>2.34</ssl_amount><ssl_salestax>0.00</ssl_salestax><ssl_cvv2cvc2_indicator>1</ssl_cvv2cv
c2_indicator><ssl_cvv2cvc2>321</ssl_cvv2cvc2><ssl_invoice_number>1234</ssl_invoice_number><ssl_
customer_code>1111</ssl_customer_code><ssl_first_name>customer</ssl_first_name><ssl_last_name>
name</ssl_last_name><ssl_avs_address>1234 main st.</ssl_avs_address><ssl_address2>apt
b</ssl_address2><ssl_city>any
town</ssl_city><ssl_state>ST</ssl_state><ssl_avs_zip>55555</ssl_avs_zip><ssl_phone>555-555-
5555</ssl_phone><ssl_email>customer@email.com</ssl_email></txn>
Response
<?xml version="1.0" encoding="UTF-8" ?>
- <txn>
  <ssl_result>0</ssl_result>
  <ssl_result_message>APPROVAL</ssl_result_message>
  <ssl_card_number>11********1111</ssl_card_number>
  <ssl_exp_date>1210</ssl_exp_date>
  <ssl_amount>2.34</ssl_amount>
.
. (other xml fields)
.
  <ssl_txn_id>14039F9DA-1111-2222-1111-634707DC3413</ssl_txn_id>
  <ssl_approval_code>N35032</ssl_approval_code>
  <ssl_cvv2_response>P</ssl_cvv2_response>
  <ssl_avs_response>X</ssl_avs_response>
  <ssl_account_balance>0.00</ssl_account_balance>
  <ssl_txn_time>10/26/2006 12:35:03 PM</ssl_txn_time>
  </txn>

*/

class radix_checkout_virtualmerchant
{
    const API_URI = 'https://www.myvirtualmerchant.com/VirtualMerchant/processxml.do';

    private $_account_mid;
    private $_account_uid;
    private $_account_pin;


    private $_parameter_list;

    /**
    */
    function ccSale($req)
    {
      $this->_resetParameters();
      $this->_mergeParameters($req);
      $this->_parameter_list['ssl_transaction_type'] = 'CCSALE';
      return $this->_execute();
    }
    /**
        Performs a Pre-Auth
    */
    function ccAuthOnly($req)
    {
      $this->_resetParameters();
      $this->_mergeParameters($req);
      $this->_parameter_list['ssl_transaction_type'] = 'CCAUTHONLY';
      return $this->_execute();
    }
    /**
        Actually Executes the Request
    */
    private function _execute()
    {
        //if (empty($this->_parameter_list['transaction_id'])) {
        //    $this->_parameter_list['transaction_id'] = md5(serialize($this));
        //}

        // Test Visa: 4544182174537267
        // Test MC:   5460506048039935

        if ($this->_parameter_list['ssl_card_number'] == self::FAKE_SUCCESS) {
            $ret = new VirtualMerchant_Response('fake-success');
            return $ret;
        }

        ksort($this->_parameter_list);
        // Do GET w/XML
        $xml = '<txn>';
        foreach ($this->_parameter_list as $k => $v) {
            $xml.= sprintf('<%s>%s</%s>',$k,htmlspecialchars($v,ENT_QUOTES),$k);
        }
        $xml.= '</txn>';
        ////Zend_Debug::dump($xml);
        //$uri = sprintf('%s?%s',self::GATEWAY_URI,http_build_query(array('xmldata' => $xml) ));
        $uri = sprintf('%s?xmldata=%s',self::GATEWAY_URI, rawurlencode($xml) );
        $ch = curl_init($uri);

        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Referer: https://domain.com/') );
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Edoceo VirtualMerchant Gateway/0.1');
        curl_setopt($ch, CURLOPT_VERBOSE,false);
        //curl_setopt($ch, CURLOPT_STDERR, fopen('php://stdout','w'));
        $buf = curl_exec($ch);
        $ret = new VirtualMerchant_Response($buf);
        return $ret;
    }
    /**
        Merge Requested Parameters to the Parameter List
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
        Re-Initalise Request Parameters
    */
    private function _resetParameters()
    {
        $this->_parameter_list = array();
        // $this->_parameter_list['ssl_test_mode'] = 'TRUE';
        $this->_parameter_list['ssl_merchant_id'] = $this->_account_mid;
        $this->_parameter_list['ssl_user_id'] = $this->_account_uid;
        $this->_parameter_list['ssl_pin'] = $this->_account_pin;
        //$this->_parameter_list['ssl_amount'] = 1;
        //$this->_parameter_list['ssl_salestax'] = 0;
        //$this->_parameter_list['ssl_card_number'] = 0;
        //$this->_parameter_list['ssl_exp_date'] = YYMM
        //$this->_parameter_list['ssl_cvv2cvc2_indicator'] = '0|1|2|9';
        //$this->_parameter_list['ssl_cvv2cvc2'] = null;
        //$this->_parameter_list['ssl_description'] = null;
        //$this->_parameter_list['ssl_invoice_number'] = null;
        //$this->_parameter_list['ssl_customer_code'] = null;
        //$this->_parameter_list['ssl_company'] = null;
        //$this->_parameter_list['ssl_first_name'] = null;
        //$this->_parameter_list['ssl_last_name'] = null;
        //$this->_parameter_list['ssl_avs_address'] = null;
        //$this->_parameter_list['ssl_address2'] = null;
        //$this->_parameter_list['ssl_city'] = null;
        //$this->_parameter_list['ssl_state'] = null;
        //$this->_parameter_list['ssl_avs_zip'] = null;
        //$this->_parameter_list['ssl_country'] = null;
        //$this->_parameter_list['ssl_phone'] = null;
        //$this->_parameter_list['ssl_email'] = null;

        //$this->_parameter_list['ssl_ship_to_company'] = null;
        //$this->_parameter_list['ssl_ship_to_first_name'] = null;
        //$this->_parameter_list['ssl_ship_to_last_name'] = null;
        //$this->_parameter_list['ssl_ship_to_address1'] = null;
        //$this->_parameter_list['ssl_ship_to_address2'] = null;
        //$this->_parameter_list['ssl_ship_to_city'] = null;
        //$this->_parameter_list['ssl_ship_to_state'] = null;
        //$this->_parameter_list['ssl_ship_to_zip'] = null;
        //$this->_parameter_list['ssl_ship_to_country'] = null;
        //$this->_parameter_list['ssl_ship_to_phone'] = null;
        //$this->_parameter_list['ssl_ship_to_email_header'] = null;
        //$this->_parameter_list['ssl_ship_to_email_apprvl_header_html'] = null;
        //$this->_parameter_list['ssl_ship_to_email_decl_header_html'] = null;

        $this->_parameter_list['ssl_show_form'] = 'FALSE';

    }
}


/*
// Success Response
object(VirtualMerchant_Response)#47 (1) {
  ["_xml:private"] => object(SimpleXMLElement)#48 (14) {
    ["ssl_card_number"] => string(14) "49********7027"
    ["ssl_exp_date"] => string(4) "0120"
    ["ssl_amount"] => string(4) "1.23"
    ["ssl_salestax"] => string(4) "0.00"
    ["ssl_invoice_number"] => object(SimpleXMLElement)#62 (0) {
    }
    ["ssl_description"] => object(SimpleXMLElement)#63 (0) {
    }
    ["ssl_result"] => string(1) "0"
    ["ssl_result_message"] => string(8) "APPROVED"
    ["ssl_txn_id"] => string(35) "00000000-0000-0000-0000-00000000000"
    ["ssl_approval_code"] => string(6) "123456"
    ["ssl_cvv2_response"] => string(1) "P"
    ["ssl_avs_response"] => string(1) "X"
    ["ssl_account_balance"] => string(4) "0.00"
    ["ssl_txn_time"] => string(22) "08/12/2009 12:32:52 AM"
  }
}
*/


class VirtualMerchant_Response
{
    private $_xml = null;

    private static $_avs_error_list = array(
        'A' => 'Address Match, Postal Code Unknown',
        'B' => 'Address Match, Postal Code Format Error',
        'C' => 'Address and Postal Code Format Error',
        'D' => 'International Address and Postal Code Match',
        'E' => 'Unknown AVS Error',
        'G' => 'Service Unsupported - Non-US Issuer',
        'I' => 'Address not Verified - International',
        'M' => 'International Address and Postal Code Match',
        'N' => 'Address and Postal Code Unknown',
        'O' => 'Invalid Response',
        'P' => 'Address Format Error, Postal Code Match',
        'R' => 'System Unavailable',
        'S' => 'Service Unsupported by Issuer',
        'U' => 'Information Unavailable',
        'W' => 'Address Unknown, Postal Code Full Match',
        'X' => 'Exact Match',
        'Y' => 'Address and Postal Code Match',
        'Z' => 'Address Unknown, Postal Code Match',
    );

    private static $_cvv2_error_list = array(
        'M' => 'Success',
        'N' => 'Failure',
        'P' => 'Not Processed',
        'S' => 'CVV2 Data Required',
        'U' => 'CVV2 Unsupported by Issuer',
    );

    function __construct($xml)
    {
        if ($xml === 'fake-success') {
            $this->_xml = new stdClass();
            $this->_xml->errorCode = 0;
            $this->_xml->ssl_result = 0;
            $this->_xml->ssl_result_message = 'APPROVAL';
            $this->_xml->ssl_approval_code = '123456';
            $this->_xml->ssl_txn_time = date('Y-m-d H:i:s');
            $this->_xml->ssl_avs_response = 'FAKE';
            $this->_xml->ssl_cvv2_response = 'FAKE';
            return;
        }
        $this->_xml = simplexml_load_string($xml);
    }
    /**
        Dumps all the data to a String
    */
    function __toString()
    {
        $str = array();
        if ( $this->_xml instanceof SimpleXmlDocument) {
            $str[] = '_xml=' . trim($this->_xml->asXML());
            foreach ($this->_xml->children() as $n) {
                $str[] = $n->getName() . '=' . strval($n);
            }
        }
        if ($this->isSuccess()) {
            $str = sprintf('Success: %s',implode('; ',$str));
        } else {
            $str = sprintf('Error: %s',implode('; ',$str));
        }
        return trim($str);
    }
    /**
        Returns True if the Response is a Successfull One
    */
    function isSuccess()
    {
        // If ErrorCode != 0 then we have an error
        $x = intval($this->_xml->errorCode);
        if ($x != 0) {
            return false;
        }
        // If ssl_result != 0 then we have an error
        $x = intval($this->_xml->ssl_result);
        if ($x != 0) {
            return false;
        }
        // Has to be True
        $x = strval($this->_xml->ssl_result_message);
        if ($x == 'APPROVAL') {
            return true;
        }
        return false;
    }
    /**
    */
    function getMessage()
    {
        $str = null;
        if ($this->isSuccess()) {
            $str = sprintf('Success #%d: %s', trim($this->_xml->ssl_approval_code), $this->_xml->ssl_txn_time);
            if (isset($this->_xml->ssl_avs_response)) {
                $str.= sprintf('; AVS: %s',$this->_xml->ssl_avs_response);
            }
            if (isset($this->_xml->ssl_cvv2_response)) {
                $str.= sprintf('; CVV2: %s',$this->_xml->ssl_cvv2_response);
            }
        } else {
            $x = intval($this->_xml->errorCode);
            if ($x != 0) {
                // @note XML Level Error
                $str = sprintf('Error #%d: %s (%s)', $this->_xml->errorCode, $this->_xml->errorName, $this->_xml->errorMessage);
            }

            $x = intval($this->_xml->ssl_result);
            if ($x != 0) {
                // @note Gateway Level Error
                $str = sprintf('Error #%d: %s', $x, $this->_xml->ssl_result_message);
            }
            // "Error Response: Error: #$this->error_code; $this->auth_response_text";
            if (isset($this->_xml->ssl_avs_response)) {
                $x = trim(strval($this->_xml->ssl_avs_response));
                if (array_key_exists($x,self::$_avs_error_list)) {
                    $str.= sprintf('; AVS: %s', self::$_avs_error_list[$x] );
                } else {
                    $str.= sprintf('; AVS: %s',$x);
                }
            }
            // Lookup the CVV2 Account
            if (isset($this->_xml->ssl_cvv2_response)) {
                $x = trim(strval($this->_xml->ssl_cvv2_response));
                if (array_key_exists($x,self::$_cvv2_error_list)) {
                    $str.= sprintf('; CVV2: %s', self::$_cvv2_error_list[$x] );
                } else {
                    $str.= sprintf('; CVV2: %s',$this->_xml->ssl_cvv2_response);
                }
            }
        }
        return $str;
    }
}

