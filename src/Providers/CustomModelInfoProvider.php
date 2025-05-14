<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Providers;

use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Contracts\ModelInfoProvider;
use Cortex\ModelInfo\Exceptions\ModelInfoException;
use Cortex\ModelInfo\Providers\Concerns\ChecksSupport;

class CustomModelInfoProvider implements ModelInfoProvider
{
    use ChecksSupport;

    /**
     * @param array<array-key, \Cortex\ModelInfo\Data\ModelInfo> $models
     */
    public function __construct(
        protected array $models,
    ) {}

    public function supportedModelProviders(): array
    {
        return ModelProvider::cases();
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return array<array-key, string>
     */
    public function getModels(ModelProvider $modelProvider): array
    {
        return array_map(
            fn(ModelInfo $model): string => $model->name,
            $this->models,
        );
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
