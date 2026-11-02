<?php
$term_list = get_published_terms($row1['class_id'], $row1['section_id']);
$sub_list = get_scholastic_subject_alloted_to_class($row1['class_id'], $row1['academic_yr']);
$scholastic = [];

foreach ($term_list as $term) {
    $headings = [];
    $subjects = [];

    $exam_list = get_exams_by_class_per_term(
        $row1['class_id'],
        $term->term_id,
        $row1['academic_yr']
    );

    foreach ($sub_list as $subject) {
        $marks = [];
        $maximums = [];
        $subjectHeadings = [];

        foreach ($exam_list as $exam) {
            $configured = [];

            foreach (get_marks_heading_class(
                $row1['class_id'],
                $subject->sub_rc_master_id,
                $exam->exam_id,
                $row1['academic_yr']
            ) as $heading) {
                $configured[$heading->marks_headings_name] = (float) $heading->highest_marks;
            }

            $result = get_marks(
                $exam->exam_id,
                $row1['class_id'],
                $row1['section_id'],
                $subject->sub_rc_master_id,
                $row1['student_id'],
                $row1['academic_yr']
            );

            $savedMarks = json_decode($result[0]['reportcard_marks'] ?? '{}', true) ?: [];
            $savedMaximums = json_decode($result[0]['reportcard_highest_marks'] ?? '{}', true) ?: [];

            $examMaximums = array_replace($configured, $savedMaximums);

            foreach (array_unique(array_merge(array_keys($examMaximums), array_keys($savedMarks))) as $key) {
                $subjectHeadings[$key] = ($subjectHeadings[$key] ?? 0) + (float) ($examMaximums[$key] ?? 0);

                if (!array_key_exists($key, $savedMarks) || $savedMarks[$key] === '' || $savedMarks[$key] === null) {
                    continue;
                }

                $value = $savedMarks[$key];

                if (is_numeric($value)) {
                    $marks[$key] = (is_numeric($marks[$key] ?? null) ? $marks[$key] : 0) + (float) $value;
                } elseif (!isset($marks[$key])) {
                    $marks[$key] = $value;
                }

                $maximums[$key] = ($maximums[$key] ?? 0) + (float) ($examMaximums[$key] ?? 0);
            }
        }

        foreach ($subjectHeadings as $key => $maximum) {
            $headings[$key] = max($headings[$key] ?? 0, $maximum);
        }
        foreach ($marks as $key => $value) {
            if (is_numeric($value)) {
                $marks[$key] = (int) round(
                    (float) $value,
                    0,
                    PHP_ROUND_HALF_UP
                );
            }
        }
        
        $numericMarks = array_filter($marks, 'is_numeric');
        $total = array_sum($numericMarks);
        $maximum = array_sum($maximums);

        $numericMarks = array_filter($marks, 'is_numeric');
        $total = array_sum($numericMarks);
        $maximum = array_sum($maximums);

        $grade = !empty($marks)
            ? get_grade_based_on_marks(
                round($total),
                'Scholastic',
                $row1['class_id']
            )
            : '';

        if ($marks && !$numericMarks && in_array('Ab', $marks, true)) {
            $grade = 'Ab';
        }

        $subjects[$subject->sub_rc_master_id] = compact(
            'marks',
            'total',
            'maximum',
            'grade'
        );
    }

    $totals = array_fill_keys(array_keys($headings), 0);
    $grandTotal = 0;
    $grandMaximum = 0;

    foreach ($subjects as $subject) {
        foreach ($subject['marks'] as $key => $value) {
            $totals[$key] += is_numeric($value) ? (float) $value : 0;
        }

        $grandTotal += $subject['total'];
        $grandMaximum += $subject['maximum'];
    }

    $grandGrade = $grandMaximum > 0
        ? get_grade_based_on_marks(
            round($grandTotal * 100 / $grandMaximum),
            'Scholastic',
            $row1['class_id']
        )
        : '';

    $scholastic[$term->term_id] = compact(
        'headings',
        'subjects',
        'totals',
        'grandTotal',
        'grandMaximum',
        'grandGrade'
    );
}
?>

<table class="scholastic-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th class="th">SCHOLASTIC AREAS</th>

            @foreach ($term_list as $term)
                <th class="th1" colspan="{{ count($scholastic[$term->term_id]['headings']) + 2 }}">
                    {{ $term->name }}
                </th>
            @endforeach
        </tr>

        <tr>
            <th class="td">SUBJECT</th>

            @foreach ($term_list as $term)

                @foreach ($scholastic[$term->term_id]['headings'] as $name => $maximum)
                    <th class="td">
                        {{ $name }}<br>
                        ({{ round($maximum) }})
                    </th>
                @endforeach

                <th class="td">
                    Total<br>
                    ({{ round(array_sum($scholastic[$term->term_id]['headings'])) }})
                </th>

                <th class="td">Grade</th>

            @endforeach
        </tr>
    </thead>

    <tbody>
        @foreach ($sub_list as $subject)
            <tr>
                <td class="td">{{ $subject->name }}</td>

                @foreach ($term_list as $term)

                    <?php
                    $subjectResult = $scholastic[$term->term_id]['subjects'][$subject->sub_rc_master_id];
                    ?>

                    @foreach ($scholastic[$term->term_id]['headings'] as $name => $maximum)

                        <?php
                        $mark = $subjectResult['marks'][$name] ?? '';
                        ?>

                        <td class="td">
                            {{ is_numeric($mark) ? round((float) $mark) : $mark }}
                        </td>

                    @endforeach

                    <td class="td">
                        {{ $subjectResult['marks'] ? round($subjectResult['total']) : '' }}
                    </td>

                    <td class="td">
                        {{ $subjectResult['grade'] }}
                    </td>

                @endforeach
            </tr>
        @endforeach

        <tr>
    <td class="td">TOTAL</td>

    @foreach ($term_list as $term)
        @php
            $termResult = $scholastic[$term->term_id];
            $headingCount = count($termResult['headings']);
        @endphp

        @if ($headingCount > 0)
            <td class="td" colspan="{{ $headingCount }}"></td>
        @endif

        <td class="td">
                {{ round($termResult['grandTotal']) }}/{{ round($termResult['grandMaximum']) }}
        </td>

        <td class="td">
           {{ $termResult['grandGrade'] }}
        </td>
    @endforeach
</tr>
    </tbody>
</table>