<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Http;

final class ApiResponse
{
    public array $data;
    public string $requestId;
    public int $statusCode;
    public array $headers;
    public array $decoded;

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $decoded
     * @param array<string, string> $headers
     */
    public function __construct(
        array $data,
        string $requestId,
        int $statusCode,
        array $headers,
        array $decoded
    ) {
        $this->data = $data;
        $this->requestId = $requestId;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->decoded = $decoded;
    }
}
