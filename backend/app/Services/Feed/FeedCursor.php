<?php

namespace App\Services\Feed;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Opaque cursor for ranked feeds. It pins the ranking time (`asOf`) so pages stay consistent
 * while new content is published, plus the offset into the ranked list.
 */
final readonly class FeedCursor
{
    public function __construct(public CarbonImmutable $asOf, public int $offset = 0) {}

    public static function start(): self
    {
        return new self(CarbonImmutable::now()->startOfSecond());
    }

    /**
     * Invalid or tampered cursors restart the feed instead of failing.
     */
    public static function decode(?string $cursor): self
    {
        if ($cursor === null || $cursor === '') {
            return self::start();
        }

        try {
            $payload = json_decode((string) base64_decode(strtr($cursor, '-_', '+/'), true), true, flags: JSON_THROW_ON_ERROR);
            $asOf = CarbonImmutable::createFromTimestampUTC((int) $payload['a']);

            if ($asOf->isFuture() || (int) $payload['o'] < 0) {
                return self::start();
            }

            return new self($asOf, (int) $payload['o']);
        } catch (Throwable) {
            return self::start();
        }
    }

    public function next(int $count): self
    {
        return new self($this->asOf, $this->offset + $count);
    }

    public function encode(): string
    {
        return rtrim(strtr(base64_encode((string) json_encode(['a' => $this->asOf->getTimestamp(), 'o' => $this->offset])), '+/', '-_'), '=');
    }
}
