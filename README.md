# A unified way to get AI model info from various providers

[![Latest Version](https://img.shields.io/packagist/v/cortexphp/model-info.svg?style=flat-square&logo=composer)](https://packagist.org/packages/cortexphp/model-info)
![GitHub Actions Test Workflow Status](https://img.shields.io/github/actions/workflow/status/cortexphp/model-info/run-tests.yml?style=flat-square&logo=github)
![GitHub License](https://img.shields.io/github/license/cortexphp/model-info?style=flat-square&logo=github)

## Features

- Get model information from various AI providers (Ollama, LiteLLM)
- PSR-16 Simple Cache support for caching model information
- Retrieve available models for supported providers
- Get detailed model information for specific models
- Extensible provider system

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

// Get information about a specific model
$modelInfo = $factory->getModelInfo(ModelProvider::Ollama, 'llama3.1');

```

```php

// Using with custom cache implementation
use Psr\SimpleCache\CacheInterface;

$factory = new ModelInfoFactory(
    cache: $yourPsr16CacheImplementation
);

// Force fetch (bypass cache) with exception handling
try {
    $models = $factory->getModelsOrFail(ModelProvider::LiteLLM);
    $modelInfo = $factory->getModelInfoOrFail(ModelProvider::LiteLLM, 'gpt-4');
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
