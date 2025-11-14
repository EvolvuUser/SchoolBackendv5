<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bonafide Caste Certificate</title>
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
    <div class="title">BONAFIDE CASTE CERTIFICATE</div>

    <p>
        This is to certify {{$data->stud_name}} was a student of Holy Spirit Convent School in class {{$data->class_division}} for the academic session {{$data->academic_yr}} as per the school record her details are as follows
    </p>

    <p><b>Details:</b></p>

    <!-- Details Table -->
    <table class="details-table">
        <tr>
            <td>Student Name</td>
            <td>
                <?php echo $data->stud_name; ?><br>
            </td>
        </tr>
        <tr><td>Nationality</td><td><?php echo $data->nationality; ?></td></tr>
        <tr><td>Religion</td><td><?php echo $data->religion; ?></td></tr>
        <tr><td>Caste</td><td><?php echo $data->caste; ?></td></tr>
        <tr><td>Sub Caste</td><td><?php echo $data->subcaste; ?></td></tr>
        <tr>
            <td>Date of Birth</td>
            <td><?php echo date_format(date_create($data->dob),'d-m-Y').' ( '.$data->dob_words.')'; ?></td>
        </tr>
        <tr><td>Previous School and Class</td><td><?php echo $data->prev_school_class; ?></td></tr>
        <tr><td>Date of Admission</td><td><?php echo date_format(date_create($data->admission_date),'d-m-Y'); ?></td></tr>
        <tr><td>In Which Class and When</td><td><?php echo $data->class_when_learning; ?></td></tr>
        <tr><td>Progress Report</td><td><?php echo $data->progress; ?></td></tr>
        <tr><td>Behaviour</td><td><?php echo $data->behaviour; ?></td></tr>
        <tr><td>Reason for Leaving</td><td><?php echo $data->leaving_reason; ?></td></tr>
        <tr>
            <td>Date of Leaving Certificate</td>
            <td>{{ \Carbon\Carbon::parse($data->lc_date_n_no)->format('d-m-Y') }}</td>
        </tr>
    </table>

    <!-- Footer -->
    <p>Place: Pune</p>

    <div class="signature">
        Clerk <span>Principal</span>
    </div>

</div>

</body>
</html>
