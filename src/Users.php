<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk;

final class Users
{
    public LiveOpenClient $client;

    public function __construct(LiveOpenClient $client)
    {
        $this->client = $client;
    }

    /**
     * 同步外部用户并返回原 H5 用户令牌。
     *
     * 省市等字段没有资料时可以省略；传空字符串表示主动清空平台已有资料。
     *
     * @param array{
     *     nickname?: string,
     *     avatar?: string,
     *     realname?: string,
     *     mobile?: string,
     *     gender?: 0|1|2,
     *     country?: string,
     *     province?: string,
     *     city?: string,
     *     openid?: string,
     *     unionid?: string,
     *     wxapp_openid?: string,
     *     app_openid?: string,
     *     status?: 'active'|'inactive',
     *     metadata?: array<string, mixed>,
     *     source_updated_at?: string
     * } $profile
     * @return array<string, mixed>
     */
    public function upsert(string $externalUserId, array $profile): array
    {
        return $this->client->request('PUT', '/open/v1/users/' . rawurlencode($externalUserId), [
            'json' => ['profile' => $profile],
        ])->data;
    }

    /**
     * 批量同步外部用户并返回每个用户的原 H5 用户令牌。
     *
     * @param list<array<string, mixed>> $users
     * @return array<string, mixed>
     */
    public function batchUpsert(array $users): array
    {
        return $this->client->request('POST', '/open/v1/users/batch-upsert', [
            'json' => ['users' => $users],
        ])->data;
    }
}
