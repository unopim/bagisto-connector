<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\CategoryDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Validator\Catalog\CategoryValidator;

class CategoryController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected CategoryValidator $categoryValidator
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(CategoryDataSource::class)->toJson();
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Display a single result of the resource.
     */
    public function get(string $code): JsonResponse
    {
        try {
            return response()->json(app(CategoryDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $requestData = request()->only([
            'code',
            'parent',
            'additional_data',
        ]);

        $parentId = $this->getParentIdByCode($requestData['parent']);
        unset($requestData['parent']);
        $requestData['parent_id'] = $parentId;

        $validator = $this->categoryValidator->validate($requestData);

        if ($validator instanceof \Illuminate\Validation\Validator && $validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        try {
            $this->sanitizeInput($requestData);
            Event::dispatch('catalog.category.create.before');
            $category = $this->categoryRepository->create($requestData);
            Event::dispatch('catalog.category.create.after', $category);

            return $this->successResponse(
                trans('admin::app.catalog.categories.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(string $code)
    {
        $category = $this->categoryRepository->findOneByField('code', $code);
        if (! $category) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.category.not-found', ['code' => $code]));
        }

        $requestData = request()->only(['parent', 'additional_data']);
        $parentId = null;
        if (isset($requestData['parent'])) {
            $parentId = $this->getParentIdByCode($requestData['parent']);
        }

        unset($requestData['parent']);
        $requestData['parent_id'] = $parentId;
        $id = $category->id;

        $validator = $this->categoryValidator->validate($requestData, $id);

        if ($validator instanceof \Illuminate\Validation\Validator && $validator->fails()) {
            return $this->validateErrorResponse($validator);
        }

        try {
            $this->sanitizeInput($requestData);
            Event::dispatch('catalog.category.update.before', $id);
            $category = $this->categoryRepository->update($requestData, $id);
            Event::dispatch('catalog.category.update.after', $category);

            return $this->successResponse(
                trans('admin::app.catalog.categories.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    public function sanitizeInput(&$requestData)
    {
        $fields = $this->categoryFieldRepository->findByField('status', true)
            ->where('enable_wysiwyg', '==', 1)
            ->where('type', '==', 'textarea');

        foreach ($fields as $field) {
            if ($field->value_per_locale) {
                foreach ($requestData['additional_data']['locale_specific'] ?? [] as $locale => $values) {
                    foreach ($values ?? [] as $code => $value) {
                        if (empty($value) || $field->code !== $code) {
                            continue;
                        }
                        $requestData['additional_data']['locale_specific'][$locale][$code] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    }
                }
            } else {
                foreach ($requestData['additional_data']['common'] ?? [] as $code => $value) {
                    if (empty($value) || $field->code !== $code) {
                        continue;
                    }
                    $requestData['additional_data']['common'][$code] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
        }
    }

    /**
     * Retrieves the ID of a category based on its code.
     *
     * @return int|null The ID of the category if found, otherwise null.
     */
    private function getParentIdByCode(string $code)
    {
        return $this->categoryRepository->findOneByField('code', $code)?->id;
    }
}
