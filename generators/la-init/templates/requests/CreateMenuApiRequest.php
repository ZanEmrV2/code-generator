<?php

namespace App\Http\Requests\Api\Setup;

use App\Models\Setup\Menu;
use App\Http\Requests\ApiRequest;

class CreateMenuApiRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return Menu::$rules;
    }
}