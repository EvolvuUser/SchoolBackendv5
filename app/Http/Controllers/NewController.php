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

     private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }
}
