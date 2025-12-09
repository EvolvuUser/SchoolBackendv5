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
  top: 176mm;     /* adjust Y-position */
  left: 107mm;     /* adjust X-position */
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
</style>
<html>
<div class="page" style="background-image: url('https://sms.evolvu.in/public/HPC/SACS/ukghpccover.jpg');page-break-after: always;">
   <div class="year-block">{{ $studentdata->academic_yr ?? '2025-2026' }}</div>
</div>

<div class="page" style="background-image: url('https://sms.evolvu.in/public/HPC/SACS/allaboutme.jpg');page-break-after: always;">
    
        <h2 style="
            position: absolute;
            top: 45mm;
            left: 0;
            right: 0;
            text-align: center;
            font-family: 'Comic Sans MS', cursive, sans-serif;
            font-size: 28px;
            color: #2c3e50;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
          ">
            ALL ABOUT ME
          </h2>
        <div class="content-wrapper">
          <div class="qna-block">
            <table class="qna-table">
              <tr>
                <td class="qna-label-td">My name is</td>
                <td class="qna-answer-td">{{ ucwords(strtolower(($studentdata->first_name ?? '') . ' ' . ($studentdata->mid_name ?? '') . ' ' . ($studentdata->last_name ?? ''))) }}</td>
              </tr>
              <tr>
                <td class="qna-label-td">I am in class</td>
                <td class="qna-answer-td">{{ trim(($studentdata->classname ?? '').'-'.($studentdata->sectionname ?? '')) ?: ' ' }}</td>
              </tr>
              <tr>
                <td class="qna-label-td">My birthday is on</td>
                <td class="qna-answer-td"> {{ $studentdata->dob ? date('d-m-Y', strtotime($studentdata->dob)) : ' ' }}</td>
              </tr>
        
              @foreach($data['allAboutMe'] ?? [] as $item)
                <tr>
                  <td class="qna-label-td">{{ $item->name ?? '' }}</td>
                  <td class="qna-answer-td">{{ $item->aboutme_value ?? ' ' }}</td>
                </tr>
              @endforeach
            </table>
          </div>
   <div class="attendance-block" >
        <h2 style="font-family: 'Times New Roman', serif; font-weight: bold; font-size: 28px; color: #1e3a8a;">Attendance</h2>
        <table style="width: 100%; border-collapse: collapse; margin-top: 5mm; font-family: 'Times New Roman', serif; font-size: 20px;">
    <thead>
        <tr>
            @php
                // Define the terms you always want
                $terms = ['Term 1', 'Term 2'];
            @endphp
            @foreach($terms as $term)
                <th style="border: 1px solid #1e3a8a; padding: 5px;font-weight:bold;background-color: #dcdcdc;">{{ $term }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            @foreach($terms as $term)
                @php
                    $att = collect($data['attendance'])->firstWhere('term', $term);
                @endphp
                <td style="border: 1px solid #1e3a8a; padding: 8px; text-align: center; vertical-align: middle; height: 5mm;">
                    @if($att)
                        {{ $att['present'] }} / {{ $att['working'] }}<br>
                    @else
                    
                    @endif
                </td>
            @endforeach
        </tr>
    </tbody>
</table>
    </div>
</div>
</div>

<div class="page" style="
    width: 210mm;
    height: 297mm;
    position: relative;
    background-image: url('https://sms.evolvu.in/public/HPC/SACS/allaboutme2.jpg');
    background-repeat: no-repeat;      
    background-size: 210mm 297mm;      
    background-position: top left;     
    overflow: hidden;
">
    <!-- Student Image -->
    <div style="position: absolute; left: 180px; top: 170px; text-align: center; width: 50%;">
        <img src="{{ $data['student']['studentimage'] ?? '' }}" style="max-width: 80%; height: auto;">
    </div>

    <!-- Family Image -->
    <div style="position: absolute; left: 180px; top: 620px; text-align: center; width: 50%; height: 300px; overflow: hidden;">
    <img src="{{ $data['student']['familyimage'] ?? '' }}" 
         style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 10px;">
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
          <td rowspan="{{ $domainRowspan }}" style="
    border: 1px solid #000;
    width: 8mm;
    min-width: 8mm;
    max-width: 8mm;
    padding: 0;
    background-color: {{ $subject['color_code'] }};
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
        {{ $subject['domainname'] }}
    </div>
</td>
        
        
        
        

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
                @if($value1 == 'Beginner')
                    <img src="https://sms.evolvu.in/public/HPC/SACS/beginner.png" alt="Beginner" style="width: 30px; height: 30px;">
                @elseif($value1 == 'Progressing')
                    <img src="https://sms.evolvu.in/public/HPC/SACS/progressing.png" alt="Progressing" style="width: 30px; height: 30px;">
                @elseif($value1 == 'Proficient')
                    <img src="https://sms.evolvu.in/public/HPC/SACS/proficient.png" alt="Proficient" style="width: 30px; height: 30px;">
                @else
                    {{ $value1 }}
                @endif
            </td>
            
            <td style="border:1px solid #000; padding:8px; text-align:center;">
                @php
                    $value2 = $detail['parameter_value'][2] ?? '';
                @endphp
                @if($value2 == 'Beginner')
                    <img src="https://sms.evolvu.in/public/HPC/SACS/beginner.png" alt="Beginner" style="width: 30px; height: 30px;">
                @elseif($value2 == 'Progressing')
                    <img src="https://sms.evolvu.in/public/HPC/SACS/progressing.png" alt="Progressing" style="width: 30px; height: 30px;">
                @elseif($value2 == 'Proficient')
                    <img src="https://sms.evolvu.in/public/HPC/SACS/proficient.png" alt="Proficient" style="width: 30px; height: 30px;">
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
    <br><br>
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
                    <img src="https://sms.evolvu.in/public/HPC/SACS/beginner.png" alt="Beginner" style="width: 25px; height: 25px;">
                </td>
                <td style="border: 1px solid #000; padding: 8px;">Tries to achieve the competency and associated learning outcome with a lot of support from teachers.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 8px;">Progressing</td>
                <td style="border: 1px solid #000; padding: 8px;">
                    <img src="https://sms.evolvu.in/public/HPC/SACS/progressing.png" alt="Progressing" style="width: 25px; height: 25px;">
                </td>
                <td style="border: 1px solid #000; padding: 8px;">Achieves the competency and associated learning outcomes with occasional/some support from teachers.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 8px;">Proficient</td>
                <td style="border: 1px solid #000; padding: 8px;">
                    <img src="https://sms.evolvu.in/public/HPC/SACS/proficient.png" alt="Proficient" style="width: 25px; height: 25px;">
                </td>
                <td style="border: 1px solid #000; padding: 8px;">Achieves the competency and associated learning outcomes on his/her own.</td>
            </tr>
        </tbody>
    </table>
@endif
 </div> 
@endforeach


<div class="page" style="background-image: url('https://sms.evolvu.in/public/HPC/SACS/learnerpeerfeedback.jpg')">
   <div style="max-width: 600px; margin: 0 auto; background: transparent; overflow: hidden; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
      <h2 style="margin-top: 150px;color:#1e3a8a;">Learner's Feedback</h2> 
        <!-- Section Header -->
     @foreach($results as $result)
    <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; margin-top: 20px;">
        <!-- Table Header Row -->
        <thead>
            <tr>
                <th colspan="2" style="
                    border: 1px solid #000;
                    background-color: #dcdcdc;
                    text-align: left;
                    padding: 10px 15px;
                    font-size: 18px;
                    font-weight: bold;
                    font-family: 'Times New Roman', serif;
                    text-transform: capitalize;
                    color: #000;
                ">
                    {{ $result['parameter'] ?? '' }}
                </th>
            </tr>
        </thead>

        <tbody>
            @for($term = 1; $term <= 2; $term++)
                <tr>
                    <!-- Term Column -->
                    <td style="
                        border: 1px solid #000;
                        padding: 10px 15px;
                        font-size: 16px;
                        font-weight: bold;
                        color: #333;
                        width: 150px;
                        text-align: center;
                        background:#f1f2f4;
                    ">
                        Term {{ $term }}
                    </td>

                    <!-- Checkbox Options -->
                    <td style="
                        border: 1px solid #000;
                        padding: 10px 15px;
                        text-align: left;
                    ">
                        @php
                            $selectedValues = [];
                            if (isset($result['parameter_values'][$term])) {
                                $selectedValues = array_map('trim', explode(',', $result['parameter_values'][$term]));
                            }
                        @endphp

                        @if(isset($result['options']) && is_array($result['options']))
                            @foreach($result['options'] as $option)
                            @if(in_array($option['option'], $selectedValues))
                                <label style="display: inline-flex; align-items: center; gap: 6px; margin-right: 20px;">
                                    <span style="">{{ $option['value'] }}</span>
                                </label>
                            @endif
                            @endforeach
                        @endif
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
@endforeach


    <br><br>
        <h2 style="font-size: 23px; color: #1e3a8a; font-weight: bold; margin: 10px;">
            Peer Feedback
        </h2>

   @php
  $term1Params = [];
  $term2Params = [];

  foreach ($peerfeedback as $feedback) {
      if (!empty($feedback['parameter_values'][1]) && $feedback['parameter_values'][1] == 'Yes') {
          $term1Params[] = $feedback['parameter'] ?? '';
      }
      if (!empty($feedback['parameter_values'][2]) && $feedback['parameter_values'][2] == 'Yes') {
          $term2Params[] = $feedback['parameter'] ?? '';
      }
  }

  $maxRows = max(count($term1Params), count($term2Params));
@endphp

<table style="
  width: 100%;
  border-collapse: collapse;
  margin-top: 0;
  text-align: center;
  border: 1px solid #000; /* outer border */
">
  <thead>
    <tr>
      <th style="
        padding: 12px;
        background-color: #dcdcdc;
        width: 50%;
        font-weight: bold;
        border-right: 1px solid #000; /* keep middle line */
        border-bottom: 1px solid #000; /* header bottom line */
      ">
        Term 1
      </th>
      <th style="
        padding: 12px;
        background-color: #dcdcdc;
        width: 50%;
        font-weight: bold;
        border-bottom: 1px solid #000;
      ">
        Term 2
      </th>
    </tr>
  </thead>

  <tbody>
    @for($i = 0; $i < $maxRows; $i++)
      <tr>
        <td style="
          padding: 10px;
          font-size: 14px;
          text-align: center;
          vertical-align: middle;
          border-right: 1px solid #000; /* keep center line */
          border-top: none;
          border-bottom: none;
        ">
          {{ $term1Params[$i] ?? '' }}
        </td>

        <td style="
          padding: 10px;
          font-size: 14px;
          text-align: center;
          vertical-align: middle;
          border: none; /* no horizontal or right border */
        ">
          {{ $term2Params[$i] ?? '' }}
        </td>
      </tr>
    @endfor
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
<div class="page" style="background-image: url('https://sms.evolvu.in/public/HPC/SACS/classteacherremark.jpg');page-break-after: always;">
    <div style=" background: transparent; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);padding-left: 70px;padding-right: 70px;">
    <h3 style="color:#1a237e; font-weight:bold;margin-top: 135px;">Class Teacher's Remark</h3>

<table style="width:100%; border-collapse: collapse; font-family:'Comic Sans MS', cursive; border:1px solid #000;">
    <tr>
        <th colspan="2" style="background-color:#dcdcdc; padding:10px; text-align:left; font-weight:bold; border:1px solid #000;">
            Term 1
        </th>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:8px;">{{ isset($classteacherremark[0]['parameter']) ? ucfirst(strtolower($classteacherremark[0]['parameter'])) : '' }}</td>
        <td style="border:1px solid #000; padding:8px;">{{ $classteacherremark[0]['parameter_values'][1] ?? '' }}</td>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:8px;">{{ isset($classteacherremark[1]['parameter']) ? ucfirst(strtolower($classteacherremark[1]['parameter'])) : '' }}</td>
        <td style="border:1px solid #000; padding:8px;">{{ $classteacherremark[1]['parameter_values'][1] ?? '' }}</td>
    </tr>

    <tr>
        <th colspan="2" style="background-color:#dcdcdc; padding:10px; text-align:left; font-weight:bold; border:1px solid #000;">
            Term 2
        </th>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:8px;">{{ isset($classteacherremark[0]['parameter']) ? ucfirst(strtolower($classteacherremark[0]['parameter'])) : '' }}</td>
        <td style="border:1px solid #000; padding:8px;">{{ $classteacherremark[0]['parameter_values'][2] ?? '' }}</td>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:8px;">{{ isset($classteacherremark[1]['parameter']) ? ucfirst(strtolower($classteacherremark[1]['parameter'])) : '' }}</td>
        <td style="border:1px solid #000; padding:8px;">{{ $classteacherremark[1]['parameter_values'][2] ?? '' }}</td>
    </tr>
</table>

<p style="margin-top:25px; font-weight:bold; color:#1a237e;font-size:18px;">Date:</p>
<p style="margin-top:25px; font-weight:bold; color:#1a237e;font-size:18px;">Signature of Class Teacher:</p>
<p style="margin-top:25px; font-weight:bold; color:#1a237e;font-size:18px;">Signature of Principal:</p>
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