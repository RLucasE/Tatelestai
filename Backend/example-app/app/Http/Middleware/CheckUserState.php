<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserState
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$states): Response
    {
        $user = Auth::user();
        if (!$user || !in_array($user->state, $states)) {
            return response()->json([
                'message' => 'Unauthorized. User state does not allow access to this resource.',
                'current_state' => $user ? $user->state : 'guest',
            ], 403);
        }

        return $next($request);
    }
}
