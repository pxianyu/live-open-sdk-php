<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Exceptions;

use Throwable;

class TransportException extends LiveOpenException
{
    /**
     * @param array<string, mixed> $context
     * @param list<string> $secrets
     */
    public static function fromThrowable(Throwable $throwable, array $context, array $secrets): self
    {
        return new self(
            'Transport request failed: ' . self::redactString($throwable->getMessage(), $secrets),
            self::redactContext($context, $secrets),
            (int) $throwable->getCode(),
            $throwable
        );
    }
}
