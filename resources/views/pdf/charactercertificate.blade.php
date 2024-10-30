<style>
@page {
    margin-top:0;
    margin-bottom:0;
    margin-left:0;
    margin-right:0;
    /*padding: 0;*/
  }
    body{
    background-image: url('http://103.159.85.174/SchoolBackendv5/public/bonafide.jpg');
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
	padding-bottom:3px;
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
	<br/>
	
    <div style="width:80%;margin-top:23%;margin-left:5%;text-align:center;display: inline-block">
     <table border="0"  class="table-responsive" style="width:95%;margin-left:5%;margin-top:20%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top:5%;" cellpadding="1" cellspacing="10" >
             <tr>
                 <?php if($student_image =''){ ?>
                <td style="font-style: italic;font-size:15px;text-align:right;">BONAFIDE AND CHARACTER CERTIFICATE  
<?php 	
$image_url	=  m; ?>
	<img src="<?php echo $image_url;?>"  class="image_thumbnail studimg" width="50" height="50" style="margin-left:80px;"/>
	</td>
<?php }else{?>
<td style="font-style: italic;font-size:15px;text-align:center;">BONAFIDE AND CHARACTER CERTIFICATE  
<?php }?><br></td>
                <!--<td rowspan=2>-->
                   
                <!--</td>-->
            </tr>
            <!--<tr>-->
            <!--    <td style="font-style: italic;font-size:14px;text-align:center;"><br></td>-->
            <!--</tr>-->
            <tr>
                <td style="font-style: italic;font-size:15px;text-align:center;">This is to certify that</td>
            </tr>
			<tr> 
                <td>
                    <!--<br>-->
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td class="cursive1" style="font-style: italic;font-size:15px;width: 18%; word-wrap:break-word;">Master / Miss </td>
						<td style="font-style: italic;font-size:15px;width: auto;text-align:center;"><div class="statistics_line"><?php echo $data->stud_name;?></div></td>
						<td style="font-style: italic;font-size:15px;width: 5%;text-align:center;">was</td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border-collapse: collapse;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:15px;width: 15%;white-space: nowrap;" class="cursive">a Bonafide student of our school studying in Std</td>
                        <td style="font-style: italic;font-size:15px;width: 5%;text-align:center;"><div class="statistics_line"><?php echo $data->class_division;?></div></td>
                        <td style="font-style: italic;font-size:15px;width: 5%;padding-left:2%;white-space: nowrap;"> in the year </td>
						<td style="font-style: italic;font-size:15px;width: 15%;text-align:center;"><div class="statistics_line"><?php echo $data->academic_yr;?></div></td>
						<!--<td style="font-style: italic;font-size:15px;width:7%;padding-top: 15px;padding-left:3%;">  place</td>-->
                    </table>
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:15px;width: 1%;word-wrap:break-word;text-align: center;">Her / His date of birth as per the General Register of the school is</td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;width:20%;text-align:center;font-size:15px;"><div class="statistics_line"><?php echo $data->dob." [ ".$data->dob_words."]";?></div></td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:15px;width: 1%;word-wrap:break-word;text-align: center;">She / He holds a good moral character.</td>
                    </table>
                    
                </td>
                <br>
            </tr>
             <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:15px;width: 1%;word-wrap:break-word;text-align: center;">She / He has passed her /his CBSE Std. <?php echo $data->class_division;?> Examination of</td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:15px;width: 10%;word-wrap:break-word;text-align: center;"></td>
                        <td style="font-style: italic;font-size:15px;width: 8%;word-wrap:break-word;text-align: center;"> Feb / March</td>
                        <?php 
                        // $academic_yr_to = $this->crud_model->get_academic_yr_to();
                        // $to_year = date('Y', strtotime($academic_yr_to) ); 
                        $academic_yr_to = $data->academic_yr;
                        $acd_yr = explode('-',$academic_yr_to);
                        $to_year = date('Y', strtotime($acd_yr[1])); 
                        //$to_year = '2024';
                        ?>
                        <td style="font-style: italic; width:5%;text-align:center;font-size:15px;"><div class="statistics_line"><?php echo $to_year;?></div></td>
                        <td style="font-style: italic; width:9%;text-align:center;font-size:15px;">in the <?php echo $data->attempt;?></td>
                        <td style="font-style: italic;font-size:15px;width: 11%;word-wrap:break-word;text-align: center;"></td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr><td><br></td></tr>
            <tr>
                 <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-style: italic;font-size:15px;width: 10%;padding-top: 10px;word-wrap:break-word;text-align: center;">Date: <?php echo date_format(date_create($data->issue_date_bonafide),'d-m-Y');?></td>
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