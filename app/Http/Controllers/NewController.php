<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use Illuminate\Support\Facades\Validator;
use DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class NewController extends Controller
{
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

     public function saveLeaveApplicationForallstaff(Request $request){
        try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                     $leaveapplication = DB::table('leave_application')->insert([
                        'staff_id'=>$request->staff_id,
                        'leave_type_id'=>$request->leave_type_id,
                        'leave_start_date'=>$request->leave_start_date,
                        'leave_end_date'=>$request->leave_end_date,
                        'no_of_days'=>$request->no_of_days,
                        'approved_by'=>$user->reg_id,
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

                    // Delete the leave application
                    DB::table('leave_application')->where('leave_app_id', $id)->delete();

                    // Update leave_allocation by subtracting no_of_days
                    DB::table('leave_allocation')
                        ->where('staff_id', $staffId)
                        ->where('leave_type_id', $leaveTypeId)
                        ->where('academic_yr', $customClaims)
                        ->decrement('leaves_availed', $noOfDays);

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

     public function updateLeaveApplicationCancel(Request $request,$id){
        try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
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
                            DB::table('teachers_remark')->insert([
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

     public function updatePublishRemarkForTeacher(Request $request,$id){
         try{
             $user = $this->authenticateUser();
             $customClaims = JWTAuth::getPayload()->get('academic_year');
             if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                // $remarkdetails = DB::table('teachers_remark')->where('t_remark_id',$id)->first();
                // // dd($remarkdetails);
                // $teacherdetails = DB::table('teacher')->where('teacher_id',$remarkdetails->teachers_id)->first();
                // // dd($teacherdetails);
                // $teacherphoneno = $teacherdetails->phone;
                // if($teacherphoneno){
                //     $templateName = 'emergency_message';
                //     $parameters =[$remarkdetails->remark_desc];
                //     Log::info($teacherphoneno);
                //     $result = $this->whatsAppService->sendTextMessage(
                //         $teacherphoneno,
                //         $templateName,
                //         $parameters
                //     );
                //     Log::info("Failed message",$result);
                //     if (isset($result['code']) && isset($result['message'])) {
                //         // Handle rate limit error
                //         Log::warning("Rate limit hit: Too many messages to same user", [
                            
                //         ]);
                
                //     } else {
                //         // Proceed if no error
                //         $wamid = $result['messages'][0]['id'];
                //         $phone_no = $result['contacts'][0]['input'];
                //         $message_type = 'teacher_remark';
                
                //         DB::table('redington_webhook_details')->insert([
                //             'wa_id' => $wamid,
                //             'phone_no' => $phone_no,
                //             'stu_teacher_id' => $remarkdetails->teachers_id,
                //             'notice_id' => $id,
                //             'message_type' => $message_type,
                //             'created_at' => now()
                //         ]);
                //     }

                //     sleep(20);

                    

                // }
                 
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

     private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }
}
