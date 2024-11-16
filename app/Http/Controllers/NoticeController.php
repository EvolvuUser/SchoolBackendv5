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

                            return response()->json([
                                'status'=> 200,
                                'message'=>'Sms and Notices Listing',
                                'data' =>$query,
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
                    dd("Hello from Notices");
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
                    dd("Hello from Notices");
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
                    dd("Hello from Notices");
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
                    dd("Hello from Notices");
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
