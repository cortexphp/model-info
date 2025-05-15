<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Data;

use Cortex\ModelInfo\Enums\ModelType;
use Cortex\ModelInfo\Enums\ModelFeature;
use Cortex\ModelInfo\Enums\ModelProvider;

/**
 * @phpstan-type ModelInfoData array{name: string, provider: string|ModelProvider, type: string|ModelType, max_input_tokens: int|null, max_output_tokens: int|null, input_cost_per_token?: float, output_cost_per_token?: float, features?: array<array-key, ModelFeature>, is_deprecated?: bool}
 */
readonly class ModelInfo
{
    /**
     * @param array<\Cortex\ModelInfo\Enums\ModelFeature> $features
     */
    public function __construct(
        public string $name,
        public ModelProvider $provider,
        public ModelType $type,
        public ?int $maxInputTokens,
        public ?int $maxOutputTokens,
        public float $inputCostPerToken,
        public float $outputCostPerToken,
        public array $features,
        public bool $isDeprecated = false,
    ) {}

    public function supportsFeature(ModelFeature $modelFeature): bool
    {
        return in_array($modelFeature, $this->features, true);
    }

    /**
     * @param ModelInfoData $data
     */
    public static function createFromArray(array $data): self
    {
        $provider = $data['provider'] instanceof ModelProvider
            ? $data['provider']
            : ModelProvider::from($data['provider']);

        $type = $data['type'] instanceof ModelType
            ? $data['type']
            : ModelType::from($data['type']);

        return new self(
            $data['name'],
            $provider,
            $type,
            $data['max_input_tokens'] ?? null,
            $data['max_output_tokens'] ?? null,
            $data['input_cost_per_token'] ?? 0.0,
            $data['output_cost_per_token'] ?? 0.0,
            $data['features'] ?? [],
            $data['is_deprecated'] ?? false,
        );
    }
}
