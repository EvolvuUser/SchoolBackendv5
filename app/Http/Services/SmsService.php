<?php 

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    public function sendSms($send_to, $message, $template_id)
    {
        $sender_id = 'ACEVIT';
        $username = 'sacs';
        $apikey = 'A3AB1-5903F';
        $uri = 'http://sms.quicksmsservices.com/sms-panel/api/http/index.php';

        $data = [
            'username' => $username,
            'apikey' => $apikey,
            'apirequest' => 'Text',
            'sender' => $sender_id,
            'route' => 'TRANS',
            'format' => 'JSON',
            'message' => $message,
            'mobile' => $send_to,
            'TemplateID' => $template_id,
        ];

        $response = Http::asForm()->post($uri, $data);

        if ($response->successful()) {
            return [
                'status' => 'success',
                'message' => 'SMS sent successfully',
                'data' => $response->json()
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to send SMS',
                'error' => $response->body()
            ];
        }
    }
}