<?php

namespace Webkul\Bagisto\Traits;

trait Mapping
{
    /**
     * Initializes Mapping for the export process.
     *
     * @return void
     */
    protected function setMapping(string|int $credentialId, string|int $relatedId, string|int $externalId, string|int $batchId, string $code = null, $entityType = self::ENTITY_TYPE)
    {
        $mapping = $this->getMapping($credentialId, $relatedId, null, null, null, $entityType);
        if (! $mapping) {
            $response = $this->bagistoDataMappingRepository->create([
                'related_id'      => $relatedId,
                'external_id'     => $externalId,
                'code'            => $code,
                'entity_type'     => $entityType,
                'job_instance_id' => $batchId,
                'credential_id'   => $credentialId,
            ]);
        } else {
            $response = $this->bagistoDataMappingRepository->update([
                'external_id'     => $externalId,
                'code'            => $code,
                'job_instance_id' => $batchId,
            ], $mapping->id);
        }

        return $response;
    }

    /**
     * Dynamically get the mapping based on available parameters.
     */
    protected function getMapping(string|int|null $credentialId = null, string|int|null $relatedId = null, string|int|null $externalId = null, string|null $code = null, string|int|null $batchId = null, $entityType = self::ENTITY_TYPE, $type = 'first')
    {
        $query = $this->bagistoDataMappingRepository->where('entity_type', $entityType);

        if ($relatedId) {
            $query->where('related_id', $relatedId);
        }

        if ($externalId) {
            $query->where('external_id', $externalId);
        }

        if ($code) {
            $query->where('code', $code);
        }

        if ($batchId) {
            $query->where('job_instance_id', $batchId);
        }

        if ($credentialId) {
            $query->where('credential_id', $credentialId);
        }

        if ($type === 'get') {
            return $query->get();
        }

        return $query->first();
    }
}
