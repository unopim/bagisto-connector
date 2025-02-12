<?php

namespace Webkul\BagistoPlugin\Helpers\Exporters\Category;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Webkul\BagistoPlugin\Enums\Export\CacheType;
use Webkul\BagistoPlugin\Enums\Services\MethodType;
use Webkul\BagistoPlugin\Repositories\BagistoDataMapping;
use Webkul\BagistoPlugin\Repositories\CategoryFieldMappingRepository;
use Webkul\BagistoPlugin\Repositories\CredentialRepository;
use Webkul\BagistoPlugin\Traits\ApiRequest as ApiRequestTrait;
use Webkul\BagistoPlugin\Traits\Credential as CredentialTrait;
use Webkul\BagistoPlugin\Traits\Mapping as MappingTrait;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Validator\FieldValidator;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Category\Exporter as BaseExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends BaseExporter
{
    use ApiRequestTrait;
    use CredentialTrait;
    use MappingTrait;

    public const BATCH_SIZE = 4;

    public const ENTITY_TYPE = 'category';

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
    protected $mappingFields = [];

    /**
     * @var array
     */
    protected $categoryFields = [];

    /**
     * Create a new instance of the exporter.
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected BagistoDataMapping $bagistoDataMappingRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected CategoryFieldMappingRepository $categoryFieldMappingRepository,
        protected CredentialRepository $credentialRepository
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer, $categoryFieldRepository);
    }

    /**
     * Initializes the data for the export process.
     *
     * @return void
     */
    public function initialize()
    {
        $this->initializeCredential($this->getFilters());

        $this->initializeCategoryFields();

        $this->initializeMappingFields();
    }

    /**
     * Initializes categoryFields for the export process.
     *
     * @return void
     */
    public function initializeCategoryFields()
    {
        $this->categoryFields = Cache::get(CacheType::UNOPIM_CATEGORY_FIELDS->value, []);
        if (empty($this->categoryFields)) {
            $this->categoryFields = $this->categoryFieldRepository->getActiveCategoryFields();

            Cache::put(CacheType::UNOPIM_CATEGORY_FIELDS->value, $this->categoryFields, Env('SESSION_LIFETIME'));
        }
    }

    /**
     * Initializes mappingField for the export process.
     *
     * @return void
     */
    public function initializeMappingFields()
    {
        $this->mappingFields = Cache::get(CacheType::CATEGORY_FIELD_MAPPING->value, []);
        if (empty($this->mappingFields)) {
            $this->mappingFields = [
                'standard_field' => $this->categoryFieldMappingRepository->findByField('section', 'standard_field')->first(),
            ];

            Cache::put(CacheType::CATEGORY_FIELD_MAPPING->value, $this->mappingFields, Env('SESSION_LIFETIME'));
        }
    }

    /**
     * Start the export process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        $this->initialize();

        $preparedData = $this->prepareCategories($batch, $filePath);

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
        if (isset($filters['code']) && $filters['code']) {
            $categoryCodes = explode(',', $filters['code']);

            return $this->source->whereIn('code', $categoryCodes)->with('parent_category')->get()?->getIterator();
        }

        return $this->source->with('parent_category')->all()?->getIterator();
    }

    public function write($items, $batchId)
    {
        foreach ($items as $item) {
            $id = $item['id'];
            unset($item['id']);

            $options = [];
            $mediaOptions = ['isMultipart' => true];
            if (isset($item['banner_path']) || isset($item['logo_path'])) {
                $mediaOptions['mediaCodes'] = [
                    'banner_path',
                    'logo_path',
                ];
            }

            $mapData = $this->getMapping($this->credential['id'], $id);
            if ($item['code'] === 'root' && ! $mapData) {
                $this->setMapping($this->credential['id'], $id, 1, $batchId);

                continue;
            }
            if ($mapData) {
                $item['_method'] = 'put';
                $item = $this->prepareCategoriesUpdataData($item);
                $externalId = ['id' => $mapData->external_id];
            } else {
                $externalId = [];
            }

            $options = array_merge($mediaOptions, $externalId);

            try {
                $response = $this->setApiRequest(MethodType::POST->value, self::ENTITY_TYPE, $item, $options);
            } catch (\Exception $e) {
                $this->jobLogger->warning($e);
            }
            if (isset($response['id'])) {
                $this->createdItemsCount++;
            } else {
                $this->skippedItemsCount++;
            }

            if (! $mapData && isset($response['id'])) {
                $this->setMapping($this->credential['id'], $id, $response['id'], $batchId);
            }
        }
    }

    /**
     * Prepare categories from current batch
     */
    public function prepareCategories(JobTrackBatchContract $batch, mixed $filePath)
    {
        $categories = [];
        $locales = core()->getAllActiveLocales()->pluck('code');
        $filters = $this->getFilters();
        if (! empty($filters['locale'])) {
            $filtersLocales = explode(',', $filters['locale']);
        }

        $bagistoLocales = $this->getMappedLocales();
        foreach ($batch->data as $rowData) {
            foreach ($filtersLocales as $locale) {
                if (! in_array($locale, $bagistoLocales[$filters['channel']])) {
                    continue;
                }

                $commonFields = $this->getCommonFields($rowData);
                $localeSpecificFields = $this->getLocaleSpecificFields($rowData, $locale);
                $mergedFields = array_merge($commonFields, $localeSpecificFields);
                $additionalData = $this->setFieldsAdditionalData($mergedFields, $filePath);
                $attributeIds = isset($this->credential['additional_info'][0]['filterableAttribtes']) ? $this->credential['additional_info'][0]['filterableAttribtes'] : null;
                $attributes = [];
                if ($attributeIds) {
                    $attributes = explode(',', $attributeIds);
                    foreach ($attributes as $key => $attribute) {
                        $additionalData["attributes[$key]"] = $attribute;
                    }
                }

                $data = array_merge([
                    'id'        => $rowData['id'],
                    'code'      => $rowData['code'],
                    'name'      => $rowData['name'] ?? null,
                    'locale'    => isset($bagistoLocales[$filters['channel']][$locale]) ? $bagistoLocales[$filters['channel']][$locale] : 'all',
                    'position'  => 1,
                ], $additionalData);

                if ($rowData['parent_category']) {
                    $data['parent_id'] = $this->getParentId($rowData);
                }

                $categories[] = $data;
            }
        }

        return $categories;
    }

    public function getParentId($item)
    {
        if (! empty($item['parent_category'])) {
            $mapData = $this->getMapping($this->credential['id'], $item['parent_category']['id']);

            return $mapData ? $mapData->external_id : 1;
        }

        return 1;
    }

    /**
     * Sets category field values for a product. If an category field is not present in the given values array,
     *
     * @param  array  $values
     * @return array
     */
    protected function setFieldsAdditionalData(array $additionalData, $filePath, $options = [])
    {
        $fieldValues = [];
        $standardFields = $this->mappingFields['standard_field']->mapped_value ?? [];
        $fixedValue = $this->mappingFields['standard_field']->fixed_value ?? [];

        foreach ($standardFields as $key => $mappingField) {
            $field = $this->categoryFieldRepository->where('code', $mappingField)->first();
            if (in_array($field->type, [FieldValidator::FILE_FIELD_TYPE, FieldValidator::IMAGE_FIELD_TYPE])) {
                $fileFullPath = $this->getExistingFilePath($field, $additionalData);
                if ($fileFullPath) {
                    $fieldValues[$key] = $fileFullPath;
                }

                continue;
            }

            if ($field->type === FieldValidator::BOOLEAN_FIELD_TYPE) {
                $additionalData[$mappingField] = isset($additionalData[$mappingField]) && $additionalData[$mappingField] ? 1 : 0;
            }

            $fieldValues[$key] = $additionalData[$mappingField] ?? null;
        }
        if (empty($standardFields)) {
            $fieldValues = array_merge($additionalData, $fixedValue);
        }
        $fieldValues = array_merge($fieldValues, $fixedValue);

        return $fieldValues;
    }

    public function prepareCategoriesUpdataData($item): array
    {
        foreach ($item as $key=> $value) {
            if (! empty($value)) {
                $item[sprintf('%s[%s]', $item['locale'], $key)] = $value;
            }
        }

        return $item;
    }

    protected function getExistingFilePath($field, $additionalData)
    {
        $existingFilePath = $additionalData[$field->code] ?? null;
        if ($existingFilePath && Storage::exists($existingFilePath)) {
            return Storage::path($existingFilePath);
        }

        return null;
    }
}
