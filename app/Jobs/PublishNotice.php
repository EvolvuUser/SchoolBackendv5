<?php

namespace App\Jobs;

use App\Http\Services\SmsService;
use App\Http\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishNotice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $unq_id;

    public function __construct($unq_id)
    {
        $this->unq_id = $unq_id;
    }

    public function handle(): void
    {
        $notices = DB::table('notice')->where('unq_id', $this->unq_id)->get();

        foreach ($notices as $notice) {
            DB::table('notice')
                ->where('unq_id', $notice->unq_id)
                ->update(['publish' => 'Y']);

            $noticemessage = $notice->notice_desc;

            $parents = DB::table('student as a')
                ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                ->where('a.class_id', $notice->class_id)
                ->select('b.phone_no', 'a.student_id', 'a.parent_id')
                ->get();

            foreach ($parents as $student) {
                $templateName = 'emergency_message';
                $parameters = [$noticemessage];

                try {
                    if ($student->phone_no) {
                        $result = app('App\Http\Services\WhatsAppService')->sendTextMessage(
                            $student->phone_no,
                            $templateName,
                            $parameters
                        );

                        if (isset($result['code']) && isset($result['message'])) {
                            Log::warning('Rate limit hit', []);
                        } else {
                            DB::table('redington_webhook_details')->insert([
                                'wa_id' => $result['messages'][0]['id'] ?? null,
                                'phone_no' => $result['contacts'][0]['input'] ?? $student->phone_no,
                                'stu_teacher_id' => $student->student_id,
                                'notice_id' => $notice->notice_id,
                                'message_type' => 'notice',
                                'created_at' => now()
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('WhatsApp error: ' . $e->getMessage());
                }
            }
        }

        sleep(20);

        $leftMessages = DB::table('redington_webhook_details')
            ->where('message_type', 'notice')
            ->where('status', 'failed')
            ->where('notice_id', $notice->notice_id)
            ->where('sms_sent', 'N')
            ->get();

        foreach ($leftMessages as $leftMessage) {
            $message = $noticemessage . '. Login to school application for details - AceVentura';
            $temp_id = '1107161354408119887';

            $sms = app('App\Http\Controllers\Controller')->send_sms(
                $leftMessage->phone_no,
                $message,
                $temp_id
            );

            $response = $sms->getData(true);
            $status = $response['data']['status'] ?? 'failed';

            if ($status === 'success') {
                DB::table('redington_webhook_details')
                    ->where('webhook_id', $leftMessage->webhook_id)
                    ->update(['sms_sent' => 'Y']);
            }

            $parent_id = DB::table('student')
                ->where('student_id', $leftMessage->stu_teacher_id)
                ->value('parent_id');

            DB::table('daily_sms')->updateOrInsert(
                [
                    'parent_id' => $parent_id,
                    'student_id' => $leftMessage->stu_teacher_id,
                ],
                [
                    'phone' => $leftMessage->phone_no,
                    'notice' => DB::raw('notice + 1'),
                    'sms_date' => Carbon::now()->format('Y/m/d'),
                ]
            );
        }
    }
}
