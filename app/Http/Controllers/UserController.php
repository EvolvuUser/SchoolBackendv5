<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function getUserDetails(Request $request) {
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
        } catch(Exception $err) {
            return response()->json([
                'message' => "Internal server error",
                'error' => $err->getMessage(),
                'line' => $err->getLine(),
            ],500);
        }
    }
}
