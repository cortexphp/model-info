<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Tests\Unit\Providers;

use GuzzleHttp\Psr7\Response;
use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\Enums\ModelType;
use Cortex\ModelInfo\Enums\ModelFeature;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Exceptions\ModelInfoException;
use Cortex\ModelInfo\Providers\LMStudioModelInfoProvider;

covers(LMStudioModelInfoProvider::class);

test('it can get the models', function (): void {
    $client = $this->mockHttpClient(
        new Response(body: json_encode([
            'data' => [
                [
                    'id' => 'text-embedding-nomic-embed-text-v1.5',
                    'object' => 'model',
                    'type' => 'embeddings',
                    'publisher' => 'nomic-ai',
                    'arch' => 'nomic-bert',
                    'compatibility_type' => 'gguf',
                    'quantization' => 'Q4_K_M',
                    'state' => 'not-loaded',
                    'max_context_length' => 2048,
                ],
                [
                    'id' => 'qwen2.5-14b-instruct-mlx',
                    'object' => 'model',
                    'type' => 'llm',
                    'publisher' => 'lmstudio-community',
                    'arch' => 'qwen2',
                    'compatibility_type' => 'mlx',
                    'quantization' => '4bit',
                    'state' => 'not-loaded',
                    'max_context_length' => 32768,
                ],
            ],
            'object' => 'list',
        ])),
    );

    $provider = new LMStudioModelInfoProvider(
        httpClient: $client,
    );

    $models = $provider->getModels(ModelProvider::LMStudio);

    expect($models)->toBeArray()
        ->toHaveCount(2)
        ->toContainOnlyInstancesOf(ModelInfo::class);

    expect($models[0]->name)->toBe('text-embedding-nomic-embed-text-v1.5');
    expect($models[1]->name)->toBe('qwen2.5-14b-instruct-mlx');
});

test('it can get the model info', function (): void {
    $client = $this->mockHttpClient(
        new Response(body: json_encode([
            'id' => 'qwen2.5-14b-instruct-mlx',
            'object' => 'model',
            'type' => 'llm',
            'publisher' => 'lmstudio-community',
            'arch' => 'qwen2',
            'compatibility_type' => 'mlx',
            'quantization' => '4bit',
            'state' => 'not-loaded',
            'max_context_length' => 32768,
        ])),
    );

    $provider = new LMStudioModelInfoProvider(
        httpClient: $client,
    );

    $modelInfo = $provider->getModelInfo(ModelProvider::LMStudio, 'qwen2.5-14b-instruct-mlx');

    expect($modelInfo)->toBeInstanceOf(ModelInfo::class);
    expect($modelInfo->name)->toBe('qwen2.5-14b-instruct-mlx');
    expect($modelInfo->provider)->toBe(ModelProvider::LMStudio);
    expect($modelInfo->type)->toBe(ModelType::Chat);
    expect($modelInfo->maxInputTokens)->toBe(32000);
    expect($modelInfo->maxOutputTokens)->toBeNull();
    expect($modelInfo->inputCostPerToken)->toBe(0.0);
    expect($modelInfo->outputCostPerToken)->toBe(0.0);
    expect($modelInfo->features)
        ->toContainOnlyInstancesOf(ModelFeature::class)
        ->toContain(ModelFeature::JsonOutput)
        ->toContain(ModelFeature::StructuredOutput);
    // ->toContain(ModelFeature::ToolCalling)
    // ->toContain(ModelFeature::ToolChoice);
});

it('throws an exception if the model is not found', function (): void {
    $client = $this->mockHttpClient(
        new Response(404),
    );

    $provider = new LMStudioModelInfoProvider(
        httpClient: $client,
    );

    expect(fn(): ModelInfo => $provider->getModelInfo(ModelProvider::LMStudio, 'qwen2.5-14b-instruct-mlx'))
        ->toThrow(ModelInfoException::class, 'Failed to get model info');
});

it('throws an exception if the model provider is not supported', function (): void {
    $provider = new LMStudioModelInfoProvider();

    expect(fn(): ModelInfo => $provider->getModelInfo(ModelProvider::OpenAI, 'gpt-4o'))
        ->toThrow(ModelInfoException::class, 'Model provider not supported');
});
