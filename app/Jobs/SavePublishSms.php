<?php

namespace App\Jobs;

use App\Models\Notice;
use App\Models\NoticeSmsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;
use App\Http\Services\WhatsAppService;
use App\Http\Controllers\NoticeController;
use App\Http\Services\SmsService;

class SavePublishSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $requestData;
    protected $user;
    protected $customClaims;
    public function __construct($requestData, $user, $customClaims)
    {
        $this->requestData = $requestData;
        $this->user = $user;
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
        try {
        Log::info("Hello from cron job");
        $whatsAppService = app(WhatsAppService::class);

        do {
            $unq = rand(1000, 9999);
        } while (Notice::where('unq_id', $unq)->exists());

        $noticeData = [
            'subject' => $this->requestData['subject'],
            'notice_desc' => "Dear Parent," . $this->requestData['notice_desc'],
            'teacher_id' => $this->user->reg_id,
            'notice_type' => 'SMS',
            'academic_yr' => $this->customClaims,
            'publish' => 'Y',
            'unq_id' => $unq,
            'notice_date' => now()->toDateString(),
        ];

        foreach ($this->requestData['checkbxevent'] as $classId) {
            if (!$classId) continue;

            $notice = new Notice($noticeData);
            $notice->class_id = $classId;
            $notice->save();

            if ($notice) {
                $students = DB::table('student as a')
                    ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                    ->where('a.class_id', $classId)
                    ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id')
                    ->get();

                foreach ($students as $student) {
                    $templateName = 'emergency_message';
                    $parameters = [str_replace('Dear', '', $noticeData['notice_desc'])];
                    Log::info("TestCronJob JOB Failed AFter parameter Whatsapp Message");

                    if ($student->phone_no) {
                        $result = app('App\Http\Services\WhatsappService')->sendTextMessage(
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
                                'notice_id' => $notice->notice_id,
                                'message_type' => 'short_sms',
                                'created_at' => now()
                            ]);
                        }
                    }
                }
                Log::info("TestCronJob JOB Failed AFter Sending Whatsapp Message");
                sleep(20); // throttle

                $failedMessages = DB::table('redington_webhook_details')
                    ->where('message_type', 'short_sms')
                    ->where('status', 'failed')
                    ->where('sms_sent', 'N')
                    ->get();
                Log::info("TestCronJob JOB Failed AFter getting failed Message");
                foreach ($failedMessages as $failed) {
                    $message = $noticeData['notice_desc'] . ". Login to school application for details - AceVentura";
                    $temp_id = '1107161354408119887';

                    
                    $sms_status = app('App\Http\Services\SmsService')->sendSms($failed->phone_no, $message, $temp_id);
                    Log::info("TestCronJob JOB Failed AFter sending text Message",$sms_status);
                    $messagestatus = $sms_status['data']['status'] ?? null;

                    if ($messagestatus == "success") {
                        DB::table('redington_webhook_details')->where('webhook_id', $failed->webhook_id)->update(['sms_sent' => 'N']);
                        Log::info("TestCronJob JOB Failed AFter saving text Message");
                    }
                    Log::info([
                        'sms_status' => json_encode($sms_status['data']),
                        'stu_teacher_id' => $failed->stu_teacher_id,
                        'notice_id' => $notice->notice_id,
                        'phone_no' => $failed->phone_no
                    ]);

                    NoticeSmsLog::create([
                        'sms_status' => json_encode($sms_status['data']),
                        'stu_teacher_id' => $failed->stu_teacher_id,
                        'notice_id' => $notice->notice_id,
                        'phone_no' => $failed->phone_no,
                        'sms_date' => Carbon::now()->format('Y/m/d')
                    ]);
                     Log::info("TestCronJob JOB Failed AFter Model Calling");
                }
            }
        }
    } catch (\Exception $e) {
        \Log::error('Job failed: ' . $e->getMessage());
        throw $e;  // re-throw to allow retries/failure handling
    }
    }
}
