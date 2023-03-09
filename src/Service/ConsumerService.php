<?php

namespace Ecosystem\BusConsumerBundle\Service;

use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

class ConsumerService
{
    private SqsClient $client;
    private array $queues;

    public function __construct()
    {
        $config = [
            'region' => getenv('AWS_REGION'),
            'version' => '2012-11-05',
        ];

        if (getenv('LOCALSTACK')) {
            $config['credentials'] = false;
        }

        $this->client = new SqsClient($config);
    }

    public function addQueue(string $name, string $url, int $maxMessages, int $waitTime, $handler): void
    {
        $this->queues[$name] = [
            'url' => $url,
            'max_messages' => $maxMessages,
            'wait_time' => $waitTime,
            'handler' => $handler,
        ];
    }

    public function receive(string $queue): void
    {
        try {
            $result = $this->client->receiveMessage([
                'WaitTimeSeconds' => intval($this->queues[$queue]['wait_time']),
                'MaxNumberOfMessages' => intval($this->queues[$queue]['max_messages']),
                'MessageAttributeNames' => ['All'],
                'QueueUrl' => $this->queues[$queue]['url'],
            ]);

            if (isset($result['Messages'])) {
                $notification = json_decode($result['Messages'][0]['Body'], true);
                $message = json_decode($notification['Message'], true);
                $this->queues[$queue]['handler']($message);
                $this->client->deleteMessage([
                    'QueueUrl' => $this->queues[$queue]['url'],
                    'ReceiptHandle' => $result['Messages'][0]['ReceiptHandle'],
                ]);
            }
        } catch (AwsException $e) {
            error_log($e->getMessage());
        }
    }
}
