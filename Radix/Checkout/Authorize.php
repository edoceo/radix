<?php
/**
    @file
    @brief Authorize.net Checkout Interface

    @package radix
    @copyright 2004 edoceo, inc

    @see http://developer.authorize.net/guides/AIM/
    @see http://developer.authorize.net/api/cim/

    @note Ugly XML, but string building is faster/smaller than XML objects
*/

class radix_checkout_authorize
{
    const AIM_URI_LIVE = 'https://secure.authorize.net/gateway/transact.dll';
    const AIM_URI_TEST = 'https://test.authorize.net/gateway/transact.dll';
    const CIM_URI_LIVE = 'https://api.authorize.net/xml/v1/request.api';
    const CIM_URI_TEST = 'https://apitest.authorize.net/xml/v1/request.api';
    // const GATEWAY_URI = 'https://certification.authorize.net/gateway/transact.dll';

    const APPROVED = 1;
    const DECLINED = 2;
    const FAILED = 3;
    const REVIEW = 4;

    private $_user; // API Login ID
    private $_tkey; // Transaction Key
    private $_mode = 'test';

    function __construct($user,$tkey,$mode='test')
    {
        $this->_user = $user;
        $this->_tkey = $tkey;
        $this->_mode = $mode;
    }
    /**
        Execute the Transaction
        @param $card = array(full,date,csc)
        @param $cart = array(cost)
        @param $args = additional AIM Fields
    */
    function aimTransaction($card,$cart,$args=null)
    {
        // Radix::dump($card);
        // Radix::dump($cart);
        $post = array(
            'x_login' => $this->_user,
            'x_tran_key' => $this->_tkey,
            // 'x_allow_partial_Auth' => 'false',
            'x_version' => '3.1',
            'x_type' => 'AUTH_CAPTURE', // ...others?
            // 'x_recurring_billing' => 'false', // false|true
            'x_amount' => sprintf('%0.2f',$cart['cost']),
            'x_card_num' => $card['full'],
            'x_exp_date' => $card['mmyy'],
            'x_card_code' => $card['csc'],
            'x_delim_data' => 'true',
            'x_delim_char' => '|',
            // 'x_relay_response' => 'false',
            // 'x_description' => $note,
            // 'x_first_name' => 
        );
        if (!empty($args['x_test_request'])) {
            $post['x_test_request'] = 'true';
            $post['x_amount'] = self::APPROVED; // Approved - Amount==Test Response
        }
        // Radix::dump($post);

        $ch = curl_init(self::AIM_URI_TEST); // initiate curl object
        if ($this->_mode=='live') {
            $ch = curl_init(self::AIM_URI_LIVE); // initiate curl object
        }

        curl_setopt($ch, CURLOPT_HEADER, false); // set to 0 to eliminate header info from response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Returns response data instead of TRUE(1)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // uncomment this line if you get no gateway response.
        // additional options may be required depending upon your server configuration
        // you can find documentation on curl options at http://www.php.net/curl_setopt
        curl_setopt($ch,CURLOPT_VERBOSE,true);
        //curl_setopt($ch,CURLOPT_STDERR,fopen('php://stdout','a')); // '/tmp/curl.log');
        curl_setopt($ch,CURLOPT_STDERR,fopen('/tmp/curl.log','w')); // '/tmp/curl.log');

        // We don't need to convert to String, curl does that for us
        // $post = "";
        // foreach($data as $key => $value ) {
        //     $post .= "$key=" . urlencode( $value ) . "&";
        // }
        // $post = rtrim( $post, "& " );
        // echo http_build_query($post);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); // use HTTP POST to send form data

        $buf = curl_exec($ch); // execute curl post and store results in $post_response
        $inf = curl_getinfo($ch);
        //Radix::dump($inf);
        //Radix::dump($buf);
        curl_close($ch); // close curl object

        // echo "$post\n";
        
        // This line takes the response and breaks it into an array using the specified delimiting character
        // $response_array = explode($post_values["x_delim_char"],$post_response);
        $res = $this->_aimParse($buf);
        //Radix::dump($res,true);
        return $res;

    }
    /**
        Set the AIM Transaction Type
    */
    function aimType($x)
    {
        switch ($x) {
        case 'AUTH_CAPTURE': // Default
        case 'AUTH_ONLY':
        case 'CAPTURE_ONLY':
        case 'CREDIT':
        case 'PRIOR_AUTH_CAPTURE':
        case 'VOID':
            $this->_x_type = $x;
            break;
        default:
            throw new Exception("Unknown Transaction Type: $x",__LINE__);
        }
    }
    /**
        Parse response from Authorize.net
        @return array of response fields
    */
    private function _aimParse($str)
    {
        $map = array(
            'code',
            'code_sub',
            'reason_code',
            'reason_text',
            'auth_code',
            'avs_code',
            'transaction_id',
            'invoice_number',
            'description',
            'amount',
            'method',
            'transaction_type',
            'customer_id',
            'first_name',
            'last_name',
            'company',
            'address',
            'city',
            'state',
            'zip',
            'country',
            'phone',
            'fax',
            'email',
            'ship_first_name',
            'ship_last_name',
            'ship_company',
            'ship_address',
            'ship_city',
            'ship_state',
            'ship_zip',
            'ship_country',
            'tax',
            'duty',
            'freight',
            'tax_exempt',
            'purchase_order_number',
            'md5_hash',
            'ccv_code',
            'cav_code',
            'account',
            'card_type',
            'split_tender_id',
            'requested_amount',
            'balance_on_card',
        );
        //Radix::dump($str);
        //Radix::dump($map);
        //Radix::dump(explode('|',$str));
        $val = array_slice(explode('|',$str),0,count($map));
        $res = array_combine($map, $val );
        // Radix::dump($res);
        return $res;
    }
    /**
        Authenticate a CIM Session
    */
    function cimAuth()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml.= '<createCustomerProfileRequest xmlns= "AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xml.= '<merchantAuthentication>';
        $xml.= '<name>' . $this->_user . '</name>';
        $xml.= '<transactionKey>' . $this->_tkey . '</transactionKey>';
        $xml.= '</merchantAuthentication>';
        $xml.= '</createCustomerProfileRequest>';
        
        
        
    }
    /**
        Create a Customer Profile
        @param $cp the customer profile data
    */
    function cimCreateCustomerProfile($cp)
    {
        // Radix::dump($cp);
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml.= '<createCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xml.= '<merchantAuthentication>';
        $xml.= '<name>' . self::e($this->_user) . '</name>';
        $xml.= '<transactionKey>' . self::e($this->_tkey) . '</transactionKey>';
        $xml.= '</merchantAuthentication>';
        $xml.= '<profile>';
        $xml.= '<merchantCustomerId>' . self::e($cp['id']) . '</merchantCustomerId>';
        if (!empty($cp['description'])) $xml.= '<description>' . self::e($cp['description']) . '</description>';
        $xml.= '<email>' . self::e($cp['email']) . '</email>';
        $xml.= '<paymentProfiles>';
            $xml.= '<customerType>individual</customerType>';
            $xml.= '<billTo>';
                $xml.= '<address>' . self::e($cp['address']) . '</address>';
                $xml.= '<city>' . self::e($cp['city']) . '</city>';
                $xml.= '<state>' . self::e($cp['state']) . '</state>';
                if (!empty($cp['zip']))     $xml.= '<zip>' . self::e($cp['zip']) . '</zip>';
                if (!empty($cp['country'])) $xml.= '<country>' . self::e($cp['country']) . '</country>';
            $xml.= '</billTo>';
            $xml.= '<payment>';
                $xml.= '<creditCard>';
                $xml.= '<cardNumber>' . self::e($cp['card_account']) . '</cardNumber>';
                $xml.= '<expirationDate>' . self::e($cp['card_expires']) . '</expirationDate>';
                // cardCode only on validationMode == liveMode or testMode
                $xml.= '</creditCard>';
            $xml.= '</payment>';
        $xml.= '</paymentProfiles>';
        $xml.= '</profile>';
        $xml.= '<validationMode>none</validationMode>';
        $xml.= '</createCustomerProfileRequest>';

        $res = $this->_cimSend($xml);

        $file = sprintf('/tmp/cim-%d.xml',$cp['id']);
        $data = (print_r($cp,true) . "\n" . $xml . "\n" . $res);
        file_put_contents($file,$data);
    }
    /**
        @param $cim_id - CIM Custoimer Profile Id
        @param $full_cost
        @param $tax 
    */
    function cimCreateCustomerPaymentProfileRequest($cim_id,$full_cost,$tax)
    {
    }
    /**
        @param $cpid customerProfileId from CIM
        @param $cppid customerPaymentProfileId from CIM
        @param $full_cost numeric full cost
        @param $tax_info hash array ['amount','name','description']
    */
    function createCustomerProfileTransaction($cpid,$cppid,$full_cost,$tax_info)
    {
        // Radix::dump($cp);
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml.= '<createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xml.= '<merchantAuthentication>';
            $xml.= '<name>' . self::e($this->_user) . '</name>';
            $xml.= '<transactionKey>' . self::e($this->_tkey) . '</transactionKey>';
        $xml.= '</merchantAuthentication>';
        $xml.= '<transaction>';
        $xml.= '<profileTransAuthCapture>';
        $xml.= '<amount>' . self::e($full_cost) . '</amount>';
        if (!empty($tax_info)) {
            $xml.= '<tax>';
            $xml.= '<amount>' . self::e(sprintf('%0.2f',$tax_info['amount'])) . '</amount>';
            if (!empty($tax_info['name'])) {
                $xml.= '<name>' . self::e(substr($tax_info['name'],0,31)) . '</name>';
            }
            // $xml.= '<description></description>';
            $xml.= '</tax>';
        }
        // Shipping (Optional)
        // Line Items

        $xml.= '<customerProfileId>' . self::e($cpid) . '</customerProfileId>';
        $xml.= '<customerPaymentProfileId> ' . self::e($cppid) . '</customerPaymentProfileId>';

        
        // customerProfileId - Payment gateway-assigned ID associated with the customer profile
        // customerPaymentProfileId - Payment gateway-assigned ID associated with the customer payment profile
        // <customerShippingAddressId>30000</customerShippingAddressId>
        
        // <order>
        // <invoiceNumber>INV000001</invoiceNumber>
        // <description>description of transaction</description>
        // <purchaseOrderNumber>PONUM000001</purchaseOrderNumber>
        // </order>

        $xml.= '<taxExempt>false</taxExempt>';
        $xml.= '<recurringBilling>true</recurringBilling>';
        // <cardCode>000</cardCode>

        $xml.= '</profileTransAuthCapture>';
        $xml.= '</transaction>';
        $xml.= '</createCustomerProfileTransactionRequest>';

        $ret = $this->_cimSend($xml);

        return $ret;

    }
    /**
        @param $pid the Profile ID
    */
    function cimGetCustomerProfile($pid)
    {
        $xml.= '<?xml version="1.0" encoding="utf-8"?>';
        $xml.= '<getCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xml.= '<merchantAuthentication>';
        $xml.= '<name>' . self::e($this->_user) . '</name>';
        $xml.= '<transactionKey>' . self::e($this->_tkey) . '</transactionKey>';
        $xml.= '</merchantAuthentication>';
        $xml.= '<customerProfileId>' . self::e($pid) . '</customerProfileId>';
        $xml.= '</getCustomerProfileRequest>';

        $ret = $this->_cimSend($xml);

        return $ret;
    }

    /**
        @param $xml string to send
    */
    private function _cimSend($xml)
    {
        if (!is_string($xml)) {
            die('You are doing it wrong');
        }
        // Radix::dump($xml);

        //$ch = curl_init(self::CIM_URI_TEST);
        //if ($this->_mode == 'live') {
        $ch = curl_init(self::CIM_URI_LIVE);
        //}
        // Options
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_VERBOSE,true);
        curl_setopt($ch, CURLOPT_STDERR,fopen('/tmp/curl.log','w')); // '/tmp/curl.log');

        $buf = curl_exec($ch);
        $inf = curl_getinfo($ch);

        return $buf;
    }
    /**
        @todo parse the response from _cimSend()
    */
    private function _cimRecv($buf)
    {
        
    }
    
    private static function e($s) { return htmlentities($s,ENT_QUOTES,'UTF-8',false); }
}
