<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class User extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly ?string $id,
        public readonly ?string $externalUserId,
        public readonly ?string $nickname,
        public readonly ?string $avatarUrl,
        public readonly ?string $status,
        public readonly ?string $sourceUpdatedAt,
        public readonly array $metadata,
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
            self::stringValue($data, 'id', 'user_id'),
            self::stringValue($data, 'external_user_id'),
            self::stringValue($data, 'nickname'),
            self::stringValue($data, 'avatar_url'),
            self::stringValue($data, 'status'),
            self::stringValue($data, 'source_updated_at'),
            self::arrayValue($data, 'metadata')
        );
    }
}
