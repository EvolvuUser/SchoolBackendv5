<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Simple Bonafide Certificate</title>
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
    <div class="title">SIMPLE BONAFIDE CERTIFICATE</div>

    <!-- General Info -->
    <p>G. R. No.: <b>{{$data->reg_no}}</b></p>
    <p>Date: <b>{{$data->issue_date_bonafide}}</b></p>

    <p>
        This is to certify that Master / Miss {{$data->stud_name}} , son / daughter of Mr. {{$data->father_name}} is a bonafide student of Holy Spirit Convent School studying in our school in class {{$data->class_division}} for the academic year {{$data->academic_yr}}.According to our record his / her date of birth is {{ \Carbon\Carbon::parse($data->dob)->format('d-m-Y') . ' (' . $data->dob_words . ')' }}
    </p>

    <p><b>Details:</b></p>

    <!-- Details Table -->
    <table class="details-table">
        <tr><td width="30%">Student's Name</td><td>: {{$data->stud_name}}</td></tr>
        <tr><td>Class</td><td>: {{$data->class_division}}</td></tr>
        <tr><td>Father’s Name</td><td>: {{$data->father_name}}</td></tr>
        <tr><td>Date of Birth (Figures)</td><td>: {{$data->dob}}</td></tr>
        <tr><td>Date of Birth (Words)</td><td>: {{$data->dob_words}}</td></tr>
    </table>

    <!-- Footer -->
    <p>Place: Pune</p>

    <div class="signature">
        Clerk <span>Principal</span>
    </div>

</div>

</body>
</html>
