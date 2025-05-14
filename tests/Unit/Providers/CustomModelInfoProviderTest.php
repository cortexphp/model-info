<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Tests\Unit\Providers;

use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\Enums\ModelType;
use Cortex\ModelInfo\Enums\ModelFeature;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Exceptions\ModelInfoException;
use Cortex\ModelInfo\Providers\CustomModelInfoProvider;

covers(CustomModelInfoProvider::class);

test('it can get the models', function (): void {
    $provider = new CustomModelInfoProvider([
        new ModelInfo(
            name: 'custom-model',
            provider: ModelProvider::Custom,
            type: ModelType::Chat,
            maxInputTokens: 256000,
            maxOutputTokens: 256000,
            inputCostPerToken: 0.0000025,
            outputCostPerToken: 0.000010,
            features: [
                ModelFeature::JsonOutput,
                ModelFeature::StructuredOutput,
                ModelFeature::ToolCalling,
                ModelFeature::ToolChoice,
            ],
        ),
        new ModelInfo(
            name: 'gpt-4o',
            provider: ModelProvider::OpenAI,
            type: ModelType::Chat,
            maxInputTokens: 128000,
            maxOutputTokens: 16384,
            inputCostPerToken: 0.0,
            outputCostPerToken: 0.0,
            features: [
                ModelFeature::JsonOutput,
                ModelFeature::StructuredOutput,
                ModelFeature::ToolCalling,
                ModelFeature::ToolChoice,
                ModelFeature::WebSearch,
                ModelFeature::Vision,
                ModelFeature::PromptCaching,
            ],
        ),
    ]);

    $models = $provider->getModels(ModelProvider::Custom);

    expect($models)->toBeArray()->toHaveCount(2)->toContain('custom-model', 'gpt-4o');
});

test('it can get the model info', function (): void {
    $provider = new CustomModelInfoProvider([
        new ModelInfo(
            name: 'custom-model',
            provider: ModelProvider::Custom,
            type: ModelType::Chat,
            maxInputTokens: 128000,
            maxOutputTokens: 16384,
            inputCostPerToken: 0.0000025,
            outputCostPerToken: 0.000010,
            features: [
                ModelFeature::JsonOutput,
                ModelFeature::StructuredOutput,
                ModelFeature::ToolCalling,
                ModelFeature::ToolChoice,
            ],
        ),
    ]);

    $modelInfo = $provider->getModelInfo(ModelProvider::Custom, 'custom-model');

    expect($modelInfo)->toBeInstanceOf(ModelInfo::class);
    expect($modelInfo->name)->toBe('custom-model');
    expect($modelInfo->provider)->toBe(ModelProvider::Custom);
    expect($modelInfo->type)->toBe(ModelType::Chat);
    expect($modelInfo->maxInputTokens)->toBe(128000);
    expect($modelInfo->maxOutputTokens)->toBe(16384);
    expect($modelInfo->inputCostPerToken)->toBe(0.0000025);
    expect($modelInfo->outputCostPerToken)->toBe(0.000010);
    expect($modelInfo->features)
        ->toContainOnlyInstancesOf(ModelFeature::class)
        ->toContain(ModelFeature::JsonOutput)
        ->toContain(ModelFeature::StructuredOutput)
        ->toContain(ModelFeature::ToolCalling)
        ->toContain(ModelFeature::ToolChoice);
});

it('throws an exception if the model is not found', function (): void {
    $provider = new CustomModelInfoProvider([
        new ModelInfo(
            name: 'custom-model',
            provider: ModelProvider::Custom,
            type: ModelType::Chat,
            maxInputTokens: 128000,
            maxOutputTokens: 16384,
            inputCostPerToken: 0.0,
            outputCostPerToken: 0.0,
            features: [],
        ),
    ]);

    expect(fn(): ModelInfo => $provider->getModelInfo(ModelProvider::Custom, 'foo'))
        ->toThrow(ModelInfoException::class, 'Model not found');
});
