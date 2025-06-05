<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;
use App\Http\Services\SmsService;
use App\Http\Services\WhatsAppService;

class StaffShortSMSsavePublish implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $unq;
    protected $nsmsdata;

    public function __construct($unq, $nsmsdata)
    {
        $this->unq = $unq;
        $this->nsmsdata = $nsmsdata;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
       $staffnoticedata = DB::table('staff_notice')->where('unq_id', $this->unq)->get();

        foreach ($staffnoticedata as $staffnotice) {

            $teacherphone = DB::table('teacher')
                ->where('teacher_id', $staffnotice->teacher_id)
                ->select('phone')
                ->first();
            if($teacherphone){
            $phone_no = $teacherphone->phone;
            
                $templateName = 'staff_notice';
            $parameters = [$this->nsmsdata['notice_desc']];

            $result = app('App\Http\Services\WhatsAppService')->sendTextMessage(
                $phone_no,
                $templateName,
                $parameters
            );

            if (isset($result['code']) && isset($result['message'])) {
                // Log::warning("Rate limit hit: Too many messages to same user");
            } else {
                $wamid = $result['messages'][0]['id'];
                $phone_no = $result['contacts'][0]['input'];
                $message_type = 'staff_sms_notice';

                DB::table('redington_webhook_details')->insert([
                    'wa_id' => $wamid,
                    'phone_no' => $phone_no,
                    'stu_teacher_id' => $staffnotice->teacher_id,
                    'notice_id' => $staffnotice->t_notice_id,
                    'message_type' => $message_type,
                    'created_at' => now(),
                ]);
            }

            }
            
        }

        sleep(20); // Optional: may be removed and handled in retry/delay mechanism

        foreach ($staffnoticedata as $staffnotice) {
            $leftmessages = DB::table('redington_webhook_details')
                ->where('sms_sent', 'N')
                ->where('status', 'failed')
                ->where('message_type', 'staff_sms_notice')
                ->where('notice_id', $staffnotice->t_notice_id)
                ->get();

            foreach ($leftmessages as $leftmessage) {

                DB::table('staff_notice_sms_log')->insert([
                    'teacher_id' => $leftmessage->stu_teacher_id,
                    'notice_id' => $leftmessage->notice_id,
                    'phone_no' => $leftmessage->phone_no,
                    'sms_date' => now()->toDateString(),
                ]);
                $message = 'Dear Staff, '.$this->nsmsdata['notice_desc'].". Login @ https://sms.arnoldcentralschool.org for details.-EvolvU";
                    $sms_status = app('App\Http\Services\SmsService')->sendSms($leftmessage->phone_no, $message, $temp_id);
                    $messagestatus = $sms_status['data']['status'] ?? null;

                    if ($messagestatus == "success") {
                        DB::table('redington_webhook_details')->where('webhook_id', $leftmessage->webhook_id)->update(['sms_sent' => 'N']);
                        DB::table('staff_notice_sms_log')->where('notice_id',$leftmessage->notice_id)->update(['sms_status'=>'Success','sms_sent'=>'Y']);
                        
                        
                    }

            }
        }
    }
}
