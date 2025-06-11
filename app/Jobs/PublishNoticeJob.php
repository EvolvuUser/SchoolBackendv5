<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Http\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use App\Models\Notice;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;
use App\Http\Controllers\NoticeController;

class PublishNoticeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $unq;
    protected $customClaims;

    public function __construct($unq, $customClaims)
    {
        $this->unq = $unq;
        $this->customClaims = $customClaims;
    }

    /**
     * Create a new job instance.
     */

    /**
     * Execute the job.
     */
    public function handle(): void
    {
         Log::info("Hello");

        $parentnotices = DB::table('notice')
                           ->where('unq_id', $this->unq)
                           ->where('academic_yr',$this->customClaims)
                           ->get();
         Log::info("Sms Information", [$parentnotices]);
         foreach($parentnotices as $parentnotice){
            $students = DB::table('student as a')
                            ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                            ->where('a.class_id', $parentnotice->class_id)
                            ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id')
                            ->get();
                            Log::info("Student Information", [$students]);
                            foreach ($students as $student) {
                            $templateName = 'emergency_message';
                            $parameters = [$parentnotice->notice_desc];
                            Log::info("TestCronJob JOB Failed AFter parameter Whatsapp Message");

                            if ($student->phone_no) {
                                $result = app('App\Http\Services\WhatsAppService')->sendTextMessage(
                                    $student->phone_no,
                                    $templateName,
                                    $parameters
                                );

                                Log::info("Failed message", $result);

                                if (isset($result['code']) && isset($result['message'])) {
                                    Log::warning("Rate limit hit", []);
                                } else {
                                    $wamid = $result['messages'][0]['id'];
                                    $phone_no = $result['contacts'][0]['input'];

                                    DB::table('redington_webhook_details')->insert([
                                        'wa_id' => $wamid,
                                        'phone_no' => $phone_no,
                                        'stu_teacher_id' => $student->student_id,
                                        'notice_id' => $parentnotice->notice_id,
                                        'message_type' => 'notice',
                                        'created_at' => now()
                                    ]);
                                }
                            }
                        }
                        Log::info("TestCronJob JOB Failed AFter Sending Whatsapp Message");
                        sleep(20); 
                        $leftmessages = DB::table('redington_webhook_details')
                                                    ->where('message_type','notice')
                                                    ->where('status','failed')
                                                    ->where('notice_id',$parentnotice->notice_id)
                                                    ->where('sms_sent','N')
                                                    ->get();
                        foreach($leftmessages as $leftmessage){
                                $parentidstudentdetails = DB::table('student')->where('student_id',$leftmessage->stu_teacher_id)->first();
                                $parent_id = $parentidstudentdetails->parent_id;
                                $smsdata = DB::table('daily_sms')
                                            ->where('parent_id', $parent_id)
                                            ->where('student_id', $leftmessage->stu_teacher_id)
                                            ->get(); 
                                // dd($smsdata);
                                 $smsdatacount= count($smsdata);
                                  if($smsdatacount=='0'){
                                    $sdata = [
                                        'parent_id' => $parent_id,
                                        'student_id' => $leftmessage->stu_teacher_id,
                                        'phone' => $leftmessage->phone_no,
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

                            $tokendata = DB::table('student as a')
                                ->select('b.token', 'b.user_id', 'b.parent_teacher_id', 'b.login_type', 'a.parent_id', 'a.student_id')
                                ->join('user_tokens as b', 'a.parent_id', '=', 'b.parent_teacher_id')
                                ->where('a.class_id', $parentnotice->class_id)
                                ->where('b.login_type', 'P')
                                ->get(); // Use get() to retrieve the results
                                foreach ($tokendata as $token) {
                                    $dailyNotification = [
                                        'student_id' => $token->student_id,
                                        'parent_id' => $token->parent_teacher_id,
                                        'homework_id' => 0,
                                        'remark_id' => 0,
                                        'notice_id' => $parentnotice->notice_id,
                                        'notes_id' => 0,
                                        'notification_date' => now()->toDateString(), // Using Laravel's now() helper to get today's date
                                        'token' => $token->token,
                                    ];
                                    $data = [
                                        'token' => $token->token, // The user's token to send the notification
                                        'notification' => [
                                            'title' => 'Notice',
                                            'description' => $parentnotice->notice_desc
                                        ]
                                    ];
                                    sendnotificationusinghttpv1($data);
                            
                                    // Insert the data using DB facade
                                    DB::table('daily_notifications')->insert($dailyNotification);
                                }
         }
       
        
    }
}
