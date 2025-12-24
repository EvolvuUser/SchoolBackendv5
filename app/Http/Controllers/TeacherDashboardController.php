<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        // Get attendance data
        /*
            Data: 
                1) total number of students present today in all classes the teacher is assigned to
                
                2) total number of students the teacher is assigned to
                => get all students the class teacher teaches
        */
        // get classes data 
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $classdata = DB::table('subject')
                            ->join('class', 'class.class_id', '=', 'subject.class_id')
                            ->join('section', 'section.section_id', '=', 'subject.section_id')
                            ->join('teacher', 'teacher.teacher_id', '=', 'subject.teacher_id')
                            ->leftJoin('class_teachers', function ($join) use($teacher_id) {
                                $join->on('class_teachers.class_id', '=', 'subject.class_id')
                                     ->on('class_teachers.section_id', '=', 'subject.section_id')
                                     ->where('class_teachers.teacher_id', '=', $teacher_id);
                            })
                            ->where('subject.academic_yr', $customClaims)
                            ->where('subject.teacher_id', $teacher_id)
                            ->where(function ($query) use ($teacher_id) {
                                $query->where('subject.teacher_id', $teacher_id)
                                      ->orWhere('class_teachers.teacher_id', $teacher_id);
                            })
                            ->distinct()
                            ->select(
                                'subject.class_id',
                                'section.section_id',
                                'class.name as classname',
                                'section.name as sectionname',
                                'teacher.name as teachername',
                                'teacher.teacher_id',
                                'class.class_id',
                                DB::raw('CASE WHEN class_teachers.teacher_id IS NOT NULL THEN 1 ELSE 0 END as is_class_teacher')
                            )
                            // ->orderBy('subject.class_id')
                            ->orderBy('class.name')
                            ->orderBy('section.name')
                            ->get();
        // get total studnets in this classes
        $total_students = 0;
        foreach($classdata as $classinfo){
            $student_count = DB::table('student')
                                ->join('enroll', 'enroll.student_id', '=', 'student.student_id')
                                ->where('enroll.class_id', $classinfo->class_id)
                                ->where('enroll.section_id', $classinfo->section_id)
                                ->where('enroll.academic_yr', $customClaims)
                                ->count();
            $total_students += $student_count;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_students' => $total_students,
            ]
        ]);
    }
}
