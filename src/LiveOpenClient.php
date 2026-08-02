<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk;

use Company\LiveOpenSdk\Exceptions\ApiException;
use Company\LiveOpenSdk\Exceptions\SerializationException;
use Company\LiveOpenSdk\Exceptions\TransportException;
use Company\LiveOpenSdk\Http\ApiResponse;
use Company\LiveOpenSdk\Http\CurlTransport;
use Company\LiveOpenSdk\Http\Request;
use Company\LiveOpenSdk\Http\Transport;
use JsonException;
use Throwable;

final class LiveOpenClient
{
    public string $appKey;
    public string $appSecret;
    public string $baseUrl;
    public Transport $transport;

    public function __construct(
        string $appKey,
        string $appSecret,
        string $baseUrl,
        ?Transport $transport = null
    ) {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->transport = $transport ?? new CurlTransport();
    }

    public function users(): Users
    {
        return new Users($this);
    }

    /**
     * 获取商户管理员的原后台令牌和接口地址。
     *
     * @return array<string, mixed>
     */
    public function adminToken(): array
    {
        return $this->request('POST', '/open/v1/admin-token')->data;
    }

    /**
     * 请求开放平台的令牌转换接口。
     *
     * @param array{query?: array<string, mixed>, json?: array<string, mixed>|null} $options
     */
    public function request(string $method, string $path, array $options = []): ApiResponse
    {
        $options['json'] = array_merge($options['json'] ?? [], [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
        ]);

        return $this->send(
            $this->baseUrl,
            $method,
            $path,
            $options,
            [],
            [$this->appSecret]
        );
    }

    /**
     * 使用管理员令牌透明调用原商户后台接口。
     *
     * @param array{query?: array<string, mixed>, json?: array<string, mixed>|null} $options
     */
    public function adminRequest(
        string $apiBaseUrl,
        string $accessToken,
        string $method,
        string $path,
        array $options = []
    ): ApiResponse {
        return $this->send(
            rtrim($apiBaseUrl, '/'),
            $method,
            $path,
            $options,
            ['Authori-zation' => $accessToken],
            [$accessToken]
        );
    }

    /**
     * 发送一次 HTTP 请求，保持目标接口的原始响应结构。
     *
     * @param array{query?: array<string, mixed>, json?: array<string, mixed>|null} $options
     * @param array<string, string> $headers
     * @param list<string> $secrets
     */
    public function send(
        string $baseUrl,
        string $method,
        string $path,
        array $options,
        array $headers,
        array $secrets
    ): ApiResponse {
        $path = '/' . ltrim($path, '/');
        $query = $options['query'] ?? [];
        $url = rtrim($baseUrl, '/') . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $body = '';
        if (array_key_exists('json', $options) && $options['json'] !== null) {
            try {
                $body = json_encode($options['json'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (JsonException $exception) {
                throw new SerializationException('Unable to encode request body to JSON.', [], 0, $exception);
            }
            $headers['Content-Type'] = 'application/json';
        }

        $request = new Request(
            strtoupper($method),
            $url,
            $path,
            array_merge(['Accept' => 'application/json'], $headers),
            $query,
            $body
        );

        try {
            $response = $this->transport->send($request);
        } catch (Throwable $throwable) {
            throw TransportException::fromThrowable(
                $throwable,
                ['request' => $request->toArray(array_keys($headers))],
                $secrets
            );
        }

        $decoded = [];
        if ($response->body !== '') {
            try {
                $decoded = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new SerializationException(
                    'Unable to decode JSON response.',
                    ['request' => $request->toArray(array_keys($headers))],
                    $response->statusCode,
                    $exception
                );
            }
            if (!is_array($decoded)) {
                throw new SerializationException(
                    'Decoded JSON response is not an object.',
                    ['request' => $request->toArray(array_keys($headers))],
                    $response->statusCode
                );
            }
        }

        if ($response->statusCode >= 400 || is_array($decoded['error'] ?? null)) {
            throw ApiException::fromResponse(
                $response,
                $decoded,
                ['request' => $request->toArray(array_keys($headers))],
                $secrets
            );
        }

        $requestId = (string)($decoded['request_id'] ?? $response->header('X-Request-Id') ?? '');
        $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : $decoded;

        return new ApiResponse($data, $requestId, $response->statusCode, $response->headers, $decoded);
    }
}
