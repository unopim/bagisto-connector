<?php

namespace Webkul\TVCMall\Helpers\Importers\Category;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\TVCMall\Repositories\ConfigurationRepository;
use Webkul\TVCMall\Services\OpenAPITVCMall;

class Importer extends AbstractImporter
{
    public const DEFAULT_LOCALE = 'en_US';

    protected $filters;

    protected $locale;

    protected $configuration;

    protected $permanentAttributes = [];

    public const BATCH_SIZE = 100;

    public function __construct(
        protected ConfigurationRepository $configurationRepository,
        protected CategoryRepository $categoryRepository,
        protected JobTrackBatchRepository $importBatchRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository,
        protected OpenAPITVCMall $openAPITVCMall,
    ) {

        $this->configuration = $this->configurationRepository->first();

        parent::__construct($importBatchRepository);
    }

    /**
     * Validate data
     */
    public function validateData(): void
    {
        $this->saveValidatedBatches();
    }

    /**
     * Save validated batches
     */
    protected function saveValidatedBatches(): self
    {
        $source = $this->getSource();

        $batchRows = [];

        $source->rewind();

        /**
         * Clean previous saved batches
         */
        $this->importBatchRepository->deleteWhere([
            'job_track_id' => $this->import->id,
        ]);

        while (
            $source->valid()
            || count($batchRows)
        ) {
            if (
                count($batchRows) == self::BATCH_SIZE
                || !$source->valid()
            ) {
                $this->importBatchRepository->create([
                    'job_track_id' => $this->import->id,
                    'data' => $batchRows,
                    'state' => Import::STATE_VALIDATING,
                ]);

                $this->processedRowsCount++;

                $batchRows = [];
            }

            if ($source->valid()) {
                $rowData = $source->current();

                if ($this->validateRow($rowData, 1)) {
                    $batchRows[] = $this->prepareRowForDb($rowData);
                }

                $source->next();
            }
        }

        return $this;
    }

    public function getSource()
    {
        $filters = $this->getFilters();

        return new \ArrayIterator($this->getCategories($filters));
    }

    /**
     * Initialize Filters
     */
    protected function getFilters(): array
    {
        if (!$this->filters) {
            $this->filters = $this->import->jobInstance->filters;
        }

        return $this->filters;
    }

    /**
     * Categories Getting by cursor
     */
    protected function getCategories(array $filters): array
    {
        $categories = $this->openAPITVCMall->getCategories($filters);

        return $categories;
    }

    /**
     * Validates row
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        return true;
    }

    /**
     * Start the import process for Category Import
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        $this->importCategoryData($batch);

        return true;
    }

    /**
     * save the category data
     */
    public function importCategoryData(JobTrackBatchContract $batch): bool
    {
        $collectionData = $batch->data;

        $categories = [];

        foreach ($collectionData as $rowData) {
            $this->prepareCategories($rowData, $categories);
        }

        $this->saveCategories($categories);

        /**
         * Update import batch summary
         */
        $batch = $this->importBatchRepository->update([
            'state' => Import::STATE_PROCESSED,
            'summary' => [
                'created' => $this->getCreatedItemsCount(),
                'updated' => $this->getUpdatedItemsCount(),
                'deleted' => $this->getDeletedItemsCount(),
            ],
        ], $batch->id);

        return true;
    }

    /**
     * Prepare categories for import
     *
     */
    public function prepareCategories(array $collection, array &$categories): void
    {
        $category = $this->categoryRepository->where('code', $collection['Code'])->first();

        $locale = $this->getLocale();

        $additionalData = [];

        foreach ($collection as $fieldCode => $value) {
            $fieldCode = Str::lower($fieldCode);

            $categoryField = $this->categoryFieldRepository->findOneByField('code', $fieldCode);

            if (!$categoryField) {
                continue;
            }

            switch ($categoryField->type) {
                case 'boolean':
                    $formattedValue = $value ? 'true' : 'false';
                    break;

                default:
                    $formattedValue = $value;
                    break;
            }

            if ($categoryField->value_per_locale) {
                $additionalData['additional_data']['locale_specific'][$locale][$fieldCode] = $formattedValue;
            } else {
                $additionalData['additional_data']['common'][$fieldCode] = $formattedValue;
            }
        }

        if ($additionalData) {
            $data = array_merge([
                'code' => $collection['Code'],
                'ParentCode' => $collection['ParentCode'],
            ], $additionalData);
        } else {
            $data = array_merge([
                'code' => $collection['Code'],
                'ParentCode' => $collection['ParentCode'],
            ]);
        }

        if ($category) {
            $categories['update'][$collection['Code']] = array_merge($categories['update'][$collection['Code']] ?? [], $data);
        } else {
            $categories['insert'][$collection['Code']] = array_merge($categories['insert'][$collection['Code']] ?? [], $data);
        }
    }

    /** Get local */
    protected function getLocale(): string
    {
        $filters = $this->getFilters();

        if (!$this->locale) {

            $this->locale = self::DEFAULT_LOCALE;

            if (isset($filters['locale'])) {
                $this->locale = $filters['locale'];
            }
        }

        return $this->locale;
    }

    public function getLocaleMapping()
    {
        $additional = json_decode($this->configuration?->additional, true);

        return $additional['localesMapping'] ?? [];
    }

    /**
     * Save categories from current batch
     */
    public function saveCategories(array $categories): void
    {
        /** single insert/update in the db because of parent  */
        if (!empty($categories['update'])) {
            $this->updatedItemsCount += count($categories['update']);
            foreach ($categories['update'] as $code => $category) {
                $category['parent_id'] = $this->getCategoryIdByCode($category['ParentCode']);

                $this->categoryRepository->update($category, $this->getCategoryIdByCode($code), withoutFormattingValues: true);
            }
        }

        if (!empty($categories['insert'])) {
            $this->createdItemsCount += count($categories['insert']);
            foreach ($categories['insert'] as $code => $category) {
                $category['parent_id'] = $this->getCategoryIdByCode($category['ParentCode']);

                $this->categoryRepository->create($category, withoutFormattingValues: true);
            }
        }
    }

    /**
     * Get category id by code
     * @param $code
     * @return mixed
     **/
    protected function getCategoryIdByCode($code)
    {
        return $this->categoryRepository->findOneByField('code', $code)?->id ?: 1;
    }
}
