<?php

namespace Webkul\SunskyOnline\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Session;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\SunskyOnline\Repositories\ConfigurationRepository;
use Webkul\SunskyOnline\Services\OpenApiClient;

class ConfigurationController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct(
        protected OpenApiClient $openApiClient,
        protected ConfigurationRepository $configurationRepository,
        protected LocaleRepository $localeRepository
    ) {}

    /**
     * Index.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $credential = $this->configurationRepository->first();

        $sunskyLanguages = \Webkul\SunskyOnline\Models\SunskyLanguage::getAll();

        return view('sunsky-online::configuration.index', compact('credential', 'sunskyLanguages'));
    }

    /**
     * Store.
     *
     * @return \Illuminate\View\View
     */
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'baseUrl'   => 'required|url',
            'key'       => 'required|string',
            'secret'    => 'required|string',
        ]);

        $baseUrl = $validatedData['baseUrl'];
        $key = $validatedData['key'];
        $secret = $validatedData['secret'];

        try {
            $result = $this->openApiClient->setCredentials($key, $secret, $baseUrl)->validate();
            if (! $result) {
                Session::flash('error', 'Invalid credentials provided: ');

                return redirect()->route('sunsky_online.configuration.index');
            }
        } catch (\Exception $e) {
            Session::flash('error', 'Invalid credentials provided: '.$e->getMessage());

            return redirect()->route('sunsky_online.configuration.index');
        }

        $additional = ['localesMapping' => []];

        foreach ($request->all() as $requestKey => $requestValue) {
            if (strpos($requestKey, 'locale_mapping-') === 0) {
                $unopimLocale = str_replace('locale_mapping-', '', $requestKey);
                $additional['localesMapping'][$unopimLocale] = $requestValue;
            }
        }

        $this->configurationRepository->updateConfiguration($baseUrl, $key, $secret, $additional);

        Session::flash('success', 'Configuration updated successfully.');

        return redirect()->route('sunsky_online.configuration.index');
    }

    /**
     * Return all active locales.
     *
     * @return \Illuminate\Support\Collection
     */
    public function listLocale()
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);

        $localeRepository = $this->localeRepository;

        $query = request()->get('query');

        if ($query) {
            $localeRepository = $localeRepository->where('code', 'LIKE', '%'.$query.'%');
        }

        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        $localeRepository = $localeRepository->where('status', 1);

        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];

            $localeRepository = $localeRepository->whereIn(
                'code',
                is_array($values) ? $values : [$values]
            );
        }

        $allActivateLocale = $localeRepository->get()->toArray();

        $allLocale = array_map(function ($item) {
            return [
                'id'    => $item['code'],
                'label' => $item['name'],
            ];
        }, $allActivateLocale);

        return new JsonResponse([
            'options' => $allLocale,
        ]);
    }
}
