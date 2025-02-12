<?php

namespace Webkul\BagistoPlugin\Http\Controllers\Mappings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\BagistoPlugin\Enums\Export\CacheType;
use Webkul\BagistoPlugin\Repositories\CategoryFieldMappingRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;

class CategoryFieldController extends Controller
{
    public function __construct(
        protected CategoryFieldRepository $categoryFieldRepository,
        protected CategoryFieldMappingRepository $categoryFieldMappingRepository,
    ) {}

    public function index()
    {
        $bagistoCategoryFields = config('bagisto-category-fields');

        $bagistoCategoryFields = $this->translate($bagistoCategoryFields);

        $categoryFields = $this->categoryFieldRepository->all();

        $mappedCategoryFields = $this->categoryFieldMappingRepository->findByField('section', 'standard_field')->first();

        return view('bagisto_plugin::export.mappings.categoryfields.index', compact('bagistoCategoryFields', 'categoryFields', 'mappedCategoryFields'));
    }

    /**
     * Handles the process of storing or updating attribute mappings.
     */
    public function storeOrUpdate(): JsonResponse
    {
        try {
            // Format the data for attribute mapping
            $formatedData = $this->setFormatForMapping(request()->all());

            // Check if standard attribute mapping exists, update if exists, otherwise create a new one
            $standardAttributes = $this->categoryFieldMappingRepository->findByField('section', 'standard_field')->first();
            if ($standardAttributes) {
                $standardAttributes->update($formatedData['standard_field']);
            } else {
                $this->categoryFieldMappingRepository->create($formatedData['standard_field']);
            }

            // if exist in cache then remove from cache
            Cache::forget(CacheType::CATEGORY_FIELD_MAPPING->value);

            return new JsonResponse([
                'message' => trans('bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.success-message'),
            ], 201);
        } catch (\Exception $e) {
            // Return an error response
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Arrange Data for the attribute mapping
     */
    private function setFormatForMapping(array $data): array
    {
        $formatedData = [];

        $standardCategoryFields = json_decode($data['standard_category_fields'], true) ?? [];

        $standardCategoryFieldsDefault = $data['standard_category_fields_default'] ?? [];

        $fixedValue = [];

        foreach ($standardCategoryFieldsDefault as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $fixedValue[$key] = $value;
        }

        /** Format for standard category fields */
        $formatedData['standard_field'] = [
            'section'      => 'standard_field',
            'mapped_value' => $standardCategoryFields,
            'fixed_value'  => $fixedValue,
        ];

        return $formatedData;
    }

    public function translate(array $arrayData): array
    {
        foreach ($arrayData as $key => $value) {
            $arrayData[$key]['name'] = trans($value['name']);
            $arrayData[$key]['title'] = trans($value['title']);
        }

        return $arrayData;
    }
}
