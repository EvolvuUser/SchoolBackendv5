<?php

namespace App\Services\ReportCard;

use Illuminate\Support\Facades\DB;

class JpsClass9ReportCardDataBuilder
{
    public function build($classId, $studentId, string $academicYear): array
    {
        $student = DB::table('student as s')
            ->leftJoin('parent as p', 's.parent_id', '=', 'p.parent_id')
            ->leftJoin('class as c', 's.class_id', '=', 'c.class_id')
            ->leftJoin('section as sec', 's.section_id', '=', 'sec.section_id')
            ->where('s.student_id', $studentId)->where('s.class_id', $classId)
            ->where('s.academic_yr', $academicYear)
            ->select('s.*', 'p.father_name', 'c.name as class_name', 'sec.name as section_name')->first();
        abort_unless($student, 404, 'Student not found for the selected class and academic year.');

        // Class 9 publication records refer to exam IDs, as in get_published_exams_class9n10.
        $exams = DB::table('exam as e')
            ->where('e.academic_yr', $academicYear)
            ->whereExists(function ($query) use ($classId, $student) {
                $query->selectRaw('1')->from('report_card_publish as p')
                    ->whereColumn('p.term_id', 'e.exam_id')->where('p.class_id', $classId)
                    ->where('p.section_id', $student->section_id)->where('p.publish', 'Y');
            })->orderBy('e.start_date')->orderBy('e.exam_id')->get();
        $roles = [];
        foreach ($exams as $exam) {
            $role = $this->examRole($exam->name);
            if ($role !== null) {
                $roles[$role] = $exam;
            }
        }
        $headings = DB::table('allot_mark_headings as a')
            ->join('marks_headings as h', 'a.marks_headings_id', '=', 'h.marks_headings_id')
            ->where('a.class_id', $classId)->where('a.academic_yr', $academicYear)
            ->whereIn('a.exam_id', $exams->pluck('exam_id'))->orderBy('h.sequence')
            ->select('a.*', 'h.name as heading_name')->get();
        $configs = [];
        foreach ($headings as $heading) {
            $configs[$heading->exam_id][$heading->sm_id][] = $heading;
        }
        $subjects = DB::table('subjects_on_report_card as a')
            ->join('subjects_on_report_card_master as s', 'a.sub_rc_master_id', '=', 's.sub_rc_master_id')
            ->where('a.class_id', $classId)->where('a.academic_yr', $academicYear)
            ->whereIn('a.subject_type', ['Scholastic', 'Co-Scholastic'])
            ->orderBy('s.sequence')->select('s.*', 'a.subject_type')->distinct()->get();
        $marks = DB::table('student_marks')->where('student_id', $studentId)
            ->where('class_id', $classId)->where('section_id', $student->section_id)
            ->where('academic_yr', $academicYear)->where('publish', 'Y')
            ->whereIn('exam_id', $exams->pluck('exam_id'))->get();
        $byExam = [];
        foreach ($marks as $mark) {
            $byExam[$mark->exam_id][$mark->subject_id] ??= $mark;
        }
        $gradeScales = DB::table('grade')->where('class_id', $classId)
            ->whereIn('subject_type', ['Scholastic', 'Co-Scholastic'])->orderByDesc('mark_from')->get()->groupBy('subject_type');
        $gradeScale = $gradeScales->get('Co-Scholastic', collect());
        $blocks = [];
        foreach ([1, 2] as $term) {
            if (!isset($roles['term' . $term])) {
                continue;
            }
            $periodics = array_values(array_filter(array_map(
                fn ($role) => $roles[$role] ?? null,
                $term === 1 ? ['pt1'] : ['pt2', 'pt3']
            )));
            $blocks[] = [
                'exam' => $roles['term' . $term],
                'term_number' => $term,
                'periodics' => $periodics,
                'source_label' => $term === 1
                    ? ($periodics[0]->name ?? 'PT-I')
                    : 'Best of ' . ($periodics ? implode(' / ', array_column($periodics, 'name')) : 'PT-II / PT-III'),
            ];
        }

        // Subjects with a different assessment scheme (such as IT) receive their own heading rows.
        $groups = [];
        foreach (($blocks ? $subjects->where('subject_type', 'Scholastic') : []) as $subject) {
            $cells = [];
            $schema = [];
            foreach ($blocks as $block) {
                $examId = $block['exam']->exam_id;
                $configuration = $configs[$examId][$subject->sub_rc_master_id] ?? [];
                $termMark = $byExam[$examId][$subject->sub_rc_master_id] ?? null;
                $raw = $this->bestPeriodic($block['periodics'], $subject->sub_rc_master_id, $byExam, $configs);
                $components = [];
                foreach ($configuration as $heading) {
                    $maximum = $this->maximum($heading);
                    $periodic = $this->isPeriodicHeading($heading->heading_name);
                    $value = $periodic
                        ? $this->scale($raw['value'], $raw['maximum'], $maximum)
                        : $this->component($termMark, $heading);
                    $components[] = [
                        'label' => $periodic ? 'On report card convert to' : $heading->heading_name,
                        'maximum' => $maximum,
                        'value' => $value,
                    ];
                }
                $schema[] = array_map(fn ($cell) => [$cell['label'], $cell['maximum']], $components);
                $cells[] = [
                    'raw' => $raw, 'components' => $components,
                    'total' => $this->total(array_column($components, 'value')),
                    'maximum' => array_sum(array_column($components, 'maximum')),
                ];
            }
            $key = json_encode([$schema, array_map(fn ($cell) => $cell['raw']['maximum'], $cells)]);
            $groups[$key]['schema'] = $schema;
            $groups[$key]['rows'][] = ['name' => $subject->name, 'terms' => $cells];
        }

        $coScholastic = [];
        foreach ($subjects->where('subject_type', 'Co-Scholastic') as $subject) {
            $cells = [];
            foreach ($blocks as $block) {
                $examId = $block['exam']->exam_id;
                $configuration = $configs[$examId][$subject->sub_rc_master_id] ?? [];
                $mark = $byExam[$examId][$subject->sub_rc_master_id] ?? null;
                $value = $mark ? $this->total(array_map(
                    fn ($heading) => $this->component($mark, $heading), $configuration
                )) : '';
                $maximum = array_sum(array_map(fn ($heading) => $this->maximum($heading), $configuration));
                $grade = '';
                if ($value === 'Ab') {
                    $grade = 'Ab';
                } elseif (is_numeric($value) && $maximum > 0) {
                    $scaleMaximum = (float) ($gradeScale->max('mark_upto') ?? 0);
                    $gradeMark = round($value / $maximum * $scaleMaximum, 0, PHP_ROUND_HALF_UP);
                    foreach ($gradeScale as $range) {
                        if ($gradeMark >= $range->mark_from && $gradeMark <= $range->mark_upto) {
                            $grade = $range->name;
                            break;
                        }
                    }
                }
                $cells[] = compact('value', 'maximum', 'grade');
            }
            $coScholastic[] = ['name' => $subject->name, 'terms' => $cells];
        }
        $settings = DB::table('settings')->where('academic_yr', $academicYear)->first();
        $dateFrom = $settings->academic_yr_from ?? null;
        $dateTo = count($blocks) === 1 && $dateFrom
            ? substr($dateFrom, 0, 4) . '-09-30' : ($settings->academic_yr_to ?? null);
        $attendance = null;
        if ($blocks && $dateFrom && $dateTo) {
            $attendance = DB::table('attendance')->where('student_id', $studentId)
                ->where('academic_yr', $academicYear)->whereBetween('only_date', [$dateFrom, $dateTo])
                ->selectRaw('SUM(CASE WHEN attendance_status = 0 THEN 1 ELSE 0 END) as present, COUNT(*) as working')
                ->first();
        }
        $remarkRecords = DB::table('report_card_remarks')->where('student_id', $studentId)->get()->keyBy('term_id');
        $remarks = [];
        foreach ($blocks as $block) {
            $remarks[$block['term_number']] = $remarkRecords->get($block['exam']->exam_id)
                ?? $remarkRecords->get($block['term_number']);
        }
        $publication = DB::table('report_card_publish')->where('class_id', $classId)
            ->where('section_id', $student->section_id)->where('publish', 'Y')
            ->whereIn('term_id', array_map(fn ($block) => $block['exam']->exam_id, $blocks))
            ->orderByDesc('term_id')->first();

        return [
            'student' => $student, 'academic_year' => $academicYear,
            'blocks' => $blocks, 'groups' => array_values($groups), 'co_scholastic' => $coScholastic,
            'grades' => $gradeScales->all(), 'settings' => $settings, 'attendance' => $attendance,
            'remarks' => $remarks, 'reopen_date' => $publication->reopen_date ?? null,
            'date_from' => $dateFrom, 'date_to' => $dateTo,
        ];
    }

    private function examRole(string $name): ?string
    {
        $name = preg_replace('/\\([^)]*\\)/', '', $name);
        $name = preg_replace('/[^A-Z0-9]/', '', strtoupper($name));
        $name = preg_replace('/(?:EXAMINATION|EXAM)$/', '', $name);
        foreach (['pt' => '(?:PT|PERIODICTEST|PERIODICASSESSMENT)', 'term' => '(?:TERM|SA|SUMMATIVEASSESSMENT)'] as $kind => $prefix) {
            if (preg_match('/^' . $prefix . '(III|II|I|3|2|1)$/', $name, $match)) {
                $number = ['I' => 1, 'II' => 2, 'III' => 3][$match[1]] ?? (int) $match[1];
                return $kind . $number;
            }
        }
        return null;
    }

    private function isPeriodicHeading(string $name): bool
    {
        $name = preg_replace('/\\([^)]*\\)/', '', $name);
        $name = preg_replace('/[^A-Z0-9]/', '', strtoupper($name));
        return str_contains($name, 'PERIODIC') || preg_match('/^PT(?:I{1,3}|[123])?$/', $name)
            || str_starts_with($name, 'ONREPORTCARDCONVERT');
    }

    private function maximum($heading): float
    {
        $maximum = $heading->reportcard_highest_marks ?? null;
        return (float) ($maximum === null || $maximum === '' ? $heading->highest_marks : $maximum);
    }

    private function decode($json): array
    {
        $decoded = json_decode($json ?? '{}', true);
        return is_array($decoded) ? $decoded : [];
    }

    private function scale($value, float $from, float $to)
    {
        if (!is_numeric($value)) {
            return $value;
        }
        return $from > 0 ? round((float) $value / $from * $to, 2, PHP_ROUND_HALF_UP) : '';
    }

    private function total(array $values)
    {
        $numeric = array_filter($values, 'is_numeric');
        return $numeric !== [] ? round(array_sum($numeric), 2) : (in_array('Ab', $values, true) ? 'Ab' : '');
    }

    private function component($mark, $heading)
    {
        if (!$mark) {
            return '';
        }
        $saved = $this->decode($mark->reportcard_marks);
        if (array_key_exists($heading->heading_name, $saved) && $saved[$heading->heading_name] !== null) {
            $value = $saved[$heading->heading_name];
            return is_numeric($value) ? round((float) $value, 2) : $value;
        }
        $raw = $this->decode($mark->mark_obtained);
        $highest = $this->decode($mark->highest_marks);
        $present = $this->decode($mark->present);
        $id = $heading->marks_headings_id;
        if (($present[$id] ?? null) === 'N') {
            return 'Ab';
        }
        return $this->scale($raw[$id] ?? '', (float) ($highest[$id] ?? $heading->highest_marks), $this->maximum($heading));
    }

    private function bestPeriodic(array $exams, $subjectId, array $marks, array $configs): array
    {
        $best = ['value' => '', 'maximum' => 0];
        foreach ($exams as $exam) {
            $configuration = $configs[$exam->exam_id][$subjectId] ?? [];
            $mark = $marks[$exam->exam_id][$subjectId] ?? null;
            $maximum = array_sum(array_map(fn ($heading) => (float) $heading->highest_marks, $configuration));
            $best['maximum'] = $best['maximum'] ?: $maximum;
            if (!$mark) {
                continue;
            }
            $raw = $this->decode($mark->mark_obtained);
            $highest = $this->decode($mark->highest_marks);
            $present = $this->decode($mark->present);
            foreach ($present as $id => $status) {
                if ($status === 'N') {
                    $raw[$id] = 'Ab';
                }
            }
            if ($raw !== []) {
                $value = $this->total(array_values($raw));
                $maximum = array_sum(array_filter($highest, 'is_numeric')) ?: $maximum;
            } else {
                // Older entries may contain only converted marks: reconstruct the raw score once.
                $reportMaximum = array_sum(array_filter($this->decode($mark->reportcard_highest_marks), 'is_numeric'));
                $value = $this->scale($this->total($this->decode($mark->reportcard_marks)), $reportMaximum, $maximum);
            }
            if (is_numeric($value) && (!is_numeric($best['value']) || $value > $best['value'])) {
                $best = ['value' => $value, 'maximum' => $maximum];
            } elseif ($best['value'] === '' && $value === 'Ab') {
                $best['value'] = 'Ab';
            }
        }
        return $best;
    }
}
