<?php

namespace Webkul\Bagisto\Traits;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Webkul\Bagisto\Enums\Export\CacheType;
use Webkul\Bagisto\Http\Client\HttpClientFactory;

trait ApiRequest
{
    protected $httpClient;

    protected $tokenReneratedAt = false;

    public function buildHttpRequest()
    {
        $this->httpClient = Cache::get(CacheType::BAGISTO_API_HTTP->value);
        if (! $this->httpClient) {
            $httpClientFactory = new HttpClientFactory;
            $this->httpClient = $httpClientFactory->withBaseUri($this->credential['shop_url'])
                ->withEmail($this->credential['email'])
                ->withPassword($this->credential['password'])
                ->make();

            Cache::put(CacheType::BAGISTO_API_HTTP->value, $this->httpClient, Env('SESSION_LIFETIME'));
        }

        return $this->httpClient;
    }

    public function setApiRequest($method, $endPoint, $data = [], array $options = [])
    {
        try {
            $this->buildHttpRequest();
            $response = $this->httpClient->toRequest($method, $endPoint, $data, $options);

            return $response;
        } catch (AuthenticationException $e) {
            if (! $this->tokenReneratedAt) {
                $this->tokenReneratedAt = true;
                $this->buildHttpRequest();

                return $this->setApiRequest($method, $endPoint, $data, $options);
            }
        } catch (ValidationException $e) {
            $this->logWarning($e->validator->errors()->messages(), $data['sku'] ?? $data['code'] ?? 'bulk');
        } catch (\Exception $e) {
            $this->jobLogger->warning($e);
        }
    }

    public function logWarning(array $data, string $identifier): void
    {
        if (! empty($data) && ! empty($identifier)) {
            $error = json_encode($data, true);

            $this->jobLogger->warning(
                "Warning for item with SKU/Code: {$identifier}, : {$error}"
            );
        }
    }
}
