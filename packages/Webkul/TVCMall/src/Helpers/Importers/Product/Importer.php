<?php

namespace Webkul\TVCMall\Helpers\Importers\Product;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Webkul\Attribute\Repositories\attributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\Product\SKUStorage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\TVCMall\Helpers\Iterators\ProductIterator;
use Webkul\TVCMall\Repositories\ConfigurationRepository;
use Webkul\TVCMall\Repositories\ProductAttributeMappingRepository;
use Webkul\TVCMall\Services\OpenAPITVCMall;
use Webkul\Core\Filesystem\FileStorer;

class Importer extends AbstractImporter
{
    protected const DEFAULT_LOCALE = 'en_US';

    protected const DEFAULT_CURRENCY = 'USD';

    protected const DEFAULT_CHANNEL = 'default';

    protected $filters;

    protected $locale;

    protected $configuration;

    protected $permanentAttributes = [];

    public const BATCH_SIZE = 100;

    protected $lastProductId = 0;

    private $attributeFamilyId = 16;

    private $superAttributeColorId = 23;

    private $price = false;

    private $attributes;

    private $attributeMapping;

    private $tempDirectory;

    protected string $masterAttributeCode = 'sku';

    public function __construct(
        protected ConfigurationRepository $configurationRepository,
        protected SKUStorage $productSkuStorage,
        protected CategoryRepository $categoryRepository,
        protected ProductRepository $productRepository,
        protected JobTrackBatchRepository $importBatchRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository,
        protected OpenAPITVCMall $openAPITVCMall,
        protected ProductAttributeMappingRepository $productAttributeMappingRepository,
        protected FileStorer $fileStorer
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

        while (
            $source->valid()
            || count($batchRows)
        ) {
            $rowData = $source->current();

            if ($rowData) {
                foreach ($rowData as $row) {
                    if ($this->validateRow($row, 1)) {
                        $batchRows[] = $this->prepareRowForDb($row);
                    }
                }

                $source->next();
            }

            if (
                count($batchRows) == self::BATCH_SIZE
                || !$source->valid()
            ) {
                $this->importBatchRepository->create([
                    'job_track_id' => $this->import->id,
                    'data' => $batchRows,
                    'state' => Import::STATE_VALIDATING,
                    'summary' => [
                        'lastProductId' => $source->getLastProductId(),
                    ],
                ]);

                $this->processedRowsCount++;

                $batchRows = [];
            }
        }

        return $this;
    }

    public function getSource()
    {
        $filters = $this->getFilters();

        $iterator = new ProductIterator($this->openAPITVCMall, $filters);

        return $iterator;
    }

    /**
     * Initialize Filters
     */
    protected function getFilters(): array
    {
        if (!$this->filters) {
            $this->filters = $this->import->jobInstance->filters;
        }

        $lastBatch = $this->importBatchRepository->findWhere([
            'job_track_id' => $this->import->id,
        ])->last();

        if ($lastBatch) {
            $this->filters['lastProductId'] = $lastBatch->summary['lastProductId'];
        }

        return $this->filters;
    }

    /**
     * Products Getting by cursor
     */
    protected function getProducts(array $filters): array
    {
        $products = $this->openAPITVCMall->getProducts($filters);

        return $products;
    }

    /**
     * Validates row
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        return true;
    }

    /**
     * Start the import process for Product Import
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        echo "Batch Started at " . date('Y-m-d H:i:s') . PHP_EOL;
        $this->importProductData($batch);

        return true;
    }

    /**
     * save the Product data
     */
    public function importProductData(JobTrackBatchContract $batch): bool
    {
        $collectionData = [];

        foreach ($batch->data as $product) {
            $productDetail = $this->openAPITVCMall->getProductDetail($product);

            if ($productDetail) {
                $collectionData[] = $this->openAPITVCMall->getProductDetail($product);
            }
        }

        echo "Product Details Fetched at " . date('Y-m-d H:i:s') . PHP_EOL;

        $this->productSkuStorage->load(Arr::pluck($batch->data, 'itemNo'));
        $this->attributes = Cache::get('attributes_cache');

        $this->attributeMapping = Cache::get('attribute_mapping_cache');

        if (! $this->attributes) {
            $this->attributes = Cache::remember('attributes_cache', now()->addDays(2), function () {
                echo "Fetched attributes at " . date('Y-m-d H:i:s') . PHP_EOL;
                return $this->attributeRepository->all();
            });
        }

        if (! $this->attributeMapping) {
            $this->attributeMapping = Cache::remember('attribute_mapping_cache', now()->addDays(2), function () {
                echo "Fetched attributeMapping at " . date('Y-m-d H:i:s') . PHP_EOL;
                return $this->productAttributeMappingRepository->all();
            });
        }

        $products = [];

        foreach ($collectionData as $rowData) {
            $this->prepareProducts($rowData, $products);
        }

        echo "Product Prepared at " . date('Y-m-d H:i:s') . PHP_EOL;

        $this->saveProducts($products);

        echo "Product Created at " . date('Y-m-d H:i:s') . PHP_EOL;

        if (File::exists($this->tempDirectory) && File::isDirectory($this->tempDirectory)) {
            File::deleteDirectory($this->tempDirectory);

            echo "Temp file deleted at " . date('Y-m-d H:i:s') . PHP_EOL;
        }

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
     * Prepare products for import
     *
     */
    public function prepareProducts(array $product, array &$products): void
    {
        if(! isset($product['Sku']) || ! $product['Sku']) {
            return;
        }

        $existingProduct = $this->productSkuStorage->get($product['Sku']);

        if (! $this->attributeFamilyId) {
            $this->attributeFamilyId = $this->attributeFamilyRepository->findOneByField('code', 'tvc')?->id ?: 1;
        }

        $data = $this->prepareProductData($product, $existingProduct, $product['Sku']);        

        if ($existingProduct) {
            $products['update'][$product['Sku']] = array_merge($products['update'][$product['Sku']] ?? [], $data);
        } else {
            $products['insert'][$product['Sku']] = array_merge($products['insert'][$product['Sku']] ?? [], $data);
        }
    }

    protected function prepareProductData($product, $existingProduct, $TVCMallSKU, $productType = 'simple') {
        $channel = self::DEFAULT_CHANNEL;

        $locale = self::DEFAULT_LOCALE;

        $values = [];

        $this->price = false;

        foreach ($this->attributes as $attribute) {
            if ($mapping = $this->attributeMapping->firstWhere('unopim_code', $attribute->code)) {
                $code = $mapping->tvc_mall_code;
            } else {
                $code = Str::studly(Str::replace('_', ' ', $attribute->code));
            }

            $price = $this->price === false ? $this->getPrice($product['PriceList'] ?? []) : $this->price;

            if ($code == 'PriceList') {
                $value = [self::DEFAULT_CURRENCY => $price];
            } else if ($code == 'Price' || $code == 'DiscountedPrice') {
                $value = $price;
            } else if ($code == 'Warehouse') {
                $value = is_array($product['Warehouse'] ?? false) ? implode(',', $product['Warehouse']) : '';
            } else if ($code == 'PackageList') {
                $value = is_array($product['PackageList'] ?? false) ? implode(',', $product['PackageList']) : '';
            } else if ($code == 'CompatibleList') {
                $value = is_array($product['CompatibleList'] ?? false) ? implode(',', array_filter(array_column($product['CompatibleList'], 'DisplayName'))) : '';
            } else if ($code == 'Source') {
                $value = 'tvc';
            } else if ($code == 'TvcImages') {
                $value = is_array($product['Images']['ProductImages'] ?? false) ? implode(',', array_filter(array_column($product['Images']['ProductImages'], 'Url'))) : '';
            } else if ($code == 'TvcMoreImages') {
                $value = is_array($product['Images']['SpecialImages'] ?? false) ? implode(',', array_filter(array_column($product['Images']['SpecialImages'], 'Url'))) : '';
            } else {
                $value = data_get($product, $code);
            }

            if ($attribute->type == 'gallery') {
                $value = $value ? $this->getImages($value, $product['Sku'], $attribute->code) : null;
            } else if ($attribute->type == 'image') {
                $value = [$this->getImage($value, $attribute->code)];
            }

            if ($attribute->value_per_locale && $attribute->value_per_channel) {
                if ($attribute->code == 'price' || $attribute->code == 'cost') {
                    $value = [
                        self::DEFAULT_CURRENCY => $value,
                    ];
                }

                if ($attribute->type == 'boolean') {
                    $value = $value ? 'true' : 'false';
                }

                $values['values']['channel_locale_specific'][$channel][$locale][$attribute->code] = $value;
            } else if (!$attribute->value_per_locale && $attribute->value_per_channel) {
                if ($attribute->code == 'price' || $attribute->code == 'cost') {
                    $value = [
                        self::DEFAULT_CURRENCY => $value,
                    ];
                }

                if ($attribute->type == 'boolean') {
                    $value = $value ? 'true' : 'false';
                }

                $values['values']['channel_specific'][$channel][$attribute->code] = $value;
            } else {
                if ($attribute->type == 'boolean') {
                    $value = $value ? 'true' : 'false';
                }

                $values['values']['common'][$attribute->code] = $value;
            }
        }

        $data = [
            'type' => $productType,
            'attribute_family_id' => $this->attributeFamilyId,
            'sku' => $product['Sku'],
            'values' => json_encode(array_merge(
                [
                    'categories' => (string) $product['CatalogCode'] ?? null,
                ],
                $values['values']
            ), true),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data['parent_id'] = $productType == 'simple' ? ($this->createOrGetParent($product['Spu']['Dimensions'] ?? [], $data) ?: null) : null;

        return $data;
    }

    /**
     * Get price from api data
     * @param  mixed  $value
     * @return array
     *
     **/
    protected function getPrice($value): mixed
    {
        if ($value) {
            $price = INF;

            foreach ($value as $data) {
                if ($data['UnitPrice'] < $price) {
                    $price = $data['UnitPrice'];
                }
            }
        } else {
            $price = 0;
        }

        $this->price = $price;

        return $price;
    }

    /**
     * Get image from URL
     * @param  mixed  $value
     * @return mixed
     *
     **/
    protected function getImage($value): mixed
    {
        if (! $this->tempDirectory) {
            $this->tempDirectory = sys_get_temp_dir() . '/' . uniqid('tvc_dir_', true);

            if (! is_dir($this->tempDirectory)) {
                mkdir($this->tempDirectory, 0777, true);
            }
        }

        $url = 'https://img.tvc-mall.com' . $value;

        $response = Http::head($url);

        if ($response->status() === 200) {
            $imageData = file_get_contents($url);

            if ($imageData === false) {
                return false;
            }

            $filename = basename($url);

            $tempFilePath = $this->tempDirectory . '/' . $filename;

            file_put_contents($tempFilePath, $imageData);

            return new UploadedFile(
                $tempFilePath,
                $filename,
                mime_content_type($tempFilePath),
                null,
                true
            );
        }

        return false;
    }

    /**
     * prepare images instances
     * @param  array  $values
     * @return mixed
     *
     **/
    protected function getImages(array $values, $sku, $attributeCode): array
    {
        if (! $this->tempDirectory) {
            $this->tempDirectory = sys_get_temp_dir() . '/' . uniqid('tvc_dir_', true);

            if (! is_dir($this->tempDirectory)) {
                mkdir($this->tempDirectory, 0777, true);
            }
        }

        $productDirectoryPath = 'product'.DIRECTORY_SEPARATOR.$sku.DIRECTORY_SEPARATOR.$attributeCode;

        $urls = array_map(function ($value) {
            return 'https://img.tvc-mall.com' . $value['Url'];
        }, $values);
        $responses = Http::pool(fn($pool) => collect($urls)->mapWithKeys(function ($url) use ($pool) {
            return [$url => $pool->get($url)];
        }));

        $imagePath = [];

        foreach ($responses as $url => $response) {
            if ($response instanceof \Exception) {
                \Log::error("Failed to fetch image: {$url}. Error: " . $response->getMessage());
                continue;
            }

            if ($response->successful()) {
                $imageData = $response->body();

                if ($imageData !== false) {
                    $filename = basename($url);

                    $tempFilePath = $this->tempDirectory . '/' . $filename;

                    file_put_contents($tempFilePath, $imageData);
                    $file = new UploadedFile(
                        $tempFilePath,
                        $filename,
                        mime_content_type($tempFilePath),
                        null,
                        true
                    );

                    $imagePath[] = $this->fileStorer->store($productDirectoryPath, $file, [FileStorer::HASHED_FOLDER_NAME_KEY => true]);
                }
            }
        }

        return $imagePath;
    }

    /**
     * Save products from current batch
     */
    public function saveProducts(array $products): void
    {
        if (!empty($products['update'])) {
            $this->updatedItemsCount += count($products['update']);

            $this->productRepository->upsert(
                $products['update'],
                $this->masterAttributeCode
            );
        }

        if (!empty($products['insert'])) {
            $this->createdItemsCount += count($products['insert']);

            $this->productRepository->insert($products['insert']);
        }
    }

    /**
     * create or update product brand
     *
     * @param array $brand
     * @return void
     *
     **/
    protected function createOrUpdateBrand(array $brand)
    {
        $this->createOrUpdateAttributeValue('brand', $brand);
    }

    /**
     * create or update attribute value
     *
     * @param string $attributeCode
     * @param array $attributeValue
     * @return void
     *
     **/
    protected function createOrUpdateAttributeValue($attributeCode, $attributeValue)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);

        if ($attribute && $attributeValue) {
            $locale = self::DEFAULT_LOCALE;

            $attributeValueId = $this->attributeOptionRepository->findOneWhere([
                'attribute_id' => $attribute->id,
                'code' => $attributeValue['code'],
            ])?->id;

            if ($attributeValueId) {
                $this->attributeOptionRepository->update([
                    'attribute_id' => $attribute->id,
                    $locale => [
                        'label' => $attributeValue['name'],
                    ],
                ], $attributeValueId);
            } else {
                $this->attributeOptionRepository->create([
                    'attribute_id' => $attribute->id,
                    'code' => $attributeValue['code'],
                    $locale => [
                        'label' => $attributeValue['name'],
                    ],
                ]);
            }
        }
    }

    /**
     * create or get parent
     *
     * @param array $dimensions
     * @param string $sku
     * @return void
     *
     **/
    protected function createOrGetParent($dimensions, $productData): int
    {
        if ($dimensions) {
            $parentSKU = '';

            $name = '';

            $attributeValue = '';

            foreach ($dimensions as $dimension) {
                if (strtolower($dimension['Name']) == 'color' && count($dimension['Mapping']) > 1) {
                    foreach ($dimension['Mapping'] as $mapping) {
                        if ($mapping['Item']) {
                            if (!$name) {
                                $position = strpos($mapping['Item']['Title'], ' - ');
    
                                if ($position === false) {
                                    $name = $mapping['Item']['Title'];
                                } else {
                                    $name = substr($mapping['Item']['Title'], 0, $position);
                                }
    
                                $parentSKU = substr($mapping['Item']['Sku'], 0, -1);
                            }
                        }

                        if ($mapping['IsCurrentItem']) {
                            $attributeValue = $mapping['Value'];
                        }
                    }
                }
            }


            if ($parentSKU) {
                if ($attributeValue) {
                    $this->createOrUpdateAttributeValue('color', [
                        'code' => $attributeValue,
                        'name' => $attributeValue,
                    ]);
                }

                $parentStorage = $this->productSkuStorage->get($parentSKU);

                if ($parentStorage) {
                    return $parentStorage['id'];
                }

                $parent = $this->productRepository->findOneByField('sku', $parentSKU);

                if ($parent) {
                    $this->productSkuStorage->set($parent->sku, [
                        'id' => $parent->id,
                        'type' => 'configurable',
                        'attribute_family_id' => $this->attributeFamilyId
                    ]);

                    return $parent->id;
                }

                $productData['sku'] = $parentSKU;
                $productData['type'] = 'configurable';

                $this->productRepository->insert([$parentSKU => $productData]);

                $parentProduct = $this->productRepository->findOneByField('sku', $parentSKU);

                if ($parentProduct) {
                    if (! $this->superAttributeColorId) {
                        $this->superAttributeColorId = $this->attributeRepository->findOneByField('code', 'color')?->id ?: 23;
                    }

                    $this->saveProductSuperAttribute($parentProduct->id);

                    $this->productSkuStorage->set($parentSKU, [
                        'id' => $parentProduct->id,
                        'type' => 'configurable',
                        'attribute_family_id' => $this->attributeFamilyId
                    ]);
                }

                return $parentProduct?->id ?? 0;
            }
        }

        return 0;
    }

    /**
     * insert product super attribute
     *
     * @param int $productId
     * @return void
     *
     **/
    protected function saveProductSuperAttribute($productId)
    {
        \DB::table('product_super_attributes')->insert([
            'attribute_id' => $this->superAttributeColorId,
            'product_id' => $productId,
        ]);
    }
}
