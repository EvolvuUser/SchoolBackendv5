<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\DailyTodo;
use App\Models\StaffNotice;

class TeacherDashboardController extends Controller
{
    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    public function getReminders(Request $request)
    {
        try {

            /* ---------------- AUTH ---------------- */
            $user = $this->authenticateUser();
            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized user'
                ], 401);
            }

            $teacher_id = $user->reg_id;
            $academic_yr = JWTAuth::getPayload()->get('academic_year');

            /* ---------------- CLASSES ---------------- */
            $classes = DB::table('subject')
                ->select('class_id', 'section_id', 'sm_id')
                ->where('teacher_id', $teacher_id)
                ->where('academic_yr', $academic_yr)
                ->get();

            $incompleteLessonPlansForNextWeek = [];

            $nextWeekStart = Carbon::now()->addWeek()->startOfWeek()->format('Y-m-d');
            $nextWeekEnd   = Carbon::now()->addWeek()->endOfWeek()->format('Y-m-d');

            /* ---------------- LESSON PLANS ---------------- */
            foreach ($classes as $data) {

                $lessonPlans = DB::table('lesson_plan')
                    ->select(
                        'lesson_plan.*',
                        'class.name as class_name',
                        'section.name as section_name',
                        'subject_master.name as subject_name',
                        'chapters.chapter_no',
                        'chapters.name as chapter_name',
                        'chapters.sub_subject'
                    )
                    ->join('class', 'lesson_plan.class_id', '=', 'class.class_id')
                    ->join('section', 'lesson_plan.section_id', '=', 'section.section_id')
                    ->join('subject_master', 'lesson_plan.subject_id', '=', 'subject_master.sm_id')
                    ->join('chapters', 'lesson_plan.chapter_id', '=', 'chapters.chapter_id')
                    ->where('chapters.isDelete', '!=', 'Y')
                    ->where('lesson_plan.reg_id', $teacher_id)
                    ->where('lesson_plan.class_id', $data->class_id)
                    ->where('lesson_plan.section_id', $data->section_id)
                    ->where('lesson_plan.subject_id', $data->sm_id)
                    ->where('lesson_plan.academic_yr', $academic_yr)
                    ->where('lesson_plan.status', 'I')
                    ->whereRaw("
                        STR_TO_DATE(SUBSTRING_INDEX(lesson_plan.week_date, ' / ', 1), '%d-%m-%Y') <= ?
                        AND
                        STR_TO_DATE(SUBSTRING_INDEX(lesson_plan.week_date, ' / ', -1), '%d-%m-%Y') >= ?
                    ", [$nextWeekEnd, $nextWeekStart])
                    ->get();

                if ($lessonPlans->isEmpty()) {
                    continue;
                }

                $key = $data->class_id . '-' . $data->section_id;

                if (!isset($incompleteLessonPlansForNextWeek[$key])) {
                    $incompleteLessonPlansForNextWeek[$key] = [
                        'class_id'     => $data->class_id,
                        'class_name'   => $lessonPlans[0]->class_name,
                        'section_id'   => $data->section_id,
                        'section_name' => $lessonPlans[0]->section_name,
                        'subjects'     => []
                    ];
                }

                $incompleteLessonPlansForNextWeek[$key]['subjects'][] = [
                    'subject_id'   => $data->sm_id,
                    'subject_name' => $lessonPlans[0]->subject_name,
                    'lesson_plans' => $lessonPlans
                ];
            }

            $incompleteLessonPlansForNextWeek = array_values($incompleteLessonPlansForNextWeek);

            /* ---------------- NOTICES ---------------- */
            $todaysDate = Carbon::today()->format('Y-m-d');

            $notices = StaffNotice::select([
                    'staff_notice.subject',
                    'staff_notice.notice_desc',
                    'staff_notice.notice_date',
                    'staff_notice.notice_type',
                    DB::raw('GROUP_CONCAT(t.name) as staff_name')
                ])
                ->join('teacher as t', 't.teacher_id', '=', 'staff_notice.teacher_id')
                ->where('staff_notice.publish', 'Y')
                ->where('staff_notice.teacher_id', $teacher_id)
                ->where('staff_notice.academic_yr', $academic_yr)
                ->whereDate('staff_notice.notice_date', $todaysDate)
                ->groupBy(
                    'staff_notice.subject',
                    'staff_notice.notice_desc',
                    'staff_notice.notice_date',
                    'staff_notice.notice_type'
                )
                ->orderBy('staff_notice.notice_date')
                ->get();

            /* -----------------TODOS--------------------- */
            $todayDate = date('Y-m-d');
            $todos = DailyTodo::where('reg_id', $user->reg_id)
                ->where('login_type', $user->role_id)
                ->where('due_date' , $todayDate)
                ->where('is_completed' , false)
                ->orderBy('created_at', 'desc')
                ->get();

            /* ---------------- RESPONSE ---------------- */
            return response()->json([
                'status'  => true,
                'data'    => [
                    'incomplete_lesson_plan_for_next_week' => $incompleteLessonPlansForNextWeek,
                    'notice_for_teacher'                   => $notices,
                    'todoForToday' => $todos
                ]
            ], 200);

        } catch (\Throwable $e) {

            /* ---------------- LOG ERROR ---------------- */
            // Log::error('getReminders API failed', [
            //     'teacher_id' => $teacher_id ?? null,
            //     'error'      => $e->getMessage(),
            //     'line'       => $e->getLine(),
            //     'file'       => $e->getFile(),
            // ]);

            /* ---------------- SAFE RESPONSE ---------------- */
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function eventsList(Request $request, $teacher_id)
    {
        try {
            // Authenticate user
            $user = $this->authenticateUser();

            // Validate teacher_id
            if (!is_numeric($teacher_id)) {
                return response()->json([
                    'status'  => 422,
                    'success' => false,
                    'message' => 'Invalid teacher id.',
                    'data'    => []
                ], 422);
            }

            $academicYr = JWTAuth::getPayload()->get('academic_year');

            if (!$academicYr) {
                return response()->json([
                    'status'  => 400,
                    'success' => false,
                    'message' => 'Academic year not found. Please logout and login again.',
                    'data'    => []
                ], 400);
            }

            $currentDate = Carbon::now();
            $month = $request->input('month', $currentDate->month);
            $year  = $request->input('year', $currentDate->year);

            // Common conditions
            $commonConditions = function ($query) use ($academicYr, $month, $year) {
                $query->where('events.isDelete', 'N')
                    ->where('events.publish', 'Y')
                    ->where('events.academic_yr', $academicYr)
                    ->whereMonth('events.start_date', $month)
                    ->whereYear('events.start_date', $year);
            };

            // Get classes taught by teacher
            $classesTaught = DB::table('subject')
                ->where('teacher_id', $teacher_id)
                ->where('academic_yr', $academicYr)
                ->distinct()
                ->pluck('class_id')
                ->toArray();

            // Events for teacher login
            $eventsForTeacherLogin = Event::select(
                    'events.unq_id',
                    'events.title',
                    'events.event_desc',
                    'events.class_id',
                    'events.login_type',
                    'events.start_date',
                    'events.start_time',
                    'events.end_date',
                    'events.end_time'
                )
                ->where('events.login_type', 'T')
                ->where($commonConditions)
                ->orderBy('events.start_date')
                ->orderByDesc('events.start_time')
                ->get();

            // Events for classes taught
            $eventsForClasses = Event::select(
                    'events.unq_id',
                    'events.title',
                    'events.event_desc',
                    'events.class_id',
                    'events.login_type',
                    'class.name as class_name',
                    'events.start_date',
                    'events.start_time',
                    'events.end_date',
                    'events.end_time'
                )
                ->leftJoin('class', 'events.class_id', '=', 'class.class_id')
                ->where($commonConditions)
                ->whereIn('events.class_id', $classesTaught)
                ->orderBy('events.start_date')
                ->orderByDesc('events.start_time')
                ->get();

            // Add category to teacher login events
            $eventsForTeacherLogin = $eventsForTeacherLogin->map(function ($event) {
                $event->category = 'teacher_login';
                return $event;
            });

            // Add category to class events
            $eventsForClasses = $eventsForClasses->map(function ($event) {
                $event->category = 'class';
                return $event;
            });

            // dd($eventsForClasses , $eventsForTeacherLogin);

            // Merge both arrays
            $allEvents = $eventsForTeacherLogin
                ->concat($eventsForClasses)
                ->values();

            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => 'Events fetched successfully.',
                'data'    => $allEvents
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => 'Something went wrong while fetching events.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function studentAcademicPerformanceGraphData($teacher_id)
    {
        $user = $this->authenticateUser();
        $reg_id = JWTAuth::getPayload()->get('reg_id');
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        // 1. Classes & sections taught by teacher
        $classes = collect(DB::select("
            SELECT 
                class.class_id,
                class.name AS class_name,
                section.name AS section_name,
                section.section_id
            FROM subject
            LEFT JOIN class ON subject.class_id = class.class_id
            LEFT JOIN section ON subject.section_id = section.section_id
            WHERE subject.teacher_id = ?
            AND subject.academic_yr = ?
            GROUP BY class.class_id, section.section_id
        ", [$user->reg_id, $academic_yr]));

        $class_and_section_ids = [];
        foreach ($classes as $item) {
            $class_and_section_ids[] = [
                'class_id'     => $item->class_id,
                'class_name'   => $item->class_name,
                'section_id'   => $item->section_id,
                'section_name' => $item->section_name,
            ];
        }

        // 2. Subjects + average marks
        $classSectionSubjects = [];

        foreach ($class_and_section_ids as $cs) {

            $subjects = DB::select("
                SELECT 
                    sm.name,
                    sm.sm_id AS subject_id
                FROM subject a
                LEFT JOIN subject_master sm ON a.sm_id = sm.sm_id
                WHERE a.class_id   = ?
                AND a.section_id = ?
                AND a.teacher_id = ?
                AND a.academic_yr = ?
            ", [$cs['class_id'], $cs['section_id'], $reg_id, $academic_yr]);

            foreach ($subjects as $row) {

                // -------- GET STUDENT MARKS (same for all classes) --------
                $student_marks = DB::table(DB::raw("(
                    SELECT b.student_id, a.present, a.total_marks
                    FROM student_marks a
                    JOIN student b ON a.student_id = b.student_id
                    WHERE b.class_id = ?
                    AND b.section_id = ?
                    AND a.subject_id = ?
                    AND a.academic_yr = ?
                    AND b.IsDelete = 'N'
                ) x"))
                ->setBindings([
                    $cs['class_id'],
                    $cs['section_id'],
                    $row->subject_id,
                    $academic_yr
                ])
                ->get();

                // -------- AVERAGE CALCULATION (JSON SAFE) --------
                $totalMarks = 0;
                $totalStudents = 0;

                foreach ($student_marks as $m) {
                    if (!$m->total_marks || !$m->present) {
                        continue;
                    }

                    // total_marks is either JSON or numeric
                    $marksArr = is_string($m->total_marks)
                        ? json_decode($m->total_marks, true)
                        : [$m->total_marks];

                    // present is JSON
                    $presentArr = is_string($m->present)
                        ? json_decode($m->present, true)
                        : ['Y']; // default to 'Y' if numeric total_marks

                    if (!is_array($marksArr) || !is_array($presentArr)) {
                        continue;
                    }

                    foreach ($marksArr as $key => $markVal) {

                        $markVal = trim((string)$markVal);

                        // Use the **same key** to get present, fallback to 'Y'
                        $present = $presentArr[$key] ?? 'Y';

                        if (
                            $present === 'Y' &&
                            $markVal !== '' &&
                            is_numeric($markVal)
                        ) {
                            $totalMarks += (float)$markVal;
                            $totalStudents++;
                        }
                    }
                }


                $averageMarks = $totalStudents > 0
                    ? round($totalMarks / $totalStudents, 2)
                    : 0;

                // echo "TOTAL MARKS: {$totalMarks}, TOTAL STUDENTS: {$totalStudents}\n";

                // -------- FINAL PUSH --------
                $classSectionSubjects[] = [
                    'class_name'    => $cs['class_name'],
                    'class_id'      => $cs['class_id'],
                    'section_id'    => $cs['section_id'],
                    'section_name'  => $cs['section_name'],
                    'subject_name'  => $row->name,
                    'subject_id'    => $row->subject_id,
                    'academic_yr'   => $academic_yr,
                    'average_marks' => $averageMarks,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'performanceData' => $classSectionSubjects
            ]
        ]);
    }

    public function timetableDetails($teacher_id, $timetable_id)
    {
        $user = $this->authenticateUser();
        $acd_yr = JWTAuth::getPayload()->get('academic_year');
        $todayDayOfWeek = date('l'); // Get current day of the week (e.g., 'Monday')
        $teacher_id = JWTAuth::getPayload()->get('reg_id');

        $timetable = DB::select("
                SELECT 
                    d.name AS class, 
                    e.name AS section, 
                    c.name AS subject, 
                    a.period_no, 
                    a.class_id,
                    c.sm_id
                FROM timetable a
                JOIN subject_master c ON SUBSTRING_INDEX(a.$todayDayOfWeek, '^', 1) = c.sm_id
                JOIN class d ON a.class_id = d.class_id
                JOIN section e ON a.section_id = e.section_id
                WHERE SUBSTRING_INDEX(a.$todayDayOfWeek, '^', -1) = ?
                    AND a.academic_yr = ?
                    AND a.t_id = ?
                ORDER BY a.period_no
            ", [$teacher_id, $acd_yr, $timetable_id]);
        

        $classId = $timetable[0]->class_id;
        $subjectId = $timetable[0]->sm_id;

        $lessonPlanTemplate = DB::table('lesson_plan_template')
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('publish', 'Y')
            ->first();
            
        if (!$lessonPlanTemplate) {
            return response()->json([
                'status' => 400,
                'message' => 'Lesson Plan Template is not created!!!',
                'success' => false
            ]);
        }

        $lessonPlanData = DB::select("
            SELECT 
                chapters.name as chapter_name,
                lesson_plan_heading.name AS heading_name,
                lesson_plan_template_details.description AS description
            FROM lesson_plan_template
            LEFT JOIN lesson_plan_template_details
                ON lesson_plan_template.les_pln_temp_id = lesson_plan_template_details.les_pln_temp_id
            LEFT JOIN class 
                ON lesson_plan_template.class_id = class.class_id
            LEFT JOIN lesson_plan_heading 
                ON lesson_plan_template_details.lesson_plan_headings_id = lesson_plan_heading.lesson_plan_headings_id
            LEFT JOIN subject_master 
                ON lesson_plan_template.subject_id = subject_master.sm_id
            LEFT JOIN chapters  
                ON lesson_plan_template.chapter_id = chapters.chapter_id
            WHERE lesson_plan_template.subject_id = ?
            AND lesson_plan_template.class_id = ?
            AND lesson_plan_template.publish = 'Y'
            AND lesson_plan_template.reg_id = ?
        ", [$subjectId, $classId , $teacher_id]);

        $lessonPlanData = collect($lessonPlanData)
        ->groupBy('chapter_name')->toArray();

        /*
        BETTER UI
        $lessonPlanData = collect($lessonPlanData)
            ->groupBy('chapter_name')
            ->map(function ($items) {
                return $items->groupBy('heading_name');
            })->toArray();
        */

        return response()->json([
            'status' => 'success',
            'data' => [
                'lessonPlanData' => $lessonPlanData
            ]
        ]);
    }

    public function getDefaulters(Request $request) {
        $user = $this->authenticateUser();
        $teacher_id = $user->reg_id;
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $classes = DB::table('class_teachers')
            ->join('class', 'class_teachers.class_id', '=', 'class.class_id')
            ->join('section', 'class_teachers.section_id', '=', 'section.section_id')
            ->select(
                'class.name as classname',
                'section.name as sectionname',
                'class_teachers.class_id',
                'class_teachers.section_id',
                DB::raw('1 as is_class_teacher')
            )
            ->where('class_teachers.teacher_id', $teacher_id)
            ->where('class_teachers.academic_yr', $customClaims)
            ->first();

        $class_id = $classes->class_id;
        $class_name = DB::table('class')->select('name')
        ->where('class_id' , $class_id)->first()->name;

        $section_id = $classes->section_id;
        $section_name = DB::table('section')->select('name')
        ->where('section_id' , $section_id)->first()->name;

        $installmentId = $request->input('installment_id');

        $defaulters = DB::table('view_student_fees_category as s')
            ->leftJoin('view_student_fees_payment as p', function ($join) {
                $join->on('s.student_id', '=', 'p.student_id')
                    ->on('s.installment', '=', 'p.installment');
            })
            ->leftJoin('fee_concession_details as c', function ($join) {
                $join->on('s.student_id', '=', 'c.student_id')
                    ->on('s.installment', '=', 'c.installment');
            })
            ->join('student as st', 'st.student_id', '=', 's.student_id')
            ->where('s.academic_yr', $customClaims)
            ->where('s.class_id', $class_id)
            ->where('st.section_id', $section_id)
            ->when($installmentId, function ($q) use ($installmentId) {
                // user-selected installment
                $q->where('s.installment', 'like', $installmentId . '%');
            }, function ($q) {
                // default behavior
                $q->where(function ($qq) {
                    $qq->where('s.installment', 'like', '1%')
                    ->orWhere('s.installment', 'like', '2%')
                    ->orWhere('s.installment', 'like', '3%');
                });
            })
            ->groupBy(
                's.student_id',
                's.installment',
                's.installment_fees',
                'st.first_name',
                'st.last_name',
                'st.roll_no'
            )
            ->select(
                's.student_id',
                'st.first_name',
                'st.last_name',
                'st.roll_no',
                's.installment',
                's.installment_fees',
                DB::raw('COALESCE(SUM(c.amount),0) as concession'),
                DB::raw('COALESCE(SUM(p.fees_paid),0) as paid_amount')
            )
            ->get();

        $defaulterStudents = [];

        foreach ($defaulters as $student) {
            $pending = $student->installment_fees 
                    - $student->concession 
                    - $student->paid_amount;
            if ($pending > 0) {

                $defaulterStudents[] = [
                    'student_id'   => $student->student_id,
                    'name'         => $student->first_name . ' ' . $student->last_name,
                    'roll_no'      => $student->roll_no,
                    'installment' => $student->installment,
                    'pending_fee' => $pending
                ];
            }
        }

        return response()->json(
            [
                'status' => true,
                'class_name' => $class_name,
                'section_name' => $section_name,
                'count' => count($defaulterStudents),
                'students' => $defaulterStudents
            ]
        );
    }

    public function dashboardSummary($teacher_id)
    {
        $user = $this->authenticateUser();
        
        /**
         * STUDENT CARDS
         */
        // get classes data 
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        // 1. Get unique class-section combinations for the teacher
        $classData = DB::table('subject')
            ->leftJoin('class_teachers', function ($join) use ($teacher_id) {
                $join->on('class_teachers.class_id', '=', 'subject.class_id')
                    ->on('class_teachers.section_id', '=', 'subject.section_id')
                    ->where('class_teachers.teacher_id', $teacher_id);
            })
            ->where('subject.academic_yr', $customClaims)
            ->where(function ($query) use ($teacher_id) {
                $query->where('subject.teacher_id', $teacher_id)
                    ->orWhere('class_teachers.teacher_id', $teacher_id);
            })
            ->distinct()
            ->select('subject.class_id', 'subject.section_id')
            ->get();

        // 2. Build arrays for WHERE IN
        $classIds   = $classData->pluck('class_id')->unique();
        $sectionIds = $classData->pluck('section_id')->unique();

        // 3. Count students in ONE query
        $totalStudents = DB::table('student')
            ->where('academic_yr', $customClaims)
            ->whereIn('class_id', $classIds)
            ->whereIn('section_id', $sectionIds)
            ->count();

        // Count total number of students present today in those classes
        $todayDate = date('Y-m-d');
        $totalStudentsPresentToday = DB::table('attendance')
            ->where('only_date', $todayDate)
            ->whereIn('class_id', $classIds)
            ->whereIn('section_id', $sectionIds)
            ->count();

        /**
         * Defaulter list card
         */
        
        $classes = DB::table('class_teachers')
            ->join('class', 'class_teachers.class_id', '=', 'class.class_id')
            ->join('section', 'class_teachers.section_id', '=', 'section.section_id')
            ->select(
                'class.name as classname',
                'section.name as sectionname',
                'class_teachers.class_id',
                'class_teachers.section_id',
                DB::raw('1 as is_class_teacher')
            )
            ->where('class_teachers.teacher_id', $teacher_id)
            ->where('class_teachers.academic_yr', $customClaims)
            ->first();

        $class_id = $classes->class_id;
        $section_id = $classes->section_id;
        $installment = 1; // 1 2 3
        $defaulters = DB::table('view_student_fees_category as s')
            ->leftJoin('view_student_fees_payment as p', function ($join) {
                $join->on('s.student_id', '=', 'p.student_id')
                    ->on('s.installment', '=', 'p.installment');
            })
            ->leftJoin('fee_concession_details as c', function ($join) {
                $join->on('s.student_id', '=', 'c.student_id')
                    ->on('s.installment', '=', 'c.installment');
            })
            ->join('student as st', 'st.student_id', '=', 's.student_id')
            ->where('s.academic_yr', $customClaims)
            ->where('s.class_id', $class_id)
            ->where('st.section_id', $section_id)
            ->where(function ($q) {
                $q->where('s.installment', 'like', '1%')
                ->orWhere('s.installment', 'like', '2%')
                ->orWhere('s.installment', 'like', '3%');
            })
            ->groupBy(
                's.student_id',
                's.installment',
                's.installment_fees',
                'st.first_name',
                'st.last_name',
                'st.roll_no'
            )
            ->select(
                's.student_id',
                'st.first_name',
                'st.last_name',
                'st.roll_no',
                's.installment',
                's.installment_fees',
                DB::raw('COALESCE(SUM(c.amount),0) as concession'),
                DB::raw('COALESCE(SUM(p.fees_paid),0) as paid_amount')
            )
            ->get();

        $pendingAmount = 0.0;
        $totalNumberOfDefaulters = 0;
        $defaulterStudents = [];

        foreach ($defaulters as $student) {
            $pending = $student->installment_fees 
                    - $student->concession 
                    - $student->paid_amount;
            if ($pending > 0) {
                $totalNumberOfDefaulters++;
                $pendingAmount += $pending;

                // $defaulterStudents[] = [
                //     'student_id'   => $student->student_id,
                //     'name'         => $student->first_name . ' ' . $student->last_name,
                //     'roll_no'      => $student->roll_no,
                //     'installment' => $student->installment,
                //     'pending_fee' => $pending
                // ];
            }
        }


        /**
         * Birthday Card
         */

        $countOfBirthdaysToday = DB::table('student')
            ->where('academic_yr', $customClaims)
            ->whereIn('class_id', $classIds)
            ->whereIn('section_id', $sectionIds)
            ->whereRaw("DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')")
            ->count();

        /**
         * Homework Card
         */
        $countOfHomeworksDueToday = 0; // Placeholder for homework due today logic
        $countOfHomeworksDueToday = DB::table('homework')
            ->where('academic_yr', $customClaims)
            ->whereIn('class_id', $classIds)
            ->whereIn('section_id', $sectionIds)
            ->where('end_date', $todayDate)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'studentCard' => [
                    'totalStudents' => $totalStudents,
                    'totalStudentsPresentToday' => $totalStudentsPresentToday
                ],
                'birthDayCard' => [
                    'countOfBirthdaysToday' => $countOfBirthdaysToday
                ],
                'homeworkCard' => [
                    'countOfHomeworksDueToday' => $countOfHomeworksDueToday
                ],
                'defaulterCount' => [
                    'totalPendingAmount' => $pendingAmount,
                    'totalNumberOfDefaulters' => $totalNumberOfDefaulters,
                    // 'defaulterStudents' => $defaulterStudents,
                    // 'count' => count($defaulterStudents)
                ]
            ]
        ]);
    }

    public function ticketsList($teacher_id)
    {
        $user = $this->authenticateUser();
        $reg_id = JWTAuth::getPayload()->get('reg_id');
        $role = $user->role_id;
        // get ticket assigned to the teacher for today  ticket , ticket_comments.appointment_date_time
        $tickets = DB::table('ticket')
            ->select('ticket.*', 'service_type.service_name','student.first_name','student.mid_name','student.last_name' , 'ticket_comments.appointment_date_time')
            ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
            ->join('student', 'student.student_id', '=', 'ticket.student_id')
            ->join('class_teachers', function ($join) {
                $join->on('class_teachers.class_id', '=', 'student.class_id')
                        ->on('class_teachers.section_id', '=', 'student.section_id');
            })
            ->leftJoin('ticket_comments', 'ticket.ticket_id', '=', 'ticket_comments.ticket_id')
            ->where('ticket_comments.appointment_date_time', 'LIKE', date('d-M-Y') . '%')
            ->where('ticket_comments.status' , 'Approved')
            ->where('service_type.role_id', $role)
            ->where('class_teachers.teacher_id', $reg_id)
            // ->where('ticket.raised_on' , '=', date('Y-m-d'))
            ->orderBy('raised_on', 'DESC')
            ->get()
            ->map(function ($ticket) {
                $ticket->description = strip_tags($ticket->description); // Remove HTML tags
                return $ticket;
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'tickets' => $tickets,
            ]
        ]);
    }

    public function timetableForToday($teacher_id)
    {
        $user = $this->authenticateUser();
        $reg_id = JWTAuth::getPayload()->get('reg_id');
        $acd_yr = JWTAuth::getPayload()->get('academic_year');

        $todayDayOfWeek = date('l'); // Get current day of the week (e.g., 'Monday')

        // Fetch timetable entries for the teacher for today
        $timetableEntries = DB::select("
                SELECT 
                    d.name AS class, 
                    e.name AS section, 
                    c.name AS subject, 
                    a.period_no,
                    a.t_id,
                    a.time_in,
                    a.time_out
                FROM timetable a
                JOIN subject_master c ON SUBSTRING_INDEX(a.$todayDayOfWeek, '^', 1) = c.sm_id
                JOIN class d ON a.class_id = d.class_id
                JOIN section e ON a.section_id = e.section_id
                WHERE SUBSTRING_INDEX(a.$todayDayOfWeek, '^', -1) = ?
                    AND a.academic_yr = ?
                ORDER BY a.period_no
            ", [$teacher_id, $acd_yr]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'timetable' => $timetableEntries,
            ]
        ]);
    }
}
