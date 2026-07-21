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

            if (($schoolSettings->whatsapp_integration ?? 'N') !== 'Y') {
                return;
            }

            $schoolName = $schoolSettings->institute_name ?? 'School';
            $defaultPassword = $schoolSettings->default_pwd ?? '';
            $shortName = $schoolSettings->short_name ?? 'School';
            $supportEmail = $schoolSettings->support_email_id ?? '';

            foreach ($this->students as $student) {
                $phoneNo = $student['phone_no'] ?? null;

                if (!$phoneNo) {
                    continue;
                }

                $studentName = trim($student['student_name'] ?? '');
                $userId = $student['user_id'] ?? '';

                $message = "Dear Parent,\n";
                $message .= "Welcome to {$schoolName} application powered by EvolvU.\n";

                if (!empty($studentName)) {
                    $message .= "{$studentName} is registered in the application.\n";
                }

                $message .= "Your login credentials are:\n";
                $message .= "User Id: {$userId}\n";
                $message .= "Password: {$defaultPassword}\n";
                $message .= "You may change your password after login.\n";

                if (!empty($supportEmail)) {
                    $message .= "For support, write to {$supportEmail}.\n";
                }

                $message .= "Regards,\n{$shortName} Support\nTeam Evolvu";

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
