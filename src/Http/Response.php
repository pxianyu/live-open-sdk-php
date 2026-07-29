<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Http;

final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly array $headers,
        public readonly string $body,
    ) {
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $headerName => $value) {
            if (strcasecmp($headerName, $name) === 0) {
                return $value;
            }
        }

        return null;
    }
}
