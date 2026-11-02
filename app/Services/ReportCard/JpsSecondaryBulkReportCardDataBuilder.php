<?php

namespace App\Services\ReportCard;

use Illuminate\Support\Facades\DB;

class JpsSecondaryBulkReportCardDataBuilder
{
    public function build($classId, $sectionId, string $academicYear, $studCount = null): array
    {
        $students = DB::table('student as s')
            ->leftJoin('parent as p', 's.parent_id', '=', 'p.parent_id')
            ->leftJoin('class as c', 's.class_id', '=', 'c.class_id')
            ->leftJoin('section as sec', 's.section_id', '=', 'sec.section_id')
            ->where('s.class_id', $classId)->where('s.section_id', $sectionId)
            ->where('s.academic_yr', $academicYear)->where('s.IsDelete', 'N')
            ->orderBy('s.roll_no')->orderBy('s.reg_no')->orderBy('s.student_id')
            ->select('s.*', 'p.father_name', 'c.name as class_name', 'sec.name as section_name')->get();

        // Keep the ten-student batches used by the existing bulk download endpoint.
        if ($studCount !== null) {
            $remainder = $students->count() % 10;
            $offset = $remainder === (int) $studCount
                ? $students->count() - $remainder : max((int) $studCount - 10, 0);
            $students = $students->slice($offset, 10);
        }
        if ($students->isEmpty()) {
            return ['students' => [], 'reports' => []];
        }
        $studentIds = $students->pluck('student_id');
        $builder = match ((string) $students->first()->class_name) {
            '9' => app(JpsClass9ReportCardDataBuilder::class),
            '10' => app(JpsClass10ReportCardDataBuilder::class),
            default => throw new \InvalidArgumentException('Only JPS classes 9 and 10 are supported.'),
        };

        // Publication IDs for classes 9/10 refer to exams, rather than terms.
        $publications = DB::table('report_card_publish')->where('class_id', $classId)
            ->where('section_id', $sectionId)->where('publish', 'Y')->get();
        $exams = DB::table('exam')->where('academic_yr', $academicYear)
            ->whereIn('exam_id', $publications->pluck('term_id'))
            ->orderBy('start_date')->orderBy('exam_id')->get();
        $examIds = $exams->pluck('exam_id');
        $headings = DB::table('allot_mark_headings as a')
            ->join('marks_headings as h', 'a.marks_headings_id', '=', 'h.marks_headings_id')
            ->where('a.class_id', $classId)->where('a.academic_yr', $academicYear)
            ->whereIn('a.exam_id', $examIds)->orderBy('h.sequence')
            ->select('a.*', 'h.name as heading_name')->get();
        $subjects = DB::table('subjects_on_report_card as a')
            ->join('subjects_on_report_card_master as s', 'a.sub_rc_master_id', '=', 's.sub_rc_master_id')
            ->where('a.class_id', $classId)->where('a.academic_yr', $academicYear)
            ->whereIn('a.subject_type', ['Scholastic', 'Co-Scholastic'])
            ->orderBy('s.sequence')->select('s.*', 'a.subject_type')->distinct()->get();
        $marks = DB::table('student_marks')->whereIn('student_id', $studentIds)
            ->where('class_id', $classId)->where('section_id', $sectionId)
            ->where('academic_yr', $academicYear)->where('publish', 'Y')
            ->whereIn('exam_id', $examIds)->get()->groupBy('student_id');
        $grades = DB::table('grade')->where('class_id', $classId)
            ->whereIn('subject_type', ['Scholastic', 'Co-Scholastic'])
            ->orderByDesc('mark_from')->get()->groupBy('subject_type');
        $settings = DB::table('settings')->where('academic_yr', $academicYear)->first();
        $remarks = DB::table('report_card_remarks')->whereIn('student_id', $studentIds)
            ->get()->groupBy('student_id');

        // Preload both attendance windows; each report chooses the same window as its single download.
        $attendance = [];
        $dateFrom = $settings->academic_yr_from ?? null;
        if ($dateFrom) {
            $dateEnds = array_unique(array_filter([
                substr($dateFrom, 0, 4) . '-09-30', $settings->academic_yr_to ?? null,
            ]));
            foreach ($dateEnds as $dateTo) {
                $attendance[$dateTo] = DB::table('attendance')->whereIn('student_id', $studentIds)
                    ->where('academic_yr', $academicYear)->whereBetween('only_date', [$dateFrom, $dateTo])
                    ->select('student_id')
                    ->selectRaw('SUM(CASE WHEN attendance_status = 0 THEN 1 ELSE 0 END) as present, COUNT(*) as working')
                    ->groupBy('student_id')->get()->keyBy('student_id');
            }
        }
        $context = compact('exams', 'headings', 'subjects', 'marks', 'grades', 'settings', 'remarks', 'publications', 'attendance');
        $context['students'] = $students->keyBy('student_id');
        $reports = [];
        foreach ($students as $student) {
            // Reuse the single-report calculations without issuing queries for each student.
            $reports[] = $builder->build($classId, $student->student_id, $academicYear, $context);
        }

        return [
            'students' => $students->values()->all(), 'reports' => $reports,
            'section_name' => $students->first()->section_name,
        ];
    }
}
