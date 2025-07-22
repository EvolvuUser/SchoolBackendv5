<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use Illuminate\Support\Facades\Validator;
use DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Carbon;
use DateTime;
use Illuminate\Support\Facades\App;
use App\Http\Services\WhatsAppService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use App\Jobs\SendOutstandingFeeSmsJob;

class NewController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }
    public function getCaretakerList(){
        $caretakerlist = Teacher::where('designation', '=', 'Caretaker')
        ->get();
        return response()->json([
                'status'=> 200,
                'message'=>'Caretaker List',
                'data' =>$caretakerlist,
                'success'=>true
              ]);
    }

    public function storeCaretaker(Request $request){
            
            try{
            $validator = Validator::make($request->all(),[
                'employee_id' => 'required|string|unique:teacher,employee_id',
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'status' => 422,
                            'errors' => $validator->errors(),
                        ], 422);
                }
            $caretaker = new Teacher();
            $caretaker->name=$request->name;
            $caretaker->birthday=$request->birthday;
            $caretaker->date_of_joining=$request->date_of_joining;
            $caretaker->academic_qual=$request->academic_qual;
            $caretaker->aadhar_card_no=$request->aadhar_card_no;
            $caretaker->sex=$request->sex;
            $caretaker->address=$request->address;
            $caretaker->phone =$request->phone;
            $caretaker->employee_id=$request->employee_id;
            $caretaker->designation='Caretaker';
            $caretaker->blood_group = $request->blood_group;
            $caretaker->religion = $request->religion;
            $caretaker->father_spouse_name = 'NULL';
            $caretaker->professional_qual = 'NULL';
            $caretaker->special_sub = 'NULL';
            $caretaker->trained = 'NULL';
            $caretaker->experience = '0';
            $caretaker->teacher_image_name = 'NULL';
            $caretaker->tc_id =$request->teacher_id ;
            $caretaker->save();

            return response()->json([
                'status'=> 201,
                'message'=>'Caretaker Added successfully.',
                'data' =>$caretaker,
                'success'=>true
            ], 201); // 201 Created
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
    }

    public function editCaretaker($id){
            try{
            $caretaker = Teacher::where('designation', '=', 'Caretaker')
            ->where('teacher_id',$id)
            ->get();

            return response()->json([
                'status'=> 200,
                'message'=>'Caretaker edit successfully',
                'data' =>$caretaker,
                'success'=>true
            ], 200);

            }
            catch (\Exception $e) {
                return response()->json([
                    'message' => 'An error occurred while fetching the teacher details',
                    'error' => $e->getMessage()
                ], 500);
            }

     }

    public function updateCaretaker(Request $request,$id){
            $caretaker = Teacher::find($id);
            try{
            $validator = Validator::make($request->all(),[
                'employee_id' => 'required|string|unique:teacher,employee_id,' . $id . ' ,teacher_id',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'status' => 422,
                        'errors' => $validator->errors(),
                    ], 422);
                }
            $caretaker->name=$request->name;
            $caretaker->birthday=$request->birthday;
            $caretaker->date_of_joining=$request->date_of_joining;
            $caretaker->academic_qual=$request->academic_qual;
            $caretaker->employee_id=$request->employee_id;
            $caretaker->aadhar_card_no=$request->aadhar_card_no;
            $caretaker->sex=$request->sex;
            $caretaker->address=$request->address;
            $caretaker->phone =$request->phone;
            $caretaker->designation='Caretaker';
            $caretaker->blood_group = $request->blood_group;
            $caretaker->religion = $request->religion;
            $caretaker->father_spouse_name = 'NULL';
            $caretaker->professional_qual = 'NULL';
            $caretaker->special_sub = 'NULL';
            $caretaker->trained = 'NULL';
            $caretaker->experience = '0';
            $caretaker->teacher_image_name = 'NULL';
            $caretaker->tc_id =$request->teacher_id ;
            $caretaker->update();

            return response()->json([
                'status'=> 200,
                'message'=>'Caretaker updated successfully',
                'data' =>$caretaker,
                'success'=>true
            ], 201); // 201 Created
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
     }

    public function deleteCaretaker($id){
            try{
            $caretaker = Teacher::find($id);
            $caretaker->isDelete = 'Y';
            $caretaker->save();

            return response()->json([
                'status'=> 200,
                'message' => 'Caretaker deleted successfully!',
                'data' =>$caretaker,
                'success'=>true
            ]); 
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

     }
     public function getTeacherCategory(){
        try{
            $teacherCategory = DB::table('teacher_category')->get();
            return response()->json([
                'status'=> 200,
                'message'=>'Teacher Category List',
                'data' =>$teacherCategory,
                'success'=>true
              ]);
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

     }
     //API for the Leave Application for all staff Dev Name- Manish Kumar Sharma 06-06-2025
     public function saveLeaveApplicationForallstaff(Request $request){
        try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                    $leavetype = DB::table('leave_type_master')
                              ->join('leave_allocation','leave_type_master.leave_type_id','=','leave_allocation.leave_type_id')
                              ->where('leave_allocation.staff_id',$request->staff_id)
                              ->where('leave_allocation.academic_yr',$customClaims)
                              ->where('leave_allocation.leave_type_id',$request->leave_type_id)
                              ->first();
                    $balanceleave = $leavetype->leaves_allocated - $leavetype->leaves_availed;
                    if($balanceleave < $request->no_of_days){
                        return response()->json([
                            'status'=>400,
                            'message' => 'You have applied for leave more than the balance leaves',
                            'success'=>false
                        ]);
                    
                    }
                     $leaveapplication = DB::table('leave_application')->insert([
                        'staff_id'=>$request->staff_id,
                        'leave_type_id'=>$request->leave_type_id,
                        'leave_start_date'=>$request->leave_start_date,
                        'leave_end_date'=>$request->leave_end_date,
                        'no_of_days'=>$request->no_of_days,
                        'approved_by'=>$user->reg_id,
                        'reason' =>$request->reason,
                        'status'=>'P',
                        'reason_for_rejection'=>$request->approverscomment,
                        'academic_yr'=>$customClaims
                    ]);

                    DB::table('leave_allocation')
                        ->where('staff_id', $request->staff_id)
                        ->where('leave_type_id', $request->leave_type_id)
                        ->where('academic_yr', $customClaims)
                        ->increment('leaves_availed', floatval($request->no_of_days));
                    return response()->json([
                        'status'=> 200,
                        'message'=>'New leave application is created!!!',
                        'data' =>$leaveapplication,
                        'success'=>true
                    ]);                                         

                }
                else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
     }
     //API for the Leave Application for all staff Dev Name- Manish Kumar Sharma 06-06-2025
     public function getLeaveApplicationData(Request $request){
         try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                    $leaveapplicationdata = DB::table('leave_application')
                                                 ->join('teacher','teacher.teacher_id','=','leave_application.staff_id')
                                                 ->join('leave_type_master','leave_type_master.leave_type_id','=','leave_application.leave_type_id')
                                                 ->select('leave_application.*','teacher.name as teachername','leave_type_master.name as leavetypename')
                                                 ->where('leave_application.academic_yr',$customClaims)
                                                 ->orderBy('leave_app_id','desc')
                                                 ->get();
                                                 return response()->json([
                                                    'status'=> 200,
                                                    'message'=>'leave application list!!!',
                                                    'data' =>$leaveapplicationdata,
                                                    'success'=>true
                                                ]);    


                }
                else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

     }
     //API for the Leave Application for all staff Dev Name- Manish Kumar Sharma 06-06-2025
     public function deleteLeaveApplicationPrincipal(Request $request,$id){
        try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                    $leaveApplication = DB::table('leave_application')->where('leave_app_id', $id)->first();

                    if (!$leaveApplication) {
                        return response()->json(['message' => 'Leave application not found.'], 404);
                    }

                    // Store required values before delete
                    $staffId = $leaveApplication->staff_id;
                    $leaveTypeId = $leaveApplication->leave_type_id;
                    $noOfDays = floatval($leaveApplication->no_of_days);
                    $status = $leaveApplication->status;
                    if($status == 'P'){
                        DB::table('leave_allocation')
                        ->where('staff_id', $staffId)
                        ->where('leave_type_id', $leaveTypeId)
                        ->where('academic_yr', $customClaims)
                        ->decrement('leaves_availed', $noOfDays);

                    }

                    // Delete the leave application
                    DB::table('leave_application')->where('leave_app_id', $id)->delete();

                    

                    return response()->json([
                        'status' =>200,
                        'message' => 'Leave application deleted successfully.',
                        'success' =>true
                    
                    ]);

                }
                else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

     }
     //API for the Leave Application for all staff Dev Name- Manish Kumar Sharma 06-06-2025
     public function updateLeaveApplicationCancel(Request $request,$id){
        try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                    $leavetype = DB::table('leave_type_master')
                              ->join('leave_allocation','leave_type_master.leave_type_id','=','leave_allocation.leave_type_id')
                              ->where('leave_allocation.staff_id',$request->staff_id)
                              ->where('leave_allocation.academic_yr',$customClaims)
                              ->where('leave_allocation.leave_type_id',$request->leave_type_id)
                              ->first();
                    $balanceleave = $leavetype->leaves_allocated - $leavetype->leaves_availed;
                    if($balanceleave < $request->no_of_days){
                        return response()->json([
                            'status'=>400,
                            'message' => 'You have applied for leave more than the balance leaves',
                            'success'=>false
                        ]);
                    
                    }
                     $leaveApplication = DB::table('leave_application')->where('leave_app_id', $id)->first();

                    if (!$leaveApplication) {
                        return response()->json(['message' => 'Leave application not found.'], 404);
                    }

                    $staffId = $leaveApplication->staff_id;
                    $leaveTypeId = $leaveApplication->leave_type_id;
                    $noOfDays = floatval($leaveApplication->no_of_days);

                    // 1. Update status to 'C'
                    DB::table('leave_application')
                        ->where('leave_app_id', $id)
                        ->update(['status' => 'C']);

                    // 2. Decrement leaves_availed
                    DB::table('leave_allocation')
                        ->where('staff_id', $staffId)
                        ->where('leave_type_id', $leaveTypeId)
                        ->where('academic_yr', $customClaims)
                        ->decrement('leaves_availed', $noOfDays);

                        return response()->json([
                        'status' =>200,
                        'message' => 'Leave application cancelled successfully.',
                        'success' =>true
                        ]);

                 }
                else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

     }
     //API for the Leave Application for all staff Dev Name- Manish Kumar Sharma 06-06-2025
     public function updateLeaveApplicationData(Request $request,$id){
        try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                    // dd($request->all());
                    $leaveTypeId = $request->leave_type_id;
                    $status = $request->status;
                    $approvercomment = $request->approvercomment;
                    // dd($leaveTypeId,$status,$approvercomment);
                    $leaveapplication = DB::table('leave_application')->where('leave_app_id',$id)->first();
                    $leavestatus = $leaveapplication->status;
                    if($status == 'P'){
                        if($leavestatus == 'P'){
                            $leaveapplication1 = DB::table('leave_application')
                                                    ->where('leave_app_id',$id)
                                                    ->update([
                                                        'leave_type_id'=>$leaveTypeId,
                                                        'reason_for_rejection'=>$approvercomment
                                                    ]);
                                                     return response()->json([
                                                        'status' =>200,
                                                        'message' => 'Leave application updated successfully.',
                                                        'success' =>true
                                                        ]);

                             }
                             $leaveapplication1 = DB::table('leave_application')
                                                    ->where('leave_app_id',$id)
                                                    ->update([
                                                        'leave_type_id'=>$leaveTypeId,
                                                        'reason_for_rejection'=>$approvercomment,
                                                        'approved_by'=>$user->reg_id,
                                                        'status'=>$status
                                                    ]);
                                    DB::table('leave_allocation')
                                            ->where('staff_id', $leaveapplication->staff_id)
                                            ->where('leave_type_id', $leaveTypeId)
                                            ->where('academic_yr', $customClaims)
                                            ->increment('leaves_availed', floatval($leaveapplication->no_of_days));

                                    return response()->json([
                                                        'status' =>200,
                                                        'message' => 'Leave application updated successfully.',
                                                        'success' =>true
                                                        ]);

                    }
                    else
                    {
                        $leaveapplication1 = DB::table('leave_application')
                                                    ->where('leave_app_id',$id)
                                                    ->update([
                                                        'leave_type_id'=>$leaveTypeId,
                                                        'reason_for_rejection'=>$approvercomment,
                                                        'status'=>$status
                                                    ]);
                         return response()->json([
                                            'status' =>200,
                                            'message' => 'Leave application updated successfully.',
                                            'success' =>true
                                            ]);

                    }


                }
                else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }


     }

     //API for the Remark and observation for teachers Dev Name- Manish Kumar Sharma 09-06-2023
     public function saveRemarkForTeacher(Request $request){
        try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                    // dd($request->all());
                    $teacherids = $request->teacherid;
                    $remarksubject = $request->remark_subject;
                    $remark = $request->remark;
                    $remarktype = $request->remark_type;

                        foreach($teacherids as $teacherid){
                            // dd($teacherid);
                            DB::table('teachers_remark')->insert([
                                'teachers_id' => $teacherid,
                                'remark_subject'=>$remarksubject,
                                'remark_desc'=>$remark,
                                'remark_type'=>$remarktype,
                                'remark_date'=>now(),
                                'dataentry_by'=>$user->reg_id,
                                'publish'=>'N',
                                'acknowledge'=>'N',
                                'academic_yr'=>$customClaims
                            ]);
                        }

                        return response()->json([
                            'status' => 200,
                            'message'=> 'Remark and observation saved successfully.',
                            'success'=>true
                        ]);


                }
                else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

     }
     //API for the Remark and observation for teachers Dev Name- Manish Kumar Sharma 09-06-2025
    //  public function savenPublishRemarkForTeacher(Request $request){
    //     try{
    //             $user = $this->authenticateUser();
    //             $customClaims = JWTAuth::getPayload()->get('academic_year');
    //             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
    //                 $teacherids = $request->teacherid;
    //                 $remarksubject = $request->remark_subject;
    //                 $remark = $request->remark;
    //                 $remarktype = $request->remark_type;

    //                     foreach($teacherids as $teacherid){
    //                         // dd($teacherid);
    //                         DB::table('teachers_remark')->insert([
    //                             'teachers_id' => $teacherid,
    //                             'remark_subject'=>$remarksubject,
    //                             'remark_desc'=>$remark,
    //                             'remark_type'=>$remarktype,
    //                             'remark_date'=>now(),
    //                             'publish_date'=>now(),
    //                             'dataentry_by'=>$user->reg_id,
    //                             'publish'=>'Y',
    //                             'acknowledge'=>'N',
    //                             'academic_yr'=>$customClaims
    //                         ]);
    //                     }

    //                     return response()->json([
    //                         'status' => 200,
    //                         'message'=> 'Remark and observation saved and published successfully.',
    //                         'success'=>true
    //                     ]);


    //              }
    //             else
    //              {
    //                 return response()->json([
    //                     'status'=> 401,
    //                     'message'=>'This User Doesnot have Permission for the getting of department list.',
    //                     'data' =>$user->role_id,
    //                     'success'=>false
    //                     ]);
    //                 }

    //            }
    //           catch (Exception $e) {
    //             \Log::error($e); // Log the exception
    //             return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    //            }

    //  }
    public function savenPublishRemarkForTeacher(Request $request){
        try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                    $teacherids = $request->teacherid;
                    $remarksubject = $request->remark_subject;
                    $remark = $request->remark;
                    $remarktype = $request->remark_type;

                        foreach($teacherids as $teacherid){
                            // dd($teacherid);
                            $id=DB::table('teachers_remark')->insertGetId([
                                'teachers_id' => $teacherid,
                                'remark_subject'=>$remarksubject,
                                'remark_desc'=>$remark,
                                'remark_type'=>$remarktype,
                                'remark_date'=>now(),
                                'publish_date'=>now(),
                                'dataentry_by'=>$user->reg_id,
                                'publish'=>'Y',
                                'acknowledge'=>'N',
                                'academic_yr'=>$customClaims
                              ]);
                            $teacherdetails = DB::table('teacher')->where('teacher_id',$teacherid)->first();
                            //   dd($teacherdetails);
                            if ($teacherdetails && isset($teacherdetails->name)) {
                                $fullName = $teacherdetails->name;
                            
                                // Remove known prefixes
                                $cleaned = preg_replace('/\b(Mr\.?|Mrs\.?|Miss\.?|Ms\.?|Fr\.?|Dr\.?)\b\.?\s*/i', '', $fullName);
                            
                                // Split into words and keep first + last only
                                $parts = preg_split('/\s+/', trim($cleaned));
                                $first = $parts[0] ?? '';
                                $last = end($parts);
                            
                                // Convert to CamelCase
                                $teacherNameCamel = ucfirst(strtolower($first)) . ' ' . ucfirst(strtolower($last));
                            }
                            //  dd($teacherNameCamel);
                            $teacherphoneno = $teacherdetails->phone;
                            if($teacherphoneno){
                                $templateName = 'emergency_message';
                                $parameters =[$teacherNameCamel.", ".$remark];
                                // Log::info($teacherphoneno);
                                $result = $this->whatsAppService->sendTextMessage(
                                    $teacherphoneno,
                                    $templateName,
                                    $parameters
                                );
                                // Log::info("Failed message",$result);
                                if (isset($result['code']) && isset($result['message'])) {
                                    // Handle rate limit error
                                    Log::warning("Rate limit hit: Too many messages to same user", [
                                        
                                    ]);
                            
                                } else {
                                    // Proceed if no error
                                    $wamid = $result['messages'][0]['id'];
                                    $phone_no = $result['contacts'][0]['input'];
                                    $message_type = 'teacher_remark';
                            
                                    DB::table('redington_webhook_details')->insert([
                                        'wa_id' => $wamid,
                                        'phone_no' => $phone_no,
                                        'stu_teacher_id' => $teacherid,
                                        'notice_id' => $id,
                                        'message_type' => $message_type,
                                        'created_at' => now()
                                    ]);
                                }
            
                            }
                        }

                        return response()->json([
                            'status' => 200,
                            'message'=> 'Remark and observation saved and published successfully.',
                            'success'=>true
                        ]);


                 }
                else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

     }
     //API for the Remark and observation for teachers Dev Name- Manish Kumar Sharma 09-06-2025
     public function getRemarkForTeacherList(Request $request){
        try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $remarkslist = DB::select("SELECT * from(SELECT  tr .*, teacher.name, 0 as read_status
        from teachers_remark tr JOIN teacher  on teacher.teacher_id=tr.teachers_id  WHERE tr.academic_yr = '".$customClaims."' and tr.t_remark_id   NOT IN (select t_remark_id from tremarks_read_log)
        UNION SELECT  tr .*, teacher.name, 1 as read_status
        from teachers_remark tr JOIN teacher  on teacher.teacher_id=tr.teachers_id  WHERE tr.academic_yr= '".$customClaims."' and tr.t_remark_id  IN (select t_remark_id from tremarks_read_log))as Z ORDER BY `t_remark_id` DESC");

        // dd($remarkslist);
                 return response()->json([
                            'status' => 200,
                            'date' =>$remarkslist,
                            'message'=> 'Remark and observation list.',
                            'success'=>true
                        ]);

             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

     }
     //API for the Remark and observation for teachers Dev Name- Manish Kumar Sharma 09-06-2025
     public function updateRemarkForTeacher(Request $request,$id){
        try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                  $t_remark_id = $id;
                  $remarksubject = $request->remarksubject;
                  $remark = $request->remark;
                //   dd( $t_remark_id,$remarksubject,$remark );
                 $updateremark= DB::table('teachers_remark')
                                    ->where('t_remark_id', $t_remark_id ) 
                                    ->update([
                                        'remark_subject'=>$remarksubject,
                                        'remark_desc'=>$remark
                                    ]);

                                     return response()->json([
                                            'status' => 200,
                                            'data' =>$updateremark,
                                            'message'=> 'Remark and observation updated.',
                                            'success'=>true
                                        ]);


             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

     }
     //API for the Remark and observation for teachers Dev Name- Manish Kumar Sharma 09-06-2025
     public function deleteRemarkForTeacher(Request $request,$id){
        try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                $exists = DB::table('teachers_remark')->where('t_remark_id', $id)->exists();

                if (!$exists) {
                    return response()->json([
                        'status'=>404,
                        'message' => 'Teacher remark not found.',
                        'success' => false
                    ], 404);
                }

                // Perform delete
                DB::table('teachers_remark')->where('t_remark_id', $id)->delete();

                return response()->json([
                    'status'=>200,
                    'message' => 'Teacher remark deleted successfully!',
                    'success' => true
                ]);


              }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

     }
     //API for the Remark and observation for teachers Dev Name- Manish Kumar Sharma 09-06-2025
    //  public function updatePublishRemarkForTeacher(Request $request,$id){
    //      try{
    //          $user = $this->authenticateUser();
    //          $customClaims = JWTAuth::getPayload()->get('academic_year');
    //          if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
    //             $remarkdetails = DB::table('teachers_remark')->where('t_remark_id',$id)->first();
    //             // dd($remarkdetails);
    //             $teacherdetails = DB::table('teacher')->where('teacher_id',$remarkdetails->teachers_id)->first();
    //             //  dd($teacherdetails);
    //             if ($teacherdetails && isset($teacherdetails->name)) {
    //                 $fullName = $teacherdetails->name;
                
    //                 // Remove known prefixes
    //                 $cleaned = preg_replace('/\b(Mr\.?|Mrs\.?|Miss\.?|Ms\.?|Fr\.?|Dr\.?)\b\.?\s*/i', '', $fullName);
                
    //                 // Split into words and keep first + last only
    //                 $parts = preg_split('/\s+/', trim($cleaned));
    //                 $first = $parts[0] ?? '';
    //                 $last = end($parts);
                
    //                 // Convert to CamelCase
    //                 $teacherNameCamel = ucfirst(strtolower($first)) . ' ' . ucfirst(strtolower($last));
    //             }
    //             // dd($teacherNameCamel);
    //             $teacherphoneno = $teacherdetails->phone;
    //             if($teacherphoneno){
    //                 $templateName = 'emergency_message';
    //                 $parameters =[$teacherNameCamel.",".$remarkdetails->remark_desc];
    //                 // Log::info($teacherphoneno);
    //                 $result = $this->whatsAppService->sendTextMessage(
    //                     $teacherphoneno,
    //                     $templateName,
    //                     $parameters
    //                 );
    //                 // Log::info("Failed message",$result);
    //                 if (isset($result['code']) && isset($result['message'])) {
    //                     // Handle rate limit error
    //                     Log::warning("Rate limit hit: Too many messages to same user", [
                            
    //                     ]);
                
    //                 } else {
    //                     // Proceed if no error
    //                     $wamid = $result['messages'][0]['id'];
    //                     $phone_no = $result['contacts'][0]['input'];
    //                     $message_type = 'teacher_remark';
                
    //                     DB::table('redington_webhook_details')->insert([
    //                         'wa_id' => $wamid,
    //                         'phone_no' => $phone_no,
    //                         'stu_teacher_id' => $remarkdetails->teachers_id,
    //                         'notice_id' => $id,
    //                         'message_type' => $message_type,
    //                         'created_at' => now()
    //                     ]);
    //                 }

    //             }
                 
    //             $publishteacherremark = DB::table('teachers_remark')
    //                                         ->where('t_remark_id', $id ) 
    //                                         ->update([
    //                                             'publish'=>'Y',
    //                                             'publish_date'=>now()
    //                                         ]);

    //             return response()->json([
    //                 'status'=>200,
    //                 'message' => 'Teacher remark published successfully!',
    //                 'success' => true
    //             ]);

    //          }
    //          else
    //              {
    //                 return response()->json([
    //                     'status'=> 401,
    //                     'message'=>'This User Doesnot have Permission for the getting of department list.',
    //                     'data' =>$user->role_id,
    //                     'success'=>false
    //                     ]);
    //                 }

    //            }
    //           catch (Exception $e) {
    //             \Log::error($e); // Log the exception
    //             return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    //            }

    //  }

    public function updatePublishRemarkForTeacher(Request $request,$id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                //  dd("Hello");
                $remarkdetails = DB::table('teachers_remark')->where('t_remark_id',$id)->first();
                // dd($remarkdetails);
                $teacherdetails = DB::table('teacher')->where('teacher_id',$remarkdetails->teachers_id)->first();
                //  dd($teacherdetails);
                if ($teacherdetails && isset($teacherdetails->name)) {
                    $fullName = $teacherdetails->name;
                
                    // Remove known prefixes
                    $cleaned = preg_replace('/\b(Mr\.?|Mrs\.?|Miss\.?|Ms\.?|Fr\.?|Dr\.?)\b\.?\s*/i', '', $fullName);
                
                    // Split into words and keep first + last only
                    $parts = preg_split('/\s+/', trim($cleaned));
                    $first = $parts[0] ?? '';
                    $last = end($parts);
                
                    // Convert to CamelCase
                    $teacherNameCamel = ucfirst(strtolower($first)) . ' ' . ucfirst(strtolower($last));
                }
                // dd($teacherNameCamel);
                $teacherphoneno = $teacherdetails->phone;
                if($teacherphoneno){
                    $templateName = 'emergency_message';
                    $parameters =[$teacherNameCamel.", ".$remarkdetails->remark_desc];
                    // Log::info($teacherphoneno);
                    $result = $this->whatsAppService->sendTextMessage(
                        $teacherphoneno,
                        $templateName,
                        $parameters
                    );
                    // Log::info("Failed message",$result);
                    if (isset($result['code']) && isset($result['message'])) {
                        // Handle rate limit error
                        Log::warning("Rate limit hit: Too many messages to same user", [
                            
                        ]);
                
                    } else {
                        // Proceed if no error
                        $wamid = $result['messages'][0]['id'];
                        $phone_no = $result['contacts'][0]['input'];
                        $message_type = 'teacher_remark';
                
                        DB::table('redington_webhook_details')->insert([
                            'wa_id' => $wamid,
                            'phone_no' => $phone_no,
                            'stu_teacher_id' => $remarkdetails->teachers_id,
                            'notice_id' => $id,
                            'message_type' => $message_type,
                            'created_at' => now()
                        ]);
                    }

                }
                 
                $publishteacherremark = DB::table('teachers_remark')
                                            ->where('t_remark_id', $id ) 
                                            ->update([
                                                'publish'=>'Y',
                                                'publish_date'=>now()
                                            ]);

                return response()->json([
                    'status'=>200,
                    'message' => 'Teacher remark published successfully!',
                    'success' => true
                ]);

             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

     }
     //API for the service type ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function saveServiceTypeTicket(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                  $servicename = $request->input('servicename');
                  $role_id = $request->input('role_id');
                  $description = $request->input('description');
                  $requiresappointment = $request->input('requiresappointment');
                  DB::table('service_type')->insert([
                      'service_name'=>$servicename,
                      'role_id'=>$role_id,
                      'description'=>$description,
                      'RequiresAppointment'=>$requiresappointment
                      ]);
                  return response()->json([
                    'status'=>200,
                    'message' => 'New service_type created!',
                    'success' => true
                ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
         
     }
     //API for the service type ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function getServiceTypeTicket(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                  $servicetypelist = DB::table('service_type')->get();
                  return response()->json([
                    'status'=>200,
                    'data'=>$servicetypelist,
                    'message' => 'Service type list.',
                    'success' => true
                ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     //API for the service type ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function deleteServiceTypeTicket(Request $request,$service_id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 DB::table('service_type')->where('service_id',$service_id)->delete();
                 return response()->json([
                    'status'=>200,
                    'message' => 'Service type deleted!',
                    'success' => true
                ]);
                 
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
         
     }
      //API for the service type ticket Dev Name- Manish Kumar Sharma 24-06-2025
      public function updateServiceTypeTicket(Request $request,$service_id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $servicename = $request->input('servicename');
                 $role_id = $request->input('role_id');
                 $description = $request->input('description');
                 $requiresappointment = $request->input('requiresappointment');
                 DB::table('service_type')
                    ->where('service_id', $service_id)
                    ->update([
                        'service_name' => $servicename,
                        'role_id' => $role_id,
                        'description' => $description,
                        'RequiresAppointment' => $requiresappointment
                    ]);
                 return response()->json([
                    'status'=>200,
                    'message' => 'Service type updated!',
                    'success' => true
                ]);
                 
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
         
     }
     //API for the sub service type ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function savesubServiceTypeTicket(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                  $subservicename = $request->input('subservicename');
                  $service_id = $request->input('service_id');
                  DB::table('sub_service_type')->insert([
                      'name'=>$subservicename,
                      'service_id'=>$service_id
                      ]);
                  return response()->json([
                    'status'=>200,
                    'message' => 'New sub service type created!',
                    'success' => true
                ]);
                 
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     //API for the sub service type ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function getsubServiceTypeTicket(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                  $subservicelist = DB::table('sub_service_type')
                                        ->join('service_type', 'service_type.service_id', '=', 'sub_service_type.service_id')
                                        ->select('sub_service_type.*', 'service_type.*')
                                        ->get()
                                        ->toArray(); 
                      return response()->json([
                        'status'=>200,
                        'data'=>$subservicelist,
                        'message' => 'Sub service list!',
                        'success' => true
                    ]);
                 
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     //API for the sub service type ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function deletesubServiceTypeTicket(Request $request,$sub_servicetype_id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                //  dd("Hello");
                 DB::table('sub_service_type')->where('sub_servicetype_id',$sub_servicetype_id)->delete();
                 return response()->json([
                    'status'=>200,
                    'message' => 'Sub service type deleted!',
                    'success' => true
                ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     //API for the sub service type ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function updatesubServiceTypeTicket(Request $request,$sub_servicetype_id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 
                 $subservicename = $request->input('subservicename');
                 $service_id = $request->input('service_id');
                 DB::table('sub_service_type')
                    ->where('sub_servicetype_id', $sub_servicetype_id)
                    ->update([
                        'name' => $subservicename,
                        'service_id' => $service_id
                    ]);
                 return response()->json([
                    'status'=>200,
                    'message' => 'Sub service type updated!',
                    'success' => true
                ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     //API for the appointment window ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function saveAppointmentWindow(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $data = [
                        'role_id' => $request->input('role'),
                        'class_id' => $request->input('class'),
                        'week' => $request->input('week'),
                        'time_from' => $request->input('time_from'),
                        'time_to' => $request->input('time_to'),
                        'weekday' => $request->has('weekday')
                            ? implode(',', $request->input('weekday'))
                            : '',
                    ];
                DB::table('appointment_window')->insert($data);
                return response()->json([
                    'status'=>200,
                    'message' => 'Save appointment window!',
                    'success' => true
                ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     //API for the appointment window ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function getAppointmentWindowList(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                //  dd("Hello");
                 $data = DB::table('appointment_window as a')
                            ->join('role_master as r', 'r.role_id', '=', 'a.role_id')
                            ->join('class as c', 'c.class_id', '=', 'a.class_id')
                            ->select('a.*', 'r.name as rn', 'c.name as cn')
                            ->get();
                    
                        return response()->json([
                            'status'=>200,
                            'data'=>$data,
                            'message'=>'Appointment window list!',
                            'success'=>true
                            ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     //API for the appointment window ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function deleteAppointmentWindow(Request $request,$aw_id){
          try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                //  dd("Hello");
                 DB::table('appointment_window')->where('aw_id',$aw_id)->delete();
                 return response()->json([
                    'status'=>200,
                    'message' => 'Appointment window deleted!',
                    'success' => true
                ]);
                 
                 
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     //API for the appointment window ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function updateAppointmentWindow(Request $request,$aw_id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $data = [
                    'role_id' => $request->input('role'),
                    'class_id' => $request->input('class'),
                    'week' => $request->input('week'),
                    'time_from' => $request->input('time_from'),
                    'time_to' => $request->input('time_to'),
                    'weekday' => $request->has('weekday')
                        ? implode(',', $request->input('weekday'))
                        : '',
                ];
                $updated = DB::table('appointment_window')
                            ->where('aw_id', $aw_id)
                            ->update($data);
                            
                return response()->json([
                    'status'=>200,
                    'message' => 'Appointment window updated!',
                    'success' => true
                ]);
                
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }

     //API for the ticket report ticket Dev Name- Manish Kumar Sharma 24-06-2025
     public function getTicketReport(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                $status = $request->input('status');
                $fromDate = $request->input('from_date');
                $toDate = $request->input('to_date');
            
                $query = DB::table('ticket')
                    ->select(
                        'ticket.raised_on',
                        'ticket.status',
                        'service_type.service_name',
                        'student.class_id',
                        'student.section_id',
                        'student.first_name',
                        'student.mid_name',
                        'student.last_name',
                        'class.name as classname',
                        'section.name as sectionname',
                        'parent.father_name as createdby'
                    )
                    ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
                    ->join('student', 'student.student_id', '=', 'ticket.student_id')
                    ->join('class','class.class_id','=','student.class_id')
                    ->join('section','section.section_id','=','student.section_id')
                    ->join('parent','parent.parent_id','=','student.parent_id');
            
                if (!empty($status)) {
                    $query->where('ticket.status', $status);
                }
            
                if (!empty($fromDate)) {
                    $query->whereDate('ticket.raised_on', '>=', date('Y-m-d', strtotime($fromDate)));
                }
            
                if (!empty($toDate)) {
                    $query->whereDate('ticket.raised_on', '<=', date('Y-m-d', strtotime($toDate)));
                }
            
                $ticketData = $query->orderByDesc('ticket.raised_on')->get();
                
                return response()->json([
                        'status'=>200,
                        'data' => $ticketData,
                        'message' => 'Ticket list report!',
                        'success'=>true
                    ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }

     //API for the ticket list Dev Name- Manish Kumar Sharma 25-06-2025
     public function getTicketList(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 
                 $data=getTicketListForRespondent($user->role_id,$user->reg_id);
                 return response()->json([
                        'status'=>200,
                        'data' => $data,
                        'message' => 'Ticket list!',
                        'success'=>true
                    ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     //API for the ticket list Dev Name- Manish Kumar Sharma 25-06-2025
     public function getTicketInformationByTicketId(Request $request,$ticket_id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                $data =  getTicketListViewInfo($ticket_id);
                return response()->json([
                        'status'=>200,
                        'data' => $data,
                        'message' => 'Ticket information!',
                        'success'=>true
                    ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }

     //API for the ticket list Dev Name- Manish Kumar Sharma 25-06-2025
     public function getStatusesForTicket(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $data = updateStatusforTicketList();
                 return response()->json([
                        'status'=>200,
                        'data' => $data,
                        'message' => 'Ticket status for ticket list!',
                        'success'=>true
                    ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }


     public function getAppointmentTimeList(Request $request,$class_id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $currentDate = Carbon::now()->format('d-M-Y');

                // Fetch appointment window data
                $windows = DB::table('appointment_window')
                    ->select('week', 'weekday', 'time_from', 'time_to')
                    ->where('role_id', $user->role_id)
                    ->where('class_id', $class_id)
                    ->get();
            
                $response = [];
            
                foreach ($windows as $row) {
                    $week = $row->week;
                    $weekdays = explode(',', $row->weekday);
                    $timeFrom = Carbon::parse($row->time_from)->format('h:i a');
                    $timeTo = Carbon::parse($row->time_to)->format('h:i a');
            
                    foreach ($weekdays as $index => $day) {
                        // Build date string like "first Monday of Jun"
                        $baseDate = "$week $day of " . date('M');
                        $formattedDate = date("d-M-Y", strtotime($baseDate));
            
                        // If past date, move to next month
                        if (strtotime($formattedDate) < strtotime($currentDate)) {
                            $date = (new DateTime($formattedDate))
                                ->modify("$week $day of next month")
                                ->format('d-M-Y');
                        } else {
                            $date = $formattedDate;
                        }
            
                        $display = "$date $timeFrom to $timeTo";
            
                        $response[] = [
                            'date' => $date,
                            'time_from' => $timeFrom,
                            'time_to' => $timeTo,
                            'display' => $display,
                            'value' => $display, // for form value or frontend use
                            'index' => $index
                        ];
                    }
                }
            
                return response()->json([
                    'status' => 200,
                    'data' => $response,
                    'message'=>'Appointment time!',
                    'success'=>true
                ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }

     //API for the ticket list Dev Name- Manish Kumar Sharma 25-06-2025
     public function getCommentTicketList(Request $request,$ticket_id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $data =getTicketComments($ticket_id);
                 return response()->json([
                    'status' => 200,
                    'data' => $data,
                    'message'=>'Comment list!',
                    'success'=>true
                ]);
                 
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     //API for the ticket list Dev Name- Manish Kumar Sharma 25-06-2025
     public function saveTicketInformation(Request $request,$ticket_id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 DB::beginTransaction();

                try {
                     $status = $request->status;
                    
                       
                        DB::table('ticket')->where('ticket_id', $ticket_id)
                            ->update(['status' => $status]);
                
                    
                        $comment = DB::table('ticket_comments')->insertGetId([
                            'ticket_id' => $ticket_id,
                            'login_type' => $user->role_id,
                            'comment' => $request->comment,
                            'status' => $request->status,
                            'appointment_date_time' => $request->appointment_date_time,
                            'commented_by' => $user->reg_id,
                        ]);
                        // dd($comment);
                
                        // Handle file upload
                        if ($request->hasFile('fileupload')) {
                            $file = $request->file('fileupload');
                            $originalName = $file->getClientOriginalName();
                            $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                            $extension = $file->getClientOriginalExtension();
                            $safeName = str_replace(' ', '_', $nameOnly);
                            $filename = $safeName.'.'.$extension;
                            $codeigniter = ticket_files_for_laravel($ticket_id,$comment,$file);
                            $path = "ticket/{$ticket_id}/{$comment}/";
                            $storedPath = $file->storeAs("public/{$path}", $filename);
                
                            DB::table('ticket_detail')->insert([
                                'ticket_id' => $ticket_id,
                                'ticket_comment_id' => $comment,
                                'image_name' => $filename,
                            ]);
                        }
                
                        DB::commit();
                        return response()->json([
                            'status' => 200,
                            'message'=>'Data created successfully!',
                            'success'=>true
                        ]);
                
                        // return redirect()->route('ticket.list')->with('ticket_message', 'Data created successfully!');
                    } catch (\Exception $e) {
                        DB::rollBack();
                        report($e);
                        return back()->withErrors('Something went wrong.')->withInput();
                    }
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }

     public function updateTimetableAllotment(Request $request){
         try{       
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                // dd("Hello");
                 $timetablerequest = $request->all();
                 $timetabledata = $timetablerequest['timetable_data'];
                 $teacherId =  $timetablerequest['teacher_id'];
                 $periodUsed = $timetablerequest['period_used'];
                  DB::table('teachers_period_allocation')->where('teacher_id',$teacherId)->where('academic_yr',$customClaims)->update(['periods_used'=>$periodUsed]);
                 foreach ($timetabledata as $timetable){
                     
                      $timetabledata5 = DB::table('timetable')->where('class_id',$timetable['class_id'])->where('section_id',$timetable['section_id'])->where('academic_yr',$customClaims)->first();
                      if(is_null($timetabledata5)){
                        //   DB::table('teachers_period_allocation')->where('teacher_id',$teacherId)->where('academic_yr',$customClaims)->update(['periods_used'=>$periodUsed]);
                          $timetabledata1 = $timetable['subjects'];
                             $classwiseperiod = DB::table('classwise_period_allocation')->where('class_id',$timetable['class_id'])->where('section_id',$timetable['section_id'])->first();
                                 $monfrilectures =  $classwiseperiod->{'mon-fri'};
                                 for($i=1;$i<=$monfrilectures;$i++){
                                     $inserttimetable = DB::table('timetable')->insert([
                                                             'date'=>Carbon::now()->format('Y-m-d H:i:s'),
                                                             'class_id' => $timetable['class_id'],
                                                             'section_id' => $timetable['section_id'],
                                                             'academic_yr'=>$customClaims,
                                                             'period_no'=>$i,
                                                         ]);
                                     
                                     
                                 }
                             foreach ($timetabledata1 as $timetabledata2){
                                 
                                 if($timetabledata2['day']== 'Monday'){
                                     $timetabledata3 = $timetabledata2['periods'];
                                     foreach ($timetabledata3 as $timetabledata4){
                                         if (isset($timetabledata4['subject']['id'])){
                                                DB::table('timetable')
                                                     ->where('class_id', $timetable['class_id'])
                                                     ->where('section_id', $timetable['section_id'])
                                                     ->where('academic_yr', $customClaims)
                                                     ->where('period_no', $timetabledata4['period_no'])
                                                     ->update([
                                                         'monday' => $timetabledata4['subject']['id'].'^'.$teacherId
                                                     ]);
                                         }
                                     }
                                     
                                 }
                                 elseif($timetabledata2['day']=='Tuesday'){
                                     $timetabledata3 = $timetabledata2['periods'];
                                     foreach ($timetabledata3 as $timetabledata4){
                                          if (isset($timetabledata4['subject']['id'])){
                                                DB::table('timetable')
                                                     ->where('class_id', $timetable['class_id'])
                                                     ->where('section_id', $timetable['section_id'])
                                                     ->where('academic_yr', $customClaims)
                                                     ->where('period_no', $timetabledata4['period_no'])
                                                     ->update([
                                                         'tuesday' =>$timetabledata4['subject']['id'].'^'.$teacherId
                                                     ]);
                                          }
                                     }
                                     
                                 }
                                 elseif($timetabledata2['day']=='Wednesday'){
                                     $timetabledata3 = $timetabledata2['periods'];
                                     foreach ($timetabledata3 as $timetabledata4){
                                          if (isset($timetabledata4['subject']['id'])){
                                                DB::table('timetable')
                                                     ->where('class_id', $timetable['class_id'])
                                                     ->where('section_id', $timetable['section_id'])
                                                     ->where('academic_yr', $customClaims)
                                                     ->where('period_no', $timetabledata4['period_no'])
                                                     ->update([
                                                         'wednesday' => $timetabledata4['subject']['id'].'^'.$teacherId
                                                     ]);
                                          }
                                     }
                                     
                                 }
                                 elseif($timetabledata2['day']=='Thursday'){
                                     $timetabledata3 = $timetabledata2['periods'];
                                     foreach ($timetabledata3 as $timetabledata4){
                                          if (isset($timetabledata4['subject']['id'])){
                                                DB::table('timetable')
                                                     ->where('class_id', $timetable['class_id'])
                                                     ->where('section_id', $timetable['section_id'])
                                                     ->where('academic_yr', $customClaims)
                                                     ->where('period_no', $timetabledata4['period_no'])
                                                     ->update([
                                                         'thursday' => $timetabledata4['subject']['id'].'^'.$teacherId
                                                     ]);
                                          }
                                     }
                                     
                                 }
                                 elseif($timetabledata2['day']=='Friday'){
                                     $timetabledata3 = $timetabledata2['periods'];
                                     foreach ($timetabledata3 as $timetabledata4){
                                          if (isset($timetabledata4['subject']['id'])){
                                                DB::table('timetable')
                                                     ->where('class_id', $timetable['class_id'])
                                                     ->where('section_id', $timetable['section_id'])
                                                     ->where('academic_yr', $customClaims)
                                                     ->where('period_no', $timetabledata4['period_no'])
                                                     ->update([
                                                         'friday' => $timetabledata4['subject']['id'].'^'.$teacherId
                                                     ]);
                                          }
                                     }
                                     
                                 }
                                 elseif($timetabledata2['day']=='Saturday'){
                                     $timetabledata3 = $timetabledata2['periods'];
                                     foreach ($timetabledata3 as $timetabledata4){
                                          if (isset($timetabledata4['subject']['id'])){
                                                DB::table('timetable')
                                                     ->where('class_id', $timetable['class_id'])
                                                     ->where('section_id', $timetable['section_id'])
                                                     ->where('academic_yr', $customClaims)
                                                     ->where('period_no', $timetabledata4['period_no'])
                                                     ->update([
                                                         'saturday' =>$timetabledata4['subject']['id'].'^'.$teacherId
                                                     ]);
                                          }
                                     }
                                     
                                 }
                             }
                          
                      }
                      else{
                          $timetabledata1 = $timetable['subjects'];
                          
                     foreach ($timetabledata1 as $timetabledata2){
                         
                         if($timetabledata2['day']== 'Monday'){
                             $timetabledata3 = $timetabledata2['periods'];
                             
                             foreach ($timetabledata3 as $timetabledata4){
                                 if (!empty($timetabledata4['subjectRemove'])) {
                                     $existing = DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->first();
                                                
                                                $currentValue = $existing->monday ?? '';
                                                $finalValue = $currentValue; 
                                                
                                                $removeSubjectId = $timetabledata4['subjectRemove'];
                                                $toRemove = $removeSubjectId . '^' . $teacherId;
                                            
                                                $entries = array_filter(explode(',', $currentValue));
                                                $updatedEntries = [];
                                            
                                                foreach ($entries as $entry) {
                                                    if ($entry !== $toRemove) {
                                                        $updatedEntries[] = $entry;
                                                    } else {
                                                        // Decrement periods_used for removed teacher
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->decrement('periods_used', 1);
                                                    }
                                                }
                                            
                                                $finalValue = implode(',', $updatedEntries);
                                            
                                                // update timetable table
                                                DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->update([
                                                        'monday' => $finalValue,
                                                    ]);
                                                    if(isset($timetabledata4['subject']['id'])) {
                                                        $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            // Override existing value
                                            $currentMonday = $existing->monday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                          

                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            } 
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            $currentMonday = $existing->monday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            
                                            // // Append to existing value (if any)
                                            // $currentMonday = $existing->monday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'monday' => $finalValue,
                                            ]);
                                                        
                                                    }
                                        
                                    }
                                    elseif (isset($timetabledata4['subject']['id'])) {
                                        $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            // Override existing value
                                            $currentMonday = $existing->monday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                          

                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            } 
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                            $currentMonday = $existing->monday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    
                                                }
                                            }
                                            
                                            // // Append to existing value (if any)
                                            // $currentMonday = $existing->monday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'monday' => $finalValue,
                                            ]);
                                    }
                                 
                                 
                             }
                             
                         }
                         elseif($timetabledata2['day']=='Tuesday'){
                             $timetabledata3 = $timetabledata2['periods'];
                             foreach ($timetabledata3 as $timetabledata4){
                                 if (!empty($timetabledata4['subjectRemove'])) {
                                     $existing = DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->first();
                                                
                                                $currentValue = $existing->tuesday ?? '';
                                                $finalValue = $currentValue;
                                                $removeSubjectId = $timetabledata4['subjectRemove'];
                                                $toRemove = $removeSubjectId . '^' . $teacherId;
                                            
                                                $entries = array_filter(explode(',', $currentValue));
                                                $updatedEntries = [];
                                            
                                                foreach ($entries as $entry) {
                                                    if ($entry !== $toRemove) {
                                                        $updatedEntries[] = $entry;
                                                    } else {
                                                        // Decrement periods_used for removed teacher
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->decrement('periods_used', 1);
                                                    }
                                                }
                                            
                                                $finalValue = implode(',', $updatedEntries);
                                            
                                                // update timetable table
                                                DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->update([
                                                        'tuesday' => $finalValue,
                                                    ]);
                                                    if(isset($timetabledata4['subject']['id'])){
                                                        $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            $currentMonday = $existing->tuesday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherId) = explode('^', $entry);
                                                    $teacherIds[] = $teacherId;
                                                }
                                            }
                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            } 
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            
                                            $currentMonday = $existing->tuesday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        // ✅ Teacher ID already seen → increment periods_used by 1
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        // First time seeing this teacherId → store it
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            // Append to existing value (if any)
                                            // $currentMonday = $existing->tuesday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'tuesday' => $finalValue,
                                            ]);
                                                        
                                                    }
                                     
    
                                } elseif (isset($timetabledata4['subject']['id'])) {
                                    $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            $currentMonday = $existing->tuesday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            } 
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                            $currentMonday = $existing->tuesday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        // ✅ Teacher ID already seen → increment periods_used by 1
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        // First time seeing this teacherId → store it
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            // Append to existing value (if any)
                                            // $currentMonday = $existing->tuesday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'tuesday' => $finalValue,
                                            ]);
                                    
                                }
                                 
                            }
                         }
                         elseif($timetabledata2['day']=='Wednesday'){
                             $timetabledata3 = $timetabledata2['periods'];
                             foreach ($timetabledata3 as $timetabledata4){
                                 if (!empty($timetabledata4['subjectRemove'])) {
                                     $existing = DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->first();
                                                
                                                $currentValue = $existing->wednesday ?? '';
                                                $finalValue = $currentValue;
                                                $removeSubjectId = $timetabledata4['subjectRemove'];
                                                $toRemove = $removeSubjectId . '^' . $teacherId;
                                            
                                                $entries = array_filter(explode(',', $currentValue));
                                                $updatedEntries = [];
                                            
                                                foreach ($entries as $entry) {
                                                    if ($entry !== $toRemove) {
                                                        $updatedEntries[] = $entry;
                                                    } else {
                                                        // Decrement periods_used for removed teacher
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->decrement('periods_used', 1);
                                                    }
                                                }
                                            
                                                $finalValue = implode(',', $updatedEntries);
                                            
                                                // update timetable table
                                                DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->update([
                                                        'wednesday' => $finalValue,
                                                    ]);
                                                    if(isset($timetabledata4['subject']['id'])) {
                                                        $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            $currentMonday = $existing->wednesday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            } 
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            $currentMonday = $existing->wednesday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        // ✅ Teacher ID already seen → increment periods_used by 1
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        // First time seeing this teacherId → store it
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            // Append to existing value (if any)
                                            // $currentMonday = $existing->wednesday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'wednesday' => $finalValue,
                                            ]);
                                                        
                                                        
                                                    }
    
                                } elseif (isset($timetabledata4['subject']['id'])) {
                                    $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            $currentMonday = $existing->wednesday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            } 
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                            $currentMonday = $existing->wednesday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        // ✅ Teacher ID already seen → increment periods_used by 1
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        // First time seeing this teacherId → store it
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            // Append to existing value (if any)
                                            // $currentMonday = $existing->wednesday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'wednesday' => $finalValue,
                                            ]);
                                    
                                }
                             }
                             
                         }
                         elseif($timetabledata2['day']=='Thursday'){
                             $timetabledata3 = $timetabledata2['periods'];
                             foreach ($timetabledata3 as $timetabledata4){
                                 if (!empty($timetabledata4['subjectRemove'])) {
                                     $existing = DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->first();
                                                
                                                $currentValue = $existing->thursday ?? '';
                                                $finalValue = $currentValue;
                                                $removeSubjectId = $timetabledata4['subjectRemove'];
                                                $toRemove = $removeSubjectId . '^' . $teacherId;
                                            
                                                $entries = array_filter(explode(',', $currentValue));
                                                $updatedEntries = [];
                                            
                                                foreach ($entries as $entry) {
                                                    if ($entry !== $toRemove) {
                                                        $updatedEntries[] = $entry;
                                                    } else {
                                                        // Decrement periods_used for removed teacher
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->decrement('periods_used', 1);
                                                    }
                                                }
                                            
                                                $finalValue = implode(',', $updatedEntries);
                                            
                                                // update timetable table
                                                DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->update([
                                                        'thursday' => $finalValue,
                                                    ]);
                                                    
                                                if(isset($timetabledata4['subject']['id'])) {
                                                    $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            $currentMonday = $existing->thursday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            }  
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            $currentMonday = $existing->thursday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        // ✅ Teacher ID already seen → increment periods_used by 1
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        // First time seeing this teacherId → store it
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            // Append to existing value (if any)
                                            // $currentMonday = $existing->thursday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'thursday' => $finalValue,
                                            ]);
                                                    
                                                    
                                                }
    
                                } elseif (isset($timetabledata4['subject']['id'])) {
                                    $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            $currentMonday = $existing->thursday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            }  
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                            $currentMonday = $existing->thursday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        // ✅ Teacher ID already seen → increment periods_used by 1
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        // First time seeing this teacherId → store it
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            // Append to existing value (if any)
                                            // $currentMonday = $existing->thursday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'thursday' => $finalValue,
                                            ]);
                                    
                                }
                                
                                      
                             }
                             
                         }
                         elseif($timetabledata2['day']=='Friday'){
                             $timetabledata3 = $timetabledata2['periods'];
                             foreach ($timetabledata3 as $timetabledata4){
                                 if (!empty($timetabledata4['subjectRemove'])) {
                                     $existing = DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->first();
                                                
                                                $currentValue = $existing->friday ?? '';
                                                $finalValue = $currentValue;
                                                $removeSubjectId = $timetabledata4['subjectRemove'];
                                                $toRemove = $removeSubjectId . '^' . $teacherId;
                                            
                                                $entries = array_filter(explode(',', $currentValue));
                                                $updatedEntries = [];
                                            
                                                foreach ($entries as $entry) {
                                                    if ($entry !== $toRemove) {
                                                        $updatedEntries[] = $entry;
                                                    } else {
                                                        // Decrement periods_used for removed teacher
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->decrement('periods_used', 1);
                                                    }
                                                }
                                            
                                                $finalValue = implode(',', $updatedEntries);
                                            
                                                // update timetable table
                                                DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->update([
                                                        'friday' => $finalValue,
                                                    ]);
                                         if(isset($timetabledata4['subject']['id'])) {
                                             $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            $currentMonday = $existing->friday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            }  
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            $currentMonday = $existing->friday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        // ✅ Teacher ID already seen → increment periods_used by 1
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        // First time seeing this teacherId → store it
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            // Append to existing value (if any)
                                            // $currentMonday = $existing->friday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'friday' => $finalValue,
                                            ]);
                                             
                                         }
    
                                } elseif (isset($timetabledata4['subject']['id'])) {
                                    $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            $currentMonday = $existing->friday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            }  
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                            $currentMonday = $existing->friday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        // ✅ Teacher ID already seen → increment periods_used by 1
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        // First time seeing this teacherId → store it
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            // Append to existing value (if any)
                                            // $currentMonday = $existing->friday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'friday' => $finalValue,
                                            ]);
                                    
                                }
                                
                                
                             }
                             
                         }
                         elseif($timetabledata2['day']=='Saturday'){
                             $timetabledata3 = $timetabledata2['periods'];
                             foreach ($timetabledata3 as $timetabledata4){
                                 if (!empty($timetabledata4['subjectRemove'])) {
                                     $existing = DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->first();
                                                
                                                $currentValue = $existing->saturday ?? '';
                                                $finalValue = $currentValue;
                                                $removeSubjectId = $timetabledata4['subjectRemove'];
                                                $toRemove = $removeSubjectId . '^' . $teacherId;
                                            
                                                $entries = array_filter(explode(',', $currentValue));
                                                $updatedEntries = [];
                                            
                                                foreach ($entries as $entry) {
                                                    if ($entry !== $toRemove) {
                                                        $updatedEntries[] = $entry;
                                                    } else {
                                                        // Decrement periods_used for removed teacher
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->decrement('periods_used', 1);
                                                    }
                                                }
                                            
                                                $finalValue = implode(',', $updatedEntries);
                                            
                                                // update timetable table
                                                DB::table('timetable')
                                                    ->where('class_id', $timetable['class_id'])
                                                    ->where('section_id', $timetable['section_id'])
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('period_no', $timetabledata4['period_no'])
                                                    ->update([
                                                        'saturday' => $finalValue,
                                                    ]);
                                                    
                                                if(isset($timetabledata4['subject']['id'])) {
                                                    $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            $currentMonday = $existing->saturday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            } 
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            $currentMonday = $existing->saturday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        // ✅ Teacher ID already seen → increment periods_used by 1
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        // First time seeing this teacherId → store it
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            // Append to existing value (if any)
                                            // $currentMonday = $existing->saturday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'saturday' => $finalValue,
                                            ]);
                                                    
                                                    
                                                    
                                                }
    
                                } elseif (isset($timetabledata4['subject']['id'])) {
                                    $existing = DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->first();
                                    
                                        $newValue = $timetabledata4['subject']['id'] . '^' . $teacherId;
                                    
                                        if ($timetabledata4['override'] === 'Y') {
                                            $currentMonday = $existing->saturday ?? '';
                                            $teacherIds = [];

                                            $entries = explode(',', $currentMonday);
                                            
                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                                    $teacherIds[] = $teacherIdd;
                                                }
                                            }
                                            if (($key = array_search($teacherId, $teacherIds)) !== false) {
                                                unset($teacherIds[$key]);
                                                // Optional: reindex array if needed
                                                $teacherIds = array_values($teacherIds);
                                            }
                                            
                                            foreach ($teacherIds as $teacherperiodused) {
                                                DB::table('teachers_period_allocation')
                                                    ->where('teacher_id', $teacherperiodused)
                                                    ->where('academic_yr', $customClaims)
                                                    ->decrement('periods_used', 1);
                                            } 
                                            $finalValue = $newValue;
                                            // dd($finalValue);
                                        } else {
                                            DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                            $currentMonday = $existing->saturday ?? '';
                                            $valuesArray = array_filter(explode(',', $currentMonday));

                                            if (in_array($newValue, $valuesArray)) {
                                                // Do nothing if value already exists
                                                $finalValue = $currentMonday;
                                            } else {
                                                $finalValue = $currentMonday
                                                    ? $currentMonday . ',' . $newValue
                                                    : $newValue;
                                            }
                                            
                                            $entries = explode(',', $currentMonday);
                                            $teacherIds = [];

                                            foreach ($entries as $entry) {
                                                if (str_contains($entry, '^')) {
                                                    list($subjectId, $teacherIdd) = explode('^', $entry);
                                            
                                                    if (in_array($teacherId, $teacherIds)) {
                                                        // ✅ Teacher ID already seen → increment periods_used by 1
                                                        DB::table('teachers_period_allocation')
                                                            ->where('teacher_id', $teacherId)
                                                            ->where('academic_yr', $customClaims)
                                                            ->increment('periods_used', 1);
                                                    } else {
                                                        // First time seeing this teacherId → store it
                                                        $teacherIds[] = $teacherIdd;
                                                    }
                                                }
                                            }
                                            // Append to existing value (if any)
                                            // $currentMonday = $existing->saturday ?? '';
                                            // $finalValue = $currentMonday
                                            //     ? $currentMonday . ',' . $newValue
                                            //     : $newValue;
                                        }
                                    
                                        DB::table('timetable')
                                            ->where('class_id', $timetable['class_id'])
                                            ->where('section_id', $timetable['section_id'])
                                            ->where('academic_yr', $customClaims)
                                            ->where('period_no', $timetabledata4['period_no'])
                                            ->update([
                                                'saturday' => $finalValue,
                                            ]);
                                    
                                }
                                
                                        
                             }
                             
                          }
                        }
                          
                      }
                     
                     
                 }
                 $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

                    // Get all teacher IDs from teachers_period_allocation
                    $teacherIds = DB::table('teachers_period_allocation')
                                    ->pluck('teacher_id')
                                    ->unique()
                                    ->toArray();
                    
                    $rows = DB::table('timetable')->get();
                    
                    // Initialize an array to keep track of count per teacher
                    $teacherCounts = array_fill_keys($teacherIds, 0);
                    
                    // Loop through timetable and count matching entries for each teacher
                    foreach ($rows as $row) {
                        foreach ($days as $day) {
                            $value = $row->$day;
                    
                            if (!empty($value) && str_contains($value, '^')) {
                                $entries = explode(',', $value);
                    
                                foreach ($entries as $entry) {
                                    if (str_contains($entry, '^')) {
                                        list($subjectId, $entryTeacherId) = explode('^', $entry);
                    
                                        if (in_array($entryTeacherId, $teacherIds)) {
                                            $teacherCounts[$entryTeacherId] += 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // Now update periods_used for each teacher based on counts
                    foreach ($teacherCounts as $teacherId => $count) {
                        DB::table('teachers_period_allocation')
                            ->where('teacher_id', $teacherId)
                            ->where('academic_yr', $customClaims)
                            ->update(['periods_used' => $count]);
                    }
                 return response()->json([
                'status' =>200,
                'message' => 'Timetable Saved Successfully!',
                'success'=>true
               ]);
                 
                
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Teacher Period Data.',
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

     public function saveRemarkObservationForStudents(Request $request){
          try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             $savepublish = $request->input('save_publish');
             if($savepublish == 'N'){
                 $files = $request->file('userfile', []);
                $studentIds = $request->student_id;
            
                foreach ($studentIds as $index => $studentId) {
                    $remark_type = $request->filled('observation') ? 'Observation' : 'Remark';

                    $insertData = [
                        'remark_type'     => $remark_type,
                        'remark_desc'     => $request->input('remark_desc'),
                        'remark_subject'  => $request->input('remark_subject'),
                        'class_id'        => $request->input('class_id'),
                        'section_id'      => $request->input('section_id'),
                        'subject_id'      => $request->input('subject_id'),
                        'teacher_id'      => $user->reg_id,
                        'academic_yr'     => $customClaims,
                        'remark_date'     => \Carbon\Carbon::parse($request->input('remark_date'))->format('Y-m-d'),
                        'publish'         => 'N',
                        'acknowledge'     => 'N',
                        'student_id'      => $studentId,
                    ];
                    
                     $remarkId = DB::table('remark')->insertGetId($insertData);
                     $filenames = [];
                    $datafiles = [];
                    
                    $uploadDate = now()->format('d-m-Y'); // Today's date
                    $docTypeFolder = 'remark';            // Document type folder
                    $noticeId = $remarkId; // If notice ID is available
                        foreach ($files as $file) {
                            if ($file && $file->isValid()) {
                                $filename = $file->getClientOriginalName();
                                $folder = 'remark/' . \Carbon\Carbon::parse($request->input('remark_date'))->format('Y-m-d') . '/' . $studentId;
                                $path = $file->storeAs($folder, $filename, 'public');
                                $filesize = $file->getSize();
                                $filenames[] = $filename;
                                $datafiles[] = base64_encode(file_get_contents($file->getRealPath()));
                                // dd($datafiles);
                                DB::table('remark_detail')->insert([
                                    'remark_id'  => $remarkId,
                                    'image_name' => $filename,
                                    'file_size'  => $filesize,
                                ]);
                            }
                    }
                    
                    $response = upload_files_for_laravel($filenames, $datafiles, $uploadDate, $docTypeFolder, $noticeId);
                    // dd($response);
                }
                
                return response()->json([
                    'status' =>200,
                    'message' => 'Remark Saved Successfully!',
                    'success'=>true
                   ]);
                 
             }
             else{
                $files = $request->file('userfile', []);
                $studentIds = $request->student_id;
            
                foreach ($studentIds as $index => $studentId) {
                    $remark_type = $request->filled('observation') ? 'Observation' : 'Remark';

                    $insertData = [
                        'remark_type'     => $remark_type,
                        'remark_desc'     => $request->input('remark_desc'),
                        'remark_subject'  => $request->input('remark_subject'),
                        'class_id'        => $request->input('class_id'),
                        'section_id'      => $request->input('section_id'),
                        'subject_id'      => $request->input('subject_id'),
                        'teacher_id'      => $user->reg_id,
                        'academic_yr'     => $customClaims,
                        'remark_date'     => \Carbon\Carbon::parse($request->input('remark_date'))->format('Y-m-d'),
                        'publish_date'=> \Carbon\Carbon::today()->toDateString(), 
                        'publish'         => 'Y',
                        'acknowledge'     => 'N',
                        'student_id'      => $studentId,
                    ];
                    
                     $remarkId = DB::table('remark')->insertGetId($insertData);

                        foreach ($files as $file) {
                            if ($file && $file->isValid()) {
                                $filename = $file->getClientOriginalName();
                                $folder = 'remark/' . \Carbon\Carbon::parse($request->input('remark_date'))->format('Y-m-d') . '/' . $studentId;
                                $path = $file->storeAs($folder, $filename, 'public');
                                $filesize = $file->getSize();
                
                                DB::table('remark_detail')->insert([
                                    'remark_id'  => $remarkId,
                                    'image_name' => $filename,
                                    'file_size'  => $filesize,
                                ]);
                            }
                        
                    }
                    $studentcontactdata = DB::table('student as a')
                                ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                                ->where('a.student_id', $studentId)
                                ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id')
                                ->first();
                    $phone = $studentcontactdata->phone_no;
                    if($phone){
                        $templateName = 'emergency_message';
                        $parameters =["Parent,".$request->input('remark_desc')];
                    
                        $result = $this->whatsAppService->sendTextMessage(
                            $phone,
                            $templateName,
                            $parameters
                        );
                        if (isset($result['code']) && isset($result['message'])) {
                                            Log::warning("Rate limit hit", []);
                        } 
                        else {
                            DB::table('redington_webhook_details')->insert([
                                        'wa_id' => $result['messages'][0]['id'] ?? null,
                                        'phone_no' => $result['contacts'][0]['input'] ?? $phone,
                                        'stu_teacher_id' => $studentId,
                                        'notice_id' => $remarkId,
                                        'message_type' => 'remarkforstudent',
                                        'created_at' => now()
                                    ]);
                            
                        }
                        
                    }
                    $tokenData = getTokenDataParentId($studentId);
                    // dd($tokenData);
            
                    foreach ($tokenData as $item) {
                        if (!empty($item->token)) {
                            // DB::table('daily_notifications')->insert([
                            //     'student_id'        => $item->student_id,
                            //     'parent_id'         => $item->parent_teacher_id,
                            //     'homework_id'       => 0,
                            //     'remark_id'         => $remark_id,
                            //     'notice_id'         => 0,
                            //     'notes_id'          => 0,
                            //     'notification_date' => now()->toDateString(), // YYYY-MM-DD
                            //     'token'             => $item->token,
                            // ]);
                        }
                        $data = [
                            'token' => $item->token, // FCM token of parent/student device
                            'notification' => [
                                'title' => 'Remark',
                                'description' =>$request->input('remark_desc'),
                            ]
                        ];
                    
                      sendnotificationusinghttpv1($data);
                    }
                }
                sleep(10);
                return response()->json([
                    'status' =>200,
                    'message' => 'Remark saved and published successfully!',
                    'success'=>true
                   ]);
                 
             }
                
             
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            } 
         
     }
     
     
     public function getRemarkObservationListForStudents(Request $request){
      try{
         $globalVariables = App::make('global_variables');
         $parent_app_url = $globalVariables['parent_app_url'];
         $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         $remarks = DB::table('remark')
                        ->join('class', 'class.class_id', '=', 'remark.class_id')
                        ->join('section', 'section.section_id', '=', 'remark.section_id')
                        ->join('student', 'student.student_id', '=', 'remark.student_id')
                        ->leftJoin('subject_master', 'subject_master.sm_id', '=', 'remark.subject_id')
                        ->where('remark.academic_yr', $customClaims)
                        ->where('remark.teacher_id', $user->reg_id)
                        ->where('remark.isDelete', 'N')
                        ->select(
                            'remark.*',
                            'class.name as classname',
                            'section.name as sectionname',
                            'student.first_name',
                            'student.mid_name',
                            'student.last_name',
                            'subject_master.name as subjectname'
                        )
                        ->get();

                        // Step 2: Fetch all remark_detail entries related to these remarks
                        $remarkIds = $remarks->pluck('remark_id')->toArray();
                        
                        $files = DB::table('remark_detail')
                            ->whereIn('remark_id', $remarkIds)
                            ->get()
                            ->groupBy('remark_id');
                        
                        // Step 3: Attach multiple file URLs to each remark
                        $remarks->transform(function ($remark) use ($files, $codeigniter_app_url) {
                            $dateFolder = Carbon::parse($remark->remark_date)->format('Y-m-d');
                        
                            $remark->files = collect($files[$remark->remark_id] ?? [])->map(function ($file) use ($remark, $codeigniter_app_url, $dateFolder) {
                                return [
                                    'image_name' => $file->image_name,
                                    'file_size'  => $file->file_size,
                                    'file_url'   => $codeigniter_app_url . "uploads/remark/{$dateFolder}/{$remark->remark_id}/{$file->image_name}"
                                ];
                            });
                        
                            return $remark;
                        });
                            return response()->json([
                    'status' =>200,
                    'data'=>$remarks,
                    'message' => 'Remark list!',
                    'success'=>true
                   ]);
        }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            } 
     }
     
     public function deleteRemarkObservationForStudents(Request $request,$remark_id){
         try{
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
    
        
        $remark = DB::table('remark')->where('remark_id', $remark_id)->first();
    
        if ($remark && $remark->publish === 'Y') {
            
            DB::table('remark')->where('remark_id', $remark_id)->update([
                'isDelete' => 'Y'
            ]);
        } else {
            
            DB::table('remark_detail')->where('remark_id', $remark_id)->delete();
            DB::table('remark')->where('remark_id', $remark_id)->delete();
        }
    
        return response()->json([
                    'status' =>200,
                    'message' => 'Remark deleted successfully!',
                    'success'=>true
                   ]);
         
         }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        } 
         
     }
     
     public function updatepublishRemarkObservationForStudent(Request $request,$remark_id){
         try{
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         DB::table('remark')
            ->where('remark_id', $remark_id)
            ->update([
                'publish'      => 'Y',
                'publish_date' => now()->toDateString(), // 'Y-m-d' format
            ]);
            
        $remarkdata = DB::table('remark')->where('remark_id',$remark_id)->first();
            
        $studentcontactdata = DB::table('student as a')
                                ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                                ->where('a.student_id', $remarkdata->student_id)
                                ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id')
                                ->first(); 
        // dd($studentcontactdata);
        $phone = $studentcontactdata->phone_no;
        if($phone){
            $templateName = 'emergency_message';
            $parameters =[$remarkdata->remark_desc];
        
            $result = $this->whatsAppService->sendTextMessage(
                $phone,
                $templateName,
                $parameters
            );
            if (isset($result['code']) && isset($result['message'])) {
                                Log::warning("Rate limit hit", []);
            } 
            else {
                DB::table('redington_webhook_details')->insert([
                            'wa_id' => $result['messages'][0]['id'] ?? null,
                            'phone_no' => $result['contacts'][0]['input'] ?? $phone,
                            'stu_teacher_id' => $remarkdata->student_id,
                            'notice_id' => $remarkdata->remark_id,
                            'message_type' => 'remarkforstudent',
                            'created_at' => now()
                        ]);
                
            }
            
        }
        
        $tokenData = getTokenDataParentId($remarkdata->student_id);
        // dd($tokenData);

        foreach ($tokenData as $item) {
            if (!empty($item->token)) {
                // DB::table('daily_notifications')->insert([
                //     'student_id'        => $item->student_id,
                //     'parent_id'         => $item->parent_teacher_id,
                //     'homework_id'       => 0,
                //     'remark_id'         => $remark_id,
                //     'notice_id'         => 0,
                //     'notes_id'          => 0,
                //     'notification_date' => now()->toDateString(), // YYYY-MM-DD
                //     'token'             => $item->token,
                // ]);
            }
            $data = [
                'token' => $item->token, // FCM token of parent/student device
                'notification' => [
                    'title' => 'Remark',
                    'description' => $remarkdata->remark_desc,
                ]
            ];
        
          sendnotificationusinghttpv1($data);
        }
        
        
        $failedsms = DB::table('redington_webhook_details')
                        ->where('notice_id',$remarkdata->remark_id)
                        ->where('stu_teacher_id',$remarkdata->student_id)
                        ->where('sms_sent','N')
                        ->where('message_type','remarkforstudent')
                        ->where('status','failed')
                        ->first();
        if($failedsms){
            $smsData = DB::table('daily_sms')
                    ->where('parent_id', $studentcontactdata->parent_id)
                    ->where('student_id', $remarkdata->student_id)
                    ->first();
                
                if (!$smsData) {
                    // Insert new record
                    DB::table('daily_sms')->insert([
                        'student_id'   => $remarkdata->student_id,
                        'parent_id'    => $studentcontactdata->parent_id,
                        'phone'        => $phone,
                        'homework'     => 0,
                        'remark'       => 1,
                        'notice'       => 0,
                        'note'         => 0,
                        'achievement'  => 0,
                        'sms_date'     => now()->format('Y-m-d H:i:s'),
                    ]);
                } else {
                    // Update existing record (increment remark)
                    DB::table('daily_sms')
                        ->where('parent_id', $studentcontactdata->parent_id)
                        ->where('student_id', $remarkdata->student_id)
                        ->update([
                            'remark'   => $smsData->remark + 1,
                            'sms_date' => now()->format('Y-m-d H:i:s'),
                        ]);
                }
            
        }
        
        
        
         
         }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        } 
     }
     
     public function updateRemarkObservationForStudent(Request $request,$remark_id){
          try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             //  dd("Hello");
             $remarksubject = $request->remark_subject;
             $remarkdesc = $request->remark_desc;
             $remarkType = $request->remark_type ? 'Observation' : 'Remark';
             $remarktypeexist = DB::table('remark')
                                    ->where('remark_id', $remark_id)
                                    ->first();
            // dd($remarktypeexist);
            $date = Carbon::parse($remarktypeexist->remark_date)->toDateString();
            // dd($date);
             if($remarkType == 'Observation'){
            
             DB::table('remark')
            ->where('remark_id', $remark_id)
            ->update([
                'remark_desc'      => $remarkdesc,
                'remark_subject' => $remarksubject, 
                'remark_type'   =>$remarkType
            ]);
            return response()->json([
                    'status' =>200,
                    'message' => 'Remark updated successfully!',
                    'success'=>true
                   ]);
            
            }
            $filePaths = $request->filenottobedeleted ?? [];
            $trimmedFilePaths = array_map(function($filePath) use ($remarktypeexist, $remark_id,$date) {
                return Str::replaceFirst('storage/app/public/remark/' . $date . '/' . $remark_id . '.', '', $filePath);
            }, $filePaths);
            
            $filesToExclude = $trimmedFilePaths; 

            if (is_string($filesToExclude)) {
                $filesToExclude = explode(',', $filesToExclude);
            }
            if (empty($filesToExclude)) {
                $filesToExclude = [];
            }
            $uploadedFiles = $request->file('userfile');
            $updateremark = DB::table('remark')->where('remark_id',$remark_id)->get();
            foreach($updateremark as $remarkupdate){
                        $notice_detail = DB::table('remark_detail')
                                        ->where('remark_id', $remarkupdate->remark_id)
                                        ->whereNotIn('image_name', $filesToExclude)
                                        ->get()
                                        ->toArray();
                                        if(!is_null($uploadedFiles)){
                                            $filenames = [];
                                            $datafiles = [];
                                    
                                            foreach ($uploadedFiles as $file) {
                                                $filenames[] = $file->getClientOriginalName();
                                                $datafiles[] = base64_encode(file_get_contents($file->getRealPath()));
                                            }
                                    
                                            
                                            $uploadDate = now()->format('d-m-Y');  // Get today's date
                                            $docTypeFolder = 'remark';
                                            $noticeId = $remarkupdate->remark_id;
                                            
                                            
                                            // Call the helper function to upload the files
                                            $response = upload_files_for_laravel($filenames, $datafiles, $uploadDate, $docTypeFolder, $noticeId);
                                            
                        
                                        }                                        
                    }
                    $notice_detail = array_filter($notice_detail, function($value) {
                        return !empty($value); // Remove empty arrays
                    });
                    
                    
                    $notice_detail = array_values($notice_detail);
                    $imageNames = array_map(function ($item) {
                        return $item->image_name;
                    }, $notice_detail);
                    
                    // If you prefer to use Laravel collection's pluck method, you can convert to collection first:
                    $noticeimagesCollection = collect($notice_detail);
                    $imageNames = $noticeimagesCollection->pluck('image_name')->toArray();
                    $uploadDate = '2025-02-23';
                    $docTypeFolder='teacher_notice';
                        // foreach($updatesmsnotice as $noticeid){                          
                        //   delete_uploaded_files_for_laravel ($imageNames,$uploadDate, $docTypeFolder, $noticeid->t_notice_id);
                        // }
                    
                      // Check if there are any notice details
                    if ($notice_detail) {
                        // Loop through each notice detail and delete the files
                        foreach ($notice_detail as $row) {
                            foreach($updateremark as $noticeid){
                            $path = storage_path("app/public/teacher_notice/{$date}/{$noticeid->remark_id}/{$row->image_name}");
                            // dd($path);
                            // Check if the file exists and delete it
                            if (File::exists($path)) {
                                File::delete($path); // Delete the file
                            }
                           }
                        }
                    }
                    foreach($updateremark as $noticeid){
                        $notice_detail = DB::table('remark_detail')
                                        ->where('remark_id', $noticeid->remark_id)
                                        ->whereNotIn('image_name', $filesToExclude)
                                        ->delete();
                    }
                    foreach ($updateremark as $notice) {
                        DB::table('remark')
                            ->where('remark_id', $notice->remark_id) // Find each notice by its unique ID
                            ->update([
                                'remark_desc'      => $remarkdesc,
                                'remark_subject' => $remarksubject, 
                                'remark_type'   =>$remarkType
                            ]);
                        }

                    
                    $uploadedFiles = $request->file('userfile');
                    if(is_null($uploadedFiles)){
                        return response()->json([
                            'status'=> 200,
                            'message'=>'Remark updated Successfully!',
                            'success'=>true
                            ]);
                    }
                    
                    
                    return response()->json([
                            'status'=> 200,
                            'message'=>'Remark updated successfully!',
                            'success'=>true
                            ]);
            // dd($filesToExclude);
            
            
         
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            } 
         
     }
     
     public function getSubjectAllotedToTeacherByClass(Request $request,$class_id,$section_id){
        
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         $subjects = DB::table('subject')
                        ->select('subject.*', 'subject_master.*') // or be specific with columns
                        ->join('subject_master', 'subject.sm_id', '=', 'subject_master.sm_id')
                        ->where('subject.class_id', $class_id)
                        ->where('subject.section_id', $section_id)
                        ->where('subject.teacher_id', $user->reg_id)
                        ->where('subject.academic_yr', $customClaims)
                        ->orderBy('subject.class_id', 'asc')
                        ->orderBy('subject.section_id', 'asc')
                        ->get()
                        ->toArray();
                        return response()->json([
                            'status' =>200,
                            'data'=>$subjects,
                            'message' => 'Subject by class section teacher!',
                            'success'=>true
                           ]);
         
     }
     
     public function getSubjectByClassSection(Request $request,$class_id,$section_id){
        //  dd($class_id,$section_id);
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         $subjects = DB::table('subject')
                        ->join('subject_master', 'subject_master.sm_id', '=', 'subject.sm_id')
                        ->select('subject_master.sm_id', 'subject_master.name')
                        ->where('subject.class_id', $class_id)
                        ->where('subject.academic_yr', $customClaims)
                        ->where('subject.section_id', 'like', "%{$section_id}%") // handles `like` as in CodeIgniter
                        ->distinct()
                        ->orderBy('subject.class_id', 'asc')
                        ->orderBy('subject.section_id', 'asc')
                        ->orderBy('subject_master.name', 'asc')
                        ->get();
                        
                        return response()->json([
                    'status' =>200,
                    'data'=>$subjects,
                    'message' => 'Subject by class section!',
                    'success'=>true
                   ]);

         
     }
     
     public function saveAllotSpecialRole(Request $request){
          try{
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         $teacherId    = $request->teacher_id;
         $departmentId = $request->department_id;
         $role         = $request->role;
    
        
            $existing = DB::table('department_special_role')
                ->where('teacher_id', $teacherId)
                ->where('department_id', $departmentId)
                ->where('academic_yr', $customClaims)
                ->exists();
    
            if ($existing) {
                return response()->json([
                    'status' =>400,
                    'message' => 'Special Role is already allotted for this Department!',
                    'success'=>false
                   ]);
            }
    
            DB::table('department_special_role')->insert([
                'teacher_id'    => $teacherId,
                'department_id' => $departmentId,
                'role'          => $role,
                'academic_yr'   => $customClaims,
            ]);
            
            return response()->json([
                    'status' =>200,
                    'message' => 'Special Role allotment done!',
                    'success'=>true
                   ]);
         
          }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        } 
         
     }
     
     public function getSpecialrolelist(Request $request){
         try{
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         $specialrolelist = DB::table('department_special_role')
                                 ->join('department','department.department_id','=','department_special_role.department_id')
                                 ->join('teacher','teacher.teacher_id','=','department_special_role.teacher_id')
                                 ->select('department_special_role.*','teacher.name as teachername','department.name as departmentname')
                                 ->where('department_special_role.academic_yr',$customClaims)
                                 ->get();
         return response()->json([
                    'status' =>200,
                    'data'=>$specialrolelist,
                    'message' => 'Special Role list!',
                    'success'=>true
                   ]);
         
         }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        } 
         
     }
     
     public function deleteSpecialrolelist(Request $request,$special_role_id){
         try{
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         
         $deletespecialrole = DB::table('department_special_role')->where('special_role_id',$special_role_id)->delete();
         return response()->json([
                    'status' =>200,
                    'message' => 'Special Role deleted successfully!',
                    'success'=>true
                   ]);
         
         
         
         }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
         
     }
     
     public function getSpecialRole(Request $request){
         try{
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         $specialroles = DB::table('special_role_master')->get();
         return response()->json([
                    'status' =>200,
                    'data'=>$specialroles,
                    'message' => 'Role list!',
                    'success'=>true
                   ]);
         
         
         
         }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
         
     }
     
     public function updateallotspecialrole(Request $request,$special_role_id){
         try{
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         
         
         DB::table('department_special_role')
                ->where('special_role_id', $special_role_id)
                ->update([
                    'teacher_id' => $request->teacher_id,
                    'role'       => $request->role,
                ]);
                
                return response()->json([
                    'status' =>200,
                    'message' => 'Special Role updated successfully!',
                    'success'=>true
                   ]);
         
         
         }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
         
         
     }
     
     public function getAllStaffwithoutCaretaker(Request $request){
         try{
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         
         
         $teacherlist = DB::table('teacher')->where('isDelete','N')->where('designation','!=','Caretaker')->get();
                
                return response()->json([
                    'status' =>200,
                    'data'=>$teacherlist,
                    'message' => 'Staff list!',
                    'success'=>true
                   ]);
         
         
         }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
         
     }
     
     public function saveClassTeacherSubstitute(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $class_teacher_id = $request->input('class_teacher_id');
                 $sub_teacher_id = $request->input('sub_teacher_id');
                 $start_date = $request->input('start_date');
                 $end_date = $request->input('end_date');
                 $class = DB::table('class_teachers')
                            ->join('class', 'class.class_id', '=', 'class_teachers.class_id')
                            ->join('section', 'section.section_id', '=', 'class_teachers.section_id')
                            ->where('class_teachers.teacher_id', $class_teacher_id)
                            ->where('class_teachers.academic_yr', $customClaims)
                            ->select('class.name as classname', 'section.name as sectionname')
                            ->first();
                //  dd($class);
                
                        if ($class) {
                            $classSec = $class->classname . '-' . $class->sectionname;
                            // dd($classSec);
                
                            DB::table('class_teacher_substitute')->insert([
                                'class_teacher_id' => $class_teacher_id,
                                'teacher_id'       => $sub_teacher_id,
                                'start_date'       => $start_date,
                                'end_date'         => $end_date,
                                'academic_yr'      => $customClaims,
                            ]);
                            
                            return response()->json([
                                'status' =>200,
                                'message' => 'A substitute teacher is appointed for class '.$classSec.'.!',
                                'success'=>true
                               ]);
                            
                        }
                        
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     
     public function getClassTeachers(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $classteachers = DB::table('class_teachers')
                                    ->join('teacher', 'class_teachers.teacher_id', '=', 'teacher.teacher_id')
                                    ->where('class_teachers.academic_yr', $customClaims)
                                    ->orderBy('class_teachers.class_id', 'ASC')
                                    ->select('class_teachers.*', 'teacher.teacher_id', 'teacher.name')
                                    ->get()
                                    ->toArray();
                                    
                                    return response()->json([
                                        'status' =>200,
                                        'data' => $classteachers,
                                        'message' => 'Class teachers list!',
                                        'success'=>true
                                       ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     
     public function getNonClassTeachers(Request $request){
          try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $roles = ['T', 'L'];

                $subquery = DB::table('class_teachers')
                    ->select('teacher_id')
                    ->where('academic_yr', $customClaims);
            
                $nonclassteachers =  DB::table('teacher')
                                        ->join('user_master', 'teacher.teacher_id', '=', 'user_master.reg_id')
                                        ->whereNotIn('teacher.teacher_id', $subquery)
                                        ->whereIn('user_master.role_id', $roles)
                                        ->select('teacher.*', 'user_master.role_id') 
                                        ->get()
                                        ->toArray();
                                        
                                         return response()->json([
                                        'status' =>200,
                                        'data' => $nonclassteachers,
                                        'message' => 'Non class teachers list!',
                                        'success'=>true
                                       ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
                 
            
         
     }
     
     public function getsubstituteClassTeacherList(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $substituteTeacherList = DB::table('class_teacher_substitute')
                                            ->join('teacher as main_teacher', 'main_teacher.teacher_id', '=', 'class_teacher_substitute.class_teacher_id')
                                            ->join('teacher as sub_teacher', 'sub_teacher.teacher_id', '=', 'class_teacher_substitute.teacher_id')       
                                            ->where('class_teacher_substitute.academic_yr', $customClaims)
                                            ->select(
                                                'class_teacher_substitute.*',
                                                'main_teacher.name as class_teacher_name',
                                                'sub_teacher.name as substitute_teacher_name'
                                            )
                                            ->get();
                                            foreach ($substituteTeacherList as $substituteTeacher){
                                                 $class = DB::table('class_teachers')
                                                              ->join('class','class.class_id','=','class_teachers.class_id')
                                                              ->join('section','section.section_id','=','class_teachers.section_id')
                                                              ->where('class_teachers.teacher_id',$substituteTeacher->class_teacher_id)
                                                              ->where('class_teachers.academic_yr',$customClaims)
                                                              ->select('class.class_id','section.section_id','class.name as classname','section.name as sectionname')
                                                              ->first();
                                                  $substituteTeacher->class_id = $class->class_id ?? null;
                                                    $substituteTeacher->classname = $class->classname ?? null;
                                                    $substituteTeacher->section_id = $class->section_id ?? null;
                                                    $substituteTeacher->sectionname = $class->sectionname ?? null;
                                            }
                                              return response()->json([
                                                    'status' =>200,
                                                    'data' => $substituteTeacherList,
                                                    'message' => 'Substitute class teachers list!',
                                                    'success'=>true
                                                   ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     
     public function updateClassTeacherSubstitute(Request $request,$class_substitute_id){
          try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $class_teacher_id = $request->input('class_teacher_id');
                $sub_teacher_id = $request->input('sub_teacher_id');
                $start_date = $request->input('start_date');
                $end_date = $request->input('end_date');
            
                $class = DB::table('class_teachers')
                    ->join('class', 'class.class_id', '=', 'class_teachers.class_id')
                    ->join('section', 'section.section_id', '=', 'class_teachers.section_id')
                    ->where('class_teachers.teacher_id', $class_teacher_id)
                    ->where('class_teachers.academic_yr', $customClaims)
                    ->select('class.name as classname', 'section.name as sectionname')
                    ->first();
            
                if ($class) {
                    $classSec = $class->classname . '-' . $class->sectionname;
            
                    // Perform update
                    $updated = DB::table('class_teacher_substitute')
                        ->where('academic_yr', $customClaims)
                        ->where('class_substitute_id', $class_substitute_id)
                        ->update([
                            'class_teacher_id'=>$class_teacher_id,
                            'teacher_id'  => $sub_teacher_id,
                            'start_date'  => $start_date,
                            'end_date'    => $end_date,
                        ]);
            
                    
                        return response()->json([
                            'status' => 200,
                            'message' => 'Substitute teacher updated for class ' . $classSec . '!',
                            'success' => true
                        ]);
                    
                }
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     
     public function deleteSubstituteClassTeacher(Request $request,$class_substitute_id){
          try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $deleted = DB::table('class_teacher_substitute')
                            ->where('class_substitute_id', $class_substitute_id)
                            ->delete();
                            
                            return response()->json([
                            'status' => 200,
                            'message' => 'A substitute teacher is deleted!',
                            'success' => true
                        ]);
                            
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     
     public function getFeesCategory(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $feescategory = DB::table('fees_category')
                                        ->join('fees_category_detail', 'fees_category_detail.fees_category_id', '=', 'fees_category.fees_category_id')
                                        ->join('class', 'class.class_id', '=', 'fees_category_detail.class_concession')
                                        ->where('fees_category.academic_yr', $customClaims)
                                        ->groupBy('fees_category.fees_category_id') 
                                        ->orderBy('fees_category.fees_category_id', 'ASC')
                                        ->select(
                                            'fees_category.fees_category_id',
                                            'fees_category.name',
                                            'fees_category.academic_yr',
                                            DB::raw('GROUP_CONCAT(class.name SEPARATOR ", ") as classnames')
                                        )
                                        ->get()
                                        ->toArray();
                                    return response()->json([
                                        'status' => 200,
                                        'data'=>$feescategory,
                                        'message' => 'Fees Category!',
                                        'success' => true
                                    ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }

      public function getFeesCategoryStudentAllotmentView(Request $request){
          try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $section_id = $request->input('section_id');
                //  dd($section_id);
                 $class_id = DB::table('section')
                                 ->where('section_id',$section_id)
                                 ->first();
                                //  dd($class_id);
                 $data = getFeesCategoryStudentAllotment($class_id->class_id,$section_id,$customClaims);
                 return response()->json([
                                        'status' => 200,
                                        'data'=>$data,
                                        'message' => 'Fees category student allotment view!',
                                        'success' => true
                                    ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     
     public function getFeesCategoryAllotmentView(Request $request){
          try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $allotments = getFeesAllotment($customClaims);

                $result = [];
            
                foreach ($allotments as $row) {
                    $feeAllotmentId = $row->fee_allotment_id;
                    $categoryName = getFeesCategoryName($row->fees_category_id, $customClaims);
                    $admissionFee = getAdmissionFee($feeAllotmentId, $customClaims);
                    $installment1 = getInstallmentAmount($feeAllotmentId, 1, $customClaims);
                    $installment2 = getInstallmentAmount($feeAllotmentId, 2, $customClaims);
                    $installment3 = getInstallmentAmount($feeAllotmentId, 3, $customClaims);
            
                    $total = floatval($admissionFee) + floatval($installment1) + floatval($installment2) + floatval($installment3);
            
                    $result[] = [
                        'category'      => $categoryName,
                        'admission_fee'=> round($admissionFee, 2),
                        'installment_1'=> round($installment1, 2),
                        'installment_2'=> round($installment2, 2),
                        'installment_3'=> round($installment3, 2),
                        'total'        => round($total, 2),
                    ];
                }
            
                return response()->json([
                    'status' => 200,
                    'data' => $result,
                    'message'=>'Fees category allotment view!',
                    'success' => true
                    
                ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     
     public function getFeesCategoryAllotmentInstallment(Request $request){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $feeAllotmentId = $request->input('fee_allotment_id');
                $installment = $request->input('installment');
            
                
                $dueDate = DB::table('fees_allotment_detail')
                    ->where('fee_allotment_id', $feeAllotmentId)
                    ->where('installment', $installment)
                    ->where('academic_yr',$customClaims)
                    ->value('due_date');
            
                
                $hasPayment = DB::table('fees_payment_record')
                    ->where('fee_allotment_id', $feeAllotmentId)
                    ->where('academic_yr', $customClaims)
                    ->where('isCancel', '<>', 'Y')
                    ->exists();
            
                $readonly = $hasPayment;
            
                
                $feeTypes = DB::table('fee_type_master')->get();
            
                
                $feeData = [];
                $total = 0.00;
                $index = 0;
            
                foreach ($feeTypes as $type) {
                    // $amount = DB::table('fees_allotment as a')
                    //             ->join('fees_allotment_detail as b', function ($join) {
                    //                 $join->on('a.fee_allotment_id', '=', 'b.fee_allotment_id')
                    //                      ->on('a.academic_yr', '=', 'b.academic_yr');
                    //             })
                    //             ->where('b.fee_allotment_id', $feeAllotmentId)
                    //             ->where('b.installment', $installment)
                    //             ->where('a.academic_yr', $customClaims)
                    //             ->where('bfee_type_id', $type->fee_type_id)
                    //             ->select('a.*', 'b.installment', DB::raw('SUM(b.amount) as installment_fees'))
                    //             ->groupBy('b.installment')
                    //             ->value('amount') ?? 0.00;
                    $amount = DB::table('fees_allotment_detail')
                        ->where('fee_allotment_id', $feeAllotmentId)
                        ->where('installment', $installment)
                        ->where('fee_type_id', $type->fee_type_id)
                        ->value('amount') ?? 0.00;
            
                    $total += floatval($amount);
                    $index++;
            
                    $feeData[] = [
                        'index'        => $index,
                        'fee_type_id'  => $type->fee_type_id,
                        'name'         => $type->name,
                        'amount'       => round($amount, 2),
                    ];
                }
            
                // Final response
                return response()->json([
                    'status' => 200,
                    'data' => [
                        'due_date'     => $dueDate ? date('d-m-Y', strtotime($dueDate)) : null,
                        'readonly'     => $readonly,
                        'fee_details'  => $feeData,
                        'total'        => round($total, 2),
                        'count_types'  => $index,
                    ],
                    'message'=>'Fees category allotment installment!',
                    'success'=>true
                ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
     }
     
     public function getFeesCategoryInstallmentDropdown(Request $request,$feesCategoryId, $selected = null){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 $options = [];

                for ($i = 1; $i <= 3; $i++) {
                    $data = DB::table('fees_allotment as a')
                                ->join('fees_allotment_detail as b', function ($join) {
                                    $join->on('a.fee_allotment_id', '=', 'b.fee_allotment_id')
                                         ->on('a.academic_yr', '=', 'b.academic_yr');
                                })
                                ->where('a.fees_category_id', $feesCategoryId)
                                ->where('b.installment', $i)
                                ->where('a.academic_yr', $customClaims)
                                ->select('a.*', 'b.installment', DB::raw('SUM(b.amount) as installment_fees'))
                                ->groupBy('b.installment')
                                ->first();
                    // // $data = DB::table('fees_allotment_detail')
                    // //     ->join('fees_allotment','fees_allotment.fee_allotment_id','=','fees_allotment_detail.fee_allotment_id')
                    // //     ->where('fees_allotment_detail.fee_allotment_id', $feesCategoryId)
                    // //     ->where('fees_allotment_detail.installment', $i)
                    // //     ->where('fees_allotment_detail.academic_yr',$customClaims)
                    // //     ->where('fees_allotment_detail.academic_yr','=','fees_allotment.academic_yr')
                    // //     ->select('fees_allotment_detail.installment', DB::raw('SUM(amount) as installment_fees'))
                    // //     ->groupBy('fees_allotment_detail.installment')
                    // //     ->first();
                    //   dd($data);
                    
                        $options[] = [
                            'value'    => $data->installment,
                            'label'    => "Installment no. {$data->installment} ({$data->installment_fees})",
                            'fee_allotment_id'=>$data->fee_allotment_id
                        ];
                   
                }
            
                return response()->json([
                    'status' => 200,
                    'data' => $options,
                    'message'=>'Fees category installment dropdown!',
                    'success'=>true
                    ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     
     public function downloadTicketFiles(Request $request,$ticket_id,$comment_id,$name){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                //  dd($ticket_id,$comment_id,$name);
                $globalVariables = App::make('global_variables');
                $parent_app_url = $globalVariables['parent_app_url'];
                $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
                // $filePath = public_path('uploads/ticket/' . $ticket_id . '/' . $comment_id . '/' . $name);
                // dd($filePath);
               if (str_contains($codeigniter_app_url, 'SACSv4test')) {
                        $filePath = '/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/SACSv4test/uploads/ticket/' . $ticket_id . '/' . $comment_id . '/' . $name;
                    } else {
                        $filePath ='/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/uploads/ticket/' . $ticket_id . '/' . $comment_id . '/' . $name;
                    }
                //  dd($filePath);
                //  dd($filePath);
                // $file = fopen($filePath, 'r');
                
                if (File::exists($filePath)) {
                    // Get MIME type (example: image/png, application/pdf, etc.)
                    $mime = File::mimeType($filePath);
            
                    return response()->file($filePath, [
                        'Content-Type' => $mime,
                        'Content-Disposition' => 'inline; filename="' . $name . '"'
                    ]);
                }
            
                return response()->json(['error' => 'File not found.'], 404);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }
     
     public function getSendSMSForFeesPendingData(Request $request,$class_id,$installment){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                
                
                  $results = DB::select("SELECT student_installment,s.student_id, s.installment, fees_category_name, first_name, last_name, roll_no, section_id, installment_fees, COALESCE(SUM(d.amount), 0) AS concession, 0 AS paid_amount,(installment_fees - COALESCE(SUM(d.amount), 0) ) AS pending_fee FROM view_student_fees_category s LEFT JOIN fee_concession_details d ON s.student_id = d.student_id AND s.installment = d.installment WHERE s.academic_yr = '".$customClaims."' and s.class_id=".$class_id." and s.installment<>4 and s.installment like '".$installment."%' AND due_date < CURDATE() AND s.student_installment NOT IN (SELECT student_installment FROM view_student_fees_payment a WHERE a.academic_yr = '".$customClaims."' and a.class_id=".$class_id." and installment like '".$installment."%') GROUP BY s.student_id, s.installment UNION SELECT concat(f.student_id,'^',b.installment) as student_installment,f.student_id AS student_id, b.installment AS installment,b.category_name as fees_category_name, first_name, last_name, roll_no, section_id, b.installment_fees, COALESCE(SUM(c.amount), 0) AS concession, SUM(f.fees_paid) AS paid_amount,(installment_fees - COALESCE(SUM(c.amount), 0) - SUM(f.fees_paid)) AS pending_fee FROM view_student_fees_payment f LEFT JOIN fee_concession_details c ON f.student_id = c.student_id AND f.installment = c.installment JOIN view_fee_allotment b ON f.fee_allotment_id = b.fee_allotment_id AND b.installment = f.installment JOIN student e ON f.student_id=e.student_id WHERE b.installment<>4 and f.academic_yr = '".$customClaims."'  and f.class_id=".$class_id." and f.installment like '".$installment."%' GROUP BY f.installment, c.installment HAVING (b.installment_fees - COALESCE(SUM(c.amount), 0)) > SUM(f.fees_paid)");
                  foreach($results as $result){
                      $contactData = DB::table('student as a')
                            ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                            ->where('a.student_id', $result->student_id)
                            ->select('b.phone_no', 'b.email_id')
                            ->first();
                    
                        $result->phone_no = $contactData->phone_no ?? '';
                        $concession = DB::select(
                                "SELECT SUM(amount) as installment_concession 
                                 FROM fee_concession_details 
                                 WHERE student_id = ? 
                                 AND installment LIKE ? 
                                 AND academic_yr = ? 
                                 GROUP BY installment",
                                [$result->student_id, $installment . '%', $customClaims]
                            );
                                        // dd( isset($concession[0]) ? $concession[0]->installment_concession : 0, $result->student_id);
                       $result->actualinstallmentamt = $result->installment_fees - (isset($concession[0]) ? $concession[0]->installment_concession : 0);
                       $smssent=DB::table('sms_log_for_outstanding_fees')
                                    ->where('student_id', $result->student_id)
                                    ->where('installment','like', $installment)
                                    ->where('academic_yr', $customClaims)
                                    ->first();
                                    // dd($smssent);
                        
                        $result->smscount = $smssent->count_of_sms ?? null;
                        $result->smslogid = $smssent->sms_log_id ?? null;
                        $result->smssentdates = isset($result->smslogid)
                                ? DB::table('sms_log_for_outstanding_fees_details')
                                    ->select('date_sms_sent')
                                    ->where('sms_log_id', $result->smslogid)
                                    ->orderBy('date_sms_sent', 'asc')
                                    ->get()
                                    ->toArray()
                                : null;
                        $classname = DB::table('section')
                                        ->join('class','class.class_id','=','section.class_id')
                                        ->where('section.section_id', $result->section_id)
                                        ->select(DB::raw("CONCAT(class.name, ' ', section.name) as classname"))
                                        ->first();
                        $result->classname = $classname->classname;
                         $lastsmsdate = DB::table('sms_log_for_outstanding_fees')
                                ->where('student_id', $result->student_id)
                                ->where('installment','like', $installment)
                                ->where('academic_yr', $customClaims)
                                ->first();
                            
                            if ($lastsmsdate && $lastsmsdate->date_last_sms_sent) {
                                $lastsmsdate= \Carbon\Carbon::parse($lastsmsdate->date_last_sms_sent)->format('d-m-Y');
                            }
                            else{
                                $lastsmsdate= null;
                                
                            }
                            // dd($lastsmsdate);
                        $result->lastsmsdate = $lastsmsdate;
                            
                            
                        
    
                      
                  }
                  return response()->json([
                    'status' => 200,
                    'data' => $results,
                    'message'=>'Fee outstanding fees data!',
                    'success'=>true
                    ]);
                 
             }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
         
     }

     public function SendSMSForFeesPending(Request $request){
        try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
              $studentid_installment = $request->studentid_installment;
              $message = $request->message;
              SendOutstandingFeeSmsJob::dispatch($studentid_installment,$customClaims,$message);

              return response()->json([
                    'status' => 200,
                    'message'=>'Fee outstanding messages sent!',
                    'success'=>true
                    ]);
            }
             else
                 {
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the getting of department list.',
                        'data' =>$user->role_id,
                        'success'=>false
                        ]);
                    }

               }
              catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }
     }
     
     public function getTeacherAllSubjects(Request $request){
         $user = $this->authenticateUser();
         $customClaims = JWTAuth::getPayload()->get('academic_year');
         $teacherId = $request->input('teacher_id');
            $excludedSubjectIds = DB::table('subjects_excluded_from_curriculum')
                                        ->pluck('sm_id')
                                        ->toArray();
            $subjectdata = DB::table('subject')
                        ->join('subject_master', 'subject_master.sm_id', '=', 'subject.sm_id')
                        ->where('subject.teacher_id', $teacherId)
                        ->where('subject.academic_yr', $customClaims)
                        ->whereNotIn('subject.sm_id', $excludedSubjectIds)
                        ->select('subject_master.name as subjectname', 'subject.*')
                        ->groupBy('subject.sm_id') // Add all selected columns if using groupBy
                        ->get();
                               
                               return response()->json([
                                    'status'=>200,
                                    'data'=>$subjectdata,
                                    'message' => 'Subject Data.',
                                    'success' =>true
                                ]); 
         
     }

     private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }
}
