<?php

namespace App\Services\ReportCard;

use Illuminate\Support\Facades\DB;

class Class3To5BulkReportCardDataBuilder
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

        $examListByTerm = [];
        $examIds = [];
        foreach ($termList as $term) {
            $examList = collect(get_exams_by_class_per_term($classId, $term->term_id, $academicYr))->values();
            $examListByTerm[$term->term_id] = $examList->all();
            $examIds = array_merge($examIds, $examList->pluck('exam_id')->all());
        }
        $examIds = array_values(array_unique($examIds));

        $scholasticSubjects = collect(get_scholastic_subject_alloted_to_class($classId, $academicYr))->values();
        $coScholasticSubjects = collect(get_coscholastic_subject_alloted_to_class($classId, $academicYr))->values();
        $subjectIds = collect()
            ->merge($scholasticSubjects->pluck('sub_rc_master_id'))
            ->merge($coScholasticSubjects->pluck('sub_rc_master_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $termMetadata = [];
        foreach ($termList as $term) {
            $generalHighestMarksArray = [];
            $generalHighestMarksList = [];
            $countOfMarkHeadingsByExam = [];

            foreach ($examListByTerm[$term->term_id] as $exam) {
                $countOfMarkHeadingsByExam[$exam->exam_id] = 0;
                $marksHeadings = get_marks_heading_class($classId, 1, $exam->exam_id, $academicYr);

                foreach ($marksHeadings as $heading) {
                    $generalHighestMarksArray[$heading->marks_headings_name] = $heading->highest_marks;
                    $generalHighestMarksList[] = [
                        'id' => $exam->exam_id . '_' . $heading->marks_headings_name,
                        'name' => $heading->marks_headings_name,
                        'highest_marks' => $heading->highest_marks,
                    ];
                    $countOfMarkHeadingsByExam[$exam->exam_id]++;
                }
            }

            $termMetadata[$term->term_id] = [
                'exam_list' => $examListByTerm[$term->term_id],
                'general_highest_marks_array' => $generalHighestMarksArray,
                'general_highest_marks_list' => $generalHighestMarksList,
                'count_of_mark_headings' => count($generalHighestMarksList),
                'count_of_mark_headings_by_exam' => $countOfMarkHeadingsByExam,
            ];
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
        $dateFrom = $settings->academic_yr_from ?? null;
        $dateTo = null;
        if ($dateFrom) {
            if (count($termList) == 1) {
                $dateTo = date_format(date_create(substr($dateFrom, 0, 4) . '-09-30'), 'Y-m-d');
            } elseif (count($termList) >= 2) {
                $dateTo = $settings->academic_yr_to ?? null;
            }
        }

        $attendanceByStudent = [];
        if ($studentIds !== [] && $dateFrom && $dateTo) {
            $attendanceRows = DB::table('attendance')
                ->select(
                    'student_id',
                    DB::raw("SUM(CASE WHEN attendance_status = 0 THEN 1 ELSE 0 END) as total_present_days"),
                    DB::raw('COUNT(*) as total_working_days')
                )
                ->whereIn('student_id', $studentIds)
                ->whereBetween('only_date', [$dateFrom, $dateTo])
                ->where('academic_yr', $academicYr)
                ->groupBy('student_id')
                ->get();

            foreach ($attendanceRows as $attendance) {
                $attendanceByStudent[$attendance->student_id] = [
                    'present' => $attendance->total_present_days,
                    'working' => $attendance->total_working_days,
                ];
            }
        }

        $gradeScale = DB::table('grade')
            ->select('class_id', 'subject_type', 'name', 'mark_from', 'mark_upto')
            ->where('class_id', $classId)
            ->whereIn('subject_type', ['Scholastic', 'Co-Scholastic'])
            ->orderBy('mark_from', 'desc')
            ->get()
            ->groupBy('subject_type')
            ->map(fn ($rows) => $rows->map(function ($row) {
                return [
                    'name' => $row->name,
                    'mark_from' => $row->mark_from,
                    'mark_upto' => $row->mark_upto,
                ];
            })->all())
            ->toArray();

        $reopenDate = DB::table('report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->value('reopen_date');

        return [
            'students' => $students->all(),
            'term_list' => $termList->all(),
            'scholastic_subjects' => $scholasticSubjects->all(),
            'co_scholastic_subjects' => $coScholasticSubjects->all(),
            'term_metadata' => $termMetadata,
            'marks_by_student' => $marksByStudent,
            'remarks_by_student' => $remarksByStudent,
            'attendance_by_student' => $attendanceByStudent,
            'grade_scale' => $gradeScale,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'reopen_date' => $reopenDate,
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
