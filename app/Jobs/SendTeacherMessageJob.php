<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use DB;

class SendTeacherMessageJob implements ShouldQueue
{
    use Queueable;

    public array $teacherIds;
    public string $message;
    public string $message_type;

    public function __construct(array $teacherIds, string $message, string $message_type)
    {
        $this->teacherIds = $teacherIds;
        $this->message = $message;
        $this->message_type = $message_type;
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
        $teacherids = implode(',', array_map('intval', $this->teacherIds));
        $teacherphones = DB::select("select phone,teacher_id from teacher where teacher_id IN ($teacherids)");
        $webhookIds = [];
        if ($whatsappintegration == 'Y') {
            foreach ($teacherphones as $teacherphone) {
                if ($teacherphone->phone) {
                    $phone_no = $teacherphone->phone;

                    $templateName = 'staff_notice';
                    $parameters = [$this->message];

                    $result = app('App\Http\Services\WhatsAppService')->sendTextMessage(
                        $phone_no,
                        $templateName,
                        $parameters
                    );

                    if (isset($result['code']) && isset($result['message'])) {
                        $message_type = $this->message_type;
                        $webhookIds[] = DB::table('redington_webhook_details')->insertGetId([
                            'wa_id' => null,
                            'phone_no' => $phone_no,
                            'stu_teacher_id' => $teacherphone->teacher_id,
                            'message_type' => $message_type,
                            'message' => $this->message,
                            'sms_sent' => 'N',
                            'status' => 'failed',
                            'created_at' => now(),
                        ]);
                    } else {
                        $wamid = $result['messages'][0]['id'];
                        $phone_no = $result['contacts'][0]['input'];
                        $message_type = $this->message_type;

                        $webhookIds[] = DB::table('redington_webhook_details')->insertGetId([
                            'wa_id' => $wamid,
                            'phone_no' => $phone_no,
                            'stu_teacher_id' => $teacherphone->teacher_id,
                            'message_type' => $message_type,
                            'message' => $this->message,
                            'created_at' => now(),
                        ]);
                    }
                }
            }
            sleep(20);
            $leftmessages = DB::table('redington_webhook_details')
                ->where('sms_sent', 'N')
                ->where('status', 'failed')
                ->whereIn('webhook_id', $webhookIds)
                ->get();
            foreach ($leftmessages as $leftmessage) {
                $temp_id = '1107164450693700526';
                // $message = 'Dear Staff, ' . $this->message . '. Login @ ' . $websiteurl . ' for details.-EvolvU';
                $message = 'Dear Staff, ' . $this->message . '. Login @ school for details.-EvolvU';
                $sms_status = app('App\Http\Services\SmsService')->sendSms($leftmessage->phone_no, $message, $temp_id);
                $messagestatus = $sms_status['data']['status'] ?? null;

                if ($messagestatus == 'success') {
                    DB::table('redington_webhook_details')->where('webhook_id', $leftmessage->webhook_id)->update(['sms_sent' => 'Y']);
                }
            }
        }
        if ($smsintegration == 'Y') {
            foreach ($teacherphones as $teacherphone) {
                if ($teacherphone->phone) {
                    $phone_no = $teacherphone->phone;
                    $webhook_id = DB::table('redington_webhook_details')->insertGetId([
                        'wa_id' => null,
                        'phone_no' => $phone_no,
                        'stu_teacher_id' => $teacherphone->teacher_id,
                        'message_type' => $this->message_type,
                        'message' => $this->message,
                        'sms_sent' => 'N',
                        'status' => 'failed',
                        'created_at' => now(),
                    ]);
                    $temp_id = '1107164450693700526';
                    // $message = 'Dear Staff, ' . $this->message . '. Login @ ' . $websiteurl . ' for details.-EvolvU';
                    $message = 'Dear Staff, ' . $this->message . '. Login @ school for details.-EvolvU';

                    $sms_status = app('App\Http\Services\SmsService')->sendSms($phone_no, $message, $temp_id);
                    $messagestatus = $sms_status['data']['status'] ?? null;

                    if ($messagestatus == 'success') {
                        DB::table('redington_webhook_details')->where('webhook_id', $webhook_id)->update(['sms_sent' => 'Y']);
                    }
                }
            }
        }
    }
}
