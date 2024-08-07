<?php

namespace Ecosystem\BusConsumerBundle\Service;

use Aws\Sqs\SqsClient;
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
            'version' => 'latest',
        ];

        if (getenv('LOCALSTACK')) {
            $config['endpoint'] = 'http://localstack:4566';
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

    public function send(string $queue, mixed $payload): void
    {
        if (!isset($this->queues[$queue])) {
            throw new \RuntimeException(sprintf('Queue "%s" not defined.', $queue));
        }

        $this->client->sendMessage([
            'MessageBody' => json_encode($payload),
            'QueueUrl' => $this->queues[$queue]['url']
        ]);
    }

    public function receive(string $queue): void
    {
        try {
            $result = $this->client->receiveMessage([
                'WaitTimeSeconds' => intval($this->queues[$queue]['wait_time']),
                'MaxNumberOfMessages' => intval($this->queues[$queue]['max_messages']),
                'MessageAttributeNames' => ['All'],
                'AttributeNames' => ['SentTimestamp'],
                'QueueUrl' => $this->queues[$queue]['url'],
            ]);

            if (isset($result['Messages'])) {
                foreach ($result['Messages'] as $message) {
                    $payload = [];
                    $notification = json_decode($message['Body'], true);

                    if (isset($notification['TopicArn'])) {
                        $metadata = [
                            'message_id' => $notification['MessageId'],
                            'type' => $notification['Type'],
                            'topic_arn' => $notification['TopicArn'],
                            'timestamp' => intval($message['Attributes']['SentTimestamp']),
                        ];
                        $payload = json_decode($notification['Message'], true);
                    } else {
                        $payload = $notification;
                        $metadata = [
                            'message_id' => $message['MessageId'],
                            'timestamp' => intval($message['Attributes']['SentTimestamp']),
                        ];
                    }

                    $this->queues[$queue]['handler']($payload, $metadata);
                    $this->client->deleteMessage([
                        'QueueUrl' => $this->queues[$queue]['url'],
                        'ReceiptHandle' => $message['ReceiptHandle'],
                    ]);
                }
            }
        } catch (\Exception $exception) {
            $this->logger->critical(
                sprintf('Unable to process messages - %s: "%s"', $exception::class, $exception->getMessage()),
                ['message_payload' => json_encode($payload ?? null)]
            );
            if (strpos($exception::class, 'Doctrine') !== false) {
                throw new \RuntimeException('Doctrine exception detected. Ending process.');
            }
        }
    }
}
