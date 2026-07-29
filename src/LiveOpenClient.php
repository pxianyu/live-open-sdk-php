<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk;

use Closure;
use Company\LiveOpenSdk\Auth\HmacSigner;
use Company\LiveOpenSdk\Exceptions\ApiException;
use Company\LiveOpenSdk\Exceptions\SerializationException;
use Company\LiveOpenSdk\Exceptions\TransportException;
use Company\LiveOpenSdk\Http\ApiResponse;
use Company\LiveOpenSdk\Http\CurlTransport;
use Company\LiveOpenSdk\Http\Request;
use Company\LiveOpenSdk\Http\Response;
use Company\LiveOpenSdk\Http\Transport;
use JsonException;
use Throwable;

final class LiveOpenClient
{
    private readonly Transport $transport;
    private readonly HmacSigner $signer;
    private readonly Closure $timestampProvider;
    private readonly Closure $nonceFactory;
    private ?Users $users = null;
    private ?LiveRooms $liveRooms = null;

    /**
     * @var array<string, mixed>
     */
    private array $lastResponseMetadata = [];

    public function __construct(
        private readonly string $appKey,
        private readonly string $appSecret,
        private readonly string $keyId,
        private readonly string $baseUrl,
        ?Transport $transport = null,
        ?HmacSigner $signer = null,
        ?callable $timestampProvider = null,
        ?callable $nonceFactory = null,
    ) {
        $this->transport = $transport ?? new CurlTransport();
        $this->signer = $signer ?? new HmacSigner();
        $this->timestampProvider = Closure::fromCallable($timestampProvider ?? static fn (): int => time());
        $this->nonceFactory = Closure::fromCallable($nonceFactory ?? static fn (): string => bin2hex(random_bytes(16)));
    }

    public function users(): Users
    {
        return $this->users ??= new Users($this);
    }

    public function liveRooms(): LiveRooms
    {
        return $this->liveRooms ??= new LiveRooms($this);
    }

    /**
     * @return array<string, mixed>
     */
    public function getLastResponseMetadata(): array
    {
        return $this->lastResponseMetadata;
    }

    /**
     * @param array{
     *     query?: array<string, mixed>,
     *     json?: array<string, mixed>|list<mixed>|null,
     *     headers?: array<string, string>,
     *     idempotencyKey?: string|null
     * } $options
     */
    public function request(string $method, string $path, array $options = []): ApiResponse
    {
        $normalizedPath = $this->signer->normalizePath($path);
        $query = $options['query'] ?? [];
        $queryString = $this->signer->normalizeQuery($query);
        $body = $this->encodeJson($options['json'] ?? null);

        $timestamp = (string) ($this->timestampProvider)();
        $nonce = (string) ($this->nonceFactory)();
        $signature = $this->signer->sign(
            $method,
            $normalizedPath,
            $query,
            $body,
            $this->appKey,
            $this->keyId,
            $timestamp,
            $nonce,
            $this->appSecret
        );

        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'company-live-open-sdk-php/1.0',
            'X-Live-App-Key' => $this->appKey,
            'X-Live-Key-Id' => $this->keyId,
            'X-Live-Timestamp' => $timestamp,
            'X-Live-Nonce' => $nonce,
            'X-Live-Signature' => $signature,
        ];

        if ($body !== '') {
            $headers['Content-Type'] = 'application/json';
        }

        $headers = array_merge($headers, $options['headers'] ?? []);

        if ($this->isWriteMethod($method)) {
            $idempotencyKey = $options['idempotencyKey'] ?? null;

            if (!is_string($idempotencyKey) || $idempotencyKey === '') {
                throw new \InvalidArgumentException('Idempotency-Key is required for write requests.');
            }

            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $url = rtrim($this->baseUrl, '/') . $normalizedPath;

        if ($queryString !== '') {
            $url .= '?' . $queryString;
        }

        $request = new Request(
            strtoupper($method),
            $url,
            $normalizedPath,
            $headers,
            $query,
            $body,
        );

        try {
            $response = $this->transport->send($request);
        } catch (Throwable $throwable) {
            throw TransportException::fromThrowable(
                $throwable,
                ['request' => $request->toArray(['X-Live-Signature'])],
                [$this->appSecret, $signature]
            );
        }

        return $this->parseResponse($request, $response, [$this->appSecret, $signature]);
    }

    /**
     * @param list<string> $secrets
     */
    private function parseResponse(Request $request, Response $response, array $secrets): ApiResponse
    {
        $decoded = [];

        if ($response->body !== '') {
            try {
                $candidate = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new SerializationException(
                    'Unable to decode JSON response.',
                    [
                        'request' => $request->toArray(['X-Live-Signature']),
                        'response' => [
                            'status_code' => $response->statusCode,
                            'body_preview' => substr($response->body, 0, 1000),
                        ],
                    ],
                    $response->statusCode,
                    $exception
                );
            }

            if (!is_array($candidate)) {
                throw new SerializationException(
                    'Decoded JSON response is not an object.',
                    [
                        'request' => $request->toArray(['X-Live-Signature']),
                        'response' => [
                            'status_code' => $response->statusCode,
                            'body_preview' => substr($response->body, 0, 1000),
                        ],
                    ],
                    $response->statusCode
                );
            }

            $decoded = $candidate;
        }

        if ($response->statusCode >= 400) {
            throw ApiException::fromResponse(
                $response,
                $decoded,
                ['request' => $request->toArray(['X-Live-Signature'])],
                $secrets
            );
        }

        $requestId = '';
        $data = [];

        if ($decoded !== []) {
            $requestId = isset($decoded['request_id']) && is_scalar($decoded['request_id'])
                ? (string) $decoded['request_id']
                : (string) ($response->header('X-Request-Id') ?? '');
            $data = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : $decoded;
        }

        $this->lastResponseMetadata = [
            'request_id' => $requestId,
            'status_code' => $response->statusCode,
            'headers' => $response->headers,
        ];

        return new ApiResponse($data, $requestId, $response->statusCode, $response->headers, $decoded);
    }

    /**
     * @param array<string, mixed>|list<mixed>|null $payload
     */
    private function encodeJson(array|null $payload): string
    {
        if ($payload === null) {
            return '';
        }

        try {
            return json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException $exception) {
            throw new SerializationException('Unable to encode request body to JSON.', previous: $exception);
        }
    }

    private function isWriteMethod(string $method): bool
    {
        return in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }
}
