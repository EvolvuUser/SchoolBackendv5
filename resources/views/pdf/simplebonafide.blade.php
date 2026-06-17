<style>
@page {
    margin-top:0;
    margin-bottom:0;
    margin-left:0;
    margin-right:0;
    /*padding: 0;*/
  }
    body{
    background-image: url('https://sms.evolvu.in/public/character_certificate.jpg');
    -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;
    font-family:Arial !important; 
    text-align:left;
    /*width: 300px;*/
  /*height: 300px;*/

}
 tr td{
	padding-top: 5px; 
	word-wrap:break-word;
	font-size:17px;
	font-family:Arial !important; 
    text-align:left;
 }
.statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        /*padding:3px;*/
    }

</style>
<html>

<div class="pdfdiv"> <!--Ends Here -->
<!--	<div style="width:100%;height:95%;margin: auto;text-align:center;border-style:groove;border:4px groove grey;">-->

 <?php
// $stud_image = $this->crud_model->get_student_profile_image($stud_id);
$student_image = '';
// $image_url	=	base_url().'uploads/student_image/'.$student_image;
?> 
		
	
	
	<div style="width:85%;margin-top:10%;margin-left:2%;text-align:center;display: inline-block">
     <table border="0"  class="table-responsive" style="width:96%;margin-left:4%;margin-top:10%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="1" cellspacing="10" >
             <tr>
                 <?php if ($student_image != '') { ?>
                <td style="font-style: italic;font-size:25px;text-align:right;">BONAFIDE CERTIFICATE  
<?php
    $image_url = m
    ?>
	<img src="<?php echo $image_url; ?>"  class="image_thumbnail studimg" width="50" height="50" style="margin-left:80px;"/>
	</td>
<?php } else { ?>
<td style="font-style: italic;font-size:20px;text-align:center;">BONAFIDE CERTIFICATE  
<?php } ?></td>

</tr>

    <tr> 
        <td>
            <!--<br>-->
            <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                <td class="cursive1" style="font-style: italic;font-size:18px;width: 90%; word-wrap:break-word;text-align:right;">Ref. No : <?php echo $data->academic_yr . '/ B.C/' . $data->sr_no; ?><br></td>
                 <!--<td style="font-style: italic;font-size:14px;width: 20%; word-wrap:break-word;"></td>-->
                
            </table>
        </td>
	</tr>
<tr>
                <td style="font-style: italic;font-size:18px;text-align:center;"><b>This is to certify that</td>
            </tr>
			<tr> 
                <td>
                    <!--<br>-->
                    <table class="table-responsive" style="width:109%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td class="cursive1" style="font-style: italic;font-size:18px;width: 15%; word-wrap:break-word;"><b>Master / Miss </td>
						<td style="font-style: italic;font-size:18px;width: auto;text-align:center;"><div class="statistics_line"><b><?php echo $data->stud_name ?></div></td>
						<td style="font-style: italic;font-size:18px;width: 5%;text-align:center;">,</td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:112%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border-collapse: collapse;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:18px;width: 4%;white-space: nowrap;" class="cursive"><b>son / daughter of Mr.</td>
                        <td nowrap style="font-style: italic;font-size:17px;width: 5%;text-align:center;"><div class="statistics_line"><b><?php echo $data->father_name ?></div></td>
                        <td style="font-style: italic;font-size:17px;width: 16%;"><b>is a bonafide student of St. Arnolds Central School</td>
                    </table>
                </td>
                
            </tr>
            <!-- <tr>-->
            <!--    <td>-->
            <!--        <table class="table-responsive" style="width:109%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border-collapse: collapse;" cellpadding="0" cellspacing="0">-->
            <!--            <td style="font-style: italic;font-size:14.5px;width: 4%;white-space: nowrap;" class="cursive"><b> St. Arnolds Central School</td>-->

            <!--        </table>-->
            <!--    </td>-->
                
            <!--</tr>-->
            <tr>
                <td>
                    <table class="table-responsive" style="width:105%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border-collapse: collapse;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:18px;width: 7%;padding-left:2%;white-space: nowrap;"><b>studying in our school in class</td>
						<td style="font-style: italic;font-size:18px;width: 5%;text-align:center;"><div class="statistics_line"><b><?php echo $data->class_division ?> </div></td>
						<td style="font-style: italic;font-size:18px;width:7%;text-align:center;"><b>for the academic year <?php echo $data->academic_yr ?>.</td>
                    </table>
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:105%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:18px;width: 1%;word-wrap:break-word;text-align: center;"><b>According to our record his / her date of birth is</td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:105%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;width:20%;text-align:center;font-size:18px;"><div class="statistics_line"><b>{{ \Carbon\Carbon::parse($data->dob)->format('d-m-Y') . ' (' . $data->dob_words . ')' }}</div></td>
                    </table>
                    
                </td>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:112%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:112%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                    </table>
                    
                </td>
                <br>
            </tr>
           
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                    </table>
                    
                </td>
            </tr>
            <?php $date_new = date_format(date_create($data->issue_date_bonafide), 'M d, Y'); ?>
            <tr>
                 <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:18px;width: 10%;padding-top: 10px;word-wrap:break-word;text-align: center;">Date: {{\Carbon\Carbon::parse($date_new)->format('M j, Y')}}</td>
                        <td style="font-style: italic; width:10%;text-align:center;font-size:15px;"></td>
                        <td style="font-style: italic;font-size:17px;width: 10%;padding-top: 10px;word-wrap:break-word;text-align: center;">Principal</td>
                    </table>
                    
                </td>
                </tr>
		</table>
	</div>   
    </div>
    <!--Ends Here -->
</html>
 --}}

{{-- @php
    $school = getSchoolDetails();
    $bgImage = getSimpleBonafideBgImage();

    $bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
    ? asset($bgImage['file_path'])
    : asset('health3_bg.jpg');
@endphp

<style>
@page {
    margin-top:0;
    margin-bottom:0;
    margin-left:0;
    margin-right:0;
}

body{
    background-image: url('{{ asset($bgImage['file_path']) }}');
    -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;

    object-fit: cover;
    background-repeat:no-repeat;

    font-family:Arial !important; 
    text-align:left;
}

tr td{
	padding-top: 3px; 
	word-wrap:break-word;
	font-size:20px;
	font-family:Arial !important; 
    text-align:left;
}

.statistics_line {
    width:100%;
    border-bottom:1px solid #000;
}
</style>

<html>

<div class="pdfdiv">

<?php
$student_image = '';
?> 
					
<br/>

<div style="width:80%;margin-top:23%;margin-left:5%;text-align:center;display: inline-block">

<table border="0" class="table-responsive"
style="width:95%;margin-left:5%;margin-right:auto;border-spacing:0px;background-color:white;margin-top:5%;"
cellpadding="1"
cellspacing="10">

<tr>
<?php if ($student_image != '') { ?>

<td style="font-style: italic;font-size:15px;text-align:right;">
    BONAFIDE CERTIFICATE  

<?php
$image_url = "";
?>

<img src="<?php echo $image_url; ?>"
class="image_thumbnail studimg"
width="50"
height="50"
style="margin-left:80px;"/>
</td>

<?php } else { ?>

<td style="font-style: italic;font-size:15px;text-align:center;">
    BONAFIDE CERTIFICATE
</td>

<?php } ?>
</tr>

<tr> 
<td>

<table class="table-responsive"
style="width:100%;margin-left:auto;margin-right:auto;border-spacing:0px;background-color:white;"
cellpadding="0"
cellspacing="0">

<td class="cursive1"
style="font-style: italic;font-size:16px;width:90%;word-wrap:break-word;text-align:right;">
    Ref. No :
    <?php echo $data->academic_yr . '/ B.C/' . $data->sr_no; ?>
    <br>
</td>

</table>

</td>
</tr>

<tr>
<td style="font-style: italic;font-size:15px;text-align:center;">
    <b>This is to certify that</b>
</td>
</tr>

<tr> 
<td>

<table class="table-responsive"
style="width:109%;margin-left:auto;margin-right:auto;border-spacing:0px;background-color:white;"
cellpadding="0"
cellspacing="0">

<td class="cursive1"
style="font-style: italic;font-size:14.5px;width:15%;word-wrap:break-word;">
    <b>Master / Miss</b>
</td>

<td style="font-style: italic;font-size:14.5px;width:auto;text-align:center;">
    <div class="statistics_line">
        <b><?php echo $data->stud_name ?></b>
    </div>
</td>

<td style="font-style: italic;font-size:14.5px;width:5%;text-align:center;">
    ,
</td>

</table>

</td>
</tr>

<tr>
<td>

<table class="table-responsive"
style="width:112%;margin-left:auto;margin-right:auto;border-spacing:0px;background-color:white;border-collapse:collapse;"
cellpadding="0"
cellspacing="0">

<td style="font-style: italic;font-size:14.5px;width:4%;white-space:nowrap;"
class="cursive">
    <b>son / daughter of Mr.</b>
</td>

<td nowrap
style="font-style: italic;font-size:14.5px;width:5%;text-align:center;">
    <div class="statistics_line">
        <b><?php echo $data->father_name ?></b>
    </div>
</td>

<td style="font-style: italic;font-size:14.5px;width:15%;">
    <b>is a bonafide student of St. Arnolds Central School</b>
</td>

</table>

</td>
</tr>

<tr>
<td>

<table class="table-responsive"
style="width:105%;margin-left:auto;margin-right:auto;border-spacing:0px;background-color:white;border-collapse:collapse;"
cellpadding="0"
cellspacing="0">

<td style="font-style: italic;font-size:14.5px;width:6%;padding-left:2%;white-space:nowrap;">
    <b>studying in our school in class</b>
</td>

<td style="font-style: italic;font-size:14.5px;width:5%;text-align:center;">
    <div class="statistics_line">
        <b><?php echo $data->class_division ?></b>
    </div>
</td>

<td style="font-style: italic;font-size:14.5px;width:7%;text-align:center;">
    <b>for the academic year <?php echo $data->academic_yr ?>.</b>
</td>

</table>

</td>
</tr>

<tr>
<td>

<table class="table-responsive"
style="width:105%;margin-left:auto;margin-right:auto;border-spacing:0px;background-color:white;"
cellpadding="0"
cellspacing="0">

<td style="font-style: italic;font-size:14.5px;width:1%;word-wrap:break-word;text-align:center;">
    <b>According to our record his / her date of birth is</b>
</td>

</table>

</td>
</tr>

<tr>
<td>

<table class="table-responsive"
style="width:105%;margin-left:auto;margin-right:auto;border-spacing:0px;background-color:white;"
cellpadding="0"
cellspacing="0">

<td style="font-style: italic;width:20%;text-align:center;font-size:14.5px;">
    <div class="statistics_line">
        <b>
        {{ \Carbon\Carbon::parse($data->dob)->format('d-m-Y') . ' (' . $data->dob_words . ')' }}
        </b>
    </div>
</td>

</table>

</td>
</tr>

<?php $date_new = date_format(date_create($data->issue_date_bonafide), 'M d, Y'); ?>

<tr>
<td>

<table class="table-responsive"
style="width:100%;margin-left:auto;margin-right:auto;border-spacing:0px;background-color:white;"
cellpadding="0"
cellspacing="0">

<td style="font-style: italic;font-size:15px;width:10%;padding-top:10px;word-wrap:break-word;text-align:center;">
    Date:
    {{ \Carbon\Carbon::parse($date_new)->format('M j, Y') }}
</td>

<td style="font-style: italic;width:10%;text-align:center;font-size:15px;"></td>

<td style="font-style: italic;font-size:15px;width:10%;padding-top:10px;word-wrap:break-word;text-align:center;">
    Principal
</td>

</table>

</td>
</tr>

</table>
</div>

</div>

</html> --}}


@php
    $bgImage = getSimpleBonafideBgImage();

    $bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
        ? asset($bgImage['file_path'])
        : asset('character_certificate.jpg');

    $pageType = $bgImage['page_type'] ?? 'A5 landscape';
@endphp

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Simple Bonafide Certificate</title>

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
                size: A5 landscape;

        @endswitch

        margin:0;
    }

    *{
        box-sizing:border-box;
    }

    html,
    body{
        margin:0;
        padding:0;
        width:100%;
        height:100%;
        font-family:Arial, sans-serif;

        background-image:url('{{ $bgPath }}');
        background-repeat:no-repeat;
        background-position:center center;
        background-size:100% 100%;
    }

    .pdfdiv{
        width:100%;
        height:100%;
    }

    .main-container{
        width:90%;
        margin:auto;
        padding-top:140px; /* Adjust according to background header */
    }

    table{
        width:100%;
        border-collapse:collapse;
    }

    td{
        padding:4px;
        font-size:15px;
        font-style:italic;
        vertical-align:middle;
    }

    .title{
        text-align:center;
        font-size:20px;
        font-weight:bold;
        font-style:italic;
        padding-bottom:10px;
    }

    .statistics_line{
        border-bottom:1px solid #000;
        text-align:center;
        font-weight:bold;
    }

</style>

</head>

<body>

<div class="pdfdiv">

    <div class="main-container">

        <table>

            <!-- TITLE -->
            <tr>
                <td>
                    <div class="title">
                        BONAFIDE CERTIFICATE
                    </div>
                </td>
            </tr>

            <!-- REF NO -->
            <tr>
                <td align="right">
                    Ref. No :
                    {{ $data->academic_yr }}/B.C/{{ $data->sr_no }}
                </td>
            </tr>

            <!-- HEADING -->
            <tr>
                <td align="center" style="padding-top:20px;">
                    <b>This is to certify that</b>
                </td>
            </tr>

            <!-- STUDENT NAME -->
            <tr>
                <td>

                    <table>

                        <tr>

                            <td width="18%">
                                Master / Miss
                            </td>

                            <td>
                                <div class="statistics_line">
                                    {{ $data->stud_name }}
                                </div>
                            </td>

                            <td width="2%">
                                ,
                            </td>

                        </tr>

                    </table>

                </td>
            </tr>

            <!-- FATHER NAME -->
            <tr>
                <td>

                    <table>

                        <tr>

                            <td width="22%">
                                son / daughter of Mr.
                            </td>

                            <td width="28%">
                                <div class="statistics_line">
                                    {{ $data->father_name }}
                                </div>
                            </td>

                            <td>
                                is a bonafide student of
                                St. Arnolds Central School
                            </td>

                        </tr>

                    </table>

                </td>
            </tr>

            <!-- CLASS -->
            <tr>
                <td>

                    <table>

                        <tr>

                            <td width="35%">
                                studying in our school in class
                            </td>

                            <td width="15%">
                                <div class="statistics_line">
                                    {{ $data->class_division }}
                                </div>
                            </td>

                            <td>
                                for the academic year {{ $data->academic_yr }}.
                            </td>

                        </tr>

                    </table>

                </td>
            </tr>

            <!-- DOB TEXT -->
            <tr>
                <td align="center" style="padding-top:25px;">
                    According to our record his / her date of birth is
                </td>
            </tr>

            <!-- DOB -->
            <tr>
                <td align="center">

                    <div class="statistics_line">

                        {{ \Carbon\Carbon::parse($data->dob)->format('d-m-Y') }}

                        ({{ $data->dob_words }})

                    </div>

                </td>
            </tr>

            <!-- PURPOSE -->
            @if(!empty($data->purpose))
            <tr>
                <td style="padding-top:25px;">
                    {{ $data->purpose }}
                </td>
            </tr>
            @endif

            <!-- FOOTER -->
            <tr>

                <td style="padding-top:20px;">

                    <table>

                        <tr>

                            <td align="left">

                                Date :
                                {{ \Carbon\Carbon::parse($data->issue_date_bonafide)->format('M j, Y') }}

                            </td>

                            <td align="right">

                                Principal

                            </td>

                        </tr>

                    </table>

                </td>

            </tr>

        </table>

    </div>

</div>

</body>
</html>