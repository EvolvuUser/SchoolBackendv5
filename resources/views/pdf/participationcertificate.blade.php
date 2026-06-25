{{-- @php

$school = getSchoolDetails();

$bgPath = public_path('participationcertificatepdf.png');

$student_name = $pdfData['student_name'] ?? '';

$classname = $pdfData['class_section'] ?? '';

$event = $pdfData['event'] ?? '';
// dd($event);

$event_date = $pdfData['event_date'] ?? '';

$academic_year = $pdfData['academic_yr'] ?? '';

@endphp

<!DOCTYPE html>
<html>

<head>

    <style>

    @page {
        margin: 0;
        padding: 0;
    }

    html,
    body {

        width: 100%;
        height: 100%;

        margin: 0;
        padding: 0;

        overflow: hidden;

        font-family: Arial, sans-serif;

        text-align: left;
    }

    body {

        background-image: url('{{ $bgPath }}');

        background-repeat: no-repeat;

        background-size: 100% 100%;

        background-position: center center;
    }

    .pdfdiv {

        width: 100%;
        height: 100%;

        position: relative;
    }

    table {

        border-collapse: collapse;
    }

    tr td {

        padding-top: 3px;

        padding-bottom: 3px;

        word-wrap: break-word;

        font-size: 20px;

        font-family: Arial, sans-serif;

        text-align: left;

        vertical-align: middle;
    }

    .statistics_line {

        width: 100%;

        border-bottom: 1px solid #000;

        padding: 3px;

        min-height: 20px;
    }

    .certificate-wrapper {

        width: 90%;

        margin-top: 20%;

        margin-left: 5%;

        text-align: center;
    }

    .main-table {

        width: 90%;

        margin-left: 5%;

        margin-right: auto;

        border-spacing: 0;

        background-color: transparent;

        margin-top: 8%;
    }

    .italic-text {

        font-style: italic;

        font-size: 22px;

        white-space: nowrap;
    }


   .signature-section {

    width: 100%;

    margin-top: 6%;

    position: relative;
}

.signature-table {

    width: 100%;

    border-collapse: collapse;
}

.signature-table td {

    vertical-align: top;

    padding-right: 35%;
}

.date-td {

    width: 40%;

    text-align: left;

    padding-left: 30%;

    padding-top: 35px;
}

.signature-td {

    width: 60%;

    text-align: right;

    padding-right: 12%;

    padding-top: 0;
}

.date-value {

    font-size: 16px;

    font-family: Arial, sans-serif;
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
            cellpadding="1"
            cellspacing="10"
        >


            <tr>

                <td
                    style="
                        font-style: italic;
                        font-size: 22px;
                        text-align: center;
                        width: 90%;
                        padding-top: 8px;
                        padding-bottom: 8px;
                    "
                >

                    This certificate recognises that

                </td>

            </tr>

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
                                    padding-bottom:8px;
                                    white-space: nowrap;
                                "
                            >

                                Master / Miss

                            </td>

                            <td
                                style="
                                    font-size:20px;
                                    width:auto;
                                    text-align:center;
                                "
                            >

                                <div class="statistics_line">

                                    {{ $student_name }}

                                </div>

                            </td>

                        </tr>

                    </table>

                    <br>

                </td>

            </tr>

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
                                    font-size:20px;
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
                                    padding-top:15px;
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

        
         <div class="signature-section">

    <table class="signature-table">

        <tr>

            <td class="date-td">

                <div class="date-value">

                    {{ date('d-m-Y', strtotime($event_date)) }}

                </div>

            </td>

            <td class="signature-td">

                <img
                    src="{{ public_path('Principal_Cert_Signature.png') }}"
                    class="signature-img"
                >

            </td>

        </tr>

    </table>

</div>

    </div>

</body>

</html> --}}


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
        $contentMarginTop = '20%';
        break;

    case 'A5 portrait':
        $pageSize = 'A5 portrait';
        $contentMarginTop = '45%';
        break;

    case 'A5 landscape':
        $pageSize = 'A5 landscape';
        $contentMarginTop = '45%';
        break;

    case 'Letter portrait':
        $pageSize = 'letter portrait';
        $contentMarginTop = '15%';
        break;

    case 'Letter landscape':
        $pageSize = 'letter landscape';
        $contentMarginTop = '20%';
        break;

    case 'A4 portrait':
    default:
        $pageSize = 'A4 portrait';
        $contentMarginTop = '45%';
        break;
}

@endphp


{{-- @php

$school = getSchoolDetails();

$bgAchievementImage = getAchievementBgImage();

$bgAchievementImage = getParticipationBgImage();
// $bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
//     ? asset($bgImage['file_path'])
//     : asset('health3_bg.jpg');
$bgPath = public_path('participationcertificatepdf.png');

$student_name = $pdfData['student_name'] ?? '';

$classname = $pdfData['class_section'] ?? '';

$event = $pdfData['event'] ?? '';

$event_date = $pdfData['event_date'] ?? '';

$academic_year = $pdfData['academic_yr'] ?? '';

$position = $pdfData['position'] ?? '';


// dd($position);

@endphp --}}



<!DOCTYPE html>
<html>

<head>

   <style>

    @page {
        size: {{ $pageSize }};
        margin: 0;
        padding: 0;
    }

    html,
    body {
        width: 100%;
        height: 100%;

        margin: 0;
        padding: 0;

        font-family: Arial, sans-serif;
        text-align: left;
    }

    body {

        background-image: url('{{ $bgPath }}');

        background-repeat: no-repeat;

        background-position: center center;

        /* Prevent background cropping */
        background-size: 100% 100%;
    }

    .pdfdiv {

        width: 100%;
        height: 100%;

        position: relative;
    }

    table {
        border-collapse: collapse;
    }

    tr td {

        padding-top: 3px;
        padding-bottom: 3px;

        word-wrap: break-word;

        font-size: 20px;

        font-family: Arial, sans-serif;

        text-align: left;

        vertical-align: middle;
    }

    .statistics_line {

        width: 100%;

        border-bottom: 1px solid #000;

        padding: 3px;

        min-height: 20px;
    }

    /* Main content wrapper */
    .certificate-wrapper {

        width: 90%;

        margin-top: {{ $contentMarginTop }};

        margin-left: 5%;

        text-align: center;
    }

    .main-table {

        width: 90%;

        margin-left: 5%;

        margin-right: auto;

        border-spacing: 0;

        background-color: transparent;

        margin-top: 8%;
    }

    .italic-text {

        font-style: italic;

        font-size: 22px;

        white-space: nowrap;
    }

    .signature-section {

        width: 100%;

        margin-top: 6%;

        position: relative;
    }

    .signature-table {

        width: 100%;

        border-collapse: collapse;
    }

    .signature-table td {

        vertical-align: top;

        padding-right: 35%;
    }

    .date-td {

        width: 40%;

        text-align: left;

        padding-left: 30%;

        padding-top: 20px;
    }

    .signature-td {

        width: 60%;

        text-align: right;

        padding-right: 12%;

        padding-top: 0;
    }

    .date-value {

        font-size: 16px;

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
            cellpadding="1"
            cellspacing="10"
        >

            {{-- Heading --}}
            <tr>

                <td
                    style="
                        font-style: italic;
                        font-size: 22px;
                        text-align: center;
                        width: 90%;
                        padding-top: 8px;
                        padding-bottom: 8px;
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
                                    padding-bottom:8px;
                                    white-space: nowrap;
                                "
                            >

                                Master / Miss

                            </td>

                            <td
                                style="
                                    font-size:20px;
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
                                    font-size:20px;
                                    padding:5px;
                                    width:18%;
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
                                    width:15%;
                                    padding-top:15px;
                                    white-space: nowrap;
                                    text-align:center;
                                "
                            >

                                has secured

                            </td>

                            <td
                                style="
                                    width:15%;
                                    text-align:center;
                                "
                            >

                                <div class="statistics_line">

                                    @if($position == 'First')
                                        First
                                    @elseif($position == 'Second')
                                        Second
                                    @elseif($position == 'Third')
                                        Third
                                    @elseif($position == 'Consolation Prize')
                                        Consolation Prize
                                    @endif

                                </div>

                            </td>

                            <td
                                class="italic-text"
                                style="
                                    padding:5px;
                                    width:10%;
                                    padding-top:15px;
                                    white-space: nowrap;
                                "
                            >

                                place in

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
                                    font-size:20px;
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
                                    padding-top:15px;
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

    </div>

</body>

</html>