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
 * @phpstan-type OllamaModelsResponse array{name: string}
 * @phpstan-type OllamaModelInfoResponse array{name: string, model_info: array<string, mixed>|null, capabilities: array<array-key, string>|null}
 */
class OllamaModelInfoProvider implements ModelInfoProvider
{
    use ChecksSupport;
    use MakesRequests;

    public function __construct(
        protected string $host = 'http://localhost:11434',
        ?ClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? self::discoverHttpClientOrFail();
    }

    public function supportedModelProviders(): array
    {
        return [
            ModelProvider::Ollama,
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

        $body = $this->getModelsResponse();

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
            $this->getModelInfoResponse($model),
        );
    }

    /**
     * @param OllamaModelInfoResponse|OllamaModelsResponse $modelResponseBody
     */
    protected static function mapModelInfo(array $modelResponseBody): ModelInfo
    {
        $modelInfo = $modelResponseBody['model_info'] ?? [];
        $capabilities = $modelResponseBody['capabilities'] ?? [];

        return new ModelInfo(
            name: $modelResponseBody['name'],
            provider: ModelProvider::Ollama,
            type: self::getModelType($capabilities),
            maxInputTokens: self::getMaxInputTokens($modelInfo),
            maxOutputTokens: null,
            inputCostPerToken: 0.0,
            outputCostPerToken: 0.0,
            features: self::getFeatures($capabilities),
        );
    }

    /**
     * @param array<array-key, string> $capabilities
     *
     * @return array<array-key, \Cortex\ModelInfo\Enums\ModelFeature>
     */
    protected static function getFeatures(array $capabilities): array
    {
        $features = [];

        if (in_array('completion', $capabilities, true)) {
            $features[] = ModelFeature::JsonOutput;
            $features[] = ModelFeature::StructuredOutput;
        }

        if (in_array('vision', $capabilities, true)) {
            $features[] = ModelFeature::Vision;
        }

        if (in_array('tools', $capabilities, true)) {
            $features[] = ModelFeature::ToolCalling;
            $features[] = ModelFeature::ToolChoice;
        }

        return $features;
    }

    /**
     * @param array<array-key, string> $capabilities
     */
    protected static function getModelType(array $capabilities): ModelType
    {
        return match (true) {
            in_array('completion', $capabilities, true) => ModelType::Chat,
            in_array('embedding', $capabilities, true) => ModelType::Embedding,
            default => ModelType::Unknown,
        };
    }

    /**
     * @param array<string, mixed> $modelInfo
     */
    protected static function getMaxInputTokens(array $modelInfo): ?int
    {
        $filtered = array_filter(
            $modelInfo,
            fn(string $key): bool => str_ends_with($key, '.context_length'),
            ARRAY_FILTER_USE_KEY,
        );

        if ($filtered === []) {
            return null;
        }

        $contextLength = reset($filtered);

        if (! is_numeric($contextLength)) {
            return null;
        }

        return (int) ($contextLength * 0.9765625);
    }

    /**
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return OllamaModelInfoResponse
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
     * @return array{models: array<array-key, OllamaModelsResponse>}
     */
    protected function getModelsResponse(): array
    {
        $request = self::discoverHttpRequestFactoryOrFail()
            ->createRequest('GET', $this->host . '/api/tags');

        // @phpstan-ignore return.type
        return $this->getJsonResponse($request);
    }
}
