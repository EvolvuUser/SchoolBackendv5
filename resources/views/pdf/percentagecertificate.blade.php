<style>
@page {
    /*size: A4;*/
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

}
 tr td{
	padding-top: 3px; 
	padding-bottom:3px;
	word-wrap:break-word;
	font-size:14px;
	font-family:Arial !important;
 }
 tr.separated td {
    /* set border style for separated rows */
    border-top: 1px solid black;
}
.statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        /*padding:3px;*/
    }
 
 .container {
  display: grid;
  grid-gap: 1rem;
  grid-template: 'date content';
}
</style>
<html>
<div class="pdfdiv"> <!--Ends Here -->
    <div style="width:95%;margin-top:17%;display: inline-block">
      
        <?php 
        //echo $student_image;
       
//  if($student_image =''){
    // $image_url	=	base_url().'uploads/student_image/'.$student_image; 
     //echo $image_url;
    ?>
    
    <img src="url('http://103.159.85.174/SchoolBackendv5/public/bonafide.jpg')"  class="image_thumbnail studimg" width="100" height="100" style="margin-left:80%;margin-top:12%;"/>
<?php 
?>
        	
<center><p style="font-size:18px"><b>PERCENTAGE CERTIFICATE</b></p></center>
<!--<center><p style="font-size:16px"><b>To whomsoever it may concern</b></p></center>-->

<!--<p style="font-size:15px;"> <span style="margin-left:80%;"><b> Ref. No : </b></span></p>-->
<p style="font-size:16px"><span style="margin-left:80%"> Ref. No : <?php echo $data->sr_no;?> </span></p>
<!--<p style="font-size:15px"> <b></span></b></p>-->
<?php $class = DB::table('class')->where('class_id',$data->class_id)->first();
    
    if($class->name=='10'){
        $class_wrd = 'Tenth';
    }
    if($class->name=='11'){
        $class_wrd = 'Eleventh';
    }
    if($class->name=='12'){
        $class_wrd = 'Twelveth';
    }?>
    <!-- <div class="container"> -->
    <table width="100%" border="0" style="border-collapse: collapse;">
        <tr>
            <td width="10%"></td>
        	<td align="left" width="90%" style="font-size:16px;">This is to certify that Master / Miss.<b><?php echo $data->stud_name;?></b> of class <?php echo $class->name."th";?> (<?php echo $class_wrd;?>) appeared for CBSE Board Examination of <?php echo $data->academic_yr;?> bearing Roll No. <?php echo $data->rollno;?>.</td>
        </tr>
        <tr>
            <td width="10%"></td>
            <td align="left" width="90%" style="font-size:16px;">He / she has secured marks as below:</td>
        </tr>
    </table>
    <br>
    <table width="90%" border="0" style="border-collapse: collapse;">
        <tr>
            <td width="10%"></td>
            <td align="left" width="35%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:16px;">SUBJECT</td>
            <td align="left" width="25%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:16px;" align="left">MARKS OBTAINED</td>
            <td align="left" width="20%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:16px;" align="left">TOTAL MARKS</td>
        </tr>
        <?php 
        if($class->name=='10'){
          $subject = DB::table('class10_subject_master')->get(); 
            $sub_total = 100*(count($subject));
          foreach($subject as $row):
        ?>
	    <tr>
		    <td width="10%"></td>
			<td align="left" width="40%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;"><?php echo $row->name;?></td>
			<?php  $marks = DB::table('percentage_marks_certificate')
                                    ->where('sr_no', $data->sr_no)
                                    ->where('c_sm_id', $row->c_sm_id)
                                    ->value('marks');?>
			<td align="center" width="7%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;" align="center"><?php echo $marks;?></td>
				<td align="center" width="7%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;" align="center"><?php echo '100';?></td>
        </tr>
        <?php endforeach;
          
        }else{
            $subject = DB::table('subjects_higher_secondary_studentwise as shs')
            ->join('subject_group as grp', 'shs.sub_group_id', '=', 'grp.sub_group_id')
            ->join('subject_group_details as grpd', 'grp.sub_group_id', '=', 'grpd.sub_group_id')
            ->join('subject_master as shsm', 'grpd.sm_hsc_id', '=', 'shsm.sm_id')
            ->join('subject_master as shs_op', 'shs.opt_subject_id', '=', 'shs_op.sm_id')
            ->join('stream', 'grp.stream_id', '=', 'stream.stream_id')
            ->select(
                'shs.*',
                'grp.sub_group_name',
                'grpd.sm_hsc_id',
                'shsm.name as subject_name',
                'shsm.subject_type',
                'stream.stream_name',
                'shs_op.name as optional_sub_name'
            )
            ->where('shs.student_id', $data->stud_id)
            ->get();
            $sub_total = 100*(count($subject)+1);
            foreach($subject as $row):
                
            ?>
    	    <tr>
    		    <td width="10%"></td>
    			<td align="left" width="40%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;"><?php echo $row->subject_name?></td>
    			<?php   $marks = DB::table('percentage_marks_certificate')
                                    ->where('sr_no', $data->sr_no)
                                    ->where('c_sm_id', $row->sm_hsc_id)
                                    ->value('marks');?> 
    			<td align="center" width="7%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;" align="center"><?php echo $marks;?></td>
    				<td align="center" width="7%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;" align="center"><?php echo '100';?></td>
            </tr>
            <?php endforeach;?>
            <tr>
    		    <td width="10%"></td>
    			<td align="left" width="40%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;"><?php echo $subject[0]->optional_sub_name;?></td>
    			<?php  
                     $marks = DB::table('percentage_marks_certificate')
                     ->where('sr_no', $data->sr_no)
                     ->where('c_sm_id', $subject[0]->opt_subject_id)
                     ->value('marks');?>
    			<td align="center" width="7%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;" align="center"><?php echo $marks;?></td>
    				<td align="center" width="7%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;" align="center"><?php echo '100';?></td>
            </tr>
        
        <?php } ?>
        
            
       <tr>
		    <td width="5%"></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-weight:bold;font-size:16px;" width="20%"><b>TOTAL</b></td>
			<!--<td align="center" width="8%"></td>-->
			<td align="center"  width="15%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-weight:bold;font-size:16px;" ><b><?php echo $data->total ;?></b></td>
			<td align="center" width="15%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;" ><b><?php echo $sub_total ;?></b></td>
        </tr>
        <tr style="border-bottom: 1px solid black;">
		    <td width="5%" ></td>
			<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;border-bottom:1px solid black;font-weight:bold;font-size:16px;" width="20%">PERCENTAGE</td>
			<!--<td align="center" width="8%"></td>-->
			<td align="center" width="15%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;border-bottom:1px solid black;font-weight:bold;font-size:16px;"><?php echo $data->percentage." %";?></td>
				<td align="center" width="7%" style="border-top:1px solid black;border-left:1px solid black;border-right:1px solid black;padding-left:5%;font-size:15px;border-bottom:1px solid black;" align="center"></td>
        </tr>
    </table>
    <br>
    <br>
    <?php

function numToWords($number) {
    $units = array('', 'One', 'Two', 'Three', 'Four',
                   'Five', 'Six', 'Seven', 'Eight', 'Nine');

    $tens = array('', 'Ten', 'Twenty', 'Thirty', 'Forty',
                  'Fifty', 'Sixty', 'Seventy', 'Eighty', 
                  'Ninety');

    $special = array('Eleven', 'Twelve', 'Thirteen',
                     'Fourteen', 'Fifteen', 'Sixteen',
                     'Seventeen', 'Eighteen', 'Nineteen');

    $words = '';
    if ($number < 10) {
        $words .= $units[$number];
    } elseif ($number < 20) {
        $words .= $special[$number - 11];
    } else {
        $words .= $tens[(int)($number / 10)] . ' '
                  . $units[$number % 10];
    }

    return $words;
}

$per = explode(".",$data->percentage);
$per_word = numToWords($per[0]);
if($per[1] !='00'){
    $per_word1 = numToWords($per[1]);
    $percentage_wrds = $per_word." Point ".$per_word1;
}else{
    $percentage_wrds = $per_word;
}


?>
    <table width="90%" border="0" style="border-collapse: collapse;">
        <tr>
            <td width="10%"></td>
            <td align="left" width="80%" style="font-size:16px;">Therefore his / her percentage in <?php echo $class->name."th";?> (<?php echo $class_wrd;?>) CBSE Board Examination <?php echo $data->academic_yr;?> is <?php echo $data->percentage." % (".$percentage_wrds.")";?></td>
        </tr>
    </table>
<br>
<br>
<br>
<br>
<p style="font-size:16px"><span style="margin-left:10%;">Date : <?php echo $data->certi_issue_date;?><span style="margin-left:50%"> Principal </span></p>
</div>
</div>
</html>
