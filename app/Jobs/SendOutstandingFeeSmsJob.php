<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Services\SmsService;
use App\Http\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendOutstandingFeeSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $studentInstallments;
    protected $customClaims;
    protected $message;

    
    public function __construct(array $studentInstallments, $customClaims,$message)
    {
        $this->studentInstallments = $studentInstallments;
        $this->customClaims = $customClaims;
        $this->message = $message;
    }

    public function handle(): void
    {
        try {
        Log::info('SendOutstandingFeeSmsJob started');

        foreach ($this->studentInstallments as $studentInstallment) {
            [$studentId, $installment] = explode('^', $studentInstallment);
            $contactno = DB::table('student as a')
                            ->join('contact_details as b', 'a.parent_id', '=', 'b.id')
                            ->where('a.student_id', $studentId)
                            ->select('b.phone_no', 'b.email_id', 'a.parent_id', 'a.student_id')
                            ->first();
            if($contactno->phone_no){
                $templateName = 'emergency_message';
                $parameters = ['Parent, '.$this->message];
                 Log::info('SendOutstandingFeeSmsJob started',$parameters);
                $result = app('App\Http\Services\WhatsAppService')->sendTextMessage(
                                    $contactno->phone_no,
                                    $templateName,
                                    $parameters
                                );
                if (isset($result['code']) && isset($result['message'])) {
                        Log::warning("Rate limit hit", []);
                    } else {
                        $wamid = $result['messages'][0]['id'];
                        $phone_no = $result['contacts'][0]['input'];

                        DB::table('redington_webhook_details')->insert([
                            'wa_id' => $wamid,
                            'phone_no' => $contactno->phone_no,
                            'stu_teacher_id' => $studentId,
                            'notice_id' => $studentInstallment,
                            'message_type' => 'outstanding_fees',
                            'created_at' => now()
                        ]);
                        $data = [
                        'student_id'         =>  $studentId,
                        'parent_id'          => 1111, 
                        'phone_no'           => '0000000000', // replace with actual number if available
                        'installment'        => $installment,
                        'date_last_sms_sent' => Carbon::now()->format('Y-m-d H:i:s'),
                        'academic_yr'        => $this->customClaims,
                    ];

                    $existingLog = DB::table('sms_log_for_outstanding_fees')
                        ->where('student_id', $data['student_id'])
                        ->where('parent_id', $data['parent_id'])
                        ->where('installment', $data['installment'])
                        ->where('academic_yr', $data['academic_yr'])
                        ->first();

                    if ($existingLog) {
                        $data['count_of_sms'] = $existingLog->count_of_sms + 1;

                        DB::table('sms_log_for_outstanding_fees')
                            ->where('sms_log_id', $existingLog->sms_log_id)
                            ->update($data);

                        DB::table('sms_log_for_outstanding_fees_details')->insert([
                            'sms_log_id'     => $existingLog->sms_log_id,
                            'date_sms_sent'  => Carbon::now()->format('Y-m-d H:i:s'),
                        ]);
                    } else {
                        $data['count_of_sms'] = 1;

                        $smsLogId = DB::table('sms_log_for_outstanding_fees')->insertGetId($data);

                        DB::table('sms_log_for_outstanding_fees_details')->insert([
                            'sms_log_id'     => $smsLogId,
                            'date_sms_sent'  => Carbon::now()->format('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        }

            Log::info('SendOutstandingFeeSmsJob finished');
        } catch (\Throwable $e) {
            Log::error('Job failed: ' . $e->getMessage());
        }
    }
}
