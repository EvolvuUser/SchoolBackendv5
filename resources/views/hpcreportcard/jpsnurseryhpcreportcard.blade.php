<style>
@page {
  size: A4;
  margin: 0;
}

body {
  margin: 0;
  padding: 0;
}

.page {
  width: 210mm;            /* A4 width */
  height: 297mm;           /* A4 height */
  background-size: 100% 100%;  /* stretch image to fit full page */
  background-position: center;
  background-repeat: no-repeat;
  position: relative;       /* for overlay text */
}

.year-block {
  position: absolute;
  top: 160mm;     /* adjust Y-position */
  left: 105mm;     /* adjust X-position */
  width: 60mm;    /* width of the green YEAR box */
  height: 10mm;   /* height of the box */
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 33px;
  color: #1e3a8a;
}

.qna-block {  
  top: 65mm;/* adjust vertical placement */
  left: 20mm;          /* left margin */
  right: 20mm;         /* right margin - IMPORTANT: makes block span full page width minus margins */
  width: auto;         /* allow it to stretch from left to right */
  font-size: 25px;
  color: #1e3a8a;
  line-height: 10mm;
  z-index: 5;          /* keep it above background */
}

/* Table-based layout for robust PDF rendering */
.qna-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed; /* makes width behavior predictable */
}

.qna-table td {
  vertical-align: middle;
  padding: 0 4mm 6px 0; /* spacing between label and line */
  font-family:"Comic Sans MS", cursive, sans-serif !important;
  color: #000000;
}

/* Label column: left side */
.qna-label-td {
  white-space: nowrap;
  font-weight: bold;
  color: #1e3a8a !important; /* Deep blue for questions */
  /* no fixed width here — it will take the remaining space */
}

/* Answer column: fixed width, right-aligned at the block's right edge */
.qna-answer-td {
  width: 60mm !important;                          /* fixed underline width */
  text-align: center;                   /* center text on the underline */
  border-bottom: 1px solid #1e3a8a;     /* the underline */
  padding-bottom: 2px;                  /* small gap between text and line */
  box-sizing: border-box;
  color: #000;
  font-weight: normal !important;
  font-size: 20px;
}
.content-wrapper {
  position: absolute;   
  top: 65mm;            /* adjust vertical placement */
  left: 20mm;           /* left margin */
  right: 20mm;          /* right margin */
  width: auto;
}
.learner-feedback-page {
        width: 210mm;
        height: 297mm;
        position: relative;
        box-sizing: border-box;
        padding: 0 !important;
        background-repeat: no-repeat;
        background-position: top left;
        background-size: 210mm 297mm;
    }

    .learner-feedback-page .learner-feedback-content {
        /* Keep content inside the decorative border */
        width: 162mm;
        margin: 0 auto;
        padding-top: 40mm;
        box-sizing: border-box;
    }

    .learner-feedback-content h2 {
        margin: 0 0 4mm !important;
        font-family: "Times New Roman", serif;
        font-size: 22px !important;
        color: #1e3a8a;
    }

    .learner-feedback-content table {
        width: 100% !important;
        margin: 0 !important;
        border-collapse: collapse;
        table-layout: fixed;
        background: white;
        font-family: "Times New Roman", serif;
        font-size: 15px !important;
    }

    .learner-feedback-content th,
    .learner-feedback-content td {
        border: 1px solid black;
        padding: 8px !important;
        height: auto !important;
        box-sizing: border-box;
        vertical-align: middle;
        line-height: 1.25 !important;
        white-space: normal !important;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .learner-feedback-content th {
        background: #dcdcdc;
        text-align: center;
    }

    .learner-feedback-content tr {
        page-break-inside: avoid;
    }
    
    .teacher-remark-page {
        width: 210mm;
        height: 297mm;
        position: relative;
        box-sizing: border-box;
        padding: 0 !important;
        background-repeat: no-repeat;
        background-position: top left;
        background-size: 210mm 297mm;
    }

    .teacher-remark-content {
        width: 162mm;
        margin: 0 auto;
        padding-top: 40mm;
        box-sizing: border-box;
    }

    .teacher-remark-content h2 {
        margin: 0 0 4mm !important;
        font-family: "Times New Roman", serif;
        font-size: 22px !important;
        color: #1e3a8a;
    }

    .teacher-remark-content table {
        width: 100% !important;
        margin: 0 0 4mm !important;
        border-collapse: collapse;
        table-layout: fixed;
        background: white;
        font-family: "Times New Roman", serif;
        font-size: 15px !important;
    }

    .teacher-remark-content th,
    .teacher-remark-content td {
        border: 1px solid black;
        padding: 8px !important;
        height: auto !important;
        box-sizing: border-box;
        vertical-align: top;
        line-height: 1.3 !important;
        white-space: normal !important;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .teacher-remark-content th {
        background: #dcdcdc;
        text-align: center;
    }

    .teacher-remark-content td {
        text-align: left;
    }

    .teacher-remark-content tr {
        page-break-inside: avoid;
    }
    .teacher-remark-content .teacher-observation-table {
        width: 100% !important;
        border-collapse: collapse;
        table-layout: fixed;
        font-family: "Times New Roman", serif;
        font-size: 16px !important;
        color: #000;
    }

    .teacher-remark-content .teacher-observation-table th,
    .teacher-remark-content .teacher-observation-table td {
        border: 1px solid #000;
        padding: 12px !important;
        vertical-align: middle !important;
        line-height: 1.3 !important;
        word-wrap: break-word;
        white-space: normal !important;
    }

    .teacher-remark-content .teacher-observation-table th {
        background: #dcdcdc;
        font-weight: bold;
        text-align: center;
    }

    .teacher-remark-content .teacher-observation-table td {
        background: #fff;
        font-weight: normal;
        text-align: center;
    }

    .teacher-remark-content .teacher-observation-table td:first-child {
        text-align: left;
    }

    .teacher-remark-content .teacher-observation-table tr {
        page-break-inside: avoid;
    }
    
     .teacher-remark-content .signature-table {
        width: 100% !important;
        margin-top: 8mm !important;
        border-collapse: collapse;
        table-layout: fixed;
        font-family: "Times New Roman", serif;
        font-size: 15px !important;
        background: #fff;
        page-break-inside: avoid;
    }

    .teacher-remark-content .signature-table td {
        border: 1px solid #000;
        padding: 6px 10px !important;
        text-align: left !important;
        vertical-align: middle !important;
        background: #fff;
    }

    .signature-table .signature-line {
        width: 85%;
        height: 7mm;
        border-bottom: 1px solid #000;
    }
    .about-me-page {
        background-image: url('https://sms.evolvu.in/public/HPC/SACS/allaboutme.jpg');
        background-size: 210mm 297mm;
        background-repeat: no-repeat;
        background-position: top left;
        page-break-after: always;
        padding: 0;
        box-sizing: border-box;
    }

    .about-me-content {
        width: 162mm;
        margin: 0 auto;
        padding-top: 44mm;
        font-family: "Times New Roman", serif;
        color: #000;
    }

    .about-me-content h2 {
        margin: 0 0 5mm;
        text-align: center;
        font-family: Arial, sans-serif;
        font-size: 23px;
        color: #2c3e50;
    }

    .about-photo {
        height: 40mm;
        margin-bottom: 7mm;
        text-align: center;
    }

    .about-photo img {
        max-width: 34mm;
        max-height: 38mm;
        width: auto;
        height: auto;
        border: 1px solid #1e3a8a;
    }

    .about-photo-placeholder {
        width: 32mm;
        height: 38mm;
        margin: 0 auto;
        border: 1px solid #1e3a8a;
        line-height: 38mm;
        font-size: 14px;
    }

    .about-details {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 14px;
    }

    .about-details th,
    .about-details td {
        padding: 8px 6px;
        text-align: left;
        vertical-align: middle;
        line-height: 1.3;
        white-space: normal;
        word-wrap: break-word;
    }

    .about-details th {
        background: #dbe5f1;
        border-bottom: 1px solid #fff;
        font-weight: bold;
    }

    .about-details td {
        border-bottom: 1px solid #1e3a8a;
    }

    .about-details tr {
        page-break-inside: avoid;
    }

    .about-attendance-title {
        margin: 8mm 0 4mm;
        text-align: center;
        font-size: 22px;
        color: #1e3a8a;
    }

    .about-attendance {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 16px;
    }

    .about-attendance th,
    .about-attendance td {
        width: 50%;
        border: 1px solid #1e3a8a;
        padding: 8px;
        text-align: center;
    }

    .about-attendance th {
        background: #dcdcdc;
    }
    
    .about-me-content .about-details {
    width: 100%;
    border: 1px solid #000;
    border-collapse: collapse;
    table-layout: fixed;
}

.about-me-content .about-details th,
.about-me-content .about-details td {
    border: 1px solid #000;
    padding: 8px 6px;
    text-align: left;
    vertical-align: middle;
    line-height: 1.3;
    white-space: normal;
    word-wrap: break-word;
}

.about-me-content .about-details th {
    background-color: #dbe5f1;
    font-weight: bold;
}

.about-me-content .about-details td {
    background-color: #fff;
}

.teacher-remark-content .signature-table .signature-line {
    width: 85%;
    height: 15mm;
    border-bottom: 1px solid #000;
}
</style>
<html>
<div class="page" style="background-image: url('https://sms.evolvu.in/public/HPC/JPS/nursery.png');page-break-after: always;">
   <div class="year-block">{{ $studentdata->academic_yr ?? '2025-2026' }}</div>
</div>



@php
    $aboutName = ucwords(strtolower(trim(
        ($studentdata->first_name ?? '') . ' ' .
        ($studentdata->mid_name ?? '') . ' ' .
        ($studentdata->last_name ?? '')
    )));

    $aboutClass = implode(' - ', array_filter([
        $studentdata->classname ?? '',
        $studentdata->sectionname ?? '',
    ], function ($value) {
        return trim((string) $value) !== '';
    }));
    

    $aboutDob = '';
    if (!empty($studentdata->dob) && $studentdata->dob !== '0000-00-00') {
        $dobTimestamp = strtotime($studentdata->dob);
        if ($dobTimestamp !== false) {
            $aboutDob = date('d-m-Y', $dobTimestamp);
        }
    }

    // Map these names to the fields supplied by your controller.
    $aboutFather  = $studentdata->father_name ?? '';
    $aboutMother  = $studentdata->mother_name ?? '';
    $aboutRoll    = $studentdata->roll_no ?? '';
    $aboutAadhar  = $studentdata->stu_aadhaar_no ?? '';
    $aboutHeight  = $studentdata->height ?? '';
    $aboutWeight  = $studentdata->weight ?? '';
    $aboutContact = $studentdata->f_mobile ?? '';
    $aboutAddress = $studentdata->permant_add ?? '';
    $aboutTeacher = $studentdata->teacher_name ?? '';

    $aboutPhoto = $data['student']['studentimage'] ?? '';
    $aboutAttendance = collect($data['attendance'] ?? []);
@endphp
@php
    $aboutPhoto = trim((string) ($data['student']['studentimage'] ?? ''));

    if ($aboutPhoto !== '' && preg_match('/^https?:\/\//i', $aboutPhoto)) {
        $separator = strpos($aboutPhoto, '?') === false ? '?' : '&';
        $aboutPhoto .= $separator . 'v=' . time();
    }
@endphp

<div class="page about-me-page">
    <div class="about-me-content">
 <div class="about-photo">
            @if(!empty($aboutPhoto))
                <img src="{{ $aboutPhoto }}" alt="Student photo">
            @else
                <div class="about-photo-placeholder">PHOTO</div>
            @endif
        </div>
       

        <table class="about-details">
            <colgroup>
                <col style="width: 21%;">
                <col style="width: 29%;">
                <col style="width: 21%;">
                <col style="width: 29%;">
            </colgroup>
            <tbody>
                <tr>
                    <th>My Name</th>
                    <td colspan="3">{{ $aboutName }}</td>
                </tr>
                <tr>
                    <th>Father’s Name</th>
                    <td>{{ $aboutFather }}</td>
                    <th>Mother’s Name</th>
                    <td>{{ $aboutMother }}</td>
                </tr>
                <tr>
                    <th>Class</th>
                    <td>{{ $aboutClass }}</td>
                    <th>Roll No.</th>
                    <td>{{ $aboutRoll }}</td>
                </tr>
                <tr>
                    <th>Date of Birth</th>
                    <td>{{ $aboutDob }}</td>
                    <th>Aadhar Number</th>
                    <td>{{ $aboutAadhar }}</td>
                </tr>
                <tr>
                    <th>Height</th>
                    <td>{{ $aboutHeight }}</td>
                    <th>Weight</th>
                    <td>{{ $aboutWeight }}</td>
                </tr>
                <tr>
                    <th>Contact No.</th>
                    <td>{{ $aboutContact }}</td>
                    <th>Residential Address</th>
                    <td>{{ $aboutAddress }}</td>
                </tr>
                <tr>
                    <th>My Teacher’s Name</th>
                    <td colspan="3">{{ $aboutTeacher }}</td>
                </tr>
            </tbody>
        </table>

    </div>
</div>



     @foreach($subjectsGrouped as $subject)
    <div style="
        background-image: url('https://sms.evolvu.in/public/HPC/SACS/domain.jpg');
        background-size: cover;
        background-position: center;
        background-size: 100% 100%;
        padding: 20px;
        box-sizing: border-box;
        min-height: 285mm;
        {{ !$loop->last ? 'page-break-after: always;' : '' }}">
  
  {{-- One printable page per subject --}}
  
    
    <table style="width: 78%; border-collapse: collapse; font-family: 'Comic Sans MS', Arial, sans-serif;margin: 130px auto 0 auto;">
      
      @php
          $competencies = data_get($subject, 'competencies', []);
          $totalDataRows = 0;

          foreach ($competencies as $comp) {
              $details = data_get($comp, 'details', []);
              // Each competency counts at least 1 row
              $totalDataRows += max(count($details), 1);
          }

          // Add rows for subject title + curriculum goal + table header
          $domainRowspan = $totalDataRows + 3;
      @endphp

      {{-- Top row: domain sidebar + subject title --}}
      <tr>
          @php
    $domainName = trim((string) ($subject['domainname'] ?? ''));
@endphp

@if($domainName !== '' && $domainName !== '-')
    <td rowspan="{{ $domainRowspan }}" style="
        border: 1px solid #000;
        width: 8mm;
        min-width: 8mm;
        max-width: 8mm;
        padding: 0;
        background-color: {{ $subject['color_code'] ?? '#ffffff' }};
        text-align: center;
        vertical-align: middle;
    ">
        <div style="
            writing-mode: vertical-rl;
            transform: rotate(270deg);
            font-weight: bold;
            font-family: 'Times New Roman', serif;
            font-size: 18px;
            color: #000;
            white-space: nowrap;
            letter-spacing: 0.3px;
            display: inline-block;
            margin: 0 auto;
        ">
            {{ $domainName }}
        </div>
    </td>
@endif
        
        
        
        

        {{-- Subject title --}}
        @php
            $hasCompetency = collect($competencies)->contains(function ($comp) {
                return !empty($comp['competency']);
            });
        @endphp
        @if($hasCompetency)
        <td colspan="4" style="border:1px solid #000; padding:8px; font-weight:700; background:#dcdcdc;">
            {{ $subject['subjectname'] ?? '' }}
        </td>
        @else
        <td colspan="3" style="border:1px solid #000; padding:8px; font-weight:700; background:#dcdcdc;">
            {{ $subject['subjectname'] ?? '' }}
        </td>
        @endif
      </tr>

      {{-- Curriculum goal row --}}
      <tr>
        @if($hasCompetency)
        <td colspan="4" style="border:1px solid #000; padding:8px; font-style:italic;background:#f1f2f4;">
            <strong>Curriculum goal:</strong> {{ $subject['curriculum_goal'] ?? '' }}
        </td>
        @else
        <td colspan="3" style="border:1px solid #000; padding:8px; font-style:italic;background:#f1f2f4;">
            <strong>Curriculum goal:</strong> {{ $subject['curriculum_goal'] ?? '' }}
        </td>
        @endif
      </tr>

      {{-- Table header --}}
      <tr style="background:#f0f0f0;">
      @if($hasCompetency)
      <th style="border:1px solid #000; padding:8px; width:10%; text-align:left; vertical-align:middle; font-size:14px;background:#f1f2f4;">Competency</th>
      @endif
      <th style="border:1px solid #000; padding:8px; width:40%; text-align:left; vertical-align:middle; font-size:14px;background:#f1f2f4;">Learning Outcome</th>
      <th style="border:1px solid #000; padding:8px; width:14%; text-align:center; vertical-align:middle; font-size:14px; line-height:1.2;background:#f1f2f4;">
          TERM&nbsp;1
      </th>
      <th style="border:1px solid #000; padding:8px; width:14%; text-align:center; vertical-align:middle; font-size:14px; line-height:1.2;background:#f1f2f4;">
          TERM&nbsp;2
      </th>
      </tr>

      {{-- Data rows --}}
      @foreach($competencies as $comp)
        @php
            $details = data_get($comp, 'details', []);
            $rows = max(count($details), 1);
            
        @endphp

        @forelse($details as $i => $detail)
          <tr>
            @if($i === 0 && !empty($comp['competency']))
                <td style="border:1px solid #000; padding:8px;text-align:center; background:#f1f2f4;font-size:14px; font-weight:700;background:#f1f2f4;" rowspan="{{ $rows }}">
                  {{ $comp['competency'] }}
                </td>
              <!--<td style="border:1px solid #000; padding:8px; background:#f1f2f4; font-weight:700;" rowspan="{{ $rows }}">-->
              <!--  {{ $comp['competency'] ?? '' }}-->
              <!--</td>-->
            @endif

            <td style="border:1px solid #000; padding:8px; text-align:left;">
                {{ $detail['learning_outcomes'] ?? '' }}
            </td>

            <td style="border:1px solid #000; padding:8px; text-align:center;">
                @php
                    $value1 = $detail['parameter_value'][1] ?? '';
                @endphp
                @if($value1 == 'Beginner' || $value1 == 'BEGINNER')
                    <img src="https://sms.evolvu.in/public/HPC/JPS/beginner.png" alt="Beginner" style="width: 30px; height: 30px;">
                @elseif($value1 == 'Progressing' || $value1 == 'PROGRESSING')
                    <img src="https://sms.evolvu.in/public/HPC/JPS/progressing.png" alt="Progressing" style="width: 30px; height: 30px;">
                @elseif($value1 == 'Proficient' || $value1 == 'PROFICIENT')
                    <img src="https://sms.evolvu.in/public/HPC/JPS/proficient.png" alt="Proficient" style="width: 30px; height: 30px;">
                @else
                    {{ $value1 }}
                @endif
            </td>
            
            <td style="border:1px solid #000; padding:8px; text-align:center;">
                @php
                    $value2 = $detail['parameter_value'][2] ?? '';
                @endphp
                @if($value2 == 'Beginner' || $value2 == 'BEGINNER')
                    <img src="https://sms.evolvu.in/public/HPC/JPS/beginner.png" alt="Beginner" style="width: 30px; height: 30px;">
                @elseif($value2 == 'Progressing' || $value2 == 'PROGRESSING')
                    <img src="https://sms.evolvu.in/public/HPC/JPS/progressing.png" alt="Progressing" style="width: 30px; height: 30px;">
                @elseif($value2 == 'Proficient' || $value2 == 'PROFICIENT')
                    <img src="https://sms.evolvu.in/public/HPC/JPS/proficient.png" alt="Proficient" style="width: 30px; height: 30px;">
                @else
                    {{ $value2 }}
                @endif
            </td>
          </tr>
        @empty
          {{-- If no details exist, still show the competency row --}}
          <tr>
            <td style="border:1px solid #000; padding:8px; text-align:left;">
                -
            </td>
            <td style="border:1px solid #000; padding:8px; text-align:center;">
                -
            </td>
            <td style="border:1px solid #000; padding:8px; text-align:center;">
                -
            </td>
          </tr>
        @endforelse

      @endforeach

    </table>
    @if($loop->last)
    <!-- Performance Level Table (Only on Last Page) -->
    <table style="
        width: 78%;
        border-collapse: collapse;
        font-family: 'Times New Roman', serif;
        margin: auto auto 0 auto;
        text-align: center;
        border: 1px solid #000;
        page-break-inside: avoid; /* keep table together */
    ">
        <thead style="background-color: #dcdcdc;">
            <tr>
                <th style="border: 1px solid #000; padding: 10px; font-size: 14px;">Performance Level</th>
                <th style="border: 1px solid #000; padding: 10px; font-size: 14px;">Symbol</th>
                <th style="border: 1px solid #000; padding: 10px; font-size: 14px;">Interpretation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 1px solid #000; padding: 8px;">Beginner</td>
                <td style="border: 1px solid #000; padding: 8px;">
                    <img src="https://sms.evolvu.in/public/HPC/JPS/beginner.png" alt="Beginner" style="width: 25px; height: 25px;">
                </td>
                <td style="border: 1px solid #000; padding: 8px;">Tries to achieve the competency and associated learning outcome with a lot of support from teachers.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 8px;">Progressing</td>
                <td style="border: 1px solid #000; padding: 8px;">
                    <img src="https://sms.evolvu.in/public/HPC/JPS/progressing.png" alt="Progressing" style="width: 25px; height: 25px;">
                </td>
                <td style="border: 1px solid #000; padding: 8px;">Achieves the competency and associated learning outcomes with occasional/some support from teachers.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 8px;">Proficient</td>
                <td style="border: 1px solid #000; padding: 8px;">
                    <img src="https://sms.evolvu.in/public/HPC/JPS/proficient.png" alt="Proficient" style="width: 25px; height: 25px;">
                </td>
                <td style="border: 1px solid #000; padding: 8px;">Achieves the competency and associated learning outcomes on his/her own.</td>
            </tr>
        </tbody>
    </table>
@endif
 </div> 
@endforeach


<div class="page learner-feedback-page" style="
    background-image: url('https://sms.evolvu.in/public/HPC/SACS/learnerpeerfeedback.jpg');
    page-break-after: always;
">
    <div class="learner-feedback-content">
        <h2 style="
            margin: 0 0 12px;
            font-family: 'Times New Roman', serif;
            font-size: 23px;
            font-weight: bold;
            color: #1e3a8a;
        ">
            Learner’s Voice &amp; Feedback
        </h2>

        <table style="
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            background-color: #ffffff;
            font-family: 'Times New Roman', serif;
            font-size: 16px;
            color: #000;
        ">
            <thead>
                <tr>
                    <th style="
                        width: 50%;
                        border: 1px solid #000;
                        padding: 12px;
                        background-color: #dcdcdc;
                        text-align: center;
                    ">
                        My Social And Emotional Development
                    </th>

                    <th style="
                        width: 25%;
                        border: 1px solid #000;
                        padding: 12px;
                        background-color: #dcdcdc;
                        text-align: center;
                    ">
                        Term 1
                    </th>

                    <th style="
                        width: 25%;
                        border: 1px solid #000;
                        padding: 12px;
                        background-color: #dcdcdc;
                        text-align: center;
                    ">
                        Term 2
                    </th>
                </tr>
            </thead>

            <tbody>
                @foreach($results as $result)
                    @php
                        $options = $result['options'] ?? [];

                        if (is_string($options)) {
                            $options = json_decode($options, true) ?: [];
                        }

                        if (!is_array($options)) {
                            $options = [];
                        }

                        $controlType = strtolower(trim(
                            (string) ($result['control_type'] ?? '')
                        ));

                        $isChoice = count($options) > 0
                            && !in_array(
                                $controlType,
                                ['text', 'textarea', 'textbox'],
                                true
                            );
                    @endphp

                    <tr style="page-break-inside: avoid;">
                        <td style="
                            border: 1px solid #000;
                            padding: 12px;
                            text-align: left;
                            vertical-align: middle;
                            line-height: 1.3;
                            word-wrap: break-word;
                        ">
                            {{ $result['parameter'] ?? '' }}
                        </td>

                        @foreach([1, 2] as $feedbackTerm)
                            @php
                                $rawValue = $result['parameter_values'][$feedbackTerm] ?? '';

                                $answer = is_array($rawValue)
                                    ? implode(', ', $rawValue)
                                    : trim((string) $rawValue);

                                $displayAnswer = $answer;

                                if ($isChoice && $answer !== '') {
                                    $selectedValues = is_array($rawValue)
                                        ? $rawValue
                                        : ($controlType === 'checkbox'
                                            ? explode(',', $answer)
                                            : [$answer]);

                                    $selectedValues = array_map(
                                        function ($value) {
                                            return strtolower(trim((string) $value));
                                        },
                                        $selectedValues
                                    );

                                    $selectedLabels = [];

                                    foreach ($options as $option) {
                                        $optionKey = strtolower(trim(
                                            (string) ($option['option'] ?? '')
                                        ));

                                        if (
                                            $optionKey !== ''
                                            && in_array($optionKey, $selectedValues, true)
                                        ) {
                                            $selectedLabels[] = $option['value'] ?? '';
                                        }
                                    }

                                    $displayAnswer = implode(', ', $selectedLabels);
                                }
                            @endphp

                            <td style="
                                border: 1px solid #000;
                                padding: 12px;
                                text-align: center;
                                vertical-align: middle;
                                line-height: 1.3;
                                word-wrap: break-word;
                            ">
                                {{ $displayAnswer }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="page" style="background-image: url('https://sms.evolvu.in/public/HPC/SACS/teacherparentfeedback.jpg');page-break-after: always;">
   <div style="max-width: 725px; background: transparent; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h2 style="font-size: 20px; margin: 195px 0 10px 70px;color:#1e3a8a;">Parent's Observation</h2>

    <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; text-align: center;padding-left: 70px;">
        <thead>
            <tr>
                <th style="border: 1px solid #000; padding: 12px; background-color: #dcdcdc;width: 50%;">Observations</th>
                <th style="border: 1px solid #000; padding: 12px; background-color: #dcdcdc;width: 25%;">Term 1</th>
                <th style="border: 1px solid #000; padding: 12px; background-color: #dcdcdc;width: 25%;">Term 2</th>
            </tr>
        </thead>
        <tbody>
            @foreach($parentfeedback as $feedback)
            @if($feedback['control_type'] == 'radio')
                <tr>
                    <!-- Observation -->
                    <td style="border: 1px solid #000; padding: 12px; text-align: left;">
                        {{ $feedback['parameter'] ?? '' }}
                    </td>
        
                    <!-- Term 1 -->
                    <td style="border: 1px solid #000; padding: 12px;">
                        @php
                            $term1Value = $feedback['parameter_values'][1] ?? null;
                            $term1Label = '';
                            if ($term1Value && isset($feedback['options'])) {
                                foreach ($feedback['options'] as $option) {
                                    if (trim($option['option']) == trim($term1Value)) {
                                        $term1Label = $option['value'];
                                        break;
                                    }
                                }
                            }
                        @endphp
                        @if($term1Label)
                            <span style="color: #000; ">{{ $term1Label }}</span>
                        @endif
                    </td>
        
                    <!-- Term 2 -->
                    <td style="border: 1px solid #000; padding: 12px;">
                        @php
                            $term2Value = $feedback['parameter_values'][2] ?? null;
                            $term2Label = '';
                            if ($term2Value && isset($feedback['options'])) {
                                foreach ($feedback['options'] as $option) {
                                    if (trim($option['option']) == trim($term2Value)) {
                                        $term2Label = $option['value'];
                                        break;
                                    }
                                }
                            }
                        @endphp
                        @if($term2Label)
                            <span style="color: #000;">{{ $term2Label }}</span>
                        @endif
                    </td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
    @foreach($parentfeedback as $feedback)
    @if($feedback['control_type'] == 'checkbox')
        <h3 style="margin: 20px 0 10px; font-size: 18px; color: #333; padding-left: 70px;">
            {{ $feedback['parameter'] }}
        </h3>
        <div style="padding-left: 70px; ">
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; margin-bottom: 30px; text-align: center;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 12px; background-color: #dcdcdc;width: 50%;">Term 1</th>
                    <th style="border: 1px solid #000; padding: 12px; background-color: #dcdcdc;width: 50%;">Term 2</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    {{-- Term 1 Selected Options --}}
                    <td style="border: 1px solid #000; padding: 12px; text-align: left;vertical-align: top;">
                        @php
                            $selectedTerm1 = [];
                            if (!empty($feedback['parameter_values'][1])) {
                                $selectedTerm1 = array_map('trim', explode(',', $feedback['parameter_values'][1]));
                            }
                        @endphp

                        @foreach($feedback['options'] as $option)
                            @if(in_array($option['option'], $selectedTerm1))
                                <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                    
                                    {{ $option['value'] }}
                                </label>
                            @endif
                        @endforeach
                    </td>

                    {{-- Term 2 Selected Options --}}
                    <td style="border: 1px solid #000; padding: 12px; text-align: left;vertical-align: top;">
                        @php
                            $selectedTerm2 = [];
                            if (!empty($feedback['parameter_values'][2])) {
                                $selectedTerm2 = array_map('trim', explode(',', $feedback['parameter_values'][2]));
                            }
                        @endphp

                        @foreach($feedback['options'] as $option)
                            @if(in_array($option['option'], $selectedTerm2))
                                <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                    
                                    {{ $option['value'] }}
                                </label>
                            @endif
                        @endforeach
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
    @endif
@endforeach
</div>
</div>
<div class="page teacher-remark-page" style="
    background-image: url('https://sms.evolvu.in/public/HPC/SACS/classteacherremark.jpg');
    page-break-after: always;
">
    <div class="teacher-remark-content">

    <h2>Class Teacher’s Remark</h2>

<table class="teacher-observation-table">
    <colgroup>
        <col style="width: 50%;">
        <col style="width: 25%;">
        <col style="width: 25%;">
    </colgroup>

    <thead>
        <tr>
            <th>Observations</th>
            <th>Term 1</th>
            <th>Term 2</th>
        </tr>
    </thead>

    <tbody>
        @foreach($classteacherremark as $remarkItem)
            <tr>
                <td>
                    {{ ucfirst(strtolower($remarkItem['parameter'] ?? '')) }}
                </td>

                <td>
                    {{ $remarkItem['parameter_values'][1] ?? '' }}
                </td>

                <td>
                    {{ $remarkItem['parameter_values'][2] ?? '' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="signature-table">
    <colgroup>
        <col style="width: 45%;">
        <col style="width: 55%;">
    </colgroup>

    <tbody>
        <tr>
            <td><strong>SIGNATURES</strong></td>
            <td></td>
        </tr>
        <tr>
            <td>Class Teacher’s Signature</td>
            <td><div class="signature-line"></div></td>
        </tr>
        <tr>
            <td>Parent’s Signature</td>
            <td><div class="signature-line"></div></td>
        </tr>
        <tr>
            <td>Principal’s Signature</td>
            <td><div class="signature-line"></div></td>
        </tr>
    </tbody>
</table>
</div>
</div>
<div class="page" style="background-image: url('https://sms.evolvu.in/public/HPC/SACS/backcover.jpg');page-break-after: never;">
</div>
</html>
<script type="text/php">
if (isset($pdf)) {
    $font = $fontMetrics->get_font("Comic Sans MS", "normal"); // you can change font if needed
    $size = 18;
    $pageText = "{PAGE_NUM}";
    
    // Position: bottom-right corner
    $y = $pdf->get_height() - 30; // distance from bottom
    $x = $pdf->get_width() - 30; // distance from right edge

    $pdf->page_text($x, $y, $pageText, $font, $size, array(1, 1, 1)); // black color text
}
</script>