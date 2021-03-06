<?php
/**
    @file
    @brief Radix Amazon Mechanical Turk Interface

    @see http://aws.amazon.com/code/Amazon-Mechanical-Turk/464 - old, but had some influence on this library
    @see https://portal.aws.amazon.com/gp/aws/securityCredentials for access keys

*/

class radix_aws_mturk
{
    const SERVICE = 'AWSMechanicalTurkRequester';
    const VERSION = '2012-03-25';
    const REST_URI = 'https://mechanicalturk.amazonaws.com/onca/xml';
    const SOAP_URI = 'https://mechanicalturk.amazonaws.com/onca/soap';
    const UA = 'Edoceo Radix AWS v2012.28';

    private $_access;
    private $_secret;

    /**
        @param $a Access Key
        @param $s Secret Key

    */
    public function __construct($a, $s)
    {
        $this->_access = $a;
        $this->_secret = $s;
    }
    /**
        Approve and Assignment
        @param $aid Assignment ID
        @param $rfb Requester Feedback
    */
    public function ApproveAssignment($aid,$rfb=null)
    {
        $arg = array(
            'Operation' => 'ApproveAssignment',
            'AssignmentId' => $aid,
            'RequesterFeedback' => $rfb,
        );
        $r = $this->_http($arg);
        return $r;
    }
    /**
    */
    public function ApproveRejectedAssignment($aid,$rfb=null)
    {
        $arg = array(
            'Operation' => 'ApproveRejectedAssignment',
            'AssignmentId' => $aid,
            'RequesterFeedback' => $rfb,
        );
        $r = $this->_http($arg);
        return $r;
    }
    /**
        @param $qti QualificationTypeId
        @param $wid WorkerId
        @param $val IntegerValue
        @param $sn SendNotification
    */
    public function AssignQualification($qti,$wid,$val=null,$sn=null)
    {
        $arg = array(
            'Operation' => 'AssignQualification',
            'QualificationTypeId' => $qti,
            'WorkerId' => $wid,
            'IntegerValue' => $val,
            'SendNotification' => $sn,
        );
        $r = $this->_http($arg);
        return $r;
    }
    /**
        @param $wid WorkerId
        @param $why Reason
    */
    public function BlockWorker($wid,$why)
    {
        $arg = array(
            'Operation' => 'BlockWorker',
            'WorkerId' => $wid,
            'Reason' => $why,
        );
        return $this->_http($arg);
    }

    /**
    */
    public function ChangeHITTypeOfHIT() {}

    /**
    */
    public function CreateHIT($hit)
    {
        $arg = array(
            'Operation' => 'CreateHIT',
        );
        return $this->_http(array_merge($arg,$hit));
    }

    /**
    */
    public function CreateQualificationType($arg)
    {
        $req = array(
            'Operation' => 'CreateQualificationType',
        );
        return $this->_http(array_merge($req,$arg));
    }

    /**
        @param $hid HITId
    */
    public function DisableHIT($hid)
    {
        $arg = array(
            'Operation' => 'DisableHIT',
            'HITId' => $hid,
        );
        return $this->_http($arg);
    }

    /**
        @param $hid HITId
    */
    public function DisposeHIT($hid)
    {
        $arg = array(
            'Operation' => 'DisposeHIT',
            'HITId' => $hid,
        );
        return $this->_http($arg);
    }

    /**
    */
    public function DisposeQualificationType() {}

    /**
        @param $hid HITId
    */
    public function ExtendHIT($hid)
    {
        $arg = array(
            'Operation' => 'ExtendHIT',
            'MaxAssignmentsIncrement' => null,
            'ExpirationIncrementInSeconds' => null,
            'UniqueRequestToken' => null
        );
        return $this->_http($arg);
    }

    /**
        @param $hid HITId
    */
    public function ForceExpireHIT($hid)
    {
        $arg = array(
            'Operation' => 'ForceExpireHIT',
            'HITId' => $hid,
        );
        return $this->_http($arg);
    }

    /**
    */
    public function GetAccountBalance()
    {
        $arg = array(
            'Operation' => 'GetAccountBalance'
        );
        return $this->_http($arg);
    }

    /**
    */
    public function GetAssignment() {}

    /**
        @param $hid HITId
    */
    public function GetAssignmentsForHIT($hid)
    {
        $arg = array(
            'Operation' => 'GetAssignmentsForHIT',
            'HITId' => $hid,
            'PageSize' => 100,
        );
        return $this->_http($arg);
    }

    /**
    */
    public function GetBlockedWorkers() {}

    /**
    */
    public function GetBonusPayments() {}


    /**
        @param $aid AssignmentId
        @param $qid QuestionIdentifier
    */
    public function GetFileUploadURL($aid,$qid)
    {
        $arg = array(
            'Operation' => 'GetFileUploadURL',
            'AssignmentId' => $aid,
            'QuestionIdentifier' => $qid,
        );
        return $this->_http($arg);
    }

    /**
    */
    public function GetHIT($hid)
    {
        $arg = array(
            'Operation' => 'GetHIT',
            'HITId' => $hid,
        );
        return $this->_http($arg);
    }

    /**
    */
    public function GetHITsForQualificationType() {}

    /**
    */
    public function GetQualificationsForQualificationType() {}

    /**
    */
    public function GetQualificationRequests()
    {
      if     (!$this->QualificationTypeId)                                              return $this->mtError("Missing QualificationTypeId Parameter");
      else                                                                              $this->QueryData['QualificationTypeId']     = $this->QualificationTypeId;
      if     (is_numeric($this->PageSize) && $this->PageSize > 0)                       $this->QueryData['PageSize']      = $this->PageSize;
      if     (is_numeric($this->PageNumber) && $this->PageNumber > 0)                   $this->QueryData['PageNumber']    = $this->PageNumber;
      if     ($this->SortProperty && !in_array($this->SortProperty, $this->validGQRSP)) return $this->mtError("Invalid Sort Property Value (AcceptTime/SubmitTime/AssignmentStatus");
      elseif ($this->SortProperty)                                                      $this->QueryData['SortProperty']  = $this->SortProperty;
      if     ($this->SortDirection && !in_array($this->SortDirection, $this->validSD))  return $this->mtError("Invalid SortDirection Value (Ascending/Descending)");
      elseif ($this->SortDirection)                                                     $this->QueryData['SortDirection'] = $this->SortDirection;
      return $this->mtMakeRequest();
    }

    /**
    */
    public function GetQualificationScore()
    {
      if     (!$this->QualificationTypeId) return $this->mtError("Missing QualificationTypeId Parameter");
      else                                 $this->QueryData['QualificationTypeId'] = $this->QualificationTypeId;
      if     (!$this->SubjectId)           return $this->mtError("Missing SubjectId Parameter");
      else                                 $this->QueryData['SubjectId']           = $this->SubjectId;
      return $this->mtMakeRequest();
    }

    /**
    */
    public function GetQualificationType()
    {
        if     (!$this->QualificationTypeId) return $this->mtError("Missing QualificationTypeId Parameter");
        else                                 $this->QueryData['QualificationTypeId'] = $this->QualificationTypeId;
        return $this->mtMakeRequest();
    }

    /* Retrieve various statistics */
    public function GetRequesterStatistic()
    {
      if     (!$this->Statistic)                                                 return $this->mtError("Missing Statistic Parameter");
      elseif (!in_array($this->Statistic, $this->validStats))                    return $this->mtError("Invalid Statistic Type");
      else                                                                       $this->QueryData['Statistic'] = $this->Statistic;
      if     ($this->TimePeriod && !in_array($this->TimePeriod, $this->validTP)) return $this->mtError("Invalid TimePeriod Type");
      elseif ($this->TimePeriod)                                                 $this->QueryData['TimePeriod'] = $this->TimePeriod;
      if     (is_numeric($this->Count) && $this->Count > 0)                      $this->QueryData['Count']      = $this->Count;
      return $this->mtMakeRequest();
   }

   /**
   */
   public function GetRequesterWorkerStatistic() {}

    /**
        @return xml string
    */
    public function GetReviewableHITs()
    {
        $arg = array(
            'Operation' => 'GetReviewableHITs',
        );
        return $this->_http($arg);
    }

    /**
        @param $hid Hit ID
    */
    public function GetReviewResultsForHIT($hid)
    {
        $arg = array(
            'Operation' => 'GetReviewResultsForHIT',
            'HITId' => $hid,
        );
        return $this->_http($arg);
    }


    /**
    */
    public function GrantBonus()
    {
      if     (!$this->WorkerId)                         return $this->mtError("Missing WorkerId Parameter(s)");
      elseif (!$this->AssignmentId)                     return $this->mtError("Missing AssignmentId");
      elseif (!$this->BonusAmount)                      return $this->mtError("Missing BonusAmount");
      if     (!mtCheckPriceData(1, $this->BonusAmount)) return false;
      return $this->mtFakeSoap();
    }

    /**
    */
    public function GrantQualification()
    {
      if     (!$this->QualificationRequestId)        return $this->mtError("Missing QualificationRequestId Parameter");
      else                                           $this->QueryData['QualificationRequestId'] = $this->QualificationRequestId;
      if     (!is_numeric($this->IntegerValue))      return $this->mtError("Missing IntegerValue Parameter");
      else                                           $this->QueryData['IntegerValue']           = $this->Value;
      return $this->mtMakeRequest();
    }

    /* GET HELP! */
    public function Help()
    {
      if     (!$this->HelpType)                           return $this->mtError("Missing HelpType Parameter");
      elseif (!in_array($this->HelpType, $this->validHT)) return $this->mtError("Invalid HelpType Type");
      else                                                $this->QueryData['HelpType'] = $this->HelpType;
      if     (!$this->About)                              return $this->mtError("Missing About Parameter");
      elseif (!in_array($this->About, $this->validOps))   return $this->mtError("Invalid About Type");
      else                                                $this->QueryData['About']    = $this->About;
      return $this->mtMakeRequest();
    }

    /**
    */
    public function NotifyWorkers()
    {
      if     (!$this->Subject)           return $this->mtError("Missing Subject Parameter");
      else                               $this->QueryData['Subject']     = $this->Subject;
      if     (!$this->MessageText)       return $this->mtError("Missing MessageText Parameter");
      else                               $this->QueryData['MessageText'] = $this->MessageText;
      if     (!$this->WorkerId)          return $this->mtError("Missing WorkerId Parameter(s)");
      elseif (is_array($this->WorkerId)) foreach ($this->WorkerId as $wk => $wi) $this->QueryData[("WorkerId." . $wk + 1)] = $wi;
      else                               $this->QueryData['WorkerId'] = $this->WorkerId;
      return $this->mtMakeRequest();
    }

    /**
    */
    public function RegisterHITType()
    {
      if     (!$this->Title)                       return $this->mtError("Missing Title Parameter");
      elseif (!$this->Description)                 return $this->mtError("Missing Description Parameter");
      elseif (!$this->Amount)                      return $this->mtError("Missing Amount Parameter");
      elseif (!$this->AssignmentDurationInSeconds) return $this->mtError("Missing AssignmentDurationInSeconds Parameter");
      elseif (!$this->LifetimeInSeconds)           return $this->mtError("Missing LifetimeInSeconds Parameter");
      if     (!$this->CurrencyCode)                $this->CurrencyCode = "USD";

      /* Qualification Array Checking */
      if     (is_array($this->QualificationRequirement))
      {
         foreach ($this->QualificationRequirement as $itr => $val)
         {
            $fg = "(Group {$itr})";
            if     (!$val['QualificationTypeId'])                   return $this->mtError("Missing QualificationTypeId Parameter {$fg}");
            elseif (!$val['Comparator'])                            return $this->mtError("Missing Comparator Parameter {$fg}");
            elseif (!in_array($val['Comparator'], $this->validCPT)) return $this->mtError("Invalid Comparator Parameter {$fg}");
            elseif (!$val['Value'])                                 return $this->mtError("Missing Value Parameter {$fg}");
            elseif (!is_numeric($val['Value']))                     return $this->mtError("Invalid Value Parameter {$fg}");
         }
      }

      return $this->mtFakeSoap();
    }

    /**
    */
    public function RejectAssignment()
    {
      if   (!$this->AssignmentId)                                                  return $this->mtError("Missing AssignmentId");
      else                                                                         $this->QueryData['AssignmentId'] = $this->AssignmentId;
      if     ($this->RequesterFeedback && strlen($this->RequesterFeedback) > 1024) return $this->mtError("RequesterFeedback entry is too long!");
      elseif ($this->RequesterFeedback)                                            $this->QueryData['RequesterFeedback'] = $this->RequesterFeedback;
      return $this->mtMakeRequest();
    }

    /**
    */
    public function RejectQualificationRequest()
    {
      if     (!$this->QualificationRequestId)        return $this->mtError("Missing QualificationRequestId Parameter");
      else                                           $this->QueryData['QualificationRequestId'] = $this->QualificationRequestId;
      if     ($this->Reason)                         $this->QueryData['Reason']                 = $this->Reason;
      return $this->mtMakeRequest();
    }

    /**
    */
    public function RevokeQualification() {}

    /**
        @param $sp SortProperty
        @param $sd SortDirection
        @param $ps PageSize; default:25
        @param $pn PageNumber
    */
    public function SearchHITs($sp=null,$sd=null,$ps=25,$pn=null)
    {
        $arg = array(
            'Operation' => 'SearchHITs',
            'SortProperty' => $sp,
            'SortDirection' => $sd,
            'PageSize' => $ps,
            'PageNumber' => $pn,
        );
        $r = $this->_http($arg);
        return $r;
    }

    /**
    */
    public function SearchQualificationTypes()
    {
       if     ($this->Query)                                                                     $this->QueryData['Query']      = $this->Query;
       if     (is_numeric($this->PageSize) && $this->PageSize > 0)                               $this->QueryData['PageSize']      = $this->PageSize;
       if     (is_numeric($this->PageNumber) && $this->PageNumber > 0)                           $this->QueryData['PageNumber']    = $this->PageNumber;
       if     ($this->SortProperty)                                                              $this->QueryData['SortProperty']  = $this->SortProperty;
       if     ($this->SortDirection && !in_array($this->SortDirection, $this->validSD))          return $this->mtError("Invalid SortDirection Value (Ascending/Descending)");
       elseif ($this->SortDirection)                                                             $this->QueryData['SortDirection'] = $this->SortDirection;
       if     ($this->MustBeRequestable && !in_array($this->MustBeRequestable, $this->validMBR)) return $this->mtError("Invalid MustBeRequestable Value (true/false)");
       elseif ($this->MustBeRequestable)                                                         $this->QueryData['MustBeRequestable'] = $this->MustBeRequestable;
       return $this->mtMakeRequest();
    }

    /**
    */
    public function SendTestEventNotification()
    {
       if     (is_array($this->Notification))
       {
          foreach ($this->Notification as $itr => $val)
          {
            if (!$this->mtCheckNotificationData($itr, $val)) return false;
            if (!isset($val['Version']) && $this->Version)   $this->Notification[$itr]['Version'] = $this->Version; /* Helpful */
          }
       }
       if     ($this->TestEventType && !in_array($this->TestEventType, $validET)) return $this->mtError("Invalid TestEventType Value");
       return $this->mtFakeSoap();
    }

    /**
    */
    public function SetHITAsReviewing()
    {
       if     (!$this->HITId) return $this->mtError("Missing HITId Parameter");
       else                   $this->QueryData['HITId'] = $this->HITId;
       if     ($this->Revert) $this->QueryData['Revert'] = 'true';
       else                   $this->QueryData['Revert'] = 'false';
       return $this->mtMakeRequest();
    }

    /**
    */
    public function SetHITTypeNotification()
    {
       if     (!$this->HITTypeId)                                          return $this->mtError("Missing HITTypeId Parameter");
       if     ($this->Active && !in_array($this->Active, $this->validMBR)) return $this->mtError("Invalid Active Parameter");
       /* Notification Array Checking */
       if     (is_array($this->Notification))
       {
          foreach ($this->Notification as $itr => $val)
          {
            if (!$this->mtCheckNotificationData($itr, $val)) return false;
            if (!isset($val['Version']) && $this->Version)   $this->Notification[$itr]['Version'] = $this->Version; /* Helpful */
          }
       }
       return $this->mtFakeSoap();
    }

    /**
    */
    public function UnblockWorker() { }

    /**
    */
    public function UpdateQualificationScore()
    {
      if     (!$this->QualificationTypeId)                          return $this->mtError("Missing QualificationTypeId Parameter");
      else                                                          $this->QueryData['QualificationTypeId'] = $this->QualificationTypeId;
      if     (!$this->SubjectId)                                    return $this->mtError("Missing SubjectId Parameter");
      else                                                          $this->QueryData['SubjectId']           = $this->SubjectId;
      if     (!is_numeric($this->IntegerValue))                     return $this->mtError("Invalid IntegerValue Parameter");
      elseif ($this->IntegerValue < 0 || $this->IntegerValue > 100) return $this->mtError("Invalid IntegerValue Parameter");
      else                                                          $this->QueryData['IntegerValue'] = $this->IntegerValue;
      return $this->mtMakeRequest();
    }

    /**
    */
    public function UpdateQualificationType($qti)
    {
        // if     (!$this->QualificationTypeId)                                                                  return $this->mtError("Missing QualificationTypeId Parameter");
        // if     ($this->QualificationTypeStatus && !in_array($this->QualificationTypeStatus, $this->validQTS)) return $this->mtError("Invalid QualificationTypeStatus Value (Active/Inactive)");
        // return $this->mtFakeSoap();
        $arg = array(
            'Operation' => 'UpdateQualificationType',
            'QualificationTypeId' => $qti,
            // 'RetryDelayInSeconds' =>
            // QualificationTypeStatus
            // Description
            // Test
            // AnswerKey
            // TestDurationInSeconds
            // AutoGranted
            // AutoGrantedValue
        );
        $r = $this->_http($arg);
        return $r;
    }
    /**
        @param $arg Request URI
        @return XML Data buffer
    */
    private function _http($arg)
    {
        $req = array(
            'AWSAccessKeyId' => $this->_access,
            'Service' => self::SERVICE,
            'Signature' => null,
            'Timestamp' => date('c'),
            // 'ResponseGroup' => null,
            // 'Version' => self::VERSION,
            // 'Validate' => null,
            // 'Credential' => null,
        );

        $req = array_merge($req,$arg);

        $req['Signature'] = $this->_sign($req['Operation'],$req['Timestamp']);

        $uri = self::REST_URI . '?' . http_build_query($req);

        $ch = curl_init($uri);
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

        curl_setopt($ch, CURLOPT_USERAGENT, self::UA);

        $buf = curl_exec($ch);
        curl_close($ch);

        return $buf;
    }

    /**
        @param $op Operation
        @param $ts Time Stamp
        @return base64 encoded stuff
    */
    private function _sign($op,$ts)
    {
        $sign = self::SERVICE . $op . $ts;
        $hmac = $this->_sign_hmac_sha1($sign);
        return base64_encode($hmac);
    }

    /**
        @param $s string to sign
        @return binary data
    */
    private function _sign_hmac_sha1($s)
    {
      return pack('H*', sha1((str_pad($this->_secret, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
                 pack('H*', sha1((str_pad($this->_secret, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $s))));

    }
}
