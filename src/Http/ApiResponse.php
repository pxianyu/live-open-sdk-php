<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Http;

final class ApiResponse
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $decoded
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly array $data,
        public readonly string $requestId,
        public readonly int $statusCode,
        public readonly array $headers,
        public readonly array $decoded,
    ) {
    }
}
