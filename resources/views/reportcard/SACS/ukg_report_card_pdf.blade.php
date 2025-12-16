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
		$student_info1 =array();	
		if(isset($class_id) && isset($section_id)){
	   
			$student_info1 =  get_students($class_id,$section_id,$academic_yr); 
		 
		}else {
			$student_info	= get_student_info($student_id,$academic_yr);
		}


		$slot = count($student_info1)/10;
		$slot_no = intval($slot); // 12
		$last_slot = explode('.', number_format($slot, 1))[1];
		$c= count($student_info1) - $last_slot;

		//print_r($student_info1[0]);
		if(isset($stud_count)){
			if($last_slot!=$stud_count){
				for($i=$stud_count-10;$i<$stud_count;$i++){
					$student_info[$i] =  $student_info1[$i];
				}
			}else{
				for($i=$c;$i<count($student_info1);$i++){
					$student_info[$i] =  $student_info1[$i];
				}
			   
		   }
		   
		}
		foreach($student_info as $row1):?>
<html>
    <head>
        <meta charset="utf-16" />
    </head>
    <body>
    <div class="col-md-12 pdfdiv">
<div class="col-md-2"></div>
	<div class="col-md-8  table-responsive bgimg" style="text-align:center;">
		<table class="table-responsive" style="width:85%;margin-left:auto;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 14%;" cellpadding="1" cellspacing="10">
			<tr>
			<td style="font-size:18px;border-left:2px solid black;border-right:2px solid black;border-bottom:2px solid black;border-top:2px solid black;">
				<table class="table-responsive col-md-12" border="0" width="100%" style="margin-top: 1%;margin-bottom: 1%;">
					 <tr> 
                         <td colspan="3" style="align:'left';padding-left:18px;" class="col-md-12" ><b>My Name : <u><?php echo $row1['first_name']." ".$row1['mid_name']." ".$row1['last_name'];?></u></b>
					   </td>
					   
					 </tr>
					 <tr>
						<td class="col-md-3" style="text-align:'left';padding-left:18px;">Roll No. : <u><?php echo $row1['roll_no'];?></u>
						</td>
						<td class="col-md-4" style="align:'left'">Std : <u><?php echo get_class_name($row1['class_id'])." ".get_section_name($row1['section_id']);?>&nbsp;
					   </u>
					   </td>
						<td class="col-md-5" style="text-align:'left'">Academic Year : <u><?php echo $row1['academic_yr'];?>
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
							$term_list= get_published_terms($row1['class_id'],$row1['section_id']);
							foreach($term_list as $term){						
								$exam_list	=	get_exams_by_class_per_term($row1['class_id'],$term->term_id,$row1['academic_yr']);?>
								
								<?php 
								foreach($exam_list as $exam):
										${'total_marks_'.$exam->exam_id}	=	0;
										${'highest_total_marks_'.$exam->exam_id}	=	0;
								?> 
								<th class="col-md-1 th1"  width="";><?php echo $exam->name;?></th>
								 <?php 
								endforeach;
							}
							?>
						</tr>
						<tbody>
							<?php 
							
							$sub_list = get_subjects_by_class($row1['class_id'],$row1['academic_yr']);
							foreach($sub_list as $rw){?>
							   <tr>
									<td  class="td2"><b>
										<?php echo $rw->name;?></b>
									</td>
							 <?php  //$exam_list	=	$this->assessment_model->get_exams($row1['academic_yr']);
								foreach($term_list as $term){						
									$exam_list	=	get_exams_by_class_per_term($row1['class_id'],$term->term_id,$row1['academic_yr']);
									foreach($exam_list as $exam){?>
										
										<?php 
										//$mark_obtained_array=array();
										${'mark_obtained_array_'.$exam->exam_id}=array();
										$highest_mark_of_a_markheading=0; //Lija 30-09-22
										
										$marks_resultarray=get_marks($exam->exam_id,$row1['class_id'],$row1['section_id'],$rw->sub_rc_master_id,$row1['student_id'],$row1['academic_yr']);
										//$mark_headings = $this->assessment_model->get_marks_headings_name_by_class_and_subject($row1['class_id'],$rw['sub_rc_master_id'],$row1['academic_yr']);
										if(isset($marks_resultarray[0])){
											
											${'marks_obtained_json_'.$exam->exam_id}=$marks_resultarray[0]['reportcard_marks'];
											${'highest_marks_json_'.$exam->exam_id}=$marks_resultarray[0]['reportcard_highest_marks'];//Lija 30-09-22
											//$mark_obtained_array=json_decode($marks_obtained_json,true);
											${'mark_obtained_array_'.$exam->exam_id}=json_decode(${'marks_obtained_json_'.$exam->exam_id},true);
											${'highest_marks_array_'.$exam->exam_id}=json_decode(${'highest_marks_json_'.$exam->exam_id},true);//Lija 30-09-22
											
											if($rw->subject_type =='Scholastic'){//Lija 30-09-22
												${'total_marks_'.$exam->exam_id}	=${'total_marks_'.$exam->exam_id}+(float)$marks_resultarray[0]['total_marks'];
												${'highest_total_marks_'.$exam->exam_id}	=${'highest_total_marks_'.$exam->exam_id}+(float)$marks_resultarray[0]['highest_total_marks'];
											}//Lija 30-09-22
											//echo ${'total_marks_'.$exam['exam_id']}."<br/>";
											//echo ${'highest_total_marks_'.$exam['exam_id']}."<br/>";
											
										} else{
											//$marks_obtained_json ='';
											//$mark_obtained_array = array();
											$marks_headings = get_marks_heading_class($row1['class_id'],$rw->sub_rc_master_id,$exam->exam_id,$row1['academic_yr']);
											${'marks_obtained_json_'.$exam->exam_id}="{";
											foreach($marks_headings as $mrow){
												${'marks_obtained_json_'.$exam->exam_id}=${'marks_obtained_json_'.$exam->exam_id}.'"'.$mrow->marks_headings_name.'":"",';
											}
											${'marks_obtained_json_'.$exam->exam_id}=rtrim(${'marks_obtained_json_'.$exam->exam_id},","); //Lija report card
											${'marks_obtained_json_'.$exam->exam_id}=${'marks_obtained_json_'.$exam->exam_id}."}";
											${'mark_obtained_array_'.$exam->exam_id}=json_decode(${'marks_obtained_json_'.$exam->exam_id},true);
										}

										if(count(${'mark_obtained_array_'.$exam->exam_id}) == 1){
											foreach(${'mark_obtained_array_'.$exam->exam_id} as $key => $value) { 
												$marks_headings_name=$key;
												$marks_obtained=$value;
												
												$highest_mark_of_a_markheading=${'highest_marks_array_'.$exam->exam_id}[$key] ?? ''; //30-09-22

												if($key=='Term'){?>
													<td style="text-align:center;margin-left: 30%;" class="td1"> 
													<?php
														if($value=='Ab'){
															echo "<font color='red'>Ab</font>";
														}else{
															if($value<>""){
																$value_per_100=($value*100)/$highest_mark_of_a_markheading;//Convert to out of 100 Lija 30-09-22
																$grade = get_grade_based_on_marks($value_per_100,$rw->subject_type,$row1['class_id']); //Lija for report card$value; 
																echo $grade."<br/>";
															}else{
																echo "<br/>";
															}
															//echo ${'highest_total_marks_'.$exam['exam_id']};
														}
														?>
													</td>
											  <?php }else{?>
														<td class="td1">&nbsp;</td>
											  <?php	}
											}
										}else{?>  
											 <td class="td1">&nbsp;</td>
										<?php	
										}
									} 
								}?>
                   
						</tr>		  
						<?php 
						$mark_headings = get_marks_headings_name_by_class_and_subject($row1['class_id'],$rw->sub_rc_master_id,$row1['academic_yr']);
						foreach($mark_headings as $mh_row){
							$marks_headings_name=$mh_row->name;
							if( $marks_headings_name!='Term'){	
										
						?>
							<tr>
									<td class="td"> <?php echo $marks_headings_name ;?></td>
							<?php 
							foreach($term_list as $term){						
								$exam_list	=	get_exams_by_class_per_term($row1['class_id'],$term->term_id,$row1['academic_yr']);
								foreach($exam_list as $exam){
							    if(isset(${'mark_obtained_array_'.$exam->exam_id}[$marks_headings_name]))
										$marks_obtained=${'mark_obtained_array_'.$exam->exam_id}[$marks_headings_name];
									else
										$marks_obtained="";
									
									$highest_mark_of_a_markheading=${'highest_marks_array_'.$exam->exam_id}[$marks_headings_name] ?? 0; //30-09-22
								?>
								
								
									<td style="text-align:center;margin-left: 30%;" class="td1">
									<?php 
										if($marks_obtained=='Ab'){
											echo "<font color='red'>Ab</font>";
										}else{
											//echo $marks_obtained."<br>";
											if($marks_obtained<>""){
												$value_per_100=($marks_obtained*100)/$highest_mark_of_a_markheading;//Convert to out of 100 Lija 30-09-22
												$grade = get_grade_based_on_marks($value_per_100,$rw->subject_type,$row1['class_id']); //Lija for report card$value; 
												
											}else{
												$grade ="<br>";
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
                    } //sub list ends here?>
							
							
							<tr>
								<td class="td2"><b>GRADE</b></td>
                                <?php 
								foreach($term_list as $term){						
									$exam_list	=	get_exams_by_class_per_term($row1['class_id'],$term->term_id,$row1['academic_yr']);
									foreach($exam_list as $exam){ 	
									if(${'highest_total_marks_'.$exam->exam_id}<>0){
									?>
										<td class="td1">
										<?php 
											//echo ${'total_marks_'.$exam['exam_id']}."/".${'highest_total_marks_'.$exam['exam_id']}."<br>";
											$percent=round((${'total_marks_'.$exam->exam_id}*100)/${'highest_total_marks_'.$exam->exam_id});
											$final_grade = get_grade_based_on_marks($percent,'Scholastic',$row1['class_id']); 
											echo $final_grade;
										?>
										</td>
									<?php
									}else{
									?>
										<td class="td1"></td>
                                <?php 
									}
								  }
								  }?>
							</tr>
							<tr>
								<td class="td2"><b>ATTENDANCE</b></td>
								<?php   
									foreach($term_list as $term){						
									$exam_list	=	get_exams_by_class_per_term($row1['class_id'],$term->term_id,$row1['academic_yr']);
										for($i=0;$i<count($exam_list);$i++){ ?>
											<td class="td1">
											<?php
											if($term->term_id==1){
												$date_from= getSettingsDataForAcademicYr($row1['academic_yr'])->academic_yr_from;
												$date_to=date_format(date_create(substr($date_from,0,4)."-09-30") , 'Y-m-d') ; // Creating date to as last day of sep;
											}elseif($term->term_id==2){
												$date_from=date_format(date_create(substr($date_from,0,4)."-10-01") , 'Y-m-d') ; // Creating date to as first day of Oct;
												$date_to=getSettingsDataForAcademicYr($row1['academic_yr'])->academic_yr_to;
											}
											if(get_total_stu_attendance_till_a_month($row1['student_id'],$date_from,$date_to,$row1['academic_yr'])<>""){
												echo get_total_stu_attendance_till_a_month($row1['student_id'],$date_from,$date_to,$row1['academic_yr'])."/".get_total_stu_workingday_till_a_month($row1['student_id'],$date_from,$date_to,$row1['academic_yr']);
											}
											?>
											</td>
										<?php }
								  }
										?>
							</tr>
							<tr>
								<td class="td2"><b>Class Teacher's Remark</b></td>
								<?php  
									foreach($term_list as $term){						
									$exam_list	=	get_exams_by_class_per_term($row1['class_id'],$term->term_id,$row1['academic_yr']);
										for($i=0;$i<count($exam_list);$i++){ ?>
										<td class="td1">
											<?php echo get_reportcard_remark_of_a_student($row1['student_id'],$term->term_id);?>
										</td>
										<?php }
									}?>
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
					 if(count($term_list)>1){
					?>
					<table class="table-responsive" style="width:80%;margin-left:4%;margin-right: 4%;border-spacing: 0px;background-color:white;" cellpadding="1" cellspacing="10">
						<tr>
							<td>
								<table class="table-responsive" style="width:100%;margin-top:2%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
									<td style="font-size:12px;text-align:left;white-space:nowrap;width:10%"> <b>Promoted To : </b></td>
									<?php
										$promote_to ="";
										if(isset($term_list[1]->term_id))
											$promote_to = get_promote_to_of_a_student($row1['student_id'],$term_list[1]->term_id);
										
									?>
									<td style="width:30%;font-size:12px;"><div class="statistics_line"><?php echo $promote_to;?></div> </td>
									<td style="font-size:12px;text-align:left;white-space:nowrap;width:15%;"> &nbsp;&nbsp;<b>Date Of Reopening :</b></td>
									<td style="width:30%;font-size:12px;">
										<div class="statistics_line">
											<?php 
												$reopen_date=get_school_reopen_date($row1['class_id'],$row1['section_id']);
												if($reopen_date<>NULL && $reopen_date<>'0000-00-00')
													echo date_format(date_create($reopen_date),'d-m-Y');
											?>
										</div></td>
								</table>

							</td>

						</tr>
					</table>
                    <?php }?> 
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
<?php endforeach;?>
</head>
<body>