<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class PreventImpersonationAbuse
{
    public function handle($request, Closure $next)
    {
        $payload = JWTAuth::parseToken()->getPayload();

        if ($payload->get('impersonation') === true) {

            // ❌ impersonated user cannot impersonate again
            if ($request->is('api/impersonate')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nested impersonation not allowed'
                ], 403);
            }

            // ❌ impersonated user cannot access admin-only routes
            if (in_array($payload->get('role_id'), ['U'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access blocked during impersonation'
                ], 403);
            }
        }

        return $next($request);
    }
}