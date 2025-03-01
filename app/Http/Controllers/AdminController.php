<?php

namespace App\Http\Controllers;

use Exception;
use Validator;
use App\Models\User;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Classes;
use App\Models\Parents;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Division;
use App\Mail\WelcomeEmail;
use App\Models\Attendence;
use App\Models\UserMaster;
use App\Models\MarksHeadings;
use App\Models\StaffNotice;
use Illuminate\Http\Request;
use App\Models\SubjectMaster;
use App\Models\ContactDetails;
use Illuminate\Support\Carbon;
use App\Models\BankAccountName;
use Illuminate\Validation\Rule;
use App\Models\SubjectAllotment;
use App\Models\Class_teachers;
use Illuminate\Http\JsonResponse;
use App\Mail\TeacherBirthdayEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Models\SubjectForReportCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\DeletedContactDetails;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\SubjectAllotmentForReportCard;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Response;
use App\Models\LeaveType;
use App\Models\LeaveAllocation;
use App\Models\Allot_mark_headings;
use App\Models\LeaveApplication;
use Illuminate\Support\Facades\App;
use League\Csv\Writer;
use ZipArchive;
use File;
// use Maatwebsite\Excel\Facades\Excel;
// use App\Exports\IdCardExport;
// use Illuminate\Support\Facades\Auth;


class AdminController extends Controller
{
    public function hello(){
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
        $textmsg .= "Wishing you many happy returns of the day. May the coming year be filled with peace, prosperity, good health, and happiness.<br/><br/>";
        $textmsg .= "Best Wishes,<br/>";
        $textmsg .= "St. Arnolds Central School";

        $data = [
            'title' => 'Birthday Greetings!!',
            'body' => $textmsg,
            'teacher' => $teacher
        ];

        Mail::to($teacher->email)->send(new TeacherBirthdayEmail($data));
    }

    return response()->json(['message' => 'Birthday emails sent successfully']);
}



    public function getAcademicyearlist(Request $request){

        $academicyearlist = Setting::get()->academic_yr;
        return response()->json($academicyearlist);

          }

    public function getStudentData(Request $request){

        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');  

        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }
        $count = Student::where('IsDelete', 'N')
                          ->where('academic_yr',$academicYr)
                          ->count();
        $currentDate = Carbon::now()->toDateString();
        $present = Attendence::where('only_date', $currentDate)
                            ->where('attendance_status', '0')
                            ->where('academic_yr',$academicYr)
                            ->count(); 
        return response()->json([
            'count'=>$count,
            'present'=>$present,
        ]);
    }

    public function staff(){
     
       $teachingStaff = UserMaster::where('IsDelete','N')
                        ->where('role_id','T')
                        ->count();

        $non_teachingStaff = UserMaster::where('IsDelete', 'N')
                        ->whereIn('role_id', ['A', 'F', 'M', 'L', 'X', 'Y'])
                        ->count();            

       return response()->json([
        'teachingStaff'=>$teachingStaff,
        'non_teachingStaff'=>$non_teachingStaff,
       ]);                 
    }


    public function staffBirthdaycount(Request $request)
{
    $currentDate = Carbon::now();
    $count = Teacher::where('IsDelete', 'N')
                     ->whereMonth('birthday', $currentDate->month)
                     ->whereDay('birthday', $currentDate->day)
                     ->count();

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
        return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
    }

    $currentDate = Carbon::now();

    $staffBirthday = Teacher::where('IsDelete', 'N')
        ->whereMonth('birthday', $currentDate->month)
        ->whereDay('birthday', $currentDate->day)
        ->get();

    return response()->json([
        'staffBirthday' => $staffBirthday,
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
            ->groupBy('events.unq_id', 'events.title', 'events.event_desc', 'events.start_date', 'events.end_date', 'events.start_time', 'events.end_time')
            ->orderBy('events.start_date')
            ->orderByDesc('events.start_time')
            ->get();

        return response()->json($events);
    }


    public function getParentNotices(Request $request): JsonResponse
    {
        // $academicYr = $request->header('X-Academic-Year');
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year'); 
        if (!$academicYr) {
            return response()->json(['message' => 'Academic year not found in request headers', 'success' => false], 404);
        }

        // Retrieve parent notices with their related class names
        $parentNotices = Notice::select([
                'subject',
                'notice_desc',
                'notice_date',
                'notice_type',
                \DB::raw('GROUP_CONCAT(class.name) as class_name')
            ])
            ->join('class', 'notice.class_id', '=', 'class.class_id') // Adjusted table name to singular 'class'
            ->where('notice.publish', 'Y')
            ->where('notice.academic_yr', $academicYr)
            ->groupBy('notice.subject', 'notice.notice_desc', 'notice.notice_date', 'notice.notice_type')
            ->orderBy('notice_date')
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
        // Fetch notices with teacher names
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

// public function getClassDivisionTotalStudents()
// {
//     $results = DB::table('class as c')
//         ->leftJoin('section as s', 'c.class_id', '=', 's.class_id')
//         ->leftJoin(DB::raw('(SELECT section_id, COUNT(student_id) AS students_count FROM student GROUP BY section_id) as st'), 's.section_id', '=', 'st.section_id')
//         ->select(
//             DB::raw("CONCAT(c.name, ' ', COALESCE(s.name, 'No division assigned')) AS class_division"),
//             DB::raw("SUM(st.students_count) AS total_students"),
//             'c.name as class_name',
//             's.name as section_name'
//         )
//         ->groupBy('c.name', 's.name')
//         ->orderBy('c.name')
//         ->orderBy('s.name')
//         ->get();

//     return response()->json($results);
// }

public function getClassDivisionTotalStudents(Request $request)
{
    // Get the academic year from the token payload
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');

    // Validate academic year
    if (!$academicYr) {
        return response()->json(['error' => 'Academic year is missing'], 400);
    }

    $results = DB::table('class as c')
        ->leftJoin('section as s', 'c.class_id', '=', 's.class_id')
        ->leftJoin(DB::raw("
            (SELECT section_id, COUNT(student_id) AS students_count
             FROM student
             WHERE academic_yr = '{$academicYr}'  -- Filter by academic year
             GROUP BY section_id) as st
        "), 's.section_id', '=', 'st.section_id')
        ->select(
            DB::raw("CONCAT(c.name, ' ', COALESCE(s.name, 'No division assigned')) AS class_division"),
            DB::raw("SUM(st.students_count) AS total_students"),
            'c.name as class_name',
            's.name as section_name'
        )
        ->groupBy('c.name', 's.name')
        ->orderBy('c.name')
        ->orderBy('s.name')
        ->get();

    return response()->json($results);
}


 public function ticketCount(Request $request){
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 
    $role_id = $payload->get('role_id');

    $count = DB::table('ticket')
           ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
           ->where('service_type.role_id',$role_id)
           ->where('ticket.acd_yr',$academicYr)
           ->where('ticket.status', '!=', 'Closed')
           ->count();

           return response()->json(['count' => $count]);
 }
 public function getTicketList(Request $request){
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
             ->where('ticket.acd_yr',$academicYr)
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

 public function feeCollection(Request $request) {
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 

    DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

    $sql = "
        SELECT SUM(installment_fees - concession - paid_amount) AS pending_fee 
        FROM (
            SELECT s.student_id, s.installment, installment_fees, COALESCE(SUM(d.amount), 0) AS concession, 0 AS paid_amount 
            FROM view_student_fees_category s
            LEFT JOIN fee_concession_details d ON s.student_id = d.student_id AND s.installment = d.installment 
            WHERE s.academic_yr = ? AND due_date < CURDATE() 
                AND s.student_installment NOT IN (
                    SELECT student_installment 
                    FROM view_student_fees_payment a 
                    WHERE a.academic_yr = ?
                ) 
            GROUP BY s.student_id, s.installment, installment_fees

            UNION

            SELECT f.student_id AS student_id, b.installment AS installment, b.installment_fees, COALESCE(SUM(c.amount), 0) AS concession, SUM(f.fees_paid) AS paid_amount 
            FROM view_student_fees_payment f
            LEFT JOIN fee_concession_details c ON f.student_id = c.student_id AND f.installment = c.installment 
            JOIN view_fee_allotment b ON f.fee_allotment_id = b.fee_allotment_id AND b.installment = f.installment 
            WHERE f.academic_yr = ?
            GROUP BY f.student_id, b.installment, b.installment_fees, c.installment, b.fees_category_id
            HAVING (b.installment_fees - COALESCE(SUM(c.amount), 0)) > SUM(f.fees_paid)
        ) as z
    ";

    $results = DB::select($sql, [$academicYr, $academicYr, $academicYr]);

    $pendingFee = $results[0]->pending_fee;

    return response()->json($pendingFee);
}


// public function getHouseViseStudent(Request $request) {
//     $className = $request->input('class_name');
//     // $academicYear = $request->header('X-Academic-Year');
//     $sessionData = session('sessionData');
//     if (!$sessionData) {
//         return response()->json(['message' => 'Session data not found', 'success' => false], 404);
//     }

//     $academicYr = $sessionData['academic_yr'] ?? null;
//     if (!$academicYr) {
//         return response()->json(['message' => 'Academic year not found in session data', 'success' => false], 404);
//     }


//     $results = DB::select("
//         SELECT CONCAT(class.name, ' ', section.name) AS class_section,
//                house.house_name AS house_name,
//                house.color_code AS color_code,
//                COUNT(student.student_id) AS student_counts
//         FROM student
//         JOIN class ON student.class_id = class.class_id
//         JOIN section ON student.section_id = section.section_id
//         JOIN house ON student.house = house.house_id
//         WHERE student.IsDelete = 'N'
//           AND class.name = ?
//           AND student.academic_yr = ?
//         GROUP BY class_section, house_name, house.color_code
//         ORDER BY class_section, house_name
//     ", [$className, $academicYr]);

//     return response()->json($results);
// }

public function getHouseViseStudent(Request $request) {
    $className = $request->input('class_name');
    // $sessionData = session('sessionData');
    // if (!$sessionData) {
    //     return response()->json(['message' => 'Session data not found', 'success' => false], 404);
    // }

    // $academicYr = $sessionData['academic_yr'] ?? null;
    // if (!$academicYr) {
    //     return response()->json(['message' => 'Academic year not found in session data', 'success' => false], 404);
    // }
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
        $query .= " AND class.name = ?";
        $params[] = $className;
    }

    $query .= "
        GROUP BY class_section, house_name, house.color_code
        ORDER BY class_section, house_name
    ";

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


// public function updateAcademicYearForAuthUser(Request $request)
// {
//     $user = Auth::user();     
//     if ($user) {
//         session(['academic_yr' => $request->newAcademicYear]);
//         Log::info('New academic year set:', ['user_id' => $user->id, 'academic_yr' => $request->newAcademicYear]);
//     }
// }


public function getBankAccountName()
{
    $bankAccountName = BankAccountName::all();
    return response()->json([
        'bankAccountName' => $bankAccountName,       
    ]);
}

public function pendingCollectedFeeData(): JsonResponse
{
     DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

    $subQuery1 = DB::table('view_student_fees_category as s')
        ->leftJoin('fee_concession_details as d', function ($join) {
            $join->on('s.student_id', '=', 'd.student_id')
                 ->on('s.installment', '=', 'd.installment');
        })
        ->select(
            's.student_id',
            's.installment',
            's.installment_fees',
            DB::raw('COALESCE(SUM(d.amount), 0) as concession'),
            DB::raw('0 as paid_amount')
        )
        ->where('s.academic_yr', '2023-2024')
        ->where('s.installment', '<>', 4)
        ->where('s.due_date', '<', DB::raw('CURDATE()'))
        ->whereNotIn('s.student_installment', function ($query) {
            $query->select('a.student_installment')
                  ->from('view_student_fees_payment as a')
                  ->where('a.academic_yr', '2023-2024');
        })
        ->groupBy('s.student_id', 's.installment');

    $subQuery2 = DB::table('view_student_fees_payment as f')
        ->leftJoin('fee_concession_details as c', function ($join) {
            $join->on('f.student_id', '=', 'c.student_id')
                 ->on('f.installment', '=', 'c.installment');
        })
        ->join('view_fee_allotment as b', function ($join) {
            $join->on('f.fee_allotment_id', '=', 'b.fee_allotment_id')
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
        ->where('f.academic_yr', '2023-2024')
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
            $join->on('s.student_id', '=', 'd.student_id')
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
            $query->select('a.student_installment')
                  ->from('view_student_fees_payment as a')
                  ->where('a.academic_yr', $academicYr);
        })
        ->groupBy('s.student_id', 's.installment');

    $subQuery2 = DB::table('view_student_fees_payment as f')
        ->leftJoin('fee_concession_details as c', function ($join) {
            $join->on('f.student_id', '=', 'c.student_id')
                 ->on('f.installment', '=', 'c.installment');
        })
        ->join('view_fee_allotment as b', function ($join) {
            $join->on('f.fee_allotment_id', '=', 'b.fee_allotment_id')
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


public function collectedFeeList(Request $request){
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 
    $bankAccountNames = DB::table('bank_account_name')
        ->whereIn('account_name', ['Nursery', 'KG', 'School'])
        ->pluck('account_name')
        ->toArray();

    $query = DB::table('view_fees_payment_record as a')
        ->join('view_fees_payment_detail as d', 'a.fees_payment_id', '=', 'd.fees_payment_id')
        ->join('student as b', 'a.student_id', '=', 'b.student_id')
        ->join('class as c', 'b.class_id', '=', 'c.class_id')
        ->select(DB::raw("'Total' as account"), 'd.installment', DB::raw('SUM(d.amount) as amount'))
        ->where('a.isCancel', 'N')
        ->where('a.academic_yr', $academicYr)
        ->groupBy('d.installment');

    foreach ($bankAccountNames as $class) {
        $query->union(function ($query) use ($class, $academicYr) {
            $query->select(DB::raw("'{$class}' as account"), 'd.installment', DB::raw('SUM(d.amount) as amount'))
                ->from('view_fees_payment_record as a')
                ->join('view_fees_payment_detail as d', 'a.fees_payment_id', '=', 'd.fees_payment_id')
                ->join('student as b', 'a.student_id', '=', 'b.student_id')
                ->join('class as c', 'b.class_id', '=', 'c.class_id')
                ->where('a.isCancel', 'N')
                ->where('a.academic_yr', $academicYr);

            if ($class === 'Nursery') {
                $query->where('c.name', 'Nursery');
            } elseif ($class === 'KG') {
                $query->whereIn('c.name', ['LKG', 'UKG']);
            } elseif ($class === 'School') {
                $query->whereIn('c.name', ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']);
            }

            $query->groupBy('d.installment');
        });
    }

    $results = $query->get();

    $formattedResults = [];

    foreach ($results as $result) {
        $account = $result->account;

        if ($account !== 'Total') {
            $formattedResults[$account][] = [
                'installment' => $result->installment,
                'amount' => $result->amount,
            ];
        }
    }

    return response()->json($formattedResults);
}


public function listSections(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');
        $sections = Section::where('academic_yr', $academicYr)->get();
        
        return response()->json($sections);
  }

  public function checkSectionName(Request $request)
  {
      $request->validate([
          'name' => 'required|string|max:30',
      ]);
      $name = $request->input('name');
      $exists = Section::where(DB::raw('LOWER(name)'), strtolower($name))->exists();

      return response()->json(['exists' =>$exists]);
  }

public function updateSection(Request $request, $id)
{
            $payload = getTokenPayload($request);
            $academicYr = $payload->get('academic_year');
            $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z]+$/',
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
        ]);

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


 // Methods for the classes model

 public function checkClassName(Request $request)
 {
     $request->validate([
         'name' => 'required|string|max:30',
     ]); 
     $name = $request->input('name');     
     $exists = Classes::where(DB::raw('LOWER(name)'), strtolower($name))->exists(); 
     return response()->json(['exists' => $exists]);
 }
 

// public function getClass(Request $request)
// {   
//     $payload = getTokenPayload($request);    
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');
//     $classes = Classes::with('getDepartment')
//         ->withCount('students')
//         ->where('academic_yr', $academicYr)
//         ->orderBy('name','asc')
//         ->get();
//     return response()->json($classes);
// }

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
        ->orderByRaw("CASE 
            WHEN name REGEXP '^[0-9]' THEN 1 
            ELSE 0 
        END, 
        name REGEXP '^[0-9]', 
        CAST(name AS SIGNED) ASC, 
        name ASC") // Ensure alphabetical first, then numeric
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

// public function updateClass(Request $request, $id)
// {
//     $validator = \Validator::make($request->all(), [
//         'name' => ['required', 'string', 'max:30'],
//         'department_id' => ['required', 'integer'],
//     ], [
//         'name.required' => 'The name field is required.',
//         'name.string' => 'The name field must be a string.',
//         'name.max' => 'The name field must not exceed 255 characters.',
//         'department_id.required' => 'The department ID is required.',
//         'department_id.integer' => 'The department ID must be an integer.',
//     ]);
//     if ($validator->fails()) {
//         return response()->json([
//             'status' => 422,
//             'errors' => $validator->errors(),
//         ], 422);
//     }
//     $class = Classes::find($id);
//     if (!$class) {
//         return response()->json(['message' => 'Class not found', 'success' => false], 404);
//     }
//     $payload = getTokenPayload($request);
//     if (!$payload) {
//         return response()->json(['error' => 'Invalid or missing token'], 401);
//     }
//     $academicYr = $payload->get('academic_year');
//     $class->name = $request->name;
//     $class->department_id = $request->department_id;
//     $class->academic_yr = $academicYr;
//     $class->save();
//     return response()->json([
//         'status' => 200,
//         'message' => 'Class updated successfully',
//         'data' => $class,
//     ]);
// }


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

    }
    else{
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


public function  getClassforDivision(Request $request){
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year');
   $classList = Classes::where('academic_yr',$academicYr)->get();
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
    $class_id=$request->class_id;
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
    return response()->json([
        'status' => 200,
        'message' => 'Division deleted successfully',
        'success' => true
                          ]
                            );
}


public function getStaffList(Request $request) {
    $globalVariables = App::make('global_variables');
    $parent_app_url = $globalVariables['parent_app_url'];
    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
    $stafflist = Teacher::where('designation', '!=', 'Caretaker')
        ->get()
        ->map(function ($staff)use($parent_app_url,$codeigniter_app_url){
            $concatprojecturl = $codeigniter_app_url."".'uploads/teacher_image/';
            if ($staff->teacher_image_name) {
                $staff->teacher_image_name = $concatprojecturl.""."$staff->teacher_image_name";
            } else {
                $staff->teacher_image_name = null; 
            }
            return $staff;
        });
    return response()->json($stafflist);
}
//Edited by - Manish Kumar sharma 15-02-2025
public function editStaff($id)
{
    try {
        // Find the teacher by ID
        $teacher = Teacher::findOrFail($id);
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        $concatprojecturl = $codeigniter_app_url."".'uploads/teacher_image/';

        // Check if the teacher has an image and generate the URL if it exists
        if ($teacher->teacher_image_name) {
            $teacher->teacher_image_name = $concatprojecturl.""."$teacher->teacher_image_name";
        } else {
            $teacher->teacher_image_name = null;
        }

        // Find the associated user record
        $user = User::where('reg_id', $id)->first();

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


// public function editStaff($id)
// {
//     try {
//         $teacher = Teacher::findOrFail($id);

//         return response()->json([
//             'message' => 'Teacher retrieved successfully!',
//             'teacher' => $teacher,
//         ], 200);
//     } catch (\Exception $e) {
//         return response()->json([
//             'message' => 'An error occurred while retrieving the teacher',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

public function storeStaff(Request $request)
{
    DB::beginTransaction(); // Start the transaction

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
            'employee_id.unique'=>'Employee Id should be unique.',
            'employee_id.required'=>'Employee Id is required.'
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
            'email' => 'required|string|max:50|unique:user_master,user_id', // Ensure email uniqueness
            'designation' => 'nullable|string|max:255',
            'academic_qual' => 'nullable|array',
            'academic_qual.*' => 'nullable|string|max:255',
            'professional_qual' => 'nullable|string|max:255',
            'special_sub' => 'nullable|string|max:255',
            'trained' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'aadhar_card_no' => 'nullable|string|max:20|unique:teacher,aadhar_card_no',
            'teacher_image_name' => 'nullable|string', // Base64 string or null
            'role' => 'required|string|max:255',
        ], $messages);

        // Concatenate academic qualifications into a string if they exist
        if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
            $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
        }

        $teacherid =DB::table('teacher')->select('teacher_id')->orderBy('teacher_id','DESC')->first();
        $incrementid = $teacherid->teacher_id + 1;

        // Check if teacher_image_name is null or empty and skip image-saving process if true
        if ($request->input('teacher_image_name') === 'null') {
            // Set image field as null if no image is provided
            $validatedData['teacher_image_name'] = null;
        } else {
            // Handle image saving logic when teacher_image_name is not null
            $imageData = $request->input('teacher_image_name');
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif

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

                // Ensure the directory exists
                $directory = dirname($filePath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Save the image to the file system
                if (file_put_contents($filePath, $imageData) === false) {
                    throw new \Exception('Failed to save image file');
                }

                // Store the filename in validated data
                $validatedData['teacher_image_name'] = $filename;
            } else {
                throw new \Exception('Invalid image data');
            }
        }

        // Create Teacher record
        $teacher = new Teacher();
        $teacher->fill($validatedData);
        $teacher->IsDelete = 'N';

        if (!$teacher->save()) {
            DB::rollBack(); // Rollback the transaction
            return response()->json([
                'message' => 'Failed to create teacher',
            ], 500);
        }

        // Create User record
        $user = UserMaster::create([
            'user_id' => $validatedData['email'],
            'name' => $validatedData['name'],
            'password' => Hash::make('arnolds'),
            'reg_id' => $teacher->teacher_id,
            'role_id' => $validatedData['role'],
            'IsDelete' => 'N',
        ]);

        if (!$user) {
            // Rollback by deleting the teacher record if user creation fails
            $teacher->delete();
            DB::rollBack(); // Rollback the transaction
            return response()->json([
                'message' => 'Failed to create user',
            ], 500);
        }

        // Send welcome email
        Mail::to($validatedData['email'])->send(new WelcomeEmail($user->email, 'arnolds'));

        // Call external API
        $response = Http::post('http://aceventura.in/demo/evolvuUserService/create_staff_userid', [
            'user_id' => $user->user_id,
            'role' => $validatedData['role'],
            'short_name' => 'SACS',
        ]);

        // Log the API response
        Log::info('External API response:', [
            'url' => 'http://aceventura.in/demo/evolvuUserService/create_staff_userid',
            'status' => $response->status(),
            'response_body' => $response->body(),
        ]);

        if ($response->successful()) {
            DB::commit(); // Commit the transaction
            $doc_type_folder = 'teacher_image';
            $fileContent = file_get_contents($filePath);           // Get the file content
            $base64File = base64_encode($fileContent); 
            upload_teacher_profile_image_into_folder($incrementid,$filename,$doc_type_folder,$base64File);
            return response()->json([
                'message' => 'Teacher and user created successfully!',
                'teacher' => $teacher,
                'user' => $user,
                'external_api_response' => $response->json(),
            ], 201);
        } else {
            DB::rollBack(); // Rollback the transaction
            return response()->json([
                'message' => 'Teacher and user created, but external API call failed',
                'external_api_error' => $response->body(),
            ], 500);
        }
    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack(); // Rollback the transaction on validation error
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
        DB::rollBack(); // Rollback the transaction
        return response()->json([
            'message' => 'An error occurred while creating the teacher',
            'error' => $e->getMessage()
        ], 500);
    }
}





// handle the existing image 
public function updateStaff(Request $request, $id)
{
    DB::beginTransaction(); // Start the transaction

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
            'employee_id.unique'=>'The Employee Id field should be unique.',
            'employee_id.required'=>'The Employee Id field is required.'
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
            'teacher_image_name' => 'nullable|string', // Base64 string
            // 'role' => 'required|string|max:255',
        ], $messages);

        if (isset($validatedData['academic_qual']) && is_array($validatedData['academic_qual'])) {
            $validatedData['academic_qual'] = implode(',', $validatedData['academic_qual']);
        }
         

    //     $staff = Teacher::findOrFail($id);
            
    //         // Get the existing image URL for comparison
    //         $existingImageUrl = Storage::url('teacher_images/' . $staff->teacher_image_name);
    //         // Handle base64 image
    // if ($request->has('teacher_image_name') && !empty($request->input('teacher_image_name'))) {
    //     $newImageData = $request->input('teacher_image_name');

    //     // Check if the new image data matches the existing image URL
    //     if ($existingImageUrl !== $newImageData) {
    //         if (preg_match('/^data:image\/(\w+);base64,/', $newImageData, $type)) {
    //             $newImageData = substr($newImageData, strpos($newImageData, ',') + 1);
    //             $type = strtolower($type[1]); // jpg, png, gif

    //             if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
    //                 throw new \Exception('Invalid image type');
    //             }
                
    //             $newImageData = base64_decode($newImageData);
    //             if ($newImageData === false) {
    //                 throw new \Exception('Base64 decode failed');
    //             }
                
    //             // Generate a filename for the new image
    //             $filename = 'teacher_' . time() . '.' . $type;
    //             $filePath = storage_path('app/public/teacher_images/' . $filename);
                
    //             // Ensure directory exists
    //             $directory = dirname($filePath);
    //             if (!is_dir($directory)) {
    //                 mkdir($directory, 0755, true);
    //             }

    //             // Save the new image to file
    //             if (file_put_contents($filePath, $newImageData) === false) {
    //                 throw new \Exception('Failed to save image file');
    //             }

    //             // Update the validated data with the new filename
    //             $validatedData['teacher_image_name'] = $filename;
    //         } else {
    //             throw new \Exception('Invalid image data');
    //         }
    //     } else {
    //         // If the image is the same, keep the existing filename
    //         $validatedData['teacher_image_name'] = $staff->teacher_image_name;
    //     }
    // }

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
                $type = strtolower($type[1]); // jpg, png, gif

                if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                    throw new \Exception('Invalid image type');
                }

                $newImageData = base64_decode($newImageData);
                if ($newImageData === false) {
                    throw new \Exception('Base64 decode failed');
                }

                // Generate a filename for the new image
                $filename = $id. '.' . $type;
                $filePath = storage_path('app/public/teacher_images/' . $filename);

                // Ensure directory exists
                $directory = dirname($filePath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Save the new image to file
                if (file_put_contents($filePath, $newImageData) === false) {
                    throw new \Exception('Failed to save image file');
                }

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

            


        // Find the teacher record by ID
        $teacher = Teacher::findOrFail($id);
        $teacher->fill($validatedData);

        if (!$teacher->save()) {
            DB::rollBack(); // Rollback the transaction
            return response()->json([
                'message' => 'Failed to update teacher',
            ], 500);
        }

        // Update user associated with the teacher
        $user = User::where('reg_id', $teacher->teacher_id)->first();
        if($user){
            $user->name = $validatedData['name'];
            $user->user_id = $validatedData['email'];
            $user->save();
        }

        // if ($user) {
        //     $user->name = $validatedData['name'];
        //     $user->email = strtolower(str_replace(' ', '.', trim($validatedData['name']))) . '@arnolds';

        //     if (!$user->save()) {
        //         DB::rollBack(); // Rollback the transaction
        //         return response()->json([
        //             'message' => 'Failed to update user',
        //         ], 500);
        //     }
        // }

        DB::commit(); // Commit the transaction
        
        $doc_type_folder = 'teacher_image';
        $fileContent = file_get_contents($filePath);           // Get the file content
        $base64File = base64_encode($fileContent); 
        upload_teacher_profile_image_into_folder($id,$filename,$doc_type_folder,$base64File);
        return response()->json([
            'message' => 'Teacher updated successfully!',
            'teacher' => $teacher,
            'user' => $user,
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack(); // Rollback the transaction on validation error
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        // Handle unexpected errors
        if (isset($teacher) && $teacher->exists) {
            // Keep teacher record but return an error
        }
        DB::rollBack(); // Rollback the transaction
        return response()->json([
            'message' => 'An error occurred while updating the teacher',
            'error' => $e->getMessage()
        ], 500);
    }
}








public function deleteStaff($id)
{
    try {
        $teacher = Teacher::findOrFail($id);
        $teacher->isDelete = 'Y';

        if ($teacher->save()) {
            $user = User::where('reg_id', $id)->first();
            if ($user) {
                $user->IsDelete = 'Y';
                $user->save();
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
            ], 400); // Return a 400 Bad Request with an error message
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


public function getStudentListBaseonClass(Request $request){

    $Studentz = Student::count();

    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 

     $Student = Student::where('academic_yr',$academicYr)->get();

     return response()->json(
        [
            'Studentz' =>$Studentz,
            'Student' =>$Student,
        ]
     );
}

//get the sections list with the student count 
public function getallSectionsWithStudentCount(Request $request)
{
    $payload = getTokenPayload($request);
    $academicYr = $payload->get('academic_year');
    $divisions = Division::with('getClass')
            ->withCount(['students' => function ($query) use ($academicYr) {
            $query->distinct()->where('academic_yr', $academicYr);
        }])
        ->where('academic_yr', $academicYr)
        ->get();
    return response()->json($divisions);
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
        ->where('student.IsDelete', 'N');

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
        //echo $student->parent_id."<br/>";
        if ($contactDetails===null) {
            $student->SetToReceiveSMS='';
        }else{
            
            $student->SetToReceiveSMS=$contactDetails->phone_no;

        }
       

        $userMaster = UserMaster::where('role_id','P')
                                    ->where('reg_id', $student->parent_id)->first();
        if ($userMaster===null) {
            $student->SetEmailIDAsUsername='';
        }else{
            
            $student->SetEmailIDAsUsername=$userMaster->user_id;

        }
        
    });

    

    return response()->json([
        'students' => $students,
    ]);
}

public function getStudentListBySectionData(Request $request){
    try{
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $sectionId = $request->query('section_id');
        if(!$sectionId){
            $student = DB::table('student')
                ->where('academic_yr',$academicYr)
                ->select('student.student_id','student.first_name','student.mid_name','student.last_name')
                ->get();
        }
        else{
            $student = DB::table('student')
                         ->where('academic_yr',$academicYr)
                         ->where('section_id',$sectionId)
                         ->select('student.student_id','student.first_name','student.mid_name','student.last_name')
                         ->get();
        }
         
        return response()->json([
            'status'=> 200,
            'message'=>'Student Information',
            'data' =>$student,
            'success'=>true
         ]);
    }
    catch (Exception $e) {
        \Log::error($e); // Log the exception
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

public function getStudentsList(Request $request){
    set_time_limit(300);
    $section_id = $request->section_id;
    $student_id = $request->student_id;
    $reg_no =$request->reg_no;

    $payload = getTokenPayload($request);  
    $academicYr = $payload->get('academic_year');

    $query = Student::query();

    $query->with(['parents', 'userMaster', 'getClass', 'getDivision']);

    if ($section_id && $reg_no) {
        $query->where('section_id', $section_id)
            ->where('reg_no', $reg_no)
            ->where('isDelete','N')->where('academic_yr',$academicYr)->where('parent_id','!=','0');
    }

    elseif ($student_id && $reg_no) {
        $query->where('student_id',$student_id)
            ->where('reg_no', $reg_no)
            ->where('isDelete','N')->where('academic_yr',$academicYr)->where('parent_id','!=','0');
    }

    elseif ($section_id && $student_id && $reg_no) {
        $query->where('section_id', $section_id)
            ->where('student_id', $student_id)
            ->where('reg_no', $reg_no)
            ->where('isDelete','N')->where('academic_yr',$academicYr)->where('parent_id','!=','0');
    }
    elseif ($section_id && $student_id) {
        $query->where('student_id',$student_id)
              ->where('section_id', $section_id)
              ->where('isDelete','N')->where('academic_yr',$academicYr)->where('parent_id','!=','0');
   }
   elseif ($section_id) {
       $query->where('section_id', $section_id)->where('isDelete','N')->where('academic_yr',$academicYr)->where('parent_id','!=','0');
   }
   elseif ($student_id) {
       $query->where('student_id', $student_id)->where('isDelete','N')->where('academic_yr',$academicYr)->where('parent_id','!=','0');
   }
   elseif ($reg_no) {
       $query->where('reg_no', $reg_no)->where('isDelete','N')->where('academic_yr',$academicYr)->where('parent_id','!=','0');
   }

    else {
        return response()->json([
            'status' => 'error',
            'message' => 'Please provide at least one search condition.',
        ], 400);
    }

    
    $students = $query->get();
    $globalVariables = App::make('global_variables');
    $parent_app_url = $globalVariables['parent_app_url'];
    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

    // Append image URLs for each student
    $students->each(function ($student) use($parent_app_url,$codeigniter_app_url) {
        // Check if the image_name is present and not empty
        $concatprojecturl = $codeigniter_app_url."".'uploads/student_image/';
        if (!empty($student->image_name)) {
            $student->image_name = $concatprojecturl."".$student->image_name;
        } else {
           
            $student->image_name = '';
        }

        $contactDetails = ContactDetails::find($student->parent_id);
        //echo $student->parent_id."<br/>";
        if ($contactDetails===null) {
            $student->SetToReceiveSMS='';
        }else{
            
            $student->SetToReceiveSMS=$contactDetails->phone_no;

        }
       

        $userMaster = UserMaster::where('role_id','P')
                                    ->where('reg_id', $student->parent_id)->first();
        if ($userMaster===null) {
            $student->SetEmailIDAsUsername='';
        }else{
            
            $student->SetEmailIDAsUsername=$userMaster->user_id;

        }
        
    });

    
    if ($students->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'No student found matching the search criteria.',
        ], 404);
    }

    
    return response()->json([
        'status' => 'success',
        'students' => $students,
    ]);
    

}



public function getStudentByGRN($reg_no)
{
    $student = Student::with(['parents.user', 'getClass', 'getDivision'])
        ->where('reg_no', $reg_no)
        ->first();

    if (!$student) {
        return response()->json(['error' => 'Student not found'], 404);
    }     
    return response()->json(['student' => [$student]]);
}


public function deleteStudent( Request $request , $studentId)
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
    $userMaster = UserMaster::where('role_id','S')
                            ->where('reg_id', $studentId)->first();
                            if ($userMaster) {
                                $userMaster->delete();
                            }

    // Check if the student has siblings
    $siblingsCount = Student::where('academic_yr',$academicYr)
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
            $userMasterParent = UserMaster::where('role_id','P')
                                           ->where('reg_id', $student->parent_id)->first();
            if ($userMasterParent) {
                $userMasterParent->IsDelete='Y';
                $userMasterParent->save();
            }

            // Hard delete parent information from the contact_details table
            ContactDetails::where('id', $student->parent_id)->delete();
        }
    }
    $parent1 = Parents::find($student->parent_id);

    // After deletion, check if the deleted information exists in the deleted_contact_details table
    $deletedContact = ContactDetails::where('id', $parent1)->first();
    if (!$deletedContact) {
        // Insert deleted contact details into the deleted_contact_details table
        DeletedContactDetails::create([
            'student_id' => $studentId,
            'parent_id' => $student->parent_id,
            'phone_no' => $student->parents->m_mobile, 
            'email_id' => $parent1->f_email, 
            'm_emailid' => $parent1->m_emailid 
        ]);
    }

    return response()->json(['message' => 'Student deleted successfully']);
    //while deleting  please cll the api for the evolvu database. while sibling is not present then  call the api to delete the paret 
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


     public function resetPasssword($user_id){  
            
        $user = UserMaster::find($user_id);
        if(!$user){
            return response()->json( [
                'Status' => 404 ,
                 'Error' => "User Id not found"
              ]);
        }
        $password = "arnolds";
        $user->password=$password;
        $user->save();
        
        return response()->json(
                      [
                        'Status' => 200 ,
                         'Message' => "Password is reset to arnolds . "
                      ]
                      );
     }
   


    // public function updateStudentAndParent(Request $request, $studentId)
    // {
    //     try {
    //         $payload = getTokenPayload($request);  
    //         $academicYr = $payload->get('academic_year');
    //         // Log the start of the request
    //         Log::info("Starting updateStudentAndParent for student ID: {$studentId}");
    //         //echo "Starting updateStudentAndParent for student ID: {$studentId}";
    //         DB::enableQueryLog();
    //         // Validate the incoming request for all fields
    //         $validatedData = $request->validate([
    //             // Student model fields
    //             'first_name' => 'nullable|string|max:100',
    //             'mid_name' => 'nullable|string|max:100',
    //             'last_name' => 'nullable|string|max:100',
    //             'house' => 'nullable|string|max:100',
    //             'student_name' => 'nullable|string|max:100',
    //             'dob' => 'nullable|date',
    //             'admission_date' => 'nullable|date',
    //             'stud_id_no' => 'nullable|string|max:25',
    //             'stu_aadhaar_no' => 'nullable|string|max:14',
    //             'gender' => 'nullable|string',
    //             'mother_tongue' => 'nullable|string|max:20',
    //             'birth_place' => 'nullable|string|max:50',
    //             'admission_class' => 'nullable|string|max:255',
    //             'city' => 'nullable|string|max:100',
    //             'state' => 'nullable|string|max:100',
    //             'roll_no' => 'nullable|max:11',
    //             'class_id' => 'nullable|integer',
    //             'section_id' => 'nullable|integer',
    //             'religion' => 'nullable|string|max:255',
    //             'caste' => 'nullable|string|max:100',
    //             'subcaste' => 'nullable|string|max:255',
    //             'vehicle_no' => 'nullable|string|max:13',
    //             'emergency_name' => 'nullable|string|max:100',
    //             'emergency_contact' => 'nullable|string|max:11',
    //             'emergency_add' => 'nullable|string|max:200',
    //             'height' => 'nullable|numeric',
    //             'weight' => 'nullable|numeric',
    //             'allergies' => 'nullable|string|max:200',
    //             'nationality' => 'nullable|string|max:100',
    //             'pincode' => 'nullable|max:11',
    //             'image_name' => 'nullable|string',
    //             'has_specs' => 'nullable|string|max:1',
    //             'udise_pen_no'=>'nullable|string',
    //             'reg_no'=>'nullable|string',
    //             'blood_group'=>'nullable|string',
    //             'permant_add'=>'nullable|string',
    //             'transport_mode'=>'nullable|string',
            
    //             // Parent model fields
    //             'father_name' => 'nullable|string|max:100',
    //             'father_occupation' => 'nullable|string|max:100',
    //             'f_office_add' => 'nullable|string|max:200',
    //             'f_office_tel' => 'nullable|string|max:11',
    //             'f_mobile' => 'nullable|string|max:10',
    //             'f_email' => 'nullable|string|max:50',
    //             'f_dob' => 'nullable|date',
    //             'f_blood_group' => 'nullable|string',
    //             'parent_adhar_no' => 'nullable|string|max:14',
    //             'mother_name' => 'nullable|string|max:100',
    //             'mother_occupation' => 'nullable|string|max:100',
    //             'm_office_add' => 'nullable|string|max:200',
    //             'm_office_tel' => 'nullable|string|max:11',
    //             'm_mobile' => 'nullable|string|max:10',
    //             'm_dob' => 'nullable|date',
    //             'm_emailid' => 'nullable|string|max:50',
    //             'm_adhar_no' => 'nullable|string|max:14',
    //             'm_blood_group' => 'nullable|string',
                
            
    //             // Preferences for SMS and email as username
    //             'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
    //             'SetEmailIDAsUsername' => 'nullable|string',
    //             // 'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
    //         ]);

    //         $validator = Validator::make($request->all(),[
        
    //             'stud_id_no' => 'nullable|string|max:255|unique:student,stud_id_no,'. $studentId . ',student_id,academic_yr,'. $academicYr,
    //             'stu_aadhaar_no' => 'nullable|string|max:255|unique:student,stu_aadhaar_no,'.$studentId . ',student_id,academic_yr,'.$academicYr,
    //             'udise_pen_no' => 'nullable|string|max:255|unique:student,udise_pen_no,'.$studentId . ',student_id,academic_yr,'.$academicYr,
    //             'reg_no' => 'nullable|string|max:255|unique:student,reg_no,'.$studentId . ',student_id,academic_yr,'.$academicYr,
    //             ]);
    //             if ($validator->fails()) {
    //                 return response()->json([
    //                     'status' => 422,
    //                     'errors' => $validator->errors(),
    //                 ], 422);
    //             }

    //         Log::info("Validation passed for student ID: {$studentId}");
    //         Log::info("Validation passed for student ID: {$request->SetEmailIDAsUsername}");
    //         //echo "Validation passed for student ID: {$studentId}";
    //         // Convert relevant fields to uppercase
    //         $fieldsToUpper = [
    //             'first_name', 'mid_name', 'last_name', 'house', 'emergency_name', 
    //             'emergency_contact', 'nationality', 'city', 'state', 'birth_place', 
    //             'mother_tongue', 'father_name', 'mother_name', 'vehicle_no', 'caste'
    //         ];

    //         foreach ($fieldsToUpper as $field) {
    //             if (isset($validatedData[$field])) {
    //                 $validatedData[$field] = strtoupper(trim($validatedData[$field]));
    //             }
    //         }
    //         //echo "msg1";
    //         // Additional fields for parent model that need to be converted to uppercase
    //         $parentFieldsToUpper = [
    //             'father_name', 'mother_name', 'f_blood_group', 'm_blood_group', 'student_blood_group'
    //         ];
    //         //echo "msg2";
    //         foreach ($parentFieldsToUpper as $field) {
    //             if (isset($validatedData[$field])) {
    //                 $validatedData[$field] = strtoupper(trim($validatedData[$field]));
    //             }
    //         }
    //         //echo "msg3";
    //         // Retrieve the token payload
    //         $payload = getTokenPayload($request);
    //         $academicYr = $payload->get('academic_year');

    //         Log::info("Academic year: {$academicYr} for student ID: {$studentId}");
    //         //echo "msg4";
    //         // Find the student by ID
    //         $student = Student::find($studentId);
    //         if (!$student) {
    //             Log::error("Student not found: ID {$studentId}");
    //             return response()->json(['error' => 'Student not found'], 404);
    //         }
    //         //echo "msg5";
    //         // Check if specified fields have changed
    //         $fieldsToCheck = ['first_name', 'mid_name', 'last_name', 'class_id', 'section_id', 'roll_no'];
    //         $isModified = false;

    //         foreach ($fieldsToCheck as $field) {
    //             if (isset($validatedData[$field]) && $student->$field != $validatedData[$field]) {
    //                 $isModified = true;
    //                 break;
    //             }
    //         }
    //         //echo "msg6";
    //         // If any of the fields are modified, set 'is_modify' to 'Y'
    //         if ($isModified) {
    //             $validatedData['is_modify'] = 'Y';
    //         }

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
    //         //echo "msg7";
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

    //         $existingImageUrl = $student->image_name;

    //         if ($request->has('image_name')) {
    // $newImageData = $request->input('image_name');

    

    // // Check if the new image data is null
    // if ($newImageData === null || $newImageData === 'null' || $newImageData === 'default.png') {
    //     // If the new image data is null, keep the existing filename
    //     $validatedData['image_name'] = $student->image_name;
    // } elseif (!empty($newImageData)) {
    //     // Check if the new image data matches the existing image URL
    //     if ($existingImageUrl !== $newImageData) {
    //         if (preg_match('/^data:image\/(\w+);base64,/', $newImageData, $type)) {
    //             $newImageData = substr($newImageData, strpos($newImageData, ',') + 1);
    //             $type = strtolower($type[1]); // jpg, png, gif

    //             if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
    //                 throw new \Exception('Invalid image type');
    //             }

    //             $newImageData = base64_decode($newImageData);
    //             if ($newImageData === false) {
    //                 throw new \Exception('Base64 decode failed');
    //             }

    //             // Generate a filename for the new image
    //             $filename = 'student_' . time() . '.' . $type;
    //             $filePath = storage_path('app/public/student_images/' . $filename);

    //             // Ensure directory exists
    //             $directory = dirname($filePath);
    //             if (!is_dir($directory)) {
    //                 mkdir($directory, 0755, true);
    //             }

    //             // Save the new image to file
    //             if (file_put_contents($filePath, $newImageData) === false) {
    //                 throw new \Exception('Failed to save image file');
    //             }

    //             // Update the validated data with the new filename
    //             $validatedData['image_name'] = $filename;
    //         } else {
    //             throw new \Exception('Invalid image data');
    //         }
    //     } else {
    //         // If the image is the same, keep the existing filename
    //         $validatedData['image_name'] = $student->image_name;
    //     }
    // }
    //         }

    //         // if ($request->has('image_name')) {
    //         //     $imageData=$request->input('image_name');
    //         //     if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
    //         //     $imageData = substr($imageData, strpos($imageData, ',') + 1);
    //         //     $type = strtolower($type[1]); // jpg, png, gif

    //         //     // Validate image type
    //         //     if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
    //         //         throw new \Exception('Invalid image type');
    //         //     }

    //         //     // Base64 decode the image
    //         //     $imageData = base64_decode($imageData);
    //         //     if ($imageData === false) {
    //         //         throw new \Exception('Base64 decode failed');
    //         //     }

    //         //     // Define the filename and path to store the image
    //         //     $filename = 'student_' . time() . '.' . $type;
    //         //     $filePath = storage_path('app/public/student_images/' . $filename);

    //         //     // Ensure the directory exists
    //         //     $directory = dirname($filePath);
    //         //     if (!is_dir($directory)) {
    //         //         mkdir($directory, 0755, true);
    //         //     }

    //         //     // Save the image to the file system
    //         //     if (file_put_contents($filePath, $imageData) === false) {
    //         //         throw new \Exception('Failed to save image file');
    //         //     }

    //         //     // Store the filename in validated data
    //         //     $validatedData['image_name'] = $filename;
    //         // } else {
    //         //     throw new \Exception('Invalid image data');
    //         // }
    //         // }
    //         //echo "msg8";
    //         // Include academic year in the update data
    //         $validatedData['academic_yr'] = $academicYr;
    //         $user = $this->authenticateUser();
    //         $customClaims = JWTAuth::getPayload()->get('academic_year');
    //         // Update student information
    //         $student->update($validatedData);
    //         $student->updated_by = $user->reg_id;
    //         $student->save();
    //         //echo $student->toSql();
    //         Log::info("Student information updated for student ID: {$studentId}");
    //         //echo "msg9";
    //         // Handle parent details if provided
    //         $parent = Parents::find($student->parent_id);
    //         //echo "msg10";
    //         if ($parent) {
    //             $parent->update($request->only([
    //                 'father_name', 'father_occupation', 'f_office_add', 'f_office_tel',
    //                 'f_mobile', 'f_email','f_blood_group', 'parent_adhar_no', 'mother_name',
    //                 'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile',
    //                 'm_emailid', 'm_adhar_no','m_dob','f_dob','m_blood_group'
    //             ]));
    //             //echo "msg11";
    //             // Determine the phone number based on the 'SetToReceiveSMS' input
    //             $phoneNo = null;
    //             $setToReceiveSMS = $request->input('SetToReceiveSMS');
    //             if ($setToReceiveSMS == 'Father') {
    //                 $phoneNo = $parent->f_mobile;
    //             } elseif ($setToReceiveSMS == 'Mother') {
    //                 $phoneNo = $parent->m_mobile;
    //             }
    //             elseif ($setToReceiveSMS) {
    //                 $phoneNo = $setToReceiveSMS;
    //             }
    //             //echo "msg12";
    //             // Check if a record already exists with parent_id as the id
    //             $contactDetails = ContactDetails::find($student->parent_id);
    //             $phoneNo1 = $parent->f_mobile;
    //             if ($contactDetails) {
    //                 // If the record exists, update the contact details
    //                 $contactDetails->update([
    //                     'phone_no' => $phoneNo,
    //                     'alternate_phone_no' => $parent->f_mobile, // Assuming alternate phone is Father's mobile number
    //                     'email_id' => $parent->f_email, // Father's email
    //                     'm_emailid' => $parent->m_emailid, // Mother's email
    //                     'sms_consent' => 'N' // Store consent for SMS
    //                 ]);
    //                 //echo "msg13";
    //             } else {
    //                 // If the record doesn't exist, create a new one with parent_id as the id
    //                 DB::insert('INSERT INTO contact_details (id, phone_no, email_id, m_emailid, sms_consent) VALUES (?, ?, ?, ?, ?)', [
    //                     $student->parent_id,                
    //                     $parent->f_mobile,
    //                     $parent->f_email,
    //                     $parent->m_emailid,
    //                     'N' // sms_consent
    //                 ]);
    //                 //echo "msg14";
    //             }

    //             // Update email ID as username preference
    //             $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id','P')->first();
    //             Log::info("Student information updated for student ID: {$user}");

    //             // $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();

    //             if ($user) {
    //                 // Conditional logic for setting email/phone based on SetEmailIDAsUsername
    //                 $emailOrPhoneMapping = [
    //                     'Father'     => $parent->f_email,     // Father's email
    //                     'Mother'     => $parent->m_emailid,   // Mother's email
    //                     'FatherMob'  => $parent->f_mobile,    // Father's mobile
    //                     'MotherMob'  => $parent->m_mobile,    // Mother's mobile
    //                 ];
                    
    //                 // Check if the provided value exists in the mapping, otherwise use the default
    //                 $user->user_id = $emailOrPhoneMapping[$request->SetEmailIDAsUsername] ?? $request->SetEmailIDAsUsername;

    //                 Log::info($user->user_id);

    //                if ($user->update(['user_id' => $user->user_id])) {
    //                     Log::info("User record updated successfully for student ID: {$student->student_id}");
    //                 } else {
    //                     Log::error("Failed to update user record for student ID: {$student->student_id}");
    //                 }
    //             }
                

    //             // $apiData = [
    //             //     'user_id' => '',
    //             //     'short_name' => 'SACS',
    //             // ];

    //             // $oldEmailPreference = $user->user_id; // Store old email preference for comparison

    //             // // Check if the email preference changed
    //             // if ($oldEmailPreference != $apiData['user_id']) {
    //             //     // Call the external API only if the email preference has changed
    //             //     $response = Http::post('http://aceventura.in/demo/evolvuUserService/user_create_new', $apiData);
    //             //     if ($response->successful()) {
    //             //         Log::info("API call successful for student ID: {$studentId}");
    //             //     } else {
    //             //         Log::error("API call failed for student ID: {$studentId}");
    //             //     }
    //             // } else {
    //             //     Log::info("Email preference unchanged for student ID: {$studentId}");
    //             // }
    //         }

    //         return response()->json(['success' => 'Student and parent information updated successfully']);
    //     } catch (Exception $e) {
    //         Log::error("Exception occurred for student ID: {$studentId} - " . $e->getMessage());
    //         return response()->json(['error' => 'An error occurred while updating information'], 500);
    //     }
    

    //     // return response()->json($request->all());

    // }



    public function updateStudentAndParent(Request $request, $studentId)
    {
        try {
            $payload = getTokenPayload($request);  
            $academicYr = $payload->get('academic_year');
            // Log the start of the request
            Log::info("Starting updateStudentAndParent for student ID: {$studentId}");
            //echo "Starting updateStudentAndParent for student ID: {$studentId}";
            DB::enableQueryLog();
            //     $requestData = $request->all();
            // Log::info('Request Data: ' . json_encode($requestData, JSON_PRETTY_PRINT));
            //     // Convert '0000-00-00' to null for f_dob and m_dob
            //     if ($requestData['f_dob'] === '0000-00-00') {
            //         $requestData['f_dob'] = null;
            //     }
            //     if ($requestData['m_dob'] === '0000-00-00') {
            //         $requestData['m_dob'] = null;
            //     }
            //   Log::info("F dob: " . ($requestData['f_dob'] ?? 'Not Set'));
            //   Log::info("M dob: " . ($requestData['m_dob'] ?? 'Not Set'));
            //   Log::info('Request Data: ' . json_encode($requestData, JSON_PRETTY_PRINT));
            // Validate the incoming request for all fields
            $validatedData = $request->validate([
                // Student model fields
                'first_name' => 'nullable|string|max:100',
                'mid_name' => 'nullable|string|max:100',
                'last_name' => 'nullable|string|max:100',
                'house' => 'nullable|string|max:100',
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
                'udise_pen_no'=>'nullable|string',
                'reg_no'=>'nullable|string',
                'blood_group'=>'nullable|string',
                'permant_add'=>'nullable|string',
                'transport_mode'=>'nullable|string',
            
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
                'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
                'SetEmailIDAsUsername' => 'nullable|string',
                // 'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
            ]);

            $validator = Validator::make($request->all(),[
        
                'stud_id_no' => 'nullable|string|max:255|unique:student,stud_id_no,'. $studentId . ',student_id,academic_yr,'. $academicYr,
                'stu_aadhaar_no' => 'nullable|string|max:255|unique:student,stu_aadhaar_no,'.$studentId . ',student_id,academic_yr,'.$academicYr,
                'udise_pen_no' => 'nullable|string|max:255|unique:student,udise_pen_no,'.$studentId . ',student_id,academic_yr,'.$academicYr,
                'reg_no' => 'nullable|string|max:255|unique:student,reg_no,'.$studentId . ',student_id,academic_yr,'.$academicYr,
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'status' => 422,
                        'errors' => $validator->errors(),
                    ], 422);
                }

            Log::info("Validation passed for student ID: {$studentId}");
            Log::info("Validation passed for student ID: {$request->SetEmailIDAsUsername}");
            //echo "Validation passed for student ID: {$studentId}";
            // Convert relevant fields to uppercase
            $fieldsToUpper = [
                'first_name', 'mid_name', 'last_name', 'house', 'emergency_name', 
                'emergency_contact', 'nationality', 'city', 'state', 'birth_place', 
                'mother_tongue', 'father_name', 'mother_name', 'vehicle_no', 'caste'
            ];

            foreach ($fieldsToUpper as $field) {
                if (isset($validatedData[$field])) {
                    $validatedData[$field] = strtoupper(trim($validatedData[$field]));
                }
            }
            //echo "msg1";
            // Additional fields for parent model that need to be converted to uppercase
            $parentFieldsToUpper = [
                'father_name', 'mother_name', 'f_blood_group', 'm_blood_group', 'student_blood_group'
            ];
            //echo "msg2";
            foreach ($parentFieldsToUpper as $field) {
                if (isset($validatedData[$field])) {
                    $validatedData[$field] = strtoupper(trim($validatedData[$field]));
                }
            }
            //echo "msg3";
            // Retrieve the token payload
            $payload = getTokenPayload($request);
            $academicYr = $payload->get('academic_year');

            Log::info("Academic year: {$academicYr} for student ID: {$studentId}");
            //echo "msg4";
            // Find the student by ID
            $student = Student::find($studentId);
            if (!$student) {
                Log::error("Student not found: ID {$studentId}");
                return response()->json(['error' => 'Student not found'], 404);
            }
            //echo "msg5";
            // Check if specified fields have changed
            $fieldsToCheck = ['first_name', 'mid_name', 'last_name', 'class_id', 'section_id', 'roll_no'];
            $isModified = false;

            foreach ($fieldsToCheck as $field) {
                if (isset($validatedData[$field]) && $student->$field != $validatedData[$field]) {
                    $isModified = true;
                    break;
                }
            }
            //echo "msg6";
            // If any of the fields are modified, set 'is_modify' to 'Y'
            if ($isModified) {
                $validatedData['is_modify'] = 'Y';
            }

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
            //echo "msg7";
            if ($request->has('image_name')) {
                $newImageData = $request->input('image_name');
            
                if (!empty($newImageData)) {
                    if (preg_match('/^data:image\/(\w+);base64,/', $newImageData, $type)) {
                        $newImageData = substr($newImageData, strpos($newImageData, ',') + 1);
                        $type = strtolower($type[1]); // jpg, png, gif
            
                        if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                            throw new \Exception('Invalid image type');
                        }
            
                        // Decode the image
                        $newImageData = base64_decode($newImageData);
                        if ($newImageData === false) {
                            throw new \Exception('Base64 decode failed');
                        }
            
                        // Generate a unique filename
                        $imageName = $studentId . '.' . $type;
                        $imagePath = public_path('storage/uploads/student_image/' . $imageName);
            
                        // Save the image file
                        file_put_contents($imagePath, $newImageData);
                        $validatedData['image_name'] = $imageName;
            
                        Log::info("Image uploaded for student ID: {$studentId}");
                    } else {
                        throw new \Exception('Invalid image data format');
                    }
                }
            }
            */

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
                $type = strtolower($type[1]); // jpg, png, gif

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

            // if ($request->has('image_name')) {
            //     $imageData=$request->input('image_name');
            //     if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            //     $imageData = substr($imageData, strpos($imageData, ',') + 1);
            //     $type = strtolower($type[1]); // jpg, png, gif

            //     // Validate image type
            //     if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
            //         throw new \Exception('Invalid image type');
            //     }

            //     // Base64 decode the image
            //     $imageData = base64_decode($imageData);
            //     if ($imageData === false) {
            //         throw new \Exception('Base64 decode failed');
            //     }

            //     // Define the filename and path to store the image
            //     $filename = 'student_' . time() . '.' . $type;
            //     $filePath = storage_path('app/public/student_images/' . $filename);

            //     // Ensure the directory exists
            //     $directory = dirname($filePath);
            //     if (!is_dir($directory)) {
            //         mkdir($directory, 0755, true);
            //     }

            //     // Save the image to the file system
            //     if (file_put_contents($filePath, $imageData) === false) {
            //         throw new \Exception('Failed to save image file');
            //     }

            //     // Store the filename in validated data
            //     $validatedData['image_name'] = $filename;
            // } else {
            //     throw new \Exception('Invalid image data');
            // }
            // }
            //echo "msg8";
            // Include academic year in the update data
            $validatedData['academic_yr'] = $academicYr;
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            // Update student information
            $student->update($validatedData);
            $student->updated_by = $user->reg_id;
            $student->save();
            //echo $student->toSql();
            Log::info("Student information updated for student ID: {$studentId}");
            //echo "msg9";
            // Handle parent details if provided
            $parent = Parents::find($student->parent_id);
            //echo "msg10";
            if ($parent) {
                $parent->update($request->only([
                    'father_name', 'father_occupation', 'f_office_add', 'f_office_tel',
                    'f_mobile', 'f_email','f_blood_group', 'parent_adhar_no', 'mother_name',
                    'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile',
                    'm_emailid', 'm_adhar_no','m_dob','f_dob','m_blood_group'
                ]));
                //echo "msg11";
                // Determine the phone number based on the 'SetToReceiveSMS' input
                $phoneNo = null;
                $setToReceiveSMS = $request->input('SetToReceiveSMS');
                if ($setToReceiveSMS == 'Father') {
                    $phoneNo = $parent->f_mobile;
                } elseif ($setToReceiveSMS == 'Mother') {
                    $phoneNo = $parent->m_mobile;
                }
                elseif ($setToReceiveSMS) {
                    $phoneNo = $setToReceiveSMS;
                }
                //echo "msg12";
                // Check if a record already exists with parent_id as the id
                $contactDetails = ContactDetails::find($student->parent_id);
                $phoneNo1 = $parent->f_mobile;
                if ($contactDetails) {
                    // If the record exists, update the contact details
                    $contactDetails->update([
                        'phone_no' => $phoneNo,
                        'alternate_phone_no' => $parent->f_mobile, // Assuming alternate phone is Father's mobile number
                        'email_id' => $parent->f_email, // Father's email
                        'm_emailid' => $parent->m_emailid, // Mother's email
                        'sms_consent' => 'N' // Store consent for SMS
                    ]);
                    //echo "msg13";
                } else {
                    // If the record doesn't exist, create a new one with parent_id as the id
                    DB::insert('INSERT INTO contact_details (id, phone_no, email_id, m_emailid, sms_consent) VALUES (?, ?, ?, ?, ?)', [
                        $student->parent_id,                
                        $parent->f_mobile,
                        $parent->f_email,
                        $parent->m_emailid,
                        'N' // sms_consent
                    ]);
                    //echo "msg14";
                }

                // Update email ID as username preference
                $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id','P')->first();
                Log::info("Student information updated for student ID: {$user}");

                // $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();

                    if ($user) {
                        // Conditional logic for setting email/phone based on SetEmailIDAsUsername
                        $emailOrPhoneMapping = [
                            'Father'     => $parent->f_email,     // Father's email
                            'Mother'     => $parent->m_emailid,   // Mother's email
                            'FatherMob'  => $parent->f_mobile,    // Father's mobile
                            'MotherMob'  => $parent->m_mobile,    // Mother's mobile
                        ];
                        
                        // Check if the provided value exists in the mapping, otherwise use the default
                        $user->user_id = $emailOrPhoneMapping[$request->SetEmailIDAsUsername] ?? $request->SetEmailIDAsUsername;

                        Log::info($user->user_id);

                       if ($user->update(['user_id' => $user->user_id])) {
                            Log::info("User record updated successfully for student ID: {$student->student_id}");
                        } else {
                            Log::error("Failed to update user record for student ID: {$student->student_id}");
                        }
                    }
                

                // $apiData = [
                //     'user_id' => '',
                //     'short_name' => 'SACS',
                // ];

                // $oldEmailPreference = $user->user_id; // Store old email preference for comparison

                // // Check if the email preference changed
                // if ($oldEmailPreference != $apiData['user_id']) {
                //     // Call the external API only if the email preference has changed
                //     $response = Http::post('http://aceventura.in/demo/evolvuUserService/user_create_new', $apiData);
                //     if ($response->successful()) {
                //         Log::info("API call successful for student ID: {$studentId}");
                //     } else {
                //         Log::error("API call failed for student ID: {$studentId}");
                //     }
                // } else {
                //     Log::info("Email preference unchanged for student ID: {$studentId}");
                // }
            }
            $fileContent = file_get_contents($filePath);           // Get the file content
            $base64File = base64_encode($fileContent); 
            upload_student_profile_image_into_folder($studentId,$filename,$doc_type_folder,$base64File);
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
        $savedUserId = $parentUser ? $parentUser->user_id : "";

        // Check if the provided userId matches the savedUserId
        if ($userId == $savedUserId) {
            // If they are the same, return false
            Log::info("User ID matches the saved user ID: {$userId}");
            return response()->json(['exists' => false], 200);
        } else {
            // If they are different, check if the userId exists in the UserMaster table
            $userExists = UserMaster::where('user_id', $userId)
                ->where('role_id', 'P')
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
        Log::error("Error checking user ID: " . $e->getMessage());
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
        ->with(['getClass' => function($query) {
            $query->select('name', 'class_id');
        }])
        ->where('academic_yr', $academicYr)
        ->distinct()
        ->orderBy('class_id') 
        ->orderBy('section_id', 'asc')
        ->get();

    return response()->json($divisions);
}



//get all the subject allotment data base on the selected class and section 
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

    $subjectAllotmentList = $query->
                             orderBy('class_id', 'DESC') // multiple section_id, sm_id
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

//Delete Subject Allotment base on the selectd Subject_id 
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
    ]);}
 
//Classs list
public function getClassList(Request $request)
{
    $payload = getTokenPayload($request);  
    $academicYr = $payload->get('academic_year');
    $classes =Classes::where('academic_yr', $academicYr)
                     ->orderBy('class_id')  //order 
                     ->get();
    return response()->json($classes);
}
  
//get  the divisions and the subjects base on the selectd class_id 
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

    // Fetch Division Names
    $divisionNames = Division::where('academic_yr', $academicYr)
        ->where('class_id', $classId)
        ->select('section_id', 'name')
        ->orderBy('name', 'asc')
        ->distinct()
        ->get();
    
    // Fetch Subjects Based on Class Type
    $subjects = ($className == 11 || $className == 12)
        ? $this->getAllSubjectsNotHsc()
        : $this->getAllSubjectsOfHsc();
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
    return SubjectMaster::whereIn('subject_type', ['Compulsory', 'Optional', 'Co-Scholastic_hsc', 'Social','Scholastic', 'Co-Scholastic'])->get();
}

private function getAllSubjectsNotHsc()
{
    return SubjectMaster::whereIn('subject_type', ['Scholastic', 'Co-Scholastic', 'Social'])->get();
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
                    ->update(['sm_id' => null]);

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
    $subjects = $request->input('subjects'); // Expecting an array of subjects with details
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
                // If teacher_id is null, delete the record 
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
                        'sm_id' => $sm_id // Ensure sm_id is correctly passed
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

        $recordCount = $recordsToDelete->count();

        if ($recordCount > 1) {
            // Delete all but one, and set teacher_id to null on the remaining one
            $recordsToDelete->slice(1)->each->delete();
            $recordsToDelete->first()->update(['teacher_id' => null]);
        } elseif ($recordCount == 1) {
            // Just set teacher_id to null
            $recordsToDelete->first()->update(['teacher_id' => null]);
        }
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
    $academic_year = '2023-2024'; // Set your academic year as needed

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
        $subject_ids_to_remove = []; // Define logic for subjects to remove if needed
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
public function getTeacherNames(Request $request){      
    $teacherList = UserMaster::Where('role_id','T')->where('IsDelete','N')->get();
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
        ->orderBy('section_id','asc')
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

    // Determine subjects based on class name
    $subjects = ($className == 11 || $className == 12)
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
    ->select('sm_id', DB::raw('MAX(subject_id) as subject_id')) // Aggregate subject_id if needed
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

public function getPresignSubjectByTeacher(Request $request,$classID, $sectionId,$teacherID)
{
    $payload = getTokenPayload($request);
    if (!$payload) {
        return response()->json(['error' => 'Invalid or missing token'], 401);
    }
    $academicYr = $payload->get('academic_year'); 
    
    $subjects = SubjectAllotment::with('getSubject')
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
        ->keyBy('sm_id'); // Use sm_id as the key for easy comparison

    $inputSmIds = collect($subjects)->pluck('sm_id')->toArray();
    $existingSmIds = $existingAllotments->pluck('sm_id')->toArray();

    // Iterate through the input subjects and update or create records
    foreach ($subjects as $subjectData) {
        // if (isset($subjectData['subject_id'])) {
        //     // Update existing record
        //     SubjectAllotment::updateOrCreate(
        //         [
        //             'subject_id' => $subjectData['subject_id'],
        //             'class_id' => $class_id,
        //             'section_id' => $section_id,
        //             'academic_yr' => $academicYr,
        //         ],
        //         [
        //             'sm_id' => $subjectData['sm_id'],
        //             'teacher_id' => $subjectData['teacher_id'],
        //         ]
        //     );
        // } else {
            // Create new record
            SubjectAllotment::updateOrCreate(
                [
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'sm_id' => $subjectData['sm_id'],
                    'academic_yr' => $academicYr,
                    'teacher_id' => $subjectData['teacher_id']
                ]
            );
        // }
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
    $subjects = SubjectForReportCard::orderBy('sequence','asc')->get();
    return response()->json(
        ['subjects'=>$subjects]
    );
}

public function checkSubjectNameForReportCard(Request $request)
{
    $validatedData = $request->validate([
        'sequence' => 'required|string|max:30',
    ]);

    $sequence = $validatedData['sequence'];
    // return response()->json($sequence);
    $exists = SubjectForReportCard::where(DB::raw('LOWER(sequence)'), strtolower($sequence))->exists();
    $exists = SubjectForReportCard::where('sequence', $sequence)->exists();
    return response()->json(['exists' => $exists]);
}


public function storeSubjectForReportCard(Request $request)
{
    $messages = [
        'name.required' => 'The name field is required.',
        'sequence.required' => 'The sequence field is required.',
        'name.unique'=> 'The name should be unique.',
        'sequence.unique'=>'The sequence should be unique',
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
        'name.unique'=>'The name has already been taken.'
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
        ], 400); // Return a 400 Bad Request with an error message
    }
    
    $subject = SubjectForReportCard::find($sub_rc_master_id);

    if (!$subject) {
        return response()->json([
            'status' => 404,
            'message' => 'Subject not found',
        ]);
    }

    //Delete condition pending 
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
 
public function getSubjectAllotmentForReportCard(Request $request,$class_id)
{  
     $payload = getTokenPayload($request);    
    $academicYr = $payload->get('academic_year');

    $subjectAllotments = SubjectAllotmentForReportCard::where('academic_yr',$academicYr)
                                ->where('class_id', $class_id)
                                ->with('getSubjectsForReportCard','getClases')
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
            'success'=>false
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
    $subjectAllotments = SubjectAllotmentForReportCard::where('academic_yr',$academicYr)
                                    ->where('class_id', $class_id)
                                    ->where('subject_type', $subject_type)
                                    ->with('getSubjectsForReportCard') // Include subject details
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
    $academicYr = $payload->get('academic_year'); // Get academic year from token payload

    // Validate the request parameters
    $request->validate([
        'subject_type'     => 'required|string',
        'subject_ids'      => 'array',
        'subject_ids.*'    => 'integer',
    ]);

    // Log the incoming request
    Log::info('Received request to create/update subject allotment', [
        'class_id' => $class_id,
        'subject_type' => $request->input('subject_type'),
        'subject_ids' => $request->input('subject_ids'),
        'academic_yr' => $academicYr, // Log the academic year for reference
    ]);

    // Fetch existing subject allotments
    $existingAllotments = SubjectAllotmentForReportCard::where('class_id', $class_id)
                                    ->where('subject_type', $request->input('subject_type'))
                                    ->where('academic_yr', $academicYr) // Ensure academic year is considered
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
            'class_id'         => $class_id,
            'sub_rc_master_id' => $subjectId,
            'subject_type'     => $request->input('subject_type'),
            'academic_yr'      => $academicYr, // Set academic year
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
                        ->where('academic_yr', $academicYr) // Ensure academic year is considered
                        ->where('sub_rc_master_id', $subjectId)
                        ->first();

        Log::info('Fetched allotment for update', [
            'allotment' => $allotment
        ]);

        if ($allotment) {
            $allotment->sub_rc_master_id = $subjectId;
            $allotment->academic_yr = $academicYr; // Update academic year
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
                        ->where('academic_yr', $academicYr) // Ensure academic year is considered
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

public function getNewStudentListbysectionforregister(Request $request , $section_id){   
    $user = $this->authenticateUser();
    $customClaims = JWTAuth::getPayload()->get('academic_year');            
    $studentList = Student::with('getClass', 'getDivision')
                            ->where('section_id',$section_id)
                            ->where('parent_id','0')
                            ->where('IsDelete','N')
                            ->where('academic_yr',$customClaims)
                            ->distinct()
                            ->get();

    return response()->json($studentList);                        
}

public function getAllNewStudentListForRegister(Request $request){  
    $user = $this->authenticateUser();
    $customClaims = JWTAuth::getPayload()->get('academic_year');               
    $studentList = Student::with('getClass', 'getDivision')
                            ->where('parent_id','0')
                            ->where('IsDelete','N')
                            ->where('academic_yr',$customClaims)
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
    ->where('student.academic_yr', $customClaims)  // Specify the table name here
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

public function downloadCsvRejected($id){
    $filePath = "https://sms.evolvu.in/storage/app/public/csv_rejected/".$id;
    $file = fopen($filePath, 'r');
    
    if ($file) {
        return Response::stream(function () use ($file) {
            // Output each line of the remote CSV file
            while (!feof($file)) {
                echo fgets($file);
            }
            
            fclose($file); // Close the file after reading
        }, 200, [
            'Content-Type' => 'text/csv', // Set the content type as CSV
            'Content-Disposition' => 'attachment; filename="rejectedrows.csv"', // Set the file name for download
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
            //echo $student->parent_id."<br/>";
            if ($contactDetails===null) {
                $student->SetToReceiveSMS='';
            }else{
                
                $student->SetToReceiveSMS=$contactDetails->phone_no;
    
            }
           
    
            $userMaster = UserMaster::where('role_id','P')
                                        ->where('reg_id', $student->parent_id)->first();
                                        
            if ($userMaster===null) {
                $student->SetEmailIDAsUsername='';
            }else{
                
                $student->SetEmailIDAsUsername=$userMaster->user_id;
    
            }
            
        });

    return response()->json(['parent' => $parent, 'success' => true]);
}

//Changed on 08-10-24 Lija M
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
            'permant_add' => 'nullable|string|max:200',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|max:6',
            'reg_no' => 'nullable|max:10',
            'house' => 'nullable|string|max:1',
            'stu_aadhaar_no' => 'nullable|string|max:14',
            'category' => 'nullable|string|max:8',
            'image_name' => 'nullable|string',
            'udise_pen_no' => 'nullable|string|max:11',
            
           
                   
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
            'SetToReceiveSMS' => 'nullable|string|in:Father,Mother',
            'SetEmailIDAsUsername' => 'nullable|string',
            // 'SetEmailIDAsUsername' => 'nullable|string|in:Father,Mother,FatherMob,MotherMob',
        ]);
        $payload = getTokenPayload($request);  
        $academicYr = $payload->get('academic_year');
        $validator = Validator::make($request->all(),[
        
        'stud_id_no' => 'nullable|string|max:255|unique:student,stud_id_no,'. $studentId . ',student_id,academic_yr,'. $academicYr,
        'stu_aadhaar_no' => 'nullable|string|max:255|unique:student,stu_aadhaar_no,'.$studentId . ',student_id,academic_yr,'.$academicYr,
        'udise_pen_no' => 'nullable|string|max:255|unique:student,udise_pen_no,'.$studentId . ',student_id,academic_yr,'.$academicYr,
        'reg_no' => 'nullable|string|max:255|unique:student,reg_no,'.$studentId . ',student_id,academic_yr,'.$academicYr,
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
            'first_name', 'mid_name', 'last_name', 'house', 'emergency_name', 
            'emergency_contact', 'nationality', 'city', 'state', 'birth_place', 
            'mother_tongue', 'father_name', 'mother_name', 'vehicle_no', 'caste', 'blood_group'
        ];

        foreach ($fieldsToUpper as $field) {
            if (isset($validatedData[$field])) {
                $validatedData[$field] = strtoupper(trim($validatedData[$field]));
            }
        }
 
        // Additional fields for parent model that need to be converted to uppercase
        $parentFieldsToUpper = [
            'father_name', 'mother_name', 'f_blood_group', 'm_blood_group'
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
            //return response()->json(['error' => 'Invalid or missing token'], 401);
        }else{
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
            Log::info("Message 1.5 Inside if ");
            $validatedData['isModify'] = 'Y';
        }else{
            Log::info("Message 1.5 Inside else ");
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
                        $type = strtolower($type[1]); // jpg, png, gif
        
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
        //Log::info("Message 2 {$validatedData['isModify']} ");
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
        if ($request->has('image_name')) {
            $newImageData = $request->input('image_name');
        
            if (!empty($newImageData)) {
                if (preg_match('/^data:image\/(\w+);base64,/', $newImageData, $type)) {
                    $newImageData = substr($newImageData, strpos($newImageData, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif
        
                    if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                        throw new \Exception('Invalid image type');
                    }
        
                    // Decode the image
                    $newImageData = base64_decode($newImageData);
                    if ($newImageData === false) {
                        throw new \Exception('Base64 decode failed');
                    }
        
                    // Generate a unique filename
                    $imageName = $studentId . '.' . $type;
                    $imagePath = public_path('storage/uploads/student_image/' . $imageName);
        
                    // Save the image file
                    file_put_contents($imagePath, $newImageData);
                    $validatedData['image_name'] = $imageName;
        
                    Log::info("Image uploaded for student ID: {$studentId}");
                } else {
                    throw new \Exception('Invalid image data format');
                }
            }
        }
        */

        // Include academic year in the update data
        $validatedData['academic_yr'] = $academicYr;
        Log::info("Message 3 {$validatedData['academic_yr']} ");
        if($parentId=='0'){
            Log::info("Message 4 Inside if");
            // Update parent details if provided
                // If the record doesn't exist, create a new one with parent_id as the id
                $parentId = Parents::insertGetId([
                    'father_name' => $validatedData['father_name'],
                    'father_occupation' =>  $validatedData['father_occupation'],
                    'f_office_add' => $validatedData['f_office_add'],
                    'f_office_tel' => $validatedData['f_office_tel'],
                    'f_mobile' => $validatedData['f_mobile'],
                    'f_email' =>  $validatedData['f_email'] ,
                    'mother_name' => $validatedData['mother_name'] ,
                    'mother_occupation' => $validatedData['mother_occupation'] ,
                    'm_office_add' => $validatedData['m_office_add'] ,
                    'm_office_tel' => $validatedData['m_office_tel'] ,
                    'm_mobile' => $validatedData['m_mobile'] ,
                    'm_emailid' => $validatedData['m_emailid'] ,
                    'parent_adhar_no' => $validatedData['parent_adhar_no'] ,
                    'm_adhar_no' => $validatedData['m_adhar_no'] ,
                    'f_dob' => $validatedData['f_dob'] ,
                    'm_dob' => $validatedData['m_dob'],
                    'f_blood_group' => $validatedData['f_blood_group'] ,
                    'm_blood_group' => $validatedData['m_blood_group'],
                    'IsDelete' => 'N'
                ]);
                
                $parent = Parents::where('parent_id',$parentId)->first();
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
                }
                elseif ($setToReceiveSMS) {
                    $phoneNo = $setToReceiveSMS;
                     $alternatePhoneNo = $validatedData['f_mobile']; 
                }

                // If the record doesn't exist, create a new one with parent_id as the id
                DB::insert('INSERT INTO contact_details (id, phone_no, alternate_phone_no, email_id, m_emailid) VALUES (?, ?, ?, ?, ?)', [
                    $parentId,                
                    $phoneNo,
                    $alternatePhoneNo,
                    $validatedData['f_email'],
                    $validatedData['m_emailid']  // sms_consent
                ]);
                
                Log::info("Message 6 parentId: {$parentId} "); 
                
                $user = new UserMaster();
                $user->user_id = $validatedData['f_email'];
                $user->name = $validatedData['father_name'];
                $user->password = bcrypt('arnolds'); 
                $user->reg_id = $parentId;
                $user->role_id = 'P';
                $user->save();
                
                $user = UserMaster::where('reg_id', $parentId)->where('role_id','P')->first();
                Log::info("Student information updated for parent ID: {$parentId}");

                // $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();

                if ($user) {
                    switch ($request->SetEmailIDAsUsername) {
                        case 'Father':
                            $user->user_id = $parent->f_email; 
                            break;
                    
                        case 'Mother':
                            $user->user_id = $parent->m_emailid; 
                            break;
                    
                        case 'FatherMob':
                            $user->user_id = $parent->f_mobile; 
                            break;
                    
                        case 'MotherMob':
                            $user->user_id = $parent->m_mobile;
                            break;
                    
                        default:
                            $user->user_id = $request->SetEmailIDAsUsername; // If the value is anything else
                            break;
                    }
                    $user->save();
                    Log::info("User Data saved in if");
                }
        }else{
            Log::info("Parent Id: {$parentId}");
            // Update parent details if provided
            $parent = Parents::find($parentId);
            if ($parent) {
                Log::info("msggg1");
                $parent->update($request->only([
                    'father_name', 'father_occupation', 'f_office_add', 'f_office_tel',
                    'f_mobile', 'f_email', 'parent_adhar_no', 'mother_name',
                    'mother_occupation', 'm_office_add', 'm_office_tel', 'm_mobile',
                    'm_emailid', 'm_adhar_no','m_dob','f_dob','f_blood_group','m_blood_group'
                ]));

                
                Log::info("msggg2");
                // Determine the phone number based on the 'SetToReceiveSMS' input
                $phoneNo = null;
                $setToReceiveSMS = $request->input('SetToReceiveSMS');
                if ($setToReceiveSMS == 'Father') {
                    $phoneNo = $parent->f_mobile;
                    $alternatePhoneNo = $validatedData['m_mobile'];
                } elseif ($setToReceiveSMS == 'Mother') {
                    $phoneNo = $parent->m_mobile;
                     $alternatePhoneNo = $validatedData['f_mobile']; 
                }
                elseif ($setToReceiveSMS) {
                    $phoneNo = $setToReceiveSMS;
                     $alternatePhoneNo = $validatedData['f_mobile']; 
                }
                Log::info("msggg3");
                // Check if a record already exists with parent_id as the id
                $contactDetails = ContactDetails::find($parentId);
                $phoneNo1 = $parent->f_mobile;
                if ($contactDetails) {
                    Log::info("msggg4");
                    // If the record exists, update the contact details
                    $contactDetails->update([
                        'phone_no' => $phoneNo,
                        'alternate_phone_no' => $alternatePhoneNo, // Assuming alternate phone is Father's mobile number
                        'email_id' => $parent->f_email, // Father's email
                        'm_emailid' => $parent->m_emailid // Mother's email
                         // Store consent for SMS
                    ]);
                } else {
                    Log::info("msggg5 {$phoneNo}");
                    Log::info("msggg5 {$alternatePhoneNo}");
                    // If the record doesn't exist, create a new one with parent_id as the id
                    DB::insert('INSERT INTO contact_details (id, phone_no, alternate_phone_no, email_id, m_emailid) VALUES (?, ?, ?, ?, ?)', [
                        $parentId,                
                        $phoneNo,
                        $alternatePhoneNo,
                        $parent->f_email,
                        $parent->m_emailid // sms_consent
                    ]);
                }

                // Update email ID as username preference
                $user = UserMaster::where('reg_id', $parentId)->where('role_id','P')->first();
                Log::info("Student information updated for student ID: {$user}");

                // $user = UserMaster::where('reg_id', $student->parent_id)->where('role_id', 'P')->first();

                if ($user) {
                    switch ($request->SetEmailIDAsUsername) {
                        case 'Father':
                            $user->user_id = $parent->f_email; // Father's email
                            break;
                    
                        case 'Mother':
                            $user->user_id = $parent->m_emailid; // Mother's email
                            break;
                    
                        case 'FatherMob':
                            $user->user_id = $parent->f_mobile; // Father's mobile
                            break;
                    
                        case 'MotherMob':
                            $user->user_id = $parent->m_mobile; // Mother's mobile
                            break;
                    
                        default:
                            $user->user_id = $request->SetEmailIDAsUsername; // If the value is anything else
                            break;
                    }
                    $user->save();
                    Log::info("User saved in else");
                }
            }
            
        }

        $validatedData['parent_id'] = $parentId;
        // Update student information
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $student->update($validatedData);
        $student->updated_by = $user->reg_id;
        $student->save();
        $fileContent = file_get_contents($filePath);           // Get the file content
        $base64File = base64_encode($fileContent); 
        upload_student_profile_image_into_folder($studentId,$filename,$doc_type_folder,$base64File);
        Log::info("Finally Student information updated for student ID: {$studentId}");

        return response()->json(['success' => 'Student and parent information updated successfully']);
    } catch (Exception $e) {
        Log::error("Exception occurred for student ID: {$studentId} - " . $e->getMessage());
        return response()->json(['error' => 'An error occurred while updating information'], 500);
    }
    // return response()->json($request->all());

}

public function getClassteacherList(Request $request)
{
    $payload = getTokenPayload($request);  
    $academicYr = $payload->get('academic_year');
    //$class_teachers =Class_teachers::where('academic_yr', $academicYr)
    //                 ->orderBy('section_id')  //order 
    //                 ->get();
    //return response()->json($class_teachers);

    $query = Class_teachers::with('getClass', 'getDivision', 'getTeacher')
            ->where('academic_yr', $academicYr);

    $class_teachers = $query->
                             orderBy('section_id', 'ASC') // multiple section_id, sm_id
                             ->get();
                             
    return response()->json($class_teachers);
}

public function saveClassTeacher(Request $request)
{
    $payload = getTokenPayload($request);  
    $academicYr = $payload->get('academic_year');
    $messages = [
        'class_id.required' => 'Class field is required.',
        'section_id.required' => 'Section field is required.',
        'teacher_id.required' => 'Teacher field is required.',
     ];

    try {
        $validatedData = $request->validate([
            'class_id' => [
                'required'
            ],
            'section_id' => [
                'required'
            ],
            'teacher_id' => [
                'required'
            ],
        ], $messages);
    } catch (ValidationException $e) {
        return response()->json([
            'status' => 422,
            'errors' => $e->errors(),
        ], 422);
    }

    $class_teacher = new Class_teachers();
    $class_teacher->class_id = $validatedData['class_id'];
    $class_teacher->section_id = $validatedData['section_id'];
    $class_teacher->teacher_id = $validatedData['teacher_id'];
    $class_teacher->academic_yr = $academicYr;
    // Check if Class teacher exists, if not, create one
    
    $existing_classteacher = Class_teachers::where('class_id', $validatedData['class_id'])->where('section_id', $validatedData['section_id'])->first();
    if (!$existing_classteacher) {
        $class_teacher->save();
        return response()->json([
            'status' => 201,
            'message' => 'Class teacher is alloted successfully.',
        ], 201);
    }else{
        return response()->json([
            'error' => 404,
            'message' => 'Class teacher already alloted.',
        ], 404);
    }
}    
    public function updateClassTeacher(Request $request, $class_id, $section_id)
    {
        $messages = [
            'class_id.required' => 'Class field is required.',
            'section_id.required' => 'Section field is required.',
            'teacher_id.required' => 'Teacher field is required.'
        ];

        try {
            $validatedData = $request->validate([
                'class_id' => [
                'required'
            ],
            'section_id' => [
                'required'
            ],
            'teacher_id' => [
                'required'
            ],
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }
        $teacher_id= $validatedData['teacher_id'];
        $class_teacher = Class_teachers::where('class_id', $validatedData['class_id'])->where('section_id', $validatedData['section_id'])->first();

        if (!$class_teacher) {
            return response()->json([
                'status' => 404,
                'message' => 'Class teacher data not found',
            ], 404);
        }else{
            $class_teacher_updated =Class_teachers::where(['class_id'=>$validatedData['class_id'],'section_id'=>$validatedData['section_id']])->update(['teacher_id'=>$teacher_id]);
            //$class_teacher->teacher_id = $validatedData['teacher_id'];
            //$class_teacher->save();

            return response()->json([
                'status' => 200,
                'message' => 'Class teacher updated successfully',
            ], 200);
        }
    }

public function deleteClassTeacher($class_id, $section_id)
{
    $class_teacher = Class_teachers::where('class_id', $class_id)->where('section_id', $section_id)->first();

    if (!$class_teacher) {
        return response()->json([
            'status' => 404,
            'message' => 'Class teacher data not found',
        ]);
    }else{
    
        //$class_teacher->delete();
        $class_teacher_deleted =Class_teachers::where(['class_id'=>$class_id,'section_id'=>$section_id])->delete();
            
        return response()->json([
            'status' => 200,
            'message' => 'Class teacher data deleted successfully',
            'success' => true
        ]);
    }
}

public function editClassteacher($class_id,$section_id)
{
    $class_teacher =Class_teachers::where('class_id', $class_id)->where('section_id', $section_id)->first();
          
    if (!$class_teacher) {
        return response()->json([
            'status' => 404,
            'message' => 'Class teacher data not found',
        ]);
    }

    return response()->json($class_teacher);
}

private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    public function getLeavetype(){
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        try{
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $leavetype=LeaveType::all();
            return response()->json([
                'status'=> 200,
                'message'=>'Leave Type',
                'data' =>$leavetype,
                'success'=>true
                ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

public function getAllStaff(){
    $user = $this->authenticateUser();
    $customClaims = JWTAuth::getPayload()->get('academic_year');
    try{
    if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
        $staff=DB::table('teacher')->where('isDelete','N')->orderBy('teacher_id','ASC')->get();
        return response()->json([
            'status'=> 200,
            'message'=>'All Staffs',
            'data' =>$staff,
            'success'=>true
            ]);

    }
    else{
        return response()->json([
            'status'=> 401,
            'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

public function saveLeaveAllocated(Request $request){

    $user = $this->authenticateUser();
    $customClaims = JWTAuth::getPayload()->get('academic_year');
    try{
    if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
        $leaveforstaff = DB::table('leave_allocation')->where('staff_id',$request->staff_id)->where('leave_type_id',$request->leave_type_id)->where('academic_yr',$customClaims)->first();
        if(!$leaveforstaff){
            $leaveallocation = new LeaveAllocation();
            $leaveallocation->staff_id = $request->staff_id;
            $leaveallocation->leave_type_id = $request->leave_type_id;
            $leaveallocation->leaves_allocated = $request->leaves_allocated;
            $leaveallocation->academic_yr = $customClaims;
            $leaveallocation->save();

            return response()->json([
                'status'=> 200,
                'message'=>'Leave Allocated Successfully.',
                'data' =>$leaveallocation,
                'success'=>true
                ]);

        }
        else{
            return response()->json([
                'status'=> 400,
                'message'=>'Leave Allocation for this staff is already done.',
                'success'=>false
                ]);
        }

    }
    else{
        return response()->json([
            'status'=> 401,
            'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

public function leaveAllocationall(){
    try{
       $user = $this->authenticateUser();
       $customClaims = JWTAuth::getPayload()->get('academic_year');
       if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
           $leaveallocationall = DB::table('leave_allocation')
                                    ->join('teacher','teacher.teacher_id','=','leave_allocation.staff_id')
                                    ->join('leave_type_master','leave_type_master.leave_type_id','=','leave_allocation.leave_type_id')
                                    ->select('leave_allocation.*','leave_type_master.name as leavename','teacher.name as teachername',DB::raw('leave_allocation.leaves_allocated - leave_allocation.leaves_availed as balance_leave'))
                                    ->where('leave_allocation.academic_yr',$customClaims)
                                    ->distinct()
                                    ->get();

               return response()->json([
               'status'=> 200,
               'message'=>'ALl Leave Allocation',
               'data' =>$leaveallocationall,
               'success'=>true
               ]);

       }
       else{
           return response()->json([
               'status'=> 401,
               'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

public function getLeaveAllocationdata($staff_id,$leave_type_id){
    try{
       $user = $this->authenticateUser();
       $customClaims = JWTAuth::getPayload()->get('academic_year');
       if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
           $leaveallocationall = DB::table('leave_allocation')
                   ->join('teacher','teacher.teacher_id','=','leave_allocation.staff_id')
                   ->join('leave_type_master','leave_type_master.leave_type_id','=','leave_allocation.leave_type_id')
                   ->where('leave_allocation.staff_id','=',$staff_id)
                   ->where('leave_allocation.leave_type_id','=',$leave_type_id)
                   ->where('leave_allocation.academic_yr',$customClaims)
                   ->select('leave_allocation.*','leave_type_master.name as leavename','teacher.name as teachername')
                   ->get();

               return response()->json([
               'status'=> 200,
               'message'=>'Leave Allocation Data',
               'data' =>$leaveallocationall,
               'success'=>true
               ]);

       }
       else{
           return response()->json([
               'status'=> 401,
               'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

public function updateLeaveAllocation(Request $request,$staff_id,$leave_type_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $leaveAllocation = LeaveAllocation::where('staff_id', $staff_id)
                                    ->where('leave_type_id', $leave_type_id)
                                    ->where('academic_yr', $customClaims)
                                    ->update([
                                        'leaves_allocated' => $request->leaves_allocated,
                                    ]);

                if (!$leaveAllocation) {
                // If no record is found, return an error response
                return response()->json([
                'status' => 404,
                'message' => 'Leave allocation not found!',
                'success' => false
                ]);
                }

                return response()->json([
                'status' => 200,
                'message' => 'Leave allocation updated successfully!',
                'data' => $leaveAllocation,
                'success' => true
                ]);
             

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

public function deleteLeaveAllocation($staff_id,$leave_type_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            // dd($staff_id,$leave_type_id);
            $leaveApplication = DB::table('leave_application')
                                        ->where('staff_id', $staff_id)
                                        ->where('leave_type_id', $leave_type_id)
                                        ->where('academic_yr', $customClaims)
                                        ->first();

                        if ($leaveApplication) {
                            return response()->json([
                                'status' => 400,
                                'message' => 'This leave allocation is in use. Delete failed!!!',
                                'success' => false
                            ]);
                        }
            DB::table('leave_allocation')
               ->where('staff_id',$staff_id)
               ->where('leave_type_id',$leave_type_id)
               ->where('academic_yr',$customClaims)
               ->delete();

               return response()->json([
                'status'=> 200,
                'message'=>'Leave Allocation deleted Successfully.',
                'success'=>true
                ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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


public function saveLeaveAllocationforallStaff(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){

            $status = false;
            $staffData = DB::table('teacher')->where('isDelete','N')->orderBy('teacher_id','ASC')->get();
            
            foreach ($staffData as $staff) {
                
                $data = [
                    'staff_id' => $staff->teacher_id,
                    'leave_type_id' => $request->input('leave_type_id'),
                    'leaves_allocated' => $request->input('leaves_allocated'),
                    'academic_yr' => $customClaims, 
                ];
    
                
                $existingLeaveAllocation = LeaveAllocation::where('leave_type_id', $request->input('leave_type_id'))
                    ->where('staff_id', $staff->teacher_id)
                    ->where('academic_yr', $customClaims) 
                    ->first();
    
                if (!$existingLeaveAllocation) {

                    LeaveAllocation::create($data);
                    $status = true;
                }
            }
    
            if ($status) {
                return response()->json([
                    'status' => '200',
                    'message' => 'Leave allocation successfully done!!!',
                    'success' =>true
                ]);
            } else {
                return response()->json([
                    'status' => '400',
                    'message' => 'Leave allocation is already present!!!',
                    'success' =>false
                ]);
            }


        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

   public function sendUserIdParents(Request $request){
    
    $checkbx = $request->input('studentId');
    foreach ($checkbx as $parent_id) {
        $student = DB::table('student')
                ->join('contact_details', 'student.parent_id', '=', 'contact_details.id')
                ->join('user_master', 'student.parent_id', '=', 'user_master.reg_id')
                ->where('student.student_id', $parent_id)
                ->select('student.isNew','student.first_name','contact_details.email_id','contact_details.m_emailid','user_master.user_id','user_master.password')
                ->first();
        // dd($student);
        $f_emailid = $student->email_id;
        $m_emailid = $student->m_emailid;
        $user_id = $student->user_id;
        $password = $student->password;
        $isNew = $student->isNew;
        $first_name = $student->first_name;
        // $decryptedPassword = Crypt::decrypt($password);
        // dd($decryptedPassword);

        if($isNew == 'Y'){
            $subject= "Welcome to St.Arnold's Central School's online application";
            $textmsg="Dear Parent,<br/><br/>Welcome to St.Arnold's Central School's online application. <br/><br/>'{$first_name}' is registered in the application. Your user id is {$user_id} and password is arnolds.<br/><br/>Regards,<br/>SACS Support";

        }
        else{
            $subject="Your login details for St.Arnold's Central School";
            $textmsg="Dear Parent,<br/><br/>Your user id for St.Arnold's Central School's online application is {$user_id} and password is arnolds.<br/><br/>Regards,<br/>SACS Support";
        }
        
        if ($f_emailid) {
            Mail::send('emails.parentUserEmail', ['textmsg' => $textmsg,'subject'=>$subject], function ($message) use ($f_emailid,$subject) {
                $message->to($f_emailid)
                        ->subject('SACS Login Details');
            });
        }

        if ($m_emailid) {
            Mail::send('emails.parentUserEmail', ['textmsg' => $textmsg], function ($message) use ($m_emailid,$subject) {
                $message->to($m_emailid)
                        ->subject('SACS Login Details');
            });
        }
    }
    return response()->json([
        'status' => '200',
        'message' => 'Emails sent to selected parents successfully.',
        'success'=>true
    ], 200);
}

public function getLeavetypedata(Request $request,$staff_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
             $leavetype = DB::table('leave_type_master')
                              ->join('leave_allocation','leave_type_master.leave_type_id','=','leave_allocation.leave_type_id')
                              ->where('leave_allocation.staff_id',$staff_id)
                              ->where('leave_allocation.academic_yr',$customClaims)
                              ->select(
                                'leave_type_master.leave_type_id',
                                DB::raw("CONCAT(leave_type_master.name, ' (', leave_allocation.leaves_allocated - leave_allocation.leaves_availed, ')') as name"),
                                'leave_allocation.staff_id',
                                'leave_allocation.leaves_allocated',
                                'leave_allocation.leaves_availed',
                                'leave_allocation.academic_yr',
                                'leave_allocation.created_at',
                                'leave_allocation.updated_at'
                            )->distinct()->get();
                            return response()->json([
                            'status' => '200',
                            'message' => 'Leave type data',
                            'data' => $leavetype,
                            'success' =>true
                             ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

   public function saveLeaveApplication(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $leavetype = DB::table('leave_type_master')
                              ->join('leave_allocation','leave_type_master.leave_type_id','=','leave_allocation.leave_type_id')
                              ->where('leave_allocation.staff_id',$request->staff_id)
                              ->where('leave_allocation.academic_yr',$customClaims)
                              ->where('leave_allocation.leave_type_id',$request->leave_type_id)
                              ->first();
            $balanceleave = $leavetype->leaves_allocated - $leavetype->leaves_availed;
            if($balanceleave < $request->no_of_days){
                return response()->json([
                    'status'=>400,
                    'message' => 'You have applied for leave more than the balance leaves',
                    'success'=>false
                ]);
            
            }
            

            $data = [
                'staff_id' => $request->staff_id,
                'leave_type_id' =>$request->leave_type_id,
                'leave_start_date' => $request->leave_start_date,
                'leave_end_date' => $request->leave_end_date,
                'no_of_days' => $request->no_of_days,
                'reason' => $request->reason,
                'status' => 'A',
                'academic_yr'=>$customClaims
            ];
        
            $leaveApplication= LeaveApplication::create($data);
        
            return response()->json([
                'status'=>200,
                'message' => 'Leave Application saved successfully.',
                'data' => $leaveApplication,
                'success'=>true
            ]);


        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

   public function getLeaveApplicationList(){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $leaveapplicationlist = LeaveApplication::join('leave_type_master','leave_application.leave_type_id','=','leave_type_master.leave_type_id')
                                                    ->where('academic_yr', $customClaims)
                                                    ->where('staff_id',$user->reg_id)
                                                    ->get();
              $leaveapplicationlist->transform(function ($leaveApplication) {
                
                if ($leaveApplication->status === 'A') {
                    $leaveApplication->status = 'Apply';   
                } elseif ($leaveApplication->status === 'H') {
                    $leaveApplication->status = 'Hold';     
                } elseif ($leaveApplication->status === 'R') {
                    $leaveApplication->status = 'Reject';   
                } elseif ($leaveApplication->status === 'P') {
                    $leaveApplication->status = 'Approve';  
                } else {
                    $leaveApplication->status = 'Unknown';  
                }
                return $leaveApplication;
            });

            return response()->json([
                'status'=>200,
                'message' => 'Leave Application List.',
                'data' => $leaveapplicationlist,
                'success'=>true
            ]);
              

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

   public function getLeaveAppliedData(Request $request,$leave_app_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
             $leaveApplicationn = LeaveApplication::find($leave_app_id);
             if ($leaveApplicationn) {
                // Modify the status temporarily for displaying
                if ($leaveApplicationn->status === 'A') {
                    $leaveApplicationn->status = 'Apply';   
                } elseif ($leaveApplicationn->status === 'H') {
                    $leaveApplicationn->status = 'Hold';     
                } elseif ($leaveApplicationn->status === 'R') {
                    $leaveApplicationn->status = 'Reject';   
                } elseif ($leaveApplicationn->status === 'P') {
                    $leaveApplicationn->status = 'Approve';  
                } else {
                    $leaveApplicationn->status = 'Unknown';  
                }

                return response()->json([
                    'status'=>200,
                    'message'=>'Leave Application Data.',
                    'data'=>$leaveApplicationn,
                    'success'=>true
                    ]);
            } else {
                return response()->json([
                    'status'=>404,
                    'message' => 'Leave application not found',
                    'success'=>false
                
                ]);
            }


        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

   public function updateLeaveApplication(Request $request,$leave_app_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $leavetype = DB::table('leave_type_master')
                              ->join('leave_allocation','leave_type_master.leave_type_id','=','leave_allocation.leave_type_id')
                              ->where('leave_allocation.staff_id',$request->staff_id)
                              ->where('leave_allocation.academic_yr',$customClaims)
                              ->where('leave_allocation.leave_type_id',$request->leave_type_id)
                              ->first();
            $balanceleave = $leavetype->leaves_allocated - $leavetype->leaves_availed;
            if($balanceleave < $request->no_of_days){
                return response()->json([
                    'status'=>400,
                    'message' => 'Applied leave is greater than Balance leave',
                    'success'=>false
                ]);
            
            }

            $leaveApplication = LeaveApplication::find($leave_app_id);
            $leaveApplication->staff_id = $request->staff_id;
            $leaveApplication->leave_type_id = $request->leave_type_id;
            $leaveApplication->leave_start_date = $request->leave_start_date;
            $leaveApplication->leave_end_date = $request->leave_end_date;
            $leaveApplication->no_of_days = $request->no_of_days;
            $leaveApplication->reason = $request->reason;
            $leaveApplication->save();

            return response()->json([
                'status'=>200,
                'message'=>'Leave Application Updated.',
                'data'=>$leaveApplication,
                'success'=>true
                ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

   public function deleteLeaveApplication($leave_app_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $leaveApplication = LeaveApplication::find($leave_app_id);

            if ($leaveApplication) {

                $leaveApplication->delete();
        
                return response()->json([
                    'status'=>200,
                    'message' => 'Leave application deleted successfully',
                    'data'=>$leaveApplication,
                    'success'=>true
                
                ]);
            } else {
                return response()->json([
                    'status'=>400,
                    'messagae' => 'Leave application not found',
                    'success'=>false
                ]);
            }

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

   public function saveSiblingMapping(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){

            $changed_data = false;
        $operation = $request->input('operation');
        
        if ($operation == "create") {
            $set_parent_id = $request->input('set_as_parent');
            
            if ($set_parent_id == '1') {
                // dd("Hello");
                $student_id2 = $request->input('student_id2');
                $parent_id1 = $request->input('parent_id1');
                $parent_id2 = $request->input('parent_id2');
                
                // Update student record
                $student = Student::where('student_id', $student_id2)
                    ->where('parent_id', $parent_id2)
                    ->first();

                if ($student) {
                    $student->parent_id = $parent_id1;
                    $student->save();
                    $changed_data = true;
                }

                // Check if there are any remaining students with the old parent_id
                $studentsWithOldParent = Student::where('parent_id', $parent_id2)
                    ->where('academic_yr', $customClaims)
                    ->get();

                if ($studentsWithOldParent->isEmpty()) {

                    UserMaster::where('reg_id', $parent_id2)
                        ->where('role_id', 'P')
                        ->update(['IsDelete' => 'Y']);

                    Parents::where('parent_id', $parent_id2)
                        ->update(['IsDelete' => 'Y']);

                    // Handle contact details deletion and insertion into deleted_contact_details
                    $contact = ContactDetails::where('id', $parent_id2)->first();
                    if ($contact) {
                        DB::table('deleted_contact_details')->insert([
                            'id' => $contact->id,
                            'phone_no' => $contact->phone_no,
                            'email_id' => $contact->email_id,
                            'm_emailid' => $contact->m_emailid,
                        ]);
                        $contact->delete();
                    }
                }
            } elseif ($set_parent_id == '2') {
                // Get data for set_parent_id == 2
                $student_id1 = $request->input('student_id1');
                $parent_id1 = $request->input('parent_id1');
                $parent_id2 = $request->input('parent_id2');

                // Update student record
                $student = Student::where('student_id', $student_id1)
                    ->where('parent_id', $parent_id1)
                    ->first();

                if ($student) {
                    $student->parent_id = $parent_id2;
                    $student->save();
                    $changed_data = true;
                }

                $studentsWithOldParent = Student::where('parent_id', $parent_id1)
                    ->where('academic_yr', $customClaims)
                    ->get();
                    // dd($studentsWithOldParent);

                if ($studentsWithOldParent->isEmpty()) {
                    // Set 'IsDelete' to 'Y' for user and parent records
                    UserMaster::where('reg_id', $parent_id1)
                        ->where('role_id', 'P')
                        ->update(['IsDelete' => 'Y']);

                    Parents::where('parent_id', $parent_id1)
                        ->update(['IsDelete' => 'Y']);

                    $contact = ContactDetails::where('id', $parent_id1)->first();
                   
                    if ($contact) {
                        DB::table('deleted_contact_details')->insert([
                            'id' => $contact->id,
                            'phone_no' => $contact->phone_no,
                            'email_id' => $contact->email_id,
                            'm_emailid' => $contact->m_emailid,
                        ]);
                        $contact->delete();
                    }
                }
            }

            // Get student names to prepare response
            $stud1 = Student::find($request->input('student_id1'))->first_name ?? '';
            $stud2 = Student::find($request->input('student_id2'))->first_name ?? '';

            if ($changed_data) {
                return response()->json([
                    'status' =>200,
                    'message' => 'Students ' . $stud1 . ' and ' . $stud2 . ' are mapped.!!!',
                    'success' =>true
                ]);
            } else {
                return response()->json([
                    'status'=>400,
                    'error' => 'Students ' . $stud1 . ' and ' . $stud2 . ' are not mapped.!!!',
                    'success'=>false
                ], 400);
            }
        }


        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
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

   public function saveLeavetype(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $notexist = DB::table('leave_type_master')->where('name',$request->input('name'))->first();
            if(!$notexist){
            $data = [
                'name' => $request->input('name'),
            ];

            DB::table('leave_type_master')->insert($data);
            return response()->json([
                'status'=> 200,
                'message'=>'Leave Type Created Successfully',
                'success'=>true
                    ]);
            }
            return response()->json([
                'status'=> 400,
                'message'=>'The Name field must contain a unique value.',
                'success'=>false
                    ]);
            

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

   }

   public function getallleavetype(){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
             $data = DB::table('leave_type_master')->get();
             return response()->json([
                'status'=> 200,
                'message'=>'Leave Type List',
                'data'=>$data,
                'success'=>true
                    ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

   }

   public function getLeaveData($id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
             $data = DB::table('leave_type_master')->where('leave_type_id',$id)->first();
             return response()->json([
                'status'=> 200,
                'message'=>'Leave Type Data',
                'data'=>$data,
                'success'=>true
                    ]);
             
        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

   }

   public function updateLeavetype(Request $request,$id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $newName = $request->input('name');
            $existingLeaveType = DB::table('leave_type_master')
                ->where('name', $newName)
                ->where('leave_type_id', '!=', $id)  // Ensure the same name is not assigned to another leave type with different ID
                ->exists();

            if ($existingLeaveType) {
                // Return an error response if the name already exists for a different leave type
                return response()->json([
                    'status' => 400,
                    'message' => 'Leave type name already exists for another leave type.',
                    'success' => false,
                ]);
            }

            // Proceed with updating the leave type record
            DB::table('leave_type_master')
                ->where('leave_type_id', $id)
                ->update([
                    'name' => $newName,  // Update the name field
                ]);

            // Return a success response
            return response()->json([
                'status' => 200,
                'message' => 'Leave type updated successfully.',
                'success' => true,
            ]);
             

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

   }

   public function deleteLeavetype($id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $data = DB::table('leave_type_master')->where('leave_type_id',$id)->delete();
             return response()->json([
                'status'=> 200,
                'message'=>'Leave Type deleted Successfully.',
                'success'=>true
                    ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

   }

   public function studentAllotGrno(Request $request,$id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
              $students = DB::table('student')
                            ->where('section_id',$id)
                            ->where('academic_yr',$customClaims)
                            ->select('student_id','first_name','mid_name','last_name','roll_no','reg_no','admission_date','stu_aadhaar_no')
                            ->orderBy('roll_no','ASC')
                            ->get();

                            $students = $students->map(function ($student) {
                                $student->full_name = getFullName($student->first_name, $student->mid_name, $student->last_name);
                                return $student;
                            });

                            return response()->json([
                                'status'=> 200,
                                'message'=>'Student List For Grno.',
                                'data'=>$students,
                                'success'=>true
                                    ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

   }

   public function updateStudentAllotGrno(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $studentsData = $request->input('students');
            // dd($studentsData);
            $validationErrors = [];
            foreach ($studentsData as $key => $studentData) {
                // For each student, define the validation rules
                $validationRules["students.$key.reg_no"] = 'nullable|unique:student,reg_no,' . $studentData['student_id'] . ',student_id,academic_yr,' . $customClaims;
            }

            // Validate the entire student data
            $validator = Validator::make($request->all(), $validationRules, [
                'students.*.reg_no.unique' => 'The GR number has already been taken by another student.',
            ]);

            // If validation fails, return the error response
            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()->toArray(),
                    'success' => false
                ], 422);
            }

            foreach ($studentsData as $studentData) {
                $studentId = $studentData['student_id'];
                $regNo = $studentData['reg_no'];
                $admissionDate = date('Y-m-d', strtotime($studentData['admission_date']));
                $aadhaarNo = $studentData['stu_aadhaar_no'];
    
                // Find existing student by student_id
                $student = Student::where('student_id', $studentId)->first();
    
                // If student exists, update the data
                if ($student) {
                    $student->update([
                        'reg_no' => $regNo,
                        'admission_date' => $admissionDate,
                        'stu_aadhaar_no' => $aadhaarNo
                    ]);
                } 
            }
    
            // Return success response
            return response()->json([
                'status' => 200,
                'message' => 'Student data saved successfully!',
                'success' => true
            ], 200);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

   }

   public function getStudentCategoryReligion($class_id,$section_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $students = DB::table('student')
                            ->where('class_id',$class_id)
                            ->where('section_id',$section_id)
                            ->where('academic_yr',$customClaims)
                            ->select('student_id','first_name','mid_name','last_name','roll_no','category','religion','gender')
                            ->get();

                            $students = $students->map(function ($student) {
                                $student->full_name = getFullName($student->first_name, $student->mid_name, $student->last_name);
                                return $student;
                            });

                            return response()->json([
                                'status'=> 200,
                                'message'=>'Student List For Category and Religion.',
                                'data'=>$students,
                                'success'=>true
                                    ]);


        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

  }


  public function updateStudentCategoryReligion(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
             $students = $request->input('students');
             foreach ($students as $student) {
                // Prepare data to be updated
                $data = [
                    'category' => $student['category'] ?? '',
                    'religion' => $student['religion'] ?? '',
                    'gender' => $student['gender'] ?? '',
                ];
    
                Student::where('student_id', $student['student_id'])
                    ->where('academic_yr', $customClaims)  // Assuming session() is being used in Laravel
                    ->update($data);
             }

             return response()->json([
                'status' => 200,
                'message' => 'Student data updated successfully!',
                'success' => true
            ], 200);


        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }


  }

  public function getStudentOtherDetails($class_id,$section_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $students = DB::table('student')
            ->where('class_id',$class_id)
            ->where('section_id',$section_id)
            ->where('academic_yr',$customClaims)
            ->select('student_id','first_name','mid_name','last_name','roll_no','stud_id_no','udise_pen_no','birth_place','mother_tongue','admission_class')
            ->orderBy('roll_no','asc')
            ->get();

            $students = $students->map(function ($student) {
                $student->full_name = getFullName($student->first_name, $student->mid_name, $student->last_name);
                return $student;
            });

            return response()->json([
                'status'=> 200,
                'message'=>'Student List For Studentid and other Details.',
                'data'=>$students,
                'success'=>true
                    ]);


        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

  }


  public function updateStudentIdOtherDetails(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $students = $request->input('students'); 

    
            
            foreach ($students as $student) {
                $data = [
                    'stud_id_no' => $student['stud_id_no'] ?? '',
                    'birth_place' => $student['birth_place'] ?? '',
                    'mother_tongue' => $student['mother_tongue'] ?? '',
                    'admission_class' => $student['admission_class'] ?? '',
                    'udise_pen_no' => $student['udise_pen_no'] ?? '',
                ];
    

                Student::where('student_id', $student['student_id'])
                    ->where('academic_yr', $customClaims) // Assuming academic year is stored in session
                    ->update($data);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Student data updated successfully!',
                'success' => true
            ], 200);
    

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

  }

   //Dev Name - Manish Kumar Sharma 18-02-2025
   public function saveHoliday(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $data = [
                'title' => $request->input('title'),
                'holiday_date' => $request->input('holiday_date'),
                'to_date' => $request->input('to_date'),
                'academic_yr' => $customClaims, 
                'isDelete' => 'N',
                'publish' => 'N',
                'created_by' => $user->reg_id, 

            ];
    
            DB::table('holidaylist')->insert($data);

            return response()->json([
                'status'=>200,
                'message' => 'New holiday created!!!',
                'data' => $data,
                'success'=>true
            ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
}

//Dev Name - Manish Kumar Sharma 18-02-2025
public function saveHolidaypublish(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $data = [
                'title' => $request->input('title'),
                'holiday_date' => $request->input('holiday_date'),
                'to_date' => $request->input('to_date'),
                'academic_yr' => $customClaims, 
                'isDelete' => 'N',
                'publish' => 'Y',
                'created_by' => $user->reg_id, 

            ];
    
            DB::table('holidaylist')->insert($data);

            return response()->json([
                'status'=>200,
                'message' => 'New holiday created and published!!!',
                'data' => $data,
                'success'=>true
            ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
}

 //Dev Name - Manish Kumar Sharma 18-02-2025
public function getholidayList(){
    try{
        $user = $this->authenticateUser();
        
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            // $holidaylist = DB::table('holidaylist')->where('academic_yr',$customClaims)->get();
            $holidaylist = DB::table('holidaylist')
                            ->join('user_master', 'holidaylist.created_by', '=', 'user_master.reg_id') // Join with the users table
                            ->where('user_master.role_id','A')
                            ->where('holidaylist.academic_yr', $customClaims)
                            ->select('holidaylist.*', 'user_master.name as created_by_name') // Select the necessary columns
                            ->get();
    
            
            return response()->json([
                'status'=>200,
                'message' => 'Holiday List!!!',
                'data' => $holidaylist,
                'success'=>true
            ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

}

//Dev Name - Manish Kumar Sharma 18-02-2025
public function deleteHoliday($holiday_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $holiday = DB::table('holidaylist')->where('holiday_id', $holiday_id)->first();
            if ($holiday) {
                if ($holiday->publish == 'N') {
                    DB::table('holidaylist')->where('holiday_id', $holiday_id)->delete();

                } else {
                    DB::table('holidaylist')
                        ->where('holiday_id', $holiday_id)
                        ->update(['isDelete' => 'Y']);
                }
            }
        
            return response()->json([
                'status'=> 200,
                'message'=>'Holiday Deleted.!!!',
                'success'=>true
                    ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

}
//Dev Name - Manish Kumar Sharma 18-02-2025
public function updatepublishholiday(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $holidayIds = $request->input('holiday_id'); 

            if ($holidayIds && is_array($holidayIds) && count($holidayIds) > 0) {

                foreach ($holidayIds as $holiday_id) {
                    DB::table('holidaylist')
                        ->where('holiday_id', $holiday_id)
                        ->update(['publish' => 'Y']);
                }

                return response()->json([
                    'status' => 200,
                    'message' => 'Holiday are published.!!!',
                    'success' => true
                ]);

            } else {

                return response()->json([
                    'status' => 400,
                    'message' => 'No holiday IDs provided.',
                    'success' => false
                ]);
            }

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
     
}
//Dev Name - Manish Kumar Sharma 18-02-2025
public function updateHoliday(Request $request,$holiday_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $data = [
                'title' => $request->input('title'),
                'holiday_date' => $request->input('holiday_date'),
                'to_date' => $request->input('to_date'),
                'academic_yr' => $customClaims, 
                'isDelete' => 'N',
                'publish' => 'N',
                'created_by' => $user->reg_id, 

            ];

            $updateddata = DB::table('holidaylist')
                                ->where('holiday_id', $holiday_id)
                                ->update($data);

            return response()->json([
                'status'=>200,
                'message' => 'Holiday edited!!!',
                'data' => $data,
                'success'=>true
            ]);


        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

}
//Dev Name - Manish Kumar Sharma 18-02-2025
public function downloadCsvTemplate(){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="holidaylist.csv"',
            ];
            ob_get_clean();
            $columns= ['*Title',
                '*Holiday date(in yyyy/mm/dd format)',
                'To date(in yyyy/mm/dd format)'];

            $callback = function() use ($columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);
        
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

}

//Dev Name - Manish Kumar Sharma 18-02-2025
public function updateholidaylistCsv(Request $request){
    $request->validate([
        'file' => 'required|file|mimes:csv,txt|max:2048',
    ]);
    
    $file = $request->file('file');
    if (!$file->isValid()) {
        return response()->json(['message' => 'Invalid file upload'], 400);
    }
    $csvData = file_get_contents($file->getRealPath());
    $rows = array_map('str_getcsv', explode("\n", $csvData));
    $header = array_shift($rows); // Extract the header row
    
    $columnMap = [
        '*Title' => 'title',
        '*Holiday date(in yyyy/mm/dd format)' => 'holiday_date',
        'To date(in yyyy/mm/dd format)' => 'to_date'
    ];
    $invalidRows = [];
    foreach ($rows as $rowIndex => $row) {
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        $holidayData = [];
        foreach ($header as $index => $columnName) {
            // dd($columnName);
            if (isset($columnMap[$columnName])) {
                $dbField = $columnMap[$columnName];
                $holidayData[$dbField] = $row[$index] ?? null;
                // dd($studentData[$dbField]);
            }
        }
        DB::beginTransaction();
        $errors = []; 
        if (empty($holidayData['title'])) {
            $errors[] = 'Title is required.';
        }

        if (empty($holidayData['holiday_date'])) {
            $errors[] = 'Holiday Date is required.';
        } elseif (!$this->validateDate($holidayData['holiday_date'], 'Y/m/d')) {
            $errors[] = 'Invalid Holiday Date format. Expected yyyy/mm/dd.';
        } else {
            try {
                // Convert DOB to the required format (yyyy-mm-dd)
                $holidayData['holiday_date'] = \Carbon\Carbon::createFromFormat('Y/m/d', $holidayData['holiday_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                $errors[] = 'Invalid Holidayy Date format. Expected yyyy/mm/dd.';
            }
        }

        if (empty($holidayData['to_date'])) {
            $holidayData['to_date'] = null;
        }
        elseif(!$this->validateDate($holidayData['holiday_date'], 'Y/m/d')){
            $errors[] = 'Invalid To Date format. Expected yyyy/mm/dd.';
        }
        else{
            $holidayData['to_date'] = \Carbon\Carbon::createFromFormat('Y/m/d', $holidayData['holiday_date'])->format('Y-m-d');
        }

        if (!empty($errors)) {
            // Combine the row with the errors and store in invalidRows
            $invalidRows[] = array_merge($row, ['error' => implode(' | ', $errors)]);
            // Rollback or continue to the next iteration to prevent processing invalid data
            DB::rollBack();
            continue; // Skip this row, moving to the next iteration
        }

        try{   
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $data = [
                    'title' => $holidayData['title'],
                    'holiday_date' => $holidayData['holiday_date'],
                    'to_date' => $holidayData['to_date'],
                    'academic_yr' => $customClaims, 
                    'isDelete' => 'N',
                    'publish' => 'N',
                    'created_by' => $user->reg_id, 

                ];
                
        
                DB::table('holidaylist')->insert($data);
                DB::commit();
                 
                

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
                DB::rollBack();
                $invalidRows[] = array_merge($row, ['error' => 'Error updating student: ' . $e->getMessage()]);
                continue;
            }


    }
    
        if (!empty($invalidRows)) {
            $csv = Writer::createFromString('');
            $csv->insertOne(['title','holiday_date','to_date','error']);
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
        return response()->json([
            'status' =>200,
            'message' => 'holidays Created Successfully.!!!',
            'success'=>true
        ]);

}


//Dev Name - Manish Kumar Sharma 25-02-2025
public function getStudentIdCard(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            
            $section_id = $request->input('section_id');
            $idcarddetails = DB::table('confirmation_idcard')
                                ->join('student', 'student.parent_id', '=', 'confirmation_idcard.parent_id')
                                ->join('class', 'student.class_id', '=', 'class.class_id')
                                ->join('section', 'student.section_id', '=', 'section.section_id')
                                ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
                                ->select(
                                    'confirmation_idcard.*',
                                    'student.first_name',
                                    'student.mid_name',
                                    'student.parent_id',
                                    'student.last_name',
                                    'student.roll_no',
                                    'student.image_name',
                                    'student.reg_no',
                                    'student.permant_add',
                                    'student.blood_group',
                                    'student.dob',
                                    'student.student_id',
                                    'parent.f_mobile',
                                    'parent.m_mobile',
                                    'class.name as class_name',
                                    'section.name as sec_name',
                                    DB::raw("
                                        CASE
                                            WHEN student.house = 'E' THEN 'Emerald'
                                            WHEN student.house = 'R' THEN 'Ruby'
                                            WHEN student.house = 'S' THEN 'Sapphire'
                                            WHEN student.house = 'D' THEN 'Diamond'
                                            ELSE 'Unknown House'
                                        END as house
                                    ")
                                )
                                ->where('student.section_id', $section_id)
                                ->where('confirmation_idcard.confirm', 'Y')
                                ->where('student.IsDelete', 'N')
                                ->orderBy('student.roll_no')
                                ->get();

                                $globalVariables = App::make('global_variables');
                                $parent_app_url = $globalVariables['parent_app_url'];
                                $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
                            
                                // Append image URLs for each student
                    $idcarddetails->each(function ($student) use($parent_app_url,$codeigniter_app_url) {
                        $concatprojecturl = $codeigniter_app_url."".'uploads/student_image/';
                        if(!empty($student->image_name)){
                            $student->image_url = $concatprojecturl."".$student->image_name;
                        }
                        else{
                            $student->image_url = '';
                            
                    }
                });
                    return response()->json([
                        'status'=> 200,
                        'message'=>'Id card details for this class.',
                        'data'=>$idcarddetails,
                        'success'=>true
                            ]);

        }
    
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

}
//Dev Name - Manish Kumar Sharma 25-02-2025
public function getziparchivestudentimages(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            
            $section_id = $request->input('section_id');
            $zip = new ZipArchive;
            $zipName = time() . ".zip";
            $imageAdded = false; 
            
            $studentDetails = DB::table('confirmation_idcard')
                            ->join('student', 'student.parent_id', '=', 'confirmation_idcard.parent_id')
                            ->join('class', 'student.class_id', '=', 'class.class_id')
                            ->join('section', 'student.section_id', '=', 'section.section_id')
                            ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
                            ->select(
                                'confirmation_idcard.*',
                                'student.first_name',
                                'student.mid_name',
                                'student.last_name',
                                'student.roll_no',
                                'student.image_name',
                                'student.reg_no',
                                'student.permant_add',
                                'student.blood_group',
                                'student.dob',
                                'student.house',
                                'student.student_id',
                                'parent.f_mobile',
                                'parent.m_mobile',
                                'class.name as class_name',
                                'section.name as sec_name'
                            )
                            ->where('student.section_id', $section_id)
                            ->where('confirmation_idcard.confirm', 'Y')
                            ->where('student.IsDelete', 'N')
                            ->orderBy('student.roll_no')
                            ->get();

                            $globalVariables = App::make('global_variables');
                                        $parent_app_url = $globalVariables['parent_app_url'];
                                        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
                                    
                                        // Append image URLs for each student
                            $studentDetails->each(function ($student) use($parent_app_url,$codeigniter_app_url) {
                                $concatprojecturl = $codeigniter_app_url."".'uploads/student_image/';
                                if(!empty($student->image_name)){
                                    $student->image_url = $concatprojecturl."".$student->image_name;
                                }
                                else{
                                    $student->image_url = '';
                                    
                            }
                            });
                            $zip->open(public_path($zipName), ZipArchive::CREATE);
                            foreach ($studentDetails as $url) {
                                if(!empty($url->image_url)){
                                $fileContent = @file_get_contents($url->image_url);
                                if ($fileContent) {
                                    $fileName = basename($url->image_url);
                                    $zip->addFromString($fileName, $fileContent);
                                    $imageAdded = true;
                                } else {
                                    \Log::warning("File could not be downloaded: " . $url->image_url);
                                }
                              }
                            }
                            if (!$imageAdded) {
                                $zip->addFromString('nofilesfound.txt', ''); // Optionally add a dummy file if you want to keep the ZIP non-empty
                            }
                            
                            $zip->close();

                            $classname = DB::table('class')
                                            ->join('section','section.class_id','=','class.class_id')
                                            ->where('section.section_id',$section_id)
                                            ->select('section.name as sectionname','class.name as classname')
                                            ->first();
                                        
                            $zipFileName = $classname->classname . '-' . $classname->sectionname . '.zip';
                            return response()->download($zipName, $zipFileName)
                                ->deleteFileAfterSend(true);
                            }
            
                            else{
                                return response()->json([
                                    'status'=> 401,
                                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                                    'data' =>$user->role_id,
                                    'success'=>false
                                        ]);
                                }
                    
                            }
                            catch (Exception $e) {
                            \Log::error($e); 
                            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
                            }
           
}


public function fieldsForTimetable(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
         $class_id = $request->input('class_id'); 
         $section_id=$request->input('section_id'); 
         $lecturesPerWeek = $request->input('lectures_per_week', 0); 
         $saturdayLectures = $request->input('saturday_lectures', 0); 
 
        $existingtimetable = DB::table('timetable')
                                ->where('class_id',$class_id)
                                ->where('section_id',$section_id)
                                ->where('academic_yr',$customClaims)
                                ->first();
        if($existingtimetable){
            return response()->json([
                'status'=> 400,
                'message'=>'Timetable already created for this class',
                'success'=>false
                ]);

        }
        $fields = [];
 
          // Generate Monday to Friday fields
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($daysOfWeek as $day) {
            
            $fields[$day] = [];
            for ($i = 1; $i <= $lecturesPerWeek; $i++) {
                $fields[$day][] = [
                    'subject' =>  [],
                ];
            }
        }

        for ($i = 1; $i <= $lecturesPerWeek; $i++) {
            $fields['Time-In'][] = [
                'Time In' => '',
            ];
        }
        for ($i = 1; $i <= $lecturesPerWeek; $i++) {
            $fields['Time-Out'][] = [
                'Time Out' => '',
            ];
        }
 
         for ($i = 1; $i <= $saturdayLectures; $i++) {
             $fields['Saturday'][] = [
                 'subject' => [],
             ];
         }
         for ($i = 1; $i <= $saturdayLectures; $i++) {
            $fields['Sat Time In'][] = [
                'Time In' => '',
            ];
        }
        for ($i = 1; $i <= $saturdayLectures; $i++) {
            $fields['Sat Time Out'][] = [
                'Time Out' => '',
            ];
        }
 
         return response()->json([
                'status'=> 200,
                'message'=>'Fields for these lectures.',
                'data'=>$fields,
                'success'=>true
                ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
  }


  public function getSubjectTimetable(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $class_id = $request->input('class_id');
            $section_id = $request->input('section_id');

            $subjects = DB::table('subject')
                            ->select('subject_master.sm_id', 'subject_master.name')
                            ->distinct()
                            ->join('subject_master', 'subject_master.sm_id', '=', 'subject.sm_id')
                            ->where('subject.class_id', $class_id)
                            ->where('subject.section_id', $section_id)
                            ->where('subject.academic_yr', $customClaims)
                            ->orderBy('subject.class_id', 'asc')
                            ->orderBy('subject.section_id', 'asc')
                            ->orderBy('subject_master.name', 'asc')
                            ->get();

                            return response()->json([
                                'status'=> 200,
                                'message'=>'Subject for this class.',
                                'data'=>$subjects,
                                'success'=>true
                                    ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

  }

  public function deleteTimetable($class_id,$section_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
             
            $deletetimetable =  DB::table('timetable')
                                    ->where('class_id', $class_id)
                                    ->where('section_id', $section_id)
                                    ->where('academic_yr', $customClaims)
                                    ->delete();

                    return response()->json([
                        'status'=> 200,
                        'message'=>'Timetable Deleted.',
                        'success'=>true
                            ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
  }

  public function getTimetableForClass($class_id,$section_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
              
            $gettimetable = DB::table('timetable')
                                ->where('class_id', $class_id)
                                ->where('section_id', $section_id)
                                ->where('academic_yr', '2024-2025')
                                ->orderBy('period_no')
                                ->get();

            // Check if timetable data exists
            if ($gettimetable->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No timetable found for the provided class and section.',
                    'success' => false,
                ], 404);
            }

            // Process the timetable data and fetch teacher names for each subject
            $result = [];
            foreach ($gettimetable as $row) {
                $subject_name = $row->subject;
                $teacher_names = $this->get_teacher_name_by_subname($subject_name, $class_id, $section_id);

                // Assuming that get_teacher_name_by_subname returns an array with teacher names
                $teacher_name = '';
                if (count($teacher_names) > 0) {
                    $teacher_name = ucfirst($teacher_names[0]['t_name']); // Take the first teacher's name
                }

                // Add to the result array
                $result[] = [
                    'period_no' => $row->period_no,
                    'subject' => $subject_name,
                    'teacher' => $teacher_name
                ];
            }

            // Return the timetable data
            return response()->json([
                'status' => 200,
                'message' => 'Timetable fetched successfully',
                'data' => $result,
                'success' => true
            ], 200);
             

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

  }

//   public function getStudentexcelIdCard(Request $request){
//             $idcarddetails = DB::table('confirmation_idcard')
//             ->join('student', 'student.parent_id', '=', 'confirmation_idcard.parent_id')
//             ->join('class', 'student.class_id', '=', 'class.class_id')
//             ->join('section', 'student.section_id', '=', 'section.section_id')
//             ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
//             ->select(
//                 'confirmation_idcard.*',
//                 'student.first_name',
//                 'student.mid_name',
//                 'student.last_name',
//                 'student.roll_no',
//                 'student.image_name',
//                 'student.reg_no',
//                 'student.permant_add',
//                 'student.blood_group',
//                 'student.dob',
//                 'student.student_id',
//                 'parent.f_mobile',
//                 'parent.m_mobile',
//                 'class.name as class_name',
//                 'section.name as sec_name',
//                 DB::raw("
//                     CASE
//                         WHEN student.house = 'E' THEN 'Emerald'
//                         WHEN student.house = 'R' THEN 'Ruby'
//                         WHEN student.house = 'S' THEN 'Sapphire'
//                         WHEN student.house = 'D' THEN 'Diamond'
//                         ELSE 'Unknown House'
//                     END as house
//                 ")
//             )
//             ->where('student.section_id', '471')
//             ->where('confirmation_idcard.confirm', 'Y')
//             ->where('student.IsDelete', 'N')
//             ->orderBy('student.roll_no')
//             ->get();

//         // Append image URLs for each student
//         $globalVariables = App::make('global_variables');
//         $parent_app_url = $globalVariables['parent_app_url'];
//         $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

//         $idcarddetails->each(function ($student) use($parent_app_url, $codeigniter_app_url) {
//         $concatprojecturl = $codeigniter_app_url . 'uploads/student_image/';
//         if (!empty($student->image_name)) {
//         $student->image_url = $concatprojecturl . $student->image_name;
//         } else {
//         $student->image_url = '';
//         }
//         });

//         // Export to Excel
//         return Excel::download(new IdCardExport($idcarddetails), 'idcarddetails_with_images.xlsx');

//   }

public function getTeacherIdCard(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $globalVariables = App::make('global_variables');
            $parent_app_url = $globalVariables['parent_app_url'];
            $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
            $staffdata = DB::table('teacher')
                            ->orderBy('teacher_id', 'asc')
                            ->where('isDelete','N')
                            ->get()
                            ->map(function ($staff)use($parent_app_url,$codeigniter_app_url){
                                $concatprojecturl = $codeigniter_app_url."".'uploads/teacher_image/';
                                if ($staff->teacher_image_name) {
                                    $staff->teacher_image_url = $concatprojecturl.""."$staff->teacher_image_name";
                                } else {
                                    $staff->teacher_image_url = null; 
                                }
                                return $staff;
                            });
                            return response()->json([
                                'status'=> 200,
                                'message'=>'Id card details for the Staffs.',
                                'data'=>$staffdata,
                                'success'=>true
                                    ]);
             

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

}

 public function getTeacherzipimages(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                    $zip = new ZipArchive;
                    $zipName = time() . ".zip";
                    $imageAdded = false; 
                    $globalVariables = App::make('global_variables');
                    $parent_app_url = $globalVariables['parent_app_url'];
                    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
                    $staffdata = DB::table('teacher')
                                ->orderBy('teacher_id', 'asc')
                                ->where('isDelete','N')
                                ->get()
                                ->map(function ($staff)use($parent_app_url,$codeigniter_app_url){
                                    $concatprojecturl = $codeigniter_app_url."".'uploads/teacher_image/';
                                    if ($staff->teacher_image_name) {
                                        $staff->teacher_image_url = $concatprojecturl.""."$staff->teacher_image_name";
                                    } else {
                                        $staff->teacher_image_url = ''; 
                                    }
                                    return $staff;
                                });
                    $zip->open(public_path($zipName), ZipArchive::CREATE);
                            foreach ($staffdata as $url) {
                                if(!empty($url->teacher_image_url)){
                                $fileContent = @file_get_contents($url->teacher_image_url);
                                if ($fileContent) {
                                    $fileName = basename($url->teacher_image_url);
                                    $zip->addFromString($fileName, $fileContent);
                                    $imageAdded = true;
                                } else {
                                    \Log::warning("File could not be downloaded: " . $url->teacher_image_url);
                                }
                              }
                            }
                            if (!$imageAdded) {
                                $zip->addFromString('nofilesfound.txt', ''); // Optionally add a dummy file if you want to keep the ZIP non-empty
                            }
                            
                            $zip->close();
                            return response()->download($zipName, 'All_teachers.zip')
                                ->deleteFileAfterSend(true);
                            }
                            else{
                                return response()->json([
                                    'status'=> 401,
                                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                                    'data' =>$user->role_id,
                                    'success'=>false
                                        ]);
                                }
                            
                            }
                            catch (Exception $e) {
                            \Log::error($e); 
                            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
                            }

 }
   //Stationery Dev Name- Manish Kumar Sharma 26-02-2025
   public function saveStationery(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
       $data = [
           'name'=>$request->name
       ];
        $savestationery= DB::table('stationery_master')->insert($data);
        return response()->json([
            'status'=> 200,
            'message'=>'Stationery Saved Successfully',
            'data'=>$savestationery,
            'success'=>true
                ]);
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

   }
   //Stationery Dev Name- Manish Kumar Sharma 26-02-2025
   public function getStationeryList(){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
       
        $stationerylist = DB::table('stationery_master')->get();
        return response()->json([
            'status'=> 200,
            'message'=>'Stationery List.',
            'data'=>$stationerylist,
            'success'=>true
                ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

   }
   //Stationery Dev Name- Manish Kumar Sharma 26-02-2025
   public function updateStationery(Request $request,$stationery_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
        $updateStationery=DB::table('stationery_master')
                            ->where('stationery_id', $stationery_id)  // Find the user with id = 1
                            ->update(['name' => $request->name]);
                    return response()->json([
                        'status'=> 200,
                        'message'=>'Stationery Updated Successfully.',
                        'data'=>$updateStationery,
                        'success'=>true
                            ]);
                }
                else{
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the Deleting of Data',
                        'data' =>$user->role_id,
                        'success'=>false
                            ]);
                    }
        
                }
                catch (Exception $e) {
                \Log::error($e); 
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
                }       

   }
   //Stationery Dev Name- Manish Kumar Sharma 26-02-2025
   public function deleteStationery(Request $request,$stationery_id){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
        DB::table('stationery_master')->where('stationery_id',$stationery_id)->delete();
        return response()->json([
            'status'=> 200,
            'message'=>'Stationery Deleted Successfully.',
            'success'=>true
                ]);
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

   }

   //Timetable Dev Name - Manish Kumar Sharma 27-02-2025
   public function saveClassTimetable(Request $request){
    try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
            $num_lec = $request->input('num_lec');
            $data = [];
    
            for ($k = 1; $k <= $num_lec; $k++) {
                $data[] = [
                    'class_id' => $request->input('class_id'),
                    'section_id' => $request->input('section_id'),
                    'monday' => $this->getSubjectss($request->input('mon' . $k)),
                    'tuesday' => $this->getSubjectss($request->input('tue' . $k)),
                    'wednesday' => $this->getSubjectss($request->input('wed' . $k)),
                    'thursday' => $this->getSubjectss($request->input('thu' . $k)),
                    'friday' => $this->getSubjectss($request->input('fri' . $k)),
                    'saturday' => $this->getSubjectss($request->input('sat' . $k)),
                    'time_in' => $request->input('time_in' . $k),
                    'time_out' => $request->input('time_out' . $k),
                    'sat_in' => $request->input('sat_in' . $k),
                    'sat_out' => $request->input('sat_out' . $k),
                    'period_no' => $k,
                    'academic_yr' => $customClaims,
                    'date' => now(),
                ]; 
            }
            
            
            DB::table('timetable')->insert($data);
    
            return response()->json([
                'status' =>200,
                'message' => 'Timetable created successfully!',
                'success'=>true
            ]);

        }
        else{
            return response()->json([
                'status'=> 401,
                'message'=>'This User Doesnot have Permission for the Deleting of Data',
                'data' =>$user->role_id,
                'success'=>false
                    ]);
            }

        }
        catch (Exception $e) {
        \Log::error($e); 
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }     
   }
   //Timetable Dev Name - Manish Kumar Sharma 27-02-2025
   private function getSubjectss($subjects)
    {
        return implode('/', (array)$subjects);
    }
    //Timetable Dev Name - Manish Kumar Sharma 27-02-2025
    public function viewclassTimetable(Request $request,$class_id,$section_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                  $timetables = DB::table('timetable')
                                    ->where('class_id', $class_id)
                                    ->where('section_id', $section_id)
                                    ->where('academic_yr', $customClaims)
                                    ->orderBy('t_id')
                                    ->get();
                    
                                           
                    if(count($timetables)==0){
                        
                        return response()->json([
                            'status'=> 400,
                            'message'=>'Timetable is not created for this class.',
                            'success'=>false
                            ]);
            
                    }
                //   dd($timetable);
                 // Initialize an array to hold data for each day
            $monday = [];
            $tuesday = [];
            $wednesday = [];
            $thursday = [];
            $friday = [];
            $saturday = [];

            // Iterate over the timetables and separate them by day
            foreach ($timetables as $timetable) {
                // For Monday
                if ($timetable->monday) {
                    $monday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no'=>$timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject' => $timetable->monday,
                        'teacher' => $this->getTeacherBySubject($timetable->monday, $class_id, $section_id),
                    ];
                }

                // For Tuesday
                if ($timetable->tuesday) {
                    $tuesday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no'=>$timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject' => $timetable->tuesday,
                        'teacher' => $this->getTeacherBySubject($timetable->tuesday, $class_id, $section_id),
                    ];
                }

                // For Wednesday
                if ($timetable->wednesday) {
                    $wednesday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no'=>$timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject' => $timetable->wednesday,
                        'teacher' => $this->getTeacherBySubject($timetable->wednesday, $class_id, $section_id),
                    ];
                }

                // For Thursday
                if ($timetable->thursday) {
                    $thursday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no'=>$timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject' => $timetable->thursday,
                        'teacher' => $this->getTeacherBySubject($timetable->thursday, $class_id, $section_id),
                    ];
                }

                // For Friday
                if ($timetable->friday) {
                    $friday[] = [
                        'time_in' => $timetable->time_in,
                        'period_no'=>$timetable->period_no,
                        'time_out' => $timetable->time_out,
                        'subject' => $timetable->friday,
                        'teacher' => $this->getTeacherBySubject($timetable->friday, $class_id, $section_id),
                    ];
                }

                // For Saturday
                if ($timetable->saturday) {
                    $saturday[] = [
                        'time_in' => $timetable->sat_in,
                        'period_no'=>$timetable->period_no,
                        'time_out' => $timetable->sat_out,
                        'subject' => $timetable->saturday,
                        'teacher' => $this->getTeacherBySubject($timetable->saturday, $class_id, $section_id),
                    ];
                }
            }
            $weeklySchedule = [
                'Monday' => $monday,
                'Tuesday' => $tuesday,
                'Wednesday' => $wednesday,
                'Thursday' => $thursday,
                'Friday' => $friday,
                'Saturday' => $saturday,
            ];
            
                  return response()->json([
                    'status' =>200,
                    'data'=>$weeklySchedule,
                    'message' => 'View Timetable!',
                    'success'=>true
                ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            } 

    }
    //Timetable Dev Name - Manish Kumar Sharma 27-02-2025
    public function getTeacherBySubject( $subname,$class_id, $section_id)
        {
            // dd($class_id,$section_id,$subname);
            $teachers = DB::table('subject')
                ->select('teacher.name as t_name')
                ->join('subject_master', 'subject_master.sm_id', '=', 'subject.sm_id')
                ->join('teacher', 'subject.teacher_id', '=', 'teacher.teacher_id')
                ->where('subject.class_id', $class_id)
                ->where('subject.section_id', $section_id)
                ->where('subject_master.name', 'like', "%$subname%") // using `like` with `%` to match partial name
                ->get();
            //    dd($teachers);
            return $teachers;
        }

        public function updateClasstimetable(Request $request){
            try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                    $class_id = $request->input('class_id');
                    $section_id = $request->input('section_id');
                    $deletetimetable =  DB::table('timetable')
                                            ->where('class_id', $class_id)
                                            ->where('section_id', $section_id)
                                            ->where('academic_yr', $customClaims)
                                            ->delete();

                    $num_lec = $request->input('num_lec');
                    $data = [];
            
                    for ($k = 1; $k <= $num_lec; $k++) {
                        $data[] = [
                            'class_id' => $request->input('class_id'),
                            'section_id' => $request->input('section_id'),
                            'monday' => $this->getSubjectss($request->input('mon' . $k)),
                            'tuesday' => $this->getSubjectss($request->input('tue' . $k)),
                            'wednesday' => $this->getSubjectss($request->input('wed' . $k)),
                            'thursday' => $this->getSubjectss($request->input('thu' . $k)),
                            'friday' => $this->getSubjectss($request->input('fri' . $k)),
                            'saturday' => $this->getSubjectss($request->input('sat' . $k)),
                            'time_in' => $request->input('time_in' . $k),
                            'time_out' => $request->input('time_out' . $k),
                            'sat_in' => $request->input('sat_in' . $k),
                            'sat_out' => $request->input('sat_out' . $k),
                            'period_no' => $k,
                            'academic_yr' => $customClaims,
                            'date' => now(),
                        ]; 
                    }
                    
                    
                    DB::table('timetable')->insert($data);
                    return response([
                        'status'=>200,
                        'message'=>'Timetable Updated Successfully.',
                        'success'=>true
                    ]);


                }
                else{
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the Deleting of Data',
                        'data' =>$user->role_id,
                        'success'=>false
                            ]);
                    }
        
                }
                catch (Exception $e) {
                \Log::error($e); 
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
                } 
    

        }

        //Pending Student Id Card Dev Name - Manish Kumar Sharma 28-02-2025
        public function getPendingStudentIdCard(Request $request){
            try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                    // dd($request->all());
                    $class_id = $request->input('class_id');
                    $section_id=$request->input('section_id');
                    $students = DB::table('student')
                                ->join('class', 'student.class_id', '=', 'class.class_id')
                                ->join('section', 'student.section_id', '=', 'section.section_id')
                                ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
                                ->leftJoin('confirmation_idcard', function($join) {
                                    $join->on('student.parent_id', '=', 'confirmation_idcard.parent_id')
                                        ->where('confirmation_idcard.confirm', '=', 'Y');
                                })
                                ->where('student.isDelete', 'N')
                                ->where('student.class_id', $class_id)
                                ->where('student.section_id', $section_id)
                                ->whereNull('confirmation_idcard.parent_id')  // This ensures the parent_id is not in the confirmation_idcard table
                                ->select(
                                    'student.first_name', 
                                    'student.mid_name', 
                                    'student.last_name', 
                                    'student.roll_no', 
                                    'student.image_name', 
                                    'student.reg_no', 
                                    'student.permant_add', 
                                    'student.blood_group', 
                                    'student.house', 
                                    'student.dob', 
                                    'student.house as student_house',
                                    'class.name as class_name', 
                                    'section.name as sec_name', 
                                    'parent.parent_id', 
                                    'parent.father_name', 
                                    'parent.f_mobile', 
                                    'parent.m_mobile'
                                )
                                ->orderBy('student.roll_no')
                                ->get();

                                $result = [];

                                foreach ($students as $student) {

                                    $siblings = Student::where('parent_id', $student->parent_id)
                                        ->where('IsDelete', 'N')
                                        ->where('academic_yr', $customClaims)
                                        ->get();
                                        
                        
                                    $sibling_data = [];
                                    foreach ($siblings as $sibling) {
                                        $sibling_data[] = [
                                            'student_id' => $sibling->student_id,
                                            'first_name' => $sibling->first_name,
                                            'mid_name' => $sibling->mid_name,
                                            'last_name' => $sibling->last_name,
                                            'roll_no' => $sibling->roll_no,
                                            'class-secname' => $this->getClassOfStudent($sibling->student_id),
                                            'dob' => $sibling->dob,
                                            'permant_add' => $sibling->permant_add,
                                            'blood_group' => $sibling->blood_group,
                                            'house' => $sibling->house,
                                        ];
                                    }
                               
                                    $result[] = [
                                        'parent' => [
                                            'parent_id'=> $student->parent_id,
                                            'father_name' => $student->father_name,
                                            'f_mobile' => $student->f_mobile,
                                            'm_mobile' => $student->m_mobile,
                                            'siblings' => $sibling_data
                                        ],
                                        // 'siblings' => $sibling_data
                                    ];
                                }
                        
                                return response([
                                    'status'=>200,
                                    'data'=>$result,
                                    'message'=>'Pending Student Id Card List.',
                                    'success'=>true
                                ]);
                                
                        


                }
                else{
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the Deleting of Data',
                        'data' =>$user->role_id,
                        'success'=>false
                            ]);
                    }
        
                }
                catch (Exception $e) {
                \Log::error($e); 
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
                } 

        }
        //Pending Student Id Card Dev Name - Manish Kumar Sharma 28-02-2025
        private function getClassOfStudent($student_id)
        {
            $result = DB::table('student as s')
                        ->join('class as c', 's.class_id', '=', 'c.class_id')
                        ->join('section as sc', 's.section_id', '=', 'sc.section_id')
                        ->where('s.student_id', $student_id)
                        ->select(DB::raw("CONCAT(c.name, '-', sc.name) as class"))
                        ->first();
            return $result->class;
        }
        //Pending Student Id Card Dev Name - Manish Kumar Sharma 28-02-2025
        public function updatePendingStudentIdCard(Request $request){
            try{
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                    
                    foreach ($request->parents as $parent) {
                        // Update Parent Data
                        $this->updateParentData($parent);
                
                        // Update Student Data
                        $this->updateStudentData($parent);
                
                        // Handle ConfirmationIdCard Data
                        $data = [
                            'confirm' => 'Y',
                            'parent_id' => $parent['parent_id'],
                            'academic_yr' => $customClaims
                        ];
            
                        $existingConfirmation = DB::table('confirmation_idcard')->where('parent_id', $parent['parent_id'])->where('academic_yr',$customClaims)->first();
            
                        if ($existingConfirmation) {
                            DB::table('confirmation_idcard')->where('parent_id', $parent['parent_id'])->where('academic_yr',$customClaims)->update($data);
                        } else {
                            DB::table('confirmation_idcard')->insert($data);
                        }
                    }

                    
                    return response()->json([
                        'status'=>200,
                        'message' => 'Id card details saved successfully!',
                        'success' =>true
                    ]);
            
                }
                else{
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the Deleting of Data',
                        'data' =>$user->role_id,
                        'success'=>false
                            ]);
                    }
        
                }
                catch (Exception $e) {
                \Log::error($e); 
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
                } 

        }

        //Pending Student Id Card Dev Name - Manish Kumar Sharma 28-02-2025
        private function updateParentData($parent)
        {
            DB::table('parent')->where('parent_id', $parent['parent_id'])
                                ->update([
                                    'f_mobile' => $parent['f_mobile'],
                                    'm_mobile' => $parent['m_mobile']
                                ]);
        }
        //Pending Student Id Card Dev Name - Manish Kumar Sharma 28-02-2025
        private function updateStudentData($parent)
        {
            foreach ($parent['students'] as $student) {
                Student::where('student_id', $student['student_id'])
                    ->update([
                        'permant_add' => $student['permant_add'],
                        'blood_group' => $student['blood_group'],
                        'house' => $student['house']
                    ]);
            }
        }
        //Student Id Card Dev Name - Manish Kumar Sharma 28-02-2025
        public function getStudentDataWithParentData(Request $request){
            try{               
                $user = $this->authenticateUser();
                $customClaims = JWTAuth::getPayload()->get('academic_year');
                if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                    $globalVariables = App::make('global_variables');
                    $parent_app_url = $globalVariables['parent_app_url'];
                    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
                    $parent_id = $request->input('parent_id');
                    $studentsdetails = DB::table('student')
                                    ->join('class','class.class_id','=','student.class_id')
                                    ->join('section','section.section_id','=','student.section_id')
                                    ->select('student.*','class.name as classname','section.name as sectionname')
                                    ->where('parent_id', $parent_id)
                                    ->where('IsDelete', 'N')
                                    ->where('student.academic_yr', $customClaims)
                                    ->get();
                                    $globalVariables = App::make('global_variables');
                                    $parent_app_url = $globalVariables['parent_app_url'];
                                    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
                            
                                // Append image URLs for each student
                    $studentsdetails->each(function ($student) use($parent_app_url,$codeigniter_app_url) {
                        $concatprojecturl = $codeigniter_app_url."".'uploads/student_image/';
                        if(!empty($student->image_name)){
                            $student->image_url = $concatprojecturl."".$student->image_name;
                        }
                        else{
                            $student->image_url = '';
                            
                    }
                });

                $parentdetails =  DB::table('parent')
                                    ->where('parent_id', $parent_id)
                                    ->get()
                                    ->map(function ($staff)use($parent_app_url,$codeigniter_app_url){
                                        $concatprojecturl = $codeigniter_app_url."".'uploads/parent_image/';
                                        if ($staff->father_image_name) {
                                            $staff->father_image_url = $concatprojecturl.""."$staff->father_image_name";
                                        } else {
                                            $staff->father_image_url = ''; 
                                        }
                                        if ($staff->mother_image_name) {
                                            $staff->mother_image_url = $concatprojecturl.""."$staff->mother_image_name";
                                        } else {
                                            $staff->mother_image_url = ''; 
                                        }
                                        return $staff;
                                    });

                                    // dd($parentdetails);
                $guardiandetails = DB::table('student')
                                     ->where('parent_id', $parent_id)
                                     ->where('IsDelete', 'N')
                                     ->where('academic_yr', $customClaims)
                                     ->select('guardian_name','guardian_add','guardian_mobile','relation','guardian_image_name')
                                     ->first();

                    if ($guardiandetails) {
                        $concatprojecturl = $codeigniter_app_url . 'uploads/parent_image/';
                        $guardiandetails->guardian_image_url = $guardiandetails->guardian_image_name 
                            ? $concatprojecturl . $guardiandetails->guardian_image_name
                            : ''; // If guardian_image_name exists, append the URL, otherwise set an empty string.
                    }
                    

                    $response = [
                        'students' => $studentsdetails,
                        'parents' => $parentdetails,
                        'guardian' => $guardiandetails,
                    ];


                

                return response()->json([
                    'status'=>200,
                    'data' => $response,
                    'message' => 'Fetching the data for the Id Card of Parent,Student,Guardian!',
                    'success' =>true
                ]);


                                    

                }
                else{
                    return response()->json([
                        'status'=> 401,
                        'message'=>'This User Doesnot have Permission for the Deleting of Data',
                        'data' =>$user->role_id,
                        'success'=>false
                            ]);
                    }
        
                }
                catch (Exception $e) {
                \Log::error($e); 
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
                } 

        }



        

}