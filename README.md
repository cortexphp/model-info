# A unified way to get AI model info from various providers

[![Latest Version](https://img.shields.io/packagist/v/cortexphp/model-info.svg?style=flat-square&logo=composer)](https://packagist.org/packages/cortexphp/model-info)
![GitHub Actions Test Workflow Status](https://img.shields.io/github/actions/workflow/status/cortexphp/model-info/run-tests.yml?style=flat-square&logo=github)
![GitHub License](https://img.shields.io/github/license/cortexphp/model-info?style=flat-square&logo=github)

## Features

- 🤖 **Model Providers** - Get detailed model information with type-safe responses from various model providers (OpenAI, Ollama, etc.)
- 💾 **Simple Cache** - PSR-16 Simple Cache support for caching model information
- 🔌 **Extensibility** - Easily add support for additional model providers

## Requirements

- PHP 8.3+

## Installation

```bash
composer require cortexphp/model-info
```

## Usage

```php
use Cortex\ModelInfo\ModelInfoFactory;
use Cortex\ModelInfo\Enums\ModelProvider;

// Create a new factory instance
$factory = new ModelInfoFactory();

// Get all available models for a provider
$models = $factory->getModels(ModelProvider::Ollama);
// ['llama3.1', 'llama3.1:8b', 'llama3.1:70b']

// Get information about a specific model
$modelInfo = $factory->getModelInfo(ModelProvider::Ollama, 'llama3.1');

// Accessing model information properties
echo $modelInfo->name;            // 'llama3.1'
echo $modelInfo->provider;        // ModelProvider::Ollama
echo $modelInfo->type;            // ModelType::Chat
echo $modelInfo->maxInputTokens;  // 8000
echo $modelInfo->maxOutputTokens; // null
echo $modelInfo->inputCostPerToken;  // 0.0
echo $modelInfo->outputCostPerToken; // 0.0
echo $modelInfo->isDeprecated;    // false
echo $modelInfo->features;        // [ModelFeature::ToolCalling, ModelFeature::JsonOutput, etc]

// Check if model supports specific features
if ($modelInfo->supportsFeature(ModelFeature::ToolCalling)) {
    // Use tool calling feature
}
```

```php
// Using with custom PSR-16 cache implementation
$factory = new ModelInfoFactory(
    cache: $yourPsr16CacheImplementation // `Psr\SimpleCache\CacheInterface`
);

// With exception handling
try {
    $modelInfo = $factory->getModelInfoOrFail(ModelProvider::OpenAI, 'gpt-4o');
} catch (ModelInfoException $e) {
    // Handle exception
}

// Clear the cache
$factory->flushCache();
```

## Credits

- [Sean Tymon](https://github.com/tymondesigns)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
