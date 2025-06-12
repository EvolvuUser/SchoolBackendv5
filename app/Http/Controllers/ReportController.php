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
                    'message'=>'This User Doesnot have Permission for the Class of New Admission.',
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
                                        $class = $class_name->name ?? 'N/A';
                                        $section = $section_name->name ?? 'N/A';
                                        
                                        $row->sibling_student_info = $row->sibling_student_id . " ({$class} {$section})";
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
                    'message'=>'This User Doesnot have Permission for the New Admission Report',
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
                    'message'=>'This User Doesnot have Permission for the Balance Leave Report',
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
                    'message'=>'This User Doesnot have Permission for the Consolidated Leave Report',
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
                // dd($customClaims);
                $section_id = $request->input('section_id');
                $studentdetails = DB::table('student as a')
                                    ->join('parent as b', 'a.parent_id', '=', 'b.parent_id')
                                    ->join('class as c','c.class_id','=','a.class_id')
                                    ->join('section as d','d.section_id','=','a.section_id')
                                    ->where('a.isDelete', 'N')  // Condition for 'isDelete'
                                    ->where('a.section_id', $section_id)  // Condition for section_id
                                    ->where('a.academic_yr', $customClaims)  // Condition for academic_yr
                                    ->orderByRaw('roll_no, CAST(a.reg_no AS UNSIGNED)')  // Order by roll_no and cast reg_no as unsigned
                                    ->select('a.*', 'b.*','c.name as classname','d.name as sectionname')  // Select all columns from both student (a) and parent (b)
                                    ->get();
                            $mappedStudentDetails = [];
                        foreach($studentdetails as $studentdetail){
                            // dd($studentdetail);
                            $acd_yr_from = substr($customClaims, 0, 4) - 1;
                            $acd_yr_to = substr($customClaims, 5, 4) - 1;
                            $prev_acd_yr = $acd_yr_from . "-" . $acd_yr_to;
                            $prev_yr_student_id = DB::table('student')
                                                   ->select('student_id')
                                                   ->where('academic_yr', $prev_acd_yr)
                                                   ->where('parent_id', $studentdetail->parent_id)
                                                   ->where('first_name',$studentdetail->first_name)
                                                   ->first();
                                                //  dd($prev_yr_student_id);
                            if(!empty($prev_yr_student_id)){
                            $class = DB::table('student as s')
                                        ->join('class as c', 's.class_id', '=', 'c.class_id')
                                        ->where('s.student_id', $prev_yr_student_id->student_id)
                                        ->first();  
                            //   dd($class);

                                // Based on the class, create the respective query
                                if ($class->name == '9') {
                                    $result = DB::table('student_marks as sm')
                                        ->join('subjects_on_report_card as sb', 'sm.subject_id', '=', 'sb.sub_rc_master_id')
                                        ->join('exam as e', 'sm.exam_id', '=', 'e.exam_id')
                                        ->selectRaw('round(sum(sm.total_marks) / sum(sm.highest_total_marks) * 100, 2) as total_percent')
                                        ->where('sm.class_id', '=', DB::raw('sb.class_id'))
                                        ->where('sb.subject_type', '=', 'Scholastic')
                                        ->where('e.name', 'like', 'Final%')
                                        ->where('sm.publish', '=', 'Y')
                                        ->where('sm.student_id', '=', $prev_yr_student_id->student_id)
                                        ->groupBy('sm.student_id')
                                        ->first();  // Using first() to get the single result
                                } elseif ($class->name == '11') {
                                    $result = DB::table('student_marks as sm')
                                        ->join('subjects_on_report_card as sb', 'sm.subject_id', '=', 'sb.sub_rc_master_id')
                                        ->join('exam as e', 'sm.exam_id', '=', 'e.exam_id')
                                        ->selectRaw('round(sum(sm.total_marks) / sum(sm.highest_total_marks) * 100, 2) as total_percent')
                                        ->where('sm.class_id', '=', DB::raw('sb.class_id'))
                                        ->where('e.name', 'like', 'Final%')
                                        ->where('sb.subject_type', '<>', 'Co-Scholastic_hsc')
                                        ->where('sm.publish', '=', 'Y')
                                        ->where('sm.student_id', '=', $prev_yr_student_id->student_id)
                                        ->groupBy('sm.student_id')
                                        ->first();
                                } elseif ($class->name == '12') {
                                    $result = DB::table('student_marks as sm')
                                        ->join('subjects_on_report_card as sb', 'sm.subject_id', '=', 'sb.sub_rc_master_id')
                                        ->selectRaw('round(sum(sm.total_marks) / sum(sm.highest_total_marks) * 100, 2) as total_percent')
                                        ->where('sm.class_id', '=', DB::raw('sb.class_id'))
                                        ->where('sb.subject_type', '<>', 'Co-Scholastic_hsc')
                                        ->where('sm.publish', '=', 'Y')
                                        ->where('sm.student_id', '=', $prev_yr_student_id->student_id)
                                        ->groupBy('sm.student_id')
                                        ->first();
                                } else {
                                    $result = DB::table('student_marks as sm')
                                        ->join('subjects_on_report_card as sb', 'sm.subject_id', '=', 'sb.sub_rc_master_id')
                                        ->selectRaw('round(sum(sm.total_marks) / sum(sm.highest_total_marks) * 100, 2) as total_percent')
                                        ->where('sb.subject_type', '=', 'Scholastic')
                                        ->where('sm.class_id', '=', DB::raw('sb.class_id'))
                                        ->where('sm.publish', '=', 'Y')
                                        ->where('sm.student_id', '=', $prev_yr_student_id->student_id)
                                        ->groupBy('sm.student_id')
                                        ->first();
                                }
                                $studentdetail->total_percent = $result ? $result->total_percent : null;
                                 $attendanceresult = DB::table('attendance')
                                                        ->select(
                                                            DB::raw('SUM(IF(attendance_status = 0, 1, 0)) as total_present_days'),
                                                            DB::raw('COUNT(*) as total_working_days')
                                                        )
                                                        ->where('student_id', $prev_yr_student_id->student_id)
                                                        ->where('academic_yr', $prev_acd_yr)
                                                        ->first();  
                                    
                                        $total_attendance = '';
                                    
                                        if ($attendanceresult) {
                                            $total_present_days = $attendanceresult->total_present_days;
                                            $total_working_days = $attendanceresult->total_working_days;
                                    
                                            if (!is_null($total_present_days) && $total_present_days !== '') {
                                                $total_attendance = $total_present_days . "/" . $total_working_days;
                                            }
                                        }
                                        $studentdetail->total_attendance = $total_attendance;
                            }
                            else{
                                 $studentdetail->total_attendance =null;
                                $studentdetail->total_percent = null;
                            }
                                
                                $mappedStudentDetails[] = $studentdetail;


                        }

                        return response([
                                'status'=>200,
                                'data'=>$mappedStudentDetails,
                                'message'=>'Student Details Report',
                                'success'=>true
                            ]);
                            
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Student List Report',
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

    //Reports Student Contact Details report Dev name - Manish Kumar Sharma 10-03-2025
    public function getContactDetailsReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                // dd($customClaims);
                $section_id= $request->input('section_id');
                $studentparent = get_parent_student_data_by_class( $section_id, $customClaims);

                return response([
                    'status'=>200,
                    'data'=>$studentparent,
                    'message'=>'Contact Details Report',
                    'success'=>true
                ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Contact Details Report',
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

    //Reports Student Remarks Report Dev Name- Manish Kumar Sharma 10-03-2025
    public function getStudentRemarksReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $date = $request->input('date');
                $staff_id=$request->input('staff_id');
                $section_id=$request->input('section_id');
                $student_id=$request->input('student_id');
                $studentremarks = DB::table('remark')
                                      ->leftjoin('student','student.student_id','=','remark.student_id')
                                      ->leftjoin('class','remark.class_id','=','class.class_id')
                                      ->leftjoin('section','remark.section_id','=','section.section_id')
                                      ->leftjoin('teacher','teacher.teacher_id','=','remark.teacher_id')
                                      ->leftjoin('subject_master','remark.subject_id','=','subject_master.sm_id')
                                      ->select('class.name as classname','section.name as sectionname','remark.remark_date','remark.remark_type','student.first_name','student.mid_name','student.last_name','teacher.name as teachername','subject_master.name as subjectname','remark.remark_subject','remark.remark_desc','remark.academic_yr')
                                      ->orderBy('remark.remark_date');

                                      if ($date != '') {
                                        $studentremarks->whereDate('remark.remark_date', '=', $date);
                                      }

                                      if ($staff_id != '') {
                                        $studentremarks->where('remark.teacher_id', '=', $staff_id);
                                      }
                                      if ($section_id != '') {
                                        $studentremarks->where('remark.section_id', '=', $section_id);
                                      }
                                      if ($student_id != '') {
                                        $studentremarks->where('remark.student_id', '=', $student_id);
                                      }

                                      $results = $studentremarks->get();

                                      return response([
                                        'status'=>200,
                                        'data'=>$results,
                                        'message'=>'Student Remarks Report',
                                        'success'=>true
                                    ]);
                                      

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Student Remarks Report',
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

    //Reports Categorywise Student Report Dev Name- Manish Kumar Sharma 10-03-2025
    public function getCategoryWiseStudentReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id');
                $studentcategorywise = DB::table('student')
                                        ->join('class', 'student.class_id', '=', 'class.class_id')
                                        ->select('student.class_id', 'category', DB::raw('COUNT(*) as counts'), 'class.name')
                                        ->where('student.academic_yr', '=', $customClaims)
                                        ->where('student.isDelete', '=', 'N')
                                        ->groupBy('student.class_id', 'category');

                    if ($class_id != '') {
                        $studentcategorywise->where('student.class_id', '=', $class_id);
                        }

                    $results = $studentcategorywise->get();
                    return response([
                        'status'=>200,
                        'data'=>$results,
                        'message'=>'Student Category Wise Report',
                        'success'=>true
                    ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Category Wise Student Report',
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

    //Reports Religionwise Student Report Dev Name- Manish Kumar Sharma 10-03-2025
    public function getReligionWiseStudentReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id');
                $studentreligionwise = DB::table('student')
                                        ->join('class', 'student.class_id', '=', 'class.class_id')
                                        ->select('class.name', DB::raw('COUNT(*) as counts'), 'student.religion')
                                        ->where('student.academic_yr', '=', $customClaims)
                                        ->where('student.isDelete', '=', 'N')
                                        ->groupBy('student.class_id', 'student.religion');
                    if ($class_id != '') {
                        $studentreligionwise->where('student.class_id', '=', $class_id);
                        }


                        $results = $studentreligionwise->get();
                        return response([
                            'status'=>200,
                            'data'=>$results,
                            'message'=>'Student Religion Wise Report',
                            'success'=>true
                        ]);


            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Religion Wise Student Report',
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
    //Reports Genderwise Student Report Dev Name- Manish Kumar Sharma 10-03-2025
    public function getGenderWiseStudentReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id');
                $studentgenderwise = DB::table('student')
                                        ->join('class', 'student.class_id', '=', 'class.class_id')
                                        ->select('student.class_id', 'student.gender', DB::raw('COUNT(*) as counts'), 'class.name')
                                        ->where('student.isDelete', '=', 'N')
                                        ->where('student.academic_yr', '=', $customClaims)
                                        ->whereIn('student.gender', ['M', 'F'])
                                        ->groupBy('student.class_id', 'student.gender');

                        if ($class_id != '') {
                        $studentgenderwise->where('student.class_id', '=', $class_id);
                        }
        
                                $results = $studentgenderwise->get();
                                $totalcount = DB::table('student')
                                                ->selectRaw("sum(case when gender = 'M' then 1 else 0 end) as male")
                                                ->selectRaw("sum(case when gender = 'F' then 1 else 0 end) as female")
                                                ->where('isDelete', 'N')
                                                ->where('academic_yr', $customClaims)
                                                ->first();

                                return response([
                                    'status'=>200,
                                    'data'=>$results,
                                    'MaleFemale'=>$totalcount,
                                    'message'=>'Student Religion Wise Report',
                                    'success'=>true
                                ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Gender Wise Student Report',
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

    //Reports Genderwise Religionwise Report Dev Name- Manish Kumar Sharma 10-03-2025
    public function getGenderReligionwiseReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id');
                $studentreligionwisegenderwise = DB::table('student')
                                                    ->join('class', 'student.class_id', '=', 'class.class_id')
                                                    ->select('student.class_id', 'student.gender', 'student.religion', DB::raw('COUNT(*) as counts'), 'class.name')
                                                    ->where('student.isDelete', 'N')
                                                    ->where('student.academic_yr', $customClaims)
                                                    ->whereIn('student.gender', ['M', 'F'])
                                                    ->groupBy('student.class_id', 'student.gender', 'student.religion');
                

                            if ($class_id != '') {
                                $studentreligionwisegenderwise->where('student.class_id', '=', $class_id);
                                }

                                $results = $studentreligionwisegenderwise->get();
                                return response([
                                    'status'=>200,
                                    'data'=>$results,
                                    'message'=>'Student Religion Gender Wise Report',
                                    'success'=>true
                                ]);
    

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Gender Religion Wise Student Report',
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

    //Reports Genderwise Categorywise Report Dev Name- Manish Kumar Sharma 10-03-2025
    public function getGenderCategorywiseReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id');
                $studentcategorywisegenderwise = DB::table('student')
                                                    ->join('class', 'student.class_id', '=', 'class.class_id')
                                                    ->select('student.class_id', 'student.gender', 'student.category', DB::raw('COUNT(*) as counts'), 'class.name')
                                                    ->where('student.isDelete', 'N')
                                                    ->where('student.academic_yr', $customClaims)
                                                    ->whereIn('student.gender', ['M', 'F'])
                                                    ->groupBy('student.class_id', 'student.gender', 'student.category');
                    
                    if ($class_id != '') {
                        $studentcategorywisegenderwise->where('student.class_id', '=', $class_id);
                        }

                        $results = $studentcategorywisegenderwise->get();
                        return response([
                            'status'=>200,
                            'data'=>$results,
                            'message'=>'Student Category Gender Wise Report',
                            'success'=>true
                        ]);

                       

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Gender Category Wise Student Report',
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

    //Reports New Student Report Dev Name-Manish Kumar Sharma 17-03-2025
    public function getNewStudentReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id');
                $newstudentreport = DB::table('student')
                                        ->join('class', 'student.class_id', '=', 'class.class_id')
                                        ->join('section', 'student.section_id', '=', 'section.section_id')
                                        ->where('student.IsNew', 'Y')
                                        ->where('student.isDelete', 'N')
                                        ->where('student.academic_yr', $customClaims)
                                        ->orderBy('student.admission_date')
                                        ->orderBy('student.reg_no')
                                        ->select('class.name as class_name', 'section.name as sec_name', 'student.*');
                            if ($class_id != '') {
                                $newstudentreport->where('student.class_id', '=', $class_id);
                                }
        
                                $results = $newstudentreport->get();
                                return response([
                                    'status'=>200,
                                    'data'=>$results,
                                    'message'=>'New Student Report',
                                    'success'=>true
                                ]);


            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the New Student Report',
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
    //Reports Left Students Report Dev Name-Manish Kumar Sharma 18-03-2025
    public function getLeftStudentReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id');
                $leftstudents = DB::table('student')
                                    ->join('class', 'student.class_id', '=', 'class.class_id')
                                    ->join('section', 'student.section_id', '=', 'section.section_id')
                                    ->where('student.IsDelete', 'Y')
                                    ->where('student.academic_yr', $customClaims)
                                    ->select('class.name as class_name', 'section.name as sec_name', 'student.*');

                       if ($class_id != '') {
                                $leftstudents->where('student.class_id', '=', $class_id);
                                }
        
                                $results = $leftstudents->get();
                                return response([
                                    'status'=>200,
                                    'data'=>$results,
                                    'message'=>'Left Students Report',
                                    'success'=>true
                                ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Left Students Report',
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

    //Reports Subject HSC Studentwise  Report Dev Name-Manish Kumar Sharma 18-03-2025
    public function getSubjectHSCStudentwiseReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id');
                $section_id = $request->input('section_id');
                $getsubjecthsc = DB::table('student AS stud')
                                        ->leftJoin('subjects_higher_secondary_studentwise AS shs', 'shs.student_id', '=', 'stud.student_id')
                                        ->leftJoin('subject_group AS grp', 'shs.sub_group_id', '=', 'grp.sub_group_id')
                                        ->leftJoin('subject_group_details AS grpd', 'grp.sub_group_id', '=', 'grpd.sub_group_id')
                                        ->leftJoin('subject_master AS shsm', 'grpd.sm_hsc_id', '=', 'shsm.sm_id')
                                        ->leftJoin('subject_master AS shs_op', 'shs.opt_subject_id', '=', 'shs_op.sm_id')
                                        ->leftJoin('stream', 'grp.stream_id', '=', 'stream.stream_id')
                                        ->where('stud.class_id', '=', $class_id)
                                        ->where('stud.section_id', '=', $section_id)
                                        ->where('stud.academic_yr', '=', $customClaims)
                                        ->select(
                                            'stud.roll_no',
                                            'stud.first_name',
                                            'stud.mid_name',
                                            'stud.last_name',
                                            'stud.student_id',
                                            'shsm.name as subject_name',
                                            'shsm.subject_type',
                                            'stream.stream_name',
                                            'shs_op.name as optional_sub_name'
                                        )
                                        ->orderBy('stud.student_id') 
                                        ->get()
                                        ->groupBy('student_id'); // Grouping by student_id 
                                    // dd($getsubjecthsc);

                                $formattedSubjects = [];

                                foreach ($getsubjecthsc as $student_id => $subjects) {
                                    $allSubjects = [];
                                    $optionalSubjectNames = [];  // This will store optional subjects
                                    $studentDetails = [
                                        'student_id'  => $student_id,
                                        'first_name'  => $subjects[0]->first_name,  // Take the first entry's first_name
                                        'mid_name'    => $subjects[0]->mid_name,    // Take the first entry's mid_name
                                        'last_name'   => $subjects[0]->last_name,   // Take the first entry's last_name
                                        'roll_no'     => $subjects[0]->roll_no,     // Take the first entry's roll_no
                                        'stream_name' => $subjects[0]->stream_name, // Take the first entry's stream_name
                                    ];
                                    
                                    // Loop through each subject for the current student
                                    foreach ($subjects as $subject) {
                                        // Add regular subject to the allSubjects array
                                        $allSubjects[] = [
                                            'subject_name' => $subject->subject_name
                                        ];

                                        // Add optional subject only once if it exists
                                        if (!empty($subject->optional_sub_name) && !in_array($subject->optional_sub_name, $optionalSubjectNames)) {
                                            $optionalSubjectNames[] = $subject->optional_sub_name;  // Add the optional subject only if it is not already in the list
                                        }
                                    }

                                    // Merge optional subjects into the allSubjects array
                                    foreach ($optionalSubjectNames as $optionalSubject) {
                                        $allSubjects[] = [
                                            'subject_name' => $optionalSubject
                                        ];
                                    }

                                    // Add the formatted data for each student
                                    $formattedSubjects[] = array_merge($studentDetails, [
                                        'subjects' => $allSubjects
                                    ]);
                                }

                                return response([
                                    'status'=>200,
                                    'data'=>$formattedSubjects,
                                    'message'=>'Subject HSC Studentwise Report',
                                    'success'=>true
                                ]);
                                                                

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Subject HSC Studentwise Report',
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

    //Reports Staff Report Dev Name-Manish Kumar Sharma 19-03-2025
    public function getStaffReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){

                $query = DB::select(" SELECT * FROM teacher WHERE designation != 'Caretaker' AND IsDelete = 'N' ");
                return response([
                    'status'=>200,
                    'data'=>$query,
                    'message'=>'Staff Report',
                    'success'=>true
                ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Staff Report',
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
    
    //Reports Monthly Attendance Report Dev Name-Manish Kumar Sharma 19-03-2025
    public function getMonthlyAttendanceReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $section_id = $request->input('section_id');
                $month = $request->input('month');
                $workingDaysQuery = DB::table('attendance as a')
                                        ->select(DB::raw('count(*) as workingdays_count'))
                                        ->where('a.section_id', $section_id)
                                        ->whereRaw('MONTHNAME(a.only_date) = ?', [$month]) 
                                        ->where('a.academic_yr', $customClaims)
                                        ->groupBy('a.student_id');

                                    $maxWorkingDaysQuery = DB::table(DB::raw("({$workingDaysQuery->toSql()}) as working_days"))
                                        ->mergeBindings($workingDaysQuery) 
                                        ->select(DB::raw('max(workingdays_count) as total_working_days'));

                                    $studentsAttendance = DB::table('attendance as a')
                                        ->select(
                                            'a.student_id',
                                            DB::raw('SUM(IF(a.attendance_status = 0, 1, 0)) as present_count'),
                                            'b.first_name',
                                            'b.mid_name',
                                            'b.last_name',
                                            'b.roll_no',
                                            'b.isDelete',

                                            DB::raw("({$maxWorkingDaysQuery->toSql()}) as total_working_days")
                                        )
                                        ->join('student as b', 'a.student_id', '=', 'b.student_id')
                                        ->where('a.section_id', $section_id)
                                        ->whereRaw('MONTHNAME(a.only_date) = ?', [$month]) 
                                        ->where('a.academic_yr', $customClaims)
                                        ->groupBy('a.student_id')
                                        ->orderBy('b.roll_no', 'ASC')
                                        ->orderBy('b.reg_no', 'ASC')
                                        ->mergeBindings($maxWorkingDaysQuery) 
                                        ->get();

                                        $res = DB::table('settings')
                                                    ->where('active', 'Y')
                                                    ->first(); 
                                        $month_number=date("n",strtotime($month));
                                        $yr_from=date('Y', strtotime($res->academic_yr_from));
                                        $yr_to=date('Y', strtotime($res->academic_yr_to));
                                        if($month_number >= 4 && $month_number <= 12) {
                                            $date_to = date("Y-m-d", mktime(0, 0, 0, $month_number + 1, 0, $yr_from));
                                        } else {
                                            $date_to = date("Y-m-d", mktime(0, 0, 0, $month_number + 1, 0, $yr_to));
                                        }

                                            

                                        $totalPresentCount = $studentsAttendance->sum('present_count');
                                        $totalWorkingDays = $studentsAttendance->sum('total_working_days');
                                        $totalDays = $totalPresentCount + $totalWorkingDays;
                                        
                                        $totalAttendanceResults = [];
                                        $totalAttendanceSum = 0;
                                        $grandtotalWorkingDays = 0;
                                        $studentData = [];

                                        foreach ($studentsAttendance as $studentsAttendances){
                                         
                                        $total_attendance = DB::table('attendance')
                                                            ->selectRaw('sum(case when attendance_status = 0 then 1 else 0 end) as total_present_days')
                                                            ->where('student_id', $studentsAttendances->student_id)
                                                            ->whereBetween('only_date', [$res->academic_yr_from, $date_to])
                                                            ->where('academic_yr', $customClaims)
                                                            ->value('total_present_days'); 
                                                            $totalAttendanceSum += $total_attendance;
                                                            
                                         $grand_total_working_days = DB::table('attendance')
                                                                ->where('student_id', $studentsAttendances->student_id)
                                                                ->whereBetween('only_date', [$res->academic_yr_from, $date_to])
                                                                ->where('academic_yr', $customClaims)
                                                                ->count();
                                         $grandtotalWorkingDays += $grand_total_working_days;
                                         $studentData[] = [
                                                        'student_id' => $studentsAttendances->student_id,
                                                        'roll_no' => $studentsAttendances->roll_no,
                                                        'first_name' => $studentsAttendances->first_name,
                                                        'mid_name' => $studentsAttendances->mid_name,
                                                        'last_name' => $studentsAttendances->last_name,
                                                        'present_count' => $studentsAttendances->present_count,
                                                        'working_days' => $studentsAttendances->total_working_days, 
                                                        'total_attendance' => $total_attendance,
                                                        'total_attendance_till_a_month'=>$grand_total_working_days
                                                    ];
                                        
                                        
                                        }
                                        $data = [
                                            'total_present_count' => $totalPresentCount,
                                            'total_working_days' => $totalWorkingDays,
                                            'total_attendance' => $totalAttendanceSum, 
                                            'grand_total_working_days' => $grandtotalWorkingDays, 
                                             'students' => $studentData, 
                                        ];
                                        
                                        return response([
                                            'status'=>200,
                                            'data'=>$data,
                                            'message'=>'Monthly Attendance Report ',
                                            'success'=>true
                                        ]);
                                        
                                        
                                        

                            
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Monthly Attendance Record Report',
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


    //Reports Fee Payment Record Report Dev Name-Manish Kumar Sharma 20-03-2025
    public function getFeePaymentRecordReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $searchFrom = $request->input('searchFrom');
                $searchTo = $request->input('searchTo');
                $reqData = [
                    'searchFrom' => $searchFrom,
                    'searchTo' => $searchTo
                ];


                $query = DB::table('view_fees_payment_record')
                            ->join('student', 'student.student_id', '=', 'view_fees_payment_record.student_id')
                            ->select(
                                'view_fees_payment_record.*',
                                'student.first_name',
                                'student.mid_name',
                                'student.last_name',
                                DB::raw("DATE_FORMAT(view_fees_payment_record.payment_date, '%d-%m-%Y') as date")
                            );

                if (isset($reqData)) {
                    if ($reqData['searchFrom'] !== null && $reqData['searchTo'] !== null) {
                        $query->where('payment_date', '>=', $reqData['searchFrom'])
                            ->where('payment_date', '<=', $reqData['searchTo']);
                    }
                }

                $feepaymentrecord = $query->get();

                return response([
                    'status'=>200,
                    'data'=>$feepaymentrecord,
                    'message'=>'Fee Payment Record Report',
                    'success'=>true
                ]);



            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Fee Payment Record Report',
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

    //Reports WorldLine Fee Payment Record Report Dev Name-Manish Kumar Sharma 20-03-2025
    public function getWorldlineFeePaymentRecordReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $account_type = $request->input('accounttype');
                $from_date = $request->input('fromdate');
                $to_date = $request->input('todate');
                $student_id = $request->input('student_id');
                $order_id = $request->input('order_id');
                if($student_id){
                    $parent_idd = DB::table('student')->where('student_id',$student_id)->select('parent_id')->first();
                    $parent_id =  $parent_idd->parent_id;
                }

                $query = DB::table('worldline_payment_details as w')
                            ->join('onlinefees_payment_record as o', DB::raw('SUBSTRING_INDEX(o.cheque_no, "/", 1)'), '=', 'w.OrderId')
                            ->select('w.*', DB::raw('GROUP_CONCAT(o.receipt_no) as receipt_no'))
                            ->where('w.Status_code', 'S');
    
                if (!empty($account_type)) {
                    $query->where('w.Account_type', 'like', $account_type . '%');
                }

                if (!empty($from_date)) {
                    $query->whereDate('w.Trnx_date', '>=', $from_date);
                }

                if (!empty($to_date)) {
                    $query->whereDate('w.Trnx_date', '<=', $to_date);
                }

                if (!empty($parent_id) && $parent_id != 0) {
                    $query->where('w.reg_id', '=', $parent_id);
                }

                if (!empty($order_id)) {
                    $query->where('w.OrderId', '=', $order_id);
                }

                $query->groupBy('w.OrderId');
                $worldinepayment = $query->get();
                return response([
                    'status'=>200,
                    'data'=>$worldinepayment,
                    'message'=>'WorldLine Fee Payment Record Report',
                    'success'=>true
                ]);
                

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the WorldLine Fee Payment Record Report',
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

    //Reports Razorpay Fee Payment Record Report Dev Name-Manish Kumar Sharma 20-03-2025
    public function getRazorpayFeePaymentRecordReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $account_type = $request->input('accounttype');
                $from_date = $request->input('fromdate');
                $to_date = $request->input('todate');
                $student_id = $request->input('student_id');
                $order_id = $request->input('order_id');
                if($student_id){
                    $parent_idd = DB::table('student')->where('student_id',$student_id)->select('parent_id')->first();
                    $parent_id =  $parent_idd->parent_id;
                }

                $query = DB::table('razorpay_payment_details as r')
                ->join('onlinefees_payment_record as o', DB::raw('SUBSTRING_INDEX(o.cheque_no, "/", 1)'), '=', 'r.OrderId')
                ->select('r.*', DB::raw('GROUP_CONCAT(o.receipt_no) as receipt_no'))
                ->where('r.Status', 'S');

                if (!empty($account_type)) {
                    $query->where('r.Account_type', 'like', $account_type . '%');
                }

                if (!empty($from_date)) {
                    $query->whereDate('r.Trnx_date', '>=', $from_date);
                }

                if (!empty($to_date)) {
                    $query->whereDate('r.Trnx_date', '<=', $to_date);
                }

                if (!empty($parent_id) && $parent_id != 0) {
                    $query->where('r.reg_id', '=', $parent_id);
                }

                if (!empty($order_id)) {
                    $query->where('r.OrderId', '=', $order_id);
                }

                $query->groupBy('r.OrderId');

                $razorpaypayment = $query->get();


                return response([
                    'status'=>200,
                    'data'=>$razorpaypayment,
                    'message'=>'Razorpay Fee Payment Record Report',
                    'success'=>true
                ]);
                

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Razorpay Fee Payment Record Report',
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

    //Reports Pending Student Id Card Record Report Dev Name-Manish Kumar Sharma 24-03-2025
    public function getPendingStudentIdCardRecordReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $section_id = $request->input('section_id');
                $result = DB::table('student')
                            ->join('class', 'student.class_id', '=', 'class.class_id')
                            ->join('section', 'student.section_id', '=', 'section.section_id')
                            ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
                            ->leftJoin('confirmation_idcard', 'student.parent_id', '=', 'confirmation_idcard.parent_id')
                            ->where('student.isDelete', 'N')
                            ->where('student.section_id', $section_id)
                            ->whereNull('confirmation_idcard.confirm')
                            ->orderBy('student.roll_no')
                            ->select(
                                'student.first_name', 
                                'student.mid_name', 
                                'student.last_name', 
                                'student.roll_no', 
                                'student.image_name', 
                                'student.reg_no', 
                                'student.permant_add', 
                                'student.blood_group', 
                                'student.house', 
                                'student.dob', 
                                'student.house as student_house', 
                                'class.name as class_name', 
                                'section.name as sec_name', 
                                'parent.parent_id', 
                                'parent.father_name', 
                                'parent.f_mobile', 
                                'parent.m_mobile'
                            )
                            ->get();
                            return response([
                                'status'=>200,
                                'data'=>$result,
                                'message'=>'Pending Student Id Card Report',
                                'success'=>true
                            ]);
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Pending Student Id Card Report',
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

    //Reports Substitute Teacher Monthly Report Dev Name-Manish Kumar Sharma 24-03-2025
    public function getSubstituteTeacherMonthlyReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $month = $request->input('month');

                $result = DB::table('substitute_teacher')
                            ->join('subject_master', 'substitute_teacher.subject_id', '=', 'subject_master.sm_id')
                            ->join('class','substitute_teacher.class_id','=','class.class_id')
                            ->join('section','substitute_teacher.section_id','=','section.section_id')
                            ->join('teacher as teacher','substitute_teacher.teacher_id','=','teacher.teacher_id')
                            ->join('teacher as subteacher','substitute_teacher.sub_teacher_id','=','subteacher.teacher_id')
                            ->whereMonth('substitute_teacher.date', $month)
                            ->where('substitute_teacher.academic_yr', $customClaims)
                            ->orderBy('substitute_teacher.teacher_id')
                            ->orderBy('substitute_teacher.date')
                            ->orderBy('substitute_teacher.period')
                            ->select('substitute_teacher.*', 'subject_master.name','class.name as classname','section.name as sectionname','teacher.name as teachername','subteacher.name as substitutename')
                            ->get();
                return response([
                    'status'=>200,
                    'data'=>$result,
                    'message'=>'Substitute Teacher Monthly Report',
                    'success'=>true
                ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Substitute Teacher Monthly Report',
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

    //Reports Substitute Teacher Weekly Report Dev Name-Manish Kumar Sharma 24-03-2025
    public function getSubstituteTeacherWeeklyReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $teacher_id = $request->input('teacher_id');
                $week = $request->input('week');
                // dd($week);
                if(is_null($week)){
                   
                $result = DB::table('substitute_teacher')
                    ->join('subject_master', 'substitute_teacher.subject_id', '=', 'subject_master.sm_id')
                    ->join('teacher','teacher.teacher_id','=','substitute_teacher.sub_teacher_id')
                    ->join('timetable', function($join) {
                        $join->on('substitute_teacher.period', '=', 'timetable.period_no')
                            ->on('substitute_teacher.class_id', '=', 'timetable.class_id')
                            ->on('substitute_teacher.section_id', '=', 'timetable.section_id');
                    })
                    ->where('substitute_teacher.academic_yr', $customClaims)
                    ->where('substitute_teacher.sub_teacher_id', $teacher_id)
                    ->orderBy('substitute_teacher.teacher_id')
                    ->orderBy('substitute_teacher.date')
                    ->select('substitute_teacher.*', 'subject_master.name', 'timetable.time_in', 'timetable.time_out','teacher.name as teachername')
                    ->get();

                    $total_minutes = 0;
                    foreach ($result as $row) {
                        
                        $dateTime1 = new \DateTime($row->time_in);
                        $dateTime2 = new \DateTime($row->time_out);
                        
                        $interval = $dateTime1->diff($dateTime2);
                        
                        $minutes = $interval->h * 60 + $interval->i;
                        
                        $total_minutes= $total_minutes + $minutes;
                        $total_hours = intdiv($total_minutes, 60); 
                        $remaining_minutes = $total_minutes % 60;
                        
                        $row->time_difference_decimal = $total_hours.".".$remaining_minutes;  
                    }
                    
                }
                else{
                    $dates = explode("/", $week);
                $start_date = \Carbon\Carbon::createFromFormat('d-m-Y', $dates[0])->format('Y-m-d');
                $end_date = \Carbon\Carbon::createFromFormat('d-m-Y', $dates[1])->format('Y-m-d');
                $result = DB::table('substitute_teacher')
                    ->join('subject_master', 'substitute_teacher.subject_id', '=', 'subject_master.sm_id')
                    ->join('teacher','teacher.teacher_id','=','substitute_teacher.sub_teacher_id')
                    ->join('timetable', function($join) {
                        $join->on('substitute_teacher.period', '=', 'timetable.period_no')
                            ->on('substitute_teacher.class_id', '=', 'timetable.class_id')
                            ->on('substitute_teacher.section_id', '=', 'timetable.section_id');
                    })
                    ->whereBetween('substitute_teacher.date', [$start_date, $end_date])
                    ->where('substitute_teacher.academic_yr', $customClaims)
                    ->where('substitute_teacher.sub_teacher_id', $teacher_id)
                    ->orderBy('substitute_teacher.teacher_id')
                    ->orderBy('substitute_teacher.date')
                    ->select('substitute_teacher.*', 'subject_master.name', 'timetable.time_in', 'timetable.time_out','teacher.name as teachername')
                    ->get();

                    $total_minutes = 0;
                    foreach ($result as $row) {
                        $row->week = $week; 
                        
                        $dateTime1 = new \DateTime($row->time_in);
                        $dateTime2 = new \DateTime($row->time_out);
                        
                        $interval = $dateTime1->diff($dateTime2);
                        
                        $minutes = $interval->h * 60 + $interval->i;
                        
                        $total_minutes= $total_minutes + $minutes;
                        $total_hours = intdiv($total_minutes, 60); 
                        $remaining_minutes = $total_minutes % 60;
                        
                        $row->time_difference_decimal = $total_hours.".".$remaining_minutes;  
                    }
                    
                }

                
                    return response([
                        'status'=>200,
                        'data'=>$result,
                        'message'=>'Substitute Teacher Weekly Report.',
                        'success'=>true
                    ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Substitute Teacher Weekly Report',
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

    //Reports Leaving Certificate Report Dev Name-Manish Kumar Sharma 24-03-2025
    public function getLeavingCertificateReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id');
                $query = DB::table('leaving_certificate as a')
                            ->join('student as b', 'a.stud_id', '=', 'b.student_id')
                            ->join('class as c', 'b.class_id', '=', 'c.class_id')
                            ->join('section as s', 'b.section_id', '=', 's.section_id')
                            ->select(
                                'a.*', 
                                DB::raw("CONCAT(a.stud_name, ' ', a.mid_name, ' ', a.last_name) as student_name"),
                                'b.roll_no', 
                                'b.reg_no', 
                                'c.name as class_name', 
                                's.name as sec_name'
                            )
                            ->where('a.academic_yr', '=', $customClaims);
                        
                        if (!empty($class_id)) {
                            $query->where('b.class_id', '=', $class_id);
                        }
                    $result = $query->get();
                    return response([
                        'status'=>200,
                        'data'=>$result,
                        'message'=>'Leaving Certificate Report.',
                        'success'=>true
                    ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Leaving Certificate Report.',
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


    public function getdiscrepancy_in_WL_payment_report(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $data = DB::table('worldline_payment_details as w')
                            ->select(
                                'w.*',
                                DB::raw('GROUP_CONCAT(o.receipt_no) as receipt_nos')
                            )
                            ->leftJoin('onlinefees_payment_record as o', 'o.cheque_no', '=', 'w.OrderId')
                            ->whereColumn('w.Amount', '<>', 'w.WL_Amount')
                            ->where('w.Status_code', 'S')
                            ->whereNotNull('w.WL_Amount')
                            ->where('w.academic_yr', $customClaims)
                            ->groupBy('w.OrderId')
                            ->get();
                            return response([
                                'status'=>200,
                                'data'=>$data,
                                'message'=>'WL discrepancy report.',
                                'success'=>true
                            ]);


             }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Leaving Certificate Report.',
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

    public function getduplicatepaymentreportfinance(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                // $academicYear ='2023-2024'; 
            //   $academicYear = '2023-2024';

                $query = "
                    SELECT student_id, installment  
                    FROM (
                        SELECT vf.student_id, vd.installment 
                        FROM view_fees_payment_record vf
                        JOIN view_fees_payment_detail vd ON vf.fees_payment_id = vd.fees_payment_id
                        WHERE vf.amount <> 0 
                        AND vf.isCancel <> 'Y' 
                        AND vd.fee_type_id NOT IN (7, 8)
                        AND vf.academic_yr = ?
                        GROUP BY vf.fees_payment_id, vf.amount, vd.installment, vf.student_id
                    ) AS X 
                    GROUP BY student_id, installment 
                    HAVING COUNT(*) > 1
                ";

             $duplicates = DB::select($query, [$customClaims]);
            // dd($duplicates);

        $result = [];
        $srNo = 1;

        foreach ($duplicates as $row) {
            $paymentDetails = DB::table('view_fees_payment_record as vf')
                ->join('view_fees_payment_detail as vd', 'vf.fees_payment_id', '=', 'vd.fees_payment_id')
                ->where('vf.amount', '<>', 0)
                ->where('vf.isCancel', '<>', 'Y')
                ->whereNotIn('vd.fee_type_id', [7, 8])
                ->where('vf.student_id', $row->student_id)
                ->where('vd.installment', $row->installment)
                ->groupBy('vf.fees_payment_id', 'vd.installment')
                ->select('vf.*', 'vd.installment')
                ->get();

            foreach ($paymentDetails as $detail) {
                $studentName = $this->getStudentName($detail->student_id);
                $class = $this->getStudentClass($detail->student_id);

                $result[] = [
                    'sr_no' => $srNo++,
                    'student_name' => $studentName,
                    'payment_date' => date('d-m-Y', strtotime($detail->payment_date)),
                    'class' => $class,
                    'installment' => $detail->installment,
                    'amount' => $detail->amount,
                    'receipt_no' => $detail->receipt_no,
                ];
            }
        }

        return response()->json([
            'status'=>200,
            'message'=>'Duplicate payment report list.',
            'data'=>$result,
            'success'=>true
            ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Leaving Certificate Report.',
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

    private function getStudentName($studentId)
    {
        $student = DB::table('student as a')
            ->join('parent as b', 'a.parent_id', '=', 'b.parent_id')
            ->where('a.student_id', $studentId)
            ->first(['a.first_name', 'a.mid_name', 'a.last_name']);

        $parts = array_filter([$student->first_name ?? '', $student->mid_name ?? '', $student->last_name ?? ''], function ($part) {
            return $part !== '' && $part !== 'No Data';
        });

        return implode(' ', $parts);
    }

    private function getStudentClass($studentId)
    {
        $class = DB::table('student as s')
            ->join('class as c', 's.class_id', '=', 'c.class_id')
            ->join('section as sc', 's.section_id', '=', 'sc.section_id')
            ->where('s.student_id', $studentId)
            ->select(DB::raw("CONCAT(c.name, '-', sc.name) as class"))
            ->first();

        return $class->class ?? '';
    }
    //API for the Staff daily attendance report Dev Name- Manish Kumar Sharma 12-06-2025
    public function getStaffDailyAttendanceReport(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $date = $request->input('date');
                $presentstaff = DB::table('teacher_attendance as a')
                                    ->select(
                                        'a.employee_id',
                                        DB::raw('DATE(a.punch_time) as date_part'),
                                        DB::raw('MIN(TIME(a.punch_time)) as punch_in_time'),
                                        DB::raw('MAX(TIME(a.punch_time)) as punch_out_time'),
                                        'b.name',
                                        'b.tc_id',
                                        'c.late_time'
                                    )
                                    ->join('teacher as b', 'a.employee_id', '=', 'b.employee_id')
                                    ->join('late_time as c','c.tc_id','=','b.tc_id')
                                    
                                    ->whereDate('a.punch_time', '=', $date)
                                    ->groupBy('a.employee_id', DB::raw('DATE(a.punch_time)'), 'b.name', 'b.tc_id')
                                    ->havingRaw('MIN(TIME(a.punch_time)) <= c.late_time')
                                    ->orderBy(DB::raw('DATE(a.punch_time)'))
                                    ->orderBy('a.employee_id')
                                    ->get();
                                     
                
                $latestaff =  DB::table('teacher_attendance as a')
                                    ->select(
                                        'a.employee_id',
                                        DB::raw('DATE(a.punch_time) as date_part'),
                                        DB::raw('MIN(TIME(a.punch_time)) as punch_in_time'),
                                        DB::raw('MAX(TIME(a.punch_time)) as punch_out_time'),
                                        'b.name',
                                        'b.tc_id',
                                        'c.late_time'
                                    )
                                    ->join('teacher as b', 'a.employee_id', '=', 'b.employee_id')
                                    ->join('late_time as c','c.tc_id','=','b.tc_id')
                                    
                                    ->whereDate('a.punch_time', '=', $date)
                                    ->groupBy('a.employee_id', DB::raw('DATE(a.punch_time)'), 'b.name', 'b.tc_id')
                                    ->havingRaw('MIN(TIME(a.punch_time)) >= c.late_time')
                                    ->orderBy(DB::raw('DATE(a.punch_time)'))
                                    ->orderBy('a.employee_id')
                                    ->get();
                $absentstaff = DB::select("SELECT t.* FROM teacher AS t LEFT JOIN teacher_attendance AS ta ON t.employee_id = ta.employee_id AND DATE(ta.punch_time) = '$date' WHERE ta.employee_id IS NULL AND t.isDelete = 'N';");
                // dd($absentstaff);
                $staffattendance = [
                    'present_staff'=> $presentstaff,
                    'late_staff'=> $latestaff,
                    'absent_staff'=> $absentstaff,
                ];
                return response()->json([
                    'status'=>200,
                    'message'=>'Staff daily attendance report.',
                    'data'=>$staffattendance,
                    'success'=>true
                    ]);
                  
                //  dd($latestaff);

             }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Leaving Certificate Report.',
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
