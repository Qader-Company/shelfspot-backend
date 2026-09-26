<?php

namespace App\Facades;

use App\Facades\FacadesLogic\ApiResponseLogic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;

/**
 * @method static JsonResponse apiFormat($info, $message = null, $code= Response::HTTP_OK)
 * @method static JsonResponse notFound($message = 'api.not_found')
 * @method static JsonResponse serverError($message = 'Faild to process this action, please try again.')
 * @method static JsonResponse validationError($errors,$message = 'validation error')
 * @method static JsonResponse unauthorized($message = 'unauthorized process', $code = Response::HTTP_UNAUTHORIZED)
 * @method static JsonResponse forbidden($message = 'Forbidden access', $code = Response::HTTP_FORBIDDEN)
 * @method static JsonResponse failed($errors, $message, $code)
 * @method static JsonResponse success($data, $message = null, $code = Response::HTTP_OK)
 * @method static JsonResponse message($message, $code = Response::HTTP_OK)
 * @method static JsonResponse created($data, $message = 'created successfully')
 * @method static JsonResponse deleted($message = 'Deleted successfully')
 * @method static JsonResponse updated($data,$message = 'Updated successfully')
 * @method static \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response tooManyRequests(int $retryAfterSeconds)
 */
class ApiResponse extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ApiResponse::class;
    }
}
