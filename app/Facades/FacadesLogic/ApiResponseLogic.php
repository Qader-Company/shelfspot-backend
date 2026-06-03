<?php

namespace App\Facades\FacadesLogic;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponseLogic
{
    public function apiFormat(
        ?array $info = null,
        ?string $message = null,
        int $code = Response::HTTP_OK
    ): JsonResponse {
        $response = [
            'success' => $code >= 200 && $code < 300,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($info !== null) {
            $response = array_merge($response, $info);
        }

        return response()->json($response, $code);
    }

    public function failed(
        mixed $errors = null,
        ?string $message = null,
        int $code = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return $this->apiFormat(
            info: $errors !== null ? ['errors' => $errors] : null,
            message: $message,
            code: $code
        );
    }

    public function success(
        mixed $data = null,
        ?string $message = null,
        int $code = Response::HTTP_OK
    ): JsonResponse {
        return $this->apiFormat(
            info: ['data' => $data],
            message: $message,
            code: $code
        );
    }

    public function message(
        ?string $message = null,
        int $code = Response::HTTP_OK
    ): JsonResponse {
        return $this->apiFormat(
            message: $message,
            code: $code
        );
    }

    public function notFound(?string $message = null): JsonResponse
    {
        return $this->message(
            $message ?? __('api.not_found'),
            Response::HTTP_NOT_FOUND
        );
    }

    public function serverError(?string $message = null): JsonResponse
    {
        return $this->message(
            $message ?? __('api.server_error'),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function validationError(
        mixed $errors,
        ?string $message = null
    ): JsonResponse {
        return $this->failed(
            errors: $errors,
            message: $message ?? __('api.validation_error'),
            code: Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    public function unauthorized(
        ?string $message = null,
        int $code = Response::HTTP_UNAUTHORIZED
    ): JsonResponse {
        return $this->message(
            $message ?? __('api.unauthorized'),
            $code
        );
    }

    public function forbidden(
        ?string $message = null,
        int $code = Response::HTTP_FORBIDDEN
    ): JsonResponse {
        return $this->message(
            $message ?? __('api.forbidden'),
            $code
        );
    }

    public function tooManyRequests(int $retryAfterSeconds): JsonResponse
    {
        return $this->message(
            __('auth.throttle', ['seconds' => $retryAfterSeconds]),
            Response::HTTP_TOO_MANY_REQUESTS
        )->withHeaders([
            'Retry-After' => $retryAfterSeconds,
        ]);
    }

    public function created(
        mixed $data = null,
        ?string $message = null
    ): JsonResponse {
        return $this->apiFormat(
            info: $data !== null ? ['data' => $data] : null,
            message: $message ?? __('api.created'),
            code: Response::HTTP_CREATED
        );
    }

    public function updated(
        mixed $data = null,
        ?string $message = null
    ): JsonResponse {
        return $this->apiFormat(
            info: $data !== null ? ['data' => $data] : null,
            message: $message ?? __('api.updated')
        );
    }

    public function deleted(?string $message = null): JsonResponse
    {
        return $this->message($message ?? __('api.deleted'));
    }
}
