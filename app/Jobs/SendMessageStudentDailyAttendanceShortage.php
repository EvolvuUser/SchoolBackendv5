<?php

namespace App\Jobs;

use App\Http\Services\SmsService;
use App\Http\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendMessageStudentDailyAttendanceShortage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $students;
    protected $message;

    public function __construct($students, $message)
    {
        $this->students = $students;
        $this->message = $message;
    }

    public function handle(): void
    {
        Log::info('Hello', ['students' => $this->students]);
        $students = DB::table('student as a')
            ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
            ->whereIn('a.student_id', $this->students)
            ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id', 'a.first_name', 'a.mid_name', 'a.last_name')
            ->get();

        $schoolsettings = getSchoolSettingsData();
        $whatsappintegration = $schoolsettings->whatsapp_integration;
        $smsintegration = $schoolsettings->sms_integration;
        Log::info('Hello', ['students' => $students]);
        if ($whatsappintegration == 'Y') {
            $wamids = [];
            $insertedIds = [];
            foreach ($students as $student) {
                $templateName = 'emergency_message';
                $parameters = ['Parent, ' . $this->message];
                Log::info('TestCronJob JOB Failed AFter parameter Whatsapp Message', ['parameter' => $parameters]);

                if ($student->phone_no) {
                    $result = app('App\Http\Services\WhatsAppService')->sendTextMessage(
                        $student->phone_no,
                        $templateName,
                        $parameters
                    );

                    if (isset($result['code']) && isset($result['message'])) {
                        $insertedIds[] = DB::table('redington_webhook_details')->insert([
                            'wa_id' => null,
                            'phone_no' => $student->phone_no,
                            'stu_teacher_id' => $student->student_id,
                            'message' => $this->message,
                            'message_type' => 'student_daily_attendance_shortage',
                            'created_at' => now()
                        ]);
                    } else {
                        $wamid = $result['messages'][0]['id'];
                        $phone_no = $result['contacts'][0]['input'];

                        $wamids[] = $wamid;  // Store for later processing

                        $insertedIds[] = DB::table('redington_webhook_details')->insert([
                            'wa_id' => $wamid,
                            'phone_no' => $phone_no,
                            'stu_teacher_id' => $student->student_id,
                            'message' => $this->message,
                            'message_type' => 'student_daily_attendance_shortage',
                            'created_at' => now()
                        ]);
                    }
                }
            }
            sleep(20);
            $leftmessages = DB::table('redington_webhook_details')
                ->whereIn('webhook_id', $insertedIds)
                ->where('status', 'failed')
                ->where('sms_sent', 'N')
                ->where('message_type', 'student_daily_attendance_shortage')
                ->get();
            Log::info('Left messages', ['Left' => [$leftmessages]]);
            foreach ($leftmessages as $leftmessage) {
                $message = 'Dear Parent, ' . $this->message . '. Login to school application for details - AceVentura';
                $temp_id = '1107161354408119887';

                $sms_status = app('App\Http\Services\SmsService')->sendSms($leftmessage->phone_no, $message, $temp_id);
                Log::info('TestCronJob JOB Failed AFter sending text Message', $sms_status);
                $messagestatus = $sms_status['data']['status'] ?? null;

                if ($messagestatus == 'success') {
                    DB::table('redington_webhook_details')->where('webhook_id', $leftmessage->webhook_id)->update(['sms_sent' => 'Y']);
                    Log::info('TestCronJob JOB Failed AFter saving text Message');
                }
            }
        }
        if ($smsintegration == 'Y') {
            foreach ($students as $student) {
                if ($student->phone_no) {
                    $message = 'Dear Parent,' . $this->message . '. Login to school application for details - AceVentura';
                    $temp_id = '1107161354408119887';
                    $sms_status = app('App\Http\Services\SmsService')->sendSms($student->phone_no, $message, $temp_id);
                }
            }
        }
    }
}
