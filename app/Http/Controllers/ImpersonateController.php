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
    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    public function impersonate(Request $request)
    {
        try {
            $payload = JWTAuth::getPayload();
            $superAdmin = auth()->user();
            $shortName = $payload->get('short_name');
            $super_admin_id = $payload->get('reg_id');

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
            ]);

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

            $impersonation_session_id = DB::table('impersonation_sessions')->insertGetId([
                'super_admin_id' => $super_admin_id,
                'impersonated_user_id' => $user->reg_id,
                'impersonated_role' => $user->role_id,
                'started_at' => now(),
                'start_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

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
                'isid' => $impersonation_session_id,
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

    public function getRoles() {
        try {
            $user = $this->authenticateUser();

            // get all the teachers 
            $data = DB::table('user_master')
            ->leftJoin('teacher' , 'teacher.teacher_id' , '=' , 'user_master.reg_id')
            ->select(
                'user_master.role_id',
            )
            ->distinct()
            ->where('user_master.isDelete' , 'N')->get();

            return response()->json([
                'data' => $data,
                'count' => count($data),
            ] , 200);

        } catch(Exception $err) {
            return response()->json([
                'status' => false,
                'message' => "Something went wrong",
                'error' => $err->getMessage(),
                'line' => $err->getLine(),
            ] , 500);
        }
    }

    public function getUsers(Request $request) {
        try {
            $user = $this->authenticateUser();
            $role_id = $request->query('role_id');

            // get all the teachers 
            $data = DB::table('user_master')
            // ->leftJoin('teacher' , 'teacher.teacher_id' , '=' , 'user_master.reg_id')
            ->select(
                'user_master.user_id' , 
                'user_master.reg_id' , 
                'user_master.role_id',
                'user_master.name',
            )->where('user_master.isDelete' , 'N');

            if($role_id) {
                $data->where('user_master.role_id' , $role_id);
            }

            $data = $data->get();

            return response()->json([
                'data' => $data,
                'count' => count($data),
            ] , 200);

        } catch(Exception $err) {
            return response()->json([
                'status' => false,
                'message' => "Something went wrong",
                'error' => $err->getMessage(),
                'line' => $err->getLine(),
            ] , 500);
        }
    }

}
