<?php

namespace Webkul\BagistoPlugin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\BagistoPlugin\DataGrids\CredentialDataGrid;
use Webkul\BagistoPlugin\Enums\Export\CacheType;
use Webkul\BagistoPlugin\Enums\Services\EndPointType;
use Webkul\BagistoPlugin\Enums\Services\MethodType;
use Webkul\BagistoPlugin\Http\Client\HttpClientFactory;
use Webkul\BagistoPlugin\Http\Requests\CredentialCreateRequest;
use Webkul\BagistoPlugin\Http\Requests\CredentialUpdateRequest;
use Webkul\BagistoPlugin\Repositories\CredentialRepository;
use Webkul\Core\Repositories\ChannelRepository;

class CredentialController extends Controller
{
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CredentialRepository $credentialRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(CredentialDataGrid::class)->toJson();
        }

        return view('bagisto_plugin::credentials.index');
    }

    /**
     * Store a credential.
     */
    public function store(CredentialCreateRequest $request): JsonResponse
    {
        $requestData = $request->only([
            'email',
            'password',
            'shop_url',
        ]);

        $httpClient = new HttpClientFactory;

        try {
            $httpClient = $httpClient->withBaseUri($requestData['shop_url'])
                ->withEmail($requestData['email'])
                ->withPassword($requestData['password'])
                ->make();

            $this->credentialRepository->create($requestData);

            return new JsonResponse([
                'message' => trans('bagisto_plugin::app.bagisto-plugin.credentials.index.create-success'),
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse([
                'errors' => $e->validator->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified resource for editing.
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the credential with the given ID is not found.
     */
    public function edit(int $id)
    {
        $credential = $this->credentialRepository->find($id);

        if (! $credential) {
            abort(404);
        }

        $httpClient = new HttpClientFactory;
        $httpClient = $httpClient->withBaseUri($credential->shop_url)
            ->withEmail($credential->email)
            ->withPassword($credential->password)
            ->make();

        $storeChannels = $httpClient->toRequest(MethodType::GET->value, EndPointType::GET_CHANNELS->value);
        $storefilterableAttribtes = $httpClient->toRequest(MethodType::GET->value, EndPointType::GET_IS_FILTERABLE_ATTRIBUTES->value, ['is_filterable' => 1]);

        $channels = $this->channelRepository->all();

        $credential->store_info = array_map(function ($channel) {
            return json_decode($channel);
        }, $credential->store_info ?? []);

        $unoPimChannels = [];

        foreach ($channels as $channel) {
            $unoPimChannels[] = [
                'id'         => $channel->id,
                'name'       => ! empty($channel->name) ? $channel->name : $channel->code,
                'code'       => $channel->code,
                'currencies' => $channel->currencies->toArray(),
                'locales'    => $channel->locales->toArray(),
            ];
        }

        return view('bagisto_plugin::credentials.edit', compact('unoPimChannels', 'storeChannels', 'storefilterableAttribtes', 'credential'));
    }

    public function update(CredentialUpdateRequest $request, $id)
    {
        $additinal = [];
        if ($request->filterableAttribtes) {
            $additinal = ['additional_info' => [['filterableAttribtes' => $request->filterableAttribtes]]];
        }

        $httpClient = new HttpClientFactory;
        $httpClient = $httpClient->withBaseUri($request->shop_url)
            ->withEmail($request->email)
            ->withPassword($request->password)
            ->make();

        $requestData = $request->only([
            'email',
            'password',
            'store_info',
        ]);

        $requestData = array_merge($additinal, $requestData);

        $this->credentialRepository->update($requestData, $id);

        // if exist in cache then remove from cache
        Cache::forget(CacheType::CREDENTIAL->value);

        session()->flash('success', trans('bagisto_plugin::app.bagisto-plugin.credentials.index.update-success'));

        return redirect()->route('admin.bagisto_plugin.credentials.edit', $id);

    }

    public function destroy($id)
    {
        $this->credentialRepository->delete($id);

        return new JsonResponse(['message' => trans('bagisto_plugin::app.bagisto-plugin.credentials.index.delete-success')]);
    }
}
