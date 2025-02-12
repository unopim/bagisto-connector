<?php

namespace Webkul\Bagisto\Helpers\Exporters\Product;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Bagisto\Enums\Export\CacheType;
use Webkul\Bagisto\Enums\Services\MethodType;
use Webkul\Bagisto\Repositories\AttributeMappingRepository;
use Webkul\Bagisto\Repositories\BagistoDataMapping;
use Webkul\Bagisto\Repositories\CredentialRepository;
use Webkul\Bagisto\Traits\ApiRequest as ApiRequestTrait;
use Webkul\Bagisto\Traits\Credential as CredentialTrait;
use Webkul\Bagisto\Traits\Mapping as MappingTrait;
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

    protected const ENTITY_TYPE = 'bulk_product';

    protected const VARIANT = 'variant';

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
     * @var array
     */
    protected $jobFilters = [];

    /**
     * @var array
     */
    protected $urlKey = [];

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
        $this->initializeJobFilters();
    }

    /**
     * Initializes mappingAttribute for the export process.
     *
     * @return void
     */
    public function initializeMappingAttributes()
    {
        $this->mappingAttributes = Cache::get(CacheType::ATTRIBUTE_MAPPING->value, []);
        if (empty($this->mappingAttributes)) {
            $this->mappingAttributes = [
                'standard_attribute' => $this->attributeMappingRepository->findByField('section', 'standard_attribute')->first(),
                'image_attribute'    => $this->attributeMappingRepository->findByField('section', 'image_attribute')->first(),
            ];

            Cache::put(CacheType::ATTRIBUTE_MAPPING->value, $this->mappingAttributes, Env('SESSION_LIFETIME'));
        }
    }

    /**
     * Initializes Job Filters for the export process.
     *
     * @return void
     */
    public function initializeJobFilters()
    {
        $this->jobFilters = Cache::get(CacheType::JOB_FILTERS->value, []);

        if (empty($this->jobFilters)) {
            $filters = $this->getFilters();

            $filtersChannels = ! empty($filters['channel']) ? explode(',', $filters['channel']) : [];
            $filtersLocales = ! empty($filters['locale']) ? explode(',', $filters['locale']) : [];

            $bagistoChannels = $this->getMappedChannels();
            $bagistoLocales = $this->getMappedLocales();

            $mappedBagistoChannels = [];
            $exportBagistoLocales = [];

            foreach ($bagistoChannels as $bagistoChannel => $unopimChannel) {
                if (in_array($unopimChannel, $filtersChannels, true)) {
                    $mappedBagistoChannels[$bagistoChannel] = $unopimChannel;

                    if (isset($bagistoLocales[$bagistoChannel])) {
                        foreach ($bagistoLocales[$bagistoChannel] as $bagistoLocal => $unopimLocal) {
                            if (in_array($unopimLocal, $filtersLocales, true)) {
                                $exportBagistoLocales[$bagistoChannel][$bagistoLocal] = $unopimLocal;
                            }
                        }
                    }
                }
            }

            $this->jobFilters = [
                'withMedia' => $filters['with_media'],
                'channel'   => $mappedBagistoChannels,
                'locales'   => $exportBagistoLocales,
            ];

            Cache::put(CacheType::JOB_FILTERS->value, $this->jobFilters, env('SESSION_LIFETIME'));
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
        try {
            $response = $this->setApiRequest(MethodType::POST->value, self::ENTITY_TYPE, $items, []);
        } catch (\Exception $e) {
            $this->jobLogger->warning($e);
        }
    }

    public function prepareProducts(JobTrackBatchContract $batch, $filePath): array
    {
        $products = [];

        foreach ($batch->data as $rowData) {
            foreach ($this->jobFilters['channel'] as $bagistoChannel => $unoPimChannel) {
                if (! isset($this->jobFilters['locales'][$bagistoChannel])) {
                    continue;
                }

                foreach ($this->jobFilters['locales'][$bagistoChannel] as $bagistoLocale => $unoPimLocale) {
                    $simple = $config = $variants = null;
                    if ($rowData['type'] === 'simple' && empty($rowData['parent'])) {
                        $simple = $this->createSimpleProductDataFormat($rowData);
                    } elseif ($rowData['type'] === 'configurable' && ! empty($rowData['super_attributes'])) {
                        $config = $this->createConfigurableProductDataFormat($rowData);
                    } elseif ($rowData['type'] === 'simple' && ! empty($rowData['parent'])) {
                        $variants = $this->createConfigurableVariantProductDataFormat($rowData);
                    }
                    $products[] = array_merge($this->getFormatedProductData($rowData, $unoPimLocale, $bagistoLocale, $unoPimChannel, $bagistoChannel, $this->jobFilters['withMedia']), $simple ?? $config ?? $variants);
                }
            }
            $this->createdItemsCount++;
        }

        return $products;
    }

    protected function createSimpleProductDataFormat(array $item): array
    {
        return [
            'id'                    => $item['id'],
            'sku'                   => $item['sku'],
            'type'                  => $item['type'],
            'attribute_family_code' => $item['attribute_family']['code'],
        ];
    }

    protected function createConfigurableProductDataFormat(array $item): array
    {
        $formatData = $this->createSimpleProductDataFormat($item);

        $formatData['configurable_variants'] = $this->getSuperAttributes($item);

        return $formatData;
    }

    protected function createConfigurableVariantProductDataFormat($item)
    {
        $formatData = $this->createSimpleProductDataFormat($item);

        $formatData['parent_sku'] = $item['parent']['sku'];

        return $formatData;
    }

    protected function getSuperAttributes(array $item): string
    {
        $newFormatData = [];

        foreach ($item['super_attributes'] as $superAttribute) {
            $superAttributeCodes[] = $superAttribute['code'];
        }

        foreach ($item['variants'] as $variant) {
            $formatData = [];
            $formatData[] = "sku={$variant['sku']}";
            $commonFields = $this->getCommonFields($variant);
            foreach ($superAttributeCodes as $key => $attribute) {
                $formatData[] = "{$attribute}={$commonFields[$attribute]}";
            }
            $newFormatData[] = implode(',', $formatData);
        }

        return implode('|', $newFormatData);
    }

    protected function getFormatedProductData(array $item, string $locale, string $bagistoLocale, string $channel, string $bagistoChannel, bool $withMedia): array
    {
        $data = [];
        $data['id'] = $item['id'];
        $data['with_media'] = $withMedia;
        $data['type'] = $item['type'];
        $data['locale'] = $bagistoLocale;
        $data['channel'] = $bagistoChannel;

        $commonFields = $this->getCommonFields($item);
        $localeSpecificFields = $this->getLocaleSpecificFields($item, $locale);
        $channelSpecificFields = $this->getChannelSpecificFields($item, $channel);
        $channelLocaleSpecificFields = $this->getChannelLocaleSpecificFields($item, $channel, $locale);
        $mergedFields = array_merge($commonFields, $localeSpecificFields, $channelSpecificFields, $channelLocaleSpecificFields);
        $this->handleAttributeType($mergedFields, $withMedia, $channel);
        $mapAttributes = $this->mappingAttributes['standard_attribute']->mapped_value ?? [];
        
        $fixedValue = $this->mappingAttributes['standard_attribute']->fixed_value ?? [];
        if (! empty($fixedValue)) {
            foreach ($fixedValue as $bagistoAttribute => $value) {
                if (isset($mergedFields[$bagistoAttribute]) && empty($mergedFields[$bagistoAttribute])) {
                    $mergedFields[$bagistoAttribute] = $value;
                }
                if (! isset($mergedFields[$bagistoAttribute])) {
                    $mergedFields[$bagistoAttribute] = $value; 
                }
            }
        }

        foreach ($mapAttributes as $bagistoAttribute => $unpoimAttribute) {
            if (isset($mergedFields[$unpoimAttribute]) && ! in_array($bagistoAttribute, array_keys($mapAttributes))) {
                if ($bagistoAttribute == 'inventories') {
                    $mergedFields[$bagistoAttribute] = 'default='.$mergedFields[$unpoimAttribute];

                    continue;
                }
                if (isset($mergedFields[$unpoimAttribute])) {
                    $mapAttributesData[$bagistoAttribute] = $mergedFields[$unpoimAttribute];
                }    
            }
        }
        if (!empty($mergedFields['url_key'])) {
            $slug = $this->createSlug($mergedFields['url_key']);
            in_array($slug, $this->urlKey) ? $mergedFields['url_key'] = $slug . '-' . array_count_values($this->urlKey)[$slug] : $mergedFields['url_key'] = $slug;
            $this->urlKey[] = $slug;
        }
        $this->getAssociationsData($item, $mergedFields);
        $this->getCategoryFormatData($item, $mergedFields);
        $data = array_merge($data, $mergedFields);
dd($data);
        return $data;
    }

    protected function handleAttributeType(array &$mergedFields, bool $withMedia, string $channel): void
    {
        foreach ($mergedFields as $attributeCode => $attributeValue) {
            $attribute = $this->attributeRepository->where('code', $attributeCode)->first();

            switch ($attribute->type) {
                case AttributeTypes::GALLERY_ATTRIBUTE_TYPE:
                    if ($withMedia) {
                        $mergedFields[$attributeCode] = array_map(fn ($path) => $this->getExistingFilePath($path), (array) $attributeValue);
                        $mergedFields[$attributeCode] = implode(',', $mergedFields[$attributeCode]);
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
                    $channelData = $this->channelRepository->where('code', $channel)->with(['locales', 'currencies'])->first()->toArray();
                    foreach ($channelData['currencies'] as $currency) {
                        $mergedFields[$attributeCode] = is_array($attributeValue) ? $attributeValue[$currency['code']] : $attributeValue;
                    }

                    break;

                case FieldValidator::BOOLEAN_FIELD_TYPE:
                    $mergedFields[$attributeCode] = $attributeValue ? 1 : 0;
                    break;

                default:
                    if (in_array($attribute->type, ['multiselect', 'checkbox', 'select'])) {
                        $mergedFields[$attributeCode] = $attributeValue;
                    }
                    break;
            }
        }
    }

    protected function getCategoryFormatData(array $item, &$mergedFields): void
    {
        if (! empty($item['values']['categories'])) {
            $categoryData = [];
            foreach ($item['values']['categories'] as $code) {
                $category = $this->categoryRepository->where('code', $code)->first();
                $externalId = $this->getMapping($this->credential['id'], $category->id, null, null, null, 'category')->external_id ?? null;
                if ($externalId) {
                    $categoryData[] = $code;
                }
            }

            $mergedFields['categories'] = implode('/', $categoryData);
        }
    }

    protected function getAssociationsData(array $item, array &$mergedFields): void
    {
        if ($upSells = $this->getAssociationsFormat($item, 'up_sells')) {
            $mergedFields['up_sell_skus'] = $upSells;
        }
        if ($crossSells = $this->getAssociationsFormat($item, 'cross_sells')) {
            $mergedFields['cross_sell_skus'] = $crossSells;
        }
        if ($relatedProducts = $this->getAssociationsFormat($item, 'related_products')) {
            $mergedFields['related_skus'] = $relatedProducts;
        }
    }

    protected function getAssociationsFormat(array $item, string $type): ?string
    {
        $associations = [];
        $newAssociations = null;
        if ($association = $this->getAssociations($item, $type)) {
            $products = explode(',', $association);
            foreach ($products as $sku) {
                $productData = $this->productRepository->where('sku', $sku)->first();
                if ($productData) {
                    $associations[] = $productData->sku;
                }
            }
            $newAssociations = implode(',', $associations);
        }

        return $newAssociations;
    }

    protected function getExistingFilePath(string $mediaPath): ?string
    {
        return Storage::exists($mediaPath) ? Storage::url($mediaPath) : null;
    }

    protected function createSlug(string $name): string
    {
        return trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $name))), '-');
    }
}
