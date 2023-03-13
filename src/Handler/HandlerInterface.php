<?php

namespace Ecosystem\BusConsumerBundle\Handler;

interface HandlerInterface
{
    public function __invoke(array $message): void;
    public function supports(string $namespace, string $event): bool;
}
