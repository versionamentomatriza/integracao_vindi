<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIntegrationToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validToken = config('services.integration.token');
        $authorizationHeader = $request->header('Authorization');

        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return response()->json(['error' => 'Authorization header ausente ou inválido.'], 401);
        }

        $receivedToken = substr($authorizationHeader, 7);

        if ($receivedToken !== $validToken) {
            return response()->json(['error' => 'Token inválido.'], 401);
        }

        return $next($request);
    }
}
