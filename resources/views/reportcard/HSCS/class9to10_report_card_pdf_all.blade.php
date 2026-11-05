<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

<style type = "text/css">
	@page {
		size: A4;
		margin-top:0;
		margin-bottom:0;
		margin-left:-2;
		margin-right:0;
		padding: 0;
	}
    body {
		background-image: url('{{ str_replace('\\', '/', public_path('reportcard/HSCS/hscs_pmy.jpg')) }}');
	   -webkit-background-size: cover;
		-moz-background-size: cover;
		-o-background-size: cover;
		background-size: cover;
		object-fit: cover;
		background-repeat:no-repeat;
	}
    .school{
        color:red;
        font-family: cursive !important;
    } 
    p{
        color:blue;
        font-family: Comic Sans MS;
    }
    h4{
        color:red;
        font-family: 'Comic Sans MS' !important;
    }
   
    .th{
        vertical-align:center;
        text-align:center;
        height:45px;
        border-bottom:1px solid grey;
        text-transform: uppercase;
    }
    .th1{
        vertical-align:center;
        text-align:center;
        height:45px;
        border-left:1px solid grey;
        border-bottom:1px solid grey;
        text-transform: uppercase;
        color:red;
    }
    .td{
        vertical-align:center;
        height:45px;
        border:1px solid grey;
		font-size:12px;
        text-transform: uppercase;
        padding-left:15%;
    }
 
    .lasttd{
        text-align:center;
        border:1px solid black;
    }
 .statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        padding:4px;
    }
    
    .pdfdiv {
   page: LC;
   page-break-after: always;
}
.pdfdiv:last-child{
    page-break-after: avoid;
    page-break-inside: avoid;
    margin-bottom: 0px;
}

    
</style> 
</head>
<body>
<?php
$exam_list = $reportCardData['exam_list'];
foreach ($reportCardData['students'] as $row1):
?>
    <div class="col-md-12 pdfdiv">
<div class="col-md-2"></div>
	<div class="col-md-8  table-responsive bgimg" style="text-align:center;">
		<table border="0" style="width:85%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 16%;">
			<tr>
				<td style="width:30%;text-align: left;font-size:14px;" >
					UDISE No. - 27251501213
				</td>
				<td style="width:40%;text-align: center;">
					<h4 >ACADEMIC SESSION <?php echo e($row1['academic_yr']);?></h4>
					<h3><font color="#000000">REPORT CARD</font></h3>
				</td>
				<td style="width:30%;text-align: left;font-size:14px;margin-left: 30px;" >
					Student ID - <?php echo e($row1['stud_id_no']);?>
				</td>
			</tr>
		</table>
		<br/>
		<table border="0"  class="table-responsive" style="width:90%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="1" cellspacing="10">
			<tr> 
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						<td style="font-size:16px;width: 25%; padding-top: 6px; padding-bottom:6px;	word-wrap:break-word;">Student's Name : </td>
						<td style="font-size:16px;text-align: center;width: auto"><div class="statistics_line"><?php echo e(trim($row1['first_name']." ".$row1['mid_name']." ".$row1['last_name']));?></div> </td>
						<td style="font-size:16px;width: 1%;"></td>
						<td style="font-size:16px;width: 15%;margin-left: 10px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">Roll No. : </td>
						<?php
						$roll_no="&nbsp;";
						if($row1['roll_no']<>null){
						    $roll_no=$row1['roll_no'];
						}
						?>
						<td style="font-size:18px;width: 8%;text-align: center;"><div class="statistics_line"> <?php echo $roll_no;?></div></td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 40%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Mother's / Father's / Gaurdian's Name : </td>
                        <td style="font-size:16px;padding:5px;width: 42%;text-align: center;"><div class="statistics_line"><?php echo e($row1['father_name'] ?? '');?></div></td>
						<td style="width: 1%;"></td>
						<td style="font-size:16px;margin-left: 10px;word-wrap:break-word;width:10%">GR No. : </td>
						<td style="font-size:16px;margin-left: 10px;word-wrap:break-word;width:auto;text-align: center;width:auto"><div class="statistics_line"> <?php echo e($row1['reg_no']);?></div></td>
                    </table>
                 </td>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 17%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Date of Birth : </td>
                        <td style="width:25%;text-align: center;"><div class="statistics_line"><?php echo $row1['dob'] ? e(date('d-m-Y', strtotime($row1['dob']))) : '';?></div></td>
						<td style="width: 5%;"></td>
                        <td style="font-size:16px;padding:5px;width: 20%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Class / Section : </td>
						<td style="width: auto;text-align: center;"><div class="statistics_line"><?php echo e($row1['class_name']." ".$row1['section_name']);?></div></td>
                    </table>
                    
                </td>
                
            </tr>
		</table>
     <br>
		<table style="width:90%;margin-left: 5%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			<tr>
				<td style="text-align:center;" cellpadding="0" cellspacing="0">
					<table border="1" style="width:100%;margin-left: auto;margin-right: 1%;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
            <tr><th class="col-md-12" colspan="{{ count($exam_list) + 1 }}" style="text-align:center;height:55px;">SCHOLASTIC AREAS</th></tr>
            <tr>
                <th style="text-align:center;height:50px;">SUBJECT</th>
                @foreach ($exam_list as $exam)
                    <td class="col-md-1" style="text-align:center;height:50px;">{{ strpos($exam->name, '(') > 0 ? substr($exam->name, 0, strpos($exam->name, '(')) : $exam->name }}<br/>({{ $reportCardData['highest_marks_by_exam'][$exam->exam_id] ?? '' }})</td>
                @endforeach
            </tr>
            @foreach ($reportCardData['scholastic_subjects'] as $subject)
                <tr>
                    <td style="text-align:center;height:40px;width:150px">{{ $subject->name }}</td>
                    @foreach ($exam_list as $exam)
                        <td class="col-md-1" style="text-align:center;height:40px;">{{ $reportCardData['marks_by_student'][$row1['student_id']][$exam->exam_id][$subject->sub_rc_master_id] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
				</td>
			</tr>
		</table>
        <br/>
		<!--table border="0"  class="table-responsive" style="width:80%;margin-left: 6%;margin-right: auto;margin-top: 66%;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0"-->
		<table style="width:90%;margin-left: 5%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
		<tr><td>
		<!--table border="1" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;" cellpadding="0" cellspacing="0"-->
		<table border="1" style="width:100%;margin-left: auto;margin-right: 1%;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			<tr>
				<td class="col-md-1" style="text-align:center;height:40px;width:126px">Class Teacher</td>
				<?php
				foreach($exam_list as $exam){
				?>
					<td  class="col-md-1"style="width:100px">&nbsp;</td>
				<?php
					}
				?>
				</tr>
				<tr>
					<td class="col-md-1" style="text-align:center;height:40px;">Parent</td>
					<?php
					foreach($exam_list as $exam){
					?>
						<td class="col-md-1" style="width:100px">&nbsp;</td>
					<?php
						}
					?>
				</tr>
				<tr>
					<td class="col-md-1" style="text-align:center;height:40px;">Principal</td>
					<?php
					foreach($exam_list as $exam){
					?>
						<td class="col-md-1" style="width:100px">&nbsp;</td>
					<?php
						}
					?>
				</tr>
		</table>
		</td></tr>
		</table>
    </div>
</div>
<?php endforeach; ?>
</body>
</html>
