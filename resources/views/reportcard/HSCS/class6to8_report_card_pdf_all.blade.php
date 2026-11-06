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
    background-image: url('{{ str_replace('\\', '/', public_path('reportcard/HSCS/hscs_pmy.jpg')) }}');
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
		font-size:15px;
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
</head>
<body>
<?php
$gradeFor = static function ($mark, $type) use ($reportCardData) {
	if (!is_numeric($mark)) {
		return '';
	}
	foreach ($reportCardData['grade_scale'][$type] ?? [] as $range) {
		if ($mark >= $range['mark_from'] && $mark <= $range['mark_upto']) {
			return $range['name'];
		}
	}
	return '';
};
foreach ($reportCardData['students'] as $row1):
	$term_list = $reportCardData['term_list'];
	$student_marks = $reportCardData['marks_by_student'][$row1['student_id']] ?? [];
	$student_remarks = $reportCardData['remarks_by_student'][$row1['student_id']] ?? [];
	$student_attendance = $reportCardData['attendance_by_student'][$row1['student_id']] ?? ['present' => '', 'working' => ''];
	?>
<div class="col-md-12 pdfdiv">
<div class="col-md-2"></div>
	<div class="col-md-8 table-responsive bgimg" style="text-align:center;">
		<table border="0" style="width:90%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top:16%;">
			<tr>
				<td style="width:30%;text-align: left;font-size:14px;" >
	                UDISE No. - 27250504625
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
                        <td style="font-size:16px;width: 20%; padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">Student's Name : </td>
						<td style="font-size:16px;text-align: center;width: auto"><div class="statistics_line"><?php echo $row1['first_name'] . ' ' . $row1['mid_name'] . ' ' . $row1['last_name']; ?></div> </td>
						<td style="font-size:16px;width: 1%;"></td>
						<td style="font-size:16px;width: 15%;margin-left: 10px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">Roll No. : </td>
						<td style="font-size:16px;width: 8%;text-align: center;"><div class="statistics_line"> <?php echo $row1['roll_no']; ?></div></td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 38%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Mother's/ Father's/ Gaurdian's Name : </td>
                        <td style="font-size:16px;padding:5px;width: 42%;text-align: center;"><div class="statistics_line"><?php echo ($row1['father_name'] ?? ''); ?></div></td>
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
						<td style="width: auto;text-align: center;"><div class="statistics_line"><?php echo $row1['class_name'] . ' ' . $row1['sec_name']; ?></div></td>
                    </table>
                    
                </td>
                
            </tr>
		</table>
     <br>
		<table class="table-responsive" style="width:85%; margin-left: 5%; margin-right: auto; border-spacing: 0px; background-color:white;" cellpadding="0" cellspacing="0" >
			 <tr>
				 <td style="vertical-align:middle;text-align: center" cellpadding="0" cellspacing="0">
					<table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <tr>
							<?php
							$term_list = $reportCardData['term_list'];
							// print_r($term_list);
							?>
							<th class="col-md-3 col-sm-3 col-xs-3 th" style="word-wrap: break-word;font-size:10px"><b>Scholastic Areas</b></th>
							<?php
							//	$count_of_mark_headings=0;
							foreach ($term_list as $term) {
								$metadata = $reportCardData['term_metadata'][$term->term_id];
								${'general_highest_marks_array_' . $term->term_id} = $metadata['general_highest_marks_array'];
								${'count_of_mark_headings_' . $term->term_id} = count($metadata['general_highest_marks_array']);
								foreach ($metadata['count_of_mark_headings_by_exam'] as $examId => $headingCount) {
									${'count_of_mark_headings_' . $examId} = $headingCount;
								}
								?>
							 <th class="col-md-1 th1" style="text-align:center;height:30px;" colspan="<?php echo ${'count_of_mark_headings_' . $term->term_id} + 2; ?>"><?php echo $term->name; ?></th>
                         <?php
							}
							?>
						</tr>		
						<tr>
							<?php
							// $term_list	=	get_term($row1['academic_yr']);
							?>
                            <td class="col-md-3 td" style="text-align:center;height:30px;">SUBJECT</th>
							<?php

							foreach ($term_list as $term) {
								${'grand_total_marks ' . $term->term_id} = 0;
								${'grand_highest_marks_' . $term->term_id} = 0;

								$highest_total_marks = 0;
								if (isset(${'general_highest_marks_array_' . $term->term_id}) && ${'general_highest_marks_array_' . $term->term_id} <> null) {
									foreach (${'general_highest_marks_array_' . $term->term_id} as $key => $value) {
										if ($key == 'Pen Paper')
											$value = 5;
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
						$sub_list = $reportCardData['scholastic_subjects'];

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
								$exam_list = $reportCardData['term_metadata'][$term->term_id]['exam_list'];
								if (isset($exam_list) && count($exam_list) > 0) {
									foreach ($exam_list as $exam) {
										${'marks_resultarray_' . $term->term_id} = isset($student_marks[$term->term_id][$sub_row->sub_rc_master_id][$exam->exam_id]) ? [$student_marks[$term->term_id][$sub_row->sub_rc_master_id][$exam->exam_id]] : [];
										if (isset(${'marks_resultarray_' . $term->term_id}[0])) {
											${'marks_obtained_json_' . $term->term_id} = ${'marks_resultarray_' . $term->term_id}[0]['reportcard_marks'];
											${'highest_marks_json_' . $term->term_id} = ${'marks_resultarray_' . $term->term_id}[0]['reportcard_highest_marks'];  // Lija 18-03-22

											${'mark_obtained_array_' . $term->term_id} = ${'marks_obtained_json_' . $term->term_id};
											${'highest_marks_array_' . $term->term_id} = ${'highest_marks_json_' . $term->term_id};  // Lija 18-03-22

											if (isset(${'mark_obtained_array_' . $term->term_id}) && ${'mark_obtained_array_' . $term->term_id} <> null) {
												if (${'count_of_mark_headings_' . $exam->exam_id} == count(${'mark_obtained_array_' . $term->term_id})) {  // Lija 23-02-26 When count of marks heading is same

													foreach (${'mark_obtained_array_' . $term->term_id} as $key => $value) {
														if ($total_marks_obtained == '')
															$total_marks_obtained = 0;
														$total_marks_obtained += is_numeric($value) ? (float) $value : 0;
														// echo "value ".$value." ";
														${'total_marks_' . $term->term_id . $key} += is_numeric($value) ? (float) $value : 0;
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
												} else {
													// Lija 23-02-26 When count of marks heading is not same
													?>
    				                            <td style="text-align:center;cellpadding:0;cellspacing:0;border: 1px solid grey;" colspan="<?php echo ${'count_of_mark_headings_' . $exam->exam_id}; ?>">
        											<table class="col-md-12 col-sm-12 col-xs-12" border="0" style="border: 0px;cellpadding:0;cellspacing:0;" width="100%">
        												<tr>
        								<?php
										$total_highest_marks = 0;
										// var_dump(${'mark_obtained_array_'.$term['term_id']});
										foreach (${'mark_obtained_array_' . $term->term_id} as $key => $value) {
											if (is_numeric($value))  // Lija 21-03-23
												$total_highest_marks = $total_highest_marks + (float) ${'highest_marks_array_' . $term->term_id}[$key];

											?>
        													<td class="td" style="vertical-align:center;text-align:center;border: 0px;height:20px;"><?php echo $key . '(' . ${'highest_marks_array_' . $term->term_id}[$key] . ')'; ?></td>
        								<?php } ?>
        												</tr>
        												<tr>
        								<?php
										foreach (${'mark_obtained_array_' . $term->term_id} as $key => $value) {
											if (is_numeric($value))  // Lija 21-03-23
												$total_marks_obtained = (float) $total_marks_obtained + (float) $value;

											?>
        													<td class="td" style="vertical-align:center;text-align:center;border: 0px;height:20px;"><?php echo $value; ?></td>
        								<?php
										}
										?>
        												</tr>
        											</table>
        										</td>
						
								<?php
												}
											} else {
												?>
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
						<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;"><?php echo number_format((float) $total_marks_obtained, 1); ?></td>
									
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
								// $subject_total_marks_per_50=($total_marks_obtained*50)/$total_highest_marks;//Convert to out of 50
								$final_grade = $gradeFor(round($total_marks_obtained), 'Scholastic');
							}

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
                            <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white; font-size:15px;border: 1px solid grey !important;" cellpadding="0" cellspacing="0">
                                <?php
								$term_list = $reportCardData['term_list'];
								$colspan = count($term_list) + 1;
								?>
                                <tr>
                                    <th class="col-md-3 th" colspan="<?php echo $colspan; ?>">CO- SCHOLASTICS AREA (Graded on 5 point Scale)</th>
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
	$sub_list = $reportCardData['co_scholastic_subjects'];

	foreach ($sub_list as $sub_row):
		?>

                                <tr>
                             <td  class="col-md-1 td" style="text-align:center;height:30px;"> <?php echo $sub_row->name; ?></td>
                             
                             <?php
		foreach ($term_list as $term) {
			${'mark_obtained_array_' . $term->term_id} = array();
			$exam_list = $reportCardData['term_metadata'][$term->term_id]['exam_list'];
			$coscholastic_grade = '';
			foreach ($exam_list as $exam) {
				${'marks_resultarray_' . $term->term_id} = isset($student_marks[$term->term_id][$sub_row->sub_rc_master_id][$exam->exam_id]) ? [$student_marks[$term->term_id][$sub_row->sub_rc_master_id][$exam->exam_id]] : [];

				if (isset(${'marks_resultarray_' . $term->term_id}[0])) {
					${'marks_obtained_json_' . $term->term_id} = ${'marks_resultarray_' . $term->term_id}[0]['reportcard_marks'];
					${'mark_obtained_array_' . $term->term_id} = array_merge(${'mark_obtained_array_' . $term->term_id}, ${'marks_obtained_json_' . $term->term_id});

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
							$marks_per_50 = ${'coscholastic_highestmarks_' . $term->term_id} > 0 ? (${'coscholastic_marksobtained_' . $term->term_id} * 50) / ${'coscholastic_highestmarks_' . $term->term_id} : null;  // Convert to out of 50

							$coscholastic_grade = is_numeric($marks_per_50) ? $gradeFor(round($marks_per_50), 'Co-Scholastic') : '';
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
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd"  style="">MARKS RANGE</td>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">GRADE</td>
						 </tr>
						 <tr>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">91-100</td>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">A1</td>
						 </tr>
						 <tr>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">81-90</td>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">A2</td>
						 </tr>
						 <tr>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">71-80</td>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">B1</td>
						 </tr>
						 <tr>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">61-70</td>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">B2</td>
						 </tr>
						 <tr>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">51-60</td>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">C1</td>
						 </tr>
						 <tr>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">41-50</td>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">C2</td>
						 </tr>
						 <tr>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">33-40</td>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">D</td>
						 </tr>
						  <tr>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">32 & below</td>
							<td class="col-md-1 col-sm-1 col-xs-1 lasttd">E (Needs Improvement)</td>
						 </tr>
                     </table>
                 </td>
             </tr>
        </table>
			<table class="table-responsive" style="width:90%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;border: 0px solid red">
			<tr> 
			   <td>
			   <?php
	$remark_string = '';
	$width = '90%';
	foreach ($term_list as $term) {
		$remark = ($student_remarks[$term->term_id]['remark'] ?? '');
		if ($remark_string == '' && $remark <> '') {
			$remark_string = $remark_string . 'Term ' . $term->term_id . ' - ' . $remark;
		} elseif ($remark_string <> '' && $remark <> '') {
			$remark_string = $remark_string . '<br/> Term ' . $term->term_id . ' - ' . $remark;
		}
	}
	if ($remark_string == '') {
		$width = '80%';
	}
	?>
			       <table width="<?php echo $width; ?>" style="border: 0px solid green">
                        <tr>
            				<td style="font-size:14px;width: 30%;"><b>Class Teacher's Remark :</b></td>
            				<td style="font-size:12px;">
								<div class="statistics_line">
								<?php
								if ($remark_string <> '') {
									echo $remark_string;
								} else
									echo '<br>';

								?>
								</div>
            				</td>
            			</tr>
        		    </table>
        		</td>
        </tr>
		<?php
		$date_from = $reportCardData['date_from'];
		$date_to = $reportCardData['date_to'];
		?>
        <tr> 
            <td>
				<table class="table-responsive" border="0" width="<?php echo $width; ?>" style="margin-right: auto;border-spacing: 0px;background-color:white;border-color:blue;">
                    <tr>
						<td style="font-size:14px;text-align:left;white-space:nowrap;width: 10%" ><b> Attendance : </b></td>
    					<td style="font-size:14px;white-space:nowrap;width:10%!important;margin-right:2%;text-align:center;" ><div class="statistics_line">
						<?php
						if ($student_attendance['present'] <> '') {
							echo $student_attendance['present'] . '/' . $student_attendance['working'];
						}
						?>&nbsp;</div> 
						</td>
					<?php
					if (count($term_list) > 1) {
						$promote_to = ($student_remarks[$term_list[1]->term_id]['promot'] ?? '');
						// if($promote_to==''){
						?>
							    <td style="text-align:left;white-space:nowrap;width: 15% !important" class="signtag"><b> Promoted To : </b></td>
							<?php // }else{ ?>
							    <!--<td style="text-align:left;white-space:nowrap;width: 3% !important" class="signtag"></td>-->
							<?php // } ?>
							<td style="white-space:nowrap;width:20%;margin-right:2%;text-align:center;" class="signtag"><div class="statistics_line">
							<?php  // echo get_promoted_class_of_student($row1['student_id']);
							echo $promote_to;
							?>
						&nbsp;</div> </td>
                        <td style="text-align:center;font-size:14px;width:25%;"><b> Date Of Reopening :</b></td>
                        <td style="width:auto;text-align:center;font-size:14px" ><div class="statistics_line">
							<?php
							$reopen_date = $reportCardData['reopen_date'];
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
	    <table class="table-responsive" style="width:90%;margin-left:4%;margin-right: auto;border-spacing: 0px;background-color:white;overflow: visible !important;" cellpadding="1" cellspacing="10">
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
				<td style="width:30%;">
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
<?php endforeach; ?>
</body>
</html>