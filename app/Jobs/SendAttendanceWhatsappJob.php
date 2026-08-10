<?php

namespace App\Jobs;

use App\Http\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendAttendanceWhatsappJob implements ShouldQueue
{
    use Queueable;

    protected $student_id;
    protected $dateatt;

    public function __construct($student_id, $dateatt)
    {
        $this->student_id = $student_id;
        $this->dateatt = $dateatt;
    }

    public function handle(): void
    {
        // ==================================================
        // SCHOOL WHATSAPP SETTING
        // ==================================================

        $schoolsettings = getSchoolSettingsData();

        $whatsappintegration =
            $schoolsettings->whatsapp_integration;

        if (
            $whatsappintegration == 'Y' &&
            isWhatsappMessageEnabled('attendance')
        ) {
            // Proceed with sending WhatsApp message
        } else {
            return;
        }

        // ==================================================
        // CHECK ATTENDANCE AGAIN
        // ==================================================

        $attendance = DB::table('attendance')
            ->where('student_id', $this->student_id)
            ->where('only_date', $this->dateatt)
            ->where('attendance_status', 1)
            ->first();

        /*
         * If attendance was changed from absent to present
         * before this job executes, don't send WhatsApp.
         */

        if (!$attendance) {
            Log::info(
                'Attendance WhatsApp skipped - student is not absent',
                [
                    'student_id' => $this->student_id,
                    'date' => $this->dateatt
                ]
            );

            return;
        }

        // ==================================================
        // GET STUDENT
        // ==================================================

        $student = DB::table('student')
            ->where('student_id', $this->student_id)
            ->select(
                'student_id',
                'first_name',
                'parent_id'
            )
            ->first();

        if (!$student) {
            Log::warning(
                'Attendance WhatsApp skipped - student not found',
                [
                    'student_id' => $this->student_id
                ]
            );

            return;
        }

        // ==================================================
        // GET PARENT CONTACTS
        // ==================================================

        $parents = DB::table('contact_details')
            ->where('id', $student->parent_id)
            ->select('phone_no')
            ->get();

        foreach ($parents as $parent) {
            if (!$parent->phone_no) {
                continue;
            }

            // ==================================================
            // CHECK ALREADY SUCCESSFULLY SENT
            // ==================================================

            $whatsappExists = DB::table(
                'redington_webhook_details'
            )
                ->where(
                    'stu_teacher_id',
                    $this->student_id
                )
                ->where(
                    'phone_no',
                    $parent->phone_no
                )
                ->where(
                    'message_type',
                    'attendance'
                )
                ->where(
                    'status',
                    'success'
                )
                ->whereDate(
                    'created_at',
                    $this->dateatt
                )
                ->exists();

            if ($whatsappExists) {
                Log::info(
                    'Attendance WhatsApp already sent',
                    [
                        'student_id' => $this->student_id,
                        'phone' => $parent->phone_no,
                        'date' => $this->dateatt
                    ]
                );

                continue;
            }

            // ==================================================
            // MESSAGE
            // ==================================================

            $studentName = cleanMessageText(
                $student->first_name
            );

            $message = "Dear Parent,\n";

            $message .=
                "Your ward {$studentName} has been marked absent on "
                . Carbon::parse($this->dateatt)->format('d-m-Y')
                . ".\n";

            $message .=
                "Please check the school application for more details.\n";

            $message .= '– Evolvu';

            // ==================================================
            // LOG MESSAGE
            // ==================================================

            Log::info(
                'Parent Attendance WhatsApp Message',
                [
                    'student_id' => $this->student_id,
                    'phone' => $parent->phone_no,
                    'message' => $message
                ]
            );

            // ==================================================
            // SEND WHATSAPP
            // ==================================================

            $result = app(
                'App\Http\Services\WhatsAppService'
            )->sendTextMessage(
                $parent->phone_no,
                null,
                [$message]
            );

            // ==================================================
            // WHATSAPP FAILED
            // ==================================================

            if (
                isset($result['code']) &&
                isset($result['message'])
            ) {
                Log::warning(
                    'Parent Attendance WhatsApp Failed',
                    [
                        'student_id' => $this->student_id,
                        'phone' => $parent->phone_no,
                        'response' => $result
                    ]
                );

                DB::table(
                    'redington_webhook_details'
                )->updateOrInsert(
                    [
                        'phone_no' => $parent->phone_no,
                        'stu_teacher_id' => $this->student_id,
                        'message_type' => 'attendance',
                    ],
                    [
                        'wa_id' => null,
                        'status' => 'failed',
                        'sms_sent' => 'N',
                        'notice_id' => null,
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );
            } else {
                // ==================================================
                // WHATSAPP SUCCESS
                // ==================================================

                $wamid =
                    $result['messages'][0]['id']
                        ?? $result['response']['id']
                        ?? null;

                $phone_no =
                    $result['contacts'][0]['input']
                        ?? $parent->phone_no;

                DB::table(
                    'redington_webhook_details'
                )->updateOrInsert(
                    [
                        'wa_id' => $wamid
                    ],
                    [
                        'phone_no' => $phone_no,
                        'stu_teacher_id' => $this->student_id,
                        'notice_id' => null,
                        'message_type' => 'attendance',
                        'status' => 'success',
                        'sms_sent' => 'Y',
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );

                Log::info(
                    'Parent Attendance WhatsApp Sent',
                    [
                        'student_id' => $this->student_id,
                        'phone' => $phone_no,
                        'wa_id' => $wamid
                    ]
                );
            }
        }
    }
}
