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
    background-image:url('https://sms.evolvu.in/public/reportcard/SACS/primary_bg.jpg');
   -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;
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
        height:30px;
		font-size:12px;
        border:1px solid grey;
        text-transform: uppercase;
        padding-top: 8px;
    }
    
    .th1{
        vertical-align:middle;
        text-align:center;
        height:30px;
		font-size:12px;
        border:1px solid grey;
        text-transform: uppercase;
        color:red;
        padding-top: 8px;
    }
	.thc{
        vertical-align:middle;
        text-align:center;
        height:30px;
        border:1px solid grey;
    }
    .statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        padding:3px;
    }
   
    .td{
        vertical-align:middle;
        height:30px;
        border:1px solid grey;
		font-size:12px;
    }

    .lasttd{
        text-align:center;
        border:1px solid grey;
		font-size:14px;
    }
	.pdfdiv {
	    page-break-after: auto;
   page-break-inside: avoid;
	}
	.pdfdiv:last-child{
		page-break-after: avoid;
		page-break-inside: avoid;
		margin-bottom: 0px;
	} 
</style>  
<?php
$student_info = $reportCardData['students'] ?? array();
$term_list_master = $reportCardData['term_list'] ?? array();
$scholastic_subjects_master = $reportCardData['scholastic_subjects'] ?? array();
$co_scholastic_subjects_master = $reportCardData['co_scholastic_subjects'] ?? array();
$term_metadata_master = $reportCardData['term_metadata'] ?? array();
$marks_by_student_master = $reportCardData['marks_by_student'] ?? array();
$remarks_by_student_master = $reportCardData['remarks_by_student'] ?? array();
$attendance_by_student_master = $reportCardData['attendance_by_student'] ?? array();
$grade_scale_master = $reportCardData['grade_scale'] ?? array();
$reopen_date_master = $reportCardData['reopen_date'] ?? null;
foreach ($student_info as $row1):
    $class_name = $row1['class_name'] ?? '';
    $section_name = $row1['sec_name'] ?? '';
    $term_list = $term_list_master;
    $scholastic_subjects = $scholastic_subjects_master;
    $co_scholastic_subjects = $co_scholastic_subjects_master;
    $term_metadata = $term_metadata_master;
    $student_marks = $marks_by_student_master[$row1['student_id']] ?? [];
    $student_remarks = $remarks_by_student_master[$row1['student_id']] ?? [];
    $student_attendance = $attendance_by_student_master[$row1['student_id']] ?? ['present' => '', 'working' => ''];
    $grade_scale = $grade_scale_master;
    ?>
    <head>
        <meta charset="utf-16" />
    </head>
    <body>
    <div class="col-md-12 pdfdiv">
<div class="col-md-2"></div>
	<div class="col-md-8 table-responsive bgimg" style="text-align:center;">
		<table border="0" style="width:85%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 20%;">
			<tr>
				<td style="width:30%;text-align: left;font-size:14px;" >
					UDISE No. - 27251501213
				</td>
				<td style="width:40%;text-align: center;">
					<h4 >ACADEMIC SESSION <?php echo $row1['academic_yr']; ?></h4>
					<h3><font color="#000000">REPORT CARD</font></h3>
				</td>
				<td style="width:30%;text-align: left;font-size:14px;margin-left: 30px;" >
					Student ID - <?php echo $row1['stud_id_no']; ?>
				</td>
			</tr>
		</table>
		<br/>
		<table border="0"  class="table-responsive" style="width:82%;margin-left:6%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="1" cellspacing="10">
			<tr> 
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;width: 20%; padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">Student's Name : </td>
						<td style="font-size:15px;text-align: center;width: auto"><div class="statistics_line"><?php echo $row1['first_name'] . ' ' . $row1['mid_name'] . ' ' . $row1['last_name']; ?></div> </td>
						<td style="font-size:16px;width: 1%;"></td>
						<td style="font-size:16px;width: 15%;margin-left: 10px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">Roll No. : </td>
						<td style="font-size:15px;width: 8%;text-align: center;"><div class="statistics_line"> <?php echo $row1['roll_no']; ?></div></td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 44%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Mother's / Father's / Guardian's Name : </td>
                        <td style="font-size:15px;padding:5px;width: 37%;text-align: center;"><div class="statistics_line"><?php echo $row1['father_name'] ?? ''; ?></div></td>
						<td style="width: 1%;"></td>
						<td style="font-size:16px;margin-left: 10px;word-wrap:break-word;width:12%">GR No. : </td>
						<td style="font-size:15px;margin-left: 10px;word-wrap:break-word;width:auto;text-align: center;width:auto"><div class="statistics_line"> <?php echo $row1['reg_no']; ?></div></td>
                    </table>
                    
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 17%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Date of Birth : </td>
                        <td style="font-size:15px;width:25%;text-align: center;"><div class="statistics_line"><?php echo date_format(date_create($row1['dob']), 'd-m-Y'); ?></div></td>
						<td style="width: 5%;"></td>
                        <td style="font-size:16px;padding:5px;width: 20%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Class / Section : </td>
						<td style="font-size:15px;width: auto;text-align: center;"><div class="statistics_line"><?php echo trim($class_name . ' ' . $section_name); ?></div></td>
                    </table>
                    
                </td>
                
            </tr>
		</table>
		<table class="table-responsive" style="width:88%; margin-left: 6%; margin-right: auto; border-spacing: 0px; background-color:white; " cellpadding="0" cellspacing="0" >
			 <tr>
				 <td style="vertical-align:middle;text-align: center" cellpadding="0" cellspacing="0">
					<table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <tr>
							<?php
                            ?>
							<th class="col-md-3 col-sm-3 col-xs-3 th" style="word-wrap: break-word;font-size:10px"><b>Scholastic Areas</b></th>
							<?php
                            //	$count_of_mark_headings=0;
                            foreach ($term_list as $term) {
                                ${'general_highest_marks_array_' . $term->term_id} = array();
                                ${'count_of_mark_headings_' . $term->term_id} = 0;
                                // $count_of_mark_headings=0;
                                $exam_list = $term_metadata[$term->term_id]['exam_list'] ?? array();
                                foreach ($exam_list as $exam) {
                                    ${'count_of_mark_headings_' . $exam->exam_id} = $term_metadata[$term->term_id]['count_of_mark_headings_by_exam'][$exam->exam_id] ?? 0;
                                }
                                ${'general_highest_marks_array_' . $term->term_id} = $term_metadata[$term->term_id]['general_highest_marks_array'] ?? array();
                                ${'count_of_mark_headings_' . $term->term_id} = $term_metadata[$term->term_id]['count_of_mark_headings'] ?? 0;
                                ?>
							 <th class="col-md-1 th1" style="text-align:center;height:30px;" colspan="<?php echo ${'count_of_mark_headings_' . $term->term_id} + 2; ?>"><?php echo $term->name; ?></th>
                         <?php
                            }
                            ?>
						</tr>		
						<tr>
							<?php
                            // $term_list	=	$this->assessment_model->get_term($acd_yr);
                            ?>
                            <td class="col-md-3 td" style="text-align:center;height:30px;">SUBJECT</th>
							<?php

                            foreach ($term_list as $term) {
                                ${'grand_total_marks ' . $term->term_id} = 0;
                                ${'grand_highest_marks_' . $term->term_id} = 0;

                                $highest_total_marks = 0;
                                if (isset(${'general_highest_marks_array_' . $term->term_id}) && ${'general_highest_marks_array_' . $term->term_id} <> null) {
                                    foreach (${'general_highest_marks_array_' . $term->term_id} as $key => $value) {
                                        $highest_total_marks = $highest_total_marks + (float) $value;

                                        ${'total_marks_' . $term->term_id . $key} = 0;
                                        ?> 
										<td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;"><?php echo $key . '<br/>(' . $value . ')'; ?></td>
							 <?php
                                    }

                                    ?>
										<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;">Total<br/>(<?php echo $highest_total_marks; ?>)</td>
										<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;">Grade</td>
							 <?php
                                } else {
                                    ?>
								        <td class="col-md-1 td"  colspan="<?php echo ${'count_of_mark_headings_' . $term->term_id} + 2; ?>" style="text-align:center;height:30px;"></td>
							        
							<?php
                                }
                            }
                            ?>
						</tr>

						<?php
                        // $grand_highest_marks=0;
                        $sub_list = $scholastic_subjects;

                        foreach ($sub_list as $sub_row) {
                            ?>
						<tr>
                             <td  class="col-md-1 td" style="text-align:center;height:30px;">  
								<?php
                                echo $sub_row->name;
                                ?>
							</td>
							<?php
                            foreach ($term_list as $term) {
                                $total_marks_obtained = '';
                                $total_highest_marks = '';  // Lija 18-03-22

                                ${'mark_obtained_array_' . $term->term_id} = array();
                                ${'highest_marks_array_' . $term->term_id} = array();
                                $exam_list = $term_metadata[$term->term_id]['exam_list'] ?? array();
                                if (isset($exam_list) && count($exam_list) > 0) {
                                    foreach ($exam_list as $exam) {
                                        ${'marks_resultarray_' . $term->term_id} = isset($student_marks[$term->term_id][$sub_row->sub_rc_master_id][$exam->exam_id])
                                            ? array($student_marks[$term->term_id][$sub_row->sub_rc_master_id][$exam->exam_id])
                                            : array();
                                        if (isset(${'marks_resultarray_' . $term->term_id}[0])) {
                                            ${'mark_obtained_array_' . $term->term_id} = ${'marks_resultarray_' . $term->term_id}[0]['reportcard_marks'];
                                            ${'highest_marks_array_' . $term->term_id} = ${'marks_resultarray_' . $term->term_id}[0]['reportcard_highest_marks'];  // Lija 18-03-22

                                            if (isset(${'mark_obtained_array_' . $term->term_id}) && ${'mark_obtained_array_' . $term->term_id} <> null) {
                                                foreach (${'mark_obtained_array_' . $term->term_id} as $key => $value) {
                                                    if ($total_marks_obtained == '')
                                                        $total_marks_obtained = 0;
                                                    $total_marks_obtained = $total_marks_obtained + (float) $value;
                                                    ${'total_marks_' . $term->term_id . $key} = ${'total_marks_' . $term->term_id . $key} + (float) $value;
                                                    // echo "marks_".$term['term_id'].$key." ".${'total_marks_'.$term['term_id'].$key}."<br/>";
                                                    ?> 
												<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;"><?php echo $value; ?></td>
										<?php
                                                }
                                                // Lija 18-03-22
                                                foreach (${'highest_marks_array_' . $term->term_id} as $key => $value) {
                                                    if ($total_highest_marks == '')
                                                        $total_highest_marks = 0;
                                                    $total_highest_marks = $total_highest_marks + (float) $value;
                                                }
                                                ?>
				
										
								<?php } else { ?>
								                <td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;" colspan="<?php echo (${'count_of_mark_headings_' . $term['term_id']} + 2); ?>"></td> 
								<?php
                                            }
                                        } else {
                                            for ($i = 0; $i < ${'count_of_mark_headings_' . $exam->exam_id}; $i++) {
                                ?>
									<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;"></td>
							<?php
                                            }
                                        }
                                    }
                                    ?>
						<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;"><?php echo $total_marks_obtained; ?></td>
									
						<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;">
						<?php
                        if ($total_marks_obtained <> '') {
                            ${'grand_total_marks ' . $term->term_id} = ${'grand_total_marks ' . $term->term_id} + $total_marks_obtained;
                            ${'grand_highest_marks_' . $term->term_id} = ${'grand_highest_marks_' . $term->term_id} + $total_highest_marks;  // Lija 18-03-22
                        }
                        if ($total_marks_obtained == '') {
                            echo '';
                        } else {
                            $final_grade = '';
                            if ($total_highest_marks <> 0) {
                                $subject_total_marks_per_50 = ($total_marks_obtained * 50) / $total_highest_marks;  // Convert to out of 50
                                $final_grade = '';
                                foreach ($grade_scale['Scholastic'] ?? [] as $range) {
                                    if (round($subject_total_marks_per_50) >= $range['mark_from'] && round($subject_total_marks_per_50) <= $range['mark_upto']) {
                                        $final_grade = $range['name'];
                                        break;
                                    }
                                }
                            }
                            echo $final_grade;
                        }
                        ?>
						</td>
					<?php
                                } else {
                    ?>
							<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;" colspan="<?php echo (${'count_of_mark_headings_' . $term['term_id']} + 2); ?>"></td> 
					<?php
                                }
                            }
                            ?>
				
                        </tr>
                        <?php
                        }
                        ?>
						<tr>
							<td class="td" style="text-align:center;height:45px;">TOTAL</td>
							<?php
                            foreach ($term_list as $term) {
                                if (isset(${'general_highest_marks_array_' . $term->term_id}) && ${'general_highest_marks_array_' . $term->term_id} <> null) {
                                    foreach (${'general_highest_marks_array_' . $term->term_id} as $key => $value) {
                                        ?>
										<td class="col-md-1 td" style="text-align:center;height:45px;"><?php echo ${'total_marks_' . $term->term_id . $key}; ?></td>
							<?php
                                    }
                                    $grand_grade = '';
                                    if (${'grand_highest_marks_' . $term->term_id} <> 0) {
                                        $grand_marks_per_50 = (${'grand_total_marks ' . $term->term_id} * 50) / ${'grand_highest_marks_' . $term->term_id};
                                        $grand_grade = '';
                                        foreach ($grade_scale['Scholastic'] ?? [] as $range) {
                                            if (round($grand_marks_per_50) >= $range['mark_from'] && round($grand_marks_per_50) <= $range['mark_upto']) {
                                                $grand_grade = $range['name'];
                                                break;
                                            }
                                        }
                                    }
                                    ?>
								<td class="col-md-1 td" style="text-align:center;height:30px;"><?php echo ${'grand_total_marks ' . $term->term_id} . '/' . ${'grand_highest_marks_' . $term->term_id}; ?></td>
									
								<td class="col-md-1 td" style="text-align:center;height:30px;"><?php echo $grand_grade; ?></td>
							<?php
                                } else {
                            ?>
							       <td class="col-md-1 td" colspan="<?php echo (${'count_of_mark_headings_' . $term['term_id']} + 2); ?>" style="vertical-align:middle;text-align:center;"></td> 
							<?php
                                }
                            }
                            ?>
						</tr>
						
				</table>
				</td>
			</tr>
		</table>
        
         <table class="table-responsive" style="width:85%;margin-left: 5%;margin-right: auto;border-spacing: 0px;background-color:white;">
			 <tr>
				 <td style="" cellpadding="0" cellspacing="0">
                    <table class="table-responsive" style="width:auto;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			 <tr>
				
                            <td class="" style="vertical-align:middle;" cellpadding="0" cellspacing="0">
                            <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white; font-size:15px;border: 1px solid grey !important;" cellpadding="0" cellspacing="0">
                                <?php
                                $colspan = count($term_list) + 1;
                                ?>
                                <tr>
                                    <th class="col-md-3 td" colspan="<?php echo $colspan; ?>">CO-SCHOLASTICS AREA (Graded on 5 point Scale)</th>
                                </tr>
                                <tr>
                                    <th class="col-md-3 th">Subjects</th>
                                    <?php
                                    foreach ($term_list as $term):
                                        ?>
                                    <th class="col-md-1 th1"  width=""><?php echo $term->name; ?></th>
                                     <?php
                                    endforeach;
                                    ?>
                                </tr>
                                 <?php
    $sub_list = $co_scholastic_subjects;

    foreach ($sub_list as $sub_row):
        $acd_yr_frm = substr($row1['academic_yr'], 0, 4);
        // Music was added in 2023-2024 Term 1 but by Term 2 it was told to remove from reportcard
        if ($acd_yr_frm == 2023 && $sub_row['name'] == 'Music') {
            continue;
        }
        ?>

                                <tr>
                                <td  class="col-md-1 td" style="text-align:center;height:30px;"> 
                                    <?php

                                    if ($acd_yr_frm >= 2023 && $sub_row->name == 'GK') {
                                        echo 'V.Ed / G.K';
                                    } else {
                                        echo $sub_row->name;
                                    }
                                    ?>
                                </td>
                             
                             <?php
        foreach ($term_list as $term) {
            ${'mark_obtained_array_' . $term->term_id} = array();
            $exam_list = $term_metadata[$term->term_id]['exam_list'] ?? array();
            $coscholastic_grade = '';
            foreach ($exam_list as $exam) {
                ${'marks_resultarray_' . $term->term_id} = isset($student_marks[$term->term_id][$sub_row->sub_rc_master_id][$exam->exam_id])
                    ? array($student_marks[$term->term_id][$sub_row->sub_rc_master_id][$exam->exam_id])
                    : array();

                if (isset(${'marks_resultarray_' . $term->term_id}[0])) {
                    ${'mark_obtained_array_' . $term->term_id} = array_merge(${'mark_obtained_array_' . $term->term_id}, ${'marks_resultarray_' . $term->term_id}[0]['reportcard_marks']);

                    if (isset(${'mark_obtained_array_' . $term->term_id}) && ${'mark_obtained_array_' . $term->term_id} <> null) {
                        ${'coscholastic_marksobtained_' . $term->term_id} = ${'marks_resultarray_' . $term->term_id}[0]['total_marks'];

                        ${'coscholastic_highestmarks_' . $term->term_id} = ${'marks_resultarray_' . $term->term_id}[0]['highest_total_marks'];

                        foreach (${'mark_obtained_array_' . $term->term_id} as $key => $value) {
                            if ($value == 'Ab')
                                $coscholastic_grade = 'Ab';
                        }
                        if ($coscholastic_grade == 'Ab' && ${'coscholastic_marksobtained_' . $term['term_id']} == 0) {
                            // If reportcard marks is Ab and total marks is 0 then Grade will be Ab
                            $coscholastic_grade = 'Ab';
                        } else {
                            // Convert co-scholastic marks to out of 50 as for some subjects like computer it is out of 25 n for others it is out of 50
                            $marks_per_50 = (${'coscholastic_marksobtained_' . $term->term_id} * 50) / ${'coscholastic_highestmarks_' . $term->term_id};  // Convert to out of 50
                            if ($sub_row->sub_rc_master_id == 8 && $term->term_id == 2 && $row1['academic_yr'] == '2020-2021' && $marks_per_50 <= 30) {
                                // Lija 13-03-21 Art/craft if marks is less than 30 give C grade.
                                $coscholastic_grade = 'C';
                            } else {
                                $coscholastic_grade = '';
                                foreach ($grade_scale['Co-Scholastic'] ?? [] as $range) {
                                    if (round($marks_per_50) >= $range['mark_from'] && round($marks_per_50) <= $range['mark_upto']) {
                                        $coscholastic_grade = $range['name'];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            ?>
							<td class="td" style="text-align:center;height:30px;"><?php echo $coscholastic_grade; ?></td>
							<?php
        }
        ?>
						</tr>
                                <?php endforeach; ?>
                            </table>
                          </td>
				</tr>
			</table>
                 </td>
                 <td></td>
                 <td>
                    <table border="1" class="table-responsive" style="width:auto;margin-left: 3%;margin-right:auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                         <tr>
                            <th class="col-md-3 lasttd" colspan="2" style="text-align:center;font-size:15px;"> GRADING SCALE FOR SCHOLASTIC AREAS <br> Grades are awarded on a 8 Point grading scale as follows</th>
                        </tr>
                        <tr>
                            <td class="col-md-1 lasttd"  style="">MARKS RANGE</td>
                            <td class="col-md-1 lasttd">GRADE</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">45-50</td>
                            <td class="col-md-1 lasttd">A1</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">40-44</td>
                            <td class="col-md-1 lasttd">A2</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">35-39</td>
                            <td class="col-md-1 lasttd">B1</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">30-34</td>
                            <td class="col-md-1 lasttd">B2</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">25-29</td>
                            <td class="col-md-1 lasttd">C1</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">20-24</td>
                            <td class="col-md-1 lasttd">C2</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">16-19</td>
                            <td class="col-md-1 lasttd">D</td>
                         </tr>
                          <tr>
                            <td class="col-md-1 lasttd">15 & below</td>
                            <td class="col-md-1 lasttd">E (Needs Improvement)</td>
                         </tr>
                     </table>
                 </td>
             </tr>
        </table>
			<table class="table-responsive" style="width:85%;margin-left:6%;margin-right: auto;border-spacing: 0px;background-color:white;border: 0px solid red">
			<tr> 
			   <td>
			   <?php
    $remark_string = '';
    $width = '90%';
    foreach ($term_list as $term) {
        $remark = $student_remarks[$term->term_id]['remark'] ?? '';
        if ($remark_string == '' && $remark <> '') {
            $remark_string = $remark_string . 'Term ' . $term['term_id'] . ' - ' . $remark;
        } elseif ($remark_string <> '' && $remark <> '') {
            $remark_string = $remark_string . '<br/> Term ' . $term['term_id'] . ' - ' . $remark;
        } else {
            $remark_string = '<br>';
            $width = '85%';
        }
    }

    ?>
			       <table width="<?php echo $width; ?>" style="border: 0px solid green">
                        <tr>
            				<td style="font-size:14px;width: 28%;"><b>Class Teacher's Remark :</b></td>
            				<td style="font-size:12px;width: 60%;">
								<div class="statistics_line">
								<?php

                                echo $remark_string;

                                ?>
								</div>
            				</td>
            			</tr>
        		    </table>
        		</td>
        </tr>
		<?php
        $date_from = null;
        $date_to = null;
        if (count($term_list) == 1) {
            $date_from = $reportCardData['date_from'] ?? null;
            $date_to = date_format(date_create(substr($date_from, 0, 4) . '-09-30'), 'Y-m-d');  // Creating date to as last day of sep;
        } elseif (count($term_list) == 2) {
            // $date_from=date_format(date_create(substr($date_from,0,4)."-10-01") , 'Y-m-d') ; // Creating date from as first day of Oct;
            $date_from = $reportCardData['date_from'] ?? null;  // Creating date from as first day of acd yr;
            $date_to = $reportCardData['date_to'] ?? null;
        }

        // $date_from=$this->crud_model->get_academic_yr_from_of_particular_yr($acd_yr);
        // $date_to=$this->crud_model->get_academic_yr_to_of_particular_yr($acd_yr);
        ?>
        <tr> 
            <td>
				<table class="table-responsive"  width="<?php echo $width; ?>" style="margin-right: auto;border-spacing: 0px;background-color:white;">
                    <tr>
						<td style="font-size:14px;text-align:left;white-space:nowrap;width: 10%" ><b> Attendance : </b></td>
    					<td style="font-size:14px;white-space:nowrap;width:20%!important;margin-right:2%;text-align:center;" ><div class="statistics_line">
						<?php
                        if (($student_attendance['present'] ?? '') !== '') {
                            echo ($student_attendance['present'] ?? '') . '/' . ($student_attendance['working'] ?? '');
                        }
                        ?>&nbsp;</div> 
						</td>
					<?php
                    if (count($term_list) > 1) {
                        ?>
                        <td style="font-size:14px;;text-align:left;white-space:nowrap;width: 15%;"><b> Promoted To :</b></td>
						<?php
                        $promote_to = '';
                        if (isset($term_list[1]->term_id))
                            $promote_to = $student_remarks[$term_list[1]->term_id]['promot'] ?? '';

                        ?>
                        <td style="text-align:center;font-size:14px;width:7%!important;">
						<div class="statistics_line"><?php echo $promote_to; ?>&nbsp;</div> </td>
                        <td style="text-align:center;font-size:14px;width:15%;"><b> Date Of Reopening :</b></td>
                        <td style="width:auto;text-align:center;font-size:14px" ><div class="statistics_line">
							<?php
                            $reopen_date = $reopen_date_master;
                            if ($reopen_date <> NULL && $reopen_date <> '0000-00-00')
                                echo date_format(date_create($reopen_date), 'd-m-Y');
                            ?>
						&nbsp;</div></td>
					<?php } else { ?>
						<td style="width: auto"> </td>
					<?php } ?>
                    </tr>
                </table>

			 </td>
             
        </tr>
		</table>
        <br><br>
	    <table class="table-responsive" style="width:85%;margin-left:6%;margin-right: auto;border-spacing: 0px;background-color:white;overflow: visible !important;" cellpadding="1" cellspacing="10">
			<tr>
				<td style="width:35%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<td style="width:30%;text-align: center;"><div class="statistics_line"> </div></td>
						</tr>
					</table>
				</td>
				<td style="width:35%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<td style="width:30%;text-align: center;"><div class="statistics_line"> </div></td>
						</tr>
					</table>
				</td>
				<td style="width:30%;">
					<table class="table-responsive" width="100%" cellspacing="0">
						 <tr>
							<td style="width:30%;text-align: center;"><div class="statistics_line"> </div></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr> 
				<td style="width:30%;">
					<table class="table-responsive" width="100%" cellspacing="0">
						<tr>
							<td style="font-size:13px;" width="30%;text-align: center;"><b>Class Teacher's Sign.</b></td>
						</tr>
					</table>
				</td>
				<td style="width:30%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<th style="font-size:10px;text-align:center;" width="15%" ></th>
							<td style="font-size:13px;text-align:left;width:30%;"><b>Parent's Sign.</b></td>
						</tr>
					</table>
				</td>
				<td style="width:35%;">
					<table class="table-responsive" width="100%" cellspacing="0">
						<tr>
							<th style="font-size:10px;text-align:center;" width="15%" ></th>
							<td style="font-size:13px;text-align:left;width:30%;"><b>Principal's Sign.</b></td>
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
