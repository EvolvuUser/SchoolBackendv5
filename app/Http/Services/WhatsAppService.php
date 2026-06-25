<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $userId;
    protected $password;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = 'https://mediaapi.smsgupshup.com/GatewayAPI/rest';
        $this->userId = '2000266228';
        $this->password = 'PzMg*u$Y';
    }

    public function sendTextMessage($phoneNumber, $templateName = null, $parameters = [])
    {
        try {
            $message = implode("\n", $parameters);

            $response = Http::get($this->apiUrl, [
                'userid' => $this->userId,
                'password' => $this->password,
                'send_to' => $phoneNumber,
                'v' => '1.1',
                'format' => 'json',
                'msg_type' => 'TEXT',
                'method' => 'SENDMESSAGE',
                'msg' => $message,
            ]);

            Log::channel('whatsapp')->info('Gupshup Response', [
                'phone' => $phoneNumber,
                'message' => $message,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('WhatsApp Error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
