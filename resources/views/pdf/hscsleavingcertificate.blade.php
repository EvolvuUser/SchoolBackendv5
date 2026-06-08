{{-- @php
$school = getSchoolDetails();
$bgImage = getHealthBgImage();
@endphp


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Leaving Certificate</title>
  <style>
    @page {
      size: A4;
      margin: 0;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      font-size: 14px;
      /* background: url('https://sms.evolvu.in/public/HSCS/lc_bg.jpg'); */
      background: url('{{ asset($bgImage['file_path']) }}');
      background-size: cover;
    }

    .certificate-container {
      width: 80%;
      margin: 28% auto 0 auto; 
      background: #ffffff;
      padding: 30px;
      border-radius: 10px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }

    td {
      padding: 5px;
      vertical-align: top;
    }

    td.label {
      width: 40%;
      font-weight: bold;
    }

    td.separator {
      width: 5%;
      text-align: center;
    }

    h1 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 22px;
      text-transform: uppercase;
    }

    .declaration {
      font-size: 13px;
      margin-top: 5px;
      line-height: 1.5;
      text-align: justify;
    }

    .footer {
      margin-top: 30px;
      font-size: 12px;
    }

    .footer .date {
      float: left;
    }

    .footer .signature {
      float: right;
      text-align: center;
    }

    .clearfix::after {
      content: "";
      display: block;
      clear: both;
    }
  </style>
</head>
<body>

<div class="certificate-container">

  <table>
    <tr>
      <td><b>LC No.: {{ $data->sr_no; }}</b></td>
      <td><b>GR No.: {{ $data->grn_no; }}</b></td>
      <td><b>Student ID: {{ $data->stud_id_no; }}</b></td>
    </tr>
    <tr>
      <td><b>PEN No.: {{ $data->udise_pen_no; }}</b></td>
      <td><b>APAAR ID: {{ $data->apaar_id; }}</b></td>
      <td></td>
    </tr>
  </table>

  <table>
    <tr><td class="label">Name of Pupil</td><td class="separator">:</td><td><?php echo $data->stud_name . ' ' . $data->mid_name . ' ' . $data->last_name; ?></td></tr>
    <tr><td class="label">Father’s Name</td><td class="separator">:</td><td><?php echo $data->father_name; ?></td></tr>
    <tr><td class="label">Mother’s Name</td><td class="separator">:</td><td><?php echo $data->mother_name; ?></td></tr>
    <tr><td class="label">Date of Birth</td><td class="separator">:</td><td><?php echo date_format(date_create($data->dob), 'd-m-Y') . ' (' . $data->dob_words . ')'; ?></td></tr>
    <tr><td class="label">Place of Birth</td><td class="separator">:</td><td><?php echo $data->birth_place; ?></td></tr>
    <tr><td class="label">Aadhar No</td><td class="separator">:</td><td><?php echo $data->aadhar_no; ?></td></tr>
    <tr><td class="label">Mother Tongue</td><td class="separator">:</td><td><?php echo $data->mother_tongue; ?></td></tr>
    <tr><td class="label">Nationality</td><td class="separator">:</td><td><?php echo $data->nationality; ?></td></tr>
    <?php
    if ($data->religion != '') {
      if ($data->caste != '') {
        if ($data->subcaste != '') {
          $relcast = $data->religion . ', ' . $data->caste . ' (' . $data->subcaste . ')';
        } else {
          $relcast = $data->religion . ', ' . $data->caste;
        }
      } else {
        if ($data->subcaste != '') {
          $relcast = $data->religion . ' (' . $data->subcaste . ')';
        } else {
          $relcast = $data->religion;
        }
      }
    } elseif ($data->caste != '') {
      if ($data->subcaste != '') {
        $relcast = $data->caste . ' (' . $data->subcaste . ')';
      } else {
        $relcast = $data->caste;
      }
    } else {
      if ($data->subcaste != '') {
        $relcast = $data->religion . ' (' . $data->subcaste . ')';
      } else {
        $relcast = $data->religion;
      }
    }

    ?>
    <tr><td class="label">Religion & Caste</td><td class="separator">:</td><td><?php echo $relcast; ?></td></tr>
    <tr><td class="label">Date of Admission / Class</td><td class="separator">:</td><td><?php echo date_format(date_create($data->date_of_admission), 'd-m-Y') . ' / Class-' . $data->admission_class; ?></td></tr>
    <tr><td class="label">Last Studied Class</td><td class="separator">:</td><td><?php echo $data->standard_studying; ?></td></tr>
    <tr><td class="label">Promotion Status</td><td class="separator">:</td><td><?php echo $data->promoted_to; ?></td></tr>
    <tr><td class="label">Last Exam & Result</td><td class="separator">:</td><td><?php echo $data->last_exam; ?></td></tr>
    <tr><td class="label">Total Working Days</td><td class="separator">:</td><td><?php echo $data->working_days; ?></td></tr>
    <tr><td class="label">Days Present</td><td class="separator">:</td><td><?php echo $data->attendance; ?></td></tr>
    <tr><td class="label">Fees Paid Till</td><td class="separator">:</td><td><?php echo $data->fee_month; ?></td></tr>
    <tr><td class="label">Part of NCC/Scout/Guide</td><td class="separator">:</td><td><?php echo $data->part_of; ?></td></tr>
    <tr><td class="label">Games / Activities</td><td class="separator">:</td><td><?php echo $data->games; ?></td></tr>
    <tr><td class="label">Application Date</td><td class="separator">:</td><td><?php echo date_format(date_create($data->application_date), 'd-m-Y'); ?></td></tr>
    <tr><td class="label">Issue Date</td><td class="separator">:</td><td><?php echo date_format(date_create($data->leaving_date), 'd-m-Y'); ?></td></tr>
    <tr><td class="label">Conduct</td><td class="separator">:</td><td><?php echo $data->conduct; ?></td></tr>
    <tr><td class="label">Reason for Leaving</td><td class="separator">:</td><td><?php echo $data->reason_leaving; ?></td></tr>
    <tr><td class="label">Remarks</td><td class="separator">:</td><td><?php echo $data->remark; ?></td></tr>
  </table>

  <p class="declaration">
    I hereby declare that the above information including Name of the Candidate, Father’s/ Guardian Name, Mother’s Name and Date of Birth furnished above is correct as per school records.
  </p>

  <div class="footer clearfix">
    <div class="date">Date: <?php echo date_format(date_create($data->issue_date), 'd-m-Y'); ?></div>
    <div class="signature">Signature of Principal</div>
  </div>
</div>

</body>
</html> --}}

@php
$school = getSchoolDetails();
$bgImage = getLeavingBgImage();

$bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
    ? asset($bgImage['file_path'])
    : asset('health3_bg.jpg');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bonafide Certificate</title>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        font-size: 15px;
    }

    .certificate-container {
        width: 95%;
        margin: auto;
        border: 3px groove grey;
        padding: 20px;

        /*  BACKGROUND IMAGE */
        background-image: url('{{ asset($bgImage['file_path']) }}');
        background-size: 100% 100%;
        background-position: center;
        background-repeat: no-repeat;
    }

    .header-table {
        width: 100%;
        border: none;
    }

    .header-table td {
        vertical-align: middle;
        text-align: center;
    }

    .header-left img {
        max-width: 150px;
        max-height: 130px;
    }

    .school-name {
        font-size: 30px;
        color: red;
        font-weight: bold;
    }

    .school-details {
        font-size: 14px;
    }

    .info-table {
        width: 100%;
        margin-top: 10px;
        font-size: 14px;
    }

    .info-table td {
        padding: 4px;
    }

    .title {
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        margin: 15px 0;
        text-decoration: underline;
    }

    .details-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .details-table td {
        padding: 6px 8px;
        font-size: 15px;
    }

    .signature {
        margin-top: 40px;
        font-size: 15px;
    }

    .signature span {
        float: right;
        margin-right: 15%;
    }

    hr.dotted {
        border: 1px dotted black;
        margin-top: 10px;
        margin-bottom: 10px;
    }
</style>
</head>

<body>

<div class="certificate-container">

    <!-- Header -->
    {{-- <table class="header-table">
        <tr>
            <td class="header-left" width="20%">
                <img src="https://sms.evolvu.in/public/HSCS/logo.jpg" alt="School Logo">
            </td>

            <td>
                <div class="school-name">Holy Spirit Convent School</div>

                <div class="school-details">
                    Lonikand P.O Haveli, Pune - 412216.<br>
                    Mobile: 9763692681 | Email: holyspiritcbse@gmail.com
                </div>

                <hr class="dotted">
            </td>
        </tr>
    </table> --}}

    <!-- School Info -->
    {{-- <table class="info-table">
        <tr>
            <td width="30%">CBSE Affiliation No.: 1130512</td>
            <td width="40%">SCHOOL CODE: 30437</td>
        </tr>
    </table> --}}

    <!-- Title -->
    <div class="title">BONAFIDE CERTIFICATE</div>

    <!-- General Info -->
    <p>G. R. No.: <b>{{$data->reg_no}}</b></p>

    <p>Date: <b>{{$data->issue_date_bonafide}}</b></p>

    <p>
        This is to certify that the student whose details are given below is a Bonafide student of this school studying in Std <b>{{$data->class_division}}</b>. 
        His/Her progress in the studies is good and to the best of our knowledge he/she bears a good moral character. 
        The details given below are as per our general register.
    </p>

    <p><b>Details:</b></p>

    <!-- Details Table -->
    <table class="details-table">
        <tr><td width="30%">Student's Name</td><td>: {{$data->stud_name}}</td></tr>
        <tr><td>Father’s Name</td><td>: {{$data->father_name}}</td></tr>
        <tr><td>Mother’s Name</td><td>: {{$data->mother_name}}</td></tr>
        <tr><td>Date of Birth (Figures)</td><td>: {{$data->dob}}</td></tr>
        <tr><td>Date of Birth (Words)</td><td>: {{$data->dob_words}}</td></tr>
        <tr><td>Place of Birth</td><td>: {{$data->birth_place}}</td></tr>
        <tr><td>State</td><td>: {{$data->state}}</td></tr>
        <tr><td>Religion</td><td>: {{$data->religion}}</td></tr>
        <tr><td>Caste</td><td>: {{$data->caste}}</td></tr>
        <tr><td>Sub-Caste</td><td>: {{$data->subcaste}}</td></tr>
        <tr><td>Nationality</td><td>: {{$data->nationality}}</td></tr>
        <tr><td>Address</td><td>: {{$data->permant_add}}</td></tr>
    </table>

    <!-- Footer -->
    <p>Place: Pune</p>

    <div class="signature">
        Clerk <span>Principal</span>
    </div>

</div>

</body>
</html>
