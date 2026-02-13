<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Http\Services\WhatsAppService;
use Carbon\Carbon;
use App\Models\NoticeSmsLog;
use App\Http\Services\SmsService;

class IssuedBookMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    public $tries = 3;
    public $timeout = 120;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {

        $memberId     = $this->data['member_id'];
        $bookId       = $this->data['book_id'];
        $copyId       = $this->data['copy_id'];
        $academicYear = $this->data['academic_year'];
        $memberType   = $this->data['member_type'];
        $issueDate    = $this->data['issue_date'];
        $dueDate      = $this->data['due_date'];

        $member = DB::table('issue_return as a')
            ->join('contact_details as b', 'a.member_id', '=', 'b.id')
            ->where('a.member_id', $memberId)
            ->select('b.phone_no', 'b.email_id', 'a.member_id')
            ->first();


        if (!$member || !$member->phone_no) {
            return;
        }

        $schoolSettings = getSchoolSettingsData();

        $whatsappFailed = false;

        // WhatsApp
        if ($schoolSettings->whatsapp_integration === 'Y') {
            $result = app(WhatsAppService::class)->sendTextMessage(
                $member->phone_no,
                'emergency_message',
                ['Library Member,' . $copyId]
            );

            // Insert webhook log
            if (isset($result['code']) && isset($result['message'])) {

                // WhatsApp failed
                $whatsappFailed = true;

                DB::table('redington_webhook_details')->insert([
                    'wa_id' => null,
                    'phone_no' => $member->phone_no,
                    'member_id' => $memberId,
                    'copy_id' => $copyId,
                    'message_type' => 'bookissued',
                    'status' => 'failed',
                    'sms_sent' => 'N',
                    'created_at' => now()
                ]);
            } else {
                DB::table('redington_webhook_details')->insert([
                    'wa_id' => $result['messages'][0]['id'] ?? null,
                    'phone_no' => $result['contacts'][0]['input'] ?? $member->phone_no,
                    'member_id' => $memberId,
                    'copy_id' => $copyId,
                    'message_type' => 'bookissued',
                    'created_at' => now()
                ]);
            }

            sleep(20);
            if (
                $schoolSettings->sms_integration === 'Y'
                && $whatsappFailed === true
            ) {

                $failed = DB::table('redington_webhook_details')
                    ->where('message_type', 'bookissued')
                    ->where('status', 'failed')
                    ->where('copy_id', $copyId)
                    ->where('sms_sent', 'N')
                    ->first();

                $sms_status = app(SmsService::class)->sendSms(
                    $member->phone_no,
                    'Dear Library Member,' . $copyId . 'this book is issued. Login to school application for details - AceVentura',
                    '1107161354408119887'
                );

                $messagestatus = $sms_status['data']['status'] ?? null;

                if ($messagestatus === 'success') {
                    DB::table('redington_webhook_details')
                        ->where('copy_id', $copyId)
                        ->where('member_id', $memberId)
                        ->update(['sms_sent' => 'Y']);
                }

                NoticeSmsLog::create([
                    'sms_status' => json_encode($sms_status['data']),
                    'member_id' => $failed->memberId,
                    'copy_id' => $copyId,
                    'phone_no' => $member->phone_no,
                    'sms_date' => Carbon::now()->format('Y/m/d')
                ]);
            }
        }



        // Push notifications
        $tokens = getTokenDataParentId($memberId);
        foreach ($tokens as $item) {
            sendnotificationusinghttpv1([
                'token' => $item->token,
                'notification' => [
                    'title' => 'Issued Book',
                    'description' => $copyId,
                ]
            ]);
        }
    }
}
