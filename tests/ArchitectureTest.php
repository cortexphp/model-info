<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Tests;

use Throwable;

arch()->preset()->php();
arch()->preset()->security();

arch()->expect('Cortex\ModelInfo\Contracts')->toBeInterfaces();
arch()->expect('Cortex\ModelInfo\Enums')->toBeEnums();
arch()->expect('Cortex\ModelInfo\Exceptions')->toImplement(Throwable::class);
