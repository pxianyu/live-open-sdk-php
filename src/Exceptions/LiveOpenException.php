<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Exceptions;

use RuntimeException;
use Throwable;

class LiveOpenException extends RuntimeException
{
    protected array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed> $context
     * @param list<string> $secrets
     * @return array<string, mixed>
     */
    protected static function redactContext(array $context, array $secrets): array
    {
        $redacted = [];

        foreach ($context as $key => $value) {
            $redacted[$key] = self::redactValue($value, $secrets);
        }

        return $redacted;
    }

    /**
     * @param mixed $value
     * @param list<string> $secrets
     * @return mixed
     */
    protected static function redactValue($value, array $secrets)
    {
        if (is_string($value)) {
            return self::redactString($value, $secrets);
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $key => $item) {
                $result[$key] = self::redactValue($item, $secrets);
            }

            return $result;
        }

        return $value;
    }

    /**
     * @param list<string> $secrets
     */
    protected static function redactString(string $value, array $secrets): string
    {
        $redacted = $value;

        foreach ($secrets as $secret) {
            if ($secret !== '') {
                $redacted = str_replace($secret, '[REDACTED]', $redacted);
            }
        }

        return $redacted;
    }
}
