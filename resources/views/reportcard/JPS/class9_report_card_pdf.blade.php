<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 0; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            background-image: url('{{ str_replace('\\', '/', public_path('reportcard/JPS/reportcard.png')) }}');
            background-size: 100% 100%;
            background-repeat: no-repeat;
        }
        .report { margin: 125px 35px 25px; }
        h2 { text-align: center; font-size: 14px; margin: 0 0 5px; }
        .session { text-align: center; font-size: 10px; margin-bottom: 12px; }
        .details { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9px; }
        .details td { padding: 4px 0; }
        .assessment { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 9px; background: white; }
        .assessment th, .assessment td { border: 1px solid #222; padding: 2px 2px; text-align: center; vertical-align: middle; word-wrap: break-word; font-weight: normal; }
        .assessment .subject { width: 22%; text-align: left; }
        .assessment .label, .assessment .area { background: #ffe69b; text-align: left; }
        .assessment .area { font-weight: bold; font-size: 9px; }
        .assessment .subject-name { text-align: left; }
        .assessment .maximum { display: block; font-size: 7px; color: #444; }
        .assessment .raw-maximum { font-size: 7px; color: #444; }
        .assessment thead { display: table-header-group; }
        .assessment tr { page-break-inside: avoid; }
        .signatures { width: 100%; margin-top: 18px; text-align: center; font-size: 9px; }
        .empty { padding: 18px; text-align: center; border: 1px solid #999; background: white; }
    </style>
</head>
<body>
<div class="report">
    <h2>REPORT CARD</h2>
    <div class="session">ACADEMIC SESSION {{ $reportCardData['academic_year'] }}</div>
    @php($student = $reportCardData['student'])
    <table class="details">
        <tr>
            <td colspan="2">Student's Name: {{ trim(implode(' ', array_filter([$student->first_name, $student->mid_name, $student->last_name]))) }}</td>
            <td>Roll No.: {{ $student->roll_no }}</td>
        </tr>
        <tr>
            <td colspan="2">Mother's / Father's / Guardian's Name: {{ $student->father_name }}</td>
            <td>GR No.: {{ $student->reg_no }}</td>
        </tr>
        <tr>
            <td colspan="2">Date of Birth: {{ $student->dob && $student->dob !== '0000-00-00' ? date('d-m-Y', strtotime($student->dob)) : '' }}</td>
            <td>Class / Section: {{ $student->class_name }} {{ $student->section_name }}</td>
        </tr>
    </table>

    @forelse ($reportCardData['groups'] as $group)
        @php($columnCount = 1 + array_sum(array_map(fn ($schema) => count($schema) + 2, $group['schema'])))
        <table class="assessment scholastic">
            <colgroup>
                <col style="width:22%;">
                @for ($column = 1; $column < $columnCount; $column++)
                    <col style="width:{{ 78 / ($columnCount - 1) }}%;">
                @endfor
            </colgroup>
            <thead>
            @if ($loop->first)
            <tr>
                <th class="subject label">Exam Name</th>
                @foreach ($reportCardData['blocks'] as $index => $block)
                    <th>{{ $block['source_label'] }}</th>
                    <th colspan="{{ max(1, count($group['schema'][$index]) + 1) }}">{{ $block['exam']->name }}</th>
                @endforeach
            </tr>
            @endif
            <tr>
                <th class="subject label">Mark heading Name</th>
                @foreach ($reportCardData['blocks'] as $index => $block)
                    <th>Periodic test</th>
                    @foreach ($group['schema'][$index] as [$name, $maximum])
                        <th>{{ $name }}<span class="maximum">({{ $maximum + 0 }})</span></th>
                    @endforeach
                    <th>Total<span class="maximum">({{ array_sum(array_column($group['schema'][$index], 1)) }})</span></th>
                @endforeach
            </tr>
            @if ($loop->first)
                <tr><th class="area" colspan="{{ $columnCount }}">SCHOLASTIC SUBJECTS</th></tr>
            @endif
            </thead>
            <tbody>
            @foreach ($group['rows'] as $row)
                <tr>
                    <td class="subject-name">{{ $row['name'] }}</td>
                    @foreach ($row['terms'] as $term)
                        <td class="periodic">{{ $term['raw']['value'] }}@if ($term['raw']['maximum'] > 0)<span class="raw-maximum"> / {{ $term['raw']['maximum'] }}</span>@endif</td>
                        @foreach ($term['components'] as $component)
                            <td class="component">{{ $component['value'] }}</td>
                        @endforeach
                        <td class="total">{{ $term['total'] }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    @empty
        <div class="empty">No published Term I or Term II assessments are available for this class and section.</div>
    @endforelse

    @if ($reportCardData['blocks'] && $reportCardData['co_scholastic'])
        @php($schemas = $reportCardData['groups'][0]['schema'] ?? array_fill(0, count($reportCardData['blocks']), []))
        @php($columnCount = 1 + array_sum(array_map(fn ($schema) => max(2, count($schema) + 2), $schemas)))
        <table class="assessment co-scholastic">
            <colgroup>
                <col style="width:22%;">
                @for ($column = 1; $column < $columnCount; $column++)
                    <col style="width:{{ 78 / ($columnCount - 1) }}%;">
                @endfor
            </colgroup>
            <thead>
            <tr><th class="area" colspan="{{ $columnCount }}">CO-SCHOLASTIC SUBJECTS</th></tr>
            <tr>
                <th class="subject label">Mark heading Name</th>
                @foreach ($reportCardData['blocks'] as $index => $block)
                    @if (count($schemas[$index]) > 0)<th colspan="{{ count($schemas[$index]) }}"></th>@endif
                    <th>Total Marks</th>
                    <th>Report card convert to</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach ($reportCardData['co_scholastic'] as $row)
                <tr>
                    <td class="subject-name">{{ $row['name'] }}</td>
                    @foreach ($row['terms'] as $index => $term)
                        @if (count($schemas[$index]) > 0)<td colspan="{{ count($schemas[$index]) }}"></td>@endif
                        <td>{{ $term['value'] }}@if ($term['maximum'] > 0)<span class="raw-maximum"> / {{ $term['maximum'] }}</span>@endif</td>
                        <td>{{ $term['grade'] }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    <table class="signatures">
        <tr><td>Class Teacher's Sign.</td><td>Parent's Sign.</td><td>Principal's Sign.</td></tr>
    </table>
</div>
</body>
</html>
