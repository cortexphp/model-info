<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Enums;

use Cortex\ModelInfo\Data\ModelInfo;
use Cortex\ModelInfo\ModelInfoFactory;
use Psr\Container\ContainerExceptionInterface;
use Cortex\ModelInfo\Providers\Concerns\DiscoversPsrImplementations;

enum ModelProvider: string
{
    use DiscoversPsrImplementations;

    case OpenAI = 'openai';
    case Anthropic = 'anthropic';
    case Groq = 'groq';
    case Gemini = 'gemini';
    case XAI = 'xai';
    case Mistral = 'mistral';
    case Ollama = 'ollama';
    case LMStudio = 'lmstudio';
    case Together = 'together';
    case OpenRouter = 'openrouter';
    case Bedrock = 'bedrock';
    case DeepSeek = 'deepseek';
    case Custom = 'custom';

    /**
     * @param array<array-key, \Cortex\ModelInfo\Contracts\ModelInfoProvider>|null $modelInfoProviders
     *
     * @return array<array-key, \Cortex\ModelInfo\Data\ModelInfo>
     */
    public function models(?array $modelInfoProviders = null): array
    {
        return self::modelInfoFactory($modelInfoProviders)->getModels($this);
    }

    /**
     * Get the info for a specific model.
     *
     * @param array<array-key, \Cortex\ModelInfo\Contracts\ModelInfoProvider>|null $modelInfoProviders
     */
    public function info(string $model, ?array $modelInfoProviders = null): ?ModelInfo
    {
        return self::modelInfoFactory($modelInfoProviders)->getModelInfo($this, $model);
    }

    /**
     * Get the input cost for tokens for a specific model.
     */
    public function inputCostForTokens(string $model, int $tokens): ?float
    {
        $inputCostPerToken = $this->info($model)?->inputCostPerToken;

        if ($inputCostPerToken === null) {
            return null;
        }

        return $inputCostPerToken * $tokens;
    }

    /**
     * Get the output cost for tokens for a specific model.
     */
    public function outputCostForTokens(string $model, int $tokens): ?float
    {
        $outputCostPerToken = $this->info($model)?->outputCostPerToken;

        if ($outputCostPerToken === null) {
            return null;
        }

        return $outputCostPerToken * $tokens;
    }

    /**
     * @param array<array-key, \Cortex\ModelInfo\Contracts\ModelInfoProvider>|null $modelInfoProviders
     */
    public static function modelInfoFactory(?array $modelInfoProviders = null): ModelInfoFactory
    {
        $container = self::discoverContainer();

        try {
            /** @var \Cortex\ModelInfo\ModelInfoFactory $factory */
            $factory = $container?->get(ModelInfoFactory::class);
        } catch (ContainerExceptionInterface) {
            //
        }

        return $factory ?? new ModelInfoFactory($modelInfoProviders);
    }
}
