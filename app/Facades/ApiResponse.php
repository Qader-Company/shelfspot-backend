<?php

namespace App\Facades;

use App\Facades\FacadesLogic\ApiResponseLogic;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ApiResponseLogic apiFormat($info, $message = null, $code= Response::HTTP_OK)
 * @method static ApiResponseLogic notFound($message = 'apiMessages.not_found')
 * @method static ApiResponseLogic serverError($message = 'Faild to process this action, please try again.')
 * @method static ApiResponseLogic validationError($errors,$message = 'validation error')
 * @method static ApiResponseLogic unauthorized($message = 'unauthorized process', $code = Response::HTTP_UNAUTHORIZED)
 * @method static ApiResponseLogic forbidden($message = 'Forbidden access', $code = Response::HTTP_FORBIDDEN)
 * @method static ApiResponseLogic failed($errors, $message, $code)
 * @method static ApiResponseLogic success($data, $message = null, $code = Response::HTTP_OK)
 * @method static ApiResponseLogic message($message, $code = Response::HTTP_OK)
 * @method static ApiResponseLogic created($data, $message = 'created successfully')
 * @method static ApiResponseLogic deleted($message = 'Deleted successfully')
 * @method static ApiResponseLogic updated($data,$message = 'Updated successfully')
 */
class ApiResponse extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ApiResponse::class;
    }
}
