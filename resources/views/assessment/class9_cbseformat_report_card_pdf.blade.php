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
   
   .mark_heading_td{
		font-size:14px;
		vertical-align:center;
        height:10px;
 	}
	.signtag{
		font-size:14px;
		vertical-align:center;
        height:10px;
 	}
    .th{
        vertical-align:middle;
        text-align:center;
        height:38px;
        font-size:14px;
        text-transform: uppercase;
/*        color:red;*/
    }
    .th1{
        vertical-align:middle;
        text-align:center;
        height:38px;
        font-size:14px;
        text-transform: uppercase;
    /*    color:red;*/
    }
	.thc{
        vertical-align:middle;
        text-align:center;
        height:30px;
        border-bottom:1px solid grey;
        font-size:14px;
    }
    
    .td{
        vertical-align:middle;
        height:28px;
        padding-left:15%;
        font-size:14px;
    }
	.tdx{
        vertical-align:middle;
        height:28px;
        padding-left:5%;
        font-size:14px;
    }
    
    
    .lasttd{
        text-align:center;
        border-top:1px solid black;
        border-right:1px solid black;
        border-bottom:1px solid black;
        border-left:1px solid black;
		font-size:13px;
    }
	.statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        padding:4px;
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
$student_info1 = array();
$student_info = get_student_info($student_id, $acd_yr);

foreach ($student_info as $row1):
    ?>
<html>
    
    <head>
        <meta charset="utf-16" />
    </head>
    <body>
    <div class="col-md-12 pdfdiv">
	<div class="col-md-2"></div>
	<div class="col-md-8  table-responsive bgimg" style="text-align:center;">
 		<div style="margin-left:7%;margin-top: 18%;">
				<h4 >ACADEMIC SESSION (<?php echo $row1['academic_yr']; ?>)</h4>
				<h3><font color="#000000">REPORT CARD</font></h3>
			</div>
		<table border="0" class="table-responsive" style="width:80%;margin-left:7%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 0%;" cellpadding="1" cellspacing="10">
			<tr> 
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:17px;width: 22%; padding-top: 6px; padding-bottom:6px;	word-wrap:break-word;">Student's Name : </td>
						<td style="font-size:16px;width: auto;text-align:center;"><div class="statistics_line"><?php echo $row1['first_name'] . ' ' . $row1['mid_name'] . ' ' . $row1['last_name']; ?></div> </td>
						<td style="font-size:17px;width: 1%;"></td>
						<td style="font-size:17px;width: 12%;margin-left: 10px;padding-top: 6px; padding-bottom:6px;word-wrap:break-word;">Roll No. : </td>
						<?php
                        $roll_no = '&nbsp;';
                        if ($row1['roll_no'] <> null) {
                            $roll_no = $row1['roll_no'];
                        }
                        ?>
						<td style="font-size:16px;width:10%;text-align:center;"><div class="statistics_line"> <?php echo $roll_no; ?></div></td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:17px;padding:5px;width: 50%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Mother's / Father's / Guardian's Name : </td>
                        <td style="font-size:16px;padding:5px;width: auto;text-align:center;"><div class="statistics_line"><?php echo get_parent_name($row1['parent_id']); ?></div></td>
                    </table>
                    
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:17px;padding:5px;width: 20%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Date of Birth : </td>
                        <td style="font-size:16px;width:20%;text-align:center;"><div class="statistics_line"><?php echo date_format(date_create($row1['dob']), 'd-m-Y'); ?></div></td>
                        <td style="font-size:17px;padding:5px;width: 25%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Class / Section : </td>
						<td style="font-size:16px;padding:5px;width: auto;text-align:center;"><div class="statistics_line"><?php echo get_class_name($row1['class_id']) . ' ' . get_section_name($row1['section_id']); ?></div></td>
                    </table>
                    
                </td>
                
            </tr>
		</table>
		<table style="width:82%;margin-left: 7%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			 <tr>
				 <td style="text-align:center;" cellpadding="0" cellspacing="0">
					<?php
                    $exam_list = get_published_exams_class9n10($row1['class_id'], $row1['section_id'], $row1['academic_yr']);
                    $count_of_exams = count($exam_list);
                    $count_of_exams_on_reportcard = 0;
                    ?>
					
					<table border="1" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <tr>
							<th class="col-md-3 col-sm-3 col-xs-3 th">Scholastic Areas</th>
							<?php
                            ${'general_highest_marks_array'} = array();
                            ${'count_of_mark_headings'} = 0;
                            $show_grand_total_column = false;

                            foreach ($exam_list as $exam) {
                                $exam_name = get_exam_name($exam->exam_id);
                                if (substr($exam_name, stripos($exam_name, 'Term 1'), 6) == 'Term 1')
                                    $exam_name = substr($exam_name, stripos($exam_name, 'Term 1'), 6);
                                if (substr($exam_name, stripos($exam_name, 'Term 2'), 6) == 'Term 2')
                                    $exam_name = substr($exam_name, stripos($exam_name, 'Term 2'), 6);

                                $exam_name = strtoupper($exam_name);
                                if ($exam_name == 'FINAL EXAM' || $exam_name == 'TERM 1' || $exam_name == 'TERM 2') {
                                    $count_of_exams_on_reportcard = $count_of_exams_on_reportcard + 1;
                                    ${'general_marks_resultarray'} = get_marks($exam->exam_id, $row1['class_id'], $row1['section_id'], 48, $row1['student_id'], $row1['academic_yr']);

                                    if (isset(${'general_marks_resultarray'}[0])) {
                                        ${'general_highest_marks_json'} = ${'general_marks_resultarray'}[0]['reportcard_highest_marks'];

                                        ${'general_highest_marks_array'} = array_merge(${'general_highest_marks_array'}, json_decode(${'general_highest_marks_json'}, true));

                                        ${'count_of_mark_headings'} = count(${'general_highest_marks_array'});
                                    }
                                }

                                if ($exam_name == 'TERM 1' || $exam_name == 'TERM 2') {
                                    $show_grand_total_column = true;
                                    ?>
								<th class="col-md-1 col-sm-1 col-xs-1  th1" colspan="<?php echo ${'count_of_mark_headings'} + 1; ?>"><?php echo $exam_name . ' (50 marks)'; ?></th>
						<?php
                                } elseif ($exam_name == 'FINAL EXAM') {
                        ?>	 
								<th class="col-md-1 col-sm-1 col-xs-1 th1" style="text-align:center;white-space:nowrap;" colspan="<?php echo ${'count_of_mark_headings'} + 1; ?>"><?php echo 'ACADEMIC YEAR (100 marks)'; ?></th>
						<?php
                                }
                            }
                            ?>
							<?php if ($show_grand_total_column == true) { ?>
								<th class="col-md-1 col-sm-1 col-xs-1 th1" style="text-align:center;white-space:nowrap;" ></th>
							<?php } ?>
							<th class="col-md-1 col-sm-1 col-xs-1 th1" style="text-align:center;white-space:nowrap;" ></th>
						</tr>		
						<tr>
							<th class="col-md-3 th" style="text-align:center;height:30px;">SUBJECT</th>
							<?php
                            // foreach($term_list as $term){
                            $final_grand_total_marks = 0;
                            $final_grand_highest_marks = 0;
                            $highest_total_marks = 0;
                            foreach ($exam_list as $exam) {
                                ${'grand_total_marks_' . $exam->exam_id} = 0;
                                ${'grand_highest_marks_' . $exam->exam_id} = 0;

                                $exam_name = get_exam_name($exam->exam_id);
                                if (substr($exam_name, stripos($exam_name, 'Term 1'), 6) == 'Term 1')
                                    $exam_name = substr($exam_name, stripos($exam_name, 'Term 1'), 6);
                                if (substr($exam_name, stripos($exam_name, 'Term 2'), 6) == 'Term 2')
                                    $exam_name = substr($exam_name, stripos($exam_name, 'Term 2'), 6);

                                $exam_name = strtoupper($exam_name);
                                if ($exam_name == 'FINAL EXAM' || $exam_name == 'TERM 1' || $exam_name == 'TERM 2') {
                                    ${'highest_total_marks_' . $exam->exam_id} = 0;
                                    if (isset(${'general_highest_marks_array'}) && ${'general_highest_marks_array'} <> null) {
                                        foreach (${'general_highest_marks_array'} as $key => $value) {
                                            ${'highest_total_marks_' . $exam->exam_id} = ${'highest_total_marks_' . $exam->exam_id} + (float) $value;
                                            ${'total_marks_' . $exam->exam_id . $key} = 0;
                                            ?> 
                                    <td class="col-md-1 mark_heading_td" style="text-align:center;height:30px;"><?php echo $key . '<br/>(' . $value . ')'; ?></td>
                         <?php
                                        }
                                        ?>
                                    <td class="col-md-1 mark_heading_td"  style="text-align:center;height:30px;">Total<br/>(<?php echo ${'highest_total_marks_' . $exam->exam_id}; ?>)</td>
                          <?php
                                    } else {
                                        ?>
								    <td class="col-md-1 mark_heading_td"  style="text-align:center;height:30px;" colspan="<?php echo ${'count_of_mark_headings'} + 2; ?>"></td>
							<?php
                                    }
                                    $highest_total_marks = $highest_total_marks + ${'highest_total_marks_' . $exam->exam_id};
                                }
                            }
                            if ($show_grand_total_column == true) {
                                ?>		
							<td class="col-md-1 col-sm-1 col-xs-1 mark_heading_td"  style="text-align:center;height:30px;">Grand Total (<?php echo $highest_total_marks; ?>)</td>
						<?php } ?>
						<td class="col-md-1 col-sm-1 col-xs-1 mark_heading_td" style="text-align:center;height:30px;">Grade</td>
					</tr>

						<?php
                        // Lija 05-03-26
                        $mark_heading_displayed = 'N';
                        $sub_list = get_scholastic_subject_for_which_marks_are_alloted_to_student($row1['student_id']);

                        foreach ($sub_list as $sub_row) {
                            ?>
						<tr>
                             <td class="col-md-1 td" style="text-align:center;height:30px;"> 
								<?php
                                echo $sub_row->name;
                                ?>
							</td>
							<?php
                            /*$mark_obtained_array=array();
                            $highest_marks_array=array();
                            $total_marks_obtained=0;
                            $highest_total_marks=0;*/
                            ${'subject_total_marks_obtained_' . $sub_row->sub_rc_master_id} = 0;
                            ${'subject_highest_total_marks_' . $sub_row->sub_rc_master_id} = 0;

                            if (isset($exam_list) && count($exam_list) > 0) {
                                foreach ($exam_list as $exam) {
                                    ${'mark_obtained_array_' . $exam->exam_id} = array();
                                    ${'highest_marks_array_' . $exam->exam_id} = array();
                                    ${'marks_obtained_json_' . $exam->exam_id} = '';
                                    ${'total_marks_obtained_' . $exam->exam_id} = 0;
                                    ${'highest_total_marks_' . $exam->exam_id} = 0;

                                    $exam_name = get_exam_name($exam->exam_id);
                                    if (substr($exam_name, stripos($exam_name, 'Term 1'), 6) == 'Term 1')
                                        $exam_name = substr($exam_name, stripos($exam_name, 'Term 1'), 6);
                                    if (substr($exam_name, stripos($exam_name, 'Term 2'), 6) == 'Term 2')
                                        $exam_name = substr($exam_name, stripos($exam_name, 'Term 2'), 6);

                                    $exam_name = strtoupper($exam_name);
                                    if ($exam_name == 'FINAL EXAM' || $exam_name == 'TERM 1' || $exam_name == 'TERM 2') {
                                        ${'marks_resultarray_' . $exam->exam_id} = get_marks($exam->exam_id, $row1['class_id'], $row1['section_id'], $sub_row->sub_rc_master_id, $row1['student_id'], $row1['academic_yr']);
                                        if (isset(${'marks_resultarray_' . $exam->exam_id}[0])) {
                                            if ($exam_name == 'FINAL EXAM')
                                                ${'grand_highest_marks_' . $exam->exam_id} = ${'grand_highest_marks_' . $exam->exam_id} + 100;  // As each subject total marks is of 100 marks
                                            elseif ($exam_name == 'TERM 1' || $exam_name == 'TERM 2')
                                                ${'grand_highest_marks_' . $exam->exam_id} = ${'grand_highest_marks_' . $exam->exam_id} + 50;  // As each subject total marks is of 50 marks

                                            ${'marks_obtained_json_' . $exam->exam_id} = ${'marks_resultarray_' . $exam->exam_id}[0]['reportcard_marks'];
                                            ${'mark_obtained_array_' . $exam->exam_id} = array_merge(${'mark_obtained_array_' . $exam->exam_id}, json_decode(${'marks_obtained_json_' . $exam->exam_id}, true));
                                            // var_dump(${'mark_obtained_array_'.$exam->exam_id});
                                            // echo "<br>";
                                            ${'highest_marks_json_' . $exam->exam_id} = ${'marks_resultarray_' . $exam->exam_id}[0]['reportcard_highest_marks'];
                                            ${'highest_marks_array_' . $exam->exam_id} = array_merge(${'highest_marks_array_' . $exam->exam_id}, json_decode(${'highest_marks_json_' . $exam->exam_id}, true));

                                            if (isset(${'mark_obtained_array_' . $exam->exam_id}) && ${'mark_obtained_array_' . $exam->exam_id} <> null) {
                                                if ($sub_row->name == 'Artificial Intelligence' && $row1['academic_yr'] == '2020-2021') {
                                                    // Put a blank <td> for Periodic Test. As AI doesnt hv Periodic Test as a mark heading
                                                    ?>
												<td class="col-md-1 td"  style="text-align:center;"></td>
				<?php
                                                }
                                                if (${'count_of_mark_headings'} == count(${'mark_obtained_array_' . $exam->exam_id})) {
                                                    // Lija 05-03-26 When count of marks heading is same
                                                    foreach (${'mark_obtained_array_' . $exam->exam_id} as $key => $value) {
                                                        ${'total_marks_obtained_' . $exam->exam_id} = ${'total_marks_obtained_' . $exam->exam_id} + (float) $value;
                                                        if (!(isset(${'total_marks_' . $exam->exam_id . $key})))
                                                            ${'total_marks_' . $exam->exam_id . $key} = 0;
                                                        ${'total_marks_' . $exam->exam_id . $key} = ${'total_marks_' . $exam->exam_id . $key} + (float) $value;
                                                        ${'highest_total_marks_' . $exam->exam_id} = ${'highest_total_marks_' . $exam->exam_id} + (float) ${'highest_marks_array_' . $exam->exam_id}[$key];
                                                        if ($sub_row->name == 'Artificial Intelligence' || $sub_row->name == 'Marathi') {
                                                            ?> 
    													<td class="col-md-1 tdx"  style="text-align:center;height:30px;"><?php echo $key . ' (' . ${'highest_marks_array_' . $exam->exam_id}[$key] . ')' . ' <br>' . $value; ?></td>
    									<?php } else { ?>				
    													<td class="col-md-1 td"  style="text-align:center;height:30px;"><?php echo $value; ?></td>
    												
    									<?php
                                                        }
                                                    }
                                                } else {
                                                    // Lija 05-03-26 When count of marks heading is not same
                                                    ?>
										        <td class="mark_heading_td" style="text-align:center;cellpadding:0;cellspacing:0;border:bottom 1px solid black;" colspan="<?php echo ${'count_of_mark_headings'}; ?>">
        											<table class="col-md-12 col-sm-12 col-xs-12" border="0" style="border: 0px ;cellpadding:0;cellspacing:0;" width="100%">
        											    <?php if ($mark_heading_displayed == 'N') { ?>
        												<tr>
        												<?php
                                                        $total_highest_marks = 0;

                                                        foreach (${'mark_obtained_array_' . $exam->exam_id} as $key => $value) {
                                                            if ($value <> 'Ab')  // Lija 21-03-23
                                                                $total_highest_marks = $total_highest_marks + (float) ${'highest_marks_array_' . $exam->exam_id}[$key];

                                                            ?>
        								                    
        													    <td class="mark_heading_td" style="vertical-align:center;text-align:center;width:25%"><?php echo $key . '(' . ${'highest_marks_array_' . $exam->exam_id}[$key] . ')'; ?></td>
        													
        								<?php
                                                        }
                                                        if ($mark_heading_displayed == 'N')
                                                            $mark_heading_displayed = 'Y';
                                                        ?>
        												</tr>
        								<?php } ?>
        												<tr>
        								<?php
                                        foreach (${'mark_obtained_array_' . $exam->exam_id} as $key => $value) {
                                            ${'total_marks_obtained_' . $exam->exam_id} = ${'total_marks_obtained_' . $exam->exam_id} + (float) $value;
                                            if (!(isset(${'total_marks_' . $exam->exam_id . $key})))
                                                ${'total_marks_' . $exam->exam_id . $key} = 0;
                                            ${'total_marks_' . $exam->exam_id . $key} = ${'total_marks_' . $exam->exam_id . $key} + (float) $value;
                                            ${'highest_total_marks_' . $exam->exam_id} = ${'highest_total_marks_' . $exam->exam_id} + (float) ${'highest_marks_array_' . $exam->exam_id}[$key];

                                            ?>
        													<td class="mark_heading_td" style="vertical-align:center;text-align:center;width:25%"><?php echo $value; ?></td>
        								<?php
                                        }
                                        ?>
        												</tr>
        											</table>
        										</td>
										<?php
                                                }
                                                ${'grand_total_marks_' . $exam->exam_id} = ${'grand_total_marks_' . $exam->exam_id} + ${'total_marks_obtained_' . $exam->exam_id};
                                                ?>
											<td class="col-md-1 td"  style="text-align:center;height:30px;"><?php echo ${'total_marks_obtained_' . $exam->exam_id} ?></td>

									<?php
                                            } else {
                                                ?>
								                <td class="col-md-1 td"  style="text-align:center;height:30px;" colspan="<?php echo ($count_of_mark_headings + 2); ?>"></td> 
								<?php
                                            }
                                        } else {
                                            for ($i = 0; $i < $count_of_mark_headings; $i++) {
                                ?>
											<td class="col-md-1 td"  style="text-align:center;height:30px;"></td>
									<?php
                                            }
                                            ?>
										<td class="col-md-1 td"  style="text-align:center;height:30px;"></td>
									<?php
                                        }
                                        ${'subject_total_marks_obtained_' . $sub_row->sub_rc_master_id} = ${'subject_total_marks_obtained_' . $sub_row->sub_rc_master_id} + ${'total_marks_obtained_' . $exam->exam_id};
                                        ${'subject_highest_total_marks_' . $sub_row->sub_rc_master_id} = ${'subject_highest_total_marks_' . $sub_row->sub_rc_master_id} + ${'highest_total_marks_' . $exam->exam_id};
                                    }
                                }
                                $final_grand_total_marks = $final_grand_total_marks + ${'subject_total_marks_obtained_' . $sub_row->sub_rc_master_id};
                                $final_grand_highest_marks = $final_grand_highest_marks + ${'subject_highest_total_marks_' . $sub_row->sub_rc_master_id};
                                if ($show_grand_total_column == true) {
                                    ?>
									<td class="col-md-1 col-sm-1 col-xs-1 td"  style="text-align:center;height:30px;"><?php echo ${'subject_total_marks_obtained_' . $sub_row->sub_rc_master_id} ?></td>
								<?php } ?>
								<td class="col-md-1 col-sm-1 col-xs-1 td"  style="text-align:center;height:30px;">
								<?php
                                if (${'subject_highest_total_marks_' . $sub_row->sub_rc_master_id} <> 0) {
                                    $total_marks_outof_100 = (${'subject_total_marks_obtained_' . $sub_row->sub_rc_master_id} * 100) / ${'subject_highest_total_marks_' . $sub_row->sub_rc_master_id};
                                    $final_grade = get_grade_based_on_marks($total_marks_outof_100, 'Scholastic', $row1['class_id']);
                                    echo $final_grade;
                                }
                                ?>
								</td>
							<?php
                            } else {
                            ?>
							   <td class="col-md-1 td" style="text-align:center;height:30px;" colspan="<?php echo ($count_of_mark_headings + 2); ?>"></td> 
					<?php
                            }
                            ?>
					</tr>
					<?php
                        }
                        ?>
					
					<tr>
                        <td class="td" style="text-align:center;height:30px;">TOTAL</td>
                        <?php
                        foreach ($exam_list as $exam) {
                            $exam_name = get_exam_name($exam->exam_id);
                            if (substr($exam_name, stripos($exam_name, 'Term 1'), 6) == 'Term 1')
                                $exam_name = substr($exam_name, stripos($exam_name, 'Term 1'), 6);
                            if (substr($exam_name, stripos($exam_name, 'Term 2'), 6) == 'Term 2')
                                $exam_name = substr($exam_name, stripos($exam_name, 'Term 2'), 6);

                            $exam_name = strtoupper($exam_name);
                            if ($exam_name == 'FINAL EXAM' || $exam_name == 'TERM 1' || $exam_name == 'TERM 2') {
                                if (isset(${'general_highest_marks_array'}) && ${'general_highest_marks_array'} <> null) {
                                    foreach (${'general_highest_marks_array'} as $key => $value) {
                                        ?>
										<td class="col-md-1 td"  style="text-align:center;height:30px;"><?php // echo ${'total_marks_'.$exam->exam_id.$key}; ?></td>
                        <?php
                                    }
                                    ?>
                            <td class="col-md-1 col-sm-1 col-xs-1 td"  style="text-align:center;height:30px;"><?php echo ${'grand_total_marks_' . $exam->exam_id} . '/' . ${'grand_highest_marks_' . $exam->exam_id}; ?></td>
						<?php
                                } else {
                        ?>
							   <td class="col-md-1 col-sm-1 col-xs-1 td" colspan="<?php echo ($count_of_mark_headings + 2); ?>" style="vertical-align:center;text-align:center;"></td> 
                        <?php
                                }
                            }
                        }
                        if ($show_grand_total_column == true) {
                            ?>
							<td class="col-md-1 col-sm-1 col-xs-1 td"  style="text-align:center;height:30px;"><?php echo $final_grand_total_marks . '/' . $final_grand_highest_marks; ?></td>
						<?php } ?>
						<?php
                        $grand_marks_per_100 = ($final_grand_total_marks * 100) / $final_grand_highest_marks;  // Convert to out of 100
                        $grand_grade = get_grade_based_on_marks(round($grand_marks_per_100), 'Scholastic', $row1['class_id']);  // Lija 19-03-21
                        ?>
                            <td class="col-md-1 col-sm-1 col-xs-1 td" style="text-align:center;height:30px;"><?php echo $grand_grade; ?></td>
                         
                    </tr>
						
				</table>
				</td>
			</tr>
		</table>
        <table class="table-responsive" style="width:84%;margin-left: 5%;margin-right: auto;border-spacing: 0px;background-color:white;">
			 <tr>
				 <td style="" cellpadding="0" cellspacing="0">
			<table class="table-responsive" style="width:90%;margin-left: 5%;margin-right: auto;border-spacing: 0px;background-color:white;border-size:0" cellpadding="0" cellspacing="0">
			 <tr>
				 <td style="vertical-align:center;" cellpadding="0" cellspacing="0">
                    <table border="1" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white; font-size:15px;border:1px solid black;" cellpadding="0" cellspacing="0">
                        <tr>
                           <th class="col-md-3 thc" colspan="<?php echo $count_of_exams_on_reportcard + 1; ?>">Co- Scholastic Areas Term 1 & 2<br/>(On a 3 point (A-C) grading scale)</th>
                        </tr>
                            <tr>
                                
                                <th class="col-md-2 th">Subjects</th>
								<?php
                                foreach ($exam_list as $exam) {
                                    $exam_name = $exam->name;
                                    if (substr($exam_name, stripos($exam_name, 'Term 1'), 6) == 'Term 1')
                                        $exam_name = substr($exam_name, stripos($exam_name, 'Term 1'), 6);
                                    if (substr($exam_name, stripos($exam_name, 'Term 2'), 6) == 'Term 2')
                                        $exam_name = substr($exam_name, stripos($exam_name, 'Term 2'), 6);

                                    $exam_name = strtoupper($exam_name);
                                    if ($exam_name == 'TERM 1' || $exam_name == 'TERM 2' || $exam_name == 'FINAL EXAM') {
                                        ?>
										<th class="col-md-1 col-sm-1 col-xs-1 th1"  width=""><?php echo $exam_name; ?></th>
							   <?php
                                    }
                                }
                                ?>
                                
                            </tr>
                             <?php
    $sub_list = get_coscholastic_subject_alloted_to_class($row1['class_id'], $row1['academic_yr']);

    foreach ($sub_list as $sub_row):
        ?>

                            <tr>
							<td class="col-md-2 td" style="text-align:center;height:30px;"> <?php echo $sub_row->name; ?></td>

                         <?php
        // Lija 30-09-20
        $mark_obtained_array = array();
        $coscholastic_grade = '';
        foreach ($exam_list as $exam) {
            $exam_name = $exam->name;
            if (substr($exam_name, stripos($exam_name, 'Term 1'), 6) == 'Term 1')
                $exam_name = substr($exam_name, stripos($exam_name, 'Term 1'), 6);
            if (substr($exam_name, stripos($exam_name, 'Term 2'), 6) == 'Term 2')
                $exam_name = substr($exam_name, stripos($exam_name, 'Term 2'), 6);

            $exam_name = strtoupper($exam_name);
            if ($exam_name == 'TERM 1' || $exam_name == 'TERM 2' || $exam_name == 'FINAL EXAM') {
                ${'total_marks_' . $exam->exam_id} = 0;
                ${'highest_total_marks_' . $exam->exam_id} = 0;

                $marks_resultarray = get_marks($exam->exam_id, $row1['class_id'], $row1['section_id'], $sub_row->sub_rc_master_id, $row1['student_id'], $row1['academic_yr']);

                if (isset($marks_resultarray[0])) {
                    ${'total_marks_' . $exam->exam_id} = $marks_resultarray[0]['total_marks'];
                    ${'highest_total_marks_' . $exam->exam_id} = $marks_resultarray[0]['highest_total_marks'];

                    $marks_obtained_json = $marks_resultarray[0]['reportcard_marks'];
                    $mark_obtained_array = array_merge($mark_obtained_array, json_decode($marks_obtained_json, true));

                    if (isset($mark_obtained_array) && $mark_obtained_array <> null) {
                        foreach ($mark_obtained_array as $key => $value) {
                            if ($value == 'Ab')
                                $coscholastic_grade = 'Ab';
                        }

                        if ($coscholastic_grade == 'Ab' && $total_marks == 0) {
                            // If student is absent for all mark headings of a subject
                            $coscholastic_grade = 'Ab';
                        } else {
                            // $sub_marks_per_100=(${'total_marks_'.$exam['exam_id']}*100)/${'highest_total_marks_'.$exam['exam_id']};  //Convert to out of 100
                            $coscholastic_grade = get_grade_based_on_marks(${'total_marks_' . $exam->exam_id}, 'Co-Scholastic', $row1['class_id']);
                        }
                    }
                }
                ?>
								<td class="col-md-1 td" style="text-align:center;height:30px;"><?php echo $coscholastic_grade; ?></td>
								<?php
            }
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
                 <td class="coscholastictable" style="vertical-align:top">
                    <table border="1" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 0%;" cellpadding="0" cellspacing="0">
                             <tr>
                                <th colspan="2" style="text-align:center;"> <div style="font-size:14px"> GRADING SCALE FOR SCHOLASTIC AREAS</div><div style="font-size:12px"> Grades are awarded on a 8 Point grading scale as follows</div></th>
                            </tr>
                            <tr>
                                <td class="lasttd"  style="">MARKS RANGE</td>
                                <td class="lasttd">GRADE</td>
                             </tr>
                             <tr>
                                <td class="lasttd">91-100</td>
                                <td class="lasttd">A1</td>
                             </tr>
                             <tr>
                                <td class="lasttd">81-90</td>
                                <td class="lasttd">A2</td>
                             </tr>
                             <tr>
                                <td class="lasttd">71-80</td>
                                <td class="lasttd">B1</td>
                             </tr>
                             <tr>
                                <td class="lasttd">61-70</td>
                                <td class="lasttd">B2</td>
                             </tr>
                             <tr>
                                <td class="lasttd">51-60</td>
                                <td class="lasttd">C1</td>
                             </tr>
                             <tr>
                                <td class="lasttd">41-50</td>
                                <td class="lasttd">C2</td>
                             </tr>
                             <tr>
                                <td class="lasttd">33-40</td>
                                <td class="lasttd">D</td>
                             </tr>
                              <tr>
                                <td class="lasttd">32 & below</td>
                                <td class="lasttd">E (Needs Improvement)</td>
                             </tr>
                         </table>
					</td>
				</tr>
			</table>
			<table class="table-responsive" style="width:82%;margin-left:7%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top:65%;" cellpadding="10" cellspacing="10" border="0">
			<tr> 
				<td >
				    <table class="table-responsive" style="width:100%;margin-left:auto;margin-right: auto;border-spacing: 0px;background-color:white;border:0">
                        <tr>
            				<td style="text-align:left;white-space:nowrap;width:20%" class="signtag"><b>Class Teacher's Remark :</b></td>
            				<td style="font-size:13px;">
                				<div class="statistics_line">
                				<?php
                                foreach ($exam_list as $exam) {
                                    $remark = get_reportcard_remark_of_a_student($row1['student_id'], $exam->exam_id);
                                    if ($remark <> '')
                                        echo $remark . '<br/>';
                                    else
                                        echo '&nbsp;';
                                }
                                ?>
                				</div>
            				</td>
            			</tr>
        		    </table>
				</td>
            </tr>
			<?php
            $date_from = getSettingsDataForAcademicYr($row1['academic_yr'])->academic_yr_from;

            $date_to = getSettingsDataForAcademicYr($row1['academic_yr'])->academic_yr_to;
            ?>
            <tr> 
                <td >
    				<table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;overflow: visible !important;" cellpadding="0" cellspacing="0">
    					<tr>
							<td style="text-align:left;white-space:nowrap;width: auto !important" class="signtag"><b> Attendance : </b></td>
							<td style="width:15%;margin-right:2%;text-align:center;"><div class="statistics_line">
								<?php
                                if (get_total_stu_attendance_till_a_month($row1['student_id'], $date_from, $date_to, $row1['academic_yr']) <> '') {
                                    echo get_total_stu_attendance_till_a_month($row1['student_id'], $date_from, $date_to, $row1['academic_yr']) . '/' . get_total_stu_workingday_till_a_month($row1['student_id'], $date_from, $date_to, $row1['academic_yr']);
                                }
                                ?>&nbsp;
								</div> 
							</td>
							<?php
                            $promote_to = '';
                            if (isset($exam->exam_id))
                                $promote_to = get_promote_to_of_a_student($row1['student_id'], $exam->exam_id);

                            ?>
							<td style="text-align:left;white-space:nowrap;width: auto !important" class="signtag"> <b>Promoted To : </b></td>
							<td style="width:15%;margin-right:2%;text-align:center;"><div class="statistics_line"><?php echo $promote_to; ?>&nbsp;</div> </td>
							<td style="text-align:left;white-space:nowrap;margin-left:2%" class="signtag"> &nbsp;&nbsp;<b>Date Of Reopening :</b></td>
							<td class="signtag" style="width:20%;text-align:center;">
								<div class="statistics_line">
									<?php
                                    $reopen_date = get_school_reopen_date($row1['class_id'], $row1['section_id']);
                                    if ($reopen_date <> NULL)
                                        echo date_format(date_create($reopen_date), 'd-m-Y');
                                    ?>
									&nbsp;
								</div>
							</td>
						</tr>
    				</table>
			 </td>
             
        </tr>
		</table>
		<table border="0" class="table-responsive" style="width:92%;margin-top:73%;margin-left:7%;margin-right: auto;border-spacing: 0px;background-color:white;overflow: visible !important;" cellpadding="1" cellspacing="10">
			<tr>
				<td style="width:35%;" >
					<table class="" width="90%" cellspacing="0" id="term" style="border: 0;text-align:center">
						<tr>
							<td class="signtag" >
								<div class="statistics_line">&nbsp;</div>
							</td>
						</tr>
						 
					</table>
				</td>
				<td style="width:35%;">
					<table class="" width="90%" cellspacing="0" id="term" style="border: 0;text-align:center">
						<tr>
							<td class="signtag" >
								<div class="statistics_line">&nbsp;</div>
							</td>
						</tr>
						 
					</table>
				</td>
				<td style="width:30%;">
					<table class="" width="100%" cellspacing="0" id="term" style="border: 0;text-align:center">
						<tr>
							<td class="signtag" >
								<div class="statistics_line">&nbsp;</div>
							</td>
						</tr>
						 
					</table>
				</td>
			</tr>
			<tr> 
				<td style="width:30%;">
					<table class="" width="90%" cellspacing="0" id="term" style="border: 0;">
						<tr>
							<td class="signtag" ><b>Class Teacher's Sign.</b></td>
						</tr>
					</table>
				</td>
				<td style="width:35%;">
					<table class="" width="90%" cellspacing="0" id="term" style="border: 0;">
						<tr>
							<td class="signtag" style=""><b>Parent's Sign.</b></td>
						</tr>
					</table>
				</td>
				<td style="width:35%;">
					<table class="" width="90%" cellspacing="0" id="term" style="border: 0;">
						<tr>
							<td class="signtag" style=""><b>Principal's Sign.</b></td>
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


