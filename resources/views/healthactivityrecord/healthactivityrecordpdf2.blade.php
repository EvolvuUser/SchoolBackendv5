@php
$school = getSchoolDetails();

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

/* ── STEP 3: Chunk and calculate rowspans per page ── */
$rowsPerPage = 22;
$chunks      = array_chunk($flatRows, $rowsPerPage);
$pages       = [];

// foreach ($chunks as $pageRows) {
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

//         $row['show_sub']      = !isset($seenSubs[$sgKey]);
//         $row['sub_rowspan']   = $row['show_sub'] ? $subCounts[$sgKey] : 0;

//         $seenGroups[$gKey] = true;
//         $seenSubs[$sgKey]  = true;

//         $processed[] = $row;
//     }

//     $pages[] = $processed;
// }

/* ── STEP 3: Chunk by estimated height, not row count ── */
$pageHeightLimit = 480; // tune this (usable px inside page-content)
$pages           = [];
$currentPage     = [];
$currentHeight   = 0;

foreach ($flatRows as $row) {

    // Estimate row height based on description length
    $descLength  = strlen($row['desc'] ?? '');
    $descLines   = max(1, ceil($descLength / 30)); // ~30 chars per line in your font/column width
    $baseHeight  = 22;  // minimum row height in px
    $lineHeight  = 14;  // extra px per extra line
    $rowHeight   = $baseHeight + (($descLines - 1) * $lineHeight);

    // Also account for sub_sub wrapping (if long)
    $subSubLength = strlen($row['sub_sub'] ?? '');
    $subSubLines  = max(1, ceil($subSubLength / 20));
    $rowHeight    = max($rowHeight, $baseHeight + (($subSubLines - 1) * $lineHeight));

    // If adding this row exceeds page, start new page
    if ($currentHeight + $rowHeight > $pageHeightLimit && count($currentPage) > 0) {
        $pages[]       = $currentPage;
        $currentPage   = [];
        $currentHeight = 0;
    }

    $currentPage[] = $row;
    $currentHeight += $rowHeight;
}

// Add last page
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
}

/* Prevent overflow issues */
* {
    box-sizing: border-box;
}
  /* .statistics_line {
    display: block;              
    width: 100%;
    border-bottom: 1px solid #000;
    padding: 2px 0;
    min-height: 16px;  
 } */
/* ================= COMMON BACKGROUND ================= */
/* .bg-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
} */

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
}

/* Wrapper to avoid absolute stacking issues */
.page-inner {
    position: relative;
    z-index: 2;
    padding: 30px 40px;
}



.school-header table {
    width: 100%;
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

.school-header {
    position: relative;
    width: 100%;
    height: 80px;
}

/* LOGO */
.logo-box {
    position: absolute;
    left: 0;
    top: 0;
}

.school-logo {
    height: 70px;
    margin-left: 15px;
}

/* CENTER TEXT (TRUE CENTER) */
.school-text {
    position: absolute;
    width: 100%;
    text-align: center;
    top: 0;
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
    /* font-size: 14px; */
}

.first-content td {
    padding: 6px 8px;
    vertical-align: top;
}

.first-content td:first-child {
    white-space: nowrap;
    /* font-weight: bold; */
    width: 18%;
}

.statistics_line {
    display: block;
    width: 100%;
    border-bottom: 1px solid #000;
    padding: 2px 4px;
    min-height: 18px;
}
   
/* Underline effect */
/* .statistics_line {
    border-bottom: 1px solid #000;
    min-height: 16px;
    display: block;
} */

/* ================= TABLE PAGES ================= */
/* .health-page {
    position: relative;
    width: 297mm;
    min-height: 210mm;
    page-break-after: always;
    break-after: page;
} */

.health-page {
    position: relative;
    width: 297mm;
    height: 210mm; /* FIXED height (important) */
    page-break-after: always;
    overflow: hidden;
}

.health-page:last-child {
    page-break-after: auto;
}

/* Content above background */
.page-content {
    position: relative;
    z-index: 2;
    width: 92%;
    margin: auto;
    padding-top: 30px;
    padding-right: 1px;
    padding-left: 1px;
    
}

/* ================= TABLE ================= */

/* .record-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    table-layout: fixed;
} */

.record-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    table-layout: fixed;
     /* page-break-after: auto; */
    break-after: auto;
}

/* HEADER */
.record-table thead {
    display: table-header-group;
}

/* CELLS */
.record-table th {
    border: 1px solid #000;
    padding: 6px;
    background-color: #f2f2f2;
    font-weight: bold;
    text-align: center;
    vertical-align: middle;
}

.record-table td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* PREVENT ROW BREAK IN PDF/PRINT */
/* .record-table tr {
    page-break-inside: avoid;
    break-inside: avoid;
} */

/* GROUP ROW */
.group-cell {
    font-weight: bold;
    background-color: #efefef;
    text-align: left;
    padding-left: 6px;
}

/* SUB GROUP ROW */
.subgroup-cell {
    background-color: #fafafa;
    text-align: left;
}

/* REMOVE ANY GLOBAL TABLE OVERRIDE ISSUES */
table {
    width: 100%;
    border-collapse: collapse;
}

/* REMOVE PAGE CUTTING ISSUE */
body {
    overflow: visible;
}

/* OPTIONAL: BETTER PRINT CONTROL */
@media print {
    .record-table {
        font-size: 9px;
    }

    .record-table tr {
        page-break-inside: avoid;
    }

    thead {
        display: table-header-group;
    }
}
</style>
</head>

<body>

{{-- ================= FIRST PAGE ================= --}}
<div class="first">
    <img src="{{ public_path('health3_bg.jpg') }}" class="bg-img">

    <div class="page-inner">

        {{-- HEADER --}}
        {{-- <div class="school-header">
            <table width="100%">
                <tr>
                    <td width="15%">
                        <img src="{{ $school['logo'] ?? '' }}" class="school-logo">
                    </td>
                    <td align="center">
                        <div class="school-name">{{ $school['school_name'] ?? '' }}</div>
                        <div class="school-address">{{ $school['address'] ?? '' }}</div>
                        <div class="school-phone">Phone: {{ $school['phone'] ?? '' }}</div>
                    </td>
                </tr>
            </table>
        </div> --}}

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

        <!-- FULL WIDTH ROW -->
        <tr>
            <td>NAME :</td>
            <td colspan="3">
                <span class="statistics_line">
                    {{ ($parent->first_name ?? '') . ' ' . ($parent->mid_name ?? '') . ' ' . ($parent->last_name ?? '') }}
                </span>
            </td>
        </tr>

        <!-- 2 COLUMN ROW -->
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

        <!-- FULL WIDTH ROW -->
        <tr>
            <td>MOTHER'S NAME :</td>
            <td colspan="3">
                <span class="statistics_line">{{ $parent->mother_name ?? '' }}</span>
            </td>
        </tr>

        <!-- FULL WIDTH ROW -->
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

        <!-- FULL WIDTH ADDRESS -->
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

{{-- ================= TABLE ================= --}}
{{-- @foreach($pages as $pageIndex => $pageRows)
<div class="health-page">
    <img src="{{ public_path('health3_bg.jpg') }}" class="bg-img">
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
                        <th>Class {{ $cls }}</th>
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

                    <td>{{ $row['sub_sub'] }}</td>
                    <td>{{ $row['test'] }}</td>
                    <td>{{ $row['desc'] }}</td>

                    @foreach($student_id_array_new as $cls => $id)
                        <td>{{ $allClassHealth[$cls][$row['test']] ?? '' }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach --}}

@foreach($finalPages as $pageIndex => $pageRows)
<div class="health-page">
    <img src="{{ public_path('health3_bg.jpg') }}" class="bg-img">
    <div class="page-content">

        {{-- @if($pageIndex === 0) --}}
        <h2 style="text-align: center; font-size: 20px; font-weight: bold; font-family: Georgia, 'Times New Roman', Times, serif; margin-bottom: 10px; letter-spacing: 1px; color: #1f2c7c;">
            HEALTH AND ACTIVITY RECORD
        </h2>
        {{-- @endif --}}

        <table class="record-table">
            <thead>
                <tr>
                    <th>Fitness</th>
                    <th>Sub</th>
                    <th>Sub Sub</th>
                    <th>Test</th>
                    <th>Description</th>
                    @foreach($student_id_array_new as $cls => $id)
                        <th>Class {{ $cls }}</th>
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

                    <td>{{ $row['sub_sub'] }}</td>
                    <td>{{ $row['test'] }}</td>
                    <td>{{ $row['desc'] }}</td>

                    @foreach($student_id_array_new as $cls => $id)
                        <td>{{ $allClassHealth[$cls][$row['test']] ?? '' }}</td>
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