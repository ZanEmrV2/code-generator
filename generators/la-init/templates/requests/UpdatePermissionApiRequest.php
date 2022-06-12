<?php

namespace App\Http\Requests\Api\Setup;
use Illuminate\Support\Str;
use App\Models\Setup\Permission;
use App\Http\Requests\ApiRequest;

class UpdatePermissionApiRequest extends ApiRequest
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
        $rules = Permission::$rules;
        $rules['display_name'] = Str::replace('unique:permissions', 'unique:permissions,display_name,' . $this->id . ',id', $rules['display_name']);
        $rules['name'] = Str::replace('unique:permissions', 'unique:permissions,name,' . $this->id . ',id', $rules['name']);
        return $rules;
    }
}
