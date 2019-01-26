<?php

namespace SqsSimple;

use Aws\Exception\AwsException;

class SqsWorker{
    
    public $SqsClient = null;
    public $Sleep = 10;
    public $WaitTimeSeconds = 20;
    public $MaxNumberOfMessages = 1;
    public $VisibilityTimeout = 3600;

    public $workerProcess = false;

    public function __construct(array $AwsConfig){
        $credentials = new \Aws\Credentials\Credentials($AwsConfig['AWS_KEY'], $AwsConfig['AWS_SECRET_KEY']);
        $sharedConfig = [
            'credentials'=>$credentials,
            'region' => $AwsConfig['AWS_REGION'],
            'version'=> $AwsConfig['API_VERSION'],
        ];

        // Create an SDK class used to share configuration across clients.
        $sdk = new \Aws\Sdk($sharedConfig);    
        
        // Create an Amazon SQS client using the shared configuration data.
        $this->SqsClient = $sdk->createSqs();
    }

    public function setClient($SqsClient){
        $this->SqsClient = $SqsClient;
    }

    public function setParams(array $params){
        foreach($params as $param=>$value){
            $this->{$param} = $value;
        }
    }
    
    public function listen($queueUrl, $workerProcess=false){
        
        $this->queueUrl = $queueUrl;
        
        $this->workerProcess = $workerProcess;
        if($this->workerProcess === false){
            throw new \Exception("WorkerProcess not found");
        }

        $this->printHeader();

        $checkForMessages = true;
        $counterCheck = 0;
        $errorCounter = 0;
        while($checkForMessages){
            
            $this->out("Check(".$counterCheck.") time: ".date("Y-m-d H:i:s"));
            
            try {
                
                $this->out("Getting messages...");
                //Step 1: GET MESSAGES:
                $this->getMessages(function( $messages ){
                    
                    //Step 2: We should now MAKE MESSAGES NOT AVAILABLE for other workers:
                    $this->setMessagesUnavailable($messages);
                    
                    //Step 3: Should work these messages
                    for ($i = 0; $i < count($messages); $i++) {
                        
                        $completed = $this->workerProcess($messages[$i]);

                        if($completed){
                            //Step 4.1: When messages finishes to get worked then we should DELETE MESSAGE from SQS
                            $this->ackMessage($message);
                        }else{
                            //Step 4.2: If we can't elaborate the message then we should MAKE MESSAGE AVAILABLE to other workers who can
                            $this->nackMessage($message);
                        }

                    }

                });

                $errorCounter=0;
            
            } catch (AwsException $e) {
                
                if($errorCounter >= 5){
                    $checkForMessages = false;
                }
                $errorCounter++;

                var_dump( $e->getMessage() );
                // output error message if fails
                error_log($e->getMessage());
            }
            $counterCheck++;
            
        }

        $this->printFooter();
    
    }

    private function getMessages($callback){
        if($this->SqsClient == null){
            throw new \Exception("No SQS client defined");
        }

        $result = $this->SqsClient->receiveMessage([
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => $this->MaxNumberOfMessages,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $this->queueUrl, // REQUIRED
            'WaitTimeSeconds' => $this->WaitTimeSeconds,
        ]);
        
        //Step 1: GET MESSAGES:
        $messages = $result->get('Messages');
        if( $messages != null ){
            $this->out("Messages found");
            $callback($messages);
        }else{
            $this->out("No messages found");
            $sleep = $this->Sleep;
            $this->out("Sleeping for $sleep seconds");
            sleep( $sleep );
        }

    }

    private function setMessagesUnavailable($messages){
        if($this->SqsClient == null){
            throw new \Exception("No SQS client defined");
        }

        $entries = [];
        for ($i = 0; $i < count($messages); $i++) {
            array_push($entries, [
                'Id' => 'unique_is_msg' . $i, // REQUIRED
                'ReceiptHandle' => $messages[$i]['ReceiptHandle'], // REQUIRED
                'VisibilityTimeout' => $this->VisibilityTimeout
            ]);
        }
        $result = $this->SqsClient->changeMessageVisibilityBatch([
            'Entries' => $entries,
            'QueueUrl' => $this->queueUrl
        ]);
    }

    private function ackMessage($message){
        if($this->SqsClient == null){
            throw new \Exception("No SQS client defined");
        }

        $result = $this->SqsClient->deleteMessage([
            'QueueUrl' => $this->queueUrl, // REQUIRED
            'ReceiptHandle' => $message['ReceiptHandle'], // REQUIRED
        ]);
    }

    private function nackMessage($message){
        if($this->SqsClient == null){
            throw new \Exception("No SQS client defined");
        }

        $result = $this->SqsClient->changeMessageVisibilityBatch([
            // VisibilityTimeout is required
            'VisibilityTimeout' => 0,
            'QueueUrl' => $this->queueUrl, // REQUIRED
            'ReceiptHandle' => $message['ReceiptHandle'], // REQUIRED
        ]);
    }

    private function printHeader(){
        echo "\n\n";
        echo "\n*****************************************************************";
        echo "\n**** Worker started at ".date("Y-m-d H:i:s");
        echo "\n*****************************************************************";
    }

    private function printFooter(){
        echo "\n\n";
        echo "\n*****************************************************************";
        echo "\n**** Worker finished at ".date("Y-m-d H:i:s");
        echo "\n*****************************************************************";
        echo "\n\n";
    }

    private function out($message){
        echo "\n".$message;
    }
    
}