# sqs-simple
PHP package for consuming AWS SQS queue in the simple way

## Description


## Requirements
- php >=5.5.9

## Installation via composer
You can add this library into your project using [Composer](https://getcomposer.org). If you don't have composer installed and want to install it then [download composer here](https://getcomposer.org/download/) and follow [how to install guide](https://getcomposer.org/doc/00-intro.md). 

To add **sqs-simple** to your porject follow these steps:

**Step 1** - add to `composer.json` this repository:
```json
"repositories": [
      {"type": "vcs", "url": "git@github.com:tedicela/sqs-simple"}
],
```

**Step 2** - excute on command line:
```
composer require tedicela/sqs-simple
```

## Use cases

### How to publish messages into an SQS Queue
**Example**:
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

// $messenger->RetryTimesOnFail = 2;
// $messenger->WaitBeforeRetry = 1; //seconds

$queue = "<Your queueUrl>";
$message = "This is a message for SQS";
$messenger->publish( $queue, $message);
```

### Making a worker to listen for messages into an SQS Queue
**Example**:
```php
<?php

require 'vendor/autoload.php';
use SqsSimple\SqsWorker;

$AwsConfig = [
    'AWS_KEY'=>'', //You should put your AWS_KEY 
    'AWS_SECRET_KEY'=>'', //You should put your AWS_SECRET_KEY 
    'AWS_REGION'=>'eu-west-1', //You should put your AWS_REGION 
    'API_VERSION'=>'2012-11-05',
];
$worker = new SqsWorker($AwsConfig);

// $worker->SqsClient = $ExistingSqsClient;
// $worker->Sleep = 10;
// $worker->WaitTimeSeconds = 20;
// $worker->MaxNumberOfMessages = 1;
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
    
});
```

