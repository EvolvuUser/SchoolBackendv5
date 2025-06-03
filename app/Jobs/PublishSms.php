<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Services\SmsService;
use App\Http\Services\WhatsAppService;

class PublishSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $unq_id;
    protected $whatsAppService;

    public function __construct($unq_id)
    {
        $this->unq_id = $unq_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $updatesmsnotice = DB::table('notice')->where('unq_id', $this->unq_id)->get();

        foreach ($updatesmsnotice as $notice) {
            $noticemessage = $notice->notice_desc;

            DB::table('notice')->where('unq_id', $notice->unq_id)->update(['publish' => 'Y']);

            $studParentdata = DB::table('student as a')
                ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                ->where('a.class_id', $notice->class_id)
                ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id')
                ->get();

            foreach ($studParentdata as $student) {
                $templateName = 'emergency_message';
                $parameters = [str_replace('Dear', '', $noticemessage)];

                Log::info($parameters);

                if ($student->phone_no) {
                    $result = app('App\Http\Services\WhatsappService')->sendTextMessage(
                        $student->phone_no,
                        $templateName,
                        $parameters
                    );

                    Log::info($result);
                    if (isset($result['code']) && isset($result['message'])) {
                            Log::warning("Rate limit hit", []);
                    } 
                    else {
                    $wamid = $result['messages'][0]['id'];
                    $phone_no = $result['contacts'][0]['input'];
                    $message_type = 'short_sms';

                    DB::table('redington_webhook_details')->insert([
                        'wa_id' => $wamid,
                        'phone_no' => $phone_no,
                        'stu_teacher_id' => $student->student_id,
                        'notice_id' => $notice->notice_id,
                        'message_type' => $message_type,
                        'created_at' => now()
                    ]);
                 }
                }
            }

            sleep(20);

            $leftmessages = DB::table('redington_webhook_details')
                ->where('message_type', 'short_sms')
                ->where('status', 'failed')
                ->where('sms_sent', 'N')
                ->get();

            foreach ($leftmessages as $leftmessage) {
                $message = $noticemessage . ". Login to school application for details - AceVentura";
                $temp_id = '1107161354408119887';

                $sms_status = app('App\Http\Services\SmsService')->sendSms(
                    $leftmessage->phone_no,
                    $message,
                    $temp_id
                );

                Log::info("TestCronJob JOB Failed AFter sending text Message",$sms_status);
                $messagestatus = $sms_status['data']['status'] ?? null;

                if ($messagestatus == "success") {
                    DB::table('redington_webhook_details')->where('webhook_id', $leftmessage->webhook_id)->update(['sms_sent' => 'Y']);
                }

                if ($leftmessage->phone_no != null) {
                    DB::table('notice_sms_log')->insert([
                        'sms_status' => json_encode($sms_status['data']),
                        'stu_teacher_id' => $leftmessage->stu_teacher_id,
                        'notice_id' => $notice->notice_id,
                        'phone_no' => $leftmessage->phone_no,
                        'sms_date' => Carbon::now()->format('Y/m/d')
                    ]);
                }
            }

            $tokens = DB::table('student as a')
                ->join('user_tokens as b', 'a.parent_id', '=', 'b.parent_teacher_id')
                ->where('a.class_id', $notice->class_id)
                ->where('b.login_type', 'P')
                ->select('b.token', 'b.user_id', 'b.parent_teacher_id', 'a.parent_id', 'a.student_id')
                ->get();

            foreach ($tokens as $token) {
                DB::table('daily_notifications')->insert([
                    'student_id' => $token->student_id,
                    'parent_id' => $token->parent_teacher_id,
                    'homework_id' => 0,
                    'remark_id' => 0,
                    'notice_id' => $notice->notice_id,
                    'notes_id' => 0,
                    'notification_date' => now()->toDateString(),
                    'token' => $token->token,
                ]);

                sendnotificationusinghttpv1([
                    'token' => $token->token,
                    'notification' => [
                        'title' => 'Notice',
                        'description' => $notice->notice_desc
                    ]
                ]);
             }
        }
    }
}
