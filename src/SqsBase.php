<?php

namespace SqsSimple;

class SqsBase {

    public $SqsClient           = null;

    /**
     * SqsWorker constructor.
     * @param array $AwsConfig
     */
    public function __construct(array $AwsConfig)
    {        
        $sharedConfig = [
            'region'      => $AwsConfig['AWS_REGION'],
            'version'     => $AwsConfig['API_VERSION'],
        ];
        
        if (isset($AwsConfig['AWS_KEY']) && isset($AwsConfig['AWS_SECRET_KEY']))
        {
            $sharedConfig['credentials'] = new \Aws\Credentials\Credentials($AwsConfig['AWS_KEY'], $AwsConfig['AWS_SECRET_KEY']);
        }

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
}