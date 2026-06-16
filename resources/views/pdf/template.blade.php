{{-- <style>
@page {
    size: A4;
    margin-top:0;
    margin-bottom:0;
    margin-left:1;
    margin-right:1;
    padding: 0;
  }
    body{
    background-image: url('https://sms.evolvu.in/public/bonafide.jpg');
    -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;

}
 tr td{
	padding-top: 3px; 
	padding-bottom:3px;
	word-wrap:break-word;
	font-size:14px;
 }
 
 
</style>
<html>
<div class="pdfdiv"> <!--Ends Here -->
    <center>
<!--	<div style="width:100%;height:95%;margin: auto;text-align:center;border-style:groove;border:4px groove grey;">-->


					
	<br/><center>
    <div style="width:95%;margin-top:20%;display: inline-block">
        	<img src=""  class="image_thumbnail studimg" width="100" height="100" style="padding-left: 70%;"/>
            <br><br><br><br><br><br>
<center><p style="font-size:20px"><b>BONAFIDE CERTIFICATE</b></p></center>
<center><p style="font-size:20px"><b>To whomsoever it may concern</b></p></center>

<p style="font-size:15px;"> <span style="margin-left:10px;"><b> Ref. No : {{$data->sr_no}}</b></span></p>

<!--<p style="font-size:15px"> <b></span></b></p>-->

<p style="font-size:15px"><span style="margin-left:20px;">This is to certify that Mst/Miss.<b>{{$data->stud_name}},</b> son /daughter of <b>Mr.{{$data->father_name}}</b> is/was studying in our school in class- {{$data->class_division}} , for the academic year {{$data->academic_yr}}. </span></p>
<p style="font-size:15px"><span style="">According to our record her date of birth is {{ \Carbon\Carbon::parse($data->dob)->format('d-m-Y') }} ({{ $data->dob_words }}). </span></p>
<p style="font-size:15px"><span style=""> {{$data->purpose}}.</span></p>
<br>
<p style="font-size:18px"><span style="">Date : {{\Carbon\Carbon::parse($data->issue_date_bonafide)->format('M j, Y')}}<span style="margin-left:50%"> Fr. Sunil Memezes </span></p>

</div>
</div>
</html> --}}


@php
    $school = getSchoolDetails();
    $bgImage = getBonafideBgImage();

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

    @page {
        size: A4;
        margin-top: 0;
        margin-bottom: 0;
        margin-left: 1;
        margin-right: 1;
        padding: 0;
    }

    body{
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;

        /* background-image: url('{{ asset($bgImage['file_path']) }}'); */
        background-image: url('{{ $bgImage['file_path'] }}');
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
    }

    .pdfdiv{
        width: 100%;
        min-height: 1000px;
    }

    tr td{
        padding-top: 3px;
        padding-bottom: 3px;
        word-wrap: break-word;
        font-size: 14px;
    }

    .main-container{
        width: 90%;
        margin: auto;
        margin-top: 15%;
    }

    .header-table{
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .header-table td{
        vertical-align: middle;
    }

    .logo-section{
        width: 20%;
        text-align: center;
    }

    .logo-section img{
        width: 100px;
        height: 100px;
        object-fit: contain;
    }

    .school-name{
        font-size: 28px;
        font-weight: bold;
        color: #c00000;
        text-align: center;
    }

    .school-details{
        font-size: 14px;
        text-align: center;
        line-height: 22px;
    }

    .dotted{
        border: 1px dotted #000;
        margin-top: 10px;
    }

    .title{
        text-align: center;
        font-size: 22px;
        font-weight: bold;
        margin-top: 40px;
    }

    .sub-title{
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        margin-top: 10px;
    }

    .content{
        font-size: 16px;
        line-height: 30px;
        margin-top: 20px;
        text-align: justify;
    }

    .footer{
        margin-top: 50px;
        width: 100%;
    }

    .footer-table{
        width: 100%;
    }

    .footer-table td{
        font-size: 16px;
    }

    .principal{
        text-align: right;
        padding-right: 50px;
    }

</style>

</head>

<body>

<div class="pdfdiv">

    <div class="main-container">

        <!-- HEADER -->
        <table class="header-table">

            <tr>

                <td class="logo-section">

                    @if(!empty($school->school_logo))
                        <img src="{{ asset($school->school_logo) }}" alt="School Logo">
                    @endif

                </td>

                <td>

                    <div class="school-name">
                        {{ $school->school_name ?? '' }}
                    </div>

                    <div class="school-details">

                        {{ $school->address ?? '' }}

                        <br>

                        @if(!empty($school->mobile))
                            Mobile: {{ $school->mobile }}
                        @endif

                        @if(!empty($school->email))
                            | Email: {{ $school->email }}
                        @endif

                    </div>

                    {{-- <hr class="dotted"> --}}

                </td>

            </tr>

        </table>

        <!-- TITLE -->
        <div class="title">
            BONAFIDE CERTIFICATE
        </div>

        <div class="sub-title">
            To whomsoever it may concern
        </div>

        <!-- REF -->
        <p class="content">
            <b>Ref. No : {{ $data->sr_no }}</b>
        </p>

        <!-- MAIN CONTENT -->
        <p class="content">

            This is to certify that Mst / Miss.
            <b>{{ $data->stud_name }}</b>,
            son / daughter of
            <b>Mr. {{ $data->father_name }}</b>
            is / was studying in our school in class
            <b>{{ $data->class_division }}</b>
            for the academic year
            <b>{{ $data->academic_yr }}</b>.

        </p>

        <p class="content">

            According to our record his / her date of birth is

            <b>
                {{ \Carbon\Carbon::parse($data->dob)->format('d-m-Y') }}
                ({{ $data->dob_words }})
            </b>.

        </p>

        <p class="content">
            {{ $data->purpose }}
        </p>

        <!-- FOOTER -->
        <div class="footer">

            <table class="footer-table">

                <tr>

                    <td>
                        Date :
                        {{ \Carbon\Carbon::parse($data->issue_date_bonafide)->format('M j, Y') }}
                    </td>

                    <td class="principal">
                        Principal
                    </td>

                </tr>

            </table>

        </div>

    </div>

</div>

</body>
</html>

