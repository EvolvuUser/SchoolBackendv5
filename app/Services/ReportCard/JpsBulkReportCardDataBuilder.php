<?php

namespace App\Services\ReportCard;

use Illuminate\Support\Facades\DB;

class JpsBulkReportCardDataBuilder
{
    public function build($classId, $sectionId, string $academicYr, $studCount = null): array
    {
        $students = DB::table('student as s')
            ->leftJoin('parent as p', 's.parent_id', '=', 'p.parent_id')
            ->where('s.class_id', $classId)->where('s.section_id', $sectionId)
            ->where('s.academic_yr', $academicYr)->where('s.IsDelete', 'N')
            ->orderBy('s.roll_no')->orderBy('s.reg_no')->orderBy('s.student_id')
            ->select('s.*', 'p.father_name')->get();

        if ($studCount !== null) {
            $remainder = $students->count() % 10;
            $offset = $remainder === (int) $studCount
                ? $students->count() - $remainder : max((int) $studCount - 10, 0);
            $students = $students->slice($offset, 10);
        }
        $students = $students->map(fn ($student) => (array) $student)->values()->all();
        if ($students === []) {
            return ['students' => []];
        }
        $studentIds = array_column($students, 'student_id');

        $className = DB::table('class')->where('class_id', $classId)->value('name');
        $sectionName = DB::table('section')->where('section_id', $sectionId)->value('name');
        $published = DB::table('report_card_publish as p')
            ->join('term as t', 'p.term_id', '=', 't.term_id')
            ->where('p.class_id', $classId)->where('p.section_id', $sectionId)
            ->where('p.publish', 'Y')->orderBy('p.term_id')
            ->select('t.term_id', 't.name', 'p.reopen_date')->get();
        $terms = $published->all();
        $termIds = $published->pluck('term_id')->all();
        $exams = DB::table('allot_mark_headings as a')
            ->join('exam as e', 'a.exam_id', '=', 'e.exam_id')
            ->where('a.class_id', $classId)->where('a.academic_yr', $academicYr)
            ->whereIn('e.term_id', $termIds)->orderBy('e.start_date')
            ->select('e.exam_id', 'e.name', 'e.term_id', 'e.start_date')->distinct()->get();
        $examIds = $exams->pluck('exam_id')->all();
        $examsByTerm = $exams->groupBy('term_id')->map->all()->all();

        $subjects = DB::table('subjects_on_report_card as a')
            ->join('subjects_on_report_card_master as s', 'a.sub_rc_master_id', '=', 's.sub_rc_master_id')
            ->where('a.class_id', $classId)->where('a.academic_yr', $academicYr)
            ->whereIn('a.subject_type', ['Scholastic', 'Co-Scholastic'])
            ->orderBy('s.sequence')->select('s.*', 'a.subject_type')->get();
        $allHeadings = DB::table('allot_mark_headings as a')
            ->join('marks_headings as h', 'a.marks_headings_id', '=', 'h.marks_headings_id')
            ->where('a.class_id', $classId)->where('a.academic_yr', $academicYr)
            ->whereIn('a.exam_id', $examIds)
            ->orderBy('h.sequence')->select('a.*', 'h.name as marks_headings_name')
            ->get();
        $headingsByExam = $allHeadings->where('sm_id', 1)->groupBy('exam_id')->map->all()->all();
        $subjectHeadings = [];
        foreach ($allHeadings as $heading) {
            $subjectHeadings[$heading->exam_id][$heading->sm_id][] = $heading;
        }

        $marks = [];
        foreach (DB::table('student_marks')->whereIn('student_id', $studentIds)
            ->whereIn('exam_id', $examIds)->whereIn('subject_id', $subjects->pluck('sub_rc_master_id'))
            ->where('academic_yr', $academicYr)->where('publish', 'Y')
            ->select('student_id', 'exam_id', 'subject_id', 'reportcard_marks', 'reportcard_highest_marks', 'total_marks', 'highest_total_marks')
            ->get() as $mark) {
            $marks[$mark->student_id][$mark->exam_id][$mark->subject_id][] = (array) $mark;
        }
        $remarks = [];
        foreach (DB::table('report_card_remarks')->whereIn('student_id', $studentIds)
            ->whereIn('term_id', $termIds)->select('student_id', 'term_id', 'remark', 'promot')->get() as $remark) {
            // Match the single report helper's first row when duplicate records exist.
            $remarks[$remark->student_id][$remark->term_id] ??= (array) $remark;
        }
        $grades = DB::table('grade')->where('class_id', $classId)
            ->whereIn('subject_type', ['Scholastic', 'Co-Scholastic'])
            ->select('subject_type', 'mark_from', 'mark_upto', 'name')->get()->groupBy('subject_type')->all();
        $settings = DB::table('settings')->where('academic_yr', $academicYr)->first();
        $dateFrom = $settings->academic_yr_from ?? null;
        $dateTo = count($terms) === 1 && $dateFrom
            ? substr($dateFrom, 0, 4) . '-09-30' : ($settings->academic_yr_to ?? null);
        $attendance = [];
        if ($terms !== [] && $dateFrom && $dateTo) {
            $attendance = DB::table('attendance')->whereIn('student_id', $studentIds)
                ->where('academic_yr', $academicYr)->whereBetween('only_date', [$dateFrom, $dateTo])
                ->select('student_id')->selectRaw('SUM(CASE WHEN attendance_status = 0 THEN 1 ELSE 0 END) as present, COUNT(*) as working')
                ->groupBy('student_id')->get()->keyBy('student_id')->all();
        }

        return [
            'students' => $students, 'class_name' => $className, 'section_name' => $sectionName,
            'terms' => $terms, 'exams' => $examsByTerm, 'headings' => $headingsByExam,
            'subject_headings' => $subjectHeadings,
            'scholastic_subjects' => $subjects->where('subject_type', 'Scholastic')->values()->all(),
            'co_scholastic_subjects' => $subjects->where('subject_type', 'Co-Scholastic')->values()->all(),
            'marks' => $marks, 'remarks' => $remarks, 'grades' => $grades,
            'attendance' => $attendance, 'settings' => $settings,
            'reopen_date' => $published->first()->reopen_date ?? null,
        ];
    }
}
