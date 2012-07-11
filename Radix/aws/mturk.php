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
    const UA = 'Radix AWS v2012.28';

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
        $r = $this->_http($arg);
        return $r;
    }
    public function ChangeHITTypeOfHIT() {}

    /**
    */
    function CreateHIT()
    {
      if     ($this->HITTypeId && $this->mtBreaksHTI()) return $this->mtError("Incompatible mixing of HITTypeId and other values");
      elseif (!$this->HITTypeId)
      {
         /* Values only applicable without HITTypeID */
         if     (!$this->Title)                            return $this->mtError("Missing Title Parameter");
         elseif (!$this->Description)                      return $this->mtError("Missing Description Parameter");
         elseif (!$this->Amount)                           return $this->mtError("Missing Amount Parameter");
         if     (!$this->CurrencyCode)                     $this->CurrencyCode = "USD";
         elseif (!$this->AssignmentDurationInSeconds)      return $this->mtError("Missing AssignmentDurationInSeconds Parameter");

         /* Qualification Array Checking */
         if     (is_array($this->QualificationRequirement))
         {
            foreach ($this->QualificationRequirement as $itr => $val)
            {
               $fg = "(Group {$itr})";
               if     (!$val['QualificationTypeId'])                   return $this->mtError("Missing QualificationTypeId Parameter {$fg}");
               elseif (!$val['Comparator'])                            return $this->mtError("Missing Comparator Parameter {$fg}");
               elseif (!in_array($val['Comparator'], $this->validCPT)) return $this->mtError("Invalid Comparator Parameter {$fg}");
               if ($val['QualificationTypeId'] == "00000000000000000071") {
                 /* Locale Value */
                 if (!$val['LocaleValue']) return $this->mtError("Need a LocaleValue to accompany a Locale Qualification {$fg}");
               } else {
                 /* Regular Value */
                 if (!isset($val['IntegerValue']) || !is_numeric($val['IntegerValue'])) return $this->mtError("Need an InterValue to accompany a qualification {$fg}");
               }
               if (isset($val['RequiredToPreview']) && !in_array($val['RequiredToPreview'], $this->validMBR)) return $this->mtError("Invalid RequiredToPreview value (true/false) {$fg}");
            }
         }
      }

      /* Required values for either type */
      if     (!$this->LifetimeInSeconds)                return $this->mtError("Missing LifetimeInSeconds Parameter");
      elseif (!$this->Question)                         return $this->mtError("Missing Question Parameter");


      return $this->mtFakeSoap();
   }
    /**

    */
    public function CreateQualificationType()
    {
        $arg = array(
            'Operation' => 'CreateQualificationType',
        );
        // if     (!$this->Name)                                               return $this->mtError("Missing Name Parameter");
        // elseif (!$this->Description)                                        return $this->mtError("Missing Description Parameter");
        // elseif (!$this->QualificationTypeStatus)                            return $this->mtError("Missing QualificationTypeStatus Parameter");
        // elseif (!in_array($this->QualificationTypeStatus, $this->validQTS)) return $this->mtError("Invalid QualificationTypeStatus Value");
        // elseif ($this->AnswerKey && !$this->Test)                           return $this->mtError("AnswerKey cannot be provided without Test!");
        // return $this->mtFakeSoap();
    }

    /**
        @param $hid HITId
    */
    public function DisableHIT($hid)
    {
        $arg = array(
            'Operation' => 'DisableHIT'
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
            'Operation' => 'DisposeHIT'
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
            'Operation' => 'ForceExpireHIT'
            'HITId' => $hid,
        );
        return $this->_http($arg);
    }

    /**
    */
    function GetAccountBalance()
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
    function GetAssignmentsForHIT($hid)
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
    function GetHIT($hid)
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
   /* Get qualification type data */
   function GetQualificationType()
   {
      if     (!$this->QualificationTypeId) return $this->mtError("Missing QualificationTypeId Parameter");
      else                                 $this->QueryData['QualificationTypeId'] = $this->QualificationTypeId;
      return $this->mtMakeRequest();
   }

    /* Retrieve various statistics */
    function GetRequesterStatistic()
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
    */
    public function GetReviewableHITs()
    {
      if     ($this->HITTypeId)                                                         $this->QueryData['HITTypeId']     = $this->HITTypeId;
      if     ($this->Status && !in_array($this->Status, $this->validSMO))               return $this->mtError("Invalid Status Value (Reviewing/Reviewable - PREVIOUSLY StatusMatchOption)");
      elseif ($this->Status)                                                            $this->QueryData['Status']        = $this->Status;
      if     (is_numeric($this->PageSize) && $this->PageSize > 0)                       $this->QueryData['PageSize']      = $this->PageSize;
      if     (is_numeric($this->PageNumber) && $this->PageNumber > 0)                   $this->QueryData['PageNumber']    = $this->PageNumber;
      if     ($this->SortProperty && !in_array($this->SortProperty, $this->validGRHSP)) return $this->mtError("Invalid SortProperty Value (Title/Reward/Expiration/CreationTime)");
      elseif ($this->SortProperty)                                                      $this->QueryData['SortProperty']  = $this->SortProperty;
      if     ($this->SortDirection && !in_array($this->SortDirection, $this->validSD))  return $this->mtError("Invalid SortDirection Value (Ascending/Descending)");
      elseif ($this->SortDirection)                                                     $this->QueryData['SortDirection'] = $this->SortDirection;
      return $this->mtMakeRequest();
    }

    /**
    */
    public function GetReviewResultsForHIT() {}


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
        @param SortProperty
        @param SortDirection
        @param PageSize
        @param PageNumber
    */
    function SearchHITs()
    {
        // if     ($this->SortProperty && !in_array($this->SortProperty, $this->validGRHSP)) return $this->mtError("Invalid SortProperty Value (Title/Reward/Expiration/CreationTime)");
        // elseif ($this->SortProperty)                                                      $this->QueryData['SortProperty']  = $this->SortProperty;
        // if     ($this->SortDirection && !in_array($this->SortDirection, $this->validSD))  return $this->mtError("Invalid SortDirection Value (Ascending/Descending)");
        // elseif ($this->SortDirection)                                                     $this->QueryData['SortDirection'] = $this->SortDirection;
        // if     (is_numeric($this->PageSize) && $this->PageSize > 0)                       $this->QueryData['PageSize']      = $this->PageSize;
        // if     (is_numeric($this->PageNumber) && $this->PageNumber > 0)                   $this->QueryData['PageNumber']    = $this->PageNumber;
        // return $this->mtMakeRequest();
        $arg = array(
            'Operation' => 'SearchHITs',
            'SortProperty' => null,
            'SortDirection' => null,
            'PageSize' => null,
            'PageNumber' => null,
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

   /* Checks Notification Data Structure */
   function mtCheckNotificationData($iteration, $data)
   {
     if     (!isset($data['Destination'])) return $this->mtError("Missing Destination Value (Notification Level {$iteration})");
     elseif (!isset($data['Transport']))   return $this->mtError("Missing Transport Value (Notification Level {$iteration} - Email/SOAP/REST)");
     elseif (!isset($data['EventType']))   return $this->mtError("Missing EventType Value (Notification Level {$iteration})");
     else
     {
       switch ($data['Transport'])
       {
         case "Email":
           if (!$this->mtValidEmail($data['Destination'])) return $this->mtError("Invalid email address as destination (Notification Level {$iteration})");
         break;
         case "SOAP":
         case "REST":
           if (!$this->mtValidHTTP($data['Destination'])) return $this->mtError("Invalid url as destination (Notification Level {$iteration})");
         break;
       }
     }

     if (is_array($data['EventType']))
     {
       foreach ($data['EventType'] as $checkMe)
       {
         if (!in_array($checkMe, $validET)) return $this->mtError("Invalid EventType Value '{$checkMe}' (Notification Level {$iteration})");
       }
     }
     elseif (!in_array($data['EventType'], $validET)) return $this->mtError("Invalid EventType Value (Notification Level {$iteration})");
     return true;
   }

   /* Checks Price Data Structure Info */
   function mtCheckPriceData($iteration, $data)
   {
     if     (!isset($data['Amount']) || !is_numeric($data['Amount'])) return $this->mtError("Missing/Invalid Amount Specified (Price Level {$iteration})");
     elseif (!isset($data['CurrencyCode']))                           return $this->mtError("Missing Currency Code Specified (Price Level {$iteration})");
     elseif (!in_array($data['CurrencyCode'], $validCC))              return $this->mtError("Invalid Currency Code Specified (Price Level {$iteration})");
     return true;
   }

    /**
        @param Request URI
        @return XML Data buffer
    */
    private function _http($arg)
    {
        $req = array(
            'AWSAccessKeyId' => $this->_access,
            'Service' => self::SERVICE,
            'Signature' => null,
            'Timestamp' => date('c'), // $this->Unix2ISO8601(time());
            // 'ResponseGroup' => null,
            // 'Version' => self::VERSION,
            // 'Validate' =>
            // 'Credential'
        );

        $req = array_merge($req,$arg);

        $req['Signature'] = $this->_sign($req['Operation'],$req['Timestamp']);
        // $this->SOAPSwitch = FALSE; /* We ARE NOT making a SOAP request */

        foreach ($req as $a => $b) $callData[] = "{$a}=" . urlencode($b);
        // $uri   = self::REST_URI . '?' . implode('&',$callData);
        // echo "uri:$uri\n";
        // echo "uri:" . self::REST_URI . '?' . http_build_query($req) . "\n";
        $uri = self::REST_URI . '?' . http_build_query($req);

        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_USERAGENT, self::UA);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0); /* 1 for return header output */
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
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
      return pack("H*", sha1((str_pad($this->_secret, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
                             pack("H*", sha1((str_pad($this->_secret, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $s))));

    }
}
