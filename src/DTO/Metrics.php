<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\DTO;

final class Metrics extends AbstractDto
{
    /**
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $series
     */
    public function __construct(
        string $requestId,
        array $raw,
        public readonly ?int $onlineCount,
        public readonly ?int $viewerCount,
        public readonly ?int $watchDurationSeconds,
        public readonly ?int $averageWatchDurationSeconds,
        public readonly ?int $commentCount,
        public readonly ?int $likeCount,
        public readonly array $series,
    ) {
        parent::__construct($requestId, $raw);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $requestId): self
    {
        return new self(
            $requestId,
            $data,
            self::intValue($data, 'online_count'),
            self::intValue($data, 'viewer_count'),
            self::intValue($data, 'watch_duration_seconds'),
            self::intValue($data, 'average_watch_duration_seconds'),
            self::intValue($data, 'comment_count'),
            self::intValue($data, 'like_count'),
            self::arrayValue($data, 'series')
        );
    }
}
