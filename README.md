# Live Open SDK for PHP

`company/live-open-sdk-php` 是直播开放平台的服务端 SDK。它只负责两件事：

1. 使用 `AppKey/AppSecret` 同步第三方用户，并换取原 H5 `access_token`。
2. 换取商户管理员 token，供第三方服务端调用原商户后台接口。

SDK 不创建临时 ticket，不保存直播间、会话或消息副本，也不把 `AppSecret` 交给浏览器。
调用 `/api/open/v1/*` 时，SDK 会自动把 `app_key`、`app_secret` 合并到 JSON 请求参数中。

```text
第三方服务端 -- AppKey/AppSecret --> 直播开放平台
  ├─ users()->upsert()  --> H5 access_token --> 浏览器使用 @company/live-room-sdk
  └─ adminToken()       --> 后台 access_token --> 服务端调用原商户后台 API
```

## 环境要求

- PHP `>= 7.4`
- `ext-curl`
- 可访问直播平台服务根地址，例如 `https://live.example.com`

## 安装

```bash
composer require company/live-open-sdk-php
```

如果使用私有 Composer 仓库，先按仓库说明配置 repository，再执行上述命令。

## 创建客户端

```php
<?php

use Company\LiveOpenSdk\LiveOpenClient;

$client = new LiveOpenClient(
    getenv('LIVE_APP_KEY'),
    getenv('LIVE_APP_SECRET'),
    getenv('LIVE_OPEN_BASE_URL')
);
```

构造参数：

| 参数 | 必填 | 说明 |
| --- | --- | --- |
| `appKey` | 是 | 开放平台应用 Key |
| `appSecret` | 是 | 开放平台应用 Secret，只能保存在服务端 |
| `baseUrl` | 是 | 直播平台服务根地址，不包含 `/api/open/v1` |
| `transport` | 否 | 自定义 HTTP Transport；默认使用 cURL |

本地联调时传实际 HTTP 地址，例如 `http://127.0.0.1:8785`。生产环境使用 HTTPS。

## 方法速查

| 方法 | 用途 | 返回值 |
| --- | --- | --- |
| `users()` | 获取用户同步入口 | `UsersClient` |
| `users()->upsert($externalUserId, $profile)` | 同步一个用户并取得 H5 凭据 | `array` |
| `users()->batchUpsert($users)` | 批量同步 1 到 100 个用户 | `array` |
| `adminToken()` | 换取当前应用绑定商户的管理员 token | `array` |
| `request($method, $path, $options)` | 调用开放平台接口 | `ApiResponse` |
| `adminRequest($baseUrl, $token, $method, $path, $options)` | 调用原商户后台接口 | `ApiResponse` |

`request()` 与 `adminRequest()` 的 `options` 支持 `query`、`json` 和 `headers`。`ApiResponse` 提供 `data`、`decoded`、`requestId`、`statusCode` 和 `headers`；命名方法已经返回 `data`，通常无需再拆响应包。

## 同步单个用户

第三方用户进入直播间前，服务端用自己的稳定用户 ID 同步一次：

```php
$credential = $client->users()->upsert('customer-member-42', [
    'nickname' => 'Ada',
    'avatar' => 'https://cdn.example.com/ada.png',
    'realname' => 'Ada Lovelace',
    'mobile' => '13800138000',
    'gender' => 2,
    'country' => '中国',
    'province' => '浙江省',
    'city' => '杭州市',
    'openid' => 'official-account-openid',
    'unionid' => 'wechat-unionid',
    'wxapp_openid' => 'mini-program-openid',
    'app_openid' => 'app-openid',
    'status' => 'active',
    'metadata' => [
        'member_level' => 'vip',
    ],
    'source_updated_at' => '2026-08-02T12:00:00+08:00',
]);
```

同一个应用、同一个商户、同一个 `external_user_id` 会更新同一位直播平台会员。`source_updated_at` 早于平台已保存时间时，平台不会用旧资料覆盖新资料，但仍会返回可用凭据。

标准资料字段包括 `nickname`、`avatar`、`realname`、`mobile`、`gender`、`country`、`province`、`city`、`openid`、`unionid`、`wxapp_openid` 和 `app_openid`。字段不存在时应省略；传空字符串表示主动清空已同步值。目标会员表没有对应列的第三方资料放入 `metadata`。

成功结果示例：

```php
[
    'id' => 'usr_xxx',
    'external_user_id' => 'customer-member-42',
    'nickname' => 'Ada',
    'avatar' => 'https://cdn.example.com/ada.png',
    'realname' => 'Ada Lovelace',
    'mobile' => '13800138000',
    'gender' => 2,
    'country' => '中国',
    'province' => '浙江省',
    'city' => '杭州市',
    'openid' => 'official-account-openid',
    'unionid' => 'wechat-unionid',
    'wxapp_openid' => 'mini-program-openid',
    'app_openid' => 'app-openid',
    'status' => 'active',
    'source_updated_at' => '2026-08-02T12:00:00+08:00',
    'metadata' => ['member_level' => 'vip'],
    'access_token' => '原 H5 token',
    'expires_time' => 1780000000,
    'uniacid' => 9,
    'type' => 1,
    'api_base_url' => 'https://merchant.example.com/api/9/1',
    'websocket_url' => 'wss://merchant.example.com/ws1/',
]
```

只把以下字段交给当前登录用户的浏览器：

```php
return json_encode([
    'access_token' => $credential['access_token'],
    'expires_time' => $credential['expires_time'],
    'api_base_url' => $credential['api_base_url'],
    'websocket_url' => $credential['websocket_url'],
    'uniacid' => $credential['uniacid'],
    'type' => $credential['type'],
]);
```

不要把 `AppKey`、`AppSecret` 或管理员 token 返回给浏览器。

## 批量同步用户

批量同步适合迁移或后台预同步，单次最多 100 个用户：

```php
$result = $client->users()->batchUpsert([
    [
        'external_user_id' => 'customer-member-42',
        'nickname' => 'Ada',
        'status' => 'active',
    ],
    [
        'external_user_id' => 'customer-member-43',
        'nickname' => 'Grace',
        'status' => 'active',
    ],
]);

$succeeded = $result['users'];
$failed = $result['failures'];
```

每个用户独立同步，因此响应可能同时包含成功项和失败项。实时进入直播间时优先调用 `upsert()`，避免把一批用户的 token 暴露给错误的会话。

## 换取管理员 token

管理员 token 只允许在第三方服务端使用：

```php
$admin = $client->adminToken();

$rooms = $client->adminRequest(
    $admin['api_base_url'],
    $admin['access_token'],
    'GET',
    '/live',
    [
        'query' => [
            'page' => 1,
            'limit' => 20,
        ],
    ]
);

$originalEnvelope = $rooms->decoded;
$originalData = $rooms->data;
```

`adminRequest()` 不转换原后台接口的字段、分页或业务状态。它按原后台约定发送：

```http
Authori-zation: {admin_access_token}
```

注意管理员接口的 token 不加 `Bearer`；H5 用户接口由浏览器 SDK 加 `Bearer`。

发送 JSON：

```php
$response = $client->adminRequest(
    $admin['api_base_url'],
    $admin['access_token'],
    'POST',
    '/live',
    ['json' => $roomPayload]
);
```

## 直接调用开放平台接口

`request()` 用于调用当前 SDK 暂未提供命名方法的 `/api/open/v1` 接口：

```php
$response = $client->request('POST', '/api/open/v1/admin-token');

echo $response->requestId;
echo $response->statusCode;
var_dump($response->data);
var_dump($response->decoded);
```

`data` 是响应中的 `data` 字段；`decoded` 是完整 JSON 响应。

## 超时配置

默认连接超时 10 秒、请求超时 30 秒。需要调整时复用内置 Transport：

```php
use Company\LiveOpenSdk\Http\CurlTransport;
use Company\LiveOpenSdk\LiveOpenClient;

$client = new LiveOpenClient(
    getenv('LIVE_APP_KEY'),
    getenv('LIVE_APP_SECRET'),
    getenv('LIVE_OPEN_BASE_URL'),
    new CurlTransport(5, 15)
);
```

## 异常处理

```php
use Company\LiveOpenSdk\Exceptions\ApiException;
use Company\LiveOpenSdk\Exceptions\SerializationException;
use Company\LiveOpenSdk\Exceptions\TransportException;

try {
    $credential = $client->users()->upsert('customer-member-42', [
        'nickname' => 'Ada',
    ]);
} catch (ApiException $exception) {
    logger()->warning('Live Open API rejected the request', [
        'http_status' => $exception->getStatusCode(),
        'business_code' => $exception->getBusinessCode(),
        'error_code' => $exception->getErrorCode(),
        'request_id' => $exception->getRequestId(),
        'message' => $exception->getMessage(),
    ]);
} catch (TransportException | SerializationException $exception) {
    logger()->error('Live Open transport failed', [
        'message' => $exception->getMessage(),
    ]);
}
```

异常类型：

| 类型 | 含义 |
| --- | --- |
| `ApiException` | 平台返回 HTTP 错误或开放平台 `error` 对象 |
| `TransportException` | DNS、连接、超时或 cURL 错误 |
| `SerializationException` | 请求无法编码或响应不是有效 JSON 对象 |

开放平台错误同时提供数字业务码、字符串错误码和 `request_id`。排查服务端日志时优先记录 `request_id`，不要记录凭据和 token。

## 安全要求

- `AppSecret` 只能保存在第三方服务端环境变量或密钥系统中。
- 浏览器只能接收与当前登录用户绑定的 H5 token。
- 管理员 token 不得进入 H5、UniApp、小程序包或浏览器日志。
- 不要记录请求参数中的 `app_secret` 或请求头中的 `Authori-zation`。
- 用户状态设为 `inactive` 后，不应继续向该用户发放浏览器凭据。
- 域名变更后重新同步用户，以取得新的 `api_base_url` 和 `websocket_url`。

## API 文档

- 开放平台接入说明：`webman_live/docs/open-platform-api.md`
- 第三方 H5 直播间实现顺序：`webman_live/docs/third-party-h5-live-room-integration.md`
- Apifox 开放平台凭据接口：`webman_live/docs/apifox/live-open-platform.openapi.json`
- Apifox H5 直播间与用户接口：`webman_live/docs/apifox/h5-live-user.openapi.json`

## 测试

```bash
composer test
```

或直接执行：

```bash
php tests/run.php
```
