<?php

namespace Ecosystem\BusConsumerBundle\Service;

use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

class ConsumerService
{
    #[Required]
    public LoggerInterface $logger;

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
                foreach ($result['Messages'] as $message) {
                    $notification = json_decode($message['Body'], true);
                    $payload = json_decode($notification['Message'], true);
                    $this->queues[$queue]['handler']($payload);
                    $this->client->deleteMessage([
                        'QueueUrl' => $this->queues[$queue]['url'],
                        'ReceiptHandle' => $message['ReceiptHandle'],
                    ]);
                }
            }
        } catch (\Exception $exception) {
            $this->logger->critical(sprintf('Unable to process messages, exception: "%s"', $exception->getMessage()));
        }
    }
}
