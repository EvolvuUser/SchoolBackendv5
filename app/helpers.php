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
        curl_close($ch);
        if ($result === FALSE) {
            return [
                'status' => 500,
                'message' => 'Failed to send notification. cURL error: ' . curl_error($ch),
                'success' => false
            ];
        }

        // Decode the response from Firebase
        $response = json_decode($result, true);
        if (isset($response['error'])) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to send notification. Firebase error: ' . $response['error']['message'],
                'success' => false
            ]);
        }

        // If Firebase response is valid, return the notification ID or other relevant information
        return response()->json([
            'status' => 200,
            'message' => 'Notification sent successfully.',
            'data' => $response,  // You can modify this to return the relevant part of the response
            'success' => true
        ]);
        return response(["status"=>true,"data"=>$result]);
    }catch(Exception $e){
        return response(["status"=>false,"message"=>$e->getMessage()]);
    }

}


if (!function_exists('getFullName')) {
    /**
     * Join first name, middle name and last name to return full name.
     *
     * @param string $firstName
     * @param string $midName
     * @param string $lastName
     * @return string
     */
    function getFullName($firstName, $midName = '', $lastName = '')
    {
        $fullName = trim($firstName);

        // Add middle name if provided
        if (!empty($midName)) {
            $fullName .= ' ' . trim($midName);
        }

        // Add last name if provided
        if (!empty($lastName)) {
            $fullName .= ' ' . trim($lastName);
        }

        return $fullName;
    }
}


function imagesforall(){
    $cacheKey = 'school_details_cache'; // You can use a custom cache key
    $cachedResponse = Cache::get($cacheKey);

    if ($cachedResponse) {
        // If data is cached, return it
        return $cachedResponse;
    }

     $url = 'https://aceventura.in/demo/evolvuUserService/get_school_details';

     $response = Http::asMultipart()->post($url, [
        [
            'name' => 'short_name',
            'contents' => 'SACS', 
        ],
     ]);

     if ($response->successful()) {
        Cache::put($cacheKey, $response->json(), now()->addHour()); 
         return $response->json();
     } else {
         return ['error' => 'Unable to fetch school details'];
     }    
}

function upload_student_profile_image_into_folder($studentId,$filename,$doc_type_folder,$newImageData){
    $url = 'https://sms.arnoldcentralschool.org/Test_ParentAppService/upload_student_profile_image_into_folder';

     $response = Http::asMultipart()->post($url, [
        [
            'name' => 'short_name',
            'contents' => 'SACS', 
        ],
        [
            'name'=>'student_id',
            'contents'=> $studentId ,
        ],
        [
            'name'=>'filename',
            'contents'=>$filename,
        ],
        [
            'name'=>'doc_type_folder',
            'contents'=>$doc_type_folder,
        ],
        [
            'name'=>'filedata',
            'contents'=>$newImageData,

        ],
     ]);

     if ($response->successful()) {
         return $response->json();
     } else {
         return ['error' => 'Unable to fetch school details'];
     }

}

function upload_teacher_profile_image_into_folder($id,$filename,$doc_type_folder,$base64File){
    $url = 'https://sms.arnoldcentralschool.org/Test_ParentAppService/upload_teacher_profile_image_into_folder';
         Log::info('Student ID: ' . $id . ' | Filename: ' . $filename . ' | Doc Type Folder: ' . $doc_type_folder);


     $response = Http::asMultipart()->post($url, [
        [
            'name' => 'short_name',
            'contents' => 'SACS', 
        ],
        [
            'name'=>'teacher_id',
            'contents'=> $id ,
        ],
        [
            'name'=>'filename',
            'contents'=>$filename,
        ],
        [
            'name'=>'doc_type_folder',
            'contents'=>$doc_type_folder,
        ],
        [
            'name'=>'filedata',
            'contents'=>$base64File,

        ],
     ]);

     if ($response->successful()) {
         Log::info('Successfully fetched school details:', ['response' => $response->json()]);
         return $response->json();
     } else {
         Log::error('Failed to fetch school details:', ['error' => $response->body()]);
         return ['error' => 'Unable to fetch school details'];
     }

}
