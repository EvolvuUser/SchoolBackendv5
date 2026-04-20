<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use DB;

class ReadmissionController extends Controller
{
    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    public function saveReadmission(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|integer',
                'current_class_id' => 'required|integer',
                'next_class_id' => 'nullable|integer',
                'confirm' => 'required|in:Y,N',
                'academic_yr' => 'required|string',
            ]);

            $data = [
                'current_class_id' => $request->current_class_id,
                'next_class_id' => $request->next_class_id ?? 0,
                'confirm' => $request->confirm,
                'academic_yr' => $request->academic_yr,
            ];

            DB::table('confirmation_readmission')->updateOrInsert(
                ['student_id' => $request->student_id],  // condition
                $data  // values
            );

            return response()->json([
                'status' => 200,
                'message' => 'Record saved successfully',
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getreAdmissionManagement(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_year = JWTAuth::getPayload()->get('academic_year');
        $role = $user->role_id;

        $data = DB::table('readmission_class as a')
            ->leftJoin('class', 'class.class_id', '=', 'a.class_id')
            ->select('a.*', 'class.name as class_name')
            ->where('a.academic_yr', $academic_year)
            ->orderBy('a.class_id', 'ASC')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ], 200);
    }

    public function getreAdmissionClassesNotCreated(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_year = JWTAuth::getPayload()->get('academic_year');

        $data = DB::table('class')
            ->select(
                'class.class_id',
                'class.name',
                'class.academic_yr',
                'class.department_id'
            )
            ->where('class.academic_yr', $academic_year)
            ->whereNotIn('class.class_id', function ($query) {
                $query
                    ->select('class_id')
                    ->from('readmission_class');
            })
            ->orderBy('class.class_id', 'asc')
            ->get();

        // $data = DB::table('class')
        //     ->select(
        //         'class.class_id',
        //         'class.name',
        //         'class.academic_yr',
        //         'class.department_id'
        //     )
        //     ->where('class.academic_yr', $academic_year)
        //     ->orderBy('class.class_id', 'asc')
        //     ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ], 200);
    }

    public function createreAdmissionForm(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            // ✅ Basic validation
            $request->validate([
                'class_id' => 'required|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);

            // ✅ Prepare data
            $data = [
                'class_id' => $request->input('class_id'),
                'start_date' => $request->input('start_date')
                    ? Carbon::parse($request->input('start_date'))->format('Y-m-d')
                    : null,
                'end_date' => $request->input('end_date')
                    ? Carbon::parse($request->input('end_date'))->format('Y-m-d')
                    : null,
                'publish' => $request->input('publish') ?? 'N',
                'academic_yr' => $academic_year
            ];

            // ❌ Academic year missing
            if (!$data['academic_yr']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic year not found'
                ], 400);
            }

            // 🔍 Check if class already exists
            $exists = DB::table('readmission_class')
                ->where('class_id', $data['class_id'])
                ->where('academic_yr', $data['academic_yr'])
                ->first();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'An re admission form already exists for the selected class.'
                ], 409);  // Conflict
            }

            // ✅ Insert record
            DB::table('readmission_class')->insert($data);

            return response()->json([
                'success' => true,
                'message' => 'Re Admission class added successfully',
                'data' => $data
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log::error('Admission Form Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Admission Form Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function viewreAdmissionForm(Request $request, $id)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 📅 Academic year from JWT
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            if (!$academic_year) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic year not found'
                ], 400);
            }

            // 🔍 Fetch admission form
            $admissionForm = DB::table('readmission_class')
                ->select('readmission_class.*', 'class.name as class_name')
                ->leftJoin('class', 'class.class_id', 'readmission_class.class_id')
                ->where('rc_id', $id)
                ->where('readmission_class.academic_yr', $academic_year)
                ->first();

            if (!$admissionForm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admission form not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Re Admission form fetched successfully',
                'data' => $admissionForm
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        } catch (\Exception $e) {
            // \Log::error('View Admission Form Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'View Admission Form Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deletereAdmissionForm(Request $request, $id)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 📅 Academic year from JWT
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            if (!$academic_year) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic year not found'
                ], 400);
            }

            // 🔍 Get admission class
            $admissionClass = DB::table('readmission_class')
                ->where('rc_id', $id)
                ->where('academic_yr', $academic_year)
                ->first();

            if (!$admissionClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Re-Admission class not found'
                ], 404);
            }

            // // 🔗 Check if class is already used
            // $inUse = DB::table('online_admission_form')
            //     ->where('class_id', $admissionClass->class_id)
            //     ->exists();

            // if ($inUse) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Application form has been filled for this class, cannot delete'
            //     ], 409);  // Conflict
            // }

            // 🗑 Delete safely
            DB::table('readmission_class')
                ->where('rc_id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Re-Admission class deleted successfully'
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        } catch (\Exception $e) {
            \Log::error('Delete Admission Form Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while deleting admission class'
            ], 500);
        }
    }

    public function updatereAdmissionForm(Request $request, $id)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 📅 Academic year from JWT
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            if (!$academic_year) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic year not found'
                ], 400);
            }

            // ✅ Validation
            $request->validate([
                'class_id' => 'required|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date'
            ]);

            // 🧾 Prepare update data
            $data = [
                'class_id' => $request->input('class_id'),
                'start_date' => $request->input('start_date')
                    ? Carbon::parse($request->input('start_date'))->format('Y-m-d')
                    : null,
                'end_date' => $request->input('end_date')
                    ? Carbon::parse($request->input('end_date'))->format('Y-m-d')
                    : null,
                'publish' => $request->input('publish') ?? 'N',
                'academic_yr' => $academic_year,
            ];

            // 🔍 Check record exists
            $admissionClass = DB::table('readmission_class')
                ->where('rc_id', $id)
                ->where('academic_yr', $academic_year)
                ->first();

            if (!$admissionClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admission class not found'
                ], 404);
            }

            // 🚫 Duplicate class check (except current record)
            $exists = DB::table('readmission_class')
                ->where('class_id', $data['class_id'])
                ->where('academic_yr', $academic_year)
                ->where('rc_id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'An Re-Admission form already exists for the selected class.'
                ], 409);
            }

            // ✏️ Update record
            DB::table('readmission_class')
                ->where('rc_id', $id)
                ->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Re-Admission class updated successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating admission class'
            ], 500);
        }
    }

    public function getNextClassWithReadmission($current_class_id)
    {
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $today = Carbon::today()->toDateString();

        // ✅ Step 1: Check readmission for CURRENT class
        $allowed = DB::table('readmission_class')
            ->where('class_id', $current_class_id)
            ->where('publish', 'Y')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        // ✅ Step 2: Get next class mapping
        $classes = DB::table('currentclass_nextclass_mapping as m')
            ->join('class as c', 'c.class_id', '=', 'm.next_class_id')
            ->where('m.current_class_id', $current_class_id)
            ->select(
                'm.next_class_id',
                'c.name as classname',
                'c.academic_yr'
            )
            ->get();

        if ($classes->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No mapping found'
            ]);
        }

        // ✅ Step 3: Attach same readmission status
        $data = $classes->map(function ($item) use ($allowed) {
            return [
                'next_class_id' => $item->next_class_id,
                'classname' => $item->classname,
                'academic_yr' => $item->academic_yr,
                'readmission_allowed' => $allowed
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }
}
