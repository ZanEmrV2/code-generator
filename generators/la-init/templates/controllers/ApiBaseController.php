<?php

namespace App\Http\Controllers;

use App\Utils\ResponseUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

/**
 * @SWG\Swagger(
 *   basePath="/api",
 *   @SWG\Info(
 *     title="App APIs",
 *     version="1.0.0",
 *   )
 * )
 * This class should be parent class for other API controllers
 * Class ApiBaseController
 */
class ApiBaseController extends Controller
{
    public function sendResponse($result, $message): JsonResponse
    {
        return Response::json(ResponseUtil::makeResponse($message, $result));
    }

    public function sendError($error, $code = 404): JsonResponse
    {
        return Response::json(ResponseUtil::makeError($error), $code);
    }

    public function sendSuccess($message): JsonResponse
    {
        return Response::json([
            'success' => true,
            'message' => $message
        ], 200);
    }
}
