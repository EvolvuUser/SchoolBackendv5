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
  .bgimg {
    background-image:url('https://sms.evolvu.in/public/reportcard/HSCS/class1n2.jpg');
   -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;
    height:1122px;
     /*margin-bottom:3px;*/
}
.bgimg1 {
    background-image: url('https://sms.evolvu.in/public/reportcard/HSCS/class1_n_2_Blank.jpg');
   -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;
     height:1122px;
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
        text-align:center;
        height:30px;
		font-size:12px;
        border:1px solid grey;
        padding-top: 8px;
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
	
	.scholastictd{
 		vertical-align:center;
        text-align:center;
        height:16px;
		font-size:13px;
        border:1px solid grey;
		border-right:1px solid grey;
        padding-top: 8px;
    }
    
    .pdfdiv {
   page: LC;
   page-break-after: always;
}
.pdfdiv:last-child{
    page-break-after: avoid;
    page-break-inside: avoid;
    /*margin-bottom: 0px;*/
}
</style> 

<?php 
$student_info1 = [];
	if(isset($class_id) && isset($section_id)){
   
 $student_info1 = get_students($class_id,$section_id,$academic_yr); 
 
}else {
    $student_info1	= get_student_info($student_id,$academic_yr);
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
   
}else{
    $student_info = $student_info1;
}
	foreach($student_info as $row1):?>
<html>
    <head>
        <meta charset="utf-16" />
    </head>
    <div class="col-md-12 pdfdiv bgimg">
<div class="col-md-2"></div>
	<div class="col-md-8 table-responsive " style="text-align:center;">
		<table border="0" style="width:750px;margin-left:7%;margin-right: auto;border-spacing: 0px;background-color:rgba(0, 0, 0, 0);margin-top: 15%;">
			<tr>
				<td style="width:30%;text-align: left;font-size:14px;" >
<!--				    UDISE No. - 27250504625-->
				</td>
				<td style="width:40%;text-align: center;">
					<h4 >ACADEMIC SESSION <?php echo $row1['academic_yr'];?></h4>
					<!--<h3><font color="#000000">REPORT CARD</font></h3>-->
				</td>
				<td style="width:30%;text-align: left;font-size:14px;margin-left: 30px;" >
				</td>
			</tr>
		</table>
	 <br>
		<table border="0"  class="table-responsive" style="table-layout:fixed;width:700px;margin-left:7%;margin-right: auto;border-spacing: 0px;background-color:white;border:1px solid grey;padding:1%;" cellpadding="2" cellspacing="10">
			<tr> 
                <td style="width:100%;text-align: left;">
                    <table class="infotd" style="width:95%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border:0px;" cellpadding="0" cellspacing="0">
                        <td style="width: 30%;word-wrap:break-word;max-width:100%">NAME OF THE STUDENT : </td>
						<td style="width: 62%;text-align: center;"><div class="statistics_line"><?php echo $row1['first_name']." ".$row1['mid_name']." ".$row1['last_name'];?></div> </td>
<!--
						<td style="width: 1%;"></td>
						
-->
                    </table>
                </td>
			</tr>
			<tr>
                <td>
                    <table class="infotd" style="width:95%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border:0px;" cellpadding="0" cellspacing="0">
                        <td style="margin-left: 10px;word-wrap:break-word;width:15%">ROLL NO. </td>
						<td style="margin-left: 10px;word-wrap:break-word;width:auto;text-align: center;"><div class="statistics_line"> <?php echo $row1['roll_no'];?></div></td>
						<td style="margin-left: 10px;word-wrap:break-word;width:20%">ADMISSION NO. </td>
						<td style="margin-left: 10px;word-wrap:break-word;width:auto;text-align: center;"><div class="statistics_line"> <?php echo $row1['reg_no'];?></div></td>
                    </table>
                    
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="infotd" style="width:95%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border:0px;" cellpadding="0" cellspacing="0">
                        <td style="width: 6%;">CLASS </td>
						<td style="width: 15%;text-align: center;"><div class="statistics_line"><?php echo get_class_name($row1['class_id']);?></div></td>
                        <td style="width: 8%;">SECTION </td>
						<td style="width: 15%;text-align: center;"><div class="statistics_line"><?php echo get_section_name($row1['section_id']);?></div></td>
                        <td style="margin-left: 10px;word-wrap:break-word;width:20%">DATE OF BIRTH </td>
						<td style="margin-left: 10px;word-wrap:break-word;width:auto;text-align: center;"><div class="statistics_line"> <?php echo date_format(date_create($row1['dob']),'d-m-Y');?></div></td>
                    </table>
                    
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="infotd" style="width:95%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border:0px;" cellpadding="0" cellspacing="0">
                        <td style="width: 1%;">ADDRESS </td>
						<td style="width: 20%;text-align: center;"><div class="statistics_line"><?php echo $row1['permant_add'];?></div></td>

                    </table>
                    
                </td>
                
            </tr>
            <?php $parent_info = get_parent_info($row1['parent_id']);?>
            <tr>
                <td>
                    <table class="infotd" style="width:95%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border:0px;" cellpadding="0" cellspacing="0">
                        <td style="width: 20%;">FATHER´S NAME </td>
                        <td style="width:30%;;text-align: center;"><div class="statistics_line"><?php echo $parent_info[0]->father_name;?></div></td> 
                         <td style="width: 15%;">MOBILE NO.</td>
                        <td style="width:30%;;text-align: center;"><div class="statistics_line"><?php echo $parent_info[0]->f_mobile;?></div></td>
                    </table>
                    
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="infotd" style="width:95%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border:0px;" cellpadding="0" cellspacing="0">
                        <td style="width: 22%;">MOTHER´S NAME </td>
                        <td style="width:30%;;text-align: center;"><div class="statistics_line"><?php echo $parent_info[0]->mother_name;?></div></td>
                        <td style="width: 15%;">MOBILE NO. </td>
                        <td style="width:30%;;text-align: center;"><div class="statistics_line"><?php echo $parent_info[0]->m_mobile;?></div></td>
                    </table>
                    
                </td>
                
            </tr>
            
		</table>
     <br>
     
     
        <table class="table-responsive" style="width:700px;margin-left:7%;margin-right: auto;border-spacing: 0px;border:0;" cellpadding="0" cellspacing="0" >
					<tr>
						<th style="width:30%;">A glimpse of myself(child photo)</th>
						<th style="height:250px;width:70%;border: 1px solid grey;border-radius: 5px;padding:2%;" align="center" valign="middle">
    					    <img src="{{ $codeigniter_app_url }}/uploads/student_image/{{ $row1['image_name'] }}" width="auto" height="200px" style="text-align:center;"> 
    					</th>
					</tr>
					<tr>
						<th style=""></th>
						<th style="height:10px;padding:2%;"></th>
					</tr>
					<tr>
						<th style="width:30%;">A glimpse of my family (child with family photo)</th>
						<th style="height:300px;width:70%;border: 1px solid grey;border-radius: 5px;padding:2%;" align="center" valign="middle">
    					    <img src="{{ $codeigniter_app_url }}/uploads/family_image/<?php echo $parent_info[0]->family_image_name;?>" width="auto" height="250px" style="text-align:center;"> 
    					</th>
					</tr>
				</table>
     
     
        </div>
        <br>
        <br>
        </div>
        <div class="col-md-12 pdfdiv bgimg1">
<div class="col-md-2"></div>
	<div class="col-md-8 table-responsive " style="text-align:center;">
	     <table class="table-responsive" style="width:90%; margin-left: 5%; margin-right: auto; border-spacing: 0px; background-color:white; margin-top: 3%;" cellpadding="0" cellspacing="0" >
			
						<tr>
						    <th class="th" style="font-size:12px;width:25%;;text-align: center;valign:top;inline-block;padding:1%;">DOMAINS</th>
						<?php 
						
						$term	=	DB::table('term')->get();
						foreach($term as $row): 
						    ${'reportcard_publish_term'.$row->term_id.'_value'}= get_reportcard_publish_value($row1['class_id'],$row1['section_id'],$row->term_id);//report card publish value for each term 
						?>
						    <th class="th" style="font-size:12px;width:10%;text-align: center;valign:top;inline-block;padding:1%;" colspan="3"><?php echo $row->name;?></th>
						<?php endforeach;?>
						</tr>
						<tr>
						    <th class="td"></th>
                            <?php 
                            //$term	=	peer_assessment_parameter($row1['class_id']);
                            $term	=	DB::table('term')->get();
                            foreach($term as $row):
                            //if($row['name']!='Term 2'){ ?>
                                <th class="td" style="font-size:10px;text-align:center;padding:2px;">Beginner</th>
                                <th class="td" style="font-size:10px;text-align:center;padding:2px;">Progressing</th>
                                <th class="td" style="font-size:10px;text-align:center;padding:2px;">Proficient</th>
                                <?php                             //}
                endforeach;?>
                        
                        </tr>

						<?php 
						$domain_p = get_domain_master_by_class_id($row1['class_id']);
						$j=1; 
						foreach($domain_p as $r){?>
						    <tr>
						        <!--<td></td>-->
						        <th class="td" style="font-size:12px;padding:2px;"><b><?php echo $r->name;?></b><br><?php echo $r->description;?></th>
						        <th class="td" style="font-size:12px;padding:2px;" colspan="6"><b></th>
						        </tr>
						        </tr>
						        <?php $parameters = get_parameter_by_dm_id($r->dm_id);
                                    foreach($parameters as $p){
                                    
                                    ?>
						    <tr>
						         
						        <!--<td></td>-->
						        <td class="td" style="word-wrap:normal;font-size:12px;padding:2px;"><?php echo $p->parameter;?></td>

                                <?php 
                                foreach($term as $row){
                                    $parameter_value= get_published_domain_parameter_value_by_id($row1['student_id'],$p->parameter_id,$row->term_id,$academic_yr);
                                  
                                ?>
                                <td class="td" style="vertical-align:center;text-align:center;"><?php if($parameter_value=='Beginner' && ${'reportcard_publish_term'.$row->term_id.'_value'}=='Y'){ ?><img height="10px" width="12px" src="{{$codeigniter_app_url}}/uploads/check.jpg" alt="Checkmark"> <?php } ?></td>
                                        <td class="td" style="vertical-align:center;text-align:center;"><?php if($parameter_value=='Progressing'  && ${'reportcard_publish_term'.$row->term_id.'_value'}){ ?><img height="10px" width="12px" src="{{$codeigniter_app_url}}/uploads/check.jpg" alt="Checkmark"> <?php } ?></td>
                                        <td class="td" style="vertical-align:center;text-align:center;"><?php if($parameter_value=='Proficient'  && ${'reportcard_publish_term'.$row->term_id.'_value'}){ ?><img height="10px" width="12px" src="{{$codeigniter_app_url}}/uploads/check.jpg" alt="Checkmark"> <?php } ?></td>
                                  <?php }?>
						    </tr>
						  
						         <?php }?>
						         <?php };?>
        </table> 
        </div>
        <br>
        <br>
        </div>
        <div class="col-md-12 pdfdiv bgimg1">
			<div class="col-md-2"></div>
			<div class="col-md-8 table-responsive " style="text-align:center;">
	    	<!--table class="table-responsive scholastictable" style="width:85%;margin-left:6%; margin-right: auto; border-spacing: 0px; margin-top: 4%;" cellpadding="0" cellspacing="0" -->
			<table class="table-responsive" style="width:680px; margin-left: 6%; margin-right: auto; border-spacing: 0px; background-color:white; margin-top: 4%;" cellpadding="0" cellspacing="0" >
			 <tr>
				 <td style="vertical-align:middle;text-align: center" cellpadding="0" cellspacing="0">
					<table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <tr>
							<?php  $term_list	=  get_published_terms($row1['class_id'],$row1['section_id']);
							//print_r($term_list);
                            ?>
							<th class="col-md-3 col-sm-3 col-xs-3 th" style="word-wrap: break-word;font-size:10px"><b>Scholastic Areas</b></th>
							<?php 
						//	$count_of_mark_headings=0;
							foreach($term_list as $term){
								${'general_highest_marks_array_'.$term->term_id}=array();
								${'count_of_mark_headings_'.$term->term_id}=0;
								//$count_of_mark_headings=0;
								$exam_list	=	get_exams_by_class_per_term($row1['class_id'],$term->term_id,$academic_yr);
								foreach($exam_list as $exam){
									${'count_of_mark_headings_'.$exam->exam_id}=0;
									$marks_headings = get_marks_heading_class($row1['class_id'],1,$exam->exam_id,$academic_yr);
									${'general_highest_marks_json_'.$term->term_id}="{";
									foreach($marks_headings as $mrow){
										${'general_highest_marks_json_'.$term->term_id}=${'general_highest_marks_json_'.$term->term_id}.'"'.$mrow->marks_headings_name.'":"'.$mrow->highest_marks.'",';
										${'count_of_mark_headings_'.$exam->exam_id}=${'count_of_mark_headings_'.$exam->exam_id}+1;
									}
									${'general_highest_marks_json_'.$term->term_id}=rtrim(${'general_highest_marks_json_'.$term->term_id},","); //Lija report card
									${'general_highest_marks_json_'.$term->term_id}=${'general_highest_marks_json_'.$term->term_id}."}";
									${'general_highest_marks_array_'.$term->term_id}=array_merge(${'general_highest_marks_array_'.$term->term_id},json_decode(${'general_highest_marks_json_'.$term->term_id},true));
									//echo ${'general_highest_marks_json_'.$term->term_id}."<br>"; 
									${'count_of_mark_headings_'.$term->term_id}=count(${'general_highest_marks_array_'.$term->term_id});
									
								}
                            ?>
							 <th class="col-md-1 th1" style="text-align:center;height:30px;" colspan="<?php echo ${'count_of_mark_headings_'.$term->term_id}+2;?>"><?php echo $term->name;?></th>
                         <?php 
                            }
							?>
						</tr>		
						<tr>
							<?php  
                            //$term_list	=	get_term($acd_yr);?>
                            <td class="col-md-3 scholastictd" style="text-align:center;height:30px;">SUBJECT</th>
							<?php 
							   
								foreach($term_list as $term){	
								     ${"grand_total_marks ".$term->term_id}=0;
									${'grand_highest_marks_'.$term->term_id}=0;
									
									$highest_total_marks=0;
									if(isset(${'general_highest_marks_array_'.$term->term_id}) && ${'general_highest_marks_array_'.$term->term_id}<>null){
										foreach (${'general_highest_marks_array_'.$term->term_id} as $key => $value){
											if($key=='Pen Paper')
												$value=10;
											$highest_total_marks=$highest_total_marks+(float)$value;
											
											${'total_marks_'.$term->term_id.$key}=0;
							?> 
										<td class="col-md-1 scholastictd" style="vertical-align:middle;text-align:center;height:30px;"><?php echo $key."<br/>(".$value.")";  ?></td>
							 <?php 		}
										
							 ?>
										<td class="col-md-1 scholastictd"  style="vertical-align:middle;text-align:center;height:30px;">Total<br/>(<?php echo $highest_total_marks; ?>)</td>
										<td class="col-md-1 scholastictd"  style="vertical-align:middle;text-align:center;height:30px;">Grade</td>
							 <?php
									}else{
							 ?>
								        <td class="col-md-1 scholastictd"  colspan="<?php echo ${'count_of_mark_headings_'.$term->term_id}+2;?>" style="text-align:center;height:30px;"></td>
							        
							<?php 
									}
								}
							?>
						</tr>

						<?php 
						//$grand_highest_marks=0;
						$sub_list = get_scholastic_subject_alloted_to_class($row1['class_id'],$academic_yr);
               
						foreach($sub_list as $sub_row){?>
						<tr>
                             <td  class="col-md-1 scholastictd" style="text-align:center;height:30px;">  
								<?php
									echo $sub_row->name;
								?>
							</td>
							<?php 
							foreach($term_list as $term){
								$total_marks_obtained="";
								$total_highest_marks=""; //Lija 18-03-22
								
								${'mark_obtained_array_'.$term->term_id}=array();
								${'highest_marks_array_'.$term->term_id}=array();
								$exam_list	=	get_exams_by_class_per_term($row1['class_id'],$term->term_id,$academic_yr);
								if(isset($exam_list) && count($exam_list)>0){
								foreach($exam_list as $exam){
									${'marks_resultarray_'.$term->term_id}	=	get_marks($exam->exam_id,$row1['class_id'],$row1['section_id'],$sub_row->sub_rc_master_id,$student_id,$academic_yr);
									if(isset(${'marks_resultarray_'.$term->term_id}[0])){
										
										
										${'marks_obtained_json_'.$term->term_id}=${'marks_resultarray_'.$term->term_id}[0]['reportcard_marks'];
										${'highest_marks_json_'.$term->term_id}=${'marks_resultarray_'.$term->term_id}[0]['reportcard_highest_marks']; //Lija 18-03-22
										
										${'mark_obtained_array_'.$term->term_id}=json_decode(${'marks_obtained_json_'.$term->term_id});
										${'highest_marks_array_'.$term->term_id}=json_decode(${'highest_marks_json_'.$term->term_id});//Lija 18-03-22
									    
										if(isset(${'mark_obtained_array_'.$term->term_id}) && ${'mark_obtained_array_'.$term->term_id}<>null){
											foreach (${'mark_obtained_array_'.$term->term_id} as $key => $value){ 
											    if($total_marks_obtained=="")
													$total_marks_obtained=0;
												$total_marks_obtained=$total_marks_obtained+$value;
												//echo "value ".$value." ";
												${'total_marks_'.$term->term_id.$key}=${'total_marks_'.$term->term_id.$key ?? 0}+$value;
							?> 
												<td class="col-md-1 scholastictd"  style="vertical-align:middle;text-align:center;height:30px;"><?php echo $value;?></td>
										<?php }
											//Lija 18-03-22
											 foreach (${'highest_marks_array_'.$term->term_id} as $key => $value){ 
												if($total_highest_marks=="")
													$total_highest_marks=0;
												$total_highest_marks=$total_highest_marks+(float)$value;
											  }
										?>
				
										
								<?php }else{?>
								                <td class="col-md-1 scholastictd"  style="vertical-align:middle;text-align:center;height:30px;" colspan="<?php echo (${'count_of_mark_headings_'.$term->term_id}+2);?>"></td> 
								<?php }
							}else{
								for($i=0;$i<${'count_of_mark_headings_'.$exam->exam_id};$i++){
								?>
									<td class="col-md-1 scholastictd"  style="vertical-align:middle;text-align:center;height:30px;"></td>
							<?php
								}
							}
						}?>
						<td class="col-md-1 scholastictd"  style="vertical-align:middle;text-align:center;height:30px;"><?php echo number_format((float) $total_marks_obtained, 1); ?></td>
									
						<td class="col-md-1 scholastictd"  style="vertical-align:middle;text-align:center;height:30px;">
						<?php
							if($total_marks_obtained<>""){
								${"grand_total_marks ".$term->term_id}=${"grand_total_marks ".$term->term_id}+$total_marks_obtained;
								${'grand_highest_marks_'.$term->term_id}=${'grand_highest_marks_'.$term->term_id}+$total_highest_marks;//Lija 18-03-22
							}
							if($total_marks_obtained==""){
								echo "";
							}else{
								$final_grade = "";
								if($total_highest_marks<>0){
									//$subject_total_marks_per_50=($total_marks_obtained*50)/$total_highest_marks;//Convert to out of 50
									$final_grade = get_grade_based_on_marks(number_format($total_marks_obtained),'Scholastic',$row1['class_id']); 
								}
								
								echo $final_grade;
							}
						?>
						</td>
					<?php
					}else{?>
							<td class="col-md-1 scholastictd"  style="vertical-align:middle;text-align:center;height:30px;" colspan="<?php echo (${'count_of_mark_headings_'.$term->term_id}+2);?>"></td> 
					<?php
						}
					}
					?>
				
                        </tr>
                        <?php 
						}?>
						
						
				</table>
				</td>
			</tr>
		</table>
			
			<br>
			<table class="table-responsive"style="table-layout:fixed;width:700px;margin-left: 6%;margin-right: auto;border-spacing: 0px;background-color:white;">
				<tr>
                    <td style="vertical-align:top;width:50%">
                        <table class="table-responsive" style="border:0px;width:100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="vertical-align:middle;" cellpadding="0" cellspacing="0">
								<?php  
                                        $term_list	=	get_published_terms($row1['class_id'],$row1['section_id']);
                                        $colspan = count($term_list)+1;
                                        ?>
                                       
                                <table class="table-responsive " style="width:100%; margin-left: auto; margin-right: auto; border-spacing: 0px; background-color:white;" cellpadding="0" cellspacing="0">
                                  <tr>
                                        <th class="th" colspan="<?php echo $colspan;?>">CO- SCHOLASTICS AREA (Graded on 5 point Scale)</th>
                                    </tr>
                                    <tr>
                                        <th class="scholastictd">Subjects</th>
                                        <?php 
                                        //$exam_list	=	get_exams_by_class_per_term($row1['class_id'],$term->term_id,$row1['academic_yr']);
                                        foreach($term_list as $term){ 
                                       ?>
                                        <th class="scholastictd"  width=""><?php echo $term->name;?></th>
                                         <?php
                                        }
                                        ?>
                                    </tr>
                                    <?php 
                                    $sub_list = get_coscholastic_subject_alloted_to_class($row1['class_id'],$row1['academic_yr']);
                                    foreach($sub_list as $sub_row):?>

                                    <tr>
										<td  class="col-md-8 col-sm-8 col-xs-8 scholastictd" style="vertical-align:middle;text-align:center;"> <?php echo $sub_row->name;?></td>

									<?php 
                                 //foreach($term_list as $term){
                                    
                                    //$exam_list	=	get_exam_for_which_marks_available($row1['class_id'],$row1['section_id'],$row1['student_id']);
                                   
                                    $coscholastic_grade="";
                                    foreach($term_list as $term){
										${'mark_obtained_array_'.$term->term_id}=array();
				                        $exam_list	=	get_exams_by_class_per_term($row1['class_id'],$term->term_id,$row1['academic_yr']);
				                        $coscholastic_grade="";
				                        foreach($exam_list as $exam){
				    
                                        ${'marks_resultarray_'.$term->term_id}	=	get_marks($exam->exam_id,$row1['class_id'],$row1['section_id'],$sub_row->sub_rc_master_id,$row1['student_id'],$row1['academic_yr']);
					
                    					if(isset(${'marks_resultarray_'.$term->term_id}[0])){
                    						${'marks_obtained_json_'.$term->term_id}=${'marks_resultarray_'.$term->term_id}[0]['reportcard_marks'];
                    						${'mark_obtained_array_'.$term->term_id}=array_merge(${'mark_obtained_array_'.$term->term_id},json_decode(${'marks_obtained_json_'.$term->term_id},true));
                    						
                    						if(isset(${'mark_obtained_array_'.$term->term_id}) && ${'mark_obtained_array_'.$term->term_id}<>null){
                    							
                    							${'coscholastic_marksobtained_'.$term->term_id}=${'marks_resultarray_'.$term->term_id}[0]['total_marks'];
                    							
                    							${'coscholastic_highestmarks_'.$term->term_id}=${'marks_resultarray_'.$term->term_id}[0]['highest_total_marks'];
                    
                    							foreach (${'mark_obtained_array_'.$term->term_id} as $key => $value){
                    								if($value=='Ab')
                    									$coscholastic_grade="Ab";
                    							}
                    							if($coscholastic_grade=="Ab" && ${'coscholastic_marksobtained_'.$term->term_id}==0){
                    								//If reportcard marks is Ab and total marks is 0 then Grade will be Ab
                    								$coscholastic_grade="Ab";
                    							}else{ 
                    
                    								$marks_per_50=(${'coscholastic_marksobtained_'.$term->term_id}*50)/${'coscholastic_highestmarks_'.$term->term_id};//Convert to out of 50
                    
                    								$coscholastic_grade= get_grade_based_on_marks(number_format($marks_per_50),'Co-Scholastic',$row1['class_id']); 
                    								
                    							 }
                    						}
                    					}else{
                    					    $coscholastic_grade= "";
                    					}
                    					
                    				}
                    			?>	
									<td class="scholastictd" style="vertical-align:middle;text-align:center;"><?php echo $coscholastic_grade;?></td>	
                                <?php
									}
                                ?>
                                
                                <?php
                                // }
                                 ?>
                            </tr>
                                    <?php endforeach;?>
                                </table>
                              </td>
                    </tr>
					
                </table><br/>
				
				<!--<table class="table-responsive" style="width:100%;margin-left:auto;margin-right: auto;border-spacing: 0px;background-color:white;border:0">-->
    <!--                    <tr>-->
    <!--        				<td style="width:30%;font-size:14px;text-align:left;white-space:nowrap;" ><b>Class Teacher's Remark :</b></td>-->
    <!--        				<td style="width:70%;font-size:12px;" >-->
    <!--            				<div class="statistics_line">-->
    <!--            				<!-?php -->
    <!--            				$remark_string="";-->
    <!--            				$exam_list_s	=	get_exam_for_which_marks_available($row1['class_id'],$row1['section_id'],$row1['student_id'],'Co-Scholastic');-->
    <!--            				foreach($exam_list_s as $exam){ -->
    <!--            				    $term_id	=	get_term_of_exam($exam->exam_id);-->
    <!--             					$remark=get_reportcard_remark_of_a_student($row1['student_id'],$term_id);-->
    <!--            					if($remark_string=="" && $remark<>"")-->
				<!--						$remark_string=$remark_string."Term ".$term_id." - ".$remark;-->
				<!--					elseif($remark_string<>"" && $remark<>"")-->
				<!--						$remark_string=$remark_string."<br/> Term ".$term_id." - ".$remark;-->
    <!--                            }-->
				<!--				if($remark_string<>""){-->
				<!--					echo $remark_string;-->
				<!--				}else-->
				<!--					//echo "&nbsp;";-->
    <!--            				 ?>-->
    <!--            				</div>-->
    <!--        				</td>-->
    <!--        			</tr>-->
    <!--    		    </table>-->
				
				</td>
				<td style="vertical-align:top;width:1%"></td>
                <td style="vertical-align:top;width:49%">
					<table class="table-responsive" style="border:0px;width:100%;" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="vertical-align:middle;" cellpadding="0" cellspacing="0">
								<?php 
                                     $sub_list = get_activity_alloted_to_class($row1['class_id'],$row1['academic_yr']);
									 $exam_list_s	=	get_exam_for_which_marks_available($row1['class_id'],$row1['section_id'],$row1['student_id'],'Activity');
                                  

                                    foreach($sub_list as $sub_row){
										$marks_headings_of_subject=get_marks_headings_of_subject($row1['class_id'],$sub_row['sub_rc_master_id'],$row1['academic_yr']);
										foreach($marks_headings_of_subject as $mrow){
											${'coscholastic_grade'.$mrow['marks_headings_name']}=array();
										}
										?>
								
								
                                <table class="table-responsive " style="width:100%; margin-left: auto; margin-right: auto; border-spacing: 0px; background-color:white;" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <th class="scholastictd" colspan="<?php echo count($exam_list_s)+1;?>"><?php echo $sub_row['name'];?></th>
                                    </tr>
                                    

                                   
                                 <?php 
								    $coscholastic_grade="";
                                    foreach($exam_list_s as $exam){
											

										${'mark_obtained_array_'.$exam->exam_id}=array();
                                        ${'marks_resultarray_'.$exam->exam_id}	=	get_marks($exam->exam_id,$row1['class_id'],$row1['section_id'],$sub_row['sub_rc_master_id'],$row1['student_id'],$row1['academic_yr']);
                                        //echo "marks ".${'marks_resultarray_'.$term->term_id}[0]."<br/>";
                                        if(isset(${'marks_resultarray_'.$exam->exam_id}[0])){
                                            ${'marks_obtained_json_'.$exam->exam_id}=${'marks_resultarray_'.$exam->exam_id}[0]['reportcard_marks'];
                                            ${'mark_obtained_array_'.$exam->exam_id}=array_merge(${'mark_obtained_array_'.$exam->exam_id},json_decode(${'marks_obtained_json_'.$exam->exam_id},true));
 											
                                            if(isset(${'mark_obtained_array_'.$exam->exam_id}) && ${'mark_obtained_array_'.$exam->exam_id}<>null){
												
												${'coscholastic_marksobtained_'.$exam->exam_id}=${'marks_resultarray_'.$exam->exam_id}[0]['total_marks'];
											
												${'coscholastic_highestmarks_'.$exam->exam_id}=${'marks_resultarray_'.$exam->exam_id}[0]['highest_total_marks'];

                                                foreach (${'mark_obtained_array_'.$exam->exam_id} as $key => $value){
												
                                                    if($value=='Ab')
                                                        $coscholastic_grade="Ab";
													if($coscholastic_grade=="Ab" && $value==0){
														//If reportcard marks is Ab and total marks is 0 then Grade will be Ab
														$coscholastic_grade="Ab";
													}else{ 
														$coscholastic_grade= get_grade_based_on_marks(number_format($value),'Co-Scholastic',$row1['class_id']); 

													 }
													 
													 ${'coscholastic_grade'.$key}=array_merge(${'coscholastic_grade'.$key},array($exam['name']=>$coscholastic_grade));

												}

											}
												
                                        }
                                    }
								
										foreach($marks_headings_of_subject as $mrow){
											//var_dump(${'coscholastic_grade'.$mrow['marks_headings_name']});
											//echo "<br/>";
										?>
												<tr>
													<td  class="col-md-8 col-sm-8 col-xs-8 scholastictd subnamesize" style="vertical-align:middle;text-align:center;"> <?php echo $mrow['marks_headings_name'];?></td>
										<?php
											foreach($exam_list_s as $exam){
										?>
													<td class="scholastictd" style="vertical-align:middle;text-align:center;"><?php echo ${'coscholastic_grade'.$mrow['marks_headings_name']}[$exam['name']];?></td>
										<?php } ?>
												 </tr>
									<?php
										}
									?>
                                </table>
								<br>
					<?php }?><br/>
                            </td>
						</tr>
					</table>
				</td>
				</tr>
			</table>
            <br>              
            
			<h3 style="margin-right: 70%;margin-top:auto;text-align:center;">Parent's Feedback</h3>
        
			<table class="table-responsive scholastictable" style="width:630px;margin-left: 6%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			 <tr>
                 <td style="valign:top;inline-block;margin:0;padding:0;" cellpadding="0" cellspacing="0" class="term">
					<table class="" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                      					
						<tr> 
                            <th class="th" style="width:40%;">Aspect</th>
                            <?php $term	=	DB::table('term')->get();
                            foreach($term as $row):
                            //if($row['name']!='Term 2'){?>
                            <th class="th" style="width:30%;"><?php echo $row->name;?></th>
                            <?php 
                                //}
                            endforeach;?>
                        </tr>
                        <?php 
                            $pa	=	parent_feedback_parameter($row1['class_id']);
                            foreach($pa as $r):?>
                            <tr>
                                <td class="td" style="vertical-align:center;text-align:center;"><?php echo $r->parameter;?></td>
                                <?php $term	=	DB::table('term')->get();
                                foreach($term as $row){
                                ?>
                                    <td class="td" style="vertical-align:center;text-align:center;">
                                <?php
                                    $parameter	= get_published_parent_feedback_parameter_value_by_id($row1['student_id'],$r->pfm_id,$row->term_id,$academic_yr);
                                    if(${'reportcard_publish_term'.$row->term_id.'_value'}=='Y'){
                                        if($r->parameter=='How satisfied are you with his/ her study time.'){?>
                                        
                                            <?php 
                                            for($i=1;$i<=$parameter;$i++){?>
    									        <img src="{{$codeigniter_app_url}}/uploads/Plain_Yellow_Star.jpg" style="width:20px;height:18px">
    										<?php } 
    										if($parameter==0)
    											echo "<font size='5'>#</font>";
    										?>

                                <?php   }else{ 
                                            echo $parameter;
                                        }
                                    }
                                ?>
                                </td>
                                <?php
                                }?>
                            </tr>
                        <?php 
                                //}
                            endforeach;?>
                        
                     </table>
                 </td>
            </tr>
        </table>
        <br>
		<!--h3 style="margin-right: 70%;margin-top:55%;text-align:center;">Peer Assessment</h3>
        
        <table class="table-responsive scholastictable" style="width:70%;margin-left: 10%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0"-->
		<h3 style="margin-right: 72%;text-align:center;">Peer Assessment</h3>
        
        <table class="table-responsive scholastictable" style="width:640px;margin-left: 6%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			 
			 <tr>
				 <td style="valign:top;inline-block;margin:0;padding:0;" cellpadding="0" cellspacing="0" class="term">
					<table class="" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                      					
						<tr> 
						<th class="th" >Aspect</th>
                            <?php 
                            $term	=	DB::table('term')->get();
                            foreach($term as $row):
                            //if($row['name']!='Term 2'){?>
                            <th class="th"><?php echo $row->name;?></th>
                            <?php 
                            endforeach;?>
                        </tr>
                        <?php 
                            $pa	=	peer_assessment_parameter($row1['class_id']);
                            foreach($pa as $r):
                            
                            ?>
                            <tr>
                                 <td class="td" style="vertical-align:center;text-align:center;width:40%;"><?php echo $r->parameter;?></td>
                                 <?php 
                                  $term	=	DB::table('term')->get();
                                 foreach($term as $row): 
                                 $pa = get_published_peer_assessment_parameter_value_by_id($row1['student_id'],$r->pam_id,$row->term_id,$academic_yr);
                                 ?>
                                    <td class="td" style="vertical-align:center;text-align:center;width:30%;">
                                    <?php 
                                    if(${'reportcard_publish_term'.$row->term_id.'_value'}=='Y'){
                                        echo $pa; 
                                    }
                                    ?></td>
                                <?php  endforeach;?>
                          </tr>
                        <?php
                            endforeach;?>
				</table>
				</td>
			</tr>
		</table>
        </div>
        <br>
        <br>
        </div>
        <div class="col-md-12 pdfdiv bgimg1">
			<div class="col-md-2"></div>
			<div class="col-md-8 table-responsive " style="text-align:center;">
         
        
        <h3 style="margin-right: 72%;margin-top:4%;text-align:center;">Self Assessment</h3>
        
        <table class="table-responsive scholastictable" style="width:620px;margin-left: 6%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			 <tr>
                 <td style="valign:top;inline-block;margin:0;padding:0;" cellpadding="0" cellspacing="0" class="term">
					<table class="" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">

						<tr> 
                            <th class="th" style="width:24%;">Aspect</th>
                            <?php $term	=	DB::table('term')->get();
                            foreach($term as $row):
                            //if($row['name']!='Term 2'){?>
                            <th class="th" style="width:30%;"><?php echo $row->name;?></th>
                            <?php 
                                //}
                            endforeach;?>
                        </tr>
                        <?php 
                            $pa	=	get_self_assessment_master($row1['class_id']);
                            foreach($pa as $r):?>
                            <tr>
                                <td class="td" style="vertical-align:center;text-align:center;"><?php echo $r->parameter;?></td>
                                <?php $term	=	DB::table('term')->get();
                                    foreach($term as $row):
                                        $parameter	= get_published_self_assessment_parameter_value_by_id($row1['student_id'],$r->sam_id,$row->term_id,$academic_yr);
                                   ?>     
                                        
                                        <td class="td" style="vertical-align:center;text-align:center;"><?php echo $parameter;?></td>
                                        <?php 
                                    endforeach;?>
                            </tr>
                        <?php 
                                //}
                            endforeach;?>
                        
                     </table>
                 </td>
            </tr>
        </table>
        <br>
		<h3 style="margin-right: 75%;">Health Status</h3>
      	<table class="table-responsive scholastictable" style="width:90%;margin-left: 6%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			<tr> 
			    <th class="th" style="text-align: center;width:30%">Aspect</th>
                <?php $term	=	DB::table('term')->get();
				foreach($term as $row):
                //if($row['name']!='Term 2'){?>
                <th class="th" style="text-align: center;width:35%"><?php echo $row->name;?></th>
                <?php 
                  //  }
                endforeach;?>
            </tr>
            <tr>
                <td class="td" style="text-align: center;">Height (Cm)</td> 
                <?php $term	=	DB::table('term')->get();
				foreach($term as $row):
                //if($row['name']!='Term 2'){ ?>
                <td class="td"  style="text-align: center;">
                    <?php 
                    if(${'reportcard_publish_term'.$row->term_id.'_value'}=='Y'){
                        echo $row1['height'];
                    }?>
                    </td> 
                <?php
                //}
                endforeach;?>
            </tr>
            <tr>
                <td class="td" style="text-align: center;">Weight (Kg)</td> 
                <?php $term	=	DB::table('term')->get();
				foreach($term as $row):
                //if($row['name']!='Term 2'){ ?>
                <td class="td"  style="text-align: center;"> 
                <?php
                if(${'reportcard_publish_term'.$row->term_id.'_value'}=='Y'){
                    echo $row1['weight'];
                }
                ?></td> 
                <?php
                //}
                endforeach;?>
            </tr>
        </table>
        <br>
         <h3 style="margin-right: 75%;">Attendance</h3>
        <table class="table-responsive scholastictable" style="width:90%;margin-left:6%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0" >
			<tr> 
			    <th class="th" style="width:30%">Aspect</th>
				
                <?php 
				$date_from= getSettingsDataForAcademicYr($academic_yr)->academic_yr_from;
				$term	=	DB::table('term')->get();
				foreach($term as $row):
				
				?>
				
                <th class="th" style="width:35%"><?php echo $row->name;?></th>
                <?php 
                endforeach;?>
            </tr>
            <tr>
                <td class="td" style="text-align: center;">Total Working Days</td> 
                <?php 
				//$term	=	get_term();
				foreach($term as $row):
					if($row->name=='Term 1'){ 
				        $date_from=getSettingsDataForAcademicYr($academic_yr)->academic_yr_from;
						$date_to=date_format(date_create(substr($date_from,0,4)."-10-30") , 'Y-m-d') ; // Creating date to as last day of Oct;
					}elseif($row->name=='Term 2'){
					    $date_from=date_format(date_create(substr($date_from,0,4)."-11-01") , 'Y-m-d') ;
						$date_to=getSettingsDataForAcademicYr($academic_yr)->academic_yr_to;
					}		
				?>
                <td class="td"  style="text-align: center;">
				    <?php
						if(${'reportcard_publish_term'.$row->term_id.'_value'}=='Y'){
							echo get_total_workingdays_from_dailyattendance_classwise($row1['class_id'],$row1['section_id'],$date_from,$date_to,$academic_yr);
						}
					?>
				</td> 
                <?php
                
                endforeach;?>
            </tr>
            <tr>
                <td class="td" style="text-align: center;">Total Attendance of the students</td> 
                <?php //$term	=	get_term();
				foreach($term as $row):
				    if($row->name=='Term 1'){ 
				        $date_from=getSettingsDataForAcademicYr($academic_yr)->academic_yr_from;
						$date_to=date_format(date_create(substr($date_from,0,4)."-10-30") , 'Y-m-d') ; // Creating date to as last day of Oct;
					}elseif($row->name=='Term 2'){
					    $date_from=date_format(date_create(substr($date_from,0,4)."-11-01") , 'Y-m-d') ;
						$date_to=getSettingsDataForAcademicYr($academic_yr)->academic_yr_to;
					}		
				?>
					<td class="td"  style="text-align: center;">
				        <?php
        				    //echo "date_from ".$date_from." date_to ".$date_to."<br>";
        					if(get_total_stu_attendance_till_a_month($row1['student_id'],$date_from,$date_to,$academic_yr)<>"" && ${'reportcard_publish_term'.$row->term_id.'_value'}=='Y'){
        						echo get_total_stu_attendance_till_a_month($row1['student_id'],$date_from,$date_to,$academic_yr);
        					}
        				?>
					
					</td> 
                <?php
                 endforeach;?>
            </tr>
        </table>
       
            <br>       
		<table class="table-responsive" style="width:86%;margin-top:12%;margin-left:6%;margin-right: auto;border-spacing: 0px;background-color:white;valign:top;border: 0px;" cellpadding="0" cellspacing="0">
            <tr> 
                <td style="width:100%;">
    				<table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border: 0;" cellpadding="0" cellspacing="0">
						<tr>
							
						<?php
						//print_r($exam_list);
						 //if(count($exam_list)>1){
						   //$term_list	=	get_published_terms($row1['class_id'],$row1['section_id']);  
						   //print_r($term_list);
						?>
							<td style="text-align:left;white-space:nowrap;width: 20% !important" class="signtag"><b> Promoted To : </b></td>
							<td style="white-space:nowrap;width:25%;margin-right:2%;text-align:center;" class="signtag"><div class="statistics_line">
							<?php 
    							$term_id	=	get_term_of_exam($exam_list[1]->exam_id);
    							//print_r($term_id);
    							echo get_promote_to_of_a_student($row1['student_id'],$term_id);
							?>&nbsp;
							</div> 
							</td>
							<td style="text-align:left;white-space:nowrap;width:20%;margin-left:2%" class="signtag"> &nbsp;&nbsp;<b>Date Of Reopening :</b></td>
							<td style="white-space:nowrap;width:25%;text-align:center;" class="signtag">
							<div class="statistics_line">
								<?php 
							        $reopen_date=get_school_reopen_date($row1['class_id'],$row1['section_id']);
									if($reopen_date<>NULL && $reopen_date<>'0000-00-00')
										echo date_format(date_create($reopen_date),'d-m-Y');
								?>
								&nbsp;
							</div></td>
					<?php //}else{?>
							<!--<td style="width: auto"> </td>-->
					<?php //}?>
						<tr>
    				</table>
				</td>
 			</tr>
			<?php //}?>
		</table>
		<br>
	<h3 style="margin-right: 68%;margin-top:14%;">Signature with date</h3>
        <table class="table-responsive scholastictable" style="width:90%;margin-left:6%;margin-right: auto;border-spacing: 0px;background-color:white;;" cellpadding="0" cellspacing="0" >
			<tr> 
			    <th class="th" style="valign:top;inline-block;text-align: center;height:30px;width:25%;!important">Term</th>
                <th class="th" style="valign:top;inline-block;text-align: center;width:25%;">Parent's/Guardian</th>
                <th class="th" style="valign:top;inline-block;text-align: center;width:25%;">Class Teacher</th>
                <th class="th" style="valign:top;inline-block;text-align: center;width:25%;">Principal</th>
                
            </tr>
             <?php $term	=	DB::table('term')->get();
				foreach($term as $row): ?>
            <tr>
                <td class="td"  style="text-align: center;"><?php echo $row->name;?></td> 
                <td class="td"  style="text-align: center;"></td> 
                <td class="td"  style="text-align: center;"></td> 
                <td class="td"  style="text-align: center;"></td> 
                
            </tr>
            <?php
                //}
                endforeach;?>
            
        </table>
					<br/><br/>
        
        <br>
        </div>
        </div>
        

 </html> 
<?php endforeach;?>
   </head>
<body>