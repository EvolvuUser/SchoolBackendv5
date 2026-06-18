@php
$school = getSchoolDetails();
$bgImage = getPercentageBgImage();

$bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
    ? asset($bgImage['file_path'])
    : asset('health3_bg.jpg');

$pageType = $bgImage['page_type'] ?? 'A4 portrait';

$contentMarginTop = match ($pageType) {
    'A4 portrait'      => '25%',
    'A4 landscape'     => '18%',
    'A5 portrait'      => '14%',
    'A5 landscape'     => '18%',
    'Letter portrait'  => '25%',
    'Letter landscape' => '18%',
    default            => '15%',
};
@endphp

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

html,
body{
    margin:0;
    padding:0;
    width:100%;
    height:100%;
}

body{
    background-image:url('{{ $bgPath }}');
    background-repeat:no-repeat;
    background-position:center center;

    /* Prevent cutting in landscape */
    background-size:100% 100%;

    font-family:Arial !important;
}

tr td{
    padding-top:3px;
    padding-bottom:3px;
    word-wrap:break-word;
    font-size:14px;
    font-family:Arial !important;
}

tr.separated td{
    border-top:1px solid black;
}

.statistics_line{
    width:100%;
    border-bottom:1px solid #000;
}

.container{
    display:grid;
    grid-gap:1rem;
    grid-template:'date content';
}

/* Main content area */
.pdfdiv{
    width:100%;
    margin-top:{{ $contentMarginTop }};
}
</style>

<html>
<div class="pdfdiv"> <!--Ends Here -->
    <div style="width:95%;display: inline-block">
      
        <?php
        // echo $student_image;

        //  if($student_image =''){
        // $image_url	=	base_url().'uploads/student_image/'.$student_image;
        // echo $image_url;
        ?>
    
    <!--<img src="url('http://103.159.85.174/SchoolBackendv5/public/bonafide.jpg')"  class="image_thumbnail studimg" width="100" height="100" style="margin-left:80%;margin-top:12%;"/>-->
<?php
?>
        	
<center><p style="font-size:18px"><b>PERCENTAGE CERTIFICATE</b></p></center>
<!--<center><p style="font-size:16px"><b>To whomsoever it may concern</b></p></center>-->

<!--<p style="font-size:15px;"> <span style="margin-left:80%;"><b> Ref. No : </b></span></p>-->
<p style="font-size:16px"><span style="margin-left:80%"> Ref. No : <?php echo $data->sr_no; ?> </span></p>
<!--<p style="font-size:15px"> <b></span></b></p>-->
<?php $class = DB::table('class')->where('class_id', $data->class_id)->first();

if ($class->name == '10') {
    $class_wrd = 'Tenth';
}
if ($class->name == '11') {
    $class_wrd = 'Eleventh';
}
if ($class->name == '12') {
    $class_wrd = 'Twelveth';
} ?>
    <!-- <div class="container"> -->
    <table width="100%" border="0" style="border-collapse: collapse;">
        <tr>
            <td width="10%"></td>
        	<td align="left" width="90%" style="font-size:16px;">This is to certify that Master / Miss.<b><?php echo $data->stud_name; ?></b> of class <?php echo $class->name . 'th'; ?> (<?php echo $class_wrd; ?>) appeared for CBSE Board Examination of <?php echo $data->academic_yr; ?> bearing Roll No. <?php echo $data->rollno; ?>.</td>
        </tr>
        <tr>
            <td width="10%"></td>
            <td align="left" width="90%" style="font-size:16px;">He / she has secured marks as below:</td>
        </tr>
    </table>
    <br>
   <table width="80%" border="0"
       style="border-collapse:collapse; margin-left:10%;">

    <!-- Header -->
    <tr>
        <td width="50%"
            style="border:1px solid black;padding:8px;font-size:16px;">
            SUBJECT
        </td>

        <td width="25%"
            align="center"
            style="border:1px solid black;padding:8px;font-size:16px;">
            MARKS OBTAINED
        </td>

        <td width="25%"
            align="center"
            style="border:1px solid black;padding:8px;font-size:16px;">
            TOTAL MARKS
        </td>
    </tr>

<?php
if ($class->name == '10') {

    $subject = DB::table('class10_subject_master')->get();
    $subject_count = 0;

    foreach ($subject as $row):

        $marks = DB::table('percentage_marks_certificate')
            ->where('sr_no', $data->sr_no)
            ->where('c_sm_id', $row->c_sm_id)
            ->value('marks');

        if ($marks !== null):

            $subject_count++;
?>
            <tr>
                <td style="border:1px solid black;padding:8px;">
                    <?php echo $row->name; ?>
                </td>

                <td align="center"
                    style="border:1px solid black;padding:8px;">
                    <?php echo $marks; ?>
                </td>

                <td align="center"
                    style="border:1px solid black;padding:8px;">
                    100
                </td>
            </tr>

<?php
        endif;
    endforeach;

    $sub_total = $subject_count * 100;

} else {

    $subject = DB::table('subjects_higher_secondary_studentwise as shs')
        ->join('subject_group as grp', 'shs.sub_group_id', '=', 'grp.sub_group_id')
        ->join('subject_group_details as grpd', 'grp.sub_group_id', '=', 'grpd.sub_group_id')
        ->join('subject_master as shsm', 'grpd.sm_hsc_id', '=', 'shsm.sm_id')
        ->join('subject_master as shs_op', 'shs.opt_subject_id', '=', 'shs_op.sm_id')
        ->join('stream', 'grp.stream_id', '=', 'stream.stream_id')
        ->select(
            'shs.*',
            'grp.sub_group_name',
            'grpd.sm_hsc_id',
            'shsm.name as subject_name',
            'stream.stream_name',
            'shs_op.name as optional_sub_name'
        )
        ->where('shs.student_id', $data->stud_id)
        ->get();

    $sub_total = 100 * (count($subject) + 1);

    foreach ($subject as $row):

        $marks = DB::table('percentage_marks_certificate')
            ->where('sr_no', $data->sr_no)
            ->where('c_sm_id', $row->sm_hsc_id)
            ->value('marks');
?>

        <tr>
            <td style="border:1px solid black;padding:8px;">
                <?php echo $row->subject_name; ?>
            </td>

            <td align="center"
                style="border:1px solid black;padding:8px;">
                <?php echo $marks; ?>
            </td>

            <td align="center"
                style="border:1px solid black;padding:8px;">
                100
            </td>
        </tr>

<?php endforeach; ?>

    <tr>
        <td style="border:1px solid black;padding:8px;">
            <?php echo $subject[0]->optional_sub_name; ?>
        </td>

        <td align="center"
            style="border:1px solid black;padding:8px;">
            <?php
            echo DB::table('percentage_marks_certificate')
                ->where('sr_no', $data->sr_no)
                ->where('c_sm_id', $subject[0]->opt_subject_id)
                ->value('marks');
            ?>
        </td>

        <td align="center"
            style="border:1px solid black;padding:8px;">
            100
        </td>
    </tr>

<?php } ?>

    <!-- TOTAL -->
    <tr>
        <td style="border:1px solid black;padding:8px;font-weight:bold;">
            TOTAL
        </td>

        <td align="center"
            style="border:1px solid black;padding:8px;font-weight:bold;">
            <?php echo $data->total; ?>
        </td>

        <td align="center"
            style="border:1px solid black;padding:8px;font-weight:bold;">
            <?php echo $sub_total; ?>
        </td>
    </tr>

    <!-- PERCENTAGE -->
    <tr>
        <td colspan="2"
            style="border:1px solid black;padding:8px;font-weight:bold;">
            PERCENTAGE
        </td>

        <td align="center"
            style="border:1px solid black;padding:8px;font-weight:bold;">
            <?php echo $data->percentage . ' %'; ?>
        </td>
    </tr>

</table>
    <br>
    <br>
    <?php

    function numToWords($number)
    {
        $units = array('', 'One', 'Two', 'Three', 'Four',
            'Five', 'Six', 'Seven', 'Eight', 'Nine');

        $tens = array('', 'Ten', 'Twenty', 'Thirty', 'Forty',
            'Fifty', 'Sixty', 'Seventy', 'Eighty',
            'Ninety');

        $special = array('Eleven', 'Twelve', 'Thirteen',
            'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen');

        $words = '';
        if ($number < 10) {
            $words .= $units[$number];
        } elseif ($number < 20) {
            $words .= $special[$number - 11];
        } else {
            $words .= $tens[(int) ($number / 10)] . ' '
                . $units[$number % 10];
        }

        return $words;
    }

    $per = explode('.', $data->percentage);
    $per_word = numToWords($per[0]);
    if ($per[1] != '00') {
        $per_word1 = numToWords($per[1]);
        $percentage_wrds = $per_word . ' Point ' . $per_word1;
    } else {
        $percentage_wrds = $per_word;
    }

    ?>
    <table width="90%" border="0" style="border-collapse: collapse;">
        <tr>
            <td width="10%"></td>
            <td align="left" width="80%" style="font-size:16px;">Therefore his / her percentage in <?php echo $class->name . 'th'; ?> (<?php echo $class_wrd; ?>) CBSE Board Examination <?php echo $data->academic_yr; ?> is <?php echo $data->percentage . ' % (' . $percentage_wrds . ')'; ?></td>
        </tr>
    </table>
<br>
<br>
<br>
<br>
<p style="font-size:16px"><span style="margin-left:10%;">Date : <?php echo \Carbon\Carbon::parse($data->certi_issue_date)->format('d-m-Y'); ?><span style="margin-left:50%"> Principal </span></p>
</div>
</div>
</html>
