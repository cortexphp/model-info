<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Providers;

use JsonException;
use SensitiveParameter;
use Psr\SimpleCache\CacheInterface;
use Cortex\ModelInfo\Data\ModelInfo;
use Psr\Http\Client\ClientInterface;
use Cortex\ModelInfo\Enums\ModelType;
use Cortex\ModelInfo\Enums\ModelFeature;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Contracts\ModelInfoProvider;
use Cortex\ModelInfo\Exceptions\ModelInfoException;
use Cortex\ModelInfo\Providers\Concerns\ChecksSupport;
use Cortex\ModelInfo\Providers\Concerns\MakesRequests;

class LiteLLMModelInfoProvider implements ModelInfoProvider
{
    use ChecksSupport;
    use MakesRequests;

    protected const string LITELLM_STATIC_URL = 'https://raw.githubusercontent.com/BerriAI/litellm/main/model_prices_and_context_window.json';

    protected ClientInterface $httpClient;

    public function __construct(
        protected ?string $host = null,
        #[SensitiveParameter]
        protected ?string $apiKey = null,
        ?ClientInterface $httpClient = null,
        protected ?CacheInterface $cache = null,
    ) {
        $this->httpClient = $httpClient ?? self::discoverHttpClientOrFail();
        $this->cache = $cache ?? self::discoverCache();
    }

    public function supportedModelProviders(): array
    {
        return array_filter(
            ModelProvider::cases(),
            fn(ModelProvider $provider): bool => $provider !== ModelProvider::Custom,
        );
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return array<array-key, \Cortex\ModelInfo\Data\ModelInfo>
     */
    public function getModels(ModelProvider $modelProvider): array
    {
        $this->checkSupportOrFail($modelProvider);

        $body = $this->getStaticResponse();

        $models = array_filter(
            $body,
            fn(array $model): bool => $model['litellm_provider'] === $modelProvider->value,
        );

        return array_values(array_map(
            fn(array $modelInfo, string $model): ModelInfo => self::mapModelInfo($modelProvider, $model, $modelInfo),
            $models,
            array_keys($models),
        ));
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    public function getModelInfo(ModelProvider $modelProvider, string $model): ModelInfo
    {
        $this->checkSupportOrFail($modelProvider);

        $body = $this->getStaticResponse();

        $models = array_filter(
            $body,
            fn(array $model): bool => $model['litellm_provider'] === $modelProvider->value,
        );

        $modelInfo = array_values(
            array_filter(
                $models,
                fn(string $key): bool => $key === $model,
                ARRAY_FILTER_USE_KEY,
            ),
        )[0] ?? null;

        if ($modelInfo === null) {
            throw new ModelInfoException('Model not found');
        }

        return self::mapModelInfo($modelProvider, $model, $modelInfo);
    }

    protected static function mapModelInfo(
        ModelProvider $modelProvider,
        string $model,
        array $modelInfo,
    ): ModelInfo {
        return new ModelInfo(
            name: $model,
            provider: $modelProvider,
            type: self::mapModelType($modelInfo['mode'] ?? ''),
            maxInputTokens: $modelInfo['max_input_tokens'] ?? null,
            maxOutputTokens: $modelInfo['max_output_tokens'] ?? null,
            inputCostPerToken: $modelInfo['input_cost_per_token'] ?? 0.0,
            outputCostPerToken: $modelInfo['output_cost_per_token'] ?? 0.0,
            features: self::getFeatures($modelInfo),
            isDeprecated: isset($modelInfo['deprecation_date']) && $modelInfo['deprecation_date'] !== null,
        );
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return array<int, \Cortex\Enums\ModelFeature>
     */
    protected static function getFeatures(array $info): array
    {
        $features = [];

        if ($info['supports_response_schema'] ?? false) {
            $features[] = ModelFeature::StructuredOutput;
            $features[] = ModelFeature::JsonOutput;
        }

        if ($info['supports_function_calling'] ?? false) {
            $features[] = ModelFeature::ToolCalling;
        }

        if ($info['supports_vision'] ?? false) {
            $features[] = ModelFeature::Vision;
        }

        if ($info['supports_tool_choice'] ?? false) {
            $features[] = ModelFeature::ToolChoice;
        }

        if ($info['supports_reasoning'] ?? false) {
            $features[] = ModelFeature::Reasoning;
        }

        if ($info['supports_web_search'] ?? false) {
            $features[] = ModelFeature::WebSearch;
        }

        if ($info['supports_prompt_caching'] ?? false) {
            $features[] = ModelFeature::PromptCaching;
        }

        if ($info['supports_audio_input'] ?? false) {
            $features[] = ModelFeature::AudioInput;
        }

        if ($info['supports_audio_output'] ?? false) {
            $features[] = ModelFeature::AudioOutput;
        }

        return $features;
    }

    protected static function mapModelType(string $type): ModelType
    {
        return match ($type) {
            'chat' => ModelType::Chat,
            'completion' => ModelType::Completion,
            'embedding' => ModelType::Embedding,
            'image_generation' => ModelType::ImageGeneration,
            'audio_speech' => ModelType::TextToSpeech,
            'audio_transcription' => ModelType::SpeechToText,
            'moderation' => ModelType::Moderation,
            default => ModelType::Unknown,
        };
    }

    /**
     * @throws \Cortex\Exceptions\ModelInfoException
     *
     * @return array<string, mixed>
     */
    protected function getStaticResponse(): array
    {
        $request = self::discoverHttpRequestFactory()->createRequest('GET', self::LITELLM_STATIC_URL);
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new ModelInfoException('Failed to get model info');
        }

        try {
            return json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new ModelInfoException('Failed to decode model info', previous: $jsonException);
        }
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return array<string, mixed>
     */
    protected function getApiModelsResponse(): array
    {
        $request = self::discoverHttpRequestFactory()
            ->createRequest('GET', $this->host . '/v1/models');

        return $this->getJsonResponse($request);
    }

    protected function shouldUseStaticResponse(): bool
    {
        return $this->host === null || $this->apiKey === null;
    }
}
