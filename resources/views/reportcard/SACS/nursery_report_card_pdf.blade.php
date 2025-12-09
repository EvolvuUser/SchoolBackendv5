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
<?php   $student_info1 =array();	
		if(isset($class_id) && isset($section_id)){
	   
			$student_info1 = $this->crud_model->get_students($class_id,$section_id,$this->session->userdata['acd_yr']); 
		 
		}else {
			$student_info	=	get_student_info($student_id,$academic_yr);
		
		}
        // 	dd($student_info);

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

		foreach($student_info as $row1):
			$section_name= DB::table('section')->where('section_id',$row1['section_id'])->value('name');
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
                         <td colspan="3" style="align:'left;'" class="col-md-12" ><b>My Name : <u><?php echo $row1['first_name']." ".$row1['mid_name']." ".$row1['last_name'];?></u></b>
					   </td>
					   
					 </tr>
					 <tr>
						<td class="col-md-3" style="text-align:'left'">Roll No. : <u><?php echo $row1['roll_no'];?></u>
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
     <br>
     <table class="table-responsive" style="width:70%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;margin-top:-1%;" cellpadding="0" cellspacing="0">
         <tr>
             <td style="vertical-align:center;border-top:2px solid black;border-left:2px solid black;border-right:2px solid black;border-bottom:2px solid black" cellpadding="0" cellspacing="0">
                 <table class="table-responsive" style="border-spacing: 0px;background-color:white;" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        
						<?php  $term_list	=	get_published_terms($row1['class_id'],$row1['section_id']);	?>
						<th class="th"> </th>
						<?php foreach($term_list as $term):?> 
							<th class="col-md-2 th1">
							    <?php 
							    if($row1['academic_yr']=='2021-2022')
							        echo "Final Term";
							    else
							        echo $term->name;?>
                            </th>
						<?php endforeach;
					?>
					</tr>
                    <tbody>
                    <?php 
					$sub_list = get_subjects_by_class($row1['class_id'],$row1['academic_yr']);
					foreach($sub_list as $rw){?>
                        <tr>
							<td  class="" style="text-align:left;height:25px;border-top:1px solid grey;border-left:1px solid grey;border-right:1px solid grey;border-bottom:1px solid grey;text-transform: uppercase;color:#0000A0;background-color:silver;font-size:18px;padding-left:30px;"><b> <?php echo $rw->name;?></b>
							</td>
                        <?php 
						foreach($term_list as $term){
						  $row1 = (object) $row1;
							$exam_list	=	get_exams_by_class_per_term($row1->class_id,$term->term_id,$row1->academic_yr);
							if(count($exam_list)>0){
								foreach($exam_list as $exam){?>
									<?php $marks_resultarray=get_marks($exam->exam_id,$row1->class_id,$row1->section_id,$rw->sub_rc_master_id,$row1->student_id,$row1->academic_yr);
							     	// 	 dd($marks_resultarray);
									$mark_headings = get_marks_headings_name_by_class_and_subject($row1->class_id,$rw->sub_rc_master_id,$row1->academic_yr);
								// 	dd($mark_headings);
									if(count($marks_resultarray)>0){ 
										$marks_obtained_json=$marks_resultarray[0]['reportcard_marks'];
										$mark_obtained_array=json_decode($marks_obtained_json,true);
									} else{
										$marks_obtained_json ='';
										$mark_obtained_array = array();
									}
									//print_r(count($mark_obtained_array));
									if(count($mark_obtained_array) == 1){
										foreach($mark_obtained_array as $key => $value) { 
											if($key=='Term'){?>
												<td class="imagetd"> 
													<?php for($i=1;$i<=$value;$i++){?>
													<img src="https://sms.evolvu.in/public/reportcard/SACS/Plain_Yellow_Star.jpg" style="width:25px;height:20px">
													<?php } 
													//If marks obtained is 0 show # Lija 13-10-22
													if($value==0)
														echo "<font size='5'>#</font>";
													?>
												</td>
									<?php  }
										}
									} else{ ?>
										<td class="emptytd" style=""></td>
								   <?php 
								   }
								   
								   
								}
							}else{
							?>
								<td class="emptytd" style=""></td>
							<?php
							}
						}?>
                   
						</tr>
                    
        <?php  
				$mark_headings = get_marks_headings_name_by_class_and_subject($row1->class_id,$rw->sub_rc_master_id,$row1->academic_yr);
				foreach($mark_headings as $mh_row){
					if($mh_row->name !='Term'){
				?>
						<tr>
							<td  class="" style="text-align:left;height:20px;border-top:1px solid grey;border-left:1px solid grey;border-right:1px solid grey;border-bottom:1px solid grey;text-transform: uppercase;color:#0000A0;padding-left:50px;padding-top: 8px;"><b> <?php echo $mh_row->name;?></b>
							</td>
                        <?php 
						foreach($term_list as $term){
							$exam_list	=	get_exams_by_class_per_term($row1->class_id,$term->term_id,$row1->academic_yr);
							if(count($exam_list)>0){
								foreach($exam_list as $exam){
									$marks_resultarray=get_marks($exam->exam_id,$row1->class_id,$row1->section_id,$rw->sub_rc_master_id,$row1->student_id,$row1->academic_yr);
									if(count($marks_resultarray)>0){
										$marks_obtained_json=$marks_resultarray[0]['reportcard_marks'];
										$mark_obtained_array=json_decode($marks_obtained_json,true);
									}else{
										$marks_obtained_json='';
										$mark_obtained_array=array();
									}
									if(count($mark_obtained_array)==0){
										$marks_obtained='';
										$total_marks_obtained='';
										$marks_exists="N";
									}else{
										$marks_headings_id=$mh_row->name;
										if (array_key_exists($marks_headings_id,$mark_obtained_array)){
											$marks_obtained=$mark_obtained_array[$marks_headings_id];
											$marks_exists="Y";
										}else{
											$marks_obtained='';
											$marks_exists="N";
										}
									}
									if($mh_row->name !='Term'){?>
										<td class="imagetd">
											<?php for($k=1;$k<=$marks_obtained;$k++){?> 
												<img src="https://sms.evolvu.in/public/reportcard/SACS/Plain_Yellow_Star.jpg" style="width:25px;height:20px"> 
											<?php }
											//If marks obtained is 0 show # Lija 13-10-22
											if($marks_obtained==0 && $marks_exists=="Y")
												echo "<font size='5'>#</font>";
											?>
										</td>
							   <?php 
									} 
								}
							}else{
							?>
							<td class="emptytd" style=""></td>
						<?php
							}
						}?>
						</tr>
					<?php
						}
					}
					?>
                  
                <?php 
                    } //sub list ends here?>
					
					<tr>
						<td  class="td4"><b>Attendance</b></td>
						<?php   
							foreach($term_list as $term){						
?>
									<td class="td2" style="text-align:center;font-size:13px">
									<?php
										if($term->term_id ==1){
											$date_from=getSettingsDataForAcademicYr($row1->academic_yr)->academic_yr_from;
											$date_to=date_format(date_create(substr($date_from,0,4)."-09-30") , 'Y-m-d') ; // Creating date to as last day of sep;
										}elseif($term->term_id ==2){
											$date_from=date_format(date_create(substr($date_from,0,4)."-10-01") , 'Y-m-d') ; // Creating date to as first day of Oct;
											$date_to=getSettingsDataForAcademicYr($row1['academic_yr'])->academic_yr_to;
										}
										if(get_total_stu_attendance_till_a_month($row1->student_id,$date_from,$date_to,$row1->academic_yr)<>""){
											echo get_total_stu_attendance_till_a_month($row1->student_id,$date_from,$date_to,$row1->academic_yr)."/".get_total_stu_workingday_till_a_month($row1->student_id,$date_from,$date_to,$row1->academic_yr);
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
						foreach($term_list as $term){ ?>
							<td class="col-md-1 td2" style="text-align:center;font-size:13px"><?php echo get_reportcard_remark_of_a_student($row1->student_id,$term->term_id);?></td>
						<?php }?>
					</tr>
                    <!--tr>
                        <td  class="td4"><b>ATTENDANCE</b></td>
						<?php 
						//foreach($term_list as $term){ ?>
							<td class="td2"></td>
						<?php //}?>
                    </tr-->
					<?php
					if(count($term_list)>1){
					?>
					<tr>
                        <td  class="signtd" style=""><b>Promoted To</b></td>
                         <?php 
							$promote_to ="";
							if(isset($term_list[1]['term_id']))
								$promote_to = $this->assessment_model->get_promote_to_of_a_student($row1['student_id'],$term_list[1]['term_id']);
						?>
							<td class="td2" colspan="2" style="text-align:center;font-size:13px"><?php echo $promote_to;?></td>
                    </tr>
					<tr>
                        <td  class="signtd" style=""><b>School Reopens on</b></td>
                      
							<td class="td2" colspan="2" style="text-align:center;font-size:13px">
							<?php 
								$reopen_date=$this->assessment_model->get_school_reopen_date($row1['class_id'],$row1['section_id']);
								if($reopen_date<>NULL && $reopen_date<>'0000-00-00')
									echo date_format(date_create($reopen_date),'d-m-Y');
							?>
							</td>
                         <?php ?>
                    </tr>
					<?php }?>
                    <tr>
                        <td  class="signtd" style="height:35px"><b>Principal's Sign.</b></td>
                         <?php 
						 //foreach($term_list as $term){  ?>
							<td class="td2" colspan="2"></td>
                         <?php// }?>
                    </tr>
                    <tr>
						<td  class="signtd" style="height:35px"><b>Teacher's Sign.</b></td>
						 <?php 
						// foreach($term_list as $term){  ?>
						<td class="td2" colspan="2"></td>
						<?php// }?>
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
<?php endforeach;?>
</head>
<body>
