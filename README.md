# sqs-simple (PHP)
PHP package for consuming AWS SQS queue in the simple way

## Description
Inspired by the Rabbitmq PHP SDK I have made this PHP library that makes simple the usage of AWS Simple Queue Service. With just a few lines of codes you can make a worker that listens for messages into a queue(**long lived worker**). You can set parameters to tune costs(requests made on SQS). Also for publishing messages into a queue simple too.

## Requirements
- php >=5.5.9

## Installation via composer
You can add this library into your project using [Composer](https://getcomposer.org). If you don't have composer installed and want to install it then [download composer here](https://getcomposer.org/download/) and follow [how to install guide](https://getcomposer.org/doc/00-intro.md). 

To add **sqs-simple** to your porject just excute on command line:
```
composer require tedicela/sqs-simple
```

## Use cases

### How to publish messages into an SQS queue
AWS SQS charges you for every request you do on that service. So you can tune SqsMessenger attributes to get the most reliable service and with lower costs.

**Example** (check the comments for explanations):
```php
<?php

require 'vendor/autoload.php';
use SqsSimple\SqsMessenger;

$AwsConfig = [
    'AWS_KEY'=>'', //You should put your AWS_KEY 
    'AWS_SECRET_KEY'=>'', //You should put your AWS_SECRET_KEY 
    'AWS_REGION'=>'eu-west-1', //You should put your AWS_REGION 
    'API_VERSION'=>'2012-11-05'
];
$messenger = new SqsMessenger($AwsConfig);

/* if a publish message request fails then it will retry again */
// $messenger->RetryTimesOnFail = 2;
/* seconds to wait after failed request to retry again */
// $messenger->WaitBeforeRetry = 1; //seconds

$queue = "<Your queueUrl>";
$message = "This is a message for SQS";
$delaySeconds = 10; // (Not FIFO Queue type) - The time in seconds that the delivery of all messages in the queue will be delayed. An integer from 0 to 900 (15 minutes). The default for this attribute is 0 (zero).
$messageGroupId = ''; // (FIFO Queue type) - The tag that specifies that a message belongs to a specific message group. Messages that belong to the same message group are always processed one by one, in a strict order relative to the message group (however, messages that belong to different message groups might be processed out of order).
$messageDeduplicationId = ''; // (FIFO Queue type) - The token used for deduplication of sent messages. If a message with a particular message deduplication ID is sent successfully, any messages sent with the same message deduplication ID are accepted successfully but aren't delivered during the 5-minute deduplication interval. The queue should either have ContentBasedDeduplication enabled or MessageDeduplicationId provided explicitly.
$messenger->publish( $queue, $message, $delaySeconds, $messageGroupId, $messageDeduplicationId);
```

### Making a worker to listen for messages into an SQS queue (long lived worker)
As it was explained above that AWS SQS charges you for every request that is done, so even in SqsWorker you can tune it's attributes to get the best effort with better costs. Tuning attributes to SqsWorker is important because the worker are the process that makes more requests than publishers.

**Example** (check the comments for explanations):
```php
<?php

require 'vendor/autoload.php';
use SqsSimple\SqsWorker;

/*
  You can pass the AWS configuration into constructor
  but this is optional. If you don't pass this configuration 
  you should set an authenticated SqsClient Object 
  like in the comment below
*/
$AwsConfig = [
    'AWS_KEY'=>'', //You should put your AWS_KEY 
    'AWS_SECRET_KEY'=>'', //You should put your AWS_SECRET_KEY 
    'AWS_REGION'=>'eu-west-1', //You should put your AWS_REGION 
    'API_VERSION'=>'2012-11-05',
];
$worker = new SqsWorker($AwsConfig);

/*
  You can set an already authenticated SqsClient Object
  if set this you don't need to pass the AwsConfig above
*/
// $worker->SqsClient = $ExistingSqsClient;
/* seconds to wait before checking again if no messages was found */
// $worker->Sleep = 10;
/* how many seconds with wait for messages in SQS to be available in one check */
// $worker->WaitTimeSeconds = 20;
/* how many messages to get from queue when the worker checks */
// $worker->MaxNumberOfMessages = 1;
/*    
  After the worker get a message and starts elaborating it 
  how many seconds the message should not be available to other workers
*/
// $worker->VisibilityTimeout = 3600;

$queueUrl = "<Your queueUrl>";
$worker->listen($queueUrl, function($message){
    echo "\n this is the working process\n";
    
    var_dump($message);
    
    /*
      You should return TRUE when you want to remove the message from queue (ACK) 
      or return FALSE when your job failed and you want to return the message back to queue(NACK) 
      to retry later or by another worker
    */
    return true;
    
},
function($exceptionMessage, $errorCounter){ // $errorHandlerCallback - this is optional as parameter
	/*
		$exceptionMessage is the $e->getMessage()
		$errorCounter - how many times in a row errors occured, if it's 5 then listening will stop
	*/
});
```

# How to contribute
If you are interested for adding new features you can open an issue (I'll try to be fast in adding it), or fork this project and create a Pull request(I'll will be happy to accept it).
