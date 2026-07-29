<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class OperationResult extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly ?string $id,
        public readonly ?string $status,
        public readonly ?string $message,
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
            self::stringValue($data, 'id', 'message_id', 'ticket_id', 'event_id'),
            self::stringValue($data, 'status'),
            self::stringValue($data, 'message')
        );
    }
}
