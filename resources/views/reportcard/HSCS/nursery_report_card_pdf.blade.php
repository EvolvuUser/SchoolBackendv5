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
    background-image: url('<?php echo base_url();?>uploads/kg_new.jpg');
   -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;
    height:1100px;
     margin-bottom:3px;
}
.bgimg1 {
    background-image: url('<?php echo base_url();?>uploads/kg_BLANK.jpg');
   -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;
     height:1120px;
}

.vertical-text {
   writing-mode: vertical-lr; /* Set vertical writing mode */
            white-space: nowrap; /* Prevent text from wrapping */
            transform: rotate(90deg); /* Adjust orientation if needed */
            display: inline-block; /* Ensures the element behaves as a block */
            /*width: 20px;*/
            padding:0px;
   
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
        height:25px;
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
        height:22px;
		font-size:13px;
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
    
    .pdfdiv {
   page: LC;
   page-break-after: always;
}
.pdfdiv:last-child{
    page-break-after: avoid;
    page-break-inside: avoid;
    /*margin-bottom: 0px;*/
}

    *{
    margin: 0;
    padding: 0;
}
.rate {
    float: left;
    height: 23px;
    padding: 0 10px;
}
.rate:not(:checked) > input {
    position:absolute;
    top:-9999px;
}
.rate:not(:checked) > label {
    float:right;
    width:1em;
    overflow:hidden;
    white-space:nowrap;
    cursor:pointer;
    font-size:15px;
    color:#ccc;
}
.rate:not(:checked) > label:before {
    content: '★ ';
}
.rate > input:checked ~ label {
    color: #ffc700;    
}
.rate:not(:checked) > label:hover,
.rate:not(:checked) > label:hover ~ label {
    color: #deb217;  
}
.rate > input:checked + label:hover,
.rate > input:checked + label:hover ~ label,
.rate > input:checked ~ label:hover,
.rate > input:checked ~ label:hover ~ label,
.rate > label:hover ~ input:checked ~ label {
    color: #c59b08;
}

.atd{
 		vertical-align:middle;
        text-align:center;
        height:15px;
		font-size:14px;
        border:1px solid grey;
        padding-top: 8px;
    }


</style> 

<?php 
	if(isset($class_id) && isset($section_id)){
   
 $student_info1 = $this->crud_model->get_students($class_id,$section_id,$this->session->userdata['acd_yr']); 
 
}else {
    $student_info1	=	$this->crud_model->get_student_info($student_id,$this->session->userdata['acd_yr']);
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
        	<table border="0"  class="table-responsive" style="table-layout:fixed;width:750px;margin-left:9%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 1%;" cellpadding="1" cellspacing="10">
				<tr> 
					<td style="height:50px;width:12%;border: 1px solid black;border-radius: 5px;">
				<!--<img height="20%" width="50%" src="https://holyspiritconvent.evolvu.in/test/hscs_test/uploads/3053.jpg">--></td>
	 
					<td style="padding:2%;margin-left:12%;width:40%;text-color:black;">   <h4 style="color: black;">A 360ᵒ View of (<?php echo $row1['first_name']." ".$row1['mid_name']." ".$row1['last_name'];?>)</h4><p style="color: black;">(A child should not be judged only his/her academic performance. Love the child for what he/she is, not for how much he/she scores)</p></td>
				 </tr>
			</table>
			<br>
			<table border="0" class="table-responsive" style="table-layout:fixed;width:750px;margin-left:7%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 10%;border:1px solid grey;padding:1%;" cellpadding="2" cellspacing="10">
				<tr> 
					<td style="width:100%;text-align: left;">
						<table border="0" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
							<td style="font-size:14px;width: 25%; padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">NAME OF THE STUDENT </td>
							<td style="font-size:14px;text-align: center;"><div class="statistics_line"><?php echo $row1['first_name']." ".$row1['mid_name']." ".$row1['last_name'];?></div> </td>						
						</table>
					</td>
				</tr>
				<tr>
					<td style="width:100%;text-align: left;">
						<table border="0" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
							<td style="width: 6%;font-size:14px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">CLASS </td>
							<td style="width: 15%;text-align: center;"><div class="statistics_line"><?php echo $this->crud_model->get_class_name($row1['class_id']);?></div></td>
							<td style="width: 8%;font-size:14px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">SECTION </td>
							<td style="width: 15%;text-align: center;"><div class="statistics_line"><?php echo $this->crud_model->get_section_name($row1['section_id']);?></div></td>
							<td style="margin-left: 10px;width:15%;font-size:14px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">ROLL NO. </td>
							<td style="margin-left: 10px;width:auto;text-align: center;"><div class="statistics_line"> <?php echo $row1['roll_no'];?></div></td>
						</table>
						
					</td>
					
				</tr>
				<tr>
					<td style="width:100%;text-align: left;">
						<table border="0" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						  <td style="width: 20%;font-size:14px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">ADMISSION NO. </td>
							<td style="width: 15%;text-align: center;"><div class="statistics_line"><?php echo $row1['reg_no'];;?></div></td>
							<td style="font-size:14px;width:17%;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;"> AADHAR NO </td>
							<td style="font-size:14px;width:auto;text-align: center;"><div class="statistics_line"> <?php echo $row1['stu_aadhaar_no'];?></div></td>
						</table>
						
					</td>
					
				</tr>
				<tr>
					<td style="width:100%;text-align: left;">
						<table border="0" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						  <td style="font-size:14px;width: 7%;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">DATE OF BIRTH </td>
							<td style="font-size:14px;width:30%;text-align: center;"><div class="statistics_line"><?php echo date_format(date_create($row1['dob']),'d-m-Y');?></div></td>
						</table>
						
					</td>
					
				</tr>
				<?php $parent_info = $this->crud_model->get_parent_info($row1['parent_id']);?>
				<tr>
					<td style="width:100%;text-align: left;">
						<table border="0" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						  <td style="font-size:14px;width: 8%;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">MOTHER´S NAME </td>
							<td style="width:30%;;text-align: center;"><div class="statistics_line"><?php echo $parent_info[0]['mother_name'];?></div></td>
						</table>
						
					</td>
					
				</tr>
				<tr>
					<td style="width:100%;text-align: left;">
						<table border="0" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						  <td style="font-size:14px;width: 8%;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">FATHER´S NAME </td>
							<td style="width:30%;;text-align: center;"><div class="statistics_line"><?php echo $parent_info[0]['father_name'];?></div></td>
						</table>
						
					</td>
					
				</tr>
				<tr>
					<td style="width:100%;text-align: left;">
						<table border="0" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						   <td style="font-size:14px;width: 18%;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">MOBILE NO. FATHER </td>
							<td style="font-size:14px;width:30%;text-align: center;"><div class="statistics_line"><?php echo $parent_info[0]['f_mobile'];?></div></td>   
							 <td style="font-size:14px;width: 6%;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">MOTHER </td>
							<td style="font-size:14px;width:30%;;text-align: center;"><div class="statistics_line"><?php echo $parent_info[0]['m_mobile'];?></div></td>   
						</table>
						
					</td>
					
				</tr>
			</table>
			<br>
			<h3 style="margin-right: 69%;margin-top:35%;">All About Me</h3>
			
			<table class="table-responsive" style="width:750px;margin-left:7%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0" >
			    <?php 
    				$allaboutme_master = $this->assessment_model->get_studentaboutme_master_by_class_id($row1['class_id']);
    				foreach($allaboutme_master as $aam_r):
    				    $allaboutme_value=$this->assessment_model->get_allaboutme_for_student($row1['student_id'],$aam_r['am_id'],$this->session->userdata['acd_yr']);
    				?>
    				    <tr>
                            <td  class="atd" width="30%" ><?php echo $aam_r['name']; ?></td>
                            <td  class="atd" width="70%"><?php echo $allaboutme_value[0]['aboutme_value'];?>
                            </td>
                        </tr> 
            <?php   endforeach; ?>
   
			</table>
			<br>
			
			<table class="table-responsive" style="width:750px;margin-left:7%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 27%;" cellpadding="0" cellspacing="0" >
				<tr> 
					<?php 
					$reportcard_publish_term1_value=$this->assessment_model->get_reportcard_publish_value_for_exam($row1['class_id'],$row1['section_id'],'Term I');//report card publish value for Term I exam
                    $reportcard_publish_term2_value=$this->assessment_model->get_reportcard_publish_value_for_exam($row1['class_id'],$row1['section_id'],'Term II');//report card publish value for Term II exam
					$term	=	$this->assessment_model->get_term();
					foreach($term as $row):
					//if($row['name']!='Term 2'){?>
					<td class="td" style="valign:top;inline-block;text-align: center;font-size:14px;" colspan="3"><?php echo $row['name'];?></td>
					<?php 
					  //  }
					endforeach;?>
				</tr>
				<tr> 
					<?php $term	=	$this->assessment_model->get_term();
					foreach($term as $row):
					//if($row['name']!='Term 2'){ ?>
					<td style="font-size:14px;width:10%;text-align: center;valign:top;inline-block;border-left:1px solid grey;border-bottom:1px solid grey;height:5%;">My height</td>
					<td class=""  style="font-size:14px;valign:top;inline-block;width:10%;border-bottom:1px solid grey;"> 
					    <div class="statistics_line">
					    <?php 
					    if(${'reportcard_publish_term'.$row['term_id'].'_value'}=='Y'){
					        echo $row1['height'];
					    }
					    ?>
					    </div>
					</td> 
					<td   style="font-size:14px;valign:top;inline-block;width:10%;border-bottom:1px solid grey;border-right:1px solid grey;">Hand Spans</td>
					<?php
					//}
					endforeach;?>
				</tr>
				<tr> 
					<?php $term	=	$this->assessment_model->get_term();
					foreach($term as $row):
					//if($row['name']!='Term 2'){ ?>
					<td style="font-size:14px;width:10%;;text-align: center;valign:top;inline-block;border-left:1px solid grey;border-bottom:1px solid grey;height:5%;">My weight is</td>
					<td style="font-size:14px;valign:top;inline-block;width:10%;border-bottom:1px solid grey;"> 
					    <div class="statistics_line">
					        <?php
					            if(${'reportcard_publish_term'.$row['term_id'].'_value'}=='Y'){
					                echo $row1['weight'];
					            }
					        ?>
					    </div>
					</td> 
					<td style="font-size:14px;valign:top;inline-block;width:10%;border-right:1px solid grey;border-bottom:1px solid grey;">kgs.</td>
					<?php 
					   // }
					endforeach;?>
				</tr>
			</table>
        <br>
		</div>
        <br><br>
    </div>
    <div class="col-md-12 pdfdiv bgimg1">
		<div class="col-md-2"></div>
		<div class="col-md-8 table-responsive " style="text-align:center;">
			
			<table class="table-responsive" style="width:750px;margin-left:7%;margin-right: auto;border-spacing: 0px;margin-top: 7%;border:0;" cellpadding="0" cellspacing="0" >
				<tr>
					<th style="width:30%;">A glimpse of myself(child photo)</th>
					<th style="height:250px;width:70%;border: 1px solid grey;border-radius: 5px;padding:2%;overflow-x: hidden;" align="center" valign="middle">
					    <img src="<?php echo base_url();?>/uploads/student_image/<?php echo $row1['image_name'];?>" width="auto" height="200px" style="text-align:center;"> 
					</th>
				</tr>
				<tr>
					<th style=""></th>
					<th style="height:10px;padding:2%;"></th>
				</tr>
				<tr>
					<th style="width:30%;">A glimpse of my family (child with family photo)</th>
					<th style="height:300px;width:70%;border: 1px solid grey;border-radius: 5px;padding:2%;overflow-x: hidden;" align="center" valign="middle">
					    <img src="<?php echo base_url();?>/uploads/family_image/<?php echo $parent_info[0]['family_image_name'];?>" width="auto" height="250px" style="text-align:center;"> 
					</th>
				</tr>
			</table>
        </div>
    </div>
	<div class="col-md-12 pdfdiv bgimg1">
		<div class="col-md-2"></div>
		<div class="col-md-8 table-responsive " style="text-align:center;">
			 <table class="table-responsive" style="width:750px;margin-left:7%; margin-right: auto; border-spacing: 0px; background-color:white; margin-top: 7%;border:1px solid black;" cellpadding="0" cellspacing="0" >
				<?php 
				$domain_p = $this->assessment_model->get_domain_master_by_class_id($row1['class_id']);
				$j=1; 
				foreach($domain_p as $r):?>
					<tr>
						<!--<td></td>-->
						<th class="th" style="font-size:14px;padding:2px;width:25%;"><b><?php echo $r['name'];?></b><br><?php echo $r['description'];?></th>
						<th class="th" style="font-size:14px;text-align:center;padding:2px;width:10%;">Beginner</th>
						<th class="th" style="font-size:14px;text-align:center;padding:2px;width:10%;">Progressing</th>
						<th class="th" style="font-size:14px;text-align:center;padding:2px;width:10%;">Proficient</th>
						<th class="th" style="font-size:14px;text-align:center;padding:2px;width:10%;">Advance</th>
					</tr>
					<?php 
						$parameters = $this->assessment_model->get_parameter_by_dm_id($r['dm_id']);
						foreach($parameters as $p):
						
						?>
							<tr>
								<td class="td" style="word-wrap:normal;font-size:14px;padding:2px;"><?php echo $p['parameter'];?></td>

								<?php 
								//foreach($term as $row):
									$parameter_value= $this->assessment_model->get_published_domain_parameter_value_by_id($row1['student_id'],$p['parameter_id'],'2',$this->session->userdata['acd_yr']);
									//print_r($parameter_value);
									//$parameter_value='Beginner';
			
								?>
								<td class="td" style="vertical-align:center;text-align:center;"><?php if($parameter_value=='Beginner'){ ?><img height="30px" width="30px" src="<?php echo base_url();?>uploads/begg.jpg" alt="Checkmark"> <?php } ?></td>
								<td class="td" style="vertical-align:center;text-align:center;"><?php if($parameter_value=='Progressing'){ ?><img height="30px" width="30px" src="<?php echo base_url();?>uploads/prog.jpg" alt="Checkmark"> <?php } ?></td>
								<td class="td" style="vertical-align:center;text-align:center;"><?php if($parameter_value=='Proficient'){ ?><img height="30px" width="30px" src="<?php echo base_url();?>uploads/prof.jpg" alt="Checkmark"> <?php } ?></td>
								<td class="td" style="vertical-align:center;text-align:center;"><?php if($parameter_value=='Advance'){ ?><img height="30px" width="30px" src="<?php echo base_url();?>uploads/adv.jpg" alt="Checkmark"> <?php } ?></td>
								  <?php //endforeach;?>
							</tr>
					  
			        <?php 	endforeach;
				endforeach;?>
        </table> 
		<br>
        <br>
        <!--/div>
        <br>
        <br>
    </div>
    <div class="col-md-12 pdfdiv bgimg1">
		<div class="col-md-2"></div>
		<div class="col-md-8 table-responsive " style="text-align:center;"-->
	        <table class="table-responsive" style="width:750px;margin-left:7%; margin-right: auto; border-spacing: 0px; margin-top: 45%;" cellpadding="0" cellspacing="0" >
				<tr>
					<td style="width:100%;vertical-align:middle;text-align: center;" cellpadding="0" cellspacing="0">
					<table border="1" class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						<tr>
							<?php  $term_list	=	$this->assessment_model->get_published_terms($row1['class_id'],$row1['section_id']);
							$exam_list	=	$this->assessment_model->get_published_exams_of_a_class($row1['class_id'],$row1['section_id'],$row1['academic_yr']);
							//print_r($term_list);
                            
							$colspan_for_scholastic=0;
							foreach($exam_list as $exam){	
								${'marks_headings_'.$exam['exam_id']}=$this->assessment_model->get_marks_heading_class($row1['class_id'],1,$exam['exam_id'],$row1['academic_yr']);
								$colspan_for_scholastic=$colspan_for_scholastic+count(${'marks_headings_'.$exam['exam_id']});
							}			
							$count_of_exams=count($exam_list);

					?>
							<th class="th" colspan="<?php echo $colspan_for_scholastic+3;?>" style="overflow-x: hidden;word-wrap: break-word;font-size:14px"><b>Scholastic Areas</b></th>
							</tr>
						<tr>
							<th class="col-md-3 td" style="" rowspan="2">SUBJECT</th>
							<?php 
								foreach($exam_list as $exam){	
									${'total_marks_'.$exam['exam_id']}=0;
									${'highest_total_marks_'.$exam['exam_id']}=0;
									//${'marks_headings_'.$exam['exam_id']}=$this->assessment_model->get_marks_heading_class($row1['class_id'],1,$exam['exam_id'],$row1['academic_yr']);
									
									$exam_name=(strpos( $exam['name'], '(')>0?substr($exam['name'], 0, strpos( $exam['name'], '(')):$exam['name']);
							?> 
										<th class="td" colspan="<?php echo count(${'marks_headings_'.$exam['exam_id']});?>" style="vertical-align:middle;text-align:center;"><?php echo $exam_name;  ?></th>
							 <?php 		
								}
							 ?>
								<th class=" td" style="" colspan="2"></th>
								<!--<th class=" td" style="" ></th>-->
						</tr>	
						<tr>
						
							<?php 
								$total_highest_marks_for_heading=0;
								foreach($exam_list as $exam){
									
									${'count_of_mark_headings_'.$exam['exam_id']}=0;
									//$marks_headings = $this->assessment_model->get_marks_heading_class($row1['class_id'],1,$exam['exam_id'],$row1['academic_yr']);
									//${'general_highest_marks_json_'.$exam['exam_id']}="{";
									foreach(${'marks_headings_'.$exam['exam_id']} as $mrow){
										$total_highest_marks_for_heading=$total_highest_marks_for_heading+$mrow['highest_marks'];
										${'count_of_mark_headings_'.$exam['exam_id']}=${'count_of_mark_headings_'.$exam['exam_id']}+1;
										/*${'general_highest_marks_json_'.$exam['exam_id']}=${'general_highest_marks_json_'.$exam['exam_id']}.'"'.$mrow['marks_headings_name'].'":"'.$mrow['highest_marks'].'",';
										
									
									${'general_highest_marks_json_'.$exam['exam_id']}=rtrim(${'general_highest_marks_json_'.$exam['exam_id']},","); //Lija report card
									${'general_highest_marks_json_'.$exam['exam_id']}=${'general_highest_marks_json_'.$exam['exam_id']}."}";
									${'general_highest_marks_array_'.$exam['exam_id']}=array_merge(${'general_highest_marks_array_'.$exam['exam_id']},json_decode(${'general_highest_marks_json_'.$exam['exam_id']},true));
									//echo ${'general_highest_marks_json_'.$term['term_id']}."<br>"; 
									${'count_of_mark_headings_'.$exam['exam_id']}=count(${'general_highest_marks_array_'.$exam['exam_id']});
									*/
								
								//echo "count ".${'count_of_mark_headings_'.$exam['exam_id']};
                            ?>
							 <th class="td" style="text-align:center;white-space:nowrap;" ><?php echo $mrow['marks_headings_name']."<br/>(".$mrow['highest_marks'].")"?></th>
                         <?php 
									}
								}
							?>
							<th class=" td" style="" >Total<?php echo "<br/>(".$total_highest_marks_for_heading.")";?></th>	
							<th class=" td" style="" >Grade</th>	
						</tr>		


						<?php 
						
						$sub_list = $this->assessment_model->get_scholastic_subject_alloted_to_class($row1['class_id'],$row1['academic_yr']);
               
						foreach($sub_list as $sub_row){
							$total_marks_obtained="";
							$total_highest_marks=""; 
							?>
						<tr>
                             <td  class="col-md-1 col-sm-1 col-xs-1 td subnamesize" style="vertical-align:middle;text-align:center;"> 
								<?php
									echo $sub_row['name'];
								?>
							</td>
							<?php 
							//foreach($term_list as $term){
								
								

								/*$exam_list	=	$this->assessment_model->get_exams_by_class_per_term($row1['class_id'],$term['term_id'],$row1['academic_yr']);
								*/
							if(isset($exam_list) && count($exam_list)>0){
								foreach($exam_list as $exam){

									${'mark_obtained_array_'.$exam['exam_id']}=array();
									${'highest_marks_array_'.$exam['exam_id']}=array();
									${'marks_resultarray_'.$exam['exam_id']}	=	$this->assessment_model->get_marks($exam['exam_id'],$row1['class_id'],$row1['section_id'],$sub_row['sub_rc_master_id'],$row1['student_id'],$row1['academic_yr']);
									if(isset(${'marks_resultarray_'.$exam['exam_id']}[0])){
										
										
										${'marks_obtained_json_'.$exam['exam_id']}=${'marks_resultarray_'.$exam['exam_id']}[0]['reportcard_marks'];
										${'highest_marks_json_'.$exam['exam_id']}=${'marks_resultarray_'.$exam['exam_id']}[0]['reportcard_highest_marks']; //Lija 18-03-22
										//echo "marks_obtained_json ".${'marks_obtained_json_'.$term['term_id']};
										${'mark_obtained_array_'.$exam['exam_id']}=json_decode(${'marks_obtained_json_'.$exam['exam_id']});
										${'highest_marks_array_'.$exam['exam_id']}=json_decode(${'highest_marks_json_'.$exam['exam_id']});//Lija 18-03-22
									    
										if(isset(${'mark_obtained_array_'.$exam['exam_id']}) && ${'mark_obtained_array_'.$exam['exam_id']}<>null){
											foreach (${'mark_obtained_array_'.$exam['exam_id']} as $key => $value){ 
											    if($total_marks_obtained=="")
													$total_marks_obtained=0;
												
												$total_marks_obtained=$total_marks_obtained+$value;
													//echo "value ".$value." ";
													//${'total_marks_'.$exam['exam_id'].$key}=${'total_marks_'.$exam['exam_id'].$key}+$value;
													//echo "marks_".$term['term_id'].$key." ".${'total_marks_'.$term['term_id'].$key}."<br/>";
													//echo "total_marks_obtained ".$total_marks_obtained."<br/>";
							?> 
												<td class="col-md-1 col-sm-1 col-xs-1 td" style="vertical-align:middle;text-align:center;"><?php echo $value;?></td>
										<?php }
											  //Lija 18-03-22
											  foreach (${'highest_marks_array_'.$exam['exam_id']} as $key => $value){ 
												if($total_highest_marks=="")
													$total_highest_marks=0;
												$total_highest_marks=$total_highest_marks+(float)$value;
											  }
										?>
				
										
								<?php }else{?>
								                <td class="col-md-1 col-sm-1 col-xs-1 td" colspan="<?php echo (${'count_of_mark_headings_'.$exam['exam_id']}+2);?>" style="vertical-align:middle;text-align:center;"></td> 
								<?php }
							}else{
								for($i=0;$i<${'count_of_mark_headings_'.$exam['exam_id']};$i++){
								?>
									<td class="col-md-1 col-sm-1 col-xs-1 td" style="vertical-align:middle;text-align:center;"></td>
							<?php
								}
							}
						}?>
						<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;"><?php 
						if($total_marks_obtained<>""){ echo number_format($total_marks_obtained,1);}?></td>
									
						<td class="col-md-1 col-sm-1 col-xs-1 td"  style="vertical-align:middle;text-align:center;">
						<?php
							if($total_marks_obtained<>""){
								//${"grand_total_marks ".$exam['exam_id']}=${"grand_total_marks ".$exam['exam_id']}+$total_marks_obtained;
								//${'grand_highest_marks_'.$exam['exam_id']}=${'grand_highest_marks_'.$exam['exam_id']}+$total_highest_marks;//Lija 18-03-22
							}
							if($total_marks_obtained==""){
								echo "";
							}else{
								$final_grade = "";
								if($total_highest_marks<>0){
									$subject_total_marks_per_100=($total_marks_obtained*100)/$total_highest_marks;//Convert to out of 100
									$final_grade = $this->assessment_model->get_grade_based_on_marks(number_format($subject_total_marks_per_100),'Scholastic',$row1['class_id']); 
								}
								
								echo $final_grade;
							}
						?>
						</td>
					<?php
					}else{?>
							<td class="col-md-1 col-sm-1 col-xs-1 td" colspan="<?php echo (${'count_of_mark_headings_'.$exam['exam_id']}+2);?>" style="vertical-align:middle;text-align:center;"></td> 
					<?php
						}
					//}
					?>
				
                        </tr>
                        <?php 
						}//sub list ends here?>
						
						
					</table>
					</td>
				</tr>
			</table>
		
			<br>
			<br>
			<table class="table-responsive"style="table-layout:fixed;width:750px;margin-left: 7%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top:59%;">
				<tr>
                    <td style="vertical-align:top;width:50%">
                        <table class="table-responsive" style="border:0px;width:100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="vertical-align:middle;" cellpadding="0" cellspacing="0">
								<?php  
                                        $exam_list	=	$this->assessment_model->get_exam_for_which_marks_available($row1['class_id'],$row1['section_id'],$row1['student_id'],'Co-Scholastic');
                                        
                                        ?>
                                       
                                <table class="table-responsive " style="width:100%; margin-left: auto; margin-right: auto; border-spacing: 0px; background-color:white;" cellpadding="0" cellspacing="0">
                                  <tr>
                                        <th class="th" colspan="<?php echo count($exam_list)+1;?>">CO- SCHOLASTICS AREA (Graded on 5 point Scale)</th>
                                    </tr>
                                    <tr>
                                         <th class="th">Subjects</th>
                                        <?php 
                                        foreach($exam_list as $exam){ 
                                       ?>
                                        <th class="th1"  width=""><?php echo $exam['name'];?></th>
                                         <?php
                                        }
                                        ?>
                                    </tr>
                                     <?php 
                                     $sub_list = $this->assessment_model->get_coscholastic_subject_alloted_to_class($row1['class_id'],$row1['academic_yr']);
                                    foreach($sub_list as $sub_row):?>

                                    <tr>
                                 <td  class="col-md-8 col-sm-8 col-xs-8 td" style="vertical-align:middle;text-align:center;"> <?php echo $sub_row['name'];?></td>

                                 <?php 
                                 //foreach($term_list as $term){
                                    
                                    //$exam_list	=	$this->assessment_model->get_exam_for_which_marks_available($row1['class_id'],$row1['section_id'],$row1['student_id']);
                                   
                                    $coscholastic_grade="";
                                    foreach($exam_list as $exam){
										${'mark_obtained_array_'.$exam['exam_id']}=array();
                                        ${'marks_resultarray_'.$exam['exam_id']}	=	$this->assessment_model->get_marks($exam['exam_id'],$row1['class_id'],$row1['section_id'],$sub_row['sub_rc_master_id'],$row1['student_id'],$row1['academic_yr']);
                                        //echo "marks ".${'marks_resultarray_'.$term['term_id']}[0]."<br/>";
                                        if(isset(${'marks_resultarray_'.$exam['exam_id']}[0])){
                                            ${'marks_obtained_json_'.$exam['exam_id']}=${'marks_resultarray_'.$exam['exam_id']}[0]['reportcard_marks'];
                                            ${'mark_obtained_array_'.$exam['exam_id']}=array_merge(${'mark_obtained_array_'.$exam['exam_id']},json_decode(${'marks_obtained_json_'.$exam['exam_id']},true));
 											
                                            if(isset(${'mark_obtained_array_'.$exam['exam_id']}) && ${'mark_obtained_array_'.$exam['exam_id']}<>null){
												
												${'coscholastic_marksobtained_'.$exam['exam_id']}=${'marks_resultarray_'.$exam['exam_id']}[0]['total_marks'];
											
												${'coscholastic_highestmarks_'.$exam['exam_id']}=${'marks_resultarray_'.$exam['exam_id']}[0]['highest_total_marks'];

                                                foreach (${'mark_obtained_array_'.$exam['exam_id']} as $key => $value){
                                                    if($value=='Ab')
                                                        $coscholastic_grade="Ab";
												}
												if($coscholastic_grade=="Ab" && ${'coscholastic_marksobtained_'.$exam['exam_id']}==0){
													//If reportcard marks is Ab and total marks is 0 then Grade will be Ab
													$coscholastic_grade="Ab";
                                                }else{ 
													$coscholastic_grade= $this->assessment_model->get_grade_based_on_marks(number_format(${'coscholastic_marksobtained_'.$exam['exam_id']}),'Co-Scholastic',$row1['class_id']); 

                                                 }
                                            }
                                        }else{
                                            $coscholastic_grade = '';
                                        }
									?>	
									<td class="td" style="vertical-align:middle;text-align:center;"><?php echo $coscholastic_grade;?></td>	
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
    <!--            				$exam_list_s	=	$this->assessment_model->get_exam_for_which_marks_available($row1['class_id'],$row1['section_id'],$row1['student_id'],'Co-Scholastic');-->
    <!--            				foreach($exam_list_s as $exam){ -->
    <!--            				    $term_id	=	$this->assessment_model->get_term_of_exam($exam['exam_id']);-->
    <!--             					$remark=$this->assessment_model->get_reportcard_remark_of_a_student($row1['student_id'],$term_id);-->
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
                                     $sub_list = $this->assessment_model->get_activity_alloted_to_class($row1['class_id'],$row1['academic_yr']);
									 $exam_list_s	=	$this->assessment_model->get_exam_for_which_marks_available($row1['class_id'],$row1['section_id'],$row1['student_id'],'Activity');
                                  

                                    foreach($sub_list as $sub_row){
										$marks_headings_of_subject=$this->assessment_model->get_marks_headings_of_subject($row1['class_id'],$sub_row['sub_rc_master_id'],$row1['academic_yr']);
										foreach($marks_headings_of_subject as $mrow){
											${'coscholastic_grade'.$mrow['marks_headings_name']}=array();
										}
										?>
								
								
                                <table class="table-responsive " style="width:100%; margin-left: auto; margin-right: auto; border-spacing: 0px; background-color:white;" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <th class="th" colspan="<?php echo count($exam_list_s)+1;?>"><?php echo $sub_row['name'];?></th>
                                    </tr>
                                    

                                   
                                 <?php 
								    $coscholastic_grade="";
                                    foreach($exam_list_s as $exam){
											

										${'mark_obtained_array_'.$exam['exam_id']}=array();
                                        ${'marks_resultarray_'.$exam['exam_id']}	=	$this->assessment_model->get_marks($exam['exam_id'],$row1['class_id'],$row1['section_id'],$sub_row['sub_rc_master_id'],$row1['student_id'],$row1['academic_yr']);
                                        //echo "marks ".${'marks_resultarray_'.$term['term_id']}[0]."<br/>";
                                        if(isset(${'marks_resultarray_'.$exam['exam_id']}[0])){
                                            ${'marks_obtained_json_'.$exam['exam_id']}=${'marks_resultarray_'.$exam['exam_id']}[0]['reportcard_marks'];
                                            ${'mark_obtained_array_'.$exam['exam_id']}=array_merge(${'mark_obtained_array_'.$exam['exam_id']},json_decode(${'marks_obtained_json_'.$exam['exam_id']},true));
 											
                                            if(isset(${'mark_obtained_array_'.$exam['exam_id']}) && ${'mark_obtained_array_'.$exam['exam_id']}<>null){
												
												${'coscholastic_marksobtained_'.$exam['exam_id']}=${'marks_resultarray_'.$exam['exam_id']}[0]['total_marks'];
											
												${'coscholastic_highestmarks_'.$exam['exam_id']}=${'marks_resultarray_'.$exam['exam_id']}[0]['highest_total_marks'];

                                                foreach (${'mark_obtained_array_'.$exam['exam_id']} as $key => $value){
												
                                                    if($value=='Ab')
                                                        $coscholastic_grade="Ab";
													if($coscholastic_grade=="Ab" && $value==0){
														//If reportcard marks is Ab and total marks is 0 then Grade will be Ab
														$coscholastic_grade="Ab";
													}else{ 
														$coscholastic_grade= $this->assessment_model->get_grade_based_on_marks(number_format($value),'Co-Scholastic',$row1['class_id']); 

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
													<td  class="col-md-8 col-sm-8 col-xs-8 td subnamesize" style="vertical-align:middle;text-align:center;"> <?php echo $mrow['marks_headings_name'];?></td>
										<?php
											foreach($exam_list_s as $exam){
										?>
													<td class="td" style="vertical-align:middle;text-align:center;"><?php echo ${'coscholastic_grade'.$mrow['marks_headings_name']}[$exam['name']];?></td>
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
          
        </div>
        <br>
        <br>
    </div>
        
    <div class="col-md-12 pdfdiv bgimg1">
        <div class="col-md-2"></div>
	    <div class="col-md-8 table-responsive " style="text-align:center;">
	            
	    <h3 style="margin-right: 50%;margin-top:8%;">Peer Feedback from Classmate(s)</h3>
        <h5 style="margin-right: 15%;">Collaborative game/activity such as colouring together playing a game, etc done in pairs/groups.</h5>
        
        <table class="table-responsive " style="width:75%;margin-left: 7%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			 <tr>
				 <td style="valign:top;inline-block;margin:0;padding:0;" cellpadding="0" cellspacing="0" class="term">
					<table class="" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                      					
						<tr> 
                            <th class="th">Aspect</th>
                            <?php $term	=	$this->assessment_model->get_term();
                            foreach($term as $row):
                            //if($row['name']!='Term 2'){?>
                            <th class="th" style="width:20%;"><?php echo $row['name'];?></th>
                            <?php 
                                //}
                            endforeach;?>
                        </tr>
                        <?php 
                            $pa	=	$this->assessment_model->get_peer_feedback_master($row1['class_id']);
                            foreach($pa as $r):?>
                            <tr>
                                <td class="td" style="vertical-align:center;text-align:center;"><?php echo $r['parameter'];?></td>
                                <?php $term	=	$this->assessment_model->get_term();
                                    foreach($term as $row):
                                        $parameter	= $this->assessment_model->get_published_peer_feedback_parameter_value_by_id($row1['student_id'],$r['pfm_id'],$row['term_id'],$acd_yr); ?>
                                        <td class="td" style="vertical-align:center;text-align:center;">
                                            <?php 
                                            if(${'reportcard_publish_term'.$row['term_id'].'_value'}=='Y'){
                                                echo $parameter;
                                            }
                                            ?>
                                        </td>
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
        
        <h3 style="margin-right: 67%; margin-top:12%;">Self Assessment</h3>
        
        <table class="table-responsive " style="width:82%;margin-left: 7%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			 <tr>
                 <td style="valign:top;inline-block;margin:0;padding:0;" cellpadding="0" cellspacing="0" class="term">
					<table class="" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                      					
						<tr> 
                            <th class="th" style="vertical-align:center;text-align:center;width:30%;">Aspect</th>
                            <?php $term	=	$this->assessment_model->get_term();
                            foreach($term as $row):
                            //if($row['name']!='Term 2'){?>
                            <th class="th" style="vertical-align:center;text-align:center;width:25%;"><?php echo $row['name'];?></th>
                            <?php 
                                //}
                            endforeach;?>
                        </tr>
                        <?php 
                            $pa	=	$this->assessment_model->get_self_assessment_master($row1['class_id']);
                            foreach($pa as $r):?>
                            <tr>
                                <td class="td" style="vertical-align:center;text-align:center;"><?php echo $r['parameter'];?></td>
                                <?php $term	=	$this->assessment_model->get_term();
                                foreach($term as $row):
                                $parameter	= $this->assessment_model->get_published_self_assessment_parameter_value_by_id($row1['student_id'],$r['sam_id'],$row['term_id'],$acd_yr);
                                if($parameter=='' && ${'reportcard_publish_term'.$row['term_id'].'_value'}=='Y'){
                                        $parameter="We are delighted to see ".$row1['first_name']." making consistent progress in academics and extracurricular activities. We appreciate the school’s efforts in nurturing their talents.";
                                    }
                                ?>
                                 <td class="td" style="vertical-align:center;text-align:center;">
                                    <?php
                                        echo $parameter;
                                     ?>
                                 </td>
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
        <h3 style="margin-right: 67%; margin-top:29%;">Parent's Feedback</h3>
        
        <table class="table-responsive " style="width:77%;margin-left: 6%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
			 <tr>
                 <td style="valign:top;inline-block;margin:0;padding:0;" cellpadding="0" cellspacing="0" class="term">
					<table class="" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                      					
						<tr> 
                            <th class="th">Aspect</th>
                            <?php $term	=	$this->assessment_model->get_term();
                            foreach($term as $row):
                            //if($row['name']!='Term 2'){?>
                            <th class="th" style="width:20%;"><?php echo $row['name'];?></th>
                            <?php 
                                //}
                            endforeach;?>
                        </tr>
                        <?php 
                            $pa	=	$this->assessment_model->parent_feedback_parameter($row1['class_id']);
                            foreach($pa as $r):?>
                            <tr>
                                <td class="td" style="vertical-align:center;text-align:center;"><?php echo $r['parameter'];?></td>
                                <?php $term	=	$this->assessment_model->get_term();
                                foreach($term as $row):
                                ?>
                                <td class="td" style="vertical-align:center;text-align:center;">
                                <?php
                                if(${'reportcard_publish_term'.$row['term_id'].'_value'}=='Y'){
                                    $parameter	= $this->assessment_model->get_published_parent_feedback_parameter_value_by_id($row1['student_id'],$r['pfm_id'],$row['term_id'],$acd_yr);
                                    if($r['parameter']=='How satisfied are you with his/ her study time.'){?>
                                 
                                        <?php 
                                        for($i=1;$i<=$parameter;$i++){?>
											<img src="<?php echo base_url();?>uploads/Plain_Yellow_Star.jpg" style="width:20px;height:18px">
										<?php } 
										if($parameter==0)
											echo "<font size='5'></font>";
										?>
                                <?php 
                                    }else{
                                        echo $parameter;
                                    }
                                }
                                ?>
                            </td>
                           <?php endforeach;?>
                            </tr>
                        <?php 
                                //}
                            endforeach;?>
                        
                     </table>
                 </td>
            </tr>
        </table>

		<br>
		<table class="table-responsive" style="width:90%;margin-left: 6%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top:18%;border: 0px solid red">
			
			<?php
			$date_from=$this->crud_model->get_academic_yr_from_of_particular_yr($row1['academic_yr']);
			$date_to='';
			$exam_list	=	$this->assessment_model->get_published_exams_of_a_class($row1['class_id'],$row1['section_id'],$acd_yr);
			if(count($exam_list)==1){
				$date_to=date_format(date_create(substr($date_from,0,4)."-10-30") , 'Y-m-d') ; // Creating date to as last day of Oct;
			}elseif(count($exam_list)>1){
				$date_to=$this->crud_model->get_academic_yr_to_of_particular_yr($row1['academic_yr']);
			}
			
			//print_r($date_to);
			?>
            <tr> 
                <td style="width:100%;">
    				<table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border: 0;" cellpadding="0" cellspacing="0">
						<tr>
							<td style="font-size:14px;text-align:left;white-space:nowrap;width: 10%" ><b> Attendance : </b></td>
							<td style="font-size:14px;white-space:nowrap;width:10%!important;margin-right:2%;text-align:center;" >
							<div class="statistics_line">
							<?php 
							if($this->crud_model->get_total_stu_attendance_till_a_month($row1['student_id'],$date_from,$date_to,$acd_yr)<>"" ){
								echo $this->crud_model->get_total_stu_attendance_till_a_month($row1['student_id'],$date_from,$date_to,$acd_yr)."/".$this->crud_model->get_total_workingdays_from_dailyattendance_classwise($row1['class_id'],$row1['section_id'],$date_from,$date_to,$acd_yr);
							}
							?>&nbsp;</div> 
							</td>
						<?php
				// 		$exam_list1	=	$this->assessment_model->get_published_exams_of_a_class($row1['class_id'],$row1['section_id'],$row1['academic_yr']);
				// 		 if(count($exam_list1)>2){
						?>
							<td style="font-size:14px;text-align:left;white-space:nowrap;width: 20%;"><b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Promoted To :</b></td>
							<td style="text-align:center;font-size:14px;width:10%!important;">
							<div class="statistics_line">
							<?php 
    							$term_id	=	$this->assessment_model->get_term_of_exam($exam_list[1]['exam_id']);
    							//print_r($term_id);
    							echo $this->assessment_model->get_promote_to_of_a_student($row1['student_id'],$term_id);
							?>&nbsp;</div> 
							&nbsp;</div> </td>
							<td style="text-align:center;font-size:14px;width:25%;"><b> Date Of Reopening :</b></td>
							<td style="width:auto;text-align:center;font-size:14px" ><div class="statistics_line">
								<?php 
								 	$reopen_date=$this->assessment_model->get_school_reopen_date($row1['class_id'],$row1['section_id']);
								 	if($reopen_date<>NULL && $reopen_date<>'0000-00-00')
								 		echo date_format(date_create($reopen_date),'d-m-Y');
								?>
							&nbsp;</div></td>
					<?php //}else{?>
							<td style="width: auto"> </td>
					<?php //}?>
						<tr>
    				</table>
				</td>
 			</tr>
			<?php //}?>
		</table>
		<br/>
		<table class="table-responsive" style="width:85%;margin-left: 6%;margin-right: auto;border-spacing: 0px;background-color:white;border: 0;margin-top:24%;" cellpadding="1" cellspacing="10">
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

 </html> 
<?php endforeach;?>
   