<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class BankAccountController extends Controller
{
    /**
     * Helper: get authenticated user safely
     */
    private function getAuthUser()
    {
        try {
            if (!JWTAuth::getToken()) {
                return null;
            }
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }
    public function index(Request $request)
    {
        try {
            // auth check
            $user = $this->getAuthUser();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            $bank_accounts = DB::table('bank_account_name')
                ->select('id', 'account_name')
                ->orderBy('id', 'desc')
                ->get();

            if ($bank_accounts->isEmpty()) {
                return response()->json([
                    'message' => 'No bank accounts found',
                    'data' => [],
                    'count' => 0
                ], 200);
            }

            return response()->json([
                'message' => 'Bank accounts fetched successfully',
                'data' => $bank_accounts,
                'count' => $bank_accounts->count()
            ], 200);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token expired'
            ], 401);

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'message' => 'Invalid token'
            ], 401);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'message' => 'Token missing'
            ], 401);

        } catch (\Throwable $err) {
            return response()->json([
                'message' => 'Internal server error',
                'error' => $err->getMessage(),
                'line' => $err->getLine(),
            ], 500);
        }
    }
}
