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
                    'status' => false,
                    'message' => 'Unauthorized user'
                ], 401);
            }

            $teacher_id = $user->reg_id;
            $academic_yr = JWTAuth::getPayload()->get('academic_year');

            /* ---------------- CLASSES ---------------- */
            $classes = DB::table('subject')
                ->select('subject.class_id', 'subject.section_id', 'subject.sm_id', 'class.name as class_name', 'section.name as section_name',
                    'subject_master.name as subject_name')
                ->leftJoin('class', 'class.class_id', '=', 'subject.class_id')
                ->leftJoin('section', 'section.section_id', '=', 'subject.section_id')
                ->leftJoin('subject_master', 'subject_master.sm_id', '=', 'subject.sm_id')
                ->where('subject.teacher_id', $teacher_id)
                ->where('subject.academic_yr', $academic_yr)
                ->get();

            $incompleteLessonPlansForNextWeek = [];

            $nextMonday = now()->next('Monday')->format('d-m-Y');

            $incompleteLessonPlan = DB::table('subject as s')
                ->selectRaw("
                    GROUP_CONCAT(CONCAT(' ', c.name, ' ', sc.name, ' ', sm.name)) AS pending_classes,
                    s.teacher_id,
                    t.name,
                    t.phone
                ")
                ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
                ->join('class as c', 's.class_id', '=', 'c.class_id')
                ->join('section as sc', 's.section_id', '=', 'sc.section_id')
                ->join('subject_master as sm', 's.sm_id', '=', 'sm.sm_id')
                ->where('t.isDelete', 'N')
                ->where('s.academic_yr', $academic_yr)
                ->where('s.teacher_id', $teacher_id)
                ->whereNotIn(
                    DB::raw('CONCAT(s.class_id, s.section_id, s.sm_id, s.teacher_id)'),
                    function ($query) use ($nextMonday) {
                        $query
                            ->select(
                                DB::raw('CONCAT(class_id, section_id, subject_id, reg_id)')
                            )
                            ->from('lesson_plan')
                            // ->where('lesson_plan.status' , '!=', 'C')
                            ->whereRaw(
                                "SUBSTRING_INDEX(week_date, ' /', 1) = ?",
                                [$nextMonday]
                            );
                    }
                )
                ->whereNotIn('s.sm_id', function ($query) {
                    $query
                        ->select('sm_id')
                        ->from('subjects_excluded_from_curriculum');
                })
                ->groupBy('s.teacher_id')
                ->get();

            $incompleteLessonPlansForNextWeek = $incompleteLessonPlan;

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
            $today = Carbon::today();

            $todos = DailyTodo::where('reg_id', $user->reg_id)
                ->where('login_type', $user->role_id)
                ->whereDate('due_date', $today)
                ->where('is_completed', 0)  // 🔥 ONLY pending
                ->orderBy('created_at', 'desc')
                ->get();

            /* ------------- Attendance Reminder -------------------- */
            $classTeacher = DB::table('class_teachers')
                ->join('class', 'class_teachers.class_id', '=', 'class.class_id')
                ->join('section', 'class_teachers.section_id', '=', 'section.section_id')
                ->select(
                    'class.name as classname',
                    'section.name as sectionname',
                    'class_teachers.class_id',
                    'class_teachers.section_id'
                )
                ->where('class_teachers.teacher_id', $teacher_id)
                ->where('class_teachers.academic_yr', $academic_yr)
                ->orderBy('class_teachers.section_id')
                ->first();

            $isClassTeacher = $classTeacher !== null;

            $classTeacherClassId = $classTeacher->class_id ?? null;
            $classTeacherSectionId = $classTeacher->section_id ?? null;

            $isAttendanceMarked = false;

            $today = now()->toDateString();

            if ($isClassTeacher) {
                $isAttendanceMarked = DB::table('attendance')
                    ->where('class_id', $classTeacherClassId)
                    ->where('section_id', $classTeacherSectionId)
                    ->where('only_date', $today)
                    ->exists();
            }

            /* ---------------- RESPONSE ---------------- */
            return response()->json([
                'status' => true,
                'data' => [
                    'todoForToday' => $todos,
                    'notice_for_teacher' => $notices,
                    'incomplete_lesson_plan_for_next_week' => $incompleteLessonPlansForNextWeek,
                    'isAttendanceMarked' => $isAttendanceMarked,
                    'isClassTeacher' => $isClassTeacher,
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
            // dd($e);
            return response()->json([
                'status' => false,
                'message' => 'Integernal Server Error',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
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
                    'status' => 422,
                    'success' => false,
                    'message' => 'Invalid teacher id.',
                    'data' => []
                ], 422);
            }

            $academicYr = JWTAuth::getPayload()->get('academic_year');

            if (!$academicYr) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => 'Academic year not found. Please logout and login again.',
                    'data' => []
                ], 400);
            }

            $currentDate = Carbon::now();
            $month = $request->input('month', $currentDate->month);
            $year = $request->input('year', $currentDate->year);

            // Common conditions
            $commonConditions = function ($query) use ($academicYr, $month, $year) {
                $query
                    ->where('events.isDelete', 'N')
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
                'events.end_time',
                'class.name as class_name',
            )
                ->where('events.login_type', 'T')
                ->where($commonConditions)
                ->orderBy('events.start_date')
                ->orderByDesc('events.start_time')
                ->leftJoin('class', 'events.class_id', '=', 'class.class_id')
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
                'status' => 200,
                'success' => true,
                'message' => 'Events fetched successfully.',
                'data' => $allEvents
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Something went wrong while fetching events.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function studentAcademicPerformanceGraphData($teacher_id)
    // {
    //     $user = $this->authenticateUser();
    //     $reg_id = JWTAuth::getPayload()->get('reg_id');
    //     $academic_yr = JWTAuth::getPayload()->get('academic_year');

    //     // $academic_yr = "2024-2025";

    //     // 1. Classes & sections taught by teacher
    //     $classes = collect(DB::select("
    //         SELECT
    //             class.class_id,
    //             class.name AS class_name,
    //             section.name AS section_name,
    //             section.section_id
    //         FROM subject
    //         LEFT JOIN class ON subject.class_id = class.class_id
    //         LEFT JOIN section ON subject.section_id = section.section_id
    //         WHERE subject.teacher_id = ?
    //         AND subject.academic_yr = ?
    //         GROUP BY class.class_id, section.section_id
    //     ", [$user->reg_id, $academic_yr]));

    //     $class_and_section_ids = [];
    //     foreach ($classes as $item) {
    //         $class_and_section_ids[] = [
    //             'class_id'     => $item->class_id,
    //             'class_name'   => $item->class_name,
    //             'section_id'   => $item->section_id,
    //             'section_name' => $item->section_name,
    //         ];
    //     }

    //     // 2. Subjects + average marks
    //     $classSectionSubjects = [];

    //     foreach ($class_and_section_ids as $cs) {

    //         $subjects = DB::select("
    //             SELECT
    //                 sm.name,
    //                 sm.sm_id AS subject_id
    //             FROM subject a
    //             LEFT JOIN subject_master sm ON a.sm_id = sm.sm_id
    //             WHERE a.class_id   = ?
    //             AND a.section_id = ?
    //             AND a.teacher_id = ?
    //             AND a.academic_yr = ?
    //         ", [$cs['class_id'], $cs['section_id'], $reg_id, $academic_yr]);

    //         foreach ($subjects as $row) {

    //             // -------- GET STUDENT MARKS (same for all classes) --------
    //             $student_marks = DB::table(DB::raw("(
    //                 SELECT b.student_id, a.present, a.total_marks
    //                 FROM student_marks a
    //                 JOIN student b ON a.student_id = b.student_id
    //                 WHERE b.class_id = ?
    //                 AND b.section_id = ?
    //                 AND a.subject_id = ?
    //                 AND a.academic_yr = ?
    //                 AND b.IsDelete = 'N'
    //             ) x"))
    //             ->setBindings([
    //                 $cs['class_id'],
    //                 $cs['section_id'],
    //                 $row->subject_id,
    //                 $academic_yr
    //             ])
    //             ->get();

    //             // -------- AVERAGE CALCULATION (JSON SAFE) --------
    //             $totalMarks = 0;
    //             $totalStudents = 0;

    //             foreach ($student_marks as $m) {
    //                 if (!$m->total_marks || !$m->present) {
    //                     continue;
    //                 }

    //                 // total_marks is either JSON or numeric
    //                 $marksArr = is_string($m->total_marks)
    //                     ? json_decode($m->total_marks, true)
    //                     : [$m->total_marks];

    //                 // present is JSON
    //                 $presentArr = is_string($m->present)
    //                     ? json_decode($m->present, true)
    //                     : ['Y']; // default to 'Y' if numeric total_marks

    //                 if (!is_array($marksArr) || !is_array($presentArr)) {
    //                     continue;
    //                 }

    //                 foreach ($marksArr as $key => $markVal) {

    //                     $markVal = trim((string)$markVal);

    //                     // Use the **same key** to get present, fallback to 'Y'
    //                     $present = $presentArr[$key] ?? 'Y';

    //                     if (
    //                         $present === 'Y' &&
    //                         $markVal !== '' &&
    //                         is_numeric($markVal)
    //                     ) {
    //                         $totalMarks += (float)$markVal;
    //                         $totalStudents++;
    //                     }
    //                 }
    //             }

    //             $averageMarks = $totalStudents > 0
    //                 ? round($totalMarks / $totalStudents, 2)
    //                 : 0;

    //             // echo "TOTAL MARKS: {$totalMarks}, TOTAL STUDENTS: {$totalStudents}\n";

    //             // -------- FINAL PUSH --------
    //             $classSectionSubjects[] = [
    //                 'class_name'    => $cs['class_name'],
    //                 'class_id'      => $cs['class_id'],
    //                 'section_id'    => $cs['section_id'],
    //                 'section_name'  => $cs['section_name'],
    //                 'subject_name'  => $row->name,
    //                 'subject_id'    => $row->subject_id,
    //                 'academic_yr'   => $academic_yr,
    //                 'average_marks' => $averageMarks,
    //             ];
    //         }
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'performanceData' => $classSectionSubjects
    //         ]
    //     ]);
    // }

    public function studentAcademicPerformanceGraphData(Request $request, $teacher_id)
    {
        $user = $this->authenticateUser();
        $reg_id = JWTAuth::getPayload()->get('reg_id');
        $teacher_id = $reg_id;
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id = $request->query('class_id');
        $section_id = $request->query('section_id');

        // 2. Subjects + average marks
        $classSectionSubjects = [];

        $subjects = DB::table('subject as a')
            ->distinct()
            ->select(
                'c.sub_rc_master_id as subject_id',
                'c.name as name'
            )
            ->join('sub_subreportcard_mapping as b', 'a.sm_id', '=', 'b.sm_id')
            ->join('subjects_on_report_card_master as c', 'b.sub_rc_master_id', '=', 'c.sub_rc_master_id')
            ->join('subjects_on_report_card as d', 'd.sub_rc_master_id', '=', 'c.sub_rc_master_id')
            ->where('a.class_id', $class_id)
            ->where('a.section_id', $section_id)
            ->where('d.class_id', $class_id)
            ->where('a.teacher_id', $teacher_id)
            ->where('a.academic_yr', $academic_yr)
            ->orderBy('a.section_id', 'asc')
            ->orderBy('c.sequence', 'asc')
            ->get()
            ->toArray();

        foreach ($subjects as $row) {
            // -------- GET STUDENT MARKS (same for all classes) --------
            $student_marks = DB::table('student_marks as a')
                ->join('student as b', 'a.student_id', '=', 'b.student_id')
                ->select(
                    DB::raw('COUNT(b.student_id) as student_count'),
                    DB::raw('SUM(a.total_marks) as total_marks_sum'),
                    DB::raw('SUM(a.highest_total_marks) as total_highest')
                )
                ->where('b.class_id', $class_id)
                ->where('b.section_id', $section_id)
                ->where('a.subject_id', $row->subject_id)
                ->where('a.academic_yr', $academic_yr)
                ->where('a.publish', 'Y')
                ->first();

            // -------- CALCULATION --------
            $totalMarksSum = $student_marks->total_marks_sum ?? 0;
            $totalMarksTotal = $student_marks->total_highest ?? 0;
            $studentCount = $student_marks->student_count ?? 0;

            if (!$totalMarksSum && !$totalMarksTotal) {
                // -------- FINAL PUSH --------
                $classSectionSubjects[] = [
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'subject_name' => $row->name,
                    'subject_id' => $row->subject_id,
                    'academic_yr' => $academic_yr,
                    'studentCount' => null,
                    'average_percentage' => null,
                    'totalMarksStudentGot' => null,
                    'outOfTotalMarks' => null,
                ];
                continue;
            }

            // avoid division by zero
            $averagePercentage = ($totalMarksTotal > 0)
                ? round(($totalMarksSum / $totalMarksTotal) * 100, 2)
                : 0;

            // -------- FINAL PUSH --------
            $classSectionSubjects[] = [
                'class_id' => $class_id,
                'section_id' => $section_id,
                'subject_name' => $row->name,
                'subject_id' => $row->subject_id,
                'academic_yr' => $academic_yr,
                'studentCount' => $studentCount,
                'average_percentage' => $averagePercentage,  // updated field
                'totalMarksStudentGot' => $totalMarksSum,
                'outOfTotalMarks' => $totalMarksTotal,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'performanceData' => $classSectionSubjects
            ],
            'ayr' => $academic_yr
        ]);
    }

    /**
     * Get timetable details along with current week lesson plan data for a teacher.
     *
     * Logic overview:
     * 1. Authenticate user and fetch academic year from JWT.
     * 2. Identify current day of the week (Monday, Tuesday, etc.).
     * 3. Fetch today's timetable for the logged-in teacher.
     * 4. From timetable, derive class_id and subject_id.
     * 5. Calculate current week start and end dates.
     * 6. Check if a lesson plan exists for the same class, subject, academic year,
     *    and falls within the current week.
     * 7. Fetch lesson plan details using Query Builder (DB::table),
     *    group them by chapter, and return the response.
     *
     * @param int $teacher_id      (Ignored, taken from JWT for security)
     * @param int $timetable_id    Timetable identifier
     * @return \Illuminate\Http\JsonResponse
     */
    public function timetableDetails($teacher_id, $timetable_id)
    {
        try {
            /* ------------------------- AUTH & CONTEXT ------------------------- */
            $user = $this->authenticateUser();
            $acd_yr = JWTAuth::getPayload()->get('academic_year');
            $teacher_id = JWTAuth::getPayload()->get('reg_id');

            $todayDayOfWeek = date('l');  // e.g. Monday, Tuesday
            $currentDate = date('Y-m-d');

            /* ------------------------- TIMETABLE DATA -------------------------- */
            $timetable = DB::select("
                SELECT 
                    d.name AS class,
                    e.name AS section,
                    c.name AS subject,
                    a.period_no,
                    a.class_id,
                    c.sm_id
                FROM timetable a
                JOIN subject_master c 
                    ON SUBSTRING_INDEX(a.$todayDayOfWeek, '^', 1) = c.sm_id
                JOIN class d 
                    ON a.class_id = d.class_id
                JOIN section e 
                    ON a.section_id = e.section_id
                WHERE SUBSTRING_INDEX(a.$todayDayOfWeek, '^', -1) = ?
                AND a.academic_yr = ?
                AND a.t_id = ?
                ORDER BY a.period_no
            ", [$teacher_id, $acd_yr, $timetable_id]);

            if (empty($timetable)) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'No timetable found for today.'
                ], 404);
            }

            $classId = $timetable[0]->class_id;
            $subjectId = $timetable[0]->sm_id;

            /* ---------------- LESSON PLAN EXISTENCE CHECK ---------------- */
            $lessonPlanTemplate = DB::table('lesson_plan')
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('academic_yr', $acd_yr)
                ->whereRaw("
                    ? BETWEEN
                    STR_TO_DATE(TRIM(SUBSTRING_INDEX(week_date, '/', 1)), '%d-%m-%Y')
                    AND
                    STR_TO_DATE(TRIM(SUBSTRING_INDEX(week_date, '/', -1)), '%d-%m-%Y')
                ", [$currentDate])
                ->first();

            if (!$lessonPlanTemplate) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'Lesson Plan is not created for the current week.'
                ], 404);
            }

            /* ---------------- LESSON PLAN DETAILS QUERY ---------------- */
            $lessonPlanData = DB::table('lesson_plan')
                ->leftJoin(
                    'lesson_plan_details',
                    'lesson_plan.lesson_plan_id',
                    '=',
                    'lesson_plan_details.lesson_plan_id'
                )
                ->leftJoin(
                    'lesson_plan_heading',
                    'lesson_plan_details.lesson_plan_headings_id',
                    '=',
                    'lesson_plan_heading.lesson_plan_headings_id'
                )
                ->leftJoin(
                    'chapters',
                    'lesson_plan.chapter_id',
                    '=',
                    'chapters.chapter_id'
                )
                ->where('lesson_plan.subject_id', $subjectId)
                ->where('lesson_plan.class_id', $classId)
                ->where('lesson_plan.reg_id', $teacher_id)
                ->where('lesson_plan.academic_yr', $acd_yr)
                ->whereRaw("
                    ? BETWEEN
                    STR_TO_DATE(TRIM(SUBSTRING_INDEX(lesson_plan.week_date, '/', 1)), '%d-%m-%Y')
                    AND
                    STR_TO_DATE(TRIM(SUBSTRING_INDEX(lesson_plan.week_date, '/', -1)), '%d-%m-%Y')
                ", [$currentDate])
                ->select(
                    'lesson_plan.lesson_plan_id',
                    'lesson_plan_heading.lesson_plan_headings_id as heading_id',
                    'lesson_plan_heading.change_daily',
                    'lesson_plan_details.start_date',
                    'chapters.name as chapter_name',
                    'lesson_plan_heading.name as heading_name',
                    'lesson_plan_details.description as description',
                    'lesson_plan.week_date',
                    'chapters.chapter_no'
                )
                ->get();

            if ($lessonPlanData->isEmpty()) {
                return response()->json([
                    'status' => 204,
                    'success' => true,
                    'message' => 'No lesson plan details found for the current week.',
                    'data' => []
                ], 204);
            }

            $lessonPlanData = $lessonPlanData
                ->groupBy('chapter_name')
                ->toArray();

            /* ----------------------------- RESPONSE ----------------------------- */
            return response()->json([
                'status' => 200,
                'success' => true,
                'data' => [
                    'lessonPlanData' => $lessonPlanData
                ]
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // Database related errors
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Generic/unexpected errors
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Something went wrong while fetching timetable details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDefaulters(Request $request)
    {
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

        if ($classes == null) {
            return response()->json([
                'status' => false,
                'message' => 'No data found for this teacher',
            ], 404);
        }

        $class_id = $classes->class_id;
        $class_name = DB::table('class')
            ->select('name')
            ->where('class_id', $class_id)
            ->first()
            ->name;

        $section_id = $classes->section_id;
        $section_name = DB::table('section')
            ->select('name')
            ->where('section_id', $section_id)
            ->first()
            ->name;

        $installmentId = $request->input('installment_id');

        $defaulters = DB::table('view_student_fees_category as s')
            ->leftJoin('view_student_fees_payment as p', function ($join) {
                $join
                    ->on('s.student_id', '=', 'p.student_id')
                    ->on('s.installment', '=', 'p.installment');
            })
            ->leftJoin('fee_concession_details as c', function ($join) {
                $join
                    ->on('s.student_id', '=', 'c.student_id')
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
                    $qq
                        ->where('s.installment', 'like', '1%')
                        ->orWhere('s.installment', 'like', '2%')
                        ->orWhere('s.installment', 'like', '3%');
                });
            })
            ->groupBy(
                's.student_id',
                's.installment',
                's.installment_fees',
                'st.first_name',
                'st.mid_name',
                'st.last_name',
                'st.roll_no'
            )
            ->select(
                's.student_id',
                'st.first_name',
                'st.mid_name',
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
                    'student_id' => $student->student_id,
                    'first_name' => $student->first_name,
                    'mid_name' => $student->mid_name,
                    'last_name' => $student->last_name,
                    'roll_no' => $student->roll_no,
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

        /** STUDENT CARDS */
        // get classes data
        $customClaims = JWTAuth::getPayload()->get('academic_year');

        // 1. Get unique class-section combinations for the teacher
        $classData = DB::table('subject')
            ->leftJoin('class_teachers', function ($join) use ($teacher_id) {
                $join
                    ->on('class_teachers.class_id', '=', 'subject.class_id')
                    ->on('class_teachers.section_id', '=', 'subject.section_id')
                    ->where('class_teachers.teacher_id', $teacher_id);
            })
            ->where('subject.academic_yr', $customClaims)
            ->where(function ($query) use ($teacher_id) {
                $query
                    ->where('subject.teacher_id', $teacher_id)
                    ->orWhere('class_teachers.teacher_id', $teacher_id);
            })
            ->distinct()
            ->select('subject.class_id', 'subject.section_id')
            ->get();

        // 2. Build arrays for WHERE IN
        $classIds = $classData->pluck('class_id')->unique();
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
            ->where('attendance_status', 0)
            ->whereIn('class_id', $classIds)
            ->whereIn('section_id', $sectionIds)
            ->count();

        /** Defaulter list card */
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

        $class_id = $classes->class_id ?? 0;
        $section_id = $classes->section_id ?? 0;
        $installment = 1;  // 1 2 3
        $defaulters = DB::table('view_student_fees_category as s')
            ->leftJoin('view_student_fees_payment as p', function ($join) {
                $join
                    ->on('s.student_id', '=', 'p.student_id')
                    ->on('s.installment', '=', 'p.installment');
            })
            ->leftJoin('fee_concession_details as c', function ($join) {
                $join
                    ->on('s.student_id', '=', 'c.student_id')
                    ->on('s.installment', '=', 'c.installment');
            })
            ->join('student as st', 'st.student_id', '=', 's.student_id')
            ->where('s.academic_yr', $customClaims)
            ->where('s.class_id', $class_id)
            ->where('st.section_id', $section_id)
            ->where(function ($q) {
                $q
                    ->where('s.installment', 'like', '1%')
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

        /** Birthday Card */
        $date = Carbon::now();
        $studentCount = DB::table('student')
            ->where('academic_yr', $customClaims)
            ->whereIn('class_id', $classIds)
            ->whereIn('section_id', $sectionIds)
            ->whereMonth('dob', $date->month)
            ->whereDay('dob', $date->day)
            ->count();

        $countOfBirthdaysToday = $studentCount + Teacher::where('IsDelete', 'N')
            ->whereMonth('birthday', $date->month)
            ->whereDay('birthday', $date->day)
            ->count();

        /** Homework Card */
        $today = Carbon::now()->toDateString();
        $countOfHomeworksDueToday = 0;  // Placeholder for homework due today logic
        $countOfHomeworksDueToday = DB::table('homework')
            ->leftJoin('homework_comments', 'homework.homework_id', '=', 'homework_comments.homework_id')
            ->where('homework.academic_yr', $customClaims)
            ->where('homework.publish', 'Y')
            ->whereIn('homework.class_id', $classIds)
            ->whereIn('homework.section_id', $sectionIds)
            ->where('homework.end_date', $today)
            ->whereIn('homework_comments.homework_status', ['Assigned', 'Partial'])
            ->count();

        /** Substitution Count */
        $substituteCount = DB::table('class_teacher_substitute')
            ->whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->where('academic_yr', $customClaims)
            ->where('teacher_id', $teacher_id)
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
                ],
                'substituteCount' => $substituteCount,
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
            ->select('ticket.*', 'service_type.service_name', 'student.first_name', 'student.mid_name', 'student.last_name', 'ticket_comments.appointment_date_time')
            ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
            ->join('student', 'student.student_id', '=', 'ticket.student_id')
            ->join('class_teachers', function ($join) {
                $join
                    ->on('class_teachers.class_id', '=', 'student.class_id')
                    ->on('class_teachers.section_id', '=', 'student.section_id');
            })
            ->leftJoin('ticket_comments', 'ticket.ticket_id', '=', 'ticket_comments.ticket_id')
            ->where('ticket_comments.appointment_date_time', 'LIKE', date('d-M-Y') . '%')
            ->where('ticket_comments.status', 'Approved')
            ->where('service_type.role_id', $role)
            ->where('class_teachers.teacher_id', $reg_id)
            // ->where('ticket.raised_on' , '=', date('Y-m-d'))
            ->orderBy('raised_on', 'DESC')
            ->get()
            ->map(function ($ticket) {
                $ticket->description = strip_tags($ticket->description);  // Remove HTML tags
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

        $todayDayOfWeek = date('l');  // Get current day of the week (e.g., 'Monday')

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

    // Dev Name - Manish Kumar Sharma
    public function getPendingBookForReturn(Request $request)
    {
        $user = $this->authenticateUser();
        $reg_id = JWTAuth::getPayload()->get('reg_id');
        $pending_books = DB::table('issue_return')
            ->join('book', 'book.book_id', '=', 'issue_return.book_id')
            ->where('member_id', $reg_id)
            ->where('member_type', 'T')
            ->where('return_date', '0000-00-00')
            ->get();
        return response()->json([
            'status' => 200,
            'message' => 'Books pending for return',
            'data' => $pending_books,
            'success' => true
        ]);
    }

    public function getTeacherTaughtClassExam(Request $request)
    {
        $user = $this->authenticateUser();

        $reg_id = JWTAuth::getPayload()->get('reg_id');
        $acd_yr = JWTAuth::getPayload()->get('academic_year');

        /** 1️⃣ Get classes teacher teaches (section used only for permission) */
        $classMappings = DB::table('subject')
            ->where('teacher_id', $reg_id)
            ->where('academic_yr', $acd_yr)
            ->select('class_id')
            ->distinct()
            ->get();

        if ($classMappings->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No classes assigned to this teacher',
                'data' => []
            ]);
        }

        $classIds = $classMappings->pluck('class_id');

        /** 2️⃣ Fetch class-level exam timetable */
        $rows = DB::table('exam_timetable_details as etd')
            ->join('exam_timetable as et', 'et.exam_tt_id', '=', 'etd.exam_tt_id')
            ->join('exam as e', 'e.exam_id', '=', 'et.exam_id')
            ->join('class as c', 'c.class_id', '=', 'et.class_id')
            ->whereIn('et.class_id', $classIds)
            ->where('et.publish', 'Y')
            ->where('et.academic_yr', $acd_yr)
            ->orderBy('etd.date')
            ->select(
                'c.class_id',
                'c.name as classname',
                'etd.date',
                'e.name as exam_name',
                'etd.subject_rc_id',
                'etd.study_leave'
            )
            ->get();

        /** 3️⃣ Load subject master once */
        $subjectMaster = DB::table('subject_master')
            ->select('sm_id', 'name')
            ->get()
            ->keyBy('sm_id');
        $teacherSubjectIds = DB::table('subject')
            ->where('teacher_id', $reg_id)
            ->where('academic_yr', $acd_yr)
            ->pluck('sm_id')  // 👈 IMPORTANT: subject_id column
            ->map(fn($id) => (string) $id)  // cast to string for CSV match
            ->toArray();

        $teacherSections = DB::table('subject as sub')
            ->join('section as sec', 'sec.section_id', '=', 'sub.section_id')
            ->where('sub.teacher_id', $reg_id)
            ->where('sub.academic_yr', $acd_yr)
            ->select(
                'sub.class_id',
                'sec.section_id',
                'sec.name as section_name'
            )
            ->distinct()
            ->get()
            ->groupBy('class_id');
        /** 4️⃣ Group by class & expand subjects */
        $result = $rows
            ->groupBy(fn($row) => $row->class_id)
            ->map(function ($classItems) use ($subjectMaster, $teacherSubjectIds, $teacherSections) {
                $firstClass = $classItems->first();

                return [
                    'class_id' => $firstClass->class_id,
                    'class_name' => $firstClass->classname,
                    // ✅ ADD SECTION INFO HERE
                    'sections' => ($teacherSections[$firstClass->class_id] ?? collect())
                        ->map(fn($sec) => [
                            'section_id' => $sec->section_id,
                            'section_name' => $sec->section_name
                        ])
                        ->values(),
                    'exams' => $classItems
                        ->groupBy(fn($row) => $row->exam_name)
                        ->map(function ($examItems, $examName) use ($subjectMaster, $teacherSubjectIds) {
                            return [
                                'exam_name' => $examName,
                                'timetable' => $examItems
                                    ->map(function ($row) use ($subjectMaster, $teacherSubjectIds) {
                                        $subjects = [];

                                        if (!empty($row->subject_rc_id)) {
                                            foreach (explode(',', $row->subject_rc_id) as $sid) {
                                                $sid = trim($sid);

                                                if (
                                                    in_array($sid, $teacherSubjectIds, true) &&
                                                    isset($subjectMaster[$sid])
                                                ) {
                                                    $subjects[] = [
                                                        'subject_id' => (int) $sid,
                                                        'subject_name' => $subjectMaster[$sid]->name
                                                    ];
                                                }
                                            }
                                        }

                                        return [
                                            'date' => $row->date,
                                            'study_leave' => $row->study_leave,
                                            'subjects' => $subjects
                                        ];
                                    })
                                    ->filter(fn($item) => count($item['subjects']) > 0 || $item['study_leave'] === 'Y')
                                    ->values()
                            ];
                        })
                        ->values()
                ];
            })
            ->values();

        return response()->json([
            'status' => 200,
            'message' => 'Teacher class exam timetable fetched successfully',
            'data' => $result,
            'success' => true
        ]);
    }

    public function getTeacherMobileDashboard(Request $request)
    {
        $user = $this->authenticateUser();
        $nextMonday = now()->next('Monday')->format('d-m-Y');
        /** STUDENT CARDS */
        // get classes data
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $teacher_id = JWTAuth::getPayload()->get('reg_id');
        // 1. Get unique class-section combinations for the teacher
        $classData = DB::table('subject')
            ->leftJoin('class_teachers', function ($join) use ($teacher_id) {
                $join
                    ->on('class_teachers.class_id', '=', 'subject.class_id')
                    ->on('class_teachers.section_id', '=', 'subject.section_id')
                    ->where('class_teachers.teacher_id', $teacher_id);
            })
            ->where('subject.academic_yr', $customClaims)
            ->where(function ($query) use ($teacher_id) {
                $query
                    ->where('subject.teacher_id', $teacher_id)
                    ->orWhere('class_teachers.teacher_id', $teacher_id);
            })
            ->distinct()
            ->select('subject.class_id', 'subject.section_id')
            ->get();

        // 2. Build arrays for WHERE IN
        $classIds = $classData->pluck('class_id')->unique();
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
            ->where('attendance_status', 0)
            ->whereIn('class_id', $classIds)
            ->whereIn('section_id', $sectionIds)
            ->count();

        /** Defaulter list card */
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

        $class_id = $classes->class_id ?? 0;
        $section_id = $classes->section_id ?? 0;
        $installment = 1;  // 1 2 3
        $defaulters = DB::table('view_student_fees_category as s')
            ->leftJoin('view_student_fees_payment as p', function ($join) {
                $join
                    ->on('s.student_id', '=', 'p.student_id')
                    ->on('s.installment', '=', 'p.installment');
            })
            ->leftJoin('fee_concession_details as c', function ($join) {
                $join
                    ->on('s.student_id', '=', 'c.student_id')
                    ->on('s.installment', '=', 'c.installment');
            })
            ->join('student as st', 'st.student_id', '=', 's.student_id')
            ->where('s.academic_yr', $customClaims)
            ->where('s.class_id', $class_id)
            ->where('st.section_id', $section_id)
            ->where(function ($q) {
                $q
                    ->where('s.installment', 'like', '1%')
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

        /** Birthday Card */
        $date = Carbon::now();
        $studentCount = DB::table('student')
            ->where('academic_yr', $customClaims)
            ->whereIn('class_id', $classIds)
            ->whereIn('section_id', $sectionIds)
            ->whereMonth('dob', $date->month)
            ->whereDay('dob', $date->day)
            ->count();

        $countOfBirthdaysToday = $studentCount + Teacher::where('IsDelete', 'N')
            ->whereMonth('birthday', $date->month)
            ->whereDay('birthday', $date->day)
            ->count();

        /** Homework Card */
        $today = Carbon::now()->toDateString();
        $twoDaysLater = Carbon::today()->addDays(2);
        $countOfHomeworksDueToday = 0;  // Placeholder for homework due today logic
        $countOfHomeworksDueToday = DB::table('homework')
            ->leftJoin('homework_comments', 'homework.homework_id', '=', 'homework_comments.homework_id')
            ->where('homework.academic_yr', $customClaims)
            ->where('homework.publish', 'Y')
            ->whereIn('homework.class_id', $classIds)
            ->whereIn('homework.section_id', $sectionIds)
            ->where('homework.end_date', $today)
            ->whereIn('homework_comments.homework_status', ['Assigned', 'Partial'])
            ->count();
        $pending_books = DB::table('issue_return')
            ->join('book', 'book.book_id', '=', 'issue_return.book_id')
            ->where('member_id', $teacher_id)
            ->where('member_type', 'T')
            ->where('return_date', '0000-00-00')
            ->whereDate('due_date', '<=', $twoDaysLater)
            ->count();
        $notCreatedCount = DB::table('subject as s')
            ->selectRaw("
                    GROUP_CONCAT(CONCAT(' ', c.name, ' ', sc.name, ' ', sm.name)) AS pending_classes,
                    s.teacher_id,
                    t.name,
                    t.phone
                ")
            ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
            ->join('class as c', 's.class_id', '=', 'c.class_id')
            ->join('section as sc', 's.section_id', '=', 'sc.section_id')
            ->join('subject_master as sm', 's.sm_id', '=', 'sm.sm_id')
            ->where('t.isDelete', 'N')
            ->where('s.academic_yr', $customClaims)
            ->where('s.teacher_id', $teacher_id)
            ->whereNotIn(
                DB::raw('CONCAT(s.class_id, s.section_id, s.sm_id, s.teacher_id)'),
                function ($query) use ($nextMonday) {
                    $query
                        ->select(
                            DB::raw('CONCAT(class_id, section_id, subject_id, reg_id)')
                        )
                        ->from('lesson_plan')
                        ->whereRaw(
                            "SUBSTRING_INDEX(week_date, ' /', 1) = ?",
                            [$nextMonday]
                        );
                }
            )
            ->whereNotIn('s.sm_id', function ($query) {
                $query
                    ->select('sm_id')
                    ->from('subjects_excluded_from_curriculum');
            })
            ->groupBy('s.teacher_id')
            ->get();
        $isLessonPlanPriorityDay = now()->isSaturday() || now()->isMonday();
        $lessonPlanCount = $notCreatedCount[0]->pending_classes
            ? count(array_filter(array_map('trim', explode(',', $notCreatedCount[0]->pending_classes))))
            : 0;
        $teacherremark = DB::select("select * from(select  tr.*,0 as read_status from teachers_remark tr  join teacher  on teacher.teacher_id=tr.teachers_id where tr.remark_type='Remark' and tr.academic_yr='" . $customClaims . "' and tr.teachers_id='" . $teacher_id . "'
         AND t_remark_id not IN( select t_remark_id FROM tremarks_read_log where teachers_id='" . $teacher_id . "'  )
              UNION
             select  tr.*,1 as read_status from teachers_remark tr  join teacher on teacher.teacher_id=tr.teachers_id where  tr.remark_type='Remark' and tr.academic_yr= '" . $customClaims . "'and tr.teachers_id='" . $teacher_id . "'  AND t_remark_id IN(select t_remark_id FROM tremarks_read_log where teachers_id='" . $teacher_id . "' ) )  as x ORDER BY publish_date DESC");
        $unreadCount = 0;

        foreach ($teacherremark as $row) {
            if ($row->read_status == 0) {
                $unreadCount++;
            }
        }

        $cards = [
            [
                'key' => 'lessonPlan',
                'value' => $lessonPlanCount,
                'data' => ['notcreatedCount' => $lessonPlanCount],
            ],
            [
                'key' => 'birthDayCard',
                'value' => $countOfBirthdaysToday,
                'data' => ['countOfBirthdaysToday' => $countOfBirthdaysToday],
            ],
            [
                'key' => 'homeworkCard',
                'value' => $countOfHomeworksDueToday,
                'data' => ['countOfHomeworksDueToday' => $countOfHomeworksDueToday],
            ],
            [
                'key' => 'pendingBooks',
                'value' => $pending_books,
                'data' => ['totalpendingBooks' => $pending_books],
            ],
            [
                'key' => 'defaulterCount',
                'value' => $totalNumberOfDefaulters,
                'data' => [
                    'totalPendingAmount' => $pendingAmount,
                    'totalNumberOfDefaulters' => $totalNumberOfDefaulters,
                ],
            ],
            [
                'key' => 'Reminder',
                'value' => $unreadCount,
                'data' => [
                    'unreadreminder' => $unreadCount,
                ],
            ]
        ];
        foreach ($cards as &$card) {
            // UI flag
            $card['show'] = (($card['value'] ?? 0) > 0) ? 1 : 0;

            // Default group
            $card['group'] = 3;

            // ZERO value → bottom group
            if (($card['value'] ?? 0) == 0) {
                $card['group'] = 5;
                continue;
            }

            // Lesson plan special day → top group
            if (
                $isLessonPlanPriorityDay &&
                ($card['key'] ?? '') === 'lessonPlan' &&
                $lessonPlanCount > 0
            ) {
                $card['group'] = 1;
                continue;
            }

            // Defaulter always last (even if value > 0)
            if (($card['key'] ?? '') === 'defaulterCount') {
                $card['group'] = 6;
                continue;
            }

            // Normal cards with value > 0
            $card['group'] = 3;
        }
        unset($card);
        $stableOrder = [
            'birthDayCard' => 1,
            'homeworkCard' => 2,
            'Reminder' => 3,
            'pendingBooks' => 4,
            'lessonPlan' => 5,
            'defaulterCount' => 6,
        ];

        usort($cards, function ($a, $b) use ($stableOrder) {
            // 1️⃣ Group sorting (business rules)
            if ($a['group'] !== $b['group']) {
                return $a['group'] <=> $b['group'];
            }

            // 2️⃣ Same group & value > 0 → VALUE DESC
            if (($a['value'] ?? 0) !== ($b['value'] ?? 0)) {
                return ($b['value'] ?? 0) <=> ($a['value'] ?? 0);
            }

            // 3️⃣ Stable fallback
            return ($stableOrder[$a['key']] ?? 99) <=>
                ($stableOrder[$b['key']] ?? 99);
        });

        $displayPriority = 1;
        foreach ($cards as &$card) {
            $card['priority'] = $displayPriority++;
        }
        unset($card);

        return response()->json([
            'status' => 'success',
            'data' => $cards
        ]);
    }

    public function getTeacherLateCountMonthly(Request $request)
    {
        $user = $this->authenticateUser();
        $teacher_id = JWTAuth::getPayload()->get('reg_id');
        $latecount = DB::select(
            "
                SELECT COUNT(*) AS late_days
                FROM (
                    SELECT DATE(ta.punch_time) AS attendance_date
                    FROM teacher t
                    JOIN teacher_category tc ON t.tc_id = tc.tc_id
                    JOIN teacher_attendance ta ON t.employee_id = ta.employee_id
                    JOIN late_time lt ON lt.tc_id = t.tc_id
                    WHERE 
                        t.isDelete = 'N'
                        AND tc.teaching = 'Y'
                        AND t.teacher_id = ?
                        AND ta.punch_time >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                        AND ta.punch_time <  DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
                    GROUP BY DATE(ta.punch_time), t.tc_id
                    HAVING MIN(TIME(ta.punch_time)) > MAX(lt.late_time)
                ) AS late_days_table
                ",
            [$teacher_id]
        );
        return response()->json([
            'status' => 200,
            'message' => 'Late days in month.',
            'data' => $latecount,
            'success' => true
        ]);
    }
}
