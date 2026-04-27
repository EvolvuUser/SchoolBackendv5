@php
    $school = getSchoolDetails();
    // dd($school);
@endphp

@php
$class = get_class_section_of_student($student_id);
$class_array = !empty($class) ? explode(' ', $class) : [];
$class_name = (!empty($class_array) && isset($class_array[0])) ? (int)$class_array[0] : 0;

$parent_info = get_student_parent_info($student_id, $customClaims);
$health_activity_data = check_health_activity_data_exist_for_studentid($student_id);

$parent = !empty($parent_info) && isset($parent_info[0]) ? $parent_info[0] : null;
$health = !empty($health_activity_data) ? $health_activity_data : [];

if ($class_name >= 1) {
    $student_id_array = [$class_name => $student_id];
    $temp_prev_stud_id = $student_id;
    for ($i = ($class_name - 1); $i >= 1; $i--) {
        $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
        if (empty($temp_prev_stud_id)) break;
        $student_id_array[$i] = $temp_prev_stud_id;
    }
    ksort($student_id_array);
    $student_id_array_new = $student_id_array;
  
} else {
    $student_id_array_new = [];
}

//   dd($student_id_array_new);
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
@endphp

@php
function flattenParamTree($nodes, $prefix = [])
{
    $result = [];
    if (empty($nodes)) return [];
    foreach ($nodes as $node) {
        $current = array_merge($prefix, [$node['label'] ?? '']);
        if (!empty($node['children'])) {
            $result = array_merge($result, flattenParamTree($node['children'], $current));
        } else {
            $result[] = $current;
        }
    }
    return $result; 
     
     
}

@endphp

@php
$tableData = [];

foreach ($val as $key => $value) {
    $groupName = $groupData[$key]['group_name'] ?? 'Other';
    if (strtolower(trim($groupName)) === 'basic information') continue;

    $param = $paramData[$key] ?? [];
    $paths = flattenParamTree($param);

    if (empty($paths)) {
        $tableData[] = [
            'group'         => $groupName,
            'sub_group'     => '',
            'sub_sub_group' => '',
            'test'          => $key,
            'desc'          => $description[$key] ?? '',
        ];
    } else {
        foreach ($paths as $path) {
            $tableData[] = [
                'group'         => $groupName,
                'sub_group'     => $path[0] ?? '',
                'sub_sub_group' => $path[1] ?? '',
                'test'          => $path[2] ?? $key,
                'desc'          => $description[$key] ?? '',
            ];
        }
    }
}

// 4 classes per column-page
$classChunks = array_chunk($student_id_array_new, 4, true);

// 20 rows per page — increase font, better readability
$rowsPerPage = 25;
$rowChunks   = array_chunk($tableData, $rowsPerPage);

@endphp

@php
$allClassHealth = [];
foreach ($student_id_array_new as $cls => $studId) {
    $healthData = check_health_activity_data_exist_for_studentid($studId);
    $allClassHealth[$cls] = $healthData['value'] ?? [];
}
@endphp

<html>
<head>
<style>

  @page {
    size: A4;
    margin: 0;
    padding: 0;
  }

  html, body {
    margin: 0;
    padding: 0;
  }

  /* .statistics_line {
    width: 100%;
    border-bottom: 1px solid #000;
    padding: 3px;
  } */

  .statistics_line {
    display: block;              
    width: 100%;
    border-bottom: 1px solid #000;
    padding: 2px 0;
    min-height: 16px;  
 }

  /* ===== PAGE 1 ===== */
  .first {
    position: relative;
    width: 210mm;
    height: 297mm;
    overflow: hidden;
    page-break-after: always;
  }

  .first .bg-img {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: fill;
    z-index: 0;
  }

  /* .first .first-content {
    position: relative;
    z-index: 1;
  } */

  .first-content table {
    width: 80%;
    border-collapse: collapse;   /* 🔥 removes weird gaps */
 }

 .first-content td {
    padding: 6px 8px;            /* 🔥 creates spacing */
    vertical-align: top;
    font-size: 14px;
 }

 .first-content tr {
    height: 26px;                /* 🔥 row spacing */
 }

 .first-content td:first-child {
    white-space: nowrap;         /* prevents break like "ADMISSION DATE" */
    /* font-weight: bold; */
    padding-right: 10px;
 }

  /* ===== PAGE 2+ ===== */

  /*
   * KEY CHANGE:
   * No fixed height — let content define height.
   * Background image is set via a wrapper that is exactly A4.
   * We use a bg-wrapper behind the content for the image.
   */

  .health-page {
    position: relative;
    width: 210mm;
    min-height: 297mm;        /* ← min not fixed */
    page-break-after: always;
    font-family: Arial, sans-serif;
    box-sizing: border-box;
  }

  .health-page:last-child {
    page-break-after: auto;
  }

  /* Background image stretched full page */
  .health-page .bg-img {
    position: absolute;
    top: 0; left: 0;
    width: 100%; 
    height: 100%;             /* ← follows page height */
    object-fit: fill;
    z-index: 0;
    display: block;
  }

  /* Content on top of bg */
  .health-page .page-content {
    position: relative;       /* ← relative not absolute */
    z-index: 1;
    width: 84%;
    margin: 0 auto;
    padding-top: 155px;       /* ← gap from top = bg image header height, adjust this */
    padding-bottom: 30px;
    box-sizing: border-box;
  }

  /* ===== TABLE ===== */
  .record-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;          /* ← readable font size */
    table-layout: fixed;
  }

  .record-table th {
    border: 1px solid #000;
    padding: 6px 4px;
    background-color: #f2f2f2;
    text-align: center;
    font-weight: bold;
    font-size: 11px;
    word-wrap: break-word;
    overflow-wrap: break-word;
  }

  .record-table td {
    border: 1px solid #000;
    padding: 5px 4px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    background-color: #ffffff;
    vertical-align: middle;
    font-size: 11px;
  }

  .record-table td:last-child,
  .record-table th:last-child {
    border-right: 1px solid #000 !important;
  }

  .group-cell {
    font-weight: bold;
    background-color: #efefef !important;
    text-align: center;
    vertical-align: middle;
  }

  .subgroup-cell {
    background-color: #fafafa !important;
    text-align: center;
    vertical-align: middle;
  }


 .school-header {
    position: absolute;
    top: 40px;      /* safe from top border */
    left: 40px;     /* safe from left border */
    right: 40px;    /* safe from right border */
    width: auto;
 }


 .school-header table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
 }


 .school-header td {
    padding: 0;
    vertical-align: middle;
 }


 .school-logo {
    height: 90px;
    display: block;
    margin: 0;
    padding: 0;
 }


 .school-name {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 34px;
    font-weight: bold;
    color: #0b5fa5;
    letter-spacing: 1.5px;
    margin: 0;
    line-height: 1.2;
 }


 .school-address {
    font-family: "Times New Roman", serif;
    font-size: 16px;
    margin-top: 4px;
    line-height: 1.2;
 }


 .school-phone {
    font-family: "Times New Roman", serif;
    font-size: 14px;
    margin-top: 2px;
 }


 .first-content {
    position: relative;
    margin-top: 220px; /* push below header */
 }

 .certificate-title {
    position: absolute;
    top: 160px;   /* 🔥 increase this */
    width: 100%;
    text-align: center;
    z-index: 2;
    
 }

 .main-title {
    font-family: "Georgia", "Times New Roman", serif;
    font-size: 20px;
    font-weight: bold;
    letter-spacing: 2px;
    color: #1f2c7c;         /* dark blue like your design */
    margin-bottom: 10px;
 }

 .sub-title {
       font-family: "Georgia", "Times New Roman", serif;
    font-size: 18px;
    font-weight: bold;
    letter-spacing: 1px;
    color: #1f2c7c;
    padding-bottom: 20px;
  }

</style>
</head>
<body>

{{-- ===================== PAGE 1 ===================== --}}
{{-- <div class="first">
    <img src="{{ public_path('health3_bg.jpg') }}" class="bg-img">
    <div class="first-content">
        <br><br><br><br>
        <table border="0" style="width:80%; margin-top:20%;" align="center">
            
            <tr>
                <td width="15%">NAME :</td>
                <td colspan="3">
                    <div class="statistics_line">
                        {{ ($parent->first_name ?? '') . ' ' . ($parent->mid_name ?? '') . ' ' . ($parent->last_name ?? '') }}
                    </div>
                </td>
            </tr>
            <tr>
                <td>ADMISSION DATE :</td>
                <td>
                    <div class="statistics_line">
                        {{ !empty($parent->admission_date) ? date('d-m-Y', strtotime($parent->admission_date)) : '' }}
                    </div>
                </td>
                <td>DATE OF BIRTH :</td>
                <td>
                    <div class="statistics_line">
                        {{ !empty($parent->dob) ? date('d-m-Y', strtotime($parent->dob)) : '' }}
                    </div>
                </td>
            </tr>
            <tr>
                <td>M F T :</td>
                <td><div class="statistics_line">{{ $parent->gender ?? '' }}</div></td>
                <td>BLOOD GROUP :</td>
                <td><div class="statistics_line">{{ $parent->blood_group ?? '' }}</div></td>
            </tr>
            <tr>
                <td>MOTHER'S NAME :</td>
                <td colspan="3">
                    <div class="statistics_line">{{ $parent->mother_name ?? '' }}</div>
                </td>
            </tr>
            <tr>
                <td>FATHER'S NAME :</td>
                <td colspan="3">
                    <div class="statistics_line">{{ $parent->father_name ?? '' }}</div>
                </td>
            </tr>
             @php $chunks = array_chunk($basicInfo, 2); @endphp
            @foreach($chunks as $rowItem)
            <tr>
                @foreach($rowItem as $item)
                    <td>{{ strtoupper($item['label']) }} :</td>
                    <td><div class="statistics_line">{{ $item['value'] }}</div></td>
                @endforeach
                @if(count($rowItem) < 2)<td></td><td></td>@endif
            </tr>
            @endforeach
            <tr>
                <td>ADDRESS :</td>
                <td colspan="3">
                    <div class="statistics_line">{{ $parent->permant_add ?? '' }}</div>
                </td>
            </tr>
            @php $mobile = $parent->f_mobile ?? $parent->m_mobile ?? ''; @endphp
            <tr>
                <td>PHONE NO :</td>
                <td colspan="3">
                    <div class="statistics_line">{{ $mobile }}</div>
                </td>
            </tr>
        </table>
    </div>
</div> --}}

<div class="first">
    <img src="{{ public_path('health3_bg.jpg') }}" class="bg-img">

    <div class="school-header">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="15%" align="left">
                    <img src="{{ $school['logo'] }}" class="school-logo">
                </td>

                <td width="85%" align="center">
                    <div class="school-name">{{ $school['school_name'] }}</div>
                    <div class="school-address">{{ $school['address'] }}</div>
                    <div class="school-phone">Phone: {{ $school['phone'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="certificate-title">
      <div class="main-title">HEALTH AND ACTIVITY CARD</div>
      <div class="sub-title">GENERAL INFORMATION</div>
    </div>

    <div class="first-content">
        <table border="0" style="width:80%;" align="center">
                <tr>
                <td width="15%">NAME :</td>
                <td colspan="3">
                    <div class="statistics_line">
                        {{ ($parent->first_name ?? '') . ' ' . ($parent->mid_name ?? '') . ' ' . ($parent->last_name ?? '') }}
                    </div>
                </td>
            </tr>
            <tr>
                <td>ADMISSION DATE :</td>
                <td>
                    <div class="statistics_line">
                        {{ !empty($parent->admission_date) ? date('d-m-Y', strtotime($parent->admission_date)) : '' }}
                    </div>
                </td>
                <td>DATE OF BIRTH :</td>
                <td>
                    <div class="statistics_line">
                        {{ !empty($parent->dob) ? date('d-m-Y', strtotime($parent->dob)) : '' }}
                    </div>
                </td>
            </tr>
            <tr>
                <td>M F T :</td>
                <td><div class="statistics_line">{{ $parent->gender ?? '' }}</div></td>
                <td>BLOOD GROUP :</td>
                <td><div class="statistics_line">{{ $parent->blood_group ?? '' }}</div></td>
            </tr>
            <tr>
                <td>MOTHER'S NAME :</td>
                <td colspan="3">
                    <div class="statistics_line">{{ $parent->mother_name ?? '' }}</div>
                </td>
            </tr>
            <tr>
                <td>FATHER'S NAME :</td>
                <td colspan="3">
                    <div class="statistics_line">{{ $parent->father_name ?? '' }}</div>
                </td>
            </tr>
             @php $chunks = array_chunk($basicInfo, 2); @endphp
            @foreach($chunks as $rowItem)
            <tr>
                @foreach($rowItem as $item)
                    <td>{{ strtoupper($item['label']) }} :</td>
                    <td><div class="statistics_line">{{ $item['value'] }}</div></td>
                @endforeach
                @if(count($rowItem) < 2)<td></td><td></td>@endif
            </tr>
            @endforeach
            <tr>
                <td>ADDRESS :</td>
                <td colspan="3">
                    <div class="statistics_line">{{ $parent->permant_add ?? '' }}</div>
                </td>
            </tr>
            @php $mobile = $parent->f_mobile ?? $parent->m_mobile ?? ''; @endphp
            <tr>
                <td>PHONE NO :</td>
                <td colspan="3">
                    <div class="statistics_line">{{ $mobile }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>


{{-- =====================
     PAGE 2+
     Outer = class chunks  (4 classes per page set)
     Inner = row chunks    (20 rows per page)
===================== --}}

@foreach($classChunks as $chunkIndex => $classChunk)
    @foreach($rowChunks as $pageIndex => $pageRows)

    @php
        $groupCounts    = [];
        $subGroupCounts = [];

        foreach ($pageRows as $row) {
            $g  = $row['group'];
            $sg = $g . '||' . $row['sub_group'];
            $groupCounts[$g]     = ($groupCounts[$g]     ?? 0) + 1;
            $subGroupCounts[$sg] = ($subGroupCounts[$sg] ?? 0) + 1;
        }

        $printedGroups    = [];
        $printedSubGroups = [];
    @endphp

    <div class="health-page">
        <img src="{{ public_path('health2_bg.jpg') }}" class="bg-img">

        <div class="page-content">

            <table class="record-table">
                <thead>
                    <tr>
                        <th style="width:14%;">Fitness Component</th>
                        <th style="width:10%;">Sub Group</th>
                        <th style="width:10%;">Sub Sub Group</th>
                        <th style="width:13%;">Test Parameter</th>
                        <th style="width:13%;">Description</th>
                        @foreach($classChunk as $cls => $id)
                            <th style="width:{{ floor(40 / count($classChunk)) }}%;">
                                Class {{ $cls }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                @foreach($pageRows as $row)

                @php
                    $g  = $row['group'];
                    $sg = $g . '||' . $row['sub_group'];
                @endphp

                <tr>
                    @if(!in_array($g, $printedGroups))
                        <td class="group-cell"
                            rowspan="{{ $groupCounts[$g] }}">
                            {{ $g }}
                        </td>
                        @php $printedGroups[] = $g; @endphp
                    @endif

                    @if(!in_array($sg, $printedSubGroups))
                        <td class="subgroup-cell"
                            rowspan="{{ $subGroupCounts[$sg] }}">
                            {{ $row['sub_group'] }}
                        </td>
                        @php $printedSubGroups[] = $sg; @endphp
                    @endif

                    <td>{{ $row['sub_sub_group'] }}</td>
                    <td>{{ $row['test'] }}</td>
                    <td>{{ $row['desc'] }}</td>

                    @foreach($classChunk as $cls => $id)
                        @php $value = $allClassHealth[$cls][$row['test']] ?? ''; @endphp
                        <td style="text-align:center;">{{ $value }}</td>
                    @endforeach
                </tr>

                @endforeach
                </tbody>
            </table>

        </div>
    </div>

    @endforeach
@endforeach

</body>
</html>