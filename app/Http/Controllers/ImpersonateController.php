<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DailyTodo;
use App\Models\Event;
use App\Models\StaffNotice;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\UserMaster;
use App\Models\Setting;

class ImpersonateController extends Controller
{
    public function impersonate(Request $request)
    {
        try {
            $payload = JWTAuth::getPayload();
            $superAdmin = auth()->user();

            // 1️⃣ Only Super Admin (U)
            if ($payload->get('role_id') !== 'U') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // 2️⃣ Prevent nested impersonation
            if ($payload->get('impersonation') === true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already impersonating'
                ], 403);
            }

            $request->validate([
                'user_id' => 'required',
                'short_name' => 'required',
            ]);

            // 3️⃣ Switch DB (SAME as login)
            $shortName = $request->short_name;
            if (!array_key_exists($shortName, config('database.connections'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid school'
                ], 400);
            }

            config(['database.default' => $shortName]);

            // 4️⃣ Load target user
            $user = UserMaster::where('user_id', $request->user_id)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // 5️⃣ Load SAME data as login
            $academic_yr = Setting::where('active', 'Y')->first()->academic_yr;
            $schoolName = Setting::where('active', 'Y')->first()->institute_name;
            $settings = DB::table('school_settings')->where('is_active', 'Y')->first();
            $settings_new = Setting::where('active', 'Y')->first();

            // 6️⃣ Build claims (MATCH login payload)
            $customClaims = [
                'role_id' => $user->role_id,
                'reg_id' => $user->reg_id,
                'academic_year' => $academic_yr,
                'school_name' => $schoolName,
                'short_name' => $shortName,
                'settings' => $settings,
                'settings_new' => $settings_new,

                // 🔥 impersonation metadata
                'impersonation' => true,
                'impersonated_by' => $payload->get('reg_id'),
                'impersonator_role' => $payload->get('role_id'),
            ];

            // 7️⃣ Generate token
            $token = JWTAuth::claims($customClaims)->fromUser($user);

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $user,
                'impersonation' => [
                    'active' => true,
                    'impersonated_by' => $payload->get('reg_id'),
                    'target_role' => $user->role_id,
                    'target_reg_id' => $user->reg_id,
                    'school' => $shortName
                ]
            ]);

        } catch (\Exception $e) {
            // Log::error('Impersonation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Could not impersonate user',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function exitImpersonation()
    {
        $payload = JWTAuth::parseToken()->getPayload();

        if ($payload->get('impersonation') !== true) {
            return response()->json([
                'success' => false,
                'message' => 'Not impersonating'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Impersonation ended'
        ]);
    }

}
