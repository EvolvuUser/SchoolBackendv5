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
    background-image:url('{{ str_replace('\\', '/', public_path('reportcard/JPS/reportcard.png')) }}');
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
    .pdfdiv + .pdfdiv {
        page-break-before: always;
    }
    .scholastic-table {
        width: 88%;
        margin-left: 6%;
        border-collapse: collapse;
        table-layout: fixed;
        background-color: white;
    }
    .scholastic-table th, .scholastic-table td {
        text-align: center;
        vertical-align: middle;
        padding: 4px 2px;
        overflow-wrap: break-word;
        word-wrap: break-word;
        font-weight: normal;
    }
    .scholastic-table th:first-child, .scholastic-table td:first-child {
        width: 17%;
    }
    .scholastic-table .th, .scholastic-table .th1 {
        font-weight: bold;
    }
</style>
</head>
<body>
<?php
// Bulk reports supply the students; single reports resolve only the requested student.
if (!isset($student_info)) {
    $student_info = get_student_info($student_id, $academic_yr);
}
foreach ($student_info as $row1):
    ?>
    <div class="col-md-12 pdfdiv">
<div class="col-md-2"></div>
	<div class="col-md-8 table-responsive bgimg" style="text-align:center;">
		<table border="0" style="width:85%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 21%;">
			<tr>
				<td style="width:30%;text-align: left;font-size:14px;" >
					UDISE No. - 27251207823
				</td>
				<td style="width:40%;text-align: center;">
					<h4 >ACADEMIC SESSION <?php echo $row1['academic_yr']; ?></h4>
					<h3><font color="#000000">REPORT CARD</font></h3>
				</td>
				<td style="width:30%;text-align: left;font-size:14px;margin-left: 30px;" >
					
				</td>
			</tr>
		</table>
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
                        <td style="font-size:15px;padding:5px;width: 37%;text-align: center;"><div class="statistics_line"><?php echo get_parent_name($row1['parent_id']); ?></div></td>
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
						<td style="font-size:15px;width: auto;text-align: center;"><div class="statistics_line"><?php echo get_class_name($row1['class_id']) . ' ' . get_section_name($row1['section_id']); ?></div></td>
                    </table>
                    
                </td>
                
            </tr>
		</table>
		@include('reportcard.JPS.partials.scholastic')
        <table class="table-responsive" style="width:85%;margin-left: 5%;margin-right: auto;border-spacing: 0px;background-color:white;">
			 <tr>
				 <td style="" cellpadding="0" cellspacing="0">
                    <table class="table-responsive" style="width:auto;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			 <tr>
				
                            <td class="" style="vertical-align:middle;" cellpadding="0" cellspacing="0">
                            <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white; font-size:15px;border: 1px solid grey !important;" cellpadding="0" cellspacing="0">
                                <?php
                                $term_list = get_published_terms($row1['class_id'], $row1['section_id']);
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
    $sub_list = get_coscholastic_subject_alloted_to_class($row1['class_id'], $row1['academic_yr']);

    foreach ($sub_list as $sub_row):
        $acd_yr_frm = substr($row1['academic_yr'], 0, 4);
        // Music was added in 2023-2024 Term 1 but by Term 2 it was told to remove from reportcard
        if ($acd_yr_frm == 2023 && $sub_row->name == 'Music') {
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
            $exam_list = get_exams_by_class_per_term($row1['class_id'], $term->term_id, $row1['academic_yr']);
            $coscholastic_grade = '';
            foreach ($exam_list as $exam) {
                ${'marks_resultarray_' . $term->term_id} = get_marks($exam->exam_id, $row1['class_id'], $row1['section_id'], $sub_row->sub_rc_master_id, $row1['student_id'], $row1['academic_yr']);

                if (isset(${'marks_resultarray_' . $term->term_id}[0])) {
                    ${'marks_obtained_json_' . $term->term_id} = ${'marks_resultarray_' . $term->term_id}[0]['reportcard_marks'];
                    ${'mark_obtained_array_' . $term->term_id} = array_merge(${'mark_obtained_array_' . $term->term_id}, json_decode(${'marks_obtained_json_' . $term->term_id}, true));

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
                        } elseif (${'coscholastic_highestmarks_' . $term->term_id} > 0) {
                            // Convert co-scholastic marks to out of 50 as for some subjects like computer it is out of 25 n for others it is out of 50
                            $marks_per_50 = (${'coscholastic_marksobtained_' . $term->term_id} * 50) / ${'coscholastic_highestmarks_' . $term->term_id};  // Convert to out of 50
                            if ($sub_row->sub_rc_master_id == 8 && $term->term_id == 2 && $row1['academic_yr'] == '2020-2021' && $marks_per_50 <= 30) {
                                // Lija 13-03-21 Art/craft if marks is less than 30 give C grade.
                                $coscholastic_grade = 'C';
                            } else {
                                $coscholastic_grade = get_grade_based_on_marks(round($marks_per_50), 'Co-Scholastic', $row1['class_id']);
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
                     @php
    $scholasticGradeScale = \Illuminate\Support\Facades\DB::table('grade')
        ->where('class_id', $row1['class_id'])
        ->where('subject_type', 'Scholastic')
        ->orderByDesc('mark_from')
        ->get();
@endphp
                    <table
    border="1"
    class="table-responsive"
    style="width:auto;margin-left:3%;margin-right:auto;border-spacing:0;background-color:white;"
    cellpadding="0"
    cellspacing="0"
>
    <tr>
        <th
            class="col-md-3 lasttd"
            colspan="2"
            style="text-align:center;font-size:15px;"
        >
            GRADING SCALE FOR SCHOLASTIC AREAS

            @if (count($scholasticGradeScale) > 0)
                <br>
                Grades are awarded on a
                {{ count($scholasticGradeScale) }} Point grading scale as follows
            @endif
        </th>
    </tr>

    <tr>
        <th class="col-md-1 lasttd">MARKS RANGE</th>
        <th class="col-md-1 lasttd">GRADE</th>
    </tr>

    @forelse ($scholasticGradeScale as $gradeRow)
    <tr>
        <td class="col-md-1 lasttd">
            {{ $gradeRow->mark_from + 0 }}–{{ $gradeRow->mark_upto + 0 }}
        </td>

        <td class="col-md-1 lasttd">
            {{ $gradeRow->name }}
        </td>
    </tr>
@empty
    <tr>
        <td colspan="2" class="lasttd">
            Grading scale is not configured.
        </td>
    </tr>
@endforelse
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
        $remark = get_reportcard_remark_of_a_student($row1['student_id'], $term->term_id);
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
            $date_from = getSettingsDataForAcademicYr($row1['academic_yr'])->academic_yr_from;
            $date_to = date_format(date_create(substr($date_from, 0, 4) . '-09-30'), 'Y-m-d');  // Creating date to as last day of sep;
        } elseif (count($term_list) == 2) {
            // $date_from=date_format(date_create(substr($date_from,0,4)."-10-01") , 'Y-m-d') ; // Creating date from as first day of Oct;
            $date_from = getSettingsDataForAcademicYr($row1['academic_yr'])->academic_yr_from;  // Creating date from as first day of acd yr;
            $date_to = getSettingsDataForAcademicYr($row1['academic_yr'])->academic_yr_to;
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
                        if (get_total_stu_attendance_till_a_month($row1['student_id'], $date_from, $date_to, $row1['academic_yr']) <> '') {
                            echo get_total_stu_attendance_till_a_month($row1['student_id'], $date_from, $date_to, $row1['academic_yr']) . '/' . get_total_stu_workingday_till_a_month($row1['student_id'], $date_from, $date_to, $row1['academic_yr']);
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
                            $promote_to = get_promote_to_of_a_student($row1['student_id'], $term_list[1]->term_id);

                        ?>
                        <td style="text-align:center;font-size:14px;width:7%!important;">
						<div class="statistics_line"><?php echo $promote_to; ?>&nbsp;</div> </td>
                        <td style="text-align:center;font-size:14px;width:15%;"><b> Date Of Reopening :</b></td>
                        <td style="width:auto;text-align:center;font-size:14px" ><div class="statistics_line">
							<?php
                            $reopen_date = get_school_reopen_date($row1['class_id'], $row1['section_id']);
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
<?php endforeach; ?>
</body>
</html>
