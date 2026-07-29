<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class AudienceSessionsPage extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     * @param list<AudienceSession> $items
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
        $sessions = [];
        $items = $data['items'] ?? $data['sessions'] ?? [];

        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $sessions[] = AudienceSession::fromArray($item, $requestId);
                }
            }
        }

        return new self($requestId, $data, $sessions, self::stringValue($data, 'next_cursor'));
    }
}
