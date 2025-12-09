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
    <table class="header-table">
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
    </table>

    <!-- School Info -->
    <table class="info-table">
        <tr>
            <td width="30%">CBSE Affiliation No.: 1130512</td>
            <td width="40%">SCHOOL CODE: 30437</td>
        </tr>
    </table>

    <!-- Title -->
    <div class="title">BONAFIDE AND CHARACTER CERTIFICATE</div>

    <p>Date: <b>{{$data->issue_date_bonafide}}</b></p>
    <?php 
                        // $academic_yr_to = $this->crud_model->get_academic_yr_to();
                        // $to_year = date('Y', strtotime($academic_yr_to) ); 
                        $academic_yr_to = $data->academic_yr;
                        $acd_yr = explode('-',$academic_yr_to);
                        $to_year = date('Y', strtotime($acd_yr[1])); 
                        //$to_year = '2024';
     ?>
    <p>
        This is to certify that Master / Miss {{$data->stud_name}} was a bonafide student of our school studying in Std {{$data->class_division}} in the year {{$data->academic_yr}}. Her / His date of birth as per the General Register of the school is {{ \Carbon\Carbon::parse($data->dob)->format('d-m-Y') . ' [ ' . $data->dob_words . ' ]' }}. She / He holds a good moral character. She / He has passed her /his CBSE Std. {{$data->class_division}} Examination of Feb / March <?php echo $to_year;?> in the <?php echo $data->attempt;?>
    </p>

    <p><b>Details:</b></p>

    <!-- Details Table -->
    <table class="details-table">
        <tr><td width="30%">Student's Name</td><td>: {{$data->stud_name}}</td></tr>
        <tr><td>Class</td><td>: {{$data->class_division}}</td></tr>
        <tr><td>Date of Birth (Figures)</td><td>: {{$data->dob}}</td></tr>
        <tr><td>Date of Birth (Words)</td><td>: {{$data->dob_words}}</td></tr>
        <tr><td>Attempt</td><td>: {{$data->attempt}}</td></tr>
    </table>

    <!-- Footer -->
    <p>Place: Pune</p>

    <div class="signature">
        Clerk <span>Principal</span>
    </div>

</div>

</body>
</html>
