<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class AudienceSession extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly ?string $sessionId,
        public readonly ?string $userId,
        public readonly ?string $externalUserId,
        public readonly ?string $joinedAt,
        public readonly ?string $leftAt,
        public readonly ?int $durationSeconds,
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
            self::stringValue($data, 'session_id'),
            self::stringValue($data, 'user_id'),
            self::stringValue($data, 'external_user_id'),
            self::stringValue($data, 'joined_at'),
            self::stringValue($data, 'left_at'),
            self::intValue($data, 'duration_seconds')
        );
    }
}
