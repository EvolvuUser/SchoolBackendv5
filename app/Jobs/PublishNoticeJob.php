<?php

namespace App\Jobs;

use App\Http\Controllers\NoticeController;
use App\Http\Services\WhatsAppService;
use App\Models\Notice;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function handle(): void
    {
        $parentnotices = DB::table('notice')
            ->where('unq_id', $this->unq)
            ->where('academic_yr', $this->customClaims)
            ->get();

        foreach ($parentnotices as $parentnotice) {
            $students = DB::table('student as a')
                ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                ->where('a.class_id', $parentnotice->class_id)
                ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id')
                ->get();
            $schoolsettings = getSchoolSettingsData();
            $whatsappintegration = $schoolsettings->whatsapp_integration;
            $smsintegration = $schoolsettings->sms_integration;
            if ($whatsappintegration == 'Y') {
                foreach ($students as $student) {
                    $templateName = 'emergency_message';
                    $parameters = [$parentnotice->notice_desc];

                    if ($student->phone_no) {
                        $result = app('App\Http\Services\WhatsAppService')->sendTextMessage(
                            $student->phone_no,
                            $templateName,
                            $parameters
                        );

                        if (isset($result['code']) && isset($result['message'])) {
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

                sleep(20);
                $leftmessages = DB::table('redington_webhook_details')
                    ->where('message_type', 'notice')
                    ->where('status', 'failed')
                    ->where('notice_id', $parentnotice->notice_id)
                    ->where('sms_sent', 'N')
                    ->get();

                foreach ($leftmessages as $leftmessage) {
                    $parentidstudentdetails = DB::table('student')->where('student_id', $leftmessage->stu_teacher_id)->first();
                    $parent_id = $parentidstudentdetails->parent_id;
                    $smsdata = DB::table('daily_sms')
                        ->where('parent_id', $parent_id)
                        ->where('student_id', $leftmessage->stu_teacher_id)
                        ->get();
                    $smsdatacount = count($smsdata);
                    if ($smsdatacount == '0') {
                        $sdata = [
                            'parent_id' => $parent_id,
                            'student_id' => $leftmessage->stu_teacher_id,
                            'phone' => $leftmessage->phone_no,
                            'homework' => 0,
                            'remark' => 0,
                            'achievement' => 0,
                            'note' => 0,
                            'notice' => 1,
                            'sms_date' => now()
                        ];

                        DB::table('daily_sms')->insert($sdata);
                    } else {
                        $smsdata[0]->notice = 1 + $smsdata[0]->notice;
                        $smsdata[0]->sms_date = now();

                        DB::table('daily_sms')
                            ->where('parent_id', $smsdata[0]->parent_id)
                            ->where('student_id', $smsdata[0]->student_id)
                            ->update(['notice' => $smsdata[0]->notice,
                                'sms_date' => $smsdata[0]->sms_date]);
                    }
                }
            }
            if ($smsintegration == 'Y') {
                foreach ($students as $student) {
                    $smsdata = DB::table('daily_sms')
                        ->where('parent_id', $student->parent_id)
                        ->where('student_id', $student->student_id)
                        ->get();
                    $smsdatacount = count($smsdata);
                    if ($smsdatacount == '0') {
                        $sdata = [
                            'parent_id' => $student->parent_id,
                            'student_id' => $student->student_id,
                            'phone' => $student->phone_no,
                            'homework' => 0,
                            'remark' => 0,
                            'achievement' => 0,
                            'note' => 0,
                            'notice' => 1,
                            'sms_date' => now()
                        ];

                        DB::table('daily_sms')->insert($sdata);
                    } else {
                        $smsdata[0]->notice = 1 + $smsdata[0]->notice;
                        $smsdata[0]->sms_date = now();

                        DB::table('daily_sms')
                            ->where('parent_id', $smsdata[0]->parent_id)
                            ->where('student_id', $smsdata[0]->student_id)
                            ->update(['notice' => $smsdata[0]->notice,
                                'sms_date' => $smsdata[0]->sms_date]);
                    }
                }
                $section_data = DB::table('section')
                    ->where('class_id', $parentnotice->class_id)
                    ->where('academic_yr', $this->customClaims)
                    ->get();
                foreach ($section_data as $section) {
                    $classteachers = DB::table('class_teachers')
                        ->where('class_id', $section->class_id)
                        ->where('section_id', $section->section_id)
                        ->get();
                    foreach ($classteachers as $classteacher) {
                        $teacherinfo = DB::table('teacher')->where('teacher_id', $classteacher->teacher_id)->first();
                        $teachersmsdata = DB::table('daily_sms_for_teacher')
                            ->where('teacher_id', $teacherinfo->teacher_id)
                            ->where('class_id', $section->class_id)
                            ->where('section_id', $section->section_id)
                            ->get();
                        $teachersmsdatacount = count($teachersmsdata);
                        if ($teachersmsdatacount == '0') {
                            $sdata = [
                                'teacher_id' => $classteacher->teacher_id,
                                'class_id' => $section->class_id,
                                'section_id' => $section->section_id,
                                'phone' => $teacherinfo->phone,
                                'homework' => 0,
                                'note' => 0,
                                'notice' => 1,
                                'sms_date' => now()
                            ];

                            DB::table('daily_sms_for_teacher')->insert($sdata);
                        } else {
                            $teachersmsdata[0]->notice = 1 + $teachersmsdata[0]->notice;
                            $teachersmsdata[0]->sms_date = now();

                            DB::table('daily_sms_for_teacher')
                                ->where('teacher_id', $classteacher->teacher_id)
                                ->where('class_id', $section->class_id)
                                ->where('section_id', $section->section_id)
                                ->update(['notice' => $teachersmsdata[0]->notice,
                                    'sms_date' => $teachersmsdata[0]->sms_date]);
                        }
                    }
                }
            }

            $tokendata = DB::table('student as a')
                ->select('b.token', 'b.user_id', 'b.parent_teacher_id', 'b.login_type', 'a.parent_id', 'a.student_id')
                ->join('user_tokens as b', 'a.parent_id', '=', 'b.parent_teacher_id')
                ->where('a.class_id', $parentnotice->class_id)
                ->where('b.login_type', 'P')
                ->get();
            foreach ($tokendata as $token) {
                $data = [
                    'token' => $token->token,
                    'notification' => [
                        'title' => 'Notice',
                        'description' => $parentnotice->notice_desc
                    ]
                ];
                sendnotificationusinghttpv1($data);
            }
        }
    }
}
