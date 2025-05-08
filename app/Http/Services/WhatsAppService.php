<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    protected $apiUrl = 'https://backend.whatsapp.redingtongroup.com/direct-apis/t1/messages';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.redington.api_key');
    }

    public function sendTextMessage($phoneNumber, $templateName, $parameters = [])
    {
        $payload = [
            "to" => $phoneNumber,
            "type" => "template",
            "template" => [
                "name" => $templateName,
                "language" => [
                    "code" => "en" 
                ],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => array_map(function ($param) {
                            return ["type" => "text", "text" => $param];
                        }, $parameters),
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl, $payload);

        return $response->json();
    }
}