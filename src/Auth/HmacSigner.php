<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Auth;

final class HmacSigner
{
    /**
     * @param array<string, mixed> $query
     */
    public function sign(
        string $method,
        string $path,
        array $query,
        string $body,
        string $appKey,
        string $keyId,
        string $timestamp,
        string $nonce,
        string $appSecret,
    ): string {
        return hash_hmac(
            'sha256',
            $this->canonicalize($method, $path, $query, $body, $appKey, $keyId, $timestamp, $nonce),
            $appSecret
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    public function canonicalize(
        string $method,
        string $path,
        array $query,
        string $body,
        string $appKey,
        string $keyId,
        string $timestamp,
        string $nonce,
    ): string {
        return implode("\n", [
            strtoupper($method),
            $this->normalizePath($path),
            $this->normalizeQuery($query),
            $this->hashBody($body),
            $appKey,
            $keyId,
            $timestamp,
            $nonce,
        ]);
    }

    public function normalizePath(string $path): string
    {
        $pathOnly = $path;

        if (preg_match('#^[a-z][a-z0-9+\-.]*://#i', $path) === 1) {
            $pathOnly = (string) (parse_url($path, PHP_URL_PATH) ?: '/');
        } else {
            $pathOnly = preg_split('/[?#]/', $path, 2)[0] ?: '/';
        }

        $normalized = preg_replace('#/+#', '/', $pathOnly);

        if ($normalized === null || $normalized === '') {
            return '/';
        }

        return str_starts_with($normalized, '/') ? $normalized : '/' . $normalized;
    }

    /**
     * @param array<string, mixed> $query
     */
    public function normalizeQuery(array $query): string
    {
        if ($query === []) {
            return '';
        }

        $sorted = $this->sortRecursive($query);

        return http_build_query($sorted, '', '&', PHP_QUERY_RFC3986);
    }

    public function hashBody(string $body): string
    {
        return hash('sha256', $body);
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function sortRecursive(array $value): array
    {
        $isList = array_is_list($value);

        if (!$isList) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursive($item);
            }
        }

        return $value;
    }
}
