<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class ConnectSchoolDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/connectdatabase') || $request->is('api/login')) {
        return $next($request);
    }
   
        $payload = JWTAuth::parseToken()->getPayload();
        $shortName = $payload->get('short_name');
        // dd($shortName);

        if (!$shortName) {
            return response()->json(['error' => 'short_name missing. Use /setup-school first.'], 400);
        }

        if($shortName == 'SACS'){
        Config::set('database.connections.dynamic', [
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'database'  => 'u333015459_arnoldstest',
            'username'  => 'u333015459_arnoldstest',
            'password'  => 'Arnolds@123',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);
        }
        elseif($shortName == 'HSCS'){
            Config::set('database.connections.dynamic', [
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'database'  => 'u333015459_hscs_test',
            'username'  => 'u333015459_hscs_test',
            'password'  => 'Hscstest@123',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);
            
        }
        else
        {
            
        }

        Config::set('database.default', 'dynamic');
        DB::purge('dynamic');
        DB::reconnect('dynamic');

        return $next($request);
    }
}
