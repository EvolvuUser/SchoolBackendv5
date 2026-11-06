<?php

namespace App\Services\ReportCard;

use Illuminate\Support\Facades\DB;

class HscsClass9To10BulkReportCardDataBuilder
{
    public function build($classId, $sectionId, string $academicYr, $studCount = null): array
    {
        $students = DB::table('student as s')
            ->leftJoin('parent as p', 's.parent_id', '=', 'p.parent_id')
            ->leftJoin('class as c', 's.class_id', '=', 'c.class_id')
            ->leftJoin('section as sec', 's.section_id', '=', 'sec.section_id')
            ->where('s.class_id', $classId)->where('s.section_id', $sectionId)
            ->where('s.academic_yr', $academicYr)->where('s.IsDelete', 'N')
            ->orderBy('s.roll_no')->orderBy('s.reg_no')->orderBy('s.student_id')
            ->select('s.*', 'p.father_name', 'c.name as class_name', 'sec.name as section_name')->get();

        if ($studCount !== null) {
            $remainder = $students->count() % 10;
            $offset = $remainder === (int) $studCount
                ? $students->count() - $remainder : max((int) $studCount - 10, 0);
            $students = $students->slice($offset, 10);
        }

        // For secondary classes, publication term_id stores the exam ID.
        $exams = DB::table('report_card_publish as p')
            ->join('exam as e', 'p.term_id', '=', 'e.exam_id')
            ->where('p.class_id', $classId)->where('p.section_id', $sectionId)
            ->where('p.publish', 'Y')->where('e.academic_yr', $academicYr)
            ->orderBy('e.start_date')->orderBy('e.exam_id')
            ->select('e.exam_id', 'e.name', 'e.start_date')->distinct()->get();
        $subjects = collect(get_scholastic_subject_alloted_to_class($classId, $academicYr));
        $highestMarks = DB::table('allot_mark_headings')
            ->where('class_id', $classId)->where('academic_yr', $academicYr)->where('sm_id', 1)
            ->whereIn('exam_id', $exams->pluck('exam_id'))
            ->select('exam_id')->selectRaw('SUM(highest_marks) as highest_marks')
            ->groupBy('exam_id')->pluck('highest_marks', 'exam_id')->all();

        $marksByStudent = [];
        if ($students->isNotEmpty() && $exams->isNotEmpty() && $subjects->isNotEmpty()) {
            $marks = DB::table('student_marks')->whereIn('student_id', $students->pluck('student_id'))
                ->whereIn('exam_id', $exams->pluck('exam_id'))
                ->whereIn('subject_id', $subjects->pluck('sub_rc_master_id'))
                ->where('academic_yr', $academicYr)->where('publish', 'Y')
                ->select('student_id', 'exam_id', 'subject_id', 'mark_obtained')->get();
            foreach ($marks as $mark) {
                // get_marks() in the single report uses the first matching published row.
                if (array_key_exists($mark->subject_id, $marksByStudent[$mark->student_id][$mark->exam_id] ?? [])) {
                    continue;
                }
                $values = json_decode($mark->mark_obtained ?? '', true);
                // The single HSCS report displays the last raw heading, not the converted reportcard_marks.
                $marksByStudent[$mark->student_id][$mark->exam_id][$mark->subject_id] =
                    is_array($values) && $values !== [] ? end($values) : 0;
            }
        }

        return [
            'students' => $students->map(fn ($student) => (array) $student)->values()->all(),
            'section_name' => $students->first()->section_name ?? '',
            'exam_list' => $exams->all(),
            'scholastic_subjects' => $subjects->all(),
            'highest_marks_by_exam' => $highestMarks,
            'marks_by_student' => $marksByStudent,
        ];
    }
}
