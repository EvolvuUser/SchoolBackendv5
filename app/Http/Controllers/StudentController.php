<?php


namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use League\Csv\Writer;
use App\Models\Parents;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Division;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Jobs\SendMessageStudentAttendanceShortage;
use App\Jobs\SendMessageStudentDailyAttendanceShortage;

class StudentController extends Controller
{
    public function getTodayAbsentStudentsForTeacher(Request $request) {
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $teacher_id = $user->reg_id;
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $only_date = Carbon::today()->toDateString();
        // $only_date = '2025-12-04';

        $classesTaught = DB::table('subject')
                ->where('teacher_id', $teacher_id)
                ->where('academic_yr', $customClaims)
                ->distinct()
                ->pluck('class_id')
                ->toArray();

        $sectionsTaught = DB::table('subject')
                ->where('teacher_id', $teacher_id)
                ->where('academic_yr', $customClaims)
                ->distinct()
                ->pluck('section_id')
                ->toArray();

        $absentstudents = DB::table('attendance')
            ->join('student', 'student.student_id', '=', 'attendance.student_id')
            ->join('class', 'class.class_id', '=', 'attendance.class_id')
            ->join('section', 'section.section_id', '=', 'attendance.section_id')
            ->leftJoin('redington_webhook_details', function ($join) use ($only_date) {
                $join->on('redington_webhook_details.stu_teacher_id', '=', 'student.student_id')
                    ->where('redington_webhook_details.message_type', '=', 'student_daily_attendance_shortage')
                    ->whereDate('redington_webhook_details.created_at', '=', $only_date);
            })
            ->where('attendance.attendance_status', '1') // absent
            ->where('attendance.only_date', $only_date)
            ->where('student.isDelete', 'N')
            ->where('attendance.teacher_id', $teacher_id)

            // ✅ Class filter
            ->when($class_id, function ($query) use ($class_id) {
                $query->where('attendance.class_id', $class_id);
            }, function ($query) use ($classesTaught) {
                $query->whereIn('attendance.class_id', $classesTaught);
            })

            // ✅ Section filter
            ->when($section_id, function ($query) use ($section_id) {
                $query->where('attendance.section_id', $section_id);
            }, function ($query) use ($sectionsTaught) {
                $query->whereIn('attendance.section_id', $sectionsTaught);
            })

            ->select(
                'attendance.teacher_id',
                'student.first_name',
                'student.mid_name',
                'student.last_name',
                'class.name as classname',
                'section.name as sectionname',
                'class.class_id',
                'section.section_id',
                'student.student_id',
                DB::raw('COUNT(redington_webhook_details.webhook_id) as messages_sent_count'),
                DB::raw('MAX(redington_webhook_details.created_at) as last_message_sent_at'),
                DB::raw('MAX(redington_webhook_details.webhook_id) as webhook_id'),
                DB::raw("
                    CASE 
                        WHEN COUNT(redington_webhook_details.webhook_id) = 0 THEN 'not_try'
                        WHEN SUM(CASE WHEN redington_webhook_details.sms_sent = 'Y' THEN 1 ELSE 0 END) > 0 THEN 'Y'
                        ELSE 'N'
                    END as sms_sent_status
                ")
            )
            ->groupBy(
                'student.student_id',
                'student.first_name',
                'student.mid_name',
                'student.last_name',
                'class.name',
                'section.name',
                'class.class_id',
                'section.section_id'
            )
            ->orderBy('section.section_id')
            ->get();

        $countstudents = count($absentstudents);
        $absentstudentdata = [
            'absent_student' => $absentstudents,
            'count_absent_student' => $countstudents
        ];

        return response()->json([
            'status' => 200,
            'data' => $absentstudentdata,
            'message' => 'Absent students list.',
            'success' => true

        ]);
    }

    public function todayPendingHomework(Request $request)
    {
        $user = $this->authenticateUser();
        $teacher_id = $user->reg_id;
        $academicYr = JWTAuth::getPayload()->get('academic_year');

        $class_id   = $request->input('class_id');
        $section_id = $request->input('section_id');

        if(!$class_id) {
            return response()->json([
                'status'  => 400,
                'message' => 'class_id is required',
                'data'    => [],
                'success' => false
            ] , 400);
        }
        
        if(!$section_id) {
            return response()->json([
                'status'  => 400,
                'message' => 'section_id is required',
                'data'    => [],
                'success' => false
            ] , 400);
        }

        $today = Carbon::now()->toDateString();

        $pendingHomeworks = DB::table('homework')
            ->select(
                'homework.homework_id',
                'homework.description',
                'homework.end_date',
                'homework_comments.homework_status',
                DB::raw("CONCAT(student.first_name, ' ', student.last_name) as student_name"),
                'subject_master.name as subject_name',
                'class.name as class_name',
                'section.name as section_name'
            )
            ->leftJoin('subject_master', 'subject_master.sm_id', '=', 'homework.sm_id')
            ->leftJoin('homework_comments', 'homework.homework_id', '=', 'homework_comments.homework_id')
            ->leftJoin('student', 'homework_comments.student_id', '=', 'student.student_id')
            ->leftJoin('class', 'homework.class_id', '=', 'class.class_id')
            ->leftJoin('section', 'homework.section_id', '=', 'section.section_id')
            ->where('homework.publish', 'Y')
            ->where('homework.end_date', $today)
            ->where('homework.class_id', $class_id)
            ->where('homework.section_id', $section_id)
            ->where('homework.teacher_id', $teacher_id)
            ->where('homework.academic_yr', $academicYr)
            ->whereIn('homework_comments.homework_status', ['Assigned', 'Partial'])
            ->get();

        return response()->json([
            'status'  => 200,
            'message' => "Today's pending homeworks fetched successfully",
            'today' => $today,
            'data'    => $pendingHomeworks,
            'success' => true
        ]);
    }
 
    public function getNewStudentListbysectionforregister(Request $request , $section_id){         
        $studentList = Student::with('getClass', 'getDivision')
                                ->where('section_id',$section_id)
                                ->where('parent_id','0')
                                ->distinct()
                                ->get();

        return response()->json($studentList);                        
    }

    public function getAllNewStudentListForRegister(Request $request){                 
        $studentList = Student::with('getClass', 'getDivision')
                                ->where('parent_id','0')
                                ->distinct()
                                ->get();

        return response()->json($studentList);                        
    }

    public function downloadCsvTemplateWithData(Request $request, $section_id)
    {
        // Extract the academic year from the token payload
        $academicYear = "2023-2024";
    
        // Fetch only the necessary fields from the Student model where academic year and section_id match
        $students = Student::select(
            'student_id as student_id', // Specify the table name
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
            'class.name as Class', // Specify the table name
            'section.name as Division',
            'mother_name as *Mother Name', // Assuming you have this field
            'mother_occupation as Mother Occupation', // Assuming you have this field
            'm_mobile as *Mother Mobile No.(Only Indian Numbers)', // Assuming you have this field
            'm_emailid as *Mother Email-Id', // Assuming you have this field
            'father_name as *Father Name', // Assuming you have this field
            'father_occupation as Father Occupation', // Assuming you have this field
            'f_mobile as *Father Mobile No.(Only Indian Numbers)', // Assuming you have this field
            'f_email as *Father Email-Id', // Assuming you have this field
            'm_adhar_no as Mother Aadhaar No.', // Assuming you have this field
            'parent_adhar_no as Father Aadhaar No.', // Assuming you have this field
            'permant_add as *Address',
            'city as *City',
            'state as *State',
            'admission_date as *DOA(in dd/mm/yyyy format)',
            'reg_no as *GRN No'
        )
        ->distinct() 
        ->leftJoin('parent', 'student.parent_id', '=', 'parent.parent_id')  
        ->leftJoin('section', 'student.section_id', '=', 'section.section_id') // Use correct table name 'sections'
        ->leftJoin('class', 'student.class_id', '=', 'class.class_id') // Use correct table name 'sections'
        ->where('student.parent_id', '=', '0')
        ->where('student.academic_yr', $academicYear)  // Specify the table name here
        ->where('student.section_id', $section_id) // Specify the table name here
        ->get()
        ->toArray();
    
        // Debugging: Log the retrieved students data
        \Log::info('Students Data: ', $students); // Check Laravel logs to see if data is fetched correctly
    
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
    
        $callback = function() use ($columns, $students) {
            $file = fopen('php://output', 'w');
    
            // Write the header row
            fputcsv($file, $columns);
    
            // Write each student's data below the headers
            foreach ($students as $student) {
                fputcsv($file, $student);
            }
    
            fclose($file);
        };
    
        // Return the CSV file as a response
        return response()->stream($callback, 200, $headers);
    }
    
public function updateCsvData(Request $request, $section_id)
{
    // Validate the uploaded CSV file
    $request->validate([
        'file' => 'required|file|mimes:csv,txt|max:2048',
    ]);

    // Read the uploaded CSV file
    $file = $request->file('file');
    if (!$file->isValid()) {
        return response()->json(['message' => 'Invalid file upload'], 400);
    }

    // Get the contents of the CSV file
    $csvData = file_get_contents($file->getRealPath());
    $rows = array_map('str_getcsv', explode("\n", $csvData));
    $header = array_shift($rows); // Extract the header row

    // Define the CSV to database column mapping
    $columnMap = [
        'student_id' => 'student_id',
        '*First Name' => 'first_name',
        'Mid name' => 'mid_name',
        'last name' => 'last_name',
        '*Gender' => 'gender',
        '*DOB(in dd/mm/yyyy format)' => 'dob',
        'Student Aadhaar No.' => 'stu_aadhaar_no',
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
        'Mother Aadhaar No.' => 'mother_aadhaar_no',
        'Father Aadhaar No.' => 'father_aadhaar_no',
        '*Address' => 'permant_add',
        '*City' => 'city',
        '*State' => 'state',
        '*DOA(in dd/mm/yyyy format)' => 'admission_date',
        '*GRN No' => 'reg_no',
    ];

    // Prepare an array to store invalid rows for reporting
    $invalidRows = [];

    // Fetch the class_id using the provided section_id
    $division = Division::find($section_id);
    if (!$division) {
        return response()->json(['message' => 'Invalid section ID'], 400);
    }
    $class_id = $division->class_id;

    // Start processing the CSV rows
    foreach ($rows as $rowIndex => $row) {
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        // Map CSV columns to database fields
        $studentData = [];
        foreach ($header as $index => $columnName) {
            if (isset($columnMap[$columnName])) {
                $dbField = $columnMap[$columnName];
                $studentData[$dbField] = $row[$index] ?? null;
            }
        }

        // Validate required fields
        if (empty($studentData['student_id'])) {
            $invalidRows[] = array_merge($row, ['error' => 'Missing student ID']);
            continue;
        }

        if (!in_array($studentData['gender'], ['M', 'F', 'O'])) {
            $invalidRows[] = array_merge($row, ['error' => 'Invalid gender value. Expected M, F, or O.']);
            continue;
        }

        // Validate and convert DOB and admission_date formats
        if (!$this->validateDate($studentData['dob'], 'd-m-Y')) {
            $invalidRows[] = array_merge($row, ['error' => 'Invalid DOB format. Expected dd/mm/yyyy.']);
            continue;
        } else {
            $studentData['dob'] = \Carbon\Carbon::createFromFormat('d-m-Y', $studentData['dob'])->format('Y-m-d');
        }

        if (!$this->validateDate($studentData['admission_date'], 'd-m-Y')) {
            $invalidRows[] = array_merge($row, ['error' => 'Invalid admission date format. Expected dd-mm-yyyy.']);
            continue;
        } else {
            $studentData['admission_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $studentData['admission_date'])->format('Y-m-d');
        }

        // Start a database transaction
        DB::beginTransaction();
        try {
            // Find the student by `student_id`
            $student = Student::where('student_id', $studentData['student_id'])->first();
            if (!$student) {
                $invalidRows[] = array_merge($row, ['error' => 'Student not found']);
                DB::rollBack();
                continue;
            }

            // Handle parent creation or update
            $parentData = [
                'father_name' => $studentData['father_name'] ?? null,
                'father_occupation' => $studentData['father_occupation'] ?? null,
                'f_mobile' => $studentData['father_mobile'] ?? null,
                'f_email' => $studentData['father_email'] ?? null,
                'mother_name' => $studentData['mother_name'] ?? null,
                'mother_occupation' => $studentData['mother_occupation'] ?? null,
                'm_mobile' => $studentData['mother_mobile'] ?? null,
                'm_emailid' => $studentData['mother_email'] ?? null,
                'parent_adhar_no' => $studentData['Father Aadhaar No.'] ?? null,
                'm_adhar_no' => $studentData['Mother Aadhaar No.'] ?? null,
            ];

            // Check if parent exists, if not, create one
            $parent = Parents::where('f_mobile', $parentData['f_mobile'])->first();
            if (!$parent) {
                $parent = Parents::create($parentData);
            }


           
            // Update the student's parent_id and class_id
            $student->parent_id = $parent->parent_id;
            $student->class_id = $class_id;
            $student->gender = $studentData['gender'];
            $student->first_name = $studentData['first_name'];
            $student->mid_name = $studentData['mid_name'];
            $student->last_name = $studentData['last_name'];
            $student->dob = $studentData['dob'];
            $student->admission_date = $studentData['admission_date'];
            $student->stu_aadhaar_no = $studentData['stu_aadhaar_no'];
            $student->mother_tongue = $studentData['mother_tongue'];
            $student->religion = $studentData['religion'];
            $student->caste = $studentData['caste'];
            $student->subcaste = $studentData['subcaste'];
            $student->IsDelete = 'N';
            $student->save();

            // Insert data into user_master table (skip if already exists)
            DB::table('user_master')->updateOrInsert(
                ['user_id' => $studentData['father_email']],
                [
                    'name' => $studentData['father_name'],
                    'password' => 'arnolds',
                    'reg_id' => $parent->parent_id,
                    'role_id' => 'P',
                    'IsDelete' => 'N',
                ]
            );

            // Commit the transaction
            DB::commit();
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
        $csv->insertOne(array_merge($header, ['error']));
        foreach ($invalidRows as $invalidRow) {
            $csv->insertOne($invalidRow);
        }
        $filePath = 'public/csv_rejected/rejected_rows_' . now()->format('Y_m_d_H_i_s') . '.csv';
        Storage::put($filePath, $csv->toString());

        return response()->json([
            'message' => 'Some rows contained errors.',
            'invalid_rows' => Storage::url($filePath),
        ], 422);
    }

    // Return a success response
    return response()->json(['message' => 'CSV data updated successfully.']);
}

// Helper method to validate date format
private function validateDate($date, $format = 'Y-m-d')
{
    $d = \DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

public function deleteNewStudent( Request $request , $studentId)
{
    // Find the student by ID
    $student = Student::find($studentId);    
    if (!$student) {
        return response()->json(['error' => 'New Student not found'], 404);
    }

    // Update the student's isDelete and isModify status to 'Y'
    $payload = getTokenPayload($request);    
    $authUser = $payload->get('reg_id'); 
    $student->isDelete = 'Y';
    $student->isModify = 'Y';
    $student->deleted_by = $authUser;
    $student->deleted_date = Carbon::now();
    $student->save();

    return response()->json(['message' => 'New Student deleted successfully']);
}

public function getParentInfoOfStudent(Request $request, $siblingStudentId): JsonResponse
    {
         
        // Fetch notices with teacher names
        $parent = Parent::select([
                'parent.parent_id',
                'parent.father_name',
                'parent.father_occupation',
                'parent.f_office_add',
                'parent.f_office_tel',
                'parent.f_mobile',
                'parent.f_email',
                'parent.mother_name',
                'parent.mother_occupation',
                'parent.m_office_add',
                'parent.m_office_tel',
                'parent.m_mobile',
                'parent.parent_adhar_no',
                'parent.m_adhar_no',
                'parent.f_dob',
                'parent.m_dob',
                'parent.f_blood_group',
                'parent.m_blood_group',
            ])
            ->join('student as s', 's.parent_id', '=', 'parent.parent_id')
             ->where('s.student_id', $siblingStudentId)
             ->get();

        return response()->json(['parent' => $parent, 'success' => true]);
    }


    public function getStudentListClass($class_id,$section_id){
        try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
            $students = DB::table('student')
                            ->where('class_id',$class_id)
                            ->where('section_id',$section_id)
                            ->where('IsDelete','N')
                            ->where('isPromoted','!=','Y')
                            ->get();
        return response()->json([
            'status'=> 200,
            'message'=>'Student List for this class',
            'data' =>$students,
            'success'=>true
            ]);
        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Updating of Data',
                'data' =>$user->role_id,
                'success'=>false
                ]);
        }
    
    }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
           }
    }

    public function nextClassPromote(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $current_academic_year = $customClaims;

                // Split the string into start year and end year
                list($start_year, $end_year) = explode('-', $current_academic_year);
                
                // Increment the start year to move to the next academic year
                $next_start_year = $start_year + 1;
                $next_end_year = $end_year + 1;
                
                // Create the next academic year
                $next_academic_year = $next_start_year . '-' . $next_end_year;
                // dd($next_academic_year);

                $class = DB::table('class')->where('academic_yr',$next_academic_year)->get();
                return response()->json([
                    'status'=> 200,
                    'message'=>'Class List for the next academic year',
                    'data' =>$class,
                    'success'=>true
                    ]);
                

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Updating of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                    ]);
            }
        
        }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

    }

    public function nextSectionPromote(Request $request,$class_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $current_academic_year = $customClaims;

                // Split the string into start year and end year
                list($start_year, $end_year) = explode('-', $current_academic_year);
                
                // Increment the start year to move to the next academic year
                $next_start_year = $start_year + 1;
                $next_end_year = $end_year + 1;
                
                // Create the next academic year
                $next_academic_year = $next_start_year . '-' . $next_end_year;
                // dd($next_academic_year);

                $section = DB::table('section')->where('academic_yr',$next_academic_year)->where('class_id',$class_id)->get();
                return response()->json([
                    'status'=> 200,
                    'message'=>'Section List for the next academic year',
                    'data' =>$section,
                    'success'=>true
                    ]);
                

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Updating of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                    ]);
            }
        
        }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
               }

    }

    public function promoteStudentsUpdate(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
                $current_academic_year = $customClaims;

                // Split the string into start year and end year
                list($start_year, $end_year) = explode('-', $current_academic_year);
                
                // Increment the start year to move to the next academic year
                $next_start_year = $start_year + 1;
                $next_end_year = $end_year + 1;
                
                // Create the next academic year
                $next_academic_year = $next_start_year . '-' . $next_end_year;
                $students = $request->input('selector');
                $tclass_id = $request->input('tclass_id');
                $tsection_id = $request->input('tsection_id');

        foreach ($students as $student_id) {
            // Skip if student ID is empty or null
            if (empty($student_id)) {
                continue;
            }

            // Fetch the student info
            $student = Student::where('student_id', $student_id)
                ->where('academic_yr', $customClaims) // Assuming the current academic year is stored in session
                ->first();

            if ($student) {
                // dd($student);
                // Prepare the data for the new record
                $data = [
                    'first_name' => $student->first_name,
                    'mid_name' => $student->mid_name,
                    'last_name' => $student->last_name,
                    'parent_id' => $student->parent_id,
                    'dob' => $student->dob,
                    'gender' => $student->gender,
                    'admission_date' => $student->admission_date,
                    'blood_group' => $student->blood_group,
                    'religion' => $student->religion,
                    'caste' => $student->caste,
                    'subcaste' => $student->subcaste,
                    'transport_mode' => $student->transport_mode,
                    'vehicle_no' => $student->vehicle_no,
                    'emergency_name' => $student->emergency_name,
                    'emergency_contact' => $student->emergency_contact,
                    'emergency_add' => $student->emergency_add,
                    'height' => $student->height,
                    'weight' => $student->weight,
                    'nationality' => $student->nationality,
                    'permant_add' => $student->permant_add,
                    'city' => $student->city,
                    'state' => $student->state,
                    'pincode' => $student->pincode,
                    'IsDelete' => $student->IsDelete,
                    'reg_no' => $student->reg_no,
                    'house' => $student->house,
                    'stu_aadhaar_no' => $student->stu_aadhaar_no,
                    'category' => $student->category,
                    'academic_yr' => $next_academic_year,
                    'prev_year_student_id' => $student_id,
                    'stud_id_no' => $student->stud_id_no,
                    'birth_place' => $student->birth_place,
                    'admission_class' => $student->admission_class,
                    'mother_tongue' => $student->mother_tongue,
                    'has_specs' => $student->has_specs,
                    'student_name' => $student->student_name,
                    'class_id' => $tclass_id,
                    'section_id' => $tsection_id,
                ];
                // dd($data);
                // Insert the student record for the next academic year
                Student::create($data);

                // Mark the student as promoted
                DB::table('student')
                    ->where('student_id', $student_id)
                    ->where('academic_yr', $customClaims)
                    ->update(['isPromoted' => 'Y']);
            }
        }

        return response()->json([
            'status' =>200,
            'message' => 'Students promoted successfully!',
            'data'=> $student,
            'success'=>true
        ]);
    }
    else{
        return response()->json([
            'status'=> 401,
            'message'=>'This User Doesnot have Permission for the Updating of Data',
            'data' =>$user->role_id,
            'success'=>false
            ]);
    }

}
    catch (Exception $e) {
        \Log::error($e); // Log the exception
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
       }
        
    }
    //Student List with Class name Dev name-Manish Kumar Sharma 09-04-2025
    public function getallStudentWithClass(){
        try{
           $user = $this->authenticateUser();
           $customClaims = JWTAuth::getPayload()->get('academic_year');
           if($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M'){
               $data = $this->get_all_registered_students($customClaims);

               // Process the data to add 'label' and 'value'
               $data->transform(function ($item) {
                   if (!empty($item->last_name) && $item->last_name != "No Data") {
                       $item->label = $item->first_name . ' ' . $item->last_name . ' (' . $item->class_name . $item->section_name . ')';
                   } else {
                       if (!empty($item->mid_name) && $item->mid_name != "No Data") {
                           $item->label = $item->first_name . ' ' . $item->mid_name . ' (' . $item->class_name . $item->section_name . ')';
                       } else {
                           $item->label = $item->first_name . ' (' . $item->class_name . $item->section_name . ')';
                       }
                   }
                   
                   // Create the 'value' field
                   $item->value = $item->student_id . "^" . $item->class_id . "*" . $item->section_id;
           
                   return $item;
               });
           
               // Return the processed data as a JSON response
               return response()->json($data);
               
               
           }
           else{
               return response()->json([
                   'status'=> 401,
                   'message'=>'This user does not have permission for the updating of data.',
                   'data' =>$user->role_id,
                   'success'=>false
                   ]);
                   }
               
               }
           catch (Exception $e) {
               \Log::error($e); // Log the exception
               return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
              }
       
   }
   //Student List with Class name Dev name-Manish Kumar Sharma 09-04-2025
   public function get_all_registered_students($acd_yr)
   {
       $data = DB::table('student as a')
           ->join('class as b', 'a.class_id', '=', 'b.class_id')
           ->join('section as c', 'a.section_id', '=', 'c.section_id')
           ->select('a.student_id', 'a.first_name', 'a.mid_name', 'a.last_name', 'b.class_id', 'b.name as class_name', 'c.section_id', 'c.name as section_name')
           ->where('a.IsDelete', 'N')
           ->where('a.academic_yr', $acd_yr)
           ->get();
   
       return $data;
   }
   
   public function getFieldsForUpdateStudent(Request $request){
       $fields = DB::table('update_studentdata_settings')
                ->get();

       return response()->json([
           'status'=>200,
           'message'=>'Fields for update',
           'data' => $fields,
           'success'=>true
           ]);
       
   }
   
   public function getStudentDataWithFieldData(Request $request,$id,$fieldname){
       $user = $this->authenticateUser();
       $customClaims = JWTAuth::getPayload()->get('academic_year');
       $query = DB::table('student')
                    ->where('section_id', $id)
                    ->where('academic_yr', $customClaims)
                    ->where('isDelete', 'N')
                    ->select(
                        'student_id',
                        'first_name',
                        'mid_name',
                        'last_name',
                        'roll_no'
                    );
            
                // If the requested field is 'religion', format it in camel case
                if ($fieldname === 'religion') {
                    $query->addSelect(DB::raw("CONCAT(UPPER(SUBSTRING(religion, 1, 1)), LOWER(SUBSTRING(religion, 2))) as religion"));
                } else {
                    $query->addSelect($fieldname);
                }
            
                $students = $query->orderBy('roll_no', 'ASC')->get();
                            
        return response()->json([
           'status'=>200,
           'message'=>'Student data according to field.',
           'data' => $students,
           'success'=>true
           ]);
       
   }
   
   public function updateStudentDataWithFieldData(Request $request){
        $user = $this->authenticateUser();
        $academicYear = JWTAuth::getPayload()->get('academic_year');
    
        $studentsData = $request->input('students'); 
    
        if (!is_array($studentsData) || empty($studentsData)) {
            return response()->json([
                'status' => 400,
                'message' => 'No student data provided',
                'success' => false
            ], 400);
        }
    
        $updatedCount = 0;
        $uppercaseFields = ['first_name', 'mid_name', 'last_name', 'student_name'];
        foreach ($studentsData as $student) {
    
            $studentId = $student['student_id'] ?? null;
            if (!$studentId) continue;
    
            $updateData = [];
    
            
    
            foreach ($student as $field => $value) {
            if (in_array($field, $uppercaseFields) && !is_null($value)) {
                $updateData[$field] = strtoupper($value); // Convert to uppercase
            } else {
                $updateData[$field] = $value;
            }
            }
    
            if (!empty($updateData)) {
                DB::table('student')
                    ->where('student_id', $studentId)
                    ->where('academic_yr', $academicYear)
                    ->update($updateData);
    
                $updatedCount++;
            }
        }
    
        return response()->json([
            'status' => 200,
            'message' => "$updatedCount students updated successfully",
            'success' => true
        ]);
   }
   
   
   public function getPendingBooksForReturn(Request $request){
       $student_id = $request->input('student_id');
       $books = DB::select("select a.*,b.book_title, s.first_name,s.last_name,s.class_id,s.section_id from issue_return a, book b, student s where a.book_id = b.book_id and a.member_id = s.student_id and member_type='S' and return_date='0000-00-00' and member_id IN (".$student_id.") order by issue_date asc");
       $booksCount = count($books);
       return response()->json([
           'status'=>200,
           'message'=>'Pending books for return.',
           'data' => $booksCount,
           'bookdetails'=>$books,
           'success'=>true
           ]);
       
       
   }

   public function birthdayList(Request $request)
    {
        try {
            // Authenticate user
            $user = $this->authenticateUser();
            $teacher_id = $user->reg_id;
            $academic_yr = JWTAuth::getPayload()->get('academic_year');

            $classes = DB::table('subject')
            ->select('class_id', 'section_id')
            ->where('teacher_id', $teacher_id)
            ->where('academic_yr', $academic_yr)
            ->get();

            // Extract UNIQUE class_ids and section_ids
            $class_ids = $classes->pluck('class_id')->unique()->values()->toArray();
            $section_ids = $classes->pluck('section_id')->unique()->values()->toArray();

            $today     = Carbon::now()->format('m-d');
            $yesterday = Carbon::now()->subDay()->format('m-d');
            $tomorrow  = Carbon::now()->addDay()->format('m-d');

            $birthdayData = [
                'yesterday' => [],
                'today'     => [],
                'tomorrow'  => []
            ];

            $students = DB::table('student')
                ->select(
                    'student.student_id',
                    'student.first_name',
                    'student.mid_name',
                    'student.last_name',
                    'student.dob',
                    'class.name as class_name',
                    'section.name as section_name'
                )
                ->leftJoin('class', 'student.class_id', '=', 'class.class_id')
                ->leftJoin('section', 'student.section_id', '=', 'section.section_id')
                ->where('student.IsDelete', 'N')
                ->whereIn('student.class_id', $class_ids)
                ->whereIn('student.section_id', $section_ids)
                ->get();

            foreach ($students as $student) {
                if (empty($student->dob)) {
                    continue;
                }
                $dob = Carbon::parse($student->dob)->format('m-d');
                if ($dob === $yesterday) {
                    $birthdayData['yesterday'][] = $student;
                } elseif ($dob === $today) {
                    $birthdayData['today'][] = $student;
                } elseif ($dob === $tomorrow) {
                    $birthdayData['tomorrow'][] = $student;
                }
            }

            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => 'Birthday list of students fetched successfully.',
                'data'    => $birthdayData
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => 'Something went wrong while fetching birthday list.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function studentsBelowAttendance(Request $request)
    {
        try {
            // Authenticate user
            $user = $this->authenticateUser();

            // Validate request
            $validated = $request->validate([
                'class_id'   => 'required|integer',
                'section_id' => 'required|integer',
                'end_date'   => 'required|date',
                'threshold'  => 'required|numeric|min:0|max:100',
            ]);

            $settingsData = JWTAuth::getPayload()->get('settings_new');

            $startDate = $settingsData['academic_yr_from'] ?? null;

            if (!$startDate) {
                return response()->json([
                    'status'  => 400,
                    'success' => false,
                    'message' => 'Academic year start date not found in settings. Please logout and login again.',
                    'data'    => []
                ], 400);
            }

            $class_id   = $validated['class_id'];
            $section_id = $validated['section_id'];
            $endDate    = $validated['end_date'];
            $threshold  = $validated['threshold'];

            $lowAttendanceStudents = DB::table('attendance')
                ->join('student', 'student.student_id', '=', 'attendance.student_id')
                ->join('class', 'class.class_id', '=', 'attendance.class_id')
                ->join('section', 'section.section_id', '=', 'attendance.section_id')
                ->whereBetween('attendance.only_date', [$startDate, $endDate])
                ->where('student.isDelete', 'N')
                ->where('attendance.class_id', $class_id)
                ->where('attendance.section_id', $section_id)
                ->select(
                    'student.student_id',
                    'student.first_name',
                    'student.mid_name',
                    'student.last_name',
                    'class.name as classname',
                    'section.name as sectionname',
                    'class.class_id',
                    'section.section_id',
                    DB::raw('SUM(CASE WHEN attendance.attendance_status = "0" THEN 1 ELSE 0 END) as present_days'),
                    DB::raw('COUNT(attendance.attendance_id) as total_days'),
                    DB::raw('ROUND((SUM(CASE WHEN attendance.attendance_status = "0" THEN 1 ELSE 0 END) / COUNT(attendance.attendance_id) * 100), 2) as attendance_percentage')
                )
                ->groupBy(
                    'student.student_id',
                    'student.first_name',
                    'student.mid_name',
                    'student.last_name',
                    'class.name',
                    'section.name',
                    'class.class_id',
                    'section.section_id'
                )
                ->having('attendance_percentage', '<', $threshold)
                ->orderBy('class.class_id', 'asc')
                ->orderBy('section.section_id', 'asc')
                ->orderBy('student.first_name', 'asc')
                ->get();

            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => 'Student attendance fetched successfully.',
                'data'    => $lowAttendanceStudents
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => 'Something went wrong while fetching student attendance.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
   
   public function getStudentListAttendance(Request $request){
        $user = $this->authenticateUser();
        $settingsData = JWTAuth::getPayload()->get('settings');
        $department_id = $request->input('department_id');
        $threshold = $request->input('threshold');
        $startDate = $settingsData['academic_yr_from'];
        $endDate = $request->input('end_date'); 
        $classIds = DB::table('class')
            ->where('department_id', $department_id)
            ->pluck('class_id');
        
        $sectionIds = DB::table('section')
            ->whereIn('class_id', $classIds)
            ->pluck('section_id');
        
        $lowAttendanceStudents = DB::table('attendance')
            ->join('student', 'student.student_id', '=', 'attendance.student_id')
            ->join('class', 'class.class_id', '=', 'attendance.class_id')
            ->join('section', 'section.section_id', '=', 'attendance.section_id')
            ->whereBetween('attendance.only_date', [$startDate, $endDate])
            ->where('student.isDelete', 'N')
            ->whereIn('attendance.class_id', $classIds)
            ->whereIn('attendance.section_id', $sectionIds)
            ->select(
                'student.student_id',
                'student.first_name',
                'student.mid_name',
                'student.last_name',
                'class.name as classname',
                'section.name as sectionname',
                'class.class_id',
                'section.section_id',
                DB::raw('SUM(CASE WHEN attendance.attendance_status = "0" THEN 1 ELSE 0 END) as present_days'),
                DB::raw('COUNT(attendance.attendance_id) as total_days'),
               DB::raw('ROUND((SUM(CASE WHEN attendance.attendance_status = "0" THEN 1 ELSE 0 END) / COUNT(attendance.attendance_id) * 100), 2) as attendance_percentage')
            )
            ->groupBy(
                'student.student_id',
                'student.first_name',
                'student.mid_name',
                'student.last_name',
                'class.name',
                'section.name',
                'class.class_id',
                'section.section_id'
            )
            ->having('attendance_percentage', '<', $threshold)
            ->orderBy('class.class_id', 'asc')   
            ->orderBy('section.section_id', 'asc') 
            ->orderBy('student.first_name', 'asc') 
            ->get();
        
            return response()->json([
                   'status'=>200,
                   'message'=>'Student attendance.',
                   'data' => $lowAttendanceStudents,
                   'success'=>true
                   ]);
       
   }
   
   public function sendMessageForAttendance(Request $request){
       $user = $this->authenticateUser();
       $academicYear = JWTAuth::getPayload()->get('academic_year');
       $students = $request->input('student_id');
       $message = $request->input('message');
       $schoolsettings = getSchoolSettingsData();
       $whatsappintegration = $schoolsettings->whatsapp_integration;
       $smsintegration = $schoolsettings->sms_integration;
         
       if ($whatsappintegration === 'Y' || $smsintegration === 'Y') {
         SendMessageStudentAttendanceShortage::dispatch($students, $message);
       }

       return response()->json([
                   'status'=>200,
                   'message'=>'Messages for student attendance shortage.',
                   'success'=>true
                   ]);
       
   }

    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }
    
    public function getStudentsByClassSection(Request $request){
        $user = $this->authenticateUser();
        $academicYear = JWTAuth::getPayload()->get('academic_year');
        $class_id = $request->input('class_id');
        $section_id= $request->input('section_id');
        $students = DB::table('student')
                        ->where('class_id', $class_id)
                        ->where('section_id', $section_id)
                        ->where('IsDelete', 'N')
                        ->where('academic_yr', $academicYear)
                        ->orderBy('roll_no', 'asc')
                        ->orderBy('reg_no', 'asc')
                        ->get();
                        
        return response()->json([
                   'status'=>200,
                   'data' =>$students,
                   'message'=>'Student list by class section.',
                   'success'=>true
                   ]);
        
    }
    
    public function getAttClassSectionDay(Request $request){
        $user = $this->authenticateUser();
        $academicYear = JWTAuth::getPayload()->get('academic_year');
        $class_id = $request->input('class_id');
        $section_id= $request->input('section_id');
        $dateatt = $request->input('dateatt');
        $attendance = DB::table('attendance as a')
                            ->select('a.*', 'b.first_name', 'b.last_name', 'b.roll_no')
                            ->join('student as b', 'a.student_id', '=', 'b.student_id')
                            ->where('a.class_id', $class_id)
                            ->where('a.section_id', $section_id)
                            ->where('a.only_date', $dateatt)
                            ->where('a.academic_yr', $academicYear)
                            ->orderBy('b.roll_no', 'asc')
                            ->orderBy('b.reg_no', 'asc')
                            ->get();
        return response()->json([
                   'status'=>200,
                   'data' =>$attendance,
                   'message'=>'Student attendance list.',
                   'success'=>true
                   ]);
        
    }
    
    // OLD BUG CODE
    // public function saveMarkAttendance(Request $request){
    //     $user = $this->authenticateUser();
    //     $academic_yr = JWTAuth::getPayload()->get('academic_year');
    //     $data = [];
    //     $t_id = $user->reg_id; 
    //     $unq = rand(200, 500);

    //     $countOfStudents = $request->input('countOfStudents');
    //     $class_id = $request->input('class_id');
    //     $section_id = $request->input('section_id');
    //     $dateatt = Carbon::createFromFormat('d-m-Y', $request->input('dateatt'))->format('Y-m-d');

        

    //         $countCheckBox = $request->input('checkbox', []);

    //         foreach ($countCheckBox as $student_id) {
    //             // Check if attendance already exists
    //             $existing = DB::table('attendance')
    //                 ->where('student_id', $student_id)
    //                 ->where('only_date', $dateatt)
    //                 ->where('academic_yr', $academic_yr)
    //                 ->get();

    //             if ($existing->count() > 0) {
    //                 // Delete existing attendance record
    //                 DB::table('attendance')
    //                     ->where('student_id', $student_id)
    //                     ->where('only_date', $dateatt)
    //                     ->where('academic_yr', $academic_yr)
    //                     ->delete();
    //             }

    //             // Prepare attendance data
    //             $attendance_status = $request->input("present_$student_id") == '1' ? '1' : '0';

    //             $attendanceData = [
    //                 'class_id' => $class_id,
    //                 'section_id' => $section_id,
    //                 'only_date' => $dateatt,
    //                 'academic_yr' => $academic_yr,
    //                 'teacher_id' => $t_id,
    //                 'student_id' => $student_id,
    //                 'attendance_status' => $attendance_status,
    //                 'unq_id' => $unq,
    //                 'date' => now(),
    //             ];

    //             // Insert attendance record
    //             DB::table('attendance')->insert($attendanceData);

    //             // Handle SMS for absent students
    //             if ($attendance_status == '1') {
    //                 $smsExists = DB::table('attendance_sms_log')
    //                     ->where('student_id', $student_id)
    //                     ->where('absent_date', $dateatt)
    //                     ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(sms_status, '$.status')) = 'success'")
    //                     ->exists();

    //                 if (!$smsExists) {
    //                     // $temp_id = '1107164450685654320';
    //                     // $studentName = DB::table('student')->where('student_id', $student_id)->value('first_name');
    //                     // $message = "Dear Parent, $studentName has been marked absent on " . Carbon::parse($dateatt)->format('d-m-Y') . ". Login to school application for details. -EvolvU";

    //                     // $contactData = DB::table('student_contact')->where('student_id', $student_id)->get();

    //                     // foreach ($contactData as $contact) {
    //                     //     // Uncomment this line to send SMS
    //                     //     // $sms_status = $this->send_sms($contact->phone_no, $message, $temp_id);
    //                     //     $sms_status = ""; 

    //                     //     DB::table('attendance_sms_log')->insert([
    //                     //         'sms_status' => $sms_status,
    //                     //         'student_id' => $student_id,
    //                     //         'absent_date' => $dateatt,
    //                     //         'phone_no' => $contact->phone_no,
    //                     //         'sms_date' => now()->format('Y-m-d'),
    //                     //     ]);
    //                     // }
    //                 }
    //             }
    //         }

    //         return response()->json([
    //             'status'  =>200,
    //             'message' => 'Attendance saved successfully!',
    //             'success' =>true
    //             ]);
    // }

    // NEW BUG FIXED CODE
    public function saveMarkAttendance(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        $t_id = $user->reg_id;
        $class_id = $request->class_id;
        $section_id = $request->section_id;
        $dateatt = Carbon::createFromFormat('d-m-Y', $request->dateatt)->format('Y-m-d');

        // Checked students only
        $checkedStudents = $request->input('checkbox', []);

        DB::beginTransaction();

        try {

            /**
             * 1️⃣ DELETE ALL existing attendance for this class/section/date
             * This avoids ghost / stale entries
             */
            DB::table('attendance')
                ->where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('only_date', $dateatt)
                ->where('academic_yr', $academic_yr)
                ->delete();

            /**
             * 2️⃣ INSERT ONLY checked students
             */
            foreach ($checkedStudents as $student_id) {

                $attendance_status = $request->input("present_$student_id") == '1' ? 1 : 0;

                DB::table('attendance')->insert([
                    'class_id'          => $class_id,
                    'section_id'        => $section_id,
                    'only_date'         => $dateatt,
                    'academic_yr'       => $academic_yr,
                    'teacher_id'        => $t_id,
                    'student_id'        => $student_id,
                    'attendance_status' => $attendance_status,
                    'unq_id'            => rand(200, 500),
                    'date'              => now(),
                ]);

                /**
                 * 3️⃣ SMS only for absentees (if 1 = absent)
                 */
                if ($attendance_status == '1') {
                    $smsExists = DB::table('attendance_sms_log')
                        ->where('student_id', $student_id)
                        ->where('absent_date', $dateatt)
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(sms_status, '$.status')) = 'success'")
                        ->exists();

                    if (!$smsExists) {
                        // $temp_id = '1107164450685654320';
                        // $studentName = DB::table('student')->where('student_id', $student_id)->value('first_name');
                        // $message = "Dear Parent, $studentName has been marked absent on " . Carbon::parse($dateatt)->format('d-m-Y') . ". Login to school application for details. -EvolvU";

                        // $contactData = DB::table('student_contact')->where('student_id', $student_id)->get();

                        // foreach ($contactData as $contact) {
                        //     // Uncomment this line to send SMS
                        //     // $sms_status = $this->send_sms($contact->phone_no, $message, $temp_id);
                        //     $sms_status = ""; 

                        //     DB::table('attendance_sms_log')->insert([
                        //         'sms_status' => $sms_status,
                        //         'student_id' => $student_id,
                        //         'absent_date' => $dateatt,
                        //         'phone_no' => $contact->phone_no,
                        //         'sms_date' => now()->format('Y-m-d'),
                        //     ]);
                        // }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => 'Attendance saved successfully'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function deleteMarkAttendance(Request $request){
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $only_date = $request->input('only_date');
        DB::table('attendance')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('only_date', $only_date)
            ->delete();
        return response()->json([
                'status'  =>200,
                'message' => 'Attendance deleted successfully!',
                'success' =>true
                ]);
        
        
    }
    
    public function deleteStudentMarkAttendance(Request $request){
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $attendance_id = $request->input('attendance_id');
        DB::table('attendance')
            ->where('attendance_id', $attendance_id)
            ->delete();
        return response()->json([
                'status'  =>200,
                'message' => 'Attendance deleted successfully!',
                'success' =>true
                ]);
        
    }
    
    public function sendMessageForDailyAttendance(Request $request){
       $user = $this->authenticateUser();
       $academicYear = JWTAuth::getPayload()->get('academic_year');
       $students = $request->input('student_id');
       $message = $request->input('message');
       $schoolsettings = getSchoolSettingsData();
       $whatsappintegration = $schoolsettings->whatsapp_integration;
       $smsintegration = $schoolsettings->sms_integration;
         
       if ($whatsappintegration === 'Y' || $smsintegration === 'Y') {
         SendMessageStudentDailyAttendanceShortage::dispatch($students, $message);
       }

       return response()->json([
                   'status'=>200,
                   'message'=>'Messages for student attendance shortage.',
                   'success'=>true
                   ]);
        
    }
    
    public function sendPendingSMSForDailyAttendanceStudent(Request $request,$webhook_id){
         
            $failedMessages = DB::table('redington_webhook_details')
                                    ->where('webhook_id', $webhook_id)
                                    ->get();
            //  dd($failedMessages);
            foreach ($failedMessages as $failedmessage){
               
                // dd($staffmessage);
                $message = "Dear Parent,".$failedmessage->message. ". Login to school application for details - Evolvu";
                $temp_id = '1107164450685654320';
                $sms_status = app('App\Http\Services\SmsService')->sendSms($failedmessage->phone_no, $message, $temp_id);
                $messagestatus = $sms_status['data']['status'] ?? null;
                // dd($messagestatus);
                if ($messagestatus == "success") {
                    DB::table('redington_webhook_details')->where('webhook_id',$failedmessage->webhook_id)->where('message_type',$failedmessage->message_type)->where('stu_teacher_id',$failedmessage->stu_teacher_id)->update(['sms_sent' => 'Y']);

                }
            }
            
            return response([
                'status'=>200,
                'message'=>'messages sended successfully.',
                'success'=>true
                ]);
         
     }

}

