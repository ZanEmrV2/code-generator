<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Utils\ResponseUtil;
use Illuminate\Support\Arr;

use Response;

class ApiRequest extends FormRequest
{
    /**
     * Get the proper failed validation response for the request.
     *
     * @param array $errors
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response(array $errors)
    {
        $messages = implode(' ', Arr::flatten($errors));

        return Response::json(ResponseUtil::makeError($messages), 400);
    }
}
