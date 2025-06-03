<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Http\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use App\Models\Notice;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;
use App\Http\Controllers\NoticeController;

class PublishNoticeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $noticeIds;
    protected $noticeDesc;

    public function __construct(array $noticeIds, string $noticeDesc)
    {
        $this->noticeIds = $noticeIds;
        $this->noticeDesc = $noticeDesc;
    }

    /**
     * Create a new job instance.
     */

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->noticeIds as $noticeId) {
            $notice = Notice::find($noticeId);
            $classId = $notice->class_id;

            $students = DB::table('student as a')
                ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id')
                ->where('a.class_id', $classId)
                ->get();

            foreach ($students as $student) {
                if ($student->phone_no) {
                    try {
                        $result = app('App\Http\Services\WhatsappService')->sendTextMessage(
                            $student->phone_no,
                            'emergency_message',
                            [$this->noticeDesc]
                        );
                        if (isset($result['code']) && isset($result['message'])) {
                            Log::warning("Rate limit hit", []);
                        } else {
                            DB::table('redington_webhook_details')->insert([
                            'wa_id' => $result['messages'][0]['id'],
                            'phone_no' => $result['contacts'][0]['input'],
                            'stu_teacher_id' => $student->student_id,
                            'notice_id' => $noticeId,
                            'message_type' => 'notice',
                            'created_at' => now()
                        ]);

                        }

                        
                    } catch (\Exception $e) {
                        \Log::error("WhatsApp failed: " . $e->getMessage());
                    }
                }
            }

            // Delay for WhatsApp rate limit
            sleep(20);

            // Fallback SMS
            $failedMessages = DB::table('redington_webhook_details')
                ->where('message_type', 'notice')
                ->where('status', 'failed')
                ->where('sms_sent', 'N')
                ->where('notice_id',$noticeId)
                ->get();

            foreach ($failedMessages as $msg) {
                $student = DB::table('student')->where('student_id', $msg->stu_teacher_id)->first();
                $sms = DB::table('daily_sms')
                    ->where('parent_id', $student->parent_id)
                    ->where('student_id', $msg->stu_teacher_id)
                    ->first();

                if ($sms) {
                    DB::table('daily_sms')
                        ->where('parent_id', $student->parent_id)
                        ->where('student_id', $msg->stu_teacher_id)
                        ->update([
                            'notice' => $sms->notice + 1,
                            'sms_date' => now()
                        ]);
                } else {
                    DB::table('daily_sms')->insert([
                        'parent_id' => $student->parent_id,
                        'student_id' => $msg->stu_teacher_id,
                        'phone' => $msg->phone_no,
                        'homework' => 0,
                        'remark' => 0,
                        'achievement' => 0,
                        'note' => 0,
                        'notice' => 1,
                        'sms_date' => now()
                    ]);
                }
            }

            // Push Notifications
            $tokens = DB::table('student as a')
                ->join('user_tokens as b', 'a.parent_id', '=', 'b.parent_teacher_id')
                ->where('a.class_id', $classId)
                ->where('b.login_type', 'P')
                ->select('b.token', 'b.user_id', 'b.parent_teacher_id', 'a.parent_id', 'a.student_id')
                ->get();

            foreach ($tokens as $token) {
                DB::table('daily_notifications')->insert([
                    'student_id' => $token->student_id,
                    'parent_id' => $token->parent_teacher_id,
                    'homework_id' => 0,
                    'remark_id' => 0,
                    'notice_id' => $noticeId,
                    'notes_id' => 0,
                    'notification_date' => now()->toDateString(),
                    'token' => $token->token,
                ]);

                sendnotificationusinghttpv1([
                    'token' => $token->token,
                    'notification' => [
                        'title' => 'Notice',
                        'description' => $this->noticeDesc
                    ]
                ]);
            }
        }
    }
}
