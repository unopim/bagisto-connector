<?php

namespace Webkul\SunskyOnline\Helpers\Importers\Product;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\Product\SKUStorage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\SunskyOnline\Helpers\Iterators\ProductIterator;
use Webkul\SunskyOnline\Repositories\AttributeMappingRepository;
use Webkul\SunskyOnline\Repositories\ConfigurationRepository;
use Webkul\SunskyOnline\Services\OpenApiClient;

class Importer extends AbstractImporter
{
    public const DEFAULT_LOCALE = 'en_US';

    public const DEFAULT_CHANNEL = 'default';

    public const DEFAULT_PRODUCT_TYPE = 'simple';

    public const CONFIGURABLE_PRODUCT_TYPE = 'configurable';

    public const DEFAULT_ATTRIBUTE_FAMILY_ID = 1;

    public const BATCH_SIZE = 100;

    public const STANDARD_MAPPING_SECTION = 'standard_attribute';

    /**
     * Permanent entity column
     */
    protected string $masterAttributeCode = 'sku';

    protected $filters;

    /**
     * Cached attributes
     */
    protected mixed $attributes = [];

    protected $locale;

    protected $channel;

    protected $configuration;

    protected $mappingValues;

    public function __construct(
        protected OpenApiClient $openApiClient,
        protected ConfigurationRepository $configurationRepository,
        protected SKUStorage $productSkuStorage,
        protected ProductRepository $productRepository,
        protected JobTrackBatchRepository $importBatchRepository,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository,
        protected AttributeMappingRepository $attributeMappingRepository,
        protected AttributeRepository $attributeRepository,
        protected FileStorer $fileStorer,
    ) {

        $this->configuration = $this->configurationRepository->getConfiguration();

        $this->openApiClient->setCredentials($this->configuration?->key, $this->configuration?->secret, $this->configuration?->baseUrl);

        parent::__construct($importBatchRepository);

        $this->initAttributes();
    }

    /**
     * Load all attributes and families to use later
     */
    protected function initAttributes(): void
    {
        $this->attributes = $this->attributeRepository->all();
    }

    /**
     * Validate data
     */
    public function validateData(): void
    {
        $this->saveAndPrepareBatchData();
    }

    /**
     * Save validated batches
     */
    protected function saveAndPrepareBatchData(): self
    {
        $source = $this->getSource();

        $batchRows = [];

        /** Need to implement the is resume option currently its enabled as static */
        if (true || $this->import->isResuming()) {
            $lastBatch = $this->importBatchRepository->findWhere([
                'job_track_id' => $this->import->id,
            ])->last();

            if ($lastBatch) {
                $source->setPage($lastBatch->summary['page'] ?? 1);
            }
        } else {
            $this->importBatchRepository->deleteWhere([
                'job_track_id' => $this->import->id,
            ]);
        }

        while ((
            $source->valid()
            || count($batchRows)
        )
        ) {

            if ($source->valid()) {
                $rowData = $source->current();

                if ($this->validateRow($rowData, 1)) {
                    $batchRows[] = $this->prepareRowForDb($rowData);
                }

                $this->processedRowsCount++;

                $source->next();
            }

            if (
                count($batchRows) == self::BATCH_SIZE
                || ! $source->valid()
            ) {

                $this->importBatchRepository->create([
                    'job_track_id'      => $this->import->id,
                    'data'              => $batchRows,
                    'state'             => Import::STATE_VALIDATING,
                    'summary'           => [
                        'page'    => $source->page(),
                    ],
                ]);

                $batchRows = [];
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

    /**
     * Initialize Filter
     */
    protected function getFilter(string $filter)
    {
        $filters = $this->getFilters();

        return $filters[$filter] ?? null;
    }

    /**
     * Get local
     *
     **/
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

    /** Get channel */
    protected function getChannel(): string
    {
        $filters = $this->getFilters();

        if (! $this->channel) {

            $this->channel = self::DEFAULT_CHANNEL;

            if (isset($filters['channel'])) {
                $this->channel = $filters['channel'];
            }
        }

        return $this->channel;
    }

    /**
     *  GET source
     */
    public function getSource()
    {
        $this->productSkuStorage->init();

        $filters = $this->getFilters();

        return $this->getProducts($filters);
    }

    /**
     * Products Getting by cursor
     */
    public function getProducts(array $filters): ProductIterator
    {
        return new ProductIterator($this->openApiClient, $filters);
    }

    /**
     * save the data
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        $this->saveProductsData($batch);

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

        Event::dispatch('data_transfer.imports.batch.import.after', $batch);

        return true;
    }

    /**
     * Save products data
     */
    public function saveProductsData(JobTrackBatchContract $batch): void
    {
        $products = [];

        $imagesData = [];

        $superAttributes = [];

        $configurableProducts = [];

        $this->productSkuStorage->load(Arr::pluck($batch->data, 'itemNo'));

        foreach ($batch->data as $rowData) {
            $this->prepareConfigurableProducts($rowData, $configurableProducts, $superAttributes);

            $this->prepareProducts($rowData, $products);

        }

        $this->saveProducts($configurableProducts);

        $this->prepareProductIdInSuperAttributes($superAttributes);

        $this->prepareParentIdInProducts($products, $superAttributes);

        $this->saveProducts($products);

    }

    /**
     * Check if SKU exists
     */
    public function isSKUExist(string $sku): bool
    {
        return $this->productSkuStorage->has($sku);
    }

    /**
     * Prepare products for import
     */
    public function prepareConfigurableProducts(array $product, array &$configurableProducts, array &$superAttributes): void
    {
        if (! isset($product['itemNo'])) {
            return;
        }

        if ($product['itemNo'] !== $product['groupItemNo'] && isset($product['modelList']) && $product['itemNo'] === $product['modelList'][0]['key']) {
            $product['itemNo'] = $product['groupItemNo'];
            $product['name'] = preg_replace('/\s*\(.*?\)$/', '', $product['name']);
            $productSuperAttributes = explode(',', $product['modelLabel']);

            foreach ($productSuperAttributes as $attribute) {
                $attribute = trim($attribute);
                $attributeId = $this->attributes->where('code', $attribute)->first()?->id;
                $superAttributes[$product['itemNo']]['attribute_id'] = $attributeId;
            }
            $this->productSkuStorage->load([$product['groupItemNo']]);
            $this->prepareProducts($product, $configurableProducts, 'configurable');
        }
    }

    /**
     * Prepare products for import
     */
    public function prepareProducts(array $product, array &$products, $productType = self::DEFAULT_PRODUCT_TYPE): void
    {
        if (! isset($product['itemNo'])) {
            return;
        }

        if ($this->getFilter('in_detailed')) {
            $product = $this->apiClient->getProductDetail($product['itemNo']);
        }

        $isExistingProduct = $this->isSKUExist($product['itemNo']);

        if ($this->getFilter('is_skip_already_exist') && $isExistingProduct) {
            return;
        }

        $locale = $this->getLocale();

        $channel = $this->getChannel();

        $data = [
            'sku'                   => $product['itemNo'],
            'type'                  => $productType,
            'attribute_family_id'   => self::DEFAULT_ATTRIBUTE_FAMILY_ID,
            'values'                => [
                'categories'            => [
                    (string) $product['categoryId'] ?? null,
                ],
                'common'           => $this->getFormattedData($product, 'common'),
                'channel_specific' => [
                    $channel => $this->getFormattedData($product, 'channel_specific'),
                ],
                'locale_specific'  => [
                    $locale => $this->getFormattedData($product, 'locale_specific'),
                ],
                'channel_locale_specific' => [
                    $channel => [
                        $locale => $this->getFormattedData($product, 'channel_locale_specific'),
                    ],
                ],
            ],
        ];

        if ($this->getFilter('with_media') && $isExistingProduct) {
            $this->fetchMedia($product, $data);
        }

        $data['values'] = json_encode($data['values'], true);

        if ($data['type'] === self::DEFAULT_PRODUCT_TYPE) {
            $data['parent_sku'] = $product['itemNo'] !== $product['groupItemNo'] ? $product['groupItemNo'] : null;
        }


        if ($isExistingProduct) {
            $products['update'][$product['itemNo']] = array_merge($products['update'][$product['itemNo']] ?? [], $data);
        } else {
            $products['insert'][$product['itemNo']] = array_merge($products['insert'][$product['itemNo']] ?? [], $data);
        }

        if ($product['itemNo'] !== $product['groupItemNo'] && isset($product['modelList']) && $product['itemNo'] === $product['modelList'][0]['key']) {
            $product['itemNo'] = $product['groupItemNo'];
            $product['name'] = preg_replace('/\s*\(.*?\)$/', '', $product['name']);
            $superAttributes = explode(',', $product['modelLabel']);

            foreach ($superAttributes as $attribute) {
                $attribute = trim($attribute);
                $attributeCode = $this->attributes->where('code', $attribute)->first()?->code;
                $product['super_attributes'][] = $attributeCode;
            }
        }
    }

    /**
     * prepare product Id in superAttributes
     */
    public function prepareProductIdInSuperAttributes(array &$superAttributes)
    {
        foreach($superAttributes as $itemNo => $attributeCode) {
            $existingProduct = $this->productSkuStorage->get($itemNo);
            $superAttributes[$itemNo]['product_id'] = $existingProduct ? $existingProduct['id'] : null;
        }
    }

    /**
     * prepare parent Id in products
     */
    public function prepareParentIdInProducts(array &$products, array $superAttributes)
    {
        foreach($products as $index => $indexProducts) {
            foreach($indexProducts as $productSku => $values) {
                $parentSku = $values['parent_sku'] ?? '';
                unset($values['parent_sku']);
                $existingProduct = $this->productSkuStorage->get($parentSku);
                $values['parent_id'] = $existingProduct ? $existingProduct['id'] : null;
                if (isset($superAttributes[$productSku])) {
                    $values['super_attributes'] = $superAttributes[$productSku];
                }
                $indexProducts[$productSku] = $values;
                $products[$index] = $indexProducts;
            }
        }
    }

    /**
     * Fetch media from product api and download
     *
     */
    public function fetchMedia(array $product, array &$data): void
    {
        $itemNo = $product['itemNo'] ?? null;

        if (! $itemNo) {
            return;
        }

        $media = $this->openApiClient->getProductMedia($itemNo);

        $images = [];
        $imageAttributeCode = 'gallery';

        if ($media) {
            $images = $this->getImagesFromZIP($media, $itemNo, $imageAttributeCode);
        }

        if (! empty($images)) {
            $data['values']['common'][$imageAttributeCode] = $images;
        }
    }

    /**
     * Save images from current batch
     */
    public function getImagesFromZIP(string $imageZipPath, $productId, $attributeCode): array
    {
        if (! str_ends_with($imageZipPath, '.zip')) {
            return [];
        }

        $productImages = [];
        $directoryPath = 'product'.DIRECTORY_SEPARATOR.$productId.DIRECTORY_SEPARATOR.$attributeCode;
        $zip = new \ZipArchive;
        $tempFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.config('app.name').DIRECTORY_SEPARATOR.'img'.uniqid();
        if (! file_exists(dirname($tempFilePath))) {
            mkdir(dirname($tempFilePath), 0777, true);
        }

        if ($zip->open($imageZipPath) === true) {

            $zip->extractTo($tempFilePath);
            $zip->close();

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tempFilePath));

            foreach ($files as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $filePath = $file->getRealPath();
                $fileName = $file->getFilename();
                $sku = pathinfo($fileName, PATHINFO_FILENAME);

                $file = new UploadedFile(
                    $filePath,
                    $fileName,
                    mime_content_type($filePath),
                    null,
                    true
                );

                $productImages[] = $this->fileStorer->store($directoryPath, $file, [FileStorer::HASHED_FOLDER_NAME_KEY => true]);
            }
        }

        return $productImages;
    }

    /**
     * Generate common value
     */
    public function getFormattedData($product, $mappingType)
    {
        $mapping = $this->getMapping()[$mappingType] ?? [];
        $fixedValue = $this->getMapping()['fixedValue'] ?? [];
        $data = [];

        foreach ($mapping as $sunskyOnlineField => $unopimAttributeCode) {

            if (isset($fixedValue[$sunskyOnlineField])) {
                $data[$unopimAttributeCode] = $fixedValue[$sunskyOnlineField];

                continue;
            }

            if (! isset($product[$sunskyOnlineField])) {
                continue;
            }

            switch ($sunskyOnlineField) {
                case 'priceList':
                    $values = array_column($product[$sunskyOnlineField], 'value');
                    $data[$unopimAttributeCode]['USD'] = min($values);
                    break;

                case 'status':
                    $data[$unopimAttributeCode] = $product[$sunskyOnlineField] === 1 ? 'true' : 'false';
                    break;

                case 'price':
                    $data[$unopimAttributeCode]['USD'] = $product[$sunskyOnlineField];
                    break;

                case 'gmtModified':
                case 'gmtListed':
                    $data[$unopimAttributeCode] = date('Y-m-d H:i:s', strtotime($product[$sunskyOnlineField]));
                    break;

                default:
                    $data[$unopimAttributeCode] = $product[$sunskyOnlineField];
                    break;
            }
        }

        return $data;
    }

    public function getMapping(): array
    {
        if (! $this->mappingValues) {

            $mapping = $this->attributeMappingRepository->getMappingBySection(self::STANDARD_MAPPING_SECTION) ?? [];

            $mappedValue = array_flip($mapping['mapped_value']) ?? [];

            $fixedValue = $mapping['fixed_value'] ?? [];

            $attributeCodes = array_keys($mappedValue);

            $commonValues = [];
            $channelSpecificValue = [];
            $localeSpecificValue = [];
            $channelLocaleSpecificValue = [];

            $attributes = $this->attributeRepository->whereIn('code', $attributeCodes)->get()->keyBy('code');

            foreach ($attributes as $attributeCode => $attribute) {
                $isValuePerLocale = $attribute->value_per_locale;
                $isValuePerChannel = $attribute->value_per_channel;

                $this->categorizeMappedValue(
                    (bool) $isValuePerLocale,
                    (bool) $isValuePerChannel,
                    $attributeCode,
                    $mappedValue,
                    $commonValues,
                    $channelSpecificValue,
                    $localeSpecificValue,
                    $channelLocaleSpecificValue
                );
            }

            $this->mappingValues = [
                'common'                  => $commonValues,
                'channel_specific'        => $channelSpecificValue,
                'locale_specific'         => $localeSpecificValue,
                'channel_locale_specific' => $channelLocaleSpecificValue,
                'fixedValue'              => $fixedValue,
            ];
        }

        return $this->mappingValues;
    }

    /**
     * Helper function to categorize mapped values based on locale and channel configuration
     */
    private function categorizeMappedValue(
        bool $isValuePerLocale,
        bool $isValuePerChannel,
        string $attributeCode,
        array $mappedValue,
        array &$commonValues,
        array &$channelSpecificValue,
        array &$localeSpecificValue,
        array &$channelLocaleSpecificValue
    ): void {

        $mappedValueForAttribute = $mappedValue[$attributeCode] ?? null;

        if (! $isValuePerLocale && ! $isValuePerChannel) {
            $commonValues[$mappedValueForAttribute] = $attributeCode;
        } elseif (! $isValuePerLocale && $isValuePerChannel) {
            $channelSpecificValue[$mappedValueForAttribute] = $attributeCode;
        } elseif ($isValuePerLocale && ! $isValuePerChannel) {
            $localeSpecificValue[$mappedValueForAttribute] = $attributeCode;
        } elseif ($isValuePerLocale && $isValuePerChannel) {
            $channelLocaleSpecificValue[$mappedValueForAttribute] = $attributeCode;
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
     * Save Products from current batch
     */
    public function saveProducts(array $products): void
    {
        /** single insert/update in the db because of parent  */
        if (! empty($products['update'])) {
            $this->updatedItemsCount += count($products['update']);
            foreach ($products['update'] as $code => $product) {
                $this->productRepository->upsert(
                    $products['update'],
                    $this->masterAttributeCode
                );
            }
        }

        if (! empty($products['insert'])) {
            $this->createdItemsCount += count($products['insert']);
            $this->productRepository->insert($products['insert']);

            /**
             * Update the sku storage with newly created products
             */
            $newProducts = $this->productRepository->findWhereIn(
                'sku',
                array_keys($products['insert']),
                [
                    'id',
                    'type',
                    'sku',
                    'attribute_family_id',
                ]
            );
            foreach ($newProducts as $product) {
                $this->productSkuStorage->set($product->sku, [
                    'id'                  => $product->id,
                    'type'                => $product->type,
                    'attribute_family_id' => $product->attribute_family_id,
                ]);
            }
        }
    }

     /**
     * Save Product Super Attributes from current batch
     */
    public function saveProductSuperAttributes(array $superAttributes): void
    {
        if (! empty($products['insert'])) {
            $this->createdItemsCount += count($products['insert']);
            $this->productRepository->insert($products['insert']);

            /**
             * Update the sku storage with newly created products
             */
            $newProducts = $this->productRepository->findWhereIn(
                'sku',
                array_keys($products['insert']),
                [
                    'id',
                    'type',
                    'sku',
                    'attribute_family_id',
                ]
            );
            foreach ($newProducts as $product) {
                $this->productSkuStorage->set($product->sku, [
                    'id'                  => $product->id,
                    'type'                => $product->type,
                    'attribute_family_id' => $product->attribute_family_id,
                ]);
            }
        }
    }

}
