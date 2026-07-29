<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class RecordingsPage extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     * @param list<Recording> $items
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly array $items,
        public readonly ?string $nextCursor,
    ) {
        parent::__construct($requestId, $raw);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $requestId): self
    {
        $recordings = [];
        $items = $data['items'] ?? $data['recordings'] ?? [];

        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $recordings[] = Recording::fromArray($item, $requestId);
                }
            }
        }

        return new self($requestId, $data, $recordings, self::stringValue($data, 'next_cursor'));
    }
}
