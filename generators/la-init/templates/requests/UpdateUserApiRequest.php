<?php

namespace App\Http\Requests\Api\Setup;

use App\Models\Setup\User;
use Illuminate\Support\Str;
use App\Http\Requests\ApiRequest;

class UpdateUserApiRequest extends ApiRequest
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
        $rules = User::$rules;
        $rules['email'] = Str::replace('unique:users', 'unique:users,email,' . $this->id . ',id', $rules['email']);
        $rules['username'] = Str::replace('unique:users', 'unique:users,username,' . $this->id . ',id', $rules['username']);
        return $rules;
    }
}
