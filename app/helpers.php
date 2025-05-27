<?php

use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

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
    $url =config('externalapis.EVOLVU_URL').'/get_school_details';

     $response = Http::asMultipart()->post($url, [
        [
            'name' => 'short_name',
            'contents' => 'SACS', 
        ],
     ]);

     if ($response->successful()) {
         return $response->json();
     } else {
         return ['error' => 'Unable to fetch school details'];
     }    
}

function upload_student_profile_image_into_folder($studentId,$filename,$doc_type_folder,$newImageData){
    $globalVariables = App::make('global_variables');
    $parent_app_url = $globalVariables['parent_app_url'];
    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
    $url = $parent_app_url . 'upload_student_profile_image_into_folder';

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
    $globalVariables = App::make('global_variables');
    $parent_app_url = $globalVariables['parent_app_url'];
    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
    $url = $parent_app_url.'upload_teacher_profile_image_into_folder';
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

function upload_guardian_profile_image_into_folder($id,$filename,$doc_type_folder,$base64File){
     $globalVariables = App::make('global_variables');
    $parent_app_url = $globalVariables['parent_app_url'];
    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
    $url = $parent_app_url.'upload_guardian_profile_image_into_folder';
         Log::info('Student ID: ' . $id . ' | Filename: ' . $filename . ' | Doc Type Folder: ' . $doc_type_folder);


     $response = Http::asMultipart()->post($url, [
        [
            'name' => 'short_name',
            'contents' => 'SACS', 
        ],
        [
            'name'=>'parent_id',
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


function upload_father_profile_image_into_folder($id,$filename,$doc_type_folder,$base64File){
    $globalVariables = App::make('global_variables');
    $parent_app_url = $globalVariables['parent_app_url'];
    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
    $url = $parent_app_url.'upload_father_profile_image_into_folder';
         Log::info('Student ID: ' . $id . ' | Filename: ' . $filename . ' | Doc Type Folder: ' . $doc_type_folder);


     $response = Http::asMultipart()->post($url, [
        [
            'name' => 'short_name',
            'contents' => 'SACS', 
        ],
        [
            'name'=>'parent_id',
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

function upload_mother_profile_image_into_folder($id,$filename,$doc_type_folder,$base64File){
    $globalVariables = App::make('global_variables');
    $parent_app_url = $globalVariables['parent_app_url'];
    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
    $url = $parent_app_url.'upload_mother_profile_image_into_folder';
         Log::info('Student ID: ' . $id . ' | Filename: ' . $filename . ' | Doc Type Folder: ' . $doc_type_folder);


     $response = Http::asMultipart()->post($url, [
        [
            'name' => 'short_name',
            'contents' => 'SACS', 
        ],
        [
            'name'=>'parent_id',
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

function upload_qrcode_into_folder($filename,$doc_type_folder,$base64File){
    $globalVariables = App::make('global_variables');
    $parent_app_url = $globalVariables['parent_app_url'];
    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
    $url = $parent_app_url.'upload_qrcode_into_folder';
         


     $response = Http::asMultipart()->post($url, [
        [
            'name' => 'short_name',
            'contents' => 'SACS', 
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

function upload_files_for_laravel($filename,$datafile, $uploadDate, $docTypeFolder, $noticeId)
    {
        // API URL
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        $url = $codeigniter_app_url.'index.php/AdminApi/upload_files_for_laravel';

        // Prepare the data array with dynamic values
        $data = [
            'short_name' => 'SACS',
            'upload_date' => $uploadDate,
            'doc_type_folder' => $docTypeFolder,
            'notice_id' => $noticeId,
            'filename' => $filename,  // Dynamic filenames passed as argument
            'datafile' => $datafile,  // Dynamic base64 encoded files passed as argument
        ];


        // Send the data to the external API
        try {
            $response = Http::post($url, $data); // Send the data to the external API

            // Check if the response is successful
            if ($response->successful()) {
                return $response->json(); // Return the response as JSON
            } else {
                return ['error' => 'Failed to upload files', 'status' => $response->status()]; // Handle errors
            }
        } catch (\Exception $e) {
            // Handle any exceptions that may occur
            return ['error' => $e->getMessage()];
        }
    }

    function delete_uploaded_files_for_laravel ($filename,$uploadDate, $docTypeFolder, $noticeId)
    {
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        // API URL
        $url = $codeigniter_app_url.'index.php/AdminApi/delete_uploaded_files_for_laravel';

        // Prepare the data array with dynamic values
        $data = [
            'short_name' => 'SACS',
            'upload_date' => $uploadDate,
            'doc_type_folder' => $docTypeFolder,
            'notice_id' => $noticeId,
            'filename' => $filename, 
        ];


        // Send the data to the external API
        try {
            $response = Http::post($url, $data); // Send the data to the external API

            // Check if the response is successful
            if ($response->successful()) {
                return $response->json(); // Return the response as JSON
            } else {
                return ['error' => 'Failed to delete files', 'status' => $response->status()]; // Handle errors
            }
        } catch (\Exception $e) {
            // Handle any exceptions that may occur
            return ['error' => $e->getMessage()];
        }
    }

     function get_parent_student_data_by_class( $section_id, $acd_yr)
    {
        return DB::table('student as a')
            ->join('parent as b', 'a.parent_id', '=', 'b.parent_id')
            ->join('class as c','c.class_id','=','a.class_id')
            ->join('section as d','d.section_id','=','a.section_id')
            ->where('a.isDelete', '=', 'N')
            ->where('a.section_id', '=', $section_id)
            ->where('a.academic_yr', '=', $acd_yr)
            ->orderByRaw('roll_no, CAST(a.reg_no AS UNSIGNED)')
            ->select('a.*','b.*','c.name as classname','d.name as sectionname')
            ->get();
    }

    function check_health_activity_data_exist_for_studentid($student_id)
    {
        $records = DB::table('health_activity_record')
             ->where('student_id', $student_id)
             ->get();
        return $records->toArray();
    }

    function get_student_name($student_id)
    {
        // Perform the join query
        $results = DB::table('student as a')
            ->join('parent as b', 'a.parent_id', '=', 'b.parent_id')
            ->where('a.student_id', $student_id)
            ->select('a.first_name', 'a.mid_name', 'a.last_name')
            ->get();

        $student_name = "";

        // Loop through the results
        foreach ($results as $row) {
            if (!empty($row->first_name) && $row->first_name !== "No Data") {
                $student_name = $row->first_name;
            }
            if (!empty($row->mid_name) && $row->mid_name !== "No Data") {
                $student_name .= " " . $row->mid_name;
            }
            if (!empty($row->last_name) && $row->last_name !== "No Data") {
                $student_name .= " " . $row->last_name;
            }
        }

        return $student_name;
    }

    function get_student_parent_info($student_id, $acd_yr)
    {
        // dd($acd_yr);
        $result = DB::table('student as s')
            ->join('parent as p', 's.parent_id', '=', 'p.parent_id')
            ->join('user_master as u', 's.parent_id', '=', 'u.reg_id')
            ->join('class as c', 's.class_id', '=', 'c.class_id')
            ->join('section as d', 's.section_id', '=', 'd.section_id')
            ->leftJoin('house as e', 's.house', '=', 'e.house_id')
            ->where('s.student_id', $student_id)
            ->where('s.academic_yr', '2021-2022')
            ->where('u.role_id', 'P')
            ->select(
                's.*', 'p.parent_id', 'p.father_name', 'p.father_occupation', 'p.f_office_add', 'p.f_office_tel',
                'p.f_mobile', 'p.f_email', 'p.mother_occupation', 'p.m_office_add', 'p.m_office_tel',
                'p.mother_name', 'p.m_mobile', 'p.m_emailid', 'p.parent_adhar_no', 'u.user_id',
                'c.name as class_name', 'd.name as sec_name', 'e.house_name','p.m_dob','p.m_blood_group','p.f_dob','p.m_adhar_no','p.f_blood_group'
            )
            ->get();

        return $result->toArray(); 
    }

    function get_class_section_of_student($student_id)
    {
        $result = DB::table('student')
            ->join('class', 'student.class_id', '=', 'class.class_id')
            ->join('section', 'student.section_id', '=', 'section.section_id')
            ->where('student.student_id', $student_id)
            ->select('class.name as classname', 'section.name as sectionname')
            ->first(); 

        if ($result) {
            return $result->classname . ' ' . $result->sectionname;
        }

        return null; 
    }

    function get_previous_student_id($student_id)
    {
        $result = DB::table('student')
            ->where('student_id', $student_id)
            ->value('prev_year_student_id'); 

        return $result; 
    }

    function createUserInEvolvu($user_id)
    {
        $payload = [
            'user_id' => $user_id,
            'school_id' => 1,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(config('externalapis.EVOLVU_URL') . '/user_create_post', $payload);

            if ($response->successful()) {
                return $response->json(); // return decoded JSON
            } else {
                Log::error('Evolvu API error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Evolvu API request failed: ' . $e->getMessage());
            return null;
        }
    }

    function get_active_academic_year()
    {
        $setting = DB::table('settings')
                    ->where('active', 'Y')
                    ->first();
    
        return $setting ? $setting->academic_yr : null;
    }
    
    function formatIndianCurrency($amount) {
    $decimal = "";
    if (strpos($amount, '.') !== false) {
        list($amount, $decimal) = explode('.', $amount);
        $decimal = '.' . $decimal;
    }

    $len = strlen($amount);
    if ($len > 3) {
        $last3digits = substr($amount, -3);
        $restUnits = substr($amount, 0, $len - 3);
        $restUnits = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $restUnits);
        return $restUnits . "," . $last3digits . $decimal;
    } else {
        return $amount . $decimal;
    }
}

    function createStaffUser($userId, $role)
    {
        $url = config('externalapis.EVOLVU_URL').'/create_staff_userid';

        $response = Http::post($url, [
            'user_id' => $userId,
            'role' => $role,
            'short_name' => 'SACS',
        ]);

        // Log the API response
        Log::info('External API response:', [
            'url' => $url,
            'status' => $response->status(),
            'response_body' => $response->body(),
        ]);

        return $response;
    }

    function getParentUserId($parentId)
    {
        $result = DB::table('user_master as a')
            ->where('a.role_id', 'P')
            ->where('a.reg_id', $parentId)
            ->select('a.user_id')
            ->first();
    
        return $result ? $result->user_id : null;
    }
    
     function deleteParentUser($currentUserName)
    {
        $url = config('externalapis.EVOLVU_URL') . '/user_delete_post';

        $payload = [
            'user_id' => $userId,
            'school_id' => '1',
        ];

        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, $payload);

        Log::info('Delete User API response:', [
            'url' => $url,
            'request_body' => $payload,
            'status' => $response->status(),
            'response_body' => $response->body(),
        ]);

        return $response;
    }

    function edit_user_id($username, $currentUserName)
    {
        $user_data = [
            'user_id'      => $username,
            'school_id'    => '1',
            'old_user_id'  => $currentUserName,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(config('externalapis.EVOLVU_URL') . '/user_edit_post', $user_data);

            // You can log or return the response for debugging
            return $response; // or $response->body();
        } catch (\Exception $e) {
            // Log the error or handle it gracefully
            \Log::error('API call failed: ' . $e->getMessage());
            return null;
        }
    }

    function getPendingLessonCountForTeacher($academicYear, $regId)
    {
        $subquery = DB::table('lesson_plan')
            ->join('chapters', 'lesson_plan.chapter_id', '=', 'chapters.chapter_id')
            ->where('chapters.isDelete', '!=', 'Y')
            ->where('lesson_plan.approve', '!=', 'Y')
            ->where('lesson_plan.academic_yr', $academicYear)
            ->where('lesson_plan.reg_id', $regId)
            ->select('lesson_plan.unq_id')
            ->groupBy('lesson_plan.unq_id');

        $pending = DB::table(DB::raw("({$subquery->toSql()}) as subquery_alias"))
            ->mergeBindings($subquery) 
            ->count();

        return $pending;
    }

    function delete_staff_user_id($user_id, $role)
    {
        $user_data = [
            'user_id'      => $user_id,
            'short_name'    => 'SACS',
            'role'  => $role,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(config('externalapis.EVOLVU_URL') . '/delete_staff_userid', $user_data);

            // You can log or return the response for debugging
            return $response; // or $response->body();
        } catch (\Exception $e) {
            // Log the error or handle it gracefully
            \Log::error('API call failed: ' . $e->getMessage());
            return null;
        }
    }
