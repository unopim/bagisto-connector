<?php

namespace Webkul\BagistoPlugin\Helpers\Exporters\Product;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\BagistoPlugin\Enums\Export\CacheType;
use Webkul\BagistoPlugin\Enums\Services\MethodType;
use Webkul\BagistoPlugin\Repositories\AttributeMappingRepository;
use Webkul\BagistoPlugin\Repositories\BagistoDataMapping;
use Webkul\BagistoPlugin\Repositories\CredentialRepository;
use Webkul\BagistoPlugin\Traits\ApiRequest as ApiRequestTrait;
use Webkul\BagistoPlugin\Traits\Credential as CredentialTrait;
use Webkul\BagistoPlugin\Traits\Mapping as MappingTrait;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Validator\FieldValidator;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter as AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\ProductRepository;

class Exporter extends AbstractExporter
{
    use ApiRequestTrait;
    use CredentialTrait;
    use MappingTrait;

    public const BATCH_SIZE = 10;

    protected const ENTITY_TYPE = 'product';

    /*
     * For check initialization
     */
    protected bool $initialized = false;

    /*
     * For exporting file
     */
    protected bool $exportsFile = false;

    /*
     * For mappingAttributes
     */
    protected array $mappingAttributes = [];

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
        protected ProductRepository $productRepository,
        protected CategoryRepository $categoryRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected AttributeMappingRepository $attributeMappingRepository,
        protected ChannelRepository $channelRepository,
        protected CredentialRepository $credentialRepository
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer, $channelRepository, $attributeRepository);
    }

    /**
     * Initializes the data for the export process.
     */
    public function initialize(): void
    {
        $this->initializeCredential($this->getFilters());
        $this->initializeMappingAttributes();
    }

    /**
     * Initializes mappingAttribute for the export process.
     *
     * @return void
     */
    public function initializeMappingAttributes()
    {
        $this->mappingAttributes = Cache::get(CacheType::CATEGORY_FIELD_MAPPING->value, []);
        if (empty($this->mappingAttributes)) {
            $this->mappingAttributes = [
                'standard_attribute' => $this->attributeMappingRepository->findByField('section', 'standard_attribute')->first(),
                'image_attribute'    => $this->attributeMappingRepository->findByField('section', 'image_attribute')->first(),
            ];

            Cache::put(CacheType::CATEGORY_FIELD_MAPPING->value, $this->mappingAttributes, Env('SESSION_LIFETIME'));
        }
    }

    /**
     * Start the export process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        if (! $this->initialized) {
            $this->initialize();
            $this->initialized = true;
        }

        $preparedData = $this->prepareProducts($batch, $filePath);
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
        $sku = $filters['sku'] ?? null;

        return $sku
            ? $this->source->with(['attribute_family', 'parent', 'super_attributes', 'variants'])
                ->whereIn('sku', explode(',', $sku))->get()->getIterator()
            : $this->source->with(['attribute_family', 'parent', 'super_attributes', 'variants'])->all()->getIterator();
    }

    public function write(array $items, int $batchId): void
    {
        foreach ($items as $item) {
            $id = $item['id'];
            unset($item['id']);
            $mapData = isset($item['isVariant']) && $item['isVariant'] ? $this->getMapping($this->credential['id'], $id, null, null, 'variant') : $this->getMapping($this->credential['id'], $id);
            $method = MethodType::POST->value;
            $options = [];
            if ($mapData) {
                $item['_method'] = 'put';
                $options = [
                    'id'          => $mapData->external_id,
                    'gallery'     => $item['with_media'],
                    'isMultipart' => true,
                ];
            }

            try {
                $response = $this->setApiRequest($method, self::ENTITY_TYPE, $item, $options);
            } catch (\Exception $e) {
                $this->jobLogger->warning($e);

                continue;
            }

            if (isset($response['id'])) {
                $this->createdItemsCount++;
            } else {
                $this->skippedItemsCount++;
            }

            $this->handleResponse($item, $response, $batchId, $mapData, $id);
        }
    }

    protected function handleResponse(array $item, $response, int $batchId, $mapData, int $id): void
    {
        if (! $mapData && isset($response['id'])) {
            $this->setMapping($this->credential['id'], $id, $response['id'], $batchId);
        }
        if (isset($response['variants']) && $response['variants'] && isset($item['variants']) && $item['variants']) {
            foreach ($response['variants'] as $key => $variant) {
                $relatedId = str_replace('variant_', '', array_keys($item['variants'])[$key]);
                $variantMapData = $this->getMapping($this->credential['id'], $variant['id'], null, 'variant');
                if (! $variantMapData) {
                    $this->setMapping($this->credential['id'], $relatedId, $variant['id'], $batchId, 'variant');
                }
            }
        }
    }

    public function prepareProducts(JobTrackBatchContract $batch, $filePath): array
    {
        $products = [];
        $filters = $this->getFilters();
        $withMedia = isset($filters['with_media']) ? $filters['with_media'] : false;
        $jobLocale = explode(',', $filters['locale']);
        $jobChannel = explode(',', $filters['channel']);
        $bagistoLocales = $this->getMappedLocales();
        $bagistoChannels = $this->getMappedChannels();

        foreach ($batch->data as $rowData) {
            $createProduct = false;
            foreach ($bagistoChannels as $bagistoChannel => $unoPimChannel) {
                if (! in_array($unoPimChannel, $jobChannel)) {
                    continue;
                }

                foreach ($bagistoLocales[$bagistoChannel] as $bagistoLocale => $unoPimLocale) {
                    if (! in_array($unoPimLocale, $jobLocale)) {
                        continue;
                    }

                    $mapData = $this->getMapping($this->credential['id'], $rowData['id']);
                    if ($rowData['type'] === 'simple' && ! $mapData && ! $createProduct && empty($rowData['parent'])) {
                        $createProduct = true;
                        $products[] = $this->createSimpleProductDataFormat($rowData);
                    } elseif ($rowData['type'] === 'configurable' && ! empty($rowData['super_attributes']) && ! $mapData && ! $createProduct) {
                        $createProduct = true;
                        $products[] = $this->createConfigurableProductDataFormat($rowData);
                    }

                    $products[] = $this->getFormatedProductData($rowData, $unoPimLocale, $bagistoLocale, $unoPimChannel, $bagistoChannel, $withMedia);
                }
            }
        }

        return $products;
    }

    protected function createSimpleProductDataFormat(array $item): array
    {
        return [
            'id'                  => $item['id'],
            'sku'                 => $item['sku'],
            'type'                => $item['type'],
            'attribute_family_id' => $this->getMapping($this->credential['id'], $item['attribute_family_id'], null, null, 'attribute_family')->external_id ?? $item['attribute_family_id'],
        ];
    }

    protected function createConfigurableProductDataFormat(array $item): array
    {
        $formatData = $this->createSimpleProductDataFormat($item);

        $formatData['super_attributes'] = $this->getSuperAttributes($item);

        return $formatData;
    }

    protected function getSuperAttributes(array $item): array
    {
        $formatData = [];
        foreach ($item['super_attributes'] as $superAttribute) {
            $externalId = $this->getMapping($this->credential['id'], $superAttribute['id'], null, null, 'attribute')->external_id ?? null;
            $formatData[$superAttribute['code']] = [$externalId ?? $superAttribute['id']];
        }

        return $formatData;
    }

    protected function getFormatedProductData(array $item, string $locale, string $bagistoLocale, string $channel, string $bagistoChannel, bool $withMedia): array
    {
        $data = [];
        $isVariant = isset($item['parent']) && $item['parent'] ? true : false;

        $data['id'] = $item['id'];
        $data['isVariant'] = $isVariant;
        $data['with_media'] = $withMedia;
        $data['type'] = $item['type'];
        $data['locale'] = $bagistoLocale;
        $data['channel'] = $bagistoChannel;

        $commonFields = $this->getCommonFields($item);
        $localeSpecificFields = $this->getLocaleSpecificFields($item, $locale);
        $channelSpecificFields = $this->getChannelSpecificFields($item, $channel);
        $channelLocaleSpecificFields = $this->getChannelLocaleSpecificFields($item, $channel, $locale);
        $mergedFields = array_merge($commonFields, $localeSpecificFields, $channelSpecificFields, $channelLocaleSpecificFields);
        $this->handleAttributeType($mergedFields, $withMedia);
        $this->getVariantsData($item, $mergedFields, $withMedia, $channel, $locale);
        $mapAttributes = array_flip($this->mappingAttributes['standard_attribute']->mapped_value ?? []);
        $fixedValue = $this->mappingAttributes['standard_attribute']->fixed_value ?? [];
        if (! empty($fixedValue)) {
            foreach ($fixedValue as $bagistoAttribute => $value) {
                if (isset($mergedFields[$bagistoAttribute]) && empty($mergedFields[$bagistoAttribute])) {
                    $mergedFields[$bagistoAttribute] = $value;
                }
                if (! isset($mergedFields[$bagistoAttribute])) {
                    if ($bagistoAttribute == 'inventories') {
                        $mergedFields[$bagistoAttribute][1] = $value;

                        continue;
                    } else {
                        $mergedFields[$bagistoAttribute] = $value;
                    }
                }
            }
        }

        foreach ($mapAttributes as $unpoimAttribute => $bagistoAttribute) {
            if (isset($mergedFields[$unpoimAttribute]) && ! in_array($bagistoAttribute, array_keys($mapAttributes))) {
                $mergedFields[$bagistoAttribute] = $mergedFields[$unpoimAttribute];
                unset($mergedFields[$unpoimAttribute]);
            }
        }
        $this->getAssociationsData($item, $mergedFields);
        $this->getCategoryFormatData($item, $mergedFields);
        $data = array_merge($data, $mergedFields);

        return $data;
    }

    protected function getVariantsData(array $item, array &$mergedFields, bool $withMedia, string $channel, string $locale): void
    {
        if (! empty($item['variants'])) {
            $variants = [];
            foreach ($item['variants'] as $variant) {
                $variantMapping = $this->getMapping($this->credential['id'], $variant['id'], null, null, 'variant')->external_id ?? null;
                $commonFields = $this->getCommonFields($variant);
                $localeSpecificFields = $this->getLocaleSpecificFields($variant, $locale);
                $channelSpecificFields = $this->getChannelSpecificFields($variant, $channel);
                $channelLocaleSpecificFields = $this->getChannelLocaleSpecificFields($variant, $channel, $locale);
                $variantMergedFields = array_merge($commonFields, $localeSpecificFields, $channelSpecificFields, $channelLocaleSpecificFields);
                $this->handleAttributeType($variantMergedFields, $withMedia);
                $mapAttributes = array_flip($this->mappingAttributes['standard_attribute']->mapped_value);
                $fixedValue = $this->mappingAttributes['standard_attribute']->fixed_value ?? [];
                foreach ($mapAttributes as $unpoimAttribute => $bagistoAttribute) {
                    if (isset($variantMergedFields[$unpoimAttribute]) && ! in_array($bagistoAttribute, array_keys($mapAttributes))) {
                        if (isset($fixedValue[$bagistoAttribute])) {
                            $variantMergedFields[$bagistoAttribute] = $fixedValue[$bagistoAttribute];
                        } else {
                            $variantMergedFields[$bagistoAttribute] = $variantMergedFields[$unpoimAttribute];
                        }
                        unset($variantMergedFields[$unpoimAttribute]);
                    }
                }
                if ($variantMapping) {
                    $variants[$variantMapping] = $variantMergedFields;
                } else {
                    $variants['variant_'.$variant['id']] = $variantMergedFields;
                }
            }
            $mergedFields['variants'] = $variants;
            $mergedFields['attribute_family_id'] = $this->getMapping($this->credential['id'], $item['attribute_family_id'], null, null, 'attribute_family')->external_id ?? $item['attribute_family_id'];
        }
    }

    protected function handleAttributeType(array &$mergedFields, bool $withMedia): void
    {
        foreach ($mergedFields as $attributeCode => $attributeValue) {
            $attribute = $this->attributeRepository->where('code', $attributeCode)->first();

            switch ($attribute->type) {
                case AttributeTypes::GALLERY_ATTRIBUTE_TYPE:
                    if ($withMedia) {
                        $mergedFields[$attributeCode] = array_map(fn ($path) => $this->getExistingFilePath($path), (array) $attributeValue);
                    } else {
                        unset($mergedFields[$attributeCode]);
                    }
                    break;

                case AttributeTypes::IMAGE_ATTRIBUTE_TYPE:
                case AttributeTypes::FILE_ATTRIBUTE_TYPE:
                    if ($withMedia) {
                        $mergedFields[$attributeCode] = is_array($attributeValue) ? $this->getExistingFilePath($attributeValue[0]) : $this->getExistingFilePath($attributeValue);
                    } else {
                        unset($mergedFields[$attributeCode]);
                    }
                    break;

                case AttributeTypes::PRICE_ATTRIBUTE_TYPE:
                    $mergedFields[$attributeCode] = is_array($attributeValue) ? $attributeValue['USD'] : $attributeValue;
                    break;

                case FieldValidator::BOOLEAN_FIELD_TYPE:
                    $mergedFields[$attributeCode] = $attributeValue ? 1 : 0;
                    break;

                default:
                    if (in_array($attribute->type, ['multiselect', 'checkbox', 'select'])) {
                        $optionIds = [];
                        $options = explode(',', $attributeValue);
                        foreach ($options as $option) {
                            $optionData = $this->attributeOptionRepository->findWhere(['attribute_id' => $attribute->id, 'code' => $option])->first();
                            $optionIds[] = $this->getMapping($this->credential['id'], $optionData->id, null, null, 'option')->external_id ?? null;
                        }
                        $mergedFields[$attributeCode] = $attribute->type == 'select' ? reset($optionIds) : $optionIds;
                    }
                    break;
            }
        }
    }

    protected function getCommonDataFormat(array $commonItem): array
    {
        $commonData = [];
        $mapAttributes = array_flip($this->mappingAttributes['standard_attribute']->mapped_value);

        foreach ($commonItem as $code => $value) {
            $attribute = $this->attributeRepository->where('code', $code)->first();
            $attributeCode = $mapAttributes[$code] ?? $code;
            $commonData[$attributeCode] = $value;

            if ($attribute->type === FieldValidator::BOOLEAN_FIELD_TYPE) {
                $commonData[$attributeCode] = $value ? 1 : 0;
            } elseif ($attribute->type === AttributeTypes::GALLERY_ATTRIBUTE_TYPE) {
                $commonData[$attributeCode] = array_map(fn ($path) => $this->getExistingFilePath($path), (array) $value);
            }
        }

        return $commonData;
    }

    protected function getCategoryFormatData(array $item, &$mergedFields): void
    {
        if (isset($item['values']['categories'])) {
            foreach ($item['values']['categories'] as $code) {
                $category = $this->categoryRepository->where('code', $code)->first();
                $externalId = $this->getMapping($this->credential['id'], $category->id, null, null, 'category')->external_id ?? null;
                if ($externalId) {
                    $mergedFields['categories'][] = $externalId;
                }
            }
        }
    }

    protected function getAssociationsData(array $item, array &$mergedFields): void
    {
        if ($upSells = $this->getAssociationsFormat($item, 'up_sells')) {
            $mergedFields['up_sells'] = $upSells;
        }
        if ($crossSells = $this->getAssociationsFormat($item, 'cross_sells')) {
            $mergedFields['cross_sells'] = $crossSells;
        }
        if ($relatedProducts = $this->getAssociationsFormat($item, 'related_products')) {
            $mergedFields['related_products'] = $relatedProducts;
        }
    }

    protected function getAssociationsFormat(array $item, string $type): array
    {
        $associations = [];
        if ($association = $this->getAssociations($item, $type)) {
            $products = explode(',', $association);
            foreach ($products as $sku) {
                $productData = $this->productRepository->where('sku', $sku)->first();
                $mapping = $this->getMapping($this->credential['id'], $productData->id, null, null, 'product')->external_id ?? null;
                if ($mapping) {
                    $associations[] = $mapping;
                }
            }
        }

        return $associations;
    }

    protected function getExistingFilePath(string $mediaPath): ?string
    {
        return Storage::exists($mediaPath) ? Storage::path($mediaPath) : null;
    }
}
