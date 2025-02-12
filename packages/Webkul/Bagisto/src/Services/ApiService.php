<?php

declare(strict_types=1);

namespace Webkul\Bagisto\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Webkul\Bagisto\Contracts\ApiServiceContract;
use Webkul\Bagisto\Enums\Services\ContentType;

/**
 * @internal
 */
final class ApiService implements ApiServiceContract
{
    /**
     * Creates a new Http Transporter instance.
     */
    public function __construct(
        private string $baseUri,
        private Headers $headers,
    ) {}

    public function setBaseUri(string $baseUri)
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * Creates a new Psr 7 Request instance.
     */
    public function toRequest(string $method, string $endpoint, array $payload = [], array $options = [])
    {
        [$uri, $contentType] = $this->buildUri($endpoint, $options);

        $headers = $this->headers->withContentType();

        $response = $this->sendRequest($method, $uri, $headers, $payload, $options);

        if ($response->failed()) {
            if ($response->clientError()) {
                $errorJson = $response->json();

                if ($response->status() == 422) {
                    throw ValidationException::withMessages($errorJson['errors'] ?? $errorJson);
                }

                if ($response->status() == 404) {
                    throw ValidationException::withMessages([
                        'endpoint' => 'Endpoint is invalid',
                    ]);
                }

                throw ValidationException::withMessages($errorJson['errors'] ?? $errorJson);
            }
        }

        $responseData = $response->json();

        return $responseData['data'] ?? [];
    }

    private function sendRequest($method, $uri, $headers, $payload, $options)
    {
        $isMultipart = ! empty($options['isMultipart']) ? true : false;
        $request = $this->initializeRequest($headers, $options['timeout'] ?? 120, $isMultipart);

        if ($isMultipart) {
            $this->attachMedia($request, $payload, $options);
        }

        $payload = $this->preparePayload($payload, $options, $isMultipart);

        return $this->executeRequest($request, $method, $uri, $payload);
    }

    private function initializeRequest($headers, $timeout, $isMultipart)
    {
        $request = Http::withHeaders($headers->toArray())->timeout($timeout);
        if ($isMultipart) {
            $request = $request->asMultipart();
        }

        return $request;
    }

    private function attachMedia($request, &$payload, $options): void
    {
        if (! empty($options['mediaCodes'])) {
            foreach ($options['mediaCodes'] as $mediaCode) {
                if (isset($payload[$mediaCode]) && is_file($payload[$mediaCode])) {
                    $request->attach($mediaCode.'[0]', fopen($payload[$mediaCode], 'r'), basename($payload[$mediaCode]));
                    unset($payload[$mediaCode]);
                }
            }
        }
    }

    private function preparePayload($payload, $options, $isMultipart)
    {
        $item = [];
        if (! isset($payload['_method'])) {
            return $payload;
        }
        foreach ($payload as $key => $value) {
            if ($key === 'variants') {
                $this->handleVariants($value, $item);

                continue;
            }

            if (is_array($value)) {
                foreach ($value as $newKey => $newValue) {
                    $item[sprintf('%s[%s]', $key, $newKey)] = $newValue;
                }

                continue;
            }

            $item[$key] = $value;
        }

        return $item;
    }

    private function handleVariants($variants, &$item): void
    {
        foreach ($variants as $variantKey => $variantValue) {
            if (is_array($variantValue)) {
                foreach ($variantValue as $newKey => $newValue) {
                    if ($newKey == 'images') {
                        continue;
                    }
                    if (! is_array($newValue)) {
                        $item[sprintf('variants[%s][%s]', $variantKey, $newKey)] = $newValue;
                    }
                }
            } else {
                $item['variants'][$variantKey] = $variantValue;
            }
        }
    }

    private function executeRequest($request, $method, $uri, $payload)
    {
        try {
            return $request->$method($uri, $payload);
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    private function buildUri(string $endpoint, $options)
    {
        $apiEndPointConfig = config('bagisto-api-end-point');

        $configEndpoint = $apiEndPointConfig[$endpoint]['endPoint'];

        if (! empty($options['id'])) {
            $configEndpoint = $apiEndPointConfig[$endpoint]['endPoint'].'/'.$options['id'];
        }

        $contentType = $apiEndPointConfig[$endpoint]['contentType'] ?? ContentType::JSON->value;

        return [
            "{$this->baseUri}/api/v1/admin/{$configEndpoint}",
            $contentType,
        ];
    }
}
