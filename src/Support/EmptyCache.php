<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Support;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class EmptyCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return true;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return array_fill_keys(
            array_map(fn(string $key): string => $key, (array) $keys),
            $default,
        );
    }

    /**
     * @phpstan-ignore missingType.iterableValue
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return true;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }
}
