<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class UserController extends Controller
{
    public function getUserDetails(Request $request)
    {
        try {
            $academicYear = JWTAuth::getPayload()->get('academic_year');
            $globalVariables = App::make('global_variables');
            $user = auth()->user();
            $teacher = $user->getTeacher;
            $specialRoles = DB::table('department_special_role')
                ->where('teacher_id', JWTAuth::getPayload()->get('reg_id'))
                ->where('academic_yr', $academicYear)
                ->pluck('role');
            return response()->json([
                'user' => $user,
                'specialRoles' => $specialRoles,
            ], 200);
        } catch (Exception $err) {
            return response()->json([
                'message' => 'Internal server error',
                'error' => $err->getMessage(),
                'line' => $err->getLine(),
            ], 500);
        }
    }

    public function syncTeacherUsersSchoolwise(Request $request)
    {
        try {
            // 1. Get active school
            $schoolId = DB::table('school_settings')
                ->where('is_active', 'Y')
                ->value('school_id');

            if (!$schoolId) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Active school not found.',
                    'success' => false
                ]);
            }

            // 2. Get all teacher user_ids only
            $teachers = DB::table('user_master')
                ->where('role_id', 'T')
                ->where(function ($q) {
                    $q
                        ->where('isDelete', '!=', 'Y');
                })
                ->pluck('user_id');
            if ($teachers->isEmpty()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'No teachers found',
                    'success' => true
                ]);
            }

            $now = now();

            // prepare bulk data
            $insertData = $teachers->map(function ($userId) use ($schoolId, $now) {
                return [
                    'user_id' => $userId,
                    'school_id' => $schoolId
                ];
            })->toArray();

            $staffInserted = 0;
            $teacherInserted = 0;

            DB::connection('school_database')
                ->transaction(function () use ($insertData, &$staffInserted, &$teacherInserted) {
                    // inserted staff count
                    $staffInserted = DB::connection('school_database')
                        ->table('staff_users_schoolwise')
                        ->insertOrIgnore($insertData);

                    // inserted teacher count
                    $teacherInserted = DB::connection('school_database')
                        ->table('teacher_users_schoolwise')
                        ->insertOrIgnore($insertData);
                });

            return response()->json([
                'status' => 200,
                'message' => 'Teacher users synced successfully',
                'total_teachers_checked' => $teachers->count(),
                'staff_synced' => $staffInserted,
                'teacher_synced' => $teacherInserted,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
