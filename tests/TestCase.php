<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function mockHttpClient(ResponseInterface ...$responses): ClientInterface
    {
        return new Client([
            'handler' => HandlerStack::create(new MockHandler($responses)),
        ]);
    }
}
