<?php

namespace Webkul\Bagisto\Helpers\Exporters\AttributeFamily;

use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Bagisto\Enums\Services\MethodType;
use Webkul\Bagisto\Repositories\BagistoDataMapping;
use Webkul\Bagisto\Repositories\CredentialRepository;
use Webkul\Bagisto\Traits\ApiRequest as ApiRequestTrait;
use Webkul\Bagisto\Traits\Credential as CredentialTrait;
use Webkul\Bagisto\Traits\Mapping as MappingTrait;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends AbstractExporter
{
    use ApiRequestTrait;
    use CredentialTrait;
    use MappingTrait;

    public const BATCH_SIZE = 4;

    public const ENTITY_TYPE = 'attribute_family';

    /*
     * For exporting file
     */
    protected bool $exportsFile = false;

    /**
     * Current crenetial.
     *
     * @var array
     */
    protected $credential = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Create a new instance of the exporter.
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected BagistoDataMapping $bagistoDataMappingRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected AttributeFamilyRepository $AttributeFamilyRepository,
        protected CredentialRepository $credentialRepository
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Initializes the data for the export process.
     *
     * @return void
     */
    public function initialize()
    {
        $this->initializeCredential($this->getFilters());
    }

    /**
     * Start the export process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        $this->initialize();

        $preparedData = $this->prepareAttributeFamilies($batch, $filePath);

        $this->write($preparedData, $batch->id);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults()
    {
        $filters = $this->getFilters();
        $attributeFamilyCodes = $filters['code'] ?? null;

        return $attributeFamilyCodes
            ? $this->source->with(['familyGroups', 'attributeFamilyGroupMappings.customAttributes'])->whereIn('code', explode(',', $attributeFamilyCodes))->get()->getIterator()
            : $this->source->with(['familyGroups', 'attributeFamilyGroupMappings.customAttributes'])->all()->getIterator();
    }

    public function write($items, $batchId)
    {
        foreach ($items as $item) {
            $id = $item['id'];
            unset($item['id']);
            $mapData = $this->getMapping($this->credential['id'], $id);
            $method = $mapData ? MethodType::PUT->value : MethodType::POST->value;
            $option = $mapData ? ['id' => $mapData->external_id] : [];

            try {
                $response = $this->setApiRequest($method, self::ENTITY_TYPE, $item, $option);
            } catch (\Exception $e) {
                $this->jobLogger->warning($e);
            }

            if (isset($response['id'])) {
                $this->createdItemsCount++;
            } else {
                $this->skippedItemsCount++;
                $this->jobLogger->warning($item['code'].' '.trans('bagisto::app.bagisto.export.mapping.attributes.skipped').' '.json_encode($response).' '.trans('bagisto::app.bagisto.export.mapping.attributes.data').' '.json_encode($item));
            }

            $this->handleResponse($item, $response, $batchId, $mapData, $id);
        }
    }

    protected function handleResponse($item, $response, $batchId, $mapData, $id): void
    {
        if (! empty($response['groups'])) {
            $groupIds = ! empty($item['attribute_groups']) ? array_keys($item['attribute_groups']) : [];

            foreach ($response['groups'] as $key => $groups) {
                if (! $this->getMapping($this->credential['id'], null, $groups['id'], null, null, 'groups')) {
                    $this->setMapping($this->credential['id'], str_replace('group_', '', $groupIds[$key]), $groups['id'], $batchId, $groups['code'], 'groups');
                }
            }
        }

        if (! $mapData && isset($response['id'])) {
            $this->setMapping($this->credential['id'], $id, $response['id'], $batchId, $item['code']);
        }

        if (! $mapData && empty($response['id'])) {
            $bagsitoFamily = $this->setApiRequest(MethodType::GET->value, self::ENTITY_TYPE, ['pagination' => 0]);

            if (empty($bagsitoFamily)) {
                return;
            }

            foreach ($bagsitoFamily as $key => $family) {
                if ($family['code'] === $item['code']) {
                    $this->setMapping($this->credential['id'], $id, $family['id'], $batchId, $family['code']);

                    $bagistoGroups = array_combine(
                        array_column($family['groups'], 'id'),
                        array_column($family['groups'], 'code')
                    );
                    $attributeGroups = ! empty($item['attribute_groups']) ? array_keys($item['attribute_groups']) : [];

                    $unopimGroups = array_combine(
                        array_map(fn ($group) => (int) preg_replace('/[^0-9]/', '', $group), $attributeGroups),
                        array_column($item['attribute_groups'], 'code')
                    );

                    foreach ($bagistoGroups as $id => $groupCode) {
                        if (! $this->getMapping($this->credential['id'], null, $id, null, null, 'groups') && in_array($groupCode, $unopimGroups)) {
                            $this->setMapping($this->credential['id'], array_search($groupCode, $unopimGroups), $id, $batchId, $groupCode['code'], 'groups');
                        }
                    }
                }
            }
        }
    }

    /**
     * Prepare attribute Families from current batch
     */
    public function prepareAttributeFamilies(JobTrackBatchContract $batch, mixed $filePath)
    {
        $attributeFamilies = [];
        $filters = $this->getFilters();

        foreach ($batch->data as $rowData) {
            $attributeFamilies[] = $this->getCommonFields($rowData);
        }

        return $attributeFamilies;
    }

    protected function getCommonFields($item)
    {
        $formatData = [];
        $formatedAttribute = [];
        foreach ($item['family_groups'] as $familyGroup) {
            foreach ($item['attribute_family_group_mappings'] as $groupMapping) {
                if ($groupMapping['attribute_group_id'] === $familyGroup['id']) {
                    $attributeIds = [];
                    foreach ($groupMapping['custom_attributes'] as $key => $attribute) {
                        $mapData = $this->getMapping($this->credential['id'], $attribute['id'], null, null, null, 'attribute');
                        $attributeIds[$key]['id'] = $mapData->external_id ?? $attribute['id'];
                        $attributeIds[$key]['position'] = $attribute['pivot']['position'];
                    }
                    $mapData = $this->getMapping($this->credential['id'], $familyGroup['id'], null, null, null, 'groups');
                    $optionKey = $mapData ? $mapData->external_id : 'group_'.$familyGroup['id'];
                    $formatedAttribute[$optionKey] = [
                        'position'          => $groupMapping['position'],
                        'column'            => 1,
                        'name'              => $familyGroup['name'],
                        'code'              => $familyGroup['code'],
                        'custom_attributes' => $attributeIds,
                    ];
                }
            }
        }

        $formatData['id'] = $item['id'];
        $formatData['code'] = $item['code'];
        $formatData['name'] = $item['name'];
        $formatData['attribute_groups'] = $formatedAttribute;

        return $formatData;
    }
}
