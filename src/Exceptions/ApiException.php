<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Exceptions;

use Company\LiveOpenSdk\Http\Response;

class ApiException extends LiveOpenException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        protected readonly int $statusCode,
        protected readonly ?string $requestId,
        protected readonly ?string $errorCode,
        array $context = [],
    ) {
        parent::__construct($message, $context, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @param array<string, mixed>|null $decoded
     * @param array<string, mixed> $context
     * @param list<string> $secrets
     */
    public static function fromResponse(
        Response $response,
        ?array $decoded,
        array $context,
        array $secrets,
    ): self {
        $requestId = null;
        $message = 'Live Open API request failed.';
        $errorCode = null;

        if (is_array($decoded)) {
            $requestId = self::stringFrom($decoded, 'request_id');
            $message = self::stringFromNested($decoded, [['error', 'message'], ['message']]) ?? $message;
            $errorCode = self::stringFromNested($decoded, [['error', 'code'], ['code']]);
        }

        $requestId ??= $response->header('X-Request-Id');
        $message = self::redactString($message, $secrets);
        $sanitizedContext = self::redactContext($context, $secrets);
        $sanitizedContext['response'] = [
            'status_code' => $response->statusCode,
            'request_id' => $requestId,
            'body_preview' => self::redactString(substr($response->body, 0, 1000), $secrets),
        ];

        $exceptionClass = match (true) {
            $response->statusCode === 401 || $response->statusCode === 403 => AuthenticationException::class,
            $response->statusCode === 404 => NotFoundException::class,
            $response->statusCode === 409 => ConflictException::class,
            $response->statusCode === 422 => ValidationException::class,
            $response->statusCode === 429 => RateLimitException::class,
            $response->statusCode >= 500 => ServerException::class,
            default => self::class,
        };

        return new $exceptionClass($message, $response->statusCode, $requestId, $errorCode, $sanitizedContext);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function stringFrom(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<list<string>> $paths
     */
    private static function stringFromNested(array $data, array $paths): ?string
    {
        foreach ($paths as $path) {
            $cursor = $data;

            foreach ($path as $segment) {
                if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                    continue 2;
                }

                $cursor = $cursor[$segment];
            }

            if (is_scalar($cursor)) {
                return (string) $cursor;
            }
        }

        return null;
    }
}
