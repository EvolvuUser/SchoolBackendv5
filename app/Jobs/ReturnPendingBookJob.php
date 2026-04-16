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

class ReturnPendingBookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $members;
    protected $message;
    protected $loginUserName;


    public function __construct($members, $message, $loginUserName)
    {
        $this->members = $members;
        $this->message   = $message;
        $this->loginUserName = $loginUserName;
    }


    public function handle(): void
    {

        $members = DB::table('issue_return as a')
            ->join('book as c', 'a.book_id', '=', 'c.book_id')

            // Student Join (ONLY ONCE)
            ->leftJoin('student as s', function ($join) {
                $join->on('a.member_id', '=', 's.student_id')
                    ->where('a.member_type', '=', 'S');
            })

            // Teacher Join
            ->leftJoin('teacher as t', function ($join) {
                $join->on('a.member_id', '=', 't.teacher_id')
                    ->where('a.member_type', '=', 'T');
            })

            // Contact ONLY for Student
            ->leftJoin('contact_details as b', function ($join) {
                $join->on('s.parent_id', '=', 'b.id')
                    ->where('a.member_type', '=', 'S');
            })

            ->whereIn('a.member_id', $this->members)
            ->whereDate('a.due_date', '<', now())
            ->where(function ($query) {
                $query->whereNull('a.return_date')
                    ->orWhere('a.return_date', '0000-00-00');
            })

            ->select(
                'a.member_id',
                'a.copy_id',
                'c.book_title',
                'a.issue_date',   // ADD
                'a.due_date',

                //  Correct Phone Mapping
                DB::raw("
            CASE 
                WHEN a.member_type = 'T' THEN t.phone
                WHEN a.member_type = 'S' THEN b.phone_no
            END as phone_no
        "),

                // Name Mapping
                DB::raw("
            CASE 
                WHEN a.member_type = 'S' THEN s.first_name
                WHEN a.member_type = 'T' THEN t.name
            END as member_name
        ")
            )

            ->orderBy('a.due_date', 'asc')
            ->get();



        $schoolSettings = getSchoolSettingsData();
        $wamids = [];

        // GROUP BY MEMBER
        $groupedMembers = $members->groupBy(function ($item) {
            return $item->member_id . '_' . $item->phone_no;
        });

        foreach ($groupedMembers as $group) {

            $member = $group->first();

            if (!$member->phone_no) {
                continue;
            }

            // collect book titles
            // $bookTitles = $group->pluck('book_title')->implode(', ');

            // $finalMessage = $member->member_name . ', ' .
            //     'Please return the following books: ' .
            //     $bookTitles . ' ' . ($this->message ?? '');

            // $finalMessage = $member->member_name . ', ' .
            //     'Please return the following books: ' .
            //     $bookTitles .
            //     (!empty($this->message) ? '. ' . $this->message : '.');

            $bookDetails = $group->map(function ($book, $index) {
                $issueDate = date('d/m/Y', strtotime($book->issue_date));
                $dueDate = date('d/m/Y', strtotime($book->due_date));

                return ($index + 1) . ') ' . $book->book_title .
                    ' (Issue: ' . $issueDate . ', Due: ' . $dueDate . ')';
            })->implode("\n");


            $customSection = !empty($this->message)
                ? trim($this->message)
                : "Please return the book(s) to the library tomorrow during short break.";

            $finalMessage = " " . $member->member_name . ",\n\n" .
                "You have not submitted the following issued book(s):\n\n" .
                $bookDetails . "\n\n" .
                $customSection . "\n\n" .
                "Regards\n" .
                $this->loginUserName . "\n" .
                "Library";

            // if (!empty($this->message)) {
            //     $finalMessage = $this->message;
            // } else {
            //     $finalMessage = $finalMessage;
            // }

            // WhatsApp
            if ($schoolSettings->whatsapp_integration === 'Y') {

                $response = app(WhatsAppService::class)->sendTextMessage(
                    $member->phone_no,
                    'emergency_message',
                    [$finalMessage]
                );

                if (!isset($response['code'])) {

                    $wamid = $response['messages'][0]['id'] ?? null;

                    if ($wamid) {
                        $wamids[] = $wamid;

                        foreach ($group as $book) {

                            DB::table('redington_webhook_details')->insert([
                                'wa_id' => $wamid,
                                'phone_no' => $member->phone_no,
                                'stu_teacher_id' => $member->member_id,
                                'notice_id' => $book->copy_id,
                                'message_type' => 'returnBookPending',
                                'created_at' => now()
                            ]);
                        }
                    }
                }
            }
        }

        sleep(20);

        //  SMS Fallback
        if ($schoolSettings->sms_integration === 'Y' && !empty($wamids)) {

            $failedMessages = DB::table('redington_webhook_details')
                ->whereIn('wa_id', $wamids)
                ->where('status', 'failed')
                ->where('sms_sent', 'N')
                ->get();

            foreach ($failedMessages as $failed) {

                $smsMessage = $this->message ?? 'Please return the book.';

                $sms_status = app(SmsService::class)->sendSms(
                    $failed->phone_no,
                    $smsMessage,
                    '1107161354408119887'
                );

                if (($sms_status['data']['status'] ?? null) === 'success') {

                    DB::table('redington_webhook_details')
                        ->where('webhook_id', $failed->webhook_id)
                        ->update(['sms_sent' => 'Y']);
                }
            }
        }
    }
}
