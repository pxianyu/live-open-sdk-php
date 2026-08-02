<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Exceptions;

use Company\LiveOpenSdk\Http\Response;

class ApiException extends LiveOpenException
{
    public int $statusCode;
    public ?string $requestId;
    public ?string $errorCode;
    public ?int $businessCode;

    public function __construct(
        string $message,
        int $statusCode,
        ?string $requestId,
        ?string $errorCode,
        array $context = [],
        ?int $businessCode = null
    ) {
        $this->statusCode = $statusCode;
        $this->requestId = $requestId;
        $this->errorCode = $errorCode;
        $this->businessCode = $businessCode;
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

    public function getBusinessCode(): ?int
    {
        return $this->businessCode;
    }

    public static function fromResponse(
        Response $response,
        array $decoded,
        array $context,
        array $secrets
    ): self {
        $error = is_array($decoded['error'] ?? null) ? $decoded['error'] : [];
        $requestId = is_scalar($decoded['request_id'] ?? null)
            ? (string)$decoded['request_id']
            : $response->header('X-Request-Id');
        $message = is_scalar($error['message'] ?? null)
            ? (string)$error['message']
            : (is_scalar($decoded['message'] ?? null) ? (string)$decoded['message'] : 'Live Open API request failed.');
        $errorCode = is_scalar($error['code'] ?? null) ? (string)$error['code'] : null;
        $businessCode = is_numeric($decoded['status'] ?? null) ? (int)$decoded['status'] : null;
        $context = self::redactContext($context, $secrets);
        $context['response'] = [
            'status_code' => $response->statusCode,
            'request_id' => $requestId,
            'body_preview' => self::redactString(substr($response->body, 0, 1000), $secrets),
        ];

        return new self(
            self::redactString($message, $secrets),
            $response->statusCode,
            $requestId,
            $errorCode,
            $context,
            $businessCode
        );
    }
}
