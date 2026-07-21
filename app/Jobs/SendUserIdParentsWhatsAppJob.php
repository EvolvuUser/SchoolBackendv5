<?php

namespace App\Jobs;

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

            foreach ($this->students as $student) {
                $phoneNo = $student['phone_no'] ?? null;

                if (!$phoneNo) {
                    continue;
                }

                $studentName = trim($student['student_name'] ?? '');
                $userId = $student['user_id'] ?? '';

                $message = "Dear Parent\n, Welcome to {$schoolName} application powered by EvolvU. ";

                if (!empty($studentName)) {
                    $message .= "{$studentName} is registered in the application. ";
                }

                $message .= 'For Android Users: https://play.google.com/store/apps/details?id=in.aceventura.evolvuschool ';
                $message .= 'For Iphone Users: https://apps.apple.com/in/app/evolvu-smart-school-parent/id6738838553 ';
                $message .= 'Your login credentials are: ';
                $message .= "User Id: {$userId} ";
                $message .= "Password: {$defaultPassword} ";
                $message .= "You may change your password after login.\n ";
                $message .= "Please check the school application for more details.\n ";
                $message .= "- Evolvu\n";

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

                DB::table('redington_webhook_details')->insert([
                    'wa_id' => $result['response']['id'] ?? ($result['messages'][0]['id'] ?? null),
                    'phone_no' => $phoneNo,
                    'stu_teacher_id' => $student['student_id'] ?? null,
                    'notice_id' => $student['parent_id'] ?? null,
                    'message_type' => 'parent_login_details',
                    'created_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SendUserIdParentsWhatsAppJob failed', [
                'students_count' => count($this->students),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
