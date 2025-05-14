<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Contracts;

use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\Enums\ModelProvider;

interface ModelInfoProvider
{
    /**
     * Get the available models for a given provider.
     *
     * @return array<array-key, string>
     */
    public function getModels(ModelProvider $modelProvider): array;

    /**
     * Get the model info for a given model.
     */
    public function getModelInfo(ModelProvider $modelProvider, string $model): ModelInfo;

    /**
     * Get the model providers that are supported.
     *
     * @return array<array-key, \Cortex\ModelInfo\Enums\ModelProvider>
     */
    public function supportedModelProviders(): array;

    /**
     * Determine if the given model provider is supported.
     */
    public function supports(ModelProvider $modelProvider): bool;

    /**
     * Determine if the given model provider is supported and throw an exception if it is not.
     *
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    public function checkSupportOrFail(ModelProvider $modelProvider): void;
}
