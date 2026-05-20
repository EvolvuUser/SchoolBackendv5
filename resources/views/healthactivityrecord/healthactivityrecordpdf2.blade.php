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

// 20-05-2026 -> First try

/* ── STEP 3: Smart page splitting ── */
// $pageUsableHeight = 670;
// $headerHeight     = 90;

// $pages = [];
// $currentPage = [];
// $currentHeight = $headerHeight;

// $currentGroup = null;
// $currentSub   = null;

// foreach ($flatRows as $row) {

//     /* ── Truncate sub_group (Sub column) ── */
//     $row['sub_group_full']    = $row['sub_group'] ?? '';
//     $row['sub_group_display'] = mb_strlen($row['sub_group'] ?? '') > 35
//         ? mb_substr($row['sub_group'] ?? '', 0, 32) . '…'
//         : ($row['sub_group'] ?? '');

//     /* ── Clean and Truncate description ── */
//     $cleanDesc = trim(preg_replace('/\s+/', ' ', strip_tags($row['desc'] ?? '')));
//     $row['desc_full']    = $cleanDesc;
//     $row['desc_display'] = mb_strlen($cleanDesc) > 100
//         ? mb_substr($cleanDesc, 0, 97) . '…'
//         : $cleanDesc;

//     /* ── Truncate sub_sub ── */
//     $cleanSubSub = trim($row['sub_sub'] ?? '');
//     $row['sub_sub_full']    = $cleanSubSub;
//     $row['sub_sub_display'] = mb_strlen($cleanSubSub) > 35
//         ? mb_substr($cleanSubSub, 0, 32) . '…'
//         : $cleanSubSub;

//     /* ── Truncate test ── */
//     $cleanTest = trim($row['test'] ?? '');
//     $row['test_full']    = $cleanTest;
//     $row['test_display'] = mb_strlen($cleanTest) > 35
//         ? mb_substr($cleanTest, 0, 32) . '…'
//         : $cleanTest;

//     /* ── Fixed height buckets based on desc length ── */
//     $descLen = mb_strlen($row['desc_display']);
//     if ($descLen <= 40) {
//         $rowHeight = 30;
//     } elseif ($descLen <= 80) {
//         $rowHeight = 44;
//     } else {
//         $rowHeight = 58;
//     }

//     /* ── Extra height for new group/subgroup ── */
//     $extraHeight = 0;
//     if ($currentGroup !== $row['group']) {
//         $extraHeight += 12;
//     } elseif ($currentSub !== $row['sub_group']) {
//         $extraHeight += 8;
//     }

//     $requiredHeight = $rowHeight + $extraHeight;

//     /* ── Page break ── */
//     if (($currentHeight + $requiredHeight > $pageUsableHeight) && !empty($currentPage)) {
//         $pages[]       = $currentPage;
//         $currentPage   = [];
//         $currentHeight = $headerHeight;
//         $currentGroup  = null;
//         $currentSub    = null;
//     }

//     $currentPage[]  = $row;
//     $currentHeight += $requiredHeight;
//     $currentGroup   = $row['group'];
//     $currentSub     = $row['sub_group'];
// }

// if (!empty($currentPage)) {
//     $pages[] = $currentPage;
// }

/* ── Recalculate rowspans per page ── */
// $finalPages = [];
// foreach ($pages as $pageRows) {
//     $groupCounts = [];
//     $subCounts   = [];

//     foreach ($pageRows as $row) {
//         $gKey  = $row['group'];
//         $sgKey = $row['group'] . '||' . $row['sub_group'];
//         $groupCounts[$gKey]  = ($groupCounts[$gKey]  ?? 0) + 1;
//         $subCounts[$sgKey]   = ($subCounts[$sgKey]   ?? 0) + 1;
//     }

//     $seenGroups = [];
//     $seenSubs   = [];
//     $processed  = [];

//     foreach ($pageRows as $row) {
//         $gKey  = $row['group'];
//         $sgKey = $row['group'] . '||' . $row['sub_group'];

//         $row['show_group']    = !isset($seenGroups[$gKey]);
//         $row['group_rowspan'] = $row['show_group'] ? $groupCounts[$gKey] : 0;

//         $row['show_sub']    = !isset($seenSubs[$sgKey]);
//         $row['sub_rowspan'] = $row['show_sub'] ? $subCounts[$sgKey] : 0;

//         $seenGroups[$gKey] = true;
//         $seenSubs[$sgKey]  = true;

//         $processed[] = $row;
//     }

//     $finalPages[] = $processed;
// }



// 2 nd try
/* ─────────────────────────────────────────────
   STEP 3 : SMART PAGE SPLITTING
───────────────────────────────────────────── */

$pageUsableHeight = 730;
$headerHeight     = 90;

$pages = [];

$currentPage   = [];
$currentHeight = $headerHeight;

$currentGroup = null;
$currentSub   = null;

foreach ($flatRows as $row) {

    /* ─────────────────────────────────────────
       CLEAN DATA
    ───────────────────────────────────────── */

    $row['group'] = trim($row['group'] ?? '');

    /* ---------- SUB GROUP ---------- */

    $row['sub_group_full'] = trim($row['sub_group'] ?? '');

    $row['sub_group_display'] =
        mb_strlen($row['sub_group_full']) > 50
        ? mb_substr($row['sub_group_full'], 0, 47) . '...'
        : $row['sub_group_full'];

    /* ---------- DESCRIPTION ---------- */

    $cleanDesc = trim(
        preg_replace(
            '/\s+/',
            ' ',
            strip_tags($row['desc'] ?? '')
        )
    );

    $row['desc_full'] = $cleanDesc;

    /*
    |--------------------------------------------------------------------------
    | KEEP FULL DESCRIPTION
    |--------------------------------------------------------------------------
    */

    $row['desc_display'] = $cleanDesc;

    /* ---------- SUB SUB ---------- */

    $cleanSubSub = trim($row['sub_sub'] ?? '');

    $row['sub_sub_full'] = $cleanSubSub;

    $row['sub_sub_display'] =
        mb_strlen($cleanSubSub) > 40
        ? mb_substr($cleanSubSub, 0, 37) . '...'
        : $cleanSubSub;

    /* ---------- TEST ---------- */

    $cleanTest = trim($row['test'] ?? '');

    $row['test_full'] = $cleanTest;

    $row['test_display'] =
        mb_strlen($cleanTest) > 40
        ? mb_substr($cleanTest, 0, 37) . '...'
        : $cleanTest;

    /* ─────────────────────────────────────────
       STABLE ROW HEIGHT ESTIMATION
    ───────────────────────────────────────── */

    $descLines = max(
        1,
        ceil(mb_strlen($row['desc_display']) / 55)
    );

    $subLines = max(
        1,
        ceil(mb_strlen($row['sub_group_display']) / 22)
    );

    $subSubLines = max(
        1,
        ceil(mb_strlen($row['sub_sub_display']) / 22)
    );

    $testLines = max(
        1,
        ceil(mb_strlen($row['test_display']) / 22)
    );

    /*
    |--------------------------------------------------------------------------
    | Take tallest wrapped column
    |--------------------------------------------------------------------------
    */

    $maxLines = max(
        $descLines,
        $subLines,
        $subSubLines,
        $testLines
    );

    /*
    |--------------------------------------------------------------------------
    | Stable row heights
    |--------------------------------------------------------------------------
    */

    if ($maxLines <= 1) {
        $rowHeight = 28;
    }
    elseif ($maxLines == 2) {
        $rowHeight = 42;
    }
    elseif ($maxLines == 3) {
        $rowHeight = 56;
    }
    elseif ($maxLines == 4) {
        $rowHeight = 72;
    }
    else {
        $rowHeight = 88;
    }

    /* ─────────────────────────────────────────
       EXTRA HEIGHT FOR NEW GROUPS
    ───────────────────────────────────────── */

    $extraHeight = 0;

    if ($currentGroup !== $row['group']) {
        $extraHeight += 12;
    }
    elseif ($currentSub !== $row['sub_group']) {
        $extraHeight += 8;
    }

    $requiredHeight = $rowHeight + $extraHeight;

    /* ─────────────────────────────────────────
       PAGE BREAK
    ───────────────────────────────────────── */

    if (
        ($currentHeight + $requiredHeight > $pageUsableHeight)
        && !empty($currentPage)
    ) {

        $pages[] = $currentPage;

        $currentPage   = [];
        $currentHeight = $headerHeight;

        /*
        |--------------------------------------------------------------------------
        | Reset rowspan tracking
        |--------------------------------------------------------------------------
        */

        $currentGroup = null;
        $currentSub   = null;
    }

    /* ---------- ADD ROW ---------- */

    $currentPage[] = $row;

    $currentHeight += $requiredHeight;

    $currentGroup = $row['group'];
    $currentSub   = $row['sub_group'];
}

/* ---------- LAST PAGE ---------- */

if (!empty($currentPage)) {
    $pages[] = $currentPage;
}

/* ─────────────────────────────────────────────
   RECALCULATE ROWSPANS PAGE-WISE
───────────────────────────────────────────── */

$finalPages = [];

foreach ($pages as $pageRows) {

    $groupCounts = [];
    $subCounts   = [];

    /* ---------- COUNT ROWS ---------- */

    foreach ($pageRows as $row) {

        $gKey  = $row['group'];

        $sgKey =
            $row['group'] . '||' . $row['sub_group'];

        $groupCounts[$gKey] =
            ($groupCounts[$gKey] ?? 0) + 1;

        $subCounts[$sgKey] =
            ($subCounts[$sgKey] ?? 0) + 1;
    }

    /* ---------- APPLY ROWSPANS ---------- */

    $seenGroups = [];
    $seenSubs   = [];

    $processed = [];

    foreach ($pageRows as $row) {

        $gKey  = $row['group'];

        $sgKey =
            $row['group'] . '||' . $row['sub_group'];

        /* ---------- GROUP ---------- */

        $row['show_group'] =
            !isset($seenGroups[$gKey]);

        $row['group_rowspan'] =
            $row['show_group']
            ? $groupCounts[$gKey]
            : 0;

        /* ---------- SUB GROUP ---------- */

        $row['show_sub'] =
            !isset($seenSubs[$sgKey]);

        $row['sub_rowspan'] =
            $row['show_sub']
            ? $subCounts[$sgKey]
            : 0;

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

html,
body {
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

/* ================= TITLE ================= */

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

/* ================= FIRST PAGE CONTENT ================= */

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

/* ================= HEALTH PAGES ================= */

.health-page {
    position: relative;
    width: 297mm;
    min-height: 210mm;
    overflow: hidden;

    page-break-after: always;
    break-after: page;
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

    table-layout: fixed;

    font-size: 9px;

    border: 1px solid #cfe3f5;

    background: #f0f7ff;

    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;

    page-break-inside: avoid;
    break-inside: avoid;
}

/* ================= TABLE HEADER ================= */

.record-table thead {
    display: table-header-group;
}

.record-table th {
    padding: 8px 6px;

    font-size: 10px;
    font-weight: 700;

    color: #000000;

    background: #dbeafe;

    border: 1px solid #cfe3f5;
    border-bottom: 2px solid #93c5fd;

    text-align: center;

    white-space: normal;
    word-break: break-word;

    line-height: 1.2;

    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.record-table th.class-col {
    white-space: normal;
    line-height: 1.2;
}

/* ================= TABLE DATA ================= */

.record-table td {
    border: 1px solid #d6e6f5;

    padding: 4px 5px;

    text-align: center;
    vertical-align: top;

    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;

    line-height: 1.3;

    background: #ffffff;

    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* IMPORTANT */
/* REMOVE ROW BREAK RESTRICTIONS */

.record-table tbody tr {
    page-break-inside: auto;
    break-inside: auto;
}

/* ================= COLUMN WIDTHS ================= */

.record-table th:nth-child(1) {
    width: 70px;
}

/* Fitness */

.record-table th:nth-child(2) {
    width: 80px;
}

/* Sub Group */

.record-table th:nth-child(3) {
    width: 80px;
}

/* Sub Sub */

.record-table th:nth-child(4) {
    width: 80px;
}

/* Test */

.record-table th:nth-child(5) {
    width: 170px;
}

/* Description */

/* ================= GROUP CELL ================= */

.group-cell {
    font-weight: bold;

    background-color: #f0f9ff;

    text-align: left;

    padding-left: 8px;

    color: #080808;

    vertical-align: top;

    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ================= SUBGROUP ================= */

.subgroup-cell {
    background-color: #ffffff;

    text-align: left;

    color: #000000;

    vertical-align: top;

    font-size: 7pt;

    line-height: 1.3;

    padding: 4px;

    word-break: break-word;

    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ================= DESCRIPTION ================= */

.record-table td.desc-cell {
    max-width: 170px;

    font-size: 7pt;

    line-height: 1.35;

    text-align: left;

    vertical-align: top;

    padding: 4px 5px;

    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}

/* ================= SUB SUB + TEST ================= */

.record-table td:nth-child(3),
.record-table td:nth-child(4) {
    font-size: 7pt;

    line-height: 1.3;

    padding: 4px;

    vertical-align: top;

    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}

/* ================= ALT ROW BG ================= */

.bgcolor {
    background-color: #f8fbff;

    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ================= PRINT ================= */

@media print {

    html,
    body {
        overflow: visible;

        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .health-page {
        overflow: hidden;
    }

    .record-table {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .record-table thead {
        display: table-header-group;
    }

    .record-table tbody tr {
        page-break-inside: auto;
        break-inside: auto;
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
                    <th>Fitness Component</th>
                    <th>Sub Group</th>
                    <th>Sub Sub Group</th>
                    <th>Test Parameter</th>
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

                    // {{-- <td class="bgcolor">{{ $row['sub_sub'] }}</td>
                    // <td class="bgcolor">{{ $row['test'] }}</td>
                    // <td class="bgcolor">{{ $row['desc'] }}</td> --}}

                    // {{-- Sub Sub --}}
  <td class="bgcolor" title="{{ $row['sub_sub_full'] }}">
      {{ $row['sub_sub_display'] }}
   </td>


   <td class="bgcolor" title="{{ $row['test_full'] }}">
       {{ $row['test_display'] }}
   </td>

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