<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class LiveRoom extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $features
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly ?string $id,
        public readonly ?string $externalRoomId,
        public readonly ?string $title,
        public readonly ?string $status,
        public readonly ?string $startsAt,
        public readonly ?string $endsAt,
        public readonly array $features,
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
            self::stringValue($data, 'id', 'room_id'),
            self::stringValue($data, 'external_room_id'),
            self::stringValue($data, 'title'),
            self::stringValue($data, 'status'),
            self::stringValue($data, 'starts_at'),
            self::stringValue($data, 'ends_at'),
            self::arrayValue($data, 'features')
        );
    }
}
