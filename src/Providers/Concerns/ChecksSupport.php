<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Providers\Concerns;

use Cortex\ModelInfo\Enums\ModelProvider;
use Cortex\ModelInfo\Exceptions\ModelInfoException;

/** @mixin \Cortex\ModelInfo\Contracts\ModelInfoProvider */
trait ChecksSupport
{
    /**
     * Determine if the given model provider is supported.
     */
    public function supports(ModelProvider $modelProvider): bool
    {
        return in_array($modelProvider, $this->supportedModelProviders(), true);
    }

    /**
     * Determine if the given model provider is supported and throw an exception if it is not.
     *
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     */
    public function checkSupportOrFail(ModelProvider $modelProvider): void
    {
        if (! $this->supports($modelProvider)) {
            throw new ModelInfoException('Model provider not supported');
        }
    }
}
