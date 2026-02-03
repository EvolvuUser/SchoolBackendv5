<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Division;
use App\Models\Parents;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use League\Csv\Writer;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])
            ->where('IsDelete', 'N')
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found', 'field' => 'email', 'success' => false], 404);
        }

        if (!Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid Password', 'field' => 'password', 'success' => false], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $activeSetting = Setting::where('active', 'Y')->first();
        $academic_yr = $activeSetting->academic_yr;
        $reg_id = $user->reg_id;
        $role_id = $user->role_id;
        $institutename = $activeSetting->institute_name;
        $user->academic_yr = $academic_yr;

        $sessionData = [
            'token' => $token,
            'role_id' => $role_id,
            'reg_id' => $reg_id,
            'academic_yr' => $academic_yr,
            'institutename' => $institutename,
        ];

        Session::put('sessionData', $sessionData);
        $cookie = cookie('sessionData', json_encode($sessionData), 120);  // 120 minutes expiration

        return response()->json([
            'message' => 'Login successfully',
            'token' => $token,
            'success' => true,
            'reg_id' => $reg_id,
            'role_id' => $role_id,
            'academic_yr' => $academic_yr,
            'institutename' => $institutename,
        ])->cookie($cookie);
    }

    public function getSessionData(Request $request)
    {
        $sessionData = $request->session()->get('sessionData', []);
        if (empty($sessionData)) {
            return response()->json([
                'message' => 'No session data found',
                'success' => false
            ]);
        }

        return response()->json([
            'data' => $sessionData,
            'success' => true
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Session::forget('sessionData');
        return response()->json(['message' => 'Logout successfully', 'success' => true], 200);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'answer_one' => 'required|string',
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'max:20',
                'regex:/^(?=.*[0-9])(?=.*[!@#\$%\^&\*]).{8,20}$/'
            ],
        ]);

        $user = Auth::user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect', 'field' => 'current_password', 'success' => false], 400);
        }
        $user->answer_one = $data['answer_one'];
        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json(['message' => 'Password updated successfully', 'success' => true], 200);
    }

    public function updateAcademicYear(Request $request)
    {
        $request->validate([
            'academic_yr' => 'required|string',
        ]);

        $academicYr = $request->input('academic_yr');
        $sessionData = Session::get('sessionData');
        if (!$sessionData) {
            return response()->json(['message' => 'Session data not found', 'success' => false], 404);
        }

        $sessionData['academic_yr'] = $academicYr;
        Session::put('sessionData', $sessionData);

        return response()->json(['message' => 'Academic year updated successfully', 'success' => true], 200);
    }

    public function clearData(Request $request)
    {
        Session::forget('sessionData');
        return response()->json(['message' => 'Logout successfully', 'success' => true], 200);
    }

    public function getAcademicyear(Request $request)
    {
        $sessionData = Session::get('sessionData');
        $academicYr = $sessionData['academic_yr'] ?? null;

        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in session data', 'success' => false], 404);
        }

        return response()->json(['academic_yr' => $academicYr, 'success' => true], 200);
    }

    public function getStudentListbysectionforregister(Request $request, $section_id)
    {
        $studentList = Student::with('getClass', 'getDivision')
            ->where('section_id', $section_id)
            ->where('parent_id', '0')
            ->distinct()
            ->get();

        return response()->json($studentList);
    }

    public function getAllStudentListForRegister(Request $request)
    {
        $studentList = Student::with('getClass', 'getDivision')
            ->where('parent_id', '0')
            ->distinct()
            ->get();

        return response()->json($studentList);
    }

    public function downloadCsvTemplateWithData(Request $request, $section_id)
    {
        $user = $this->authenticateUser();
        $academicYear = JWTAuth::getPayload()->get('academic_year');

        $students = Student::select(
            'student_id as student_id',
            'first_name as *First Name',
            'mid_name as Mid name',
            'last_name as last name',
            'gender as *Gender',
            'dob as *DOB(in dd/mm/yyyy format)',
            'stu_aadhaar_no as Student Aadhaar No.',
            'mother_tongue as Mother Tongue',
            'religion as Religion',
            'blood_group as *Blood Group',
            'caste as caste',
            'subcaste as Sub Caste',
            'class.name as Class',
            'section.name as Division',
            'mother_name as *Mother Name',
            'mother_occupation as Mother Occupation',  // Assuming you have this field
            'm_mobile as *Mother Mobile No.(Only Indian Numbers)',  // Assuming you have this field
            'm_emailid as *Mother Email-Id',  // Assuming you have this field
            'father_name as *Father Name',  // Assuming you have this field
            'father_occupation as Father Occupation',  // Assuming you have this field
            'f_mobile as *Father Mobile No.(Only Indian Numbers)',  // Assuming you have this field
            'f_email as *Father Email-Id',  // Assuming you have this field
            'm_adhar_no as Mother Aadhaar No.',  // Assuming you have this field
            'parent_adhar_no as Father Aadhaar No.',  // Assuming you have this field
            'permant_add as *Address',
            'city as *City',
            'state as *State',
            'admission_date as *DOA(in dd/mm/yyyy format)',
            'reg_no as *GRN No'
        )
            ->distinct()
            ->leftJoin('parent', 'student.parent_id', '=', 'parent.parent_id')
            ->leftJoin('section', 'student.section_id', '=', 'section.section_id')  // Use correct table name 'sections'
            ->leftJoin('class', 'student.class_id', '=', 'class.class_id')  // Use correct table name 'sections'
            ->where('student.parent_id', '=', '0')
            ->where('student.academic_yr', $academicYear)  // Specify the table name here
            ->where('student.section_id', $section_id)  // Specify the table name here
            ->get()
            ->toArray();

        \Log::info('Students Data: ', $students);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_template.csv"',
        ];

        $columns = [
            'student_id',
            '*First Name',
            'Mid name',
            'last name',
            '*Gender',
            '*DOB(in dd/mm/yyyy format)',
            'Student Aadhaar No.',
            'Mother Tongue',
            'Religion',
            '*Blood Group',
            'caste',
            'Sub Caste',
            'Class',
            'Division',
            '*Mother Name',
            'Mother Occupation',
            '*Mother Mobile No.(Only Indian Numbers)',
            '*Mother Email-Id',
            '*Father Name',
            'Father Occupation',
            '*Father Mobile No.(Only Indian Numbers)',
            '*Father Email-Id',
            'Mother Aadhaar No.',
            'Father Aadhaar No.',
            '*Address',
            '*City',
            '*State',
            '*DOA(in dd/mm/yyyy format)',
            '*GRN No',
        ];

        $callback = function () use ($columns, $students) {
            $file = fopen('php://output', 'w');

            fputcsv($file, $columns);
            foreach ($students as $student) {
                fputcsv($file, $student);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function updateCsvData(Request $request, $section_id)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);
        $academicYear = JWTAuth::getPayload()->get('academic_year');
        $settingsData = getSchoolSettingsData();
        $defaultPassword = $settingsData->default_pwd;

        $file = $request->file('file');
        if (!$file->isValid()) {
            return response()->json(['message' => 'Invalid file upload'], 400);
        }

        $csvData = file_get_contents($file->getRealPath());
        $rows = array_map('str_getcsv', explode("\n", $csvData));
        $header = array_shift($rows);
        $columnMap = [
            'student_id' => 'student_id',
            '*First Name' => 'first_name',
            'Mid name' => 'mid_name',
            'last name' => 'last_name',
            '*Gender' => 'gender',
            '*DOB(in dd/mm/yyyy format)' => 'dob',
            '*Student Aadhaar No.' => 'stu_aadhaar_no',
            'Udise Pen No.' => 'udise_pen_no',
            'Apaar ID No.' => 'apaar_id',
            'Mother Tongue' => 'mother_tongue',
            'Religion' => 'religion',
            '*Blood Group' => 'blood_group',
            'caste' => 'caste',
            'Sub Caste' => 'subcaste',
            '*Mother Name' => 'mother_name',
            'Mother Occupation' => 'mother_occupation',
            '*Mother Mobile No.(Only Indian Numbers)' => 'mother_mobile',
            '*Mother Email-Id' => 'mother_email',
            '*Father Name' => 'father_name',
            'Father Occupation' => 'father_occupation',
            '*Father Mobile No.(Only Indian Numbers)' => 'father_mobile',
            '*Father Email-Id' => 'father_email',
            '*Mother Aadhaar No.' => 'mother_aadhaar_no',
            '*Father Aadhaar No.' => 'father_aadhaar_no',
            '*Address' => 'permant_add',
            '*City' => 'city',
            '*State' => 'state',
            '*DOA(in dd/mm/yyyy format)' => 'admission_date',
            '*GRN No' => 'reg_no',
        ];

        $invalidRows = [];
        $successfulInserts = 0;

        $division = Division::find($section_id);
        if (!$division) {
            return response()->json(['message' => 'Invalid section ID'], 400);
        }
        $class_id = $division->class_id;

        foreach ($rows as $rowIndex => $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $studentData = [];
            foreach ($header as $index => $columnName) {
                if (isset($columnMap[$columnName])) {
                    $dbField = $columnMap[$columnName];
                    $studentData[$dbField] = $row[$index] ?? null;
                }
            }
            $studentData['father_aadhaar_no'] = preg_replace('/\D/', '', $studentData['father_aadhaar_no']);
            $studentData['mother_aadhaar_no'] = preg_replace('/\D/', '', $studentData['mother_aadhaar_no']);
            $studentData['stu_aadhaar_no'] = preg_replace('/\D/', '', $studentData['stu_aadhaar_no']);
            $studentData['mother_mobile'] = preg_replace('/\D/', '', $studentData['mother_mobile']);
            $studentData['father_mobile'] = preg_replace('/\D/', '', $studentData['father_mobile']);
            $studentData['dob'] = preg_replace('/[^0-9\/]/', '', $studentData['dob']);
            $studentData['admission_date'] = preg_replace('/[^0-9\/]/', '', $studentData['admission_date']);

            DB::beginTransaction();
            $errors = [];
            if (empty($studentData['student_id'])) {
                $errors[] = 'Missing student ID';
            }

            if (empty($studentData['first_name'])) {
                $errors[] = 'Please do not delete the first name.';
            }

            if (empty($studentData['gender'])) {
                $errors[] = 'Gender is required.';
            } elseif (!in_array($studentData['gender'], ['M', 'F', 'O'])) {
                $errors[] = 'Invalid gender value. Expected M, F, or O.';
            }

            if (empty($studentData['blood_group'])) {
                $errors[] = 'Blood group is required.';
            } elseif (!in_array($studentData['blood_group'], ['A+', 'B+', 'AB+', 'O+', 'A-', 'B-', 'AB-', 'O-'])) {
                $errors[] = 'Invalid Blood group value. Expected A+,B+,AB+,O+,A-,B-,AB-,O- . ';
            }

            if (!empty($studentData['religion']) && !in_array($studentData['religion'], ['Christian', 'Hindu', 'Jain', 'Muslim', 'Buddhist', 'Sikh'])) {
                $errors[] = 'Invalid religion value. Expected Christian,Hindu,Jain,Muslim,Buddhist,Sikh. ';
            }

            if (empty($studentData['mother_name'])) {
                $errors[] = 'Mother name is required.';
            }

            if (empty($studentData['mother_mobile'])) {
                $errors[] = 'Mother mobile is required.';
            } elseif (!is_numeric($studentData['mother_mobile']) || strlen($studentData['mother_mobile']) != 10) {
                $errors[] = 'Mother mobile must be a 10-digit numeric value.';
            }

            if (empty($studentData['mother_email'])) {
                $errors[] = 'Mother Email is required.';
            }

            if (empty($studentData['father_name'])) {
                $errors[] = 'Father Name is required.';
            }

            if (empty($studentData['father_mobile'])) {
                $errors[] = 'Father Mobile is required.';
            } elseif (!is_numeric($studentData['father_mobile']) || strlen($studentData['father_mobile']) != 10) {
                $errors[] = 'Father mobile must be a 10-digit numeric value.';
            }

            if (empty($studentData['father_email'])) {
                $errors[] = 'Father Email is required.';
            }

            if (empty($studentData['permant_add'])) {
                $errors[] = 'Address is required.';
            }

            if (empty($studentData['city'])) {
                $errors[] = 'City is required.';
            }
            if (empty($studentData['state'])) {
                $errors[] = 'State is required.';
            }

            if (empty($studentData['reg_no'])) {
                $errors[] = 'GRN No. is required.';
            }

            if (empty($studentData['father_aadhaar_no'])) {
                $errors[] = 'Father Aadhar is required.';
            } elseif (!is_numeric($studentData['father_aadhaar_no']) || strlen($studentData['father_aadhaar_no']) != 12) {
                $errors[] = 'Father Aadhar must be a 12-digit numeric value.';
            } else {
                // Ensure it's stored as an integer
                $studentData['father_aadhaar_no'] = intval($studentData['father_aadhaar_no']);
            }

            if (empty($studentData['mother_aadhaar_no'])) {
                $errors[] = 'Mother Aadhar is required.';
            } elseif (!is_numeric((string) $studentData['mother_aadhaar_no']) || strlen($studentData['mother_aadhaar_no']) != 12) {
                $errors[] = 'Mother Aadhar must be a 12-digit numeric value.';
            } else {
                // Ensure it's stored as an integer
                $studentData['mother_aadhaar_no'] = intval($studentData['mother_aadhaar_no']);
            }

            if (empty($studentData['stu_aadhaar_no'])) {
                $errors[] = 'Student Aadhar is required.';
            } elseif (!is_numeric((string) $studentData['stu_aadhaar_no']) || strlen($studentData['stu_aadhaar_no']) != 12) {
                $errors[] = 'Student Aadhar must be a 12-digit numeric value.';
            } else {
                // Ensure it's stored as an integer
                $studentData['stu_aadhaar_no'] = intval($studentData['stu_aadhaar_no']);
            }

            // Validate and handle DOB format (dd/mm/yyyy)
            if (empty($studentData['dob'])) {
                $errors[] = 'DOB is required.';
            } elseif (!$this->validateDate($studentData['dob'], 'd/m/Y')) {
                $errors[] = 'Invalid DOB format. Expected dd/mm/yyyy.';
            } else {
                try {
                    // Convert DOB to the required format (yyyy-mm-dd)
                    $studentData['dob'] = \Carbon\Carbon::createFromFormat('d/m/Y', $studentData['dob'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $errors[] = 'Invalid DOB format. Expected dd/mm/yyyy.';
                }
            }

            // Validate and handle admission_date format (dd/mm/yyyy)
            if (empty($studentData['admission_date'])) {
                $errors[] = 'Admission date is required.';
            } elseif (!$this->validateDate($studentData['admission_date'], 'd/m/Y')) {
                $errors[] = 'Invalid admission date format. Expected dd/mm/yyyy.';
            } else {
                try {
                    // Convert admission_date to the required format (yyyy-mm-dd)
                    $studentData['admission_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $studentData['admission_date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $errors[] = 'Invalid admission date format. Expected dd/mm/yyyy.';
                }
            }

            // Now, check if the student exists
            $student = Student::where('student_id', $studentData['student_id'])->first();
            if (!$student) {
                $errors[] = 'Student not found';
            }

            // If there are any errors, add them to the invalidRows array and skip this row
            if (!empty($errors)) {
                // Combine the row with the errors and store in invalidRows
                $invalidRows[] = array_merge($row, ['error' => implode(' | ', $errors)]);
                // Rollback or continue to the next iteration to prevent processing invalid data
                DB::rollBack();
                continue;  // Skip this row, moving to the next iteration
            }

            try {
                $parentData = [
                    'father_name' => $studentData['father_name'] ?? null,
                    'father_occupation' => $studentData['father_occupation'] ?? null,
                    'f_mobile' => $studentData['father_mobile'] ?? null,
                    'f_email' => $studentData['father_email'] ?? null,
                    'mother_name' => $studentData['mother_name'] ?? null,
                    'mother_occupation' => $studentData['mother_occupation'] ?? null,
                    'm_mobile' => $studentData['mother_mobile'] ?? null,
                    'm_emailid' => $studentData['mother_email'] ?? null,
                    'parent_adhar_no' => $studentData['father_aadhaar_no'] ?? null,
                    'm_adhar_no' => $studentData['mother_aadhaar_no'] ?? null,
                ];

                // Check if parent exists, if not, create one
                $parent = Parents::where('f_mobile', $parentData['f_mobile'])->first();
                if (!$parent) {
                    $parent = Parents::create($parentData);
                    DB::insert('INSERT INTO contact_details (id, phone_no, email_id, m_emailid) VALUES (?, ?, ?, ?)', [
                        $student->parent_id,
                        $studentData['father_mobile'],
                        $studentData['father_email'],
                        $studentData['mother_email']  // sms_consent
                    ]);

                    // Insert data into user_master table (skip if already exists)
                    DB::table('user_master')->updateOrInsert(
                        ['user_id' => $studentData['father_email']],
                        [
                            'name' => $studentData['father_name'],
                            'password' => bcrypt($defaultPassword),
                            'reg_id' => $parent->parent_id,
                            'role_id' => 'P',
                            'IsDelete' => 'N',
                        ]
                    );
                }

                $user = $this->authenticateUser();
                $academicYear = JWTAuth::getPayload()->get('academic_year');

                // Update the student's parent_id and class_id
                $student->parent_id = $parent->parent_id;
                $student->class_id = $class_id;
                $student->gender = $studentData['gender'];
                $student->first_name = $studentData['first_name'];
                $student->mid_name = $studentData['mid_name'];
                $student->last_name = $studentData['last_name'];
                $student->dob = $studentData['dob'];
                $student->blood_group = $studentData['blood_group'];
                $student->admission_date = $studentData['admission_date'];
                $student->stu_aadhaar_no = $studentData['stu_aadhaar_no'];
                $student->udise_pen_no = $studentData['udise_pen_no'];
                $student->apaar_id = $studentData['apaar_id'];
                $student->mother_tongue = $studentData['mother_tongue'];
                $student->religion = $studentData['religion'];
                $student->caste = $studentData['caste'];
                $student->subcaste = $studentData['subcaste'];
                $student->reg_no = $studentData['reg_no'];
                $student->permant_add = $studentData['permant_add'];
                $student->city = $studentData['city'];
                $student->state = $studentData['state'];
                $student->IsDelete = 'N';
                $student->created_by = $user->reg_id;
                $student->save();

                // Commit the transaction
                DB::commit();
                $successfulInserts++;
                $user_id_user_master = DB::table('user_master')->where('role_id', 'P')->where('reg_id', $parent->parent_id)->first();
                createUserInEvolvu($user_id_user_master->user_id);
            } catch (\Exception $e) {
                // Rollback the transaction in case of an error
                DB::rollBack();
                $invalidRows[] = array_merge($row, ['error' => 'Error updating student: ' . $e->getMessage()]);
                continue;
            }
        }

        // If there are invalid rows, generate a CSV for rejected rows
        if (!empty($invalidRows)) {
            $csv = Writer::createFromString('');
            $csv->insertOne(['student_id',
                '*First Name',
                'Mid name',
                'last name',
                '*Gender',
                '*DOB(in dd/mm/yyyy format)',
                '*Student Aadhaar No.',
                'Udise Pen No.',
                'Apaar ID No.',
                'Mother Tongue',
                'Religion',
                '*Blood Group',
                'caste',
                'Sub Caste',
                'Class',
                'Division',
                '*Mother Name',
                'Mother Occupation',
                '*Mother Mobile No.(Only Indian Numbers)',
                '*Mother Email-Id',
                '*Father Name',
                'Father Occupation',
                '*Father Mobile No.(Only Indian Numbers)',
                '*Father Email-Id',
                '*Mother Aadhaar No.',
                '*Father Aadhaar No.',
                '*Address',
                '*City',
                '*State',
                '*DOA(in dd/mm/yyyy format)',
                '*GRN No', 'error']);
            foreach ($invalidRows as $invalidRow) {
                $csv->insertOne($invalidRow);
            }
            $filePath = 'public/csv_rejected/rejected_rows_' . now()->format('Y_m_d_H_i_s') . '.csv';
            Storage::put($filePath, $csv->toString());
            $relativePath = str_replace('public/csv_rejected/', '', $filePath);

            return response()->json([
                'message' => 'Some rows contained errors.',
                'invalid_rows' => $relativePath,
            ], 422);
        }

        if ($successfulInserts === 0) {
            return response()->json([
                'message' => 'Students details rows are empty. Please check your CSV.',
                'success' => false
            ], 422);
        }

        // Return a success response
        return response()->json(['message' => 'CSV data updated successfully.']);
    }

    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    // Helper method to validate date format
    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
