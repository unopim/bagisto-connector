<?php

namespace Webkul\TVCMall\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Webkul\TVCMall\Repositories\ConfigurationRepository;
use Webkul\TVCMall\Services\OpenAPITVCMall;

class ConfigurationController extends Controller
{
    public function __construct(
        protected ConfigurationRepository $configurationRepository,
        protected OpenAPITVCMall $openApiTvcMall
    ) {}

    /**
     * render form
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $credential = $this->configurationRepository->first();

        return view('tvc_mall::configuration.index', compact('credential'));
    }

    /**
     * Store configuration
     *
     * @return \Illuminate\View\View
     */
    public function update()
    {
        $this->validate(request(), [
            'baseUrl' => 'required|url',
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $this->openApiTvcMall->setCredentials(
            $baseUrl = request()->input('baseUrl'),
            $email = request()->input('email'),
            $password = request()->input('password')
        );

        $response = $this->openApiTvcMall->generateAuthToken();

        if ($response['Success']) {
            $this->configurationRepository->updateOrCreate(
                [],
                [
                    'baseUrl' => $baseUrl,
                    'email' => $email,
                    'password' => $password,
                    'token' => $response['AuthorizationToken'],
                ]
            );

            Session::flash('success', trans('tvc_mall::app.configuration.alert.success'));
        } else {
            Session::flash('error', $response['Reason']);
        }

        return redirect()->route('tvc_mall.configuration.index');
    }
}
