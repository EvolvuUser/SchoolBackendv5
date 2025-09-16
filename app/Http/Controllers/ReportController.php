<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Services\WhatsAppService;
use Carbon\CarbonPeriod;


class ReportController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }
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

    //API for the Phase 1 Reports Dev Name- Manish Kumar Sharma 07-06-2025
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
    //API for the Phase 1 Reports Dev Name- Manish Kumar Sharma 07-06-2025
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
                                        DB::raw('CASE 
                                            WHEN COUNT(a.punch_time) > 1 
                                            THEN MAX(TIME(a.punch_time)) 
                                            ELSE NULL 
                                        END as punch_out_time'),
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
                                        DB::raw('CASE 
                                            WHEN COUNT(a.punch_time) > 1 
                                            THEN MAX(TIME(a.punch_time)) 
                                            ELSE NULL 
                                         END as punch_out_time'),
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
                $totalattendance = DB::select("SELECT a.employee_id, DATE(a.punch_time) AS date_part, MIN(TIME(a.punch_time)) AS punch_in_time, MAX(TIME(a.punch_time)) AS punch_out_time, b.name,b.tc_id FROM teacher_attendance AS a JOIN teacher AS b ON a.employee_id = b.employee_id WHERE DATE(a.punch_time) = '".$date."' GROUP BY a.employee_id, DATE(a.punch_time), b.name ORDER BY DATE(a.punch_time),a.employee_id;");

                $totalattendancecount = count($totalattendance);
                // dd($totalattendancecount);
                $totalteacher = count(DB::table('teacher')->where('IsDelete','N')->get());
                $staffattendance = [
                    'present_staff'=> $presentstaff,
                    'late_staff'=> $latestaff,
                    'absent_staff'=> $absentstaff,
                    'total_attendance'=>$totalattendancecount."/".$totalteacher
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

    //API for the Approve leave Dev Name- Manish Kumar Sharma 13-06-2025
    public function getListForleaveApprove(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $statuses = ['A', 'H'];

                $leaveApplications = DB::table('leave_application')
                                        ->whereIn('status', $statuses)
                                        ->join('teacher','teacher.teacher_id','=','leave_application.staff_id')
                                        ->join('leave_type_master','leave_type_master.leave_type_id','=','leave_application.leave_type_id')
                                        ->orderBy('leave_app_id', 'DESC')
                                        ->select('leave_application.*','teacher.name as teachername','leave_type_master.name as leavetypename')
                                        ->where('leave_application.academic_yr',$customClaims)
                                        ->get()
                                        ->toArray();

                return response()->json([
                    'status'=>200,
                    'message'=>'Leave approve list!',
                    'data'=>$leaveApplications,
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

    //API for the Approve leave Dev Name- Manish Kumar Sharma 13-06-2025
    public function updateLeaveApproveStatus(Request $request,$id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $status = $request->status;
                $approverscomment = $request->approverscomment;
                DB::table('leave_application')
                    ->where('leave_app_id', $id)
                    ->update([
                           'status'=>$status,
                           'reason_for_rejection'=>$approverscomment
                    ]);
                    if($status == 'P'){
                        $leaveinformation = DB::table('leave_application')
                                               ->where('leave_app_id', $id)
                                               ->first();
                     DB::table('leave_application')
                            ->where('leave_app_id', $id)
                            ->update([
                                   'status'=>$status,
                                   'reason_for_rejection'=>$approverscomment,
                                   'approved_by'=>$user->reg_id
                            ]);


                        DB::table('leave_allocation')
                            ->where('staff_id', $leaveinformation->staff_id)
                            ->where('leave_type_id', $leaveinformation->leave_type_id)
                            ->increment('leaves_availed', $leaveinformation->no_of_days);
                        
                    }

                    return response()->json([
                    'status'=>200,
                    'message'=>'Leave status changed!',
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

    //API for the Approve leave Dev Name- Manish Kumar Sharma 13-06-2025
    public function getCountofApproveLeave(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $statuses = ['A', 'H'];

                $leaveApplications = DB::table('leave_application')
                                        ->whereIn('status', $statuses)
                                        ->join('teacher','teacher.teacher_id','=','leave_application.staff_id')
                                        ->join('leave_type_master','leave_type_master.leave_type_id','=','leave_application.leave_type_id')
                                        ->orderBy('leave_app_id', 'DESC')
                                        ->select('leave_application.*','teacher.name as teachername','leave_type_master.name as leavetypename')
                                        ->where('leave_application.academic_yr',$customClaims)
                                        ->get()
                                        ->toArray();
                $leaveapplication = count($leaveApplications);

                return response()->json([
                    'status'=>200,
                    'message'=>'Leave approve count!',
                    'data'=>$leaveapplication,
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

    public function whatsappmessagesfornotapprovinglessonplan(Request $request){
        ini_set('max_execution_time', 3600);
        $academicYear = DB::table('settings')->where('active','Y')->value('academic_yr');
        $nextMonday = Carbon::now()->startOfWeek()->format('d-m-Y');
         
         $lessonplanteachers = DB::select("SELECT group_concat(' ',c.name ,' ', sc.name,' ', sm.name) as pending_classes, s.teacher_id, t.name, t.phone FROM subject s, teacher t, class c, section sc, subject_master sm WHERE s.teacher_id=t.teacher_id AND t.isDelete = 'N' and s.class_id=c.class_id and s.section_id=sc.section_id and s.sm_id=sm.sm_id and s.academic_yr='$academicYear' and concat(s.class_id, s.section_id, s.sm_id, s.teacher_id) not in (select concat(class_id, section_id, subject_id, reg_id) from lesson_plan where SUBSTRING_INDEX(week_date,' /',1)='$nextMonday') and s.sm_id not in (select sm_id from subjects_excluded_from_curriculum) group by s.teacher_id;");
        //  dd($lessonplanteachers);
        //  dd("Hello");
         foreach($lessonplanteachers as $lessonplanteacher){
             $teacherid = $lessonplanteacher->teacher_id ?? null;
             $pendingclasses = $lessonplanteacher->pending_classes ?? null;
             $teachername = $lessonplanteacher->name ?? null;
             $phoneno = $lessonplanteacher->phone ?? null;
             $parts = explode(' ', trim($teachername));
            $firstname = $parts[0] ?? '';
            $lastname = $parts[count($parts) - 1] ?? '';
             $message = "Tr. ".ucwords(strtolower($firstname . ' ' . $lastname)).", your lesson plan of this week for".$pendingclasses." is not yet submitted";
             Log::info("WhatsApp lesson plan message: " . $message);
            //  dd($message);
             $templateName = 'emergency_message';
                    $parameters =[$message];
                    Log::info($phoneno);
                    if($phoneno){
                        $result = $this->whatsAppService->sendTextMessage(
                                $phoneno,
                                $templateName,
                                $parameters
                            );
                            if (isset($result['code']) && isset($result['message'])) {
                                // Handle rate limit error
                                Log::warning("Rate limit hit: Too many messages to same user", [
                                    
                                ]);
                        
                            } else {
                                // Proceed if no error
                                $wamid = $result['messages'][0]['id'];
                                $phone_no = $result['contacts'][0]['input'];
                                $message_type = 'pending_lesson_message_for_teacher';
                        
                                DB::table('redington_webhook_details')->insert([
                                    'wa_id' => $wamid,
                                    'phone_no' => $phone_no,
                                    'stu_teacher_id' => $teacherid,
                                    'message_type' => $message_type,
                                    'created_at' => now()
                                ]);
                            }
                    }
             
            //  dd($pendingclasses);
         }
         
         return response()->json([
                    'status'=>200,
                    'message'=>'Whatsapp messages for the pending lesson plan sended successfully.!',
                    'data'=>$lessonplanteachers,
                    'success'=>true
                    ]);
        
    }

    //API for the Teacher attendance monthly report Dev Name- Manish Kumar Sharma 15-06-2025
    public function getTeacherAttendanceMonthlyReport(Request $request,$month){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                 $teachers = DB::table('teacher')->where('isDelete', 'N')->get(); // Replace with actual active condition
                         // You can fetch this from DB if needed

                        list($startYear, $endYear) = explode('-', $customClaims);
                        
                        // Determine the correct year for this month
                        $year = ($month >= 4) ? $startYear : $endYear;
                        $report = [];
                    
                        foreach ($teachers as $teacher) {
                            $teacherReport = [
                                'teacher_id' => $teacher->teacher_id,
                                'name' => $teacher->name,
                                'days' => []
                            ];
                    
                            for ($day = 1; $day <= 31; $day++) {
                                $d = str_pad($day, 2, '0', STR_PAD_LEFT);
                                $m = str_pad($month, 2, '0', STR_PAD_LEFT);
                                $adate = "$year-$m-$d";
                    
                                $inTime = $this->getPunchInTime($teacher->teacher_id, $adate);
                                $outTime = $this->getPunchOutTime($teacher->teacher_id, $adate);
                    
                                $teacherReport['days'][] = [
                                    'date' => $adate,
                                    'in' => $inTime,
                                    'out' => $outTime,
                                ];
                            }
                    
                            $report[] = $teacherReport;
                        }
                    
                        return response()->json([
                            'status' => true,
                            'month' => $month,
                            'year' => $year,
                            'report' => $report
                        ]);
                
                
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the get teacher attendance monthly report.',
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
    //API for the Teacher attendance monthly report Dev Name- Manish Kumar Sharma 15-06-2025
    protected function getPunchInTime($teacherId, $date)
    {
        return DB::table('teacher_attendance as a')
            ->join('teacher as b', 'a.employee_id', '=', 'b.employee_id')
            ->whereDate('a.punch_time', $date)
            ->where('b.teacher_id', $teacherId)
            ->selectRaw('MIN(TIME(a.punch_time)) as punch_in_time')
            ->value('punch_in_time');
    }
    //API for the Teacher attendance monthly report Dev Name- Manish Kumar Sharma 15-06-2025
    protected function getPunchOutTime($teacherId, $date)
    {
        return DB::table('teacher_attendance as a')
            ->join('teacher as b', 'a.employee_id', '=', 'b.employee_id')
            ->whereDate('a.punch_time', $date)
            ->where('b.teacher_id', $teacherId)
            ->selectRaw('CASE 
                WHEN COUNT(a.punch_time) > 1 
                THEN MAX(TIME(a.punch_time)) 
                ELSE NULL 
            END as punch_out_time')
            ->value('punch_out_time');
    }

     public function getStaffLeaveReport(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            if ($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M' || $user->role_id == 'U') {
                // if (!in_array($user->role_id, ['U', 'T', 'M', 'A'])) {
                //     return response()->json([
                //         'status' => 401,
                //         'message' => 'Unauthorized access',
                //         'success' => false
                //     ]);
                // }

                $staff_id = $request->input('staff_id');
                $from_date = $request->input('from_date');
                $to_date = $request->input('to_date');

                // Step 1: Get all leave types
                $leave_types = DB::table('leave_type_master')->pluck('name', 'leave_type_id');

                // Step 2: Get staff list
                $staff_query = DB::table('teacher')->select('teacher_id', 'name')->where('isDelete', 'N');
                if (!empty($staff_id)) {
                    $staff_query->where('teacher_id', $staff_id);
                }
                $staffList = $staff_query->get();

                $result = [];

                // Step 3: For each staff and each leave type, calculate leaves
                foreach ($staffList as $staff) {
                    $staffData = [
                        'staff_id' => $staff->teacher_id,
                        'staff_name' => $staff->name,
                        'leaves' => [],
                        'total' => 0
                    ];

                    foreach ($leave_types as $leave_type_id => $leave_type_name) {
                        $query = DB::table('leave_application')
                            ->where('staff_id', $staff->teacher_id)
                            ->where('leave_type_id', $leave_type_id)
                            ->where('academic_yr', $academic_year)
                            ->where('status', 'P');

                        if (!empty($from_date)) {
                            $query->whereDate('leave_start_date', '>=', date('Y-m-d', strtotime($from_date)));
                        }

                        if (!empty($to_date)) {
                            $query->whereDate('leave_end_date', '<=', date('Y-m-d', strtotime($to_date)));
                        }

                        $count = $query->sum('no_of_days') ?? 0;

                        $staffData['leaves'][$leave_type_name] = (float) $count;
                        $staffData['total'] += (float) $count;
                    }

                    $result[] = $staffData;
                }

                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'leave_types' => array_values($leave_types->toArray()),
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'This User Doesnot have Permission for the Balance Leave Report',
                    'data' => $user->role_id,
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Reports Lesson Plan Status Report Dev Name-Manish Kumar Sharma 14-07-2025
    public function getLessonPlanStatusReport(Request $request){
        try {
            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');
            $teacherId = $request->input('teacher_id');
            $classId = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $status = $request->input('status');
            $query = DB::table('lesson_plan')
                        ->select(
                            'lesson_plan.*',
                            'class.name as classname',
                            'section.name as secname',
                            'subject_master.name as subname',
                            'chapters.name as chaptername'
                        )
                        ->join('class', 'lesson_plan.class_id', '=', 'class.class_id')
                        ->join('section', 'lesson_plan.section_id', '=', 'section.section_id')
                        ->join('subject_master', 'lesson_plan.subject_id', '=', 'subject_master.sm_id')
                        ->join('chapters', 'lesson_plan.chapter_id', '=', 'chapters.chapter_id')
                        ->where('chapters.isDelete', '!=', 'Y')
                        ->where('lesson_plan.reg_id', $teacherId)
                        ->where('lesson_plan.academic_yr', $academic_year);
                
                    if (!empty($classId)) {
                        $query->where('lesson_plan.class_id', $classId)
                              ->where('lesson_plan.section_id', $sectionId);
                    }
                
                    if (!empty($status)) {
                        $query->where('lesson_plan.status', $status);
                    }
                    $result =  $query->get()->toArray();
                    return response()->json([
                    'status'=>200,
                    'message'=>'Lesson plan status report!',
                    'data'=>$result,
                    'success'=>true
                    ]);
            
            
         }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }
     //Reports Lesson Plan Summarised Report Dev Name - Manish Kumar Sharma 14-07-2025
    public function getLessonPlanSummarisedReport(Request $request){
        try {
            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');
            $staffId = $request->input('teacher_id');
            $week = $request->input('week');
            $subjectId = $request->input('subject_id');
             $query = DB::table('lesson_plan')
                        ->select(
                            'lesson_plan.*',
                            'class.name as classname',
                            'section.name as secname',
                            'subject_master.name as subname',
                            'chapters.chapter_no',
                            'chapters.name as chaptername',
                            'chapters.sub_subject',
                            'teacher.name as teachername'
                        )
                        ->join('class', 'lesson_plan.class_id', '=', 'class.class_id')
                        ->join('section', 'lesson_plan.section_id', '=', 'section.section_id')
                        ->join('subject_master', 'lesson_plan.subject_id', '=', 'subject_master.sm_id')
                        ->join('chapters', 'lesson_plan.chapter_id', '=', 'chapters.chapter_id')
                        ->join('teacher','teacher.teacher_id','=','lesson_plan.reg_id')
                        ->where('chapters.isDelete', '!=', 'Y')
                        ->where('lesson_plan.academic_yr', $academic_year)
                        ->orderByDesc('lesson_plan.lesson_plan_id')
                        ->groupBy('lesson_plan.unq_id');
                
                    if (!empty($staffId)) {
                        $query->where('lesson_plan.reg_id', $staffId);
                    }
                
                    if (!empty($week)) {
                        $query->where('lesson_plan.week_date', $week);
                    }
                    if (!empty($subjectId)) {
                        $query->where('lesson_plan.subject_id', $subjectId);
                    }
                
                    $lessonPlans = $query->get()->toArray();
                    foreach ($lessonPlans as &$plan) {
                        $plan = (array) $plan; 
                        $plan['classnames'] = getLpClassNamesByUnqId($plan['unq_id'], $academic_year);
                    }
                    
                     return response()->json([
                    'status'=>200,
                    'message'=>'Lesson plan summarised report!',
                    'data'=>$lessonPlans,
                    'success'=>true
                    ]);
            
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }
    //Reports Lesson Plan detailed Report Dev Name - Manish Kumar Sharma 14-07-2025
    public function getLessonPlanDetailedReport(Request $request){
        try {
            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');
            $staffId = $request->query('staff_id');
            $week = $request->query('week');
            $month = $request->query('month');
            $query = DB::table('lesson_plan')
        ->select(
            'lesson_plan.*',
            'class.name as classname',
            'section.name as secname',
            'subject_master.name as subname',
            'chapters.chapter_no',
            'chapters.name as chaptername',
            'chapters.sub_subject',
            'teacher.name as teachername'
        )
        ->join('class', 'lesson_plan.class_id', '=', 'class.class_id')
        ->join('teacher', 'teacher.teacher_id', '=', 'lesson_plan.reg_id')
        ->join('section', 'lesson_plan.section_id', '=', 'section.section_id')
        ->join('subject_master', 'lesson_plan.subject_id', '=', 'subject_master.sm_id')
        ->join('chapters', 'lesson_plan.chapter_id', '=', 'chapters.chapter_id')
        ->where('chapters.isDelete', '!=', 'Y')
        ->where('lesson_plan.academic_yr', $academic_year)
        ->orderByDesc('lesson_plan.lesson_plan_id')
        ->groupBy('lesson_plan.unq_id');

        if (!empty($staffId)) {
            $query->where('lesson_plan.reg_id', $staffId);
        }
    
        if (!empty($week)) {
            $query->where('lesson_plan.week_date', $week);
        }
        
         if (!empty($month)) {
            $query->whereRaw('MONTH(STR_TO_DATE(SUBSTRING_INDEX(lesson_plan.week_date, " / ", 1), "%d-%m-%Y")) = ?', [$month]);
        }
    
        $lessonPlans = $query->get();
    
        foreach ($lessonPlans as $plan) {
            $plan->class_names = DB::table('lesson_plan as a')
                ->join('class as b', 'a.class_id', '=', 'b.class_id')
                ->join('section as c', 'a.section_id', '=', 'c.section_id')
                ->select('b.name as class_name', 'c.name as sec_name')
                ->where('a.academic_yr', $academic_year)
                ->where('a.unq_id', $plan->unq_id)
                ->orderBy('a.class_id')
                ->get()
                ->map(fn ($x) => $x->class_name . ' ' . $x->sec_name)
                ->implode(', ');
        }
    
        // Step 3: Attach static headings
        $nonDailyHeadings = DB::table('lesson_plan_heading')
            ->where('change_daily', '!=', 'Y')
            ->orderBy('sequence')
            ->get();
    
        $dailyHeadings = DB::table('lesson_plan_heading')
            ->where('change_daily', '=', 'Y')
            ->orderBy('sequence')
            ->get();
    
        // Step 4: Attach headings and descriptions
        foreach ($lessonPlans as $plan) {
            // Non-daily content
            $plan->non_daily = [];
            foreach ($nonDailyHeadings as $heading) {
                $desc = DB::table('lesson_plan_details')
                    ->where('lesson_plan_headings_id', $heading->lesson_plan_headings_id)
                    ->where('lesson_plan_id', $plan->lesson_plan_id)
                    ->value('description');
                $plan->non_daily[] = [
                    'heading' => $heading->name,
                    'description' => explode(PHP_EOL, $desc ?? '')
                ];
            }
    
            $plan->daily_changes = [];
            foreach ($dailyHeadings as $heading) {
                $entries = DB::table('lesson_plan_details')
                    ->where('lesson_plan_headings_id', $heading->lesson_plan_headings_id)
                    ->where('lesson_plan_id', $plan->lesson_plan_id)
                    ->get();
    
                $headingData = [
                    'heading' => $heading->name,
                    'entries' => []
                ];
                
                foreach ($entries as $entry) {
                    $headingData['entries'][] = [
                        'start_date' => $entry->start_date == '0000-00-00' ? '' : date('d-m-Y', strtotime($entry->start_date)),
                        'description' => explode(PHP_EOL, $entry->description ?? '')
                    ];
                }
                
                $plan->daily_changes[] = $headingData;
            }
        }
    
        return response()->json([
            'status'=>200,
            'message'=>'Lesson plan detailed report!',
            'data'=>$lessonPlans,
            'success'=>true
        ]);
            
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }

    public function teachersRemarkReport(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_year = JWTAuth::getPayload()->get('academic_year');

        if ($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M' || $user->role_id == 'U') {

            $date = $request->input('date');
            $staff_id = $request->input('staff_id');

            $query = DB::table('teachers_remark as tr')
                ->join('teacher', 'teacher.teacher_id', '=', 'tr.teachers_id')
                ->select('tr.*', 'teacher.name')
                ->where('tr.academic_yr', 'like', '%');

            if (!empty($date)) {
                $query->where(DB::raw("DATE_FORMAT(tr.remark_date, '%d-%m-%Y')"), '=', $date);
            }

            if (!empty($staff_id)) {
                $query->where('tr.teachers_id', $staff_id);
            }

            $query->orderByDesc('tr.t_remark_id');
            $remarks = $query->get();


            $remarks = $remarks->map(function ($row) {
                $row->published = ($row->remark_type === 'Remark' && $row->publish === 'Y') ? 'Yes' : 'No';
                $row->acknowledged = ($row->remark_type === 'Remark' && $row->acknowledge === 'Y') ? 'Yes' : 'No';
                $row->viewed = ($row->remark_type === 'Remark' && checkTeacherRemarkViewed($row->t_remark_id, $row->teachers_id) === 'Y') ? 'Yes' : 'No';
                $row->formatted_date = $row->publish_date && $row->publish_date !== '0000-00-00'
                    ? \Carbon\Carbon::parse($row->publish_date)->format('d-m-Y')
                    : \Carbon\Carbon::parse($row->remark_date)->format('d-m-Y');
                return $row;
            });

            return response()->json([
                'status' => 200,
                'message' => 'Teachers Remarks Data Successfully',
                'success' => true,
                'data' => $remarks,
            ]);
        } else {
            return response()->json([
                'status' => 401,
                'message' => 'This User Doesnot have Permission for the Balance Leave Report',
                'data' => $user->role_id,
                'success' => false
            ]);
        }
    }
    
    public function getStaffDetailedYearwiseAttendance(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_year = JWTAuth::getPayload()->get('academic_year');

        if (in_array($user->role_id, ['A', 'T', 'M', 'U'])) {

            $fromDate = getAcademicYearFrom();
            $toDate = getAcademicYearTo();

            $month = $request->input('month');
            $staffId = $request->input('staff_id'); // This is teacher.teacher_id

            $query = DB::table('teacher_attendance as a')
                ->select(
                    'a.employee_id',
                    DB::raw('COUNT(DISTINCT DATE(a.punch_time)) AS attend_cnt'),
                    DB::raw('MONTHNAME(a.punch_time) AS month_name'),
                    'b.name',
                    'b.tc_id',
                    'b.teacher_id'
                )
                ->join('teacher as b', 'a.employee_id', '=', 'b.employee_id')
                ->whereDate('a.punch_time', '>=', $fromDate)
                ->whereDate('a.punch_time', '<=', $toDate);

            // Optional filter: teacher name
            if (!empty($teacherName)) {
                $query->where('b.name', 'like', '%' . $teacherName . '%');
            }

            // Optional filter: month (number or name)
            if (!empty($month)) {
                if (is_numeric($month)) {
                    $query->whereMonth('a.punch_time', $month);
                } else {
                    $query->whereRaw('MONTHNAME(a.punch_time) = ?', [$month]);
                }
            }

            // Optional filter: staff_id (which is teacher.teacher_id)
            if (!empty($staffId)) {
                $query->where('b.teacher_id', $staffId);
            }

            $attendance = $query
                ->groupBy('a.employee_id', DB::raw('MONTHNAME(a.punch_time)'))
                ->orderBy('a.employee_id')
                ->orderBy(DB::raw('MONTH(a.punch_time)'))
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Staff Yearwise Attendance Data Retrieved Successfully',
                'success' => true,
                'data' => $attendance,
            ]);
        } else {
            return response()->json([
                'status' => 401,
                'message' => 'This User Does Not Have Permission for the Staff Yearwise Attendance Report',
                'data' => $user->role_id,
                'success' => false
            ]);
        }
    }
    
    public function getStudentDailyAttendanceMonthwise(Request $request){
            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');
            $classId = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $monthYear = $request->input('month_year');
            $academicYear = $academic_year;

        try {
            // Parse month and year
            [$month, $year] = explode('-', $monthYear);
            $monthName = Carbon::createFromFormat('m', $month)->format('F');
            
            // Check if data exists for this month
            $workingDays = DB::table('attendance')
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->whereRaw("MONTHNAME(only_date) = ?", [$monthName])
                ->where('academic_yr', $academicYear)
                ->groupBy('student_id')
                ->selectRaw('COUNT(*) as workingdays_count')
                ->get()
                ->max('workingdays_count') ?? 0;

            if ($workingDays === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data available'
                ]);
            }

            // Get class and section names
            $className = DB::table('class')->where('class_id', $classId)->value('name') ?? 'Unknown Class';
            $sectionName = DB::table('section')->where('section_id', $sectionId)->value('name') ?? 'Unknown Section';

            // Get academic year settings
            $academicSettings = DB::table('settings')
                ->where('academic_yr', $academicYear)
                ->first();

            // Generate date range for the month
            $startDate = Carbon::createFromFormat('F Y', $monthName . ' ' . $year)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            
            $dateRange = [];
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                $dateRange[] = [
                    'date' => $currentDate->format('d-m-Y'),
                    'day' => $currentDate->format('D'),
                    'formatted_date' => $currentDate->format('d-m-y'),
                    'db_date' => $currentDate->format('Y-m-d')
                ];
                $currentDate->addDay();
            }

            // Get all attendance data for the month
            $dates = array_column($dateRange, 'db_date');
            $attendanceData = DB::table('attendance as a')
                ->join('student as s', 'a.student_id', '=', 's.student_id')
                ->where('a.class_id', $classId)
                ->where('a.section_id', $sectionId)
                ->whereIn('a.only_date', $dates)
                ->where('a.academic_yr', $academicYear)
                ->select('a.student_id', 'a.only_date', 'a.attendance_status', 's.first_name', 's.last_name', 's.roll_no', 's.isDelete')
                ->get()
                ->groupBy('student_id');

            // Get students with their monthly attendance summary
            $students = DB::table('student as s')
                ->join('attendance as a', 's.student_id', '=', 'a.student_id')
                ->where('a.class_id', $classId)
                ->where('a.section_id', $sectionId)
                ->whereRaw("MONTHNAME(a.only_date) = ?", [$monthName])
                ->where('a.academic_yr', $academicYear)
                ->groupBy('s.student_id')
                ->selectRaw('s.student_id, s.first_name, s.last_name, s.roll_no, s.isDelete, SUM(IF(a.attendance_status = 0, 1, 0)) as present_count')
                ->orderBy('s.roll_no')
                ->get();
            // dd($students);

            // Process student data
            $processedStudents = [];
            $dailyTotals = [
                'present' => array_fill(0, count($dateRange), 0),
                'absent' => array_fill(0, count($dateRange), 0)
            ];

            foreach ($students as $student) {
                $studentAttendance = $attendanceData->get($student->student_id) ?? collect();
                
                $studentData = [
                    'student_id' => $student->student_id,
                    'name' => trim($student->first_name . ' ' . $student->last_name),
                    'roll_no' => $student->isDelete === 'Y' ? 'Left' : $student->roll_no,
                    'is_deleted' => $student->isDelete === 'Y',
                    'daily_attendance' => [],
                    'present_days' => 0,
                    'absent_days' => 0,
                    'working_days'=>0
                ];

                // Process daily attendance
                foreach ($dateRange as $index => $dateInfo) {
                    $dayAttendance = $studentAttendance->where('only_date', $dateInfo['db_date'])->first();
                    $duplicateMarker = $this->hasDuplicateAttendance($student->student_id, $dateInfo['date']); // 'd-m-Y'
                    $entry = [
                        'date' => $dateInfo['date'],
                        'status' => '',
                        'duplicate' => false
                    ];
                    
                    if ($dayAttendance) {
                        if ($dayAttendance->attendance_status == 0) {
                            $entry['status'] = 'P';
                            $studentData['present_days']++;
                            $dailyTotals['present'][$index]++;
                            $studentData['working_days']++;
                        } else {
                             $entry['status'] = 'A';
                            $studentData['absent_days']++;
                            $dailyTotals['absent'][$index]++;
                            $studentData['working_days']++;
                        }
                         if ($duplicateMarker === '*') {
                        $entry['duplicate'] = true;
                    }
                    } else {
                         $entry['status'] = '';
                    }
                   
                     $studentData['daily_attendance'][] = $entry;
                 }

                // Calculate previous attendance and cumulative data
                if ($academicSettings) {
                    // dd(end($dateRange)['db_date']);
                    $endOfMonth = Carbon::parse(end($dateRange)['db_date']); // Convert string to Carbon object
                    $endOfPrevMonth = $endOfMonth->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d');
                                    // dd($endOfPrevMonth);
                    //  dd($endOfPrevMonth,$endOfMonth);
                    $totalWorkingDataTillMonth = DB::table('attendance')
                                ->where('student_id', $student->student_id)
                                ->where('only_date', '>=', $academicSettings->academic_yr_from)
                                ->where('only_date', '<=', $endOfMonth)
                                ->selectRaw('
                                    SUM(IF(attendance_status = 0, 1, 0)) as total_present_days,
                                    SUM(IF(attendance_status = 1, 1, 0)) as total_absent_days,
                                    SUM(IF(attendance_status IN (0, 1), 1, 0)) as total_present_absent_days_till_month
                                ')
                                ->first();
                                                
                                                // dd($totalWorkingDataTillMonth);
                        
                    $prevAttendance = DB::table('attendance')
                        ->where('student_id', $student->student_id)
                        ->where('only_date', '>=', $academicSettings->academic_yr_from)
                        ->where('only_date', '<=', $endOfPrevMonth)
                        ->selectRaw('SUM(IF(attendance_status = 0, 1, 0)) as total_present_days')
                        ->first();
                        // dd($prevAttendance);
                    
                    $studentData['prev_attendance'] = (int)$prevAttendance->total_present_days ?? 0;
                    $studentData['total_attendance'] = $studentData['present_days'] + $studentData['prev_attendance'];
                    $studentData['total_working_days_till_month']=(int)$totalWorkingDataTillMonth->total_present_absent_days_till_month ?? 0;
                    
                    
                    $cumulativeAbsent = DB::table('attendance')
                        ->where('student_id', $student->student_id)
                        ->where('only_date', '>=', $academicSettings->academic_yr_from)
                        ->where('only_date', '<=', $endOfMonth)
                        ->selectRaw('SUM(attendance_status) as total_absent_days')
                        ->first();
                    
                    $studentData['cumulative_absent_days'] = $cumulativeAbsent->total_absent_days ?? 0;
                } else {
                    $studentData['prev_attendance'] = 0;
                    $studentData['total_attendance'] = $studentData['present_days'];
                    $studentData['cumulative_absent_days'] = 0;
                }

                $processedStudents[] = $studentData;
            }

            $totalPresentDays = array_sum(array_column($processedStudents, 'present_days'));
            $totalAbsentDays = array_sum(array_column($processedStudents, 'absent_days'));
            $totalPrevAttendance = array_sum(array_column($processedStudents, 'prev_attendance'));
            $totalAttendance = array_sum(array_column($processedStudents, 'total_attendance'));
            $totalCumulativeAbsentDays = array_sum(array_column($processedStudents, 'cumulative_absent_days'));
            $totalwokingdays = array_sum(array_column($processedStudents, 'working_days'));
            $totalwokingdaystillmonth = array_sum(array_column($processedStudents, 'total_working_days_till_month'));
            $totalPrevAbsentDays = 0;
            if ($academicSettings) {
                $endOfMonth = end($dateRange)['db_date'];
                $prevAbsent = DB::table('attendance')
                    ->where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('only_date', '>=', $academicSettings->academic_yr_from)
                    ->where('only_date', '<=', $endOfPrevMonth)
                    ->selectRaw('SUM(IF(attendance_status = 1, 1, 0)) as total_absent_days')
                    ->first();
                
                $totalPrevAbsentDays = $prevAbsent->total_absent_days ?? 0;
            }

            // Calculate daily totals
            $dailyTotal = [];
            for ($i = 0; $i < count($dateRange); $i++) {
                $dailyTotal[] = $dailyTotals['present'][$i] + $dailyTotals['absent'][$i];
            }

            // Prepare response
            $response = [
                'success' => true,
                'data' => [
                    'class_name' => $className,
                    'section_name' => $sectionName,
                    'month_name' => $monthName,
                    'year' => $year,
                    'academic_year' => $academicYear,
                    'date_range' => array_map(function($date) {
                        unset($date['db_date']); 
                        return $date;
                    }, $dateRange),
                    'students' => $processedStudents,
                    'totals' => [
                        'daily_present' => $dailyTotals['present'],
                        'daily_absent' => $dailyTotals['absent'],
                        'daily_total' => $dailyTotal,
                        'total_present_days' => $totalPresentDays,
                        'total_absent_days' => $totalAbsentDays,
                        'total_present_absent_days'=>$totalPresentDays + $totalAbsentDays,
                        'total_prev_attendance' => $totalPrevAttendance,
                        'total_previous_attendance' => $totalPrevAttendance+$totalPrevAbsentDays,
                        'total_prev_absent_days' => (int) $totalPrevAbsentDays,
                        'total_cumulative_absent_days' => $totalCumulativeAbsentDays,
                        'total_attendance' => $totalAttendance,
                        'grand_total_absent_attendance'=>$totalAbsentDays+$totalPrevAbsentDays,
                        'grand_total_attendance' => $totalPresentDays + $totalAbsentDays + $totalPrevAttendance + $totalPrevAbsentDays,
                        'total_working_days_for_this_month'=>$totalwokingdays,
                        'total_working_days_till_month'=>$totalwokingdaystillmonth
                    ]
                ],
                'status'=>200
                
            ];

            return response()->json($response);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating attendance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function hasDuplicateAttendance($studentId, $date)
    {
        return DB::table('attendance')
            ->where('student_id', $studentId)
            ->whereRaw("DATE_FORMAT(only_date, '%d-%m-%Y') = ?", [$date])
            ->count() > 1 ? '*' : '';
    }
    
    public function getAttendanceMarkingStatus(Request $request){
        $user = $this->authenticateUser();
        $academic_year = JWTAuth::getPayload()->get('academic_year');
        $section = $request->input('section');
        $date    = $request->input('date');
        $acd_yr  = $academic_year;
        if($section=='all'){
        $dept1="'Primary','Secondary'";
        }else{
            $dept1 = "'$section'";
        }

    $rows = DB::select("
        select department.*, class.class_id, class.name as class_name, 
               section.section_id, section.name as sec_name 
        from department, class, section 
        where department.department_id = class.department_id 
        and class.class_id = section.class_id 
        and department.name IN ($dept1) 
        and department.academic_yr = '$acd_yr'
    ");
    
    

        $report = [];

        foreach ($rows as $row) {
            $teacherInfo = DB::table('class_teachers')
                ->where('class_id', $row->class_id)
                ->where('section_id', $row->section_id)
                ->first();

            $mainTeacherId = $teacherInfo->teacher_id ?? null;
            $mainTeacher   = $mainTeacherId
                ? DB::table('teacher')->where('teacher_id', $mainTeacherId)->value('name')
                : null;

            $substitute = DB::table('class_teacher_substitute as sub')
                ->join('teacher as t', 'sub.teacher_id', 't.teacher_id')
                ->where('sub.class_teacher_id', $mainTeacherId)
                ->where('sub.academic_yr', $acd_yr)
                ->whereDate('sub.start_date', '<=', $date)
                ->whereDate('sub.end_date', '>=', $date)
                ->select('sub.teacher_id', 't.name')
                ->first();

            $subteacherName = $substitute->name ?? null;

            // 4️⃣ Check who marked attendance on this date
            $att = DB::table('attendance as a')
                ->join('student as st', 'a.student_id', 'st.student_id')
                ->where('a.class_id', $row->class_id)
                ->where('a.section_id', $row->section_id)
                ->where('a.academic_yr', $acd_yr)
                ->whereDate('a.only_date', $date)
                ->select(DB::raw('COUNT(*) as attendance_count'), 'a.teacher_id')
                ->first();

            $markedBy = ($att->attendance_count ?? 0) > 0
                ? DB::table('teacher')->where('teacher_id', $att->teacher_id)->value('name')
                : null;

            $marked = ($att->attendance_count ?? 0) > 0 ? 'Y' : 'N';

            $report[] = [
                'class_section'        => "{$row->class_name}-{$row->sec_name}",
                'class_teacher'        => $mainTeacher,
                'substitute_teacher'   => $subteacherName,
                'attendance_marked_by' => $markedBy,
                'marked'               => $marked,
            ];
        }

        return response()->json([
            'date'     => $date,
            'academic_yr' => $acd_yr,
            'report'   => $report,
        ]);
        
    }
    
    public function getHomeworkStatusReport(Request $request){
        $user = $this->authenticateUser();
        $academicYear = JWTAuth::getPayload()->get('academic_year');
        try {
            $classId = $request->input('class_id');
            // dd($class_id);
            $className = DB::table('class')->where('class_id', $classId)->value('name');
            $subjects = DB::table('subject as s')
            ->distinct()
            ->join('subject_master as sm', 's.sm_id', 'sm.sm_id')
            ->where('sm.subject_type','!=','Social')
            ->where('s.class_id', $classId)
            ->where('s.academic_yr', $academicYear)
            ->select('sm.sm_id', 'sm.name')
            ->orderBy('sm.sm_id')
            ->get();

        // Get sections for the class
        $sections = DB::table('section')
            ->where('class_id', $classId)
            ->where('academic_yr', $academicYear)
            ->orderBy('name')
            ->get();

        // Compile report data
        $data = [];

        foreach ($sections as $section) {
            $row = [
                'class_name' =>$className."-". $section->name,
                'subjects'     => []
            ];

            foreach ($subjects as $subj) {
                // Last homework date
                $lastHomework = DB::table('homework')
                    ->where('class_id', $classId)
                    ->where('section_id', $section->section_id)
                    ->where('sm_id', $subj->sm_id)
                    ->where('publish', 'Y')
                    ->max('publish_date'); 

                // Teacher name
                $teacher = DB::table('subject as s')
                    ->join('teacher', 's.teacher_id', 'teacher.teacher_id')
                    ->where('s.class_id', $classId)
                    ->where('s.section_id', $section->section_id)
                    ->where('s.sm_id', $subj->sm_id)
                    ->value('teacher.name');

                $row['subjects'][] = [
                    'subject_name'      => $subj->name,
                    'last_homework_date'=> $lastHomework ? date('d-m-Y', strtotime($lastHomework)) : null,
                    'status'            => $lastHomework ? 'Last HW on ' . date('d-m-Y', strtotime($lastHomework)) : 'No HW',
                    'status_color'      => $lastHomework ? 'black' : 'red',
                    'teacher_name'      => $teacher ? "($teacher)" : ''
                ];
            }

            $data[] = $row;
        }
        $report['data'] = $data;
        $report['subjects']=$subjects;
        
        

        return response()->json([
            'status'  =>200,
            'data'  => $report,
            'success' =>true
        ]);
            
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }
    
    public function getTeachersByClassSection(Request $request){
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        try {
            $classId = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $teachersaccordingtoclass = DB::table('subject')
                                            ->join('teacher','teacher.teacher_id','=','subject.teacher_id')
                                            ->where('subject.class_id',$classId)
                                            ->where('subject.section_id',$sectionId)
                                            ->select('teacher.name as teachername','subject.teacher_id')
                                            ->distinct()
                                            ->get();
                                             return response()->json([
                                                'status'  =>200,
                                                'message'=>'Teacher list by class and section.',
                                                'data'  => $teachersaccordingtoclass,
                                                'success' =>true
                                            ]);
            
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }
    
    
    public function getClasswiseHomework(Request $request)
    {
        try {

            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            // if ($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M' || $user->role_id == 'U') {
            //     $classId = $request->query('class_id');
            //     $sectionId = $request->query('section_id');
            //     $date = $request->query('date'); // format: YYYY-MM-DD

            //     if (!$classId || !$sectionId) {
            //         return response()->json([
            //             'status' => false,
            //             'message' => 'class_id and section_id are required',
            //         ], 400);
            //     }

            //     $query = DB::table('homework')
            //         ->join('class', 'class.class_id', '=', 'homework.class_id')
            //         ->join('section', 'section.section_id', '=', 'homework.section_id')
            //         ->join('subject_master', 'subject_master.sm_id', '=', 'homework.sm_id')
            //         ->join('teacher', 'teacher.teacher_id', '=', 'homework.teacher_id')
            //         ->select(
            //             'homework.*',
            //             'class.name as class_name',
            //             'subject_master.name as sub_name',
            //             'section.section_id',
            //             'section.name as sec_name',
            //             'teacher.name as tec_name',
            //             'teacher.teacher_id'
            //         )
            //         ->where('homework.class_id', $classId)
            //         ->where('homework.section_id', $sectionId)
            //         ->where('homework.publish', 'Y')
            //         ->when($date, function ($query, $date) {
            //             return $query->whereDate('homework.publish_date', $date);
            //         })
            //         ->orderByDesc('homework.publish_date')
            //         ->get();

            //     return response()->json([
            //         'status' => 200,
            //         'message' => 'Homework Classwise Details Report Successfully',
            //         'success' => true,
            //         'data' => $query,
            //     ]);
            // } 

            if (in_array($user->role_id, ['A', 'T', 'M', 'U'])) {
                $sectionId = $request->query('section_id');
                $classId = $request->query('class_id'); // optional
                $date = $request->query('date'); // optional

                if (!$sectionId) {
                    return response()->json([
                        'status' => false,
                        'message' => 'section_id is required',
                    ], 400);
                }

                $query = DB::table('homework')
                    ->join('class', 'class.class_id', '=', 'homework.class_id')
                    ->join('section', 'section.section_id', '=', 'homework.section_id')
                    ->join('subject_master', 'subject_master.sm_id', '=', 'homework.sm_id')
                    ->join('teacher', 'teacher.teacher_id', '=', 'homework.teacher_id')
                    ->select(
                        'homework.*',
                        'class.name as class_name',
                        'subject_master.name as sub_name',
                        'section.section_id',
                        'section.name as sec_name',
                        'teacher.name as tec_name',
                        'teacher.teacher_id'
                    )
                    ->where('subject_master.subject_type','!=','Social')
                    ->where('homework.section_id', $sectionId)
                    ->where('homework.publish', 'Y')
                    ->when($classId, function ($query, $classId) {
                        return $query->where('homework.class_id', $classId);
                    })
                    ->when($date, function ($query, $date) {
                        return $query->whereDate('homework.publish_date', $date);
                    })
                    ->orderByDesc('homework.publish_date')
                    ->get();

                return response()->json([
                    'status' => 200,
                    'message' => 'Homework Classwise Details Report Successfully',
                    'success' => true,
                    'data' => $query,
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'This User Does Not Have Permission for the Staff Yearwise Attendance Report',
                    'data' => $user->role_id,
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getHomeworkNotAssignedReport(Request $request){
        try {

            $user = $this->authenticateUser();
            $academicYr = JWTAuth::getPayload()->get('academic_year');
            $classId   = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $teacherId = $request->input('teacher_id'); // optional
            $daterange = $request->input('daterange');
            // dd($classId,$sectionId,$daterange);
        

        $dates = explode(' / ', $daterange); // format: "2025-07-10 - 2025-07-19"
        if (count($dates) !== 2) {
            return response()->json(['error' => 'daterange format should be "YYYY-MM-DD / YYYY-MM-DD"'], 400);
        }

        try {
            $startDate = Carbon::parse(trim($dates[0]));
            $endDate   = Carbon::parse(trim($dates[1]));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date range format'], 400);
        }

        $subjectsQuery = DB::table('subject')
            ->join('subject_master', 'subject_master.sm_id', '=', 'subject.sm_id')
            ->select('subject_master.sm_id', 'subject_master.name')
            ->where('subject_master.subject_type','!=','Social')
            ->where('subject.class_id', $classId)
            ->where('subject.section_id', $sectionId)
            ->where('subject.academic_yr', $academicYr);

        if ($teacherId) {
            $subjectsQuery->where('subject.teacher_id', $teacherId);
        }

        $subjects = $subjectsQuery->distinct()->orderBy('subject_master.name', 'asc')->get();

        $workdays = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->isWeekday()) {
                $workdays[] = $current->toDateString();
            }
            $current->addDay();
        }

        $missingHomework = [];

        foreach ($subjects as $subject) {
            $missingDates = [];
        
            foreach ($workdays as $date) {
                $count = DB::table('homework')
                    ->where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('sm_id', $subject->sm_id)
                    ->where('publish_date', $date)
                    ->where('publish', 'Y')
                    ->count();
        
                if ($count === 0) {
                    $missingDates[] = Carbon::parse($date)->format('d-m-Y');
                }
            }
        
            $className = DB::table('class')->where('class_id', $classId)->value('name');
            $sectionName = DB::table('section')->where('section_id', $sectionId)->value('name');
            if (!empty($missingDates)) {
                $missingHomework[] = [
                    'classname' => $className."-".$sectionName,
                    'teacher_id'  => $teacherId ?? null,
                    'subject'     => $subject->name,
                    'dates'       => $missingDates
                ];
            }
        }
        
        return response()->json([
            'status'=>200,
            'data' => $missingHomework,
            'message'=>'Missing homework report!',
            'success'=>true
            ]);
            
            
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }
    
    // public function getClasswiseReportCardMarksReport(Request $request){
        

    //         $user = $this->authenticateUser();
    //         $academicYr = JWTAuth::getPayload()->get('academic_year');
    //         $classId   = $request->input('class_id');
    //         $sectionId = $request->input('section_id');
    //         $academicYear = '2024-2025';
    
    //         if (!$classId || !$sectionId || !$academicYear) {
    //             return response()->json(['message' => 'Missing required parameters.'], 400);
    //         }

        
    //         // 1. Get subject list
    //         $subjects = DB::table('subjects_on_report_card as a')
    //             ->join('subjects_on_report_card_master as b', 'a.sub_rc_master_id', '=', 'b.sub_rc_master_id')
    //             ->select('a.sub_rc_master_id', 'b.name', 'a.subject_type')
    //             ->where('a.class_id', $classId)
    //             ->where('a.academic_yr', $academicYear)
    //             ->orderBy('a.class_id')
    //             ->orderBy('b.sequence')
    //             ->distinct()
    //             ->get();

    //         // 2. Get students
    //         $students = DB::table('student as a')
    //             ->leftJoin('parent as b', 'a.parent_id', '=', 'b.parent_id')
    //             ->join('user_master as c', 'a.parent_id', '=', 'c.reg_id')
    //             ->join('class as d', 'a.class_id', '=', 'd.class_id')
    //             ->join('section as e', 'a.section_id', '=', 'e.section_id')
    //             ->leftJoin('house as f', 'a.house', '=', 'f.house_id')
    //             ->where([
    //                 ['a.IsDelete', 'N'],
    //                 ['a.academic_yr', $academicYear],
    //                 ['a.class_id', $classId],
    //                 ['a.section_id', $sectionId],
    //                 ['c.role_id', 'P']
    //             ])
    //             ->orderBy('a.roll_no')
    //             ->orderBy('a.reg_no')
    //             ->select('a.*', 'd.name as class_name', 'e.name as section_name', 'f.house_name')
    //             ->get();

    //         $reportData = [];

    //         foreach ($students as $student) {
    //             $studentReport = [
    //                 'roll_no'   => $student->roll_no,
    //                 'reg_no'    => $student->reg_no,
    //                 'class_div' => $student->class_name . $student->section_name,
    //                 'name'      => trim("{$student->first_name} {$student->mid_name} {$student->last_name}"),
    //                 'subjects'  => []
    //             ];

    //             foreach ($subjects as $subject) {
    //                 $exams = DB::table('allot_mark_headings')
    //                     ->join('exam', 'allot_mark_headings.exam_id', '=', 'exam.exam_id')
    //                     ->where('allot_mark_headings.sm_id', $subject->sub_rc_master_id)
    //                     ->where('allot_mark_headings.class_id', $classId)
    //                     ->where('allot_mark_headings.academic_yr', $academicYear)
    //                     ->select('exam.exam_id', 'exam.name')
    //                     ->distinct()
    //                     ->orderBy('exam.start_date')
    //                     ->get();

    //                 $subjectData = [
    //                     'subject_name' => $subject->name,
    //                     'exams' => []
    //                 ];

    //                 foreach ($exams as $exam) {
    //                     $marksHeadings = DB::table('allot_mark_headings')
    //                         ->join('marks_headings', 'allot_mark_headings.marks_headings_id', '=', 'marks_headings.marks_headings_id')
    //                         ->where([
    //                             ['allot_mark_headings.exam_id', $exam->exam_id],
    //                             ['allot_mark_headings.class_id', $classId],
    //                             ['allot_mark_headings.sm_id', $subject->sub_rc_master_id],
    //                             ['allot_mark_headings.academic_yr', $academicYear],
    //                         ])
    //                         ->orderBy('marks_headings.sequence')
    //                         ->select('marks_headings.name', 'allot_mark_headings.highest_marks')
    //                         ->get();

    //                     $studentMarks = DB::table('student_marks')
    //                         ->where([
    //                             ['exam_id', $exam->exam_id],
    //                             ['subject_id', $subject->sub_rc_master_id],
    //                             ['student_id', $student->student_id],
    //                             ['academic_yr', $academicYear],
    //                             ['publish', 'Y']
    //                         ])
    //                         ->first();

    //                     $obtainedMarks = [];
    //                     $totalObtained = 0;

    //                     if ($studentMarks) {
    //                         $marksData = json_decode($studentMarks->reportcard_marks, true);
    //                         foreach ($marksHeadings as $heading) {
    //                             $mark = $marksData[$heading->name] ?? '';
    //                             $obtainedMarks[] = [
    //                                 'heading' => $heading->name,
    //                                 'obtained' => (float)$mark,
    //                                 'max' => $heading->highest_marks
    //                             ];
    //                             if (is_numeric($mark)) {
    //                                 $totalObtained += (float)$mark;
    //                             }
    //                         }
    //                     } else {
    //                         foreach ($marksHeadings as $heading) {
    //                             $obtainedMarks[] = [
    //                                 'heading' => $heading->name,
    //                                 'obtained' => '',
    //                                 'max' => $heading->highest_marks
    //                             ];
    //                         }
    //                     }

    //                     $subjectData['exams'][] = [
    //                         'exam_name' => $exam->name,
    //                         'marks'     => $obtainedMarks,
    //                         'total'     => count($marksHeadings) > 1 ? $totalObtained : null
    //                     ];
    //                 }

    //                 $studentReport['subjects'][] = $subjectData;
    //             }

    //             $reportData[] = $studentReport;
    //         }

    //         return response()->json([
    //             'status' => 200,
    //             'message' => 'Classwise report card marks generated successfully.',
    //             'data' => $reportData,
    //             'success' => true
    //         ]);
    //     }
    
    // public function getClasswiseReportCardMarksReport(Request $request){
        

    //         $user = $this->authenticateUser();
    //         $academicYr = JWTAuth::getPayload()->get('academic_year');
    //         $classId   = $request->input('class_id');
    //         $sectionId = $request->input('section_id');
    //         $academicYear = '2024-2025';
    //         $subjectId = $request->input('subject_id');
    //         $examination_id = $request->input('examination_id');
    //         $termId = $request->input('term_id');
        
    //     $classname = DB::table('class')->where('class_id',$classId)->where('academic_yr',$academicYear)->value('name');
        
    //     if($classname == '9' || $classname == '10'){
    //         // $subjectsRaw = DB::table('subjects_on_report_card as a')
    //         // ->join('subjects_on_report_card_master as m', 'a.sub_rc_master_id','=', 'm.sub_rc_master_id')
    //         // ->where('a.class_id', $classId)
    //         // ->where('a.academic_yr', $academicYear)
    //         // ->distinct()
    //         // ->select('a.sub_rc_master_id as subject_id', 'm.name as subject_name')
    //         // ->orderBy('m.sequence')
    //         // ->get();
    //         // dd($subjectsRaw);
    //         $query = DB::table('subjects_on_report_card as a')
    //                     ->join('subjects_on_report_card_master as m', 'a.sub_rc_master_id', '=', 'm.sub_rc_master_id')
    //                     ->where('a.class_id', $classId)
    //                     ->where('a.academic_yr', $academicYear);
                    
    //                 // Only apply subject ID filter if it's not empty
    //                 if (!empty($subjectId)) {
    //                     $query->where('a.sub_rc_master_id', $subjectId);
    //                 }
                    
    //                 $subjectsRaw = $query->distinct()
    //                     ->select('a.sub_rc_master_id as subject_id', 'm.name as subject_name')
    //                     ->orderBy('m.sequence')
    //                     ->get();

    //     $headings = [];
    //     foreach ($subjectsRaw as $subject) {
    //         // $exams = DB::table('allot_mark_headings as am')
    //         //     ->join('exam', 'am.exam_id','=', 'exam.exam_id')
    //         //     ->where('am.sm_id', $subject->subject_id)
    //         //     ->where('am.class_id', $classId)
    //         //     ->where('am.academic_yr', $academicYear)
    //         //     ->distinct()
    //         //     ->select('exam.exam_id', 'exam.name')
    //         //     ->orderBy('exam.start_date')
    //         //     ->get();
    //         $query = DB::table('allot_mark_headings as am')
    //                     ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
    //                     ->where('am.sm_id', $subject->subject_id)
    //                     ->where('am.class_id', $classId)
    //                     ->where('am.academic_yr', $academicYear);
                    
    //                 // Apply exam ID filter if provided
    //                 if (!empty($examination_id)) {
    //                     $query->where('am.exam_id', $examination_id);
    //                 }
                    
    //                 $exams = $query->distinct()
    //                     ->select('exam.exam_id', 'exam.name')
    //                     ->orderBy('exam.start_date')
    //                     ->get();

    //         $examArr = [];
    //         foreach ($exams as $exam) {
    //             $marksHeads = DB::table('allot_mark_headings as am2')
    //                 ->join('marks_headings as mh', 'am2.marks_headings_id','=', 'mh.marks_headings_id')
    //                 ->where('am2.exam_id', $exam->exam_id)
    //                 ->where('am2.sm_id', $subject->subject_id)
    //                 ->where('am2.class_id', $classId)
    //                 ->where('am2.academic_yr', $academicYear)
    //                 ->orderBy('mh.sequence')
    //                 ->select('mh.name as heading_name', 'am2.highest_marks')
    //                 ->get();

    //             $totalMax = $marksHeads->sum('highest_marks');
    //             $examArr[] = [
    //                 'exam_id'      => $exam->exam_id,
    //                 'exam_name'    => $exam->name,
    //                 'mark_headings'=> $marksHeads,
    //                 'show_total'   => $marksHeads->count() > 1,
    //                 'total_max'    => $marksHeads->count() > 1 ? $totalMax : null,
    //             ];
    //         }

    //         $headings[] = [
    //             'subject_id'   => $subject->subject_id,
    //             'subject_name' => $subject->subject_name,
    //             'exams'        => $examArr,
    //         ];
    //     }

    //     $students = DB::table('student as s')
    //         ->join('class as c','s.class_id','=', 'c.class_id')
    //         ->join('section as sec','s.section_id','=', 'sec.section_id')
    //         ->where('s.academic_yr', $academicYear)
    //         ->where('s.class_id', $classId)
    //         ->where('s.section_id', $sectionId)
    //         ->where('s.IsDelete','N')
    //         ->orderBy('s.roll_no')
    //         ->select('s.student_id','s.roll_no','s.reg_no',
    //                  DB::raw("CONCAT(s.first_name,' ',s.mid_name,' ',s.last_name) as name"),
    //                  DB::raw("CONCAT(c.name, sec.name) as class_div"))
    //         ->get();

    //     $data = [];
    //     foreach ($students as $st) {
    //         $marksNested = [];

    //         foreach ($headings as $sub) {
    //             $subId = $sub['subject_id'];
    //             $marksNested[$subId] = [];

    //             foreach ($sub['exams'] as $exam) {
    //                 $examId = $exam['exam_id'];

    //                 $row = DB::table('student_marks')
    //                     ->where([
    //                         ['exam_id',      $examId],
    //                         ['subject_id',   $subId],
    //                         ['student_id',   $st->student_id],
    //                         ['academic_yr',  $academicYear],
    //                         ['publish',      'Y'],
    //                     ])
    //                     ->first();

    //                 $rowMarks = $row ? json_decode($row->reportcard_marks, true) : [];

    //                 $examMarks = [];
    //                 foreach ($exam['mark_headings'] as $mh) {
    //                     $name = $mh->heading_name;
    //                     $examMarks[$name] = $rowMarks[$name] ?? '';
    //                 }

    //                 if ($exam['show_total']) {
    //                     $total = array_reduce($examMarks, fn($sum, $val) =>
    //                         is_numeric($val) ? $sum + $val : $sum, 0);
    //                     $examMarks['Total'] = $total;
    //                 }

    //                 $marksNested[$subId][$examId] = $examMarks;
    //             }
    //         }

    //         $data[] = [
    //             'roll_no'   => $st->roll_no,
    //             'reg_no'    => $st->reg_no,
    //             'class_div' => $st->class_div,
    //             'name'      => $st->name,
    //             'marks'     => $marksNested,
    //         ];
    //     }
            
    //     }
    //     elseif($classname == '11' || $classname == '12'){
    //         $query = DB::table('term');

    //         // Apply term filter only if $termId is provided
    //         if (!empty($termId)) {
    //             $query->where('term_id', $termId);
    //         }
            
    //         $terms = $query->get();

        
    //     // $subjects = DB::table('subject as a')
    //     //     ->join('sub_subreportcard_mapping as b', 'a.sm_id', '=', 'b.sm_id')
    //     //     ->join('subjects_on_report_card_master as c', 'b.sub_rc_master_id', '=', 'c.sub_rc_master_id')
    //     //     ->where([
    //     //         ['a.class_id', $classId],
    //     //         ['a.section_id', $sectionId],
    //     //         ['a.academic_yr', $academicYear]
    //     //     ])
    //     //     ->select('c.sub_rc_master_id as subject_id', 'c.name as subject_name')
    //     //     ->distinct()
    //     //     ->orderBy('c.sequence')
    //     //     ->get();
    //     $query = DB::table('subject as a')
    //                 ->join('sub_subreportcard_mapping as b', 'a.sm_id', '=', 'b.sm_id')
    //                 ->join('subjects_on_report_card_master as c', 'b.sub_rc_master_id', '=', 'c.sub_rc_master_id')
    //                 ->where([
    //                     ['a.class_id', $classId],
    //                     ['a.section_id', $sectionId],
    //                     ['a.academic_yr', $academicYear],
    //                 ]);
                
    //             // Apply subject ID filter only if provided
    //             if (!empty($subjectId)) {
    //                 $query->where('c.sub_rc_master_id', $subjectId);
    //             }
                
    //             $subjects = $query->select('c.sub_rc_master_id as subject_id', 'c.name as subject_name')
    //                 ->distinct()
    //                 ->orderBy('c.sequence')
    //                 ->get();

    //     // 3. Exams & headings per term + subject
    //     $structure = [];
    //     foreach ($terms as $term) {
    //         foreach ($subjects as $subject) {
    //             // $exams = DB::table('allot_mark_headings as am')
    //             //     ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
    //             //     ->where([
    //             //         ['am.sm_id', $subject->subject_id],
    //             //         ['am.class_id', $classId],
    //             //         ['am.academic_yr', $academicYear],
    //             //         ['exam.term_id', $term->term_id]
    //             //     ])
    //             //     ->select('exam.exam_id', 'exam.name as exam_name')
    //             //     ->distinct()
    //             //     ->orderBy('exam.start_date')
    //             //     ->get();
    //             $query = DB::table('allot_mark_headings as am')
    //                     ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
    //                     ->where([
    //                         ['am.sm_id', $subject->subject_id],
    //                         ['am.class_id', $classId],
    //                         ['am.academic_yr', $academicYear],
    //                         ['exam.term_id', $term->term_id]
    //                     ]);
                    
    //                 // Apply filter only if $examinationId is provided
    //                 if (!empty($examination_id)) {
    //                     $query->where('exam.exam_id', $examination_id);
    //                 }
                    
    //                 $exams = $query->select('exam.exam_id', 'exam.name as exam_name')
    //                     ->distinct()
    //                     ->orderBy('exam.start_date')
    //                     ->get();

    //             if ($exams->isEmpty()) continue;

    //             $exArr = [];
    //             $totalMax = 0;

    //             foreach ($exams as $exam) {
    //                 $heads = DB::table('allot_mark_headings as am2')
    //                     ->join('marks_headings as mh', 'am2.marks_headings_id', '=', 'mh.marks_headings_id')
    //                     ->where([
    //                         ['am2.exam_id', $exam->exam_id],
    //                         ['am2.sm_id', $subject->subject_id],
    //                         ['am2.class_id', $classId],
    //                         ['am2.academic_yr', $academicYear]
    //                     ])
    //                     ->select('mh.marks_headings_id', 'mh.name as heading_name', 'am2.highest_marks')
    //                     ->orderBy('mh.sequence')
    //                     ->get();

    //                 $maxSub = $heads->sum('highest_marks');
    //                 $totalMax += $maxSub;

    //                 $exArr[] = [
    //                     'exam_id'       => $exam->exam_id,
    //                     'exam_name'     => $exam->exam_name,
    //                     'headings'      => $heads,
    //                     'total_max'     => $maxSub,
    //                     'colspan'       => $heads->count()
    //                 ];
    //             }

    //             $structure[$term->term_id][$subject->subject_id] = [
    //                 'term_name'      => $term->name,
    //                 'subject_name'   => $subject->subject_name,
    //                 'exams'          => $exArr,
    //                 'total_max_all'  => $totalMax,
    //             ];
    //         }
    //     }

    //     // 4. Students
    //     $students = DB::table('student as s')
    //         ->join('student_marks as sm', 's.student_id', '=', 'sm.student_id')
    //         ->where([
    //             ['s.class_id', $classId],
    //             ['s.section_id', $sectionId],
    //             ['s.academic_yr', $academicYear],
    //             ['s.IsDelete', 'N']
    //         ])
    //         ->select('s.student_id', 's.roll_no', 's.reg_no',
    //                  DB::raw("CONCAT(s.first_name, ' ', s.mid_name, ' ', s.last_name) as name"),
    //                  DB::raw("CONCAT(s.class_id, '', s.section_id) as class_div"))
    //         ->distinct()
    //         ->orderBy('s.roll_no')
    //         ->get();

    //     // 5. Populate marks
    //     foreach ($students as &$student) {
    //         $student->marks = [];
    //         foreach ($structure as $termId => $subs) {
    //             foreach ($subs as $subId => $info) {
    //                 $subjectTotal = 0;
    //                 foreach ($info['exams'] as $exam) {
    //                     $row = DB::table('student_marks')
    //                         ->where([
    //                             ['student_id', $student->student_id],
    //                             ['exam_id', $exam['exam_id']],
    //                             ['subject_id', $subId],
    //                             ['academic_yr', $academicYear],
    //                             ['publish', 'Y']
    //                         ])
    //                         ->first();

    //                     $marksArr = $row ? json_decode($row->reportcard_marks, true) : [];
    //                     $totalObt = 0;
    //                     $cell = [];
    //                     foreach ($exam['headings'] as $head) {
    //                         $val = $marksArr[$head->heading_name] ?? '';
    //                         $cell[$head->heading_name] = ceil((float)$val);
    //                         if (is_numeric($val)) $totalObt += ceil((float)$val); $subjectTotal += ceil((float)$val); 
    //                     }
    //                     if ($exam['colspan'] > 1) {
    //                         $cell['Total'] = $subjectTotal;
    //                     }

    //                     $student->marks[$termId][$subId][$exam['exam_id']] = $cell;
    //                 }
    //             }
    //         }
    //     }
    //     unset($student);

    //     return response()->json([
    //         'status'=>200,
    //         'message'=>'Report card marks report.',
    //         'success'=>true,
    //         'headings' => $structure,
    //         'data'  => $students
    //     ]);
            
    //     }
    //     else{
    //         // dd("Hello");
    //         $query = DB::table('term');

    //         if (!empty($termId)) {
    //             $query->where('term_id', $termId);
    //         }
            
    //         $terms = $query->get();
    //         // $subjects = DB::select("select distinct a.sub_rc_master_id as sub_rc_master_id,b.name as name,a.subject_type from subjects_on_report_card as a join subjects_on_report_card_master as b on b.sub_rc_master_id=a.sub_rc_master_id where a.class_id = ".$classId." and a.academic_yr= '".$academicYear."' order by a.class_id asc,b.sequence asc");
    //         $sql = "SELECT DISTINCT a.sub_rc_master_id AS sub_rc_master_id, 
    //            b.name AS name, 
    //            a.subject_type 
    //             FROM subjects_on_report_card AS a 
    //             JOIN subjects_on_report_card_master AS b 
    //               ON b.sub_rc_master_id = a.sub_rc_master_id 
    //             WHERE a.class_id = ? 
    //               AND a.academic_yr = ?";
        
    //     $params = [$classId, $academicYear];
        
    //     // Add subject ID filter if provided
    //     if (!empty($subjectId)) {
    //         $sql .= " AND a.sub_rc_master_id = ?";
    //         $params[] = $subjectId;
    //     }
        
    //     $sql .= " ORDER BY a.class_id ASC, b.sequence ASC";
        
    //     $subjects = DB::select($sql, $params);
    //         // dd($subjects);
    //         // 3. Exams & headings per term + subject
    //         $structure = [];
    //         foreach ($terms as $term) {
    //             foreach ($subjects as $subject) {
    //                 // dd($subject);
    //                 // $exams = DB::table('allot_mark_headings as am')
    //                 //     ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
    //                 //     ->where([
    //                 //         ['am.sm_id', $subject->sub_rc_master_id],
    //                 //         ['am.class_id', $classId],
    //                 //         ['am.academic_yr', $academicYear],
    //                 //         ['exam.term_id', $term->term_id]
    //                 //     ])
    //                 //     ->select('exam.exam_id', 'exam.name as exam_name')
    //                 //     ->distinct()
    //                 //     ->orderBy('exam.start_date')
    //                 //     ->get();
                    
    //                 $query = DB::table('allot_mark_headings as am')
    //                     ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
    //                     ->where([
    //                         ['am.sm_id', $subject->sub_rc_master_id],
    //                         ['am.class_id', $classId],
    //                         ['am.academic_yr', $academicYear],
    //                         ['exam.term_id', $term->term_id]
    //                     ]);
                    
    //                 // Apply filter only if $examinationId is provided
    //                 if (!empty($examination_id)) {
    //                     $query->where('exam.exam_id', $examination_id);
    //                 }
                    
    //                 $exams = $query->select('exam.exam_id', 'exam.name as exam_name')
    //                     ->distinct()
    //                     ->orderBy('exam.start_date')
    //                     ->get();
    
    //                 if ($exams->isEmpty()) continue;
    
    //                 $exArr = [];
    //                 $totalMax = 0;
    
    //                 foreach ($exams as $exam) {
    //                     $heads = DB::table('allot_mark_headings as am2')
    //                         ->join('marks_headings as mh', 'am2.marks_headings_id', '=', 'mh.marks_headings_id')
    //                         ->where([
    //                             ['am2.exam_id', $exam->exam_id],
    //                             ['am2.sm_id', $subject->sub_rc_master_id],
    //                             ['am2.class_id', $classId],
    //                             ['am2.academic_yr', $academicYear]
    //                         ])
    //                         ->select('mh.marks_headings_id', 'mh.name as heading_name', 'am2.highest_marks')
    //                         ->orderBy('mh.sequence')
    //                         ->get();
    //                         // dd($heads);
    //                         $reportcardhighestmarks = DB::table('student_marks')
    //                                                      ->where('class_id',$classId)
    //                                                      ->where('section_id',$sectionId)
    //                                                      ->where('exam_id',$exam->exam_id)
    //                                                      ->where('subject_id',$subject->sub_rc_master_id)
    //                                                      ->value('reportcard_highest_marks');
    //                         $decodedMarks = json_decode($reportcardhighestmarks, true);
    //                         $maxSub = 0;
                            
    //                         foreach ($heads as $head) {
    //                             if (isset($decodedMarks[$head->heading_name])) {
    //                                 // Update the highest_marks with the value from JSON
    //                                 $head->highest_marks = (float) $decodedMarks[$head->heading_name];
    //                                 $maxSub += $head->highest_marks;
    //                             } else {
    //                                 // Optionally set to 0 if not found in JSON
    //                                 $head->highest_marks = 0;
    //                             }
    //                         }
    
    //                     $totalMax += $maxSub;
    
    //                     $exArr[] = [
    //                         'exam_id'       => $exam->exam_id,
    //                         'exam_name'     => $exam->exam_name,
    //                         'headings'      => $heads,
    //                         'total_max'     => $maxSub,
    //                         'colspan'       => $heads->count()
    //                     ];
                        
    //                 }
    //                 // dd($exArr);
    //                 $structure[$term->term_id][$subject->sub_rc_master_id] = [
    //                     'term_name'      => $term->name,
    //                     'subject_name'   => $subject->name,
    //                     'exams'          => $exArr,
    //                     'total_max_all'  => $totalMax,
    //                 ];
    //             }
    //         }
    
    //         // 4. Students
    //         $students = DB::select("select a.*,b.*,c.user_id,d.name as class_name,e.name as sec_name,f.house_name from student a left join parent b on a.parent_id=b.parent_id join user_master c on a.parent_id = c.reg_id join class d on a.class_id=d.class_id join section e on a.section_id=e.section_id left join house f on a.house=f.house_id where a.IsDelete='N' and a.academic_yr='".$academicYear."'  and a.class_id='".$classId."' and a.section_id='".$sectionId."' and c.role_id='P' order by a.roll_no,a.reg_no");
    //             // dd($students);
    
    //         // 5. Populate marks
    //         foreach ($students as &$student) {
    //             $student->marks = [];
    //             foreach ($structure as $termId => $subs) {
    //                 foreach ($subs as $subId => $info) {
    //                      $subjectTotal = 0;
    //                     foreach ($info['exams'] as $exam) {
    //                         $row = DB::table('student_marks')
    //                             ->where([
    //                                 ['student_id', $student->student_id],
    //                                 ['exam_id', $exam['exam_id']],
    //                                 ['subject_id', $subId],
    //                                 ['academic_yr', $academicYear],
    //                                 ['publish', 'Y']
    //                             ])
    //                             ->first();
    
    //                         $marksArr = $row ? json_decode($row->reportcard_marks, true) : [];
    //                         // dd($marksArr);
    //                         $totalObt = 0;
    //                         $cell = [];
    //                         foreach ($exam['headings'] as $head) {
    //                             $val = $marksArr[$head->heading_name] ?? '';
    //                             $cell[$head->heading_name] = ceil((float)$val);
    //                             if (is_numeric($val)) $totalObt += ceil((float)$val); $subjectTotal += ceil((float)$val); 
    //                         }
    //                         if ($exam['colspan'] > 1) {
    //                             $cell['Total'] = $subjectTotal;
    //                         }
    
    //                         $student->marks[$termId][$subId][$exam['exam_id']] = $cell;
    //                     }
    //                 }
    //             }
    //         }
    //         unset($student);
    
    //         return response()->json([
    //             'status'=>200,
    //             'message'=>'Report card marks report.',
    //             'success'=>true,
    //             'headings' => $structure,
    //             'data'  => $students
    //         ]);
            
    //     }
       

        

    //     // return response()->json([
    //     //     'status'   => 200,
    //     //     'message'  => 'Report card marks report.',
    //     //     'success'  => true,
    //     //     'headings' => $headings,
    //     //     'data'     => $data,
    //     // ]);
    //     }


        public function getClasswiseReportCardMarksReport(Request $request){
        

            $user = $this->authenticateUser();
            $academicYr = JWTAuth::getPayload()->get('academic_year');
            $classId   = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $academicYear = $academicYr;
            $subjectId = $request->input('subject_id');
            $examination_id = $request->input('examination_id');
            $termId = $request->input('term_id');
        
        $classname = DB::table('class')->where('class_id',$classId)->where('academic_yr',$academicYear)->value('name');
        
        if($classname == '9' || $classname == '10'){
            // $subjectsRaw = DB::table('subjects_on_report_card as a')
            // ->join('subjects_on_report_card_master as m', 'a.sub_rc_master_id','=', 'm.sub_rc_master_id')
            // ->where('a.class_id', $classId)
            // ->where('a.academic_yr', $academicYear)
            // ->distinct()
            // ->select('a.sub_rc_master_id as subject_id', 'm.name as subject_name')
            // ->orderBy('m.sequence')
            // ->get();
            // dd($subjectsRaw);
            $query = DB::table('subjects_on_report_card as a')
                        ->join('subjects_on_report_card_master as m', 'a.sub_rc_master_id', '=', 'm.sub_rc_master_id')
                        ->where('a.class_id', $classId)
                        ->where('a.academic_yr', $academicYear);
                    
                    // Only apply subject ID filter if it's not empty
                    if (!empty($subjectId)) {
                        $query->where('a.sub_rc_master_id', $subjectId);
                    }
                    
                    $subjectsRaw = $query->distinct()
                        ->select('a.sub_rc_master_id as subject_id', 'm.name as subject_name')
                        ->orderBy('m.sequence')
                        ->get();

        $headings = [];
        foreach ($subjectsRaw as $subject) {
            // $exams = DB::table('allot_mark_headings as am')
            //     ->join('exam', 'am.exam_id','=', 'exam.exam_id')
            //     ->where('am.sm_id', $subject->subject_id)
            //     ->where('am.class_id', $classId)
            //     ->where('am.academic_yr', $academicYear)
            //     ->distinct()
            //     ->select('exam.exam_id', 'exam.name')
            //     ->orderBy('exam.start_date')
            //     ->get();
            $query = DB::table('allot_mark_headings as am')
                        ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
                        ->where('am.sm_id', $subject->subject_id)
                        ->where('am.class_id', $classId)
                        ->where('am.academic_yr', $academicYear);
                    
                    // Apply exam ID filter if provided
                    if (!empty($examination_id)) {
                        $query->where('am.exam_id', $examination_id);
                    }
                    
                    $exams = $query->distinct()
                        ->select('exam.exam_id', 'exam.name')
                        ->orderBy('exam.start_date')
                        ->get();

            if ($exams->isEmpty()) continue;

                $exArr = [];
                $totalMax = 0;

                foreach ($exams as $exam) {
                    $heads = DB::table('allot_mark_headings as am2')
                        ->join('marks_headings as mh', 'am2.marks_headings_id', '=', 'mh.marks_headings_id')
                        ->where([
                            ['am2.exam_id', $exam->exam_id],
                            ['am2.sm_id', $subject->subject_id],
                            ['am2.class_id', $classId],
                            ['am2.academic_yr', $academicYear]
                        ])
                        ->select('mh.marks_headings_id', 'mh.name as heading_name', 'am2.highest_marks')
                        ->orderBy('mh.sequence')
                        ->get();

                    $maxSub = $heads->sum('highest_marks');
                    $totalMax += $maxSub;

                    $exArr[] = [
                        'exam_id'       => $exam->exam_id,
                        'exam_name'     => $exam->name,
                        'headings'      => $heads,
                        'total_max'     => $maxSub,
                        'colspan'       => $heads->count()
                    ];
                }

                $structure[$subject->subject_id] = [
                    'subject_name'   => $subject->subject_name,
                    'exams'          => $exArr,
                    'total_max_all'  => $totalMax,
                ];
             }

          
          $students = DB::table('student as s')
            ->join('student_marks as sm', 's.student_id', '=', 'sm.student_id')
            ->where([
                ['s.class_id', $classId],
                ['s.section_id', $sectionId],
                ['s.academic_yr', $academicYear],
                ['s.IsDelete', 'N']
            ])
            ->select('s.student_id', 's.roll_no', 's.reg_no',
                     DB::raw("CONCAT(s.first_name, ' ', s.mid_name, ' ', s.last_name) as name"),
                     DB::raw("CONCAT(s.class_id, '', s.section_id) as class_div"))
            ->distinct()
            ->orderBy('s.roll_no')
            ->get();

        // 5. Populate marks
        foreach ($students as &$student) {
            $student->marks = [];
                foreach ($structure as $subId => $info) {
                    $subjectTotal = 0;
                    foreach ($info['exams'] as $exam) {
                        $row = DB::table('student_marks')
                            ->where([
                                ['student_id', $student->student_id],
                                ['exam_id', $exam['exam_id']],
                                ['subject_id', $subId],
                                ['academic_yr', $academicYear],
                                ['publish', 'Y']
                            ])
                            ->first();

                        $marksArr = $row ? json_decode($row->reportcard_marks, true) : [];
                        $totalObt = 0;
                        $cell = [];
                        foreach ($exam['headings'] as $head) {
                            $val = $marksArr[$head->heading_name] ?? '';
                            $cell[$head->heading_name] = ceil((float)$val);
                            if (is_numeric($val)) $totalObt += ceil((float)$val); $subjectTotal += ceil((float)$val); 
                        }
                        if ($exam['colspan'] > 1) {
                            $cell['Total'] = $subjectTotal;
                        }

                        $student->marks[$subId][$exam['exam_id']] = $cell;
                    }
                }
        }
        unset($student);

        return response()->json([
            'status'=>200,
            'message'=>'Report card marks report.',
            'success'=>true,
            'headings' => $structure,
            'data'  => $students
        ]);
            
        }
        elseif($classname == '11' || $classname == '12'){
            $query = DB::table('term');

            // Apply term filter only if $termId is provided
            if (!empty($termId)) {
                $query->where('term_id', $termId);
            }
            
            $terms = $query->get();

        
        // $subjects = DB::table('subject as a')
        //     ->join('sub_subreportcard_mapping as b', 'a.sm_id', '=', 'b.sm_id')
        //     ->join('subjects_on_report_card_master as c', 'b.sub_rc_master_id', '=', 'c.sub_rc_master_id')
        //     ->where([
        //         ['a.class_id', $classId],
        //         ['a.section_id', $sectionId],
        //         ['a.academic_yr', $academicYear]
        //     ])
        //     ->select('c.sub_rc_master_id as subject_id', 'c.name as subject_name')
        //     ->distinct()
        //     ->orderBy('c.sequence')
        //     ->get();
        $query = DB::table('subject as a')
                    ->join('sub_subreportcard_mapping as b', 'a.sm_id', '=', 'b.sm_id')
                    ->join('subjects_on_report_card_master as c', 'b.sub_rc_master_id', '=', 'c.sub_rc_master_id')
                    ->where([
                        ['a.class_id', $classId],
                        ['a.section_id', $sectionId],
                        ['a.academic_yr', $academicYear],
                    ]);
                
                // Apply subject ID filter only if provided
                if (!empty($subjectId)) {
                    $query->where('c.sub_rc_master_id', $subjectId);
                }
                
                $subjects = $query->select('c.sub_rc_master_id as subject_id', 'c.name as subject_name')
                    ->distinct()
                    ->orderBy('c.sequence')
                    ->get();

        // 3. Exams & headings per term + subject
        $structure = [];
        foreach ($terms as $term) {
            foreach ($subjects as $subject) {
                // $exams = DB::table('allot_mark_headings as am')
                //     ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
                //     ->where([
                //         ['am.sm_id', $subject->subject_id],
                //         ['am.class_id', $classId],
                //         ['am.academic_yr', $academicYear],
                //         ['exam.term_id', $term->term_id]
                //     ])
                //     ->select('exam.exam_id', 'exam.name as exam_name')
                //     ->distinct()
                //     ->orderBy('exam.start_date')
                //     ->get();
                $query = DB::table('allot_mark_headings as am')
                        ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
                        ->where([
                            ['am.sm_id', $subject->subject_id],
                            ['am.class_id', $classId],
                            ['am.academic_yr', $academicYear],
                            ['exam.term_id', $term->term_id]
                        ]);
                    
                    // Apply filter only if $examinationId is provided
                    if (!empty($examination_id)) {
                        $query->where('exam.exam_id', $examination_id);
                    }
                    
                    $exams = $query->select('exam.exam_id', 'exam.name as exam_name')
                        ->distinct()
                        ->orderBy('exam.start_date')
                        ->get();

                if ($exams->isEmpty()) continue;

                $exArr = [];
                $totalMax = 0;

                foreach ($exams as $exam) {
                    $heads = DB::table('allot_mark_headings as am2')
                        ->join('marks_headings as mh', 'am2.marks_headings_id', '=', 'mh.marks_headings_id')
                        ->where([
                            ['am2.exam_id', $exam->exam_id],
                            ['am2.sm_id', $subject->subject_id],
                            ['am2.class_id', $classId],
                            ['am2.academic_yr', $academicYear]
                        ])
                        ->select('mh.marks_headings_id', 'mh.name as heading_name', 'am2.highest_marks')
                        ->orderBy('mh.sequence')
                        ->get();

                    $maxSub = $heads->sum('highest_marks');
                    $totalMax += $maxSub;

                    $exArr[] = [
                        'exam_id'       => $exam->exam_id,
                        'exam_name'     => $exam->exam_name,
                        'headings'      => $heads,
                        'total_max'     => $maxSub,
                        'colspan'       => $heads->count()
                    ];
                }

                $structure[$term->term_id][$subject->subject_id] = [
                    'term_name'      => $term->name,
                    'subject_name'   => $subject->subject_name,
                    'exams'          => $exArr,
                    'total_max_all'  => $totalMax,
                ];
            }
        }

        // 4. Students
        $students = DB::table('student as s')
            ->join('student_marks as sm', 's.student_id', '=', 'sm.student_id')
            ->where([
                ['s.class_id', $classId],
                ['s.section_id', $sectionId],
                ['s.academic_yr', $academicYear],
                ['s.IsDelete', 'N']
            ])
            ->select('s.student_id', 's.roll_no', 's.reg_no',
                     DB::raw("CONCAT(s.first_name, ' ', s.mid_name, ' ', s.last_name) as name"),
                     DB::raw("CONCAT(s.class_id, '', s.section_id) as class_div"))
            ->distinct()
            ->orderBy('s.roll_no')
            ->get();

        // 5. Populate marks
        foreach ($students as &$student) {
            $student->marks = [];
            foreach ($structure as $termId => $subs) {
                foreach ($subs as $subId => $info) {
                    $subjectTotal = 0;
                    foreach ($info['exams'] as $exam) {
                        $row = DB::table('student_marks')
                            ->where([
                                ['student_id', $student->student_id],
                                ['exam_id', $exam['exam_id']],
                                ['subject_id', $subId],
                                ['academic_yr', $academicYear],
                                ['publish', 'Y']
                            ])
                            ->first();

                        $marksArr = $row ? json_decode($row->reportcard_marks, true) : [];
                        $totalObt = 0;
                        $cell = [];
                        foreach ($exam['headings'] as $head) {
                            $val = $marksArr[$head->heading_name] ?? '';
                            $cell[$head->heading_name] = ceil((float)$val);
                            if (is_numeric($val)) $totalObt += ceil((float)$val); $subjectTotal += ceil((float)$val); 
                        }
                        if ($exam['colspan'] > 1) {
                            $cell['Total'] = $subjectTotal;
                        }

                        $student->marks[$termId][$subId][$exam['exam_id']] = $cell;
                    }
                }
            }
        }
        unset($student);

        return response()->json([
            'status'=>200,
            'message'=>'Report card marks report.',
            'success'=>true,
            'headings' => $structure,
            'data'  => $students
        ]);
            
        }
        else{
            // dd("Hello");
            $query = DB::table('term');

            if (!empty($termId)) {
                $query->where('term_id', $termId);
            }
            
            $terms = $query->get();
            // $subjects = DB::select("select distinct a.sub_rc_master_id as sub_rc_master_id,b.name as name,a.subject_type from subjects_on_report_card as a join subjects_on_report_card_master as b on b.sub_rc_master_id=a.sub_rc_master_id where a.class_id = ".$classId." and a.academic_yr= '".$academicYear."' order by a.class_id asc,b.sequence asc");
            $sql = "SELECT DISTINCT a.sub_rc_master_id AS sub_rc_master_id, 
               b.name AS name, 
               a.subject_type 
                FROM subjects_on_report_card AS a 
                JOIN subjects_on_report_card_master AS b 
                  ON b.sub_rc_master_id = a.sub_rc_master_id 
                WHERE a.class_id = ? 
                  AND a.academic_yr = ?";
        
        $params = [$classId, $academicYear];
        
        // Add subject ID filter if provided
        if (!empty($subjectId)) {
            $sql .= " AND a.sub_rc_master_id = ?";
            $params[] = $subjectId;
        }
        
        $sql .= " ORDER BY a.class_id ASC, b.sequence ASC";
        
        $subjects = DB::select($sql, $params);
            // dd($subjects);
            // 3. Exams & headings per term + subject
            $structure = [];
            foreach ($terms as $term) {
                foreach ($subjects as $subject) {
                    // dd($subject);
                    // $exams = DB::table('allot_mark_headings as am')
                    //     ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
                    //     ->where([
                    //         ['am.sm_id', $subject->sub_rc_master_id],
                    //         ['am.class_id', $classId],
                    //         ['am.academic_yr', $academicYear],
                    //         ['exam.term_id', $term->term_id]
                    //     ])
                    //     ->select('exam.exam_id', 'exam.name as exam_name')
                    //     ->distinct()
                    //     ->orderBy('exam.start_date')
                    //     ->get();
                    
                    $query = DB::table('allot_mark_headings as am')
                        ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
                        ->where([
                            ['am.sm_id', $subject->sub_rc_master_id],
                            ['am.class_id', $classId],
                            ['am.academic_yr', $academicYear],
                            ['exam.term_id', $term->term_id]
                        ]);
                    
                    // Apply filter only if $examinationId is provided
                    if (!empty($examination_id)) {
                        $query->where('exam.exam_id', $examination_id);
                    }
                    
                    $exams = $query->select('exam.exam_id', 'exam.name as exam_name')
                        ->distinct()
                        ->orderBy('exam.start_date')
                        ->get();
    
                    if ($exams->isEmpty()) continue;
    
                    $exArr = [];
                    $totalMax = 0;
    
                    foreach ($exams as $exam) {
                        $heads = DB::table('allot_mark_headings as am2')
                            ->join('marks_headings as mh', 'am2.marks_headings_id', '=', 'mh.marks_headings_id')
                            ->where([
                                ['am2.exam_id', $exam->exam_id],
                                ['am2.sm_id', $subject->sub_rc_master_id],
                                ['am2.class_id', $classId],
                                ['am2.academic_yr', $academicYear]
                            ])
                            ->select('mh.marks_headings_id', 'mh.name as heading_name', 'am2.highest_marks')
                            ->orderBy('mh.sequence')
                            ->get();
                            //  dd($heads);
                            $reportcardhighestmarks = DB::table('student_marks')
                                                         ->where('class_id',$classId)
                                                         ->where('section_id',$sectionId)
                                                         ->where('exam_id',$exam->exam_id)
                                                         ->where('subject_id',$subject->sub_rc_master_id)
                                                         ->value('reportcard_highest_marks');
                            $decodedMarks = json_decode($reportcardhighestmarks, true);
                            $maxSub = 0;
                            
                            foreach ($heads as $head) {
                                if (isset($decodedMarks[$head->heading_name])) {
                                    // Update the highest_marks with the value from JSON
                                    $head->highest_marks = (float) $decodedMarks[$head->heading_name];
                                    $maxSub += $head->highest_marks;
                                } else {
                                    // Optionally set to 0 if not found in JSON
                                    $head->highest_marks = 0;
                                }
                            }
    
                        $totalMax += $maxSub;
    
                        $exArr[] = [
                            'exam_id'       => $exam->exam_id,
                            'exam_name'     => $exam->exam_name,
                            'headings'      => $heads,
                            'total_max'     => $maxSub,
                            'colspan'       => $heads->count()
                        ];
                        
                    }
                    //  dd($exArr);
                    $structure[$term->term_id][$subject->sub_rc_master_id] = [
                        'term_name'      => $term->name,
                        'subject_name'   => $subject->name,
                        'exams'          => $exArr,
                        'total_max_all'  => $totalMax,
                    ];
                }
            }
    
            // 4. Students
            $students = DB::select("select a.*,b.*,c.user_id,d.name as class_name,e.name as sec_name,f.house_name from student a left join parent b on a.parent_id=b.parent_id join user_master c on a.parent_id = c.reg_id join class d on a.class_id=d.class_id join section e on a.section_id=e.section_id left join house f on a.house=f.house_id where a.IsDelete='N' and a.academic_yr='".$academicYear."'  and a.class_id='".$classId."' and a.section_id='".$sectionId."' and c.role_id='P' order by a.roll_no,a.reg_no");
                // dd($students);
    
            // 5. Populate marks
            foreach ($students as &$student) {
                $student->marks = [];
                foreach ($structure as $termId => $subs) {
                    foreach ($subs as $subId => $info) {
                         $subjectTotal = 0;
                        foreach ($info['exams'] as $exam) {
                            $row = DB::table('student_marks')
                                ->where([
                                    ['student_id', $student->student_id],
                                    ['exam_id', $exam['exam_id']],
                                    ['subject_id', $subId],
                                    ['academic_yr', $academicYear],
                                    ['publish', 'Y']
                                ])
                                ->first();
    
                            $marksArr = $row ? json_decode($row->reportcard_marks, true) : [];
                            // dd($marksArr);
                            $totalObt = 0;
                            $cell = [];
                            foreach ($exam['headings'] as $head) {
                                $headingId = $head->heading_name;
                                if (isset($marksArr[$headingId]) && is_numeric($marksArr[$headingId])) {
                                    $val = ceil((float)$marksArr[$headingId]);
                                    $cell[$headingId] = $val;
                                    $totalObt += $val;
                                    $subjectTotal += $val;
                                } else {
                                    
                                } 
                            }
                            if ($exam['colspan'] > 1) {
                                $cell['Total'] = $subjectTotal;
                            }
    
                            $student->marks[$termId][$subId][$exam['exam_id']] = $cell;
                        }
                    }
                }
            }
            unset($student);
    
            return response()->json([
                'status'=>200,
                'message'=>'Report card marks report.',
                'success'=>true,
                'headings' => $structure,
                'data'  => $students
            ]);
            
        }
       

        

        // return response()->json([
        //     'status'   => 200,
        //     'message'  => 'Report card marks report.',
        //     'success'  => true,
        //     'headings' => $headings,
        //     'data'     => $data,
        // ]);
        }
        
        public function getClasswiseMarksReport(Request $request){
            $user = $this->authenticateUser();
            $academicYr = JWTAuth::getPayload()->get('academic_year');
            $classId   = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $examination_id = $request->input('examination_id'); 
            $subjectId = $request->input('subject_id');
            $academicYear = '2024-2025';
            $subjectsQuery  = DB::table('subjects_on_report_card as a')
                ->join('subjects_on_report_card_master as m', 'a.sub_rc_master_id','=', 'm.sub_rc_master_id')
                ->where('a.class_id', $classId)
                ->where('a.academic_yr', $academicYear)
                ->distinct()
                ->select('a.sub_rc_master_id as subject_id', 'm.name as subject_name')
                ->orderBy('m.sequence');
            if (!empty($subjectId)) {
                $subjectsQuery->where('a.sub_rc_master_id', $subjectId);
            }
            
            $subjectsRaw = $subjectsQuery->get();
    
            $headings = [];
            foreach ($subjectsRaw as $subject) {
                $examQuery = "
                    SELECT DISTINCT exam.exam_id, exam.name 
                    FROM allot_mark_headings 
                    JOIN exam ON allot_mark_headings.exam_id = exam.exam_id 
                    WHERE allot_mark_headings.sm_id = :sm_id
                      AND allot_mark_headings.class_id = :class_id
                      AND allot_mark_headings.academic_yr = :academic_yr
                ";
            
                $params = [
                    'sm_id' => $subject->subject_id,
                    'class_id' => $classId,
                    'academic_yr' => $academicYear,
                ];
            
                if (!empty($examination_id)) {
                    $examQuery .= " AND exam.exam_id = :exam_id";
                    $params['exam_id'] = $examination_id;
                }
            
                $examQuery .= " ORDER BY exam.start_date";
            
                $exams = DB::select($examQuery, $params);
                // dd($exams);
    
                $examArr = [];
                foreach ($exams as $exam) {
                    $marksHeads = DB::table('allot_mark_headings as am2')
                        ->join('marks_headings as mh', 'am2.marks_headings_id','=', 'mh.marks_headings_id')
                        ->where('am2.exam_id', $exam->exam_id)
                        ->where('am2.sm_id', $subject->subject_id)
                        ->where('am2.class_id', $classId)
                        ->where('am2.academic_yr', $academicYear)
                        ->orderBy('mh.sequence')
                        ->select('mh.name as heading_name', 'am2.highest_marks','am2.marks_headings_id')
                        ->get();
                        // dd($marksHeads);
    
                    $totalMax = $marksHeads->sum('highest_marks');
                    $examArr[] = [
                        'exam_id'      => $exam->exam_id,
                        'exam_name'    => $exam->name,
                        'mark_headings'=> $marksHeads,
                    ];
                }
    
                $headings[] = [
                    'subject_id'   => $subject->subject_id,
                    'subject_name' => $subject->subject_name,
                    'exams'        => $examArr,
                ];
            }
    
            $students = DB::select("select distinct(a.student_id),a.class_id, a.section_id, b.first_name,b.mid_name,b.last_name,b.roll_no,b.reg_no from student_marks a, student b where a.class_id=".$classId." and a.section_id=".$sectionId." and a.academic_yr='".$academicYear."' and a.student_id=b.student_id order by b.roll_no,b.reg_no");
            
    
            $data = [];
            foreach ($students as $st) {
                $marksNested = [];
    
                foreach ($headings as $sub) {
                    $subId = $sub['subject_id'];
                    $marksNested[$subId] = [];
    
                    foreach ($sub['exams'] as $exam) {
                        $examId = $exam['exam_id'];
    
                        $row = DB::table('student_marks')
                            ->where([
                                ['exam_id',      $examId],
                                ['subject_id',   $subId],
                                ['student_id',   $st->student_id],
                                ['academic_yr',  $academicYear],
                                ['publish',      'Y'],
                            ])
                            ->first();
    
                        $rowMarks = $row ? json_decode($row->mark_obtained, true) : [];
                        // dd($rowMarks);
    
                        $examMarks = [];
                        // dd($exam);
                        foreach ($exam['mark_headings'] as $mh) {
                            // dd($mh);
                            $name = $mh->marks_headings_id;
                            $examMarks[$name] = $rowMarks[$name] ?? '';
                        }
    
                        
    
                        $marksNested[$subId][$examId] = $examMarks;
                    }
                }
                // dd($marksNested);
                $data[] = [
                    'roll_no'   => $st->roll_no,
                    'reg_no'    => $st->reg_no,
                    'class_div' => get_class_section_of_student($st->student_id),
                    'name'      => get_student_name($st->student_id),
                    'marks'     => $marksNested,
                ];
            }
            return response()->json([
            'status'   => 200,
            'message'  => 'Classwise marks report.',
            'success'  => true,
            'headings' => $headings,
            'data'     => $data,
        ]);
    
            
        }
        
        public function getClasswiseMarksReportchanges(Request $request){
            $user = $this->authenticateUser();
            $academicYr = JWTAuth::getPayload()->get('academic_year');
            $classId   = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $examination_id = $request->input('examination_id'); 
            $subjectId = $request->input('subject_id');
            $academicYear = $academicYr;
            $termId = $request->input('term_id');
            $query = DB::table('term');

            if (!empty($termId)) {
                $query->where('term_id', $termId);
            }
            
            $terms = $query->get();
            // $subjects = DB::select("select distinct a.sub_rc_master_id as sub_rc_master_id,b.name as name,a.subject_type from subjects_on_report_card as a join subjects_on_report_card_master as b on b.sub_rc_master_id=a.sub_rc_master_id where a.class_id = ".$classId." and a.academic_yr= '".$academicYear."' order by a.class_id asc,b.sequence asc");
            $sql = "SELECT DISTINCT a.sub_rc_master_id AS sub_rc_master_id, 
               b.name AS name, 
               a.subject_type 
                FROM subjects_on_report_card AS a 
                JOIN subjects_on_report_card_master AS b 
                  ON b.sub_rc_master_id = a.sub_rc_master_id 
                WHERE a.class_id = ? 
                  AND a.academic_yr = ?";
        
        $params = [$classId, $academicYear];
        
        // Add subject ID filter if provided
        if (!empty($subjectId)) {
            $sql .= " AND a.sub_rc_master_id = ?";
            $params[] = $subjectId;
        }
        
        $sql .= " ORDER BY a.class_id ASC, b.sequence ASC";
        
        $subjects = DB::select($sql, $params);
            // dd($subjects);
            // 3. Exams & headings per term + subject
            $structure = [];
            foreach ($terms as $term) {
                foreach ($subjects as $subject) {
                    // dd($subject);
                    // $exams = DB::table('allot_mark_headings as am')
                    //     ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
                    //     ->where([
                    //         ['am.sm_id', $subject->sub_rc_master_id],
                    //         ['am.class_id', $classId],
                    //         ['am.academic_yr', $academicYear],
                    //         ['exam.term_id', $term->term_id]
                    //     ])
                    //     ->select('exam.exam_id', 'exam.name as exam_name')
                    //     ->distinct()
                    //     ->orderBy('exam.start_date')
                    //     ->get();
                    
                    $query = DB::table('allot_mark_headings as am')
                        ->join('exam', 'am.exam_id', '=', 'exam.exam_id')
                        ->where([
                            ['am.sm_id', $subject->sub_rc_master_id],
                            ['am.class_id', $classId],
                            ['am.academic_yr', $academicYear],
                            ['exam.term_id', $term->term_id]
                        ]);
                    
                    // Apply filter only if $examinationId is provided
                    if (!empty($examination_id)) {
                        $query->where('exam.exam_id', $examination_id);
                    }
                    
                    $exams = $query->select('exam.exam_id', 'exam.name as exam_name')
                        ->distinct()
                        ->orderBy('exam.start_date')
                        ->get();
    
                    if ($exams->isEmpty()) continue;
    
                    $exArr = [];
                    $totalMax = 0;
    
                    foreach ($exams as $exam) {
                        $heads = DB::table('allot_mark_headings as am2')
                            ->join('marks_headings as mh', 'am2.marks_headings_id', '=', 'mh.marks_headings_id')
                            ->where([
                                ['am2.exam_id', $exam->exam_id],
                                ['am2.sm_id', $subject->sub_rc_master_id],
                                ['am2.class_id', $classId],
                                ['am2.academic_yr', $academicYear]
                            ])
                            ->select('mh.marks_headings_id', 'mh.name as heading_name', 'am2.highest_marks')
                            ->orderBy('mh.sequence')
                            ->get();
                            // dd($heads);
    
                        $maxSub = $heads->sum('highest_marks');
                        $totalMax += $maxSub;
    
                        $exArr[] = [
                            'exam_id'       => $exam->exam_id,
                            'exam_name'     => $exam->exam_name,
                            'headings'      => $heads,
                            'total_max'     => $maxSub,
                            'colspan'       => $heads->count()
                        ];
                    }
    
                    $structure[$term->term_id][$subject->sub_rc_master_id] = [
                        'term_name'      => $term->name,
                        'subject_name'   => $subject->name,
                        'exams'          => $exArr,
                        'total_max_all'  => $totalMax,
                    ];
                }
            }
    
            // 4. Students
            $students = DB::select("select a.*,b.*,c.user_id,d.name as class_name,e.name as sec_name,f.house_name from student a left join parent b on a.parent_id=b.parent_id join user_master c on a.parent_id = c.reg_id join class d on a.class_id=d.class_id join section e on a.section_id=e.section_id left join house f on a.house=f.house_id where a.IsDelete='N' and a.academic_yr='".$academicYear."'  and a.class_id='".$classId."' and a.section_id='".$sectionId."' and c.role_id='P' order by a.roll_no,a.reg_no");
                // dd($students);
    
            // 5. Populate marks
            foreach ($students as &$student) {
                $student->marks = [];
                foreach ($structure as $termId => $subs) {
                    foreach ($subs as $subId => $info) {
                         $subjectTotal = 0;
                        foreach ($info['exams'] as $exam) {
                            $row = DB::table('student_marks')
                                ->where([
                                    ['student_id', $student->student_id],
                                    ['exam_id', $exam['exam_id']],
                                    ['subject_id', $subId],
                                    ['academic_yr', $academicYear],
                                    ['publish', 'Y']
                                ])
                                ->first();
                                // dd($row);
    
                            $marksArr = $row ? json_decode($row->mark_obtained, true) : [];
                            //  dd($marksArr);
                            // echo $marksArr;
                            
                            
                            $totalObt = 0;
                            $cell = [];
                            
                            foreach ($exam['headings'] as $head) {
                                $headingId = $head->marks_headings_id;
                            
                                // Check if the mark exists and is numeric
                                if (isset($marksArr[$headingId]) && is_numeric($marksArr[$headingId])) {
                                    $val = ceil((float)$marksArr[$headingId]);
                                    $cell[$headingId] = $val;
                                    $totalObt += $val;
                                    $subjectTotal += $val;
                                } else {
                                    
                                }
                            }
                            
    
                            $student->marks[$termId][$subId][$exam['exam_id']] = $cell;
                        }
                    }
                }
            }
            unset($student);
    
            return response()->json([
                'status'=>200,
                'message'=>'Report card marks report.',
                'success'=>true,
                'headings' => $structure,
                'data'  => $students
            ]);
            
        }
            

        public function getIciciFeePaymentReport(Request $request)
            {
                try {
                    $user = $this->authenticateUser(); // Assume you have this
                    $academicYear = JWTAuth::getPayload()->get('academic_year');
        
                    if (in_array($user->role_id, ['A', 'M', 'F', 'U'])) {
        
                        $account_type = $request->input('account_type');
                        $from_date = $request->input('fromdate');
                        $to_date = $request->input('todate');
                        $student_id = $request->input('student_id');
                        $order_id = $request->input('order_id');
        
                        // Get parent_id based on student_id
                        $parent_id = null;
                        if (!empty($student_id)) {
                            $parent = DB::table('student')
                                ->where('student_id', $student_id)
                                ->select('parent_id')
                                ->first();
        
                            if ($parent) {
                                $parent_id = $parent->parent_id;
                            }
                        }
                        // dd($parent_id);
                        // dd($from_date,$to_date);
                        $query = DB::table('icicipg_payment_details as ip')
                            ->join('onlinefees_payment_record as o', DB::raw('SUBSTRING_INDEX(o.cheque_no, "/", 1)'), '=', 'ip.OrderId')
                            ->select('ip.*', DB::raw('GROUP_CONCAT(o.receipt_no) as receipt_no'))
                            ->where('ip.Status_code', '=', 'S');
        
                        if (!empty($account_type)) {
                            $query->where('ip.Account_type', 'like', $account_type . '%');
                        }
        
                        if (!empty($from_date)) {
                            $query->whereDate('ip.Trnx_date', '>=', $from_date);
                        }
        
                        if (!empty($to_date)) {
                            $query->whereDate('ip.Trnx_date', '<=', $to_date);
                        }
        
                        if (!empty($parent_id)) {
                            $query->where('ip.reg_id', '=', $parent_id);
                        }
        
                        if (!empty($order_id)) {
                            $query->where('ip.OrderId', '=', $order_id);
                        }
        
                        $query->groupBy('ip.OrderId');
                        $result = $query->get();
        
                        return response()->json([
                            'status' => 200,
                            'data' => $result,
                            'message' => 'ICICI Fee Payment Report fetched successfully',
                            'success' => true
                        ]);
                    } else {
                        return response()->json([
                            'status' => 401,
                            'message' => 'Unauthorized Access',
                            'success' => false
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error($e);
                    return response()->json([
                        'status' => 500,
                        'error' => 'An error occurred: ' . $e->getMessage(),
                        'success' => false
                    ]);
                }
            }
        
    
    
}






