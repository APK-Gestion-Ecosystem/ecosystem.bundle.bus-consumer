<?php

namespace Ecosystem\BusConsumerBundle\Handler;

interface HandlerInterface
{
    public function __invoke(array $message, array $metadata): void;
    public function supports(string $namespace, string $event): bool;
}
