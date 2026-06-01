<?php

namespace App\Http\Controllers;

use App\Http\Services\SmsService;
use App\Http\Services\WhatsAppService;
use App\Jobs\SendTeacherMessageJob;
use App\Mail\TeacherBirthdayEmail;
use App\Mail\WelcomeEmail;
use App\Models\Allot_mark_headings;
use App\Models\Attendence;
use App\Models\BankAccountName;
use App\Models\Class_teachers;
use App\Models\Classes;
use App\Models\ContactDetails;
use App\Models\DeletedContactDetails;
use App\Models\Division;
use App\Models\Event;
use App\Models\LeaveAllocation;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\MarksHeadings;
use App\Models\Notice;
use App\Models\Parents;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StaffNotice;
use App\Models\Student;
use App\Models\SubjectAllotment;
use App\Models\SubjectAllotmentForReportCard;
use App\Models\SubjectForReportCard;
use App\Models\SubjectMaster;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserMaster;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use League\Csv\Writer;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use File;
use PDF;
use Validator;
use ZipArchive;
// use Maatwebsite\Excel\Facades\Excel;
// use App\Exports\IdCardExport;
// use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function hello()
    {
        return view('hello');
    }

    public function sendTeacherBirthdayEmail()
    {
        $currentMonth = Carbon::now()->format('m');
        $currentDay = Carbon::now()->format('d');

        $teachers = Teacher::whereMonth('birthday', $currentMonth)
            ->whereDay('birthday', $currentDay)
            ->get();

        foreach ($teachers as $teacher) {
            $textmsg = "Dear {$teacher->name},<br><br>";
            $textmsg .= 'Wishing you many happy returns of the day. May the coming year be filled with peace, prosperity, good health, and happiness.<br/><br/>';
            $textmsg .= 'Best Wishes,<br/>';
            $textmsg .= 'St. Arnolds Central School';

            $data = [
                'title' => 'Birthday Greetings!!',
                'body' => $textmsg,
                'teacher' => $teacher
            ];

            Mail::to($teacher->email)->send(new TeacherBirthdayEmail($data));
        }

        return response()->json(['message' => 'Birthday emails sent successfully']);
    }

    public function getAcademicyearlist(Request $request)
    {
        $academicyearlist = Setting::get()->academic_yr;
        return response()->json($academicyearlist);
    }

    public function getStudentData(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }
        $count = Student::where('IsDelete', 'N')
            ->where('academic_yr', $academicYr)
            ->count();
        $currentDate = Carbon::now()->toDateString();
        $present = Attendence::where('only_date', $currentDate)
            ->where('attendance_status', '0')
            ->where('academic_yr', $academicYr)
            ->count();
        return response()->json([
            'count' => $count,
            'present' => $present,
        ]);
    }

    public function staff()
    {
        $user = $this->authenticateUser();
        $short_code = JWTAuth::getPayload()->get('short_code');
        if ($short_code == 'HSCS') {
            $teachingStaff = count(
                DB::select("
                    SELECT DISTINCT t.teacher_id
                    FROM teacher t
                    JOIN user_master u
                        ON t.teacher_id = u.reg_id
                    LEFT JOIN teacher_category tc
                        ON t.tc_id = tc.tc_id
                    WHERE t.isDelete = 'N'
                    AND tc.teaching = 'Y'
                ")
            );

            $attendanceteachingstaff = count(
                DB::select("
                    SELECT DISTINCT ta.employee_id
                    FROM teacher_attendance ta,
                        teacher t,
                        user_master u,
                        teacher_category tc
                    WHERE ta.employee_id = CAST(t.employee_id AS UNSIGNED)
                    AND t.isDelete = 'N'
                    AND tc.teaching = 'Y'
                    AND t.tc_id = tc.tc_id
                    AND DATE_FORMAT(punch_time, '%y-%m-%d') = CURDATE()
                ")
            );

            $non_teachingStaff = count(
                DB::select("
                    SELECT DISTINCT t.teacher_id
                    FROM teacher t
                    JOIN user_master u
                        ON t.teacher_id = u.reg_id
                    LEFT JOIN teacher_category tc
                        ON t.tc_id = tc.tc_id
                    WHERE t.isDelete = 'N'
                    AND tc.teaching = 'N'

                    UNION

                    SELECT DISTINCT c.teacher_id
                    FROM teacher c
                    LEFT JOIN teacher_category tc
                        ON c.tc_id = tc.tc_id
                    WHERE c.designation = 'Caretaker'
                    AND c.isDelete = 'N'
                    AND tc.teaching = 'N'

                    ORDER BY teacher_id ASC
                ")
            );

            $attendancenonteachingstaff = count(
                DB::select("
                    SELECT DISTINCT ta.employee_id
                    FROM teacher_attendance ta,
                        teacher t,
                        user_master u,
                        teacher_category tc
                    WHERE ta.employee_id = CAST(t.employee_id AS UNSIGNED)
                    AND t.teacher_id = u.reg_id
                    AND t.tc_id = tc.tc_id
                    AND t.isDelete = 'N'
                    AND tc.teaching = 'N'
                    AND DATE_FORMAT(punch_time, '%y-%m-%d') = CURDATE()

                    UNION

                    SELECT DISTINCT ta.employee_id
                    FROM teacher_attendance ta,
                        teacher t
                    WHERE ta.employee_id = CAST(t.employee_id AS UNSIGNED)
                    AND t.isDelete = 'N'
                    AND t.designation = 'Caretaker'
                    AND DATE_FORMAT(punch_time, '%y-%m-%d') = CURDATE()
                ")
            );

            return response()->json([
                'teachingStaff' => $teachingStaff,
                'non_teachingStaff' => $non_teachingStaff,
                'attendancenonteachingstaff' => $attendancenonteachingstaff,
                'attendanceteachingstaff' => $attendanceteachingstaff
            ]);
        } else if ('SACS') {
            $teachingStaff = count(
                DB::select("
                    SELECT DISTINCT t.teacher_id
                    FROM teacher t
                    JOIN user_master u
                        ON t.teacher_id = u.reg_id
                    LEFT JOIN teacher_category tc
                        ON t.tc_id = tc.tc_id
                    WHERE t.isDelete = 'N'
                    AND tc.teaching = 'Y'
                ")
            );

            $attendanceteachingstaff = count(
                DB::select("
                    SELECT DISTINCT ta.employee_id
                    FROM teacher_attendance ta,
                        teacher t,
                        user_master u,
                        teacher_category tc
                    WHERE ta.employee_id = CAST(t.employee_id AS UNSIGNED)
                    AND t.isDelete = 'N'
                    AND tc.teaching = 'Y'
                    AND t.tc_id = tc.tc_id
                    AND DATE_FORMAT(punch_time, '%y-%m-%d') = CURDATE()
                ")
            );

            $non_teachingStaff = count(
                DB::select("
                    SELECT DISTINCT t.teacher_id
                    FROM teacher t
                    JOIN user_master u
                        ON t.teacher_id = u.reg_id
                    LEFT JOIN teacher_category tc
                        ON t.tc_id = tc.tc_id
                    WHERE t.isDelete = 'N'
                    AND tc.teaching = 'N'

                    UNION

                    SELECT DISTINCT c.teacher_id
                    FROM teacher c
                    LEFT JOIN teacher_category tc
                        ON c.tc_id = tc.tc_id
                    WHERE c.designation = 'Caretaker'
                    AND c.isDelete = 'N'
                    AND tc.teaching = 'N'

                    ORDER BY teacher_id ASC
                ")
            );

            $attendancenonteachingstaff = count(
                DB::select("
                    SELECT DISTINCT ta.employee_id
                    FROM teacher_attendance ta,
                        teacher t,
                        user_master u,
                        teacher_category tc
                    WHERE ta.employee_id = CAST(t.employee_id AS UNSIGNED)
                    AND t.teacher_id = u.reg_id
                    AND t.tc_id = tc.tc_id
                    AND t.isDelete = 'N'
                    AND tc.teaching = 'N'
                    AND DATE_FORMAT(punch_time, '%y-%m-%d') = CURDATE()

                    UNION

                    SELECT DISTINCT ta.employee_id
                    FROM teacher_attendance ta,
                        teacher t
                    WHERE ta.employee_id = CAST(t.employee_id AS UNSIGNED)
                    AND t.isDelete = 'N'
                    AND t.designation = 'Caretaker'
                    AND DATE_FORMAT(punch_time, '%y-%m-%d') = CURDATE()
                ")
            );

            return response()->json([
                'teachingStaff' => $teachingStaff,
                'non_teachingStaff' => $non_teachingStaff,
                'attendancenonteachingstaff' => $attendancenonteachingstaff,
                'attendanceteachingstaff' => $attendanceteachingstaff
            ]);
        }
    }

    public function staffBirthdaycount(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }
        $currentDate = Carbon::now();
        $teachercount = Teacher::where('IsDelete', 'N')
            ->whereMonth('birthday', $currentDate->month)
            ->whereDay('birthday', $currentDate->day)
            ->count();
        $studentcount = Student::where('IsDelete', 'N')
            ->whereMonth('dob', $currentDate->month)
            ->whereDay('dob', $currentDate->day)
            ->where('academic_yr', $academicYr)
            ->count();
        $count = $teachercount + $studentcount;
        return response()->json([
            'count' => $count,
        ]);
    }

    public function staffBirthdayList(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }

        $academicYr = $payload->get('academic_year');
        if (!$academicYr) {
            return response()->json([
                'message' => 'Academic year not found in request headers',
                'success' => false
            ], 404);
        }

        // Dates
        $dates = [
            'yesterday' => Carbon::now()->subDay(),
            'today' => Carbon::now(),
            'tomorrow' => Carbon::now()->addDay(),
        ];

        /**
         * -------------------------
         *  Base Queries
         *  -------------------------
         */
        $staffBaseQuery = Teacher::where('IsDelete', 'N');

        $studentBaseQuery = Student::where('student.IsDelete', 'N')
            ->join('class', 'class.class_id', '=', 'student.class_id')
            ->join('section', 'section.section_id', '=', 'student.section_id')
            ->leftJoin('contact_details', 'contact_details.id', '=', 'student.parent_id')
            ->where('student.academic_yr', $academicYr)
            ->select(
                'student.*',
                'class.name as classname',
                'section.name as sectionname',
                'contact_details.*'
            );

        /**
         * -------------------------
         *  Response Structure
         *  -------------------------
         */
        $response = [
            'students' => [],
            'staff' => []
        ];

        foreach ($dates as $key => $date) {
            // STAFF
            $staffList = (clone $staffBaseQuery)
                ->whereMonth('birthday', $date->month)
                ->whereDay('birthday', $date->day)
                ->get();

            // STUDENTS
            $studentList = (clone $studentBaseQuery)
                ->whereMonth('dob', $date->month)
                ->whereDay('dob', $date->day)
                ->get();

            $response['staff'][$key] = [
                'count' => $staffList->count(),
                'list' => $staffList
            ];

            $response['students'][$key] = [
                'count' => $studentList->count(),
                'list' => $studentList
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public function getEvents(Request $request): JsonResponse
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }

        $currentDate = Carbon::now();
        $month = $request->input('month', $currentDate->month);
        $year = $request->input('year', $currentDate->year);

        $events = Event::select([
            'events.unq_id',
            'events.title',
            'events.event_desc',
            'events.start_date',
            'events.end_date',
            'events.start_time',
            'events.end_time',
            DB::raw('GROUP_CONCAT(class.name) as class_name')
        ])
            ->join('class', 'events.class_id', '=', 'class.class_id')
            ->where('events.isDelete', 'N')
            ->where('events.publish', 'Y')
            ->where('events.academic_yr', $academicYr)
            ->whereMonth('events.start_date', $month)
            ->whereYear('events.start_date', $year)
            ->groupBy(
                'events.unq_id',
                'events.title',
                'events.event_desc',
                'events.start_date',
                'events.end_date',
                'events.start_time',
                'events.end_time'
            )
            ->orderBy('events.start_date')
            ->orderByDesc('events.start_time')
            ->get()
            ->map(function ($event) {
                $event->event_desc = $event->event_desc;
                return $event;
            });

        return response()->json($events);
    }

    public function getParentNotices(Request $request): JsonResponse
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }

        $parentNotices = Notice::select([
            'subject',
            'notice_desc',
            'notice_date',
            'notice_type',
            \DB::raw('GROUP_CONCAT(class.name) as class_name')
        ])
            ->join('class', 'notice.class_id', '=', 'class.class_id')
            ->where('notice.publish', 'Y')
            ->where('notice.academic_yr', $academicYr)
            ->groupBy('notice.unq_id')
            ->orderBy('notice_id')
            ->get();

        return response()->json(['parent_notices' => $parentNotices]);
    }

    public function getNoticesForTeachers(Request $request): JsonResponse
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $notices = StaffNotice::select([
            'staff_notice.subject',
            'staff_notice.notice_desc',
            'staff_notice.notice_date',
            'staff_notice.notice_type',
            DB::raw('GROUP_CONCAT(t.name) as staff_name')
        ])
            ->join('teacher as t', 't.teacher_id', '=', 'staff_notice.teacher_id')
            ->where('staff_notice.publish', 'Y')
            ->where('staff_notice.academic_yr', $academicYr)
            ->groupBy('staff_notice.subject', 'staff_notice.notice_desc', 'staff_notice.notice_date', 'staff_notice.notice_type')
            ->orderBy('staff_notice.notice_date')
            ->get();

        return response()->json(['notices' => $notices, 'success' => true]);
    }

    public function getClassDivisionTotalStudents(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        if (!$academicYr) {
            return response()->json(['error' => 'Academic year is missing'], 400);
        }

        $results = DB::table('class as c')
            ->leftJoin('section as s', 'c.class_id', '=', 's.class_id')
            ->leftJoin(DB::raw("
            (SELECT section_id, COUNT(student_id) AS students_count
             FROM student
             WHERE academic_yr = '{$academicYr}'  -- Filter by academic year
             AND isDelete = 'N'
             AND parent_id != '0'
             GROUP BY section_id) as st
        "), 's.section_id', '=', 'st.section_id')
            ->select(
                'c.class_id',
                DB::raw("CONCAT(c.name, ' ', COALESCE(s.name, 'No division assigned')) AS class_division"),
                DB::raw('SUM(st.students_count) AS total_students'),
                'c.name as class_name',
                's.name as section_name'
            )
            ->where('s.academic_yr', $academicYr)
            ->where('c.academic_yr', $academicYr)
            ->groupBy('c.name', 's.name')
            ->orderBy('c.class_id')
            ->orderBy('s.name')
            ->get();

        return response()->json($results);
    }

    public function ticketCount(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $role_id = $payload->get('role_id');

        $count = DB::table('ticket')
            ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
            ->where('service_type.role_id', $role_id)
            ->where('ticket.acd_yr', $academicYr)
            ->where('ticket.status', '!=', 'Closed')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function getTicketList(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $role_id = $payload->get('role_id');

        $tickets = DB::table('ticket')
            ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
            ->join('student', 'ticket.student_id', '=', 'student.student_id')
            ->where('service_type.role_id', $role_id)
            ->where('ticket.acd_yr', $academicYr)
            ->where('ticket.status', '!=', 'Closed')
            ->orderBy('ticket.raised_on', 'DESC')
            ->select(
                'ticket.*',
                'service_type.service_name',
                'student.first_name',
                'student.mid_name',
                'student.last_name'
            )
            ->get();

        return response()->json($tickets);
    }

    public function feeCollection(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

        $sql = "
            SELECT SUM(installment_fees - concession - paid_amount) AS pending_fee FROM
            (SELECT s.student_id, s.installment, installment_fees, COALESCE(SUM(d.amount), 0) AS concession, 0 AS paid_amount FROM
            view_student_fees_category s LEFT JOIN fee_concession_details d ON s.student_id = d.student_id AND s.installment = d.installment WHERE
            s.academic_yr = '$academicYr' and s.installment<>4 AND due_date < CURDATE() AND s.student_installment NOT IN
            (SELECT student_installment FROM view_student_fees_payment a WHERE a.academic_yr = '$academicYr') GROUP BY s.student_id, s.installment
            UNION SELECT f.student_id AS student_id, b.installment AS installment, b.installment_fees, COALESCE(SUM(c.amount), 0) AS concession,
            SUM(f.fees_paid) AS paid_amount FROM view_student_fees_payment f LEFT JOIN fee_concession_details c ON f.student_id = c.student_id
            AND f.installment = c.installment JOIN view_fee_allotment b ON f.fee_allotment_id = b.fee_allotment_id AND b.installment = f.installment
            WHERE b.installment<>4 and f.academic_yr = '$academicYr' GROUP BY f.installment, c.installment  HAVING
            (b.installment_fees - COALESCE(SUM(c.amount), 0)) > SUM(f.fees_paid)) as z
        ";

        $results = DB::select($sql);

        // $pendingFee = $results[0]->pending_fee;
        $pendingFee = $results[0]->pending_fee ?? 0;

        $collectedfees = DB::select("SELECT 'Nursery' AS account, 
           IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
           SUM(d.amount) AS amount 
    FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
    WHERE a.student_id = b.student_id 
      AND b.class_id = c.class_id 
      AND a.fees_payment_id = d.fees_payment_id 
      AND a.isCancel = 'N' 
      AND a.academic_yr = '$academicYr' 
      AND c.name = 'Nursery' 
    GROUP BY d.installment 

    UNION

    SELECT 'KG' AS account, 
           IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
           SUM(d.amount) AS amount 
            FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
            WHERE a.student_id = b.student_id 
            AND b.class_id = c.class_id 
            AND a.fees_payment_id = d.fees_payment_id 
            AND a.isCancel = 'N' 
            AND a.academic_yr = '$academicYr' 
            AND c.name IN ('LKG','UKG') 
            GROUP BY d.installment 

            UNION

            SELECT 'School' AS account, 
                IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
                SUM(d.amount) AS amount 
            FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
            WHERE a.student_id = b.student_id 
            AND b.class_id = c.class_id 
            AND a.fees_payment_id = d.fees_payment_id 
            AND a.isCancel = 'N' 
            AND a.academic_yr = '$academicYr' 
            AND c.name IN ('1','2','3','4','5','6','7','8','9','10','11','12') 
            GROUP BY d.installment");
        $totalAmount = number_format(collect($collectedfees)->sum('amount'), 2, '.', '');
        $feesdata = [
            'Collected Fees' => $totalAmount,
            'Pending Fees' => $pendingFee
        ];

        return response()->json($feesdata);
    }

    public function getHouseViseStudent(Request $request)
    {
        $className = $request->input('class_name');

        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $query = "
        SELECT CONCAT(class.name, ' ', section.name) AS class_section,
               house.house_name AS house_name,
               house.color_code AS color_code,
               COUNT(student.student_id) AS student_counts
        FROM student
        JOIN class ON student.class_id = class.class_id
        JOIN section ON student.section_id = section.section_id
        JOIN house ON student.house = house.house_id
        WHERE student.IsDelete = 'N'
          AND student.academic_yr = ?
        ";

        $params = [$academicYr];

        if ($className) {
            $query .= ' AND class.name = ?';
            $params[] = $className;
        }

        $query .= '
        GROUP BY class_section, house_name, house.color_code
        ORDER BY class_section, house_name
        ';

        $results = DB::select($query, $params);

        return response()->json($results);
    }

    public function getAcademicYears(Request $request)
    {
        $user = Auth::user();
        $activeAcademicYear = Setting::where('active', 'Y')->first()->academic_yr;

        $settings = Setting::all();

        if ($user->role_id === 'P') {
            $settings = $settings->filter(function ($setting) use ($activeAcademicYear) {
                return $setting->academic_yr <= $activeAcademicYear;
            });
        }
        $academicYears = $settings->pluck('academic_yr');

        return response()->json([
            'academic_years' => $academicYears,
            'settings' => $settings
        ]);
    }

    public function getAuthUser()
    {
        $user = auth()->user();
        $academic_yr = $user->academic_yr;

        return response()->json([
            'user' => $user,
            'academic_yr' => $academic_yr,
        ]);
    }

    public function getBankAccountName()
    {
        $bankAccountName = BankAccountName::all();
        return response()->json([
            'bankAccountName' => $bankAccountName,
        ]);
    }

    public function pendingCollectedFeeData(): JsonResponse
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $finalQuery = DB::select("
                    select z.installment, z.Account, sum(z.installment_fees-concession-paid_amount) as pending_fee from (SELECT s.student_id,s.installment, installment_fees, coalesce(sum(d.amount),0) as concession,
                0 as paid_amount, CASE WHEN cl.name = 'Nursery' THEN 'Nursery' WHEN cl.name IN ('LKG','UKG') THEN 'KG' ELSE 'School' END as Account FROM view_student_fees_category s left join fee_concession_details d on s.student_id=d.student_id and s.installment=d.installment join class cl on s.class_id=cl.class_id WHERE s.academic_yr='$customClaims' and s.installment<>4 and due_date < CURDATE() and s.student_installment not in (SELECT student_installment FROM view_student_fees_payment a where a.academic_yr='$customClaims') group by s.student_id, s.installment UNION SELECT f.student_id as student_id, b.installment as installment, b.installment_fees, coalesce(sum(c.amount),0) as concession, sum(f.fees_paid) as paid_amount, CASE WHEN cs.name = 'Nursery' THEN 'Nursery' WHEN cs.name IN ('LKG','UKG') THEN 'KG' ELSE 'School'  END as Account  FROM view_student_fees_payment f left join fee_concession_details c on  f.student_id=c.student_id and f.installment=c.installment join view_fee_allotment b on f.fee_allotment_id= b.fee_allotment_id and b.installment=f.installment join class cs on f.class_id=cs.class_id WHERE b.installment<>4 and f.academic_yr='$customClaims' group by f.installment, c.installment having (b.installment_fees-coalesce(sum(c.amount),0))>sum(f.fees_paid)) z group by z.installment, z.Account
                ");
            foreach ($finalQuery as &$row) {
                $row->pending_fee = formatIndianCurrency(number_format((float) $row->pending_fee, 2, '.', ''));
            }

            return response()->json($finalQuery);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function pendingCollectedFeeDatalist(Request $request): JsonResponse
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

        $subQuery1 = DB::table('view_student_fees_category as s')
            ->leftJoin('fee_concession_details as d', function ($join) {
                $join
                    ->on('s.student_id', '=', 'd.student_id')
                    ->on('s.installment', '=', 'd.installment');
            })
            ->select(
                's.student_id',
                's.installment',
                's.installment_fees',
                DB::raw('COALESCE(SUM(d.amount), 0) as concession'),
                DB::raw('0 as paid_amount')
            )
            ->where('s.academic_yr', $academicYr)
            ->where('s.installment', '<>', 4)
            ->where('s.due_date', '<', DB::raw('CURDATE()'))
            ->whereNotIn('s.student_installment', function ($query) use ($academicYr) {
                $query
                    ->select('a.student_installment')
                    ->from('view_student_fees_payment as a')
                    ->where('a.academic_yr', $academicYr);
            })
            ->groupBy('s.student_id', 's.installment');

        $subQuery2 = DB::table('view_student_fees_payment as f')
            ->leftJoin('fee_concession_details as c', function ($join) {
                $join
                    ->on('f.student_id', '=', 'c.student_id')
                    ->on('f.installment', '=', 'c.installment');
            })
            ->join('view_fee_allotment as b', function ($join) {
                $join
                    ->on('f.fee_allotment_id', '=', 'b.fee_allotment_id')
                    ->on('b.installment', '=', 'f.installment');
            })
            ->select(
                'f.student_id as student_id',
                'b.installment as installment',
                'b.installment_fees',
                DB::raw('COALESCE(SUM(c.amount), 0) as concession'),
                DB::raw('SUM(f.fees_paid) as paid_amount')
            )
            ->where('b.installment', '<>', 4)
            ->where('f.academic_yr', $academicYr)
            ->groupBy('f.installment', 'c.installment')
            ->havingRaw('(b.installment_fees - COALESCE(SUM(c.amount), 0)) > SUM(f.fees_paid)');

        $unionQuery = $subQuery1->union($subQuery2);

        $finalQuery = DB::table(DB::raw("({$unionQuery->toSql()}) as z"))
            ->select(
                'z.installment',
                DB::raw('SUM(z.installment_fees - z.concession - z.paid_amount) as pending_fee')
            )
            ->groupBy('z.installment')
            ->mergeBindings($unionQuery)
            ->get();

        return response()->json($finalQuery);
    }

    public function collectedFeeList(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $collectedfees = DB::select("SELECT 'Nursery' AS account, 
           IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
           SUM(d.amount) AS amount 
            FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
            WHERE a.student_id = b.student_id 
            AND b.class_id = c.class_id 
            AND a.fees_payment_id = d.fees_payment_id 
            AND a.isCancel = 'N' 
            AND a.academic_yr = '$academicYr' 
            AND c.name = 'Nursery' 
            GROUP BY d.installment 

            UNION

            SELECT 'KG' AS account, 
                IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
                SUM(d.amount) AS amount 
            FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
            WHERE a.student_id = b.student_id 
            AND b.class_id = c.class_id 
            AND a.fees_payment_id = d.fees_payment_id 
            AND a.isCancel = 'N' 
            AND a.academic_yr = '$academicYr' 
            AND c.name IN ('LKG','UKG') 
            GROUP BY d.installment 

            UNION

            SELECT 'School' AS account, 
                IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
                SUM(d.amount) AS amount 
            FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
            WHERE a.student_id = b.student_id 
            AND b.class_id = c.class_id 
            AND a.fees_payment_id = d.fees_payment_id 
            AND a.isCancel = 'N' 
            AND a.academic_yr = '$academicYr' 
            AND c.name IN ('1','2','3','4','5','6','7','8','9','10','11','12') 
            GROUP BY d.installment");

        return response()->json($collectedfees);
    }

    public function listSections(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $sections = DB::table('department')
            ->where('department.academic_yr', $academicYr)
            ->leftJoin('class', 'department.department_id', '=', 'class.department_id')
            ->select(
                'department.*',
                DB::raw("GROUP_CONCAT(class.name ORDER BY CAST(class.name AS UNSIGNED) ASC SEPARATOR ', ') as class_names")
            )
            ->groupBy('department.department_id')
            ->get();

        return response()->json($sections);
    }

    public function checkSectionName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:30',
        ]);
        $name = $request->input('name');
        $exists = Section::where(DB::raw('LOWER(name)'), strtolower($name))->exists();

        return response()->json(['exists' => $exists]);
    }

    public function updateSection(Request $request, $id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:30',
                    'regex:/^[a-zA-Z]+$/',
                    Rule::unique('department')
                        ->ignore($id, 'department_id')
                        ->where(function ($query) use ($academicYr) {
                            $query->where('academic_yr', $academicYr);
                        })
                ],
            ],
            [
                'name.required' => 'The name field is required.',
                'name.string' => 'The name field must be a string.',
                'name.max' => 'The name field must not exceed 255 characters.',
                'name.regex' => 'The name field must contain only alphabetic characters without spaces.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $section = Section::find($id);
        if (!$section) {
            return response()->json(['message' => 'Section not found', 'success' => false], 404);
        }
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }

        // Update the section
        $section->name = $request->name;
        $section->academic_yr = $academicYr;
        $section->save();

        // Return success response
        return response()->json([
            'status' => 200,
            'message' => 'Section updated successfully',
        ]);
    }

    public function storeSection(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z]+$/',
            ],
        ], [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name field must be a string.',
            'name.max' => 'The name field must not exceed 255 characters.',
            'name.regex' => 'The name field must contain only alphabetic characters without spaces.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }

        $academicYr = $payload->get('academic_year');

        $section = new Section();
        $section->name = $request->name;
        $section->academic_yr = $academicYr;
        $section->save();

        return response()->json([
            'status' => 201,
            'message' => 'Section created successfully',
            'data' => $section,
        ]);
    }

    public function editSection($id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json(['message' => 'Section not found', 'success' => false], 404);
        }

        return response()->json($section);
    }

    public function deleteSection($id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json(['message' => 'Section not found', 'success' => false], 404);
        }
        if ($section->classes()->exists()) {
            return response()->json(['message' => 'This section is in use and cannot be deleted.', 'success' => false], 400);
        }

        $section->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Section deleted successfully',
            'success' => true
        ]);
    }

    public function checkClassName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:30',
        ]);
        $name = $request->input('name');
        $exists = Classes::where(DB::raw('LOWER(name)'), strtolower($name))->exists();
        return response()->json(['exists' => $exists]);
    }

    public function getClass(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }

        $academicYr = $payload->get('academic_year');

        $classes = Classes::with('getDepartment')
            ->withCount('students')
            ->where('academic_yr', $academicYr)
            ->orderBy('class_id')
            ->get();

        return response()->json($classes);
    }

    public function storeClass(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        $validator = \Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:30'],
            'department_id' => ['required', 'integer'],
        ], [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name field must be a string.',
            'name.max' => 'The name field must not exceed 255 characters.',
            'department_id.required' => 'The department ID is required.',
            'department_id.integer' => 'The department ID must be an integer.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $class = new Classes();
        $class->name = $request->name;
        $class->department_id = $request->department_id;
        $class->academic_yr = $academicYr;
        $class->save();
        return response()->json([
            'status' => 201,
            'message' => 'Class created successfully',
            'data' => $class,
        ]);
    }

    public function updateClass(Request $request, $id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $validator = \Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('class')
                    ->ignore($id, 'class_id')
                    ->where(function ($query) use ($academicYr) {
                        $query->where('academic_yr', $academicYr);
                    })
            ],
            'department_id' => ['required', 'integer'],
        ], [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name field must be a string.',
            'name.max' => 'The name field must not exceed 30 characters.',
            'department_id.required' => 'The department ID is required.',
            'department_id.integer' => 'The department ID must be an integer.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $class = Classes::find($id);
        if (!$class) {
            return response()->json(['message' => 'Class not found', 'success' => false], 404);
        }

        $class->name = $request->name;
        $class->department_id = $request->department_id;
        $class->academic_yr = $academicYr;
        $class->save();

        return response()->json([
            'status' => 200,
            'message' => 'Class updated successfully',
            'data' => $class,
        ]);
    }

    public function showClass($id)
    {
        $class = Classes::find($id);
        if (!$class) {
            return response()->json(['message' => 'Class not found', 'success' => false], 404);
        }

        // Return the class data
        return response()->json([
            'status' => 200,
            'message' => 'Class retrieved successfully',
            'data' => $class,
        ]);
    }

    public function getDepartments()
    {
        $departments = Section::all();
        return response()->json($departments);
    }

    public function destroyClass($id)
    {
        $class = Classes::find($id);
        if (!$class) {
            return response()->json(['message' => 'Class not found', 'success' => false], 404);
        }
        $sectionCount = DB::table('section')->where('class_id', $id)->count();
        if ($sectionCount > 0) {
            return response()->json([
                'status' => 400,
                'message' => 'This class is in use. Delete failed!',
            ]);
        } else {
            $class->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Class deleted successfully',
            ]);
        }
    }

    // Methods for the Divisons
    public function checkDivisionName(Request $request)
    {
        $messages = [
            'name.required' => 'The division name is required.',
            'name.string' => 'The division name must be a string.',
            'name.max' => 'The division name may not be greater than 30 characters.',
            'class_id.required' => 'The class ID is required.',
            'class_id.integer' => 'The class ID must be an integer.',
            'class_id.exists' => 'The selected class ID is invalid.',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:30',
            'class_id' => 'required|integer|exists:class,class_id',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $validatedData = $validator->validated();
        $name = $validatedData['name'];
        $classId = $validatedData['class_id'];

        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $exists = Division::where(DB::raw('LOWER(name)'), strtolower($name))
            ->where('class_id', $classId)
            ->where('academic_yr', $academicYr)
            ->exists();
        return response()->json(['exists' => $exists]);
    }

    public function getDivision(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $divisions = Division::with('getClass.getDepartment')
            ->where('academic_yr', $academicYr)
            ->get();
        return response()->json($divisions);
    }

    public function getClassforDivision(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $classList = Classes::where('academic_yr', $academicYr)->get();
        return response()->json($classList);
    }

    public function storeDivision(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $division = new Division();
        $division->name = $request->name;
        $division->class_id = $request->class_id;
        $division->academic_yr = $academicYr;
        $division->save();
        return response()->json([
            'status' => 200,
            'message' => 'Class created successfully',
        ]);
    }

    public function updateDivision(Request $request, $id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $sectiondata = Division::find($id);
        $class_id = $request->class_id;
        $validator = \Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('section')
                    ->ignore($id, 'section_id')
                    ->where(function ($query) use ($academicYr) {
                        $query->where('academic_yr', $academicYr);
                    })
                    ->where(function ($query) use ($class_id) {
                        $query->where('class_id', $class_id);
                    })
            ]
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $division = Division::find($id);
        if (!$division) {
            return response()->json([
                'status' => 404,
                'message' => 'Division not found',
            ], 404);
        }
        $division->name = $request->name;
        $division->class_id = $request->class_id;
        $division->academic_yr = $academicYr;
        $division->update();

        return response()->json([
            'status' => 200,
            'message' => 'Division updated successfully',
        ]);
    }

    public function showDivision($id)
    {
        $division = Division::with('getClass')->find($id);

        if (is_null($division)) {
            return response()->json(['message' => 'Division not found'], 404);
        }

        return response()->json($division);
    }

    public function destroyDivision($id)
    {
        $studentCount = DB::table('student')->where('section_id', $id)->count();

        if ($studentCount > 0) {
            return response()->json([
                'error' => 'This division is in use by students. Deletion failed!'
            ], 400);
        }

        // Check if section_id exists in the subject table
        $subjectCount = DB::table('subject')->where('section_id', $id)->count();

        if ($subjectCount > 0) {
            return response()->json([
                'error' => 'This division is in use by subjects. Deletion failed!'
            ], 400);
        }
        $division = Division::find($id);

        if (is_null($division)) {
            return response()->json(['message' => 'Division not found'], 404);
        }

        $division->delete();
        return response()->json(
            [
                'status' => 200,
                'message' => 'Division deleted successfully',
                'success' => true
            ]
        );
    }

    // Updated By-Manish Kumar Sharma 21-04-2025
    public function getStaffList(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            $globalVariables = App::make('global_variables');
            $parent_app_url = $globalVariables['parent_app_url'];
            $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
            $stafflist = DB::table('teacher')
                ->where('teacher.designation', '!=', 'Caretaker')
                ->select('teacher.*')
                ->get();

            // Get class-section mappings for all teachers
            $classMappings = DB::table('class_teachers')
                ->join('class', 'class_teachers.class_id', '=', 'class.class_id')
                ->join('section', 'class_teachers.section_id', '=', 'section.section_id')
                ->select(
                    'class_teachers.teacher_id',
                    'class.name as classname',
                    'section.name as sectionname',
                    'class_teachers.class_id',
                    'class_teachers.section_id'
                )
                ->where('class_teachers.academic_yr', $customClaims)
                ->orderBy('class_teachers.section_id')
                ->get();

            // Attach classes + fix image URL
            $stafflist = $stafflist->map(function ($staff) use ($classMappings, $codeigniter_app_url) {
                $concatprojecturl = $codeigniter_app_url . 'uploads/teacher_image/';

                // Fix image path
                $staff->teacher_image_name = $staff->teacher_image_name
                    ? $concatprojecturl . $staff->teacher_image_name
                    : null;

                // Attach class-section data
                $staff->classes = $classMappings
                    ->where('teacher_id', $staff->teacher_id)
                    ->values();  // reset index

                return $staff;
            });

            return response()->json($stafflist);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    // Edited by - Manish Kumar sharma 15-02-2025  Updated By-Manish Kumar Sharma 21-04-2025
    public function editStaff($id)
    {
        try {
            // Find the teacher by ID
            $teacher = DB::table('teacher')
                ->where('teacher.teacher_id', $id)
                ->select('teacher.*')  // or any user fields you need
                ->first();
            $globalVariables = App::make('global_variables');
            $parent_app_url = $globalVariables['parent_app_url'];
            $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
            $concatprojecturl = $codeigniter_app_url . '' . 'uploads/teacher_image/';

            // Check if the teacher has an image and generate the URL if it exists
            if ($teacher->teacher_image_name) {
                $teacher->teacher_image_name = $concatprojecturl . '' . "$teacher->teacher_image_name";
            } else {
                $teacher->teacher_image_name = null;
            }

            // Find the associated user record
            $user = DB::table('user_master')->where('reg_id', $id)->whereNotIn('role_id', ['P', 'S'])->first();

            return response()->json([
                'teacher' => $teacher,
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the teacher details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeStaff(Request $request)
    {
        DB::beginTransaction();  // Start the transaction

        try {
            // Validation rules and messages
            $messages = [
                'name.required' => 'The name field is mandatory.',
                'birthday.required' => 'The birthday field is required.',
                'date_of_joining.required' => 'The date of joining is required.',
                'email.required' => 'The email field is required.',
                'email.email' => 'The email must be a valid email address.',
                'email.unique' => 'The email has already been taken.',
                'phone.required' => 'The phone number is required.',
                'phone.max' => 'The phone number cannot exceed 15 characters.',
                'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
                'role.required' => 'The role field is required.',
                'employee_id.unique' => 'Employee Id should be unique.',
                'employee_id.required' => 'Employee Id is required.'
            ];

            $validatedData = $request->validate([
                'employee_id' => 'required|unique:teacher,employee_id',
                'name' => 'required|string|max:255',
                'birthday' => 'required|date',
                'date_of_joining' => 'required|date',
                'sex' => 'required|string|max:10',
                'religion' => 'nullable|string|max:255',
                'blood_group' => 'nullable|string|max:10',
                'address' => 'required|string|max:255',
                'phone' => 'required|string|max:15',
                'email' => 'required|string|max:50',  // Ensure email uniqueness
                'designation' => 'nullable|string|max:255',
                'academic_qual' => 'nullable|array',
                'academic_qual.*' => 'nullable|string|max:255',
                'professional_qual' => 'nullable|string|max:255',
                'special_sub' => 'nullable|string|max:255',
                'trained' => 'nullable|string|max:255',
                'experience' => 'nullable|string|max:255',
                'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no',
                'teacher_image_name' => 'nullable|string',  // Base64 string or null
                'role' => 'required|string|max:255',
                'tc_id' => 'nullable|string|max:255',
                'emergency_phone' => 'nullable|string|max:10',
                'permanent_address' => 'nullable|string|max:255',
            ], $messages);

            // Concatenate academic qualifications into a string if they exist
            if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
                $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
            }

            $teacherid = DB::table('teacher')
                ->select('teacher_id')
                ->orderBy('teacher_id', 'DESC')
                ->first();

            $incrementid = $teacherid ? $teacherid->teacher_id + 1 : 1;

            // Check if teacher_image_name is null or empty and skip image-saving process if true
            if ($request->input('teacher_image_name') === 'null') {
                // Set image field as null if no image is provided
                $validatedData['teacher_image_name'] = null;
            } else {
                // Handle image saving logic when teacher_image_name is not null
                $imageData = $request->input('teacher_image_name');
                if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $type = strtolower($type[1]);  // jpg, png, gif

                    // Validate image type
                    if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                        throw new \Exception('Invalid image type');
                    }

                    // Base64 decode the image
                    $imageData = base64_decode($imageData);
                    if ($imageData === false) {
                        throw new \Exception('Base64 decode failed');
                    }

                    // Define the filename and path to store the image
                    $filename = $incrementid . '.' . $type;
                    $filePath = storage_path('app/public/teacher_images/' . $filename);
                    $doc_type_folder = 'teacher_image';

                    // Ensure the directory exists
                    $directory = dirname($filePath);
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    // Save the image to the file system
                    if (file_put_contents($filePath, $imageData) === false) {
                        throw new \Exception('Failed to save image file');
                    }

                    $fileContent = file_get_contents($filePath);  // Get the file content
                    $base64File = base64_encode($fileContent);
                    upload_teacher_profile_image_into_folder($incrementid, $filename, $doc_type_folder, $base64File);
                    // Store the filename in validated data
                    $validatedData['teacher_image_name'] = $filename;
                } else {
                    throw new \Exception('Invalid image data');
                }
            }
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $settingsData = getSchoolSettingsData();
            $staffUserSuffix = $settingsData->staffuser_suffix;
            $defaultPassword = $settingsData->default_pwd;
            $shortName = $settingsData->short_name;
            $schoolName = $settingsData->institute_name;
            $websiteUrl = $settingsData->website_url;

            // Create Teacher record
            $teacher = new Teacher();
            $teacher->fill($validatedData);
            $teacher->IsDelete = 'N';
            $teacher->created_by = $user->reg_id;

            if (!$teacher->save()) {
                DB::rollBack();  // Rollback the transaction
                return response()->json([
                    'message' => 'Failed to create teacher',
                ], 500);
            }

            $firstname = explode(' ', trim($validatedData['name']))[0];
            Log::info('First Name', ['value' => $firstname]);
            $user_id = strtolower($firstname . '@' . $staffUserSuffix);
            Log::info('First Name userid', ['value' => $user_id]);
            $checkuserid = DB::table('user_master')
                ->where('user_id', $user_id)
                ->exists();
            if ($checkuserid == true) {
                $user_id = strtolower(str_replace(' ', '', $validatedData['name']) . '@' . $staffUserSuffix);
                $checkuseridforfullname = DB::table('user_master')
                    ->where('user_id', $user_id)
                    ->exists();
                if ($checkuseridforfullname == true) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Userid is created using staff name, please use a different name to create user id.',
                        'success' => false
                    ]);
                }
                Log::info('Full name userid', ['value' => $user_id]);
            }

            // Create User record
            $user = UserMaster::create([
                'user_id' => $user_id,
                'name' => $validatedData['name'],
                'password' => Hash::make($defaultPassword),
                'reg_id' => $teacher->teacher_id,
                'role_id' => $validatedData['role'],
                'IsDelete' => 'N',
            ]);

            if (!$user) {
                // Rollback by deleting the teacher record if user creation fails
                $teacher->delete();
                DB::rollBack();  // Rollback the transaction
                return response()->json([
                    'message' => 'Failed to create user',
                ], 500);
            }
            $emailData = [
                'schoolname' => $schoolName,
                'websiteurl' => $websiteUrl,
                'userid' => $user_id,
                'defaultpassword' => $defaultPassword
            ];

            smart_mail($validatedData['email'], 'Welcome', 'emails.welcome', $emailData);

            $response = createStaffUser($user->user_id, $validatedData['role']);

            if ($response->successful()) {
                DB::commit();

                return response()->json([
                    'message' => 'Staff created. Your user id is ' . $user_id . ' and password is ' . $defaultPassword . '.',
                    'teacher' => $teacher,
                    'user' => $user,
                    'external_api_response' => $response->json(),
                ], 201);
            } else {
                DB::rollBack();  // Rollback the transaction
                return response()->json([
                    'message' => 'Teacher and user created, but external API call failed',
                    'external_api_error' => $response->body(),
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();  // Rollback the transaction on validation error
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Handle unexpected errors
            if (isset($teacher) && $teacher->exists) {
                // Rollback by deleting the teacher record if an unexpected error occurs
                $teacher->delete();
            }
            DB::rollBack();  // Rollback the transaction
            return response()->json([
                'message' => 'An error occurred while creating the teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStaff(Request $request, $id)
    {
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        DB::beginTransaction();  // Start the transaction

        try {
            $messages = [
                'name.required' => 'The name field is mandatory.',
                'birthday.required' => 'The birthday field is required.',
                'date_of_joining.required' => 'The date of joining is required.',
                'email.required' => 'The email field is required.',
                'email.email' => 'The email must be a valid email address.',
                'email.unique' => 'The email has already been taken.',
                'phone.required' => 'The phone number is required.',
                'phone.max' => 'The phone number cannot exceed 15 characters.',
                'aadhar_card_no.unique' => 'The Aadhar card number has already been taken.',
                'teacher_image_name.string' => 'The file must be an image.',
                'role.required' => 'The role field is required.',
                'employee_id.unique' => 'The Employee Id field should be unique.',
                'employee_id.required' => 'The Employee Id field is required.'
            ];

            $validatedData = $request->validate([
                'employee_id' => 'required|integer|unique:teacher,employee_id,' . $id . ' ,teacher_id',
                'name' => 'required|string|max:255',
                'birthday' => 'required|date',
                'date_of_joining' => 'required|date',
                'sex' => 'required|string|max:10',
                'religion' => 'nullable|string|max:255',
                'blood_group' => 'nullable|string|max:10',
                'address' => 'required|string|max:255',
                'phone' => 'required|string|max:15',
                // 'email' => 'required|string|email|max:255|unique:teacher,email,' . $id . ',teacher_id',
                'email' => 'required|string|email',
                'designation' => 'nullable|string|max:255',
                'academic_qual' => 'nullable|array',
                'academic_qual.*' => 'nullable|string|max:255',
                'professional_qual' => 'nullable|string|max:255',
                'special_sub' => 'nullable|string|max:255',
                'trained' => 'nullable|string|max:255',
                'experience' => 'nullable|string|max:255',
                'aadhar_card_no' => 'nullable|string',
                'teacher_image_name' => 'nullable|string',  // Base64 string
                'tc_id' => 'nullable|string|max:255',
                'emergency_phone' => 'nullable|string|max:10',
                'permanent_address' => 'nullable|string|max:255',
                // 'role' => 'required|string|max:255',
            ], $messages);

            if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
                $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
            }

            $staff = Teacher::findOrFail($id);

            // Get the existing image URL for comparison
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

                            $filename = $id . '.' . $type;
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
                            upload_teacher_profile_image_into_folder($id, $filename, $doc_type_folder, $base64File);

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

            $teacher = Teacher::findOrFail($id);
            $teacher->fill($validatedData);
            $teacher->updated_by = $user->reg_id;
            if (!$teacher->save()) {
                DB::rollBack();  // Rollback the transaction
                return response()->json([
                    'message' => 'Failed to update teacher',
                ], 500);
            }

            // Update user associated with the teacher
            $user = User::where('reg_id', $teacher->teacher_id)->first();
            if ($user) {
                DB::table('user_master')
                    ->where('reg_id', $teacher->teacher_id)
                    ->whereNotIn('role_id', ['S', 'P', 'M'])
                    ->update([
                        'name' => $validatedData['name']
                    ]);
            }

            DB::commit();  // Commit the transaction

            return response()->json([
                'message' => 'Teacher updated successfully!',
                'teacher' => $teacher,
                'user' => $user,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();  // Rollback the transaction on validation error
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Handle unexpected errors
            if (isset($teacher) && $teacher->exists) {
                // Keep teacher record but return an error
            }
            DB::rollBack();  // Rollback the transaction
            return response()->json([
                'message' => 'An error occurred while updating the teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteStaff($id)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $teacher = Teacher::findOrFail($id);
            $teacher->isDelete = 'Y';
            $teacher->deleted_by = $user->reg_id;
            if ($teacher->save()) {
                $user = UserMaster::where('reg_id', $id)->first();
                Log::info($user);
                $user_id = $user->user_id;
                $role = $user->role_id;
                Log::info($user_id);
                Log::info($role);

                $deletestaff = delete_staff_user_id($user_id, $role);
                if ($user) {
                    DB::table('user_master')
                        ->where('reg_id', $id)
                        ->whereNotIn('role_id', ['S', 'P'])
                        ->delete();
                }

                return response()->json([
                    'message' => 'Teacher marked as deleted successfully!',
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Failed to mark teacher as deleted',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while marking the teacher as deleted',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Methods for  Subject Master  API
    public function getSubjects(Request $request)
    {
        $subjects = SubjectMaster::all();
        return response()->json($subjects);
    }

    public function checkSubjectName(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:30',
            'subject_type' => 'required|string|max:30',
        ]);

        $name = $validatedData['name'];
        $subjectType = $validatedData['subject_type'];

        // Check if the combination of name and subject_type exists
        $exists = SubjectMaster::whereRaw('LOWER(name) = ? AND LOWER(subject_type) = ?', [strtolower($name), strtolower($subjectType)])->exists();

        return response()->json(['exists' => $exists]);
    }

    public function storeSubject(Request $request)
    {
        $messages = [
            'name.required' => 'The name field is required.',
            // 'name.unique' => 'The name has already been taken.',
            'subject_type.required' => 'The subject type field is required.',
            'subject_type.unique' => 'The subject type has already been taken.',
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:30',
                    // Rule::unique('subject_master', 'name')
                ],
                'subject_type' => [
                    'required',
                    'string',
                    'max:255'
                ],
            ], $messages);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $subject = new SubjectMaster();
        $subject->name = $validatedData['name'];
        $subject->subject_type = $validatedData['subject_type'];
        $subject->save();

        return response()->json([
            'status' => 201,
            'message' => 'Subject created successfully',
        ], 201);
    }

    public function updateSubject(Request $request, $id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $subjectType = $request->subject_type;

        $messages = [
            'name.required' => 'The name field is required.',
            // 'name.unique' => 'The name has already been taken.',
            'subject_type.required' => 'The subject type field is required.',
            // 'subject_type.unique' => 'The subject type has already been taken.',
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:30',
                    Rule::unique('subject_master')
                        ->ignore($id, 'sm_id')
                        ->where(function ($query) use ($subjectType) {
                            $query->where('subject_type', $subjectType);
                        })
                ],
                'subject_type' => [
                    'required',
                    'string',
                    'max:255'
                ],
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $subject = SubjectMaster::find($id);

        if (!$subject) {
            return response()->json([
                'status' => 404,
                'message' => 'Subject not found',
            ], 404);
        }

        $subject->name = $validatedData['name'];
        $subject->subject_type = $validatedData['subject_type'];
        $subject->save();

        return response()->json([
            'status' => 200,
            'message' => 'Subject updated successfully',
        ], 200);
    }

    public function editSubject($id)
    {
        $subject = SubjectMaster::find($id);

        if (!$subject) {
            return response()->json([
                'status' => 404,
                'message' => 'Subject not found',
            ]);
        }

        return response()->json($subject);
    }

    public function deleteSubject($id)
    {
        $subjectCount = DB::table('subject')->where('sm_id', $id)->count();

        // If subject is in use
        if ($subjectCount > 0) {
            return response()->json([
                'error' => 'This subject is in use. Deletion failed!'
            ], 400);  // Return a 400 Bad Request with an error message
        }

        $subject = SubjectMaster::find($id);

        if (!$subject) {
            return response()->json([
                'status' => 404,
                'message' => 'Subject not found',
            ]);
        }
        $subjectAllotmentExists = SubjectAllotment::where('sm_id', $id)->exists();
        if ($subjectAllotmentExists) {
            return response()->json([
                'status' => 400,
                'message' => 'Subject cannot be deleted because it is associated with other records.',
            ]);
        }
        $subject->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Subject deleted successfully',
            'success' => true
        ]);
    }

    public function getStudentListBaseonClass(Request $request)
    {
        $Studentz = Student::count();

        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        $Student = Student::where('academic_yr', $academicYr)->get();

        return response()->json(
            [
                'Studentz' => $Studentz,
                'Student' => $Student,
            ]
        );
    }

    // get the sections list with the student count
    public function getallSectionsWithStudentCount(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $divisions = Division::with('getClass')
            ->withCount(['students' => function ($query) use ($academicYr) {
                $query->distinct()->where('academic_yr', $academicYr);
            }])
            ->where('academic_yr', $academicYr)
            ->orderBy('class_id', 'ASC')
            ->orderBy('section_id', 'ASC')
            ->get();
        return response()->json($divisions);
    }

    public function getallSectionsWithDummyStudentCount(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $sql = "
        SELECT 
            x.*,
            (
                SELECT COUNT(*) 
                FROM student s 
                WHERE s.class_id = x.class_id
                AND s.section_id = x.section_id
                  AND s.academic_yr = ?
                  AND s.isDelete = 'N'
            ) as students_count
        FROM (
            
            SELECT 
                class.class_id, 
                section.section_id, 
                class.name AS classname, 
                section.name AS sectionname 
            FROM class 
            JOIN section 
                ON class.class_id = section.class_id 
            WHERE class.academic_yr = ? 
              AND section.academic_yr = ?

            UNION

            SELECT 
                class.class_id, 
                section.section_id, 
                class.name AS classname, 
                section.name AS sectionname 
            FROM class, section 
            WHERE section.class_id IS NULL 
              AND class.academic_yr = ?

        ) AS x
        ORDER BY x.class_id ASC, x.section_id ASC
    ";

        $data = DB::select($sql, [
            $academicYr,  // for student count
            $academicYr,
            $academicYr,
            $academicYr
        ]);

        return response()->json($data);
    }

    public function getStudentListBySection(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $sectionId = $request->query('section_id');

        // Fetch the student list along with necessary relationships
        $query = Student::with(['parents', 'userMaster', 'getClass', 'getDivision'])
            ->where('academic_yr', $academicYr)
            ->distinct()
            ->where('student.IsDelete', 'N')
            ->where('student.parent_id', '!=', 0);

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        // Retrieve students with order by roll number
        $students = $query->orderBy('roll_no')->get();

        // Append image URLs for each student
        $students->each(function ($student) {
            // Check if the image_name is present and not empty
            if (!empty($student->image_name)) {
                // Generate the full URL for the student image based on their unique image_name
                $student->image_name = $student->image_name;
            } else {
                // Set a default image if no image is available
                $student->image_name = 'default.png';
            }

            $contactDetails = ContactDetails::find($student->parent_id);
            // echo $student->parent_id."<br/>";
            if ($contactDetails === null) {
                $student->SetToReceiveSMS = '';
            } else {
                $student->SetToReceiveSMS = $contactDetails->phone_no;
            }

            $userMaster = UserMaster::where('role_id', 'P')
                ->where('reg_id', $student->parent_id)
                ->first();
            if ($userMaster === null) {
                $student->SetEmailIDAsUsername = '';
            } else {
                $student->SetEmailIDAsUsername = $userMaster->user_id;
            }
        });

        return response()->json([
            'students' => $students,
        ]);
    }

    public function getStudentListBySectionData(Request $request)
    {
        try {
            $payload = getTokenPayload($request);
            $academicYr = $payload->get('academic_year');
            $sectionId = $request->query('section_id');
            if (!$sectionId) {
                $student = DB::table('student')
                    ->join('class', 'class.class_id', '=', 'student.class_id')
                    ->join('section', 'section.section_id', '=', 'student.section_id')
                    ->where('student.academic_yr', $academicYr)
                    ->where('isDelete', 'N')
                    ->where('parent_id', '!=', 0)
                    ->select('student.student_id', 'student.first_name', 'student.mid_name', 'student.last_name', 'student.class_id', 'student.section_id', 'class.name as classname', 'section.name as sectionname')
                    ->get();
            } else {
                $student = DB::table('student')
                    ->join('class', 'class.class_id', '=', 'student.class_id')
                    ->join('section', 'section.section_id', '=', 'student.section_id')
                    ->where('student.academic_yr', $academicYr)
                    ->where('isDelete', 'N')
                    ->where('student.section_id', $sectionId)
                    ->where('parent_id', '!=', 0)
                    ->select('student.student_id', 'student.first_name', 'student.mid_name', 'student.last_name', 'student.class_id', 'student.section_id', 'class.name as classname', 'section.name as sectionname')
                    ->get();
            }

            return response()->json([
                'status' => 200,
                'message' => 'Student Information',
                'data' => $student,
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);  // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function getStudentListByClassSectionData(Request $request)
    {
        try {
            $payload = getTokenPayload($request);
            $academicYr = $payload->get('academic_year');
            $classId = $request->query('class_id');
            $sectionId = $request->query('section_id');
            if (!$sectionId) {
                $student = DB::table('student')
                    ->join('class', 'class.class_id', '=', 'student.class_id')
                    ->join('section', 'section.section_id', '=', 'student.section_id')
                    ->where('student.academic_yr', $academicYr)
                    ->where('isDelete', 'N')
                    ->where('parent_id', '!=', 0)
                    ->select('student.student_id', 'student.first_name', 'student.mid_name', 'student.last_name', 'student.class_id', 'student.section_id', 'class.name as classname', 'section.name as sectionname')
                    ->get();
            } else {
                $student = DB::table('student')
                    ->join('class', 'class.class_id', '=', 'student.class_id')
                    ->join('section', 'section.section_id', '=', 'student.section_id')
                    ->where('student.academic_yr', $academicYr)
                    ->where('isDelete', 'N')
                    ->where('student.class_id', $classId)
                    ->where('student.section_id', $sectionId)
                    ->where('parent_id', '!=', 0)
                    ->select('student.student_id', 'student.first_name', 'student.mid_name', 'student.last_name', 'student.class_id', 'student.section_id', 'class.name as classname', 'section.name as sectionname')
                    ->get();
            }

            return response()->json([
                'status' => 200,
                'message' => 'Student Information',
                'data' => $student,
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);  // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    //  get the student list by there id  with the parent details
    // public function getStudentById($studentId)
    // {
    //     $student = Student::with(['parents','userMaster', 'getClass', 'getDivision'])->find($studentId);

    //     if (!$student) {
    //         return response()->json(['error' => 'Student not found'], 404);
    //     }

    //     return response()->json(
    //         ['students' => [$student]]
    //     );
    // }

    public function getStudentById($studentId)
    {
        $student = Student::with(['parents', 'userMaster', 'getClass', 'getDivision'])->find($studentId);

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Append the image URL for the student
        if (!empty($student->image_name)) {
            // Generate the full URL for the student image based on their unique image_name
            $student->image_name = asset('storage/uploads/student_image/' . $student->image_name);
        } else {
            // Set a default image if no image is available
            $student->image_name = asset('storage/uploads/student_image/default.png');
        }

        return response()->json(
            ['students' => [$student]]
        );
    }

    public function getStudentsList(Request $request)
    {
        set_time_limit(300);
        $section_id = $request->section_id;
        $class_id = $request->class_id;
        $student_id = $request->student_id;
        $reg_no = $request->reg_no;
        $user = $this->authenticateUser();
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $query = Student::query();

        $query->with(['parents', 'userMaster', 'getClass', 'getDivision']);

        if ($class_id && $section_id && $reg_no) {
            $query
                ->where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('reg_no', $reg_no)
                ->where('isDelete', 'N')
                ->where('academic_yr', $academicYr)
                ->where('parent_id', '!=', '0');
        } elseif ($student_id && $reg_no) {
            $query
                ->where('student_id', $student_id)
                ->where('reg_no', $reg_no)
                ->where('isDelete', 'N')
                ->where('academic_yr', $academicYr)
                ->where('parent_id', '!=', '0');
        } elseif ($class_id && $section_id && $student_id && $reg_no) {
            $query
                ->where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('student_id', $student_id)
                ->where('reg_no', $reg_no)
                ->where('isDelete', 'N')
                ->where('academic_yr', $academicYr)
                ->where('parent_id', '!=', '0');
        } elseif ($class_id && $section_id && $student_id) {
            $query
                ->where('class_id', $class_id)
                ->where('student_id', $student_id)
                ->where('section_id', $section_id)
                ->where('isDelete', 'N')
                ->where('academic_yr', $academicYr)
                ->where('parent_id', '!=', '0');
        } elseif ($class_id && $section_id) {
            $query->where('section_id', $section_id)->where('class_id', $class_id)->where('isDelete', 'N')->where('academic_yr', $academicYr)->where('parent_id', '!=', '0');
        } elseif ($student_id) {
            $query->where('student_id', $student_id)->where('isDelete', 'N')->where('academic_yr', $academicYr)->where('parent_id', '!=', '0');
        } elseif ($reg_no) {
            $query->where('reg_no', $reg_no)->where('isDelete', 'N')->where('academic_yr', $academicYr)->where('parent_id', '!=', '0');
            if ($user->role_id == 'T') {
                $teacherSubjects = DB::table('subject')
                    ->select('class_id', 'section_id')
                    ->where('teacher_id', $user->reg_id)
                    ->where('academic_yr', $academicYr)
                    ->get();

                $classIds = $teacherSubjects->pluck('class_id')->unique()->toArray();
                $sectionIds = $teacherSubjects->pluck('section_id')->unique()->toArray();

                if (!empty($classIds) && !empty($sectionIds)) {
                    $query
                        ->whereIn('class_id', $classIds)
                        ->whereIn('section_id', $sectionIds);
                } else {
                    return response()->json([
                        'status' => 402,
                        'message' => 'No assigned classes found',
                        'success' => false
                    ]);
                }
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Please provide at least one search condition.',
            ], 400);
        }
        $query->orderBy('roll_no', 'asc');
        $students = $query->get();
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

        // Append image URLs for each student
        $students->each(function ($student) use ($parent_app_url, $codeigniter_app_url) {
            // Check if the image_name is present and not empty
            $concatprojecturl = $codeigniter_app_url . '' . 'uploads/student_image/';
            if (!empty($student->image_name)) {
                $student->image_name = $concatprojecturl . '' . $student->image_name;
            } else {
                $student->image_name = '';
            }

            $contactDetails = ContactDetails::find($student->parent_id);
            // echo $student->parent_id."<br/>";
            if ($contactDetails === null) {
                $student->SetToReceiveSMS = '';
            } else {
                $student->SetToReceiveSMS = $contactDetails->phone_no;
            }

            $userMaster = UserMaster::where('role_id', 'P')
                ->where('reg_id', $student->parent_id)
                ->first();
            if ($userMaster === null) {
                $student->SetEmailIDAsUsername = '';
            } else {
                $student->SetEmailIDAsUsername = $userMaster->user_id;
            }

            $lastAddressChange = DB::table('permanent_address_change_log')
                ->where('student_id', $student->student_id)
                ->orderBy('changed_at', 'desc')
                ->first();

            $student->last_permanent_address_change = $lastAddressChange;
        });

        if ($students->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No student found.',
            ], 404);
        }

        $students->transform(function ($student) {
            if (isset($student->religion)) {
                // Force proper camel case (first letter lowercase)
                $student->religion = ucfirst(strtolower($student->religion));
            }
            return $student;
        });

        return response()->json([
            'status' => 'success',
            'students' => $students,
        ]);
    }

    // public function getStudentByGRN($reg_no)
    // {
    //     try {
    //         $user = $this->authenticateUser();
    //         $customClaims = JWTAuth::getPayload()->get('academic_year');
    //         $globalVariables = App::make('global_variables');
    //         $parent_app_url = $globalVariables['parent_app_url'];
    //         $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
    //         $student = Student::with(['parents.user', 'getClass', 'getDivision'])
    //             ->where('reg_no', $reg_no)
    //             ->where('academic_yr', $customClaims)
    //             ->first();

    //         if (!$student) {
    //             return response()->json(['error' => 'Student not found'], 404);
    //         }
    //         $concatprojecturl = $codeigniter_app_url . 'uploads/student_image/';
    //         $student->student_image_url = $student->image_name
    //             ? $concatprojecturl . $student->image_name
    //             : null;
    //         return response()->json(['student' => [$student]]);
    //     } catch (Exception $e) {
    //         \Log::error($e);
    //         return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    //     }
    // }

    public function getStudentByGRN($reg_no)
    {
        try {
            $user = $this->authenticateUser();

            $customClaims = JWTAuth::getPayload()->get('academic_year');

            $globalVariables = App::make('global_variables');
            $parent_app_url = $globalVariables['parent_app_url'];
            $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

            // ============================================
            // GET USER ROLE
            // ============================================
            $role_id = $user->role_id ?? null;

            // ============================================
            // ADMIN / MANAGEMENT USERS
            // A = Admin
            // M = Management
            // U = User
            // ============================================
            if (in_array($role_id, ['A', 'M', 'U'])) {
                $student = Student::with([
                    'parents.user',
                    'getClass',
                    'getDivision'
                ])
                    ->where('reg_no', $reg_no)
                    ->where('academic_yr', $customClaims)
                    ->first();
            }
            // ============================================
            // TEACHER LOGIN
            // T = Teacher
            // Teacher can only search students
            // from classes/sections they teach
            // ============================================
            else if ($role_id == 'T') {
                // Teacher ID from logged-in user
                $teacher_id = $user->reg_id;

                // ============================================
                // CHECK STUDENT EXISTS OR NOT
                // ============================================
                $studentExists = Student::where('reg_no', $reg_no)
                    ->where('academic_yr', $customClaims)
                    ->first();

                // Invalid GR No
                if (!$studentExists) {
                    return response()->json([
                        'status' => 422,
                        'success' => false,
                        'message' => 'Invalid GR No',
                        'data' => null
                    ], 422);
                }

                // ============================================
                // GET TEACHER ASSIGNED CLASSES
                // ============================================
                $teacherClasses = DB::table('subject')
                    ->leftJoin('class_teachers', function ($join) use ($teacher_id) {
                        $join
                            ->on('class_teachers.class_id', '=', 'subject.class_id')
                            ->on('class_teachers.section_id', '=', 'subject.section_id')
                            ->where('class_teachers.teacher_id', '=', $teacher_id);
                    })
                    ->where('subject.academic_yr', $customClaims)
                    ->where(function ($query) use ($teacher_id) {
                        $query
                            ->where('subject.teacher_id', $teacher_id)
                            ->orWhere('class_teachers.teacher_id', $teacher_id);
                    })
                    ->select(
                        'subject.class_id',
                        'subject.section_id'
                    )
                    ->distinct()
                    ->get();

                // ============================================
                // FETCH STUDENT ONLY FROM ASSIGNED CLASS
                // ============================================
                $studentQuery = Student::with([
                    'parents.user',
                    'getClass',
                    'getDivision'
                ])
                    ->where('reg_no', $reg_no)
                    ->where('academic_yr', $customClaims);

                $studentQuery->where(function ($query) use ($teacherClasses) {
                    foreach ($teacherClasses as $class) {
                        $query->orWhere(function ($subQuery) use ($class) {
                            $subQuery
                                ->where('class_id', $class->class_id)
                                ->where('section_id', $class->section_id);
                        });
                    }
                });

                $student = $studentQuery->first();

                // ============================================
                // STUDENT EXISTS BUT NOT ACCESSIBLE
                // ============================================
                if (!$student) {
                    return response()->json([
                        'status' => 403,
                        'success' => false,
                        'message' => 'Student not found in your assigned class or section',
                        'data' => null
                    ], 403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'success' => false,
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            // ============================================
            // IMAGE URL
            // ============================================
            $concatprojecturl = $codeigniter_app_url . 'uploads/student_image/';

            $student->student_image_url = $student->image_name
                ? $concatprojecturl . $student->image_name
                : null;

            return response()->json([
                'student' => [$student]
            ]);
        } catch (Exception $e) {
            \Log::error($e);

            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteStudent(Request $request, $studentId)
    {
        // Find the student by ID
        $student = Student::find($studentId);
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Update the student's isDelete and isModify status to 'Y'
        $payload = getTokenPayload($request);
        $authUser = $payload->get('reg_id');
        $student->isDelete = 'Y';
        $student->isModify = 'Y';
        $student->deleted_by = $authUser;
        $student->deleted_date = Carbon::now();
        $student->save();

        $academicYr = $payload->get('academic_year');
        // Hard delete the student from the user_master table
        $userMaster = UserMaster::where('role_id', 'S')
            ->where('reg_id', $studentId)
            ->first();
        if ($userMaster) {
            $userMaster->delete();
        }

        // Check if the student has siblings
        $siblingsCount = Student::where('academic_yr', $academicYr)
            ->where('parent_id', $student->parent_id)
            ->where('student_id', '!=', $studentId)
            ->where('isDelete', 'N')
            ->count();

        // If no siblings are present, mark the parent as deleted in the parent table
        if ($siblingsCount == 0) {
            $parent = Parents::find($student->parent_id);
            if ($parent) {
                $parent->isDelete = 'Y';
                $parent->save();

                // Soft Delete  delete parent information from the user_master table
                $userMasterParent = UserMaster::where('role_id', 'P')
                    ->where('reg_id', $student->parent_id)
                    ->first();
                if ($userMasterParent) {
                    $userMasterParent->IsDelete = 'Y';
                    $userMasterParent->save();
                }
                $parent1 = Parents::find($student->parent_id);
                $contact = ContactDetails::find($student->parent_id);
                // After deletion, check if the deleted information exists in the deleted_contact_details table
                $deletedContact = DeletedContactDetails::where('id', $parent1)->first();
                if (!$deletedContact) {
                    // Insert deleted contact details into the deleted_contact_details table
                    DeletedContactDetails::create([
                        'student_id' => $studentId,
                        'parent_id' => $student->parent_id,
                        'phone_no' => $contact->phone_no,
                        'email_id' => $parent1->f_email,
                        'm_emailid' => $parent1->m_emailid
                    ]);
                }

                // Hard delete parent information from the contact_details table
                ContactDetails::where('id', $student->parent_id)->delete();
                $currentUserName = getParentUserId($student->parent_id);
                if ($currentUserName) {
                    $response = deleteParentUser($currentUserName);
                }
            }
        }

        return response()->json(['message' => 'Student deleted successfully']);
        // while deleting  please cll the api for the evolvu database. while sibling is not present then  call the api to delete the paret
    }

    public function toggleActiveStudent($studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Toggle isActive value
        if ($student->isActive == 'Y') {
            $student->isActive = 'N';
            $message = 'Student deactivated successfully';
        } else {
            $student->isActive = 'Y';
            $message = 'Student activated successfully';
        }
        $student->save();

        return response()->json(['message' => $message]);
    }

    public function resetPasssword($user_id)
    {
        $userinfo = $this->authenticateUser();
        $shortname = JWTAuth::getPayload()->get('short_name');
        if ($shortname == 'SACS') {
            $user = UserMaster::find($user_id);

            if (!$user) {
                return response()->json([
                    'Status' => 404,
                    'Error' => 'User Id not found'
                ]);
            }
            $userinfouserid = $userinfo->user_id;
            $userinforoleid = $userinfo->role_id;
            // dd($userinfo->user_id);
            $role_id = $user->role_id;
            $loginuserid = $userinfo->reg_id;
            if ($userinforoleid == 'A') {
                if ($role_id == 'M') {
                    return response()->json([
                        'status' => 400,
                        'message' => 'You are not authorised to change password for this user'
                    ]);
                }
                if (!$user) {
                    return response()->json([
                        'Status' => 404,
                        'Error' => 'User Id not found'
                    ]);
                }
                $customClaims = JWTAuth::getPayload()->get('academic_year');

                // 4. Get default password
                $settingsData = getSchoolSettingsData();
                $defaultPassword = $settingsData->default_pwd;

                // 5. Update password
                $user->password = Hash::make($defaultPassword);
                $user->changed_by = $userinfo->reg_id;
                $user->save();

                return response()->json([
                    'Status' => 200,
                    'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                ]);
            } else {
                if ($userinforoleid == 'M') {
                    if (!$user) {
                        return response()->json([
                            'Status' => 404,
                            'Error' => 'User Id not found'
                        ]);
                    }
                    $customClaims = JWTAuth::getPayload()->get('academic_year');

                    // 4. Get default password
                    $settingsData = getSchoolSettingsData();
                    $defaultPassword = $settingsData->default_pwd;

                    // 5. Update password
                    $user->password = Hash::make($defaultPassword);
                    $user->changed_by = $userinfo->reg_id;
                    $user->save();

                    return response()->json([
                        'Status' => 200,
                        'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                    ]);
                }

                if ($role_id == 'P') {
                    if (!$user) {
                        return response()->json([
                            'Status' => 404,
                            'Error' => 'User Id not found'
                        ]);
                    }
                    $customClaims = JWTAuth::getPayload()->get('academic_year');

                    // 4. Get default password
                    $settingsData = getSchoolSettingsData();
                    $defaultPassword = $settingsData->default_pwd;

                    // 5. Update password
                    $user->password = Hash::make($defaultPassword);
                    $user->changed_by = $userinfo->reg_id;
                    $user->save();

                    return response()->json([
                        'Status' => 200,
                        'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                    ]);
                } else {
                    if (strtolower($userinfouserid) == strtolower($user_id)) {
                        if (!$user) {
                            return response()->json([
                                'Status' => 404,
                                'Error' => 'User Id not found'
                            ]);
                        }
                        $customClaims = JWTAuth::getPayload()->get('academic_year');

                        // 4. Get default password
                        $settingsData = getSchoolSettingsData();
                        $defaultPassword = $settingsData->default_pwd;

                        // 5. Update password
                        $user->password = Hash::make($defaultPassword);
                        $user->changed_by = $userinfo->reg_id;
                        $user->save();

                        return response()->json([
                            'Status' => 200,
                            'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                        ]);
                    } else {
                        return response()->json([
                            'status' => 400,
                            'message' => 'You are not authorised to change password for this user'
                        ]);
                    }
                }
            }
        } elseif ($shortname == 'HSCS') {
            $user = UserMaster::find($user_id);
            if (!$user) {
                return response()->json([
                    'Status' => 404,
                    'Error' => 'User Id not found'
                ]);
            }
            $userinfouserid = $userinfo->user_id;
            $userinforoleid = $userinfo->role_id;
            // dd($userinfo->user_id);
            $role_id = $user->role_id;
            $loginuserid = $userinfo->reg_id;
            if ($loginuserid == '52') {
                if ($role_id == 'M') {
                    return response()->json([
                        'status' => 400,
                        'message' => 'You are not authorised to change password for this user'
                    ]);
                }
                if (!$user) {
                    return response()->json([
                        'Status' => 404,
                        'Error' => 'User Id not found'
                    ]);
                }
                $customClaims = JWTAuth::getPayload()->get('academic_year');

                // 4. Get default password
                $settingsData = getSchoolSettingsData();
                $defaultPassword = $settingsData->default_pwd;

                // 5. Update password
                $user->password = Hash::make($defaultPassword);
                $user->changed_by = $userinfo->reg_id;
                $user->save();

                return response()->json([
                    'Status' => 200,
                    'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                ]);
            } else {
                if ($userinforoleid == 'M') {
                    if (!$user) {
                        return response()->json([
                            'Status' => 404,
                            'Error' => 'User Id not found'
                        ]);
                    }
                    $customClaims = JWTAuth::getPayload()->get('academic_year');

                    // 4. Get default password
                    $settingsData = getSchoolSettingsData();
                    $defaultPassword = $settingsData->default_pwd;

                    // 5. Update password
                    $user->password = Hash::make($defaultPassword);
                    $user->changed_by = $userinfo->reg_id;
                    $user->save();

                    return response()->json([
                        'Status' => 200,
                        'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                    ]);
                }

                if ($role_id == 'P') {
                    if (!$user) {
                        return response()->json([
                            'Status' => 404,
                            'Error' => 'User Id not found'
                        ]);
                    }
                    $customClaims = JWTAuth::getPayload()->get('academic_year');

                    // 4. Get default password
                    $settingsData = getSchoolSettingsData();
                    $defaultPassword = $settingsData->default_pwd;

                    // 5. Update password
                    $user->password = Hash::make($defaultPassword);
                    $user->changed_by = $userinfo->reg_id;
                    $user->save();

                    return response()->json([
                        'Status' => 200,
                        'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                    ]);
                } else {
                    if (strtolower($userinfouserid) == strtolower($user_id)) {
                        if (!$user) {
                            return response()->json([
                                'Status' => 404,
                                'Error' => 'User Id not found'
                            ]);
                        }
                        $customClaims = JWTAuth::getPayload()->get('academic_year');

                        // 4. Get default password
                        $settingsData = getSchoolSettingsData();
                        $defaultPassword = $settingsData->default_pwd;

                        // 5. Update password
                        $user->password = Hash::make($defaultPassword);
                        $user->changed_by = $userinfo->reg_id;
                        $user->save();

                        return response()->json([
                            'Status' => 200,
                            'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                        ]);
                    } else {
                        return response()->json([
                            'status' => 400,
                            'message' => 'You are not authorised to change password for this user'
                        ]);
                    }
                }
            }
        } else {
            $user = UserMaster::find($user_id);

            if (!$user) {
                return response()->json([
                    'Status' => 404,
                    'Error' => 'User Id not found'
                ]);
            }
            $userinfouserid = $userinfo->user_id;
            $userinforoleid = $userinfo->role_id;
            // dd($userinfo->user_id);
            $role_id = $user->role_id;
            $loginuserid = $userinfo->reg_id;
            if ($userinforoleid == 'A') {
                if ($role_id == 'M') {
                    return response()->json([
                        'status' => 400,
                        'message' => 'You are not authorised to change password for this user'
                    ]);
                }
                if (!$user) {
                    return response()->json([
                        'Status' => 404,
                        'Error' => 'User Id not found'
                    ]);
                }
                $customClaims = JWTAuth::getPayload()->get('academic_year');

                // 4. Get default password
                $settingsData = getSchoolSettingsData();
                $defaultPassword = $settingsData->default_pwd;

                // 5. Update password
                $user->password = Hash::make($defaultPassword);
                $user->changed_by = $userinfo->reg_id;
                $user->save();

                return response()->json([
                    'Status' => 200,
                    'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                ]);
            } else {
                if ($userinforoleid == 'M') {
                    if (!$user) {
                        return response()->json([
                            'Status' => 404,
                            'Error' => 'User Id not found'
                        ]);
                    }
                    $customClaims = JWTAuth::getPayload()->get('academic_year');

                    // 4. Get default password
                    $settingsData = getSchoolSettingsData();
                    $defaultPassword = $settingsData->default_pwd;

                    // 5. Update password
                    $user->password = Hash::make($defaultPassword);
                    $user->changed_by = $userinfo->reg_id;
                    $user->save();

                    return response()->json([
                        'Status' => 200,
                        'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                    ]);
                }

                if ($role_id == 'P') {
                    if (!$user) {
                        return response()->json([
                            'Status' => 404,
                            'Error' => 'User Id not found'
                        ]);
                    }
                    $customClaims = JWTAuth::getPayload()->get('academic_year');

                    // 4. Get default password
                    $settingsData = getSchoolSettingsData();
                    $defaultPassword = $settingsData->default_pwd;

                    // 5. Update password
                    $user->password = Hash::make($defaultPassword);
                    $user->changed_by = $userinfo->reg_id;
                    $user->save();

                    return response()->json([
                        'Status' => 200,
                        'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                    ]);
                } else {
                    if (strtolower($userinfouserid) == strtolower($user_id)) {
                        if (!$user) {
                            return response()->json([
                                'Status' => 404,
                                'Error' => 'User Id not found'
                            ]);
                        }
                        $customClaims = JWTAuth::getPayload()->get('academic_year');

                        // 4. Get default password
                        $settingsData = getSchoolSettingsData();
                        $defaultPassword = $settingsData->default_pwd;

                        // 5. Update password
                        $user->password = Hash::make($defaultPassword);
                        $user->changed_by = $userinfo->reg_id;
                        $user->save();

                        return response()->json([
                            'Status' => 200,
                            'Message' => 'Your password has been successfully reset to ' . $defaultPassword . ' . '
                        ]);
                    } else {
                        return response()->json([
                            'status' => 400,
                            'message' => 'You are not authorised to change password for this user'
                        ]);
                    }
                }
            }
        }
    }

    public function updateStudentAndParent(Request $request, $studentId)
    {
        try {
            $payload = getTokenPayload($request);
            $academicYr = $payload->get('academic_year');
            Log::info("Starting updateStudentAndParent for student ID: {$studentId}");
            DB::enableQueryLog();

            $validatedData = $request->validate([
                // Student model fields
                'first_name' => 'nullable|string|max:100',
                'mid_name' => 'nullable|string|max:100',
                'last_name' => 'nullable|string|max:100',
                'house' => 'nullable|max:100',
                'student_name' => 'nullable|string|max:100',
                'dob' => 'nullable|date',
                'admission_date' => 'nullable|date',
                'stud_id_no' => 'nullable|string|max:25',
                'stu_aadhaar_no' => 'nullable|string|max:14',
                'gender' => 'nullable|string',
                'mother_tongue' => 'nullable|string|max:20',
                'birth_place' => 'nullable|string|max:50',
                'admission_class' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'roll_no' => 'nullable|max:11',
                'class_id' => 'nullable|integer',
                'section_id' => 'nullable|integer',
                'religion' => 'nullable|string|max:255',
                'caste' => 'nullable|string|max:100',
                'category' => 'nullable|string|max:100',
                'subcaste' => 'nullable|string|max:255',
                'vehicle_no' => 'nullable|string|max:13',
                'emergency_name' => 'nullable|string|max:100',
                'emergency_contact' => 'nullable|string|max:11',
                'emergency_add' => 'nullable|string|max:200',
                'height' => 'nullable|numeric',
                'weight' => 'nullable|numeric',
                'allergies' => 'nullable|string|max:200',
                'nationality' => 'nullable|string|max:100',
                'pincode' => 'nullable|max:11',
                'image_name' => 'nullable|string',
                'has_specs' => 'nullable|string|max:1',
                'udise_pen_no' => 'nullable|string',
                'apaar_id' => 'nullable|string|max:12',
                'reg_no' => 'nullable|string',
                'blood_group' => 'nullable|string',
                'current_address' => 'nullable|string',
                'permant_add' => 'nullable|string',
                'transport_mode' => 'nullable|string',
                // Parent model fields
                'father_name' => 'nullable|string|max:100',
                'father_occupation' => 'nullable|string|max:100',
                'f_office_add' => 'nullable|string|max:200',
                'f_office_tel' => 'nullable|string|max:11',
                'f_mobile' => 'nullable|string|max:10',
                'f_email' => 'nullable|string|max:50',
                'f_dob' => 'nullable',
                'f_blood_group' => 'nullable|string',
                'parent_adhar_no' => 'nullable|string|max:14',
                'mother_name' => 'nullable|string|max:100',
                'mother_occupation' => 'nullable|string|max:100',
                'm_office_add' => 'nullable|string|max:200',
                'm_office_tel' => 'nullable|string|max:11',
                'm_mobile' => 'nullable|string|max:10',
                'm_dob' => 'nullable',
                'm_emailid' => 'nullable|string|max:50',
                'm_adhar_no' => 'nullable|string|max:14',
                'm_blood_group' => 'nullable|string',
                // Preferences for SMS and email as username
                'SetToReceiveSMS' => 'nullable|string',
                'SetEmailIDAsUsername' => 'nullable|string',
                'address_remark' => 'nullable|string'
                // 'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
            ]);

            $validator = Validator::make($request->all(), [
                'stud_id_no' => 'nullable|string|max:255|unique:student,stud_id_no,' . $studentId . ',student_id,academic_yr,' . $academicYr,
                'stu_aadhaar_no' => 'nullable|string|max:255|unique:student,stu_aadhaar_no,' . $studentId . ',student_id,academic_yr,' . $academicYr,
                'udise_pen_no' => 'nullable|string|max:255|unique:student,udise_pen_no,' . $studentId . ',student_id,academic_yr,' . $academicYr,
                'reg_no' => 'nullable|string|max:255|unique:student,reg_no,' . $studentId . ',student_id,academic_yr,' . $academicYr,
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            Log::info("Validation passed for student ID: {$studentId}");
            Log::info("Validation passed for student ID: {$request->SetEmailIDAsUsername}");
            // echo "Validation passed for student ID: {$studentId}";
            // Convert relevant fields to uppercase
            $fieldsToUpper = [
                'first_name',
                'mid_name',
                'last_name',
                'house',
                'emergency_name',
                'emergency_contact',
                'nationality',
                'city',
                'state',
                'birth_place',
                'mother_tongue',
                'father_name',
                'mother_name',
                'vehicle_no',
                'caste'
            ];

            foreach ($fieldsToUpper as $field) {
                if (isset($validatedData[$field])) {
                    $validatedData[$field] = strtoupper(trim($validatedData[$field]));
                }
            }
            // echo "msg1";
            // Additional fields for parent model that need to be converted to uppercase
            $parentFieldsToUpper = [
                'father_name',
                'mother_name',
                'f_blood_group',
                'm_blood_group',
                'student_blood_group'
            ];
            // echo "msg2";
            foreach ($parentFieldsToUpper as $field) {
                if (isset($validatedData[$field])) {
                    $validatedData[$field] = strtoupper(trim($validatedData[$field]));
                }
            }
            // echo "msg3";
            // Retrieve the token payload
            $payload = getTokenPayload($request);
            $academicYr = $payload->get('academic_year');

            Log::info("Academic year: {$academicYr} for student ID: {$studentId}");
            // echo "msg4";
            // Find the student by ID
            $student = Student::find($studentId);
            if (!$student) {
                Log::error("Student not found: ID {$studentId}");
                return response()->json(['error' => 'Student not found'], 404);
            }
            // echo "msg5";
            // Check if specified fields have changed
            $fieldsToCheck = ['first_name', 'mid_name', 'last_name', 'class_id', 'section_id', 'roll_no'];
            $isModified = false;

            foreach ($fieldsToCheck as $field) {
                if (isset($validatedData[$field]) && $student->$field != $validatedData[$field]) {
                    $isModified = true;
                    break;
                }
            }
            // echo "msg6";
            // If any of the fields are modified, set 'is_modify' to 'Y'
            if ($isModified) {
                $validatedData['is_modify'] = 'Y';
            }

            $existingImageUrl = $student->image_name;

            if ($request->has('image_name')) {
                $newImageData = $request->input('image_name');

                // Check if the new image data is null
                if ($newImageData === null || $newImageData === 'null' || $newImageData === 'default.png') {
                    // If the new image data is null, keep the existing filename
                    $validatedData['image_name'] = $student->image_name;
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

                            // Generate a filename for the new image
                            $filename = $studentId . '.' . $type;
                            $filePath = storage_path('app/public/student_images/' . $filename);
                            $directory = dirname($filePath);
                            if (!is_dir($directory)) {
                                mkdir($directory, 0755, true);
                            }

                            // Save the new image to file
                            if (file_put_contents($filePath, $newImageData) === false) {
                                throw new \Exception('Failed to save image file');
                            }
                            $doc_type_folder = 'student_image';
                            $fileContent = file_get_contents($filePath);  // Get the file content
                            $base64File = base64_encode($fileContent);
                            upload_student_profile_image_into_folder($studentId, $filename, $doc_type_folder, $base64File);

                            // Ensure directory exists

                            // Update the validated data with the new filename
                            $validatedData['image_name'] = $filename;
                        } else {
                            throw new \Exception('Invalid image data');
                        }
                    } else {
                        // If the image is the same, keep the existing filename
                        $validatedData['image_name'] = $student->image_name;
                    }
                }
            }

            $validatedData['academic_yr'] = $academicYr;
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            // Update student information
            $oldPermanentAddress = trim((string) $student->permant_add);
            $newPermanentAddress = trim((string) ($validatedData['permant_add'] ?? ''));

            // Check if permanent address changed
            if (
                isset($validatedData['permant_add']) &&
                $oldPermanentAddress != $newPermanentAddress
            ) {
                DB::table('permanent_address_change_log')->insert([
                    'student_id' => $student->student_id,
                    'old_address' => $oldPermanentAddress,
                    'remark' => $request->address_remark,
                    'changed_by' => $user->reg_id ?? null,
                    'changed_at' => now(),
                ]);

                Log::info("Permanent address changed for student ID: {$student->student_id}");
            }
            $student->update($validatedData);
            $student->updated_by = $user->reg_id;
            $student->save();
            // echo $student->toSql();
            Log::info("Student information updated for student ID: {$studentId}");
            // echo "msg9";
            // Handle parent details if provided
            $parent = Parents::find($student->parent_id);
            // echo "msg10";
            if ($parent) {
                $parent->update($request->only([
                    'father_name',
                    'father_occupation',
                    'f_office_add',
                    'f_office_tel',
                    'f_mobile',
                    'f_email',
                    'f_blood_group',
                    'parent_adhar_no',
                    'mother_name',
                    'mother_occupation',
                    'm_office_add',
                    'm_office_tel',
                    'm_mobile',
                    'm_emailid',
                    'm_adhar_no',
                    'm_dob',
                    'f_dob',
                    'm_blood_group'
                ]));
                // echo "msg11";
                // Determine the phone number based on the 'SetToReceiveSMS' input
                $phoneNo = null;
                $setToReceiveSMS = $request->input('SetToReceiveSMS');
                if ($setToReceiveSMS == 'Father') {
                    $phoneNo = $parent->f_mobile;
                } elseif ($setToReceiveSMS == 'Mother') {
                    $phoneNo = $parent->m_mobile;
                } elseif ($setToReceiveSMS) {
                    $phoneNo = $setToReceiveSMS;
                }
                // echo "msg12";
                // Check if a record already exists with parent_id as the id
                $contactDetails = ContactDetails::find($student->parent_id);
                $phoneNo1 = $parent->f_mobile;
                if ($contactDetails) {
                    // If the record exists, update the contact details
                    $contactDetails->update([
                        'phone_no' => $phoneNo,
                        'email_id' => $parent->f_email,  // Father's email
                        'm_emailid' => $parent->m_emailid,  // Mother's email
                        'sms_consent' => 'N'  // Store consent for SMS
                    ]);
                    // echo "msg13";
                } else {
                    // If the record doesn't exist, create a new one with parent_id as the id
                    DB::insert('INSERT INTO contact_details (id, phone_no, email_id, m_emailid, sms_consent) VALUES (?, ?, ?, ?, ?)', [
                        $student->parent_id,
                        $parent->f_mobile,
                        $parent->f_email,
                        $parent->m_emailid,
                        'N'  // sms_consent
                    ]);
                    // echo "msg14";
                }

                // Update email ID as username preference
                $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();
                if ($user) {
                    $currentUserName = $user->user_id;
                    Log::info("Current Username is : {$currentUserName}");
                    Log::info("Student information updated for student ID: {$user}");

                    // $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();

                    // Conditional logic for setting email/phone based on SetEmailIDAsUsername
                    $emailOrPhoneMapping = [
                        'Father' => $parent->f_email,  // Father's email
                        'Mother' => $parent->m_emailid,  // Mother's email
                        'FatherMob' => $parent->f_mobile,  // Father's mobile
                        'MotherMob' => $parent->m_mobile,  // Mother's mobile
                    ];

                    $user->user_id = $emailOrPhoneMapping[$request->SetEmailIDAsUsername] ?? $request->SetEmailIDAsUsername;

                    Log::info($user->user_id);
                    if ($currentUserName != $user->user_id) {
                        $response = edit_user_id($user->user_id, $currentUserName);
                    }

                    if ($user->update(['user_id' => $user->user_id])) {
                        Log::info("User record updated successfully for student ID: {$student->student_id}");
                    } else {
                        Log::error("Failed to update user record for student ID: {$student->student_id}");
                    }
                }
            }

            return response()->json(['success' => 'Student and parent information updated successfully']);
        } catch (Exception $e) {
            Log::error("Exception occurred for student ID: {$studentId} - " . $e->getMessage());
            return response()->json(['error' => 'An error occurred while updating information'], 500);
        }

        // return response()->json($request->all());
    }

    // public function checkUserId($studentId, $userId)
    // {
    //     try {
    //         // Log the start of the request
    //         Log::info("Checking user ID: {$userId} for student ID: {$studentId}");

    //         // Retrieve the student record to get the parent_id
    //         $student = Student::find($studentId);
    //         if (!$student) {
    //             Log::error("Student not found: ID {$studentId}");
    //             return response()->json(['error' => 'Student not found'], 404);
    //         }

    //         $parentId = $student->parent_id;

    //         // Retrieve the user_id associated with this parent_id
    //         $parentUser = UserMaster::where('role_id', 'P')
    //             ->where('reg_id', $parentId)
    //             ->first();

    //         // return response()->json($parentUser);

    //         if (!$parentUser) {
    //             //Log::error("User not found for parent_id: {$parentId}");
    //             //return response()->json(['error' => 'User not found for the given parent ID'], 404);
    //             $savedUserId ="";
    //         }else{
    //             $savedUserId = $parentUser->user_id;
    //         }
    //         //if current user id and the user id in the database are different then check for duplicate
    //         if($userId<>$savedUserId){
    //             $userExists = UserMaster::where('user_id',$userId)
    //             ->where('role_id','P')->first();

    //             if ($userExists) {
    //                 //echo "User ID exists . Duplicate User id {$userId}".$parentId;
    //                 Log::info("User ID exists . DUplicate User id {$userId}");
    //                 return response()->json(['exists' => true], 200);
    //             } else {
    //                 //echo "User ID does not exist: {$userId}".$parentId;
    //                 Log::info("User ID does not exist: {$userId}");
    //                 return response()->json(['exists' => false], 200);
    //             }
    //         } else {
    //             //echo "Else User ID does not exist: {$userId}".$parentId;
    //             Log::info("Else User ID does not exist: {$userId}");
    //             return response()->json(['exists' => false], 200);
    //         }
    //     } catch (\Exception $e) {
    //         Log::error("Error checking user ID: " . $e->getMessage());
    //         return response()->json([
    //             'error' => 'Failed to check user ID.',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function checkUserId($studentId, $userId)
    {
        try {
            // Log the start of the request
            Log::info("Checking user ID: {$userId} for student ID: {$studentId}");

            // Retrieve the student record to get the parent_id
            $student = Student::find($studentId);
            if (!$student) {
                Log::error("Student not found: ID {$studentId}");
                return response()->json(['error' => 'Student not found'], 404);
            }

            $parentId = $student->parent_id;

            // Retrieve the user_id associated with this parent_id
            $parentUser = UserMaster::where('role_id', 'P')
                ->where('reg_id', $parentId)
                ->first();

            // If no parent user is found, set savedUserId to an empty string
            $savedUserId = $parentUser ? $parentUser->user_id : '';

            // Check if the provided userId matches the savedUserId
            if ($userId == $savedUserId) {
                // If they are the same, return false
                Log::info("User ID matches the saved user ID: {$userId}");
                return response()->json(['exists' => false], 200);
            } else {
                // If they are different, check if the userId exists in the UserMaster table
                $userExists = UserMaster::where('user_id', $userId)
                    ->exists();

                if ($userExists) {
                    // If the userId exists, return true
                    Log::info("User ID exists. Duplicate User ID: {$userId}");
                    return response()->json(['exists' => true], 200);
                } else {
                    // If the userId does not exist, return false
                    Log::info("User ID does not exist: {$userId}");
                    return response()->json(['exists' => false], 200);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error checking user ID: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to check user ID.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // get all the class and their associated Division.
    public function getallClass(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $divisions = Division::select('name', 'section_id', 'class_id')
            ->with(['getClass' => function ($query) {
                $query->select('name', 'class_id');
            }])
            ->where('academic_yr', $academicYr)
            ->distinct()
            ->orderBy('class_id')
            ->orderBy('section_id', 'asc')
            ->get();

        return response()->json($divisions);
    }

    // get all the subject allotment data base on the selected class and section
    public function getSubjectAlloted(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }

        $academicYr = $payload->get('academic_year');
        $section = $request->query('section_id');
        $query = SubjectAllotment::with('getClass', 'getDivision', 'getTeacher', 'getSubject')
            ->where('academic_yr', $academicYr);

        if (!empty($section)) {
            $query->where('section_id', $section);
        } else {
            return response()->json([]);
        }

        $subjectAllotmentList = $query
            ->orderBy('class_id', 'DESC')  // multiple section_id, sm_id
            ->get();
        return response()->json($subjectAllotmentList);
    }

    // Edit Subject Allotment base on the selectd Subject_id
    public function editSubjectAllotment(Request $request, $subjectId)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        $subjectAllotment = SubjectAllotment::with('getClass', 'getDivision', 'getTeacher', 'getSubject')
            ->where('subject_id', $subjectId)
            ->where('academic_yr', $academicYr)
            ->first();

        if (!$subjectAllotment) {
            return response()->json(['error' => 'Subject Allotment not found'], 404);
        }
        return response()->json($subjectAllotment);
    }

    // Update Subject Allotment base on the selectd Subject_id
    public function updateSubjectAllotment(Request $request, $subjectId)
    {
        $request->validate([
            'teacher_id',
        ]);

        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        $subjectAllotment = SubjectAllotment::where('subject_id', $subjectId)
            ->where('academic_yr', $academicYr)
            ->first();

        if (!$subjectAllotment) {
            return response()->json(['error' => 'Subject Allotment not found'], 404);
        }

        $subjectAllotment->teacher_id = $request->input('teacher_id');

        if ($subjectAllotment->save()) {
            return response()->json(['message' => 'Teacher updated successfully'], 200);
        }

        return response()->json(['error' => 'Failed to update Teacher'], 500);
    }

    // Delete Subject Allotment base on the selectd Subject_id
    public function deleteSubjectAllotment(Request $request, $subjectId)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $subjectAllotment = SubjectAllotment::where('subject_id', $subjectId)
            ->where('academic_yr', $academicYr)
            ->first();

        // if (!$subjectAllotment) {
        //     return response()->json(['error' => 'Subject Allotment not found'], 404);
        // }
        // $isAllocated = StudentMark::where('subject_id', $subjectAllotment->subject_id)
        //     ->exists();

        // if ($isAllocated) {
        //     return response()->json(['error' => 'Subject Allotment cannot be deleted as it is associated with student marks'], 400);
        // }

        if ($subjectAllotment->delete()) {
            return response()->json([
                'status' => 200,
                'message' => 'Subject Allotment  deleted successfully',
                'success' => true
            ]);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Error occured while deleting Subject Allotment',
            'success' => false
        ]);
    }

    // Classs list
    public function getClassList(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $classes = Classes::where('academic_yr', $academicYr)
            ->orderBy('class_id')  // order
            ->get();
        return response()->json($classes);
    }

    // get  the divisions and the subjects base on the selectd class_id
    public function getDivisionsAndSubjects(Request $request, $classId)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }

        $academicYr = $payload->get('academic_year');

        // Retrieve Class Information
        $class = Classes::find($classId);
        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        $className = $class->name;
        $hscClasses = getClassesOfADepartment('Higher Secondary');
        $hscClassIds = collect($hscClasses)->pluck('class_id')->toArray();
        // Fetch Division Names
        $divisionNames = Division::where('academic_yr', $academicYr)
            ->where('class_id', $classId)
            ->select('section_id', 'name')
            ->orderBy('name', 'asc')
            ->distinct()
            ->get();

        // Fetch Subjects Based on Class Type
        $subjects = in_array($classId, $hscClassIds)
            ? $this->getAllSubjectsOfHsc()
            : $this->getAllSubjectsNotHsc();
        $count = $subjects->count();
        // Return Combined Response
        return response()->json([
            'divisions' => $divisionNames,
            'subjects' => $subjects,
            'count' => $count
        ]);
    }

    private function getAllSubjectsOfHsc()
    {
        return SubjectMaster::whereIn('subject_type', ['Compulsory', 'Optional', 'Co-Scholastic_hsc', 'Social'])->get();
    }

    private function getAllSubjectsNotHsc()
    {
        return SubjectMaster::whereIn('subject_type', ['Scholastic', 'Social', 'Co-Scholastic'])->get();
    }

    // Save the Subject Allotment
    // public function storeSubjectAllotment(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'class_id' => 'required|exists:class,class_id',
    //         'section_ids' => 'required|array',
    //         'section_ids.*' => 'exists:section,section_id',
    //         'subject_ids' => 'required|array',
    //         'subject_ids.*' => 'exists:subject_master,sm_id',
    //     ]);

    //     $payload = getTokenPayload($request);
    //     if (!$payload) {
    //         return response()->json(['error' => 'Invalid or missing token'], 401);
    //     }
    //     $academicYr = $payload->get('academic_year');

    //     $classId = $validatedData['class_id'];
    //     $sectionIds = $validatedData['section_ids'];
    //     $subjectIds = $validatedData['subject_ids'];

    //     foreach ($sectionIds as $sectionId) {
    //         foreach ($subjectIds as $subjectId) {
    //             $existingAllotment = SubjectAllotment::where([
    //                 ['class_id', '=', $classId],
    //                 ['section_id', '=', $sectionId],
    //                 ['sm_id', '=', $subjectId],
    //                 ['academic_yr', '=', $academicYr],
    //             ])->first();

    //             if (!$existingAllotment) {
    //                 SubjectAllotment::create([
    //                     'class_id' => $classId,
    //                     'section_id' => $sectionId,
    //                     'sm_id' => $subjectId,
    //                     'academic_yr' => $academicYr,
    //                 ]);
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'message' => 'Subject allotment details stored successfully',
    //     ], 201);
    // }

    public function storeSubjectAllotment(Request $request)
    {
        try {
            Log::info('Starting subject allotment process.', ['request_data' => $request->all()]);

            // Validate the request data
            $validatedData = $request->validate([
                'class_id' => 'required|exists:class,class_id',
                'section_ids' => 'required|array',
                'section_ids.*' => 'exists:section,section_id',
                'subject_ids' => 'required|array',
                'subject_ids.*' => 'exists:subject_master,sm_id',
            ]);

            // Retrieve token payload
            $payload = getTokenPayload($request);
            if (!$payload) {
                Log::error('Invalid or missing token.', ['request_data' => $request->all()]);
                return response()->json(['error' => 'Invalid or missing token'], 401);
            }

            $academicYr = $payload->get('academic_year');

            $classId = $validatedData['class_id'];
            $sectionIds = $validatedData['section_ids'];
            $subjectIds = $validatedData['subject_ids'];

            foreach ($sectionIds as $sectionId) {
                Log::info('Processing section', ['section_id' => $sectionId]);

                // Fetch existing subject allotments for the class, section, and academic year
                $existingAllotments = SubjectAllotment::where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('academic_yr', $academicYr)
                    ->get();

                $existingSubjectIds = $existingAllotments->pluck('sm_id')->toArray();

                // Identify subject IDs that need to be removed (set to null)
                $subjectIdsToRemove = array_diff($existingSubjectIds, $subjectIds);
                Log::info('Subjects to remove', ['subject_ids_to_remove' => $subjectIdsToRemove]);

                if (!empty($subjectIdsToRemove)) {
                    // Set sm_id to null for the removed subjects
                    SubjectAllotment::where('class_id', $classId)
                        ->where('section_id', $sectionId)
                        ->where('academic_yr', $academicYr)
                        ->whereIn('sm_id', $subjectIdsToRemove)
                        ->delete();

                    Log::info('Removed subjects', ['class_id' => $classId, 'section_id' => $sectionId, 'removed_subject_ids' => $subjectIdsToRemove]);
                }

                // Add or update the subject allotments
                foreach ($subjectIds as $subjectId) {
                    $existingAllotment = SubjectAllotment::where([
                        ['class_id', '=', $classId],
                        ['section_id', '=', $sectionId],
                        ['sm_id', '=', $subjectId],
                        ['academic_yr', '=', $academicYr],
                    ])->first();

                    if (!$existingAllotment) {
                        Log::info('Creating new subject allotment', [
                            'class_id' => $classId,
                            'section_id' => $sectionId,
                            'subject_id' => $subjectId,
                            'academic_year' => $academicYr,
                        ]);

                        SubjectAllotment::create([
                            'class_id' => $classId,
                            'section_id' => $sectionId,
                            'sm_id' => $subjectId,
                            'academic_yr' => $academicYr,
                        ]);
                    } else {
                        Log::info('Subject allotment already exists', [
                            'class_id' => $classId,
                            'section_id' => $sectionId,
                            'subject_id' => $subjectId,
                            'academic_year' => $academicYr,
                        ]);
                    }
                }
            }

            Log::info('Subject allotment process completed successfully.');

            return response()->json([
                'message' => 'Subject allotment details stored successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error during subject allotment process.', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'An error occurred during the subject allotment process. Please try again later.'
            ], 500);
        }
    }

    public function getSubjectAllotmentWithTeachersBySection(Request $request, $sectionId)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $sectionExists = DB::table('subject')
            ->where('section_id', $sectionId)
            ->exists();

        if (!$sectionExists) {
            return response()->json([
                'error' => 'Subject is not Alloted for this Class.'
            ], 404);
        }

        $subjectAllotments = SubjectAllotment::with(['getSubject', 'getTeacher'])
            ->where('section_id', $sectionId)
            ->where('academic_yr', $academicYr)
            ->whereNotNull('sm_id')
            ->get()
            ->groupBy('sm_id');

        // Create a new array to hold the transformed data
        $transformedData = [];

        foreach ($subjectAllotments as $smId => $allotments) {
            // Get the first record to extract subject details (assuming all records for a sm_id have the same subject)
            $firstRecord = $allotments->first();
            $subjectName = $firstRecord->getSubject->name ?? 'Unknown Subject';

            // Transform each allotment, reducing repetition
            $allotmentDetails = $allotments->map(function ($allotment) {
                return [
                    'subject_id' => $allotment->subject_id,
                    'class_id' => $allotment->class_id,
                    'section_id' => $allotment->section_id,
                    'teacher_id' => $allotment->teacher_id,
                    'teacher' => $allotment->getTeacher ? [
                        'teacher_id' => $allotment->getTeacher->teacher_id,
                        'name' => $allotment->getTeacher->name,
                        'designation' => $allotment->getTeacher->designation,
                        'experience' => $allotment->getTeacher->experience,
                        // Add any other relevant teacher details here
                    ] : null,
                    'created_at' => $allotment->created_at,
                    'updated_at' => $allotment->updated_at,
                ];
            });

            // Add the sm_id and subject name to the transformed data
            $transformedData[$smId] = [
                'subject_name' => $subjectName,
                'details' => $allotmentDetails
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $transformedData
        ]);
    }

    // first code  working code
    public function updateTeacherAllotment(Request $request, $classId, $sectionId)
    {
        // Retrieve the incoming data
        $subjects = $request->input('subjects');  // Expecting an array of subjects with details
        $payload = getTokenPayload($request);

        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        // Step 1: Fetch existing records
        $existingRecords = SubjectAllotment::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('academic_yr', $academicYr)
            ->get();

        // Collect IDs to keep
        $idsToKeep = [];

        // Step 2: Iterate through the subjects from the input and process updates
        foreach ($subjects as $sm_id => $subjectData) {
            // Ensure sm_id is not null or empty before proceeding
            if (empty($sm_id)) {
                return response()->json(['error' => 'Invalid subject module ID (sm_id) provided.'], 400);
            }

            foreach ($subjectData['details'] as $detail) {
                // Ensure subject_id is not null or empty, otherwise generate a new subject_id
                if ($detail['subject_id'] === null) {
                    $maxSubjectId = SubjectAllotment::max('subject_id');
                    $detail['subject_id'] = $maxSubjectId ? $maxSubjectId + 1 : 1;
                }

                // Store the identifier in the list of IDs to keep
                $idsToKeep[] = [
                    'subject_id' => $detail['subject_id'],
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'teacher_id' => $detail['teacher_id'],
                    'sm_id' => $sm_id
                ];

                // Check if the subject allotment exists based on subject_id, class_id, section_id, and academic_yr
                $subjectAllotment = SubjectAllotment::where('subject_id', $detail['subject_id'])
                    ->where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('academic_yr', $academicYr)
                    ->where('sm_id', $sm_id)
                    ->first();

                if ($detail['teacher_id'] === null) {
                    if ($subjectAllotment) {
                        $subjectAllotment->delete();
                    }
                } else {
                    if ($subjectAllotment) {
                        // Update the existing record
                        $subjectAllotment->update([
                            'teacher_id' => $detail['teacher_id'],
                        ]);
                    } else {
                        // Create a new record if it doesn't exist
                        SubjectAllotment::create([
                            'subject_id' => $detail['subject_id'],
                            'class_id' => $classId,
                            'section_id' => $sectionId,
                            'teacher_id' => $detail['teacher_id'],
                            'academic_yr' => $academicYr,
                            'sm_id' => $sm_id
                        ]);
                    }
                }
            }
        }

        // Step 3: Delete records not present in the input data, but retain one record with null teacher_id if needed
        $idsToKeepArray = array_map(function ($item) {
            return implode(',', [
                $item['subject_id'],
                $item['class_id'],
                $item['section_id'],
                $item['teacher_id'],
                $item['sm_id'],
            ]);
        }, $idsToKeep);

        $groupedExistingRecords = $existingRecords->groupBy('sm_id');

        foreach ($groupedExistingRecords as $sm_id => $records) {
            $recordsToDelete = $records->filter(function ($record) use ($idsToKeepArray) {
                $recordKey = implode(',', [
                    $record->subject_id,
                    $record->class_id,
                    $record->section_id,
                    $record->teacher_id,
                    $record->sm_id,
                ]);
                return !in_array($recordKey, $idsToKeepArray);
            });

            $recordsToDelete->each->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Subject allotments updated successfully.',
        ]);
    }

    public function allotSubjects(Request $request)
    {
        $class_id = $request->input('class_id');
        $section_ids = $request->input('section_ids');
        $subject_ids = $request->input('subject_ids');
        $academic_year = '2023-2024';  // Set your academic year as needed

        Log::info('Starting subject allotment process.', [
            'request_data' => $request->all()
        ]);

        foreach ($section_ids as $section_id) {
            Log::info('Processing section', ['section_id' => $section_id]);

            // Fetch existing records
            $existing_records = SubjectAllotment::where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('academic_yr', $academic_year)
                ->whereIn('sm_id', $subject_ids)
                ->get();

            Log::info('Existing Records:', [$existing_records]);

            // Subjects to remove if any (for example purposes)
            $subject_ids_to_remove = [];  // Define logic for subjects to remove if needed
            Log::info('Subjects to remove', ['subject_ids_to_remove' => $subject_ids_to_remove]);

            foreach ($subject_ids as $subject_module_id) {
                Log::info('Processing Subject Module ID:', [$subject_module_id]);

                // Check if details exist for this subject module
                $details = $request->input("subjects.$subject_module_id.details", []);

                foreach ($details as $detail) {
                    Log::info('Processing Detail:', $detail);

                    $teacher_id = $detail['teacher_id'] ?? null;

                    // Query for existing allotment
                    $existing_allotment = SubjectAllotment::where([
                        'class_id' => $class_id,
                        'section_id' => $section_id,
                        'academic_yr' => $academic_year,
                        'sm_id' => $subject_module_id
                    ])->first();

                    if ($existing_allotment) {
                        // Update existing record
                        $updated = $existing_allotment->update(['teacher_id' => $teacher_id]);
                        Log::info('Updating Subject Allotment:', [
                            'existing_record' => $existing_allotment,
                            'updated' => $updated
                        ]);
                    } else {
                        // Create new record if it doesn't exist
                        Log::info('Creating new subject allotment', [
                            'class_id' => $class_id,
                            'section_id' => $section_id,
                            'subject_id' => $subject_module_id,
                            'academic_year' => $academic_year
                        ]);

                        SubjectAllotment::create([
                            'class_id' => $class_id,
                            'section_id' => $section_id,
                            'sm_id' => $subject_module_id,
                            'teacher_id' => $teacher_id,
                            'academic_yr' => $academic_year
                        ]);
                    }
                }
            }
        }

        Log::info('Subject allotment process completed successfully.');

        return response()->json(['message' => 'Subject allotment completed successfully.']);
    }

    private function determineSubjectId($academicYr, $smId, $teacherId, $existingTeacherRecords)
    {
        Log::info('Determining subject_id', [
            'academic_year' => $academicYr,
            'sm_id' => $smId,
            'teacher_id' => $teacherId
        ]);

        $existingRecord = $existingTeacherRecords->firstWhere('teacher_id', $teacherId);
        if ($existingRecord) {
            Log::info('Reusing existing subject_id', ['subject_id' => $existingRecord->subject_id]);
            return $existingRecord->subject_id;
        }

        $newSubjectId = SubjectAllotment::max('subject_id') + 1;
        Log::info('Generated new subject_id', ['subject_id' => $newSubjectId]);

        return $newSubjectId;
    }

    // Allot teacher Tab APIs
    public function getTeacherNames(Request $request)
    {
        $teacherList = UserMaster::Where('role_id', 'T')->where('IsDelete', 'N')->get();
        return response()->json($teacherList);
    }

    // Get the divisions list base on the selected Class
    public function getDivisionsbyClass(Request $request, $classId)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        // Retrieve Class Information
        $class = Classes::find($classId);
        // $className = $class->name;
        // Fetch Division Names
        $divisionNames = Division::where('academic_yr', $academicYr)
            ->where('class_id', $classId)
            ->select('section_id', 'name')
            ->orderBy('section_id', 'asc')
            ->distinct()
            ->get();

        // Return Combined Response
        return response()->json([
            'divisions' => $divisionNames,
        ]);
    }

    public function getDivisionswithDummybyClass(Request $request, $classId)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        // Retrieve Class Information
        $class = Classes::find($classId);
        // $className = $class->name;
        // Fetch Division Names
        $divisionNames = Division::where(function ($q) use ($classId, $academicYr) {
            $q
                ->where(function ($q1) use ($classId, $academicYr) {
                    $q1
                        ->where('class_id', $classId)
                        ->where('academic_yr', $academicYr);
                })
                ->orWhereNull('class_id');
        })
            ->select('section_id', 'name')
            ->orderBy('section_id', 'asc')
            ->distinct()
            ->get();

        // Return Combined Response
        return response()->json([
            'divisions' => $divisionNames,
        ]);
    }

    // Get the Subject list base on the Division
    public function getSubjectsbyDivision(Request $request, $sectionId)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        // Retrieve Division Information
        $division = Division::find($sectionId);
        if (!$division) {
            return response()->json(['error' => '']);
        }

        // Fetch Class Information based on the division
        $class = Classes::find($division->class_id);
        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        $className = $class->name;
        $hscClasses = getClassesOfADepartment('Higher Secondary');
        $hscClassIds = collect($hscClasses)->pluck('class_id')->toArray();
        // Determine subjects based on class name
        $subjects = in_array($classId, $hscClassIds)
            ? $this->getAllSubjectsNotHsc()
            : $this->getAllSubjectsOfHsc();

        // Return Combined Response
        return response()->json([
            'subjects' => $subjects
        ]);
    }

    public function getPresignSubjectByDivision(Request $request, $classId)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }

        $academicYr = $payload->get('academic_year');

        // Retrieve section_id(s) from the query parameters
        $sectionIds = $request->query('section_id', []);

        // Ensure sectionIds is an array
        if (!is_array($sectionIds)) {
            return response()->json(['error' => 'section_id must be an array'], 400);
        }

        $subjects = SubjectAllotment::with('getSubject')
            ->select('sm_id', DB::raw('MAX(subject_id) as subject_id'))  // Aggregate subject_id if needed
            ->where('academic_yr', $academicYr)
            ->where('class_id', $classId)
            ->whereNotNull('sm_id')
            ->whereIn('section_id', $sectionIds)
            ->groupBy('sm_id')
            ->get();

        $count = $subjects->count();

        return response()->json([
            'subjects' => $subjects,
            'count' => $count
        ]);
    }

    public function getPresignSubjectByTeacher(Request $request, $classID, $sectionId, $teacherID)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        $subjects = SubjectAllotment::with('getSubject')
            ->select('sm_id', 'subject_id')
            ->where('academic_yr', $academicYr)
            ->where('class_id', $classID)
            ->where('section_id', $sectionId)
            ->where('teacher_id', $teacherID)
            ->groupBy('sm_id', 'subject_id')
            ->get();
        return response()->json([
            'subjects' => $subjects
        ]);
    }

    // public function updateOrCreateSubjectAllotments($class_id, $section_id, Request $request)
    // {
    //     $payload = getTokenPayload($request);
    //     if (!$payload) {
    //         return response()->json(['error' => 'Invalid or missing token'], 401);
    //     }
    //     $academicYr = $payload->get('academic_year');
    //     $validatedData = $request->validate([
    //         'subjects' => 'required|array',
    //         'subjects.*.sm_id' => 'required|integer|exists:subject_master,sm_id',
    //         'subjects.*.teacher_id' => 'nullable|integer|exists:teacher,teacher_id',
    //         'subjects.*.subject_id' => 'nullable|integer|exists:subject,subject_id',
    //     ]);

    //     $subjects = $validatedData['subjects'];

    //     foreach ($subjects as $subjectData) {
    //         if (isset($subjectData['subject_id'])) {
    //             // Update existing record
    //             SubjectAllotment::updateOrCreate(
    //                 [
    //                     'subject_id' => $subjectData['subject_id'],
    //                     'class_id' => $class_id,
    //                     'section_id' => $section_id,
    //                     'academic_yr' => $academicYr,

    //                 ],
    //                 [
    //                     'sm_id' => $subjectData['sm_id'],
    //                     'teacher_id' => $subjectData['teacher_id'],
    //                 ]
    //             );
    //         } else {
    //             // Create new record
    //             SubjectAllotment::updateOrCreate(
    //                 [
    //                     'class_id' => $class_id,
    //                     'section_id' => $section_id,
    //                     'sm_id' => $subjectData['sm_id'],
    //                     'academic_yr' => $academicYr,

    //                 ],
    //                 [
    //                     'teacher_id' => $subjectData['teacher_id'],
    //                 ]
    //             );
    //         }
    //     }

    //     return response()->json(['success' => 'Subject allotments updated or created successfully']);
    // }

    public function updateOrCreateSubjectAllotments($class_id, $section_id, Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        // $validatedData = $request->validate([
        //     'subjects' => 'required|array',
        //     'subjects.*.sm_id' => 'required|integer|exists:subject_master,sm_id',
        //     'subjects.*.teacher_id' => 'nullable|integer|exists:teacher,teacher_id',
        //     'subjects.*.subject_id' => 'nullable|integer|exists:subject,subject_id',
        // ]);

        $subjects = $request->subjects;

        // Get existing subject allotments for the class, section, and academic year
        $existingAllotments = SubjectAllotment::where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('academic_yr', $academicYr)
            ->get()
            ->keyBy('sm_id');  // Use sm_id as the key for easy comparison

        $inputSmIds = collect($subjects)->pluck('sm_id')->toArray();
        $existingSmIds = $existingAllotments->pluck('sm_id')->toArray();

        // Iterate through the input subjects and update or create records
        foreach ($subjects as $subjectData) {
            if (!array_key_exists('teacher_id', $subjectData)) {
                continue;
            }

            if (empty($subjectData['teacher_id'])) {
                continue;
            }

            SubjectAllotment::updateOrCreate(
                [
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'sm_id' => $subjectData['sm_id'],
                    'academic_yr' => $academicYr,
                ],
                [
                    'teacher_id' => $subjectData['teacher_id']
                ]
            );
        }

        // Handle extra records in the existing allotments that are not in the input
        $extraSmIds = array_diff($existingSmIds, $inputSmIds);
        // foreach ($extraSmIds as $extraSmId) {
        //     $existingAllotments[$extraSmId]->update(['teacher_id' => null]);
        // }

        return response()->json(['success' => 'Subject allotments updated or created successfully']);
    }

    // Metods for the Subject for report card
    public function getSubjectsForReportCard(Request $request)
    {
        $subjects = SubjectForReportCard::orderBy('sequence', 'asc')->get();
        return response()->json(
            ['subjects' => $subjects]
        );
    }

    public function checkSubjectNameForReportCard(Request $request)
    {
        $validatedData = $request->validate([
            'sequence' => 'required|string|max:30',
        ]);

        $sequence = $validatedData['sequence'];
        // return response()->json($sequence);
        // $exists = SubjectForReportCard::where(DB::raw('LOWER(sequence)'), strtolower($sequence))->exists();
        $exists = SubjectForReportCard::where('sequence', $sequence)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function storeSubjectForReportCard(Request $request)
    {
        $messages = [
            'name.required' => 'The name field is required.',
            'sequence.required' => 'The sequence field is required.',
            'name.unique' => 'The name should be unique.',
            'sequence.unique' => 'The sequence should be unique',
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:30',
                    'unique:subjects_on_report_card_master,name'
                ],
                'sequence' => [
                    'required',
                    'Integer',
                    'unique:subjects_on_report_card_master,sequence'
                ],
            ], $messages);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $subject = new SubjectForReportCard();
        $subject->name = $validatedData['name'];
        $subject->sequence = $validatedData['sequence'];
        $subject->save();

        return response()->json([
            'status' => 201,
            'message' => 'Subject created successfully',
        ], 201);
    }

    // public function updateSubjectForReportCard(Request $request, $sub_rc_master_id)
    //     {
    //         $messages = [
    //             'name.required' => 'The name field is required.',
    //             // 'name.unique' => 'The name has already been taken.',
    //             'sequence.required' => 'The sequence field is required.',
    //             // 'subject_type.unique' => 'The subject type has already been taken.',
    //         ];

    //         try {
    //             $validatedData = $request->validate([
    //                 'name' => [
    //                     'required',
    //                     'string',
    //                     'max:30',
    //                 ],
    //                 'sequence' => [
    //                     'required',
    //                     'Integer'

    //                 ],
    //             ], $messages);
    //         } catch (\Illuminate\Validation\ValidationException $e) {
    //             return response()->json([
    //                 'status' => 422,
    //                 'errors' => $e->errors(),
    //             ], 422);
    //         }

    //         $subject = SubjectForReportCard::find($sub_rc_master_id);

    //         if (!$subject) {
    //             return response()->json([
    //                 'status' => 404,
    //                 'message' => 'Subject not found',
    //             ], 404);
    //         }

    //         $subject->name = $validatedData['name'];
    //         $subject->sequence = $validatedData['sequence'];
    //         $subject->save();

    //         return response()->json([
    //             'status' => 200,
    //             'message' => 'Subject updated successfully',
    //         ], 200);
    //     }

    public function updateSubjectForReportCard(Request $request, $sub_rc_master_id)
    {
        $messages = [
            'name.required' => 'The name field is required.',
            'sequence.required' => 'The sequence field is required.',
            'sequence.unique' => 'The sequence has already been taken.',
            'name.unique' => 'The name has already been taken.'
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:30',
                    Rule::unique('subjects_on_report_card_master', 'name')->ignore($sub_rc_master_id, 'sub_rc_master_id')
                ],
                'sequence' => [
                    'required',
                    'integer',
                    // Ensures the sequence is unique, but ignores the current record's sequence
                    Rule::unique('subjects_on_report_card_master', 'sequence')->ignore($sub_rc_master_id, 'sub_rc_master_id')
                ],
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        // Find the subject by sub_rc_master_id
        $subject = SubjectForReportCard::find($sub_rc_master_id);

        if (!$subject) {
            return response()->json([
                'status' => 404,
                'message' => 'Subject not found',
            ], 404);
        }

        // Update the subject with validated data
        $subject->name = $validatedData['name'];
        $subject->sequence = $validatedData['sequence'];
        $subject->save();

        return response()->json([
            'status' => 200,
            'message' => 'Subject updated successfully',
        ], 200);
    }

    public function editSubjectForReportCard($sub_rc_master_id)
    {
        $subject = SubjectForReportCard::find($sub_rc_master_id);

        if (!$subject) {
            return response()->json([
                'status' => 404,
                'message' => 'Subject not found',
            ]);
        }

        return response()->json($subject);
    }

    public function deleteSubjectForReportCard($sub_rc_master_id)
    {
        $subject = DB::table('subjects_on_report_card')->where('sub_rc_master_id', $sub_rc_master_id)->count();
        // dd($subject);
        if ($subject > 0) {
            return response()->json([
                'error' => 'This subject is in use. Deletion failed!'
            ], 400);  // Return a 400 Bad Request with an error message
        }

        $subject = SubjectForReportCard::find($sub_rc_master_id);

        if (!$subject) {
            return response()->json([
                'status' => 404,
                'message' => 'Subject not found',
            ]);
        }

        // Delete condition pending
        // $subjectAllotmentExists = SubjectAllotment::where('sm_id', $id)->exists();
        // if ($subjectAllotmentExists) {
        //     return response()->json([
        //         'status' => 400,
        //         'message' => 'Subject cannot be deleted because it is associated with other records.',
        //     ]);
        // }
        $subject->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Subject deleted successfully',
            'success' => true
        ]);
    }

    // Method for Subject Allotment for the report Card

    public function getSubjectAllotmentForReportCard(Request $request, $class_id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $subjectAllotments = SubjectAllotmentForReportCard::where('academic_yr', $academicYr)
            ->where('class_id', $class_id)
            ->with('getSubjectsForReportCard', 'getClases')
            ->get();

        return response()->json([
            'subjectAllotments' => $subjectAllotments,
        ]);
    }

    // for Edit
    public function getSubjectAllotmentById($sub_reportcard_id)
    {
        $subjectAllotment = SubjectAllotmentForReportCard::where('sub_reportcard_id', $sub_reportcard_id)
            ->with('getSubjectsForReportCard')
            ->first();

        if (!$subjectAllotment) {
            return response()->json(['error' => 'Subject Allotment not found'], 404);
        }

        return response()->json([
            'subjectAllotment' => $subjectAllotment,
        ]);
    }

    // for update
    public function updateSubjectType(Request $request, $sub_reportcard_id)
    {
        $subjectAllotment = SubjectAllotmentForReportCard::find($sub_reportcard_id);
        if (!$subjectAllotment) {
            return response()->json(['error' => 'Subject Allotment not found'], 404);
        }

        $request->validate([
            'subject_type' => 'required|string',
        ]);
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $subjectAllotment->subject_type = $request->input('subject_type');
        $subjectAllotment->academic_yr = $academicYr;

        $subjectAllotment->save();

        return response()->json(['message' => 'Subject type updated successfully']);
    }

    // for delete
    public function deleteSubjectAllotmentforReportcard($sub_reportcard_id)
    {
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $subjectAllotment = SubjectAllotmentForReportCard::find($sub_reportcard_id);
        if (!$subjectAllotment) {
            return response()->json(['error' => 'Subject Allotment not found'], 404);
        }
        $markHeadingsQuery = Allot_mark_headings::where([
            'sm_id' => $subjectAllotment->sub_rc_master_id,
            'class_id' => $subjectAllotment->class_id,
            'academic_yr' => $customClaims
        ])->first();

        if ($markHeadingsQuery) {
            // Marks allotment exists, do not allow deletion
            return response()->json([
                'status' => '400',
                'message' => 'This subject allotment is in use. Delete failed!',
                'success' => false
            ]);
        }

        // // Check if the subject allotment is associated with any MarkHeading
        // $isAssociatedWithMarkHeading = MarksHeadings::where('sub_reportcard_id', $sub_reportcard_id)->exists();

        // if ($isAssociatedWithMarkHeading) {
        //     return response()->json(['error' => 'Cannot delete: Subject allotment is associated with a Mark Heading'], 400);
        // }

        // Hard delete the subject allotment
        $subjectAllotment->delete();

        return response()->json(['message' => 'Subject allotment deleted successfully']);
    }

    // for the Edit
    public function editSubjectAllotmentforReportCard(Request $request, $class_id, $subject_type)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        // Fetch the list of subjects for the selected class_id and subject_type
        $subjectAllotments = SubjectAllotmentForReportCard::where('academic_yr', $academicYr)
            ->where('class_id', $class_id)
            ->where('subject_type', $subject_type)
            ->with('getSubjectsForReportCard')  // Include subject details
            ->get();

        // Check if subject allotments are found
        if ($subjectAllotments->isEmpty()) {
            return response()->json([]);
        }

        return response()->json([
            'message' => 'Subject allotments retrieved successfully',
            'subjectAllotments' => $subjectAllotments,
        ]);
    }

    public function createOrUpdateSubjectAllotment(Request $request, $class_id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');  // Get academic year from token payload

        // Validate the request parameters
        $request->validate([
            'subject_type' => 'required|string',
            'subject_ids' => 'array',
            'subject_ids.*' => 'integer',
        ]);

        // Log the incoming request
        Log::info('Received request to create/update subject allotment', [
            'class_id' => $class_id,
            'subject_type' => $request->input('subject_type'),
            'subject_ids' => $request->input('subject_ids'),
            'academic_yr' => $academicYr,  // Log the academic year for reference
        ]);

        // Fetch existing subject allotments
        $existingAllotments = SubjectAllotmentForReportCard::where('class_id', $class_id)
            ->where('subject_type', $request->input('subject_type'))
            ->where('academic_yr', $academicYr)  // Ensure academic year is considered
            ->get();

        Log::info('Fetched existing subject allotments', ['existingAllotments' => $existingAllotments]);

        $existingSubjectIds = $existingAllotments->pluck('sub_rc_master_id')->toArray();
        $inputSubjectIds = $request->input('subject_ids');

        $newSubjectIds = array_diff($inputSubjectIds, $existingSubjectIds);
        $deallocateSubjectIds = array_diff($existingSubjectIds, $inputSubjectIds);
        $updateSubjectIds = array_intersect($inputSubjectIds, $existingSubjectIds);

        Log::info('Comparison results', [
            'newSubjectIds' => $newSubjectIds,
            'updateSubjectIds' => $updateSubjectIds,
            'deallocateSubjectIds' => $deallocateSubjectIds
        ]);

        // Create new allotments
        foreach ($newSubjectIds as $subjectId) {
            SubjectAllotmentForReportCard::create([
                'class_id' => $class_id,
                'sub_rc_master_id' => $subjectId,
                'subject_type' => $request->input('subject_type'),
                'academic_yr' => $academicYr,  // Set academic year
            ]);

            Log::info('Created new subject allotment', [
                'class_id' => $class_id,
                'sub_rc_master_id' => $subjectId,
                'subject_type' => $request->input('subject_type'),
                'academic_yr' => $academicYr,
            ]);
        }

        // Update existing allotments
        foreach ($updateSubjectIds as $subjectId) {
            $allotment = SubjectAllotmentForReportCard::where('class_id', $class_id)
                ->where('subject_type', $request->input('subject_type'))
                ->where('academic_yr', $academicYr)  // Ensure academic year is considered
                ->where('sub_rc_master_id', $subjectId)
                ->first();

            Log::info('Fetched allotment for update', [
                'allotment' => $allotment
            ]);

            if ($allotment) {
                $allotment->sub_rc_master_id = $subjectId;
                $allotment->academic_yr = $academicYr;  // Update academic year
                $allotment->save();

                Log::info('Updated subject allotment', [
                    'class_id' => $class_id,
                    'sub_rc_master_id' => $subjectId,
                    'subject_type' => $request->input('subject_type'),
                    'academic_yr' => $academicYr
                ]);
            } else {
                Log::warning('Subject allotment not found for update', [
                    'class_id' => $class_id,
                    'sub_rc_master_id' => $subjectId,
                    'subject_type' => $request->input('subject_type')
                ]);
                return response()->json(['error' => 'Subject Allotment not found'], 404);
            }
        }

        // Deallocate subjects
        foreach ($deallocateSubjectIds as $subjectId) {
            $allotment = SubjectAllotmentForReportCard::where('class_id', $class_id)
                ->where('subject_type', $request->input('subject_type'))
                ->where('academic_yr', $academicYr)  // Ensure academic year is considered
                ->where('sub_rc_master_id', $subjectId)
                ->first();

            Log::info('Fetched allotment for deallocation', [
                'allotment' => $allotment
            ]);

            if ($allotment) {
                $allotment->delete();

                Log::info('Deallocated subject allotment', [
                    'class_id' => $class_id,
                    'sub_rc_master_id' => $subjectId,
                    'subject_type' => $request->input('subject_type'),
                    'academic_yr' => $academicYr
                ]);
            } else {
                Log::warning('Subject allotment not found for deallocation', [
                    'class_id' => $class_id,
                    'sub_rc_master_id' => $subjectId,
                    'subject_type' => $request->input('subject_type')
                ]);
                return response()->json(['error' => 'Subject Allotment not found'], 404);
            }
        }

        Log::info('Subject allotments updated successfully for class_id', ['class_id' => $class_id, 'academic_yr' => $academicYr]);

        return response()->json(['message' => 'Subject allotments updated successfully']);
    }

    public function getNewStudentListbysectionforregister(Request $request, $section_id)
    {
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $studentList = Student::with('getClass', 'getDivision')
            ->where('section_id', $section_id)
            ->where('parent_id', '0')
            ->where('IsDelete', 'N')
            ->where('isNew', 'Y')
            ->where('academic_yr', $customClaims)
            ->distinct()
            ->get();

        return response()->json($studentList);
    }

    public function getAllNewStudentListForRegister(Request $request)
    {
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $studentList = Student::with('getClass', 'getDivision')
            ->where('parent_id', '0')
            ->where('IsDelete', 'N')
            ->where('isNew', 'Y')
            ->where('academic_yr', $customClaims)
            ->distinct()
            ->get();

        return response()->json($studentList);
    }

    public function downloadCsvTemplateWithData(Request $request, $section_id)
    {
        // Extract the academic year from the token payload
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');

        // Fetch only the necessary fields from the Student model where academic year and section_id match
        $students = Student::select(
            'student_id as student_id',  // Specify the table name
            'first_name as *First Name',
            'mid_name as Mid name',
            'last_name as last name',
            'gender as *Gender',
            'dob as dob',  // Normal field name for DOB
            'stu_aadhaar_no as *Student Aadhaar No.',
            'udise_pen_no as Udise Pen No.',
            'apaar_id as Apaar ID No.',
            'mother_tongue as Mother Tongue',
            'religion as Religion',
            'blood_group as *Blood Group',
            'caste as caste',
            'subcaste as Sub Caste',
            'class.name as Class',
            'section.name as Division',
            'mother_name as *Mother Name',
            'mother_occupation as Mother Occupation',
            'm_mobile as *Mother Mobile No.(Only Indian Numbers)',
            'm_emailid as *Mother Email-Id',  // Assuming you have this field
            'father_name as *Father Name',  // Assuming you have this field
            'father_occupation as Father Occupation',  // Assuming you have this field
            'f_mobile as *Father Mobile No.(Only Indian Numbers)',  // Assuming you have this field
            'f_email as *Father Email-Id',
            'm_adhar_no as *Mother Aadhaar No.',
            'parent_adhar_no as *Father Aadhaar No.',
            'permant_add as *Address',
            'city as *City',
            'state as *State',
            'admission_date as admission_date',
            'reg_no as *GRN No'
        )
            ->distinct()
            ->leftJoin('parent', 'student.parent_id', '=', 'parent.parent_id')
            ->leftJoin('section', 'student.section_id', '=', 'section.section_id')  // Use correct table name 'sections'
            ->leftJoin('class', 'student.class_id', '=', 'class.class_id')  // Use correct table name 'sections'
            ->where('student.parent_id', '=', '0')
            ->where('student.isNew', '=', 'Y')
            ->where('student.isDelete', 'N')
            ->where('student.academic_yr', $customClaims)  // Specify the table name here
            ->where('student.section_id', $section_id)  // Specify the table name here
            ->get()
            ->toArray();

        foreach ($students as &$student) {
            // Format DOB (Date of Birth) to dd/mm/yyyy
            if (!empty($student['dob'])) {
                $student['dob'] = \Carbon\Carbon::parse($student['dob'])->format('d/m/Y');
            }

            // Format Admission Date (DOA) to dd/mm/yyyy
            if (!empty($student['admission_date'])) {
                $student['admission_date'] = \Carbon\Carbon::parse($student['admission_date'])->format('d/m/Y');
            }
        }

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
            '*GRN No',
        ];

        $callback = function () use ($columns, $students) {
            $file = fopen('php://output', 'w');

            // Write the header row
            fputcsv($file, $columns);

            // Write each student's data below the headers
            foreach ($students as $student) {
                $student['*Father Aadhaar No.'] = " ' " . (string) $student['*Father Aadhaar No.'] . " ' ";
                $student['*Mother Aadhaar No.'] = " ' " . (string) $student['*Mother Aadhaar No.'] . " ' ";
                $student['*Student Aadhaar No.'] = " ' " . (string) $student['*Student Aadhaar No.'] . " ' ";
                $student['*Mother Mobile No.(Only Indian Numbers)'] = " ' " . (string) $student['*Mother Mobile No.(Only Indian Numbers)'] . " ' ";
                $student['*Father Mobile No.(Only Indian Numbers)'] = " ' " . (string) $student['*Father Mobile No.(Only Indian Numbers)'] . " ' ";
                $student['dob'] = " ' " . (string) $student['dob'] . " ' ";
                $student['admission_date'] = " ' " . (string) $student['admission_date'] . " ' ";

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
        $header = array_shift($rows);  // Extract the header row

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

    public function downloadCsvRejected($id)
    {
        $filePath = storage_path('app/public/csv_rejected/' . $id);
        $file = fopen($filePath, 'r');

        if ($file) {
            return Response::stream(function () use ($file) {
                // Output each line of the remote CSV file
                while (!feof($file)) {
                    echo fgets($file);
                }

                fclose($file);  // Close the file after reading
            }, 200, [
                'Content-Type' => 'text/csv',  // Set the content type as CSV
                'Content-Disposition' => 'attachment; filename="rejectedrows.csv"',  // Set the file name for download
            ]);
        } else {
            return response()->json(['error' => 'File not found'], 404);
        }
    }

    // Helper method to validate date format
    private function validateDate($date, $format)
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public function deleteNewStudent(Request $request, $studentId)
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
        $parent = Parents::select([
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
            'parent.m_emailid',
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

        $parent->each(function ($student) {
            //

            $contactDetails = ContactDetails::find($student->parent_id);
            // echo $student->parent_id."<br/>";
            if ($contactDetails === null) {
                $student->SetToReceiveSMS = '';
            } else {
                $student->SetToReceiveSMS = $contactDetails->phone_no;
            }

            $userMaster = UserMaster::where('role_id', 'P')
                ->where('reg_id', $student->parent_id)
                ->first();

            if ($userMaster === null) {
                $student->SetEmailIDAsUsername = '';
            } else {
                $student->SetEmailIDAsUsername = $userMaster->user_id;
            }
        });

        return response()->json(['parent' => $parent, 'success' => true]);
    }

    // Changed on 08-10-24 Lija M
    // public function updateNewStudentAndParentData(Request $request, $studentId, $parentId)
    // {
    //     try {
    //         // Log the start of the request
    //         Log::info("Starting updateNewStudentAndParent for student ID: {$studentId}");

    //         // Validate the incoming request for all fields
    //         $validatedData = $request->validate([
    //             // Student model fields
    //             'first_name' => 'nullable|string|max:100',
    //             'mid_name' => 'nullable|string|max:100',
    //             'last_name' => 'nullable|string|max:100',

    //             'student_name' => 'nullable|string|max:100',
    //             'dob' => 'nullable|date',
    //             'gender' => 'nullable|string',
    //             'admission_date' => 'nullable|date',
    //             'stud_id_no' => 'nullable|string|max:25',
    //             'mother_tongue' => 'nullable|string|max:20',
    //             'birth_place' => 'nullable|string|max:50',
    //             'admission_class' => 'nullable|string|max:7',
    //             'roll_no' => 'nullable|max:4',
    //             'class_id' => 'nullable|integer',
    //             'section_id' => 'nullable|integer',
    //             'blood_group' => 'nullable|string|max:5',
    //             'religion' => 'nullable|string|max:100',
    //             'caste' => 'nullable|string|max:100',
    //             'subcaste' => 'nullable|string|max:100',
    //             'transport_mode' => 'nullable|string|max:100',
    //             'vehicle_no' => 'nullable|string|max:13',
    //             'emergency_name' => 'nullable|string|max:100',
    //             'emergency_contact' => 'nullable|string|max:11',
    //             'emergency_add' => 'nullable|string|max:200',
    //             'height' => 'nullable|numeric',
    //             'weight' => 'nullable|numeric',
    //             'has_specs' => 'nullable|string|max:1',
    //             'allergies' => 'nullable|string|max:200',
    //             'nationality' => 'nullable|string|max:100',
    //             'permant_add' => 'nullable|string|max:200',
    //             'city' => 'nullable|string|max:100',
    //             'state' => 'nullable|string|max:100',
    //             'pincode' => 'nullable|max:6',
    //             'reg_no' => 'nullable|max:10',
    //             'house' => 'nullable|string|max:1',
    //             'stu_aadhaar_no' => 'nullable|string|max:14',
    //             'category' => 'nullable|string|max:8',
    //             'image_name' => 'nullable|string',
    //             'udise_pen_no' => 'nullable|string|max:11',

    //             // Parent model fields
    //             'father_name' => 'nullable|string|max:100',
    //             'father_occupation' => 'nullable|string|max:100',
    //             'f_office_add' => 'nullable|string|max:200',
    //             'f_office_tel' => 'nullable|string|max:11',
    //             'f_mobile' => 'nullable|string|max:10',
    //             'f_email' => 'nullable|string|max:50',
    //             'f_dob' => 'nullable|date',
    //             'f_blood_group' => 'nullable|string|max:5',
    //             'parent_adhar_no' => 'nullable|string|max:14',
    //             'mother_name' => 'nullable|string|max:100',
    //             'mother_occupation' => 'nullable|string|max:100',
    //             'm_office_add' => 'nullable|string|max:200',
    //             'm_office_tel' => 'nullable|string|max:11',
    //             'm_mobile' => 'nullable|string|max:10',
    //             'm_emailid' => 'nullable|string|max:50',
    //             'm_dob' => 'nullable|date',
    //             'm_blood_group' => 'nullable|string|max:5',
    //             'm_adhar_no' => 'nullable|string|max:14',

    //             // Preferences for SMS and email as username
    //             'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
    //             'SetEmailIDAsUsername' => 'nullable|string',
    //             // 'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
    //         ]);

    //         Log::info("Validation passed for student ID: {$studentId}");
    //         Log::info("Validation passed for student ID: {$request->SetEmailIDAsUsername}");

    //         // Convert relevant fields to uppercase
    //         $fieldsToUpper = [
    //             'first_name', 'mid_name', 'last_name', 'house', 'emergency_name',
    //             'emergency_contact', 'nationality', 'city', 'state', 'birth_place',
    //             'mother_tongue', 'father_name', 'mother_name', 'vehicle_no', 'caste', 'blood_group'
    //         ];

    //         foreach ($fieldsToUpper as $field) {
    //             if (isset($validatedData[$field])) {
    //                 $validatedData[$field] = strtoupper(trim($validatedData[$field]));
    //             }
    //         }

    //         // Additional fields for parent model that need to be converted to uppercase
    //         $parentFieldsToUpper = [
    //             'father_name', 'mother_name', 'f_blood_group', 'm_blood_group'
    //         ];

    //         foreach ($parentFieldsToUpper as $field) {
    //             if (isset($validatedData[$field])) {
    //                 $validatedData[$field] = strtoupper(trim($validatedData[$field]));
    //             }
    //         }
    //         Log::info("student ID before trim: {$studentId}");
    //         // Retrieve the token payload
    //         $payload = getTokenPayload($request);
    //         if (!$payload) {
    //             //return response()->json(['error' => 'Invalid or missing token'], 401);
    //         }else{
    //             $academicYr = $payload->get('academic_year');
    //         }
    //         // $academicYr ='2023-2024';

    //         Log::info("Academic year: {$academicYr} for student ID: {$studentId}");

    //         // Find the student by ID
    //         $student = Student::find($studentId);
    //         if (!$student) {
    //             Log::error("Student not found: ID {$studentId}");
    //             return response()->json(['error' => 'Student not found'], 404);
    //         }

    //         // Check if specified fields have changed
    //         $fieldsToCheck = ['first_name', 'mid_name', 'last_name', 'class_id', 'section_id', 'roll_no'];
    //         $isModified = false;

    //         foreach ($fieldsToCheck as $field) {
    //             if (isset($validatedData[$field]) && $student->$field != $validatedData[$field]) {
    //                 $isModified = true;
    //                 break;
    //             }
    //         }
    //         Log::info("Message 1 {$isModified} ");
    //         // If any of the fields are modified, set 'is_modify' to 'Y'
    //         if ($isModified) {
    //             Log::info("Message 1.5 Inside if ");
    //             $validatedData['isModify'] = 'Y';
    //         }else{
    //             Log::info("Message 1.5 Inside else ");
    //             $validatedData['isModify'] = 'N';
    //         }

    //         if ($request->has('image_name')) {
    //             $newImageData = $request->input('image_name');

    //             // Check if the new image data is null
    //             if ($newImageData === null || $newImageData === 'null' || $newImageData === 'default.png') {
    //                 // If the new image data is null, keep the existing filename
    //                 $validatedData['image_name'] = 'default.png';
    //             } elseif (!empty($newImageData)) {
    //                 // Check if the new image data matches the existing image URL
    //                 if ($newImageData) {
    //                     if (preg_match('/^data:image\/(\w+);base64,/', $newImageData, $type)) {
    //                         $newImageData = substr($newImageData, strpos($newImageData, ',') + 1);
    //                         $type = strtolower($type[1]); // jpg, png, gif

    //                         if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
    //                             throw new \Exception('Invalid image type');
    //                         }

    //                         $newImageData = base64_decode($newImageData);
    //                         if ($newImageData === false) {
    //                             throw new \Exception('Base64 decode failed');
    //                         }

    //                         // Generate a filename for the new image
    //                         $filename = 'student_' . time() . '.' . $type;
    //                         $filePath = storage_path('app/public/student_images/' . $filename);

    //                         // Ensure directory exists
    //                         $directory = dirname($filePath);
    //                         if (!is_dir($directory)) {
    //                             mkdir($directory, 0755, true);
    //                         }

    //                         // Save the new image to file
    //                         if (file_put_contents($filePath, $newImageData) === false) {
    //                             throw new \Exception('Failed to save image file');
    //                         }

    //                         // Update the validated data with the new filename
    //                         $validatedData['image_name'] = $filename;
    //                     } else {
    //                         throw new \Exception('Invalid image data');
    //                     }
    //                 } else {
    //                     // If the image is the same, keep the existing filename
    //                     $validatedData['image_name'] = $student->image_name;
    //                 }
    //             }
    //                     }
    //         //Log::info("Message 2 {$validatedData['isModify']} ");
    //         // Handle student image if provided
    //         // if ($request->hasFile('student_image')) {
    //         //     $image = $request->file('student_image');
    //         //     $imageExtension = $image->getClientOriginalExtension();
    //         //     $imageName = $studentId . '.' . $imageExtension;
    //         //     $imagePath = public_path('uploads/student_image');

    //         //     if (!file_exists($imagePath)) {
    //         //         mkdir($imagePath, 0755, true);
    //         //     }

    //         //     $image->move($imagePath, $imageName);
    //         //     $validatedData['image_name'] = $imageName;
    //         //     Log::info("Image uploaded for student ID: {$studentId}");
    //         // }

    //         /*
    //         if ($request->has('image_name')) {
    //             $newImageData = $request->input('image_name');

    //             if (!empty($newImageData)) {
    //                 if (preg_match('/^data:image\/(\w+);base64,/', $newImageData, $type)) {
    //                     $newImageData = substr($newImageData, strpos($newImageData, ',') + 1);
    //                     $type = strtolower($type[1]); // jpg, png, gif

    //                     if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
    //                         throw new \Exception('Invalid image type');
    //                     }

    //                     // Decode the image
    //                     $newImageData = base64_decode($newImageData);
    //                     if ($newImageData === false) {
    //                         throw new \Exception('Base64 decode failed');
    //                     }

    //                     // Generate a unique filename
    //                     $imageName = $studentId . '.' . $type;
    //                     $imagePath = public_path('storage/uploads/student_image/' . $imageName);

    //                     // Save the image file
    //                     file_put_contents($imagePath, $newImageData);
    //                     $validatedData['image_name'] = $imageName;

    //                     Log::info("Image uploaded for student ID: {$studentId}");
    //                 } else {
    //                     throw new \Exception('Invalid image data format');
    //                 }
    //             }
    //         }
    //         */

    //         // Include academic year in the update data
    //         $validatedData['academic_yr'] = $academicYr;
    //         Log::info("Message 3 {$validatedData['academic_yr']} ");
    //         if($parentId=='0'){
    //             Log::info("Message 4 Inside if");
    //             // Update parent details if provided
    //                 // If the record doesn't exist, create a new one with parent_id as the id
    //                 $parentId = Parents::insertGetId([
    //                     'father_name' => $validatedData['father_name'],
    //                     'father_occupation' =>  $validatedData['father_occupation'],
    //                     'f_office_add' => $validatedData['f_office_add'],
    //                     'f_office_tel' => $validatedData['f_office_tel'],
    //                     'f_mobile' => $validatedData['f_mobile'],
    //                     'f_email' =>  $validatedData['f_email'] ,
    //                     'mother_name' => $validatedData['mother_name'] ,
    //                     'mother_occupation' => $validatedData['mother_occupation'] ,
    //                     'm_office_add' => $validatedData['m_office_add'] ,
    //                     'm_office_tel' => $validatedData['m_office_tel'] ,
    //                     'm_mobile' => $validatedData['m_mobile'] ,
    //                     'm_emailid' => $validatedData['m_emailid'] ,
    //                     'parent_adhar_no' => $validatedData['parent_adhar_no'] ,
    //                     'm_adhar_no' => $validatedData['m_adhar_no'] ,
    //                     'f_dob' => $validatedData['f_dob'] ,
    //                     'm_dob' => $validatedData['m_dob'],
    //                     'f_blood_group' => $validatedData['f_blood_group'] ,
    //                     'm_blood_group' => $validatedData['m_blood_group'],
    //                     'IsDelete' => 'N'
    //                 ]);
    //                 Log::info("Message 5 parentId: {$parentId} ");
    //                 // Determine the phone number based on the 'SetToReceiveSMS' input
    //                 $phoneNo = null;
    //                 if ($request->input('SetToReceiveSMS') == 'Father') {
    //                     $phoneNo = $validatedData['f_mobile'];
    //                 } elseif ($request->input('SetToReceiveSMS') == 'Mother') {
    //                     $phoneNo = $validatedData['m_mobile'];
    //                 }

    //                 // If the record doesn't exist, create a new one with parent_id as the id
    //                 DB::insert('INSERT INTO contact_details (id, phone_no, alternate_phone_no, email_id, m_emailid) VALUES (?, ?, ?, ?, ?)', [
    //                     $parentId,
    //                     $validatedData['f_mobile'],
    //                     $validatedData['m_mobile'],
    //                     $validatedData['f_email'],
    //                     $validatedData['m_emailid']  // sms_consent
    //                 ]);

    //                 Log::info("Message 6 parentId: {$parentId} ");
    //                 // Update email ID as username preference
    //                 $user = UserMaster::where('reg_id', $parentId)->where('role_id','P')->first();
    //                 Log::info("Student information updated for parent ID: {$parentId}");

    //                 // $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();

    //                 if ($user) {
    //                     switch ($request->SetEmailIDAsUsername) {
    //                         case 'Father':
    //                             $user->user_id = $parent->f_email; // Father's email
    //                             break;

    //                         case 'Mother':
    //                             $user->user_id = $parent->m_emailid; // Mother's email
    //                             break;

    //                         case 'FatherMob':
    //                             $user->user_id = $parent->f_mobile; // Father's mobile
    //                             break;

    //                         case 'MotherMob':
    //                             $user->user_id = $parent->m_mobile; // Mother's mobile
    //                             break;

    //                         default:
    //                             $user->user_id = $request->SetEmailIDAsUsername; // If the value is anything else
    //                             break;
    //                     }
    //                     Log::info("User Data saved in if");
    //                 }
    //         }else{
    //             Log::info("Parent Id: {$parentId}");
    //             // Update parent details if provided
    //             $parent = Parents::find($parentId);
    //             if ($parent) {
    //                 Log::info("msggg1");
    //                 $parent->update($request->only([
    //                     'father_name', 'father_occupation', 'f_office_add', 'f_office_tel',
    //                     'f_mobile', 'f_email', 'parent_adhar_no', 'mother_name',
    //                     'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile',
    //                     'm_emailid', 'm_adhar_no','m_dob','f_dob','f_blood_group','m_blood_group'
    //                 ]));

    //                 Log::info("msggg2");
    //                 // Determine the phone number based on the 'SetToReceiveSMS' input
    //                 $phoneNo = null;
    //                 if ($request->input('SetToReceiveSMS') == 'Father') {
    //                     $phoneNo = $parent->f_mobile;
    //                 } elseif ($request->input('SetToReceiveSMS') == 'Mother') {
    //                     $phoneNo = $parent->m_mobile;
    //                 }
    //                 Log::info("msggg3");
    //                 // Check if a record already exists with parent_id as the id
    //                 $contactDetails = ContactDetails::find($parentId);
    //                 $phoneNo1 = $parent->f_mobile;
    //                 if ($contactDetails) {
    //                     Log::info("msggg4");
    //                     // If the record exists, update the contact details
    //                     $contactDetails->update([
    //                         'phone_no' => $phoneNo,
    //                         'alternate_phone_no' => $parent->m_mobile, // Assuming alternate phone is Father's mobile number
    //                         'email_id' => $parent->f_email, // Father's email
    //                         'm_emailid' => $parent->m_emailid // Mother's email
    //                          // Store consent for SMS
    //                     ]);
    //                 } else {
    //                     Log::info("msggg5");
    //                     // If the record doesn't exist, create a new one with parent_id as the id
    //                     DB::insert('INSERT INTO contact_details (id, phone_no, alternate_phone_no, email_id, m_emailid) VALUES (?, ?, ?, ?, ?)', [
    //                         $parentId,
    //                         $parent->f_mobile,
    //                         $parent->m_mobile,
    //                         $parent->f_email,
    //                         $parent->m_emailid // sms_consent
    //                     ]);
    //                 }

    //                 // Update email ID as username preference
    //                 $user = UserMaster::where('reg_id', $parentId)->where('role_id','P')->first();
    //                 Log::info("Student information updated for student ID: {$user}");

    //                 // $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();

    //                 if ($user) {
    //                     switch ($request->SetEmailIDAsUsername) {
    //                         case 'Father':
    //                             $user->user_id = $parent->f_email; // Father's email
    //                             break;

    //                         case 'Mother':
    //                             $user->user_id = $parent->m_emailid; // Mother's email
    //                             break;

    //                         case 'FatherMob':
    //                             $user->user_id = $parent->f_mobile; // Father's mobile
    //                             break;

    //                         case 'MotherMob':
    //                             $user->user_id = $parent->m_mobile; // Mother's mobile
    //                             break;

    //                         default:
    //                             $user->user_id = $request->SetEmailIDAsUsername; // If the value is anything else
    //                             break;
    //                     }
    //                     Log::info("User saved in else");
    //                 }
    //             }

    //         }

    //         $validatedData['parent_id'] = $parentId;
    //         // Update student information
    //         $student->update($validatedData);
    //         Log::info("Finally Student information updated for student ID: {$studentId}");

    //         return response()->json(['success' => 'Student and parent information updated successfully']);
    //     } catch (Exception $e) {
    //         Log::error("Exception occurred for student ID: {$studentId} - " . $e->getMessage());
    //         return response()->json(['error' => 'An error occurred while updating information'], 500);
    //     }
    //     // return response()->json($request->all());

    // }

    public function updateNewStudentAndParentData(Request $request, $studentId, $parentId)
    {
        try {
            // Log the start of the request
            Log::info("Starting updateNewStudentAndParent for student ID: {$studentId}");

            // Validate the incoming request for all fields
            $validatedData = $request->validate([
                // Student model fields
                'first_name' => 'nullable|string|max:100',
                'mid_name' => 'nullable|string|max:100',
                'last_name' => 'nullable|string|max:100',
                'student_name' => 'nullable|string|max:100',
                'dob' => 'nullable',
                'gender' => 'nullable|string',
                'admission_date' => 'nullable',
                'stud_id_no' => 'nullable|string|max:25',
                'mother_tongue' => 'nullable|string|max:20',
                'birth_place' => 'nullable|string|max:50',
                'admission_class' => 'nullable|string|max:7',
                'roll_no' => 'nullable|max:4',
                'class_id' => 'nullable|integer',
                'section_id' => 'nullable|integer',
                'blood_group' => 'nullable|string|max:5',
                'religion' => 'nullable|string|max:100',
                'caste' => 'nullable|string|max:100',
                'subcaste' => 'nullable|string|max:100',
                'transport_mode' => 'nullable|string|max:100',
                'vehicle_no' => 'nullable|string|max:13',
                'emergency_name' => 'nullable|string|max:100',
                'emergency_contact' => 'nullable|string|max:11',
                'emergency_add' => 'nullable|string|max:200',
                'height' => 'nullable|numeric',
                'weight' => 'nullable|numeric',
                'has_specs' => 'nullable|string|max:1',
                'allergies' => 'nullable|string|max:200',
                'nationality' => 'nullable|string|max:100',
                'current_address' => 'nullable|string',
                'permant_add' => 'nullable|string|max:200',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'pincode' => 'nullable|max:6',
                'reg_no' => 'nullable|max:10',
                'house' => 'nullable',
                'stu_aadhaar_no' => 'nullable|string|max:14',
                'category' => 'nullable|string|max:8',
                'image_name' => 'nullable|string',
                'udise_pen_no' => 'nullable|string|max:11',
                'apaar_id' => 'nullable|string|max:12',
                // Parent model fields
                'father_name' => 'nullable|string|max:100',
                'father_occupation' => 'nullable|string|max:100',
                'f_office_add' => 'nullable|string|max:200',
                'f_office_tel' => 'nullable|string|max:11',
                'f_mobile' => 'nullable|string|max:10',
                'f_email' => 'nullable|string|max:50',
                'f_dob' => 'nullable|date',
                'f_blood_group' => 'nullable|string|max:5',
                'parent_adhar_no' => 'nullable|string|max:14',
                'mother_name' => 'nullable|string|max:100',
                'mother_occupation' => 'nullable|string|max:100',
                'm_office_add' => 'nullable|string|max:200',
                'm_office_tel' => 'nullable|string|max:11',
                'm_mobile' => 'nullable|string|max:10',
                'm_emailid' => 'nullable|string|max:50',
                'm_dob' => 'nullable|date',
                'm_blood_group' => 'nullable|string|max:5',
                'm_adhar_no' => 'nullable|string|max:14',
                // Preferences for SMS and email as username
                'SetToReceiveSMS' => 'nullable|string',
                'SetEmailIDAsUsername' => 'nullable|string',
                'address_remark' => 'nullable|string'
                // 'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
            ]);
            $payload = getTokenPayload($request);
            $studentdetails = DB::table('student')->where('student_id', $studentId)->first();
            $studentAcademicYr = $studentdetails->academic_yr;
            $academicYr = $payload->get('academic_year');
            $settingsData = getSchoolSettingsData();
            $schoolName = $settingsData->institute_name;
            $defaultPassword = $settingsData->default_pwd;
            $websiteUrl = $settingsData->website_url;
            $shortName = $settingsData->short_name;
            $validator = Validator::make($request->all(), [
                'stud_id_no' => 'nullable|string|max:255|unique:student,stud_id_no,' . $studentId . ',student_id,academic_yr,' . $academicYr,
                'stu_aadhaar_no' => 'nullable|string|max:255|unique:student,stu_aadhaar_no,' . $studentId . ',student_id,academic_yr,' . $academicYr,
                'udise_pen_no' => 'nullable|string|max:255|unique:student,udise_pen_no,' . $studentId . ',student_id,academic_yr,' . $academicYr,
                'reg_no' => 'nullable|string|max:255|unique:student,reg_no,' . $studentId . ',student_id,academic_yr,' . $academicYr,
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            Log::info("Validation passed for student ID: {$studentId}");
            Log::info("Validation passed for student ID: {$request->SetEmailIDAsUsername}");

            // Convert relevant fields to uppercase
            $fieldsToUpper = [
                'first_name',
                'mid_name',
                'last_name',
                'house',
                'emergency_name',
                'emergency_contact',
                'nationality',
                'city',
                'state',
                'birth_place',
                'mother_tongue',
                'father_name',
                'mother_name',
                'vehicle_no',
                'caste',
                'blood_group'
            ];

            foreach ($fieldsToUpper as $field) {
                if (isset($validatedData[$field])) {
                    $validatedData[$field] = strtoupper(trim($validatedData[$field]));
                }
            }

            // Additional fields for parent model that need to be converted to uppercase
            $parentFieldsToUpper = [
                'father_name',
                'mother_name',
                'f_blood_group',
                'm_blood_group'
            ];

            foreach ($parentFieldsToUpper as $field) {
                if (isset($validatedData[$field])) {
                    $validatedData[$field] = strtoupper(trim($validatedData[$field]));
                }
            }
            Log::info("student ID before trim: {$studentId}");
            // Retrieve the token payload
            $payload = getTokenPayload($request);
            if (!$payload) {
                // return response()->json(['error' => 'Invalid or missing token'], 401);
            } else {
                $academicYr = $payload->get('academic_year');
            }
            // $academicYr ='2023-2024';

            Log::info("Academic year: {$academicYr} for student ID: {$studentId}");

            // Find the student by ID
            $student = Student::find($studentId);
            if (!$student) {
                Log::error("Student not found: ID {$studentId}");
                return response()->json(['error' => 'Student not found'], 404);
            }

            // Check if specified fields have changed
            $fieldsToCheck = ['first_name', 'mid_name', 'last_name', 'class_id', 'section_id', 'roll_no'];
            $isModified = false;

            foreach ($fieldsToCheck as $field) {
                if (isset($validatedData[$field]) && $student->$field != $validatedData[$field]) {
                    $isModified = true;
                    break;
                }
            }
            Log::info("Message 1 {$isModified} ");
            // If any of the fields are modified, set 'is_modify' to 'Y'
            if ($isModified) {
                Log::info('Message 1.5 Inside if ');
                $validatedData['isModify'] = 'Y';
            } else {
                Log::info('Message 1.5 Inside else ');
                $validatedData['isModify'] = 'N';
            }

            if ($request->has('image_name')) {
                $newImageData = $request->input('image_name');

                // Check if the new image data is null
                if ($newImageData === null || $newImageData === 'null' || $newImageData === 'default.png') {
                    // If the new image data is null, keep the existing filename
                    $validatedData['image_name'] = 'default.png';
                } elseif (!empty($newImageData)) {
                    // Check if the new image data matches the existing image URL
                    if ($newImageData) {
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

                            // Generate a filename for the new image
                            $filename = $studentId . '.' . $type;
                            $filePath = storage_path('app/public/student_images/' . $filename);
                            $doc_type_folder = 'student_image';
                            // Ensure directory exists
                            $directory = dirname($filePath);
                            if (!is_dir($directory)) {
                                mkdir($directory, 0755, true);
                            }

                            // Save the new image to file
                            if (file_put_contents($filePath, $newImageData) === false) {
                                throw new \Exception('Failed to save image file');
                            }
                            $fileContent = file_get_contents($filePath);  // Get the file content
                            $base64File = base64_encode($fileContent);
                            upload_student_profile_image_into_folder($studentId, $filename, $doc_type_folder, $base64File);

                            // Update the validated data with the new filename
                            $validatedData['image_name'] = $filename;
                        } else {
                            throw new \Exception('Invalid image data');
                        }
                    } else {
                        // If the image is the same, keep the existing filename
                        $validatedData['image_name'] = $student->image_name;
                    }
                }
            }
            // Log::info("Message 2 {$validatedData['isModify']} ");
            // Handle student image if provided
            // if ($request->hasFile('student_image')) {
            //     $image = $request->file('student_image');
            //     $imageExtension = $image->getClientOriginalExtension();
            //     $imageName = $studentId . '.' . $imageExtension;
            //     $imagePath = public_path('uploads/student_image');

            //     if (!file_exists($imagePath)) {
            //         mkdir($imagePath, 0755, true);
            //     }

            //     $image->move($imagePath, $imageName);
            //     $validatedData['image_name'] = $imageName;
            //     Log::info("Image uploaded for student ID: {$studentId}");
            // }

            /*
             * if ($request->has('image_name')) {
             *     $newImageData = $request->input('image_name');
             *
             *     if (!empty($newImageData)) {
             *         if (preg_match('/^data:image\/(\w+);base64,/', $newImageData, $type)) {
             *             $newImageData = substr($newImageData, strpos($newImageData, ',') + 1);
             *             $type = strtolower($type[1]); // jpg, png, gif
             *
             *             if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
             *                 throw new \Exception('Invalid image type');
             *             }
             *
             *             // Decode the image
             *             $newImageData = base64_decode($newImageData);
             *             if ($newImageData === false) {
             *                 throw new \Exception('Base64 decode failed');
             *             }
             *
             *             // Generate a unique filename
             *             $imageName = $studentId . '.' . $type;
             *             $imagePath = public_path('storage/uploads/student_image/' . $imageName);
             *
             *             // Save the image file
             *             file_put_contents($imagePath, $newImageData);
             *             $validatedData['image_name'] = $imageName;
             *
             *             Log::info("Image uploaded for student ID: {$studentId}");
             *         } else {
             *             throw new \Exception('Invalid image data format');
             *         }
             *     }
             * }
             */

            // Include academic year in the update data
            $validatedData['academic_yr'] = $academicYr;
            Log::info("Message 3 {$validatedData['academic_yr']} ");
            if ($parentId == '0') {
                Log::info('Message 4 Inside if');
                // Update parent details if provided
                // If the record doesn't exist, create a new one with parent_id as the id
                $parentId = Parents::insertGetId([
                    'father_name' => $validatedData['father_name'],
                    'father_occupation' => $validatedData['father_occupation'],
                    'f_office_add' => $validatedData['f_office_add'],
                    'f_office_tel' => $validatedData['f_office_tel'],
                    'f_mobile' => $validatedData['f_mobile'],
                    'f_email' => $validatedData['f_email'],
                    'mother_name' => $validatedData['mother_name'],
                    'mother_occupation' => $validatedData['mother_occupation'],
                    'm_office_add' => $validatedData['m_office_add'],
                    'm_office_tel' => $validatedData['m_office_tel'],
                    'm_mobile' => $validatedData['m_mobile'],
                    'm_emailid' => $validatedData['m_emailid'],
                    'parent_adhar_no' => $validatedData['parent_adhar_no'],
                    'm_adhar_no' => $validatedData['m_adhar_no'],
                    'f_dob' => $validatedData['f_dob'],
                    'm_dob' => $validatedData['m_dob'],
                    'f_blood_group' => $validatedData['f_blood_group'],
                    'm_blood_group' => $validatedData['m_blood_group'],
                    'IsDelete' => 'N'
                ]);

                $parent = Parents::where('parent_id', $parentId)->first();
                Log::info("Message 5 parentId: {$parentId} ");
                // Determine the phone number based on the 'SetToReceiveSMS' input
                $phoneNo = null;
                $setToReceiveSMS = $request->input('SetToReceiveSMS');
                if ($setToReceiveSMS == 'Father') {
                    $phoneNo = $parent->f_mobile;
                    $alternatePhoneNo = $validatedData['m_mobile'];
                } elseif ($setToReceiveSMS == 'Mother') {
                    $phoneNo = $parent->m_mobile;
                    $alternatePhoneNo = $validatedData['f_mobile'];
                } elseif ($setToReceiveSMS) {
                    $phoneNo = $setToReceiveSMS;
                    $alternatePhoneNo = $validatedData['f_mobile'];
                }

                // If the record doesn't exist, create a new one with parent_id as the id
                DB::insert('INSERT INTO contact_details (id, phone_no, email_id, m_emailid) VALUES (?, ?, ?, ?)', [
                    $parentId,
                    $phoneNo,
                    $validatedData['f_email'],
                    $validatedData['m_emailid']  // sms_consent
                ]);

                Log::info("Message 6 parentId: {$parentId} ");

                // $user = UserMaster::where('reg_id', $parentId)->where('role_id','P')->first();
                Log::info("Student information updated for parent ID: {$parentId}");

                // $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();

                switch ($request->SetEmailIDAsUsername) {
                    case 'Father':
                        $user_id = $parent->f_email;
                        break;

                    case 'Mother':
                        $user_id = $parent->m_emailid;
                        break;

                    case 'FatherMob':
                        $user_id = $parent->f_mobile;
                        break;

                    case 'MotherMob':
                        $user_id = $parent->m_mobile;
                        break;

                    default:
                        $user_id = $request->SetEmailIDAsUsername;  // If the value is anything else
                        break;
                }
                // Log::info("Info",$shortName);
                $user = new UserMaster();
                $user->user_id = $user_id;
                $user->name = $validatedData['father_name'];
                $user->password = bcrypt($defaultPassword);
                $user->reg_id = $parentId;
                $user->role_id = 'P';
                $user->save();
                createUserInEvolvu($user->user_id);
                if ($studentAcademicYr == get_active_academic_year()) {
                    $templateName = 'send_user_id';
                    $parameters = [$validatedData['first_name'], $user_id];

                    $recipients = array_filter([
                        $validatedData['f_email'] ?? null,
                        $validatedData['m_emailid'] ?? null,
                    ]);

                    $messageemail = 'Dear Parent,Welcome to ' . $schoolName . " online application.'" . $validatedData['first_name'] . "' is registered in the application. Your user id is " . $user_id . ' and password is ' . $defaultPassword . ".The application can be accessed from school website by clicking 'ACEVENTURA LOGIN'. You can also directly access it at " . $websiteUrl . " .Please READ THE INSTRUCTIONS on the login page and refer to the help once you login into the application.Please make sure to update your profile and your child's profile.Regards," . $shortName . ' Support';
                    Mail::raw($messageemail, function ($mail) use ($recipients) {
                        $mail
                            ->to($recipients)
                            ->subject('Login Details');
                    });
                }

                Log::info('User Data saved in if');
            } else {
                Log::info("Parent Id: {$parentId}");
                // Update parent details if provided
                $parent = Parents::find($parentId);
                if ($parent) {
                    $phoneNo = null;
                    $setToReceiveSMS = $request->input('SetToReceiveSMS');
                    if ($setToReceiveSMS == 'Father') {
                        $phoneNo = $parent->f_mobile;
                        $alternatePhoneNo = $validatedData['m_mobile'];
                    } elseif ($setToReceiveSMS == 'Mother') {
                        $phoneNo = $parent->m_mobile;
                        $alternatePhoneNo = $validatedData['f_mobile'];
                    } elseif ($setToReceiveSMS) {
                        $phoneNo = $setToReceiveSMS;
                        $alternatePhoneNo = $validatedData['f_mobile'];
                    }
                    Log::info('msggg3');
                    // Check if a record already exists with parent_id as the id
                    $contactDetails = ContactDetails::find($parentId);
                    $phoneNo1 = $parent->f_mobile;
                    if ($contactDetails) {
                        Log::info('msggg4');
                        // If the record exists, update the contact details
                        $contactDetails->update([
                            'phone_no' => $phoneNo,
                            'email_id' => $parent->f_email,  // Father's email
                            'm_emailid' => $parent->m_emailid  // Mother's email
                            // Store consent for SMS
                        ]);
                    } else {
                        Log::info("msggg5 {$phoneNo}");
                        Log::info("msggg5 {$alternatePhoneNo}");
                        // If the record doesn't exist, create a new one with parent_id as the id
                        DB::insert('INSERT INTO contact_details (id, phone_no, email_id, m_emailid) VALUES (?, ?, ?, ?)', [
                            $parentId,
                            $phoneNo,
                            $parent->f_email,
                            $parent->m_emailid  // sms_consent
                        ]);
                    }

                    // Update email ID as username preference
                    $user = UserMaster::where('reg_id', $parentId)->where('role_id', 'P')->first();
                    Log::info("Student information updated for student ID: {$user}");

                    // $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();

                    if ($user) {
                        switch ($request->SetEmailIDAsUsername) {
                            case 'Father':
                                $user->user_id = $parent->f_email;  // Father's email
                                break;

                            case 'Mother':
                                $user->user_id = $parent->m_emailid;  // Mother's email
                                break;

                            case 'FatherMob':
                                $user->user_id = $parent->f_mobile;  // Father's mobile
                                break;

                            case 'MotherMob':
                                $user->user_id = $parent->m_mobile;  // Mother's mobile
                                break;

                            default:
                                $user->user_id = $request->SetEmailIDAsUsername;  // If the value is anything else
                                break;
                        }
                        if ($studentAcademicYr == get_active_academic_year()) {
                            $templateName = 'send_existing_user_id';
                            $parameters = [$validatedData['first_name'], $user->user_id];

                            $recipients = array_filter([
                                $parent->f_email ?? null,
                                $parent->m_emailid ?? null,
                            ]);

                            $messageemail = 'Dear Parent,<br/><br/>Welcome to ' . $schoolName . " online application. <br/><br/>'" . $validatedData['first_name'] . "' is registered in the application. Please use your existing user id " . $user->user_id . " to access the application.<br><br>Please READ THE INSTRUCTIONS on the login page and refer to the help once you login into the application.
<br/><br/>Please make sure to update your profile and your child's profile.<br/><br/>Regards,<br/>
" . $shortName . ' Support';
                            Mail::raw($messageemail, function ($mail) use ($recipients) {
                                $mail
                                    ->to($recipients)
                                    ->subject('Login Details');
                            });
                        }
                        $user->save();
                        Log::info('User saved in else');
                    }
                }
            }

            $validatedData['parent_id'] = $parentId;
            // Update student information
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $oldPermanentAddress = trim((string) $student->permant_add);
            $newPermanentAddress = trim((string) ($validatedData['permant_add'] ?? ''));

            // Check if permanent address changed
            if (
                isset($validatedData['permant_add']) &&
                $oldPermanentAddress != $newPermanentAddress
            ) {
                DB::table('permanent_address_change_log')->insert([
                    'student_id' => $student->student_id,
                    'old_address' => $oldPermanentAddress,
                    'remark' => $request->address_remark,
                    'changed_by' => $user->reg_id ?? null,
                    'changed_at' => now(),
                ]);

                Log::info("Permanent address changed for student ID: {$student->student_id}");
            }
            $student->update($validatedData);
            $student->updated_by = $user->reg_id;
            $student->save();
            $user_id = 'S' . str_pad($studentId, 4, '0', STR_PAD_LEFT);

            DB::table('user_master')->insert([
                'user_id' => $user_id,
                'name' => $validatedData['first_name'],
                'password' => bcrypt($defaultPassword),  // Consider hashing if it's a real password
                'reg_id' => $studentId,
                'role_id' => 'S',
            ]);

            Log::info("Finally Student information updated for student ID: {$studentId}");

            return response()->json(['success' => 'Student and parent information updated successfully']);
        } catch (Exception $e) {
            Log::error("Exception occurred for student ID: {$studentId} - " . $e->getMessage());
            return response()->json(['error' => 'An error occurred while updating information'], 500);
        }
        // return response()->json($request->all());
    }

    // API for the Lesson Plan Teacher  Dev Name- Manish Kumar Sharma 23-05-2025
    public function get_lesson_plan_created_teachers(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            $teachers = DB::table('lesson_plan')
                ->distinct()
                ->select('teacher.teacher_id', 'teacher.name', 'lesson_plan.reg_id')
                ->join('teacher', 'lesson_plan.reg_id', '=', 'teacher.teacher_id')
                ->join('chapters', 'lesson_plan.chapter_id', '=', 'chapters.chapter_id')
                ->join('class', 'lesson_plan.class_id', '=', 'class.class_id')
                ->where('lesson_plan.academic_yr', $customClaims)
                ->where('lesson_plan.approve', '!=', 'Y')
                ->where('chapters.isDelete', '!=', 'Y')
                ->get();
            $teachers = $teachers->map(function ($teacher) use ($customClaims) {
                $lessonCount = getPendingLessonCountForTeacher($customClaims, $teacher->teacher_id);
                $teacher->name = $teacher->name . " ({$lessonCount})";
                return $teacher;
            });
            return response()->json([
                'status' => 200,
                'data' => $teachers,
                'message' => 'Lesson Plan created teachers.',
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    // API for the Count Non Approved Lesson  Dev Name- Manish Kumar Sharma 23-05-2025
    public function getCountNonApprovedLessonPlan(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            $pending = DB::table('lesson_plan')
                ->join('chapters', 'lesson_plan.chapter_id', '=', 'chapters.chapter_id')
                ->where('chapters.isDelete', '!=', 'Y')
                ->where('lesson_plan.approve', '!=', 'Y')
                ->where('lesson_plan.academic_yr', $customClaims)
                ->count();

            return response()->json([
                'status' => 200,
                'data' => $pending,
                'message' => 'Lesson Plan created teachers.',
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    // API for the Sending whatsapp messages to late teachers Dev Name- Manish Kumar Sharma 15-06-2025
    public function sendWhatsappLateComing(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            $settingsData = getSchoolSettingsData();
            $schoolName = $settingsData->institute_name;
            $defaultPassword = $settingsData->default_pwd;
            $websiteUrl = $settingsData->website_url;
            $shortName = $settingsData->short_name;
            $whatsappIntegration = $settingsData->whatsapp_integration;
            $smsIntegration = $settingsData->sms_integration;
            $teacherids = $request->teacher_id;
            $message = $request->message;

            foreach ($teacherids as $teacherid) {
                $staffdetails = DB::table('teacher')->where('teacher_id', $teacherid)->first();
                $staffphone = $staffdetails->phone ?? null;
                // dd($staffphone);
                $templateName = 'emergency_message';
                $parameters = [ucwords(strtolower($staffdetails->name)) . ',' . $message];
                Log::info($staffphone);
                if ($staffphone) {
                    if ($whatsappIntegration == 'Y') {
                        $result = $this->whatsAppService->sendTextMessage(
                            $staffphone,
                            $templateName,
                            $parameters
                        );
                        if (isset($result['code']) && isset($result['message'])) {
                            $message_type = 'late_message_for_teacher';

                            DB::table('redington_webhook_details')->insert([
                                'wa_id' => null,
                                'phone_no' => $staffphone,
                                'message' => $message,
                                'status' => 'failed',
                                'sms_sent' => 'N',
                                'stu_teacher_id' => $teacherid,
                                'message_type' => $message_type,
                                'created_at' => now()
                            ]);
                        } else {
                            // Proceed if no error
                            $wamid = $result['messages'][0]['id'];
                            $phone_no = $result['contacts'][0]['input'];
                            $message_type = 'late_message_for_teacher';

                            DB::table('redington_webhook_details')->insert([
                                'wa_id' => $wamid,
                                'phone_no' => $phone_no,
                                'stu_teacher_id' => $teacherid,
                                'message' => $message,
                                'message_type' => $message_type,
                                'created_at' => now()
                            ]);
                        }
                        sleep(5);
                        $leftmessages = DB::table('redington_webhook_details')
                            ->where('status', 'failed')
                            ->where('sms_sent', 'N')
                            ->where('message_type', 'late_message_for_teacher')
                            ->whereDate('created_at', Carbon::today())
                            ->get();
                        foreach ($leftmessages as $leftmessage) {
                            $temp_id = '1107164450693700526';
                            $message = 'Dear Staff, ' . $message . '. Login @ ' . $websiteurl . ' for details.-EvolvU';
                            $sms_status = app('App\Http\Services\SmsService')->sendSms($leftmessage->phone_no, $message, $temp_id);
                            $messagestatus = $sms_status['data']['status'] ?? null;

                            if ($messagestatus == 'success') {
                                DB::table('redington_webhook_details')->where('webhook_id', $leftmessage->webhook_id)->update(['sms_sent' => 'Y']);
                            }
                        }
                    }
                    if ($smsIntegration == 'Y') {
                        $temp_id = '1107164450693700526';
                        $message = 'Dear Staff,' . $message . '. Login @ ' . $websiteUrl . ' for details.-EvolvU';
                        $sms_status = app('App\Http\Services\SmsService')->sendSms($staffphone, $message, $temp_id);
                    }
                }
            }

            return response()->json([
                'status' => 200,
                'message' => 'Whatsapp sended successfully.',
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    // API for the timetable view classwise Dev Name- Manish Kumar Sharma 26-06-2025
    public function Timetableviewbyteacherid($class_id, $section_id, $teacher_id)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            $timetables = DB::table('timetable')
                ->where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('academic_yr', $customClaims)
                ->orderBy('t_id')
                ->get();

            if (count($timetables) == 0) {
                $monday = [];
                $tuesday = [];
                $wednesday = [];
                $thursday = [];
                $friday = [];
                $saturday = [];
                $classwiseperiod = DB::table('classwise_period_allocation')
                    ->where('class_id', $class_id)
                    ->where('section_id', $section_id)
                    ->where('academic_yr', $customClaims)
                    ->first();

                if ($classwiseperiod === null) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Classwise Period Allocation is not done.',
                        'success' => false
                    ]);
                }

                $monfrilectures = $classwiseperiod->{'mon-fri'};
                for ($i = 1; $i <= $monfrilectures; $i++) {
                    $monday[] = [
                        'time_in' => null,
                        'period_no' => $i,
                        'time_out' => null,
                        'subject_id' => null,
                        'subject' => null,
                        'teacher' => null,
                    ];
                    $tuesday[] = [
                        'time_in' => null,
                        'period_no' => $i,
                        'time_out' => null,
                        'subject_id' => null,
                        'subject' => null,
                        'teacher' => null,
                    ];
                    $wednesday[] = [
                        'time_in' => null,
                        'period_no' => $i,
                        'time_out' => null,
                        'subject_id' => null,
                        'subject' => null,
                        'teacher' => null,
                    ];
                    $thursday[] = [
                        'time_in' => null,
                        'period_no' => $i,
                        'time_out' => null,
                        'subject_id' => null,
                        'subject' => null,
                        'teacher' => null,
                    ];
                    $friday[] = [
                        'time_in' => null,
                        'period_no' => $i,
                        'time_out' => null,
                        'subject_id' => null,
                        'subject' => null,
                        'teacher' => null,
                    ];
                }
                $satlectures = $classwiseperiod->sat;
                for ($i = 1; $i <= $satlectures; $i++) {
                    $saturday[] = [
                        'time_in' => null,
                        'period_no' => $i,
                        'time_out' => null,
                        'subject_id' => null,
                        'subject' => null,
                        'teacher' => null,
                    ];
                }

                $weeklySchedule = [
                    'mon_fri' => $monfrilectures,
                    'sat' => $satlectures,
                    'Monday' => $monday,
                    'Tuesday' => $tuesday,
                    'Wednesday' => $wednesday,
                    'Thursday' => $thursday,
                    'Friday' => $friday,
                    'Saturday' => $saturday,
                ];

                return response()->json([
                    'status' => 200,
                    'data' => $weeklySchedule,
                    'message' => 'View Timetable!',
                    'success' => true
                ]);
            }
            $monday = [];
            $tuesday = [];
            $wednesday = [];
            $thursday = [];
            $friday = [];
            $saturday = [];

            foreach ($timetables as $timetable) {
                $subjectIdmonday = null;
                $subjectIdtuesday = null;
                $subjectIdwednesday = null;
                $subjectIdthursday = null;
                $subjectIdfriday = null;
                $subjectIdsaturday = null;

                if ($timetable->monday) {
                    $subjects = [];
                    $teachers = [];

                    $entries = str_contains($timetable->monday, ',')
                        ? explode(',', $timetable->monday)
                        : [$timetable->monday];

                    foreach ($entries as $entry) {
                        if (str_contains($entry, '^')) {
                            list($subjectId, $teacherId) = explode('^', $entry);
                            if ($teacherId === $teacher_id) {
                                $subjectIdmonday = $subjectId;
                                $subjectName = $this->getSubjectnameBySubjectId($subjectId);
                                $teacherName = $this->getTeacherByTeacherIddd($teacherId);

                                $subjects[] = ['subject_name' => $subjectName];
                                $teachers[] = ['t_name' => $teacherName];
                            }
                        }
                    }

                    $monday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no' => $timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject_id' => $subjectIdmonday,
                        'subject' => $subjects,
                        'teacher' => $teachers,
                    ];
                }

                if ($timetable->tuesday) {
                    $subjects = [];
                    $teachers = [];

                    $entries = str_contains($timetable->tuesday, ',')
                        ? explode(',', $timetable->tuesday)
                        : [$timetable->tuesday];

                    foreach ($entries as $entry) {
                        if (str_contains($entry, '^')) {
                            list($subjectId, $teacherId) = explode('^', $entry);
                            if ($teacherId === $teacher_id) {
                                $subjectIdtuesday = $subjectId;
                                $subjectName = $this->getSubjectnameBySubjectId($subjectId);
                                $teacherName = $this->getTeacherByTeacherIddd($teacherId);

                                $subjects[] = ['subject_name' => $subjectName];
                                $teachers[] = ['t_name' => $teacherName];
                            }
                        }
                    }

                    $tuesday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no' => $timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject_id' => $subjectIdtuesday,
                        'subject' => $subjects,
                        'teacher' => $teachers,
                    ];
                }

                if ($timetable->wednesday) {
                    $subjects = [];
                    $teachers = [];

                    $entries = str_contains($timetable->wednesday, ',')
                        ? explode(',', $timetable->wednesday)
                        : [$timetable->wednesday];

                    foreach ($entries as $entry) {
                        if (str_contains($entry, '^')) {
                            list($subjectId, $teacherId) = explode('^', $entry);
                            if ($teacherId === $teacher_id) {
                                $subjectIdwednesday = $subjectId;
                                $subjectName = $this->getSubjectnameBySubjectId($subjectId);
                                $teacherName = $this->getTeacherByTeacherIddd($teacherId);

                                $subjects[] = ['subject_name' => $subjectName];
                                $teachers[] = ['t_name' => $teacherName];
                            }
                        }
                    }

                    $wednesday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no' => $timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject_id' => $subjectIdwednesday,
                        'subject' => $subjects,
                        'teacher' => $teachers,
                    ];
                }

                if ($timetable->thursday) {
                    $subjects = [];
                    $teachers = [];

                    $entries = str_contains($timetable->thursday, ',')
                        ? explode(',', $timetable->thursday)
                        : [$timetable->thursday];

                    foreach ($entries as $entry) {
                        if (str_contains($entry, '^')) {
                            list($subjectId, $teacherId) = explode('^', $entry);
                            if ($teacherId === $teacher_id) {
                                $subjectIdthursday = $subjectId;
                                $subjectName = $this->getSubjectnameBySubjectId($subjectId);
                                $teacherName = $this->getTeacherByTeacherIddd($teacherId);

                                $subjects[] = ['subject_name' => $subjectName];
                                $teachers[] = ['t_name' => $teacherName];
                            }
                        }
                    }

                    $thursday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no' => $timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject_id' => $subjectIdthursday,
                        'subject' => $subjects,
                        'teacher' => $teachers,
                    ];
                }

                if ($timetable->friday) {
                    $subjects = [];
                    $teachers = [];

                    $entries = str_contains($timetable->friday, ',')
                        ? explode(',', $timetable->friday)
                        : [$timetable->friday];

                    foreach ($entries as $entry) {
                        if (str_contains($entry, '^')) {
                            list($subjectId, $teacherId) = explode('^', $entry);
                            if ($teacherId === $teacher_id) {
                                $subjectIdfriday = $subjectId;
                                $subjectName = $this->getSubjectnameBySubjectId($subjectId);
                                $teacherName = $this->getTeacherByTeacherIddd($teacherId);

                                $subjects[] = ['subject_name' => $subjectName];
                                $teachers[] = ['t_name' => $teacherName];
                            }
                        }
                    }

                    $friday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no' => $timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject_id' => $subjectIdfriday,
                        'subject' => $subjects,
                        'teacher' => $teachers,
                    ];
                }

                if ($timetable->saturday) {
                    $subjects = [];
                    $teachers = [];

                    $entries = str_contains($timetable->saturday, ',')
                        ? explode(',', $timetable->saturday)
                        : [$timetable->saturday];

                    foreach ($entries as $entry) {
                        if (str_contains($entry, '^')) {
                            list($subjectId, $teacherId) = explode('^', $entry);
                            if ($teacherId === $teacher_id) {
                                $subjectIdsaturday = $subjectId;
                                $subjectName = $this->getSubjectnameBySubjectId($subjectId);
                                $teacherName = $this->getTeacherByTeacherIddd($teacherId);

                                $subjects[] = ['subject_name' => $subjectName];
                                $teachers[] = ['t_name' => $teacherName];
                            }
                        }
                    }

                    $saturday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no' => $timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject_id' => $subjectIdsaturday,
                        'subject' => $subjects,
                        'teacher' => $teachers,
                    ];
                }
            }

            $lastMondayPeriodNo = DB::table('classwise_period_allocation')->where('class_id', $class_id)->where('section_id', $section_id)->where('academic_yr', $customClaims)->first();
            $lastSaturdayPeriodNo = DB::table('classwise_period_allocation')->where('class_id', $class_id)->where('section_id', $section_id)->where('academic_yr', $customClaims)->first();

            $weeklySchedule = [
                'mon_fri' => $lastMondayPeriodNo->{'mon-fri'},
                'sat' => $lastSaturdayPeriodNo->sat,
                'Monday' => $monday,
                'Tuesday' => $tuesday,
                'Wednesday' => $wednesday,
                'Thursday' => $thursday,
                'Friday' => $friday,
                'Saturday' => $saturday,
            ];

            return response()->json([
                'status' => 200,
                'data' => $weeklySchedule,
                'message' => 'View Timetable!',
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function listAdmissionClasses(Request $request)
    {
        try {
            $user = $this->authenticateUser();

            $academicYear = JWTAuth::getPayload()->get('academic_year');

            $classes = DB::table('new_admission_class as nac')
                ->join('class as c', 'nac.class_id', '=', 'c.class_id')
                ->where('nac.academic_yr', $academicYear)
                ->select('nac.class_id', 'c.name as class_name')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Admission classes fetched successfully',
                'data' => $classes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch admission classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function indexSuccessfulPayments(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year');

            $classId = $request->query('class_id');
            $formId = $request->query('form_id');

            $query = DB::table('online_admission_form as a')
                ->join('class', 'class.class_id', '=', 'a.class_id')
                ->join('online_admfee as b', 'b.form_id', '=', 'a.form_id')
                ->where('b.status', 'S')
                ->where('a.academic_yr', $academicYear)
                ->select(
                    'a.*',
                    'b.form_id as payment_form_id',
                    'b.status as payment_status',
                    'b.payment_date',
                    'class.name as class_name'
                )
                ->orderBy('a.adm_form_pk', 'desc');

            if (!empty($classId)) {
                $query->where('a.class_id', $classId);
            }

            if (!empty($formId)) {
                $query->where('a.form_id', 'like', '%' . $formId . '%');
            }

            $admissions = $query->get();

            return response()->json([
                'status' => true,
                'message' => 'Successful admission payments fetched successfully',
                'data' => $admissions
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch successful admission payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showApplication(Request $request, $form_id)
    {
        try {
            if (!$form_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'form_id is required'
                ], 400);
            }

            $user = $this->authenticateUser();
            $application = DB::table('online_admission_form')
                ->select(
                    'online_admission_form.*',
                    'class.name as class_name',
                )
                ->leftJoin('class', 'class.class_id', '=', 'online_admission_form.class_id')
                ->where('online_admission_form.form_id', $form_id)
                ->first();

            if (!$application) {
                return response()->json([
                    'status' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            if ($application->sibling === 'Y') {
                $classSection = $application->sibling_class_id;
                $class_id = null;
                $section_id = null;
                if (!empty($classSection) && strpos($classSection, '^') !== false) {
                    [$class_id, $section_id] = explode('^', $classSection);
                }
                $sibling_id = $application->sibling_student_id;
                if (!empty($sibling_id) && ctype_digit((string) $sibling_id)) {
                    $sibling_student = DB::table('student')
                        ->where('student_id', $sibling_id)
                        ->first();
                    if ($sibling_student) {
                        $application->sibling_name =
                            trim(
                                $sibling_student->first_name . ' '
                                . $sibling_student->mid_name . ' '
                                . $sibling_student->last_name
                            );
                    }
                } else {
                    $application->sibling_name = $sibling_id;
                }

                $application->sibling_class = $class_id
                    ? DB::table('class')->where('class_id', $class_id)->first()->name
                    : null;

                $application->sibling_section = $section_id
                    ? DB::table('section')->where('section_id', $section_id)->first()->name
                    : null;
            }

            // $docTypes = [
            //     '9R',
            //     'AC',
            //     'BC',
            //     'BP',
            //     'CC',
            //     'FA',
            //     'FP',
            //     'MA',
            //     'MB',
            //     'MC',
            //     'MP',
            //     'PS',
            //     'RC',
            //     'TC',
            //     'PC'
            // ];

            // $allowedImageExt = ['gif', 'png', 'jpg', 'jpeg'];

            // $globalVariables = App::make('global_variables');
            // $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

            // $attachments = [];

            // foreach ($docTypes as $docType) {
            //     $files = DB::table('admission_upload_detail')
            //         ->where('form_id', $form_id)
            //         ->where('doc_type', $docType)
            //         ->get()
            //         ->map(function ($file) use ($allowedImageExt, $codeigniter_app_url) {
            //             $extension = strtolower(pathinfo($file->image_name, PATHINFO_EXTENSION));

            //             return [
            //                 'id' => $file->id ?? null,
            //                 'doc_type' => $file->doc_type,
            //                 'file_name' => $file->image_name,
            //                 'extension' => $extension,
            //                 'is_image' => true,
            //                 'preview_type' => in_array($extension, $allowedImageExt) ? 'image' : 'file',
            //                 'file_url' => $codeigniter_app_url
            //                     . 'uploads/admission_form/'
            //                     . $file->form_id . '/'
            //                     . $file->image_name,
            //             ];
            //         });

            //     $attachments[$docType] = $files;
            // }

            $allowedImageExt = ['gif', 'png', 'jpg', 'jpeg'];

            $globalVariables = App::make('global_variables');
            $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

            $attachments = [];

            // Fetch all uploads for this form_id at once
            // $files = DB::table('admission_upload_detail')
            //     ->where('form_id', $form_id)
            //     ->get();

            // foreach ($files as $file) {

            //     $extension = strtolower(pathinfo($file->image_name, PATHINFO_EXTENSION));

            //     $attachments[$file->doc_type][] = [
            //         'id' => $file->id ?? null,
            //         'doc_type' => $file->doc_type,
            //         'file_name' => $file->image_name,
            //         'extension' => $extension,
            //         'is_image' => true,
            //         'preview_type' => in_array($extension, $allowedImageExt) ? 'image' : 'file',
            //         'file_url' => $codeigniter_app_url
            //             . 'uploads/admission_form/'
            //             . $file->form_id . '/'
            //             . $file->image_name,
            //     ];
            // }

            $files = DB::table('admission_upload_detail')
                ->where('form_id', $form_id)
                ->get();

            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file->image_name, PATHINFO_EXTENSION));

                // Build file path relative to CI3 FCPATH
                $relativePath = 'uploads/admission_form/' . $file->form_id . '/' . $file->image_name;

                $attachments[$file->doc_type][] = [
                    'id' => $file->id ?? null,
                    'doc_type' => $file->doc_type,
                    'file_name' => $file->image_name,
                    'extension' => $extension,
                    'is_image' => true,
                    'preview_type' => in_array($extension, $allowedImageExt) ? 'image' : 'file',
                    'downloadUrl' => $codeigniter_app_url
                        . 'index.php/Admission/downloadFiles?file='
                        . urlencode($relativePath),
                    'file_url' => $codeigniter_app_url
                        . 'uploads/admission_form/'
                        . $file->form_id . '/'
                        . $file->image_name,
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Application details fetched successfully',
                'data' => [
                    'application' => $application,
                    'attachments' => $attachments
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch application details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function directFileDownload(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $short_name = JWTAuth::getPayload()->get('short_name');
            $form_id = $request->query('form_id');
            $file_name = $request->query('file_name');

            $env = config('app.env');
            $basePath = '';
            switch ($short_name) {
                case 'SACS':
                    $basePath = rtrim(config('externalapis.SACS_PATH'), '/');
                    $subPath = ($env == 'dev') ? 'SACSv4test/uploads/admission_form' : 'uploads/admission_form';
                    break;
                case 'HSCS':
                    $basePath = rtrim(config('externalapis.HSCS_PATH'), '/');
                    $subPath = ($env == 'dev') ? 'test/hscs_test/uploads/admission_form' : 'uploads/admission_form';
                    break;
                default:
                    $basePath = '/home/u333015459/domains/arnolds.evolvu.in/public_html';
                    $subPath = 'uploads/admission_form';
                    break;
            }

            $filePath = $basePath . '/' . $subPath . '/' . $form_id . '/' . $file_name;

            if (File::exists($filePath)) {
                $mime = File::mimeType($filePath);
                return response()->file($filePath, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . $file_name . '"'
                ]);
            }
            return response()->json(['error' => 'File not found.'], 404);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred: ' . $err->getMessage()], 500);
        }
    }

    public function updateApplicationStatus(Request $request, $form_id)
    {
        $status = $request->status;

        if ($status == 'Approved') {
            return response()->json([
                'message' => 'Invalid status value passed. Allowed: Applied, Hold, Reject'
            ], 400);
        }

        $form = DB::table('online_admission_form')->where('form_id', $form_id)->first();

        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => 'Form not found for the form_id',
            ], 404);
        }
        if ($status == 'Rejected') {
            $student_id = $form->student_id;
            $sibling_student_id = $form->sibling_student_id;
            $statusOLD = $form->admission_form_status;
            $parent_id = DB::table('student')
                ->where('student_id', $student_id)
                ->value('parent_id');
            if ($statusOLD == 'Approved') {
                if ($sibling_student_id != 0) {
                    // Update student
                    DB::table('student')
                        ->where('student_id', $student_id)
                        ->update([
                            'IsDelete' => 'Y',
                            'isModify' => 'Y',
                        ]);

                    // Update student user
                    DB::table('user_master')
                        ->where('reg_id', $student_id)
                        ->where('role_id', 'S')
                        ->update([
                            'IsDelete' => 'Y',
                        ]);
                } else {
                    // Update parent
                    DB::table('parent')
                        ->where('parent_id', $parent_id)
                        ->update([
                            'IsDelete' => 'Y',
                        ]);

                    // Update parent user
                    DB::table('user_master')
                        ->where('reg_id', $parent_id)
                        ->where('role_id', 'P')
                        ->update([
                            'IsDelete' => 'Y',
                        ]);

                    // Update student
                    DB::table('student')
                        ->where('student_id', $student_id)
                        ->update([
                            'IsDelete' => 'Y',
                            'isModify' => 'Y',
                        ]);

                    // Update student user
                    DB::table('user_master')
                        ->where('reg_id', $student_id)
                        ->where('role_id', 'S')
                        ->update([
                            'IsDelete' => 'Y',
                        ]);

                    // Delete contact details
                    DB::table('contact_details')
                        ->where('id', $parent_id)
                        ->delete();
                }
            }
            DB::table('online_admission_form')
                ->where('form_id', $form->form_id)
                ->update([
                    'admission_form_status' => $status
                ]);
        } else if ($status == 'Hold') {
            DB::table('online_admission_form')
                ->where('form_id', $form->form_id)
                ->update([
                    'admission_form_status' => $status
                ]);
        } else if ($status == 'Applied') {
            // check if the status is Approved then only move ahead. ( later )

            // change the form status
            DB::table('online_admission_form')
                ->where('form_id', $form_id)
                ->update([
                    'admission_form_status' => $status,
                ]);

            // soft delete the student.
            $student_id = $form->student_id;
            DB::table('student')
                ->where('student_id', $student_id)
                ->update([
                    'isDelete' => 'Y',
                ]);

            // soft delete the student from user_master.
            DB::table('user_master')
                ->where('reg_id', $student_id)
                ->update([
                    'isDelete' => 'Y',
                ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully'
        ], 200);
    }

    public function indexDocumentSubmission(Request $request)
    {
        try {
            // Authenticate user
            $user = $this->authenticateUser();

            // Get academic year from JWT
            $academicYear = JWTAuth::getPayload()->get('academic_year');

            // Build query
            $query = DB::table('online_admission_form as a')
                ->join('online_admfee as b', 'b.form_id', '=', 'a.form_id')
                ->leftJoin('class as cc', 'cc.class_id', '=', 'a.class_id')
                ->where('a.status', 'S')
                ->where('a.admission_form_status', 'Applied')
                ->where('a.academic_yr', $academicYear)
                ->where('b.status', 'S')
                ->select(
                    'a.*',
                    'b.form_id as payment_form_id',
                    'b.status as payment_status',
                    'b.payment_date',
                    'cc.name as class_name'
                )
                ->orderBy('a.adm_form_pk', 'desc');

            $admissions = $query->get();

            return response()->json([
                'status' => true,
                'data' => $admissions
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch successful admission payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateDocumentSubmission(Request $request)
    {
        try {
            // selector = array of form_ids (checkbox values)
            $formIds = $request->input('form_ids');

            // Validate selection
            if (empty($formIds) || !is_array($formIds)) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Please select form for document submission.!!!'
                    ],
                    400
                );
            }

            // Remove empty / null values (same as CI continue logic)
            $formIds = array_filter($formIds, function ($value) {
                return !empty($value);
            });

            if (count($formIds) === 0) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Please select valid form for document submission.!!!'
                    ],
                    400
                );
            }

            DB::beginTransaction();

            // Bulk update instead of loop (better performance)
            DB::table('online_admission_form')
                ->whereIn('form_id', $formIds)
                ->update([
                    'admission_form_status' => 'Document Submitted'
                ]);

            DB::commit();

            return response()->json(
                [
                    'status' => true,
                    'message' => 'Form is successfully updated.!!!'
                ],
                200
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            // Log error for debugging
            // Log::error('Document submission update failed', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong. Please try again.'
                ],
                500
            );
        }
    }

    public function indexInterviewScheduling(Request $request)
    {
        try {
            // Authenticate user
            $user = $this->authenticateUser();

            // Get academic year from JWT
            $academicYear = JWTAuth::getPayload()->get('academic_year');
            $short_name = JWTAuth::getPayload()->get('short_name');

            // Inputs
            $religion = $request->query('religion');
            $sibling = $request->query('sibling');
            $form_id = $request->query('form_id');

            // Build query
            $query = DB::table('online_admission_form')
                ->select('online_admission_form.*', 'class.name as class_name')
                ->leftJoin('class', 'class.class_id', '=', 'online_admission_form.class_id')
                ->where('online_admission_form.status', 'S')
                ->where('online_admission_form.admission_form_status', $short_name == 'SACS' ? 'Document Submitted' : 'Applied')
                ->where('online_admission_form.academic_yr', $academicYear);

            if (!empty($religion)) {
                $query->where('religion', $religion);
            }

            if (!empty($sibling)) {
                $query->where('sibling', $sibling);
            }

            if (!empty($form_id)) {
                $query->where('form_id', $form_id);
            }

            $admissions = $query
                ->orderBy('adm_form_pk', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $admissions
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch interview scheduling list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function storeInterviewScheduling(Request $request)
    // {
    //     try {
    //         $user = $this->authenticateUser();
    //         $academicYr = JWTAuth::getPayload()->get('academic_year');
    //         $shortname = JWTAuth::getPayload()->get('short_name');

    //         $interview_date = $request->input('interview_date');
    //         $form_ids = $request->input('form_ids');

    //         $interview_time_from = $request->input('interview_time_from');
    //         $interview_time_to   = $request->input('interview_time_to');

    //         $time_from_12hr = '';
    //         $time_to_12hr   = '';

    //         if (!empty($interview_time_from)) {
    //             $time_from_12hr = Carbon::createFromFormat('H:i', $interview_time_from)->format('h:i A');
    //         }

    //         if (!empty($interview_time_to)) {
    //             $time_to_12hr = Carbon::createFromFormat('H:i', $interview_time_to)->format('h:i A');
    //         }

    //         if (!empty($form_ids)) {
    //             for ($i = 0; $i < count($form_ids); $i++) {
    //                 if (empty($form_ids[$i])) {
    //                     continue;
    //                 }

    //                 if (!empty($interview_date)) {
    //                     // Check if already scheduled
    //                     $query = DB::table('online_adm_interview_schedule')
    //                         ->where('form_id', $form_ids[$i])
    //                         ->exists();

    //                     $class_id = DB::table('online_admission_form')
    //                         ->where('form_id', $form_ids[$i])
    //                         ->value('class_id');

    //                     $class_name = DB::table('class')
    //                         ->where('class_id', $class_id)
    //                         ->value('name');

    //                     $data = [
    //                         'interview_date' => Carbon::parse($interview_date)->format('Y-m-d'),
    //                         'interview_time_from' => $interview_time_from,
    //                         'interview_time_to' => $interview_time_to,
    //                         'academic_yr' => $academicYr
    //                     ];

    //                     if (!$query) {
    //                         // Insert
    //                         $data['form_id'] = $form_ids[$i];
    //                         DB::table('online_adm_interview_schedule')->insert($data);
    //                     } else {
    //                         // Update
    //                         DB::table('online_adm_interview_schedule')
    //                             ->where('form_id', $form_ids[$i])
    //                             ->update($data);
    //                     }

    //                     // Update admission form status
    //                     DB::table('online_admission_form')
    //                         ->where('form_id', $form_ids[$i])
    //                         ->update([
    //                             'admission_form_status' => 'Scheduled'
    //                         ]);

    //                     // Dont remove this part keep it as it is
    //                     $father_emailid = DB::table('online_admission_form')->where('form_id', $form_ids[$i])->value('f_email');
    //                     $mother_emailid = DB::table('online_admission_form')->where('form_id', $form_ids[$i])->value('m_emailid');

    //                     $formData = DB::table('online_admission_form')
    //                         ->where('form_id', $form_ids[$i])->first();
    //                     $form_class_id = $formData->class_id;
    //                     $textmsg = $this->getEmailBodyByKey('INTERVIEW_SCHEDULING', $form_class_id);
    //                     // if ($class_name == 'Nursery') {

    //                     // } else if ($class_name == '11') {
    //                     //     $textmsg = str_replace(
    //                     //         ['INTERVIEW_DATE', 'TIME_FROM', 'TIME_TO'],
    //                     //         [$interview_date, $time_from_12hr, $time_to_12hr],
    //                     //         $textmsg
    //                     //     );
    //                     //     $emailData = [
    //                     //         'subject' => 'Inviting For Verification for Class 11 Admission',
    //                     //         'textmsg' => $textmsg,
    //                     //     ];
    //                     //     smart_mail($father_emailid, 'Inviting For Verification for Class 11 Admission', 'emails.parentUserEmail', $emailData);
    //                     //     smart_mail($mother_emailid, 'Inviting For Verification for Class 11 Admission', 'emails.parentUserEmail', $emailData);
    //                     // }

    //                     $textmsg = str_replace(
    //                         ['INTERVIEW_DATE', 'TIME_FROM', 'TIME_TO'],
    //                         [
    //                             $interview_date ?? '',
    //                             $time_from_12hr ?? '',
    //                             $time_to_12hr ?? ''
    //                         ],
    //                         $textmsg
    //                     );
    //                     $emailData = [
    //                         'subject' => 'Inviting For Verification for Admission',
    //                         'textmsg' => $textmsg,
    //                     ];
    //                     smart_mail($father_emailid, 'Inviting For Verification for Admission', 'emails.parentUserEmail', $emailData);
    //                     smart_mail($mother_emailid, 'Inviting For Verification for Admission', 'emails.parentUserEmail', $emailData);
    //                 } else {
    //                     DB::table('online_admission_form')
    //                         ->where('form_id', $form_ids[$i])
    //                         ->update([
    //                             'admission_form_status' => 'Scheduled'
    //                         ]);
    //                 }
    //             }
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Interview scheduling updated successfully'
    //         ], 200);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function storeInterviewScheduling(Request $request)
    {
        try {
            Log::channel('approve_admission')->info('Interview Scheduling API started');

            $user = $this->authenticateUser();
            Log::channel('approve_admission')->info('User authenticated', ['user' => $user]);

            $academicYr = JWTAuth::getPayload()->get('academic_year');
            $shortname = JWTAuth::getPayload()->get('short_name');

            Log::channel('approve_admission')->info('JWT Payload', [
                'academic_year' => $academicYr,
                'short_name' => $shortname
            ]);

            $interview_date = $request->input('interview_date');
            $form_ids = $request->input('form_ids');
            $interview_time_from = $request->input('interview_time_from');
            $interview_time_to = $request->input('interview_time_to');

            Log::channel('approve_admission')->info('Request Data', [
                'interview_date' => $interview_date,
                'form_ids' => $form_ids,
                'time_from' => $interview_time_from,
                'time_to' => $interview_time_to
            ]);

            $time_from_12hr = '';
            $time_to_12hr = '';

            if (!empty($interview_time_from)) {
                $time_from_12hr = Carbon::createFromFormat('H:i', $interview_time_from)->format('h:i A');
            }

            if (!empty($interview_time_to)) {
                $time_to_12hr = Carbon::createFromFormat('H:i', $interview_time_to)->format('h:i A');
            }

            if (!empty($form_ids)) {
                foreach ($form_ids as $form_id) {
                    if (empty($form_id)) {
                        continue;
                    }

                    Log::channel('approve_admission')->info('Processing form_id', [
                        'form_id' => $form_id
                    ]);

                    if (!empty($interview_date)) {
                        $exists = DB::table('online_adm_interview_schedule')
                            ->where('form_id', $form_id)
                            ->exists();

                        Log::channel('approve_admission')->info('Schedule exists check', [
                            'form_id' => $form_id,
                            'exists' => $exists
                        ]);

                        $class_id = DB::table('online_admission_form')
                            ->where('form_id', $form_id)
                            ->value('class_id');

                        $class_name = DB::table('class')
                            ->where('class_id', $class_id)
                            ->value('name');

                        $data = [
                            'interview_date' => Carbon::parse($interview_date)->format('Y-m-d'),
                            'interview_time_from' => $interview_time_from,
                            'interview_time_to' => $interview_time_to,
                            'academic_yr' => $academicYr
                        ];

                        if (!$exists) {
                            Log::channel('approve_admission')->info('Inserting interview schedule', [
                                'form_id' => $form_id,
                                'data' => $data
                            ]);

                            $data['form_id'] = $form_id;

                            DB::table('online_adm_interview_schedule')->insert($data);
                        } else {
                            Log::channel('approve_admission')->info('Updating interview schedule', [
                                'form_id' => $form_id
                            ]);

                            DB::table('online_adm_interview_schedule')
                                ->where('form_id', $form_id)
                                ->update($data);
                        }

                        DB::table('online_admission_form')
                            ->where('form_id', $form_id)
                            ->update([
                                'admission_form_status' => 'Scheduled'
                            ]);

                        Log::channel('approve_admission')->info('Admission form status updated', [
                            'form_id' => $form_id
                        ]);

                        // Dont remove this part keep it as it is
                        $father_emailid = DB::table('online_admission_form')
                            ->where('form_id', $form_id)
                            ->value('f_email');

                        $mother_emailid = DB::table('online_admission_form')
                            ->where('form_id', $form_id)
                            ->value('m_emailid');

                        Log::channel('approve_admission')->info('Parent Emails fetched', [
                            'father_email' => $father_emailid,
                            'mother_email' => $mother_emailid
                        ]);

                        $formData = DB::table('online_admission_form')
                            ->where('form_id', $form_id)
                            ->first();

                        $form_class_id = $formData->class_id;

                        $textmsg = $this->getEmailBodyByKey('INTERVIEW_SCHEDULING', $form_class_id);

                        $textmsg = str_replace(
                            ['INTERVIEW_DATE', 'TIME_FROM', 'TIME_TO'],
                            [
                                $interview_date ?? '',
                                $time_from_12hr ?? '',
                                $time_to_12hr ?? ''
                            ],
                            $textmsg
                        );

                        $emailData = [
                            'subject' => 'Inviting For Verification for Admission',
                            'textmsg' => $textmsg,
                        ];

                        Log::channel('approve_admission')->info('Sending email', [
                            'form_id' => $form_id,
                            'father_email' => $father_emailid,
                            'mother_email' => $mother_emailid
                        ]);

                        smart_mail(
                            $father_emailid,
                            'Inviting For Verification for Admission',
                            'emails.parentUserEmail',
                            $emailData
                        );

                        smart_mail(
                            $mother_emailid,
                            'Inviting For Verification for Admission',
                            'emails.parentUserEmail',
                            $emailData
                        );

                        // stage => dev , live => production
                        if (env('APP_ENV') == 'production') {
                            $cc = 'school@arnoldcentralschoolpune.edu.in';
                            smart_mail(
                                $cc,
                                'Inviting For Verification for Admission',
                                'emails.parentUserEmail',
                                $emailData
                            );
                        }

                        Log::channel('approve_admission')->info('Emails sent successfully', [
                            'form_id' => $form_id
                        ]);
                    } else {
                        DB::table('online_admission_form')
                            ->where('form_id', $form_id)
                            ->update([
                                'admission_form_status' => 'Scheduled'
                            ]);

                        Log::channel('approve_admission')->info('Scheduled without interview date', [
                            'form_id' => $form_id
                        ]);
                    }
                }
            }

            Log::channel('approve_admission')->info('Interview scheduling completed');

            return response()->json([
                'status' => true,
                'message' => 'Interview scheduling updated successfully'
            ], 200);
        } catch (\Throwable $e) {
            Log::channel('approve_admission')->error('Interview scheduling failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function indexVerificationList(Request $request)
    {
        try {
            // Authenticate user
            $user = $this->authenticateUser();

            // Get academic year from JWT
            $academicYear = JWTAuth::getPayload()->get('academic_year');

            // Inputs
            $religion = $request->query('religion');
            $sibling = $request->query('sibling');
            $form_id = $request->query('form_id');

            // Build query
            $query = DB::table('online_admission_form')
                ->leftJoin('class', 'class.class_id', '=', 'online_admission_form.class_id')
                ->select('online_admission_form.*', 'class.name as class_name')
                ->where('online_admission_form.status', 'S')
                ->where('online_admission_form.admission_form_status', 'Scheduled')
                ->where('online_admission_form.academic_yr', $academicYear);

            if (!empty($religion)) {
                $query->where('religion', $religion);
            }

            if (!empty($sibling)) {
                $query->where('sibling', $sibling);
            }

            if (!empty($form_id)) {
                $query->where('form_id', $form_id);
            }

            $admissions = $query
                ->orderBy('adm_form_pk', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $admissions
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch verification list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateVerificationList(Request $request)
    {
        try {
            // Authenticate user
            $user = $this->authenticateUser();
            $academicYr = JWTAuth::getPayload()->get('academic_year');
            $short_name = JWTAuth::getPayload()->get('short_name');

            // Selector array (form IDs)
            $form_ids = $request->input('form_ids');

            if (empty($form_ids) || !is_array($form_ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please select form for verification.'
                ], 422);
            }

            DB::beginTransaction();

            foreach ($form_ids as $form_id) {
                DB::table('online_admission_form')
                    ->where('form_id', $form_id)
                    ->update([
                        'admission_form_status' => 'Verified'
                    ]);

                $formData = DB::table('online_admission_form')
                    ->where('form_id', $form_id)
                    ->first();
                $form_class_id = $formData->class_id;

                $textmsg = $textmsg = $this->getEmailBodyByKey('VERIFICATION_SUCCESSFULL', $form_class_id);

                $emailData = [
                    'subject' => $short_name . '-Admission Details',
                    'textmsg' => $textmsg,
                ];

                $father_emailid = DB::table('online_admission_form')->where('form_id', $form_id)->value('f_email');
                $mother_emailid = DB::table('online_admission_form')->where('form_id', $form_id)->value('m_emailid');
                smart_mail($father_emailid, 'Admission Details', 'emails.parentUserEmail', $emailData);
                smart_mail($mother_emailid, 'Admission Details', 'emails.parentUserEmail', $emailData);

                if (env('APP_ENV') == 'production') {
                    $cc = 'school@arnoldcentralschoolpune.edu.in';
                    smart_mail($cc, 'Admission Details', 'emails.parentUserEmail', $emailData);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Form(s) successfully verified.'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to verify form(s)',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function indexApprovalList(Request $request)
    {
        try {
            // Authenticate user
            $user = $this->authenticateUser();

            // Get academic year from JWT
            $academicYear = JWTAuth::getPayload()->get('academic_year');

            // Optional filters
            $class_id = $request->query('class_id');
            $form_id = $request->query('form_id');
            $student_name = trim($request->query('student_name'));  // "Leo Harry Devanesan"

            if (!empty($student_name) && empty($class_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'class_id is required when searching by student name'
                ], 422);
            }

            // Build query
            $query = DB::table('online_admission_form')
                ->leftJoin('class', 'class.class_id', '=', 'online_admission_form.class_id')
                ->select('online_admission_form.*', 'class.name as class_name')
                ->where('online_admission_form.status', 'S')
                ->where('online_admission_form.admission_form_status', 'Verified')
                ->where('online_admission_form.academic_yr', $academicYear);

            if (!empty($class_id)) {
                $query->where('online_admission_form.class_id', $class_id);
            }

            if (!empty($student_name)) {
                $nameParts = array_values(array_filter(explode(' ', $student_name)));
                $query->where(function ($q) use ($nameParts) {
                    foreach ($nameParts as $part) {
                        $q->where(function ($sub) use ($part) {
                            $sub
                                ->orWhere('online_admission_form.first_name', 'LIKE', "%{$part}%")
                                ->orWhere('online_admission_form.mid_name', 'LIKE', "%{$part}%")
                                ->orWhere('online_admission_form.last_name', 'LIKE', "%{$part}%");
                        });
                    }
                });
            }

            if (!empty($form_id)) {
                $query->where('online_admission_form.form_id', $form_id);
            }

            $admissions = $query
                ->orderBy('online_admission_form.adm_form_pk', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $admissions
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch approval list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*
     * Have to update the API for sibling logic based on short code.
     */
    // public function updateApprovalList(Request $request)
    // {
    //     try {
    //         $form_ids = $request->input('form_ids');
    //         $class_id = $request->input('class_id');
    //         $section_id = $request->input('section_id');
    //         $short_name = JWTAuth::getPayload()->get('short_name');
    //         $defaultPassword = DB::table('school_settings')->where('short_name', $short_name)->value('default_pwd');
    //         $passwordCode = 'arnolds';
    //         if ($defaultPassword == null) {
    //             $passwordCode = $short_name == 'HSCS' ? 'hscs' : 'arnolds';
    //         }
    //         if ($short_name == 'HSCS') {
    //             for ($i = 0, $j = 1; $i < count($form_ids); $i++, $j++) {
    //                 if ($form_ids[$i] == '' || $form_ids[$i] == NULL) {
    //                     continue;
    //                 } else {
    //                     $form_id = $form_ids[$i];
    //                     DB::table('online_admission_form')
    //                         ->where('form_id', $form_id)
    //                         ->update([
    //                             'admission_form_status' => 'Approved'
    //                         ]);

    //                     $application_data = DB::table('online_admission_form')->where('form_id', $form_id)->first();
    //                     $sibling_student_id = $application_data->sibling_student_id;

    //                     $father_name = $application_data->father_name;
    //                     $f_occupation = $application_data->father_occupation;
    //                     $f_mobile = $application_data->f_mobile;
    //                     $f_email = $application_data->f_email;

    //                     $mother_name = $application_data->mother_name;
    //                     $m_occupation = $application_data->mother_occupation;
    //                     $m_mobile = $application_data->m_mobile;
    //                     $m_emailid = $application_data->m_emailid;

    //                     $father_adhar_no = $application_data->f_aadhar_no;
    //                     $mother_adhar_no = $application_data->m_aadhar_no;

    //                     $f_qualification = $application_data->f_qualification;
    //                     $m_qualification = $application_data->m_qualification;

    //                     $academic_yr = $application_data->academic_yr;

    //                     $first_name = $application_data->first_name;
    //                     $mid_name = $application_data->mid_name;
    //                     $last_name = $application_data->last_name;

    //                     $dob = $application_data->dob;
    //                     $gender = $application_data->gender;
    //                     $application_date = $application_data->application_date;

    //                     $religion = $application_data->religion;
    //                     $caste = $application_data->caste;
    //                     $category = $application_data->category;
    //                     $nationality = $application_data->nationality;

    //                     $sms_sending_phone_no = $application_data->sms_sending_phone_no;

    //                     $class_id = $application_data->class_id;
    //                     $mother_tongue = $application_data->mother_tongue;
    //                     $sub_caste = $application_data->subcaste;

    //                     $perm_address = $application_data->perm_address;
    //                     $city = $application_data->city;
    //                     $state = $application_data->state;
    //                     $pincode = $application_data->pincode;

    //                     $stud_aadhar = $application_data->stud_aadhar;
    //                     $blood_group = $application_data->blood_group;
    //                     $birth_place = $application_data->birth_place;

    //                     $class_name = DB::table('class')->where('class_id', $class_id)->value('name');

    //                     // START
    //                     if ($sibling_student_id != 0) {
    //                         $parent = DB::table('student')
    //                             ->select('parent_id')
    //                             ->where('student_id', $sibling_student_id)
    //                             ->first();

    //                         $parent_id = $parent ? $parent->parent_id : null;

    //                         $formRecord = DB::table('online_admission_form')
    //                             ->where('form_id', $form_id)
    //                             ->first();

    //                         $student_id_new = null;

    //                         $studentOldRecord = DB::table('student')
    //                             ->where('student_id', $formRecord->student_id)
    //                             ->first();

    //                         if ($studentOldRecord) {
    //                             $student_id_new = $studentOldRecord->student_id;
    //                         } else {
    //                             $student_id_new = DB::table('student')->insertGetId([
    //                                 'academic_yr' => $academic_yr,
    //                                 'parent_id' => $parent_id,
    //                                 'first_name' => $first_name,
    //                                 'mid_name' => $mid_name,
    //                                 'last_name' => $last_name,
    //                                 'dob' => $dob,
    //                                 'gender' => $gender,
    //                                 'class_id' => $class_id,
    //                                 'section_id' => $section_id,
    //                                 'religion' => $religion,
    //                                 'caste' => $caste,
    //                                 'IsDelete' => 'N',
    //                                 'isNew' => 'Y',
    //                                 'isModify' => 'N',
    //                                 'category' => $category,
    //                                 'mother_tongue' => $mother_tongue,
    //                                 'subcaste' => $sub_caste,
    //                                 'permant_add' => $perm_address,
    //                                 'city' => $city,
    //                                 'state' => $state,
    //                                 'pincode' => $pincode,
    //                                 'stu_aadhaar_no' => $stud_aadhar,
    //                                 'blood_group' => $blood_group,
    //                                 'admission_date' => date('Y-m-d'),
    //                                 'admission_class' => $class_name,
    //                                 'birth_place' => $birth_place,
    //                                 'nationality' => $nationality,
    //                                 'student_name' => $first_name,
    //                             ]);
    //                         }

    //                         if ($student_id_new) {

    //                             DB::table('online_admission_form')
    //                                 ->where('form_id', $form_id)
    //                                 ->update(['student_id' => $student_id_new]);

    //                             $password = bcrypt('arnolds');
    //                             $user_id1 = 'S' . str_pad($student_id_new, 4, '0', STR_PAD_LEFT);

    //                             DB::table('user_master')->insert([
    //                                 'user_id' => $user_id1,
    //                                 'name' => $first_name,
    //                                 'password' => $password,
    //                                 'reg_id' => $student_id_new,
    //                                 'role_id' => 'S',
    //                             ]);

    //                             // fee category
    //                             $fees_category = DB::table('fees_category_detail')
    //                                 ->where('class_concession', $class_id)
    //                                 ->select('fees_category_id')
    //                                 ->first();

    //                             if ($fees_category && $fees_category->fees_category_id) {
    //                                 $fees_category_id = $fees_category->fees_category_id;
    //                                 $fee_cat_query = DB::table('fees_student_category')
    //                                     ->where([
    //                                         'student_id' => $student_id_new,
    //                                         'fees_category_id' => $fees_category_id
    //                                     ])
    //                                     ->count();
    //                                 if ($fee_cat_query == 0) {
    //                                     $fee_cat_data = [
    //                                         'student_id' => $student_id_new,
    //                                         'fees_category_id' => $fees_category_id,
    //                                         'academic_yr' => $academic_yr
    //                                     ];
    //                                     DB::table('fees_student_category')->insert($fee_cat_data);
    //                                 }
    //                             }
    //                         }

    //                         $from = 'supportsacs@aceventura.in';
    //                         $cc = 'school@arnoldcentralschool.org';

    //                         if ($m_emailid != '') {
    //                             $mmail = str_replace("'", '', $m_emailid);
    //                         }

    //                         if ($f_email != '') {
    //                             $fmail = str_replace("'", '', $f_email);
    //                         }

    //                         $formData = DB::table('online_admission_form')
    //                             ->where('form_id', $form_id)->first();
    //                         $form_class_id = $formData->class_id;
    //                         $textmsg = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $form_class_id);
    //                         $emailData = [
    //                             'subject' => $short_name . ' - ',
    //                             'textmsg' => $textmsg,
    //                         ];
    //                         smart_mail($fmail, $short_name . ' - ' . "Admission Approved", 'emails.parentUserEmail', $emailData);
    //                         smart_mail($mmail,  $short_name . ' - ' . "Admission Approved", 'emails.parentUserEmail', $emailData);
    //                     } else {
    //                         $parent_id = '';
    //                         if (!is_null($f_mobile)) {
    //                             $parent_id = DB::table('parent')
    //                                 ->where('f_mobile', $f_mobile)
    //                                 ->value('parent_id');
    //                         }
    //                         if (empty($parent_id) && $f_email !== null) {
    //                             $parent_id = DB::table('user_master')
    //                                 ->where('user_id', $f_email)
    //                                 ->value('reg_id');
    //                         }
    //                         if (empty($parent_id) && $m_emailid !== null) {
    //                             $parent_id = DB::table('user_master')
    //                                 ->where('user_id', $m_emailid)
    //                                 ->value('reg_id');
    //                         }
    //                         if (empty($parent_id) && $m_mobile !== null) {
    //                             $parent_id = DB::table('user_master')
    //                                 ->where('user_id', $m_mobile)
    //                                 ->value('reg_id');
    //                         }
    //                         if ($parent_id == '') {
    //                             $parent_id = DB::table('parent')->insertGetId([
    //                                 'father_name' => $father_name,
    //                                 'father_occupation' => $f_occupation,
    //                                 'f_mobile' => $f_mobile,
    //                                 'f_email' => $f_email,
    //                                 'parent_adhar_no' => $father_adhar_no,
    //                                 'f_qualification' => $f_qualification,
    //                                 'mother_name' => $mother_name,
    //                                 'mother_occupation' => $m_occupation,
    //                                 'm_mobile' => $m_mobile,
    //                                 'm_emailid' => $m_emailid,
    //                                 'm_adhar_no' => $mother_adhar_no,
    //                                 'm_qualification' => $m_qualification,
    //                                 'IsDelete' => 'N',
    //                             ]);

    //                             if ($parent_id) {
    //                                 if ($f_mobile == null || $f_mobile == 'null' || trim($f_mobile) == "" || trim($f_mobile) == "''") {
    //                                     if ($m_mobile == null || $m_mobile == 'null' || trim($m_mobile) == "" || trim($m_mobile) == "''") {
    //                                         if ($last_name != 'null' && $father_name != 'null') {
    //                                             $user_id = str_replace(" ", "", $father_name) . $last_name;
    //                                         } elseif ($last_name != 'null') {
    //                                             $user_id = str_replace(" ", "", $last_name);
    //                                         } elseif ($father_name != 'null') {
    //                                             $user_id = str_replace(" ", "", $father_name);
    //                                         }
    //                                     } else {
    //                                         //echo "4";
    //                                         $user_id = $m_mobile;
    //                                     }
    //                                 } else {
    //                                     //echo "5";
    //                                     $user_id = $f_mobile;
    //                                 }

    //                                 $user_id = str_replace("''", "", $user_id);
    //                                 $name = "";
    //                                 if ($father_name <> 'null') {
    //                                     $name = $father_name;
    //                                 } else {
    //                                     $name = $mother_name;
    //                                 }

    //                                 $password = bcrypt($passwordCode);

    //                                 $usql = DB::table('user_master')->insertGetId([
    //                                     'user_id' => $user_id,
    //                                     'name' => $name,
    //                                     'password' => $password,
    //                                     'reg_id' => $parent_id,
    //                                     'role_id' => 'P',
    //                                 ]);

    //                                 $user_id = str_replace("'", '', $user_id);

    //                                 if ($usql) {
    //                                     $school_id = '7';
    //                                     $user_data = json_encode([
    //                                         'user_id' => $user_id,
    //                                         'school_id' => $school_id,
    //                                     ]);
    //                                     $evolvuUrl = config('externalapis.EVOLVU_URL');

    //                                     $response = Http::withHeaders([
    //                                         'Content-Type' => 'application/json',
    //                                     ])->post($evolvuUrl . 'user_create_post', json_decode($user_data, true));

    //                                     $token_data = $response->body();
    //                                     $err = $response->failed() ? $response->status() : null;

    //                                     $phone_no = ($sms_sending_phone_no != '') ? $sms_sending_phone_no : $f_mobile;

    //                                     DB::table('contact_details')->insert([
    //                                         'id' => $parent_id,
    //                                         'phone_no' => $phone_no,
    //                                         'email_id' => $f_email,
    //                                         'm_emailid' => $m_emailid,
    //                                     ]);

    //                                     $formRecord = DB::table('online_admission_form')
    //                                         ->where('form_id', $form_id)
    //                                         ->first();

    //                                     $student_id_new = null;

    //                                     $studentOldRecord = DB::table('student')
    //                                         ->where('student_id', $formRecord->student_id)
    //                                         ->first();

    //                                     if ($studentOldRecord) {
    //                                         $student_id_new = $studentOldRecord->student_id;
    //                                     } else {
    //                                         $student_id_new = DB::table('student')->insertGetId([
    //                                             'academic_yr' => $academic_yr,
    //                                             'parent_id' => $parent_id,
    //                                             'first_name' => $first_name,
    //                                             'mid_name' => $mid_name,
    //                                             'last_name' => $last_name,
    //                                             'dob' => $dob,
    //                                             'gender' => $gender,
    //                                             'class_id' => $class_id,
    //                                             'section_id' => $section_id,
    //                                             'religion' => $religion,
    //                                             'caste' => $caste,
    //                                             'IsDelete' => 'N',
    //                                             'isNew' => 'Y',
    //                                             'isModify' => 'N',
    //                                             'category' => $category,
    //                                             'mother_tongue' => $mother_tongue,
    //                                             'subcaste' => $sub_caste,
    //                                             'permant_add' => $perm_address,
    //                                             'city' => $city,
    //                                             'state' => $state,
    //                                             'pincode' => $pincode,
    //                                             'stu_aadhaar_no' => $stud_aadhar,
    //                                             'blood_group' => $blood_group,
    //                                             'admission_date' => date('Y-m-d'),
    //                                             'admission_class' => $class_name,
    //                                             'birth_place' => $birth_place,
    //                                             'nationality' => $nationality,
    //                                             'student_name' => $first_name,
    //                                         ]);
    //                                     }

    //                                     if ($student_id_new) {

    //                                         DB::table('online_admission_form')
    //                                             ->where('form_id', $form_id)
    //                                             ->update(['student_id' => $student_id_new]);

    //                                         $password = $passwordCode;
    //                                         $user_id1 = 'S' . str_pad($student_id_new, 4, '0', STR_PAD_LEFT);

    //                                         DB::table('user_master')->insert([
    //                                             'user_id' => $user_id1,
    //                                             'name' => $first_name,
    //                                             'password' => $password,
    //                                             'reg_id' => $student_id_new,
    //                                             'role_id' => 'S',
    //                                         ]);

    //                                         $fees_category = DB::table('fees_category_detail')
    //                                             ->where('class_concession', $class_id)
    //                                             ->select('fees_category_id')
    //                                             ->first();

    //                                         if ($fees_category && $fees_category->fees_category_id) {
    //                                             $fees_category_id = $fees_category->fees_category_id;
    //                                             $fee_cat_query = DB::table('fees_student_category')
    //                                                 ->where([
    //                                                     'student_id' => $student_id_new,
    //                                                     'fees_category_id' => $fees_category_id
    //                                                 ])
    //                                                 ->count();
    //                                             if ($fee_cat_query == 0) {
    //                                                 $fee_cat_data = [
    //                                                     'student_id' => $student_id_new,
    //                                                     'fees_category_id' => $fees_category_id,
    //                                                     'academic_yr' => $academic_yr
    //                                                 ];
    //                                                 DB::table('fees_student_category')->insert($fee_cat_data);
    //                                             }
    //                                         }
    //                                     }

    //                                     DB::table('online_admission_form')
    //                                         ->where('form_id', $form_id)
    //                                         ->update([
    //                                             'student_id' => $student_id_new,
    //                                         ]);

    //                                     $from = 'supportsacs@aceventura.in';
    //                                     $cc = 'school@arnoldcentralschool.org';

    //                                     if ($m_emailid != '') {
    //                                         $mmail = str_replace("'", '', $m_emailid);
    //                                     }

    //                                     if ($f_email != '') {
    //                                         $fmail = str_replace("'", '', $f_email);
    //                                     }
    //                                     $formData = DB::table('online_admission_form')
    //                                         ->where('form_id', $form_ids[$i])->first();
    //                                     $form_class_id = $formData->class_id;
    //                                     $textmsg = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $form_class_id);
    //                                     if ($class_name == 'Nursery') {
    //                                         $subject = 'Information for Nursery admission';
    //                                     } else if ($class_name = '11') {
    //                                         $subject = 'Information for Class 11 admission';
    //                                     }
    //                                     $emailData = [
    //                                         'subject' => $short_name . ' - ' . $subject,
    //                                         'textmsg' => $textmsg,
    //                                     ];
    //                                     smart_mail($fmail, $short_name . ' - ' . "Admission Approved", 'emails.parentUserEmail', $emailData);
    //                                     smart_mail($mmail,  $short_name . ' - ' . "Admission Approved", 'emails.parentUserEmail', $emailData);
    //                                 }
    //                             }
    //                         } elseif ($parent_id != '') {
    //                             $formRecord = DB::table('online_admission_form')
    //                                 ->where('form_id', $form_id)
    //                                 ->first();

    //                             $student_id_new = null;

    //                             $studentOldRecord = DB::table('student')
    //                                 ->where('student_id', $formRecord->student_id)
    //                                 ->first();

    //                             if ($studentOldRecord) {
    //                                 $student_id_new = $studentOldRecord->student_id;
    //                             } else {
    //                                 $student_id_new = DB::table('student')->insertGetId([
    //                                     'academic_yr' => $academic_yr,
    //                                     'parent_id' => $parent_id,
    //                                     'first_name' => $first_name,
    //                                     'mid_name' => $mid_name,
    //                                     'last_name' => $last_name,
    //                                     'dob' => $dob,
    //                                     'gender' => $gender,
    //                                     'class_id' => $class_id,
    //                                     'section_id' => $section_id,
    //                                     'religion' => $religion,
    //                                     'caste' => $caste,
    //                                     'IsDelete' => 'N',
    //                                     'isNew' => 'Y',
    //                                     'isModify' => 'N',
    //                                     'category' => $category,
    //                                     'mother_tongue' => $mother_tongue,
    //                                     'subcaste' => $sub_caste,
    //                                     'permant_add' => $perm_address,
    //                                     'city' => $city,
    //                                     'state' => $state,
    //                                     'pincode' => $pincode,
    //                                     'stu_aadhaar_no' => $stud_aadhar,
    //                                     'blood_group' => $blood_group,
    //                                     'admission_date' => date('Y-m-d'),
    //                                     'admission_class' => $class_name,
    //                                     'birth_place' => $birth_place,
    //                                     'nationality' => $nationality,
    //                                     'student_name' => $first_name,
    //                                 ]);
    //                             }

    //                             if ($student_id_new) {

    //                                 DB::table('online_admission_form')
    //                                     ->where('form_id', $form_id)
    //                                     ->update(['student_id' => $student_id_new]);

    //                                 $password = bcrypt('arnolds');
    //                                 $user_id1 = 'S' . str_pad($student_id_new, 4, '0', STR_PAD_LEFT);

    //                                 DB::table('user_master')->insert([
    //                                     'user_id' => $user_id1,
    //                                     'name' => $first_name,
    //                                     'password' => $password,
    //                                     'reg_id' => $student_id_new,
    //                                     'role_id' => 'S',
    //                                 ]);

    //                                 // fee category
    //                                 $fees_category = DB::table('fees_category_detail')
    //                                     ->where('class_concession', $class_id)
    //                                     ->select('fees_category_id')
    //                                     ->first();

    //                                 if ($fees_category && $fees_category->fees_category_id) {
    //                                     $fees_category_id = $fees_category->fees_category_id;
    //                                     $fee_cat_query = DB::table('fees_student_category')
    //                                         ->where([
    //                                             'student_id' => $student_id_new,
    //                                             'fees_category_id' => $fees_category_id
    //                                         ])
    //                                         ->count();
    //                                     if ($fee_cat_query == 0) {
    //                                         $fee_cat_data = [
    //                                             'student_id' => $student_id_new,
    //                                             'fees_category_id' => $fees_category_id,
    //                                             'academic_yr' => $academic_yr
    //                                         ];
    //                                         DB::table('fees_student_category')->insert($fee_cat_data);
    //                                     }
    //                                 }
    //                             }

    //                             $from = 'supportsacs@aceventura.in';
    //                             $cc = 'school@arnoldcentralschool.org';

    //                             if ($m_emailid != '') {
    //                                 $mmail = str_replace("'", '', $m_emailid);
    //                             }

    //                             if ($f_email != '') {
    //                                 $fmail = str_replace("'", '', $f_email);
    //                             }

    //                             $formData = DB::table('online_admission_form')
    //                                 ->where('form_id', $form_id)->first();
    //                             $form_class_id = $formData->class_id;
    //                             $textmsg = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $form_class_id);
    //                             if ($class_name == 'Nursery') {
    //                                 $subject = 'Information for Nursery admission';
    //                             } else if ($class_name = '11') {
    //                                 $subject = 'Information for Class 11 admission';
    //                             }
    //                             $emailData = [
    //                                 'subject' => $short_name . ' - ' . $subject,
    //                                 'textmsg' => $textmsg,
    //                             ];
    //                             smart_mail($fmail, $short_name . ' - ' . $subject, 'emails.parentUserEmail', $emailData);
    //                             smart_mail($mmail,  $short_name . ' - ' . $subject, 'emails.parentUserEmail', $emailData);
    //                         }
    //                     }
    //                     // END
    //                 }
    //             }
    //         } else {
    //             for ($i = 0, $j = 1; $i < count($form_ids); $i++, $j++) {
    //                 if ($form_ids[$i] == '' || $form_ids[$i] == NULL) {
    //                     continue;
    //                 } else {
    //                     $form_id = $form_ids[$i];
    //                     DB::table('online_admission_form')
    //                         ->where('form_id', $form_id)
    //                         ->update([
    //                             'admission_form_status' => 'Approved'
    //                         ]);

    //                     $application_data = DB::table('online_admission_form')->where('form_id', $form_id)->first();
    //                     $sibling_student_id = $application_data->sibling_student_id;

    //                     $father_name = $application_data->father_name;
    //                     $f_occupation = $application_data->father_occupation;
    //                     $f_mobile = $application_data->f_mobile;
    //                     $f_email = $application_data->f_email;

    //                     $mother_name = $application_data->mother_name;
    //                     $m_occupation = $application_data->mother_occupation;
    //                     $m_mobile = $application_data->m_mobile;
    //                     $m_emailid = $application_data->m_emailid;

    //                     $father_adhar_no = $application_data->f_aadhar_no;
    //                     $mother_adhar_no = $application_data->m_aadhar_no;

    //                     $f_qualification = $application_data->f_qualification;
    //                     $m_qualification = $application_data->m_qualification;

    //                     $academic_yr = $application_data->academic_yr;

    //                     $first_name = $application_data->first_name;
    //                     $mid_name = $application_data->mid_name;
    //                     $last_name = $application_data->last_name;

    //                     $dob = $application_data->dob;
    //                     $gender = $application_data->gender;
    //                     $application_date = $application_data->application_date;

    //                     $religion = $application_data->religion;
    //                     $caste = $application_data->caste;
    //                     $category = $application_data->category;
    //                     $nationality = $application_data->nationality;

    //                     $sms_sending_phone_no = $application_data->sms_sending_phone_no;

    //                     $class_id = $application_data->class_id;
    //                     $mother_tongue = $application_data->mother_tongue;
    //                     $sub_caste = $application_data->subcaste;

    //                     $perm_address = $application_data->perm_address;
    //                     $city = $application_data->city;
    //                     $state = $application_data->state;
    //                     $pincode = $application_data->pincode;

    //                     $stud_aadhar = $application_data->stud_aadhar;
    //                     $blood_group = $application_data->blood_group;
    //                     $birth_place = $application_data->birth_place;

    //                     $class_name = DB::table('class')->where('class_id', $class_id)->value('name');

    //                     $parent_id = '';
    //                     if (!is_null($f_mobile)) {
    //                         $parent_id = DB::table('parent')
    //                             ->where('f_mobile', $f_mobile)
    //                             ->value('parent_id');
    //                     }
    //                     if (empty($parent_id) && $f_email !== null) {
    //                         $parent_id = DB::table('user_master')
    //                             ->where('user_id', $f_email)
    //                             ->value('reg_id');
    //                     }
    //                     if (empty($parent_id) && $m_emailid !== null) {
    //                         $parent_id = DB::table('user_master')
    //                             ->where('user_id', $m_emailid)
    //                             ->value('reg_id');
    //                     }
    //                     if (empty($parent_id) && $m_mobile !== null) {
    //                         $parent_id = DB::table('user_master')
    //                             ->where('user_id', $m_mobile)
    //                             ->value('reg_id');
    //                     }
    //                     if ($parent_id == '') {
    //                         $parent_id = DB::table('parent')->insertGetId([
    //                             'father_name' => $father_name,
    //                             'father_occupation' => $f_occupation,
    //                             'f_mobile' => $f_mobile,
    //                             'f_email' => $f_email,
    //                             'parent_adhar_no' => $father_adhar_no,
    //                             'f_qualification' => $f_qualification,
    //                             'mother_name' => $mother_name,
    //                             'mother_occupation' => $m_occupation,
    //                             'm_mobile' => $m_mobile,
    //                             'm_emailid' => $m_emailid,
    //                             'm_adhar_no' => $mother_adhar_no,
    //                             'm_qualification' => $m_qualification,
    //                             'IsDelete' => 'N',
    //                         ]);

    //                         if ($parent_id) {
    //                             if (is_null($f_email) || $f_email === 'null' || trim($f_email) === '' || trim($f_email) === "''") {
    //                                 if (is_null($m_emailid) || $m_emailid === 'null' || trim($m_emailid) === '' || trim($m_emailid) === "''") {
    //                                     if ($last_name !== 'null' && $father_name !== 'null') {
    //                                         $user_id = str_replace(' ', '', $father_name) . $last_name;
    //                                     } elseif ($last_name !== 'null') {
    //                                         $user_id = str_replace(' ', '', $last_name);
    //                                     } elseif ($father_name !== 'null') {
    //                                         $user_id = str_replace(' ', '', $father_name);
    //                                     }
    //                                 } else {
    //                                     $user_id = $m_emailid;
    //                                 }
    //                             } else {
    //                                 $user_id = $f_email;
    //                             }

    //                             $user_id = str_replace("''", '', $user_id);

    //                             $name = ($father_name !== 'null') ? $father_name : $mother_name;

    //                             $password = bcrypt($passwordCode);

    //                             $usql = DB::table('user_master')->insertGetId([
    //                                 'user_id' => $user_id,
    //                                 'name' => $name,
    //                                 'password' => $password,
    //                                 'reg_id' => $parent_id,
    //                                 'role_id' => 'P',
    //                             ]);

    //                             $user_id = str_replace("'", '', $user_id);

    //                             if ($usql) {
    //                                 $school_id = '1';
    //                                 $user_data = json_encode([
    //                                     'user_id' => $user_id,
    //                                     'school_id' => $school_id,
    //                                 ]);
    //                                 $evolvuUrl = config('externalapis.EVOLVU_URL');

    //                                 $response = Http::withHeaders([
    //                                     'Content-Type' => 'application/json',
    //                                 ])->post($evolvuUrl . 'user_create_post', json_decode($user_data, true));

    //                                 $token_data = $response->body();
    //                                 $err = $response->failed() ? $response->status() : null;

    //                                 $phone_no = ($sms_sending_phone_no != '') ? $sms_sending_phone_no : $f_mobile;
    //                                 // fail point
    //                                 DB::table('contact_details')->insert([
    //                                     'id' => $parent_id,
    //                                     'phone_no' => $phone_no,
    //                                     'email_id' => $f_email,
    //                                     'm_emailid' => $m_emailid,
    //                                 ]);

    //                                 $formRecord = DB::table('online_admission_form')
    //                                     ->where('form_id', $form_id)
    //                                     ->first();

    //                                 $student_id_new = null;

    //                                 $studentOldRecord = DB::table('student')
    //                                     ->where('student_id', $formRecord->student_id)
    //                                     ->first();

    //                                 if ($studentOldRecord) {
    //                                     $student_id_new = $studentOldRecord->student_id;
    //                                 } else {
    //                                     $student_id_new = DB::table('student')->insertGetId([
    //                                         'academic_yr' => $academic_yr,
    //                                         'parent_id' => $parent_id,
    //                                         'first_name' => $first_name,
    //                                         'mid_name' => $mid_name,
    //                                         'last_name' => $last_name,
    //                                         'dob' => $dob,
    //                                         'gender' => $gender,
    //                                         'class_id' => $class_id,
    //                                         'section_id' => $section_id,
    //                                         'religion' => $religion,
    //                                         'caste' => $caste,
    //                                         'IsDelete' => 'N',
    //                                         'isNew' => 'Y',
    //                                         'isModify' => 'N',
    //                                         'category' => $category,
    //                                         'mother_tongue' => $mother_tongue,
    //                                         'subcaste' => $sub_caste,
    //                                         'permant_add' => $perm_address,
    //                                         'city' => $city,
    //                                         'state' => $state,
    //                                         'pincode' => $pincode,
    //                                         'stu_aadhaar_no' => $stud_aadhar,
    //                                         'blood_group' => $blood_group,
    //                                         'admission_date' => date('Y-m-d'),
    //                                         'admission_class' => $class_name,
    //                                         'birth_place' => $birth_place,
    //                                         'nationality' => $nationality,
    //                                         'student_name' => $first_name,
    //                                     ]);
    //                                 }

    //                                 if ($student_id_new) {

    //                                     DB::table('online_admission_form')
    //                                         ->where('form_id', $form_id)
    //                                         ->update(['student_id' => $student_id_new]);

    //                                     $password = $passwordCode;
    //                                     $user_id1 = 'S' . str_pad($student_id_new, 4, '0', STR_PAD_LEFT);

    //                                     DB::table('user_master')->insert([
    //                                         'user_id' => $user_id1,
    //                                         'name' => $first_name,
    //                                         'password' => $password,
    //                                         'reg_id' => $student_id_new,
    //                                         'role_id' => 'S',
    //                                     ]);
    //                                 }

    //                                 DB::table('online_admission_form')
    //                                     ->where('form_id', $form_id)
    //                                     ->update([
    //                                         'student_id' => $student_id_new,
    //                                     ]);

    //                                 $from = 'supportsacs@aceventura.in';
    //                                 $cc = 'school@arnoldcentralschool.org';

    //                                 if ($m_emailid != '') {
    //                                     $mmail = str_replace("'", '', $m_emailid);
    //                                 }

    //                                 if ($f_email != '') {
    //                                     $fmail = str_replace("'", '', $f_email);
    //                                 }
    //                                 $formData = DB::table('online_admission_form')
    //                                     ->where('form_id', $form_ids[$i])->first();
    //                                 $form_class_id = $formData->class_id;
    //                                 $textmsg = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $form_class_id);
    //                                 $emailData = [
    //                                     'subject' => $short_name,
    //                                     'textmsg' => $textmsg,
    //                                 ];
    //                                 smart_mail($fmail, $short_name . ' - ' . "Admission Approved", 'emails.parentUserEmail', $emailData);
    //                                 smart_mail($mmail,  $short_name . ' - ' . "Admission Approved", 'emails.parentUserEmail', $emailData);
    //                             }
    //                         }
    //                     } elseif ($parent_id != '') {
    //                         $formRecord = DB::table('online_admission_form')
    //                             ->where('form_id', $form_id)
    //                             ->first();

    //                         $student_id_new = null;

    //                         $studentOldRecord = DB::table('student')
    //                             ->where('student_id', $formRecord->student_id)
    //                             ->first();

    //                         if ($studentOldRecord) {
    //                             $student_id_new = $studentOldRecord->student_id;
    //                         } else {
    //                             $student_id_new = DB::table('student')->insertGetId([
    //                                 'academic_yr' => $academic_yr,
    //                                 'parent_id' => $parent_id,
    //                                 'first_name' => $first_name,
    //                                 'mid_name' => $mid_name,
    //                                 'last_name' => $last_name,
    //                                 'dob' => $dob,
    //                                 'gender' => $gender,
    //                                 'class_id' => $class_id,
    //                                 'section_id' => $section_id,
    //                                 'religion' => $religion,
    //                                 'caste' => $caste,
    //                                 'IsDelete' => 'N',
    //                                 'isNew' => 'Y',
    //                                 'isModify' => 'N',
    //                                 'category' => $category,
    //                                 'mother_tongue' => $mother_tongue,
    //                                 'subcaste' => $sub_caste,
    //                                 'permant_add' => $perm_address,
    //                                 'city' => $city,
    //                                 'state' => $state,
    //                                 'pincode' => $pincode,
    //                                 'stu_aadhaar_no' => $stud_aadhar,
    //                                 'blood_group' => $blood_group,
    //                                 'admission_date' => date('Y-m-d'),
    //                                 'admission_class' => $class_name,
    //                                 'birth_place' => $birth_place,
    //                                 'nationality' => $nationality,
    //                                 'student_name' => $first_name,
    //                             ]);
    //                         }

    //                         if ($student_id_new) {

    //                             DB::table('online_admission_form')
    //                                 ->where('form_id', $form_id)
    //                                 ->update(['student_id' => $student_id_new]);

    //                             $password = bcrypt('arnolds');
    //                             $user_id1 = 'S' . str_pad($student_id_new, 4, '0', STR_PAD_LEFT);

    //                             DB::table('user_master')->insert([
    //                                 'user_id' => $user_id1,
    //                                 'name' => $first_name,
    //                                 'password' => $password,
    //                                 'reg_id' => $student_id_new,
    //                                 'role_id' => 'S',
    //                             ]);
    //                         }

    //                         $from = 'supportsacs@aceventura.in';
    //                         $cc = 'school@arnoldcentralschool.org';

    //                         if ($m_emailid != '') {
    //                             $mmail = str_replace("'", '', $m_emailid);
    //                         }

    //                         if ($f_email != '') {
    //                             $fmail = str_replace("'", '', $f_email);
    //                         }

    //                         $formData = DB::table('online_admission_form')
    //                             ->where('form_id', $form_id)->first();
    //                         $form_class_id = $formData->class_id;
    //                         $textmsg = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $form_class_id);
    //                         $emailData = [
    //                             'subject' => $short_name . ' - ',
    //                             'textmsg' => $textmsg,
    //                         ];
    //                         smart_mail($fmail, $short_name . ' - ' . "Admission Approved", 'emails.parentUserEmail', $emailData);
    //                         smart_mail($mmail,  $short_name . ' - ' . "Admission Approved", 'emails.parentUserEmail', $emailData);
    //                     }
    //                 }
    //             }
    //         }
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Forms are successfully approved.!!!',
    //         ], 200);
    //     } catch (Exception $e) {
    //         // dd($e);
    //         return response()->json([
    //             'status' => false,
    //             'errorMessage' => $e->getMessage(),
    //             'errorLine' => $e->getLine(),
    //         ], 500);
    //     }
    // }

    public function updateApprovalList(Request $request)
    {
        $logger = Log::channel('approve_admission');

        try {
            $form_ids = $request->input('form_ids');
            $class_id = $request->input('class_id');
            $section_id = $request->input('section_id');
            $short_name = JWTAuth::getPayload()->get('short_name');

            $logger->info('updateApprovalList started', [
                'short_name' => $short_name,
                'form_ids' => $form_ids,
                'class_id' => $class_id,
                'section_id' => $section_id,
            ]);

            $defaultPassword = DB::table('school_settings')
                ->where('short_name', $short_name)
                ->value('default_pwd');

            $passwordCode = 'arnolds';
            if ($defaultPassword == null) {
                $passwordCode = $short_name == 'HSCS' ? 'hscs' : 'arnolds';
            }

            if ($short_name == 'HSCS') {
                $logger->info('Processing HSCS school');
                for ($i = 0, $j = 1; $i < count($form_ids); $i++, $j++) {
                    if ($form_ids[$i] == '' || $form_ids[$i] == NULL) {
                        continue;
                    }

                    $form_id = $form_ids[$i];
                    $logger->info("Processing form_id: {$form_id} [HSCS]");

                    DB::table('online_admission_form')
                        ->where('form_id', $form_id)
                        ->update(['admission_form_status' => 'Approved']);

                    $application_data = DB::table('online_admission_form')->where('form_id', $form_id)->first();
                    $sibling_student_id = $application_data->sibling_student_id;
                    $father_name = $application_data->father_name;
                    $f_occupation = $application_data->father_occupation;
                    $f_mobile = $application_data->f_mobile;
                    $f_email = $application_data->f_email;
                    $mother_name = $application_data->mother_name;
                    $m_occupation = $application_data->mother_occupation;
                    $m_mobile = $application_data->m_mobile;
                    $m_emailid = $application_data->m_emailid;
                    $father_adhar_no = $application_data->f_aadhar_no;
                    $mother_adhar_no = $application_data->m_aadhar_no;
                    $f_qualification = $application_data->f_qualification;
                    $m_qualification = $application_data->m_qualification;
                    $academic_yr = $application_data->academic_yr;
                    $first_name = $application_data->first_name;
                    $mid_name = $application_data->mid_name;
                    $last_name = $application_data->last_name;
                    $dob = $application_data->dob;
                    $gender = $application_data->gender;
                    $application_date = $application_data->application_date;
                    $religion = $application_data->religion;
                    $caste = $application_data->caste;
                    $category = $application_data->category;
                    $nationality = $application_data->nationality;
                    $sms_sending_phone_no = $application_data->sms_sending_phone_no;
                    $class_id = $application_data->class_id;
                    $mother_tongue = $application_data->mother_tongue;
                    $sub_caste = $application_data->subcaste;
                    $perm_address = $application_data->perm_address;
                    $city = $application_data->city;
                    $state = $application_data->state;
                    $pincode = $application_data->pincode;
                    $stud_aadhar = $application_data->stud_aadhar;
                    $blood_group = $application_data->blood_group;
                    $birth_place = $application_data->birth_place;
                    $class_name = DB::table('class')->where('class_id', $class_id)->value('name');

                    if ($sibling_student_id != 0) {
                        $logger->info("form_id {$form_id}: sibling path, sibling_student_id={$sibling_student_id}");

                        $parent = DB::table('student')->select('parent_id')->where('student_id', $sibling_student_id)->first();
                        $parent_id = $parent ? $parent->parent_id : null;
                        $formRecord = DB::table('online_admission_form')->where('form_id', $form_id)->first();

                        $student_id_new = null;
                        $studentOldRecord = DB::table('student')->where('student_id', $formRecord->student_id)->first();

                        if ($studentOldRecord) {
                            $student_id_new = $studentOldRecord->student_id;
                            $logger->info("form_id {$form_id}: existing student found, student_id={$student_id_new}");
                        } else {
                            $student_id_new = DB::table('student')->insertGetId([
                                'academic_yr' => $academic_yr,
                                'parent_id' => $parent_id,
                                'first_name' => $first_name,
                                'mid_name' => $mid_name,
                                'last_name' => $last_name,
                                'dob' => $dob,
                                'gender' => $gender,
                                'class_id' => $class_id,
                                'section_id' => $section_id,
                                'religion' => $religion,
                                'caste' => $caste,
                                'IsDelete' => 'N',
                                'isNew' => 'Y',
                                'isModify' => 'N',
                                'category' => $category,
                                'mother_tongue' => $mother_tongue,
                                'subcaste' => $sub_caste,
                                'permant_add' => $perm_address,
                                'city' => $city,
                                'state' => $state,
                                'pincode' => $pincode,
                                'stu_aadhaar_no' => $stud_aadhar,
                                'blood_group' => $blood_group,
                                'admission_date' => date('Y-m-d'),
                                'admission_class' => $class_name,
                                'birth_place' => $birth_place,
                                'nationality' => $nationality,
                                'student_name' => $first_name,
                            ]);
                            $logger->info("form_id {$form_id}: new student inserted, student_id={$student_id_new}");
                        }

                        if ($student_id_new) {
                            DB::table('online_admission_form')->where('form_id', $form_id)->update(['student_id' => $student_id_new]);

                            $password = bcrypt('arnolds');
                            $user_id1 = 'S' . str_pad($student_id_new, 4, '0', STR_PAD_LEFT);
                            DB::table('user_master')->insert([
                                'user_id' => $user_id1,
                                'name' => $first_name,
                                'password' => $password,
                                'reg_id' => $student_id_new,
                                'role_id' => 'S',
                            ]);
                            $logger->info("form_id {$form_id}: user_master inserted for student user_id={$user_id1}");

                            $fees_category = DB::table('fees_category_detail')->where('class_concession', $class_id)->select('fees_category_id')->first();
                            if ($fees_category && $fees_category->fees_category_id) {
                                $fees_category_id = $fees_category->fees_category_id;
                                $fee_cat_query = DB::table('fees_student_category')->where(['student_id' => $student_id_new, 'fees_category_id' => $fees_category_id])->count();
                                if ($fee_cat_query == 0) {
                                    DB::table('fees_student_category')->insert(['student_id' => $student_id_new, 'fees_category_id' => $fees_category_id, 'academic_yr' => $academic_yr]);
                                    $logger->info("form_id {$form_id}: fees_student_category inserted");
                                }
                            }
                        }

                        $mmail = ($m_emailid != '') ? str_replace("'", '', $m_emailid) : '';
                        $fmail = ($f_email != '') ? str_replace("'", '', $f_email) : '';
                        $formData = DB::table('online_admission_form')->where('form_id', $form_id)->first();
                        $form_class_id = $formData->class_id;
                        $textmsg = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $form_class_id);
                        $emailData = ['subject' => $short_name . ' - ', 'textmsg' => $textmsg];

                        smart_mail($fmail, $short_name . ' - Admission Approved', 'emails.parentUserEmail', $emailData);
                        smart_mail($mmail, $short_name . ' - Admission Approved', 'emails.parentUserEmail', $emailData);

                        if (env('APP_ENV') == 'production') {
                            $cc = 'school@arnoldcentralschoolpune.edu.in';
                            smart_mail($cc, $short_name . ' - Admission Approved', 'emails.parentUserEmail', $emailData);
                        }
                        $logger->info("form_id {$form_id}: emails sent to fmail={$fmail}, mmail={$mmail}");
                    } else {
                        $logger->info("form_id {$form_id}: non-sibling path [HSCS]");

                        $parent_id = '';
                        if (!is_null($f_mobile)) {
                            $parent_id = DB::table('parent')->where('f_mobile', $f_mobile)->value('parent_id');
                        }
                        if (empty($parent_id) && $f_email !== null) {
                            $parent_id = DB::table('user_master')->where('user_id', $f_email)->value('reg_id');
                        }
                        if (empty($parent_id) && $m_emailid !== null) {
                            $parent_id = DB::table('user_master')->where('user_id', $m_emailid)->value('reg_id');
                        }
                        if (empty($parent_id) && $m_mobile !== null) {
                            $parent_id = DB::table('user_master')->where('user_id', $m_mobile)->value('reg_id');
                        }

                        $logger->info("form_id {$form_id}: resolved parent_id={$parent_id}");

                        if ($parent_id == '') {
                            $parent_id = DB::table('parent')->insertGetId([
                                'father_name' => $father_name,
                                'father_occupation' => $f_occupation,
                                'f_mobile' => $f_mobile,
                                'f_email' => $f_email,
                                'parent_adhar_no' => $father_adhar_no,
                                'f_qualification' => $f_qualification,
                                'mother_name' => $mother_name,
                                'mother_occupation' => $m_occupation,
                                'm_mobile' => $m_mobile,
                                'm_emailid' => $m_emailid,
                                'm_adhar_no' => $mother_adhar_no,
                                'm_qualification' => $m_qualification,
                                'IsDelete' => 'N',
                            ]);
                            $logger->info("form_id {$form_id}: new parent inserted, parent_id={$parent_id}");

                            if ($parent_id) {
                                if (is_null($f_email) || $f_email === 'null' || trim($f_email) === '' || trim($f_email) === "''") {
                                    if (is_null($m_emailid) || $m_emailid === 'null' || trim($m_emailid) === '' || trim($m_emailid) === "''") {
                                        if ($last_name !== 'null' && $father_name !== 'null') {
                                            $user_id = str_replace(' ', '', $father_name) . $last_name;
                                        } elseif ($last_name !== 'null') {
                                            $user_id = str_replace(' ', '', $last_name);
                                        } elseif ($father_name !== 'null') {
                                            $user_id = str_replace(' ', '', $father_name);
                                        }
                                    } else {
                                        $user_id = $m_emailid;
                                    }
                                } else {
                                    $user_id = $f_email;
                                }

                                $user_id = str_replace("''", '', $user_id);
                                $name = ($father_name !== 'null') ? $father_name : $mother_name;
                                $password = bcrypt($passwordCode);

                                $usql = DB::table('user_master')->insert([
                                    'user_id' => $user_id,
                                    'name' => $name,
                                    'password' => $password,
                                    'reg_id' => $parent_id,
                                    'role_id' => 'P',
                                ]);
                                $logger->info("form_id {$form_id}: parent user_master inserted, user_id={$user_id}, usql={$usql}");

                                $user_id = str_replace("'", '', $user_id);

                                if ($usql) {
                                    $resp = createUserInEvolvu($user_id);
                                    // $evolvuUrl = config('externalapis.EVOLVU_URL');
                                    // $response = Http::withHeaders(['Content-Type' => 'application/json'])
                                    //     ->post($evolvuUrl . 'user_create_post', ['user_id' => $user_id, 'school_id' => $school_id]);
                                    // $err = $response->failed() ? $response->status() : null;
                                    // $logger->info("form_id {$form_id}: evolvu API called", ['status' => $response->status(), 'err' => $err]);
                                    $logger->info("form_id {$form_id}: evolvu API called", ['response' => $resp]);

                                    $phone_no = ($sms_sending_phone_no != '') ? $sms_sending_phone_no : $f_mobile;
                                    DB::table('contact_details')->insert([
                                        'id' => $parent_id,
                                        'phone_no' => $phone_no,
                                        'email_id' => $f_email,
                                        'm_emailid' => $m_emailid,
                                    ]);

                                    $formRecord = DB::table('online_admission_form')->where('form_id', $form_id)->first();
                                    $student_id_new = null;
                                    $studentOldRecord = DB::table('student')->where('student_id', $formRecord->student_id)->first();

                                    $logger->info("form_id {$form_id}: checking existing student, form->student_id={$formRecord->student_id}");

                                    if ($studentOldRecord) {
                                        // ✅ BUG FIX: was ->studentId (wrong), now ->student_id (correct)
                                        $student_id_new = $studentOldRecord->student_id;
                                        $logger->info("form_id {$form_id}: existing student found, student_id={$student_id_new}");
                                    } else {
                                        $logger->info("form_id {$form_id}: no existing student found, inserting new student");
                                        $student_id_new = DB::table('student')->insertGetId([
                                            'academic_yr' => $academic_yr,
                                            'parent_id' => $parent_id,
                                            'first_name' => $first_name,
                                            'mid_name' => $mid_name,
                                            'last_name' => $last_name,
                                            'dob' => $dob,
                                            'gender' => $gender,
                                            'class_id' => $class_id,
                                            'section_id' => $section_id,
                                            'religion' => $religion,
                                            'caste' => $caste,
                                            'IsDelete' => 'N',
                                            'isNew' => 'Y',
                                            'isModify' => 'N',
                                            'category' => $category,
                                            'mother_tongue' => $mother_tongue,
                                            'subcaste' => $sub_caste,
                                            'permant_add' => $perm_address,
                                            'city' => $city,
                                            'state' => $state,
                                            'pincode' => $pincode,
                                            'stu_aadhaar_no' => $stud_aadhar,
                                            'blood_group' => $blood_group,
                                            'admission_date' => date('Y-m-d'),
                                            'admission_class' => $class_name,
                                            'birth_place' => $birth_place,
                                            'nationality' => $nationality,
                                            'student_name' => $first_name,
                                        ]);
                                        $logger->info("form_id {$form_id}: new student inserted, student_id={$student_id_new}");
                                    }

                                    if ($student_id_new) {
                                        DB::table('online_admission_form')->where('form_id', $form_id)->update(['student_id' => $student_id_new]);

                                        $password = $passwordCode;
                                        $user_id1 = 'S' . str_pad($student_id_new, 4, '0', STR_PAD_LEFT);
                                        DB::table('user_master')->insert([
                                            'user_id' => $user_id1,
                                            'name' => $first_name,
                                            'password' => $password,
                                            'reg_id' => $student_id_new,
                                            'role_id' => 'S',
                                        ]);
                                        $logger->info("form_id {$form_id}: student user_master inserted user_id={$user_id1}");

                                        $fees_category = DB::table('fees_category_detail')->where('class_concession', $class_id)->select('fees_category_id')->first();
                                        if ($fees_category && $fees_category->fees_category_id) {
                                            $fees_category_id = $fees_category->fees_category_id;
                                            $fee_cat_query = DB::table('fees_student_category')->where(['student_id' => $student_id_new, 'fees_category_id' => $fees_category_id])->count();
                                            if ($fee_cat_query == 0) {
                                                DB::table('fees_student_category')->insert(['student_id' => $student_id_new, 'fees_category_id' => $fees_category_id, 'academic_yr' => $academic_yr]);
                                                $logger->info("form_id {$form_id}: fees_student_category inserted");
                                            }
                                        }
                                    } else {
                                        $logger->error("form_id {$form_id}: student_id_new is null after insert/lookup — student NOT created");
                                    }

                                    DB::table('online_admission_form')->where('form_id', $form_id)->update(['student_id' => $student_id_new]);

                                    $mmail = ($m_emailid != '') ? str_replace("'", '', $m_emailid) : '';
                                    $fmail = ($f_email != '') ? str_replace("'", '', $f_email) : '';
                                    $formData = DB::table('online_admission_form')->where('form_id', $form_ids[$i])->first();
                                    $form_class_id = $formData->class_id;
                                    $textmsg = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $form_class_id);
                                    $emailData = ['subject' => $short_name, 'textmsg' => $textmsg];

                                    smart_mail($fmail, $short_name . ' - Admission Approved', 'emails.parentUserEmail', $emailData);
                                    smart_mail($mmail, $short_name . ' - Admission Approved', 'emails.parentUserEmail', $emailData);
                                    $logger->info("form_id {$form_id}: emails sent fmail={$fmail}, mmail={$mmail}");
                                }
                            }
                        } elseif ($parent_id != '') {
                            $logger->info("form_id {$form_id}: existing parent found, parent_id={$parent_id} [HSCS]");

                            $formRecord = DB::table('online_admission_form')->where('form_id', $form_id)->first();
                            $student_id_new = null;
                            $studentOldRecord = DB::table('student')->where('student_id', $formRecord->student_id)->first();

                            if ($studentOldRecord) {
                                $student_id_new = $studentOldRecord->student_id;
                                $logger->info("form_id {$form_id}: existing student found, student_id={$student_id_new}");
                            } else {
                                $student_id_new = DB::table('student')->insertGetId([
                                    'academic_yr' => $academic_yr,
                                    'parent_id' => $parent_id,
                                    'first_name' => $first_name,
                                    'mid_name' => $mid_name,
                                    'last_name' => $last_name,
                                    'dob' => $dob,
                                    'gender' => $gender,
                                    'class_id' => $class_id,
                                    'section_id' => $section_id,
                                    'religion' => $religion,
                                    'caste' => $caste,
                                    'IsDelete' => 'N',
                                    'isNew' => 'Y',
                                    'isModify' => 'N',
                                    'category' => $category,
                                    'mother_tongue' => $mother_tongue,
                                    'subcaste' => $sub_caste,
                                    'permant_add' => $perm_address,
                                    'city' => $city,
                                    'state' => $state,
                                    'pincode' => $pincode,
                                    'stu_aadhaar_no' => $stud_aadhar,
                                    'blood_group' => $blood_group,
                                    'admission_date' => date('Y-m-d'),
                                    'admission_class' => $class_name,
                                    'birth_place' => $birth_place,
                                    'nationality' => $nationality,
                                    'student_name' => $first_name,
                                ]);
                                $logger->info("form_id {$form_id}: new student inserted, student_id={$student_id_new}");
                            }

                            if ($student_id_new) {
                                DB::table('online_admission_form')->where('form_id', $form_id)->update(['student_id' => $student_id_new]);

                                $password = bcrypt('arnolds');
                                $user_id1 = 'S' . str_pad($student_id_new, 4, '0', STR_PAD_LEFT);
                                DB::table('user_master')->insert([
                                    'user_id' => $user_id1,
                                    'name' => $first_name,
                                    'password' => $password,
                                    'reg_id' => $student_id_new,
                                    'role_id' => 'S',
                                ]);
                                $logger->info("form_id {$form_id}: student user_master inserted user_id={$user_id1}");

                                $fees_category = DB::table('fees_category_detail')->where('class_concession', $class_id)->select('fees_category_id')->first();
                                if ($fees_category && $fees_category->fees_category_id) {
                                    $fees_category_id = $fees_category->fees_category_id;
                                    $fee_cat_query = DB::table('fees_student_category')->where(['student_id' => $student_id_new, 'fees_category_id' => $fees_category_id])->count();
                                    if ($fee_cat_query == 0) {
                                        DB::table('fees_student_category')->insert(['student_id' => $student_id_new, 'fees_category_id' => $fees_category_id, 'academic_yr' => $academic_yr]);
                                    }
                                }
                            } else {
                                $logger->error("form_id {$form_id}: student_id_new is null — student NOT created [HSCS existing parent]");
                            }

                            $mmail = ($m_emailid != '') ? str_replace("'", '', $m_emailid) : '';
                            $fmail = ($f_email != '') ? str_replace("'", '', $f_email) : '';
                            $formData = DB::table('online_admission_form')->where('form_id', $form_id)->first();
                            $form_class_id = $formData->class_id;
                            $textmsg = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $form_class_id);

                            if ($class_name == 'Nursery') {
                                $subject = 'Information for Nursery admission';
                            } elseif ($class_name == '11') {  // ✅ Note: original had assignment = not comparison ==
                                $subject = 'Information for Class 11 admission';
                            } else {
                                $subject = 'Admission Approved';
                            }

                            $emailData = ['subject' => $short_name . ' - ' . $subject, 'textmsg' => $textmsg];
                            smart_mail($fmail, $short_name . ' - ' . $subject, 'emails.parentUserEmail', $emailData);
                            smart_mail($mmail, $short_name . ' - ' . $subject, 'emails.parentUserEmail', $emailData);
                            $logger->info("form_id {$form_id}: emails sent fmail={$fmail}, mmail={$mmail}");
                        }
                    }
                }
            } else {
                // Non-HSCS schools
                $logger->info("Processing SACS school: {$short_name}");

                for ($i = 0, $j = 1; $i < count($form_ids); $i++, $j++) {
                    if ($form_ids[$i] == '' || $form_ids[$i] == NULL) {
                        continue;
                    }

                    $form_id = $form_ids[$i];
                    $logger->info("Processing form_id: {$form_id} [non-HSCS]");

                    DB::table('online_admission_form')
                        ->where('form_id', $form_id)
                        ->update(['admission_form_status' => 'Approved']);

                    $application_data = DB::table('online_admission_form')->where('form_id', $form_id)->first();
                    $sibling_student_id = $application_data->sibling_student_id;
                    $father_name = $application_data->father_name;
                    $f_occupation = $application_data->father_occupation;
                    $f_mobile = $application_data->f_mobile;
                    $f_email = $application_data->f_email;
                    $mother_name = $application_data->mother_name;
                    $m_occupation = $application_data->mother_occupation;
                    $m_mobile = $application_data->m_mobile;
                    $m_emailid = $application_data->m_emailid;
                    $father_adhar_no = $application_data->f_aadhar_no;
                    $mother_adhar_no = $application_data->m_aadhar_no;
                    $f_qualification = $application_data->f_qualification;
                    $m_qualification = $application_data->m_qualification;
                    $academic_yr = $application_data->academic_yr;
                    $first_name = $application_data->first_name;
                    $mid_name = $application_data->mid_name;
                    $last_name = $application_data->last_name;
                    $dob = $application_data->dob;
                    $gender = $application_data->gender;
                    $religion = $application_data->religion;
                    $caste = $application_data->caste;
                    $category = $application_data->category;
                    $nationality = $application_data->nationality;
                    $sms_sending_phone_no = $application_data->sms_sending_phone_no;
                    $class_id = $application_data->class_id;
                    $mother_tongue = $application_data->mother_tongue;
                    $sub_caste = $application_data->subcaste;
                    $perm_address = $application_data->perm_address;
                    $city = $application_data->city;
                    $state = $application_data->state;
                    $pincode = $application_data->pincode;
                    $stud_aadhar = $application_data->stud_aadhar;
                    $blood_group = $application_data->blood_group;
                    $birth_place = $application_data->birth_place;
                    $class_name = DB::table('class')->where('class_id', $class_id)->value('name');

                    $parent_id = '';
                    if (!is_null($f_mobile)) {
                        $parent_id = DB::table('parent')->where('f_mobile', $f_mobile)->value('parent_id');
                    }
                    if (empty($parent_id) && $f_email !== null) {
                        $parent_id = DB::table('user_master')->where('user_id', $f_email)->value('reg_id');
                    }
                    if (empty($parent_id) && $m_emailid !== null) {
                        $parent_id = DB::table('user_master')->where('user_id', $m_emailid)->value('reg_id');
                    }
                    if (empty($parent_id) && $m_mobile !== null) {
                        $parent_id = DB::table('user_master')->where('user_id', $m_mobile)->value('reg_id');
                    }

                    $logger->info("form_id {$form_id}: resolved parent_id={$parent_id}");

                    if ($parent_id == '') {
                        $parent_id = DB::table('parent')->insertGetId([
                            'father_name' => $father_name,
                            'father_occupation' => $f_occupation,
                            'f_mobile' => $f_mobile,
                            'f_email' => $f_email,
                            'parent_adhar_no' => $father_adhar_no,
                            'f_qualification' => $f_qualification,
                            'mother_name' => $mother_name,
                            'mother_occupation' => $m_occupation,
                            'm_mobile' => $m_mobile,
                            'm_emailid' => $m_emailid,
                            'm_adhar_no' => $mother_adhar_no,
                            'm_qualification' => $m_qualification,
                            'IsDelete' => 'N',
                        ]);
                        $logger->info("form_id {$form_id}: new parent inserted, parent_id={$parent_id}");

                        if ($parent_id) {
                            if (is_null($f_email) || $f_email === 'null' || trim($f_email) === '' || trim($f_email) === "''") {
                                if (is_null($m_emailid) || $m_emailid === 'null' || trim($m_emailid) === '' || trim($m_emailid) === "''") {
                                    if ($last_name !== 'null' && $father_name !== 'null') {
                                        $user_id = str_replace(' ', '', $father_name) . $last_name;
                                    } elseif ($last_name !== 'null') {
                                        $user_id = str_replace(' ', '', $last_name);
                                    } elseif ($father_name !== 'null') {
                                        $user_id = str_replace(' ', '', $father_name);
                                    }
                                } else {
                                    $user_id = $m_emailid;
                                }
                            } else {
                                $user_id = $f_email;
                            }

                            $user_id = str_replace("''", '', $user_id);
                            $name = ($father_name !== 'null') ? $father_name : $mother_name;
                            $password = bcrypt($passwordCode);

                            $usql = DB::table('user_master')->insert([
                                'user_id' => $user_id,
                                'name' => $name,
                                'password' => $password,
                                'reg_id' => $parent_id,
                                'role_id' => 'P',
                            ]);

                            $logger->info("form_id {$form_id}: parent user_master inserted user_id={$user_id}, usql={$usql}");

                            $user_id = str_replace("'", '', $user_id);

                            if ($usql) {
                                $resp = createUserInEvolvu($user_id);
                                // $school_id = '1';
                                // $evolvuUrl = config('externalapis.EVOLVU_URL');
                                // $response = Http::withHeaders(['Content-Type' => 'application/json'])
                                //     ->post($evolvuUrl . 'user_create_post', ['user_id' => $user_id, 'school_id' => $school_id]);
                                // $err = $response->failed() ? $response->status() : null;
                                // $logger->info("form_id {$form_id}: evolvu API called", ['status' => $response->status(), 'err' => $err]);
                                $logger->info("form_id {$form_id}: evolvu API called", ['response' => $resp]);

                                $phone_no = ($sms_sending_phone_no != '') ? $sms_sending_phone_no : $f_mobile;
                                DB::table('contact_details')->insert([
                                    'id' => $parent_id,
                                    'phone_no' => $phone_no,
                                    'email_id' => $f_email,
                                    'm_emailid' => $m_emailid,
                                ]);

                                $formRecord = DB::table('online_admission_form')->where('form_id', $form_id)->first();
                                $student_id_new = null;
                                $studentOldRecord = DB::table('student')->where('student_id', $formRecord->student_id)->first();

                                $logger->info("form_id {$form_id}: form->student_id={$formRecord->student_id}, studentOldRecord=" . ($studentOldRecord ? 'found' : 'not found'));

                                if ($studentOldRecord) {
                                    $student_id_new = $studentOldRecord->student_id;
                                    $logger->info("form_id {$form_id}: existing student found, student_id={$student_id_new}");
                                } else {
                                    $logger->info("form_id {$form_id}: inserting new student record");
                                    $student_id_new = DB::table('student')->insertGetId([
                                        'academic_yr' => $academic_yr,
                                        'parent_id' => $parent_id,
                                        'first_name' => $first_name,
                                        'mid_name' => $mid_name,
                                        'last_name' => $last_name,
                                        'dob' => $dob,
                                        'gender' => $gender,
                                        'class_id' => $class_id,
                                        'section_id' => $section_id,
                                        'religion' => $religion,
                                        'caste' => $caste,
                                        'IsDelete' => 'N',
                                        'isNew' => 'Y',
                                        'isModify' => 'N',
                                        'category' => $category,
                                        'mother_tongue' => $mother_tongue,
                                        'subcaste' => $sub_caste,
                                        'permant_add' => $perm_address,
                                        'city' => $city,
                                        'state' => $state,
                                        'pincode' => $pincode,
                                        'stu_aadhaar_no' => $stud_aadhar,
                                        'blood_group' => $blood_group,
                                        'admission_date' => date('Y-m-d'),
                                        'admission_class' => $class_name,
                                        'birth_place' => $birth_place,
                                        'nationality' => $nationality,
                                        'student_name' => $first_name,
                                    ]);
                                    $logger->info("form_id {$form_id}: new student inserted, student_id={$student_id_new}");
                                }

                                if ($student_id_new) {
                                    DB::table('online_admission_form')->where('form_id', $form_id)->update(['student_id' => $student_id_new]);

                                    $password = $passwordCode;
                                    $user_id1 = 'S' . str_pad($student_id_new, 4, '0', STR_PAD_LEFT);
                                    DB::table('user_master')->insert([
                                        'user_id' => $user_id1,
                                        'name' => $first_name,
                                        'password' => $password,
                                        'reg_id' => $student_id_new,
                                        'role_id' => 'S',
                                    ]);
                                    $logger->info("form_id {$form_id}: student user_master inserted user_id={$user_id1}");
                                } else {
                                    $logger->error("form_id {$form_id}: student_id_new is null — student NOT created [non-HSCS new parent]");
                                }

                                DB::table('online_admission_form')->where('form_id', $form_id)->update(['student_id' => $student_id_new]);

                                $mmail = ($m_emailid != '') ? str_replace("'", '', $m_emailid) : '';
                                $fmail = ($f_email != '') ? str_replace("'", '', $f_email) : '';
                                $formData = DB::table('online_admission_form')->where('form_id', $form_ids[$i])->first();
                                $form_class_id = $formData->class_id;
                                $textmsg = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $form_class_id);
                                $emailData = ['subject' => $short_name, 'textmsg' => $textmsg];

                                smart_mail($fmail, $short_name . ' - Admission Approved', 'emails.parentUserEmail', $emailData);
                                smart_mail($mmail, $short_name . ' - Admission Approved', 'emails.parentUserEmail', $emailData);
                                $logger->info("form_id {$form_id}: emails sent fmail={$fmail}, mmail={$mmail}");
                            }
                        }
                    } elseif ($parent_id != '') {
                        $logger->info("form_id {$form_id}: existing parent path [non-HSCS], parent_id={$parent_id}");

                        $formRecord = DB::table('online_admission_form')->where('form_id', $form_id)->first();
                        $student_id_new = null;
                        $studentOldRecord = DB::table('student')->where('student_id', $formRecord->student_id)->first();

                        if ($studentOldRecord) {
                            $student_id_new = $studentOldRecord->student_id;
                            $logger->info("form_id {$form_id}: existing student found, student_id={$student_id_new}");
                        } else {
                            $student_id_new = DB::table('student')->insertGetId([
                                'academic_yr' => $academic_yr,
                                'parent_id' => $parent_id,
                                'first_name' => $first_name,
                                'mid_name' => $mid_name,
                                'last_name' => $last_name,
                                'dob' => $dob,
                                'gender' => $gender,
                                'class_id' => $class_id,
                                'section_id' => $section_id,
                                'religion' => $religion,
                                'caste' => $caste,
                                'IsDelete' => 'N',
                                'isNew' => 'Y',
                                'isModify' => 'N',
                                'category' => $category,
                                'mother_tongue' => $mother_tongue,
                                'subcaste' => $sub_caste,
                                'permant_add' => $perm_address,
                                'city' => $city,
                                'state' => $state,
                                'pincode' => $pincode,
                                'stu_aadhaar_no' => $stud_aadhar,
                                'blood_group' => $blood_group,
                                'admission_date' => date('Y-m-d'),
                                'admission_class' => $class_name,
                                'birth_place' => $birth_place,
                                'nationality' => $nationality,
                                'student_name' => $first_name,
                            ]);
                            $logger->info("form_id {$form_id}: new student inserted, student_id={$student_id_new}");
                        }

                        if ($student_id_new) {
                            DB::table('online_admission_form')->where('form_id', $form_id)->update(['student_id' => $student_id_new]);

                            $password = bcrypt('arnolds');
                            $user_id1 = 'S' . str_pad($student_id_new, 4, '0', STR_PAD_LEFT);
                            DB::table('user_master')->insert([
                                'user_id' => $user_id1,
                                'name' => $first_name,
                                'password' => $password,
                                'reg_id' => $student_id_new,
                                'role_id' => 'S',
                            ]);
                            $logger->info("form_id {$form_id}: student user_master inserted user_id={$user_id1}");

                            $fees_category = DB::table('fees_category_detail')->where('class_concession', $class_id)->select('fees_category_id')->first();
                            if ($fees_category && $fees_category->fees_category_id) {
                                $fees_category_id = $fees_category->fees_category_id;
                                $fee_cat_query = DB::table('fees_student_category')->where(['student_id' => $student_id_new, 'fees_category_id' => $fees_category_id])->count();
                                if ($fee_cat_query == 0) {
                                    DB::table('fees_student_category')->insert(['student_id' => $student_id_new, 'fees_category_id' => $fees_category_id, 'academic_yr' => $academic_yr]);
                                }
                            }
                        } else {
                            $logger->error("form_id {$form_id}: student_id_new is null — student NOT created [non-HSCS existing parent]");
                        }

                        $mmail = ($m_emailid != '') ? str_replace("'", '', $m_emailid) : '';
                        $fmail = ($f_email != '') ? str_replace("'", '', $f_email) : '';
                        $formData = DB::table('online_admission_form')->where('form_id', $form_id)->first();
                        $form_class_id = $formData->class_id;
                        $textmsg = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $form_class_id);
                        $emailData = ['subject' => $short_name . ' - ', 'textmsg' => $textmsg];

                        smart_mail($fmail, $short_name . ' - Admission Approved', 'emails.parentUserEmail', $emailData);
                        smart_mail($mmail, $short_name . ' - Admission Approved', 'emails.parentUserEmail', $emailData);

                        if (env('APP_ENV') == 'production') {
                            $cc = 'school@arnoldcentralschoolpune.edu.in';
                            smart_mail($cc, $short_name . ' - Admission Approved', 'emails.parentUserEmail', $emailData);
                        }

                        $logger->info("form_id {$form_id}: emails sent fmail={$fmail}, mmail={$mmail}");
                    }
                }
            }

            $logger->info('updateApprovalList completed successfully');

            return response()->json([
                'status' => true,
                'message' => 'Forms are successfully approved.!!!',
            ], 200);
        } catch (Exception $e) {
            Log::channel('approve_admission')->error('updateApprovalList exception', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
            ], 500);
        }
    }

    // optmized version:
    // public function updateApprovalList(Request $request)
    // {
    //     try {
    //         $form_ids   = $request->input('form_ids');
    //         $section_id = $request->input('section_id');
    //         $short_name = JWTAuth::getPayload()->get('short_name');

    //         $defaultPassword = DB::table('school_settings')->where('short_name', $short_name)->value('default_pwd');
    //         $passwordCode = $defaultPassword ?? ($short_name == 'HSCS' ? 'hscs' : 'arnolds');
    //         $school_id = $short_name == 'HSCS' ? '7' : '1';

    //         foreach ($form_ids as $form_id) {
    //             if (empty($form_id)) continue;

    //             DB::table('online_admission_form')
    //                 ->where('form_id', $form_id)
    //                 ->update(['admission_form_status' => 'Approved']);

    //             $app = DB::table('online_admission_form')->where('form_id', $form_id)->first();
    //             $class_name = DB::table('class')->where('class_id', $app->class_id)->value('name');

    //             $parent_id = $app->sibling_student_id
    //                 ? $this->getParentIdFromSibling($app->sibling_student_id)
    //                 : $this->resolveOrCreateParent($app, $passwordCode, $school_id, $section_id);

    //             if ($parent_id) {
    //                 $student_id = $this->resolveOrCreateStudent($app, $form_id, $parent_id, $section_id, $class_name, $passwordCode);
    //                 $this->assignFeesCategory($student_id, $app->class_id, $app->academic_yr);
    //                 $this->sendApprovalEmails($app, $form_id, $class_name, $short_name);
    //             }
    //         }

    //         return response()->json(['status' => true, 'message' => 'Forms are successfully approved.!!!'], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status'       => false,
    //             'errorMessage' => $e->getMessage(),
    //             'errorLine'    => $e->getLine(),
    //         ], 500);
    //     }
    // }

    // // --- Helper Methods ---

    // private function getParentIdFromSibling(int $sibling_student_id): ?int
    // {
    //     $parent = DB::table('student')->where('student_id', $sibling_student_id)->value('parent_id');
    //     return $parent ?: null;
    // }

    // private function resolveOrCreateParent(object $app, string $passwordCode, string $school_id, string $section_id): ?int
    // {
    //     $parent_id = null;

    //     if (!is_null($app->f_mobile))  $parent_id = DB::table('parent')->where('f_mobile', $app->f_mobile)->value('parent_id');
    //     if (!$parent_id && $app->f_email)   $parent_id = DB::table('user_master')->where('user_id', $app->f_email)->value('reg_id');
    //     if (!$parent_id && $app->m_emailid) $parent_id = DB::table('user_master')->where('user_id', $app->m_emailid)->value('reg_id');
    //     if (!$parent_id && $app->m_mobile)  $parent_id = DB::table('user_master')->where('user_id', $app->m_mobile)->value('reg_id');

    //     if ($parent_id) return $parent_id;

    //     $parent_id = DB::table('parent')->insertGetId([
    //         'father_name'       => $app->father_name,
    //         'father_occupation' => $app->father_occupation,
    //         'f_mobile'          => $app->f_mobile,
    //         'f_email'           => $app->f_email,
    //         'parent_adhar_no'   => $app->f_aadhar_no,
    //         'f_qualification'   => $app->f_qualification,
    //         'mother_name'       => $app->mother_name,
    //         'mother_occupation' => $app->mother_occupation,
    //         'm_mobile'          => $app->m_mobile,
    //         'm_emailid'         => $app->m_emailid,
    //         'm_adhar_no'        => $app->m_aadhar_no,
    //         'm_qualification'   => $app->m_qualification,
    //         'IsDelete'          => 'N',
    //     ]);

    //     if (!$parent_id) return null;

    //     $user_id = $this->resolveParentUserId($app);
    //     $name    = ($app->father_name !== 'null') ? $app->father_name : $app->mother_name;

    //     $usql = DB::table('user_master')->insertGetId([
    //         'user_id'  => $user_id,
    //         'name'     => $name,
    //         'password' => bcrypt($passwordCode),
    //         'reg_id'   => $parent_id,
    //         'role_id'  => 'P',
    //     ]);

    //     if ($usql) {
    //         $evolvuUrl = config('externalapis.EVOLVU_URL');
    //         Http::withHeaders(['Content-Type' => 'application/json'])
    //             ->post($evolvuUrl . 'user_create_post', ['user_id' => $user_id, 'school_id' => $school_id]);

    //         $phone_no = ($app->sms_sending_phone_no != '') ? $app->sms_sending_phone_no : $app->f_mobile;
    //         DB::table('contact_details')->insert([
    //             'id'        => $parent_id,
    //             'phone_no'  => $phone_no,
    //             'email_id'  => $app->f_email,
    //             'm_emailid' => $app->m_emailid,
    //         ]);
    //     }

    //     return $parent_id;
    // }

    // private function resolveParentUserId(object $app): string
    // {
    //     $f_email   = $app->f_email;
    //     $m_emailid = $app->m_emailid;
    //     $f_mobile  = $app->f_mobile;
    //     $m_mobile  = $app->m_mobile;

    //     $isBlank = fn($v) => is_null($v) || in_array(trim($v), ['', 'null', "''"]);

    //     if (!$isBlank($f_email))   return str_replace("'", '', $f_email);
    //     if (!$isBlank($m_emailid)) return str_replace("'", '', $m_emailid);
    //     if (!$isBlank($f_mobile))  return $f_mobile;
    //     if (!$isBlank($m_mobile))  return $m_mobile;

    //     $last   = $app->last_name !== 'null' ? $app->last_name : '';
    //     $father = $app->father_name !== 'null' ? str_replace(' ', '', $app->father_name) : '';

    //     return str_replace("''", '', $father . $last);
    // }

    // private function resolveOrCreateStudent(object $app, $form_id, int $parent_id, string $section_id, string $class_name, string $passwordCode): ?int
    // {
    //     $formRecord = DB::table('online_admission_form')->where('form_id', $form_id)->first();
    //     $existing   = DB::table('student')->where('student_id', $formRecord->student_id)->first();

    //     $student_id = $existing
    //         ? $existing->student_id
    //         : DB::table('student')->insertGetId([
    //             'academic_yr'    => $app->academic_yr,
    //             'parent_id'      => $parent_id,
    //             'first_name'     => $app->first_name,
    //             'mid_name'       => $app->mid_name,
    //             'last_name'      => $app->last_name,
    //             'dob'            => $app->dob,
    //             'gender'         => $app->gender,
    //             'class_id'       => $app->class_id,
    //             'section_id'     => $section_id,
    //             'religion'       => $app->religion,
    //             'caste'          => $app->caste,
    //             'IsDelete'       => 'N',
    //             'isNew'          => 'Y',
    //             'isModify'       => 'N',
    //             'category'       => $app->category,
    //             'mother_tongue'  => $app->mother_tongue,
    //             'subcaste'       => $app->subcaste,
    //             'permant_add'    => $app->perm_address,
    //             'city'           => $app->city,
    //             'state'          => $app->state,
    //             'pincode'        => $app->pincode,
    //             'stu_aadhaar_no' => $app->stud_aadhar,
    //             'blood_group'    => $app->blood_group,
    //             'admission_date' => date('Y-m-d'),
    //             'admission_class'=> $class_name,
    //             'birth_place'    => $app->birth_place,
    //             'nationality'    => $app->nationality,
    //             'student_name'   => $app->first_name,
    //         ]);

    //     if ($student_id) {
    //         DB::table('online_admission_form')->where('form_id', $form_id)->update(['student_id' => $student_id]);

    //         DB::table('user_master')->insert([
    //             'user_id'  => 'S' . str_pad($student_id, 4, '0', STR_PAD_LEFT),
    //             'name'     => $app->first_name,
    //             'password' => bcrypt($passwordCode),
    //             'reg_id'   => $student_id,
    //             'role_id'  => 'S',
    //         ]);
    //     }

    //     return $student_id;
    // }

    // private function assignFeesCategory(?int $student_id, $class_id, $academic_yr): void
    // {
    //     if (!$student_id) return;

    //     $fees_category = DB::table('fees_category_detail')
    //         ->where('class_concession', $class_id)
    //         ->value('fees_category_id');

    //     if ($fees_category) {
    //         $exists = DB::table('fees_student_category')
    //             ->where(['student_id' => $student_id, 'fees_category_id' => $fees_category])
    //             ->exists();

    //         if (!$exists) {
    //             DB::table('fees_student_category')->insert([
    //                 'student_id'       => $student_id,
    //                 'fees_category_id' => $fees_category,
    //                 'academic_yr'      => $academic_yr,
    //             ]);
    //         }
    //     }
    // }

    // private function sendApprovalEmails(object $app, $form_id, string $class_name, string $short_name): void
    // {
    //     $formData   = DB::table('online_admission_form')->where('form_id', $form_id)->first();
    //     $textmsg    = $this->getEmailBodyByKey('ADDMISSION_APPROVED', $formData->class_id);

    //     $subject = match(true) {
    //         $class_name === 'Nursery' => 'Information for Nursery admission',
    //         $class_name === '11'      => 'Information for Class 11 admission',
    //         default                   => 'Admission approved',
    //     };

    //     $emailData = ['subject' => "$short_name - $subject", 'textmsg' => $textmsg];

    //     $fmail = $app->f_email   ? str_replace("'", '', $app->f_email)   : null;
    //     $mmail = $app->m_emailid ? str_replace("'", '', $app->m_emailid) : null;

    //     if ($fmail) smart_mail($fmail, "$short_name - $subject", 'emails.parentUserEmail', $emailData);
    //     if ($mmail) smart_mail($mmail, "$short_name - $subject", 'emails.parentUserEmail', $emailData);
    // }

    // HSCS extra admission modules
    public function getAdmissionManagement(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_year = JWTAuth::getPayload()->get('academic_year');
        $role = $user->role_id;

        $data = DB::table('new_admission_class as a')
            ->leftJoin('class', 'class.class_id', '=', 'a.class_id')
            ->leftJoin('bank_account_name', 'bank_account_name.id', '=', 'a.account_id')
            ->select('a.*', 'class.name as class_name', 'bank_account_name.account_name')
            ->where('a.academic_yr', $academic_year)
            ->orderBy('a.class_id', 'ASC')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ], 200);
    }

    public function getAdmissionClassesNotCreated(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_year = JWTAuth::getPayload()->get('academic_year');

        // $data = DB::table('class')
        //     ->select(
        //         'class.class_id',
        //         'class.name',
        //         'class.academic_yr',
        //         'class.department_id'
        //     )
        //     ->where('class.academic_yr', $academic_year)
        //     ->whereNotIn('class.class_id', function ($query) {
        //         $query
        //             ->select('class_id')
        //             ->from('new_admission_class');
        //     })
        //     ->orderBy('class.class_id', 'asc')
        //     ->get();

        $data = DB::table('class')
            ->select(
                'class.class_id',
                'class.name',
                'class.academic_yr',
                'class.department_id'
            )
            ->where('class.academic_yr', $academic_year)
            ->orderBy('class.class_id', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ], 200);
    }

    public function createAdmissionForm(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            // ✅ Basic validation
            $request->validate([
                'class_id' => 'required|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'age_start_date' => 'nullable|date',
                'age_end_date' => 'nullable|date',
                'form_fee' => 'required|numeric',
                'account_id' => 'required|integer',
                'type' => 'nullable'
            ]);

            // ✅ Prepare data
            $data = [
                'class_id' => $request->input('class_id'),
                'start_date' => $request->input('start_date')
                    ? Carbon::parse($request->input('start_date'))->format('Y-m-d')
                    : null,
                'age_start_date' => $request->input('age_start_date')
                    ? Carbon::parse($request->input('age_start_date'))->format('Y-m-d')
                    : null,
                'age_end_date' => $request->input('age_end_date')
                    ? Carbon::parse($request->input('age_end_date'))->format('Y-m-d')
                    : null,
                'end_date' => $request->input('end_date')
                    ? Carbon::parse($request->input('end_date'))->format('Y-m-d')
                    : null,
                'application_form_fee' => $request->input('form_fee'),
                'publish' => $request->input('publish') ?? 'N',
                'academic_yr' => $academic_year,  // adjust if using JWT,
                'account_id' => $request->input('account_id'),
                'type' => $request->input('type') ?? '',
            ];

            // ❌ Academic year missing
            if (!$data['academic_yr']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic year not found'
                ], 400);
            }

            // 🔍 Check if class already exists
            $exists = DB::table('new_admission_class')
                ->where('class_id', $data['class_id'])
                ->where('type', $data['type'])
                ->where('academic_yr', $data['academic_yr'])
                ->first();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'An application form already exists for the selected class and shift.'
                ], 409);  // Conflict
            }

            // ✅ Insert record
            DB::table('new_admission_class')->insert($data);

            return response()->json([
                'success' => true,
                'message' => 'Admission class added successfully',
                'data' => $data
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log::error('Admission Form Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Admission Form Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function viewAdmissionForm(Request $request, $id)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 📅 Academic year from JWT
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            if (!$academic_year) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic year not found'
                ], 400);
            }

            // 🔍 Fetch admission form
            $admissionForm = DB::table('new_admission_class')
                ->select('new_admission_class.*', 'class.name as class_name')
                ->leftJoin('class', 'class.class_id', 'new_admission_class.class_id')
                ->where('nac_id', $id)
                ->where('new_admission_class.academic_yr', $academic_year)
                ->first();

            if (!$admissionForm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admission form not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Admission form fetched successfully',
                'data' => $admissionForm
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        } catch (\Exception $e) {
            // \Log::error('View Admission Form Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'View Admission Form Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteAdmissionForm(Request $request, $id)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 📅 Academic year from JWT
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            if (!$academic_year) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic year not found'
                ], 400);
            }

            // 🔍 Get admission class
            $admissionClass = DB::table('new_admission_class')
                ->where('nac_id', $id)
                ->where('academic_yr', $academic_year)
                ->first();

            if (!$admissionClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admission class not found'
                ], 404);
            }

            // 🔗 Check if class is already used
            $inUse = DB::table('online_admission_form')
                ->where('class_id', $admissionClass->class_id)
                ->exists();

            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application form has been filled for this class, cannot delete'
                ], 409);  // Conflict
            }

            // 🗑 Delete safely
            DB::table('new_admission_class')
                ->where('nac_id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Admission class deleted successfully'
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        } catch (\Exception $e) {
            \Log::error('Delete Admission Form Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while deleting admission class'
            ], 500);
        }
    }

    public function updateAdmissionForm(Request $request, $id)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 📅 Academic year from JWT
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            if (!$academic_year) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academic year not found'
                ], 400);
            }

            // ✅ Validation
            $request->validate([
                'class_id' => 'required|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'age_start_date' => 'nullable|date',
                'age_end_date' => 'nullable|date',
                'form_fee' => 'required|numeric',
                'type' => 'nullable',
                'account_id' => 'required|integer'
            ]);

            // 🧾 Prepare update data
            $data = [
                'class_id' => $request->input('class_id'),
                'start_date' => $request->input('start_date')
                    ? Carbon::parse($request->input('start_date'))->format('Y-m-d')
                    : null,
                'age_start_date' => $request->input('age_start_date')
                    ? Carbon::parse($request->input('age_start_date'))->format('Y-m-d')
                    : null,
                'age_end_date' => $request->input('age_end_date')
                    ? Carbon::parse($request->input('age_end_date'))->format('Y-m-d')
                    : null,
                'end_date' => $request->input('end_date')
                    ? Carbon::parse($request->input('end_date'))->format('Y-m-d')
                    : null,
                'application_form_fee' => $request->input('form_fee'),
                'publish' => $request->input('publish') ?? 'N',
                'academic_yr' => $academic_year,
                'account_id' => $request->input('account_id'),
                'type' => $request->input('type') ?? '',
            ];

            // 🔍 Check record exists
            $admissionClass = DB::table('new_admission_class')
                ->where('nac_id', $id)
                ->where('academic_yr', $academic_year)
                ->first();

            if (!$admissionClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admission class not found'
                ], 404);
            }

            // 🚫 Duplicate class check (except current record)
            $exists = DB::table('new_admission_class')
                ->where('class_id', $data['class_id'])
                ->where('academic_yr', $academic_year)
                ->where('type', $data['type'])
                ->where('nac_id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'An application form already exists for the selected class and shift.'
                ], 409);
            }

            // ✏️ Update record
            DB::table('new_admission_class')
                ->where('nac_id', $id)
                ->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Admission class updated successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating admission class'
            ], 500);
        }
    }

    public function getSectionsByClass(Request $req, $class_id)
    {
        try {
            $user = $this->authenticateUser();
            $payload = JWTAuth::getPayload();
            $sections = DB::table('section')->where('academic_yr', $payload->get('academic_year'))->where('class_id', $class_id)->get();
            return response()->json([
                'status' => true,
                'data' => $sections,
            ]);
        } catch (Exception $err) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wront please try again',
                'errorMessage' => $err->getMessage(),
            ]);
        }
    }

    // Admission email module
    public function AdmissionEmailIndex(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $payload = JWTAuth::getPayload();
            $academic_year = $request->query('academic_year') ?? JWTAuth::getPayload()->get('academic_year');

            $templates = DB::table('email_templates')
                ->select('class.name as class_name', 'email_templates.*')
                ->leftJoin('class', 'class.class_id', 'email_templates.class_id')
                ->where('class.academic_yr', $academic_year)
                ->orderBy('email_templates.id', 'desc')
                ->get();
            return response()->json([
                'status' => true,
                'data' => $templates,
            ]);
        } catch (Exception $err) {
            return response()->json();
        }
    }

    public function AdmissionEmailStore(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $payload = JWTAuth::getPayload();

            $request->validate([
                'key' => 'required|string|max:100',
                'body' => 'required|string',
                'class_id' => 'nullable|integer',
            ]);

            $exists = DB::table('email_templates')->where('key', $request->key)->where('class_id', $request->class_id)->exists();

            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email Key already found'
                ], 403);
            }

            DB::table('email_templates')->insert([
                'key' => $request->key,
                'body' => $request->body,
                'class_id' => $request->class_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Email template created successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $err) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while creating template',
                'errorMessage' => $err->getMessage(),
            ], 500);
        }
    }

    public function AdmissionEmailShow($id)
    {
        try {
            $user = $this->authenticateUser();
            $payload = JWTAuth::getPayload();

            $template = DB::table('email_templates')
                ->select('class.name as class_name', 'email_templates.*')
                ->leftJoin('class', 'class.class_id', 'email_templates.class_id')
                ->where('email_templates.id', $id)
                ->first();

            if (!$template) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email template not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $template
            ], 200);
        } catch (\Exception $err) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch email template'
            ], 500);
        }
    }

    public function AdmissionEmailUpdate(Request $request, $id)
    {
        try {
            $user = $this->authenticateUser();
            $payload = JWTAuth::getPayload();

            $request->validate([
                'body' => 'required|string',
                'class_id' => 'required|integer',
            ]);

            $exists = DB::table('email_templates')->where('id', $id)->exists();

            if (!$exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email template not found'
                ], 404);
            }

            DB::table('email_templates')
                ->where('id', $id)
                ->update([
                    'body' => $request->body,
                    'class_id' => $request->class_id,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Email template updated successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $err) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update email template'
            ], 500);
        }
    }

    public function AdmissionEmailDestroy($id)
    {
        try {
            $user = $this->authenticateUser();
            $payload = JWTAuth::getPayload();

            $template = DB::table('email_templates')->where('id', $id)->first();

            if (!$template) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email template not found'
                ], 404);
            }

            DB::table('email_templates')->where('id', $id)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Email template deleted successfully'
            ], 200);
        } catch (\Exception $err) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete email template'
            ], 500);
        }
    }

    /*
     * INTERVIEW_SCHEDULING
     * VERIFICATION_SUCCESSFULL
     * ADDMISSION_APPROVED
     */
    private function getEmailBodyByKey($key, $class_id)
    {
        $template = DB::table('email_templates')
            ->where('key', $key)
            ->where('class_id', $class_id)
            ->first();

        if ($template) {
            return $template->body;
        }

        $defaultBodies = [
            'INTERVIEW_SCHEDULING' =>
                'Dear Candidate,<br><br>
                We are pleased to inform you that your interview has been scheduled as per the details below:<br><br>
                <strong>Date:</strong> INTERVIEW_DATE<br>
                <strong>Time:</strong> TIME_FROM - TIME_TO<br><br>
                Kindly ensure your availability at the scheduled time. If you have any questions or require further clarification, please contact us.<br><br>
                Best regards.',
            'VERIFICATION_SUCCESSFULL' =>
                'Dear Candidate,<br><br>
                We are pleased to inform you that your verification process has been completed successfully.<br><br>
                If you require any further assistance, please feel free to contact us.<br><br>
                Best regards.',
            'ADDMISSION_APPROVED' =>
                'Dear Candidate,<br><br>
                Congratulations! We are delighted to inform you that your admission has been approved.<br><br>
                Further details regarding the next steps will be shared with you shortly. Please contact us if you need any additional information.<br><br>
                Best regards.'
        ];

        $defaultBody = $defaultBodies[$key] ?? 'Default email content.';

        // Insert default template
        DB::table('email_templates')->insert([
            'key' => $key,
            'class_id' => $class_id,
            'body' => $defaultBody,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $defaultBody;
    }

    public function attendanceAnalyticsGraph(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            // ---------- Inputs ----------
            $academicYear = JWTAuth::getPayload()->get('academic_year');
            $date = $request->query('date', now()->toDateString());

            // ---------- Fetch Classes ----------
            $classes = DB::table('class')
                ->select('class_id', 'name as class_name')
                ->where('academic_yr', $academicYear)
                ->get();

            if ($classes->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No classes found',
                    'data' => []
                ]);
            }

            // ---------- Preload Sections ----------
            $sections = DB::table('section')
                ->select('section_id', 'class_id', 'name as section_name')
                ->get()
                ->groupBy('class_id');

            // ---------- Student Strength ----------
            $studentCounts = DB::table('student')
                ->select('class_id', 'section_id', DB::raw('COUNT(*) as total'))
                ->where('academic_yr', $academicYear)
                ->groupBy('class_id', 'section_id')
                ->get()
                ->keyBy(fn($row) => $row->class_id . '_' . $row->section_id);

            // ---------- Present Students ----------
            $presentCounts = DB::table('attendance')
                ->select('class_id', 'section_id', DB::raw('COUNT(*) as present'))
                ->where('attendance_status', 0)
                ->where('only_date', $date)
                ->where('academic_yr', $academicYear)
                ->groupBy('class_id', 'section_id')
                ->get()
                ->keyBy(fn($row) => $row->class_id . '_' . $row->section_id);

            // ---------- Build Final Response ----------
            $responseData = [];

            foreach ($classes as $class) {
                $classSections = [];

                foreach ($sections[$class->class_id] ?? [] as $section) {
                    $key = $class->class_id . '_' . $section->section_id;

                    $strength = $studentCounts[$key]->total ?? 0;
                    $present = $presentCounts[$key]->present ?? 0;

                    $classSections[] = [
                        'section' => $section->section_name,
                        'strength' => $strength,
                        'present' => $present,
                        'absent' => max($strength - $present, 0),
                    ];
                }

                $responseData[] = [
                    'class_id' => $class->class_id,
                    'class_name' => $class->class_name,
                    'sections' => $classSections
                ];
            }

            return response()->json([
                'status' => true,
                'date' => $date,
                'data' => $responseData
            ]);
        } catch (\Throwable $e) {
            // Log::error('Attendance Analytics Error', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while generating attendance analytics',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
            ], 500);
        }
    }

    public function birthDaysSummaryCount(Request $request)
    {
        $user = $this->authenticateUser();
        $date = Carbon::now();
        $academicYear = JWTAuth::getPayload()->get('academic_year');

        $studentCount = DB::table('student')
            ->where('academic_yr', $academicYear)
            ->whereMonth('dob', $date->month)
            ->whereDay('dob', $date->day)
            ->count();

        $countOfBirthdaysToday = $studentCount + Teacher::where('IsDelete', 'N')
            ->whereMonth('birthday', $date->month)
            ->whereDay('birthday', $date->day)
            ->count();

        return response()->json([
            'status' => true,
            'count' => $countOfBirthdaysToday,
        ], 200);
    }

    public function birthDaysSummaryList()
    {
        try {
            // Authenticate user
            $user = $this->authenticateUser();
            $teacher_id = $user->reg_id;
            $academic_yr = JWTAuth::getPayload()->get('academic_year');

            $today = Carbon::now()->format('m-d');
            $yesterday = Carbon::now()->subDay()->format('m-d');
            $tomorrow = Carbon::now()->addDay()->format('m-d');

            $birthdayData = [
                'yesterday' => [],
                'today' => [],
                'tomorrow' => []
            ];

            $staffBirthDayData = [
                'yesterday' => [],
                'today' => [],
                'tomorrow' => []
            ];
            $dates = [
                'yesterday' => Carbon::now()->subDay(),
                'today' => Carbon::now(),
                'tomorrow' => Carbon::now()->addDay(),
            ];
            $staffBaseQuery = Teacher::where('IsDelete', 'N');
            foreach ($dates as $key => $date) {
                // STAFF
                $staffList = (clone $staffBaseQuery)
                    ->whereMonth('birthday', $date->month)
                    ->whereDay('birthday', $date->day)
                    ->get();

                $staffBirthDayData[$key] = $staffList;
            }

            $students = DB::table('student')
                ->select(
                    'student.student_id',
                    'student.first_name',
                    'student.mid_name',
                    'student.last_name',
                    'student.dob',
                    'class.name as class_name',
                    'section.name as section_name',
                    'contact_details.*'
                )
                ->leftJoin('class', 'student.class_id', '=', 'class.class_id')
                ->leftJoin('section', 'student.section_id', '=', 'section.section_id')
                ->leftJoin('contact_details', 'contact_details.id', '=', 'student.parent_id')
                ->where('student.IsDelete', 'N')
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
                'status' => 200,
                'success' => true,
                'message' => 'Birthday list of students fetched successfully.',
                'data' => [
                    'studentBirthDays' => $birthdayData,
                    'staffBirthDays' => $staffBirthDayData
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Something went wrong while fetching birthday list.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function lessonPlanNotCreatedCount(Request $request)
    {
        $user = $this->authenticateUser();
        $role_id = JWTAuth::getPayload()->get('role_id');
        $reg_id = JWTAuth::getPayload()->get('reg_id');
        $academic_year = JWTAuth::getPayload()->get('academic_year');

        // dd($role_id);
        // Get next Monday (or accept from request if you want later)
        $nextMonday = now()->next('Monday')->format('d-m-Y');

        if ($role_id == 'P' || $role_id == 'A' || $role_id == 'M') {
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
                ->where('s.academic_yr', $academic_year)
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
                ->get()
                ->count();

            $createdCount = DB::table('subject as s')
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
                ->where('s.academic_yr', $academic_year)
                ->whereIn(
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
                ->get()
                ->count();

            return response()->json([
                'status' => true,
                'notCreatedCount' => $notCreatedCount,
                'createdCount' => $createdCount,
            ], 200);
        } else if ($role_id == 'T') {
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
                ->where('s.academic_yr', $academic_year)
                ->where('s.teacher_id', $reg_id)
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
                ->get()
                ->count();

            $createdCount = DB::table('subject as s')
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
                ->where('s.academic_yr', $academic_year)
                ->where('s.teacher_id', $reg_id)
                ->whereIn(
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
                ->get()
                ->count();

            return response()->json([
                'status' => true,
                'count' => $notCreatedCount,
                'createdCount' => $createdCount,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to access this resource',
            ], 403);
        }
    }

    public function lessonPlanNotCreatedList(Request $request)
    {
        $user = $this->authenticateUser();
        $role_id = JWTAuth::getPayload()->get('role_id');
        $reg_id = JWTAuth::getPayload()->get('reg_id');
        $academic_year = JWTAuth::getPayload()->get('academic_year');

        // dd($role_id);
        // Get next Monday (or accept from request if you want later)
        $nextMonday = now()->next('Monday')->format('d-m-Y');

        if ($role_id == 'P' || $role_id == 'A' || $role_id == 'M') {
            $notCreatedList = DB::table('subject as s')
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
                ->where('s.academic_yr', $academic_year)
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

            $createdList = DB::table('subject as s')
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
                ->where('s.academic_yr', $academic_year)
                ->whereIn(
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

            return response()->json([
                'status' => true,
                'notCreatedList' => $notCreatedList,
                'createdList' => $createdList,
            ], 200);
        } else if ($role_id == 'T') {
            $data = DB::table('subject as s')
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
                ->where('s.academic_yr', $academic_year)
                ->where('s.teacher_id', $reg_id)
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

            $createdList = DB::table('subject as s')
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
                ->where('s.academic_yr', $academic_year)
                ->where('s.teacher_id', $reg_id)
                ->whereIn(
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

            return response()->json([
                'status' => true,
                'list' => $data,
                'createdList' => $createdList,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to access this resource',
            ], 403);
        }
    }

    public function attendanceSummaryByDepartment(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');
            $date = $request->input('date', date('Y-m-d'));

            /*
             * |--------------------------------------------------------------------------
             * | 1️⃣ Total teachers per department
             * |--------------------------------------------------------------------------
             */
            $totalByDepartment = DB::table('subject as sub')
                ->join('teacher as t', 't.teacher_id', '=', 'sub.teacher_id')
                ->join('class as c', 'c.class_id', '=', 'sub.class_id')
                ->join('department as d', 'd.department_id', '=', 'c.department_id')
                ->where('sub.academic_yr', $academic_year)
                ->groupBy('d.department_id', 'd.name')
                ->select(
                    'd.department_id',
                    'd.name as department_name',
                    DB::raw('COUNT(DISTINCT t.teacher_id) as total')
                )
                ->get()
                ->keyBy('department_id');

            /*
             * |--------------------------------------------------------------------------
             * | 2️⃣ Present teachers per department (ONCE, SQL-level)
             * |--------------------------------------------------------------------------
             */
            $presentByDepartment = DB::table('teacher_attendance as ta')
                ->join('teacher as t', 't.employee_id', '=', 'ta.employee_id')
                ->join('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                ->join('subject as sub', 'sub.teacher_id', '=', 't.teacher_id')
                ->join('class as c', 'c.class_id', '=', 'sub.class_id')
                ->join('department as d', 'd.department_id', '=', 'c.department_id')
                ->where('t.isDelete', 'N')
                ->where('tc.teaching', 'Y')
                ->where('sub.academic_yr', $academic_year)
                ->whereDate('ta.punch_time', $date)
                ->groupBy('d.department_id')
                ->select(
                    'd.department_id',
                    DB::raw('COUNT(DISTINCT t.teacher_id) as present')
                )
                ->pluck('present', 'department_id');

            /*
             * |--------------------------------------------------------------------------
             * | 3️⃣ Final merge (FAST)
             * |--------------------------------------------------------------------------
             */
            $finalArray = [];

            foreach ($totalByDepartment as $deptId => $dept) {
                $present = $presentByDepartment[$deptId] ?? 0;
                $total = $dept->total;

                $finalArray[$dept->department_name] = [
                    'present' => $present,
                    'absent' => $total - $present,
                    'total' => $total
                ];
            }

            /*
             * |--------------------------------------------------------------------------
             * | 4 Caretaker
             * |--------------------------------------------------------------------------
             */
            $presentCaretakers = DB::table('teacher_attendance as ta')
                ->leftJoin('teacher as t', 't.employee_id', '=', 'ta.employee_id')
                ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                ->whereDate('ta.punch_time', $date)
                ->where('tc.name', 'Caretakers')
                ->count();

            $totalCaretaker = DB::table('teacher as t')
                ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                ->where('tc.name', 'Caretakers')
                ->count();

            $finalArray['Caretaker'] = [
                'present' => $presentCaretakers,
                'absent' => $totalCaretaker - $presentCaretakers,
                'total' => $totalCaretaker,
            ];

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Department wise present teacher count',
                'data' => $finalArray
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function attendanceSummaryByCategory(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $date = $request->input('date', now()->toDateString());
            $totalByCategory = DB::table('teacher as t')
                ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
                ->where('t.isDelete', 'N')
                ->groupBy('tc.name')
                ->select(
                    'tc.name as category',
                    DB::raw('COUNT(t.teacher_id) as total')
                )
                ->pluck('total', 'category');

            /*
             * |--------------------------------------------------------------------------
             * | 2️⃣ Present teachers per category (distinct attendance)
             * |--------------------------------------------------------------------------
             */
            $presentByCategory = DB::table('teacher_attendance as ta')
                ->join('teacher as t', 't.employee_id', '=', 'ta.employee_id')
                ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
                ->where('t.isDelete', 'N')
                ->whereDate('ta.punch_time', $date)
                ->groupBy('tc.name')
                ->select(
                    'tc.name as category',
                    DB::raw('COUNT(DISTINCT t.teacher_id) as present')
                )
                ->pluck('present', 'category');

            /*
             * |--------------------------------------------------------------------------
             * | 3️⃣ Final response build
             * |--------------------------------------------------------------------------
             */
            $finalData = [];

            foreach ($totalByCategory as $category => $total) {
                $present = $presentByCategory[$category] ?? 0;

                $finalData[$category] = [
                    'total' => $total,
                    'present' => $present,
                    'absent' => $total - $present
                ];
            }

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Category wise teacher attendance summary',
                'data' => $finalData
            ]);
        } catch (\Throwable $e) {
            \Log::error($e);

            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Something went wrong'
            ]);
        }
    }

    public function attendanceSummaryCaretaker(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_year = JWTAuth::getPayload()->get('academic_year');
        $date = $request->input('date', date('Y-m-d'));

        $presentCaretakers = DB::table('teacher_attendance as ta')
            ->leftJoin('teacher as t', 't.employee_id', '=', 'ta.employee_id')
            ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
            ->whereDate('ta.punch_time', $date)
            ->where('tc.name', 'Caretakers')
            ->count();

        $totalCaretaker = DB::table('teacher as t')
            ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
            ->where('tc.name', 'Caretakers')
            ->count();

        return response()->json([
            'status' => true,
            'presentCaretakers' => $presentCaretakers,
            'totalCaretaker' => $totalCaretaker,
            'absentCaretakers' => $totalCaretaker - $presentCaretakers,
        ]);
    }

    public function lessonPlanSummary(Request $request)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 🔑 JWT payload
            $role_id = JWTAuth::getPayload()->get('role_id');
            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            // 📅 Next Monday
            $nextMonday = now()->next('Monday')->format('d-m-Y');

            // 👨‍🏫 Total teaching staff
            // $totalNumberOfTeachers = DB::table('teacher')
            // ->leftJoin('teacher_category', 'teacher_category.tc_id', '=', 'teacher.tc_id')
            // ->where('teacher_category.teaching', 'Y')
            // ->where('teacher.isDelete' , 'N')
            // ->get()
            // ->count();

            $totalNumberOfTeachers = DB::table('subject as s')
                ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
                ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
                ->where('tc.teaching', 'Y')
                ->where('t.isDelete', 'N')
                ->where('s.academic_yr', $academic_year)
                ->whereNotIn('s.sm_id', function ($query) {
                    $query
                        ->select('sm_id')
                        ->from('subjects_excluded_from_curriculum');
                })
                ->distinct('s.teacher_id')
                ->count('s.teacher_id');

            // ✅ Lesson plan submitted
            $lessonPlanSubmitted = DB::table('subject as s')
                ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
                ->join('class as c', 's.class_id', '=', 'c.class_id')
                ->join('section as sc', 's.section_id', '=', 'sc.section_id')
                ->join('subject_master as sm', 's.sm_id', '=', 'sm.sm_id')
                ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
                ->where('tc.teaching', 'Y')
                ->where('t.isDelete', 'N')
                ->where('s.academic_yr', $academic_year)
                ->whereIn(
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
                ->get()
                ->count();

            // ❌ Lesson plan not submitted
            $lessonPlanNotSubmitted = DB::table('subject as s')
                ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
                ->join('class as c', 's.class_id', '=', 'c.class_id')
                ->join('section as sc', 's.section_id', '=', 'sc.section_id')
                ->join('subject_master as sm', 's.sm_id', '=', 'sm.sm_id')
                ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
                ->where('tc.teaching', 'Y')
                ->where('t.isDelete', 'N')
                ->where('s.academic_yr', $academic_year)
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
                ->get()
                ->count();

            // ⏳ Pending for approval
            $pendingForApproval = DB::table('subject as s')
                ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
                ->join('class as c', 's.class_id', '=', 'c.class_id')
                ->join('section as sc', 's.section_id', '=', 'sc.section_id')
                ->join('subject_master as sm', 's.sm_id', '=', 'sm.sm_id')
                ->where('t.isDelete', 'N')
                ->where('s.academic_yr', $academic_year)
                ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
                ->where('tc.teaching', 'Y')
                ->whereIn(
                    DB::raw('CONCAT(s.class_id, s.section_id, s.sm_id, s.teacher_id)'),
                    function ($query) use ($nextMonday) {
                        $query
                            ->select(
                                DB::raw('CONCAT(class_id, section_id, subject_id, reg_id)')
                            )
                            ->from('lesson_plan')
                            ->where('approve', '!=', 'Y')
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
                ->get()
                ->count();

            // ✅ Success response
            return response()->json([
                'status' => true,
                'totalNumberOfTeachers' => $totalNumberOfTeachers,
                'lessonPlanSubmitted' => $lessonPlanSubmitted,
                'lessonPlanNotSubmitted' => $lessonPlanNotSubmitted,
                'pendingForApproval' => $pendingForApproval,
                'nextMonday' => $nextMonday
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // 🛑 Database errors
            return response()->json([
                'status' => false,
                'message' => 'Database error while fetching lesson plan summary',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // 🔐 JWT errors
            return response()->json([
                'status' => false,
                'message' => 'Authentication token error',
                'error' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            // ❗ Any other error
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function totalTeachers(Request $request)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 🔑 JWT payload
            $role_id = JWTAuth::getPayload()->get('role_id');
            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            // 👨‍🏫 Total teaching staff
            // $totalNumberOfTeachers = DB::table('teacher')
            // ->select('teacher.*' , 'teacher_category.name as category_name')
            // ->leftJoin('teacher_category', 'teacher_category.tc_id', '=', 'teacher.tc_id')
            // ->where('teacher_category.teaching', 'Y')
            // ->where('teacher.isDelete' , 'N')
            // ->get();

            $totalNumberOfTeachers = DB::table('subject as s')
                ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
                ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
                ->where('tc.teaching', 'Y')
                ->where('t.isDelete', 'N')
                ->where('s.academic_yr', $academic_year)
                ->whereNotIn('s.sm_id', function ($query) {
                    $query
                        ->select('sm_id')
                        ->from('subjects_excluded_from_curriculum');
                })
                ->select(
                    't.*',
                    'tc.name as category_name',
                )
                ->distinct()
                ->get();

            // ✅ Success response
            return response()->json([
                'status' => true,
                'data' => $totalNumberOfTeachers,
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // 🛑 Database errors
            return response()->json([
                'status' => false,
                'message' => 'Database error while fetching lesson plan summary',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // 🔐 JWT errors
            return response()->json([
                'status' => false,
                'message' => 'Authentication token error',
                'error' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            // ❗ Any other error
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function lessonPlanSubmitted(Request $request)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 🔑 JWT payload
            $role_id = JWTAuth::getPayload()->get('role_id');
            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            // 📅 Next Monday
            $nextMonday = now()->next('Monday')->format('d-m-Y');

            // ✅ Lesson plan submitted
            $createdList = DB::table('subject as s')
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
                ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
                ->where('tc.teaching', 'Y')
                ->where('t.isDelete', 'N')
                ->where('s.academic_yr', $academic_year)
                ->whereIn(
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

            // ✅ Success response
            return response()->json([
                'status' => true,
                'data' => $createdList,
                'nextMonday' => $nextMonday
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // 🛑 Database errors
            return response()->json([
                'status' => false,
                'message' => 'Database error while fetching lesson plan summary',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // 🔐 JWT errors
            return response()->json([
                'status' => false,
                'message' => 'Authentication token error',
                'error' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            // ❗ Any other error
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function lessonPlanNotSubmitted(Request $request)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 🔑 JWT payload
            $role_id = JWTAuth::getPayload()->get('role_id');
            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            // 📅 Next Monday
            $nextMonday = now()->next('Monday')->format('d-m-Y');

            // ✅ Lesson plan submitted
            $notCreatedList = DB::table('subject as s')
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
                ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
                ->where('tc.teaching', 'Y')
                ->where('t.isDelete', 'N')
                ->where('s.academic_yr', $academic_year)
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

            // ✅ Success response
            return response()->json([
                'status' => true,
                'data' => $notCreatedList,
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // 🛑 Database errors
            return response()->json([
                'status' => false,
                'message' => 'Database error while fetching lesson plan summary',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // 🔐 JWT errors
            return response()->json([
                'status' => false,
                'message' => 'Authentication token error',
                'error' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            // ❗ Any other error
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function lessonPlanPending(Request $request)
    {
        try {
            // 🔐 Authenticate user
            $user = $this->authenticateUser();

            // 🔑 JWT payload
            $role_id = JWTAuth::getPayload()->get('role_id');
            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            // 📅 Next Monday
            $nextMonday = now()->next('Monday')->format('d-m-Y');

            // ✅ Lesson plan submitted
            $data = DB::table('subject as s')
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
                ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
                ->where('tc.teaching', 'Y')
                ->where('t.isDelete', 'N')
                ->where('s.academic_yr', $academic_year)
                ->whereIn(
                    DB::raw('CONCAT(s.class_id, s.section_id, s.sm_id, s.teacher_id)'),
                    function ($query) use ($nextMonday) {
                        $query
                            ->select(
                                DB::raw('CONCAT(class_id, section_id, subject_id, reg_id)')
                            )
                            ->from('lesson_plan')
                            ->where('approve', '!=', 'Y')
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

            // ✅ Success response
            return response()->json([
                'status' => true,
                'data' => $data,
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // 🛑 Database errors
            return response()->json([
                'status' => false,
                'message' => 'Database error while fetching lesson plan summary',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // 🔐 JWT errors
            return response()->json([
                'status' => false,
                'message' => 'Authentication token error',
                'error' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            // ❗ Any other error
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function attendanceNotMarkedCount(Request $request)
    {
        $user = $this->authenticateUser();

        // ---------- Inputs ----------
        $academicYear = JWTAuth::getPayload()->get('academic_year');
        $date = $request->query('date', now()->toDateString());

        // ---------- Fetch Classes ----------
        $classes = DB::table('class')
            ->select('class_id')
            ->where('academic_yr', $academicYear)
            ->get();

        // ---------- Preload Sections ----------
        $sectionsByClass = DB::table('section')
            ->select('section_id', 'class_id')
            ->get()
            ->groupBy('class_id');

        $notMarkedCount = 0;
        $totalClasses = 0;
        foreach ($classes as $class) {
            foreach ($sectionsByClass[$class->class_id] ?? [] as $section) {
                $exists = DB::table('attendance')
                    ->where('class_id', $class->class_id)
                    ->where('section_id', $section->section_id)
                    ->whereDate('only_date', $date)
                    ->exists();
                if (!$exists) {
                    $notMarkedCount++;
                }
                $totalClasses++;
            }
        }

        return response()->json([
            'status' => true,
            'date' => $date,
            'AttendanceNotMarkedCount' => $notMarkedCount,
            'TotalClassesCount' => $totalClasses,
        ]);
    }

    public function attendanceNotMarkedList(Request $request)
    {
        $user = $this->authenticateUser();

        // ---------- Inputs ----------
        $academicYear = JWTAuth::getPayload()->get('academic_year');
        $date = $request->query('date', now()->toDateString());

        $notMarkedSub = DB::table('class as c')
            ->join('section as s', 's.class_id', '=', 'c.class_id')
            ->leftJoin('attendance as a', function ($join) use ($date) {
                $join
                    ->on('a.class_id', '=', 'c.class_id')
                    ->on('a.section_id', '=', 's.section_id')
                    ->whereBetween('a.only_date', [
                        $date . ' 00:00:00',
                        $date . ' 23:59:59'
                    ]);
            })
            ->where('c.academic_yr', $academicYear)
            ->whereNull('a.class_id')
            ->select('c.class_id', 's.section_id');
        $list = DB::table('class_teachers as ct')
            ->joinSub($notMarkedSub, 'nm', function ($join) {
                $join
                    ->on('nm.class_id', '=', 'ct.class_id')
                    ->on('nm.section_id', '=', 'ct.section_id');
            })
            ->join('teacher as t', 't.teacher_id', '=', 'ct.teacher_id')
            ->join('class as c', 'c.class_id', '=', 'ct.class_id')
            ->join('section as s', 's.section_id', '=', 'ct.section_id')
            ->leftJoin('redington_webhook_details as rwd', function ($join) use ($date) {
                $join
                    ->on('rwd.stu_teacher_id', '=', 't.teacher_id')
                    ->where('rwd.message_type', 'attendance_not_marked')
                    ->whereBetween('rwd.created_at', [
                        $date . ' 00:00:00',
                        $date . ' 23:59:59'
                    ]);
            })
            ->select(
                't.teacher_id',
                't.name as teacher_name',
                't.employee_id',
                'c.name as class_name',
                's.name as section_name',
                DB::raw("COALESCE(MAX(rwd.sms_sent), '') as sms_sent"),
                DB::raw("COALESCE(MAX(rwd.status), '') as whatsapp_status")
            )
            ->groupBy(
                't.teacher_id',
                't.name',
                't.employee_id',
                'c.name',
                's.name'
            )
            ->orderBy('c.class_id')
            ->orderBy('s.section_id')
            ->get();
        return response()->json([
            'status' => true,
            'date' => $date,
            'AttendanceNotMarkedList' => $list,
            'AttendanceNotMarkedCount' => count($list),
        ]);
    }

    private function studentCard(Request $request, $academicYr)
    {
        $currentDate = now()->toDateString();

        $totalStudent = DB::table('student')
            ->where('IsDelete', 'N')
            ->where('academic_yr', $academicYr)
            ->count();

        $presentStudent = DB::table('attendance')
            ->where('only_date', $currentDate)
            ->where('attendance_status', '0')
            ->where('academic_yr', $academicYr)
            ->count();

        $date = $request->query('date', $currentDate);

        $result = DB::selectOne('
            SELECT COUNT(cs.class_id) AS total,
                SUM(CASE WHEN a.class_id IS NULL THEN 1 ELSE 0 END) AS not_marked
            FROM class c
            JOIN section cs ON cs.class_id = c.class_id
            LEFT JOIN (
                SELECT DISTINCT class_id, section_id
                FROM attendance
                WHERE only_date = ?
            ) a ON a.class_id = c.class_id AND a.section_id = cs.section_id
            WHERE c.academic_yr = ?
        ', [$date, $academicYr]);

        return [
            'present' => $presentStudent,
            'total' => $totalStudent,
            'attendanceNotMarked' => [
                'notMarked' => $result->not_marked,
                'total' => $result->total,
            ],
        ];
    }

    private function staffCard(string $short_code)
    {
        if ($short_code === 'HSCS') {
            return $this->staffHSCS();
        }

        return $this->staffSACS();
    }

    private function staffHSCS()
    {
        $teachingStaff = DB::table('teacher as t')
            ->join('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
            ->where('t.isDelete', 'N')
            ->where('tc.teaching', 'Y')
            ->distinct()
            ->count('t.teacher_id');

        $attendanceteachingstaff = DB::table('teacher_attendance as ta')
            ->join('teacher as t', DB::raw('ta.employee_id'), '=', DB::raw('CAST(t.employee_id AS UNSIGNED)'))
            ->join('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
            ->where('tc.teaching', 'Y')
            ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
            ->distinct()
            ->count('ta.employee_id');

        $nonTeaching = DB::table('teacher as t')
            ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
            ->where('t.isDelete', 'N')
            ->where('tc.teaching', 'N')
            ->select('t.teacher_id')
            ->union(
                DB::table('teacher')
                    ->where('designation', 'Caretaker')
                    ->select('teacher_id')
            )
            ->distinct()
            ->count();

        $attendanceNonTeaching = DB::table('teacher_attendance as ta')
            ->join('teacher as t', DB::raw('ta.employee_id'), '=', DB::raw('CAST(t.employee_id AS UNSIGNED)'))
            ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
            ->whereIn('t.designation', ['Caretaker'])
            ->distinct()
            ->count('ta.employee_id');

        return [
            'teachingStaff' => $teachingStaff,
            'non_teachingStaff' => $nonTeaching,
            'attendancenonteachingstaff' => $attendanceNonTeaching,
            'attendanceteachingstaff' => $attendanceteachingstaff,
        ];
    }

    private function staffSACS()
    {
        $teachingStaff = DB::table('teacher as t')
            ->join('user_master as u', 't.teacher_id', '=', 'u.reg_id')
            ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
            ->where('t.isDelete', 'N')
            ->where('tc.teaching', 'Y')
            ->distinct()
            ->count(DB::raw('t.teacher_id'));

        $attendanceteachingstaff = DB::table('teacher_attendance as ta')
            ->join(
                'teacher as t',
                DB::raw('ta.employee_id'),
                '=',
                DB::raw('CAST(t.employee_id AS UNSIGNED)')
            )
            ->join('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
            ->where('t.isDelete', 'N')
            ->where('tc.teaching', 'Y')
            ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
            ->distinct()
            ->count(DB::raw('ta.employee_id'));

        $nonTeachingTeachers = DB::table('teacher as t')
            ->join('user_master as u', 't.teacher_id', '=', 'u.reg_id')
            ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
            ->where('t.isDelete', 'N')
            ->where('tc.teaching', 'N')
            ->select(DB::raw('t.teacher_id'));

        $nonTeachingCaretakers = DB::table('teacher as c')
            ->leftJoin('teacher_category as tc', 'c.tc_id', '=', 'tc.tc_id')
            ->where('c.isDelete', 'N')
            ->where('c.designation', 'Caretaker')
            ->where('tc.teaching', 'N')
            ->select(DB::raw('c.teacher_id'));

        $non_teachingStaff = $nonTeachingTeachers
            ->union($nonTeachingCaretakers)
            ->distinct()
            ->count();

        $attendanceNonTeachingTeachers = DB::table('teacher_attendance as ta')
            ->join(
                'teacher as t',
                DB::raw('ta.employee_id'),
                '=',
                DB::raw('CAST(t.employee_id AS UNSIGNED)')
            )
            ->join('user_master as u', 't.teacher_id', '=', 'u.reg_id')
            ->join('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
            ->where('t.isDelete', 'N')
            ->where('tc.teaching', 'N')
            ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
            ->select(DB::raw('ta.employee_id'));

        $attendanceCaretakers = DB::table('teacher_attendance as ta')
            ->join(
                'teacher as t',
                DB::raw('ta.employee_id'),
                '=',
                DB::raw('CAST(t.employee_id AS UNSIGNED)')
            )
            ->where('t.isDelete', 'N')
            ->where('t.designation', 'Caretaker')
            ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
            ->select(DB::raw('ta.employee_id'));

        $attendancenonteachingstaff = $attendanceNonTeachingTeachers
            ->union($attendanceCaretakers)
            ->distinct()
            ->count();

        return [
            'teachingStaff' => $teachingStaff,
            'non_teachingStaff' => $non_teachingStaff,
            'attendancenonteachingstaff' => $attendancenonteachingstaff,
            'attendanceteachingstaff' => $attendanceteachingstaff,
        ];
    }

    private function birthdayCard($academicYr)
    {
        $today = now();

        $teacher = DB::table('teacher')
            ->where('IsDelete', 'N')
            ->whereMonth('birthday', $today->month)
            ->whereDay('birthday', $today->day)
            ->count();

        $student = DB::table('student')
            ->where('IsDelete', 'N')
            ->where('academic_yr', $academicYr)
            ->whereMonth('dob', $today->month)
            ->whereDay('dob', $today->day)
            ->count();

        return [
            'count' => $teacher + $student
        ];
    }

    private function feesCard($academicYr)
    {
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

        $sql = "
            SELECT SUM(installment_fees - concession - paid_amount) AS pending_fee FROM
            (SELECT s.student_id, s.installment, installment_fees, COALESCE(SUM(d.amount), 0) AS concession, 0 AS paid_amount FROM
            view_student_fees_category s LEFT JOIN fee_concession_details d ON s.student_id = d.student_id AND s.installment = d.installment WHERE
            s.academic_yr = '$academicYr' and s.installment<>4 AND due_date < CURDATE() AND s.student_installment NOT IN
            (SELECT student_installment FROM view_student_fees_payment a WHERE a.academic_yr = '$academicYr') GROUP BY s.student_id, s.installment
            UNION SELECT f.student_id AS student_id, b.installment AS installment, b.installment_fees, COALESCE(SUM(c.amount), 0) AS concession,
            SUM(f.fees_paid) AS paid_amount FROM view_student_fees_payment f LEFT JOIN fee_concession_details c ON f.student_id = c.student_id
            AND f.installment = c.installment JOIN view_fee_allotment b ON f.fee_allotment_id = b.fee_allotment_id AND b.installment = f.installment
            WHERE b.installment<>4 and f.academic_yr = '$academicYr' GROUP BY f.installment, c.installment  HAVING
            (b.installment_fees - COALESCE(SUM(c.amount), 0)) > SUM(f.fees_paid)) as z
        ";

        $results = DB::select($sql);

        $pendingFee = $results[0]->pending_fee ?? 0;

        $collectedfees = DB::select(
            "SELECT 'Nursery' AS account, 
            IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
            SUM(d.amount) AS amount 
                FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
                WHERE a.student_id = b.student_id 
                AND b.class_id = c.class_id 
                AND a.fees_payment_id = d.fees_payment_id 
                AND a.isCancel = 'N' 
                AND a.academic_yr = '$academicYr' 
                AND c.name = 'Nursery' 
                GROUP BY d.installment 

                UNION

                SELECT 'KG' AS account, 
                    IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
                    SUM(d.amount) AS amount 
                        FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
                        WHERE a.student_id = b.student_id 
                        AND b.class_id = c.class_id 
                        AND a.fees_payment_id = d.fees_payment_id 
                        AND a.isCancel = 'N' 
                        AND a.academic_yr = '$academicYr' 
                        AND c.name IN ('LKG','UKG') 
                        GROUP BY d.installment 

                        UNION

                        SELECT 'School' AS account, 
                            IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
                            SUM(d.amount) AS amount 
                        FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
                        WHERE a.student_id = b.student_id 
                        AND b.class_id = c.class_id 
                        AND a.fees_payment_id = d.fees_payment_id 
                        AND a.isCancel = 'N' 
                        AND a.academic_yr = '$academicYr' 
                        AND c.name IN ('1','2','3','4','5','6','7','8','9','10','11','12') 
                        GROUP BY d.installment"
        );
        $totalAmount = number_format(collect($collectedfees)->sum('amount'), 2, '.', '');

        return [
            'Collected Fees' => $totalAmount,
            'Pending Fees' => $pendingFee
        ];
    }

    private function approveLeaveCard($academicYr)
    {
        $statuses = ['A', 'H'];

        $leaveApplications = DB::table('leave_application')
            ->whereIn('status', $statuses)
            ->join('teacher', 'teacher.teacher_id', '=', 'leave_application.staff_id')
            ->join('leave_type_master', 'leave_type_master.leave_type_id', '=', 'leave_application.leave_type_id')
            ->orderBy('leave_app_id', 'DESC')
            ->select('leave_application.*', 'teacher.name as teachername', 'leave_type_master.name as leavetypename')
            ->where('leave_application.academic_yr', $academicYr)
            ->get()
            ->toArray();

        $leaveapplication = count($leaveApplications);

        return [
            'count' => $leaveapplication,
        ];
    }

    private function lessonPlanCard($academicYr)
    {
        $nextMonday = now()->next('Monday')->format('d-m-Y');
        $totalNumberOfTeachers = DB::table('subject as s')
            ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
            ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
            ->where('tc.teaching', 'Y')
            ->where('t.isDelete', 'N')
            ->where('s.academic_yr', $academicYr)
            ->whereNotIn('s.sm_id', function ($query) {
                $query
                    ->select('sm_id')
                    ->from('subjects_excluded_from_curriculum');
            })
            ->distinct('s.teacher_id')
            ->count('s.teacher_id');

        $lessonPlanSubmitted = DB::table('subject as s')
            ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
            ->join('class as c', 's.class_id', '=', 'c.class_id')
            ->join('section as sc', 's.section_id', '=', 'sc.section_id')
            ->join('subject_master as sm', 's.sm_id', '=', 'sm.sm_id')
            ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
            ->where('tc.teaching', 'Y')
            ->where('t.isDelete', 'N')
            ->where('s.academic_yr', $academicYr)
            ->whereIn(
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
            ->get()
            ->count();

        $lessonPlanNotSubmitted = DB::table('subject as s')
            ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
            ->join('class as c', 's.class_id', '=', 'c.class_id')
            ->join('section as sc', 's.section_id', '=', 'sc.section_id')
            ->join('subject_master as sm', 's.sm_id', '=', 'sm.sm_id')
            ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
            ->where('tc.teaching', 'Y')
            ->where('t.isDelete', 'N')
            ->where('s.academic_yr', $academicYr)
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
            ->get()
            ->count();

        $pendingForApproval = DB::table('subject as s')
            ->join('teacher as t', 's.teacher_id', '=', 't.teacher_id')
            ->join('class as c', 's.class_id', '=', 'c.class_id')
            ->join('section as sc', 's.section_id', '=', 'sc.section_id')
            ->join('subject_master as sm', 's.sm_id', '=', 'sm.sm_id')
            ->where('t.isDelete', 'N')
            ->where('s.academic_yr', $academicYr)
            ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
            ->where('tc.teaching', 'Y')
            ->whereIn(
                DB::raw('CONCAT(s.class_id, s.section_id, s.sm_id, s.teacher_id)'),
                function ($query) use ($nextMonday) {
                    $query
                        ->select(
                            DB::raw('CONCAT(class_id, section_id, subject_id, reg_id)')
                        )
                        ->from('lesson_plan')
                        ->where('approve', '!=', 'Y')
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
            ->get()
            ->count();

        return [
            'totalNumberOfTeachers' => $totalNumberOfTeachers,
            'lessonPlanSubmitted' => $lessonPlanSubmitted,
            'lessonPlanNotSubmitted' => $lessonPlanNotSubmitted,
            'pendingForApproval' => $pendingForApproval,
            'nextMonday' => $nextMonday
        ];
    }

    private function attendanceByCategoryCard(Request $request, $response)
    {
        $date = $request->input('date', now()->toDateString());
        $totalByCategory = DB::table('teacher as t')
            ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
            ->where('t.isDelete', 'N')
            ->groupBy('tc.name')
            ->select(
                'tc.name as category',
                DB::raw('COUNT(t.teacher_id) as total')
            )
            ->pluck('total', 'category');

        $presentByCategory = DB::table('teacher_attendance as ta')
            ->join('teacher as t', 't.employee_id', '=', 'ta.employee_id')
            ->join('teacher_category as tc', 'tc.tc_id', '=', 't.tc_id')
            ->where('t.isDelete', 'N')
            ->whereDate('ta.punch_time', $date)
            ->groupBy('tc.name')
            ->select(
                'tc.name as category',
                DB::raw('COUNT(DISTINCT t.teacher_id) as present')
            )
            ->pluck('present', 'category');

        foreach ($totalByCategory as $category => $total) {
            $present = $presentByCategory[$category] ?? 0;

            if (in_array($category, ['Nursery teachers', 'KG teachers', 'SACS teachers', 'Caretakers'])) {
                $response[$category] = [
                    'total' => $total,
                    'present' => $present,
                    'absent' => $total - $present
                ];
            }
        }

        return $response;
    }

    private function ticketCountCard($academicYr, $role_id)
    {
        $count = DB::table('ticket')
            ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
            ->where('service_type.role_id', $role_id)
            ->where('ticket.status', '!=', 'Closed')
            ->count();

        return $count;
    }

    private function birthDayCountCard($academicYr)
    {
        $currentDate = Carbon::now();
        $teachercount = DB::table('teacher')
            ->where('IsDelete', 'N')
            ->whereMonth('birthday', $currentDate->month)
            ->whereDay('birthday', $currentDate->day)
            ->count();
        $studentcount = DB::table('student')
            ->where('IsDelete', 'N')
            ->whereMonth('dob', $currentDate->month)
            ->whereDay('dob', $currentDate->day)
            ->where('academic_yr', $academicYr)
            ->count();
        $count = $teachercount + $studentcount;
        return $count;
    }

    public function principalDashboardSummary(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $role_id = $user->reg_id;
            $short_code = JWTAuth::getPayload()->get('short_name');
            $academicYr = JWTAuth::getPayload()->get('academic_year');

            $response = [];

            if ($short_code == 'SACS') {
                $response['student'] = $this->studentCard($request, $academicYr);
                $response['staff'] = $this->staffCard($short_code);
                $response['staff_student_bday_count'] = $this->birthdayCard($academicYr);
                $response['fees_collection'] = $this->feesCard($academicYr);
                $response['approve_leave'] = $this->approveLeaveCard($academicYr);
                $response['lesson_plan_summary'] = $this->lessonPlanCard($academicYr);
                $response = $this->attendanceByCategoryCard($request, $response);
            } else if ($short_code == 'HSCS') {
                $response['student'] = $this->studentCard($request, $academicYr);
                $response['staff'] = $this->staffCard($short_code);
                $response['staff_student_bday_count'] = $this->birthdayCard($academicYr);
                $response['fees_collection'] = $this->feesCard($academicYr);
                $response['approve_leave'] = $this->approveLeaveCard($academicYr);
                $response['lesson_plan_summary'] = $this->lessonPlanCard($academicYr);
            } else {
                $response['student'] = $this->studentCard($request, $academicYr);
                $response['staff'] = $this->staffCard($short_code);
                $response['staff_student_bday_count'] = $this->birthdayCard($academicYr);
                $response['fees_collection'] = $this->feesCard($academicYr);
                $response['approve_leave'] = $this->approveLeaveCard($academicYr);
                $response['lesson_plan_summary'] = $this->lessonPlanCard($academicYr);
            }

            return response()->json([
                'data' => $response,
                'count' => count($response),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function adminDashboardSummary(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $role_id = $user->role_id;
            $short_code = JWTAuth::getPayload()->get('short_code');
            $academicYr = JWTAuth::getPayload()->get('academic_year');

            $currentDate = Carbon::now()->toDateString();
            $response = [];

            // 1. student
            $totalStudent = DB::table('student')
                ->where('IsDelete', 'N')
                ->where('academic_yr', $academicYr)
                ->select(DB::raw('COUNT(*) as total'))
                ->value('total');

            $presentStudent = DB::table('attendance')
                ->where('only_date', $currentDate)
                ->where('attendance_status', '0')
                ->where('academic_yr', $academicYr)
                ->select(DB::raw('COUNT(*) as present'))
                ->value('present');

            $date = $request->query('date', now()->toDateString());

            $result = DB::selectOne('
                SELECT
                    COUNT(cs.class_id) AS total,
                    SUM(
                        CASE
                            WHEN a.class_id IS NULL THEN 1
                            ELSE 0
                        END
                    ) AS not_marked
                FROM class c
                JOIN section cs ON cs.class_id = c.class_id
                LEFT JOIN (
                    SELECT DISTINCT class_id, section_id
                    FROM attendance
                    WHERE only_date = ?
                ) a
                    ON a.class_id = c.class_id
                    AND a.section_id = cs.section_id
                WHERE c.academic_yr = ?
            ', [$date, $academicYr]);

            $response['student'] = [
                'present' => $presentStudent,
                'total' => $totalStudent,
                'attendanceNotMarked' => [
                    'notMarked' => $result->not_marked,
                    'total' => $result->total,
                ],
            ];

            // 2. staff
            if ($short_code == 'HSCS') {
                $teachingStaff = DB::table('teacher as t')
                    ->join('user_master as u', 't.teacher_id', '=', 'u.reg_id')
                    ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                    ->where('t.isDelete', 'N')
                    ->where('tc.teaching', 'Y')
                    ->distinct('t.teacher_id')
                    ->count(DB::raw('t.teacher_id'));

                $attendanceteachingstaff = DB::table('teacher_attendance as ta')
                    ->join('teacher as t', DB::raw('ta.employee_id'), '=', DB::raw('CAST(t.employee_id AS UNSIGNED)'))
                    ->join('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                    ->where('t.isDelete', 'N')
                    ->where('tc.teaching', 'Y')
                    ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
                    ->distinct('ta.employee_id')
                    ->count(DB::raw('ta.employee_id'));

                $nonTeachingQuery1 = DB::table('teacher as t')
                    ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                    ->where('t.isDelete', 'N')
                    ->where('tc.teaching', 'N')
                    ->select(DB::raw('t.teacher_id'));

                $nonTeachingQuery2 = DB::table('teacher as c')
                    ->leftJoin('teacher_category as tc', 'c.tc_id', '=', 'tc.tc_id')
                    ->where('c.isDelete', 'N')
                    ->where('c.designation', 'Caretaker')
                    ->where('tc.teaching', 'N')
                    ->select(DB::raw('c.teacher_id'));

                $non_teachingStaff = $nonTeachingQuery1
                    ->union($nonTeachingQuery2)
                    ->distinct()
                    ->count();

                $attendanceNonTeaching1 = DB::table('teacher_attendance as ta')
                    ->join('teacher as t', DB::raw('ta.employee_id'), '=', DB::raw('CAST(t.employee_id AS UNSIGNED)'))
                    ->join('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                    ->where('t.isDelete', 'N')
                    ->where('tc.teaching', 'N')
                    ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
                    ->select(DB::raw('ta.employee_id'));

                $attendanceNonTeaching2 = DB::table('teacher_attendance as ta')
                    ->join('teacher as t', DB::raw('ta.employee_id'), '=', DB::raw('CAST(t.employee_id AS UNSIGNED)'))
                    ->where('t.isDelete', 'N')
                    ->where('t.designation', 'Caretaker')
                    ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
                    ->select(DB::raw('ta.employee_id'));

                $attendancenonteachingstaff = $attendanceNonTeaching1
                    ->union($attendanceNonTeaching2)
                    ->distinct()
                    ->count();

                $response['teachingStaff'] = [
                    'count' => $attendanceteachingstaff,
                    'total' => $teachingStaff,
                ];

                $response['non_teachingStaff'] = [
                    'count' => $attendancenonteachingstaff,
                    'total' => $non_teachingStaff,
                ];
            } else if ('SACS') {
                $teachingStaff = DB::table('teacher as t')
                    ->join('user_master as u', 't.teacher_id', '=', 'u.reg_id')
                    ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                    ->where('t.isDelete', 'N')
                    ->where('tc.teaching', 'Y')
                    ->distinct()
                    ->count(DB::raw('t.teacher_id'));

                $attendanceteachingstaff = DB::table('teacher_attendance as ta')
                    ->join(
                        'teacher as t',
                        DB::raw('ta.employee_id'),
                        '=',
                        DB::raw('CAST(t.employee_id AS UNSIGNED)')
                    )
                    ->join('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                    ->where('t.isDelete', 'N')
                    ->where('tc.teaching', 'Y')
                    ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
                    ->distinct()
                    ->count(DB::raw('ta.employee_id'));

                $nonTeachingTeachers = DB::table('teacher as t')
                    ->join('user_master as u', 't.teacher_id', '=', 'u.reg_id')
                    ->leftJoin('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                    ->where('t.isDelete', 'N')
                    ->where('tc.teaching', 'N')
                    ->select(DB::raw('t.teacher_id'));

                $nonTeachingCaretakers = DB::table('teacher as c')
                    ->leftJoin('teacher_category as tc', 'c.tc_id', '=', 'tc.tc_id')
                    ->where('c.isDelete', 'N')
                    ->where('c.designation', 'Caretaker')
                    ->where('tc.teaching', 'N')
                    ->select(DB::raw('c.teacher_id'));

                $non_teachingStaff = $nonTeachingTeachers
                    ->union($nonTeachingCaretakers)
                    ->distinct()
                    ->count();

                $attendanceNonTeachingTeachers = DB::table('teacher_attendance as ta')
                    ->join(
                        'teacher as t',
                        DB::raw('ta.employee_id'),
                        '=',
                        DB::raw('CAST(t.employee_id AS UNSIGNED)')
                    )
                    ->join('user_master as u', 't.teacher_id', '=', 'u.reg_id')
                    ->join('teacher_category as tc', 't.tc_id', '=', 'tc.tc_id')
                    ->where('t.isDelete', 'N')
                    ->where('tc.teaching', 'N')
                    ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
                    ->select(DB::raw('ta.employee_id'));

                $attendanceCaretakers = DB::table('teacher_attendance as ta')
                    ->join(
                        'teacher as t',
                        DB::raw('ta.employee_id'),
                        '=',
                        DB::raw('CAST(t.employee_id AS UNSIGNED)')
                    )
                    ->where('t.isDelete', 'N')
                    ->where('t.designation', 'Caretaker')
                    ->whereDate('ta.punch_time', DB::raw('CURDATE()'))
                    ->select(DB::raw('ta.employee_id'));

                $attendancenonteachingstaff = $attendanceNonTeachingTeachers
                    ->union($attendanceCaretakers)
                    ->distinct()
                    ->count();

                $response['teachingStaff'] = [
                    'count' => $attendanceteachingstaff,
                    'total' => $teachingStaff,
                ];

                $response['non_teachingStaff'] = [
                    'count' => $attendancenonteachingstaff,
                    'total' => $non_teachingStaff,
                ];
            }

            // 3. feeCollection
            DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

            $sql = "
                SELECT SUM(installment_fees - concession - paid_amount) AS pending_fee FROM
                (SELECT s.student_id, s.installment, installment_fees, COALESCE(SUM(d.amount), 0) AS concession, 0 AS paid_amount FROM
                view_student_fees_category s LEFT JOIN fee_concession_details d ON s.student_id = d.student_id AND s.installment = d.installment WHERE
                s.academic_yr = '$academicYr' and s.installment<>4 AND due_date < CURDATE() AND s.student_installment NOT IN
                (SELECT student_installment FROM view_student_fees_payment a WHERE a.academic_yr = '$academicYr') GROUP BY s.student_id, s.installment
                UNION SELECT f.student_id AS student_id, b.installment AS installment, b.installment_fees, COALESCE(SUM(c.amount), 0) AS concession,
                SUM(f.fees_paid) AS paid_amount FROM view_student_fees_payment f LEFT JOIN fee_concession_details c ON f.student_id = c.student_id
                AND f.installment = c.installment JOIN view_fee_allotment b ON f.fee_allotment_id = b.fee_allotment_id AND b.installment = f.installment
                WHERE b.installment<>4 and f.academic_yr = '$academicYr' GROUP BY f.installment, c.installment  HAVING
                (b.installment_fees - COALESCE(SUM(c.amount), 0)) > SUM(f.fees_paid)) as z
            ";

            $results = DB::select($sql);

            $pendingFee = $results[0]->pending_fee ?? 0;

            $collectedfees = DB::select(
                "SELECT 'Nursery' AS account, 
                IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
                SUM(d.amount) AS amount 
                    FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
                    WHERE a.student_id = b.student_id 
                    AND b.class_id = c.class_id 
                    AND a.fees_payment_id = d.fees_payment_id 
                    AND a.isCancel = 'N' 
                    AND a.academic_yr = '$academicYr' 
                    AND c.name = 'Nursery' 
                    GROUP BY d.installment 

                    UNION

                    SELECT 'KG' AS account, 
                        IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
                        SUM(d.amount) AS amount 
                            FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
                            WHERE a.student_id = b.student_id 
                            AND b.class_id = c.class_id 
                            AND a.fees_payment_id = d.fees_payment_id 
                            AND a.isCancel = 'N' 
                            AND a.academic_yr = '$academicYr' 
                            AND c.name IN ('LKG','UKG') 
                            GROUP BY d.installment 

                            UNION

                            SELECT 'School' AS account, 
                                IF(d.installment = 4, 'CBSE Exam fee', d.installment) AS installment, 
                                SUM(d.amount) AS amount 
                            FROM view_fees_payment_record a, view_fees_payment_detail d, student b, class c 
                            WHERE a.student_id = b.student_id 
                            AND b.class_id = c.class_id 
                            AND a.fees_payment_id = d.fees_payment_id 
                            AND a.isCancel = 'N' 
                            AND a.academic_yr = '$academicYr' 
                            AND c.name IN ('1','2','3','4','5','6','7','8','9','10','11','12') 
                            GROUP BY d.installment"
            );
            $totalAmount = number_format(collect($collectedfees)->sum('amount'), 2, '.', '');

            $response['fees_collection'] = [
                'Collected Fees' => $totalAmount,
                'Pending Fees' => $pendingFee
            ];

            $response['ticket_count'] = $this->ticketCountCard($academicYr, $role_id);
            $response['birthday_count'] = $this->birthDayCountCard($academicYr, $role_id);

            return response()->json([
                'data' => $response,
                'count' => count($response),
            ], 200);
        } catch (Exception $err) {
            return response()->json([
                'message' => 'Something went wrong, Server Error',
                'error' => $err->getMessage(),
                'line' => $err->getLine(),
            ], 500);
        }
    }

    public function sendMessagesForTeacher(Request $request)
    {
        SendTeacherMessageJob::dispatch(
            $request->teacher_ids,
            $request->message,
            $request->message_type
        );

        return response()->json([
            'status' => 200,
            'message' => 'Message job queued successfully',
            'success' => true
        ]);
    }

    // 07-04-2026
    public function getAllTeachingNonTeachingStaffList(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            $globalVariables = App::make('global_variables');
            $parent_app_url = $globalVariables['parent_app_url'];
            $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

            //  Get tc_id from request
            $tc_id = $request->input('tc_id');

            //  Build query first
            $stafflistQuery = DB::table('teacher')
                ->select('teacher.*');

            // Apply tc_id filter (FINAL FIX)
            if (!empty($tc_id)) {
                $stafflistQuery->whereRaw(
                    'LOWER(TRIM(tc_id)) = ?',
                    [strtolower(trim($tc_id))]
                );
            }

            //  Execute query
            $stafflist = $stafflistQuery->get();

            // Get class-section mappings for all teachers
            $classMappings = DB::table('class_teachers')
                ->join('class', 'class_teachers.class_id', '=', 'class.class_id')
                ->join('section', 'class_teachers.section_id', '=', 'section.section_id')
                ->select(
                    'class_teachers.teacher_id',
                    'class.name as classname',
                    'section.name as sectionname',
                    'class_teachers.class_id',
                    'class_teachers.section_id'
                )
                ->where('class_teachers.academic_yr', $customClaims)
                ->orderBy('class_teachers.section_id')
                ->get();

            // Attach classes + fix image URL
            $stafflist = $stafflist->map(function ($staff) use ($classMappings, $codeigniter_app_url) {
                $concatprojecturl = $codeigniter_app_url . 'uploads/teacher_image/';

                // Fix image path
                $staff->teacher_image_name = $staff->teacher_image_name
                    ? $concatprojecturl . $staff->teacher_image_name
                    : null;

                // Attach class-section data
                $staff->classes = $classMappings
                    ->where('teacher_id', $staff->teacher_id)
                    ->values();

                return $staff;
            });

            return response()->json($stafflist);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    // Mahima 02-02-2026
    // public function getAllHouses(Request $request)
    // {
    //     try {
    //         $academic_year = JWTAuth::getPayload()->get('academic_year');

    //         $query = DB::table('house as h')
    //             ->select('h.*')
    //             ->where('h.academic_yr', $academic_year);

    //         $houses = $query->orderBy('h.house_name', 'asc')->get();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'House data fetched successfully',
    //             'data' => $houses
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    // public function insertHouse(Request $request)
    // {
    //     try {
    //         $academic_year = JWTAuth::getPayload()->get('academic_year');

    //         $exists = DB::table('house')
    //             ->where('academic_yr', $academic_year)
    //             ->where(function ($query) use ($request) {
    //                 $query
    //                     ->where('house_name', $request->house_name)
    //                     ->orWhere('color_code', $request->color);
    //             })
    //             ->exists();

    //         if ($exists) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'House name or color already exists for this academic year'
    //             ], 409);
    //         }

    //         $houseId = DB::table('house')->insertGetId([
    //             'house_name' => $request->house_name,
    //             'color_code' => $request->color,
    //             'academic_yr' => $academic_year,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'House inserted successfully',
    //             'house_id' => $houseId
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function updateHouse(Request $request, $id)
    // {
    //     try {
    //         $academic_year = JWTAuth::getPayload()->get('academic_year');

    //         // Check house exists
    //         $house = DB::table('house')
    //             ->where('house_id', $id)
    //             ->first();

    //         if (!$house) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'House not found'
    //             ], 404);
    //         }

    //         $exists = DB::table('house')
    //             ->where('academic_yr', $academic_year)
    //             ->where('house_id', '!=', $id)
    //             ->where(function ($query) use ($request) {
    //                 $query
    //                     ->where('house_name', $request->house_name)
    //                     ->orWhere('color_code', $request->color_code);
    //             })
    //             ->exists();

    //         if ($exists) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'House name or color already exists for this academic year'
    //             ], 409);
    //         }

    //         // Check if house is used in student table
    //         $isUsed = DB::table('student')
    //             ->where('house', $id)
    //             ->exists();

    //         if ($isUsed) {
    //             // Only update house_name
    //             DB::table('house')
    //                 ->where('house_id', $id)
    //                 ->update([
    //                     'house_name' => $request->house_name,
    //                 ]);

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'House name updated successfully.'
    //             ], 200);
    //         } else {
    //             DB::table('house')
    //                 ->where('house_id', $id)
    //                 ->update([
    //                     'house_name' => $request->house_name,
    //                     'color_code' => $request->color_code,
    //                 ]);

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'House updated successfully'
    //             ], 200);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function deleteHouse($id)
    // {
    //     try {
    //         $house = DB::table('house')
    //             ->where('house_id', $id)
    //             ->first();

    //         if (!$house) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'House not found'
    //             ], 404);
    //         }

    //         $isUsed = DB::table('student')
    //             ->where('house', $id)
    //             ->exists();

    //         if ($isUsed) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'House cannot be deleted because it is used.'
    //             ], 400);
    //         }

    //         // Delete house
    //         DB::table('house')
    //             ->where('house_id', $id)
    //             ->delete();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'House deleted successfully'
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // Mahima 07-04-2026
    public function getAllHouses(Request $request)
    {
        try {
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            $houses = DB::table('house as h')
                ->select('h.*')
                ->where(function ($query) use ($academic_year) {
                    $query
                        ->whereJsonContains('h.academic_yr', $academic_year)
                        ->orWhereNull('h.academic_yr');
                })
                ->orderBy('h.house_name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'House data fetched successfully',
                'data' => $houses
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function insertHouse(Request $request)
    // {
    //     try {
    //         $academic_year = JWTAuth::getPayload()->get('academic_year');

    //         // Check if same house already exists (ignore year)
    //         $existingHouse = DB::table('house')
    //             ->where(function ($query) use ($request) {
    //                 $query->where('house_name', $request->house_name)
    //                     ->orWhere('color_code', $request->color);
    //             })
    //             ->first();

    //         if ($existingHouse) {

    //             $academicYears = json_decode($existingHouse->academic_yr, true) ?? [];

    //             // If year already exists → prevent duplicate
    //             if (in_array($academic_year, $academicYears)) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'House already exists for this academic year'
    //                 ], 409);
    //             }

    //             // Append new academic year
    //             $academicYears[] = $academic_year;

    //             DB::table('house')
    //                 ->where('house_id', $existingHouse->house_id)
    //                 ->update([
    //                     'academic_yr' => json_encode($academicYears)
    //                 ]);

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Academic year added to existing house',
    //                 'house_id' => $existingHouse->house_id
    //             ], 200);
    //         }

    //         // Insert new house with JSON year
    //         $houseId = DB::table('house')->insertGetId([
    //             'house_name' => $request->house_name,
    //             'color_code' => $request->color,
    //             'academic_yr' => json_encode([$academic_year]),
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'House inserted successfully',
    //             'house_id' => $houseId
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function insertHouse(Request $request)
    {
        try {
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            $house_name = $request->house_name;
            $color_code = $request->color;

            // Step 1: Find exact match (same house + color)
            $existingExact = DB::table('house')
                ->where('house_name', $house_name)
                ->where('color_code', $color_code)
                ->first();

            if ($existingExact) {
                $years = json_decode($existingExact->academic_yr, true) ?? [];

                //  Case 4: same year already exists
                if (in_array($academic_year, $years)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'House already exists.'
                    ], 409);
                }

                //  Case 1: append new year
                $years[] = $academic_year;

                DB::table('house')
                    ->where('house_id', $existingExact->house_id)
                    ->update([
                        'academic_yr' => json_encode($years)
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'House added successfully.'
                ], 200);
            }

            //  Step 2: Check same house_name for this year (Case 2)
            $sameNameExists = DB::table('house')
                ->where('house_name', $house_name)
                ->whereJsonContains('academic_yr', $academic_year)
                ->exists();

            if ($sameNameExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'House name already exists.'
                ], 409);
            }

            //  Step 3: Check same color_code for this year (Case 3)
            $sameColorExists = DB::table('house')
                ->where('color_code', $color_code)
                ->whereJsonContains('academic_yr', $academic_year)
                ->exists();

            if ($sameColorExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Color already assigned to another house.'
                ], 409);
            }

            //  Insert new row
            DB::table('house')->insert([
                'house_name' => $house_name,
                'color_code' => $color_code,
                'academic_yr' => json_encode([$academic_year]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'House inserted successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function updateHouse(Request $request, $id)
    // {
    //     try {
    //         $academic_year = JWTAuth::getPayload()->get('academic_year');

    //         // 🔍 Check house exists
    //         $house = DB::table('house')
    //             ->where('house_id', $id)
    //             ->first();

    //         if (!$house) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'House not found'
    //             ], 404);
    //         }

    //         $house_name = $request->house_name;
    //         $color_code = $request->color_code;

    //         //  Check duplicate house_name for this academic year (excluding current)
    //         $sameNameExists = DB::table('house')
    //             ->where('house_id', '!=', $id)
    //             ->where('house_name', $house_name)
    //             ->whereJsonContains('academic_yr', $academic_year)
    //             ->exists();

    //         if ($sameNameExists) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'House name already exists.'
    //             ], 409);
    //         }

    //         // Check duplicate color_code for this academic year (excluding current)
    //         $sameColorExists = DB::table('house')
    //             ->where('house_id', '!=', $id)
    //             ->where('color_code', $color_code)
    //             ->whereJsonContains('academic_yr', $academic_year)
    //             ->exists();

    //         if ($sameColorExists) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Color already assigned to another house.'
    //             ], 409);
    //         }

    //         // Check if house is used in student table
    //         $isUsed = DB::table('student')
    //             ->where('house', $id)
    //             ->exists();

    //         if ($isUsed) {
    //             // Only update name
    //             DB::table('house')
    //                 ->where('house_id', $id)
    //                 ->update([
    //                     'house_name' => $house_name,
    //                 ]);

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'House updated successfully.'
    //             ], 200);
    //         }

    //         // Full update allowed
    //         DB::table('house')
    //             ->where('house_id', $id)
    //             ->update([
    //                 'house_name' => $house_name,
    //                 'color_code' => $color_code,
    //             ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'House updated successfully'
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function updateHouse(Request $request, $id)
    {
        try {
            $academic_year = JWTAuth::getPayload()->get('academic_yr');

            // 🔍 Check house exists
            $house = DB::table('house')
                ->where('house_id', $id)
                ->first();

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'House not found'
                ], 404);
            }

            $house_name = trim($request->house_name);
            $color_code = trim($request->color_code);

            // 📦 Decode academic years
            $currentYears = json_decode($house->academic_yr, true);

            // ================================
            // 🔁 CASE 1: MULTIPLE YEARS → SPLIT
            // ================================
            if (is_array($currentYears) && count($currentYears) > 1) {
                // ✅ Step 1: Remove current academic year from old record
                $updatedYears = array_values(array_filter($currentYears, function ($yr) use ($academic_year) {
                    return $yr != $academic_year;
                }));

                DB::table('house')
                    ->where('house_id', $id)
                    ->update([
                        'academic_yr' => json_encode($updatedYears)
                    ]);

                // ✅ Step 2: Check duplicates ONLY in this academic year

                $sameNameExists = DB::table('house')
                    ->where('house_name', $house_name)
                    ->whereJsonContains('academic_yr', $academic_year)
                    ->exists();

                if ($sameNameExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'House name already exists for this academic year.'
                    ], 409);
                }

                $sameColorExists = DB::table('house')
                    ->where('color_code', $color_code)
                    ->whereJsonContains('academic_yr', $academic_year)
                    ->exists();

                if ($sameColorExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Color already assigned for this academic year.'
                    ], 409);
                }

                // ✅ Step 3: Insert new record
                $newHouseId = DB::table('house')->insertGetId([
                    'house_name' => $house_name,
                    'color_code' => $color_code,
                    'academic_yr' => json_encode([$academic_year]),
                ]);

                // ✅ Step 4: Update students for this academic year
                DB::table('student')
                    ->where('house', $id)
                    ->where('academic_yr', $academic_year)  // make sure column exists
                    ->update([
                        'house' => $newHouseId
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'House updated for this academic year only.',
                    'new_house_id' => $newHouseId
                ], 200);
            }

            // ==================================
            // ✏️ CASE 2: SINGLE YEAR → NORMAL UPDATE
            // ==================================

            // ✅ Duplicate check (same academic year only)
            $sameNameExists = DB::table('house')
                ->where('house_id', '!=', $id)
                ->where('house_name', $house_name)
                ->whereJsonContains('academic_yr', $academic_year)
                ->exists();

            if ($sameNameExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'House name already exists for this academic year.'
                ], 409);
            }

            $sameColorExists = DB::table('house')
                ->where('house_id', '!=', $id)
                ->where('color_code', $color_code)
                ->whereJsonContains('academic_yr', $academic_year)
                ->exists();

            if ($sameColorExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Color already assigned for this academic year.'
                ], 409);
            }

            // ✅ Update directly
            DB::table('house')
                ->where('house_id', $id)
                ->update([
                    'house_name' => $house_name,
                    'color_code' => $color_code,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'House updated successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteHouse($id)
    {
        try {
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            $house = DB::table('house')
                ->where('house_id', $id)
                ->first();

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'House not found'
                ], 404);
            }

            // Check if used in student table (optional: you can also check per year if needed)
            $isUsed = DB::table('student')
                ->where('house', $id)
                ->exists();

            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'House cannot be deleted because it is used.'
                ], 400);
            }

            // Decode JSON academic years
            $years = json_decode($house->academic_yr, true) ?? [];

            // If current year not found
            if (!in_array($academic_year, $years)) {
                return response()->json([
                    'success' => false,
                    'message' => 'House not assigned to this academic year'
                ], 400);
            }

            // Remove current academic year
            $updatedYears = array_values(array_filter($years, function ($year) use ($academic_year) {
                return $year !== $academic_year;
            }));

            if (empty($updatedYears)) {
                // No years left → delete row
                DB::table('house')
                    ->where('house_id', $id)
                    ->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'House deleted completely (no academic years left)'
                ], 200);
            }

            // Update remaining years
            DB::table('house')
                ->where('house_id', $id)
                ->update([
                    'academic_yr' => json_encode($updatedYears)
                ]);

            return response()->json([
                'success' => true,
                'message' => 'House removed for current academic year only'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAdmissionUsers(Request $request)
    {
        try {
            $user = $this->authenticateUser();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $users = DB::table('new_adm_registration as r')
                ->Join('new_adm_user_master as u', 'u.nar_id', '=', 'r.nar_id')
                ->select(
                    'r.nar_id',
                    'r.parent_name',
                    'r.email',
                    'r.phone_no',
                    'r.date',
                    'u.user_id',
                    'u.special_user'
                )
                ->orderBy('r.nar_id', 'desc')
                ->get();

            $data = $users->map(function ($row) {
                return [
                    'nar_id' => $row->nar_id,
                    'parent_name' => $row->parent_name,
                    'email' => $row->email,
                    'phone_no' => $row->phone_no,
                    'date' => $row->date,
                    'user_id' => $row->user_id,
                    'is_special_user' => $row->special_user === 'Y',
                    'user_type' => $row->special_user === 'Y' ? 'SPECIAL' : 'NORMAL'
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateSpecialUser(Request $request)
    {
        try {
            $user = $this->authenticateUser();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $request->validate([
                'nar_id' => 'required|integer',
                'special_user' => 'required|in:Y,N'
            ]);

            $record = DB::table('new_adm_user_master')
                ->where('nar_id', $request->nar_id)
                ->first();

            if (!$record) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            DB::table('new_adm_user_master')
                ->where('nar_id', $request->nar_id)
                ->update([
                    'special_user' => $request->special_user
                ]);

            return response()->json([
                'status' => 200,
                'message' => 'Special user updated successfully',
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadRemarkFile(Request $request)
    {
        try {
            $remark_id = $request->input('remark_id');
            $file_name = $request->input('file_name');

            if (!$remark_id || !$file_name) {
                return response()->json([
                    'status' => 400,
                    'message' => 'remark_id and file_name are required',
                    'success' => false
                ]);
            }

            $globalVariables = App::make('global_variables');
            $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

            $remark = DB::table('remark')
                ->where('remark_id', $remark_id)
                ->first();

            if (!$remark) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Remark not found',
                    'success' => false
                ]);
            }

            $dateFolder = Carbon::parse($remark->remark_date)->format('Y-m-d');

            $fileUrl = $codeigniter_app_url . "uploads/remark/{$dateFolder}/{$remark_id}/{$file_name}";

            // STREAM (binary safe)
            return response()->streamDownload(function () use ($fileUrl) {
                $stream = fopen($fileUrl, 'rb');  // binary mode

                if ($stream) {
                    while (!feof($stream)) {
                        echo fread($stream, 8192);
                        flush();
                    }
                    fclose($stream);
                }
            }, $file_name, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $file_name . '"',
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
                'success' => false
            ]);
        }
    }
}
