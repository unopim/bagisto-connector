<?php

namespace Webkul\TVCMall\Helpers\Importers\Product;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Webkul\Attribute\Repositories\attributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
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

    public function __construct(
        protected ConfigurationRepository $configurationRepository,
        protected SKUStorage $productSkuStorage,
        protected CategoryRepository $categoryRepository,
        protected ProductRepository $productRepository,
        protected JobTrackBatchRepository $importBatchRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository,
        protected OpenAPITVCMall $openAPITVCMall,
        protected ProductAttributeMappingRepository $productAttributeMappingRepository,
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
        $this->productSkuStorage->init();

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

        $this->productSkuStorage->load(Arr::pluck($batch->data, 'itemNo'));

        $products = [];

        foreach ($collectionData as $rowData) {
            $this->prepareProducts($rowData, $products);
        }

        $this->saveProducts($products);

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
        $existingProduct = $this->productSkuStorage->get($product['Sku']);

        $channel = self::DEFAULT_CHANNEL;

        $locale = self::DEFAULT_LOCALE;

        $values = [];

        $attributes = $this->attributeRepository->all();

        $attributeMapping = $this->productAttributeMappingRepository->all();

        foreach ($attributes as $attribute) {
            if ($mapping = $attributeMapping->firstWhere('unopim_code', $attribute->code)) {
                $code = $mapping->tvc_mall_code;
            } else {
                $code = Str::studly(Str::replace('_', ' ', $attribute->code));
            }

            $value = data_get($product, $code);

            if ($code == 'PriceList') {
                $value = $this->getPrice($value);
            } else if ($code == 'Warehouse') {
                $value = is_array($value) ? implode(',', $value) : $value;
            } else if ($code == 'Source') {
                $value = 'tvc';
            }

            if ($attribute->type == 'gallery') {
                if ($existingProduct) {
                    continue;
                }

                $value = $this->getImages($product, $attribute->code);
            } else if ($attribute->type == 'image') {
                if ($existingProduct) {
                    continue;
                }

                $value = $this->getImage($value, $attribute->code);
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

        // if ($product['brand']) {
        //     $this->createOrUpdateBrand($product['brand']);
        // }

        $data = array_merge([
            'type' => 'simple',
            'attribute_family_id' => 1,
            'sku' => $product['Sku'],
            'channel' => $channel,
            'locale' => $locale,
            'url_key' => $product['Sku'],
            'categories' => [$product['CatalogCode']],
            'parent_id' => $this->createOrGetParent($product['Spu']['Dimensions'] ?? [], $product['Sku']) ?: null
        ], $values);

        if ($existingProduct) {
            $products['update'][$product['Sku']] = array_merge($products['update'][$product['Sku']] ?? [], $data);
        } else {
            $products['insert'][$product['Sku']] = array_merge($products['insert'][$product['Sku']] ?? [], $data);
        }
    }

    /**
     * Get price from api data
     * @param  mixed  $value
     * @return array
     *
     **/
    protected function getPrice($value): mixed
    {
        if (is_array($value)) {
            $price = INF;

            foreach ($value as $data) {
                if ($data['UnitPrice'] < $price) {
                    $price = $data['UnitPrice'];
                }
            }
        } else {
            $price = $value;
        }

        return [self::DEFAULT_CURRENCY => $price];
    }

    /**
     * Get image from URL
     * @param  mixed  $value
     * @param  mixed  $code
     * @return mixed
     *
     **/
    protected function getImage($value, $code): mixed
    {
        $url = 'https://img.tvc-mall.com' . $value;

        $image = [];

        $response = Http::head($url);

        if ($response->status() === 200) {
            $imageData = file_get_contents($url);

            if ($imageData === false) {
                return false;
            }

            $tempImage = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tempImage, $imageData);

            $filename = basename($url);

            $image[] = new UploadedFile(
                $tempImage,
                $filename,
                mime_content_type($tempImage),
                null,
                true
            );

            return $image;
        }

        return false;
    }

    /**
     * Get images from zip provided by API
     * @param  mixed  $product
     * @param  mixed  $code
     * @return mixed
     *
     **/
    protected function getImages($product, $code): mixed
    {
        $downloadImageUrl = '';

        if ($code == 'gallery') {
            $downloadImageUrl = $this->openAPITVCMall->getProductImages([
                'ItemNo' => $product['Sku'],
                'Size' => '1000x1000',
            ]);
        } else if ($code == 'additional_images') {
            $downloadImageUrl = $this->openAPITVCMall->getProductAdditionalImages([
                'ItemNo' => $product['Sku'],
                'Size' => '1000x1000',
            ]);
        }

        if (!$downloadImageUrl) {
            return false;
        }

        $zipData = file_get_contents($downloadImageUrl);

        if ($zipData === false) {
            return false;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tempFile, $zipData);

        $zip = new \ZipArchive();

        if ($zip->open($tempFile) === true) {
            $images = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                $imageData = $zip->getFromName($filename);

                $tempImage = tempnam(sys_get_temp_dir(), 'img');
                file_put_contents($tempImage, $imageData);

                $images[] = new UploadedFile(
                    $tempImage,
                    $filename,
                    mime_content_type($tempImage),
                    null,
                    true
                );
            }

            $zip->close();

            unlink($tempFile);

            return $images;
        }

        return false;
    }

    /**
     * Save products from current batch
     */
    public function saveProducts(array $products): void
    {
        if (!empty($products['update'])) {
            $this->updatedItemsCount += count($products['update']);
            foreach ($products['update'] as $code => $product) {
                $storageData = $this->productSkuStorage->get($code);

                $this->productRepository->update($product, $storageData['id'] ?? null);
            }
        }

        if (!empty($products['insert'])) {
            $this->createdItemsCount += count($products['insert']);

            foreach ($products['insert'] as $code => $product) {
                $newProduct = $this->productRepository->create($product);

                if ($newProduct) {
                    $this->productSkuStorage->set($code, [
                        'id' => $newProduct?->id,
                        'type' => 'simple',
                        'attribute_family_id' => 1,
                    ]);

                    $this->productRepository->update($product, $newProduct?->id);
                }
            }
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
    protected function createOrGetParent($dimensions, $sku): int
    {
        if ($dimensions) {
            $SKUs = [];

            $name = '';

            $attributeValue = '';

            foreach ($dimensions as $dimension) {
                if (strtolower($dimension['Name']) == 'color' && count($dimension['Mapping']) > 1) {
                    foreach ($dimension['Mapping'] as $mapping) {
                        $SKUs[] = $mapping['Item']['Sku'];

                        if (!$name) {
                            $position = strpos($mapping['Item']['Title'], ' - ');

                            if ($position === false) {
                                $name = $mapping['Item']['Title'];
                            } else {
                                $name = substr($mapping['Item']['Title'], 0, $position);
                            }
                        }

                        if ($mapping['IsCurrentItem']) {
                            $attributeValue = $mapping['Value'];
                        }
                    }
                }
            }


            if ($SKUs) {
                if ($attributeValue) {
                    $this->createOrUpdateAttributeValue('color', [
                        'code' => $attributeValue,
                        'name' => $attributeValue,
                    ]);
                }

                $parentSKU = implode('_', $SKUs);

                $parentStorage = $this->productSkuStorage->get($parentSKU);

                if ($parentStorage) {
                    return $parentStorage['id'];
                }

                $parent = $this->productRepository->findOneByField('sku', $parentSKU);

                if ($parent) {
                    $this->productSkuStorage->set($parent->sku, [
                        'id' => $parent->id,
                        'type' => 'configurable',
                        'attribute_family_id' => 1
                    ]);

                    return $parent->id;
                }

                $parentProduct = $this->productRepository->create([
                    'type' => 'configurable',
                    'sku' => $parentSKU,
                    'attribute_family_id' => 1,
                    'super_attributes' => [
                        'color' => 'color'
                    ]
                ]);

                if ($parentProduct) {
                    $this->productRepository->update([
                        'price' => 0,
                        'values' => [
                            'common' => [
                                'status' => "true",
                                'product_status' => "true",
                            ],
                            'channel_locale_specific' => [
                                self::DEFAULT_CHANNEL => [
                                    self::DEFAULT_LOCALE => [
                                        'name' => $name,
                                        'description' => $name
                                    ]
                                ]
                            ]
                        ]
                    ], $parentProduct->id);

                    $this->productSkuStorage->set($parentSKU, [
                        'id' => $parentProduct->id,
                        'type' => 'configurable',
                        'attribute_family_id' => 1
                    ]);
                }

                return $parentProduct?->id ?? 0;
            }
        }

        return 0;
    }
}
