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
    margin-left:0;
    margin-right:0;
    padding: 0;
  }
    body{
    background-image: url('https://sms.evolvu.in/public/reportcard/SACS/nursery_bg.jpg');
    -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;

}
 
    .th{
        vertical-align:middle;
        text-align:center;
        height:25px;
        border-right:1px solid grey;
        border-left:1px solid grey;
        border-bottom:1px solid grey;
        text-transform: uppercase;
        color:red;
		//width: 25%; 
    }
    .th1{
        vertical-align:middle;
        text-align:center;
        height:28px;
        border-right:1px solid grey;
        border-left:1px solid grey;
        border-bottom:1px solid grey;
        text-transform: uppercase;
        color:red;
		//width: 15%;
    }

    .td2{
        vertical-align:middle;
        text-align:left;
        height:20px;
        border-top:1px solid grey;
        border-left:1px solid grey;
        border-right:1px solid grey;
        border-bottom:1px solid grey;
        color:#0000A0;
		font-size:12px;
     }
     .td4{
        vertical-align:middle;
        text-align:left;
        height:20px;
        border-top:1px solid grey;
        border-left:1px solid grey;
        border-right:1px solid grey;
        border-bottom:1px solid grey;
        text-transform: uppercase;
        color:#0000A0;
        background-color:silver;
        padding-left:30px;
        font-size:16px;
    }
   
    .imagetd{
        vertical-align:middle;
        border-top:1px solid grey;
        border-left:1px solid grey;
        border-right:1px solid grey;
        border-bottom:1px solid grey;
        color:#0000A0;
        font-size:18px;
        padding-top: 8px;
        padding-left: 4px;
    }
     .emptytd{
        vertical-align:middle;
        text-align:left;
        height:20px;
        border-top:1px solid grey;
        border-left:1px solid grey;
        border-right:1px solid grey;
        border-bottom:1px solid grey;
        padding-left:25px;
    }
     .signtd{
        vertical-align:middle;
        text-align:left;
        height:20px;
        border-top:1px solid grey;
        border-left:1px solid grey;
        border-right:1px solid grey;
        border-bottom:1px solid grey;
        color:#0000A0;
        background-color:silver;
        padding-left:30px;
        font-size:18px;
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
    ?>
<br>
<html>
    <body>
    <div class="col-md-12 pdfdiv" style="align:center;">
<div class="col-md-2"></div>
 <div class="col-md-8 table-responsive bgimg" style="text-align:center;">

     <table class="table-responsive" style="width:70%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 24%;" cellpadding="7%">
		<tr>
			<td style="font-size:18px;border-left:2px solid black;border-right:2px solid black;border-bottom:2px solid black;border-top:2px solid black;">
				<table class="table-responsive col-md-12" border="0" width="100%" style="margin-top: 1%;margin-bottom:1%;" cellpadding="3">
					 <tr> 
                         <td colspan="3" style="align:'left;'" class="col-md-12" ><b>My Name : <u><?php echo $row1['first_name'] . ' ' . $row1['mid_name'] . ' ' . $row1['last_name']; ?></u></b>
					   </td>
					   
					 </tr>
					 <tr>
						<td class="col-md-3" style="text-align:'left'">Roll No. : <u><?php echo $row1['roll_no']; ?></u>
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
     <br>
     <table class="table-responsive" style="width:70%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;margin-top:-1%;" cellpadding="0" cellspacing="0">
         <tr>
             <td style="vertical-align:center;border-top:2px solid black;border-left:2px solid black;border-right:2px solid black;border-bottom:2px solid black" cellpadding="0" cellspacing="0">
                 <table class="table-responsive" style="border-spacing: 0px;background-color:white;" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        
						<th class="th"> </th>
						<?php foreach ($term_list as $term): ?> 
							<th class="col-md-2 th1">
							    <?php
                                if ($row1['academic_yr'] == '2021-2022')
                                    echo 'Final Term';
                                else
                                    echo $term->name;
                                ?>
                            </th>
						<?php
    endforeach;
    ?>
					</tr>
                    <tbody>
                    <?php
                    foreach ($subjects as $rw) {
                        ?>
                        <tr>
							<td  class="" style="text-align:left;height:25px;border-top:1px solid grey;border-left:1px solid grey;border-right:1px solid grey;border-bottom:1px solid grey;text-transform: uppercase;color:#0000A0;background-color:silver;font-size:18px;padding-left:30px;"><b> <?php echo $rw->name; ?></b>
							</td>
                        <?php
                        foreach ($term_list as $term) {
                            $term_exams = $exam_list_by_term[$term->term_id] ?? array();
                            if (count($term_exams) > 0) {
                                foreach ($term_exams as $exam) {
                                    $marks_resultarray = isset($student_marks[$term->term_id][$rw->sub_rc_master_id][$exam->exam_id])
                                        ? array($student_marks[$term->term_id][$rw->sub_rc_master_id][$exam->exam_id])
                                        : array();
                                    if (count($marks_resultarray) > 0) {
                                        $mark_obtained_array = $marks_resultarray[0]['reportcard_marks'];
                                    } else {
                                        $mark_obtained_array = array();
                                    }
                                    if (count($mark_obtained_array) == 1) {
                                        foreach ($mark_obtained_array as $key => $value) {
                                            if ($key == 'Term') { ?>
												<td class="imagetd"> 
													<?php for ($i = 1; $i <= $value; $i++) { ?>
													<img src="https://sms.evolvu.in/public/reportcard/SACS/Plain_Yellow_Star.jpg" style="width:25px;height:20px">
													<?php
                                                }
                                                if ($value == 0)
                                                    echo "<font size='5'>#</font>";
                                                ?>
												</td>
									<?php }
                                        }
                                    } else { ?>
										<td class="emptytd" style=""></td>
								   <?php
                                    }
                                }
                            } else {
                                ?>
								<td class="emptytd" style=""></td>
							<?php
                            }
                        }
                        ?>
                   
						</tr>
                    
        <?php
        $mark_headings = $mark_headings_by_subject[$rw->sub_rc_master_id] ?? array();
        foreach ($mark_headings as $mh_row) {
            if ($mh_row->name != 'Term') {
                ?>
						<tr>
							<td  class="" style="text-align:left;height:20px;border-top:1px solid grey;border-left:1px solid grey;border-right:1px solid grey;border-bottom:1px solid grey;text-transform: uppercase;color:#0000A0;padding-left:50px;padding-top: 8px;"><b> <?php echo $mh_row->name; ?></b>
							</td>
                        <?php
                        foreach ($term_list as $term) {
                            $term_exams = $exam_list_by_term[$term->term_id] ?? array();
                            if (count($term_exams) > 0) {
                                foreach ($term_exams as $exam) {
                                    $marks_resultarray = isset($student_marks[$term->term_id][$rw->sub_rc_master_id][$exam->exam_id])
                                        ? array($student_marks[$term->term_id][$rw->sub_rc_master_id][$exam->exam_id])
                                        : array();
                                    if (count($marks_resultarray) > 0) {
                                        $mark_obtained_array = $marks_resultarray[0]['reportcard_marks'];
                                    } else {
                                        $mark_obtained_array = array();
                                    }
                                    if (count($mark_obtained_array) == 0) {
                                        $marks_obtained = '';
                                        $marks_exists = 'N';
                                    } else {
                                        $marks_headings_id = $mh_row->name;
                                        if (array_key_exists($marks_headings_id, $mark_obtained_array)) {
                                            $marks_obtained = $mark_obtained_array[$marks_headings_id];
                                            $marks_exists = 'Y';
                                        } else {
                                            $marks_obtained = '';
                                            $marks_exists = 'N';
                                        }
                                    }
                                    if ($mh_row->name != 'Term') {
                                        ?>
										<td class="imagetd">
											<?php for ($k = 1; $k <= $marks_obtained; $k++) { ?> 
												<img src="https://sms.evolvu.in/public/reportcard/SACS/Plain_Yellow_Star.jpg" style="width:25px;height:20px"> 
											<?php
                                        }
                                        if ($marks_obtained == 0 && $marks_exists == 'Y')
                                            echo "<font size='5'>#</font>";
                                        ?>
										</td>
							   <?php
                                    }
                                }
                            } else {
                                ?>
							<td class="emptytd" style=""></td>
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
                    }
                    ?>
					
					<tr>
						<td  class="td4"><b>Attendance</b></td>
						<?php
                        foreach ($term_list as $term) {
                            $attendance = $student_attendance[$term->term_id] ?? ['present' => '', 'working' => ''];
                            ?>
									<td class="td2" style="text-align:center;font-size:13px">
									<?php
                                    if (($attendance['present'] ?? '') !== '') {
                                        echo ($attendance['present'] ?? '') . '/' . ($attendance['working'] ?? '');
                                    }
                                    ?>
									</td>
								<?php
                        }
                        ?>
					</tr>
                    <tr>
						<td  class="td4"><b>Remarks</b></td>
						<?php
                        foreach ($term_list as $term) {
                            ?>
							<td class="col-md-1 td2" style="text-align:center;font-size:13px"><?php echo $student_remarks[$term->term_id]['remark'] ?? ''; ?></td>
						<?php } ?>
					</tr>
					<?php
                    if (count($term_list) > 1) {
                        ?>
					<tr>
                        <td  class="signtd" style=""><b>Promoted To</b></td>
                         <?php
                        $promote_to = '';
                        if (isset($term_list[1]->term_id))
                            $promote_to = $student_remarks[$term_list[1]->term_id]['promot'] ?? '';
                        ?>
							<td class="td2" colspan="2" style="text-align:center;font-size:13px"><?php echo $promote_to; ?></td>
                    </tr>
					<tr>
                        <td  class="signtd" style=""><b>School Reopens on</b></td>
                      
							<td class="td2" colspan="2" style="text-align:center;font-size:13px">
							<?php
                            $reopen_date = $reopen_date_master;
                            if ($reopen_date <> NULL && $reopen_date <> '0000-00-00')
                                echo date_format(date_create($reopen_date), 'd-m-Y');
                            ?>
							</td>
                         <?php ?>
                    </tr>
					<?php } ?>
                    <tr>
                        <td  class="signtd" style="height:35px"><b>Principal's Sign.</b></td>
							<td class="td2" colspan="2"></td>
                    </tr>
                    <tr>
						<td  class="signtd" style="height:35px"><b>Teacher's Sign.</b></td>
						<td class="td2" colspan="2"></td>
                    </tr>
                    
                </tbody>
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
