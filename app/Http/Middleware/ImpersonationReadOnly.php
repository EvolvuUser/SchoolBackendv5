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

            // Write to impersonation_route_logs 
            DB::table('impersonation_route_logs')->insert([
                'impersonation_session_id' => $payload->get('impersonation_session_id'),
                'method' => $request->method(),
                'route' => $request->path(),
            ]);

            // Block write methods
            if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
                // write to impersonation_blocked_actions

                DB::table('impersonation_blocked_actions')->insert([
                    'impersonation_session_id' => $payload->get('impersonation_session_id'),
                    'method' => $request->method(),
                    'route' => $request->path(),
                    'reason' => 'WRITE_BLOCKED'
                ]);

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
