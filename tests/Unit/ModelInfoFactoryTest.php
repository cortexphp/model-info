<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\Enums\ModelType;
use Cortex\ModelInfo\ModelInfoFactory;
use Cortex\ModelInfo\Enums\ModelFeature;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Providers\CustomModelInfoProvider;
use Cortex\ModelInfo\Providers\OllamaModelInfoProvider;

covers(ModelInfoFactory::class);

test('it can get the available models', function (): void {
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
        new Response(body: json_encode([
            'model_info' => [
                'mock.context_length' => 1024 * 8,
            ],
            'capabilities' => [
                'completion',
                'tools',
            ],
        ])),
    );

    $factory = new ModelInfoFactory([
        new OllamaModelInfoProvider(httpClient: $client),
        new CustomModelInfoProvider([
            [
                'name' => 'foobar',
                'provider' => ModelProvider::Custom,
                'type' => ModelType::Chat,
                'max_input_tokens' => 8000,
                'max_output_tokens' => 16000,
                'input_cost_per_token' => 0.00123,
                'output_cost_per_token' => 0.00123,
                'features' => [
                    ModelFeature::Vision,
                    ModelFeature::Reasoning,
                ],
            ],
        ]),
    ]);

    $ollamaModels = $factory->getModels(ModelProvider::Ollama);
    $customModels = $factory->getModels(ModelProvider::Custom);

    expect($ollamaModels)->toBeArray()->toHaveCount(1);
    expect($ollamaModels[0])->toEqual('gemma3:12b');

    expect($customModels)->toBeArray()->toHaveCount(1);
    expect($customModels[0])->toEqual('foobar');

    $ollamaModelInfo = $factory->getModelInfo(ModelProvider::Ollama, 'gemma3:12b');
    $customModelInfo = $factory->getModelInfo(ModelProvider::Custom, 'foobar');

    expect($ollamaModelInfo)->toBeInstanceOf(ModelInfo::class);
    expect($ollamaModelInfo->name)->toEqual('gemma3:12b');
    expect($ollamaModelInfo->provider)->toEqual(ModelProvider::Ollama);
    expect($ollamaModelInfo->type)->toEqual(ModelType::Chat);
    expect($ollamaModelInfo->features)->toEqual([
        ModelFeature::JsonOutput,
        ModelFeature::StructuredOutput,
        ModelFeature::ToolCalling,
        ModelFeature::ToolChoice,
    ]);

    expect($customModelInfo)->toBeInstanceOf(ModelInfo::class);
    expect($customModelInfo->name)->toEqual('foobar');
    expect($customModelInfo->provider)->toEqual(ModelProvider::Custom);
    expect($customModelInfo->type)->toEqual(ModelType::Chat);
    expect($customModelInfo->maxInputTokens)->toEqual(8000);
    expect($customModelInfo->maxOutputTokens)->toEqual(16000);
    expect($customModelInfo->inputCostPerToken)->toEqual(0.00123);
    expect($customModelInfo->outputCostPerToken)->toEqual(0.00123);
    expect($customModelInfo->features)->toEqual([
        ModelFeature::Vision,
        ModelFeature::Reasoning,
    ]);
});
