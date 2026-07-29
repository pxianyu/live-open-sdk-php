<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class BroadcastCredential extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly ?string $rtmpUrl,
        public readonly ?string $streamKey,
        public readonly ?string $streamUrl,
        public readonly ?string $expiresAt,
    ) {
        parent::__construct($requestId, $raw);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $requestId): self
    {
        return new self(
            $requestId,
            $data,
            self::stringValue($data, 'rtmp_url'),
            self::stringValue($data, 'stream_key'),
            self::stringValue($data, 'stream_url'),
            self::stringValue($data, 'expires_at')
        );
    }
}
