{{-- <!DOCTYPE html>
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
</html> --}}

@php
$school = getSchoolDetails();
$bgImage = getSimpleBonafideBgImage();

/* ✅ Safe background fallback */
$bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
    ? asset($bgImage['file_path'])
    : asset('health3_bg.jpg');

/* Optional page type (if you want later use) */
$pageType = $bgImage['page_type'] ?? 'A4 portrait';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Simple Bonafide Certificate</title>

{{-- <style>

    /* ✅ Page size control (DOMPDF safe switch case) */
    @page {

        @switch($pageType)

            @case('A4 portrait')
                size: A4 portrait;
                @break

            @case('A4 landscape')
                size: A4 landscape;
                @break

            @case('A5 portrait')
                size: A5 portrait;
                @break

            @case('A5 landscape')
                size: A5 landscape;
                @break

            @case('Letter portrait')
                size: letter portrait;
                @break

            @case('Letter landscape')
                size: letter landscape;
                @break

            @default
                size: A5 landscape;

        @endswitch

        margin: 0;
    }

    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        font-size: 15px;
    }

    .certificate-container {
        width: 95%;
        margin: auto;
        border: 3px groove grey;

        /* keep background here */
        background-image: url('{{ $bgPath }}');
        background-size: 100% 100%;
        background-position: center;
        background-repeat: no-repeat;
    }

    /* ✅ THIS CREATES PROPER GAP INSIDE BORDER */
    .certificate-content {
        padding: 35px 30px; /* 👈 REAL GAP BETWEEN BORDER & CONTENT */
        box-sizing: border-box;
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

    /* FIXED TITLE SPACING */
    .title {
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        /* margin: 15px 0; */
        text-decoration: underline;
        margin-top: 25%;
        margin-bottom: 15px
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

</style> --}}

<style>

    /* =========================
       PAGE SETUP (DOMPDF SAFE)
    ========================== */
    @page {

        @switch($pageType)

            @case('A4 portrait')
                size: A4 portrait;
                @break

            @case('A4 landscape')
                size: A4 landscape;
                @break

            @case('A5 portrait')
                size: A5 portrait;
                @break

            @case('A5 landscape')
                size: A5 landscape;
                @break

            @case('Letter portrait')
                size: letter portrait;
                @break

            @case('Letter landscape')
                size: letter landscape;
                @break

            @default
                size: A4 portrait;

        @endswitch

        /* ✅ PRINT SAFE MARGINS */
        margin: 15mm 12mm 15mm 12mm;
    }

    /* =========================
       BASE RESET
    ========================== */
    html, body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        font-size: 15px;
        height: 100%;
    }

    /* =========================
       MAIN CONTAINER
    ========================== */
    .certificate-container {
        width: 100%;
        margin: auto;
        border: 3px groove grey;
        box-sizing: border-box;

        /* BACKGROUND */
        background-image: url('{{ $bgPath }}');
        background-size: 100% 100%;
        background-position: center;
        background-repeat: no-repeat;
    }

    /* =========================
       INNER CONTENT GAP
    ========================== */
    .certificate-content {
        padding: 35px 30px;
        box-sizing: border-box;
        min-height: 250mm; /* A4 safe height */
    }

    /* =========================
       HEADER
    ========================== */
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

    /* =========================
       INFO TABLE
    ========================== */
    .info-table {
        width: 100%;
        margin-top: 10px;
        font-size: 14px;
    }

    .info-table td {
        padding: 4px;
    }

    /* =========================
       TITLE (FIXED SPACING)
    ========================== */
    .title {
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        text-decoration: underline;

        margin-top: 150px;   /* safer than 25% */
        margin-bottom: 15px;
    }

    /* =========================
       DETAILS TABLE
    ========================== */
    .details-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .details-table td {
        padding: 6px 8px;
        font-size: 15px;
    }

    /* =========================
       SIGNATURE
    ========================== */
    .signature {
        margin-top: 40px;
        font-size: 15px;
    }

    .signature span {
        float: right;
        margin-right: 15%;
    }

    /* =========================
       HR STYLE
    ========================== */
    hr.dotted {
        border: 1px dotted black;
        margin-top: 10px;
        margin-bottom: 10px;
    }

</style>
</head>

<body>

<div class="certificate-container">

    <!-- ✅ NEW INNER WRAPPER FOR GAP -->
    <div class="certificate-content">

        <!-- Title -->
        <div class="title">SIMPLE BONAFIDE CERTIFICATE</div>

        <!-- General Info -->
        <p>G. R. No.: <b>{{$data->reg_no}}</b></p>

        <p>Date: <b>{{$data->issue_date_bonafide}}</b></p>

        <p>
            This is to certify that Master / Miss {{$data->stud_name}},
            son / daughter of Mr. {{$data->father_name}}
            is a bonafide student of Holy Spirit Convent School
            studying in our school in class {{$data->class_division}}
            for the academic year {{$data->academic_yr}}.
            According to our record his / her date of birth is
            {{ \Carbon\Carbon::parse($data->dob)->format('d-m-Y') . ' (' . $data->dob_words . ')' }}
        </p>

        <p><b>Details:</b></p>

        <!-- Details Table -->
        <table class="details-table">
            <tr>
                <td width="30%">Student's Name</td>
                <td>: {{$data->stud_name}}</td>
            </tr>

            <tr>
                <td>Class</td>
                <td>: {{$data->class_division}}</td>
            </tr>

            <tr>
                <td>Father’s Name</td>
                <td>: {{$data->father_name}}</td>
            </tr>

            <tr>
                <td>Date of Birth (Figures)</td>
                <td>: {{$data->dob}}</td>
            </tr>

            <tr>
                <td>Date of Birth (Words)</td>
                <td>: {{$data->dob_words}}</td>
            </tr>
        </table>

        <!-- Footer -->
        <p>Place: Pune</p>

        <div class="signature">
            Clerk <span>Principal</span>
        </div>

    </div>
</div>

</body>
</html>