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
    
    protected $unq;
    protected $customClaims;
    public function __construct($unq , $customClaims)
    {
        $this->unq = $unq;
        $this->customClaims = $customClaims;
    }

    
    public function handle(): void
    {
        $parentnotices = DB::table('notice')
                           ->where('unq_id', $this->unq)
                           ->where('academic_yr',$this->customClaims)
                           ->get();
        foreach($parentnotices as $parentnotice){
            $students = DB::table('student as a')
                            ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                            ->where('a.class_id', $parentnotice->class_id)
                            ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id')
                            ->get();
            $schoolsettings = getSchoolSettingsData();
            $whatsappintegration = $schoolsettings->whatsapp_integration;
            $smsintegration = $schoolsettings->sms_integration;
            if($whatsappintegration == 'Y'){
                foreach ($students as $student) {
                            $templateName = 'emergency_message';
                            $parameters = [str_replace('Dear', '', $parentnotice->notice_desc)];
                            Log::info("TestCronJob JOB Failed AFter parameter Whatsapp Message");

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
                                        'message_type' => 'short_sms',
                                        'created_at' => now()
                                    ]);
                                }
                            }
                        }
                        sleep(20); 
                        $failedMessages = DB::table('redington_webhook_details')
                                            ->where('message_type', 'short_sms')
                                            ->where('status', 'failed')
                                            ->where('notice_id',$parentnotice->notice_id)
                                            ->where('sms_sent', 'N')
                                            ->get();
                                        foreach ($failedMessages as $failed) {
                                            $message = $parentnotice->notice_desc . ". Login to school application for details - AceVentura";
                                            $temp_id = '1107161354408119887';

                                            
                                            $sms_status = app('App\Http\Services\SmsService')->sendSms($failed->phone_no, $message, $temp_id);
                                            
                                            $messagestatus = $sms_status['data']['status'] ?? null;

                                            if ($messagestatus == "success") {
                                                DB::table('redington_webhook_details')->where('webhook_id', $failed->webhook_id)->update(['sms_sent' => 'Y']);
                                                
                                            }
                                            

                                            NoticeSmsLog::create([
                                                'sms_status' => json_encode($sms_status['data']),
                                                'stu_teacher_id' => $failed->stu_teacher_id,
                                                'notice_id' => $parentnotice->notice_id,
                                                'phone_no' => $failed->phone_no,
                                                'sms_date' => Carbon::now()->format('Y/m/d')
                                            ]);
                    }

                    $tokens = DB::table('student as a')
                        ->join('user_tokens as b', 'a.parent_id', '=', 'b.parent_teacher_id')
                        ->where('a.class_id', $parentnotice->class_id)
                        ->where('b.login_type', 'P')
                        ->select('b.token', 'b.user_id', 'b.parent_teacher_id', 'a.parent_id', 'a.student_id')
                        ->get();

                    foreach ($tokens as $token) {
                        sendnotificationusinghttpv1([
                            'token' => $token->token,
                            'notification' => [
                                'title' => 'Notice',
                                'description' => $parentnotice->notice_desc
                            ]
                        ]);
                    }
                
            }
            if($smsintegration == 'Y'){
                foreach ($students as $student) {
                    if ($student->phone_no) {
                    $message = $parentnotice->notice_desc . ". Login to school application for details - AceVentura";
                    $temp_id = '1107161354408119887';
                    $sms_status = app('App\Http\Services\SmsService')->sendSms($student->phone_no, $message, $temp_id);
                    
                    $messagestatus = $sms_status['data']['status'] ?? null;
                    if($messagestatus == 'success'){
                        $smssent = 'Y';
                     }
                     else{
                         $smssent = 'N';
                     }
                     if (!empty($student->phone_no)) {
                        DB::table('notice_sms_log')->insert([
                            'stu_teacher_id' => $student->student_id,
                            'notice_id'      => $parentnotice->notice_id,
                            'phone_no'       => $student->phone_no,
                            'sms_status'     => json_encode($sms_status['data']),
                            'sms_sent'       => $smssent,
                            'sms_date'       => now()->format('Y-m-d'),
                        ]);
                     }
                    }
                    
                }
                $section_data = DB::table('section')
                                    ->where('class_id',$parentnotice->class_id)
                                    ->where('academic_yr',$this->customClaims)
                                    ->get();
                foreach ($section_data as $section){
                    $classteachers = DB::table('class_teachers')
                                         ->where('class_id',$section->class_id)
                                         ->where('section_id',$section->section_id)
                                         ->get();
                    foreach ($classteachers as $classteacher){
                        $teacherinfo = DB::table('teacher')->where('teacher_id',$classteacher->teacher_id)->first();
                        if($teacherinfo->phone){
                            $message = $parentnotice->notice_desc . ". Login to school application for details - AceVentura";
                            $temp_id = '1107161354408119887';
                            $sms_status = app('App\Http\Services\SmsService')->sendSms($teacherinfo->phone, $message, $temp_id);
                            Log::info("TestCronJob JOB Failed AFter sending text Message",$sms_status);
                            $messagestatus = $sms_status['data']['status'] ?? null;
                            if($messagestatus == 'success'){
                                $smssent = 'Y';
                             }
                             else{
                                 $smssent = 'N';
                             }
                             if (!empty($teacher->phone)) {
                                DB::table('notice_sms_log')->insert([
                                    'stu_teacher_id' => $classteacher->teacher_id,
                                    'notice_id'      => $parentnotice->notice_id,
                                    'phone_no'       => $teacherinfo->phone,
                                    'sms_status'     => json_encode($sms_status['data']),
                                    'sms_sent'       => $smssent,
                                    'sms_date'       => now()->format('Y-m-d'),
                                ]);
                            
                        }
                        
                        
                    }
                 }
                }
                $tokens = DB::table('student as a')
                        ->join('user_tokens as b', 'a.parent_id', '=', 'b.parent_teacher_id')
                        ->where('a.class_id', $parentnotice->class_id)
                        ->where('b.login_type', 'P')
                        ->select('b.token', 'b.user_id', 'b.parent_teacher_id', 'a.parent_id', 'a.student_id')
                        ->get();

                    foreach ($tokens as $token) {
                        sendnotificationusinghttpv1([
                            'token' => $token->token,
                            'notification' => [
                                'title' => 'Notice',
                                'description' => $parentnotice->notice_desc
                            ]
                        ]);
                    }
                
            
           }

        }

    
    }

}