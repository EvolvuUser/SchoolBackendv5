<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use DB;

class HscController extends Controller
{
    public function getSubjectGroup()
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $subjectgroup = DB::table('subject_group')->get();
            return response()->json([
                'status' => 200,
                'data' => $subjectgroup,
                'message' => 'All Subject Groups',
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function getOptionalSubject()
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $optionalsubject = DB::table('subject_master')->where('subject_type', 'Optional')->get();
            return response()->json([
                'status' => 200,
                'data' => $optionalsubject,
                'message' => 'All Optional Subjects',
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function getSubjectStudentwise($class_id, $section_id)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $studentwisesubject = DB::table('student')
                ->leftJoin('subjects_higher_secondary_studentwise', 'student.student_id', '=', 'subjects_higher_secondary_studentwise.student_id')
                ->where('student.class_id', $class_id)
                ->where('student.section_id', $section_id)
                ->select(
                    'student.student_id',
                    DB::raw('COALESCE(subjects_higher_secondary_studentwise.sub_group_id, "") as sub_group_id'),
                    DB::raw('COALESCE(subjects_higher_secondary_studentwise.opt_subject_id, "") as opt_subject_id'),
                    'student.first_name', 'student.mid_name', 'student.last_name'
                )
                ->get();

            return response()->json([
                'status' => 200,
                'data' => $studentwisesubject,
                'message' => 'Studentwise Subjects',
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function saveSubjectforHsc(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $data = $request->all();
            foreach ($data as $item) {
                if ($item['sub_group_id'] === null || $item['opt_subject_id'] === null) {
                    $record = DB::table('subjects_higher_secondary_studentwise')->where('student_id', $item['student_id'])->delete();
                    continue;
                }

                $existingRecord = DB::table('subjects_higher_secondary_studentwise')->where('student_id', $item['student_id'])->exists();

                $dataToSave = [
                    'student_id' => $item['student_id'],
                    'sub_group_id' => $item['sub_group_id'],
                    'opt_subject_id' => $item['opt_subject_id'],
                    'academic_yr' => $customClaims,
                ];

                if ($existingRecord) {
                    DB::table('subjects_higher_secondary_studentwise')
                        ->where('student_id', $item['student_id'])
                        ->update($dataToSave);
                } else {
                    DB::table('subjects_higher_secondary_studentwise')->insert($dataToSave);
                }
            }

            // Return a response or success message
            return response()->json([
                'status' => 200,
                'message' => 'Subjects added for higher Secondary',
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
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
