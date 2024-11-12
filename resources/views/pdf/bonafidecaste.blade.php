<style>
@page {
    size: A4;
    margin-top:0;
    margin-bottom:0;
    margin-left:0;
    margin-right:0;
    padding: 0;
  }
    body{
    background-image: url('http://103.159.85.174/SchoolBackendv5/public/bonafide.jpg');
    -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;

}
 tr td{
	padding-top: 3px; 
	padding-bottom:3px;
	word-wrap:break-word;
	font-size:14px;
 }

</style>
<html>
<body>
<div class="pdfdiv"> <!--Ends Here -->
<!-- <?php 
// if($student_image!=''){
// // $image_url	=	base_url().'uploads/student_image/'.$student_image;
// $image_url = m ;
// }
?> -->
    <center>
<!--	<div style="width:100%;height:95%;margin: auto;text-align:center;border-style:groove;border:4px groove grey;">-->
	<br/><center>
		<br>
		<br>
	    
    <div style="width:95%;margin-top:20%;;text-align:center;display: inline-block">
	    	<img src="url('http://103.159.85.174/SchoolBackendv5/public/bonafide.jpg')"  class="image_thumbnail studimg" width="100" height="100" style="padding-left: 70%;"/>
        <table width="100%" border="0">
            <tr>
		    <td width="10%"></td>
			<td align="left" width="25%"></td>
			<td align="left" align="left" width="28%"></td>
			<td align="left" align="left"width="25%">REF. NO.: <?php echo $data->academic_yr."/".$data->sr_no;?></td>
			<td align="left" align="left"></td>
        </tr>
            <tr>
                <td width="10%"></td>
		    <td width="" colspan="4">This is to certify <?php echo $data->stud_name;?> was a student of St. Arnolds Central School in class <?php echo $data->class_division;?> for the academic session <?php echo $data->academic_yr;?> .as per the school record her details are as follows</td>
        </tr>
	    
        </table>
        
     <table width="100%" border="0" style="border-collapse: collapse;">
        <tr>
		    <td width="10%"></td>
			<td align="left" width="45%"  style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;">GR No.</td>
			<!--<td align="center" width="8%">:</td>-->
			<td align="left" align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;"><?php echo $data->reg_no;?></td>
        </tr>
	    <tr>
		    <td width="10%"></td>
			<td align="left" width="45%" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;">Student name with Student ID and UID</td>
			<!--<td align="center" width="8%">:</td>-->
			<td align="left" width="45%" align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;"><?php echo $data->stud_name;?><br><?php echo $data->stud_id_no;?><br><?php echo $data->stu_aadhaar_no;?></td>
        </tr>

		<tr>
			<td></td>	
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;">Mother’s Name</td>
			<!--<td align="center" >:</td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;"><?php echo $data->mother_name;?></td>
		</tr>
		 <tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;">Nationality</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;"><?php echo $data->nationality;?></td>
			
	    </tr>
	    <tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;">Mother Tongue</td>
			<!--<td align="center" >:</td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;"><?php echo $data->mother_tongue;?></td>
        </tr>
		<tr>
			<td></td>	
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Religion</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->religion;?></td>
	    </tr>
	    <tr>
			<td></td>	
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Caste</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->caste;?></td>
	    </tr>
	    <tr>
			<td></td>	
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Sub Caste</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->subcaste;?></td>
	    </tr>
	     <tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Birth Place</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->birth_place;?></td>
			
	    </tr>
		<tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Date of Birth</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo date_format(date_create($data->dob),'d-m-Y').' ( '.$data->dob_words.')';?></td>
			
	    </tr>
        <tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Previous School And Class</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->prev_school_class;?></td>
			
	    </tr>
       	<tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Date of admission</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;"><?php echo date_format(date_create($data->admission_date),'d-m-Y');?>
			</td>
	    </tr>
       
        <tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">In which class and when was he/she was learning from</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->class_when_learning;?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Progress Report</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->progress;?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Behaviour</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->behaviour; ?></td>
			
	    </tr>
		<tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Reason for Leaving School</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->leaving_reason;?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Date of Leaving Certificate</td>
			<!--<td align="center" >: </td>-->
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->lc_date_n_no;?></td>
	    </tr>
    </table>

        <br>
        <table width="90%" border="0">
            <tbody>
            <tr>
                <td width="10%"></td>
			<td align="left" style=""></td>
			<!--<td align="center" >: </td>-->
			<td align="center" style="">Fr. Sunil Menezes</td>
            </tr>
            <tr>
            	<td></td>
			<td align="left" style="">Date : <?php echo date_format(date_create($data->issue_date_bonafide) , 'd-m-Y');?></td>
			<!--<td align="center" >: </td>-->
			<td align="center" style="padding:20px;">Principal</td>
			</tr>
			</tbody>
            </table>
    <!--<p style="font-size:15px;padding-left: 50%;"> <span style="margin-left:10%;">Fr. Sunil Menezes </span></p>-->
    <!--<p style="font-size:15px;padding-left: 5%;"> <span style="">Date : <?php echo date_format(date_create($data->issue_date_bonafide) , 'd-m-Y');?></span><span style="margin-left:10%;padding-left: 20%;">Principal </span></p>-->
	</div></center>
<!--	</div>-->
       </center>
<!--    Aparna 14-03-2020-->     
    </div>
    <!--Ends Here -->
    </body>
</html>