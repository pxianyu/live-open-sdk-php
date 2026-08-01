<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk;

use Company\LiveOpenSdk\DTO\OperationResult;
use Company\LiveOpenSdk\DTO\User;
use Company\LiveOpenSdk\DTO\UserBatchResult;

final class Users
{
    public function __construct(
        private readonly LiveOpenClient $client,
    ) {
    }

    /**
     * @param array<string, mixed> $profile
     */
    public function upsert(string $externalUserId, array $profile, string $idempotencyKey): User
    {
        $response = $this->client->request('PUT', '/open/v1/users/' . rawurlencode($externalUserId), [
            'json' => ['profile' => $profile],
            'idempotencyKey' => $idempotencyKey,
        ]);

        return User::fromArray($response->data, $response->requestId);
    }

    /**
     * @param list<array<string, mixed>> $users
     */
    public function batchUpsert(array $users, string $idempotencyKey): UserBatchResult
    {
        $response = $this->client->request('POST', '/open/v1/users/batch-upsert', [
            'json' => ['users' => $users],
            'idempotencyKey' => $idempotencyKey,
        ]);

        return UserBatchResult::fromArray($response->data, $response->requestId);
    }

    public function get(string $externalUserId): User
    {
        $response = $this->client->request('GET', '/open/v1/users/' . rawurlencode($externalUserId));

        return User::fromArray($response->data, $response->requestId);
    }

    public function deactivate(string $externalUserId, string $idempotencyKey): OperationResult
    {
        $response = $this->client->request(
            'POST',
            '/open/v1/users/' . rawurlencode($externalUserId) . '/deactivate',
            [
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return OperationResult::fromArray($response->data, $response->requestId);
    }
}
