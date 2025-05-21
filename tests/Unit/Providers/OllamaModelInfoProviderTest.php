<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Tests\Unit\Providers;

use GuzzleHttp\Psr7\Response;
use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\Enums\ModelType;
use Cortex\ModelInfo\Enums\ModelFeature;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Exceptions\ModelInfoException;
use Cortex\ModelInfo\Providers\OllamaModelInfoProvider;

covers(OllamaModelInfoProvider::class);

test('it can get the models', function (): void {
    $client = $this->mockHttpClient(
        new Response(body: json_encode([
            'models' => [
                [
                    'name' => 'gemma3:12b',
                    'model' => 'gemma3:12b',
                    'modified_at' => '2025-03-19T08:07:16.962438556Z',
                    'size' => 8149190199,
                    'digest' => '6fd036cefda5093cc827b6c16be5e447f23857d4a472ce0bdba0720573d4dcd9',
                    'details' => [
                        'parent_model' => '',
                        'format' => 'gguf',
                        'family' => 'gemma3',
                        'families' => [
                            'gemma3',
                        ],
                        'parameter_size' => '12.2B',
                        'quantization_level' => 'Q4_K_M',
                    ],
                ],
            ],
        ])),
    );

    $provider = new OllamaModelInfoProvider(
        httpClient: $client,
    );

    $models = $provider->getModels(ModelProvider::Ollama);

    expect($models)->toBeArray()
        ->toHaveCount(1)
        ->toContainOnlyInstancesOf(ModelInfo::class);

    expect($models[0]->name)->toBe('gemma3:12b');
});

test('it can get the model info', function (): void {
    $client = $this->mockHttpClient(
        new Response(body: json_encode([
            'name' => 'mistral-small3.1',
            'model_info' => [
                'mock.context_length' => 1024 * 8,
            ],
            'capabilities' => [
                'completion',
                'tools',
            ],
        ])),
    );

    $provider = new OllamaModelInfoProvider(
        httpClient: $client,
    );

    $modelInfo = $provider->getModelInfo(ModelProvider::Ollama, 'mistral-small3.1');

    expect($modelInfo)->toBeInstanceOf(ModelInfo::class);
    expect($modelInfo->name)->toBe('mistral-small3.1');
    expect($modelInfo->provider)->toBe(ModelProvider::Ollama);
    expect($modelInfo->type)->toBe(ModelType::Chat);
    expect($modelInfo->maxInputTokens)->toBe(8000);
    expect($modelInfo->maxOutputTokens)->toBeNull();
    expect($modelInfo->inputCostPerToken)->toBe(0.0);
    expect($modelInfo->outputCostPerToken)->toBe(0.0);
    expect($modelInfo->features)
        ->toContainOnlyInstancesOf(ModelFeature::class)
        ->toContain(ModelFeature::JsonOutput)
        ->toContain(ModelFeature::StructuredOutput)
        ->toContain(ModelFeature::ToolCalling)
        ->toContain(ModelFeature::ToolChoice);
});

it('throws an exception if the model is not found', function (): void {
    $client = $this->mockHttpClient(
        new Response(404),
    );

    $provider = new OllamaModelInfoProvider(
        httpClient: $client,
    );

    expect(fn(): ModelInfo => $provider->getModelInfo(ModelProvider::Ollama, 'mistral-small3.1'))
        ->toThrow(ModelInfoException::class, 'Failed to get model info');
});

it('throws an exception if the model provider is not supported', function (): void {
    $provider = new OllamaModelInfoProvider();

    expect(fn(): ModelInfo => $provider->getModelInfo(ModelProvider::OpenAI, 'gpt-4o'))
        ->toThrow(ModelInfoException::class, 'Model provider not supported');
});
