<?php

namespace SqsSimple;

use Aws\Exception\AwsException;

class SqsMessenger extends SqsBase
{    
    public $RetryTimesOnFail = 2;
    public $WaitBeforeRetry  = 1;    
    
    /**
     * Publish
     *
     * @param $queueUrl
     * @param $message
     * @param array $messageAttributes
     * @param int $delaySeconds
     * @param string $messageGroupId
     * @param string $messageDeduplicationId
     * @return \Aws\Result|bool
     * @throws \Exception
     */
    public function publish($queueUrl, $message, $messageAttributes = [], $delaySeconds = 10, $messageGroupId = '', $messageDeduplicationId = '')
    {
        
        if ($this->SqsClient == null) {
            throw new \Exception("No SQS client defined");
        }
        
        $params = [
            'QueueUrl'          => $queueUrl,
            'MessageBody'       => $message,
            'MessageAttributes' => $messageAttributes,
        ];
        
        if ($delaySeconds)
            $params['DelaySeconds'] = $delaySeconds;
        
        if ($messageGroupId)
            $params['MessageGroupId'] = $messageGroupId;
        
        if ($messageDeduplicationId)
            $params['MessageDeduplicationId'] = $messageDeduplicationId;
        
        $tryAgain     = false;
        $errorCounter = 0;
        do {
            
            try {
                $result   = $this->SqsClient->sendMessage($params);
                $tryAgain = false;
            } catch (AwsException $e) {
                
                if ($this->RetryTimesOnFail > 0) {
                    $result   = false;
                    $tryAgain = true;
                    
                    if ($errorCounter >= $this->RetryTimesOnFail) {
                        break;
                    }
                    
                    if ($errorCounter >= 2 && $this->WaitBeforeRetry > 0) {
                        sleep($this->WaitBeforeRetry);
                    }
                    
                    // output error message if fails
                    error_log($e->getMessage());
                    $errorCounter++;
                }
                
            }
            
        } while ($tryAgain);
        
        return $result;
        
    }
    
}