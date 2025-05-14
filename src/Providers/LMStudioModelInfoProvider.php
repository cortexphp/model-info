<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Providers;

use Cortex\ModelInfo\Data\ModelInfo;
use Psr\Http\Client\ClientInterface;
use Cortex\ModelInfo\Enums\ModelType;
use Cortex\ModelInfo\Enums\ModelFeature;
use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Contracts\ModelInfoProvider;
use Cortex\ModelInfo\Providers\Concerns\ChecksSupport;
use Cortex\ModelInfo\Providers\Concerns\MakesRequests;

/**
 * @phpstan-type ModelInfoResponse array{id: string, object: string, type: string, max_context_length: int, type: ?string}
 */
class LMStudioModelInfoProvider implements ModelInfoProvider
{
    use ChecksSupport;
    use MakesRequests;

    public function __construct(
        protected string $host = 'http://localhost:1234',
        ?ClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? self::discoverHttpClientOrFail();
    }

    public function supportedModelProviders(): array
    {
        return [
            ModelProvider::LMStudio,
        ];
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return array<array-key, string>
     */
    public function getModels(ModelProvider $modelProvider): array
    {
        $this->checkSupportOrFail($modelProvider);

        $body = $this->getModelsResponse();

        $models = array_map(
            // @phpstan-ignore return.type,argument.type
            fn(array $model): string => $model['id'],
            $body['data'],
        );

        return array_values($models);
    }

    public function getModelInfo(ModelProvider $modelProvider, string $model): ModelInfo
    {
        $this->checkSupportOrFail($modelProvider);

        $body = $this->getModelInfoResponse($model);
        $type = $body['type'] ?? '';

        return new ModelInfo(
            name: $model,
            provider: ModelProvider::LMStudio,
            type: self::getModelType($type),
            maxInputTokens: self::getMaxInputTokens($body['max_context_length']),
            maxOutputTokens: null,
            inputCostPerToken: 0.0,
            outputCostPerToken: 0.0,
            features: self::getFeatures($body),
        );
    }

    /**
     * @param ModelInfoResponse $body
     *
     * @return array<array-key, \Cortex\ModelInfo\Enums\ModelFeature>
     */
    protected static function getFeatures(array $body): array
    {
        $features = [];

        if ($body['type'] === 'llm') {
            $features[] = ModelFeature::JsonOutput;
            $features[] = ModelFeature::StructuredOutput;
        }

        return $features;
    }

    protected static function getModelType(string $type): ModelType
    {
        return match ($type) {
            'llm' => ModelType::Chat,
            'embeddings' => ModelType::Embedding,
            default => ModelType::Other,
        };
    }

    protected static function getMaxInputTokens(int $maxContextLength): ?int
    {
        return (int) ($maxContextLength * 0.9765625);
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return ModelInfoResponse
     */
    protected function getModelInfoResponse(string $model): array
    {
        $body = self::discoverHttpStreamFactoryOrFail()->createStream(json_encode([
            'model' => $model,
        ], JSON_THROW_ON_ERROR));

        $request = self::discoverHttpRequestFactoryOrFail()
            ->createRequest('POST', $this->host . '/api/show')
            ->withBody($body);

        // @phpstan-ignore return.type
        return $this->getJsonResponse($request);
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return array{data: array{id: string}}
     */
    protected function getModelsResponse(): array
    {
        $request = self::discoverHttpRequestFactoryOrFail()
            ->createRequest('GET', $this->host . '/api/v0/models');

        // @phpstan-ignore return.type
        return $this->getJsonResponse($request);
    }
}
