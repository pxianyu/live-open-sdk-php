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

    /**
     * @param array<string, mixed> $payload
     */
    public function publish(string $roomId, string $idempotencyKey, array $payload = []): OperationResult
    {
        return $this->command(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/publish',
            $payload,
            $idempotencyKey
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function setMember(
        string $roomId,
        string $externalUserId,
        string $role,
        string $idempotencyKey,
        array $attributes = [],
    ): OperationResult {
        $payload = $attributes;
        $payload['role'] = $role;

        return $this->command(
            'PUT',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/members/' . rawurlencode($externalUserId),
            $payload,
            $idempotencyKey
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createBroadcastCredential(string $roomId, string $idempotencyKey, array $payload = []): BroadcastCredential
    {
        $response = $this->client->request(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/broadcast-credential',
            [
                'json' => $payload,
                'idempotencyKey' => $idempotencyKey,
            ]
        );

        return BroadcastCredential::fromArray($response->data, $response->requestId);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function stop(string $roomId, string $idempotencyKey, array $payload = []): OperationResult
    {
        return $this->command(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/stop',
            $payload,
            $idempotencyKey
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function issueViewerTicket(
        string $roomId,
        string $externalUserId,
        string $origin,
        string $idempotencyKey,
        array $payload = [],
    ): Ticket {
        $body = $payload;
        $body['external_user_id'] = $externalUserId;
        $body['origin'] = $origin;

        return $this->issueTicket(
            '/open/v1/rooms/' . rawurlencode($roomId) . '/viewer-tickets',
            $body,
            $idempotencyKey
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function issueOperatorTicket(
        string $roomId,
        string $externalUserId,
        string $origin,
        string $idempotencyKey,
        array $payload = [],
    ): Ticket {
        $body = $payload;
        $body['external_user_id'] = $externalUserId;
        $body['origin'] = $origin;

        return $this->issueTicket(
            '/open/v1/rooms/' . rawurlencode($roomId) . '/operator-tickets',
            $body,
            $idempotencyKey
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function revokeTicket(string $roomId, string $ticketId, string $idempotencyKey, array $payload = []): OperationResult
    {
        return $this->command(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/tickets/' . rawurlencode($ticketId) . '/revoke',
            $payload,
            $idempotencyKey
        );
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

    /**
     * @param array<string, mixed> $payload
     */
    public function sendComment(string $roomId, string $text, string $idempotencyKey, array $payload = []): OperationResult
    {
        $body = $payload;
        $body['text'] = $text;

        return $this->command(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/commands/comments',
            $body,
            $idempotencyKey
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function deleteComment(string $roomId, string $messageId, string $idempotencyKey, array $payload = []): OperationResult
    {
        $body = $payload;
        $body['message_id'] = $messageId;

        return $this->command(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/commands/delete-comment',
            $body,
            $idempotencyKey
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function muteUser(string $roomId, string $externalUserId, string $idempotencyKey, array $payload = []): OperationResult
    {
        $body = $payload;
        $body['external_user_id'] = $externalUserId;

        return $this->command(
            'POST',
            '/open/v1/rooms/' . rawurlencode($roomId) . '/commands/mute-user',
            $body,
            $idempotencyKey
        );
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

    /**
     * @param array<string, mixed> $body
     */
    private function issueTicket(string $path, array $body, string $idempotencyKey): Ticket
    {
        $response = $this->client->request('POST', $path, [
            'json' => $body,
            'idempotencyKey' => $idempotencyKey,
        ]);

        return Ticket::fromArray($response->data, $response->requestId);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function command(string $method, string $path, array $body, string $idempotencyKey): OperationResult
    {
        $response = $this->client->request($method, $path, [
            'json' => $body,
            'idempotencyKey' => $idempotencyKey,
        ]);

        return OperationResult::fromArray($response->data, $response->requestId);
    }
}
