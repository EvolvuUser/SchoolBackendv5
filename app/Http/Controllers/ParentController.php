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

    public function getUrl(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
        ]);

        $version = DB::connection('school_database')
            ->table('flutter_apk_version')
            ->where('type', $request->type)
            ->orderByDesc('major')
            ->orderByDesc('minor')
            ->orderByDesc('fixes')
            ->selectRaw("
            CONCAT(major, '.', minor, '.', fixes) AS flutter_apk_version,
            release_notes,
            forced_update
        ")
            ->first();

        return response()->json([
            'status' => true,
            'url' => config('externalapis.laravel_url'),
            'flutter_apk_version' => $version?->flutter_apk_version,
            'release_notes' => $version?->release_notes,
            'forced_update' => $version?->forced_update,
        ]);
    }

    public function validateUser(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required'
            ]);

            // School List
            $schools = DB::connection('school_database')
                ->table('user_schoolwise as us')
                ->join('school as s', 's.school_id', '=', 'us.school_id')
                ->where('us.user_id', $request->user_id)
                ->select('s.*')
                ->get();

            if ($schools->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Not a valid user'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'schools' => $schools
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Validate User API Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }

    public function getDashboardData(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academic_yr = JWTAuth::getPayload()->get('academic_year');
            $parent_id = JWTAuth::getPayload()->get('reg_id');
            $today_date = date('Y-m-d');
            $tomorrow_date = date('Y-m-d', strtotime($today_date . ' +1 day'));
            $type_link = $request->input('type_link');

            // Student Data
            $students = DB::table('student')
                ->join('class', 'student.class_id', '=', 'class.class_id')
                ->join('section', 'student.section_id', '=', 'section.section_id')
                ->leftJoin('class_teachers', function ($join) {
                    $join
                        ->on('student.class_id', '=', 'class_teachers.class_id')
                        ->on('student.section_id', '=', 'class_teachers.section_id');
                })
                ->leftJoin('teacher', 'class_teachers.teacher_id', '=', 'teacher.teacher_id')
                ->where('student.parent_id', $parent_id)
                ->where('student.IsDelete', 'N')
                ->where('student.academic_yr', $academic_yr)
                ->where('student.isActive', 'Y')
                ->select(
                    'student.*',
                    'class.name as class_name',
                    'section.name as section_name',
                    'class_teachers.*',
                    'teacher.name as class_teacher'
                )
                ->get();

            // News
            $news = DB::table('news')
                ->select(
                    'news_id',
                    'title',
                    DB::raw("REPLACE(description,'<br/>','\n') as description"),
                    'date_posted',
                    'active_till_date',
                    'posted_by',
                    'url',
                    'image_name',
                    'publish',
                    'isDelete'
                )
                ->where('publish', 'Y')
                ->where('isDelete', 'N')
                ->where(function ($query) use ($today_date) {
                    $query
                        ->whereNull('active_till_date')
                        ->orWhere('active_till_date', '>', $today_date);
                })
                ->orderBy('date_posted', 'DESC')
                ->get();

            // Important Links
            $importantLinks = DB::table('important_links')
                ->where('publish', 'Y')
                ->where('isDelete', 'N')
                ->when($type_link, function ($query) use ($type_link) {
                    return $query->where('type_link', $type_link);
                })
                ->orderBy('create_date', 'DESC')
                ->get();

            // Evolvu Updates
            $evolvuUpdates = DB::table('evolvu_updates')
                ->where('publish', 'Y')
                ->where('isDelete', 'N')
                ->whereDate('expiry_date', '>=', $today_date)
                ->where('role', 'P')
                ->orderBy('publish_date', 'DESC')
                ->get();

            // Attach Images (batched - single query instead of N+1)
            if ($evolvuUpdates->isNotEmpty()) {
                $updateIds = $evolvuUpdates->pluck('update_id');

                $imagesByUpdate = DB::table('evolvu_updates_detail')
                    ->whereIn('update_id', $updateIds)
                    ->select('update_id', 'image_name')
                    ->get()
                    ->groupBy('update_id');

                foreach ($evolvuUpdates as $update) {
                    $update->image_list = $imagesByUpdate->get($update->update_id, collect())->values();
                }
            }

            $todaysExam = [];

            if ($students->isNotEmpty()) {
                $classIds = $students->pluck('class_id')->unique()->values();

                $allExamRows = DB::table('exam_timetable')
                    ->join('exam_timetable_details', 'exam_timetable.exam_tt_id', '=', 'exam_timetable_details.exam_tt_id')
                    ->join('exam', 'exam_timetable.exam_id', '=', 'exam.exam_id')
                    ->whereIn('exam_timetable.class_id', $classIds)
                    ->where('exam_timetable.publish', 'Y')
                    ->where(function ($query) {
                        $query
                            ->whereNotNull('subject_rc_id')
                            ->where('subject_rc_id', '!=', '')
                            ->orWhere(function ($q) {
                                $q
                                    ->where('study_leave', 'Y')
                                    ->whereNull('subject_rc_id');
                            });
                    })
                    ->where(function ($query) use ($today_date, $tomorrow_date) {
                        $query
                            ->whereDate('exam_timetable_details.date', $today_date)
                            ->orWhereDate('exam_timetable_details.date', $tomorrow_date);
                    })
                    ->select(
                        'exam_timetable.*',
                        'exam_timetable_details.*',
                        'exam.name as exam_name'
                    )
                    ->get()
                    ->groupBy('class_id');

                // Collect all subject_rc_ids across all exam rows, resolve names in ONE query
                $allSubjectIds = [];
                foreach ($allExamRows as $classExams) {
                    foreach ($classExams as $exam) {
                        if (!empty($exam->subject_rc_id)) {
                            $ids = preg_split('/[,\/]/', $exam->subject_rc_id);
                            $allSubjectIds = array_merge($allSubjectIds, $ids);
                        }
                    }
                }
                $allSubjectIds = array_values(array_unique(array_filter($allSubjectIds)));

                $subjectNameMap = collect();
                if (!empty($allSubjectIds)) {
                    $subjectNameMap = DB::table('subjects_on_report_card_master')
                        ->whereIn('sub_rc_master_id', $allSubjectIds)
                        ->pluck('name', 'sub_rc_master_id');
                }

                foreach ($students as $student) {
                    $examTimetable = $allExamRows->get($student->class_id, collect());

                    foreach ($examTimetable as $exam) {
                        $subjectNames = '';

                        if (!empty($exam->subject_rc_id)) {
                            if (str_contains($exam->subject_rc_id, ',')) {
                                $subjectIds = explode(',', $exam->subject_rc_id);
                                $delimiter = ' & ';
                            } elseif (str_contains($exam->subject_rc_id, '/')) {
                                $subjectIds = explode('/', $exam->subject_rc_id);
                                $delimiter = ' / ';
                            } else {
                                $subjectIds = [$exam->subject_rc_id];
                                $delimiter = '';
                            }

                            $names = [];
                            foreach ($subjectIds as $sid) {
                                if (isset($subjectNameMap[$sid])) {
                                    $names[] = $subjectNameMap[$sid];
                                }
                            }

                            $subjectNames = implode($delimiter, $names);
                        }

                        $todaysExam[] = [
                            'student_id' => $student->student_id,
                            'student_name' => $student->first_name,
                            'class_name' => $student->class_name,
                            'section_name' => $student->section_name,
                            'exam_id' => $exam->exam_id,
                            'exam_name' => $exam->exam_name,
                            'exam_date' => $exam->date,
                            'start_time' => $exam->start_time ?? null,
                            'end_time' => $exam->end_time ?? null,
                            'study_leave' => $exam->study_leave,
                            'subject_name' => $subjectNames
                        ];
                    }
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Dashboard data fetched successfully.',
                'data' => [
                    'get_childs' => $students,
                    'news' => $news,
                    'important_links' => $importantLinks,
                    'evolvu_updates' => $evolvuUpdates,
                    'todays_exam' => $todaysExam
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('getDashboardData error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
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
            // Get parent_id if student_id is provided
            $parentId = null;

            if ($request->filled('student_id')) {
                $student = DB::table('student')
                    ->select('parent_id', 'academic_yr')
                    ->where('student_id', $request->student_id)
                    ->first();

                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found.'
                    ], 404);
                }

                $parentId = $student->parent_id;
            }

            $query = DB::table('visitors as v')
                ->join('parent as p', 'v.parent_id', '=', 'p.parent_id')
                ->select(
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

            // Filter by parent (derived from student)
            if ($parentId) {
                $query->where('v.parent_id', $parentId);
            }

            // Academic Year Filter
            if ($request->filled('academic_yr')) {
                $years = explode('-', $request->academic_yr);

                if (count($years) == 2) {
                    // Modify these dates according to your school's academic calendar
                    $startDate = $years[0] . '-04-01';
                    $endDate = $years[1] . '-03-31';

                    $query->whereBetween('v.visit_date', [
                        $startDate,
                        $endDate
                    ]);
                }
            }

            // From Date Filter
            if ($request->filled('from_date')) {
                $query->whereDate('v.visit_date', '>=', $request->from_date);
            }

            // To Date Filter
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
