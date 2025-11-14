<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Models\SubstituteTeacher as SubstituteTeacher1;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Mail\SubstituteTeacherNotification;
use App\Models\LateTime;
use Illuminate\Validation\Rule;
use App\Models\Student;
use Illuminate\Support\Facades\App;
use App\Models\ContactDetails;
use App\Models\UserMaster;
use App\Models\Parents;
use Illuminate\Support\Facades\Http;

class SubstituteTeacher extends Controller
{
    public function getSubstituteTeacherDetails(Request $request,$teacher_id,$date){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                // $day= 'thursday';
                $carbonDate = Carbon::parse($date);
                $day = $carbonDate->format('l'); 
                $query = DB::table('timetable')
                        ->select(
                            'timetable.' . $day . ' as subject',
                            'timetable.class_id',
                            'timetable.section_id',
                            'timetable.period_no',
                            'subject_master.sm_id',
                            'subject.teacher_id',
                            'teacher.name as teacher_name',
                            'class.name as c_name',
                            'section.name as s_name'
                        )
                        ->join('subject_master', 'timetable.' . $day, '=', 'subject_master.name')
                        ->join('subject', function($join) {
                            $join->on('timetable.class_id', '=', 'subject.class_id')
                                ->on('timetable.section_id', '=', 'subject.section_id')
                                ->on('subject_master.sm_id', '=', 'subject.sm_id');
                        })
                        ->join('teacher', 'subject.teacher_id', '=', 'teacher.teacher_id')
                        ->join('class', 'timetable.class_id', '=', 'class.class_id')
                        ->join('section', 'timetable.section_id', '=', 'section.section_id')
                        ->where('subject.teacher_id', $teacher_id)
                         ->where('timetable.academic_yr', $customClaims)
                        ->groupBy('timetable.period_no')
                        ->orderBy('timetable.period_no', 'ASC')
                        ->get();

                        $data['day']=$day;
                        $data['date']=$date;

                        return response()->json([
                            'status'=> 200,
                            'message'=>'Get Substitution Data',
                            'data'=>$query,
                            'data1'=>$data,
                            'success'=>true
                            ]);
                        // $class_name = $query->get()->c_name;                

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Updating of Data',
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


    public function getSubstituteTeacherClasswise(Request $request,$class_name,$period,$date){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                if ($class_name == 'Nursery' || $class_name == 'LKG' || $class_name == 'UKG') {
                    $teacher_group = 'Preprimary';
                } elseif ($class_name == '1' || $class_name == '2' || $class_name == '3' || $class_name == '4') {
                    $teacher_group = 'Primary';
                } elseif ($class_name == '5' || $class_name == '6' || $class_name == '7' || $class_name == '8') {
                    $teacher_group = 'Secondary';
                } elseif ($class_name == '9' || $class_name == '10' || $class_name == '11' || $class_name == '12') {
                    $teacher_group = 'Highschool';
                } else {
                    // Handle case where class is not matched
                    $teacher_group = null; // You can set this to an appropriate default value if needed
                }
                
                $carbonDate = Carbon::parse($date);                        
                $day = $carbonDate->format('l'); 
                
                    $query = DB::table('view_teacher_group')
                        ->select('teacher_id', 'name')
                        // Apply COLLATE directly in the where() clause to resolve collation issue
                        ->where('teacher_group', $teacher_group)
                        // ->whereRaw('teacher_group COLLATE utf8mb4_general_ci LIKE ?', ['%' . $teacher_group . '%'])
                        ->where('academic_yr', $customClaims)
                        ->whereNotIn('teacher_id', function($subquery) use ($day, $period, $teacher_group, $customClaims) {
                            $subquery->select('teacher_id')
                                ->from('view_periodwise_subject_teacher')
                                ->whereRaw("teacher_group COLLATE utf8mb4_unicode_ci LIKE ?", [$day])
                                ->where('period_no', $period)
                                ->whereRaw("teacher_group COLLATE utf8mb4_unicode_ci LIKE ?", [$teacher_group])
                                ->where('academic_yr', $customClaims);
                        })
                        ->whereNotIn('teacher_id', function($subquery) use ($date, $period) {
                            $subquery->select('sub_teacher_id')
                                ->from('substitute_teacher')
                                ->where('date', $date)
                                ->where('period', $period);
                        })->get();
                
                
                // dd($s_id);
                    return response()->json([
                        'status'=> 200,
                        'message'=>'Get Substitution Data',
                        'data'=>$query,
                        'success'=>true
                        ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Updating of Data',
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

    public function saveSubstituteTeacher(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $substitutions = $request->input('substitutions');
            //    dd($substitutions);
                // Loop through each substitution record and insert into the database
                foreach ($substitutions as $data) {
                    if (!empty($data['substitute_teacher_id'])) {
                    SubstituteTeacher1::create([
                        'class_id' => $data['class_id'],
                        'section_id' => $data['section_id'],
                        'subject_id' => $data['subject_id'],
                        'period' => $data['period'],
                        'date' => $data['date'],
                        'teacher_id' => $data['teacher_id'],
                        'sub_teacher_id' => $data['substitute_teacher_id'],
                        'academic_yr'=>$customClaims
                    ]);

                    $classname = DB::table('class')->select('name')->where('class_id',$data['class_id'])->first();  
                    $subjectname = DB::table('subject_master')->where('sm_id',$data['subject_id'])->first();
                    $sectionname = DB::table('section')->where('section_id',$data['section_id'])->first();
                    // dd($classname->name , $subjectname->name , $sectionname->name);
                    $substitutionData = [
                        'date' => $data['date'],
                        'subject_name' => $subjectname->name,
                        'class_name' => $classname->name,
                        'section_name' => $sectionname->name,
                        'period' => $data['period']
                    ];
                    // dd($substitutionData);
                    $teacher_email = DB::table('teacher')->where('teacher_id',$data['substitute_teacher_id'])->first();
                    if ($teacher_email->email) {
                        // dd($teacher_email->email);
                        Mail::to('manishnehwal@gmail.com')->send(new SubstituteTeacherNotification($substitutionData));
                    }
                  } 
                }
                return response()->json([
                    'status'=> 200,
                    'message'=>'Substitution Saved Successfully',
                    'success'=>true
                    ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Updating of Data',
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

    public function getSubstituteTeacherData(Request $request,$teacher_id,$date){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $query = DB::table('substitute_teacher')
                        ->select(
                            'substitute_teacher.*',
                            'subject_master.name as sname',
                            'teacher.name as sub_teacher',
                            'class.name as c_name',
                            'section.name as s_name'
                        )
                        ->join('subject_master', 'substitute_teacher.subject_id', '=', 'subject_master.sm_id')
                        ->join('teacher', 'substitute_teacher.sub_teacher_id', '=', 'teacher.teacher_id')
                        ->join('class', 'substitute_teacher.class_id', '=', 'class.class_id')
                        ->join('section', 'substitute_teacher.section_id', '=', 'section.section_id')
                        ->where('substitute_teacher.teacher_id', $teacher_id)
                        ->where('substitute_teacher.date', $date)
                        ->orderBy('substitute_teacher.period')
                        ->get();

                        $carbonDate = Carbon::parse($date);
                        $dayOfWeek = $carbonDate->format('l');
                        return response()->json([
                            'status'=> 200,
                            'message'=>'Get Substitution Data',
                            'data'=>$query,
                            'day_week'=>$dayOfWeek,
                            'success'=>true
                            ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Updating of Data',
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

    public function updateSubstituteTeacher(Request $request,$teacher_id,$date){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                DB::table('substitute_teacher')
                    ->where('teacher_id', $teacher_id)
                    ->where('date', $date)
                    ->where('academic_yr', $customClaims)
                    ->delete();
                $substitutions = $request->input('substitutions');
            //    dd($substitutions);
                // Loop through each substitution record and insert into the database
                foreach ($substitutions as $data) {
                    SubstituteTeacher1::create([
                        'class_id' => $data['class_id'],
                        'section_id' => $data['section_id'],
                        'subject_id' => $data['subject_id'],
                        'period' => $data['period'],
                        'date' => $data['date'],
                        'teacher_id' => $data['teacher_id'],
                        'sub_teacher_id' => $data['substitute_teacher_id'],
                        'academic_yr'=>$customClaims
                    ]);

                    $classname = DB::table('class')->select('name')->where('class_id',$data['class_id'])->first();  
                    $subjectname = DB::table('subject_master')->where('sm_id',$data['subject_id'])->first();
                    $sectionname = DB::table('section')->where('section_id',$data['section_id'])->first();
                    // dd($classname->name , $subjectname->name , $sectionname->name);
                    $substitutionData = [
                        'date' => $data['date'],
                        'subject_name' => $subjectname->name,
                        'class_name' => $classname->name,
                        'section_name' => $sectionname->name,
                        'period' => $data['period']
                    ];
                    // dd($substitutionData);
                    $teacher_email = DB::table('teacher')->where('teacher_id',$data['substitute_teacher_id'])->first();
                    if ($teacher_email->email) {
                        // dd($teacher_email->email);
                        // Mail::to('manishnehwal@gmail.com')->send(new SubstituteTeacherNotification($substitutionData));
                    }
                }
                return response()->json([
                    'status'=> 200,
                    'message'=>'Substitution Updated Successfully',
                    'success'=>true
                    ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Updating of Data',
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

    public function deleteSubstituteTeacher(Request $request,$teacher_id,$date){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                // dd($customClaims);
                DB::table('substitute_teacher')
                    ->where('teacher_id', $teacher_id)
                    ->where('date', $date)
                    ->where('academic_yr', $customClaims)
                    ->delete();

                    return response()->json([
                        'status'=> 200,
                        'message'=>'Deleted Subsitute',
                        'success'=>true
                        ]);

                

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Updating of Data',
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

    public function saveLateTime(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $tc_id = $request->input('tc_id');

                // Check if the tc_id already exists
                $existingTestCase = LateTime::where('tc_id', $tc_id)->first();

                if ($existingTestCase) {
                    // If tc_id exists, return a response with the message
                    return response()->json([
                        'message' => 'Teacher Category already created',
                        'tc_id' => $tc_id,
                        'status'=> 400,
                        'success'=>false
                    ]);  // 400 means bad request as it's already there
                }

        // If tc_id does not exist, create a new entry
        $newTestCase = LateTime::create([
            'tc_id' => $tc_id,
            'late_time' => $request->input('latetime'),
        ]);

        // Return success response with the created data
        return response()->json([
            'success' => true,
            'message' => 'Late Time created successfully',
            'status' =>201,
            'data'=>$newTestCase
        ]);  // 201 means resource created


                

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Updating of Data',
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


    public function LateTimeList(){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $latetimelist = LateTime::join('teacher_category','late_time.tc_id','=','teacher_category.tc_id')->get();
                return response()->json([
                    'status'=> 200,
                    'message'=>'Late Time List',
                    'data' =>$latetimelist,
                    'success'=>true
                    ]);

            
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Creating of Data',
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


    public function deleteLateTime($lt_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $latetimedelete=LateTime::find($lt_id);
                $latetimedelete->delete();
                return response()->json([
                    'status'=> 200,
                    'message'=>'Late Time Deleted Successfully for this class.',
                    'data' =>$latetimedelete,
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
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
           }

    }


    public function LateTimeData(Request $request,$lt_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $latetimedata=LateTime::find($lt_id);
                return response()->json([
                    'status'=> 200,
                    'message'=>'Late Time Data',
                    'data' =>$latetimedata,
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
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
           }

    }

    public function updateLateTime(Request $request,$lt_id){
        $messages = [
            'tc_id.unique' => 'The teacher category has already been taken.',
        ];
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                try{
                $validatedData = $request->validate([
                    'tc_id' => [
                        Rule::unique('late_time', 'tc_id')->ignore($lt_id, 'lt_id')
                    ]
                ], $messages);
                $updatelatetime = LateTime::find($lt_id);
                $updatelatetime->tc_id = $request->tc_id;
                $updatelatetime->late_time = $request->latetime;
                $updatelatetime->save();

                return response()->json([
                    'status'=> 200,
                    'message'=>'Late Time Updated for this Class',
                    'data' =>$updatelatetime,
                    'success'=>true
                    ]);
                
                

            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'status' => 422,
                    'errors' => $e->errors(),
                ], 422);
            }


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

    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    public function getTeacherListforSubstitution(){
        $teacher = DB::table('teacher')->where('isDelete','N')->orderBy('teacher_id','ASC')->get();
        return response()->json([
                    'status'=> 201,
                    'message'=>'Teacher List',
                    'data' =>$teacher,
                    'success'=>true
                ]);
    }

    public function sendNotification(Request $request){
        // dd("Hello");
        // $token = 'cnMaZq4TRTKG2Sic49524o:APA91bHPc4EJtx4Z7U-Y407B-8JJJUGYHfIIOsytnRJvGsbX88eMolgc9_fynECDlszXfKu7dtPfywa0lbT9GWxV5KkFGe9DPTxgwzAOLq89j7199M3ldqg' ;
        $load = array();
        // dd($load);
        $load['title']  = env('APP_NAME');
        $load['msg']    = 'Testingg';
        $load['action'] = 'CONFIRMED';
        // dd($load);
        $data = [
            'token'=>$request->token,
            'notification'=>[
                'title'=> $request->title,
                'description'=> $request->message,
                'mutable_content'=>true,
                'sound'=>'Tri-tone'
            ],                    
            
        ];
        // dd($data);
        $response= sendnotificationusinghttpv1($data);
        // dd($response);
         $responseData = $response->getData(); 
        if ($responseData->status == 200) {
            return response()->json([
                "status" => 200,
                "message" => "Notification sent successfully.",
                "success" => true,
                "data" => $responseData->data
            ]);
        } else {
            return response()->json([
                "status" => 400,
                "message" => "Failed to send notification.",
                "success" => false,
            ]);
        }
    }

    public function getStudentsListwithSibling(Request $request)
    {
        set_time_limit(300);
        $section_id = $request->section_id;
        $student_id = $request->student_id;
        $reg_no = $request->reg_no;

        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $query = Student::query();
        $query->with(['parents', 'userMaster', 'getClass', 'getDivision']);

        if ($section_id && $reg_no) {
            $query->where('section_id', $section_id)
                ->where('reg_no', $reg_no)
                ->where('isDelete', 'N')->where('academic_yr', $academicYr)->where('parent_id', '!=', '0');
        } elseif ($student_id && $reg_no) {
            $query->where('student_id', $student_id)
                ->where('reg_no', $reg_no)
                ->where('isDelete', 'N')->where('academic_yr', $academicYr)->where('parent_id', '!=', '0');
        } elseif ($section_id && $student_id && $reg_no) {
            $query->where('section_id', $section_id)
                ->where('student_id', $student_id)
                ->where('reg_no', $reg_no)
                ->where('isDelete', 'N')->where('academic_yr', $academicYr)->where('parent_id', '!=', '0');
        } elseif ($section_id && $student_id) {
            $query->where('student_id', $student_id)
                ->where('section_id', $section_id)
                ->where('isDelete', 'N')->where('academic_yr', $academicYr)->where('parent_id', '!=', '0');
        } elseif ($section_id) {
            $query->where('section_id', $section_id)
                ->where('isDelete', 'N')->where('academic_yr', $academicYr)->where('parent_id', '!=', '0');
        } elseif ($student_id) {
            $query->where('student_id', $student_id)
                ->where('isDelete', 'N')->where('academic_yr', $academicYr)->where('parent_id', '!=', '0');
        } elseif ($reg_no) {
            $query->where('reg_no', $reg_no)
                ->where('isDelete', 'N')->where('academic_yr', $academicYr)->where('parent_id', '!=', '0');
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Please provide at least one search condition.',
            ], 400);
        }

        $students = $query->get();

        if ($students->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No student found matching the search criteria.',
            ], 404);
        }

        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

        // Enhance each student and attach siblings
        $students->each(function ($student) use ($academicYr, $parent_app_url, $codeigniter_app_url) {
            $concatprojecturl = $codeigniter_app_url . 'uploads/student_image/';
            $student->image_name = !empty($student->image_name) ? $concatprojecturl . $student->image_name : '';

            $contactDetails = ContactDetails::find($student->parent_id);
            $student->SetToReceiveSMS = $contactDetails?->phone_no ?? '';

            $userMaster = UserMaster::where('role_id', 'P')
                ->where('reg_id', $student->parent_id)
                ->first();
            $student->SetEmailIDAsUsername = $userMaster?->user_id ?? '';

            // Fetch and attach siblings for this student
            $siblings = Student::with(['parents', 'userMaster', 'getClass', 'getDivision'])
                ->where('parent_id', $student->parent_id)
                ->where('student_id', '!=', $student->student_id) // exclude self
                ->where('isDelete', 'N')
                ->where('academic_yr', $academicYr)
                ->get();

            // Add image and contact details to each sibling
            $siblings->each(function ($sibling) use ($codeigniter_app_url) {
                $imgPath = $codeigniter_app_url . 'uploads/student_image/';
                $sibling->image_name = !empty($sibling->image_name) ? $imgPath . $sibling->image_name : '';

                $contactDetails = ContactDetails::find($sibling->parent_id);
                $sibling->SetToReceiveSMS = $contactDetails?->phone_no ?? '';

                $userMaster = UserMaster::where('role_id', 'P')
                    ->where('reg_id', $sibling->parent_id)
                    ->first();
                $sibling->SetEmailIDAsUsername = $userMaster?->user_id ?? '';
            });

            $student->siblings = $siblings;
        });

        return response()->json([
            'status' => 'success',
            'students' => $students,
        ]);
    }

    public function saveUnmappingSibling(Request $request, $id)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            if ($user->role_id !== 'A') {
                return response()->json([
                    'status' => 403,
                    'message' => 'This is Unauthorized for another user.',
                    'success' => true
                ]);
            }

            $studentId = $id;
            if (!$studentId) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Student ID is required.',
                    'success' => false
                ]);
            }

            $changedData = false;

            if ($request->filled('parent_id2')) {

                $parentId = $request->input('parent_id2');
                $changedData = Student::where('student_id', $studentId)
                    ->update(['parent_id' => $parentId]) > 0;
            } else if ($request->filled('parent_id')) {

                $existingParentId = $request->input('parent_id');

                $changedData = Student::where('student_id', $studentId)
                    ->update(['parent_id' => $existingParentId]) > 0;

                UserMaster::where('reg_id', $existingParentId)
                    ->where('role_id', 'P')
                    ->update(['IsDelete' => 'N']);

                Parents::where('parent_id', $existingParentId)
                    ->update(['IsDelete' => 'N']);

                $deletedContact = DB::table('deleted_contact_details')
                    ->where('id', $existingParentId)->first();

                if ($deletedContact) {
                    DB::table('contact_details')->insert([
                        'id' => $deletedContact->id,
                        'phone_no' => $deletedContact->phone_no,
                        'email_id' => $deletedContact->email_id,
                        'm_emailid' => $deletedContact->m_emailid
                    ]);

                    DB::table('deleted_contact_details')->where('id', $existingParentId)->delete();
                }
            } else {
                // Case: create new parent + user + contact and assign
                $f_mobile = $request->input('f_mobile');
                $m_mobile = $request->input('m_mobile');
                $f_email = $request->input('f_email');
                $m_email = $request->input('m_email');
                $userIdInput = $request->input('user_id');

                // Resolve user_id if it's a keyword
                if ($userIdInput === 'f_email') {
                    $userId = $f_email;
                } elseif ($userIdInput === 'm_email') {
                    $userId = $m_email;
                } else {
                    $userId = $userIdInput;
                }

                // Check if user_id already exists
                $existingUser = UserMaster::where('user_id', $userId)->first();
                if ($existingUser) {
                    return response()->json([
                        'status' => 409,
                        'success' => false,
                        'message' => 'Userid already exists. Do you want to you use existing parent data?.'
                    ]);
                }

                // Continue with parent creation
                $parentData = [
                    'father_name' => trim($request->input('father_name')),
                    'f_email' => $f_email,
                    'f_mobile' => $f_mobile,
                    'mother_name' => trim($request->input('mother_name')),
                    'm_mobile' => $m_mobile,
                    'm_emailid' => $m_email,
                    'IsDelete' => 'N'
                ];
                $parentId = DB::table('parent')->insertGetId($parentData);
                $settingsData = getSchoolSettingsData();
                $defaultPassword = $settingsData->default_pwd;
                // Create user since we already checked it doesn't exist
                UserMaster::create([
                    'user_id' => $userId,
                    'name' => $request->input('father_name'),
                    'password' => bcrypt($defaultPassword),
                    'reg_id' => $parentId,
                    'role_id' => 'P'
                ]);

                // Send to external EVOLVU service
                createUserInEvolvu($userId);

                // Add contact details
                $phone = $f_mobile ?: $m_mobile;
                DB::table('contact_details')->insert([
                    'id' => $parentId,
                    'phone_no' => $phone,
                    'email_id' => $f_email,
                    'm_emailid' => $m_email
                ]);

                // Update student with parent_id
                $changedData = Student::where('student_id', $studentId)
                    ->update(['parent_id' => $parentId]) > 0;
            }



            // Final success response
            if ($changedData) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Student unmapped successfully.'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student update failed or no changes detected.'
                ]);
            }
        } catch (Exception $e) {
            \Log::error('Unmapping sibling error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error'
            ], 500);
        }
    }
    
}
