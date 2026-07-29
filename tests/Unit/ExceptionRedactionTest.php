<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Tests\Unit;

use Company\LiveOpenSdk\Exceptions\TransportException;
use Company\LiveOpenSdk\Http\Request;
use Company\LiveOpenSdk\Http\Response;
use Company\LiveOpenSdk\Http\Transport;
use Company\LiveOpenSdk\LiveOpenClient;
use Company\LiveOpenSdk\Tests\TestCase;
use RuntimeException;

final class ExceptionRedactionTest extends TestCase
{
    public function testTransportExceptionsDoNotLeakAppSecretOrSignature(): void
    {
        $client = new LiveOpenClient(
            'app_key_123',
            'super-secret-value',
            'key_1',
            'https://open.example.com/',
            new ThrowingTransport(),
            nonceFactory: static fn (): string => 'nonce-123'
        );

        try {
            $client->users()->get('user-1');
            $this->fail('Expected TransportException was not thrown.');
        } catch (TransportException $exception) {
            $this->assertNotContains('super-secret-value', $exception->getMessage());
            $this->assertContains('[REDACTED]', $exception->getMessage());

            $contextJson = json_encode($exception->getContext(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $this->assertNotContains('super-secret-value', $contextJson ?: '');
            $this->assertContains('[REDACTED]', $contextJson ?: '');
            $this->assertSame('[REDACTED]', $exception->getContext()['request']['headers']['X-Live-Signature'] ?? null);
        }
    }
}

final class ThrowingTransport implements Transport
{
    public function send(Request $request): Response
    {
        $signature = $request->headers['X-Live-Signature'] ?? '';

        throw new RuntimeException(
            'network failed for secret super-secret-value with signature ' . $signature
        );
    }
}
