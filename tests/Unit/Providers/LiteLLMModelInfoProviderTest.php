<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Tests\Unit\Providers;

use GuzzleHttp\Psr7\Response;
use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\Enums\ModelType;
use Cortex\ModelInfo\Enums\ModelFeature;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Exceptions\ModelInfoException;
use Cortex\ModelInfo\Providers\LiteLLMModelInfoProvider;

covers(LiteLLMModelInfoProvider::class);

test('it can get the models', function (): void {
    $client = $this->mockHttpClient(
        new Response(body: json_encode([
            'gpt-4o' => [
                'litellm_provider' => 'openai',
                'mode' => 'chat',
            ],
            'gpt-3.5-turbo' => [
                'litellm_provider' => 'openai',
                'mode' => 'chat',
            ],
        ])),
        new Response(body: json_encode([
            'xai/grok-2-latest' => [
                'litellm_provider' => 'xai',
                'mode' => 'chat',
            ],
        ])),
    );

    $provider = new LiteLLMModelInfoProvider(
        httpClient: $client,
    );

    $openAIModels = $provider->getModels(ModelProvider::OpenAI);
    $xAIModels = $provider->getModels(ModelProvider::XAI);

    expect($openAIModels)->toBeArray()
        ->toHaveCount(2)
        ->toContainOnlyInstancesOf(ModelInfo::class);

    expect($openAIModels[0]->name)->toBe('gpt-4o');
    expect($openAIModels[1]->name)->toBe('gpt-3.5-turbo');

    expect($xAIModels)->toBeArray()
        ->toHaveCount(1)
        ->toContainOnlyInstancesOf(ModelInfo::class);

    // prefix is removed from model name
    expect($xAIModels[0]->name)->toBe('grok-2-latest');
});

test('it can get the model info', function (): void {
    $client = $this->mockHttpClient(
        new Response(body: json_encode([
            'gpt-4o' => [
                'max_tokens' => 16384,
                'max_input_tokens' => 128000,
                'max_output_tokens' => 16384,
                'input_cost_per_token' => 0.0000025,
                'output_cost_per_token' => 0.000010,
                'input_cost_per_token_batches' => 0.00000125,
                'output_cost_per_token_batches' => 0.00000500,
                'cache_read_input_token_cost' => 0.00000125,
                'litellm_provider' => 'openai',
                'mode' => 'chat',
                'supports_function_calling' => true,
                'supports_parallel_function_calling' => true,
                'supports_response_schema' => true,
                'supports_vision' => true,
                'supports_prompt_caching' => true,
                'supports_system_messages' => true,
                'supports_tool_choice' => true,
                'supports_web_search' => true,
                'search_context_cost_per_query' => [
                    'search_context_size_low' => 0.030,
                    'search_context_size_medium' => 0.035,
                    'search_context_size_high' => 0.050,
                ],
            ],
        ])),
    );

    $provider = new LiteLLMModelInfoProvider(
        httpClient: $client,
    );

    $modelInfo = $provider->getModelInfo(ModelProvider::OpenAI, 'gpt-4o');

    expect($modelInfo)->toBeInstanceOf(ModelInfo::class);
    expect($modelInfo->name)->toBe('gpt-4o');
    expect($modelInfo->provider)->toBe(ModelProvider::OpenAI);
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
        ->toContain(ModelFeature::ToolChoice)
        ->toContain(ModelFeature::WebSearch)
        ->toContain(ModelFeature::Vision)
        ->toContain(ModelFeature::PromptCaching);
});

it('throws an exception if the model is not found', function (): void {
    $client = $this->mockHttpClient(
        new Response(body: json_encode([])),
    );

    $provider = new LiteLLMModelInfoProvider(
        httpClient: $client,
    );

    expect(fn(): ModelInfo => $provider->getModelInfo(ModelProvider::OpenAI, 'gpt-7'))
        ->toThrow(ModelInfoException::class, 'Model not found');
});

it('throws an exception if the model provider is not supported', function (): void {
    $provider = new LiteLLMModelInfoProvider();

    expect(fn(): ModelInfo => $provider->getModelInfo(ModelProvider::Custom, 'foo'))
        ->toThrow(ModelInfoException::class, 'Model provider not supported');
});
