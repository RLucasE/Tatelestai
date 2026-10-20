<?php

namespace App\Exceptions;

use App\Http\Middleware\AssignRequestId;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiErrorRenderer
{
    /**
     * Renderiza cualquier excepción como una respuesta JSON uniforme para clientes API (Vue / Android).
     */
    public static function render(Throwable $e, Request $request): JsonResponse
    {
        $requestId = AssignRequestId::resolveRequestId($request);

        if ($e instanceof ApiException) {
            return self::buildResponse(
                requestId: $requestId,
                status: $e->status(),
                message: $e->getMessage(),
                errorCode: $e->errorCode(),
                errors: null,
                publicContext: $e->publicContext(),
                rootFields: $e->rootFields()
            );
        }

        if ($e instanceof ValidationException) {
            return self::buildResponse(
                requestId: $requestId,
                status: $e->status,
                message: $e->getMessage(),
                errorCode: 'VALIDATION_ERROR',
                errors: $e->errors(),
                publicContext: []
            );
        }

        if ($e instanceof AuthenticationException) {
            return self::buildResponse(
                requestId: $requestId,
                status: 401,
                message: 'Unauthenticated.',
                errorCode: 'UNAUTHENTICATED',
                errors: null,
                publicContext: []
            );
        }

        if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException || $e instanceof UnauthorizedException) {
            $message = $e->getMessage() !== ''
                ? $e->getMessage()
                : 'No tienes permisos para realizar esta acción.';

            return self::buildResponse(
                requestId: $requestId,
                status: 403,
                message: $message,
                errorCode: 'FORBIDDEN',
                errors: null,
                publicContext: []
            );
        }

        if ($e instanceof ModelNotFoundException) {
            return self::buildResponse(
                requestId: $requestId,
                status: 404,
                message: 'Recurso no encontrado.',
                errorCode: 'RESOURCE_NOT_FOUND',
                errors: null,
                publicContext: []
            );
        }

        if ($e instanceof NotFoundHttpException) {
            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                return self::buildResponse(
                    requestId: $requestId,
                    status: 404,
                    message: 'Recurso no encontrado.',
                    errorCode: 'RESOURCE_NOT_FOUND',
                    errors: null,
                    publicContext: []
                );
            }

            $message = $e->getMessage() !== '' ? $e->getMessage() : 'Recurso o ruta no encontrada.';

            return self::buildResponse(
                requestId: $requestId,
                status: 404,
                message: $message,
                errorCode: 'NOT_FOUND',
                errors: null,
                publicContext: []
            );
        }

        if ($e instanceof ThrottleRequestsException) {
            return self::buildResponse(
                requestId: $requestId,
                status: 429,
                message: 'Demasiadas solicitudes. Por favor, intenta nuevamente en unos momentos.',
                errorCode: 'TOO_MANY_REQUESTS',
                errors: null,
                publicContext: []
            );
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $message = $status >= 500
                ? 'Ocurrió un error interno en el servidor.'
                : ($e->getMessage() !== '' ? $e->getMessage() : 'Error en la solicitud.');

            return self::buildResponse(
                requestId: $requestId,
                status: $status,
                message: $message,
                errorCode: $status >= 500 ? 'INTERNAL_ERROR' : 'HTTP_ERROR',
                errors: null,
                publicContext: []
            );
        }

        // Excepciones inesperadas (500): Nunca exponer mensajes SQL, stack trace ni detalles internos en producción.
        return self::buildResponse(
            requestId: $requestId,
            status: 500,
            message: 'Ocurrió un error interno en el servidor.',
            errorCode: 'INTERNAL_ERROR',
            errors: null,
            publicContext: []
        );
    }

    /**
     * Construye un JsonResponse con el contrato estándar y la cabecera X-Request-ID.
     *
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>  $publicContext
     * @param  array<string, mixed>  $rootFields
     */
    public static function buildResponse(
        string $requestId,
        int $status,
        string $message,
        string $errorCode,
        ?array $errors = null,
        array $publicContext = [],
        array $rootFields = []
    ): JsonResponse {
        $payload = array_merge($rootFields, [
            'message' => $message,
            'error' => $message,
            'error_code' => $errorCode,
            'errors' => $errors,
            'context' => array_merge(
                ['request_id' => $requestId],
                $publicContext
            ),
        ]);

        return response()->json($payload, $status, [
            AssignRequestId::HEADER_NAME => $requestId,
        ]);
    }
}
