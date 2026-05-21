<?php

namespace App\Facades\FacadesLogic;

use Illuminate\Http\Response;

class ApiResponseLogic
{
    /**
     * @param $info
     * @param $message
     * @param $code
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|Response
     */
    function apiFormat($info, $message = null, $code = Response::HTTP_OK)
    {
        $response = [
            'success' => ($code >= 200 && $code < 300),
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($info) {
            $key = key($info);
            $response[$key] = $info[$key];
        }

        return response($response, $code);
    }

    public function failed($errors, $message, $code)
    {
        $errors = $errors ? ['errors' => $errors] : null;
        return $this->apiFormat(
            $errors,
            $message,
            $code
        );
    }

    public function success($data, $message = null, $code = Response::HTTP_OK)
    {
        return $this->apiFormat(
            ['data' => $data],
            __($message),
            $code
        );
    }

    public function message($message, $code = Response::HTTP_OK)
    {
        return $this->apiFormat(
            null,
            __($message),
            $code
        );
    }

    public function notFound($message = 'apiMessages.not_found')
    {
        return $this->apiFormat(
            null,
            __($message),
            Response::HTTP_NOT_FOUND
        );
    }

    public function serverError($message = 'apiMessages.server_error')
    {
        return $this->apiFormat(
            null,
            __($message),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function validationError($errors, $message = 'apiMessages.validation_error')
    {
        return $this->failed(
            $errors,
            __($message),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    public function unauthorized($message = 'apiMessages.unauthorized', $code = Response::HTTP_UNAUTHORIZED)
    {
        return $this->message(
            __($message), 
            $code
        );
    }

    public function forbidden($message = 'apiMessages.forbidden', $code = Response::HTTP_FORBIDDEN)
    {
        return $this->message(
            __($message), 
            $code
        );
    }

    public function created($data = null, $message = 'apiMessages.created')
    {
        return ($data) ? 
            $this->success(
                $data,
                __($message),
                Response::HTTP_CREATED
            ) : 
            $this->message(
                __($message),
                Response::HTTP_CREATED
            );
    }

    public function updated($data = null, $message = 'apiMessages.updated')
    {
        return ($data) ? 
            $this->success(
                $data,
                __($message)
            ) :
            $this->message(
                __($message)
            );
    }

    public function deleted($message = 'apiMessages.deleted')
    {
        return $this->message( 
            __($message), 
            Response::HTTP_NO_CONTENT
        );
    }
}

