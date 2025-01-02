<?php

use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

if (!function_exists('getTokenPayload')) {
    function getTokenPayload(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            Log::error('Token not provided');
            return null;
        }

        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            return $payload;
        } catch (\Exception $e) {
            Log::error('Token error: ' . $e->getMessage());
            return null;
        }
    }
}


function googleaccounttoken(){
    $credentialsFilePath = "fcm.json";
    // dd($credentialsFilePath);
    $client = new \Google_Client();
    //  dd($client);
    $client->setAuthConfig($credentialsFilePath);
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    $apiurl = 'https://fcm.googleapis.com/v1/projects/evolvuparentapp/messages:send';
    
    $client->refreshTokenWithAssertion();
    $token = $client->getAccessToken();
    // dd($token);
    $access_token = $token['access_token'];
    //  dd($access_token);
    return $access_token ;
}

function sendnotificationusinghttpv1($data){
    // dd($data);
    // dd($data);
    try{
        $data['apiurl'] = 'https://fcm.googleapis.com/v1/projects/evolvuparentapp/messages:send';
        // dd($data);
        $headers = [
            'Authorization: Bearer ' . googleaccounttoken(),
            'Content-Type:application/json'
        ];
        //  dd($headers);
        $data['headers'] = $headers;
        //  dd($data);
        $fields = [
            'message' => [
                'token' => $data['token'],
                'notification' => [
                    'title' => $data['notification']['title'],
                    'body' => $data['notification']['description']
                ]
            ]
        ];
        $fields = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $data['apiurl']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $data['headers']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
         dd($result);
        curl_close($ch);
        return response(["status"=>true,"data"=>$result]);
    }catch(Exception $e){
        return response(["status"=>false,"message"=>$e->getMessage()]);
    }

}
