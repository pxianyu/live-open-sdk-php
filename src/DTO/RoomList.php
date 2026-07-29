<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class RoomList extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     * @param list<LiveRoom> $items
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly array $items,
        public readonly ?string $nextCursor,
        public readonly ?int $total,
    ) {
        parent::__construct($requestId, $raw);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $requestId): self
    {
        $rooms = [];
        $items = $data['items'] ?? $data['rooms'] ?? [];

        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $rooms[] = LiveRoom::fromArray($item, $requestId);
                }
            }
        }

        return new self(
            $requestId,
            $data,
            $rooms,
            self::stringValue($data, 'next_cursor'),
            self::intValue($data, 'total')
        );
    }
}
