<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Http;

use Company\LiveOpenSdk\Exceptions\TransportException;

final class CurlTransport implements Transport
{
    public function __construct(
        private readonly int $connectTimeoutSeconds = 10,
        private readonly int $timeoutSeconds = 30,
    ) {
    }

    public function send(Request $request): Response
    {
        if (!function_exists('curl_init')) {
            throw new TransportException('The cURL extension is required to send requests.');
        }

        $handle = curl_init($request->url);

        if ($handle === false) {
            throw new TransportException('Unable to initialize cURL.');
        }

        $headers = [];
        $headerLines = [];

        foreach ($request->headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $request->method,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_POSTFIELDS => $request->body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HEADERFUNCTION => static function ($curlHandle, string $headerLine) use (&$headers): int {
                $trimmed = trim($headerLine);

                if ($trimmed === '' || !str_contains($trimmed, ':')) {
                    return strlen($headerLine);
                }

                [$name, $value] = explode(':', $trimmed, 2);
                $headers[trim($name)] = trim($value);

                return strlen($headerLine);
            },
        ]);

        $body = curl_exec($handle);

        if ($body === false) {
            $message = curl_error($handle);
            curl_close($handle);

            throw new TransportException('cURL error: ' . $message);
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return new Response($statusCode, $headers, $body);
    }
}
