<?php

namespace App\Http\Requests\Api\Setup;

use App\Models\Setup\Group;
use Illuminate\Support\Str;
use App\Http\Requests\ApiRequest;

class UpdateGroupApiRequest extends ApiRequest
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
        $rules = Group::$rules;
        $rules['name'] = Str::replace('unique:groups', 'unique:groups,name,' . $this->id . ',id', $rules['name']);
        return $rules;
    }
}
