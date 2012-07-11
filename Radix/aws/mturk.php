<?php
/**

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

    /** Sanity Checks **/
    /* Valid operations, includes types added 10-01-2006 after RegisterHITType */
    public static $command_list = array(
        'ApproveAssignment',
        'ApproveRejectedAssignment',
        'CreateHIT',
        'CreateQualificationType',
        'DisableHIT',
        'DisposeHIT',
        'ExtendHIT',
        'GetAccountBalance',
        'GetAssignmentsForHIT',
        'GetHIT',
        'GetQualificationRequests',
        'GetQualificationScore',
        'GetQualificationType',
        'GetRequesterStatistic',
        'GetReviewableHITs',
        'GrantQualification',
        'Help',
        'NotifyWorkers',
        'RejectAssignment',
        'SearchQualificationTypes',
        'UpdateQualificationScore',
        'UpdateQualificationType',
        'SetHITAsReviewing',
        'RegisterHITType',
        'SearchHITs',
        'ForceExpireHIT',
        'SetHITTypeNotification',
        'SendTestEventNotification',
        'GrantBonus',
        'GetFileUploadURL',
        'RejectQualificationRequest',
        'GetQualificationsForQualificationType'
    );
    /* Valid statistics, not fully in use yet */
    // var $validStats = array("NumberAssignmentsAvailable", "NumberAssignmentsAccepted", "NumberAssignmentsPending",
    // "NumberAssignmentsApproved", "NumberAssignmentsRejected", "NumberAssignmentsReturned", "NumberAssignmentsAbandoned",
    // "PercentAssignmentsApproved", "PercentAssignmentsRejected", "TotalRewardPayout", "AverageRewardAmount",
    // "TotalFeePayout", "TotalRewardAndFeePayout", "NumberHITsCreated", "NumberHITsCompleted", "NumberHITsAssignable",
    // "NumberHITsReviewable", "EstimatedRewardLiability", "EstimatedFeeLiability", "EstimatedTotalLiability");
    //
    // var $validQTS   = array("Active", "Inactive"); /* Qualification Type Status */
    // var $validSP    = array("AcceptTime", "SubmitTime", "AssignmentStatus");  /* Sort Property for GetAssignmentsForHIT */
    // var $validGRHSP = array("Title", "Reward", "Expiration", "CreationTime"); /* Sort Property for GetReviewableHITs */
    // var $validGQRSP = array("QualificationTypeId", "SubmitTime");             /* Sort Property for GetQualificationRequests */
    // var $validSD    = array("Ascending", "Descending"); /* Sort Direction */
    // var $validTP    = array("OneDay", "SevenDays", "ThirtyDays", "LifeToDate");
    // var $validHT    = array("Operation", "ResponseGroup", "AssignmentSummary");
    // var $validMBR   = array("true", "false"); /* Simple Boolean */
    // var $validCPT   = array("LessThan", "LessThanOrEqualTo", "GreaterThan", "GreaterThanOrEqualTo", "EqualTo","NotEqualTo", "Exists");
    // var $validSMO   = array("Reviewable", "Reviewing"); /* StatusMatchOption, or now just Status */
    // var $validAS    = array("Submitted", "Approved", "Rejected"); /* Assignment Status */
    // var $validET    = array("AssignmentAccepted", "AssignmentAbandoned", "AssignmentReturned", "AssignmentSubmitted", "HITReviewable", "HITExpired", "Ping"); /* Event Type */
    // var $validGQS   = array("Granted", "Revoked"); /* Status for GetQualificationsForQualificationType */
    /**
    */
    function __construct($a, $s)
    {
        // $this->Service       = "AWSMechanicalTurkRequester";
        // $this->SecretKey     = $SecretKey;
        // $this->AccessKey     = $AccessKey;
        // $this->Version       = $Version;
        // $this->ResponseGroup = "Minimal";
        $this->_access = $a;
        $this->_secret = $s;
    }
    /**
        Approve and Assignment
        @param $aid Assignment ID
        @param $rfb Requester Feedback
    */
    function ApproveAssignment($aid,$rfb=null)
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
    public function ApproveRejectedAssignment()
    {
        
    }

   /* Create a new HIT - SOAP Operation understands HITTypeId */
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

   /* Create a new qualification */
   function CreateQualificationType()
   {
      if     (!$this->Name)                                               return $this->mtError("Missing Name Parameter");
      elseif (!$this->Description)                                        return $this->mtError("Missing Description Parameter");
      elseif (!$this->QualificationTypeStatus)                            return $this->mtError("Missing QualificationTypeStatus Parameter");
      elseif (!in_array($this->QualificationTypeStatus, $this->validQTS)) return $this->mtError("Invalid QualificationTypeStatus Value");
      elseif ($this->AnswerKey && !$this->Test)                           return $this->mtError("AnswerKey cannot be provided without Test!");
      return $this->mtFakeSoap();
   }

   /* Disable a HIT */
   function DisableHIT()
   {
      if     (!$this->HITId)        return $this->mtError("Missing HITId Parameter");
      else                          $this->QueryData['HITId'] = $this->HITId;
      return $this->mtMakeRequest();
   }

   /* Dispose of a HIT */
   function DisposeHIT()
   {
      if     (!$this->HITId)        return $this->mtError("Missing HITId Parameter");
      else                          $this->QueryData['HITId'] = $this->HITId;
      return $this->mtMakeRequest();
   }

   /* Extend the timing on a HIT */
   function ExtendHIT()
   {
      if     (!$this->HITId)                       return $this->mtError("Missing HITId Parameter");
      else                                         $this->QueryData['HITId']                        = $this->HITId;
      if     ($this->MaxAssignmentsIncrement)      $this->QueryData['MaxAssignmentsIncrement']      = $this->MaxAssignmentsIncrement;
      if     ($this->ExpirationIncrementInSeconds) $this->QueryData['ExpirationIncrementInSeconds'] = $this->ExpirationIncrementInSeconds;
      return $this->mtMakeRequest();
   }

   /* Force a HIT to expire immediately, as if the HIT's LifetimeInSeconds had elapsed */
   function ForceExpireHIT() {
      if     (!$this->HITId)                       return $this->mtError("Missing HITId Parameter");
      else                                         $this->QueryData['HITId']                        = $this->HITId;
      return $this->mtMakeRequest();
   }
    /**
    */
    function GetAccountBalance()
    {
        $arg = array(
            'Operation' => 'GetAccountBalance'
        );
        return $this->mtMakeRequest($arg);
    }
    /* Get a list of assignments for a given HIT Id */
    function GetAssignmentsForHIT($hid)
    {
        $arg = array(
            'Operation' => 'GetAssignmentsForHIT',
            'HITId' => $hid,
        );
        // if     (!$this->HITId)                                                        return $this->mtError("Missing HITId Parameter");
        // else                                                                                   $this->QueryData['HITId']         = $this->HITId;
        // if     ($this->AssignmentStatus && !in_array($this->AssignmentStatus, $this->validAS)) return $this->mtError("Invalid Assignment Status Value (Submitted/Approved/Rejected)");
        // elseif ($this->AssignmentStatus)                                                       $this->QueryData['AssignmentStatus'] = $this->AssignmentStatus;
        // if     ($this->SortProperty && !in_array($this->SortProperty, $this->validSP))         return $this->mtError("Invalid Sort Property Value (AcceptTime/SubmitTime/AssignmentStatus");
        // elseif ($this->SortProperty)                                                           $this->QueryData['SortProperty']  = $this->SortProperty;
        // if     ($this->SortDirection && !in_array($this->SortDirection, $this->validSD))       return $this->mtError("Invalid SortDirection Value (Ascending/Descending)");
        // elseif ($this->SortDirection)                                                          $this->QueryData['SortDirection'] = $this->SortDirection;
        // if     (is_numeric($this->PageSize) && $this->PageSize > 0)                            $this->QueryData['PageSize']      = $this->PageSize;
        // if     (is_numeric($this->PageNumber) && $this->PageNumber > 0)                        $this->QueryData['PageNumber']    = $this->PageNumber;
        return $this->mtMakeRequest($arg);
    }

   function GetFileUploadURL()
   {
      if   (!$this->AssignmentId)                    return $this->mtError("Missing AssignmentId");
      else                                           $this->QueryData['AssignmentId']       = $this->AssignmentId;
      if   (!$this->QuestionIdentifier)              return $this->mtError("Missing QuestionIdentifier");
      else                                           $this->QueryData['QuestionIdentifier'] = $this->QuestionIdentifier;
      return $this->mtMakeRequest();
   }

    /**
    */
    function GetHIT($hid)
    {
        $arg = array(
            'Operation' => 'GetHIT',
            'HITId' => $hid,
        );
        return $this->mtMakeRequest($arg);
    }

   /* Get qualification requests list */
   function GetQualificationRequests()
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

   /* Return a User's Qualification Score */
   function GetQualificationScore()
   {
      if     (!$this->QualificationTypeId) return $this->mtError("Missing QualificationTypeId Parameter");
      else                                 $this->QueryData['QualificationTypeId'] = $this->QualificationTypeId;
      if     (!$this->SubjectId)           return $this->mtError("Missing SubjectId Parameter");
      else                                 $this->QueryData['SubjectId']           = $this->SubjectId;
      return $this->mtMakeRequest();
   }

   /* Returns all of the Qualifications granted to Workers for a given Qualification type */
   function GetQualificationsForQualificationType()
   {
      if     (!$this->QualificationTypeId)                                return $this->mtError("Missing QualificationTypeId Parameter");
      else                                                                $this->QueryData['QualificationTypeId'] = $this->QualificationTypeId;
      if     ($this->Status && !in_array($this->Status, $this->validGQS)) return $this->mtError("Invalid Status Parameter");
      else                                                                $this->QueryData['Status']              = $this->Status;
      if     (is_numeric($this->PageSize) && $this->PageSize > 0)         $this->QueryData['PageSize']      = $this->PageSize;
      if     (is_numeric($this->PageNumber) && $this->PageNumber > 0)     $this->QueryData['PageNumber']    = $this->PageNumber;
      return $this->mtMakeRequest();
   }

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

   /* Get Reviewable HITs */
   function GetReviewableHITs()
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

   /* Issues a payment of money from your account to a Worker */
   function GrantBonus()
   {
      if     (!$this->WorkerId)                         return $this->mtError("Missing WorkerId Parameter(s)");
      elseif (!$this->AssignmentId)                     return $this->mtError("Missing AssignmentId");
      elseif (!$this->BonusAmount)                      return $this->mtError("Missing BonusAmount");
      if     (!mtCheckPriceData(1, $this->BonusAmount)) return false;
      return $this->mtFakeSoap();
   }

   /* Grant a qualification score to a user */
   function GrantQualification()
   {
      if     (!$this->QualificationRequestId)        return $this->mtError("Missing QualificationRequestId Parameter");
      else                                           $this->QueryData['QualificationRequestId'] = $this->QualificationRequestId;
      if     (!is_numeric($this->IntegerValue))      return $this->mtError("Missing IntegerValue Parameter");
      else                                           $this->QueryData['IntegerValue']           = $this->Value;
      return $this->mtMakeRequest();
   }

   /* GET HELP! */
   function Help()
   {
      if     (!$this->HelpType)                           return $this->mtError("Missing HelpType Parameter");
      elseif (!in_array($this->HelpType, $this->validHT)) return $this->mtError("Invalid HelpType Type");
      else                                                $this->QueryData['HelpType'] = $this->HelpType;
      if     (!$this->About)                              return $this->mtError("Missing About Parameter");
      elseif (!in_array($this->About, $this->validOps))   return $this->mtError("Invalid About Type");
      else                                                $this->QueryData['About']    = $this->About;
      return $this->mtMakeRequest();
   }

   /* Send workers a message */
   function NotifyWorkers()
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

   /* Register a HIT Type, essentially the same as CreateHIT, minus the actual HIT creation */
   function RegisterHITType()
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

   /* Reject HIT Assigmnment */
   function RejectAssignment()
   {
      if   (!$this->AssignmentId)                                                  return $this->mtError("Missing AssignmentId");
      else                                                                         $this->QueryData['AssignmentId'] = $this->AssignmentId;
      if     ($this->RequesterFeedback && strlen($this->RequesterFeedback) > 1024) return $this->mtError("RequesterFeedback entry is too long!");
      elseif ($this->RequesterFeedback)                                            $this->QueryData['RequesterFeedback'] = $this->RequesterFeedback;
      return $this->mtMakeRequest();
   }

   /* Reject a Qualification Request */
   function RejectQualificationRequest()
   {
      if     (!$this->QualificationRequestId)        return $this->mtError("Missing QualificationRequestId Parameter");
      else                                           $this->QueryData['QualificationRequestId'] = $this->QualificationRequestId;
      if     ($this->Reason)                         $this->QueryData['Reason']                 = $this->Reason;
      return $this->mtMakeRequest();
   }

   /* Returns all HITs, except for HITs that have been disposed with the DisposeHIT  operation.*/
   function SearchHITs()
   {
      if     ($this->SortProperty && !in_array($this->SortProperty, $this->validGRHSP)) return $this->mtError("Invalid SortProperty Value (Title/Reward/Expiration/CreationTime)");
      elseif ($this->SortProperty)                                                      $this->QueryData['SortProperty']  = $this->SortProperty;
      if     ($this->SortDirection && !in_array($this->SortDirection, $this->validSD))  return $this->mtError("Invalid SortDirection Value (Ascending/Descending)");
      elseif ($this->SortDirection)                                                     $this->QueryData['SortDirection'] = $this->SortDirection;
      if     (is_numeric($this->PageSize) && $this->PageSize > 0)                       $this->QueryData['PageSize']      = $this->PageSize;
      if     (is_numeric($this->PageNumber) && $this->PageNumber > 0)                   $this->QueryData['PageNumber']    = $this->PageNumber;
      return $this->mtMakeRequest();
   }

   /* Search Qualification Types on keyword */
   function SearchQualificationTypes()
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

   function SendTestEventNotification()
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

   function SetHITAsReviewing()
   {
      if     (!$this->HITId) return $this->mtError("Missing HITId Parameter");
      else                   $this->QueryData['HITId'] = $this->HITId;
      if     ($this->Revert) $this->QueryData['Revert'] = 'true';
      else                   $this->QueryData['Revert'] = 'false';
      return $this->mtMakeRequest();
   }

   /* Creates, updates, disables or re-enables notifications for a HIT type */
   function SetHITTypeNotification()
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

   /* Update the qualification score for a user */
   function UpdateQualificationScore()
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

   /* Update Qualification - RTB 03/10/06 - Switched to SOAP after API update */
   function UpdateQualificationType()
   {
      if     (!$this->QualificationTypeId)                                                                  return $this->mtError("Missing QualificationTypeId Parameter");
      if     ($this->QualificationTypeStatus && !in_array($this->QualificationTypeStatus, $this->validQTS)) return $this->mtError("Invalid QualificationTypeStatus Value (Active/Inactive)");
      return $this->mtFakeSoap();
   }
   /* Figures out if a HITTypeID is going to be busted because a field used for it is included */
   function mtBreaksHTI()
   {
     if ($this->Title || $this->Description || $this->Keywords || $this->Reward || $this->AssignmentDurationsInSeconds || $this->AutoApprovalDelayInSeconds || is_array($this->QualificationRequirement))
     {
       return TRUE;
     }
     return FALSE;
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

   /* Convert given date to ISO 8601 format */
//    function Unix2ISO8601($int_date)
//    {
//       // $int_date = $int_date + $this->mtHours(8);
//       $date_mod = date('Y-m-d\TH:i:s', $int_date);
//       // $pre_timezone = date('O', $int_date);
//       return $date_mod . ".000Z";
//    }

   /* Convert given RFC 8601 Date to Unix Timestamp */
   function ISO86012Unix($timestamp)
   {
      $day    = substr($timestamp,8,2);
      $month  = substr($timestamp,5,2);
      $year   = substr($timestamp,0,4);
      $hour   = substr($timestamp,11,2);
      $minute = substr($timestamp,14,2);
      $second = substr($timestamp,17,2);
      $output = mktime($hour,$minute,$second,$month,$day,$year);
      return    $output;
   }

   function LoadQuestion($inputsource)
   {
      $data = $this->mtPullSource($inputsource);
      if   ($data) $this->Question = $data;
      else         return $data;
      return TRUE;
   }

   function LoadTest($inputsource)
   {
      $data = $this->mtPullSource($inputsource);
      if   ($data) $this->Test = $data;
      else         return $data;
      return TRUE;
   }

   function LoadAnswerKey($inputsource)
   {
      $data = $this->mtPullSource($inputsource);
      if   ($data) $this->AnswerKey = $data;
      else         return $data;
      return TRUE;
   }

   /* Output hit list internal var */
   function PullHITList()
   {
      if   (is_array($this->HITList)) return $this->HITList;
      else                            return array();
   }

   function PullAssignmentList()
   {
      if   (is_array($this->AssignmentList)) return $this->AssignmentList;
      else                                   return array();
   }

   function PullQualificationRequestList()
   {
      if   (is_array($this->QualificationRequestList)) return $this->QualificationRequestList;
      else                                             return array();
   }
    /**
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
        $uri   = self::REST_URI . '?' . implode('&',$callData);
        echo "uri:$uri\n";
        echo "uri:" . self::REST_URI . '?' . http_build_query($req) . "\n";

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
        // print_r($buf);
        return $buf;
    }
    /**
    */
    private function _sign($op,$ts)
    {
        $sign = self::SERVICE . $op . $ts;
        $hmac = $this->_sign_hmac_sha1($this->_secret,$sign);
        $r = base64_encode($hmac);
        return $r;
    }

    private function _sign_hmac_sha1($key,$s)
    {
      return pack("H*", sha1((str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
                             pack("H*", sha1((str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $s))));

    }
}


