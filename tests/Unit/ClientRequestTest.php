<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Tests\Unit;

use Company\LiveOpenSdk\Exceptions\ApiException;
use Company\LiveOpenSdk\LiveOpenClient;
use Company\LiveOpenSdk\Http\Request;
use Company\LiveOpenSdk\Http\Response;
use Company\LiveOpenSdk\Http\Transport;
use Company\LiveOpenSdk\Tests\TestCase;

final class ClientRequestTest extends TestCase
{
    public function testBusinessErrorEnvelopeThrowsApiException(): void
    {
        $transport = new CapturingTransport(new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"status":40302,"request_id":"req_error","data":null,"error":{"code":"ORIGIN_DENIED","message":"Origin is not allowed"}}'
        ));
        $client = new LiveOpenClient(
            'app_key_123',
            'super-secret',
            'key_1',
            'https://open.example.com/',
            $transport,
        );

        try {
            $client->users()->get('user-1');
        } catch (ApiException $exception) {
            $this->assertSame(200, $exception->getStatusCode());
            $this->assertSame(40302, $exception->getBusinessCode());
            $this->assertSame('ORIGIN_DENIED', $exception->getErrorCode());
            $this->assertSame('req_error', $exception->getRequestId());
            return;
        }

        $this->fail('A business error envelope must throw ApiException.');
    }

    public function testUsersUpsertBuildsExpectedPathHeadersAndBody(): void
    {
        $transport = new CapturingTransport(new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"request_id":"req_123","data":{"id":"usr_1","external_user_id":"user/a","nickname":"Ada"}}'
        ));

        $client = new LiveOpenClient(
            'app_key_123',
            'super-secret',
            'key_1',
            'https://open.example.com/',
            $transport,
            timestampProvider: static fn (): int => 1722000000,
            nonceFactory: static fn (): string => 'nonce-123'
        );

        $user = $client->users()->upsert('user/a', ['nickname' => 'Ada'], 'idem-1');

        $this->assertSame('usr_1', $user->id);
        $this->assertSame('/open/v1/users/user%2Fa', $transport->lastRequest?->path);
        $this->assertSame('https://open.example.com/open/v1/users/user%2Fa', $transport->lastRequest?->url);
        $this->assertSame('idem-1', $transport->lastRequest?->headers['Idempotency-Key'] ?? null);
        $this->assertSame('app_key_123', $transport->lastRequest?->headers['X-Live-App-Key'] ?? null);
        $this->assertSame('key_1', $transport->lastRequest?->headers['X-Live-Key-Id'] ?? null);
        $this->assertSame('1722000000', $transport->lastRequest?->headers['X-Live-Timestamp'] ?? null);
        $this->assertSame('nonce-123', $transport->lastRequest?->headers['X-Live-Nonce'] ?? null);
        $this->assertSame('application/json', $transport->lastRequest?->headers['Content-Type'] ?? null);
        $this->assertSame('{"profile":{"nickname":"Ada"}}', $transport->lastRequest?->body);
    }

    public function testRoomMessagesPreserveQueryStringEncoding(): void
    {
        $transport = new CapturingTransport(new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"request_id":"req_456","data":{"items":[],"next_cursor":"cursor_2"}}'
        ));

        $client = new LiveOpenClient(
            'app_key_123',
            'super-secret',
            'key_1',
            'https://open.example.com/',
            $transport,
            nonceFactory: static fn (): string => 'nonce-999'
        );

        $page = $client->liveRooms()->messages('room_1', [
            'before_cursor' => 'cursor 1',
            'limit' => 50,
        ]);

        $this->assertSame('cursor_2', $page->nextCursor);
        $this->assertSame(
            'https://open.example.com/open/v1/rooms/room_1/messages?before_cursor=cursor%201&limit=50',
            $transport->lastRequest?->url
        );
        $this->assertTrue(!isset($transport->lastRequest?->headers['Idempotency-Key']));
    }

    public function testRoomDataMethodsMapCurrentServerResponses(): void
    {
        $transport = new QueueingTransport([
            new Response(200, ['Content-Type' => 'application/json'], '{"request_id":"req_messages","data":{"items":[{"message_id":"msg_1","content":{"text":"hello"}}],"next_cursor":"cursor_2"}}'),
            new Response(201, ['Content-Type' => 'application/json'], '{"request_id":"req_comment","data":{"event_id":"evt_comment","status":"COMMITTED"}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"request_id":"req_delete","data":{"id":"msg_1","status":"DELETED"}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"request_id":"req_mute","data":{"id":"usr_1","status":"MUTED"}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"request_id":"req_metrics","data":{"online_count":8,"viewer_count":30,"watch_duration_seconds":900,"average_watch_duration_seconds":30,"comment_count":4,"like_count":12,"series":[]}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"request_id":"req_audience","data":{"items":[{"session_id":"view_1"}],"next_cursor":"cursor_3"}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"request_id":"req_recordings","data":{"items":[{"id":"rec_1","status":"READY"}],"next_cursor":"cursor_4"}}'),
        ]);
        $client = new LiveOpenClient(
            'app_key_123',
            'super-secret',
            'key_1',
            'https://open.example.com/',
            $transport,
            nonceFactory: static fn (): string => 'nonce-room-data'
        );

        $messages = $client->liveRooms()->messages('room_1');
        $comment = $client->liveRooms()->sendComment('room_1', 'hello', 'idem-comment');
        $deleted = $client->liveRooms()->deleteComment('room_1', 'msg_1', 'idem-delete');
        $muted = $client->liveRooms()->muteUser('room_1', 'user_1', 'idem-mute');
        $metrics = $client->liveRooms()->metrics('room_1');
        $audience = $client->liveRooms()->audienceSessions('room_1');
        $recordings = $client->liveRooms()->recordings('room_1');

        $this->assertSame('msg_1', $messages->items[0]->id);
        $this->assertSame('evt_comment', $comment->id);
        $this->assertSame('DELETED', $deleted->status);
        $this->assertSame('MUTED', $muted->status);
        $this->assertSame(900, $metrics->watchDurationSeconds);
        $this->assertSame(30, $metrics->averageWatchDurationSeconds);
        $this->assertSame('view_1', $audience->items[0]->sessionId);
        $this->assertSame('rec_1', $recordings->items[0]->id);

        $this->assertSame('/open/v1/rooms/room_1/commands/comments', $transport->requests[1]->path);
        $this->assertSame(['text' => 'hello'], json_decode($transport->requests[1]->body, true));
        $this->assertSame(
            ['external_user_id' => 'user_1'],
            json_decode($transport->requests[3]->body, true)
        );
        $this->assertSame('/open/v1/rooms/room_1/metrics', $transport->requests[4]->path);
    }

    public function testRoomModerationCommandsUseExplicitPaths(): void
    {
        $transport = new QueueingTransport([
            new Response(200, ['Content-Type' => 'application/json'], '{"request_id":"req_unmute","data":{"id":"usr_1","status":"ACTIVE"}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"request_id":"req_room_mute","data":{"status":"MUTED"}}'),
        ]);
        $client = new LiveOpenClient(
            'app_key_123',
            'super-secret',
            'key_1',
            'https://open.example.com/',
            $transport,
            nonceFactory: static fn (): string => 'nonce-room-moderation'
        );

        $unmuted = $client->liveRooms()->unmuteUser('room_1', 'user_1', 'idem-unmute');
        $roomMuted = $client->liveRooms()->setRoomMute('room_1', true, 'idem-room-mute');

        $this->assertSame('ACTIVE', $unmuted->status);
        $this->assertSame('MUTED', $roomMuted->status);
        $this->assertSame(
            '/open/v1/rooms/room_1/commands/unmute-user',
            $transport->requests[0]->path
        );
        $this->assertSame(
            ['external_user_id' => 'user_1'],
            json_decode($transport->requests[0]->body, true)
        );
        $this->assertSame('idem-unmute', $transport->requests[0]->headers['Idempotency-Key'] ?? null);
        $this->assertSame(
            '/open/v1/rooms/room_1/commands/room-mute',
            $transport->requests[1]->path
        );
        $this->assertSame(['enabled' => true], json_decode($transport->requests[1]->body, true));
        $this->assertSame('idem-room-mute', $transport->requests[1]->headers['Idempotency-Key'] ?? null);
    }

    public function testRetryResignsIdempotentWriteAfterTemporaryFailure(): void
    {
        $transport = new QueueingTransport([
            new Response(503, ['Content-Type' => 'application/json'], '{"error":{"code":"unavailable"}}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"request_id":"req_retry","data":{"id":"usr_1","external_user_id":"user-1"}}'),
        ]);
        $client = new LiveOpenClient(
            'app_key_123',
            'super-secret',
            'key_1',
            'https://open.example.com/',
            $transport,
            timestampProvider: static fn (): int => 1722000000,
            nonceFactory: (static function (): callable {
                $attempt = 0;

                return static function () use (&$attempt): string {
                    $attempt++;

                    return 'nonce-retry-' . $attempt;
                };
            })()
        );

        $user = $client->users()->upsert('user-1', ['nickname' => 'Ada'], 'idem-retry');

        $this->assertSame('usr_1', $user->id);
        $this->assertSame(2, count($transport->requests));
        $this->assertTrue(
            $transport->requests[0]->headers['X-Live-Signature']
                !== $transport->requests[1]->headers['X-Live-Signature']
        );
        $this->assertTrue(
            $transport->requests[0]->headers['X-Live-Nonce']
                !== $transport->requests[1]->headers['X-Live-Nonce']
        );
        $this->assertSame('idem-retry', $transport->requests[0]->headers['Idempotency-Key'] ?? null);
        $this->assertSame('idem-retry', $transport->requests[1]->headers['Idempotency-Key'] ?? null);
    }

    public function testUsersP0ApiContractBuildsExpectedRequestsAndMapsResponses(): void
    {
        $transport = new QueueingTransport([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_batch","data":{"users":[{"id":"usr_1","external_user_id":"user-1","nickname":"Ada"}],"failures":[{"external_user_id":"user-2","code":"invalid_profile"}]}}'
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_get","data":{"id":"usr_1","external_user_id":"user-1","nickname":"Ada","status":"active","metadata":{"segment":"vip"}}}'
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_deactivate","data":{"id":"usr_1","status":"DEACTIVATED","message":"User deactivated"}}'
            ),
        ]);

        $client = new LiveOpenClient(
            'app_key_123',
            'super-secret',
            'key_1',
            'https://open.example.com/',
            $transport,
            nonceFactory: static fn (): string => 'nonce-users'
        );

        $batch = $client->users()->batchUpsert([
            [
                'external_user_id' => 'user-1',
                'nickname' => 'Ada',
                'status' => 'active',
            ],
            [
                'external_user_id' => 'user-2',
                'nickname' => 'Grace',
            ],
        ], 'idem-batch');
        $user = $client->users()->get('user-1');
        $result = $client->users()->deactivate('user-1', 'idem-deactivate');

        $this->assertSame(1, count($batch->users));
        $this->assertSame('usr_1', $batch->users[0]->id);
        $this->assertSame(1, count($batch->failures));
        $this->assertSame('invalid_profile', (string) ($batch->failures[0]['code'] ?? ''));

        $this->assertSame('usr_1', $user->id);
        $this->assertSame('active', $user->status);
        $this->assertSame('vip', (string) ($user->metadata['segment'] ?? ''));

        $this->assertSame('usr_1', $result->id);
        $this->assertSame('DEACTIVATED', $result->status);

        $this->assertSame(3, count($transport->requests));

        $batchRequest = $transport->requests[0];
        $this->assertSame('POST', $batchRequest->method);
        $this->assertSame('/open/v1/users/batch-upsert', $batchRequest->path);
        $this->assertSame('idem-batch', $batchRequest->headers['Idempotency-Key'] ?? null);
        $this->assertSame([
            'users' => [
                [
                    'external_user_id' => 'user-1',
                    'nickname' => 'Ada',
                    'status' => 'active',
                ],
                [
                    'external_user_id' => 'user-2',
                    'nickname' => 'Grace',
                ],
            ],
        ], json_decode($batchRequest->body, true));

        $getRequest = $transport->requests[1];
        $this->assertSame('GET', $getRequest->method);
        $this->assertSame('/open/v1/users/user-1', $getRequest->path);
        $this->assertTrue(!isset($getRequest->headers['Idempotency-Key']));

        $deactivateRequest = $transport->requests[2];
        $this->assertSame('POST', $deactivateRequest->method);
        $this->assertSame('/open/v1/users/user-1/deactivate', $deactivateRequest->path);
        $this->assertSame('idem-deactivate', $deactivateRequest->headers['Idempotency-Key'] ?? null);
        $this->assertSame('', $deactivateRequest->body);
    }

    public function testLiveRoomsP0ApiContractBuildsExpectedRequestsAndMapsResponses(): void
    {
        $transport = new QueueingTransport([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_create","data":{"id":"room_1","external_room_id":"course-2026-001","title":"新品直播","status":"DRAFT","starts_at":"2026-07-27T20:00:00+08:00","features":{"chat":true}}}'
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_list","data":{"items":[{"id":"room_1","external_room_id":"course-2026-001","title":"新品直播","status":"PUBLISHED"}],"next_cursor":"cursor_2","total":1}}'
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_get","data":{"room_id":"room_1","external_room_id":"course-2026-001","title":"新品直播","status":"PUBLISHED"}}'
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_update","data":{"id":"room_1","title":"新品直播-更新","status":"PUBLISHED"}}'
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_publish","data":{"id":"room_1","status":"PUBLISHED","message":"Room published"}}'
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_stop","data":{"id":"room_1","status":"STOPPED","message":"Room stopped"}}'
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_viewer","data":{"id":"ticket_viewer_1","ticket":"viewer-once","role":"viewer","origin":"https://customer.example","expires_at":"2026-07-26T12:01:00Z","capabilities":["room:view"]}}'
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"request_id":"req_operator","data":{"id":"ticket_operator_1","ticket":"operator-once","role":"operator","origin":"https://ops.example","expires_at":"2026-07-26T12:01:00Z","capabilities":["room:view","room:mute"]}}'
            ),
        ]);

        $client = new LiveOpenClient(
            'app_key_123',
            'super-secret',
            'key_1',
            'https://open.example.com/',
            $transport,
            nonceFactory: static fn (): string => 'nonce-rooms'
        );

        $created = $client->liveRooms()->create(
            'course-2026-001',
            '新品直播',
            'idem-create',
            [
                'starts_at' => '2026-07-27T20:00:00+08:00',
                'features' => ['chat' => true],
            ]
        );
        $listed = $client->liveRooms()->list(['status' => 'PUBLISHED', 'limit' => 20]);
        $fetched = $client->liveRooms()->get('room_1');
        $updated = $client->liveRooms()->update('room_1', ['title' => '新品直播-更新'], 'idem-update');
        $published = $client->liveRooms()->publish('room_1', 'idem-publish');
        $stopped = $client->liveRooms()->stop('room_1', 'idem-stop');
        $viewerTicket = $client->liveRooms()->issueViewerTicket(
            'room_1',
            'user-1',
            'https://customer.example',
            'idem-viewer',
            60,
            ['room:view']
        );
        $operatorTicket = $client->liveRooms()->issueOperatorTicket(
            'room_1',
            'operator-1',
            'https://ops.example',
            'idem-operator',
            60,
            ['room:view', 'room:mute']
        );

        $this->assertSame('room_1', $created->id);
        $this->assertSame('course-2026-001', $created->externalRoomId);
        $this->assertSame(true, $created->features['chat'] ?? null);

        $this->assertSame(1, count($listed->items));
        $this->assertSame('cursor_2', $listed->nextCursor);
        $this->assertSame(1, $listed->total);

        $this->assertSame('room_1', $fetched->id);
        $this->assertSame('PUBLISHED', $fetched->status);
        $this->assertSame('新品直播-更新', $updated->title);

        $this->assertSame('room_1', $published->id);
        $this->assertSame('PUBLISHED', $published->status);
        $this->assertSame('STOPPED', $stopped->status);

        $this->assertSame('ticket_viewer_1', $viewerTicket->id);
        $this->assertSame('viewer', $viewerTicket->role);
        $this->assertSame(['room:view'], $viewerTicket->capabilities);

        $this->assertSame('ticket_operator_1', $operatorTicket->id);
        $this->assertSame('operator', $operatorTicket->role);
        $this->assertSame(['room:view', 'room:mute'], $operatorTicket->capabilities);

        $this->assertSame(8, count($transport->requests));

        $createRequest = $transport->requests[0];
        $this->assertSame('POST', $createRequest->method);
        $this->assertSame('/open/v1/rooms', $createRequest->path);
        $this->assertSame('idem-create', $createRequest->headers['Idempotency-Key'] ?? null);
        $this->assertSame([
            'starts_at' => '2026-07-27T20:00:00+08:00',
            'features' => ['chat' => true],
            'title' => '新品直播',
            'external_room_id' => 'course-2026-001',
        ], json_decode($createRequest->body, true));

        $listRequest = $transport->requests[1];
        $this->assertSame('GET', $listRequest->method);
        $this->assertSame(
            'https://open.example.com/open/v1/rooms?limit=20&status=PUBLISHED',
            $listRequest->url
        );
        $this->assertTrue(!isset($listRequest->headers['Idempotency-Key']));

        $getRequest = $transport->requests[2];
        $this->assertSame('GET', $getRequest->method);
        $this->assertSame('/open/v1/rooms/room_1', $getRequest->path);

        $updateRequest = $transport->requests[3];
        $this->assertSame('PATCH', $updateRequest->method);
        $this->assertSame('/open/v1/rooms/room_1', $updateRequest->path);
        $this->assertSame(['title' => '新品直播-更新'], json_decode($updateRequest->body, true));

        $publishRequest = $transport->requests[4];
        $this->assertSame('POST', $publishRequest->method);
        $this->assertSame('/open/v1/rooms/room_1/publish', $publishRequest->path);
        $this->assertSame('', $publishRequest->body);

        $stopRequest = $transport->requests[5];
        $this->assertSame('POST', $stopRequest->method);
        $this->assertSame('/open/v1/rooms/room_1/stop', $stopRequest->path);
        $this->assertSame('', $stopRequest->body);

        $viewerRequest = $transport->requests[6];
        $this->assertSame('POST', $viewerRequest->method);
        $this->assertSame('/open/v1/rooms/room_1/viewer-tickets', $viewerRequest->path);
        $this->assertSame([
            'external_user_id' => 'user-1',
            'origin' => 'https://customer.example',
            'ttl' => 60,
            'capabilities' => ['room:view'],
        ], json_decode($viewerRequest->body, true));

        $operatorRequest = $transport->requests[7];
        $this->assertSame('POST', $operatorRequest->method);
        $this->assertSame('/open/v1/rooms/room_1/operator-tickets', $operatorRequest->path);
        $this->assertSame([
            'external_user_id' => 'operator-1',
            'origin' => 'https://ops.example',
            'ttl' => 60,
            'capabilities' => ['room:view', 'room:mute'],
        ], json_decode($operatorRequest->body, true));
    }
}

final class CapturingTransport implements Transport
{
    public ?Request $lastRequest = null;

    public function __construct(
        private readonly Response $response,
    ) {
    }

    public function send(Request $request): Response
    {
        $this->lastRequest = $request;

        return $this->response;
    }
}

final class QueueingTransport implements Transport
{
    /**
     * @var list<Request>
     */
    public array $requests = [];

    /**
     * @param list<Response> $responses
     */
    public function __construct(
        private array $responses,
    ) {
    }

    public function send(Request $request): Response
    {
        $this->requests[] = $request;

        if ($this->responses === []) {
            throw new \RuntimeException('No queued response available for request: ' . $request->path);
        }

        return array_shift($this->responses);
    }
}
