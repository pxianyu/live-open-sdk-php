<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class Recording extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly ?string $id,
        public readonly ?string $status,
        public readonly ?string $playbackUrl,
        public readonly ?int $durationSeconds,
        public readonly ?string $createdAt,
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
            self::stringValue($data, 'id', 'recording_id'),
            self::stringValue($data, 'status'),
            self::stringValue($data, 'playback_url', 'url'),
            self::intValue($data, 'duration_seconds'),
            self::stringValue($data, 'created_at')
        );
    }
}
