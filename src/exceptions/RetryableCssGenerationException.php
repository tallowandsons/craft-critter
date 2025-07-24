<?php

namespace mijewe\critter\exceptions;

/**
 * Retryable CSS Generation Exception
 *
 * Thrown when a CSS generation fails in a way that can be retried,
 * such as timeouts or temporary service failures.
 */
class RetryableCssGenerationException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
