<?php

namespace Webkul\Bagisto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CredentialCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'shop_url' => ['required', 'url', 'unique:wk_bagisto_credential,shop_url'],
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ];
    }
}
