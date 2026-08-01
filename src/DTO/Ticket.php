<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class Ticket extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     * @param list<string> $capabilities
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly ?string $id,
        public readonly ?string $ticket,
        public readonly ?string $role,
        public readonly ?string $origin,
        public readonly ?string $expiresAt,
        public readonly array $capabilities,
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
            self::stringValue($data, 'id', 'ticket_id'),
            self::stringValue($data, 'ticket', 'token'),
            self::stringValue($data, 'role'),
            self::stringValue($data, 'origin'),
            self::stringValue($data, 'expires_at'),
            array_values(self::arrayValue($data, 'capabilities'))
        );
    }
}
