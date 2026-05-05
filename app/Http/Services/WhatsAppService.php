<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DB;

class WhatsAppService
{
    protected $apiUrl = 'https://backend.whatsapp.redingtongroup.com/direct-apis/t1/messages';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = $this->getApiKey();
    }

    public function sendTextMessage($phoneNumber, $templateName, $parameters = [])
    {
        $languages = ['en', 'en_GB'];

        foreach ($languages as $lang) {
            $payload = [
                'to' => $phoneNumber,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $lang
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => array_map(function ($param) {
                                return [
                                    'type' => 'text',
                                    'text' => $this->sanitizeSingleLine((string) $param)
                                ];
                            }, $parameters),
                        ]
                    ]
                ]
            ];

            Log::channel('whatsapp')->info('WhatsApp Attempt', [
                'phone' => $phoneNumber,
                'language' => $lang,
                'template' => $templateName
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $payload);

            $body = $response->json();

            Log::channel('whatsapp')->info('WhatsApp Response', [
                'language' => $lang,
                'status' => $response->status(),
                'body' => $body
            ]);

            if ($response->successful()) {
                return $body;
            }

            $errorCode = $response->status();

            if ($errorCode == 400) {
                continue;
            }

            return $body;
        }

        return [
            'status' => 'failed',
            'message' => 'All language attempts failed'
        ];
    }

    protected function sanitizeSingleLine(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $text = str_replace(['/n', '\n'], "\n", $text);

        $text = preg_replace('/[\t\x{00A0}]/u', ' ', $text);

        $lines = explode("\n", $text);

        $cleanWords = [];

        foreach ($lines as $line) {
            $line = preg_replace('/\s+/', ' ', $line);
            $line = trim($line);

            if ($line !== '') {
                $words = explode(' ', $line);
                foreach ($words as $w) {
                    if ($w !== '') {
                        $cleanWords[] = $w;
                    }
                }
            }
        }

        return implode(' ', $cleanWords);
    }

    protected function getApiKey()
    {
        $key = DB::table('school_settings')
            ->where('is_active', 'Y')
            ->value('redington_api_key');

        Log::channel('whatsapp')->info('Fetched API Key', [
            'key_preview' => substr($key, 0, 10) . '...'
        ]);

        return $key;
    }
}
