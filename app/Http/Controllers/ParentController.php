<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DailyTodo;
use App\Models\Event;
use App\Models\StaffNotice;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ParentController extends Controller
{
    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }
    public function getParentDetails(Request $request)
    {
        try {
            // Authenticate User
            $user = $this->authenticateUser();
            $academic_yr = JWTAuth::getPayload()->get('academic_year');

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized user'
                ], 401);
            }

            // Fetch Parent
            $parent = DB::table('parent')
                ->where('parent_id', $user->reg_id)
                ->first();

            if (!$parent) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Parent record not found'
                ], 404);
            }

            $userMaster = DB::table('user_master')->where('reg_id' , $user->reg_id)->first();

            // Fetch Children
            $children = DB::table('student')
                ->select(
                    'student.*',
                    'class.name as class_name',
                    'section.name as section_name'
                )
                ->leftJoin('class', 'student.class_id', '=', 'class.class_id')
                ->leftJoin('section', 'student.section_id', '=', 'section.section_id')
                ->where('student.parent_id', $user->reg_id)
                ->where('student.academic_yr',$academic_yr)
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Parent details fetched successfully',
                'data' => [
                    'parent' => $parent,
                    'children' => $children,
                    'userMaster' => $userMaster,
                ]
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {

            // Log::error('Database error in getParentDetails', [
            //     'error' => $e->getMessage(),
            //     'user_id' => $user->reg_id ?? null
            // ]);

            return response()->json([
                'status'  => false,
                'message' => 'Database error occurred',
                'error' => $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {

            Log::error('Unexpected error in getParentDetails', [
                'error' => $e->getMessage(),
                'user_id' => $user->reg_id ?? null
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }
}
