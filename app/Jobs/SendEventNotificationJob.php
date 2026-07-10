<?php

namespace App\Jobs;

use App\Http\Services\SmsService;
use App\Http\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use DB;
use Log;

class SendEventNotificationJob implements ShouldQueue
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        $schoolsettings = getSchoolSettingsData();
        $whatsappintegration = $schoolsettings->whatsapp_integration;
        $smsintegration = $schoolsettings->sms_integration;
        $websiteurl = $schoolsettings->website_url;
        if ($whatsappintegration == 'Y' && isWhatsappMessageEnabled('event')) {
            foreach ($this->data['login_type'] as $loginType) {
                if (strtoupper($loginType) === 'P') {
                    foreach ($this->data['class_ids'] as $classId) {
                        $parents = DB::table('contact_details')
                            ->join('student', 'student.parent_id', '=', 'contact_details.id')
                            ->where('student.class_id', $classId)
                            ->select('contact_details.phone_no', 'student.student_id')
                            ->get();

                        foreach ($parents as $parent) {
                            $message = "Dear Parent,\n";
                            $message .= 'You are invited for this event: ' . cleanMessageText($this->data['title']) . ".\n";
                            $message .= "Please check the school application for more details.\n";
                            $message .= '– Evolvu';

                            Log::info('Parent Event WhatsApp Message', [
                                'student_id' => $parent->student_id,
                                'message' => $message
                            ]);

                            $result = app('App\Http\Services\WhatsAppService')
                                ->sendTextMessage(
                                    $parent->phone_no,
                                    null,
                                    [$message]
                                );

                            // WhatsApp Failed
                            if (isset($result['code']) && isset($result['message'])) {
                                Log::warning('Parent Event WhatsApp Failed', [
                                    'phone' => $parent->phone_no,
                                    'response' => $result
                                ]);

                                DB::table('redington_webhook_details')->updateOrInsert(
                                    [
                                        'phone_no' => $parent->phone_no,
                                        'notice_id' => $this->data['unq_id'],
                                        'message_type' => 'event'
                                    ],
                                    [
                                        'wa_id' => null,
                                        'stu_teacher_id' => $parent->student_id,
                                        'status' => 'failed',
                                        'sms_sent' => 'N',
                                        'updated_at' => now(),
                                        'created_at' => now()
                                    ]
                                );
                            } else {
                                $wamid = $result['messages'][0]['id']
                                    ?? $result['response']['id']
                                    ?? null;

                                $phone_no = $result['contacts'][0]['input']
                                    ?? $parent->phone_no;

                                DB::table('redington_webhook_details')->updateOrInsert(
                                    [
                                        'wa_id' => $wamid
                                    ],
                                    [
                                        'phone_no' => $phone_no,
                                        'stu_teacher_id' => $parent->student_id,
                                        'notice_id' => $this->data['unq_id'],
                                        'message_type' => 'event',
                                        'updated_at' => now(),
                                        'created_at' => now()
                                    ]
                                );
                            }
                        }
                    }
                } else {
                    $users = DB::table('teacher')
                        ->join('user_master', 'user_master.reg_id', '=', 'teacher.teacher_id')
                        ->where('user_master.role_id', $loginType)
                        ->select('teacher.phone', 'teacher.name', 'teacher.teacher_id')
                        ->get();

                    foreach ($users as $user) {
                        $teacherName = Str::title(strtolower($user->name));

                        if ($user->phone) {
                            $message = 'Dear ' . $teacherName . ",\n";
                            $message .= 'You are invited for this event: ' . cleanMessageText($this->data['title']) . ".\n";
                            $message .= "Please check the school application for more details.\n";
                            $message .= '– Evolvu';

                            Log::info('Teacher Event WhatsApp Message', [
                                'teacher_id' => $user->teacher_id,
                                'message' => $message
                            ]);

                            $result = app('App\Http\Services\WhatsAppService')
                                ->sendTextMessage(
                                    $user->phone,
                                    null,
                                    [$message]
                                );

                            // WhatsApp Failed
                            if (isset($result['code']) && isset($result['message'])) {
                            } else {
                                $wamid = $result['response']['id'] ?? null;

                                DB::table('redington_webhook_details')->updateOrInsert(
                                    [
                                        'wa_id' => $wamid
                                    ],
                                    [
                                        'phone_no' => $user->phone,
                                        'stu_teacher_id' => $user->teacher_id,
                                        'notice_id' => $this->data['unq_id'],
                                        'message_type' => 'event',
                                        'updated_at' => now(),
                                        'created_at' => now()
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
        if ($smsintegration == 'Y') {
            foreach ($this->data['login_type'] as $loginType) {
                if (strtoupper($loginType) === 'P') {
                    foreach ($this->data['class_ids'] as $classId) {
                        $parents = DB::table('contact_details')
                            ->join('student', 'student.parent_id', '=', 'contact_details.id')
                            ->where('student.class_id', $classId)
                            ->select('contact_details.phone_no', 'student.student_id')
                            ->get();

                        foreach ($parents as $parent) {
                            if ($parent->phone_no) {
                                $message = 'Dear Parent,Your are invited for this event:' . $this->data['title'] . '. Login to school application for details - AceVentura';
                                $temp_id = '1107161354408119887';
                                $sms_status = app('App\Http\Services\SmsService')->sendSms($parent->phone_no, $message, $temp_id);
                            }
                        }
                    }
                } else {
                    $users = DB::table('teacher')
                        ->join('user_master', 'user_master.reg_id', '=', 'teacher.teacher_id')
                        ->where('user_master.role_id', $loginType)
                        ->select('teacher.phone', 'teacher.name', 'teacher.teacher_id')
                        ->get();

                    foreach ($users as $user) {
                        $teacherName = Str::title(strtolower($user->name));
                        $templateName = 'emergency_message';
                        $parameters = [$teacherName . ',You are invited for this event:' . $this->data['title']];

                        if ($user->phone) {
                            $temp_id = '1107164450693700526';
                            $message = 'Dear Staff,You are invited for this event: ' . $this->data['title'] . '. Login @ ' . $websiteurl . ' for details.-EvolvU';
                            $sms_status = app('App\Http\Services\SmsService')->sendSms($parent->phone_no, $message, $temp_id);
                        }
                    }
                }
            }
        }
    }
}
