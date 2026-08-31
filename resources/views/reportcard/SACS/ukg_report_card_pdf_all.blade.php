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
        body{
    background-image:url('https://sms.evolvu.in/public/reportcard/SACS/UKG_bg.jpg');
   -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;
}
   
    .th{
        text-align:center;
        height:30px;
        border-bottom:1px solid grey;
        text-transform: uppercase;
        color:#000080;
    }
    .th1{
        text-align:center;
        height:30px;
        border-left:1px solid grey;
        border-bottom:1px solid grey;
        text-transform: uppercase;
        color:#000080;
    }
    .td1{
        vertical-align:center;
        text-align:center;
        height:25px;
        border-top:1px solid grey;
        border-left:1px solid grey;
        border-right:0px solid grey;
        border-bottom:1px solid grey;
        padding-top: 3px;
    }
    .td2{
        vertical-align:center;
        text-align:left;
        height:25px;
        border-top:1px solid grey;
        border-left:1px solid grey;
        border-right:0px solid grey;
        border-bottom:1px solid grey;
        text-transform: uppercase;
        padding-left:18px;
        background-color: silver;
        padding-top: 3px;
    }
    .td{
        vertical-align:center;
        height:25px;
        border-top:1px solid grey;
        border-left:1px solid grey;
        border-right:0px solid grey;
        border-bottom:1px solid grey;
        text-transform: uppercase;
        padding-left:15%;
        padding-top: 3px;
    }
     .statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        padding:4px;
    }
    
    .termsigntd{
    border-left:1px solid black;
    border-top:1px solid black;
    border-right:1px solid black;
    border-bottom:1px solid black;
    width:30%;
    padding-left:33%;
}
.bottomth{
    font-size:10px;
    text-align:center;
}
.signtd{
    font-size:12px;
    text-align:left;
    width:30%;
}
	.pdfdiv {
	   page-break-after: always;
	}
	.pdfdiv:last-child{
		page-break-after: avoid;
		page-break-inside: avoid;
		margin-bottom: 0px;
	}  
</style> 
<br>
<?php
$student_info = $reportCardData['students'] ?? array();
$term_list_master = $reportCardData['term_list'] ?? array();
$subjects_master = $reportCardData['subjects'] ?? array();
$exam_list_by_term_master = $reportCardData['exam_list_by_term'] ?? array();
$mark_headings_by_subject_master = $reportCardData['mark_headings_by_subject'] ?? array();
$marks_by_student_master = $reportCardData['marks_by_student'] ?? array();
$remarks_by_student_master = $reportCardData['remarks_by_student'] ?? array();
$attendance_by_student_master = $reportCardData['attendance_by_student'] ?? array();
$grade_scale_master = $reportCardData['grade_scale'] ?? array();
$reopen_date_master = $reportCardData['reopen_date'] ?? null;
foreach ($student_info as $row1):
    $class_name = $row1['class_name'] ?? '';
    $section_name = $row1['sec_name'] ?? '';
    $term_list = $term_list_master;
    $subjects = $subjects_master;
    $exam_list_by_term = $exam_list_by_term_master;
    $mark_headings_by_subject = $mark_headings_by_subject_master;
    $student_marks = $marks_by_student_master[$row1['student_id']] ?? [];
    $student_remarks = $remarks_by_student_master[$row1['student_id']] ?? [];
    $student_attendance = $attendance_by_student_master[$row1['student_id']] ?? [];
    $grade_scale = $grade_scale_master;
    ?>
<html>
    <head>
        <meta charset="utf-16" />
    </head>
    <body>
    <div class="col-md-12 pdfdiv">
<div class="col-md-2"></div>
	<div class="col-md-8  table-responsive bgimg" style="text-align:center;">
		<table class="table-responsive" style="width:85%;margin-left:auto;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 20%;" cellpadding="1" cellspacing="10">
			<tr>
			<td style="font-size:18px;border-left:2px solid black;border-right:2px solid black;border-bottom:2px solid black;border-top:2px solid black;">
				<table class="table-responsive col-md-12" border="0" width="100%" style="margin-top: 1%;margin-bottom: 1%;">
					 <tr> 
                         <td colspan="3" style="align:'left';padding-left:18px;" class="col-md-12" ><b>My Name : <u><?php echo $row1['first_name'] . ' ' . $row1['mid_name'] . ' ' . $row1['last_name']; ?></u></b>
					   </td>
					   
					 </tr>
					 <tr>
						<td class="col-md-3" style="text-align:'left';padding-left:18px;">Roll No. : <u><?php echo $row1['roll_no']; ?></u>
						</td>
						<td class="col-md-4" style="align:'left'">Std : <u><?php echo trim($class_name . ' ' . $section_name); ?>&nbsp;
					   </u>
					   </td>
						<td class="col-md-5" style="text-align:'left'">Academic Year : <u><?php echo $row1['academic_yr']; ?>
						 </u>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		<table class="table-responsive" style="width:85%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 1%;" cellpadding="0" cellspacing="0">
			 <tr>
				 <td style="vertical-align:center;border-top:2px solid black;border-left:2px solid black;border-right:2px solid black;border-bottom:2px solid black" cellpadding="0" cellspacing="0">
					<table class="" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						<tr>
						    <th class="col-md-3 th">SUBJECT</th>
							<?php
                            foreach ($term_list as $term) {
                                $exam_list = $exam_list_by_term[$term->term_id] ?? array();
                                ?>
								
								<?php
                                foreach ($exam_list as $exam):
                                    ${'total_marks_' . $exam->exam_id} = 0;
                                    ${'highest_total_marks_' . $exam->exam_id} = 0;
                                    ?> 
								<th class="col-md-1 th1"  width="";><?php echo $exam->name; ?></th>
								 <?php
                                endforeach;
                            }
                            ?>
						</tr>
						<tbody>
							<?php

                            $sub_list = $subjects;
                            foreach ($sub_list as $rw) {
                                ?>
							   <tr>
									<td  class="td2"><b>
										<?php echo $rw->name; ?></b>
									</td>
                                <?php  // $exam_list	=	$this->assessment_model->get_exams($row1['academic_yr']);
                                foreach ($term_list as $term) {
                                    $exam_list = $exam_list_by_term[$term->term_id] ?? array();
                                    foreach ($exam_list as $exam) {
                                        ?>
										
										<?php
                                        // $mark_obtained_array=array();
                                        ${'mark_obtained_array_' . $exam->exam_id} = array();
                                        $highest_mark_of_a_markheading = 0;  // Lija 30-09-22

                                        $marks_resultarray = isset($student_marks[$term->term_id][$rw->sub_rc_master_id][$exam->exam_id])
                                            ? array($student_marks[$term->term_id][$rw->sub_rc_master_id][$exam->exam_id])
                                            : array();
                                        if (isset($marks_resultarray[0])) {
                                            ${'mark_obtained_array_' . $exam->exam_id} = $marks_resultarray[0]['reportcard_marks'];
                                            ${'highest_marks_array_' . $exam->exam_id} = $marks_resultarray[0]['reportcard_highest_marks'];  // Lija 30-09-22

                                            if ($rw->subject_type == 'Scholastic') {  // Lija 30-09-22
                                                ${'total_marks_' . $exam->exam_id} = ${'total_marks_' . $exam->exam_id} + (float) $marks_resultarray[0]['total_marks'];
                                                ${'highest_total_marks_' . $exam->exam_id} = ${'highest_total_marks_' . $exam->exam_id} + (float) $marks_resultarray[0]['highest_total_marks'];
                                            }  // Lija 30-09-22
                                            // echo ${'total_marks_'.$exam['exam_id']}."<br/>";
                                            // echo ${'highest_total_marks_'.$exam['exam_id']}."<br/>";
                                        } else {
                                            // $marks_obtained_json ='';
                                            // $mark_obtained_array = array();
                                            $marks_headings = $mark_headings_by_subject[$rw->sub_rc_master_id] ?? array();
                                            ${'marks_obtained_json_' . $exam->exam_id} = '{';
                                            foreach ($marks_headings as $mrow) {
                                                ${'marks_obtained_json_' . $exam->exam_id} = ${'marks_obtained_json_' . $exam->exam_id} . '"' . $mrow->name . '":"",';
                                            }
                                            ${'marks_obtained_json_' . $exam->exam_id} = rtrim(${'marks_obtained_json_' . $exam->exam_id}, ',');  // Lija report card
                                            ${'marks_obtained_json_' . $exam->exam_id} = ${'marks_obtained_json_' . $exam->exam_id} . '}';
                                            ${'mark_obtained_array_' . $exam->exam_id} = json_decode(${'marks_obtained_json_' . $exam->exam_id}, true);
                                        }

                                        if (count(${'mark_obtained_array_' . $exam->exam_id}) == 1) {
                                            foreach (${'mark_obtained_array_' . $exam->exam_id} as $key => $value) {
                                                $marks_headings_name = $key;
                                                $marks_obtained = $value;

                                                $highest_mark_of_a_markheading = ${'highest_marks_array_' . $exam->exam_id}[$key] ?? '';  // 30-09-22

                                                if ($key == 'Term') {
                                                    ?>
													<td style="text-align:center;margin-left: 30%;" class="td1"> 
													<?php
                                                    if ($value == 'Ab') {
                                                        echo "<font color='red'>Ab</font>";
                                                    } else {
                                                        if ($value <> '') {
                                                            $value_per_100 = ($value * 100) / $highest_mark_of_a_markheading;  // Convert to out of 100 Lija 30-09-22
                                                            $grade = '';
                                                            foreach ($grade_scale[$rw->subject_type] ?? [] as $range) {
                                                                if ($value_per_100 >= $range['mark_from'] && $value_per_100 <= $range['mark_upto']) {
                                                                    $grade = $range['name'];
                                                                    break;
                                                                }
                                                            }
                                                            echo $grade . '<br/>';
                                                        } else {
                                                            echo '<br/>';
                                                        }
                                                        // echo ${'highest_total_marks_'.$exam['exam_id']};
                                                    }
                                                    ?>
													</td>
											  <?php } else { ?>
														<td class="td1">&nbsp;</td>
											  <?php }
                                            }
                                        } else { ?>  
											 <td class="td1">&nbsp;</td>
										<?php
                                        }
                                    }
                                }
                                ?>
                   
						</tr>		  
						<?php
                        $mark_headings = $mark_headings_by_subject[$rw->sub_rc_master_id] ?? array();
                        foreach ($mark_headings as $mh_row) {
                            $marks_headings_name = $mh_row->name;
                            if ($marks_headings_name != 'Term') {
                                ?>
							<tr>
									<td class="td"> <?php echo $marks_headings_name; ?></td>
							<?php
                            foreach ($term_list as $term) {
                                $exam_list = $exam_list_by_term[$term->term_id] ?? array();
                                foreach ($exam_list as $exam) {
                                    if (isset(${'mark_obtained_array_' . $exam->exam_id}[$marks_headings_name]))
                                        $marks_obtained = ${'mark_obtained_array_' . $exam->exam_id}[$marks_headings_name];
                                    else
                                        $marks_obtained = '';

                                    $highest_mark_of_a_markheading = ${'highest_marks_array_' . $exam->exam_id}[$marks_headings_name] ?? 0;  // 30-09-22
                                    ?>
								
								
									<td style="text-align:center;margin-left: 30%;" class="td1">
									<?php
                                    if ($marks_obtained == 'Ab') {
                                        echo "<font color='red'>Ab</font>";
                                    } else {
                                        // echo $marks_obtained."<br>";
                                        if ($marks_obtained <> '') {
                                            $value_per_100 = ($marks_obtained * 100) / $highest_mark_of_a_markheading;  // Convert to out of 100 Lija 30-09-22
                                            $grade = '';
                                            foreach ($grade_scale[$rw->subject_type] ?? [] as $range) {
                                                if ($value_per_100 >= $range['mark_from'] && $value_per_100 <= $range['mark_upto']) {
                                                    $grade = $range['name'];
                                                    break;
                                                }
                                            }
                                        } else {
                                            $grade = '<br>';
                                        }
                                        echo $grade;
                                    }
                                    ?>
									</td>
										
										
								<?php
                                }
                            }
                            ?>
										
							   </tr>

						<?php
                            }
                        }
                        ?>
                  
                <?php
                            }  // sub list ends here
                            ?>
							
							
							<tr>
								<td class="td2"><b>GRADE</b></td>
                                <?php
                                foreach ($term_list as $term) {
                                    $exam_list = $exam_list_by_term[$term->term_id] ?? array();
                                    foreach ($exam_list as $exam) {
                                        if (${'highest_total_marks_' . $exam->exam_id} <> 0) {
                                            ?>
										<td class="td1">
										<?php
                                        // echo ${'total_marks_'.$exam['exam_id']}."/".${'highest_total_marks_'.$exam['exam_id']}."<br>";
                                        $percent = round((${'total_marks_' . $exam->exam_id} * 100) / ${'highest_total_marks_' . $exam->exam_id});
                                        $final_grade = '';
                                        foreach ($grade_scale['Scholastic'] ?? [] as $range) {
                                            if ($percent >= $range['mark_from'] && $percent <= $range['mark_upto']) {
                                                $final_grade = $range['name'];
                                                break;
                                            }
                                        }
                                        echo $final_grade;
                                        ?>
										</td>
									<?php
                                        } else {
                                    ?>
										<td class="td1"></td>
                                <?php
                                        }
                                    }
                                }
                                ?>
							</tr>
							<tr>
								<td class="td2"><b>ATTENDANCE</b></td>
								<?php
                                foreach ($term_list as $term) {
                                    $exam_list = $exam_list_by_term[$term->term_id] ?? array();
                                    for ($i = 0; $i < count($exam_list); $i++) {
                                        ?>
											<td class="td1">
											<?php
                                            $attendance = $student_attendance[$term->term_id] ?? ['present' => '', 'working' => ''];
                                            if (($attendance['present'] ?? '') !== '') {
                                                echo ($attendance['present'] ?? '') . '/' . ($attendance['working'] ?? '');
                                            }
                                            ?>
											</td>
										<?php
                                    }
                                }
                                ?>
							</tr>
							<tr>
								<td class="td2"><b>Class Teacher's Remark</b></td>
								<?php
                                foreach ($term_list as $term) {
                                    $exam_list = $exam_list_by_term[$term->term_id] ?? array();
                                    for ($i = 0; $i < count($exam_list); $i++) {
                                        ?>
										<td class="td1">
											<?php echo $student_remarks[$term->term_id]['remark'] ?? ''; ?>
										</td>
										<?php }
                                } ?>
							</tr>
					</tbody>
				</table>
				</td>
			</tr>
		</table>
		<table class="table-responsive" border="0" style="width:85%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 1%;" cellpadding="0" cellspacing="0">
			 <tr>
				 <td style="vertical-align:center;border-top:2px solid black;border-left:2px solid black;border-right:2px solid black;border-bottom:2px solid black" cellpadding="0" cellspacing="0">
					<?php
                    if (count($term_list) > 1) {
                        ?>
					<table class="table-responsive" style="width:80%;margin-left:4%;margin-right: 4%;border-spacing: 0px;background-color:white;" cellpadding="1" cellspacing="10">
						<tr>
							<td>
								<table class="table-responsive" style="width:100%;margin-top:2%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
									<td style="font-size:12px;text-align:left;white-space:nowrap;width:10%"> <b>Promoted To : </b></td>
									<?php
                                    $promote_to = '';
                                    if (isset($term_list[1]->term_id))
                                        $promote_to = $student_remarks[$term_list[1]->term_id]['promot'] ?? '';

                                    ?>
									<td style="width:30%;font-size:12px;"><div class="statistics_line"><?php echo $promote_to; ?></div> </td>
									<td style="font-size:12px;text-align:left;white-space:nowrap;width:15%;"> &nbsp;&nbsp;<b>Date Of Reopening :</b></td>
									<td style="width:30%;font-size:12px;">
										<div class="statistics_line">
											<?php
                                            $reopen_date = $reopen_date_master;
                                            if ($reopen_date <> NULL && $reopen_date <> '0000-00-00')
                                                echo date_format(date_create($reopen_date), 'd-m-Y');
                                            ?>
										</div></td>
								</table>

							</td>

						</tr>
					</table>
                    <?php } ?> 
                    <table class="table-responsive" style="width:95%;margin-top:1%;margin-left:4%;margin-right: 4%;border-spacing: 0px;background-color:white;" cellpadding="1" cellspacing="10">
						<tr>
							<td style="width:33%;">
								<table class="table-responsive" width="100%" cellspacing="0">
									<tr>
										<th style="" class="bottomth" width="15%" ><br/>Term 1 <br/></th>
										<td style="" class="termsigntd"></td>
									</tr>
									 <tr>
										<th style="" class="bottomth" width="15%"><br/> Term 2<br/></th>
										<td style="" class="termsigntd"></td>
									</tr>
								</table>
							</td>
							<td style="width:33%;">
								<table class="table-responsive" width="100%" cellspacing="0">
									<tr>
										<th style="" class="bottomth" width="15%" ><br/>Term 1 <br/></th>
										<td style="" class="termsigntd"></td>
									</tr>
									 <tr>
										<th style="" class="bottomth" width="15%"><br/> Term 2<br/></th>
										<td style="" class="termsigntd"></td>
									</tr>
								</table>
							</td>
							<td style="width:33%;">
								<table class="table-responsive" width="100%" cellspacing="0">
									<tr>
										<th style="" class="bottomth" width="15%" ><br/>Term 1 <br/></th>
										<td style="" class="termsigntd"></td>
									</tr>
									 <tr>
										<th style="" class="bottomth" width="15%"><br/> Term 2<br/></th>
										<td style="" class="termsigntd"></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr> 
							<td style="width:33%;">
								<table class="table-responsive" width="100%" cellspacing="0">
									<tr>
										<th style="" class="bottomth" width="15%" ></th>
										<td style="" class="signtd"><b>Class Teacher's Sign.</b></td>
									</tr>
								</table>
							</td>
							<td style="width:33%;">
								<table class="table-responsive" width="100%" cellspacing="0">
									<tr>
										<th style="" class="bottomth" width="15%" ></th>
										<td style="" class="signtd"><b>Parent's Sign.</b></td>
									</tr>
								</table>
							</td>
							<td style="width:33%;">
								<table class="table-responsive" width="100%" cellspacing="0">
									<tr>
										<th style="" class="bottomth" width="15%" ></th>
										<td style="" class="signtd"><b>Principal's Sign.</b></td>
									</tr>
								</table>
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
<?php endforeach; ?>
</head>
<body>
