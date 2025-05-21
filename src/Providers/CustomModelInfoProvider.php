<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Providers;

use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Contracts\ModelInfoProvider;
use Cortex\ModelInfo\Exceptions\ModelInfoException;
use Cortex\ModelInfo\Providers\Concerns\ChecksSupport;

/**
 * @phpstan-import-type ModelInfoData from \Cortex\ModelInfo\Data\ModelInfo
 */
class CustomModelInfoProvider implements ModelInfoProvider
{
    use ChecksSupport;

    /**
     * @var array<array-key, \Cortex\ModelInfo\Data\ModelInfo>
     */
    protected array $models;

    /**
     * @param array<array-key, \Cortex\ModelInfo\Data\ModelInfo|ModelInfoData> $models
     */
    public function __construct(array $models)
    {
        $this->models = array_map(
            fn(ModelInfo|array $model): ModelInfo => $model instanceof ModelInfo
                ? $model
                : ModelInfo::createFromArray($model),
            $models,
        );
    }

    public function supportedModelProviders(): array
    {
        return ModelProvider::cases();
    }

    public function getModels(ModelProvider $modelProvider): array
    {
        return $this->models;
    }

    public function getModelInfo(ModelProvider $modelProvider, string $model): ModelInfo
    {
        $models = array_values(array_filter(
            $this->models,
            fn(ModelInfo $modelInfo): bool => $modelInfo->provider === $modelProvider,
        ));

        $modelInfo = array_values(array_filter(
            $models,
            fn(ModelInfo $modelInfo): bool => $modelInfo->name === $model,
        ))[0] ?? null;

        if ($modelInfo === null) {
            throw new ModelInfoException('Model not found');
        }

        return $modelInfo;
    }
}
