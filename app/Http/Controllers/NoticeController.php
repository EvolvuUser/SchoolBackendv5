<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Notice;
use DB;
use Http;
use App\Models\NoticeSmsLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\NoticeDetail;
use App\Models\ExamTimetable;
use App\Models\ExamTimetableDetail;
use App\Models\Exams;
use Log;

class NoticeController extends Controller
{
    public function saveSmsNotice(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            
    
            if($user->role_id == 'A'|| $user->role_id == 'U'  || $user->role_id == 'M'){
                 
            // Generate a unique ID for the notice
            do {
                $unq = rand(1000, 9999);
            } while (Notice::where('unq_id', $unq)->exists());
    
            // Prepare the notice data
            $noticeData = [
                'subject' => $request->subject,
                'notice_desc' =>"Dear Parent,".$request->notice_desc,
                'teacher_id' => $user->id, // Assuming the teacher is authenticated
                'notice_type' => 'SMS',
                'academic_yr' => $customClaims, // Assuming academic year is stored in Session
                'publish' => 'N',
                'unq_id' => $unq,
                'notice_date' => now()->toDateString(), // Laravel helper for current date
            ];
    
            // Insert the notice for each selected class
            if ($request->has('checkbxevent') && !empty($request->checkbxevent)) {
                foreach ($request->checkbxevent as $classId) {
                    if (!empty($classId)) {
                        // Associate notice with the class
                        $notice = new Notice($noticeData);
                        $notice->class_id = $classId;
                        $notice->save(); // Insert the notice
                    }
                
                }
            }
    
            return response()->json([
                'status'=> 200,
                'message'=>'New Sms Created',
                'data' =>$noticeData,
                'success'=>true
                ]);
        
            }
            else{
                return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Save Sms',
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

    public function SaveAndPublishSms(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
        

        if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
        
        set_time_limit(3600);  //Time Limit of 6 minutes
        // Generate a unique ID for the notice
        do {
            $unq = rand(1000, 9999);
        } while (Notice::where('unq_id', $unq)->exists());
       
         // Prepare the notice data
         $noticeData = [
            'subject' => $request->subject,
            'notice_desc' =>"Dear Parent,".$request->notice_desc,
            'teacher_id' => $user->id, // Assuming the teacher is authenticated
            'notice_type' => 'SMS',
            'academic_yr' => $customClaims, // Assuming academic year is stored in Session
            'publish' => 'Y',
            'unq_id' => $unq,
            'notice_date' => now()->toDateString(), // Laravel helper for current date
        ];
                if ($request->has('checkbxevent') && !empty($request->checkbxevent)) {
                    foreach ($request->checkbxevent as $classId) {
                        if (!empty($classId)) {
                            // Associate notice with the class
                            $notice = new Notice($noticeData);
                            $notice->class_id = $classId;
                            $notice->save(); // Insert the notice
                        }
                        if($notice){

                            $studParentdata = DB::table('student as a') // 'students' table alias as 'a'
                                        ->join('contact_details as b', 'a.parent_id', '=', 'b.id') // Joining contact_details with alias 'b'
                                        ->where('a.class_id', $classId) // Filter by class_id
                                        ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id') // Select the required fields
                                        ->get();
                        foreach ($studParentdata as $student) {
                            $message = $noticeData['notice_desc'] . ". Login to school application for details - AceVentura";
                            $temp_id = '1107161354408119887';  // Assuming this is required for SMS service
                    
                            // Send SMS using the send_sms method
                                $sms_status = $this->send_sms($student->phone_no, $message, $temp_id); // Assuming send_sms is implemented
                                if ($student->phone_no != null) {
                                    // Prepare the data to be inserted
                                    $sms_log_data = [
                                        'sms_status' => $sms_status,
                                        'stu_teacher_id' => $student->student_id,
                                        'notice_id' => $notice->notice_id,
                                        'phone_no' => $student->phone_no,
                                        'sms_date' => Carbon::now()->format('Y/m/d') // Using Carbon to format the date
                                    ];
                                
                                    // Insert the data into the 'notice_sms_log' table
                                    NoticeSmsLog::create($sms_log_data);
                                }
                        }  
                                               
               }
           
        }
        return response()->json([
            'status'=> 200,
            'message'=>'New Sms Created And Sended',
            'data' =>$noticeData,
            'success'=>true
            ]);
        
    }
                
    
        }
        else{
            return response()->json([
            'status'=> 401,
            'message'=>'This User Doesnot have Permission for the Save and Publish Sms',
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


    public function getNoticeSmsList(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            $notice_date = $request->query('notice_date');
            $status = $request->query('status');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $query = DB::table('notice')
                            ->select('notice.*', 'teacher.name', 'class.class_id', DB::raw('GROUP_CONCAT(class.name ) as classnames'),DB::raw('GROUP_CONCAT(notice.class_id ) as classIds'))
                            ->join('teacher', 'notice.teacher_id', '=', 'teacher.teacher_id')
                            ->join('class', 'notice.class_id', '=', 'class.class_id')
                            ->groupBy('unq_id')  // Grouping by unq_id
                            ->orderBy('notice_id', 'desc')  // Ordering by notice_id descending
                            
                            // Filter by notice_date if it's not '0' or empty
                            ->when($notice_date != '0' && $notice_date != '', function($query) use ($notice_date) {
                                return $query->where('notice_date', '=', \Carbon\Carbon::createFromFormat('Y-m-d', $notice_date)->format('Y-m-d'));
                            })

                            // Filter by publish status if it's not 'All' or empty
                            ->when($status != 'All' && $status != '', function($query) use ($status) {
                                return $query->where('publish', $status);
                            })

                            // Filter by academic year
                            ->where('notice.academic_yr', $customClaims)

                            // Execute the query
                            ->get();
                            $unq_ids = DB::table('notice')->select('unq_id')->distinct()->pluck('unq_id');
                            $counts = [];
                                foreach ($unq_ids as $unqids) {
                                    $counts[$unqids] = DB::table('notice_sms_log')
                                                        ->where('sms_sent', 'N')
                                                        ->where('phone_no', '<>', '')
                                                        ->whereIn('notice_id', function($query) use ($unqids) {
                                                            $query->select('notice_id')
                                                                ->from('notice')
                                                                ->where('unq_id', $unqids);
                                                        })
                                                        ->count();
                                }

                            $data['smscount'] = $counts;

                            return response()->json([
                                'status'=> 200,
                                'message'=>'Sms and Notices Listing',
                                'data' =>$query,$data,
                                'success'=>true
                                ]);
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Viewing of List',
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

    public function getNoticeSmsData(Request $request,$unq_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $notice_type = DB::table('notice')->where('unq_id',$unq_id)->first();

                if($notice_type->notice_type == "SMS"){
                    $noticeData = DB::table('notice')
                    ->select('notice.*', 'teacher.name', 'class.class_id', DB::raw('GROUP_CONCAT(class.name ) as classnames'))
                    ->join('teacher', 'notice.teacher_id', '=', 'teacher.teacher_id')
                    ->join('class', 'notice.class_id', '=', 'class.class_id')
                    ->where('unq_id',$unq_id)
                    ->groupBy('unq_id')  // Grouping by unq_id
                    ->orderBy('notice_id', 'desc')  // Ordering by notice_id descending
                    ->get();
                    return response()->json([
                        'status'=> 200,
                        'message'=>'Sms View Edit',
                        'data' =>$noticeData,
                        'success'=>true
                        ]);
                }
                else{
                    $noticeData1 = DB::table('notice')
                    ->select('notice.*', 'teacher.name' ,'class.class_id', DB::raw('GROUP_CONCAT(class.name ) as classnames'))
                    ->join('teacher', 'notice.teacher_id', '=', 'teacher.teacher_id')
                    ->join('class', 'notice.class_id', '=', 'class.class_id')
                    ->where('unq_id',$unq_id)
                    ->groupBy('unq_id')  // Grouping by unq_id
                    ->orderBy('notice_id', 'desc')  // Ordering by notice_id descending
                    ->get();
                    $noticeimages=DB::table('notice')
                                    ->where('unq_id',$unq_id)
                                    ->join('notice_detail','notice_detail.notice_id','=','notice.notice_id')
                                    ->select('notice_detail.image_name')
                                    ->get();
                                 
                    $imageUrls = []; 
                    foreach($noticeimages as $image){
                        $imageurl = ("storage/notice/".$image->image_name);
                        $imageUrls[] = $imageurl;
                        
                    }
                    $noticeData['noticedata']= $noticeData1;
                    $noticeData['noticeimages']=$noticeimages;
                    $noticeData['imageurl'] = $imageUrls;
                    return response()->json([
                        'status'=> 200,
                        'message'=>'Notice View Edit',
                        'data' =>$noticeData,
                        'success'=>true
                        ]);
                }
                    
           }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Viewing of List',
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

    public function UpdateSMSNotice(Request $request,$unq_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $notice_type = DB::table('notice')->where('unq_id',$unq_id)->first();
                if($notice_type->notice_type == "SMS"){
                      $updatesmsnotice = DB::table('notice')->where('unq_id',$unq_id)->get();
                      foreach ($updatesmsnotice as $notice) {
                        DB::table('notice')
                            ->where('unq_id', $notice->unq_id) // Find each notice by its unique ID
                            ->update([
                                'subject' => $request->subject, // Update the subject field (example)
                                'notice_desc' => $request->notice_desc, // Update the description (example)
                                'teacher_id' => $user->id,
                                'notice_date' => now(), // You can also use dynamic values like current timestamp
                                // Add other fields to update as needed
                            ]);
                    }
                  
                    $newsmsdata = DB::table('notice')->where('unq_id',$unq_id)->get();
                    return response()->json([
                        'status'=> 200,
                        'message'=>'Sms Updated',
                        'data' =>$newsmsdata,
                        'success'=>true
                        ]);

                    
                }
                else{
                    $updatesmsnotice = DB::table('notice')->where('unq_id',$unq_id)->get();
                    foreach($updatesmsnotice as $noticeid){
                        $notice_detail = DB::table('notice_detail')
                                        ->where('notice_id', $noticeid->notice_id)
                                        ->get()
                                        ->toArray();
                    }
                    $notice_detail = array_filter($notice_detail, function($value) {
                        return !empty($value); // Remove empty arrays
                    });
                    
                    $notice_detail = array_values($notice_detail);
                      // Check if there are any notice details
                    if ($notice_detail) {
                        // Loop through each notice detail and delete the files
                        foreach ($notice_detail as $row) {
                            $path = storage_path("app/public/notice/{$row->image_name}");
                            // Check if the file exists and delete it
                            if (File::exists($path)) {
                                File::delete($path); // Delete the file
                            }
                        }
                    }
                    foreach($updatesmsnotice as $noticeid){
                        $notice_detail = DB::table('notice_detail')
                                        ->where('notice_id', $noticeid->notice_id)
                                        ->delete();
                    }
                      foreach ($updatesmsnotice as $notice) {
                        DB::table('notice')
                            ->where('unq_id', $notice->unq_id) // Find each notice by its unique ID
                            ->update([
                                'subject' => $request->subject, // Update the subject field (example)
                                'notice_desc' => $request->notice_desc, // Update the description (example)
                                'teacher_id' => $user->id,
                                'notice_date' => now(), 
                            ]);
                        }

                    $noticeFolder = storage_path("app/public/notice");
                    if (!File::exists($noticeFolder)) {
                        File::makeDirectory($noticeFolder, 0777, true);
                    }
                    // Handle file uploads
                    $uploadedFiles = $request->file('userfile');
                    if(is_null($uploadedFiles)){
                        return response()->json([
                            'status'=> 200,
                            'message'=>'Notice Updated Successfully.',
                            'success'=>true
                            ]);
                    }
                    $notice=DB::table('notice')->where('unq_id',$unq_id)->first();
                    foreach ($uploadedFiles as $file) {
                        $fileName = $file->getClientOriginalName();
                        $ImageName = $notice->notice_id.$fileName;
                        $filePath = $noticeFolder . '/' . $fileName;
                        
                        // Save file details in 'notice_detail' table
                        NoticeDetail::create([
                            'notice_id' => $notice->notice_id,
                            'image_name' => $ImageName,
                            'file_size' => $file->getSize(),
                        ]);

                        // Move the file to the appropriate folder
                        $file->move($noticeFolder, $ImageName);
                        }
                        return response()->json([
                            'status'=> 200,
                            'message'=>'Notice Updated',
                            'data' =>$updatesmsnotice,
                            'success'=>true
                            ]);
                }
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


    public function DeleteSMSNotice(Request $request,$unq_id){
         try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $notice_type = DB::table('notice')->where('unq_id',$unq_id)->first();
                if($notice_type->notice_type == "SMS"){

                    $deletedRows = DB::table('notice')
                                        ->where('unq_id', $unq_id)
                                        ->delete();
                    
                    return response()->json([
                        'status'=> 200,
                        'message'=>'Sms Deleted Successfully.',
                        'data' =>$deletedRows,
                        'success'=>true
                        ]);  
                }
                else
                {
                    $noticeids = DB::table('notice')->where('unq_id',$unq_id)->get();
                    foreach($noticeids as $noticeid){
                        $notice_detail = DB::table('notice_detail')
                                        ->where('notice_id', $noticeid->notice_id)
                                        ->get()
                                        ->toArray();
                    }
                    $notice_detail = array_filter($notice_detail, function($value) {
                        return !empty($value); // Remove empty arrays
                    });
                    
                    $notice_detail = array_values($notice_detail);
                      // Check if there are any notice details
                    if ($notice_detail) {
                        // Loop through each notice detail and delete the files
                        foreach ($notice_detail as $row) {
                            $path = storage_path("app/public/notice/{$row->image_name}");
                            // Check if the file exists and delete it
                            if (File::exists($path)) {
                                File::delete($path); // Delete the file
                            }
                        }
                    }
                    $deletedRows = DB::table('notice')
                                        ->where('unq_id', $unq_id)
                                        ->delete();

                    foreach($noticeids as $noticeid){
                        $notice_detail = DB::table('notice_detail')
                                        ->where('notice_id', $noticeid->notice_id)
                                        ->delete();
                    }

                    return response()->json([
                        'status'=> 200,
                        'message'=>'Notice Deleted Successfully.',
                        'data' =>$deletedRows,
                        'success'=>true
                        ]);
                }
            

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

    public function publishSMSNotice(Request $request,$unq_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $notice_type = DB::table('notice')->where('unq_id',$unq_id)->first();
                if($notice_type->notice_type == "SMS"){
                    $updatesmsnotice = DB::table('notice')->where('unq_id',$unq_id)->get();
                    foreach ($updatesmsnotice as $notice) {
                      DB::table('notice')
                          ->where('unq_id', $notice->unq_id) // Find each notice by its unique ID
                          ->update(['publish' => 'Y',]);

                          $studParentdata = DB::table('student as a') // 'students' table alias as 'a'
                                        ->join('contact_details as b', 'a.parent_id', '=', 'b.id') // Joining contact_details with alias 'b'
                                        ->where('a.class_id', $notice->class_id) // Filter by class_id
                                        ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id') // Select the required fields
                                        ->get();
                                foreach ($studParentdata as $student) {
                                    $message = $notice->notice_desc . ". Login to school application for details - AceVentura";
                                    $temp_id = '1107161354408119887';  // Assuming this is required for SMS service
                            
                                    // Send SMS using the send_sms method
                                        $sms_status = $this->send_sms($student->phone_no, $message, $temp_id); // Assuming send_sms is implemented
                                        if ($student->phone_no != null) {
                                            // Prepare the data to be inserted
                                            $sms_log_data = [
                                                'sms_status' => $sms_status,
                                                'stu_teacher_id' => $student->student_id,
                                                'notice_id' => $notice->notice_id,
                                                'phone_no' => $student->phone_no,
                                                'sms_date' => Carbon::now()->format('Y/m/d') // Using Carbon to format the date
                                            ];
                                        
                                            // Insert the data into the 'notice_sms_log' table
                                            NoticeSmsLog::create($sms_log_data);
                                        }
                                }
                 }

                    return response()->json([
                        'status'=> 200,
                        'message'=>'Sms Published Successfully.',
                        'data' => $updatesmsnotice,
                        'success'=>true
                        ]);  
                }
                else
                {
                    $updatesmsnotice = DB::table('notice')->where('unq_id',$unq_id)->get();
                    foreach ($updatesmsnotice as $notice) {
                      DB::table('notice')
                          ->where('unq_id', $notice->unq_id) // Find each notice by its unique ID
                          ->update(['publish' => 'Y',]);

                          $studParentdata = DB::table('student as a') // 'students' table alias as 'a'
                                        ->join('contact_details as b', 'a.parent_id', '=', 'b.id') // Joining contact_details with alias 'b'
                                        ->where('a.class_id', $notice->class_id) // Filter by class_id
                                        ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id') // Select the required fields
                                        ->get();
                                foreach ($studParentdata as $student) {
                                    $smsdata = DB::table('daily_sms')
                                        ->where('parent_id', $student->parent_id)
                                        ->where('student_id', $student->student_id)
                                        ->get(); 
                            // dd($smsdata);
                             $smsdatacount= count($smsdata);
                              if($smsdatacount=='0'){
                                $sdata = [
                                    'parent_id' => $student->parent_id,
                                    'student_id' => $student->student_id,
                                    'phone' => $student->phone_no,
                                    'homework' => 0,
                                    'remark' => 0,
                                    'achievement' => 0,
                                    'note' => 0,
                                    'notice' => 1,
                                    'sms_date' => now() // Laravel's `now()` function returns the current date and time
                                ];
                                
                                DB::table('daily_sms')->insert($sdata);
                              }
                              else{
                                $smsdata[0]->notice = 1 + $smsdata[0]->notice;
                                $smsdata[0]->sms_date = now();  // Laravel's `now()` helper for the current timestamp

                                // Perform the update
                                DB::table('daily_sms')
                                    ->where('parent_id', $smsdata[0]->parent_id)
                                    ->where('student_id', $smsdata[0]->student_id)
                                    ->update(['notice' => $smsdata[0]->notice,
                                             'sms_date' => $smsdata[0]->sms_date]);
                              }
                            
                                }
                            }
                            return response()->json([
                                'status'=> 200,
                                'message'=>'Sms Published Successfully.',
                                'data' => $updatesmsnotice,
                                'success'=>true
                                ]);
                }
            

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

    public function saveNotice(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                // Generate a unique ID for the notice
            do {
                $unq = rand(1000, 9999);
            } while (Notice::where('unq_id', $unq)->exists());
    
            // Prepare the notice data
            $noticeData = [
                'subject' => $request->subject,
                'notice_desc' =>"Dear Parent,".$request->notice_desc,
                'teacher_id' => $user->id, // Assuming the teacher is authenticated
                'notice_type' => 'NOTICE',
                'academic_yr' => $customClaims, // Assuming academic year is stored in Session
                'publish' => 'N',
                'unq_id' => $unq,
                'notice_date' => now()->toDateString(), // Laravel helper for current date
            ];
    
            // Insert the notice for each selected class
            if ($request->has('checkbxevent') && !empty($request->checkbxevent)) {
                foreach ($request->checkbxevent as $classId) {
                    if (!empty($classId)) {
                        // Associate notice with the class
                        $notice = new Notice($noticeData);
                        $notice->class_id = $classId;
                        $notice->save(); // Insert the notice
                    }
            }
        }

        $noticeFolder = storage_path("app/public/notice");
                    if (!File::exists($noticeFolder)) {
                        File::makeDirectory($noticeFolder, 0777, true);
                    }
                    // Handle file uploads
                    $uploadedFiles = $request->file('userfile');
                    if(is_null($uploadedFiles)){
                        return response()->json([
                            'status'=> 200,
                            'message'=>'Notice Saved Successfully.',
                            'data' => $noticeData,
                            'success'=>true
                            ]);
                    }
                    foreach ($uploadedFiles as $file) {
                        $fileName = $file->getClientOriginalName();
                        $ImageName = $notice->notice_id.$fileName;
                        $filePath = $noticeFolder . '/' . $fileName;
                        
                        // Save file details in 'notice_detail' table
                        NoticeDetail::create([
                            'notice_id' => $notice->notice_id,
                            'image_name' => $ImageName,
                            'file_size' => $file->getSize(),
                        ]);

                        // Move the file to the appropriate folder
                        $file->move($noticeFolder, $ImageName);
                        }

                        return response()->json([
                            'status'=> 200,
                            'message'=>'Notice Saved Successfully.',
                            'data' => $noticeData,
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

    public function savePUblishNotice(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                // Generate a unique ID for the notice
            do {
                $unq = rand(1000, 9999);
            } while (Notice::where('unq_id', $unq)->exists());
    
            // Prepare the notice data
            $noticeData = [
                'subject' => $request->subject,
                'notice_desc' =>"Dear Parent,".$request->notice_desc,
                'teacher_id' => $user->id, // Assuming the teacher is authenticated
                'notice_type' => 'NOTICE',
                'academic_yr' => $customClaims, // Assuming academic year is stored in Session
                'publish' => 'Y',
                'unq_id' => $unq,
                'notice_date' => now()->toDateString(), // Laravel helper for current date
            ];
    
            // Insert the notice for each selected class
            if ($request->has('checkbxevent') && !empty($request->checkbxevent)) {
                foreach ($request->checkbxevent as $classId) {
                    if (!empty($classId)) {
                        // Associate notice with the class
                        $notice = new Notice($noticeData);
                        $notice->class_id = $classId;
                        $notice->save(); // Insert the notice
                    }
                    if($notice){
                        $studParentdata = DB::table('student as a')
                                    ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                                    ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id')
                                    ->where('a.class_id', $classId)
                                    ->get();
                        foreach ($studParentdata as $student) {
                            $smsdata = DB::table('daily_sms')
                                        ->where('parent_id', $student->parent_id)
                                        ->where('student_id', $student->student_id)
                                        ->get(); 
                            // dd($smsdata);
                             $smsdatacount= count($smsdata);
                              if($smsdatacount=='0'){
                                $sdata = [
                                    'parent_id' => $student->parent_id,
                                    'student_id' => $student->student_id,
                                    'phone' => $student->phone_no,
                                    'homework' => 0,
                                    'remark' => 0,
                                    'achievement' => 0,
                                    'note' => 0,
                                    'notice' => 1,
                                    'sms_date' => now() // Laravel's `now()` function returns the current date and time
                                ];
                                
                                DB::table('daily_sms')->insert($sdata);
                              }
                              else{
                                $smsdata[0]->notice = 1 + $smsdata[0]->notice;
                                $smsdata[0]->sms_date = now();  // Laravel's `now()` helper for the current timestamp

                                // Perform the update
                                DB::table('daily_sms')
                                    ->where('parent_id', $smsdata[0]->parent_id)
                                    ->where('student_id', $smsdata[0]->student_id)
                                    ->update(['notice' => $smsdata[0]->notice,
                                             'sms_date' => $smsdata[0]->sms_date]);
                              }
                              
                        }  

                    }
            }
        }

        $noticeFolder = storage_path("app/public/notice/");
                    if (!File::exists($noticeFolder)) {
                        File::makeDirectory($noticeFolder, 0777, true);
                    }
                    // Handle file uploads
                    $uploadedFiles = $request->file('userfile');
                    if(is_null($uploadedFiles)){
                        return response()->json([
                            'status'=> 200,
                            'message'=>'Notice Saved and Published Successfully.',
                            'data' => $noticeData,
                            'success'=>true
                            ]);
                    }
                    foreach ($uploadedFiles as $file) {
                        $fileName = $file->getClientOriginalName();
                        $ImageName = $notice->notice_id.$fileName;
                        $filePath = $noticeFolder . '/' . $fileName;
                        
                        // Save file details in 'notice_detail' table
                        NoticeDetail::create([
                            'notice_id' => $notice->notice_id,
                            'image_name' => $ImageName,
                            'file_size' => $file->getSize(),
                        ]);

                        // Move the file to the appropriate folder
                        $file->move($noticeFolder, $ImageName);
                        }

                        
                        
                        return response()->json([
                            'status'=> 200,
                            'message'=>'Notice Saved and Published Successfully.',
                            'data' => $noticeData,
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

    public function SendSMSLeft(Request $request,$unq_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                    $noticedata = DB::table('notice_sms_log')
                                    ->join('notice','notice.notice_id','=','notice_sms_log.notice_id')
                                    ->where('notice.unq_id',$unq_id)
                                    ->get();

                    foreach($noticedata as $noticedata1){
                        $message = $noticedata1->notice_desc . ". Login to school application for details - AceVentura";
                            $temp_id = '1107161354408119887';  // Assuming this is required for SMS service
                    
                            // Send SMS using the send_sms method
                            $sms_status = $this->send_sms($noticedata1->phone_no, $message, $temp_id);
                            $updatesmsdata = DB::table('notice_sms_log')
                                                 ->where('notice_sms_log_id',$noticedata1->notice_sms_log_id)
                                                 ->update(['sms_sent' => 'Y']);
                    }

                    return response()->json([
                        'status'=> 200,
                        'message'=>'Message Sended Successfully',
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

    public function saveExamTimetable(Request $request,$exam_id,$class_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $existTimetable = DB::table('exam_timetable')
                                     ->where('exam_id',$exam_id)
                                     ->where('class_id',$class_id)
                                     ->exists();
                                    //  dd($existTimetable);
                if($existTimetable){
                    return response()->json([
                        'status'  => 400,
                        'message' => 'Exam Timetable is already created for this class!!',
                        'success' =>false
                    ]);     
                }
                $exam_dates = DB::table('exam')
                                ->select('start_date', 'end_date')
                                ->where('exam_id', $exam_id)
                                ->where('academic_yr', $customClaims)
                                ->first();
                $startDate = $exam_dates->start_date;
                $endDate = $exam_dates->end_date;

                $examTimetableData = [
                    'description' => $request->input('description'),
                    'exam_id' => $exam_id,
                    'class_id' => $class_id,
                    'publish' => 'N',
                    'academic_yr' => $customClaims
                ];
        
                $examTimetable = ExamTimetable::create($examTimetableData);
                $exam_tt_id = $examTimetable->id;

                $dates = [$startDate];
                $start = $startDate;
                $i = 1;

                // Generate the dates between the start and end date
                if (strtotime($startDate) < strtotime($endDate)) {
                    while (strtotime($start) < strtotime($endDate)) {
                        $start = date('Y-m-d', strtotime($startDate . ' +' . $i . ' days'));
                        $dates[] = $start;
                        $i++;
                    }
                }

                $k = 1;
                    foreach ($dates as $date) {
                        $subject_ids = '';
                        $data1 = [
                            'exam_tt_id' => $exam_tt_id,
                            'date' => $date,
                        ];

                        // Determine the option for the current exam date (A, O, Select)
                        $option = $request->input('option' . $k);
                        if ($option == 'A' || $option == 'O') {
                            
                            for ($i = 1; $i <= 4; $i++) {
                                $subject_id = $request->input('subject_id' . $k . $i);
                                
                                if ($subject_id != '') {
                                    if ($option == 'A') {
                                        // For 'A' option, use comma separator
                                        $subject_ids .= ($i > 1 ? ',' : '') . $subject_id;
                                    } elseif ($option == 'O') {
                                        // For 'O' option, use slash separator
                                        $subject_ids .= ($i > 1 ? '/' : '') . $subject_id;
                                    }
                                }
                            }
                            $data1['subject_rc_id'] = $subject_ids;
                        } 
                        elseif ($option == 'Select') {
                            $data1['subject_rc_id'] = $request->input('subject_id' . $k.'1');
                        }


                        if (isset($data1['subject_rc_id'])) {
                            $subject_rc_id = $data1['subject_rc_id'];  // Extract the value if the key exists
                        } else {
                            // Handle the case where the key doesn't exist
                            $subject_rc_id = '0';  // Assign a default value
                        }
                        // Check if study leave is set
                        $study_leave = $request->input('study_leave' . $k);
                        $data1['study_leave'] = $study_leave ? 'Y' : ($subject_rc_id ? 'N' : '');

                        // Insert into exam_timetable_details table
                        ExamTimetableDetail::create($data1);

                        $k++;
                    }

                    return response()->json([
                        'status'  => 200,
                        'message' => 'Exam timetable created successfully!',
                        'success' =>true
                    ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Saving of Data',
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

    public function getAllSubjects(Request $request,$class_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){

            $results = DB::table('subjects_on_report_card as a')
                            ->select('a.sub_rc_master_id', 'b.name', 'a.subject_type')
                            ->distinct()
                            ->join('subjects_on_report_card_master as b', 'b.sub_rc_master_id', '=', 'a.sub_rc_master_id')
                            ->where('a.class_id', '=', $class_id)
                            ->where('a.academic_yr', '=', $customClaims)
                            ->orderBy('a.class_id', 'asc')
                            ->orderBy('b.sequence', 'asc')
                            ->get();
                    return response()->json([
                        'status'  => 200,
                        'message' => 'List of Subjects',
                        'data' =>$results,
                        'success' =>true
                    ]);
              }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Saving of Data',
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

    public function getTimetableList(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
              if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                 
                 $timetablelist = DB::table('exam')
                                       ->join('exam_timetable','exam_timetable.exam_id','=','exam.exam_id')
                                       ->join('class','class.class_id','=','exam_timetable.class_id')
                                       ->select('exam.name as examname','exam.*','class.*','exam_timetable.*')
                                       ->get();
                          
                                return response()->json([
                                'status'  => 200,
                                'message' => 'List of Timetable',
                                'data' =>$timetablelist,
                                'success' =>true
                            ]);
               
              }
              else{
                  return response()->json([
                      'status'=> 401,
                      'message'=>'This User Doesnot have Permission for the Saving of Data',
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

    public function deleteTimetable(Request $request,$exam_tt_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
              if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                DB::table('exam_timetable_details')
                    ->where('exam_tt_id', $exam_tt_id)
                    ->delete();
        
                // Delete records from 'exam_timetable' table where 'exam_tt_id' matches $param2
                DB::table('exam_timetable')
                    ->where('exam_tt_id', $exam_tt_id)
                    ->delete();

                    return response()->json([
                        'status'  => 200,
                        'message' => 'Timetable Deleted Successfully.',
                        'success' =>true
                    ]);
              }
              else{
                  return response()->json([
                      'status'=> 401,
                      'message'=>'This User Doesnot have Permission for the Saving of Data',
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

    public function updatePublishTimetable(Request $request,$exam_tt_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
              if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $data = ['publish' => 'Y'];

                DB::table('exam_timetable')
                    ->where('exam_tt_id', $exam_tt_id)
                    ->update($data);

                return response()->json([
                    'status'  => 200,
                    'message' => 'Timetable Published Successfully.',
                    'success' =>true
                ]);
              }
              else{
                  return response()->json([
                      'status'=> 401,
                      'message'=>'This User Doesnot have Permission for the Saving of Data',
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

    public function updateunPublishTimetable(Request $request,$exam_tt_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
              if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $data = ['publish' => 'N'];

                DB::table('exam_timetable')
                    ->where('exam_tt_id', $exam_tt_id)
                    ->update($data);

                return response()->json([
                    'status'  => 200,
                    'message' => 'Timetable UnPublished Successfully.',
                    'success' =>true
                ]);
              }
              else{
                  return response()->json([
                      'status'=> 401,
                      'message'=>'This User Doesnot have Permission for the Saving of Data',
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

    public function viewTimetableStudent(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
              if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $exam_id = $request->query('exam_id');
                $classterm=DB::table('exam_timetable')
                              ->join('class','class.class_id','=','exam_timetable.class_id')
                              ->join('exam','exam.exam_id','=','exam_timetable.exam_id')
                              ->select('exam.name as examname','class.name as classname')
                              ->first();

                if(isset($exam_id)){
                    $examTimetableDetails = ExamTimetable::join('exam_timetable_details', 'exam_timetable.exam_tt_id', '=', 'exam_timetable_details.exam_tt_id')
                                ->where('exam_timetable.exam_tt_id', $exam_id)
                                ->get();
                    
                    $description = ExamTimetable::where('exam_timetable.exam_tt_id',$exam_id)->select('description')->first();

                            $data = [];

                            foreach ($examTimetableDetails as $rw) {
                                // Process each row of the data
                                $studyLeave = $rw->study_leave == 'Y' ? 'Study Leave' : null;
                                
                                $subjects = [];
                                if ($rw->study_leave != 'Y') {
                                    // Check if the subject_rc_id contains multiple subjects
                                    if (strpos($rw->subject_rc_id, ',') !== false) {
                                        $subjectIds = explode(",", $rw->subject_rc_id);
                                        foreach ($subjectIds as $subjectId) {
                                            $subject = DB::table('subjects_on_report_card_master')->where('sub_rc_master_id',$subjectId)->first();
                                            if ($subject) {
                                                $subjects[] = $subject->name;
                                            }
                                        }
                                        $subjectNames = implode(" & ", $subjects);
                                    } elseif (strpos($rw->subject_rc_id, '/') !== false) {
                                        $subjectIds = explode("/", $rw->subject_rc_id);
                                        foreach ($subjectIds as $subjectId) {
                                            $subject = DB::table('subjects_on_report_card_master')->where('sub_rc_master_id',$subjectId)->first();
                                            if ($subject) {
                                                $subjects[] = $subject->name;
                                            }
                                        }
                                        $subjectNames = implode(" / ", $subjects);
                                    } else {
                                        $subject = DB::table('subjects_on_report_card_master')->where('sub_rc_master_id',$subjectId)->first();
                                        $subjectNames = $subject ? $subject->name : null;
                                    }
                                } else {
                                    $subjectNames = null;
                                }

                                // Prepare the data structure for API response
                                $data[] = [
                                    'date' => \Carbon\Carbon::parse($rw->date)->format('d-m-Y'),
                                    'study_leave' => $studyLeave,
                                    'subjects' => $subjectNames,
                                    'description'=>$description,
                                ];

                            }
                            

                            // Return the data as a JSON response
                            return response()->json([
                                'status'=>200,
                                'exam_tt_id' => $exam_id,
                                'exam_timetable_details' => $data,
                                'classterm'=>$classterm,
                                'success'=>true
                            ]);
                     
                }
                else{
                    return response()->json([
                        'status'  => 400,
                        'message' => 'Timetable Not Found.',
                        'success' =>false
                    ]);                  
                }
              }
              else{
                  return response()->json([
                      'status'=> 401,
                      'message'=>'This User Doesnot have Permission for the Saving of Data',
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

    public function getExamDateswithnames(Request $request,$exam_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
            $exams = Exams::find($exam_id);
            $startDate = Carbon::parse($exams->start_date);  // Parse the start date
            $endDate = Carbon::parse($exams->end_date);      // Parse the end date
    
            // Generate an array of all dates between start and end date
            $dates = [];
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                $dates[] = $date->format('Y-m-d'); // or use any format you prefer
            }
    
            $data['dates']= $dates;
            $response = [];
            foreach ($dates as $index => $date) {
                $optionKey = 'option' . ($index + 1); // Dynamically generate option1, option2, etc.
                $response[] = $optionKey;
            }
            $data['option']=$response;
            $studyleave=[];
            foreach ($dates as $index => $date) {
                $optionKey = 'study_leave' . ($index + 1); // Dynamically generate option1, option2, etc.
                $studyleave[] = $optionKey;
            }
            $data['study_leave']=$studyleave;
            $subjectIds = [];
            foreach ($dates as $index => $date) {
                $optionKey = 'subject_id1' . ($index + 1);
                $optionKey1 = 'subject_id2' . ($index + 1);
                $optionKey2 = 'subject_id3' . ($index + 1);
                $optionKey3 = 'subject_id4' . ($index + 1);
                $subjectIds[] = [
                     $optionKey,
                     $optionKey1,
                     $optionKey2,
                     $optionKey3
                ];
            }
            $data['subject_ids'] = $subjectIds;
            $description = 'description';
            $data['description']=$description;
            return response()->json([
                'status'  => 200,
                'message' => 'Dates with subject Ids',
                'data' => $data,
                'success' =>true
            ]);
        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Saving of Data',
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

        public function getExamdataSingle(Request $request,$exam_tt_id){
            try{
                $user = $this->authenticateUser();
                    $transformedData = JWTAuth::getPayload()->get('academic_year');
                    if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                          $data=ExamTimetable::join('class','class.class_id','=','exam_timetable.class_id')->join('exam','exam.exam_id','=','exam_timetable.exam_id')->where('exam_tt_id',$exam_tt_id)->select('description','class.name as classname','exam.name as examname')->first();
                          $main['dates']= ExamTimetableDetail::where('exam_tt_id',$exam_tt_id)->select('date','subject_rc_id','study_leave')->get();
                          $collection = collect($main['dates']);
    
                          // Transform 'subject_rc_id' into an array
                        //   $transformedData = $collection->map(function ($item) {
                        //       $item['subject_rc_id'] = explode(',', str_replace('/', ',', $item['subject_rc_id']));  // Replace slashes with commas and explode
                        //       return $item;
                        //   });
                          
                        // $transformedData = $collection->map(function ($item) {
                        //     // Convert subject_rc_id to an array (handling both commas and slashes)
                        //     // dd($item['subject_rc_id']);
                        //     $item['subject_rc_id'] = explode(',', str_replace('/', ',', $item['subject_rc_id']));
                        
                        //     // Check study_leave condition
                        //     if ($item['study_leave'] === 'N') {
                        //         $item['study_leave'] = '';  // Set study_leave to empty string if 'N'
                        //     } elseif ($item['study_leave'] === 'Y') {
                        //         $item['study_leave'] = 1;  // Set study_leave to 1 if 'Y'
                        //     }
                        
                        //     // Check for the condition on subject_rc_id and add the "option" key
                        //     $subjectCount = count($item['subject_rc_id']);
                        //     // dd($item['subject_rc_id']);
                        //     dd($subjectCount);
                        //     if ($subjectCount > 1) {
                        //         // If there is more than 1 subject, check for a slash (for "O") or comma (for "A")
                        //         $item['option'] = strpos($item['subject_rc_id'][0], '/') !== false ? 'O' : 'A';
                        //     } elseif ($subjectCount === 1) {
                        //         // If there is only 1 subject
                        //         $item['option'] = 'Select';
                        //     }
                        //     dd($item['subject_rc_id'][0]);
                        
                        //     return $item;
                        // });
    
                        $transformedData = $collection->map(function ($item) {
                            // Check study_leave condition
                            if ($item['study_leave'] === 'N') {
                                $item['study_leave'] = '';  // Set study_leave to empty string if 'N'
                            } elseif ($item['study_leave'] === 'Y') {
                                $item['study_leave'] = 1;  // Set study_leave to 1 if 'Y'
                            }
                        
                            // Check for the condition on subject_rc_id and add the "option" key
                            if (strpos($item['subject_rc_id'], '/') !== false) {
                                // If subject_rc_id contains a slash, mark it as "O" (e.g., 23/24)
                                $item['option'] = 'O';
                            } elseif (strpos($item['subject_rc_id'], ',') !== false) {
                                // If subject_rc_id contains a comma, mark it as "A" (e.g., 23,24)
                                $item['option'] = 'A';
                            } else {
                                // If it's a single subject
                                $item['option'] = 'Select';
                            }
                        
                            $item['subject_rc_id'] = explode(',', str_replace('/', ',', $item['subject_rc_id']));
                            return $item;
                        });
    
                          return response()->json([
                            'status'  => 200,
                            'data'=>$transformedData,$data,
                            'message' => 'Exam timetable data',
                            'success' =>true
                        ]);
                    }
                    else{
                        return response()->json([
                            'status'=> 401,
                            'message'=>'This User Doesnot have Permission for the Saving of Data',
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
    
        public function updateExamTimetable(Request $request,$exam_tt_id){
            // dd($request->all());
            try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                    //  dd($request->all());
                    DB::table('exam_timetable')->where('exam_tt_id',$exam_tt_id)->update(['description'=>$request->description]);
            $data = $request->input('data');
            
            foreach ($data as $item) {
                // Get data from the item
                $date = $item['date'];
                $option = $item['option'];
                $studyLeave = $item['studyLeave'];
                $subjects = array_filter($item['subjects']);  // Remove any null values from subjects array
                
                if ($studyLeave === '1') {
                    $subjects = ['0'];  // Only store '0' as the subject when studyLeave is "1"
                }
                // dd($subjects);
                if ($studyLeave === '1') {
                    $studyLeave = 'Y';
                } else {
                    $studyLeave = 'N';
                }
    
                if ($option === 'A') {
                    // If option is 'A', save subjects as a comma-separated string
                    $subjects = implode(',', $subjects);  // Example: "12,13"
                } elseif ($option === 'O') {
                    // If option is 'O', save subjects as a slash-separated string
                    $subjects = implode('/', $subjects);  // Example: "12/13"
                } else {
                    
                    $subjects = implode(",", $subjects);;  
                }
    
                Log::info('Updating or Creating Record', [
                    'date' => $date,
                    'option' => $option,
                    'studyLeave' => $studyLeave,
                    'subjects' => $subjects,
                ]);
    
                // Find the schedule by date or create a new one if it doesn't exist
                $schedule = ExamTimetableDetail::where('exam_tt_id',$exam_tt_id)->where('date',$date)->update( // Find record by date
                    [
                        'study_leave' => $studyLeave,
                        'subject_rc_id' => $subjects,
                    ]
                );
              }
              return response()->json([
                'status'  => 200,
                'data'=>$schedule,
                'message' => 'Exam timetable Updated',
                'success' =>true
            ]);
    
    
                }
                else{
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the Saving of Data',
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

    public function send_sms($send_to, $message, $template_id)
    {

            // Fallback to SMS if the recipient is not on WhatsApp
            $sender_id = 'ACEVIT';
            $username = 'ACEVENTURA';
            $apikey = '435B6-9DEAB';
            $uri = 'http://sms.quicksmsservices.com/sms-panel/api/http/index.php';

            $data = [
                'username' => $username,
                'apikey' => $apikey,
                'apirequest' => 'Text',
                'sender' => $sender_id,
                'route' => 'TRANS',
                'format' => 'JSON',
                'message' => $message,
                'mobile' => $send_to,
                'TemplateID' => $template_id,
            ];

            // Send SMS using Guzzle HTTP client
            $response = Http::asForm()->post($uri, $data);

            // Handle the response
            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'SMS sent successfully',
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send SMS',
                    'error' => $response->body()
                ], 500);
            }
        }
}
