@php
$school = getSchoolDetails();
$bgImage = getHealthBgImage();

$class = get_class_section_of_student($student_id);
$class_array = !empty($class) ? explode(' ', $class) : [];
$class_name = (!empty($class_array) && isset($class_array[0])) ? (int)$class_array[0] : 0;

$parent_info = get_student_parent_info($student_id, $customClaims);
$health_activity_data = check_health_activity_data_exist_for_studentid($student_id);

$parent = $parent_info[0] ?? null;
$health  = $health_activity_data ?? [];

/* ── Build student_id_array_new (class 1 → current class) ── */
$student_id_array_new = [];
if ($class_name >= 1) {
    $student_id_array_new[$class_name] = $student_id;
    $temp = $student_id;
    for ($i = $class_name - 1; $i >= 1; $i--) {
        $temp = get_previous_student_id($temp);
        if (!$temp) break;
        $student_id_array_new[$i] = $temp;
    }
    ksort($student_id_array_new);
}

$val         = $health['value']       ?? [];
$groupData   = $health['group_data']  ?? [];
$paramData   = $health['param_data']  ?? [];
$description = $health['description'] ?? [];

/* ── Basic information block (first page) ── */
$basicInfo = [];
foreach ($val as $key => $value) {
    $groupName = $groupData[$key]['group_name'] ?? '';
    if (strtolower(trim($groupName)) === 'basic information') {
        if ($value === '' || $value === null) continue;
        $basicInfo[] = ['label' => $key, 'value' => $value];
    }
}

/* ── Flatten param tree ── */
function flattenParamTree($nodes, $prefix = []) {
    $result = [];
    foreach ($nodes ?? [] as $node) {
        $current = array_merge($prefix, [$node['label'] ?? '']);
        if (!empty($node['children'])) {
            $result = array_merge($result, flattenParamTree($node['children'], $current));
        } else {
            $result[] = $current;
        }
    }
    return $result;
}

/* ── Build $tableData ── */
$tableData = [];
foreach ($val as $key => $value) {
    $groupName = $groupData[$key]['group_name'] ?? 'Other';
    if (strtolower(trim($groupName)) === 'basic information') continue;

    $paths = flattenParamTree($paramData[$key] ?? []);

    if (empty($paths)) {
        $tableData[] = [
            'group'     => $groupName,
            'sub_group' => '',
            'sub_sub'   => '',
            'test'      => $key,
            'desc'      => $description[$key] ?? '',
        ];
    } else {
        foreach ($paths as $p) {
            $tableData[] = [
                'group'     => $groupName,
                'sub_group' => $p[0] ?? '',
                'sub_sub'   => $p[1] ?? '',
                'test'      => $p[2] ?? $key,
                'desc'      => $description[$key] ?? '',
            ];
        }
    }
}

/* ── STEP 1: Group → SubGroup → SubSub ── */
$grouped = [];
foreach ($tableData as $row) {
    $grouped[$row['group']][$row['sub_group']][$row['sub_sub']][] = $row;
}

/* ── STEP 2: Flatten into $flatRows ── */
$flatRows = [];
foreach ($grouped as $groupName => $subGroups) {
    foreach ($subGroups as $subGroupName => $subSubs) {
        foreach ($subSubs as $subSubName => $items) {
            foreach ($items as $item) {
                $flatRows[] = [
                    'group'     => $groupName,
                    'sub_group' => $subGroupName,
                    'sub_sub'   => $subSubName,
                    'test'      => $item['test'],
                    'desc'      => $item['desc'],
                ];
            }
        }
    }
}

/* ── STEP 3: Truncate display values & calculate rowspans ── */
$groupCounts = [];
$subCounts   = [];

foreach ($flatRows as $row) {
    $gKey  = $row['group'];
    $sgKey = $row['group'] . '||' . $row['sub_group'];
    $groupCounts[$gKey]  = ($groupCounts[$gKey]  ?? 0) + 1;
    $subCounts[$sgKey]   = ($subCounts[$sgKey]   ?? 0) + 1;
}

$seenGroups  = [];
$seenSubs    = [];
$processedRows = [];

foreach ($flatRows as $row) {
    $gKey  = $row['group'];
    $sgKey = $row['group'] . '||' . $row['sub_group'];

    /* Truncate sub_group */
    $row['sub_group_full']    = $row['sub_group'] ?? '';
    $row['sub_group_display'] = mb_strlen($row['sub_group'] ?? '') > 35
        ? mb_substr($row['sub_group'] ?? '', 0, 32) . '…'
        : ($row['sub_group'] ?? '');

    /* Clean & truncate description */
    $cleanDesc = trim(preg_replace('/\s+/', ' ', strip_tags($row['desc'] ?? '')));
    $row['desc_full']    = $cleanDesc;
    $row['desc_display'] = mb_strlen($cleanDesc) > 100
        ? mb_substr($cleanDesc, 0, 97) . '…'
        : $cleanDesc;

    /* Truncate sub_sub */
    $cleanSubSub = trim($row['sub_sub'] ?? '');
    $row['sub_sub_full']    = $cleanSubSub;
    $row['sub_sub_display'] = mb_strlen($cleanSubSub) > 35
        ? mb_substr($cleanSubSub, 0, 32) . '…'
        : $cleanSubSub;

    /* Truncate test */
    $cleanTest = trim($row['test'] ?? '');
    $row['test_full']    = $cleanTest;
    $row['test_display'] = mb_strlen($cleanTest) > 35
        ? mb_substr($cleanTest, 0, 32) . '…'
        : $cleanTest;

    /* Rowspan flags */
    $row['show_group']    = !isset($seenGroups[$gKey]);
    $row['group_rowspan'] = $row['show_group'] ? $groupCounts[$gKey] : 0;
    $row['show_sub']      = !isset($seenSubs[$sgKey]);
    $row['sub_rowspan']   = $row['show_sub'] ? $subCounts[$sgKey] : 0;

    /* Is this the first row of a NEW group (used for CSS page-break) */
    $row['is_group_start'] = !isset($seenGroups[$gKey]);

    $seenGroups[$gKey] = true;
    $seenSubs[$sgKey]  = true;

    $processedRows[] = $row;
}

/* ── STEP 4: All class health data ── */
$allClassHealth = [];
foreach ($student_id_array_new as $cls => $id) {
    $h = check_health_activity_data_exist_for_studentid($id);
    $allClassHealth[$cls] = $h['value'] ?? [];
}

/* ── Dynamic column widths ── */
$totalClassCols  = count($student_id_array_new);
$pageWidthPx     = 1032;  // 92% of A4 landscape at 96dpi
$fixedColsPx     = 60 + 70 + 70 + 70;  // Fitness + Sub + SubSub + Test
$classColPx      = max(40, (int)(($pageWidthPx - $fixedColsPx - 150) / max(1, $totalClassCols)));
$descColPx       = $pageWidthPx - $fixedColsPx - ($classColPx * $totalClassCols);
$descColPx       = max(80, $descColPx);
@endphp

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>

/* ══════════════════════════════════════
   PAGE SETUP
══════════════════════════════════════ */
@page {
    size: A4 landscape;
    margin: 0;
}

html, body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

* { box-sizing: border-box; }

/* ══════════════════════════════════════
   BACKGROUND IMAGE
══════════════════════════════════════ */
.bg-img {
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    height: 210mm;
    z-index: 1;
    object-fit: cover;
}

/* ══════════════════════════════════════
   FIRST PAGE
══════════════════════════════════════ */
.first {
    position: relative;
    width: 297mm;
    height: 210mm;
    overflow: hidden;
    page-break-after: always;
    break-after: page;
}

.page-inner {
    position: relative;
    z-index: 2;
    padding: 30px 40px;
}

.school-header {
    position: relative;
    width: 100%;
    height: 80px;
}

.logo-box {
    position: absolute;
    left: 0; top: 0;
}

.school-logo { height: 70px; margin-left: 15px; }

.school-text {
    position: absolute;
    width: 100%;
    text-align: center;
    top: 0;
}

.school-name    { font-size: 26px; font-weight: bold; color: #0b5fa5; }
.school-address { font-size: 14px; margin-top: 3px; }
.school-phone   { font-size: 13px; }

.certificate-title {
    text-align: center;
    margin-top: 10px;
    margin-bottom: 20px;
}

.main-title {
    font-size: 20px; font-weight: bold;
    letter-spacing: 1px; color: #1f2c7c;
}

.sub-title {
    font-size: 16px; font-weight: bold;
    margin-top: 5px; color: #1f2c7c;
}

.first-content table {
    width: 85%; margin: auto; border-collapse: collapse;
}

.first-content td { padding: 6px 8px; vertical-align: top; }
.first-content td:first-child { white-space: nowrap; width: 18%; }

.statistics_line {
    display: block;
    width: 100%;
    border-bottom: 1px solid #000;
    padding: 2px 4px;
    min-height: 18px;
}

/* ══════════════════════════════════════
   HEALTH TABLE WRAPPER
   ONE single div — Puppeteer handles
   all page breaks via CSS
══════════════════════════════════════ */
.health-section {
    position: relative;
    width: 297mm;
    padding: 30px 4% 20px 4%;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ══════════════════════════════════════
   TABLE
══════════════════════════════════════ */
.record-table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    table-layout: fixed;
    border: 1px solid #cfe3f5;
    background: #f0f7ff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ── thead repeats on every PDF page automatically ── */
.record-table thead {
    display: table-header-group;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ── Never cut a row across pages ── */
.record-table tbody tr {
    page-break-inside: avoid;
    break-inside:      avoid;
}

/* ── Page break BEFORE first row of each new group ── */
.record-table tbody tr.group-break {
    page-break-before: always;
    break-before:      page;
}

/* ══ HEADER CELLS ══ */
.record-table th {
    padding: 8px 6px;
    font-size: 11px;
    font-weight: 700;
    color: #000;
    background: #dbeafe;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    border: 1px solid #cfe3f5;
    border-bottom: 2px solid #93c5fd;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
}

.record-table th.class-col {
    white-space: normal;
    line-height: 1.2;
    font-size: 9px;
}

/* ══ DATA CELLS (default) ══ */
.record-table td {
    border: 1px solid #d6e6f5;
    padding: 4px 5px;
    font-size: 7pt;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    word-break: break-word;
    background: #ffffff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ══ GROUP CELL ══ */
.group-cell {
    font-weight: bold;
    font-size: 7pt;
    background-color: #f0f9ff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    text-align: left;
    padding-left: 6px;
    vertical-align: middle;
    color: #080808;
}

/* ══ SUBGROUP CELL ══ */
.subgroup-cell {
    font-size: 7pt;
    background-color: #ffffff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    text-align: left;
    vertical-align: middle;
    padding: 3px 4px;
    color: #000;
}

/* ══ ALT ROW ══ */
.bgcolor {
    background-color: #f8fbff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ══ DESCRIPTION CELL ══ */
.desc-cell {
    text-align: left;
    vertical-align: top;
    line-height: 1.4;
    padding: 3px 5px;
}

/* ══ SUB / SUBSUB / TEST CELLS ══ */
.subsub-cell,
.test-cell {
    vertical-align: middle;
    text-align: center;
}

/* ══════════════════════════════════════
   PRINT
══════════════════════════════════════ */
@media print {
    html, body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .record-table thead {
        display: table-header-group;
    }

    .record-table tbody tr {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .record-table tbody tr.group-break {
        page-break-before: always;
        break-before: page;
    }
}

</style>
</head>
<body>

{{-- ══════════════════════════════════════
     FIRST PAGE — General Information
══════════════════════════════════════ --}}
<div class="first">
    <img src="{{ $bgImage['file_path'] }}" class="bg-img">

    <div class="page-inner">

        <div class="school-header">
            <div class="logo-box">
                <img src="{{ $school['logo'] ?? '' }}" class="school-logo">
            </div>
            <div class="school-text">
                <div class="school-name">{{ $school['school_name'] ?? '' }}</div>
                <div class="school-address">{{ $school['address'] ?? '' }}</div>
                <div class="school-phone">Phone: {{ $school['phone'] ?? '' }}</div>
            </div>
        </div>

        <div class="certificate-title">
            <div class="main-title">HEALTH AND ACTIVITY CARD</div>
            <div class="sub-title">GENERAL INFORMATION</div>
        </div>

        <div class="first-content">
            <table>
                <tr>
                    <td>NAME :</td>
                    <td colspan="3">
                        <span class="statistics_line">
                            {{ trim(($parent->first_name ?? '') . ' ' . ($parent->mid_name ?? '') . ' ' . ($parent->last_name ?? '')) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>ADMISSION DATE :</td>
                    <td>
                        <span class="statistics_line">
                            {{ !empty($parent->admission_date) ? date('d-m-Y', strtotime($parent->admission_date)) : '' }}
                        </span>
                    </td>
                    <td>DATE OF BIRTH :</td>
                    <td>
                        <span class="statistics_line">
                            {{ !empty($parent->dob) ? date('d-m-Y', strtotime($parent->dob)) : '' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>M F T :</td>
                    <td><span class="statistics_line">{{ $parent->gender ?? '' }}</span></td>
                    <td>BLOOD GROUP :</td>
                    <td><span class="statistics_line">{{ $parent->blood_group ?? '' }}</span></td>
                </tr>
                <tr>
                    <td>MOTHER'S NAME :</td>
                    <td colspan="3">
                        <span class="statistics_line">{{ $parent->mother_name ?? '' }}</span>
                    </td>
                </tr>
                <tr>
                    <td>FATHER'S NAME :</td>
                    <td colspan="3">
                        <span class="statistics_line">{{ $parent->father_name ?? '' }}</span>
                    </td>
                </tr>

                @php $chunks = array_chunk($basicInfo, 2); @endphp
                @foreach($chunks as $rowItem)
                <tr>
                    @foreach($rowItem as $item)
                        <td>{{ strtoupper($item['label']) }} :</td>
                        <td><span class="statistics_line">{{ $item['value'] }}</span></td>
                    @endforeach
                    @if(count($rowItem) < 2)
                        <td></td><td></td>
                    @endif
                </tr>
                @endforeach

                <tr>
                    <td>ADDRESS :</td>
                    <td colspan="3">
                        <span class="statistics_line">{{ $parent->permant_add ?? '' }}</span>
                    </td>
                </tr>
                <tr>
                    <td>PHONE NO :</td>
                    <td colspan="3">
                        <span class="statistics_line">
                            {{ $parent->f_mobile ?? $parent->m_mobile ?? '' }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════
     HEALTH TABLE — Single section
     Puppeteer handles all page breaks
     thead repeats on every page auto
══════════════════════════════════════ --}}
<div class="health-section">

    <table class="record-table">

        {{-- thead repeats automatically on every Puppeteer page --}}
        <thead>
            <tr>
                <th style="width:60px;">Fitness</th>
                <th style="width:70px;">Sub</th>
                <th style="width:70px;">Sub Sub</th>
                <th style="width:70px;">Test</th>
                <th style="width:{{ $descColPx }}px;" class="desc-cell">Description</th>
                @foreach($student_id_array_new as $cls => $id)
                    <th class="class-col" style="width:{{ $classColPx }}px;">
                        Class {{ $cls }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach($processedRows as $index => $row)

            {{--
                group-break: triggers CSS page-break-before on first row
                of every new group EXCEPT the very first group (index 0)
            --}}
            <tr class="{{ ($row['is_group_start'] && $index > 0) ? 'group-break' : '' }}">

                {{-- Fitness / Group --}}
                @if($row['show_group'])
                    <td class="group-cell"
                        rowspan="{{ $row['group_rowspan'] }}"
                        style="width:60px;">
                        {{ $row['group'] }}
                    </td>
                @endif

                {{-- Sub Group --}}
                @if($row['show_sub'])
                    <td class="subgroup-cell"
                        rowspan="{{ $row['sub_rowspan'] }}"
                        style="width:70px;"
                        title="{{ $row['sub_group_full'] }}">
                        {{ $row['sub_group_display'] }}
                    </td>
                @endif

                {{-- Sub Sub --}}
                <td class="bgcolor subsub-cell"
                    style="width:70px;"
                    title="{{ $row['sub_sub_full'] }}">
                    {{ $row['sub_sub_display'] }}
                </td>

                {{-- Test --}}
                <td class="bgcolor test-cell"
                    style="width:70px;"
                    title="{{ $row['test_full'] }}">
                    {{ $row['test_display'] }}
                </td>

                {{-- Description --}}
                <td class="bgcolor desc-cell"
                    style="width:{{ $descColPx }}px;"
                    title="{{ $row['desc_full'] }}">
                    {{ $row['desc_display'] }}
                </td>

                {{-- Class value columns --}}
                @foreach($student_id_array_new as $cls => $id)
                    <td class="bgcolor"
                        style="width:{{ $classColPx }}px;">
                        {{ $allClassHealth[$cls][$row['test']] ?? '' }}
                    </td>
                @endforeach

            </tr>
            @endforeach
        </tbody>

    </table>

</div>

</body>
</html>