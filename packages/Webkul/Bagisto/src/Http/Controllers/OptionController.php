<?php

namespace Webkul\Bagisto\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Bagisto\Repositories\CredentialRepository;
use Webkul\Bagisto\Traits\ApiRequest;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;

class OptionController extends Controller
{
    use ApiRequest;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CredentialRepository $bagistoRepository,
        protected AttributeRepository $attributeRepository,
        protected ChannelRepository $channelRepository,
        protected CurrencyRepository $currencyRepository,
        protected LocaleRepository $localeRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
    ) {}

    /**
     * Return All credentials
     */
    public function listBagistoCredential(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);
        $query = request()->get('query');

        $bagistoRepository = $this->applySearchIdentifiers($this->bagistoRepository, $queryParams, 'id');

        $bagistoRepository = $this->searchByCode($bagistoRepository, $query, 'shop_url');

        $allCredential = $bagistoRepository->get()->toArray();

        return new JsonResponse([
            'options' => $allCredential,
        ]);
    }

    /**
     * Return All Channels
     */
    public function listChannel(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);
        $query = request()->get('query');

        $channelRepository = $this->applySearchIdentifiers($this->channelRepository, $queryParams, 'code');

        $channelRepository = $this->searchByCode($channelRepository, $query);

        $allActivateChannel = $channelRepository->get()->toArray();

        foreach ($allActivateChannel as $key => $channel) {
            $allActivateChannel[$key]['name'] = ! empty($channel['name']) ? $channel['name'] : $channel['code'];
        }

        return new JsonResponse([
            'options' => $allActivateChannel,
        ]);
    }

    /**
     * Return All Currency
     */
    public function listCurrency(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);
        $query = request()->get('query');

        $currencyRepository = $this->applySearchIdentifiers($this->currencyRepository->where('status', 1), $queryParams, 'code');

        $currencyRepository = $this->searchByCode($currencyRepository, $query);

        $allActivateCurrency = $currencyRepository->get()->toArray();

        return new JsonResponse([
            'options' => $allActivateCurrency,
        ]);
    }

    /**
     * Return All Locale
     */
    public function listLocale(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);
        $query = request()->get('query');

        $localeRepository = $this->applySearchIdentifiers($this->localeRepository->where('status', 1), $queryParams, 'code');

        $localeRepository = $this->searchByCode($localeRepository, $query);

        $allActivateLocale = $localeRepository->get()->toArray();

        return new JsonResponse([
            'options' => $allActivateLocale,
        ]);
    }

    /**
     * Return All family
     */
    public function listFamily(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);
        $query = request()->get('query');

        $attributeFamilyRepository = $this->applySearchIdentifiers($this->attributeFamilyRepository, $queryParams, 'code');
        $attributeFamilyRepository = $this->searchByCode($attributeFamilyRepository, $query);

        $allActivateFamilies = $attributeFamilyRepository->get()->toArray();

        foreach ($allActivateFamilies as $key => $family) {
            $allActivateFamilies[$key]['name'] = ! empty($family['name']) ? $family['name'] : $family['code'];
        }

        return new JsonResponse([
            'options' => $allActivateFamilies,
        ]);
    }

    /**
     * Return All type
     */
    public function listType(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);
        $query = request()->get('query');

        $supportedTypes = config('product_types');

        if (isset($queryParams['identifiers']['values'])) {
            foreach ($supportedTypes as $id => $type) {
                $label = trans($type['name']);
                if (in_array($id, $queryParams['identifiers']['values'])) {
                    $types[] = [
                        'id'    => $id,
                        'label' => $label,
                    ];
                }
            }

            return new JsonResponse([
                'options' => $types,
            ]);
        }

        $types = [];

        foreach ($supportedTypes as $id => $type) {
            $label = trans($type['name']);

            if (! $query || stripos($label, $query) !== false) {
                $types[] = [
                    'id'    => $id,
                    'label' => $label,
                ];
            }
        }

        return new JsonResponse([
            'options' => $types,
        ]);
    }

    protected function applySearchIdentifiers($repository, array $queryParams, ?string $code = null)
    {
        $searchIdentifiers = $queryParams['identifiers']['columnName'] ?? null;

        if (! empty($searchIdentifiers)) {
            $values = $queryParams['identifiers']['values'] ?? [];
            $repository = $repository->whereIn($code ?? $searchIdentifiers, is_array($values) ? $values : [$values]);
        }

        return $repository;
    }

    protected function searchByCode($repository, $query, string $code = 'code')
    {
        if (! empty($query)) {
            $repository = $repository->where($code, 'LIKE', '%'.$query.'%');
        }

        return $repository;
    }

    public function fetchAttribute()
    {
        $attribute = $this->attributeRepository->where('code', request()->query('code'))->first();

        if ($attribute) {
            return response()->json([
                'attribute' => [
                    'id'    => $attribute->id,
                    'name'  => ! empty($attribute->name) ? $attribute->name : $attribute->code,
                    'code'  => $attribute->code,
                    'type'  => $attribute->type,
                    'label' => trans('admin::app.catalog.attributes.create.'.$attribute->type),
                ],
                'data' => true,
            ]);
        }

        return response()->json(['data' => false, 'message' => 'Attribute not found'], 404);
    }
}
