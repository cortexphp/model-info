<?php

declare(strict_types=1);

namespace Cortex\ModelInfo;

use Psr\SimpleCache\CacheInterface;
use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\Support\EmptyCache;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Exceptions\ModelInfoException;
use Cortex\ModelInfo\Providers\OllamaModelInfoProvider;
use Cortex\ModelInfo\Providers\LiteLLMModelInfoProvider;
use Cortex\ModelInfo\Providers\Concerns\DiscoversPsrImplementations;

class ModelInfoFactory
{
    use DiscoversPsrImplementations;

    /**
     * @var array<array-key, \Cortex\ModelInfo\Contracts\ModelInfoProvider>
     */
    protected array $modelInfoProviders;

    protected CacheInterface $cache;

    /**
     * @param array<array-key, \Cortex\ModelInfo\Contracts\ModelInfoProvider>|null $modelInfoProviders
     */
    public function __construct(
        ?array $modelInfoProviders = null,
        ?CacheInterface $cache = null,
    ) {
        $this->modelInfoProviders = $modelInfoProviders ?? self::defaultModelInfoProviders();
        $this->cache = $cache ?? self::discoverCache() ?? new EmptyCache();
    }

    /**
     * @return array<array-key, string>
     */
    public function getModels(ModelProvider $modelProvider): array
    {
        try {
            return $this->getModelsOrFail($modelProvider);
        } catch (ModelInfoException) {
            return [];
        }
    }

    public function getModelInfo(ModelProvider $modelProvider, string $model): ?ModelInfo
    {
        try {
            return $this->getModelInfoOrFail($modelProvider, $model);
        } catch (ModelInfoException) {
            return null;
        }
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return array<array-key, string>
     */
    public function getModelsOrFail(ModelProvider $modelProvider): array
    {
        $cacheKey = $this->cacheKey(sprintf('models.%s', $modelProvider->value));

        if ($this->cache->has($cacheKey)) {
            // @phpstan-ignore return.type
            return $this->cache->get($cacheKey);
        }

        foreach ($this->modelInfoProviders as $modelInfoProvider) {
            if ($modelInfoProvider->supports($modelProvider)) {
                $models = $modelInfoProvider->getModels($modelProvider);

                $this->cache->set($cacheKey, $models);

                return $models;
            }
        }

        throw new ModelInfoException('Model provider not supported');
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    public function getModelInfoOrFail(ModelProvider $modelProvider, string $model): ModelInfo
    {
        $cacheKey = $this->cacheKey(sprintf('model-info.%s.%s', $modelProvider->value, $model));

        if ($this->cache->has($cacheKey)) {
            // @phpstan-ignore return.type
            return $this->cache->get($cacheKey);
        }

        foreach ($this->modelInfoProviders as $modelInfoProvider) {
            if ($modelInfoProvider->supports($modelProvider)) {
                $modelInfo = $modelInfoProvider->getModelInfo($modelProvider, $model);

                $this->cache->set($cacheKey, $modelInfo);

                return $modelInfo;
            }
        }

        throw new ModelInfoException('Model provider not supported');
    }

    public function flushCache(): void
    {
        $this->cache->deleteMultiple([
            $this->cacheKey('models.*'),
            $this->cacheKey('model-info.*.*'),
        ]);
    }

    /**
     * @return array<array-key, \Cortex\ModelInfo\Contracts\ModelInfoProvider>
     */
    protected static function defaultModelInfoProviders(): array
    {
        return [
            new OllamaModelInfoProvider(),
            new LiteLLMModelInfoProvider(),
        ];
    }

    protected function cacheKey(string $key): string
    {
        return 'cortex.' . $key;
    }
}
