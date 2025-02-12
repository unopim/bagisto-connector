<?php

namespace Webkul\Bagisto\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Bagisto\DataGrids\CredentialDataGrid;
use Webkul\Bagisto\Enums\Export\CacheType;
use Webkul\Bagisto\Enums\Services\EndPointType;
use Webkul\Bagisto\Enums\Services\MethodType;
use Webkul\Bagisto\Http\Client\HttpClientFactory;
use Webkul\Bagisto\Http\Requests\CredentialCreateRequest;
use Webkul\Bagisto\Http\Requests\CredentialUpdateRequest;
use Webkul\Bagisto\Traits\EncryptableTrait;
use Webkul\Bagisto\Repositories\CredentialRepository;
use Webkul\Core\Repositories\ChannelRepository;

class CredentialController extends Controller
{
    use EncryptableTrait;

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

        return view('bagisto::credentials.index');
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
        $requestData['shop_url'] = rtrim($requestData['shop_url'], '/');

        try {
            $httpClient = $httpClient->withBaseUri($requestData['shop_url'])
                ->withEmail($requestData['email'])
                ->withPassword($requestData['password'])
                ->make();

            // Encrypt the password for secure storage
            $requestData['password'] = $this->encryptValue($requestData['password']);

            $responseData = $this->credentialRepository->create($requestData);

            return new JsonResponse([
                'message'      => trans('bagisto::app.bagisto.credentials.index.create-success'),
                'redirect_url' => route('admin.bagisto.credentials.edit', $responseData->id),
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
            ->withPassword($this->decryptValue($credential->password))
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

        return view('bagisto::credentials.edit', compact('unoPimChannels', 'storeChannels', 'storefilterableAttribtes', 'credential'));
    }

    public function update(CredentialUpdateRequest $request, $id)
    {
        $additional = [];
        if ($request->filterableAttribtes) {
            $additional = ['additional_info' => [['filterableAttribtes' => $request->filterableAttribtes]]];
        }
        $password = $request->password;
        $credential = $this->credentialRepository->findWhere(['shop_url' => $request->shop_url, 'email' => $request->email])->first();

        if ($request->password === $credential->password) {
            $password = $this->decryptValue($credential->password);
            $additional['password'] = $credential->password;
        }

        $httpClient = new HttpClientFactory;
        $httpClient = $httpClient->withBaseUri($request->shop_url)
            ->withEmail($request->email)
            ->withPassword($password)
            ->make();

        $requestData = $request->only([
            'email',
            'password',
            'store_info',
        ]);
        
        // Encrypt password for storage
        $requestData['password'] = $this->encryptValue($password);
        $requestData = array_merge($requestData, $additional);

        $this->credentialRepository->update($requestData, $id);

        // if exist in cache then remove from cache
        Cache::forget(CacheType::CREDENTIAL->value);

        session()->flash('success', trans('bagisto::app.bagisto.credentials.index.update-success'));

        return redirect()->route('admin.bagisto.credentials.edit', $id);

    }

    public function destroy($id)
    {
        $this->credentialRepository->delete($id);

        return new JsonResponse(['message' => trans('bagisto::app.bagisto.credentials.index.delete-success')]);
    }
}
