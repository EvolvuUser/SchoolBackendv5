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

class StaffShortSMSsavePublish implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $unq;
    protected $nsmsdata;

    public function __construct($unq, $nsmsdata)
    {
        $this->unq = $unq;
        $this->nsmsdata = $nsmsdata;
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
                    $parameters = [$this->nsmsdata['notice_desc']];

                    $result = app('App\Http\Services\WhatsAppService')->sendTextMessage(
                        $phone_no,
                        $templateName,
                        $parameters
                    );

                    if (isset($result['code']) && isset($result['message'])) {
                        $message_type = 'staff_sms_notice';
                        DB::table('redington_webhook_details')->insert([
                            'wa_id' => null,
                            'phone_no' => $phone_no,
                            'stu_teacher_id' => $staffnotice->teacher_id,
                            'notice_id' => $staffnotice->t_notice_id,
                            'message_type' => $message_type,
                            'sms_sent' => 'N',
                            'status' => 'failed',
                            'created_at' => now(),
                        ]);
                    } else {
                        $wamid = $result['messages'][0]['id'];
                        $phone_no = $result['contacts'][0]['input'];
                        $message_type = 'staff_sms_notice';

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
                    ->where('message_type', 'staff_sms_notice')
                    ->where('notice_id', $staffnotice->t_notice_id)
                    ->get();

                foreach ($leftmessages as $leftmessage) {
                    DB::table('staff_notice_sms_log')->insert([
                        'teacher_id' => $leftmessage->stu_teacher_id,
                        'notice_id' => $leftmessage->notice_id,
                        'phone_no' => $leftmessage->phone_no,
                        'sms_date' => now()->toDateString(),
                    ]);
                    $temp_id = '1107164450693700526';
                    $message = 'Dear Staff, ' . $this->nsmsdata['notice_desc'] . '. Login @ ' . $websiteurl . ' for details.-EvolvU';
                    $sms_status = app('App\Http\Services\SmsService')->sendSms($leftmessage->phone_no, $message, $temp_id);
                    $messagestatus = $sms_status['data']['status'] ?? null;

                    if ($messagestatus == 'success') {
                        DB::table('redington_webhook_details')->where('webhook_id', $leftmessage->webhook_id)->update(['sms_sent' => 'Y']);
                        DB::table('staff_notice_sms_log')->where('notice_id', $leftmessage->notice_id)->update(['sms_status' => 'Success', 'sms_sent' => 'Y']);
                    }
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
                    DB::table('staff_notice_sms_log')->insert([
                        'teacher_id' => $staffnotice->teacher_id,
                        'notice_id' => $staffnotice->t_notice_id,
                        'phone_no' => $phone_no,
                        'sms_date' => now()->format('Y/m/d'),
                    ]);
                    $temp_id = '1107164450693700526';
                    $message = 'Dear Staff, ' . $this->nsmsdata['notice_desc'] . '. Login @ ' . $websiteurl . ' for details.-EvolvU';
                    Log::info('message', ['messafe', [$message]]);
                    $sms_status = app('App\Http\Services\SmsService')->sendSms($phone_no, $message, $temp_id);
                    $messagestatus = $sms_status['data']['status'] ?? null;
                    Log::info('message', ['messafe', [$sms_status]]);

                    if ($messagestatus == 'success') {
                        DB::table('staff_notice_sms_log')->where('notice_id', $staffnotice->t_notice_id)->update(['sms_status' => 'Success', 'sms_sent' => 'Y']);
                    }
                }
            }
        }
    }
}
