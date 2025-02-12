<?php

namespace Webkul\TVCMall\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\TVCMall\DataGrids\ProductAttributeMappingDataGrid as DataGrid;
use Webkul\TVCMall\Repositories\ProductAttributeMappingRepository;

class ProductAttributeMappingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductAttributeMappingRepository $repository,
        protected AttributeRepository $attributeRepository
    ) {

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(DataGrid::class)->toJson();
        }

        $unopim_codes = $this->attributeRepository->all()
            ->pluck('code')
            ->map(function ($code) {
                return [
                    'id' => $code,
                    'label' => Str::replace('_', ' ', $code),
                ];
            })
            ->values()
            ->all();

        $tvc_mall_codes = collect(config('tvcmall_product_attributes'))
            ->map(function ($code) {
                return [
                    'id' => $code,
                    'label' => Str::replace('_', ' ', $code),
                ];
            })
            ->values()
            ->all();

        return view('tvc_mall::mapping.product.index', compact('unopim_codes', 'tvc_mall_codes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'unopim_code' => ['required', 'unique:tvc_mall_product_attribute_mappings'],
            'tvc_mall_code' => ['required'],
        ]);

        $this->repository->create(request()->only([
            'unopim_code',
            'tvc_mall_code',
        ]));

        return new JsonResponse([
            'message' => trans('tvc_mall::app.mapping.product.datagrid.create-success'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $locale = $this->repository->findOrFail($id);

        try {
            if ($locale) {
                $this->repository->delete($id);
            }

            return new JsonResponse([
                'message' => trans('tvc_mall::app.mapping.product.datagrid.delete-success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('tvc_mall::app.mapping.product.datagrid.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass delete locales from the locale datagrid
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $attributeIds = $massDestroyRequest->input('indices');

        foreach ($attributeIds as $attributeId) {
            $locale = $this->repository->find($attributeId);

            if (!$locale) {
                continue;
            }

            try {
                $this->repository->delete($attributeId);
            } catch (\Exception $e) {
                report($e);

                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new JsonResponse([
            'message' => trans('tvc_mall::app.mapping.product.datagrid.delete-success'),
        ], JsonResponse::HTTP_OK);
    }
}
