<style>
@page {
    margin-top:0;
    margin-bottom:0;
    margin-left:0;
    margin-right:0;
    /*padding: 0;*/
  }
    body{
    background-image: url('http://103.159.85.174/SchoolBackendv5/public/character_certificate.jpg');
    -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;
    font-family:Arial !important; 
    text-align:left;
    /*width: 300px;*/
  /*height: 300px;*/

}
 tr td{
	padding-top: 3px; 
	word-wrap:break-word;
	font-size:20px;
	font-family:Arial !important; 
    text-align:left;
 }
.statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        /*padding:3px;*/
    }

</style>
<html>

<div class="pdfdiv"> <!--Ends Here -->
<!--	<div style="width:100%;height:95%;margin: auto;text-align:center;border-style:groove;border:4px groove grey;">-->

 <?php 
//$stud_image = $this->crud_model->get_student_profile_image($stud_id);
$student_image='';
//$image_url	=	base_url().'uploads/student_image/'.$student_image;
?> 
					
	<br/>
	
	
	<div style="width:80%;margin-top:23%;margin-left:5%;text-align:center;display: inline-block">
     <table border="0"  class="table-responsive" style="width:95%;margin-left:5%;margin-top:20%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top:5%;" cellpadding="1" cellspacing="10" >
             <tr>
                 <?php if($student_image!=''){ ?>
                <td style="font-style: italic;font-size:15px;text-align:right;">BONAFIDE CERTIFICATE  
<?php 	
$image_url	=	m ?>
	<img src="<?php echo $image_url;?>"  class="image_thumbnail studimg" width="50" height="50" style="margin-left:80px;"/>
	</td>
<?php }else{?>
<td style="font-style: italic;font-size:15px;text-align:center;">BONAFIDE CERTIFICATE  
<?php }?></td>

</tr>

    <tr> 
        <td>
            <!--<br>-->
            <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                <td class="cursive1" style="font-style: italic;font-size:16px;width: 90%; word-wrap:break-word;text-align:right;">Ref. No : <?php echo $data->academic_yr."/ B.C/".$data->sr_no;?><br></td>
                 <!--<td style="font-style: italic;font-size:14px;width: 20%; word-wrap:break-word;"></td>-->
                
            </table>
        </td>
	</tr>
<tr>
                <td style="font-style: italic;font-size:15px;text-align:center;"><b>This is to certify that</td>
            </tr>
			<tr> 
                <td>
                    <!--<br>-->
                    <table class="table-responsive" style="width:109%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td class="cursive1" style="font-style: italic;font-size:14.5px;width: 15%; word-wrap:break-word;"><b>Master / Miss </td>
						<td style="font-style: italic;font-size:14.5px;width: auto;text-align:center;"><div class="statistics_line"><b><?php echo $data->stud_name?></div></td>
						<td style="font-style: italic;font-size:14.5px;width: 5%;text-align:center;">,</td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:112%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border-collapse: collapse;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:14.5px;width: 4%;white-space: nowrap;" class="cursive"><b> son / daughter of Mr.</td>
                        <td nowrap style="font-style: italic;font-size:14.5px;width: 5%;text-align:center;"><div class="statistics_line"><b><?php echo $data->father_name?></div></td>
                        <td style="font-style: italic;font-size:14.5px;width: 15%;"><b>is a bonafide student of St. Arnolds Central School</td>
                    </table>
                </td>
                
            </tr>
            <!-- <tr>-->
            <!--    <td>-->
            <!--        <table class="table-responsive" style="width:109%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border-collapse: collapse;" cellpadding="0" cellspacing="0">-->
            <!--            <td style="font-style: italic;font-size:14.5px;width: 4%;white-space: nowrap;" class="cursive"><b> St. Arnolds Central School</td>-->

            <!--        </table>-->
            <!--    </td>-->
                
            <!--</tr>-->
            <tr>
                <td>
                    <table class="table-responsive" style="width:105%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border-collapse: collapse;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:14.5px;width: 6%;padding-left:2%;white-space: nowrap;"><b>studying in our school in class</td>
						<td style="font-style: italic;font-size:14.5px;width: 5%;text-align:center;"><div class="statistics_line"><b><?php echo $data->class_division?> </div></td>
						<td style="font-style: italic;font-size:14.5px;width:7%;text-align:center;"><b>for the academic year <?php echo $data->academic_yr?>.</td>
                    </table>
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:105%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:14.5px;width: 1%;word-wrap:break-word;text-align: center;"><b>According to our record his / her date of birth is</td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:105%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;width:20%;text-align:center;font-size:14.5px;"><div class="statistics_line"><b><?php echo $data->dob." ( ".$data->dob_words.")";?></div></td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:112%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:112%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                    </table>
                    
                </td>
                <br>
            </tr>
           
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                    </table>
                    
                </td>
            </tr>
            <?php $date_new = date_format(date_create($data->issue_date_bonafide) , 'M d, Y');?>
            <tr>
                 <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:15px;width: 10%;padding-top: 10px;word-wrap:break-word;text-align: center;">Date: <?php echo $data->issue_date_bonafide?></td>
                        <td style="font-style: italic; width:10%;text-align:center;font-size:15px;"></td>
                        <td style="font-style: italic;font-size:15px;width: 10%;padding-top: 10px;word-wrap:break-word;text-align: center;">Principal</td>
                    </table>
                    
                </td>
                </tr>
		</table>
	</div>   
    </div>
    <!--Ends Here -->
</html>

