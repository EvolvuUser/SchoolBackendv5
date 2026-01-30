<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class ImpersonationReadOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $payload = JWTAuth::parseToken()->getPayload();

        // Only restrict when impersonating
        if ($payload->get('impersonation') === true) {

            // Block write methods
            if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'IMPERSONATION_READ_ONLY',
                    'message' => 'Write actions are disabled during impersonation'
                ], 403);
            }
        }

        return $next($request);
    }
}
