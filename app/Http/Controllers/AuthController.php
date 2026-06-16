<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use DB;
use Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AuthController extends Controller
{
    public function connectByShortName(Request $request)
    {
        $request->validate([
            'short_name' => 'required|string',
        ]);

        $shortName = $request->short_name;
        session(['short_name' => $shortName]);
        return response()->json([
            'status' => 200,
            'message' => 'Connected to school DB',
            'success' => true
        ]);
    }

    // Modified By Manish Kumar Sharma 27-03-2025
    public function login(Request $request)
    {
        $credentials = $request->only('user_id', 'password');
        $remember_me = $request->rememberme;

        try {
            if ($request->has('short_name') && !empty($request->short_name)) {
                $shortName = $request->short_name;
            } else {
                $shortName = 'SACS';
            }
            $shortName = $request->short_name;
            if ($request->has('short_name') && !empty($request->short_name)) {
                $shortName = $request->short_name;
                $databaseConnectionName = $shortName;

                if (array_key_exists($databaseConnectionName, config('database.connections'))) {
                    config(['database.default' => $databaseConnectionName]);
                } else {
                    dd('No database configuration for the given short_name');
                }
            }

            $userrole = UserMaster::where('user_id', $credentials['user_id'])
                ->first();
            if ($userrole) {
                $user = UserMaster::where('user_id', $credentials['user_id'])->first();
                $passwordFromDb = $user->password;
                $isHashed = strlen($passwordFromDb) === 60 && preg_match('/^\$2[ayb]\$/', $passwordFromDb);

                if (!$isHashed) {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Password is not hashed.',
                        'success' => false
                    ]);
                }

                if ($remember_me == 'true') {
                    JWTAuth::factory()->setTTL(null);
                } else {
                    JWTAuth::factory()->setTTL(null);
                }
                if (!$user) {
                    Log::warning('Username is not valid:', $credentials);
                    return response()->json(['error' => 'Username is not valid'], 404);
                }

                if (!($user instanceof \Tymon\JWTAuth\Contracts\JWTSubject)) {
                    return response()->json(['error' => 'User model does not implement JWTSubject'], 500);
                }

                if (!$token = JWTAuth::attempt($credentials)) {
                    Log::warning('Invalid password for user:', $credentials);
                    return response()->json(['error' => 'Invalid password'], 401);
                }
                if ($userrole->role_id != 'U' && $userrole->role_id != 'T' && $userrole->role_id != 'P') {
                    $url = config('externalapis.EVOLVU_URL') . '/validate_staff_user';

                    $response = Http::asMultipart()->post($url, [
                        [
                            'name' => 'user_id',
                            'contents' => $credentials['user_id'],
                        ],
                    ]);
                    $responseData = $response->json();
                    $shortName = $responseData[0]['short_name'];
                    $schoolName = $responseData[0]['name'];
                    $databaseConnectionName = $shortName;
                    if (array_key_exists($databaseConnectionName, config('database.connections'))) {
                        config(['database.default' => $databaseConnectionName]);
                    } else {
                        dd('No database configuration for the given short_name');
                    }
                    $academic_yr = Setting::where('active', 'Y')->first()->academic_yr;
                    $settings = Setting::where('active', 'Y')->first();
                    $customClaims = [
                        'role_id' => $user->role_id,
                        'reg_id' => $user->reg_id,
                        'academic_year' => $academic_yr,
                        'short_name' => $shortName,
                        'school_name' => $schoolName,
                        'settings' => $settings
                    ];
                    $token = JWTAuth::claims($customClaims)->fromUser($user);

                    Log::info('Token created successfully:', ['token' => $token]);

                    return response()->json([
                        'token' => $token,
                        'user' => $user,
                        'userdetails' => $customClaims
                    ]);
                } else {
                    $academic_yr = Setting::where('active', 'Y')->first()->academic_yr;
                    $schoolName = Setting::where('active', 'Y')->first()->institute_name;
                    $settings = DB::table('school_settings')->where('is_active', 'Y')->first();

                    $settings_new = Setting::where('active', 'Y')->first();
                    $customClaims = [
                        'role_id' => $user->role_id,
                        'reg_id' => $user->reg_id,
                        'academic_year' => $academic_yr,
                        'school_name' => $schoolName,
                        'settings' => $settings,
                        'short_name' => $settings->short_name,
                        'settings_new' => $settings_new,
                    ];
                    $token = JWTAuth::claims($customClaims)->fromUser($user);

                    Log::info('Token created successfully:', ['token' => $token]);

                    return response()->json([
                        'token' => $token,
                        'user' => $user,
                        'userdetails' => $customClaims
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Invalid user name',
                    'success' => false
                ]);
            }
        } catch (JWTException $e) {
            Log::error('JWTException occurred:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    public function getUserDetails(Request $request)
    {
        $user = $this->authenticateUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized User'], 401);
        }

        $customClaims = JWTAuth::getPayload();

        return response()->json([
            'user' => $user,
            'custom_claims' => $customClaims,
        ]);
    }

    public function updateAcademicYear(Request $request)
    {
        $user = $this->authenticateUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized User'], 401);
        }

        $newAcademicYear = $request->input('academic_year');
        $settings = Setting::where('academic_yr', $newAcademicYear)->first();

        $customClaims = [
            'user_id' => $user->user_id,
            'role_id' => $user->role_id,
            'academic_year' => $newAcademicYear,
            'settings' => $settings,
        ];

        $token = JWTAuth::claims($customClaims)->fromUser($user);

        return response()->json([
            'token' => $token,
            'message' => 'Academic year updated successfully',
        ]);
    }

    public function listSections(Request $request)
    {
        // Extract the JWT token from the Authorization header
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            // Get the payload from the token
            $payload = JWTAuth::setToken($token)->getPayload();
            // Extract the academic year from the custom claims
            $academicYr = $payload->get('academic_year');

            // Fetch the sections for the academic year
            $sections = Section::where('academic_yr', $academicYr)->get();
            return response()->json($sections);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token error'], 401);
        }
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }

        return response()->json(['message' => 'Successfully logged out']);
    }

    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    // Edited By Manish Kumar Sharma 25-04-2025
    public function editUser(Request $request)
    {
        $academicYear = JWTAuth::getPayload()->get('academic_year');
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        $user = auth()->user();
        $teacher = $user->getTeacher;
        if ($teacher) {
            $teacher->teacher_image_name = $teacher->teacher_image_name
                ? $codeigniter_app_url . 'uploads/teacher_image/' . $teacher->teacher_image_name
                : null;
        }
        $specialRoles = DB::table('department_special_role')
            ->where('teacher_id', JWTAuth::getPayload()->get('reg_id'))
            ->where('academic_yr', $academicYear)
            ->pluck('role');

        if ($teacher) {
            return response()->json([
                'user' => $user,
                'special_roles' => $specialRoles,
            ]);
        } else {
            return response()->json([
                'message' => 'Teacher information not found.',
            ], 404);
        }
    }

    public function updateUser(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'employee_id' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'father_spouse_name' => 'nullable|string|max:255',
                'birthday' => 'required|date',
                'date_of_joining' => 'required|date',
                'sex' => 'required|string|max:10',
                'religion' => 'nullable|string|max:255',
                'blood_group' => 'nullable|string|max:10',
                'address' => 'required|string|max:255',
                'phone' => 'required|string|max:15',
                'email' => 'required|string|email|max:255',
                'designation' => 'nullable|string|max:255',
                'academic_qual' => 'nullable|array',
                'academic_qual.*' => 'nullable|string|max:255',
                'professional_qual' => 'nullable|string|max:255',
                'special_sub' => 'nullable|string|max:255',
                'trained' => 'nullable|string|max:255',
                'experience' => 'nullable|string|max:255',
                'aadhar_card_no' => 'nullable|string|max:20',
                'class_id' => 'nullable|integer',
                'section_id' => 'nullable|integer',
                'isDelete' => 'nullable|string|in:Y,N',
                'emergency_phone' => 'nullable|string|max:10',
                'permanent_address' => 'nullable|string|max:255',
            ]);

            if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
                $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
            }

            $user = $this->authenticateUser();
            $teacher = $user->getTeacher;
            if (!isset($validatedData['teacher_image_name']) || $validatedData['teacher_image_name'] === null) {
                unset($validatedData['teacher_image_name']);
            }

            if ($teacher) {
                $teacher->fill($validatedData);
                $teacher->save();

                $user->update($request->only('name'));
                $staff = DB::table('teacher')->where('teacher_id', auth()->user()->reg_id)->first();
                $existingImageUrl = $staff->teacher_image_name;

                // Handle base64 image
                if ($request->has('teacher_image_name')) {
                    $newImageData = $request->input('teacher_image_name');

                    // Check if the new image data is null
                    if ($newImageData === null || $newImageData === 'null') {
                        // If the new image data is null, keep the existing filename
                        $validatedData['teacher_image_name'] = $staff->teacher_image_name;
                    } elseif (!empty($newImageData)) {
                        // Check if the new image data matches the existing image URL
                        if ($existingImageUrl !== $newImageData) {
                            if (preg_match('/^data:image\/(\w+);base64,/', $newImageData, $type)) {
                                $newImageData = substr($newImageData, strpos($newImageData, ',') + 1);
                                $type = strtolower($type[1]);  // jpg, png, gif

                                if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                                    throw new \Exception('Invalid image type');
                                }

                                $newImageData = base64_decode($newImageData);
                                if ($newImageData === false) {
                                    throw new \Exception('Base64 decode failed');
                                }

                                $filename = auth()->user()->reg_id . '.' . $type;
                                $filePath = storage_path('app/public/teacher_images/' . $filename);
                                $directory = dirname($filePath);
                                if (!is_dir($directory)) {
                                    mkdir($directory, 0755, true);
                                }
                                $doc_type_folder = 'teacher_image';
                                // Save the new image to file
                                if (file_put_contents($filePath, $newImageData) === false) {
                                    throw new \Exception('Failed to save image file');
                                }
                                $fileContent = file_get_contents($filePath);
                                $base64File = base64_encode($fileContent);
                                upload_teacher_profile_image_into_folder(auth()->user()->reg_id, $filename, $doc_type_folder, $base64File);

                                // Update the validated data with the new filename
                                $validatedData['teacher_image_name'] = $filename;
                            } else {
                                throw new \Exception('Invalid image data');
                            }
                        } else {
                            // If the image is the same, keep the existing filename
                            $validatedData['teacher_image_name'] = $staff->teacher_image_name;
                        }
                    }
                }

                return response()->json([
                    'message' => 'Profile updated successfully!',
                    'user' => $user,
                    'teacher' => $teacher,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Teacher information not found.',
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Error occurred while updating profile: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => $e
            ]);

            return response()->json([
                'message' => 'An error occurred while updating the profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getParentProfile(Request $request)
    {
        try {

            $parent_id = auth()->user()->reg_id;

            $parentProfile = DB::table('parent as p')
                ->leftJoin('contact_details as cd', 'cd.id', '=', 'p.parent_id')
                ->select(
                    'p.*',
                    'cd.phone_no',
                    'cd.email_id',
                    'cd.m_emailid'
                )
                ->where('p.parent_id', $parent_id)
                ->first();

            if (!$parentProfile) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parent profile not found.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $parentProfile
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function updateParentProfile(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'father_name'        => 'required|string|max:255',
                'foccupation'        => 'nullable|string|max:255',
                'f_office_add'       => 'nullable|string|max:255',
                'f_office_tel'       => 'nullable|string|max:20',
                'f_mobile'           => 'nullable|string|max:15',
                'f_email'            => 'nullable|email|max:255',
                'adharcard_no'       => 'nullable|string|max:20',

                'mother_name'        => 'required|string|max:255',
                'mother_occupation'  => 'nullable|string|max:255',
                'm_office_add'       => 'nullable|string|max:255',
                'm_office_tel'       => 'nullable|string|max:20',
                'm_mobile'           => 'nullable|string|max:15',
                'm_emailid'          => 'nullable|email|max:255',
                'm_adharcard_no'     => 'nullable|string|max:20',

                'f_dob'              => 'nullable|date',
                'm_dob'              => 'nullable|date',

                'f_blood_group'      => 'nullable|string|max:10',
                'm_blood_group'      => 'nullable|string|max:10',

                'parent_mobile'      => 'nullable|in:f_mobile,m_mobile',
            ]);

            $parent_id = auth()->user()->reg_id;

            $parent = DB::table('parent')
                ->where('parent_id', $parent_id)
                ->first();

            if (!$parent) {
                return response()->json([
                    'message' => 'Parent profile not found.'
                ], 404);
            }

            $parentData = [
                'father_name'        => strtoupper($validatedData['father_name']),
                'father_occupation'  => strtoupper($validatedData['foccupation'] ?? ''),
                'f_office_add'       => strtoupper($validatedData['f_office_add'] ?? ''),
                'f_office_tel'       => $validatedData['f_office_tel'] ?? null,
                'f_mobile'           => $validatedData['f_mobile'] ?? null,
                'f_email'            => $validatedData['f_email'] ?? null,
                'parent_adhar_no'    => $validatedData['adharcard_no'] ?? null,

                'mother_name'        => strtoupper($validatedData['mother_name']),
                'mother_occupation'  => strtoupper($validatedData['mother_occupation'] ?? ''),
                'm_office_add'       => strtoupper($validatedData['m_office_add'] ?? ''),
                'm_office_tel'       => $validatedData['m_office_tel'] ?? null,
                'm_mobile'           => $validatedData['m_mobile'] ?? null,
                'm_emailid'          => $validatedData['m_emailid'] ?? null,
                'm_adhar_no'         => $validatedData['m_adharcard_no'] ?? null,

                'f_dob'              => $validatedData['f_dob'] ?? null,
                'm_dob'              => $validatedData['m_dob'] ?? null,

                'f_blood_group'      => $validatedData['f_blood_group'] ?? null,
                'm_blood_group'      => $validatedData['m_blood_group'] ?? null,

                'IsDelete'           => 'N',
            ];

            DB::table('parent')
                ->where('parent_id', $parent_id)
                ->update($parentData);

            // Communication Mobile
            $phone_no = '';

            if (($validatedData['parent_mobile'] ?? '') === 'm_mobile') {
                $phone_no = $validatedData['m_mobile'] ?? '';
            } elseif (($validatedData['parent_mobile'] ?? '') === 'f_mobile') {
                $phone_no = $validatedData['f_mobile'] ?? '';
            }

            $contactData = [
                'phone_no'  => $phone_no,
                'email_id'  => $validatedData['f_email'] ?? null,
                'm_emailid' => $validatedData['m_emailid'] ?? null,
            ];

            $contactExists = DB::table('contact_details')
                ->where('id', $parent_id)
                ->exists();

            if ($contactExists) {

                DB::table('contact_details')
                    ->where('id', $parent_id)
                    ->update($contactData);
            } else {

                $contactData['id'] = $parent_id;

                DB::table('contact_details')
                    ->insert($contactData);
            }

            return response()->json([
                'message' => 'Parent profile updated successfully.',
                'parent_id' => $parent_id
            ], 200);
        } catch (\Exception $e) {

            Log::error('Error updating parent profile', [
                'parent_id' => auth()->user()->reg_id ?? null,
                'request_data' => $request->all(),
                'exception' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'An error occurred while updating parent profile.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getParentDetailsForIdCard()
    {
        $payload = JWTAuth::getPayload();
        $academicYear = $payload->get('academic_year');
        $parent_id = auth()->user()->reg_id;

        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

        // Parent details
        $parent = DB::table('parent')
            ->where('parent_id', $parent_id)
            ->first();

        if (!$parent) {
            return response()->json([
                'status' => false,
                'message' => 'Parent not found',
                'data' => (object)[]
            ]);
        }

        // Parent image URLs
        $parent->father_image_url = !empty($parent->father_image_name)
            ? $codeigniter_app_url . 'uploads/parent_image/' . $parent->father_image_name
            : '';

        $parent->mother_image_url = !empty($parent->mother_image_name)
            ? $codeigniter_app_url . 'uploads/parent_image/' . $parent->mother_image_name
            : '';

        // Confirmation status
        $confirmation = DB::table('confirmation_idcard')
            ->where('parent_id', $parent_id)
            ->where('academic_yr', $academicYear)
            ->first();

        $parent->confirm = $confirmation ? $confirmation->confirm : 'N';

        // Students
        $students = DB::table('student')
            ->where([
                'parent_id'   => $parent_id,
                'IsDelete'    => 'N',
                'academic_yr' => $academicYear
            ])
            ->get();

        $firstStudent = $students->first();

        $guardianFields = [];

        if ($firstStudent) {

            $guardianFields = [
                'guardian_name'   => $firstStudent->guardian_name,
                'guardian_mobile' => $firstStudent->guardian_mobile,
                'guardian_add'    => $firstStudent->guardian_add,
                'relation'        => $firstStudent->relation,
            ];

            // Guardian image URL
            $parent->guardian_image_url = !empty($firstStudent->guardian_image_name)
                ? $codeigniter_app_url . 'uploads/parent_image/' . $firstStudent->guardian_image_name
                : '';
        } else {
            $parent->guardian_image_url = '';
        }

        $students = $students->map(function ($student) use ($guardianFields, $codeigniter_app_url) {

            $student->class_name = DB::table('class')
                ->where('class_id', $student->class_id)
                ->value('name');

            $student->section_name = DB::table('section')
                ->where('section_id', $student->section_id)
                ->value('name');

            // Student image URL
            $student->image_url = !empty($student->image_name)
                ? $codeigniter_app_url . 'uploads/student_image/' . $student->image_name
                : '';

            foreach ($guardianFields as $key => $value) {
                $student->$key = $value;
            }

            return $student;
        });

        // Add guardian fields to parent object
        foreach ($guardianFields as $key => $value) {
            $parent->$key = $value;
        }

        return response()->json([
            'status' => true,
            'message' => 'Data fetched successfully',
            'data' => [
                'parent_info' => $parent,
                'students' => $students
            ]
        ]);
    }


    public function saveParentStudentIdCardDetails(Request $request)
    {
        $parent_id = $request->parent_id;
        $student_count = $request->students;

        // 1. STUDENT LOOP UPDATE
        for ($j = 1; $j <= $student_count; $j++) {

            $student_id = $request->input("student_id$j");

            $data = [];

            $data['blood_group']  = $request->input("blood_group$j");
            $data['house']        = $request->input("house$j");
            $data['permant_add']  = $request->input("permant_add$j");

            //  Student Image Upload (CI)
            $s_image = $request->input("s_cropped_image$j");

            if (!empty($s_image)) {

                $ext = 'png';

                // $decoded = base64_decode(str_replace('[removed]', '', $s_image));
                $decoded = preg_replace('/^data:image\/\w+;base64,/', '', $s_image);

                $fileName = $student_id . '.' . $ext;

                $uploadResponse = upload_student_profile_image_into_folder(
                    $student_id,
                    $fileName,
                    'student_image',
                    $decoded
                );

                if (isset($uploadResponse['error'])) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Student image upload failed',
                        'error' => $uploadResponse
                    ]);
                }

                $data['image_name'] = $fileName;
            }

            // Guardian Info
            $data['guardian_name']   = $request->guardian_name;
            $data['guardian_mobile'] = $request->guardian_mobile;
            $data['relation']        = $request->relation;

            DB::table('student')
                ->where('student_id', $student_id)
                ->update($data);
        }

        // 2. PARENT UPDATE
        $parentData = [];

        $parentData['f_mobile'] = $request->f_mobile;
        $parentData['m_mobile'] = $request->m_mobile;

        $doc_type_folder = 'parent_image';

        // Father Image
        if ($request->f_cropped_image) {

            // $decoded = base64_decode(str_replace('[removed]', '', $request->f_cropped_image));
            $decoded = preg_replace('/^data:image\/\w+;base64,/', '',  $request->f_cropped_image);

            $fileName = "f_" . $parent_id . ".png";
            $doc_type_folder = 'parent_image';

            $uploadResponse = upload_father_profile_image_into_folder(
                $parent_id,
                $fileName,
                $doc_type_folder,
                $decoded
            );

            if (isset($uploadResponse['error'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Father image upload failed',
                    'error' => $uploadResponse
                ]);
            }

            $parentData['father_image_name'] = $fileName;
        }

        //  Mother Image
        if ($request->m_cropped_image) {

            // $decoded = base64_decode(str_replace('[removed]', '', $request->m_cropped_image));
            $decoded = preg_replace('/^data:image\/\w+;base64,/', '',  $request->m_cropped_image);

            $fileName = "m_" . $parent_id . ".png";

            $doc_type_folder = 'parent_image';
            $uploadResponse = upload_mother_profile_image_into_folder(
                $parent_id,
                $fileName,
                $doc_type_folder,
                $decoded
            );

            if (isset($uploadResponse['error'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mother image upload failed',
                    'error' => $uploadResponse
                ]);
            }

            $parentData['mother_image_name'] = $fileName;
        }

        // Guardian Image
        // if ($request->g_cropped_image) {

        //     $decoded = base64_decode(str_replace('[removed]', '', $request->g_cropped_image));

        //     $fileName = "g_" . $parent_id . ".png";

        //     $uploadResponse = upload_guardian_profile_image_into_folder(
        //         $parent_id,
        //         $fileName,
        //         $doc_type_folder,
        //         $decoded
        //     );

        //     if (isset($uploadResponse['error'])) {
        //         return response()->json([
        //             'status' => false,
        //             'message' => 'Guardian image upload failed',
        //             'error' => $uploadResponse
        //         ]);
        //     }

        //     $parentData['guardian_image_name'] = $fileName;
        // }
        // Guardian Image
        if ($request->g_cropped_image) {

            // $decoded = base64_decode(str_replace('[removed]', '', $request->g_cropped_image));
            $decoded = preg_replace('/^data:image\/\w+;base64,/', '',  $request->g_cropped_image);

            $fileName = "g_" . $parent_id . ".png";

            $doc_type_folder = 'parent_image';
            $uploadResponse = upload_guardian_profile_image_into_folder(
                $parent_id,
                $fileName,
                $doc_type_folder,
                $decoded
            );

            if (isset($uploadResponse['error'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Guardian image upload failed',
                    'error' => $uploadResponse
                ]);
            }

            // Don't update parent table with guardian_image_name
        }

        DB::table('parent')
            ->where('parent_id', $parent_id)
            ->update($parentData);

        //  3. CONFIRMATION TABLE
        $confirmData = [
            'parent_id'   => $parent_id,
            'academic_yr' => $request->academic_yr,
            'confirm'     => $request->has('confirm') ? 'Y' : 'N'
        ];

        $exists = DB::table('confirmation_idcard')
            ->where('parent_id', $parent_id)
            ->first();

        if ($exists) {
            DB::table('confirmation_idcard')
                ->where('parent_id', $parent_id)
                ->update($confirmData);
        } else {
            DB::table('confirmation_idcard')->insert($confirmData);
        }

        //  QR CODE GENERATION
        $fileName = $parent_id . ".svg";


        $folderPath = storage_path('app/public/qrcode');

        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);
        }


        $svgData = \QrCode::format('svg')
            ->size(200)
            ->generate($parent_id);


        $filelocation = $folderPath . '/' . $fileName;
        file_put_contents($filelocation, $svgData);


        $base64File = base64_encode($svgData);


        upload_qrcode_into_folder(
            $fileName,
            'qrcode',
            $base64File
        );

        return response()->json([
            'status' => true,
            'message' => 'ID Card details saved successfully',
            'qr_code' => asset("uploads/qrcode/" . $fileName)
        ]);
    }
}
