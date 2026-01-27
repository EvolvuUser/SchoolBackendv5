<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Http\Services\WhatsAppService;
use Carbon\Carbon;
use App\Models\NoticeSmsLog;
use App\Http\Services\SmsService;


class SendReminderRemarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public int $studentId,
        public array $remarkData,
        public int $remarkId
    ) {}

    /**
     * Execute the job.
     */
    public function handle()
    {
        $student = DB::table('student as a')
            ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
            ->where('a.student_id', $this->studentId)
            ->select('b.phone_no', 'b.email_id' , 'a.student_id')
            ->first();

        if (!$student || !$student->phone_no) {
            return;
        }

        $schoolSettings = getSchoolSettingsData();

        $whatsappFailed = false;

        // WhatsApp
        if ($schoolSettings->whatsapp_integration === 'Y') {
            $result = app(WhatsAppService::class)->sendTextMessage(
                $student->phone_no,
                'emergency_message',
                ['Parent,' . $this->remarkData['remark_desc']]
            );

            // Insert webhook log
            if (isset($result['code']) && isset($result['message'])) {

                // WhatsApp failed
                $whatsappFailed = true;

                DB::table('redington_webhook_details')->insert([
                    'wa_id' => null,
                    'phone_no' => $student->phone_no,
                    'stu_teacher_id' => $this->studentId,
                    'notice_id' => $this->remarkId,
                    'message_type' => 'remarkforstudent',
                    'status' => 'failed',
                    'sms_sent' => 'N',
                    'created_at' => now()
                ]);
            } else {
                DB::table('redington_webhook_details')->insert([
                    'wa_id' => $result['messages'][0]['id'] ?? null,
                    'phone_no' => $result['contacts'][0]['input'] ?? $student->phone_no,
                    'stu_teacher_id' => $student->student_id,
                    'notice_id' => $this->remarkId,
                    'message_type' => 'remarkforstudent',
                    'created_at' => now()
                ]);
            }

            sleep(20);
            if (
                $schoolSettings->sms_integration === 'Y'
                && $whatsappFailed === true
            ) {

                $failed = DB::table('redington_webhook_details')
                    ->where('message_type', 'remarkforstudent')
                    ->where('status', 'failed')
                    ->where('notice_id', $this->remarkId)
                    ->where('sms_sent', 'N')
                    ->first();

                $sms_status = app(SmsService::class)->sendSms(
                    $student->phone_no,
                    'Dear Parent,' . $this->remarkData['remark_desc'] . '. Login to school application for details - AceVentura',
                    '1107161354408119887'
                );

                $messagestatus = $sms_status['data']['status'] ?? null;

                if ($messagestatus === 'success') {
                    DB::table('redington_webhook_details')
                        ->where('notice_id', $this->remarkId)
                        ->where('stu_teacher_id', $this->studentId)
                        ->update(['sms_sent' => 'Y']);
                }

                NoticeSmsLog::create([
                    'sms_status' => json_encode($sms_status['data']),
                    'stu_teacher_id' => $failed->stu_teacher_id,
                    'notice_id' => $this->remarkId,
                    'phone_no' => $student->phone_no,
                    'sms_date' => Carbon::now()->format('Y/m/d')
                ]);
            }

        }

        // SMS
        if ($schoolSettings->sms_integration === 'Y' && !$whatsappFailed) {
            app(SmsService::class)->sendSms(
                $student->phone_no,
                'Dear Parent,' . $this->remarkData['remark_desc'],
                '1107161354408119887'
            );
        }

        // Push notifications
        $tokens = getTokenDataParentId($this->studentId);
        foreach ($tokens as $item) {
            sendnotificationusinghttpv1([
                'token' => $item->token,
                'notification' => [
                    'title' => 'Remark',
                    'description' => $this->remarkData['remark_desc'],
                ]
            ]);
        }
    }
}
