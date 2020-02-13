<?php

namespace SqsSimple;

use Aws\Exception\AwsException;

class SqsMessenger
{
    
    public $SqsClient        = null;
    public $RetryTimesOnFail = 2;
    public $WaitBeforeRetry  = 1;
    
    /**
     * SqsMessenger constructor.
     * @param array $AwsConfig
     */
    public function __construct(array $AwsConfig)
    {
        $credentials  = new \Aws\Credentials\Credentials($AwsConfig['AWS_KEY'], $AwsConfig['AWS_SECRET_KEY']);
        $sharedConfig = [
            'credentials' => $credentials,
            'region'      => $AwsConfig['AWS_REGION'],
            'version'     => $AwsConfig['API_VERSION'],
        ];
        
        // Create an SDK class used to share configuration across clients.
        $sdk = new \Aws\Sdk($sharedConfig);
        
        // Create an Amazon SQS client using the shared configuration data.
        $this->SqsClient = $sdk->createSqs();
    }
    
    /**
     * Set client
     *
     * @param $SqsClient
     */
    public function setClient($SqsClient)
    {
        $this->SqsClient = $SqsClient;
    }
    
    /**
     * Set params
     *
     * @param array $params
     */
    public function setParams(array $params)
    {
        foreach ($params as $param => $value) {
            $this->{$param} = $value;
        }
    }
    
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