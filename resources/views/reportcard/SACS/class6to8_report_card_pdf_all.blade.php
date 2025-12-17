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
		width: auto;
		max-width: 1px;
		vertical-align:middle;
		height:25px;
		border:1px solid grey;
		word-wrap:break-word;
		font-size:12px;
	} 
    .th{
        vertical-align:middle;
        text-align:center;
        height:25px;
        border:1px solid grey;
        text-transform: uppercase;
		font-size:12px;
    }
    .th1{
        vertical-align:middle;
        text-align:center;
        height:25px;
		font-size:12px;
        border:1px solid grey;
        text-transform: uppercase;
        color:red;
        padding-top: 8px;
    }
	
    .td{
        vertical-align:middle;
        height:25px;
        border:1px solid grey;
		font-size:12px;
		
    }
    .lasttd{
        text-align:center;
        border-top:1px solid grey;
        border-right:1px solid grey;
        border-bottom:1px solid grey;
        border-left:1px solid grey;
		font-size:12px;
    }
	.statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        padding:4px;
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
		$student_info1 =array();	
		if(isset($class_id) && isset($section_id)){
	   
			$student_info1 = get_students($class_id,$section_id,$academic_yr); 
		 
		}else {
			$student_info	=	get_student_info($student_id,$academic_yr);
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
		foreach($student_info as $row1):
			$class_name=get_class_name($row1->class_id);
			$section_name=get_section_name($row1->section_id);
		?>
<html>
    <head>
        <meta charset="utf-16" />
    </head>
	<body>
    <div class="col-md-12 pdfdiv">
<div class="col-md-2"></div>
	<div class="col-md-8  table-responsive bgimg" style="text-align:center;">
        <table border="0" style="width:85%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 20%;">
			<tr>
				<td style="width:30%;text-align: left;font-size:14px;" >
					UDISE No. - 27251501213
				</td>
				<td style="width:40%;text-align: center;">
					<h4 >ACADEMIC SESSION <?php echo $row1->academic_yr;?></h4>
					<h3><font color="#000000">REPORT CARD</font></h3>
				</td>
				<td style="width:30%;text-align: left;font-size:14px;margin-left: 30px;" >
					Student ID - <?php echo $row1->stud_id_no;?>
				</td>
			</tr>
		</table>
		<br/>
		<table border="0"  class="table-responsive" style="width:85%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="1" cellspacing="10">
			<tr> 
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;width: 25%; padding-top: 6px; padding-bottom:6px;	word-wrap:break-word;">Student's Name : </td>
						<td style="font-size:15px;text-align: center;width: auto"><div class="statistics_line"><?php echo $row1->first_name." ".$row1->mid_name." ".$row1->last_name;?></div> </td>
						<td style="font-size:16px;width: 1%;"></td>
						<td style="font-size:16px;width: 15%;margin-left: 10px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">Roll No. : </td>
						<td style="font-size:15px;width: 8%;text-align: center;"><div class="statistics_line"> <?php echo $row1->roll_no;?></div></td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:15px;width: 35%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Mother's / Father's / Gaurdian's Name : </td>
                        <td style="font-size:15px;width: 45%;text-align: center;"><div class="statistics_line"><?php echo get_parent_name($row1->parent_id);?></div></td>
						<td style="width: 1%;"></td>
						<td style="font-size:15px;margin-left: 10px;word-wrap:break-word;width:12%;padding-top: 8px; padding-bottom:8px;">GR No.: </td>
						<td style="font-size:15px;margin-left: 10px;word-wrap:break-word;width:auto;text-align: center;width:auto"><div class="statistics_line"> <?php echo $row1->reg_no;?></div></td>
                    </table>
                    
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:15px;padding:5px;width: 17%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Date of Birth : </td>
                        <td style="font-size:15px;width:25%;text-align: center;"><div class="statistics_line"><?php echo date_format(date_create($row1->dob),'d-m-Y');?></div></td>
						<td style="width: 5%;"></td>
                        <td style="font-size:15px;padding:5px;width: 20%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Class / Section : </td>
						<td style="font-size:15px;width: auto;text-align: center;"><div class="statistics_line"><?php echo get_class_name($row1->class_id)." ".get_section_name($row1->section_id);?></div></td>
                    </table>
                    
                </td>
                
            </tr>
		</table>
		<?php 
			$scholastic_table_width='88%';
			$term_list	=	get_published_terms($row1->class_id,$row1->section_id);
			if(count($term_list)==1)
				$scholastic_table_width='85%';
				
		?>
		<table class="table-responsive" style="width:<?php echo $scholastic_table_width; ?>;margin-left: 5%;margin-right: auto;border-spacing: 0px;background-color:white;border: 0px solid black !important;" cellpadding="0" cellspacing="0">
			 <tr>
				 <td style="vertical-align:middle;" cellpadding="0" cellspacing="0">
					<table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border:0" cellpadding="0" cellspacing="0">
                        <tr>
							
							<th class="col-md-2 th" style="width:10%;word-wrap: break-word; font-size:10px"><b>Scholastic Areas</b></th>
							<?php 
								//$count_of_mark_headings=0;
								foreach($term_list as $term){
									${"term".$term->term_id."_computer"}=""; //Added this variable to check if computer or AI marks are available for a term
								    ${"term".$term->term_id."_marathi_dictation"}="";
									${"term".$term->term_id."_marathi_oral"}="";
									${"term".$term->term_id."_marathi_term"}="";
									${"term".$term->term_id."_marathi_activity"}="";
									
									
									${"term".$term->term_id."_sanskrit_dictation"}="";
									${"term".$term->term_id."_sanskrit_oral"}="";
									${"term".$term->term_id."_sanskrit_term"}="";
									${"term".$term->term_id."_sanskrit_activity"}="";
									
									//${'general_mark_obtained_array_'.$term->term_id}=array();
									${'general_highest_marks_array_'.$term->term_id}=array();
									${'count_of_mark_headings_'.$term->term_id}=0;
									
									$exam_list	=	get_exams_by_class_per_term($row1->class_id,$term->term_id,$row1->academic_yr);
									foreach($exam_list as $exam){
										$sub_id="";
										//Subject for which marks data entry is there //28-09-20
										/*$sub_array=get_a_subject_for_which_marks_available($exam->exam_id,$row1->class_id,$row1->section_id,$student_id,$acd_yr);
										if(isset($sub_array[0]))
											$sub_id=$sub_array[0]['subject_id'];
										
										${'general_marks_resultarray_'.$term->term_id}	=	get_marks($exam->exam_id,$row1->class_id,$row1->section_id,$sub_id,$student_id,$acd_yr);
									    
										if(isset(${'general_marks_resultarray_'.$term->term_id}[0])){
											${'general_highest_marks_json_'.$term->term_id}=${'general_marks_resultarray_'.$term->term_id}[0]['reportcard_highest_marks'];
											
											${'general_highest_marks_array_'.$term->term_id}=array_merge(${'general_highest_marks_array_'.$term->term_id},json_decode(${'general_highest_marks_json_'.$term->term_id},true));
											 
											${'count_of_mark_headings_'.$term->term_id}=count(${'general_highest_marks_array_'.$term->term_id});
								?>
											
								<?php
										}
										*/
										${'count_of_mark_headings_'.$exam->exam_id}=0;
										$marks_headings = get_marks_heading_class($row1->class_id,1,$exam->exam_id,$row1->academic_yr);
										${'general_highest_marks_json_'.$term->term_id}="{";
										foreach($marks_headings as $mrow){
											${'general_highest_marks_json_'.$term->term_id}=${'general_highest_marks_json_'.$term->term_id}.'"'.$mrow->marks_headings_name.'":"'.$mrow->highest_marks.'",';
											${'count_of_mark_headings_'.$exam->exam_id}=${'count_of_mark_headings_'.$exam->exam_id}+1;
											${'count_of_mark_headings_'.$term->term_id}=${'count_of_mark_headings_'.$term->term_id}+1;
										}
										${'general_highest_marks_json_'.$term->term_id}=rtrim(${'general_highest_marks_json_'.$term->term_id},","); //Lija report card
										${'general_highest_marks_json_'.$term->term_id}=${'general_highest_marks_json_'.$term->term_id}."}";
										${'general_highest_marks_array_'.$term->term_id}=array_merge(${'general_highest_marks_array_'.$term->term_id},json_decode(${'general_highest_marks_json_'.$term->term_id},true));
										//echo ${'general_highest_marks_json_'.$term->term_id}."<br>"; 
										//${'count_of_mark_headings_'.$term->term_id}=count(${'general_highest_marks_array_'.$term->term_id});
									}
							?>
								<th class="col-md-1 col-sm-1 col-xs-1 th1" style="width:40%;text-align:center;white-space:nowrap;" colspan="<?php echo ${'count_of_mark_headings_'.$term->term_id}+2;?>"><?php echo $term->name;?></th>
							 <?php 
								}
							?>
						</tr>
						<tr>
							<?php  
                            //$term_list	=	get_term($acd_yr);?>
                            <th class="col-md-2 th" style="vertical-align:middle;text-align:center;height:10px;">SUBJECT</th>
							<?php 
								foreach($term_list as $term){
                                    ${"grand_total_marks_".$term->term_id}=0;		
									${'grand_highest_marks_'.$term->term_id}=0;
									$total_per_term=0;
									
									if(isset(${'general_highest_marks_array_'.$term->term_id}) && ${'general_highest_marks_array_'.$term->term_id}<>null){
										foreach (${'general_highest_marks_array_'.$term->term_id} as $key => $value){
											if($key=='Periodic Test')
												$value=10;
											//Lija For term marks were doubled. Remove this if condtion next yr
											if($term->term_id==1 && $key=='Term' && $row1->academic_yr=='2020-2021'){ //Lija 10-09-21
												$value=$value*2;
											}
							?> 
										<td class="col-md-1 col-sm-1 col-xs-1 mark_heading_td"  style="width:7%;vertical-align:middle;text-align:center;"><?php echo $key."<br/>(".$value.")"; $total_per_term=$total_per_term+$value;?></td>
							 <?php 		}
							 ?>
										<td class="col-md-1 col-sm-1 col-xs-1 mark_heading_td"  style="width:7%;vertical-align:middle;text-align:center;">Total<br/>(<?php echo $total_per_term;?>)</td>
										<td class="col-md-1 col-sm-1 col-xs-1 mark_heading_td"  style="width:7%;vertical-align:middle;text-align:center;">Grade</td>
							 <?php
									}else{
							 ?>
								        <td class="col-md-1 col-sm-1 col-xs-1 mark_heading_td" colspan="<?php echo ${'count_of_mark_headings_'.$term->term_id}+2;?>" style="text-align:center;"></td>
							        
							<?php 
								}
							}
							?>
						</tr>
						<?php 
						$sub_list = get_scholastic_subject_alloted_to_class($row1->class_id,$row1->academic_yr);

						foreach($sub_list as $sub_row){
							if($sub_row->name=='Marathi' || $sub_row->name=='Sanskrit' || $sub_row->name=='Computer Applications' || $sub_row->name=='Artificial Intelligence'){
								foreach($term_list as $term){
									${'mark_obtained_array_'.$term->term_id}=array();
									${'highest_marks_array_'.$term->term_id}=array();
									$exam_list	=	get_exams_by_class_per_term($row1->class_id,$term->term_id,$row1->academic_yr);
									foreach($exam_list as $exam){
										${'marks_resultarray_'.$term->term_id}	=	get_marks($exam->exam_id,$row1->class_id,$row1->section_id,$sub_row->sub_rc_master_id,$row1->student_id,$row1->academic_yr);

										if(isset(${'marks_resultarray_'.$term->term_id}[0])){
											${'marks_obtained_json_'.$term->term_id}=${'marks_resultarray_'.$term->term_id}[0]['reportcard_marks'];
											${'mark_obtained_array_'.$term->term_id}=array_merge(${'mark_obtained_array_'.$term->term_id},json_decode(${'marks_obtained_json_'.$term->term_id},true));

											${'highest_marks_json_'.$term->term_id}=${'marks_resultarray_'.$term->term_id}[0]['reportcard_highest_marks'];
											${'highest_marks_array_'.$term->term_id}=array_merge(${'highest_marks_array_'.$term->term_id},json_decode(${'highest_marks_json_'.$term->term_id},true));

											if($sub_row->name=='Marathi'){
												if (array_key_exists("Dictation",${'mark_obtained_array_'.$term->term_id}))
													${"term".$term->term_id."_marathi_dictation"}=(${'mark_obtained_array_'.$term->term_id}['Dictation']== 'Ab') ? 'Ab' : ${'mark_obtained_array_'.$term->term_id}['Dictation'];

												if (array_key_exists("Oral",${'mark_obtained_array_'.$term->term_id}))
													${"term".$term->term_id."_marathi_oral"}=(${'mark_obtained_array_'.$term->term_id}['Oral']== 'Ab') ? 'Ab' : ${'mark_obtained_array_'.$term->term_id}['Oral'];

												if (array_key_exists("Term",${'mark_obtained_array_'.$term->term_id}))
													${"term".$term->term_id."_marathi_term"}=(${'mark_obtained_array_'.$term->term_id}['Term']== 'Ab') ? 'Ab' : ${'mark_obtained_array_'.$term->term_id}['Term'];
												
												if (array_key_exists("Activity",${'mark_obtained_array_'.$term->term_id}))
													${"term".$term->term_id."_marathi_activity"}=(${'mark_obtained_array_'.$term->term_id}['Activity']== 'Ab') ? 'Ab' : ${'mark_obtained_array_'.$term->term_id}['Activity'];

											}elseif($sub_row->name=='Sanskrit'){
												if (array_key_exists("Dictation",${'mark_obtained_array_'.$term->term_id}))
													${"term".$term->term_id."_sanskrit_dictation"}=(${'mark_obtained_array_'.$term->term_id}['Dictation']== 'Ab') ? 'Ab' : ${'mark_obtained_array_'.$term->term_id}['Dictation'];

												if (array_key_exists("Oral",${'mark_obtained_array_'.$term->term_id}))
													${"term".$term->term_id."_sanskrit_oral"}=(${'mark_obtained_array_'.$term->term_id}['Oral']== 'Ab') ? 'Ab' : ${'mark_obtained_array_'.$term->term_id}['Oral'];

												if (array_key_exists("Term",${'mark_obtained_array_'.$term->term_id}))
													${"term".$term->term_id."_sanskrit_term"}=(${'mark_obtained_array_'.$term->term_id}['Term']== 'Ab') ? 'Ab' : ${'mark_obtained_array_'.$term->term_id}['Term'];
												
												if (array_key_exists("Activity",${'mark_obtained_array_'.$term->term_id}))
													${"term".$term->term_id."_sanskrit_activity"}=(${'mark_obtained_array_'.$term->term_id}['Activity']== 'Ab') ? 'Ab' : ${'mark_obtained_array_'.$term->term_id}['Activity'];
											}elseif($sub_row->name=='Computer Applications' || $sub_row->name=='Artificial Intelligence'){
												//var_dump(${'mark_obtained_array_'.$term->term_id})."<br/>";
												if (array_key_exists("Term",${'mark_obtained_array_'.$term->term_id}))
													${"term".$term->term_id."_computer"}=(${'mark_obtained_array_'.$term->term_id}['Term']== 'Ab') ? 'Ab' : ${'mark_obtained_array_'.$term->term_id}['Term'];
											}
												
										}
									}
								}
							
							}	
						}
	
					
					$sub_list = get_scholastic_subject_alloted_to_class($row1->class_id,$row1->academic_yr);
					foreach($sub_list as $sub_row){
						if($sub_row->name=='Sanskrit'){
							continue;
						}
						?>
			             <tr>
                             <td  class="col-md-1 td" style="vertical-align:middle;text-align:center;height:25px;"> 
								<?php
									if($sub_row->name=='Marathi'){
										echo "Marathi/ Sanskrit";
									}else{
										echo $sub_row->name;
									}
								?>
							</td>
 							<?php 
							
							foreach($term_list as $term){
								$total_marks_obtained=0;
								$highest_total_marks=0;
								
								${'mark_obtained_array_'.$term->term_id}=array();
								${'highest_marks_array_'.$term->term_id}=array();
								
								$exam_list	=	get_exams_by_class_per_term($row1->class_id,$term->term_id,$row1->academic_yr);
								foreach($exam_list as $exam){
										${'marks_resultarray_'.$term->term_id}	=	get_marks($exam->exam_id,$row1->class_id,$row1->section_id,$sub_row->sub_rc_master_id,$row1->student_id,$row1->academic_yr);
										//var_dump(${'marks_resultarray_'.$term->term_id})."<br/>";
										if(isset(${'marks_resultarray_'.$term->term_id}[0])){
											//As each subject is of 100 marks
											${'marks_obtained_json_'.$term->term_id}=${'marks_resultarray_'.$term->term_id}[0]['reportcard_marks'];
											//${'mark_obtained_array_'.$term->term_id}=array_merge(${'mark_obtained_array_'.$term->term_id},json_decode(${'marks_obtained_json_'.$term->term_id},true));
											${'mark_obtained_array_'.$term->term_id}=json_decode(${'marks_obtained_json_'.$term->term_id},true);

											${'highest_marks_json_'.$term->term_id}=${'marks_resultarray_'.$term->term_id}[0]['reportcard_highest_marks'];
											//${'highest_marks_array_'.$term->term_id}=array_merge(${'highest_marks_array_'.$term->term_id},json_decode(${'highest_marks_json_'.$term->term_id},true));
											${'highest_marks_array_'.$term->term_id}=json_decode(${'highest_marks_json_'.$term->term_id},true);

											if(isset(${'mark_obtained_array_'.$term->term_id}) && ${'mark_obtained_array_'.$term->term_id}<>null){

												if((${'count_of_mark_headings_'.$exam->exam_id}==count(${'mark_obtained_array_'.$term->term_id})) || $sub_row->name=='Marathi'){
													if($sub_row->name=='Marathi'){
														foreach (${'mark_obtained_array_'.$term->term_id} as $key => $value){
															if($term->term_id==1 && $row1->academic_yr=='2020-2021'){//Marking Scheme of 20-21 term 1 was different as Periodic test 1 was not conducted
																if($key=='Dictation'){
																	if(${"term".$term->term_id."_marathi_oral"}<>'Ab' && ${"term".$term->term_id."_marathi_dictation"}<>'Ab'){
																		$total_marks_obtained=$total_marks_obtained+((float)${"term".$term->term_id."_marathi_oral"}+(float)${"term".$term->term_id."_marathi_dictation"});
																		$highest_total_marks=$highest_total_marks+10;
																	}
												?>
																	<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;">
																	<?php 
																		if(${"term".$term->term_id."_marathi_oral"}<>"" && ${"term".$term->term_id."_marathi_dictation"}<>"" && ${"term".$term->term_id."_marathi_oral"}<>"Ab" && ${"term".$term->term_id."_marathi_dictation"}<>"Ab")
																			echo ((float)${"term".$term->term_id."_marathi_oral"}+(float)${"term".$term->term_id."_marathi_dictation"});
																	?>
																	</td>
												<?php
																}
																
																if($key=='Oral'){
																	if(${"term".$term->term_id."_sanskrit_dictation"}<>'Ab' && ${"term".$term->term_id."_sanskrit_oral"}<>'Ab'){
																		$total_marks_obtained=$total_marks_obtained+(float)${"term".$term->term_id."_sanskrit_dictation"}+(float)${"term".$term->term_id."_sanskrit_oral"};
																		$highest_total_marks=$highest_total_marks+10;
																	}
												?>
																	<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"><?php echo ${"term".$term->term_id."_sanskrit_dictation"};?></td>
																	<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"><?php echo ${"term".$term->term_id."_sanskrit_oral"};?></td>
												<?php	
																}
																if($key=='Term'){
																	if(${"term".$term->term_id."_sanskrit_term"}<>'Ab' && ${"term".$term->term_id."_marathi_term"}<>'Ab'){
																		$total_marks_obtained=$total_marks_obtained+(float)${"term".$term->term_id."_sanskrit_term"}+(float)${"term".$term->term_id."_marathi_term"}; //28-09-20
																		$highest_total_marks=$highest_total_marks+80;
																	
												?>
																		<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"><?php echo ((float)${"term".$term->term_id."_marathi_term"}+(float)${"term".$term->term_id."_sanskrit_term"});?></td>
												<?php				}else{?>
																		<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"><?php echo 'Ab';?></td>
												<?php
																	}
																}
															}else{
																/*if($key=='Oral'){
																	if(${"term".$term->term_id."_marathi_oral"}<>'Ab' && ${"term".$term->term_id."_sanskrit_oral"}<>'Ab'){
																		$total_marks_obtained=$total_marks_obtained+((float)${"term".$term->term_id."_marathi_oral"}+(float)${"term".$term->term_id."_sanskrit_oral"});
																		$highest_total_marks=$highest_total_marks+10;
												?>
																		<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:center;text-align:center;"><?php echo ((float)${"term".$term->term_id."_marathi_oral"}+(float)${"term".$term->term_id."_sanskrit_oral"});?></td>
												<?php 				}else{ ?>
																		<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:center;text-align:center;"><?php echo 'Ab';?></td>
												<?php
																	}
												?>
												<?php
																}*/

																if($key=='Activity'){
																	if(${"term".$term->term_id."_marathi_activity"}<>'' && ${"term".$term->term_id."_sanskrit_activity"}<>'' && ${"term".$term->term_id."_marathi_activity"}<>'Ab' && ${"term".$term->term_id."_sanskrit_activity"}<>'Ab'){ //Lija 21-03-23
																		$total_marks_obtained=$total_marks_obtained+(float)${"term".$term->term_id."_marathi_activity"}+(float)${"term".$term->term_id."_sanskrit_activity"};
																		$highest_total_marks=$highest_total_marks+10;
																	}
												?>
																	<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"><?php echo ${"term".$term->term_id."_marathi_activity"};?></td>
																	<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"><?php echo ${"term".$term->term_id."_sanskrit_activity"};?></td>
												<?php	
																}
																if($key=='Term'){ //Lija 21-03-23
																	if(${"term".$term->term_id."_sanskrit_term"}<>'' && ${"term".$term->term_id."_marathi_term"}<>'' && ${"term".$term->term_id."_sanskrit_term"}<>'Ab' && ${"term".$term->term_id."_marathi_term"}<>'Ab'){ //Lija 21-03-23
																	   	$total_marks_obtained=$total_marks_obtained+(float)${"term".$term->term_id."_sanskrit_term"}+(float)${"term".$term->term_id."_marathi_term"}; //28-09-20
																		$highest_total_marks=$highest_total_marks+80;
																	
												?>
																		<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"><?php echo ((float)${"term".$term->term_id."_marathi_term"}+(float)${"term".$term->term_id."_sanskrit_term"});?></td>
												<?php 				}else{?>
																		<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"><?php echo ${"term".$term->term_id."_sanskrit_term"};?></td>
												<?php				}
																}
															}
														}
														
													}else{
														//If not marathi

														foreach (${'mark_obtained_array_'.$term->term_id} as $key => $value){
															if($value<>'Ab'){//Lija 21-03-23 As round was converting Ab to 0
																$total_marks_obtained=$total_marks_obtained+round((float)$value);
																$highest_total_marks=$highest_total_marks+${'highest_marks_array_'.$term->term_id}[$key];//28-09-20
																$value=round($value);
															}
							?> 
															<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;;"><?php echo $value;?></td>
							<?php						}
													}
									}else{
										//When count of mark headings dont match
								?>
										<td class="col-md-1 col-sm-1 col-xs-1 td"  style="text-align:center;cellpadding:0;cellspacing:0" colspan="<?php echo ${'count_of_mark_headings_'.$term->term_id};?>">
											<table class="col-md-12 col-sm-12 col-xs-12" border="0" style="border: 0px solid black;" width="100%">
												<tr>
								<?php
								                $highest_total_marks=0;
												foreach (${'mark_obtained_array_'.$term->term_id} as $key => $value){
													if($value<>'Ab')//Lija 21-03-23
														$highest_total_marks=$highest_total_marks+(float)${'highest_marks_array_'.$term->term_id}[$key];
		
								?>
													<td class="col-md-1" style="vertical-align:middle;text-align:center;height:20px;"><?php echo $key."(".${'highest_marks_array_'.$term->term_id}[$key].")";?></td>
								<?php			} ?>
												</tr>
												<tr>
								<?php
												foreach (${'mark_obtained_array_'.$term->term_id} as $key => $value){
													if($value<>'Ab')//Lija 21-03-23
														$total_marks_obtained=$total_marks_obtained+(float)$value;
													
								?>
													<td class="col-md-1" style="vertical-align:middle;text-align:center;height:20px;"><?php echo $value;?></td>
								<?php			} 
								?>
												</tr>
											</table>
										</td>
									
								<?php	} 
								        
										//${"grand_total_marks_".$term->term_id}=${"grand_total_marks_".$term->term_id}+$total_marks_obtained;
								}else{
								?>	
											<td class="col-md-1 col-sm-1 col-xs-1 td" colspan="<?php echo (${'count_of_mark_headings_'.$term->term_id}+2);?>" style="vertical-align:middle;text-align:center;"><?php echo (${'count_of_mark_headings_'.$term->term_id});?></td> 
							<?php 	
								}
							}else{
								if(($sub_row->name=='Computer Applications' || $sub_row->name=='Artificial Intelligence') && (strtolower($exam->name)=='periodic test 2' || strtolower($exam->name)=='periodic test 1') && ${"term".$term->term_id."_computer"}<>'') { //Lija 10-09-21
								//Do nothing
								}elseif(($sub_row->name=='Computer Applications' || $sub_row->name=='Artificial Intelligence') && (strtolower($exam->name)=='periodic test 2' || strtolower($exam->name)=='periodic test 1') && ${"term".$term->term_id."_computer"}=='') { //Lija 10-09-21
								?>
								<td class="col-md-1 col-sm-1 col-xs-1 td" style="vertical-align:center;text-align:center;"></td>
								<?php
								}elseif(($sub_row->name=='Marathi') && (strtolower($exam->name)=='periodic test 2' || strtolower($exam->name)=='periodic test 1') ){ //Lija 10-09-21
								?>
									
									<?php
										if(${"term".$term->term_id."_marathi_oral"}=='' && ${"term".$term->term_id."_sanskrit_oral"}==''){
									?>
											<td class="col-md-1 col-sm-1 col-xs-1 td" style="vertical-align:center;text-align:center;"></td>
									<?php
										}else{
									?>
											<td class="col-md-1 col-sm-1 col-xs-1 td" style="vertical-align:middle;text-align:center;">
											<?php echo (float)${"term".$term->term_id."_marathi_oral"}+(float)${"term".$term->term_id."_sanskrit_oral"};
												$total_marks_obtained=$total_marks_obtained+((float)${"term".$term->term_id."_marathi_oral"}+(float)${"term".$term->term_id."_sanskrit_oral"});
												$highest_total_marks=$highest_total_marks+10;
											?></td>
								<?php
										}
								}elseif(($sub_row->name=='Marathi') && (strtolower($exam->name)<>'periodic test 2' || strtolower($exam->name)<>'periodic test 1') ){ //Lija 21-03-23
								
							?>
								<td class="col-md-1 col-sm-1 col-xs-1 td" colspan="<?php echo ${'count_of_mark_headings_'.$term->term_id};?>" style="vertical-align:center;text-align:center;"></td>
							<?php
								}else{
									for($i=0;$i<${'count_of_mark_headings_'.$exam->exam_id};$i++){
								?>
									<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"></td>
							<?php
									}
								}
							?>
								<!--td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:center;text-align:center;">T</td>
								<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:center;text-align:center;">G</td-->
							<?php
							}
						}?>
						<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"><?php echo $total_marks_obtained;?></td>

						<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;">
						<?php
							if($total_marks_obtained<>""){
								${"grand_total_marks_".$term->term_id}=${"grand_total_marks_".$term->term_id}+$total_marks_obtained;
								${'grand_highest_marks_'.$term->term_id}=${'grand_highest_marks_'.$term->term_id}+$highest_total_marks;
							}
							if($highest_total_marks<>0){
								$percent=($total_marks_obtained*100)/$highest_total_marks;
								$final_grade = get_grade_based_on_marks(round($percent),'Scholastic',$row1->class_id); //Lija 28-09-20
								echo $final_grade;
							}
						?>
						</td>
					<?php
					}?>
                     </tr>
			<?php }?>
					<tr>
						<td class="td" style="vertical-align:middle;text-align:center;height:30px;">TOTAL</td>
						<?php
						foreach($term_list as $term){
							if(isset(${'general_highest_marks_array_'.$term->term_id}) && ${'general_highest_marks_array_'.$term->term_id}<>null){
								foreach (${'general_highest_marks_array_'.$term->term_id} as $key => $value){

						?>
									<td class="col-md-1 td" ></td>
						<?php }
							$grand_grade="";
						    if(${'grand_highest_marks_'.$term->term_id}<>0){
								$grand_marks_per_100=(${"grand_total_marks_".$term->term_id}*100)/${'grand_highest_marks_'.$term->term_id}; //Convert to out of 100
								$grand_grade = get_grade_based_on_marks(round($grand_marks_per_100),'Scholastic',$row1->class_id); //Lija 28-09-20
							}				
						?>
							<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;"><?php echo ${"grand_total_marks_".$term->term_id}."/".${'grand_highest_marks_'.$term->term_id};?></td>
								
							<td class="col-md-1 td"  style="vertical-align:middle;text-align:center;height:30px;"><?php echo $grand_grade;?></td>
						<?php
							}else{
						?>
							   <td class="col-md-1 col-sm-1 col-xs-1 td" colspan="<?php echo (${'count_of_mark_headings_'.$term->term_id}+2);?>" style="vertical-align:middle;text-align:center;"></td> 
                        <?php
                            }
                        }
                        ?>
					</tr>
	   
		   
				</table>
				</td>
			</tr>
		</table>
        
        <table class="table-responsive" style="width:92%;margin-left: 5%;margin-right: auto;border-spacing: 0px;background-color:white;">
			 <tr>
				 <td style="" cellpadding="0" cellspacing="0">
			<table class="table-responsive" style="width:90%;margin-left: 0%;margin-right: auto;border-spacing: 0px;background-color:white;border-size:0" cellpadding="0" cellspacing="0">
			 <tr>
				 <td style="vertical-align:middle;" cellpadding="0" cellspacing="0">
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white; font-size:15px;" cellpadding="0" cellspacing="0">
                        <tr>
                            <?php  
							$term_list	=	get_published_terms($row1->class_id,$row1->section_id);
                            $colcount = count($term_list) +1;
                            ?>
                            
							<?php if($row1->academic_yr=='2020-2021'){?>
								<th class="col-md-3 th" colspan="<?php echo $colcount;?>">Co- Scholastic Areas Term 1 & 2<br/>(On a 3 point (A-C) grading scale)</th>
							<?php }else{?>
								<th class="col-md-3 th" colspan="<?php echo $colcount;?>">Co- Scholastic Areas Term 1 & 2<br/>(On a 5 point (A-E) grading scale)</th>
							<?php }?>
                        </tr>
                        <tr>
							
							<th class="col-md-5 th">Subjects</th>
							<?php foreach($term_list as $term): ?>
                            <th class="col-md-1 th1"  width="" style="vertical-align:middle;text-align:center;height:30px;"><?php echo $term->name;?></th>
							 <?php
                            endforeach;
                            ?>
                        </tr>
                         <?php $sub_list = get_coscholastic_subject_alloted_to_class($row1->class_id,$row1->academic_yr);
               
                        foreach($sub_list as $sub_row):?>
		
			             <tr>
                             <td  class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;">
								<?php 
									$acd_yr_frm=substr($row1->academic_yr,0,4);
									if($acd_yr_frm>=2023 && $sub_row->name=="GK"){
										echo "V.Ed / G.K";
									}else{
										echo $sub_row->name;
									}
								?>
							 </td>
                             
                             <?php 
							 foreach($term_list as $term){
								${'mark_obtained_array_'.$term->term_id}=array();
								$exam_list	=	get_exams_by_class_per_term($row1->class_id,$term->term_id,$row1->academic_yr);
								$coscholastic_grade="";
								foreach($exam_list as $exam){
									${'marks_resultarray_'.$term->term_id}	=	get_marks($exam->exam_id,$row1->class_id,$row1->section_id,$sub_row->sub_rc_master_id,$row1->student_id,$row1->academic_yr);
									
									if(isset(${'marks_resultarray_'.$term->term_id}[0])){
										${'marks_obtained_json_'.$term->term_id}=${'marks_resultarray_'.$term->term_id}[0]['reportcard_marks'];
										${'mark_obtained_array_'.$term->term_id}=array_merge(${'mark_obtained_array_'.$term->term_id},json_decode(${'marks_obtained_json_'.$term->term_id},true));
										
										if(isset(${'mark_obtained_array_'.$term->term_id}) && ${'mark_obtained_array_'.$term->term_id}<>null){
											
											foreach (${'mark_obtained_array_'.$term->term_id} as $key => $value){
												if($value=='Ab')
													$coscholastic_grade="Ab";
												else{
													$coscholastic_grade= get_grade_based_on_marks(round($value),'Co-Scholastic',$row1->class_id); //Lija 28-09-20
												}
											}
										}
									}
								}
							?>
							<td class="td" style="vertical-align:middle;text-align:center;"><?php echo $coscholastic_grade;?></td>
							<?php
							 }
                             ?>
						</tr>
                        <?php endforeach;?>
					</table>
                 </td>
			</tr>
		</table>
                 </td>
                 <td></td>
                 <td>
			<table class="table-responsive" style="width:auto;margin-left: 0%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 0%;border: 1px solid grey" cellpadding="0" cellspacing="0">
                         <tr>
                            <th colspan="2" style="text-align:center;"> <div style="font-size:15px"> GRADING SCALE FOR SCHOLASTIC AREAS</div><div style="font-size:12px"> Grades are awarded on a 8 Point grading scale as follows</div></th>
                        </tr>
                        <tr>
                            <td class="col-md-2 lasttd"  style="">MARKS RANGE</td>
                            <td class="col-md-1 lasttd">GRADE</td>
                         </tr>
                         <tr>
                            <td class="lasttd">91-100</td>
                            <td class="lasttd">A1</td>
                         </tr>
                         <tr>
                            <td class=" lasttd">81-90</td>
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
        
			<table class="table-responsive" style="width:85%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="10" cellspacing="10" border="0">
			<tr> 
				<td>
					<?php 
						$remark_string="";
						$width="100%";
						foreach($term_list as $term){ 
							$remark=get_reportcard_remark_of_a_student($row1->student_id,$term->term_id);
							if($remark_string=="" && $remark<>""){
								$remark_string=$remark_string."Term ".$term->term_id." - ".$remark;

							}elseif($remark_string<>"" && $remark<>""){
								$remark_string=$remark_string."<br/> Term ".$term->term_id." - ".$remark;

							}else{
								$remark_string="<br>";
								$width="90%";
							}
						}
						
					?>
			       <table class="table-responsive" width="<?php echo $width;?>" style="border-spacing: 0px;background-color:white;">
                        <tr>
            				<td style="font-size:12px;text-align:left;white-space:nowrap;width: 25%;"><b>Class Teacher's Remark :</b></td>
            				<td style="font-size:12px;">
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
			    /*
				if(count($term_list)==1){
					$date_from=get_academic_yr_from_of_particular_yr($acd_yr);
					$date_to=date_format(date_create(substr($date_from,0,4)."-09-30") , 'Y-m-d') ; // Creating date to as last day of sep;
				}elseif(count($term_list)==2){
					$date_from=date_format(date_create(substr($date_from,0,4)."-10-01") , 'Y-m-d') ; // Creating date to as first day of Oct;
					$date_to=get_academic_yr_to_of_particular_yr($acd_yr);
				}
				*/
				$date_from=getSettingsDataForAcademicYr($row1->academic_yr)->academic_yr_from;
				if(count($term_list)==1){
					$date_to=date_format(date_create(substr($date_from,0,4)."-09-30") , 'Y-m-d') ; // Creating date to as last day of sep;
				}elseif(count($term_list)==2){
					$date_to=getSettingsDataForAcademicYr($row1->academic_yr)->academic_yr_to;
				}
			?>
        <tr> 
            <td>
				<table class="table-responsive" style="width:100%;margin-left:auto;margin-right: auto;border-spacing: 0px;background-color:white;">
                    <tr>
						<td style="font-size:12px;text-align:left;white-space:nowrap;width: 10% !important" ><b> Attendance : </b></td>
    					<td style="font-size:12px;white-space:nowrap;width:20%!important;margin-right:2%;text-align:center;" ><div class="statistics_line">
						<?php 
						if(get_total_stu_attendance_till_a_month($row1->student_id,$date_from,$date_to,$row1->academic_yr)<>""){
							echo get_total_stu_attendance_till_a_month($row1->student_id,$date_from,$date_to,$row1->academic_yr)."/".get_total_stu_workingday_till_a_month($row1->student_id,$date_from,$date_to,$row1->academic_yr);
						}
						?>&nbsp;
							</div> 
						</td>
						<?php if(count($term_list)>1){?>
							<td style="font-size:12px;width: 15%;"><b> Promoted To :</b></td>
							<?php
								$promote_to ="";
								if(isset($term_list[1]->term_id))
									$promote_to = get_promote_to_of_a_student($row1->student_id,$term_list[1]->term_id);
								
							?>
							<td style="margin-left:5%;text-align:center;font-size:12px;">
								<div class="statistics_line">
									<?php echo $promote_to;?>&nbsp;
								</div> 
							</td>
							<td style="font-size:12px;width: 21%;"><b> Date Of Reopening :</b></td>
							<td style="text-align:center;font-size:12px;">
								<div class="statistics_line">
									<?php 
										$reopen_date=get_school_reopen_date($row1->class_id,$row1->section_id);
										if($reopen_date<>NULL && $reopen_date<>'0000-00-00')
											echo date_format(date_create($reopen_date),'d-m-Y');
									?>
									&nbsp;
								</div>
							</td>
						<?php }else{?>
						<td style="width: auto"> </td>
					<?php }?>
                    </tr>
                </table>

			 </td>
             
        </tr>
		</table>
        <br>
		<table class="table-responsive" style="width:85%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;overflow: visible !important;" cellpadding="1" cellspacing="10">
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
				<td style="width:35%;">
					<table class="table-responsive" width="100%" cellspacing="0">
						<tr>
							<td style="font-size:13px;" width="30%;text-align: center;"><b>Class Teacher's Sign.</b></td>
						</tr>
					</table>
				</td>
				<td style="width:35%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<th style="font-size:10px;text-align:center;" width="15%" ></th>
							<td style="font-size:13px;text-align:left;width:30%;"><b>Parent's Sign.</b></td>
						</tr>
					</table>
				</td>
				<td style="width:30%;">
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
<?php endforeach;?>

</head>
<body>