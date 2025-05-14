<?php

declare(strict_types=1);

namespace Cortex\ModelInfo\Providers\Concerns;

use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Cortex\ModelInfo\Exceptions\ModelInfoException;

/** @mixin \Cortex\ModelInfo\Contracts\ModelInfoProvider */
trait MakesRequests
{
    use DiscoversPsrImplementations;

    protected ClientInterface $httpClient;

    /**
     * Get a JSON response from the given request.
     *
     * @throws \Cortex\ModelInfo\Exceptions\ModelInfoException
     *
     * @return array<array-key, mixed>
     */
    public function getJsonResponse(RequestInterface $request): array
    {
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new ModelInfoException('Failed to get model info');
        }

        try {
            /** @var array<array-key, mixed> $result */
            $result = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new ModelInfoException('Failed to decode model info', previous: $jsonException);
        }

        return $result;
    }
}
