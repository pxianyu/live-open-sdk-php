<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk;

use Company\LiveOpenSdk\DTO\AudienceSessionsPage;
use Company\LiveOpenSdk\DTO\BroadcastCredential;
use Company\LiveOpenSdk\DTO\LiveRoom;
use Company\LiveOpenSdk\DTO\MessagesPage;
use Company\LiveOpenSdk\DTO\Metrics;
use Company\LiveOpenSdk\DTO\OperationResult;
use Company\LiveOpenSdk\DTO\RecordingsPage;
use Company\LiveOpenSdk\DTO\RoomList;
use Company\LiveOpenSdk\DTO\Ticket;

final class LiveRooms
{
    public function __construct(
        private readonly LiveOpenClient $client,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(?string $externalRoomId, string $title, string $idempotencyKey, array $attributes = []): LiveRoom
    {
        $payload = $attributes;
        $payload['title'] = $title;

        if ($externalRoomId !== null && $externalRoomId !== '') {
            $payload['external_room_id'] = $externalRoomId;
        }

        $response = $this->client->request('POST', '/open/v1/rooms', [
            'json' => $payload,
            'idempotencyKey' => $idempotencyKey,
        ]);

        return LiveRoom::fromArray($response->data, $response->requestId);
    }

    public function get(string $roomId): LiveRoom
    {
        $response = $this->client->request('GET', '/open/v1/rooms/' . rawurlencode($roomId));

        return LiveRoom::fromArray($response->data, $response->requestId);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = []): RoomList
    {
        $response = $this->client->request('GET', '/open/v1/rooms', ['query' => $filters]);

        return RoomList::fromArray($response->data, $response->requestId);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(string $roomId, array $attributes, string $idempotencyKey): LiveRoom
    {
        $response = $this->client->request('PATCH', '/open/v1/rooms/' . rawurlencode($roomId), [
            'json' => $attributes,
            'idempotencyKey' => $idempotencyKey,
        ]);

        return LiveRoom::fromArray($response->data, $response->requestId);
    }

    public function publish(string $roomId, string $idempotencyKey): OperationResult
    {
        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/publish',
            [
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return OperationResult::fromArray($response->data, $response->requestId);
    }

    public function createBroadcastCredential(string $roomId, string $idempotencyKey): BroadcastCredential
    {
        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/broadcast-credential',
            [
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return BroadcastCredential::fromArray($response->data, $response->requestId);
    }

    public function stop(string $roomId, string $idempotencyKey): OperationResult
    {
        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/stop',
            [
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return OperationResult::fromArray($response->data, $response->requestId);
    }

    public function issueViewerTicket(
        string $roomId,
        string $externalUserId,
        string $origin,
        string $idempotencyKey,
        ?int $ttl = null,
        array $capabilities = [],
    ): Ticket {
        $body = [
            'external_user_id' => $externalUserId,
            'origin' => $origin,
        ];
        if ($ttl !== null) {
            $body['ttl'] = $ttl;
        }
        if ($capabilities !== []) {
            $body['capabilities'] = $capabilities;
        }

        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/viewer-tickets',
            [
                'json' => $body,
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return Ticket::fromArray($response->data, $response->requestId);
    }

    public function issueOperatorTicket(
        string $roomId,
        string $externalUserId,
        string $origin,
        string $idempotencyKey,
        ?int $ttl = null,
        array $capabilities = [],
    ): Ticket {
        $body = [
            'external_user_id' => $externalUserId,
            'origin' => $origin,
        ];
        if ($ttl !== null) {
            $body['ttl'] = $ttl;
        }
        if ($capabilities !== []) {
            $body['capabilities'] = $capabilities;
        }

        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/operator-tickets',
            [
                'json' => $body,
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return Ticket::fromArray($response->data, $response->requestId);
    }

    public function revokeTicket(string $roomId, string $ticketId, string $idempotencyKey): OperationResult
    {
        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/tickets/' . rawurlencode($ticketId) . '/revoke',
            [
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return OperationResult::fromArray($response->data, $response->requestId);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function messages(string $roomId, array $filters = []): MessagesPage
    {
        $response = $this->client->request(
            'GET',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/messages',
            ['query' => $filters]
        );

        return MessagesPage::fromArray($response->data, $response->requestId);
    }

    public function sendComment(
        string $roomId,
        string $text,
        string $idempotencyKey,
        ?string $clientRequestId = null,
    ): OperationResult
    {
        $body = ['text' => $text];
        if ($clientRequestId !== null && $clientRequestId !== '') {
            $body['client_request_id'] = $clientRequestId;
        }

        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/commands/comments',
            [
                'json' => $body,
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return OperationResult::fromArray($response->data, $response->requestId);
    }

    public function deleteComment(
        string $roomId,
        string $messageId,
        string $idempotencyKey,
        ?string $reason = null,
    ): OperationResult
    {
        $body = ['message_id' => $messageId];
        if ($reason !== null && $reason !== '') {
            $body['reason'] = $reason;
        }

        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/commands/delete-comment',
            [
                'json' => $body,
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return OperationResult::fromArray($response->data, $response->requestId);
    }

    public function muteUser(
        string $roomId,
        string $externalUserId,
        string $idempotencyKey,
        ?string $reason = null,
    ): OperationResult
    {
        $body = ['external_user_id' => $externalUserId];
        if ($reason !== null && $reason !== '') {
            $body['reason'] = $reason;
        }

        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/commands/mute-user',
            [
                'json' => $body,
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return OperationResult::fromArray($response->data, $response->requestId);
    }

    public function unmuteUser(
        string $roomId,
        string $externalUserId,
        string $idempotencyKey,
    ): OperationResult
    {
        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/commands/unmute-user',
            [
                'json' => ['external_user_id' => $externalUserId],
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return OperationResult::fromArray($response->data, $response->requestId);
    }

    public function setRoomMute(string $roomId, bool $enabled, string $idempotencyKey): OperationResult
    {
        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/commands/room-mute',
            [
                'json' => ['enabled' => $enabled],
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return OperationResult::fromArray($response->data, $response->requestId);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function metrics(string $roomId, array $filters = []): Metrics
    {
        $response = $this->client->request(
            'GET',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/metrics',
            ['query' => $filters]
        );

        return Metrics::fromArray($response->data, $response->requestId);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function audienceSessions(string $roomId, array $filters = []): AudienceSessionsPage
    {
        $response = $this->client->request(
            'GET',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/audience-sessions',
            ['query' => $filters]
        );

        return AudienceSessionsPage::fromArray($response->data, $response->requestId);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function recordings(string $roomId, array $filters = []): RecordingsPage
    {
        $response = $this->client->request(
            'GET',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/recordings',
            ['query' => $filters]
        );

        return RecordingsPage::fromArray($response->data, $response->requestId);
    }

}
