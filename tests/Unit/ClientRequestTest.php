<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Tests\Unit;

use Company\LiveOpenSdk\Http\Request;
use Company\LiveOpenSdk\Http\Response;
use Company\LiveOpenSdk\Http\Transport;
use Company\LiveOpenSdk\LiveOpenClient;
use Company\LiveOpenSdk\Tests\TestCase;

final class ClientRequestTest extends TestCase
{
    public function testUserUpsertUsesOnlyApplicationCredentialsAndReturnsOriginalToken(): void
    {
        $transport = new QueueingTransport([
            new Response(200, [], '{"status":200,"request_id":"req_user","data":{"access_token":"h5-token","api_base_url":"https://merchant.example/api/9/1","websocket_url":"wss://merchant.example"},"error":null}'),
        ]);
        $client = new LiveOpenClient('app_key_123', 'app_secret_123', 'https://open.example.com', $transport);

        $profile = [
            'nickname' => 'Ada',
            'avatar' => 'https://cdn.example.com/ada.png',
            'realname' => 'Ada Lovelace',
            'mobile' => '13800138000',
            'gender' => 2,
            'openid' => 'official-openid',
            'unionid' => 'wechat-unionid',
            'wxapp_openid' => 'mini-program-openid',
        ];
        $user = $client->users()->upsert('member/a', $profile);

        $this->assertSame('h5-token', $user['access_token']);
        $this->assertSame('/api/open/v1/users/member%2Fa', $transport->requests[0]->path);
        $requestBody = json_decode($transport->requests[0]->body, true);
        $this->assertSame('app_key_123', $requestBody['app_key']);
        $this->assertSame('app_secret_123', $requestBody['app_secret']);
        $this->assertSame($profile, $requestBody['profile']);
    }

    public function testAdminTokenThenDirectAdminRequestKeepsOriginalEnvelope(): void
    {
        $transport = new QueueingTransport([
            new Response(200, [], '{"status":200,"request_id":"req_admin","data":{"access_token":"admin-token","api_base_url":"https://merchant.example/api/live/9"},"error":null}'),
            new Response(200, [], '{"status":200,"data":{"data":[{"id":7,"title":"直播间"}]},"message":"成功"}'),
        ]);
        $client = new LiveOpenClient('app_key_123', 'app_secret_123', 'https://open.example.com', $transport);

        $credential = $client->adminToken();
        $this->assertSame('/api/open/v1/admin-token', $transport->requests[0]->path);
        $this->assertSame([
            'app_key' => 'app_key_123',
            'app_secret' => 'app_secret_123',
        ], json_decode($transport->requests[0]->body, true));
        $response = $client->adminRequest(
            $credential['api_base_url'],
            $credential['access_token'],
            'GET',
            '/live',
            ['query' => ['limit' => 20]]
        );

        $this->assertSame('admin-token', $credential['access_token']);
        $this->assertSame('https://merchant.example/api/live/9/live?limit=20', $transport->requests[1]->url);
        $this->assertSame('admin-token', $transport->requests[1]->headers['Authori-zation']);
        $this->assertSame('', $transport->requests[1]->body);
        $this->assertSame(200, $response->decoded['status']);
        $this->assertSame('直播间', $response->decoded['data']['data'][0]['title']);
    }
}

final class QueueingTransport implements Transport
{
    public array $requests = [];

    public array $responses;

    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function send(Request $request): Response
    {
        $this->requests[] = $request;

        return array_shift($this->responses);
    }
}
