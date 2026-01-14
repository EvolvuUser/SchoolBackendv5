<?php

namespace App\Jobs;

use App\Http\Services\SmsService;
use App\Http\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffNoticeJobSavePublish implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $unq;
    protected $noticeDataTemplate;

    public function __construct($unq, $noticeDataTemplate)
    {
        $this->unq = $unq;
        $this->noticeDataTemplate = $noticeDataTemplate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $schoolsettings = getSchoolSettingsData();
        $whatsappintegration = $schoolsettings->whatsapp_integration;
        $smsintegration = $schoolsettings->sms_integration;
        $websiteurl = $schoolsettings->website_url;
        $staffnoticedata = DB::table('staff_notice')->where('unq_id', $this->unq)->get();

        if ($whatsappintegration == 'Y') {
            foreach ($staffnoticedata as $staffnotice) {
                $teacherphone = DB::table('teacher')
                    ->where('teacher_id', $staffnotice->teacher_id)
                    ->select('phone')
                    ->first();
                if ($teacherphone) {
                    $phone_no = $teacherphone->phone;

                    $templateName = 'staff_notice';
                    $parameters = [$this->noticeDataTemplate['notice_desc']];

                    $result = app('App\Http\Services\WhatsAppService')->sendTextMessage(
                        $phone_no,
                        $templateName,
                        $parameters
                    );

                    if (isset($result['code']) && isset($result['message'])) {
                        $message_type = 'staff_notice';

                        DB::table('redington_webhook_details')->insert([
                            'wa_id' => null,
                            'phone_no' => $phone_no,
                            'stu_teacher_id' => $staffnotice->teacher_id,
                            'notice_id' => $staffnotice->t_notice_id,
                            'message_type' => $message_type,
                            'status' => 'failed',
                            'sms_sent' => 'N',
                            'created_at' => now(),
                        ]);
                    } else {
                        $wamid = $result['messages'][0]['id'];
                        $phone_no = $result['contacts'][0]['input'];
                        $message_type = 'staff_notice';

                        DB::table('redington_webhook_details')->insert([
                            'wa_id' => $wamid,
                            'phone_no' => $phone_no,
                            'stu_teacher_id' => $staffnotice->teacher_id,
                            'notice_id' => $staffnotice->t_notice_id,
                            'message_type' => $message_type,
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            sleep(20);

            foreach ($staffnoticedata as $staffnotice) {
                $leftmessages = DB::table('redington_webhook_details')
                    ->where('sms_sent', 'N')
                    ->where('status', 'failed')
                    ->where('message_type', 'staff_notice')
                    ->where('notice_id', $staffnotice->t_notice_id)
                    ->get();

                foreach ($leftmessages as $leftmessage) {
                    DB::table('daily_sms_for_teacher')->insert([
                        'teacher_id' => $leftmessage->stu_teacher_id,
                        'phone' => $leftmessage->phone_no,
                        'homework' => 0,
                        'notice' => 0,
                        'note' => 0,
                        'staff_notice' => 1,
                        'sms_date' => now(),
                    ]);
                    DB::table('redington_webhook_details')->where('notice_id', $leftmessage->notice_id)->where('message_type', 'staff_notice')->update(['sms_sent' => 'Y']);
                }
            }
        }
        if ($smsintegration == 'Y') {
            foreach ($staffnoticedata as $staffnotice) {
                $teacherphone = DB::table('teacher')
                    ->where('teacher_id', $staffnotice->teacher_id)
                    ->select('phone')
                    ->first();
                if ($teacherphone) {
                    $phone_no = $teacherphone->phone;
                    DB::table('daily_sms_for_teacher')->insert([
                        'teacher_id' => $staffnotice->teacher_id,
                        'phone' => $phone_no,
                        'homework' => 0,
                        'notice' => 0,
                        'note' => 0,
                        'staff_notice' => 1,
                        'sms_date' => now()->format('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }
}
