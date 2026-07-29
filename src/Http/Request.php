<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Http;

final class Request
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly string $path,
        public readonly array $headers,
        public readonly array $query = [],
        public readonly string $body = '',
    ) {
    }

    /**
     * @param list<string> $redactedHeaders
     * @return array<string, mixed>
     */
    public function toArray(array $redactedHeaders = []): array
    {
        $redacted = [];
        $lookup = array_map('strtolower', $redactedHeaders);

        foreach ($this->headers as $name => $value) {
            $redacted[$name] = in_array(strtolower($name), $lookup, true)
                ? '[REDACTED]'
                : $value;
        }

        return [
            'method' => $this->method,
            'url' => $this->url,
            'path' => $this->path,
            'query' => $this->query,
            'headers' => $redacted,
            'body' => $this->body,
        ];
    }
}
