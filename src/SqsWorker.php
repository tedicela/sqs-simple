<?php

namespace SqsSimple;

use Aws\Exception\AwsException;

class SqsWorker extends SqsBase
{    
    public $Sleep               = 10;
    public $WaitTimeSeconds     = 20;
    public $MaxNumberOfMessages = 1;
    public $VisibilityTimeout   = 3600;
    public $workerProcess       = false;    
    
    /**
     * Listener
     *
     * @param $queueUrl
     * @param $workerProcess
     * @param null $errorHandlerCallback
     * @throws \Exception
     */
    public function listen($queueUrl, $workerProcess, $errorHandlerCallback = null)
    {
        
        $this->queueUrl = $queueUrl;
        
        if (!is_callable($workerProcess)) {
            throw new \InvalidArgumentException("WorkerProcess not found");
        }
        
        if ($errorHandlerCallback != null && !is_callable($errorHandlerCallback)) {
            throw new \InvalidArgumentException("errorHandlerCallback is not a callable function");
        }
        
        $this->printHeader();
        
        $checkForMessages = true;
        $counterCheck     = 0;
        $errorCounter     = 0;
        while ($checkForMessages) {
            
            $this->out("Check(" . $counterCheck . ") time: " . date("Y-m-d H:i:s"));
            
            try {
                
                $this->out("Getting messages...");
                //Step 1: Get messages that are marked as unavailable upon receiving
                $this->getMessages(function ($messages) use ($workerProcess) {
                    
                    //Step 2: Should work these messages
                    for ($i = 0; $i < count($messages); $i++) {
                        
                        $completed = $workerProcess($messages[$i]);
                        
                        if ($completed) {
                            //Step 3.1: When messages finishes to get worked then we should DELETE MESSAGE from SQS
                            $this->ackMessage($messages[$i]);
                        } else {
                            //Step 3.2: If we can't elaborate the message then we should MAKE MESSAGE AVAILABLE to other workers who can
                            $this->nackMessage($messages[$i]);
                        }
                        
                    }
                    
                });
                
                $errorCounter = 0;
                
            } catch (AwsException $e) {
                
                if ($errorCounter >= 5) {
                    $checkForMessages = false;
                }
                $errorCounter++;
                
                // output error message if fails
                error_log($e->getMessage());
                
                if ($errorHandlerCallback != null) {
                    $errorHandlerCallback($e->getMessage(), $errorCounter);
                }
            }
            $counterCheck++;
            
        }
        
        $this->printFooter();
        
    }
    
    /**
     * Delete message
     *
     * @param $receiptHandle
     * @param $queueUrl
     * @return \Aws\Result
     * @throws \Exception
     */
    public function deleteMessage($receiptHandle, $queueUrl)
    {
        if ($this->SqsClient == null) {
            throw new \Exception("No SQS client defined");
        }
        
        return $this->SqsClient->deleteMessage([
            'QueueUrl'      => $queueUrl, // REQUIRED
            'ReceiptHandle' => $receiptHandle, // REQUIRED
        ]);
    }
    
    /**
     * Get messages
     *
     * @param $callback
     * @throws \Exception
     */
    private function getMessages($callback)
    {
        if ($this->SqsClient == null) {
            throw new \Exception("No SQS client defined");
        }
        
        $result = $this->SqsClient->receiveMessage([
            'AttributeNames'        => ['SentTimestamp'],
            'MaxNumberOfMessages'   => $this->MaxNumberOfMessages,
            'MessageAttributeNames' => ['All'],
            'QueueUrl'              => $this->queueUrl, // REQUIRED
            'WaitTimeSeconds'       => $this->WaitTimeSeconds,
            'VisibilityTimeout'     => $this->VisibilityTimeout,
        ]);
        
        //Step 1: GET MESSAGES:
        $messages = $result->get('Messages');
        if ($messages != null) {
            $this->out("Messages found");
            $callback($messages);
        } else {
            $this->out("No messages found");
            $sleep = $this->Sleep;
            $this->out("Sleeping for $sleep seconds");
            sleep($sleep);
        }
        
    }
    
    /**
     * Ack message
     *
     * @param $message
     * @throws \Exception
     */
    private function ackMessage($message)
    {
        if ($this->SqsClient == null) {
            throw new \Exception("No SQS client defined");
        }
        
        $this->SqsClient->deleteMessage([
            'QueueUrl'      => $this->queueUrl, // REQUIRED
            'ReceiptHandle' => $message['ReceiptHandle'], // REQUIRED
        ]);
    }
    
    /**
     * Nack message
     *
     * @param $message
     * @throws \Exception
     */
    private function nackMessage($message)
    {
        if ($this->SqsClient == null) {
            throw new \Exception("No SQS client defined");
        }
        
        $this->SqsClient->changeMessageVisibility([
            // VisibilityTimeout is required
            'VisibilityTimeout' => 0,
            'QueueUrl'          => $this->queueUrl, // REQUIRED
            'ReceiptHandle'     => $message['ReceiptHandle'], // REQUIRED
        ]);
    }
    
    private function printHeader()
    {
        echo "\n\n";
        echo "\n*****************************************************************";
        echo "\n**** Worker started at " . date("Y-m-d H:i:s");
        echo "\n*****************************************************************";
    }
    
    private function printFooter()
    {
        echo "\n\n";
        echo "\n*****************************************************************";
        echo "\n**** Worker finished at " . date("Y-m-d H:i:s");
        echo "\n*****************************************************************";
        echo "\n\n";
    }
    
    /**
     * Out
     *
     * @param $message
     */
    private function out($message)
    {
        echo "\n" . $message;
    }
    
}
