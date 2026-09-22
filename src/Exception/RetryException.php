<?php

namespace Ecosystem\BusConsumerBundle\Exception;

class RetryException extends \RuntimeException
{
    public function __construct(string $message = '', protected readonly int $timeout = 120, int $code = 0, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
