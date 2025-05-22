<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Tests\Unit\Providers;

use GuzzleHttp\Psr7\Response;
use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\Enums\ModelType;
use Cortex\ModelInfo\Enums\ModelFeature;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Exceptions\ModelInfoException;
use Cortex\ModelInfo\Providers\XAIModelInfoProvider;

covers(XAIModelInfoProvider::class);

test('it can get the models', function (): void {
    $client = $this->mockHttpClient(
        new Response(body: json_encode([
            'models' => [
                [
                    'id' => 'grok-3-beta',
                    'fingerprint' => 'fp_fcf5abc12d',
                    'created' => 1743724800,
                    'object' => 'model',
                    'owned_by' => 'xai',
                    'version' => '1.0.0',
                    'input_modalities' => [
                        'text',
                    ],
                    'output_modalities' => [
                        'text',
                    ],
                    'prompt_text_token_price' => 20000,
                    'cached_prompt_text_token_price' => 0,
                    'prompt_image_token_price' => 0,
                    'completion_text_token_price' => 100000,
                    'aliases' => [
                        'grok-3',
                        'grok-3-latest',
                    ],
                ],
            ],
        ])),
    );

    $provider = new XAIModelInfoProvider(
        httpClient: $client,
    );

    $models = $provider->getModels(ModelProvider::XAI);

    expect($models)->toBeArray()
        ->toHaveCount(1)
        ->toContainOnlyInstancesOf(ModelInfo::class);

    $modelInfo = $models[0];

    expect($modelInfo->name)->toBe('grok-3-beta');
    expect($modelInfo->provider)->toBe(ModelProvider::XAI);
    expect($modelInfo->type)->toBe(ModelType::Chat);
    expect($modelInfo->maxInputTokens)->toBe(128000);
    expect($modelInfo->maxOutputTokens)->toBeNull();
    expect($modelInfo->inputCostPerToken)->toBe(0.0002);
    expect($modelInfo->outputCostPerToken)->toBe(0.001);
    expect($modelInfo->features)
        ->toContainOnlyInstancesOf(ModelFeature::class)
        ->toContain(ModelFeature::JsonOutput)
        ->toContain(ModelFeature::StructuredOutput)
        ->toContain(ModelFeature::ToolCalling)
        ->toContain(ModelFeature::ToolChoice);
});

test('it can get the model info', function (): void {
    $client = $this->mockHttpClient(
        new Response(body: json_encode([
            'id' => 'grok-2-vision-1212',
            'fingerprint' => 'fp_daba7546e5',
            'created' => 1733961600,
            'object' => 'model',
            'owned_by' => 'xai',
            'version' => '0.1.0',
            'input_modalities' => [
                'text',
                'image',
            ],
            'output_modalities' => [
                'text',
            ],
            'prompt_text_token_price' => 20000,
            'prompt_image_token_price' => 20000,
            'completion_text_token_price' => 100000,
            'aliases' => [],
        ])),
    );

    $provider = new XAIModelInfoProvider(
        httpClient: $client,
    );

    $modelInfo = $provider->getModelInfo(ModelProvider::XAI, 'grok-2-vision-1212');

    expect($modelInfo)->toBeInstanceOf(ModelInfo::class);
    expect($modelInfo->name)->toBe('grok-2-vision-1212');
    expect($modelInfo->provider)->toBe(ModelProvider::XAI);
    expect($modelInfo->type)->toBe(ModelType::Chat);
    expect($modelInfo->maxInputTokens)->toBe(32000);
    expect($modelInfo->maxOutputTokens)->toBeNull();
    expect($modelInfo->inputCostPerToken)->toBe(0.0002);
    expect($modelInfo->outputCostPerToken)->toBe(0.001);
    expect($modelInfo->features)
        ->toContainOnlyInstancesOf(ModelFeature::class)
        ->toContain(ModelFeature::JsonOutput)
        ->toContain(ModelFeature::StructuredOutput)
        ->toContain(ModelFeature::ToolCalling)
        ->toContain(ModelFeature::ToolChoice)
        ->toContain(ModelFeature::Vision);
});

it('throws an exception if the model is not found', function (): void {
    $client = $this->mockHttpClient(
        new Response(404),
    );

    $provider = new XAIModelInfoProvider(
        httpClient: $client,
    );

    expect(fn(): ModelInfo => $provider->getModelInfo(ModelProvider::XAI, 'grok-2-vision-1212'))
        ->toThrow(ModelInfoException::class, 'Failed to get model info');
});

it('throws an exception if the model provider is not supported', function (): void {
    $provider = new XAIModelInfoProvider();

    expect(fn(): ModelInfo => $provider->getModelInfo(ModelProvider::Ollama, 'mistral-small3.1'))
        ->toThrow(ModelInfoException::class, 'Model provider not supported');
});
