@php
    $school = getSchoolDetails();
    $bgImage = getCasteBgImage();

    $bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
        ? asset($bgImage['file_path'])
        : asset('health3_bg.jpg');

    $pageType = $bgImage['page_type'] ?? 'A4 portrait';

    $headerMarginTop = match ($pageType) {
        'A4 portrait'   => '200px',
        'A4 landscape'  => '150px',
        'A5 portrait'   => '170px',
        'A5 landscape'  => '130px',
        default         => '200px'
    };
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bonafide Caste Certificate</title>

<style>

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

    margin: 0;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html,
body{
    font-family:Arial, sans-serif;
    font-size:13px;
}

body{
    background-image:url('{{ $bgPath }}');
    background-repeat:no-repeat;
    background-position:center top;
    background-size:100% 100%;
}

/* Wrapper */
.page-wrapper{
    width:100%;
}

/* Header space according to template */
.header-spacer{
    height:{{$headerMarginTop}};
}

/* Content */
.certificate-content{
    width:88%;
    margin:0 auto;
    padding-left:25px;
    padding-right:25px;
}

/* Title */
.title{
    text-align:center;
    font-size:16px;
    font-weight:bold;
    text-decoration:underline;
    margin-bottom:15px;
}

/* Intro paragraph */
.intro-para{
    font-size:13px;
    line-height:1.6;
    margin-bottom:10px;
    text-align:justify;
}

.details-label{
    font-size:13px;
    font-weight:bold;
    margin-bottom:5px;
}

/* Table */
.details-table{
    width:100%;
    border-collapse:collapse;
    margin-top:5px;
}

.details-table td{
    border:1px solid #000;
    padding:5px 8px;
    font-size:12px;
    vertical-align:top;
}

.details-table td:first-child{
    width:38%;
}

/* Place */
.place{
    margin-top:15px;
    font-size:13px;
}

/* Signature */
.signature{
    width:100%;
    margin-top:30px;
}

.signature-left{
    float:left;
    margin-left:2%;
}

.signature-right{
    float:right;
    margin-right:8%;
}

.clearfix{
    clear:both;
}

</style>



</head>
<body>

{{--
    .page-wrapper is exactly one page tall with the BG image stretched to fill it.
    .header-spacer pushes content below the BG image's built-in header.
    .certificate-content fills the remaining space with flex:1 so the
    signature stays inside the BG image's bottom border.
--}}

<div class="page-wrapper">

    <div class="header-spacer"></div>

    <div class="certificate-content">

        <div class="title">BONAFIDE CASTE CERTIFICATE</div>

        <p class="intro-para">
            This is to certify <strong>{{ $data->stud_name }}</strong> was a student of
            Holy Spirit Convent School in class <strong>{{ $data->class_division }}</strong>
            for the academic session <strong>{{ $data->academic_yr }}</strong>
            as per the school record her details are as follows.
        </p>

        <p class="details-label">Details:</p>

        <table class="details-table">
            <tr>
                <td>Student Name</td>
                <td>{{ $data->stud_name }}</td>
            </tr>
            <tr>
                <td>Nationality</td>
                <td>{{ $data->nationality }}</td>
            </tr>
            <tr>
                <td>Religion</td>
                <td>{{ $data->religion }}</td>
            </tr>
            <tr>
                <td>Caste</td>
                <td>{{ $data->caste }}</td>
            </tr>
            <tr>
                <td>Sub Caste</td>
                <td>{{ $data->subcaste }}</td>
            </tr>
            <tr>
                <td>Date of Birth</td>
                <td>
                    {{ date_format(date_create($data->dob), 'd-m-Y') }}
                    ({{ $data->dob_words }})
                </td>
            </tr>
            <tr>
                <td>Previous School and Class</td>
                <td>{{ $data->prev_school_class }}</td>
            </tr>
            <tr>
                <td>Date of Admission</td>
                <td>{{ date_format(date_create($data->admission_date), 'd-m-Y') }}</td>
            </tr>
            <tr>
                <td>In Which Class and When</td>
                <td>{{ $data->class_when_learning }}</td>
            </tr>
            <tr>
                <td>Progress Report</td>
                <td>{{ $data->progress }}</td>
            </tr>
            <tr>
                <td>Behaviour</td>
                <td>{{ $data->behaviour }}</td>
            </tr>
            <tr>
                <td>Reason for Leaving</td>
                <td>{{ $data->leaving_reason }}</td>
            </tr>
            <tr>
                <td>Date of Leaving Certificate</td>
                <td>{{ \Carbon\Carbon::parse($data->lc_date_n_no)->format('d-m-Y') }}</td>
            </tr>
        </table>

        <p class="place">Place: Pune</p>

            <div class="signature">
        <span style="float: left; margin-left: 2%;">Clerk</span>
        <span style="float: right; margin-right: 8%;">Principal</span>
    </div>

    </div>{{-- end .certificate-content --}}

</div>{{-- end .page-wrapper --}}

</body>
</html>