<?php

namespace App\Services\ReportCard;

use Illuminate\Support\Facades\DB;

class Class9To10BulkReportCardDataBuilder
{
    public function build($classId, $sectionId, $academicYr): array
    {
        $students = collect(get_students($classId, $sectionId, $academicYr))->values();
        $studentIds = $students->pluck('student_id')->filter()->values()->all();

        $examList = collect(get_published_exams_class9n10($classId, $sectionId, $academicYr))->values();
        $examIds = $examList->pluck('exam_id')->all();

        $subjectsByStudent = [];
        if ($studentIds !== []) {
            $subjectRows = DB::table('student_marks as a')
                ->join('subjects_on_report_card_master as b', 'a.subject_id', '=', 'b.sub_rc_master_id')
                ->join('subjects_on_report_card as c', 'b.sub_rc_master_id', '=', 'c.sub_rc_master_id')
                ->select('a.student_id', 'a.subject_id as sub_rc_master_id', 'b.name', 'b.sequence')
                ->whereIn('a.student_id', $studentIds)
                ->where('c.subject_type', '<>', 'Co-Scholastic')
                ->where('c.class_id', $classId)
                ->distinct()
                ->orderBy('b.sequence')
                ->get();

            foreach ($subjectRows as $row) {
                $subjectsByStudent[$row->student_id][] = (object) [
                    'sub_rc_master_id' => $row->sub_rc_master_id,
                    'name' => $row->name,
                ];
            }
        }

        $subjectIds = collect($subjectsByStudent)
            ->flatten(1)
            ->pluck('sub_rc_master_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $marksByStudent = [];
        if ($studentIds !== [] && $examIds !== [] && $subjectIds !== []) {
            $studentMarks = DB::table('student_marks')
                ->select(
                    'student_id',
                    'exam_id',
                    'subject_id',
                    'reportcard_marks',
                    'reportcard_highest_marks',
                    'total_marks',
                    'highest_total_marks'
                )
                ->whereIn('student_id', $studentIds)
                ->whereIn('exam_id', $examIds)
                ->whereIn('subject_id', $subjectIds)
                ->where('academic_yr', $academicYr)
                ->where('publish', 'Y')
                ->get();

            foreach ($studentMarks as $mark) {
                $marksByStudent[$mark->student_id][$mark->exam_id][$mark->subject_id] = [
                    'reportcard_marks' => $this->decodeJsonArray($mark->reportcard_marks),
                    'reportcard_highest_marks' => $this->decodeJsonArray($mark->reportcard_highest_marks),
                    'total_marks' => $mark->total_marks,
                    'highest_total_marks' => $mark->highest_total_marks,
                ];
            }
        }

        return [
            'students' => $students->all(),
            'exam_list' => $examList->all(),
            'subjects_by_student' => $subjectsByStudent,
            'marks_by_student' => $marksByStudent,
            'academic_yr' => $academicYr,
        ];
    }

    private function decodeJsonArray($value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
