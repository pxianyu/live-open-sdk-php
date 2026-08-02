<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Http;

final class Request
{
    public string $method;
    public string $url;
    public string $path;
    public array $headers;
    public array $query;
    public string $body;

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     */
    public function __construct(
        string $method,
        string $url,
        string $path,
        array $headers,
        array $query = [],
        string $body = ''
    ) {
        $this->method = $method;
        $this->url = $url;
        $this->path = $path;
        $this->headers = $headers;
        $this->query = $query;
        $this->body = $body;
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
