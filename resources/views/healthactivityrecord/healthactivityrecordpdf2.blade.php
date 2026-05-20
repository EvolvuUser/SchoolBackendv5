@php
$school = getSchoolDetails();
$bgImage = getHealthBgImage();

$class = get_class_section_of_student($student_id);
$class_array = !empty($class) ? explode(' ', $class) : [];
$class_name = (!empty($class_array) && isset($class_array[0])) ? (int)$class_array[0] : 0;

$parent_info = get_student_parent_info($student_id, $customClaims);
$health_activity_data = check_health_activity_data_exist_for_studentid($student_id);

$parent = $parent_info[0] ?? null;
$health = $health_activity_data ?? [];

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
            'group'         => $groupName,
            'sub_group'     => '',
            'sub_sub_group' => '',
            'test'          => $key,
            'desc'          => $description[$key] ?? '',
        ];
    } else {
        foreach ($paths as $p) {
            $tableData[] = [
                'group'         => $groupName,
                'sub_group'     => $p[0] ?? '',
                'sub_sub_group' => $p[1] ?? '',
                'test'          => $p[2] ?? $key,
                'desc'          => $description[$key] ?? '',
            ];
        }
    }
}

/* ── STEP 1: Build $grouped from $tableData ── */
$grouped = [];
foreach ($tableData as $row) {
    $g   = $row['group'];
    $sg  = $row['sub_group'];
    $ssg = $row['sub_sub_group'];
    $grouped[$g][$sg][$ssg][] = $row;
}

/* ── STEP 2: Flatten $grouped into $flatRows ── */
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

/* ── STEP 3: Chunk by estimated height for Puppeteer A4 landscape ── */
// $pageUsableHeight = 670; // A4 landscape at 96dpi (~794px) minus padding/title/thead
// $headerHeight     = 90;  // title h2 (~40px) + thead row (~50px)

// $pages        = [];
// $currentPage  = [];
// $currentHeight = $headerHeight; // start with header already counted

// foreach ($flatRows as $row) {

//     // Estimate row height based on description length
//     $descLength  = strlen($row['desc'] ?? '');
//     $descLines   = max(1, ceil($descLength / 30)); // ~30 chars per line
//     $baseHeight  = 22;  // minimum row height in px
//     $lineHeight  = 14;  // extra px per extra line
//     $rowHeight   = $baseHeight + (($descLines - 1) * $lineHeight);

//     // Also account for sub_sub wrapping
//     $subSubLength = strlen($row['sub_sub'] ?? '');
//     $subSubLines  = max(1, ceil($subSubLength / 20));
//     $rowHeight    = max($rowHeight, $baseHeight + (($subSubLines - 1) * $lineHeight));

//     // If adding this row exceeds page height, start a new page
//     if ($currentHeight + $rowHeight > $pageUsableHeight && count($currentPage) > 0) {
//         $pages[]       = $currentPage;
//         $currentPage   = [];
//         $currentHeight = $headerHeight; // reset with header height for new page
//     }

//     $currentPage[] = $row;
//     $currentHeight += $rowHeight;
// }

// // Add last page
// if (!empty($currentPage)) {
//     $pages[] = $currentPage;
// }

// 20-05-2026
/* ── STEP 3: Smart page splitting ── */

// $pageUsableHeight = 670;
// $headerHeight     = 90;

// $pages = [];
// $currentPage = [];
// $currentHeight = $headerHeight;

// $currentGroup = null;
// $currentSub   = null;

// foreach ($flatRows as $row) {

//     // Estimate dynamic row height
//     $descLength = strlen(strip_tags($row['desc'] ?? ''));
//     $descLines  = max(1, ceil($descLength / 35));

//     $subSubLength = strlen($row['sub_sub'] ?? '');
//     $subSubLines  = max(1, ceil($subSubLength / 22));

//     $lineCount = max($descLines, $subSubLines);

//     $rowHeight = 24 + (($lineCount - 1) * 14);

//     /*
//     ADD EXTRA HEIGHT
//     when new group/subgroup starts
//     because rowspan cells become taller visually
//     */

//     $extraHeight = 0;

//     if ($currentGroup !== $row['group']) {
//         $extraHeight += 12;
//     }

//     if (
//         $currentGroup === $row['group'] &&
//         $currentSub !== $row['sub_group']
//     ) {
//         $extraHeight += 8;
//     }

//     $requiredHeight = $rowHeight + $extraHeight;

//     /*
//     PAGE BREAK
//     */

//     if (
//         ($currentHeight + $requiredHeight > $pageUsableHeight)
//         && !empty($currentPage)
//     ) {

//         $pages[] = $currentPage;

//         $currentPage = [];
//         $currentHeight = $headerHeight;

//         /*
//         RESET
//         so rowspan starts fresh
//         */

//         $currentGroup = null;
//         $currentSub   = null;
//     }

//     $currentPage[] = $row;

//     $currentHeight += $requiredHeight;

//     $currentGroup = $row['group'];
//     $currentSub   = $row['sub_group'];
// }


// 2nd 
// foreach ($flatRows as $row) {

//     /* ── Truncate description ── */
//     $row['desc_full']    = $row['desc'];
//     $row['desc_display'] = mb_strlen(strip_tags($row['desc'])) > 100
//         ? mb_substr(strip_tags($row['desc']), 0, 97) . '…'
//         : strip_tags($row['desc']);

//     // Estimate dynamic row height
//     $descLength = strlen($row['desc_display']);
//     $descLines  = max(1, ceil($descLength / 35));

//     $subSubLength = strlen($row['sub_sub'] ?? '');
//     $subSubLines  = max(1, ceil($subSubLength / 22));

//     $lineCount = max($descLines, $subSubLines);

//     $rowHeight = 24 + (($lineCount - 1) * 14);

//     /*
//     ADD EXTRA HEIGHT
//     when new group/subgroup starts
//     because rowspan cells become taller visually
//     */

//     $extraHeight = 0;

//     if ($currentGroup !== $row['group']) {
//         $extraHeight += 12;
//     }

//     if (
//         $currentGroup === $row['group'] &&
//         $currentSub !== $row['sub_group']
//     ) {
//         $extraHeight += 8;
//     }

//     $requiredHeight = $rowHeight + $extraHeight;

//     /*
//     PAGE BREAK
//     */

//     if (
//         ($currentHeight + $requiredHeight > $pageUsableHeight)
//         && !empty($currentPage)
//     ) {

//         $pages[] = $currentPage;

//         $currentPage = [];
//         $currentHeight = $headerHeight;

//         /*
//         RESET
//         so rowspan starts fresh
//         */

//         $currentGroup = null;
//         $currentSub   = null;
//     }

//     $currentPage[] = $row;

//     $currentHeight += $requiredHeight;

//     $currentGroup = $row['group'];
//     $currentSub   = $row['sub_group'];
// }

// if (!empty($currentPage)) {
//     $pages[] = $currentPage;
// }

/* ── STEP 3: Smart page splitting ── */
$pageUsableHeight = 670;
$headerHeight     = 90;

$pages = [];
$currentPage = [];
$currentHeight = $headerHeight;

$currentGroup = null;
$currentSub   = null;

// foreach ($flatRows as $row) {

//     /* ── Truncate description ── */
//     $row['desc_full']    = $row['desc'];
//     $row['desc_display'] = mb_strlen(strip_tags($row['desc'])) > 100
//         ? mb_substr(strip_tags($row['desc']), 0, 97) . '…'
//         : strip_tags($row['desc']);

//     /* ── Truncate sub_sub ── */
//     $row['sub_sub_full']    = $row['sub_sub'] ?? '';
//     $row['sub_sub_display'] = mb_strlen($row['sub_sub'] ?? '') > 40
//         ? mb_substr($row['sub_sub'] ?? '', 0, 37) . '…'
//         : ($row['sub_sub'] ?? '');

//     /* ── Truncate test ── */
//     $row['test_full']    = $row['test'] ?? '';
//     $row['test_display'] = mb_strlen($row['test'] ?? '') > 40
//         ? mb_substr($row['test'] ?? '', 0, 37) . '…'
//         : ($row['test'] ?? '');

//     // Estimate dynamic row height using display values
//     $descLength   = strlen($row['desc_display']);
//     $descLines    = max(1, ceil($descLength / 30));

//     $subSubLength = strlen($row['sub_sub_display']);
//     $subSubLines  = max(1, ceil($subSubLength / 18));

//     $lineCount = max($descLines, $subSubLines);

//     $rowHeight = 22 + (($lineCount - 1) * 12);

//     /*
//     ADD EXTRA HEIGHT
//     when new group/subgroup starts
//     because rowspan cells become taller visually
//     */

//     $extraHeight = 0;

//     if ($currentGroup !== $row['group']) {
//         $extraHeight += 12;
//     }

//     if (
//         $currentGroup === $row['group'] &&
//         $currentSub !== $row['sub_group']
//     ) {
//         $extraHeight += 8;
//     }

//     $requiredHeight = $rowHeight + $extraHeight;

//     /*
//     PAGE BREAK
//     */

//     if (
//         ($currentHeight + $requiredHeight > $pageUsableHeight)
//         && !empty($currentPage)
//     ) {

//         $pages[] = $currentPage;

//         $currentPage   = [];
//         $currentHeight = $headerHeight;

//         /*
//         RESET
//         so rowspan starts fresh
//         */

//         $currentGroup = null;
//         $currentSub   = null;
//     }

//     $currentPage[] = $row;

//     $currentHeight += $requiredHeight;

//     $currentGroup = $row['group'];
//     $currentSub   = $row['sub_group'];
// }

foreach ($flatRows as $row) {

    /* ── Clean and Truncate description ── */
    $cleanDesc = strip_tags($row['desc'] ?? '');  // strip HTML first
    $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc); // remove extra spaces/newlines
    $cleanDesc = trim($cleanDesc);

    $row['desc_full']    = $cleanDesc;
    $row['desc_display'] = mb_strlen($cleanDesc) > 100
        ? mb_substr($cleanDesc, 0, 97) . '…'
        : $cleanDesc;

    /* ── Truncate sub_sub ── */
    $row['sub_sub_full']    = $row['sub_sub'] ?? '';
    $row['sub_sub_display'] = mb_strlen($row['sub_sub'] ?? '') > 35
        ? mb_substr($row['sub_sub'] ?? '', 0, 32) . '…'
        : ($row['sub_sub'] ?? '');

    /* ── Truncate test ── */
    $row['test_full']    = $row['test'] ?? '';
    $row['test_display'] = mb_strlen($row['test'] ?? '') > 35
        ? mb_substr($row['test'] ?? '', 0, 32) . '…'
        : ($row['test'] ?? '');

    // Estimate dynamic row height
    $descLength   = strlen($row['desc_display']);
    $descLines    = max(1, ceil($descLength / 30));

    $subSubLength = strlen($row['sub_sub_display']);
    $subSubLines  = max(1, ceil($subSubLength / 18));

    $lineCount = max($descLines, $subSubLines);

    $rowHeight = 22 + (($lineCount - 1) * 12);

    $extraHeight = 0;

    if ($currentGroup !== $row['group']) {
        $extraHeight += 12;
    }

    if (
        $currentGroup === $row['group'] &&
        $currentSub !== $row['sub_group']
    ) {
        $extraHeight += 8;
    }

    $requiredHeight = $rowHeight + $extraHeight;

    if (
        ($currentHeight + $requiredHeight > $pageUsableHeight)
        && !empty($currentPage)
    ) {
        $pages[] = $currentPage;
        $currentPage   = [];
        $currentHeight = $headerHeight;
        $currentGroup  = null;
        $currentSub    = null;
    }

    $currentPage[] = $row;
    $currentHeight += $requiredHeight;
    $currentGroup   = $row['group'];
    $currentSub     = $row['sub_group'];
}

if (!empty($currentPage)) {
    $pages[] = $currentPage;
}


/* ── Recalculate rowspans per page ── */
$finalPages = [];
foreach ($pages as $pageRows) {
    $groupCounts = [];
    $subCounts   = [];

    foreach ($pageRows as $row) {
        $gKey  = $row['group'];
        $sgKey = $row['group'] . '||' . $row['sub_group'];
        $groupCounts[$gKey]  = ($groupCounts[$gKey]  ?? 0) + 1;
        $subCounts[$sgKey]   = ($subCounts[$sgKey]   ?? 0) + 1;
    }

    $seenGroups = [];
    $seenSubs   = [];
    $processed  = [];

    foreach ($pageRows as $row) {
        $gKey  = $row['group'];
        $sgKey = $row['group'] . '||' . $row['sub_group'];

        $row['show_group']    = !isset($seenGroups[$gKey]);
        $row['group_rowspan'] = $row['show_group'] ? $groupCounts[$gKey] : 0;

        $row['show_sub']    = !isset($seenSubs[$sgKey]);
        $row['sub_rowspan'] = $row['show_sub'] ? $subCounts[$sgKey] : 0;

        $seenGroups[$gKey] = true;
        $seenSubs[$sgKey]  = true;

        $processed[] = $row;
    }

    $finalPages[] = $processed;
}

/* ── STEP 4: All class health data ── */
$allClassHealth = [];
foreach ($student_id_array_new as $cls => $id) {
    $h = check_health_activity_data_exist_for_studentid($id);
    $allClassHealth[$cls] = $h['value'] ?? [];
}
@endphp

<html>
<head>
<style>
/* ================= PAGE SETUP ================= */
@page {
    size: A4 landscape;
    margin: 0;
}

html, body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    overflow: visible;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

* {
    box-sizing: border-box;
}

/* ================= BACKGROUND IMAGE ================= */
.bg-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 210mm;
    z-index: 1;
}

/* ================= FIRST PAGE ================= */
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

.school-header table {
    width: 100%;
}

.logo-box {
    position: absolute;
    left: 0;
    top: 0;
}

.school-logo {
    height: 70px;
    margin-left: 15px;
}

.school-text {
    position: absolute;
    width: 100%;
    text-align: center;
    top: 0;
}

.school-name {
    font-size: 26px;
    font-weight: bold;
    color: #0b5fa5;
}

.school-address {
    font-size: 14px;
    margin-top: 3px;
}

.school-phone {
    font-size: 13px;
}

/* ===== TITLE ===== */
.certificate-title {
    text-align: center;
    margin-top: 10px;
    margin-bottom: 20px;
}

.main-title {
    font-size: 20px;
    font-weight: bold;
    letter-spacing: 1px;
    color: #1f2c7c;
}

.sub-title {
    font-size: 16px;
    font-weight: bold;
    margin-top: 5px;
    color: #1f2c7c;
}

/* ===== FIRST PAGE CONTENT ===== */
.first-content table {
    width: 85%;
    margin: auto;
    border-collapse: collapse;
}

.first-content td {
    padding: 6px 8px;
    vertical-align: top;
}

.first-content td:first-child {
    white-space: nowrap;
    width: 18%;
}

.statistics_line {
    display: block;
    width: 100%;
    border-bottom: 1px solid #000;
    padding: 2px 4px;
    min-height: 18px;
}

/* ================= HEALTH TABLE PAGES ================= */
.health-page {
    position: relative;
    width: 297mm;
    min-height: 210mm;
    page-break-after: always;
    break-after: page;
    overflow: visible;
}

.health-page:last-child {
    page-break-after: auto;
    break-after: auto;
}

.page-content {
    position: relative;
    z-index: 2;
    width: 92%;
    margin: auto;
    padding-top: 30px;
}

/* ================= TABLE ================= */
.record-table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    font-size: 10px;
    table-layout: fixed;
    border: 1px solid #cfe3f5;
    background: #f0f7ff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    page-break-inside: auto;
    break-inside: auto;
}

.record-table td.desc-cell {
    max-width: 160px;
    min-width: 80px;
    word-break: break-word;
    white-space: normal;
    line-height: 1.3;
    font-size: 7pt;
    vertical-align: top;
    padding: 3px 4px;
}

/* also constrain sub_sub and test columns */
.record-table td:nth-child(3),
.record-table td:nth-child(4) {
    max-width: 90px;
    word-break: break-word;
    white-space: normal;
    font-size: 7pt;
    vertical-align: top;
}

/* Repeat thead on every new page */
.record-table thead {
    display: table-header-group;
}

/* Avoid cutting a single row in half */
.record-table tbody tr {
    page-break-inside: avoid;
    break-inside: avoid;
}

/* HEADER CELLS */
.record-table th {
    padding: 10px 14px;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.05em;
    color: #000000;
    background: #dbeafe;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    border: 1px solid #cfe3f5;
    border-bottom: 2px solid #93c5fd;
    text-align: center;
    white-space: nowrap;
}

.record-table th.class-col {
    white-space: normal;
    line-height: 1.2;
}

/* DATA CELLS */
.record-table td {
    border: 1px solid #d6e6f5;
    padding: 6px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
    background: #ffffff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* GROUP CELL */
.group-cell {
    font-weight: bold;
    background: #e0f2fe;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    text-align: left;
    padding-left: 8px;
    color: #080808;
}

/* SUB GROUP */
.subgroup-cell {
    background-color: #f0f9ff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    text-align: left;
    color: #000000;
}

/* ALT ROW BG */
.bgcolor {
    background-color: #f8fbff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ================= PRINT MEDIA ================= */
@media print {
    html, body {
        overflow: visible;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .record-table {
        font-size: 9px;
        page-break-inside: auto;
        break-inside: auto;
    }

    .record-table thead {
        display: table-header-group;
    }

    .record-table tbody tr {
        page-break-inside: avoid;
        break-inside: avoid;
    }
}
</style>
</head>

<body>

{{-- ================= FIRST PAGE ================= --}}
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
                            {{ ($parent->first_name ?? '') . ' ' . ($parent->mid_name ?? '') . ' ' . ($parent->last_name ?? '') }}
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
                    <td>
                        <span class="statistics_line">{{ $parent->gender ?? '' }}</span>
                    </td>
                    <td>BLOOD GROUP :</td>
                    <td>
                        <span class="statistics_line">{{ $parent->blood_group ?? '' }}</span>
                    </td>
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
                        <td>
                            <span class="statistics_line">{{ $item['value'] }}</span>
                        </td>
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

{{-- ================= TABLE PAGES ================= --}}
@foreach($finalPages as $pageIndex => $pageRows)
<div class="health-page">
    <img src="{{ $bgImage['file_path'] }}" class="bg-img">
    <div class="page-content">

        <h2 style="text-align: center; font-size: 20px; font-weight: bold; font-family: Georgia, 'Times New Roman', Times, serif; margin-bottom: 10px; letter-spacing: 1px; color: #1f2c7c;">
            HEALTH AND ACTIVITY RECORD
        </h2>

        <table class="record-table">
            <thead>
                <tr>
                    <th>Fitness</th>
                    <th>Sub</th>
                    <th>Sub Sub</th>
                    <th>Test</th>
                    <th>Description</th>
                    @foreach($student_id_array_new as $cls => $id)
                        <th class="class-col">Class {{ $cls }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach($pageRows as $row)
                <tr>
                    @if($row['show_group'])
                        <td class="group-cell" rowspan="{{ $row['group_rowspan'] }}">
                            {{ $row['group'] }}
                        </td>
                    @endif

                    @if($row['show_sub'])
                        <td class="subgroup-cell" rowspan="{{ $row['sub_rowspan'] }}">
                            {{ $row['sub_group'] }}
                        </td>
                    @endif

                    {{-- <td class="bgcolor">{{ $row['sub_sub'] }}</td>
                    <td class="bgcolor">{{ $row['test'] }}</td>
                    <td class="bgcolor">{{ $row['desc'] }}</td> --}}

                    {{-- Sub Sub --}}
     <td class="bgcolor" title="{{ $row['sub_sub_full'] }}">
       {{ $row['sub_sub_display'] }}
    </td>

    {{-- Test --}}
    <td class="bgcolor" title="{{ $row['test_full'] }}">
       {{ $row['test_display'] }}
    </td>

    {{-- Description --}}
    <td class="bgcolor desc-cell" title="{{ $row['desc_full'] }}">
      {{ $row['desc_display'] }}
    </td>
                    

                    @foreach($student_id_array_new as $cls => $id)
                        <td class="bgcolor">{{ $allClassHealth[$cls][$row['test']] ?? '' }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>
@endforeach

</body>
</html>