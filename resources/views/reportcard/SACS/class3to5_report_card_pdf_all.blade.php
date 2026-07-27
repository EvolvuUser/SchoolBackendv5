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
    @media print {
    .element-that-contains-table {
        overflow: visible !important;
    }
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
    p{
        color:blue;
        font-family: Comic Sans MS;
    }
    h4{
        color:red;
        font-family: 'Comic Sans MS' !important;
    }
    

    .th{
        vertical-align:middle;
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
    .td1{
        vertical-align:middle;
        text-align:center;
        height:30px;
        border:1px solid grey;
        text-transform: uppercase;
    }
    .td2{
        vertical-align:middle;
        text-align:left;
        height:30px;
        border:1px solid grey;
        text-transform: uppercase;
        padding-left:18px;
        background-color: orange;
    }
    .td{
        vertical-align:middle;
        height:30px;
        border:1px solid grey;
		font-size:12px;
    }
    .td3{
        vertical-align:middle;
        text-align:left;
        height:30px;
        border:1px solid grey;
        color:#0000A0;
        font-size:21px;
        padding-left:30px;
    }
    .td4{
        vertical-align:middle;
        text-align:left;
        height:30px;
        border:1px solid grey;
        text-transform: uppercase;
        color:#0000A0;
        background-color:silver;
        padding-left:30px;
        font-size:21px;
    }
    .lasttd{
        text-align:center;
        border:1px solid grey;
		font-size:14px;
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
<?php
if (!function_exists('resolveClass3To5ReportCardGradeFromScale')) {
    function resolveClass3To5ReportCardGradeFromScale($mark, $subjectType, $gradeScale)
    {
        if (!is_numeric($mark) || is_nan($mark)) {
            return '';
        }

        foreach ($gradeScale[$subjectType] ?? [] as $range) {
            if ($mark >= $range['mark_from'] && $mark <= $range['mark_upto']) {
                return $range['name'];
            }
        }

        return '';
    }
}

$student_info = $reportCardData['students'] ?? [];
$term_list_master = $reportCardData['term_list'] ?? [];
$scholastic_subjects_master = $reportCardData['scholastic_subjects'] ?? [];
$co_scholastic_subjects_master = $reportCardData['co_scholastic_subjects'] ?? [];
$term_metadata_master = $reportCardData['term_metadata'] ?? [];
$marks_by_student_master = $reportCardData['marks_by_student'] ?? [];
$remarks_by_student_master = $reportCardData['remarks_by_student'] ?? [];
$attendance_by_student_master = $reportCardData['attendance_by_student'] ?? [];
$grade_scale_master = $reportCardData['grade_scale'] ?? [];
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
<html>
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
		<table border="0"  class="table-responsive" style="width:85%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="1" cellspacing="10">
			<tr> 
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;width: 25%; padding-top: 6px; padding-bottom:6px;	word-wrap:break-word;">Student's Name : </td>
						<td style="font-size:16px;text-align: center;width: auto"><div class="statistics_line"><?php echo $row1['first_name'] . ' ' . $row1['mid_name'] . ' ' . $row1['last_name']; ?></div> </td>
						<td style="font-size:16px;width: 1%;"></td>
						<td style="font-size:16px;width: 15%;margin-left: 10px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">Roll No. : </td>
						<td style="font-size:18px;width: 8%;text-align: center;"><div class="statistics_line"> <?php echo $row1['roll_no']; ?></div></td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 38%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Mother's / Father's / Guardian's Name : </td>
                        <td style="font-size:16px;padding:5px;width: 42%;text-align: center;"><div class="statistics_line"><?php echo $row1['father_name'] ?? ''; ?></div></td>
						<td style="width: 1%;"></td>
						<td style="font-size:16px;margin-left: 10px;word-wrap:break-word;width:10%">GR No. : </td>
						<td style="font-size:16px;margin-left: 10px;word-wrap:break-word;width:auto;text-align: center;width:auto"><div class="statistics_line"> <?php echo $row1['reg_no']; ?></div></td>
                    </table>
                    
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 17%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Date of Birth : </td>
                        <td style="width:25%;text-align: center;"><div class="statistics_line"><?php echo date_format(date_create($row1['dob']), 'd-m-Y'); ?></div></td>
						<td style="width: 5%;"></td>
                        <td style="font-size:16px;padding:5px;width: 20%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Class / Section : </td>
						<td style="width: auto;text-align: center;"><div class="statistics_line"><?php echo trim($class_name . ' ' . $section_name); ?></div></td>
                    </table>
                    
                </td>
                
            </tr>
		</table>
		<table class="table-responsive" style="width:85%; margin-left: 5%; margin-right: auto; border-spacing: 0px; background-color:white; " cellpadding="0" cellspacing="0" >
			 <tr>
				 <td style="vertical-align:middle;" cellpadding="0" cellspacing="0">
					<table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border: 1px solid grey !important;" cellpadding="0" cellspacing="0">
                         <tr>
							<?php
                            ?>
							<th class="col-md-3 col-sm-3 col-xs-3 th" style="word-wrap: break-word; font-size:10px"><b>Scholastic Areas</b></th>
							<?php
                            //	$count_of_mark_headings=0;
                            foreach ($term_list as $term) {
                                ${'general_highest_marks_array_' . $term->term_id} = array();
                                ${'general_highest_marks_list_' . $term->term_id} = array();
                                ${'count_of_mark_headings_' . $term->term_id} = 0;
                                // $count_of_mark_headings=0;
                                $exam_list = $term_metadata[$term->term_id]['exam_list'] ?? array();
                                foreach ($exam_list as $exam) {
                                    ${'count_of_mark_headings_' . $exam->exam_id} = $term_metadata[$term->term_id]['count_of_mark_headings_by_exam'][$exam->exam_id] ?? 0;
                                }
                                ${'general_highest_marks_array_' . $term->term_id} = $term_metadata[$term->term_id]['general_highest_marks_array'] ?? array();
                                ${'general_highest_marks_list_' . $term->term_id} = $term_metadata[$term->term_id]['general_highest_marks_list'] ?? array();
                                ${'count_of_mark_headings_' . $term->term_id} = $term_metadata[$term->term_id]['count_of_mark_headings'] ?? 0;

                                ?>
							 <th class="col-md-1 th1" style="text-align:center;height:30px;" colspan="<?php echo ${'count_of_mark_headings_' . $term->term_id} + 2; ?>"><?php echo $term->name; ?></th>
                         <?php
                            }
                            ?>
						</tr>		
						<tr>
							<?php
                            // $term_list	=	get_term($acd_yr);
                            ?>
                            <td class="col-md-3 td" style="text-align:center;height:30px;">SUBJECT</th>
							<?php

                            foreach ($term_list as $term) {
                                ${'grand_total_marks ' . $term->term_id} = 0;
                                ${'grand_highest_marks_' . $term->term_id} = 0;

                                $highest_total_marks = 0;
                                if (isset(${'general_highest_marks_list_' . $term->term_id}) && ${'general_highest_marks_list_' . $term->term_id} <> null) {
                                    foreach (${'general_highest_marks_list_' . $term->term_id} as $heading) {
                                        // Lija For term 1 marks were doubled for acd yr 2020-2021. Remove this if condtion next yr
                                        if ($term->term_id == 1 && $heading['name'] == 'Term' && $row1['academic_yr'] == '2020-2021') {  // Lija 10-09-21
                                            $heading['highest_marks'] = $heading['highest_marks'] * 2;
                                        }
                                        $highest_total_marks = $highest_total_marks + (float) $heading['highest_marks'];

                                        ${'total_marks_' . $term->term_id . $heading['id']} = 0;
                                        ?> 
										<td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;"><?php echo $heading['name'] . '<br/>(' . $heading['highest_marks'] . ')'; ?></td>
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

                                ${'mark_obtained_array_' . $term->term_id} = array();
                                ${'highest_marks_array_' . $term->term_id} = array();
                                $exam_list = $term_metadata[$term->term_id]['exam_list'] ?? array();
                                if (isset($exam_list) && count($exam_list) > 0) {
                                    foreach ($exam_list as $exam) {
                                        ${'marks_resultarray_' . $term->term_id} = isset($student_marks[$term->term_id][$sub_row->sub_rc_master_id][$exam->exam_id])
                                            ? array($student_marks[$term->term_id][$sub_row->sub_rc_master_id][$exam->exam_id])
                                            : array();
                                        if (isset(${'marks_resultarray_' . $term->term_id}[0])) {
                                            // $grand_highest_marks=$grand_highest_marks+50; //As each subject total marks is of 50 marks

                                            ${'mark_obtained_array_' . $term->term_id} = ${'marks_resultarray_' . $term->term_id}[0]['reportcard_marks'];

                                            if (isset(${'mark_obtained_array_' . $term->term_id}) && ${'mark_obtained_array_' . $term->term_id} <> null) {
                                                foreach (${'mark_obtained_array_' . $term->term_id} as $key => $value) {
                                                    if ($total_marks_obtained == '')
                                                        $total_marks_obtained = 0;
                                                    $total_marks_obtained = $total_marks_obtained + (float) $value;
                                                    $varName = 'total_marks_' . $term->term_id . $exam->exam_id . '_' . $key;

                                                    if (!isset($$varName)) {
                                                        $$varName = 0;
                                                    }

                                                    $$varName += (float) $value;
                                                    // echo "marks_".$term->term_id.$key." ".${'total_marks_'.$term->term_id.$key}."<br/>";
                                                    ?> 
												<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;"><?php echo $value; ?></td>
										<?php }
                                            } else { ?>
								                <td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;" colspan="<?php echo (${'count_of_mark_headings_' . $term->term_id} + 2); ?>"></td> 
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
                            ${'grand_highest_marks_' . $term->term_id} = ${'grand_highest_marks_' . $term->term_id} + 100;
                        }
                        if ($total_marks_obtained == '') {
                            echo '';
                        } else {
                            $final_grade = resolveClass3To5ReportCardGradeFromScale(round($total_marks_obtained), 'Scholastic', $grade_scale);
                            echo $final_grade;
                        }
                        ?>
						</td>
					<?php
                                } else {
                    ?>
							<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;" colspan="<?php echo (${'count_of_mark_headings_' . $term->term_id} + 2); ?>"></td> 
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
                                if (isset(${'general_highest_marks_list_' . $term->term_id}) && ${'general_highest_marks_list_' . $term->term_id} <> null) {
                                    foreach (${'general_highest_marks_list_' . $term->term_id} as $heading) {
                                        ?>
										<td class="col-md-1 td" style="text-align:center;height:45px;"><?php echo ${'total_marks_' . $term->term_id . $heading['id']}; ?></td>
							<?php
                                    }
                                    $grand_grade = '';
                                    if (${'grand_highest_marks_' . $term->term_id} <> 0) {
                                        $grand_marks_per_100 = (${'grand_total_marks ' . $term->term_id} * 100) / ${'grand_highest_marks_' . $term->term_id};  // Convert to out of 100
                                        $grand_grade = resolveClass3To5ReportCardGradeFromScale(round($grand_marks_per_100), 'Scholastic', $grade_scale);
                                    }
                                    ?>
								<td class="col-md-1 td" style="text-align:center;height:30px;"><?php echo ${'grand_total_marks ' . $term->term_id} . '/' . ${'grand_highest_marks_' . $term->term_id}; ?></td>
									
								<td class="col-md-1 td" style="text-align:center;height:30px;"><?php echo $grand_grade; ?></td>
							<?php
                                } else {
                            ?>
							       <td class="col-md-1 td" colspan="<?php echo (${'count_of_mark_headings_' . $term->term_id} + 2); ?>" style="vertical-align:middle;text-align:center;"></td> 
							<?php
                                }
                            }
                            ?>
						</tr>
						
				</table>
				</td>
			</tr>
		</table>
        
        <br>
         <table class="table-responsive" style="width:90%;margin-left: 4%;margin-right: auto;border-spacing: 0px;background-color:white;">
			 <tr>
				 <td style="" cellpadding="0" cellspacing="0">
                    <table class="table-responsive" style="width:auto;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						<tr>
				
                            <td class="" style="vertical-align:middle;" cellpadding="0" cellspacing="0">
                            <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white; font-size:15px;" cellpadding="2" cellspacing="0">
                                <?php
                                $colspan = count($term_list) + 1;
                                ?>
                                <tr>
                                    <th class="td" cellpadding="0" colspan="<?php echo $colspan; ?>">CO- SCHOLASTICS AREA (Graded on 5 point Scale)</th>
                                </tr>
                                <tr>
                                    <th class="col-md-3 th" style="height:25px;">Subjects</th>
                                    <?php
                                    foreach ($term_list as $term):
                                        ?>
                                    <th class="col-md-1 th1" style="height:25px;" width=""><?php echo $term->name; ?></th>
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
                                    <td  class="col-md-1 td" style="text-align:center;height:20px;"> 
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
            $sub_marks_per_term = 0;  // Lija 28-09-20
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
                        if ($coscholastic_grade == 'Ab' && ${'coscholastic_marksobtained_' . $term->term_id} == 0) {
                            // If reportcard marks is Ab and total marks is 0 then Grade will be Ab
                            $coscholastic_grade = 'Ab';
                        } else {
                            // Convert co-scholastic marks to out of 50 as for some subjects like computer it is out of 25 n for others it is out of 50
                            $marks_per_50 = (${'coscholastic_marksobtained_' . $term->term_id} * 50) / ${'coscholastic_highestmarks_' . $term->term_id};  // Convert to out of 50
                            if ($sub_row->sub_rc_master_id == 8 && $term->term_id == 2 && $row1['academic_yr'] == '2020-2021' && $marks_per_50 <= 30) {
                                // Lija 13-03-21 Art/craft if marks is less than 30 give C grade.
                                $coscholastic_grade = 'C';
                            } else {
                                $coscholastic_grade = resolveClass3To5ReportCardGradeFromScale(round($marks_per_50), 'Co-Scholastic', $grade_scale);
                            }
                        }
                    }
                }
            }
            ?>
							<td class="td" style="text-align:center;height:20px;"><?php echo $coscholastic_grade; ?></td>
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
                 <td ></td>
				<td >
                     <table border="1" class="table-responsive" style="width:auto;margin-left: 3%; margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 0%;border-color:grey" cellpadding="0" cellspacing="0">
                         <tr>
                            <th class="col-md-3 lasttd" colspan="2" style="text-align:center;"> <div style="font-size:15px"> GRADING SCALE FOR SCHOLASTIC AREAS </div><div style="font-size:12px">  Grades are awarded on a 8 Point grading scale as follows</div></th>
                        </tr>
                        <tr>
                            <td class="col-md-1 lasttd"  style="">MARKS RANGE</td>
                            <td class="col-md-1 lasttd">GRADE</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">91-100</td>
                            <td class="col-md-1 lasttd">A1</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">81-90</td>
                            <td class="col-md-1 lasttd">A2</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">71-80</td>
                            <td class="col-md-1 lasttd">B1</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">61-70</td>
                            <td class="col-md-1 lasttd">B2</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">51-60</td>
                            <td class="col-md-1 lasttd">C1</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">41-50</td>
                            <td class="col-md-1 lasttd">C2</td>
                         </tr>
                         <tr>
                            <td class="col-md-1 lasttd">33-40</td>
                            <td class="col-md-1 lasttd">D</td>
                         </tr>
                          <tr>
                            <td class="col-md-1 lasttd">32 & below</td>
                            <td class="col-md-1 lasttd">E (Needs Improvement)</td>
                         </tr>
                     </table>
                 </td>
             </tr>
        </table>
			<table class="table-responsive" style="width:90%;margin-left:4%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="10" cellspacing="10" border="0">
			<tr> 
			   <!--td-->
				<?php
                $remark_string = '';
                $width = '100%';
                foreach ($term_list as $term) {
                    $remark = $student_remarks[$term->term_id]['remark'] ?? '';
                    if ($remark_string == '' && $remark <> '') {
                        $remark_string = $remark_string . 'Term ' . $term->term_id . ' - ' . $remark;
                    } elseif ($remark_string <> '' && $remark <> '') {
                        $remark_string = $remark_string . '<br/> Term ' . $term->term_id . ' - ' . $remark;
                    } else {
                        $remark_string = '<br>';
                        $width = '85%';
                    }
                }

                ?>
				<td style="font-size:14px;width: 28%;"><b>Class Teacher's Remark :</b></td>
				<td style="font-size:12px;width: 70%;">
    				<div class="statistics_line">
    				<?php
                    echo $remark_string;
                    ?>
    				</div>
				</td>
        </tr>
		<?php

        $date_from = $reportCardData['date_from'] ?? null;
        $date_to = $reportCardData['date_to'] ?? null;
        // $date_from=get_academic_yr_from_of_particular_yr($acd_yr);
        // $date_to=get_academic_yr_to_of_particular_yr($acd_yr);
        ?>
        <tr> 
            <td colspan="2">
				<table class="table-responsive" border=0 style="width:100%;margin-left:auto;margin-right: auto;border-spacing: 0px;background-color:white;">
                    <tr>
						<td style="font-size:14px;text-align:left;white-space:nowrap;width: 10% !important" ><b> Attendance : </b></td>
    					<td style="font-size:14px;white-space:nowrap;width:20%!important;margin-right:2%;text-align:center;" ><div class="statistics_line">
						<?php
                        if (($student_attendance['present'] ?? '') !== '') {
                            echo ($student_attendance['present'] ?? '') . '/' . ($student_attendance['working'] ?? '');
                        }
                        ?>&nbsp;
							</div> 
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
                        <td style="margin-left:5%;text-align:center;font-size:14px;width:20%!important;">
						<div class="statistics_line"><?php echo $promote_to; ?>&nbsp;</div> </td>
                        <td style="text-align:center;font-size:14px;width:15%;"><b> Date Of Reopening :</b></td>
                        <td style="width:auto;text-align:center;" ><div class="statistics_line">
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
        <br>
	    <table class="table-responsive" style="width:86%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;overflow: visible !important;" cellpadding="1" cellspacing="10">
			<tr>
				<td style="width:33%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<td style="width:30%;text-align: center;"><div class="statistics_line"> </div></td>
						</tr>
					</table>
				</td>
				<td style="width:33%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<td style="width:30%;text-align: center;"><div class="statistics_line"> </div></td>
						</tr>
					</table>
				</td>
				<td style="width:33%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						 <tr>
							<td style="width:30%;text-align: center;"><div class="statistics_line"> </div></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr> 
				<td style="width:33%;">
					<table class="table-responsive" width="100%" cellspacing="0">
						<tr>
							<td style="font-size:13px;" width="30%;text-align: center;"><b>Class Teacher's Sign.</b></td>
						</tr>
					</table>
				</td>
				<td style="width:33%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<th style="font-size:10px;text-align:center;" width="15%" ></th>
							<td style="font-size:13px;text-align:left;width:30%;"><b>Parent's Sign.</b></td>
						</tr>
					</table>
				</td>
				<td style="width:33%;">
					<table class="table-responsive" width="90%" cellspacing="0">
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
</head>
<body>
