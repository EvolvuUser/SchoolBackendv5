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
                            // dd($query);
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
                                                   ->where('reg_no',$studentdetail->reg_no)
                                                   ->first();
                                                //  dd($prev_yr_student_id);
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
                            //    dd($result);
                                // If the result is found, return the total percentage
                                $studentdetail->total_percent = $result ? $result->total_percent : null;

                                // Push the student detail with total_percent into the array
                                $mappedStudentDetails[] = $studentdetail;

                        }

                        dd($mappedStudentDetails);
                            
                            

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




}
