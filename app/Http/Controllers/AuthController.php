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

class AuthController extends Controller
{
    //     public function login(Request $request)
    // {
    //     $credentials = $request->only('email', 'password');

    //     if (!$token = JWTAuth::attempt($credentials)) {
    //         return response()->json(['error' => 'Invalid credentials'], 401);
    //     }

    //     $user = auth()->user();
    //     $academic_yr = Setting::where('active', 'Y')->first()->academic_yr;
    //     $customClaims = [
    //         'role_id' => $user->role_id,
    //         'reg_id' =>$user->reg_id,
    //         'academic_year' => $academic_yr,
    //     ];

    //     $token = JWTAuth::claims($customClaims)->fromUser($user);

    //     return response()->json([
    //         'token' => $token,
    //         // 'user' => $user,
    //     ]);
    // }

    // public function login(Request $request)
    // {
    //     $credentials = $request->only('email', 'password');

    //     Log::info('Login attempt with credentials:', $credentials);

    //     try {
    //         if (!$token = JWTAuth::attempt($credentials)) {
    //             Log::warning('Invalid credentials for user:', $credentials);
    //             return response()->json(['error' => 'Invalid credentials'], 401);
    //         }

    //         $user = JWTAuth::setToken($token)->toUser();
    //         $academic_yr = Setting::where('active', 'Y')->first()->academic_yr;

    //         Log::info('Authenticated user:', ['user_id' => $user->id, 'academic_year' => $academic_yr]);

    //         $customClaims = [
    //             'role_id' => $user->role_id,
    //             'reg_id' => $user->reg_id,
    //             'academic_year' => $academic_yr,
    //         ];

    //         $token = JWTAuth::claims($customClaims)->fromUser($user);

    //         Log::info('Token created successfully:', ['token' => $token]);

    //         return response()->json(['token' => $token]);

    //     } catch (JWTException $e) {
    //         Log::error('JWTException occurred:', ['message' => $e->getMessage()]);
    //         return response()->json(['error' => 'Could not create token'], 500);
    //     }
    // }

    public function connectByShortName(Request $request)
    {
        $request->validate([
            'short_name' => 'required|string',
        ]);

        $shortName = $request->short_name;
        Log::info($shortName);

        // Optional: validate short_name from a master DB
        // $exists = DB::table('schools')->where('short_name', $shortName)->exists();
        // if (!$exists) {
        //     return response()->json(['error' => 'Invalid school short_name'], 404);
        // }
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
            // dd($shortName);
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
                ->whereIn('role_id', ['A', 'M', 'U', 'T', 'P', 'L', 'E'])
                ->first();
            if ($userrole) {
                $user = UserMaster::where('user_id', $credentials['user_id'])->first();
                $passwordFromDb = $user->password;
                $isHashed = strlen($passwordFromDb) === 60 && preg_match('/^\$2[ayb]\$/', $passwordFromDb);

                if (!$isHashed) {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Invalid userid',
                        'success' => false
                    ]);
                }

                if ($remember_me == 'true') {
                    // e.g. 1 week in minutes
                    JWTAuth::factory()->setTTL(60 * 24 * 7);
                } else {
                    // Default e.g. 1 hour
                    // dd("Hello from else");
                    JWTAuth::factory()->setTTL(60 * 24);
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

                    return response()->json(['token' => $token,
                        'user' => $user, 'userdetails' => $customClaims]);
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

                    return response()->json(['token' => $token,
                        'user' => $user, 'userdetails' => $customClaims]);
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

        if ($teacher) {
            return response()->json([
                'user' => $user,
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
}
