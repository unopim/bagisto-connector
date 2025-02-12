<?php

namespace Webkul\SunskyOnline\Helpers\Importers\Category;

use Illuminate\Support\Arr;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\Category\Storage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\SunskyOnline\Repositories\ConfigurationRepository;
use Webkul\SunskyOnline\Services\OpenApiClient;

class Importer extends AbstractImporter
{
    public const DEFAULT_LOCALE = 'en_US';

    protected $filters;

    protected $locale;

    protected $configuration;

    public function __construct(
        protected OpenApiClient $openApiClient,
        protected ConfigurationRepository $configurationRepository,
        protected Storage $categoryStorage,
        protected CategoryRepository $categoryRepository,
        protected JobTrackBatchRepository $importBatchRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository,
    ) {

        $this->configuration = $this->configurationRepository->getConfiguration();

        $this->openApiClient->setCredentials($this->configuration?->key, $this->configuration?->secret, $this->configuration?->baseUrl)->validate();

        parent::__construct($importBatchRepository);
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
                || ! $source->valid()
            ) {
                $this->importBatchRepository->create([
                    'job_track_id' => $this->import->id,
                    'data'         => $batchRows,
                ]);

                $batchRows = [];
            }

            if ($source->valid()) {
                $rowData = $source->current();

                if ($this->validateRow($rowData, 1)) {
                    $batchRows[] = $this->prepareRowForDb($rowData);
                }

                $this->processedRowsCount++;

                $source->next();
            }
        }

        return $this;
    }

    /**
     * Initialize Filters
     */
    protected function getFilters(): array
    {
        if (! $this->filters) {
            $this->filters = $this->import->jobInstance->filters;
        }

        return $this->filters;
    }

    /** Get local */
    protected function getLocale(): string
    {
        $filters = $this->getFilters();

        if (! $this->locale) {

            $this->locale = self::DEFAULT_LOCALE;

            if (isset($filters['locale'])) {
                $this->locale = $filters['locale'];
            }
        }

        return $this->locale;
    }

    /**
     * Source for import
     */
    public function getSource()
    {
        $this->categoryStorage->init();

        $filters = $this->getFilters();

        return new \ArrayIterator($this->getCategories($filters));
    }

    /**
     * Categories Getting by cursor
     */
    public function getCategories(array $filters): array
    {
        $collections = [];

        $gmtModifiedStart = $filters['gmtModifiedStart'] ?? null;

        $locale = null;
        if (isset($filters['locale'])) {
            $localMapping = $this->getLocaleMapping($filters['locale']);

            $locale = $localMapping[$filters['locale']] ?? null;
        }

        // Fetch root categories, parentID = 0 mean to fetch all parent categories
        $rootCategories = $this->openApiClient->getCategories(0, $gmtModifiedStart, $locale);
        if (is_array($rootCategories) && ! empty($rootCategories) && isset($rootCategories[0]['id'])) {
            $collections = array_merge($collections, $rootCategories);
        }

        // Fetch child categories parentID = 0 mean to fetch all categories
        $childCategories = $this->openApiClient->getCategories(null, $gmtModifiedStart, $locale);
        if (is_array($childCategories) && ! empty($childCategories) && isset($childCategories[0]['id'])) {
            $collections = array_merge($collections, $childCategories);
        }

        return $collections;
    }

    /**
     * save the category data
     */
    public function importCategoryData(JobTrackBatchContract $batch): bool
    {
        $collectionData = $batch->data;

        $this->categoryStorage->load(Arr::pluck($collectionData, 'id'));

        $categories = [];
        foreach ($batch->data as $rowData) {
            $this->prepareCategories($rowData, $categories);
        }

        $this->saveCategories($categories);

        /**
         * Update import batch summary
         */
        $batch = $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
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
     */
    public function prepareCategories(array $collection, array &$categories): void
    {
        $existingCategory = $this->categoryRepository->where('code', $collection['id'])->first();
        $parentCategory = isset($collection['parentId']) ? $this->categoryRepository->where('code', $collection['parentId'])->first() : null;

        $data = [
            'code'            => $collection['id'],
            'parent_id'       => $parentCategory?->id ?? 1,
            'additional_data' => $existingCategory ? $existingCategory->additional_data : [],
        ];

        $locale = $this->getLocale();

        foreach ($collection as $fieldCode => $value) {
            if ($fieldCode === 'code') {
                $fieldCode = 'code_';
            }

            $categoryField = $this->categoryFieldRepository->findOneByField('code', $fieldCode);
            if (! $categoryField) {
                continue;
            }

            switch ($categoryField->type) {
                case 'boolean':
                    $formattedValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($formattedValue === null) {
                        continue;
                    }
                    $formattedValue = $formattedValue ? 'true' : 'false';
                    break;

                default:
                    $formattedValue = $value;
                    break;
            }

            if ($categoryField->value_per_locale) {
                $data['additional_data']['locale_specific'][$locale][$fieldCode] = $formattedValue;
            } else {
                $data['additional_data']['common'][$fieldCode] = $formattedValue;
            }
        }

        if ($existingCategory) {
            $categories['update'][$collection['id']] = array_merge($categories['update'][$collection['id']] ?? [], $data);
        } else {
            $categories['insert'][$collection['id']] = array_merge($categories['insert'][$collection['id']] ?? [], $data);
        }
    }

    /**
     * Validates row
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        return true;
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
        if (! empty($categories['update'])) {
            $this->updatedItemsCount += count($categories['update']);
            foreach ($categories['update'] as $code => $category) {
                $this->categoryRepository->update($category, $this->categoryStorage->get($code), withoutFormattingValues: true);
            }
        }

        if (! empty($categories['insert'])) {
            $this->createdItemsCount += count($categories['insert']);
            foreach ($categories['insert'] as $code => $category) {
                $newCategory = $this->categoryRepository->create($category, withoutFormattingValues: true);
                if ($newCategory) {
                    $this->categoryStorage->set($code, $newCategory?->id);
                }
            }
        }
    }
}
