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
		background-image: url('https://sms.evolvu.in/public/reportcard/SACS/primary_bg.jpg');
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
        height:40px;
        border-bottom:1px solid grey;
        text-transform: uppercase;
    }
    .th1{
        vertical-align:center;
        text-align:center;
        height:40px;
        border-left:1px solid grey;
        border-bottom:1px solid grey;
        text-transform: uppercase;
        color:red;
    }
    .td{
        vertical-align:center;
        height:35px;
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
    
</style> 
<br>
<?php
$student_info1 = array();
$student_info = get_student_info($student_id, $academic_yr);
foreach ($student_info as $row1):
	?>
<html>
    
    <head>
        <meta charset="utf-16" />
    </head>
    <body>
    <div class="col-md-12">
<div class="col-md-2"></div>
	<div class="col-md-8  table-responsive bgimg" style="text-align:center;">
		<table border="0" style="width:90%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 20%;">
			<tr>
				<td style="width:30%;text-align: left;font-size:14px;" >
					UDISE No. - 27251501213
				</td>
				<td style="width:40%;text-align: center;">
					<h4 >ACADEMIC SESSION <?php echo $academic_yr; ?></h4>
					<h3><font color="#000000">REPORT CARD</font></h3>
				</td>
				<td style="width:30%;text-align: left;font-size:14px;margin-left: 30px;" >
					Student ID - <?php echo $row1['stud_id_no']; ?>
				</td>
			</tr>
		</table>
		<br/>
		<table border="0"  class="table-responsive" style="width:90%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="1" cellspacing="10">
			<tr> 
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						<td style="font-size:16px;width: 25%; padding-top: 6px; padding-bottom:6px;	word-wrap:break-word;">Student's Name : </td>
						<td style="font-size:14px;text-align: center;width: auto"><div class="statistics_line"><?php echo $row1['first_name'] . ' ' . $row1['mid_name'] . ' ' . $row1['last_name']; ?></div> </td>
						<td style="font-size:16px;width: 1%;"></td>
						<td style="font-size:16px;width: 15%;margin-left: 10px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">Roll No. : </td>
						<?php
						$roll_no = '&nbsp;';
						if ($row1['roll_no'] <> null) {
							$roll_no = $row1['roll_no'];
						}
						?>
						<td style="font-size:16px;width: 8%;text-align: center;"><div class="statistics_line"> <?php echo $roll_no; ?></div></td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 38%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Mother's / Father's / Guardian's Name : </td>
                        <td style="font-size:14px;padding:5px;width: 42%;text-align: center;"><div class="statistics_line"><?php echo get_parent_name($row1['parent_id']); ?></div></td>
						<td style="width: 1%;"></td>
						<td style="font-size:16px;margin-left: 10px;padding-top: 8px; padding-bottom:8px;word-wrap:break-word;width:12%">GR No. : </td>
						<td style="font-size:16px;margin-left: 10px;word-wrap:break-word;width:auto;text-align: center;width:auto"><div class="statistics_line"> <?php echo $row1['reg_no']; ?></div></td>
                    </table>
                 </td>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 18%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Date of Birth : </td>
                        <td style="width:25%;text-align: center;"><div class="statistics_line"><?php echo date_format(date_create($row1['dob']), 'd-m-Y'); ?></div></td>
						<td style="width: 5%;"></td>
                        <td style="font-size:16px;padding:5px;width: 20%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Class / Section : </td>
						<td style="width: auto;text-align: center;"><div class="statistics_line"><?php echo get_class_name($row1['class_id']) . ' ' . get_section_name($row1['section_id']); ?></div></td>
                    </table>
                    
                </td>
                
            </tr>
		</table>
		<table style="width:90%;margin-left: 5%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			 <tr>
				 <td style="text-align:center;" cellpadding="0" cellspacing="0">
					<?php
					$exam_list = get_published_exams_class9n10($row1['class_id'], $row1['section_id'], $academic_yr);
					$count_of_exams = count($exam_list);
					?>
					
					<table border="1" style="width:100%;margin-left: auto;margin-right: 1%;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <tr>
							<th class="col-md-12" colspan="<?php echo ($count_of_exams + 1) ?>" style="text-align:center;height:45px;">SCHOLASTIC AREAS</th>
						</tr>		
						<tr>
							<th style="text-align:center;height:45px;">SUBJECT</th>
							<?php
							foreach ($exam_list as $exam) {
								${'total_marks_' . $exam->exam_id} = 0;
								${'highest_total_marks_' . $exam->exam_id} = 0;  // Lija 14-12-20
								$exam_name = (strpos($exam->name, '(') > 0 ? substr($exam->name, 0, strpos($exam->name, '(')) : $exam->name);
								?> 
										<td class="col-md-1" style="text-align:center;height:60px;"><?php echo $exam_name; ?></td>
							 <?php
							}
							?>
										
						</tr>

						<?php

						// $sub_list = get_scholastic_subject_alloted_to_class($row1['class_id'],$academic_yr);
						$sub_list = get_scholastic_subject_for_which_marks_are_alloted_to_student($row1['student_id']);  // Lija 30-10-25
						foreach ($sub_list as $sub_row) {
							?>
						<tr>
                             <td style="text-align:center;height:40px;"> 
								<?php
								echo $sub_row->name;
								?>
							</td>
							<?php
							foreach ($exam_list as $exam) {
								${'mark_obtained_array_' . $exam->exam_id} = array();
								${'highest_marks_array_' . $exam->exam_id} = array();
								$marks_obtained = 0;
								$highest_marks = 0;
								// $highest_total_marks=0;
								// $total_marks_obtained=0;

								${'marks_resultarray_' . $exam->exam_id} = get_marks($exam->exam_id, $row1['class_id'], $row1['section_id'], $sub_row->sub_rc_master_id, $student_id, $academic_yr);
								// var_dump (${'marks_resultarray_'.$exam->exam_id})."<br/>";
								if (isset(${'marks_resultarray_' . $exam->exam_id}[0])) {
									${'marks_obtained_json_' . $exam->exam_id} = ${'marks_resultarray_' . $exam->exam_id}[0]['reportcard_marks'];
									// echo (${'marks_obtained_json_'.$exam->exam_id}."<br/>");
									${'mark_obtained_array_' . $exam->exam_id} = json_decode(${'marks_obtained_json_' . $exam->exam_id}, true);
									// var_dump (${'mark_obtained_array_'.$exam->exam_id});
									${'highest_marks_json_' . $exam->exam_id} = ${'marks_resultarray_' . $exam->exam_id}[0]['reportcard_highest_marks'];
									${'highest_marks_array_' . $exam->exam_id} = json_decode(${'highest_marks_json_' . $exam->exam_id}, true);
									foreach (${'mark_obtained_array_' . $exam->exam_id} as $key => $value) {
										if ($value <> 'Ab') {
											if (is_numeric($value))
												$marks_obtained = $marks_obtained + $value;
										} else {
											$marks_obtained = 'Ab';
										}
										if ($marks_obtained <> 'Ab')  // Lija 13-10-24
											$highest_marks = $highest_marks + ${'highest_marks_array_' . $exam->exam_id}[$key];
									}
									${'total_marks_' . $exam->exam_id} = ${'total_marks_' . $exam->exam_id} + (float) $marks_obtained;
									${'highest_total_marks_' . $exam->exam_id} = ${'highest_total_marks_' . $exam->exam_id} + (float) $highest_marks;

									?>
											<td class="col-md-1" style="text-align:center;height:40px;"><?php echo $marks_obtained . '/' . $highest_marks; ?>
								<?php
								} else {
									?>
										<td class="col-md-1" style="text-align:center;height:40px;"></td>
									<?php
								}
							}

							?>
				
                        </tr>
                        <?php
						}
						?>
						<tr>
							<td style="text-align:center;height:45px;">TOTAL</td>
							<?php
							foreach ($exam_list as $exam) {
								?>
								<td class="col-md-1" style="text-align:center;height:40px;"><?php echo ${'total_marks_' . $exam->exam_id} . '/' . ${'highest_total_marks_' . $exam->exam_id}; // Lija 14-12-20 ?></td>
							<?php } ?>
						</tr>
						
				</table>
				</td>
			</tr>
		</table>
        <br><br>
		<!--table border="0"  class="table-responsive" style="width:80%;margin-left: 6%;margin-right: auto;margin-top: 66%;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0"-->
		<table style="width:90%;margin-left: 5%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
		<tr><td>
		<!--table border="1" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;" cellpadding="0" cellspacing="0"-->
		<table border="1" style="width:100%;margin-left: auto;margin-right: 1%;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			<tr>
				<td class="col-md-1" style="text-align:center;height:45px;width:126px">Class Teacher</td>
				<?php
				foreach ($exam_list as $exam) {
					?>
					<td  class="col-md-1"style="width:100px">&nbsp;</td>
				<?php
				}
				?>
				</tr>
				<tr>
					<td class="col-md-1" style="text-align:center;height:60px;">Parent</td>
					<?php
					foreach ($exam_list as $exam) {
						?>
						<td class="col-md-1" style="width:100px">&nbsp;</td>
					<?php
					}
					?>
				</tr>
				<tr>
					<td class="col-md-1" style="text-align:center;height:60px;">Principal</td>
					<?php
					foreach ($exam_list as $exam) {
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
</body>
</html> 
<?php endforeach; ?>

</head>
<body>