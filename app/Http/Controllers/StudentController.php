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

class StudentController extends Controller
{
 
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

    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

}

