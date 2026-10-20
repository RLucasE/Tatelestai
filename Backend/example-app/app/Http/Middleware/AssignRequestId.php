<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    public const ATTRIBUTE_KEY = 'request_id';

    public const HEADER_NAME = 'X-Request-ID';

    /**
     * Genera o propaga un identificador único de petición (request_id)
     * y lo vincula tanto al contexto de logs como a la cabecera de respuesta.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = self::resolveRequestId($request);

        $request->attributes->set(self::ATTRIBUTE_KEY, $requestId);

        Log::withContext([
            'request_id' => $requestId,
        ]);

        $response = $next($request);

        $response->headers->set(self::HEADER_NAME, $requestId);

        return $response;
    }

    /**
     * Obtiene el request_id actual del Request o genera uno nuevo si aún no fue asignado.
     */
    public static function resolveRequestId(?Request $request = null): string
    {
        $request ??= request();

        $existing = $request?->attributes->get(self::ATTRIBUTE_KEY);
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $incomingHeader = $request?->header(self::HEADER_NAME);
        if (is_string($incomingHeader) && preg_match('/^[a-zA-Z0-9_\-]{4,64}$/', $incomingHeader) === 1) {
            $request->attributes->set(self::ATTRIBUTE_KEY, $incomingHeader);

            return $incomingHeader;
        }

        $generated = 'req_'.bin2hex(random_bytes(6));
        $request?->attributes->set(self::ATTRIBUTE_KEY, $generated);

        return $generated;
    }
}
