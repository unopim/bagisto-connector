<?php

namespace Webkul\BagistoPlugin\Traits;

use Illuminate\Support\Facades\Cache;
use Webkul\BagistoPlugin\Enums\Export\CacheType;

trait Credential
{
    /**
     * Initializes Credential for the export process.
     */
    protected function initializeCredential($filters): void
    {
        $this->credential = Cache::get(CacheType::CREDENTIAL->value, []);
        if (empty($this->credential)) {
            $activeCredential = $this->credentialRepository->find($filters['credentials']);
            $this->credential = [
                'id'              => $activeCredential->id,
                'shop_url'        => $activeCredential->shop_url,
                'email'           => $activeCredential->email,
                'password'        => $activeCredential->password,
                'store_info'      => $activeCredential->store_info,
                'additional_info' => $activeCredential->additional_info,
            ];

            Cache::put(CacheType::CREDENTIAL->value, $this->credential, Env('SESSION_LIFETIME'));
        }
    }

    protected function getCredential(): array
    {
        return $this->credential;
    }

    protected function getMappedLocales(): array
    {
        $locales = [];
        foreach ($this->credential['store_info'] as $storeInfo) {
            $data = json_decode($storeInfo, true);
            if (! empty($data) && isset($data['locales'])) {
                $locales[array_key_first($data['channel'])] = $data['locales'];
            }
        }

        return $locales;
    }

    protected function getMappedChannels(): array
    {
        $channel = [];
        foreach ($this->credential['store_info'] as $storeInfo) {
            $data = json_decode($storeInfo, true);
            if (! empty($data) && isset($data['channel'])) {
                $channel[array_key_first($data['channel'])] = $data['channel'][array_key_first($data['channel'])];
            }
        }

        return $channel;
    }
}
