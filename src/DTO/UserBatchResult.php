<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class UserBatchResult extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     * @param list<User> $users
     * @param list<array<string, mixed>> $failures
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly array $users,
        public readonly array $failures,
    ) {
        parent::__construct($requestId, $raw);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $requestId): self
    {
        $items = $data['users'] ?? $data['items'] ?? [];
        $users = [];

        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $users[] = User::fromArray($item, $requestId);
                }
            }
        }

        $failures = [];
        $rawFailures = $data['failures'] ?? [];

        if (is_array($rawFailures)) {
            foreach ($rawFailures as $failure) {
                if (is_array($failure)) {
                    $failures[] = $failure;
                }
            }
        }

        return new self($requestId, $data, $users, $failures);
    }
}
