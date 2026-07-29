<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class Message extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly ?string $id,
        public readonly ?string $eventId,
        public readonly ?int $sequence,
        public readonly ?string $authorId,
        public readonly ?string $authorNickname,
        public readonly ?string $contentType,
        public readonly ?string $text,
        public readonly ?string $occurredAt,
    ) {
        parent::__construct($requestId, $raw);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $requestId): self
    {
        $author = self::arrayValue($data, 'author');
        $content = self::arrayValue($data, 'content');

        return new self(
            $requestId,
            $data,
            self::stringValue($data, 'message_id', 'id'),
            self::stringValue($data, 'event_id'),
            self::intValue($data, 'sequence'),
            self::stringValue($author, 'id'),
            self::stringValue($author, 'nickname'),
            self::stringValue($content, 'type'),
            self::stringValue($content, 'text'),
            self::stringValue($data, 'occurred_at', 'created_at')
        );
    }
}
