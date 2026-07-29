<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Tests\Unit;

use Company\LiveOpenSdk\Auth\HmacSigner;
use Company\LiveOpenSdk\Tests\TestCase;

final class HmacSignerTest extends TestCase
{
    public function testCanonicalizationNormalizesPathAndSortsQuery(): void
    {
        $signer = new HmacSigner();
        $body = '{"title":"Demo"}';

        $canonical = $signer->canonicalize(
            'post',
            'https://open.example.com//open//v1//rooms//room_1',
            [
                'z' => 'last',
                'a' => 'first',
                'filter' => [
                    'status' => 'LIVE',
                    'tag' => '新品',
                ],
                'space' => 'hello world',
            ],
            $body,
            'app_key_123',
            'key_1',
            '1722000000',
            'nonce-123'
        );

        $expected = implode("\n", [
            'POST',
            '/open/v1/rooms/room_1',
            'a=first&filter%5Bstatus%5D=LIVE&filter%5Btag%5D=%E6%96%B0%E5%93%81&space=hello%20world&z=last',
            hash('sha256', $body),
            'app_key_123',
            'key_1',
            '1722000000',
            'nonce-123',
        ]);

        $this->assertSame($expected, $canonical);
        $this->assertSame(
            hash_hmac('sha256', $expected, 'super-secret'),
            $signer->sign(
                'post',
                '//open//v1//rooms//room_1',
                [
                    'z' => 'last',
                    'a' => 'first',
                    'filter' => [
                        'tag' => '新品',
                        'status' => 'LIVE',
                    ],
                    'space' => 'hello world',
                ],
                $body,
                'app_key_123',
                'key_1',
                '1722000000',
                'nonce-123',
                'super-secret'
            )
        );
    }
}
