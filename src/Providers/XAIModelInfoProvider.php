<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Providers;

use SensitiveParameter;
use Cortex\ModelInfo\Data\ModelInfo;
use Psr\Http\Client\ClientInterface;
use Cortex\ModelInfo\Enums\ModelType;
use Cortex\ModelInfo\Enums\ModelFeature;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Contracts\ModelInfoProvider;
use Cortex\ModelInfo\Providers\Concerns\ChecksSupport;
use Cortex\ModelInfo\Providers\Concerns\MakesRequests;

/**
 * @phpstan-type XAILanguageModelResponse array{id: string, fingerprint: string, created: int, object: string, owned_by: string, version: string, input_modalities: array<array-key, string>, output_modalities: array<array-key, string>, prompt_text_token_price: int, cached_prompt_text_token_price: int, prompt_image_token_price: int, completion_text_token_price: int, aliases: array<array-key, string>}
 */
class XAIModelInfoProvider implements ModelInfoProvider
{
    use ChecksSupport;
    use MakesRequests;

    public function __construct(
        #[SensitiveParameter]
        protected ?string $apiKey = null,
        ?ClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? self::discoverHttpClientOrFail();
    }

    public function supportedModelProviders(): array
    {
        return [
            ModelProvider::XAI,
        ];
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return array<array-key, \Cortex\ModelInfo\Data\ModelInfo>
     */
    public function getModels(ModelProvider $modelProvider): array
    {
        $this->checkSupportOrFail($modelProvider);

        // Only supports chat models at the moment
        $body = $this->getLanguageModelsResponse();

        return array_values(array_map(
            fn(array $model): ModelInfo => self::mapModelInfo($model),
            $body['models'],
        ));
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    public function getModelInfo(ModelProvider $modelProvider, string $model): ModelInfo
    {
        $this->checkSupportOrFail($modelProvider);

        return self::mapModelInfo(
            $this->getLanguageModelResponse($model),
        );
    }

    /**
     * @param XAILanguageModelResponse $body
     */
    protected static function mapModelInfo(array $body): ModelInfo
    {
        return new ModelInfo(
            name: $body['id'],
            provider: ModelProvider::XAI,
            type: self::getModelType($body),
            maxInputTokens: self::getMaxInputTokens($body),
            maxOutputTokens: null,
            inputCostPerToken: self::getCostPerToken($body['prompt_text_token_price']),
            outputCostPerToken: self::getCostPerToken($body['completion_text_token_price']),
            features: self::getFeatures($body),
        );
    }

    /**
     * @param XAILanguageModelResponse $body
     *
     * @return array<int, \Cortex\ModelInfo\Enums\ModelFeature>
     */
    protected static function getFeatures(array $body): array
    {
        $features = [
            ModelFeature::JsonOutput,
            ModelFeature::ToolCalling,
            ModelFeature::ToolChoice,
            ModelFeature::StructuredOutput,
        ];

        if (in_array('image', $body['input_modalities'], true)) {
            $features[] = ModelFeature::Vision;
        }

        return $features;
    }

    /**
     * @param XAILanguageModelResponse $body
     */
    protected static function getModelType(array $body): ModelType
    {
        if (in_array('text', $body['input_modalities'], true)) {
            return ModelType::Chat;
        }

        return ModelType::Unknown;
    }

    protected static function getCostPerToken(int $pricePerMillionTokens): float
    {
        // Convert from cents per million tokens to dollars per token
        return ($pricePerMillionTokens / 100.0) / 1_000_000;
    }

    /**
     * @param XAILanguageModelResponse $body
     */
    protected static function getMaxInputTokens(array $body): ?int
    {
        return str_starts_with($body['id'], 'grok-2-vision')
            ? 32768
            : 131072;
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return array{models: array<array-key, XAILanguageModelResponse>}
     */
    protected function getLanguageModelsResponse(): array
    {
        $request = self::discoverHttpRequestFactoryOrFail()
            ->createRequest('GET', 'https://api.x.ai/v1/language-models')
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey);

        // @phpstan-ignore return.type
        return $this->getJsonResponse($request);
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return XAILanguageModelResponse
     */
    protected function getLanguageModelResponse(string $id): array
    {
        $request = self::discoverHttpRequestFactoryOrFail()
            ->createRequest('GET', 'https://api.x.ai/v1/language-models/' . $id)
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey);

        // @phpstan-ignore return.type
        return $this->getJsonResponse($request);
    }
}
