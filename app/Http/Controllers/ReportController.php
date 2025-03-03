<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;


class ReportController extends Controller
{
    //Reports Dev Name - Manish Kumar Sharma 01-03-2025
     public function getClassofNewStudent(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $classes = DB::table('new_admission_class as n')
                            ->join('class', 'n.class_id', '=', 'class.class_id')
                            ->where('n.academic_yr',$customClaims)
                            ->select('n.*', 'class.name')
                            ->get();
                            
                return response([
                    'status'=>200,
                    'data'=>$classes,
                    'message'=>'Classes for New Admission',
                    'success'=>true
                ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
     }
     //Reports Dev Name - Manish Kumar Sharma 01-03-2025
     private function authenticateUser()
        {
            try {
                return JWTAuth::parseToken()->authenticate();
            } catch (JWTException $e) {
                return null;
            }
        }
    //Reports Dev Name - Manish Kumar Sharma 03-03-2025
    public function getReportofNewAdmission(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id'); // Get class_id from request
                $status = $request->input('status'); // Get status from request
                $admissionreport = DB::table('online_admission_form')
                                    ->join('online_admfee', 'online_admission_form.form_id', '=', 'online_admfee.form_id')
                                    ->join('class', 'class.class_id', '=', 'online_admission_form.class_id')
                                    ->select('online_admission_form.*',
                                            'online_admfee.form_id',
                                            'online_admfee.status',
                                            'online_admfee.payment_date',
                                            'online_admfee.OrderId',
                                            'class.name as classname')
                                    ->where('online_admission_form.academic_yr', $customClaims)
                                    ->where('online_admfee.status', 'S')
                                    ->orderBy('online_admission_form.adm_form_pk', 'ASC');

                                // Apply filters for class_id and status if they are provided
                                if ($class_id) {
                                    $admissionreport->where('online_admission_form.class_id', $class_id);
                                }

                                if ($status) {
                                    $admissionreport->where('online_admission_form.admission_form_status', $status);
                                }

                                // Get results and map sibling info
                                $admissionreport = $admissionreport->get()->map(function ($row) {
                                    if ($row->sibling == 'Y') {
                                        if($row->sibling_class_id != '0'){
                                        $class_id = substr($row->sibling_class_id, 0, strpos($row->sibling_class_id, '^'));
                                        $section_id = substr($row->sibling_class_id, strpos($row->sibling_class_id, '^') + 1);
                                        // dd($section_id);
                                        $class_name = DB::table('class')
                                                        ->where('class_id', $class_id)
                                                        ->select('name')
                                                        ->first();
                                                        // dd($class_name);

                                        $section_name = DB::table('section')
                                                        ->where('section_id', $section_id)
                                                        ->select('name')
                                                        ->first();
                                        //  dd($section_name);
                                        // Set sibling student info
                                        $row->sibling_student_info = $row->sibling_student_id . " (" . $class_name->name . " " . $section_name->name . ")";
                                        }
                                        else{
                                             $row->sibling_student_info = $row->sibling_student_id."()";
                                        }
                                        // $row->sibling_student_info = $row->sibling_student_id."()";

                                    } 
                                    else {
                                        $row->sibling_student_info = "No";
                                    }

                                    return $row;
                                });

                                     return response([
                                        'status'=>200,
                                        'data'=>$admissionreport,
                                        'message'=>'New Admission Report',
                                        'success'=>true
                                    ]);
                                     
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

    }
    //Reports Balance Leave Dev Name - Manish Kumar Sharma 03-03-2025
    public function getBalanceLeaveReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                // dd("Hello");
                $staff_id = $request->input('staff_id');
                $query = DB::table('leave_allocation')
                        ->join('leave_type_master', 'leave_allocation.leave_type_id', '=', 'leave_type_master.leave_type_id')
                        ->join('teacher','teacher.teacher_id','=','leave_allocation.staff_id')
                        ->select(
                            'leave_type_master.leave_type_id as leave_type_id',
                            'leave_type_master.name as name',
                            'leave_allocation.staff_id as staff_id',
                            'leave_allocation.leaves_allocated as leaves_allocated',
                            'leave_allocation.leaves_availed as leaves_availed',
                            'teacher.name as staffname'
                        )
                        ->where('leave_allocation.academic_yr', $customClaims)
                        ->where('teacher.isDelete','N');

                    // Add staff_id filter if provided
                    if ($staff_id) {
                        $query->where('leave_allocation.staff_id', $staff_id);
                    }

                    // Execute the query and get the results
                    $leaveAllocations = $query->get();
                    $leaveAllocations = $leaveAllocations->map(function ($row) {
                        // Calculate balance leave
                        $row->balance_leave = $row->leaves_allocated - $row->leaves_availed;
                        if ($row->balance_leave == (int) $row->balance_leave) {
                            // Cast to integer if the value is essentially an integer (no fraction part)
                            $row->balance_leave = (int) $row->balance_leave;
                        }
                        return $row;
                    });
                    return response([
                        'status'=>200,
                        'data'=>$leaveAllocations,
                        'message'=>'Balance Leave Report',
                        'success'=>true
                    ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

    }
    //Reports Consolidated Leave Dev Name - Manish Kumar Sharma 03-03-2025
    public function getConsolidatedLeaveReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $staff_id = $request->input('staff_id');
                $from_date = $request->input('from_date');
                $to_date = $request->input('to_date');

                $query = DB::table('leave_application')
                        ->join('teacher as staff', 'leave_application.staff_id', '=', 'staff.teacher_id') // Alias for staff
                        ->join('leave_type_master', 'leave_type_master.leave_type_id', '=', 'leave_application.leave_type_id')
                        ->leftJoin('teacher as approver', 'approver.teacher_id', '=', 'leave_application.approved_by') // LEFT JOIN for approver
                        ->select(
                            'leave_application.leave_app_id',
                            'leave_application.leave_type_id as leave_type_id',
                            'staff.name as StaffName', 
                            'staff.phone',
                            'leave_application.status as status',
                            'leave_application.leave_end_date',
                            'leave_application.leave_start_date',
                            'leave_application.no_of_days',
                            'leave_application.approved_by as approved_by',
                            'leave_type_master.name as LeaveType',
                            'approver.name as ApprovedBy'
                        )
                        ->where('leave_application.academic_yr', $customClaims);
        
            // Add the 'from_date' filter if provided
            if ($from_date) {
                $query->where('leave_application.leave_start_date', '>=', Carbon::createFromFormat('Y-m-d', $from_date)->format('Y-m-d'));
            }
        
            // Add the 'to_date' filter if provided
            if ($to_date) {
                $query->where('leave_application.leave_start_date', '<=', Carbon::createFromFormat('Y-m-d', $to_date)->format('Y-m-d'));
            }
        
            // Add the 'staff_id' filter if provided
            if ($staff_id) {
                $query->where('leave_application.staff_id', $staff_id);
            }
        
            // Execute the query and get the results
            $leaveApplications = $query->get();
            // dd($leaveApplications);
            return response([
                'status'=>200,
                'data'=>$leaveApplications,
                'message'=>'Consolidated Leave Report',
                'success'=>true
            ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

    }


    public function getStudentReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                // dd("Hello");
                $class_id = $request->input('class_id');
                $section_id = $request->input('section_id');
                $query = DB::table('student as a')
                            ->join('parent as b', 'a.parent_id', '=', 'b.parent_id')
                            ->join('class as c','c.class_id','=','a.class_id')
                            ->join('section as d','d.section_id','=','a.section_id')
                            ->where('a.isDelete', 'N')  // Condition for 'isDelete'
                            ->where('a.class_id', $class_id)  // Condition for class_id
                            ->where('a.section_id', $section_id)  // Condition for section_id
                            ->where('a.academic_yr', $customClaims)  // Condition for academic_yr
                            ->orderByRaw('roll_no, CAST(a.reg_no AS UNSIGNED)')  // Order by roll_no and cast reg_no as unsigned
                            ->select('a.*', 'b.*','c.name as classname','d.name as sectionname')  // Select all columns from both student (a) and parent (b)
                            ->get();
                            dd($query);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

    }
}
