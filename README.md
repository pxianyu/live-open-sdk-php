# Live Open SDK for PHP

`company/live-open-sdk-php` is a dependency-free PHP 8.1 server SDK for the Live Open Platform. It signs every request with the documented HMAC headers, sends JSON over `curl`, exposes only `users()` and `liveRooms()`, and keeps transport/auth failures free of leaked secrets.

## Requirements

- PHP `^8.1`
- `ext-curl`

## Install

```bash
composer require company/live-open-sdk-php
```

## Quick Start

```php
<?php

use Company\LiveOpenSdk\LiveOpenClient;

$client = new LiveOpenClient(
    appKey: getenv('LIVE_APP_KEY'),
    appSecret: getenv('LIVE_APP_SECRET'),
    keyId: getenv('LIVE_KEY_ID'),
    baseUrl: 'https://open.example.com',
);

$user = $client->users()->upsert(
    externalUserId: 'user-42',
    profile: [
        'nickname' => 'Ada',
        'avatar_url' => 'https://cdn.example.com/ada.png',
        'status' => 'active',
        'source_updated_at' => '2026-07-25T11:00:00+08:00',
    ],
    idempotencyKey: 'user-42-v3',
);

$room = $client->liveRooms()->create(
    externalRoomId: 'course-2026-001',
    title: '新品直播',
    idempotencyKey: 'room-course-2026-001',
    attributes: [
        'starts_at' => '2026-07-27T20:00:00+08:00',
    ],
);

$ticket = $client->liveRooms()->issueViewerTicket(
    roomId: $room->id ?? '',
    externalUserId: 'user-42',
    origin: 'https://customer.example',
    idempotencyKey: 'watch-user-42-room-1',
    payload: [
        'ttl' => 60,
    ],
);
```

## Resource Surface

### `users()`

- `upsert(string $externalUserId, array $profile, string $idempotencyKey): User`
- `batchUpsert(array $users, string $idempotencyKey): UserBatchResult`
- `get(string $externalUserId): User`
- `deactivate(string $externalUserId, string $idempotencyKey, array $payload = []): OperationResult`

### `liveRooms()`

- `create(?string $externalRoomId, string $title, string $idempotencyKey, array $attributes = []): LiveRoom`
- `get(string $roomId): LiveRoom`
- `list(array $filters = []): RoomList`
- `update(string $roomId, array $attributes, string $idempotencyKey): LiveRoom`
- `publish(string $roomId, string $idempotencyKey, array $payload = []): OperationResult`
- `setMember(string $roomId, string $externalUserId, string $role, string $idempotencyKey, array $attributes = []): OperationResult`
- `createBroadcastCredential(string $roomId, string $idempotencyKey, array $payload = []): BroadcastCredential`
- `stop(string $roomId, string $idempotencyKey, array $payload = []): OperationResult`
- `issueViewerTicket(string $roomId, string $externalUserId, string $origin, string $idempotencyKey, array $payload = []): Ticket`
- `issueOperatorTicket(string $roomId, string $externalUserId, string $origin, string $idempotencyKey, array $payload = []): Ticket`
- `revokeTicket(string $roomId, string $ticketId, string $idempotencyKey, array $payload = []): OperationResult`
- `messages(string $roomId, array $filters = []): MessagesPage`
- `sendComment(string $roomId, string $text, string $idempotencyKey, array $payload = []): OperationResult`
- `deleteComment(string $roomId, string $messageId, string $idempotencyKey, array $payload = []): OperationResult`
- `muteUser(string $roomId, string $externalUserId, string $idempotencyKey, array $payload = []): OperationResult`
- `metrics(string $roomId, array $filters = []): Metrics`
- `audienceSessions(string $roomId, array $filters = []): AudienceSessionsPage`
- `recordings(string $roomId, array $filters = []): RecordingsPage`

## Current Release Availability

The current server release supports application authentication, user synchronization,
room create/read/list/update/publish/stop, broadcast credentials, room tickets, SDK
sessions, bootstrap, media refresh, and realtime credentials.

The following methods are reserved in the SDK contract but currently return
`501 FEATURE_NOT_AVAILABLE` until the platform has persistent interaction history,
outbox publishing, sequence catch-up, and recording/audience query support:

- `setMember()`
- `messages()`
- `sendComment()`
- `deleteComment()`
- `muteUser()`
- `metrics()`
- `audienceSessions()`
- `recordings()`

## Request Signing

Every request includes:

- `X-Live-App-Key`
- `X-Live-Key-Id`
- `X-Live-Timestamp`
- `X-Live-Nonce`
- `X-Live-Signature`
- `Idempotency-Key` for all `POST` / `PUT` / `PATCH` / `DELETE` operations

The canonical string matches the integration plan:

```text
METHOD
NORMALIZED_PATH
NORMALIZED_QUERY
SHA256_HEX(RAW_BODY)
APP_KEY
KEY_ID
TIMESTAMP
NONCE
```

## Error Handling

- 4xx/5xx responses throw `ApiException` subclasses.
- `curl` and transport failures throw `TransportException`.
- JSON encode/decode problems throw `SerializationException`.
- Request snapshots in exception context redact `X-Live-Signature`, and secret values are stripped from messages/context.

## Tests

Run the pure PHP unit suite without PHPUnit:

```bash
php tests/run.php
```

If Composer is available, the same suite is exposed as:

```bash
composer test
```
