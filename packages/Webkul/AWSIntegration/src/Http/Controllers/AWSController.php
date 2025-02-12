<?php

namespace Webkul\AWSIntegration\Http\Controllers;

class AWSController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @var array
     */
    protected $_config;

    public function __construct(
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view($this->_config['view']);
    }
}