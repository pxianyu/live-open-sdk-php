<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class MessagesPage extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     * @param list<Message> $items
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
        $messages = [];
        $items = $data['items'] ?? $data['messages'] ?? [];

        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $messages[] = Message::fromArray($item, $requestId);
                }
            }
        }

        return new self($requestId, $data, $messages, self::stringValue($data, 'next_cursor'));
    }
}
