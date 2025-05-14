<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Providers\Concerns;

use PsrDiscovery\Discover;
use Psr\SimpleCache\CacheInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Cortex\ModelInfo\Exceptions\ModelInfoException;

trait DiscoversPsrImplementations
{
    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    protected static function discoverHttpClientOrFail(bool $singleton = true): ClientInterface
    {
        $client = Discover::httpClient($singleton);

        if (! $client instanceof ClientInterface) {
            throw new ModelInfoException('HTTP client not found');
        }

        return $client;
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    protected static function discoverHttpRequestFactoryOrFail(bool $singleton = true): RequestFactoryInterface
    {
        $requestFactory = Discover::httpRequestFactory($singleton);

        if (! $requestFactory instanceof RequestFactoryInterface) {
            throw new ModelInfoException('HTTP request factory not found');
        }

        return $requestFactory;
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    protected static function discoverHttpUriFactoryOrFail(bool $singleton = true): UriFactoryInterface
    {
        $uriFactory = Discover::httpUriFactory($singleton);

        if (! $uriFactory instanceof UriFactoryInterface) {
            throw new ModelInfoException('HTTP URI factory not found');
        }

        return $uriFactory;
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    protected static function discoverHttpStreamFactoryOrFail(bool $singleton = true): StreamFactoryInterface
    {
        $streamFactory = Discover::httpStreamFactory($singleton);

        if (! $streamFactory instanceof StreamFactoryInterface) {
            throw new ModelInfoException('HTTP stream factory not found');
        }

        return $streamFactory;
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    protected static function discoverCacheOrFail(bool $singleton = true): CacheInterface
    {
        $cache = Discover::cache($singleton);

        if (! $cache instanceof CacheInterface) {
            throw new ModelInfoException('Cache not found');
        }

        return $cache;
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    protected static function discoverContainerOrFail(bool $singleton = true): ContainerInterface
    {
        $container = Discover::container($singleton);

        if (! $container instanceof ContainerInterface) {
            throw new ModelInfoException('Container not found');
        }

        return $container;
    }

    protected static function discoverHttpClient(bool $singleton = true): ?ClientInterface
    {
        try {
            return self::discoverHttpClientOrFail($singleton);
        } catch (ModelInfoException) {
            return null;
        }
    }

    protected static function discoverHttpRequestFactory(bool $singleton = true): ?RequestFactoryInterface
    {
        try {
            return self::discoverHttpRequestFactoryOrFail($singleton);
        } catch (ModelInfoException) {
            return null;
        }
    }

    protected static function discoverHttpUriFactory(bool $singleton = true): ?UriFactoryInterface
    {
        try {
            return self::discoverHttpUriFactoryOrFail($singleton);
        } catch (ModelInfoException) {
            return null;
        }
    }

    protected static function discoverHttpStreamFactory(bool $singleton = true): ?StreamFactoryInterface
    {
        try {
            return self::discoverHttpStreamFactoryOrFail($singleton);
        } catch (ModelInfoException) {
            return null;
        }
    }

    protected static function discoverCache(bool $singleton = true): ?CacheInterface
    {
        try {
            return self::discoverCacheOrFail($singleton);
        } catch (ModelInfoException) {
            return null;
        }
    }

    protected static function discoverContainer(bool $singleton = true): ?ContainerInterface
    {
        try {
            return self::discoverContainerOrFail($singleton);
        } catch (ModelInfoException) {
            return null;
        }
    }
}
