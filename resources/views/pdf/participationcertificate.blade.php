
@php

$school = getSchoolDetails();

$bgAchievementImage = getAchievementBgImage();
$bgParticipationImage = getParticipationBgImage();

$student_name = $pdfData['student_name'] ?? '';
$classname = $pdfData['class_section'] ?? '';
$event = $pdfData['event'] ?? '';
$event_date = $pdfData['event_date'] ?? '';
$academic_year = $pdfData['academic_yr'] ?? '';
$position = $pdfData['position'] ?? '';

/*
|--------------------------------------------------------------------------
| Dynamic Background According To Position
|--------------------------------------------------------------------------
*/
$isAchievementCertificate =
    in_array($position, [1, 2, 3]) ||
    in_array(
        strtolower(trim($position)),
        ['first', 'second', 'third', 'consolation prize']
    );

if ($isAchievementCertificate) {

    $bgImage = $bgAchievementImage;

} else {

    $bgImage = $bgParticipationImage;
}

/*
|--------------------------------------------------------------------------
| Background Path
|--------------------------------------------------------------------------
*/
$bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
    ? asset($bgImage['file_path'])
    : asset('health3_bg.jpg');

/*
|--------------------------------------------------------------------------
| Page Type
|--------------------------------------------------------------------------
*/
$pageType = $bgImage['page_type'] ?? 'A4 landscape';

/*
|--------------------------------------------------------------------------
| Page Size and Top Margin
|--------------------------------------------------------------------------
*/

switch ($pageType) {

    case 'A4 landscape':
        $pageSize = 'A4 landscape';
        $contentTop = '200px';
        $fontSize = '14px';
        $italicFontSize = '22px';
        break;

    case 'A5 portrait':
        $pageSize = 'A5 portrait';
        $contentTop = '250px';
        $fontSize = '14px';
        $italicFontSize = '16px';
        break;

    case 'A5 landscape':
        $pageSize = 'A5 landscape';
        $contentTop = '120px';
        $fontSize = '14px';
        $italicFontSize = '16px';
        break;

    case 'Letter portrait':
        $pageSize = 'letter portrait';
        $contentTop = '150px';
        $fontSize = '18px';
        $italicFontSize = '20px';
        break;

    case 'Letter landscape':
        $pageSize = 'letter landscape';
        $contentTop = '120px';
        $fontSize = '18px';
        $italicFontSize = '20px';
        break;

    case 'A4 portrait':
    default:
        $pageSize = 'A4 portrait';
        $contentTop = '300px';
        $fontSize = '20px';
        $italicFontSize = '22px';
        break;
}

@endphp



<!DOCTYPE html>
<html>

<head>

<style>

    @page {
        size: {{ $pageSize }};
        margin: 0;
        padding: 0;
    }
    
    html,body{
    margin:0;
    padding:0;
    font-family:Arial,sans-serif;
    }

   body{
    background-image:url('{{ $bgPath }}');
    background-repeat:no-repeat;
    background-size:100% 100%;
    background-position:center;
    overflow:hidden;
    }

    .pdfdiv {

        width: 100%;
        height: 100%;

        position: relative;
    }

    table {
        border-collapse: collapse;
    }

    tr td{
    padding-top:3px;
    padding-bottom:3px;
    word-wrap:break-word;
    font-size:{{ $fontSize }};
    font-family:Arial,sans-serif;
    text-align:left;
    vertical-align:middle;
    }

    .statistics_line {

        width: 100%;

        border-bottom: 1px solid #000;

        padding: 3px;

        min-height: 20px;
    }

    /* Main content wrapper */
    .certificate-wrapper{
    width:90%;
    margin-left:5%;
    margin-right:5%;
    margin-top:{{$contentTop}};
    }

  .main-table{
    width:90%;
    margin:auto;
    border-spacing:0;
    background:transparent;
   }

   .italic-text{
    font-style:italic;
    font-size:{{ $italicFontSize }};
    white-space:nowrap;
    }

    .signature-section{
    margin-top:100px;
    /*margin-bottom: 30px;*/
    }

    .signature-table {

        width: 100%;

        border-collapse: collapse;
    }

   .signature-table{
     width:100%;
    }


   .date-td{
    width:50%;
    text-align:left;
    padding-left:160px;
    }

   .signature-td{
    width:50%;
    text-align:right;
    padding-right:70px;
    }

    .date-value {

        font-size: 14px;

        font-family: Arial, sans-serif;

        white-space: nowrap;
    }

    .signature-img {

        width: 70px;

        height: 50px;
    }

</style>

</head>

<body>

    <div class="certificate-wrapper">

        <table
            border="0"
            class="main-table"
            cellpadding="0"
            cellspacing="0"
        >

            {{-- Heading --}}
            <tr>

                <td
                    style="
                        font-style: italic;
                        font-size: 14px;
                        text-align: center;
                        width: 90%;
                        padding-top: 25px;
                        padding-bottom: 2px;
                    "
                >

                   @if(
                        in_array($position, [1, 2, 3]) ||
                        in_array($position, ['First', 'Second', 'Third', 'Consolation prize'])
                    )
                        This is to certify that
                    @else
                        This certificate recognises that
                    @endif

                </td>

            </tr>

            {{-- Student Name --}}
            <tr>

                <td>

                    <br>

                    <table
                        style="
                            width:100%;
                            border-spacing:0px;
                            background-color:transparent;
                        "
                        cellpadding="0"
                        cellspacing="0"
                    >

                        <tr>

                            <td
                                class="italic-text"
                                style="
                                    width:18%;
                                    padding-top:8px;
                                    white-space: nowrap;
                                "
                            >

                                Master / Miss

                            </td>

                            <td
                                style="
                                    font-size:14px;
                                    width:auto;
                                    text-align:center;
                                "
                            >

                                <div class="statistics_line">

                                    {{ strtoupper($student_name) }}

                                </div>

                            </td>

                        </tr>

                    </table>

                    <br>

                </td>

            </tr>

            {{-- Position Certificate --}}
           @if(
                 in_array($position, [1, 2, 3]) ||
                 in_array($position, ['First', 'Second', 'Third', 'Consolation prize'])
                )

            <tr>

                <td>

                    <table
                        style="
                            width:100%;
                            border-spacing:0px;
                            background-color:transparent;
                            border-collapse: collapse;
                        "
                        cellpadding="0"
                        cellspacing="0"
                    >

                <tr>
                    <td style="text-align:center;">

                        <div style="font-size:14px; font-style:italic; margin-top:10px;">

                            of std

                            <span class="statistics_line"
                                style="display:inline-block;width:120px;text-align:center;">
                                {{ $classname }}
                            </span>

                            has secured

                            <span class="statistics_line"
                                style="display:inline-block;width:120px;text-align:center;">
                                {{ ucfirst($position) }}
                            </span>

                            place in

                        </div>

                        <div style="
                            margin-top:10px;
                            text-align:center;
                            font-size:14px;
                        ">

                            <span class="statistics_line"
                                style="
                                    display:inline-block;
                                    width:500px;
                                    text-align:center;
                                ">
                                {{ $event }}
                            </span>

                        </div>

                    </td>
                </tr>

                    </table>

                    <br>

                </td>

            </tr>

            @else

            {{-- Participation Certificate --}}
            <tr>

                <td>

                    <table
                        style="
                            width:100%;
                            border-spacing:0px;
                            background-color:transparent;
                            border-collapse: collapse;
                        "
                        cellpadding="0"
                        cellspacing="0"
                    >

                        <tr>

                            <td
                                class="italic-text"
                                style="
                                    padding:5px;
                                    width:7%;
                                    padding-top:15px;
                                    white-space: nowrap;
                                "
                            >

                                of std

                            </td>

                            <td
                                style="
                                    font-size:14px;
                                    padding:5px;
                                    width:25%;
                                    text-align:center;
                                "
                            >

                                <div class="statistics_line">

                                    {{ $classname }}

                                </div>

                            </td>

                            <td
                                class="italic-text"
                                style="
                                    padding:5px;
                                    width:10%;
                                    padding-top:7px;
                                    white-space: nowrap;
                                "
                            >

                                took part in

                            </td>

                            <td
                                style="
                                    width:auto;
                                    text-align:center;
                                "
                            >

                                <div class="statistics_line">

                                    {{ $event }}

                                </div>

                            </td>

                        </tr>

                    </table>

                    <br>

                </td>

            </tr>

            @endif

            {{-- Event Date --}}
            <tr>
                <td>
                    <table
                        style="
                            width:100%;
                            border-spacing:0px;
                            background-color:transparent;
                        "
                        cellpadding="0"
                        cellspacing="0"
                    >

                        <tr>

                            <td
                                class="italic-text"
                                style="
                                    padding:5px;
                                    width:1%;
                                    padding-top:15px;
                                    white-space: nowrap;
                                "
                            >

                                on

                            </td>

                            <td
                                style="
                                    width:25%;
                                    text-align:center;
                                "
                            >

                                <div class="statistics_line">

                                    {{ date('d-m-Y', strtotime($event_date)) }}

                                </div>

                            </td>

                        </tr>

                    </table>

                </td>

            </tr>

        </table>


    </div>
        
    <div class="signature-section">

            <table class="signature-table">

                <tr>

                    {{-- Date --}}
                    <td class="date-td">

                        <div class="date-value">

                            {{ date('d-m-Y', strtotime($event_date)) }}

                        </div>

                    </td>

                    {{-- Signature --}}
                    <td class="signature-td">

                        {{-- <img
                            src="{{ public_path('Principal_Cert_Signature.png') }}"
                            class="signature-img"
                        > --}}

                    </td>

                </tr>

            </table>

    </div>

</body>

</html>