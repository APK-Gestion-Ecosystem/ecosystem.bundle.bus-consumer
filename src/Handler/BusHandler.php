<?php

namespace Ecosystem\BusConsumerBundle\Handler;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Contracts\Service\Attribute\Required;

class BusHandler
{
    #[Required]
    public LoggerInterface $logger;

    public function __construct(
        #[TaggedIterator('ecosystem.bus.handler')] private iterable $handlers
    ) {
    }

    /** @param array<mixed> $message */
    /** @param array<mixed> $metadata */
    public function __invoke(array $message, array $metadata): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($message['namespace'], $message['event'])) {
                $this->logger->debug(sprintf(
                    'Dispatching "%s:%s" to handler %s.',
                    $message['namespace'],
                    $message['event'],
                    $handler::class
                ));

                $handler($message, $metadata);
            }
        }
    }
}
