<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

abstract class AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly string $requestId,
        public readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    protected static function stringValue(array $data, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && is_scalar($data[$key])) {
                return (string) $data[$key];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected static function intValue(array $data, string ...$keys): ?int
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && is_numeric($data[$key])) {
                return (int) $data[$key];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected static function boolValue(array $data, string ...$keys): ?bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (is_bool($value)) {
                return $value;
            }

            if ($value === 1 || $value === 0 || $value === '1' || $value === '0') {
                return (bool) $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected static function arrayValue(array $data, string ...$keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        return [];
    }
}
