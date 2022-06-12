<?php

namespace App\Http\Requests\Api\Setup;

use Illuminate\Support\Str;
use App\Models\Setup\Role;
use App\Http\Requests\ApiRequest;

class UpdateRoleApiRequest extends ApiRequest
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
        $rules = Role::$rules;
        $rules['name'] = Str::replace('unique:roles', 'unique:roles,name,' . $this->id . ',id', $rules['name']);
        return $rules;
    }
}
