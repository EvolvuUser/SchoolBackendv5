<?php

namespace App\Jobs;

use App\Http\Services\SmartMailer;
use App\Http\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendUserIdParentsWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $students;

    public function __construct(array $students)
    {
        $this->students = $students;
    }

    public function handle(): void
    {
        try {
            $schoolSettings = getSchoolSettingsData();

            if (
                ($schoolSettings->whatsapp_integration ?? 'N') !== 'Y' ||
                !isWhatsappMessageEnabled('parent_login_details')
            ) {
                return;
            }

            $schoolName = $schoolSettings->institute_name ?? 'School';
            $defaultPassword = $schoolSettings->default_pwd ?? '';
            $sentMessages = [];
            foreach ($this->students as $student) {
                $phoneNo = $student['phone_no'] ?? null;

                if (!$phoneNo) {
                    continue;
                }

                $studentName = trim($student['student_name'] ?? '');
                $userId = $student['user_id'] ?? '';

                $message = "Dear Parent,\n";
                $message .= "Welcome to {$schoolName} application powered by EvolvU.";

                if (!empty($studentName)) {
                    $message .= "{$studentName} is registered in the application.";
                }

                $message .= 'For Android Users: https://play.google.com/store/apps/details?id=in.aceventura.evolvuschool ';
                $message .= 'For Iphone Users: https://apps.apple.com/in/app/evolvu-smart-school-parent/id6738838553 ';
                $message .= 'Your login credentials are: ';
                $message .= "User Id: {$userId} ";
                $message .= "Password: {$defaultPassword} ";
                $message .= "You may change your password after login.\n";
                $message .= "Please check the school application for more details.\n";
                $message .= '– Evolvu';

                Log::info('Parent WhatsApp Message', [
                    'student_id' => $student['student_id'] ?? null,
                    'parent_id' => $student['parent_id'] ?? null,
                    'phone_no' => $phoneNo,
                    'student_name' => $studentName,
                    'user_id' => $userId,
                    'message' => $message,
                ]);

                $result = app(WhatsAppService::class)->sendTextMessage(
                    $phoneNo,
                    null,
                    [$message]
                );

                if (isset($result['code']) && isset($result['message'])) {
                    DB::table('redington_webhook_details')->insert([
                        'wa_id' => null,
                        'phone_no' => $phoneNo,
                        'stu_teacher_id' => $student['student_id'] ?? null,
                        'notice_id' => $student['parent_id'] ?? null,
                        'message_type' => 'parent_login_details',
                        'status' => 'failed',
                        'sms_sent' => 'N',
                        'created_at' => now(),
                    ]);

                    Log::warning('Parent login details WhatsApp failed', [
                        'student_id' => $student['student_id'] ?? null,
                        'phone_no' => $phoneNo,
                        'response' => $result,
                    ]);

                    continue;
                }

                $sentMessages[] = DB::table('redington_webhook_details')->insert([
                    'wa_id' => $result['response']['id'] ?? ($result['messages'][0]['id'] ?? null),
                    'phone_no' => $phoneNo,
                    'stu_teacher_id' => $student['student_id'] ?? null,
                    'notice_id' => $student['parent_id'] ?? null,
                    'message_type' => 'parent_login_details',
                    'created_at' => now(),
                ]);
            }

            sleep(20);
            foreach ($sentMessages as $item) {
                $status = DB::table('redington_webhook_details')
                    ->where('wa_id', $item['wa_id'])
                    ->value('status');

                if ($status === 'failed') {
                    $studentData = DB::table('student')
                        ->join('contact_details', 'student.parent_id', '=', 'contact_details.id')
                        ->join('user_master', 'student.parent_id', '=', 'user_master.reg_id')
                        ->where('student.student_id', $item->stu_teacher_id)
                        ->select(
                            'student.isNew',
                            'student.first_name',
                            'student.last_name',
                            'student.student_id',
                            'student.parent_id',
                            'contact_details.email_id',
                            'contact_details.m_emailid',
                            'contact_details.phone_no',
                            'user_master.user_id',
                            'user_master.password'
                        )
                        ->first();

                    if (!$studentData) {
                        continue;
                    }

                    $f_emailid = $studentData->email_id;
                    $m_emailid = $studentData->m_emailid;
                    $user_id = $studentData->user_id;
                    $first_name = $studentData->first_name;
                    $last_name = $studentData->last_name;
                    $studentName = trim($first_name . ' ' . $last_name);

                    $settingsData = getSchoolSettingsData();

                    $schoolName = $settingsData->institute_name;
                    $defaultPassword = $settingsData->default_pwd;
                    $shortName = $settingsData->short_name;
                    $supportEmail = $settingsData->support_email_id;

                    $subject = 'Welcome to the ' . $schoolName . ' application powered by EvolvU.';

                    $textmsg = 'Dear Parent,<br/><br/>
                        Welcome to ' . $schoolName . ' application powered by EvolvU.
                        <br/><br/>
                        ' . $studentName . ' is registered in the application.<br/><br/>

                        You can download our app from Play Store and App Store.<br/>

                        For Android Users:
                        <a href="https://play.google.com/store/apps/details?id=in.aceventura.evolvuschool">
                        https://play.google.com/store/apps/details?id=in.aceventura.evolvuschool
                        </a><br/>

                        For iPhone Users:
                        <a href="https://apps.apple.com/in/app/evolvu-smart-school-parent/id6738838553">
                        https://apps.apple.com/in/app/evolvu-smart-school-parent/id6738838553
                        </a><br/><br/>

                        Here are your login credentials:<br/>
                        User ID: ' . $user_id . '<br/>
                        Password: ' . $defaultPassword . '<br/><br/>

                        You may change your password after login.<br/>
                        If you face any issues please write to us at ' . $supportEmail . '<br/><br/>

                        Regards,<br/>
                        ' . $shortName . ' Support<br/>
                        Team EvolvU';

                    $mailer = app(\App\Http\Services\SmartMailer::class);

                    if (!empty($f_emailid)) {
                        $mailer->send(
                            $f_emailid,
                            $subject,
                            'emails.parent_login_details',
                            [
                                'subject' => $subject,
                                'textmsg' => $textmsg,
                            ]
                        );
                    }

                    if (!empty($m_emailid) && $m_emailid != $f_emailid) {
                        $mailer->send(
                            $m_emailid,
                            $subject,
                            'emails.parent_login_details',
                            [
                                'subject' => $subject,
                                'textmsg' => $textmsg,
                            ]
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('SendUserIdParentsWhatsAppJob failed', [
                'students_count' => count($this->students),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
