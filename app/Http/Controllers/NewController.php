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
                            $filename = $file->getClientOriginalName();
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

     private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }
}
