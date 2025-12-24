<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;

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
        
        $pendingAmount = 0.0; // Placeholder for pending amount logic
        $totalNumberOfDefaulters = 0; // Placeholder for defaulter count logic

        // have to find out the pending amount and total number of defaulters of the classes the teacher teach logic here
        // skipping this for now

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
            ]
        ]);
    }

    public function ticketsList($teacher_id)
    {
        $user = $this->authenticateUser();
        $reg_id = JWTAuth::getPayload()->get('reg_id');
        $role = $user->role_id;
        // get ticket assigned to the teacher for today
        $tickets = DB::table('ticket')
            ->select('ticket.*', 'service_type.service_name','student.first_name','student.mid_name','student.last_name')
            ->join('service_type', 'service_type.service_id', '=', 'ticket.service_id')
            ->join('student', 'student.student_id', '=', 'ticket.student_id')
            ->join('class_teachers', function ($join) {
                $join->on('class_teachers.class_id', '=', 'student.class_id')
                        ->on('class_teachers.section_id', '=', 'student.section_id');
            })
            ->where('service_type.role_id', $role)
            ->where('class_teachers.teacher_id', $reg_id)
            ->where('ticket.raised_on' , '=', date('Y-m-d'))
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
                    a.t_id
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
