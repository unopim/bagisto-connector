<?php

namespace Webkul\SunskyOnline\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\SunskyOnline\Models\Configuration;

class ConfigurationRepository extends Repository
{
    /**
     * {@inheritDoc}
     */
    public function model(): string
    {
        return Configuration::class;
    }

    /**
     * Update the configuration
     */
    public function updateConfiguration($baseUrl, $key, $secret, $additional = null)
    {
        $existingConfig = $this->findWhere(['baseUrl' => $baseUrl])->first();
        $data = ['baseUrl' => $baseUrl, 'key' => $key, 'secret' => $secret, 'additional' => json_encode($additional, true)];
        if ($existingConfig) {
            $this->update($data, $existingConfig->id);
        } else {
            $this->create($data);
        }
    }

    /**
     * configuration of first data
     *
     **/
    public function getConfiguration()
    {
        return $this->first();
    }
}
