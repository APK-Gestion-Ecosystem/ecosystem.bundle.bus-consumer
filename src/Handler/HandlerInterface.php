<?php

namespace Ecosystem\BusConsumerBundle\Handler;

interface HandlerInterface
{
    /**
     * @param array<string, mixed> $message
     * @param array<string, mixed> $metadata
     */
    public function __invoke(array $message, array $metadata): void;

    public function supports(string $namespace, string $event): bool;
}
