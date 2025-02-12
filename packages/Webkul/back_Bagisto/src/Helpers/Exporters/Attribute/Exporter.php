<?php

namespace Webkul\Bagisto\Helpers\Exporters\Attribute;

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

    public const ENTITY_TYPE = 'attribute';

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

        $preparedData = $this->prepareAttributes($batch, $filePath);

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
        $attributeCodes = $filters['code'] ?? null;

        return $attributeCodes
            ? $this->source->with('options')->whereIn('code', explode(',', $attributeCodes))->get()->getIterator()
            : $this->source->with('options')->all()->getIterator();
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

                continue;
            }

            if (isset($response['id'])) {
                $this->createdItemsCount++;
            } else {
                $this->createMappingAlreadyExistAttributes($response, $item, $mapData, $batchId, $id);
                $this->skippedItemsCount++;
            }

            $this->handleOptions($item, $response, $batchId, $mapData, $id);
        }
    }

    private function handleOptions(array $item, ?array $response, int $batchId, $mapData, int $id): void
    {
        if (! empty($response['options']) && in_array($response['type'], ['select', 'multiselect', 'checkbox'])) {
            $optionIds = ! empty($item['options']) ? array_keys($item['options']) : [];

            foreach ($response['options'] as $key => $option) {
                if (isset($option['code']) && ! $this->getMapping($this->credential['id'], null, $option['id'], null, null, 'option')) {
                    $this->setMapping($this->credential['id'], str_replace('option_', '', $optionIds[$key]), $option['id'], $batchId, $option['code'], 'option');
                }
            }
        }

        if (! $mapData && isset($response['id'])) {
            $this->setMapping($this->credential['id'], $id, $response['id'], $batchId, $item['code']);
        }
    }

    private function createMappingAlreadyExistAttributes($response, $item, $mapData, $batchId, $id): void
    {
        if ($mapData || ! empty($response)) {
            return;
        }

        $response = $this->setApiRequest(MethodType::GET->value, self::ENTITY_TYPE, ['pagination' => 0]);
        if (empty($response)) {
            return;
        }

        $attributes = array_column($response, 'code');
        if (! in_array($item['code'], $attributes)) {
            return;
        }

        $arrayKey = array_search($item['code'], $attributes);
        $itemData = $response[$arrayKey];

        if (in_array($itemData['type'], ['select', 'multiselect', 'checkbox'])) {
            $this->setMapping($this->credential['id'], $id, $itemData['id'], $batchId, $item['code']);

            
            foreach ($itemData['options'] as $value) {
                $key = null;

                if (is_array($item) && is_array($item['options']) && isset($value['code']) ) {
                    foreach ($item['options'] as $optionKey => $option) {
                        if ($option['admin_name'] === $value['admin_name']) {
                            $key = $optionKey;
                            break;
                        }
                    }
                    $mappedKey = $key ? str_replace('option_', '', $key) : null;
                    $this->setMapping($this->credential['id'], $mappedKey, $value['id'], $batchId, $value['code'], 'option');
                }
            }
        } else {
            $this->setMapping($this->credential['id'], $id, $itemData['id'], $batchId, $item['code']);
        }
    }

    /**
     * Prepare attributes from current batch
     */
    public function prepareAttributes(JobTrackBatchContract $batch, mixed $filePath)
    {
        $attributes = [];
        $filters = $this->getFilters();
        $bagistoLocales = $this->getMappedLocales();

        foreach ($batch->data as $rowData) {
            $attributes[] = $this->getCommonFields($rowData, $bagistoLocales[$filters['channel']]);
        }

        return $attributes;
    }

    protected function getCommonFields($item, $locale)
    {
        $locale = array_flip($locale);
        unset($item['created_at'], $item['updated_at']);
        $item = array_filter($item);

        if ($item['type'] === 'gallery') {
            $item['type'] = 'image';
        }

        if (isset($item['translations'])) {
            foreach ($item['translations'] as $translation) {
                if (isset($locale[$translation['locale']])) {
                    $bagistoLocal = $locale[$translation['locale']];
                    $item[$bagistoLocal]['name'] = $translation['name'];
                }
            }
        }

        if (in_array($item['type'], ['select', 'multiselect', 'checkbox'])) {
            $this->processOptions($item, $locale);
            if ($item['type'] === 'select') {
                $item['is_configurable'] = true;
            }
        }

        if (! empty($item['validation']) && $item['validation'] == 'number') {
            $item['validation'] = 'numeric';
        }

        $item['is_required'] = $item['is_required'] ?? 0;
        $item['admin_name'] = $item['name'] ?? $item['code'];
        unset($item['translations']);

        return $item;
    }

    private function processOptions(array &$item, array $locale): void
    {
        if (! empty($item['options'])) {
            $data = [];
            foreach ($item['options'] as $key => $option) {
                $mapData = $this->getMapping($this->credential['id'], $option['id'], null, null, null, 'option');
                $optionKey = $mapData ? $mapData->external_id : 'option_'.$option['id'];
                $data[$optionKey] = [
                    'swatch_value' => $option['swatch_value'],
                    'isNew'        => ! $mapData,
                    'isDelete'     => false,
                    'admin_name'   => $option['code'],
                    'sort_order'   => $option['sort_order'],
                ];

                foreach ($option['translations'] as $translation) {
                    if (isset($locale[$translation['locale']])) {
                        $bagistoLocal = $locale[$translation['locale']];
                        $data[$optionKey][$bagistoLocal]['label'] = $translation['label'];
                    }
                }
            }

            $item['options'] = ! empty($data) ? $data : null;
        }
    }
}
