<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DailyTodo;
use App\Models\Event;
use App\Models\StaffNotice;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ParentController extends Controller
{
    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    public function getParentDetails(Request $request)
    {
        try {
            // Authenticate User
            $user = $this->authenticateUser();
            $academic_yr = JWTAuth::getPayload()->get('academic_year');

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized user'
                ], 401);
            }

            // Fetch Parent
            $parent = DB::table('parent')
                ->where('parent_id', $user->reg_id)
                ->first();

            if (!$parent) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parent record not found'
                ], 404);
            }

            $userMaster = DB::table('user_master')->where('reg_id', $user->reg_id)->first();

            // Fetch Children
            $children = DB::table('student')
                ->select(
                    'student.*',
                    'class.name as class_name',
                    'section.name as section_name'
                )
                ->leftJoin('class', 'student.class_id', '=', 'class.class_id')
                ->leftJoin('section', 'student.section_id', '=', 'section.section_id')
                ->where('student.parent_id', $user->reg_id)
                ->where('student.academic_yr', $academic_yr)
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Parent details fetched successfully',
                'data' => [
                    'parent' => $parent,
                    'children' => $children,
                    'userMaster' => $userMaster,
                ]
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // Log::error('Database error in getParentDetails', [
            //     'error' => $e->getMessage(),
            //     'user_id' => $user->reg_id ?? null
            // ]);

            return response()->json([
                'status' => false,
                'message' => 'Database error occurred',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in getParentDetails', [
                'error' => $e->getMessage(),
                'user_id' => $user->reg_id ?? null
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
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
                'data' => (object) []
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
                'parent_id' => $parent_id,
                'IsDelete' => 'N',
                'academic_yr' => $academicYear
            ])
            ->get();

        $firstStudent = $students->first();

        $guardianFields = [];

        if ($firstStudent) {
            $guardianFields = [
                'guardian_name' => $firstStudent->guardian_name,
                'guardian_mobile' => $firstStudent->guardian_mobile,
                'guardian_add' => $firstStudent->guardian_add,
                'relation' => $firstStudent->relation,
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

            $data['blood_group'] = $request->input("blood_group$j");
            $data['house'] = $request->input("house$j");
            $data['permant_add'] = $request->input("permant_add$j");

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
            $data['guardian_name'] = $request->guardian_name;
            $data['guardian_mobile'] = $request->guardian_mobile;
            $data['relation'] = $request->relation;

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
            $decoded = preg_replace('/^data:image\/\w+;base64,/', '', $request->f_cropped_image);

            $fileName = 'f_' . $parent_id . '.png';
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
            $decoded = preg_replace('/^data:image\/\w+;base64,/', '', $request->m_cropped_image);

            $fileName = 'm_' . $parent_id . '.png';

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
            $decoded = preg_replace('/^data:image\/\w+;base64,/', '', $request->g_cropped_image);

            $fileName = 'g_' . $parent_id . '.png';

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
            'parent_id' => $parent_id,
            'academic_yr' => $request->academic_yr,
            'confirm' => $request->has('confirm') ? 'Y' : 'N'
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
        $fileName = $parent_id . '.svg';

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
            'qr_code' => asset('uploads/qrcode/' . $fileName)
        ]);
    }

    public function getRaiseTicketList()
    {
        try {
            // Authenticate User
            $user = $this->authenticateUser();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized user'
                ], 401);
            }

            $tickets = DB::table('ticket')
                ->select(
                    'ticket.*',
                    'service_type.service_name',
                    's.first_name',
                    's.mid_name',
                    's.last_name'
                )
                ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
                ->join('student as s', 's.student_id', '=', 'ticket.student_id')
                ->where('ticket.created_by', $user->reg_id)
                ->orderBy('ticket.ticket_id', 'DESC')
                ->get()
                ->map(function ($item) {
                    return array_map(function ($value) {
                        return is_string($value)
                            ? preg_replace('/<.+>/sU', '', $value)
                            : $value;
                    }, (array) $item);
                });

            if ($tickets->isNotEmpty()) {
                return response()->json([
                    'status' => true,
                    'Ticket_list' => $tickets
                ], 200);
            }

            return response()->json([
                'status' => false,
                'Ticket_list' => 'No Records Found'
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => false,
                'Ticket_list' => 'No Records Found'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'Ticket_list' => 'No Records Found'
            ], 200);
        }
    }

    public function studentParentVisitReport(Request $request)
    {
        try {
            $query = DB::table('student as s')
                ->join('parent as p', 's.parent_id', '=', 'p.parent_id')
                ->join('visitors as v', 'p.parent_id', '=', 'v.parent_id')
                ->select(
                    's.student_id',
                    's.student_name',
                    's.academic_yr',
                    'p.parent_id',
                    'p.father_name',
                    'p.mother_name',
                    'p.f_mobile',
                    'p.m_mobile',
                    'v.visitor_id',
                    'v.visit_by',
                    'v.visit_date',
                    'v.visit_in_time',
                    'v.visit_out_time'
                );

            if ($request->filled('student_id')) {
                $query->where('s.student_id', $request->student_id);
            }

            if ($request->filled('academic_yr')) {
                $query->where('s.academic_yr', $request->academic_yr);

                $years = explode('-', $request->academic_yr);

                if (count($years) == 2) {
                    $startDate = $years[0] . '-06-01';  // Change if your academic year starts differently
                    $endDate = $years[1] . '-05-31';

                    $query->whereBetween('v.visit_date', [
                        $startDate,
                        $endDate
                    ]);
                }
            }

            if ($request->filled('from_date')) {
                $query->whereDate('v.visit_date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('v.visit_date', '<=', $request->to_date);
            }

            $data = $query
                ->orderByDesc('v.visit_date')
                ->orderByDesc('v.visit_in_time')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
