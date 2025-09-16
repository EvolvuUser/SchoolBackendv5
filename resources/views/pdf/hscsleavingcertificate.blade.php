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
      background: url('https://sms.evolvu.in/public/HSCS/lc_bg.jpg');
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
    <tr><td class="label">Name of Pupil</td><td class="separator">:</td><td><?php echo $data->stud_name . " " . $data->mid_name . " " . $data->last_name; ?></td></tr>
    <tr><td class="label">Father’s Name</td><td class="separator">:</td><td><?php echo $data->father_name; ?></td></tr>
    <tr><td class="label">Mother’s Name</td><td class="separator">:</td><td><?php echo $data->mother_name; ?></td></tr>
    <tr><td class="label">Date of Birth</td><td class="separator">:</td><td><?php echo date_format(date_create($data->dob), 'd-m-Y') . ' (' . $data->dob_words . ')'; ?></td></tr>
    <tr><td class="label">Place of Birth</td><td class="separator">:</td><td><?php echo $data->birth_place; ?></td></tr>
    <tr><td class="label">Aadhar No</td><td class="separator">:</td><td><?php echo $data->aadhar_no; ?></td></tr>
    <tr><td class="label">Mother Tongue</td><td class="separator">:</td><td><?php echo $data->mother_tongue; ?></td></tr>
    <tr><td class="label">Nationality</td><td class="separator">:</td><td><?php echo $data->nationality; ?></td></tr>
    <?php
    if($data->religion!='' )
    {
        if($data->caste!='')
        {
            if($data->subcaste!='')
            {
               $relcast = $data->religion.", ".$data->caste." (".$data->subcaste.")";   
            }
            else
            {
                $relcast = $data->religion.", ".$data->caste;
            }
          
        }
        else
        {
           if($data->subcaste!='')
            {
               $relcast = $data->religion." (".$data->subcaste.")";   
            }
            else
            {
                $relcast = $data->religion;
            } 
        }
        
    }
    elseif($data->caste!='')
        {
            if($data->subcaste!='')
            {
               $relcast = $data->caste." (".$data->subcaste.")";   
            }
            else
            {
                $relcast = $data->caste;
            }
          
        }
        else
        {
           if($data->subcaste!='')
            {
               $relcast = $data->religion." (".$data->subcaste.")";     
            }
            else
            {
                $relcast = $data->religion;
            } 
        }
    
    ?>
    <tr><td class="label">Religion & Caste</td><td class="separator">:</td><td><?php echo $relcast; ?></td></tr>
    <tr><td class="label">Date of Admission / Class</td><td class="separator">:</td><td><?php echo date_format(date_create($data->date_of_admission), 'd-m-Y') . " / Class-" . $data->admission_class; ?></td></tr>
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
</html>
