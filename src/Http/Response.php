<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Http;

final class Response
{
    public int $statusCode;
    public array $headers;
    public string $body;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        int $statusCode,
        array $headers,
        string $body
    ) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
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
