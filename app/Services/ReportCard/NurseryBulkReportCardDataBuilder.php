<?php

namespace App\Services\ReportCard;

use Illuminate\Support\Facades\DB;

class NurseryBulkReportCardDataBuilder
{
    public function build($classId, $sectionId, $academicYr, $studCount = null): array
    {
        $students = collect(get_students($classId, $sectionId, $academicYr))->map(function ($student) {
            return (array) $student;
        });

        if ($studCount !== null) {
            $students = $this->sliceStudents($students, (int) $studCount);
        }

        $students = $students->values();
        $studentIds = $students->pluck('student_id')->filter()->values()->all();

        $termList = collect(get_published_terms($classId, $sectionId))->values();
        $termIds = $termList->pluck('term_id')->all();

        $subjects = collect(get_subjects_by_class($classId, $academicYr))->values();
        $subjectIds = $subjects->pluck('sub_rc_master_id')->filter()->unique()->values()->all();

        $examListByTerm = [];
        $examIds = [];
        foreach ($termList as $term) {
            $examList = collect(get_exams_by_class_per_term($classId, $term->term_id, $academicYr))->values();
            $examListByTerm[$term->term_id] = $examList->all();
            $examIds = array_merge($examIds, $examList->pluck('exam_id')->all());
        }
        $examIds = array_values(array_unique($examIds));

        $markHeadingsBySubject = [];
        foreach ($subjects as $subject) {
            $markHeadingsBySubject[$subject->sub_rc_master_id] = collect(
                get_marks_headings_name_by_class_and_subject($classId, $subject->sub_rc_master_id, $academicYr)
            )->map(function ($heading) {
                return (object) [
                    'name' => $heading->name,
                    'marks_headings_id' => $heading->marks_headings_id ?? null,
                ];
            })->values()->all();
        }

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

            $examToTerm = [];
            foreach ($examListByTerm as $termId => $examList) {
                foreach ($examList as $exam) {
                    $examToTerm[$exam->exam_id] = $termId;
                }
            }

            foreach ($studentMarks as $mark) {
                $termId = $examToTerm[$mark->exam_id] ?? null;
                if ($termId === null) {
                    continue;
                }

                $marksByStudent[$mark->student_id][$termId][$mark->subject_id][$mark->exam_id] = [
                    'reportcard_marks' => $this->decodeJsonArray($mark->reportcard_marks),
                    'reportcard_highest_marks' => $this->decodeJsonArray($mark->reportcard_highest_marks),
                    'total_marks' => $mark->total_marks,
                    'highest_total_marks' => $mark->highest_total_marks,
                ];
            }
        }

        $remarksByStudent = [];
        if ($studentIds !== [] && $termIds !== []) {
            $remarks = DB::table('report_card_remarks')
                ->select('student_id', 'term_id', 'remark', 'promot')
                ->whereIn('student_id', $studentIds)
                ->whereIn('term_id', $termIds)
                ->get();

            foreach ($remarks as $remark) {
                $remarksByStudent[$remark->student_id][$remark->term_id] = [
                    'remark' => $remark->remark,
                    'promot' => $remark->promot,
                ];
            }
        }

        $settings = getSettingsDataForAcademicYr($academicYr);
        $attendanceRangesByTerm = [];
        $yearStart = $settings->academic_yr_from ?? null;
        $yearEnd = $settings->academic_yr_to ?? null;
        foreach ($termList as $term) {
            if ($term->term_id == 1 && $yearStart) {
                $attendanceRangesByTerm[$term->term_id] = [
                    'from' => $yearStart,
                    'to' => date_format(date_create(substr($yearStart, 0, 4) . '-09-30'), 'Y-m-d'),
                ];
            } elseif ($term->term_id == 2 && $yearStart && $yearEnd) {
                $attendanceRangesByTerm[$term->term_id] = [
                    'from' => date_format(date_create(substr($yearStart, 0, 4) . '-10-01'), 'Y-m-d'),
                    'to' => $yearEnd,
                ];
            }
        }

        $attendanceByStudent = [];
        if ($studentIds !== [] && $attendanceRangesByTerm !== []) {
            foreach ($attendanceRangesByTerm as $termId => $range) {
                $rows = DB::table('attendance')
                    ->select(
                        'student_id',
                        DB::raw("SUM(CASE WHEN attendance_status = 0 THEN 1 ELSE 0 END) as total_present_days"),
                        DB::raw('COUNT(*) as total_working_days')
                    )
                    ->whereIn('student_id', $studentIds)
                    ->whereBetween('only_date', [$range['from'], $range['to']])
                    ->where('academic_yr', $academicYr)
                    ->groupBy('student_id')
                    ->get();

                foreach ($rows as $row) {
                    $attendanceByStudent[$row->student_id][$termId] = [
                        'present' => $row->total_present_days,
                        'working' => $row->total_working_days,
                    ];
                }
            }
        }

        $reopenDate = DB::table('report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->value('reopen_date');

        return [
            'students' => $students->all(),
            'term_list' => $termList->all(),
            'subjects' => $subjects->all(),
            'exam_list_by_term' => $examListByTerm,
            'mark_headings_by_subject' => $markHeadingsBySubject,
            'marks_by_student' => $marksByStudent,
            'remarks_by_student' => $remarksByStudent,
            'attendance_by_student' => $attendanceByStudent,
            'reopen_date' => $reopenDate,
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

    private function sliceStudents($students, int $studCount)
    {
        $totalStudents = $students->count();
        if ($totalStudents === 0) {
            return $students;
        }

        $slot = $totalStudents / 10;
        $lastSlot = explode('.', number_format($slot, 1))[1];
        $c = $totalStudents - $lastSlot;

        if ($lastSlot != $studCount) {
            return $students->slice(max($studCount - 10, 0), 10);
        }

        return $students->slice($c);
    }
}
