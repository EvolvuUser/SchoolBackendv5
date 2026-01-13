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
    background-image: url('https://sms.evolvu.in/public/lc_bg.jpg');
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
    <center>
<!--	<div style="width:100%;height:95%;margin: auto;text-align:center;border-style:groove;border:4px groove grey;">-->
	<br/><center>
	<br><br><br>
    <div style="width:95%;margin-top:22%;;text-align:center;display: inline-block">
        <table width="100%" border="0">
    <tr>
        <td width="5%"></td>
        <td align="left" width="30%">
            REF. NO.: <?php echo $data->academic_yr . '/' . $data->sr_no; ?>
        </td>
        <td  align="center" width="30%"></td>
        <td width="8%"></td>
        <td align="left" width="30%">
            GR No.: <?php echo $data->grn_no; ?>
        </td>
        <td width="5%"></td>
    </tr>
    <tr>
        <td width="5%"></td>
        <td align="left" width="30%">
            SARAL ID: <?php echo $data->stud_id_no; ?>
        </td>
        <td align="center" width="30%">
            UDISE PEN: <?php echo $data->udise_pen_no; ?>
        </td>
        <td width="8%"></td>
        <td align="left" width="30%">
            APAAR ID: <?php echo $data->apaar_id; ?>
        </td>
        <td width="5%"></td>
    </tr>
</table>
         <table width="100%" border="0">
       
    </table>
 	<!--<p style="font-size:15px;"><span style="margin-left:5%;">GR No.: <?php // echo $reg_no; ?></span><span width="10%"></span> <b>REF. NO.: <?php // echo $academic_yr."/".$sr_no; ?><br> <span style="margin-left:10%;text-align:right;">SARAL STUDENT ID-NO: <?php // echo $stud_id_no; ?></span><span style="margin-left:10%;text-align:right;">UDISE PEN NO: <?php // echo $udise_pen_no; ?></span></b></p>-->
    <table width="100%" border="0">
	    <tr>
		    <td width="5%"></td>
			<td align="left" width="40%">Name of Pupil in full</td>
			<td align="center" width="8%">:</td>
			<td align="left" align="left"><?php echo $data->stud_name . ' ' . $data->mid_name . ' ' . $data->last_name; ?></td>
        </tr>

	    <tr>
		    <td></td>
			<td align="left">Father’s Name</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->father_name; ?></td>
		</tr>

		<tr>
			<td></td>	
			<td align="left">Mother’s Name</td>
			<td align="center" >:</td>
			<td align="left"><?php echo $data->mother_name; ?></td>
		</tr>		
		<tr>
			<td></td>
			<td align="left">Date of Birth</td>
			<td align="center" >: </td>
			<td align="left"><?php echo date_format(date_create($data->dob), 'd-m-Y') . ' ( ' . $data->dob_words . ')'; ?></td>
			
	    </tr>
        <tr>
			<td></td>
			<td align="left">Place of Birth</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->birth_place; ?></td>
			
	    </tr>
        <tr>
			<td></td>
			<td align="left">Proof of DOB Submitted at the time of Admission</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->dob_proof; ?></td>
			
	    </tr>
        <tr>
			<td></td>
			<td align="left">Mother Tongue</td>
			<td align="center" >:</td>
			<td align="left"><?php echo $data->mother_tongue; ?></td>
        </tr>
        <tr>
			<td></td>
			<td align="left">Nationality</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->nationality; ?></td>
			
	    </tr>
        
	<?php
	if ($data->religion != '') {
		if ($data->caste != '') {
			if ($data->subcaste != '') {
				$relcast = $data->religion . ', ' . $data->caste . ' (' . $data->subcaste . ')';
			} else {
				$relcast = $data->religion . ', ' . $data->caste;
			}
		} else {
			if ($data->subcaste != '') {
				$relcast = $data->religion . ' (' . $data->subcaste . ')';
			} else {
				$relcast = $data->religion;
			}
		}
	} elseif ($data->caste != '') {
		if ($data->subcaste != '') {
			$relcast = $data->caste . ' (' . $data->subcaste . ')';
		} else {
			$relcast = $data->caste;
		}
	} else {
		if ($data->subcaste != '') {
			$relcast = $data->religion . ' (' . $data->subcaste . ')';
		} else {
			$relcast = $data->religion;
		}
	}

	?>
		<tr>
			<td></td>	
			<td align="left">Religion &amp; Caste (with Sub Caste)</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $relcast; ?></td>
	    </tr>
		<tr>
			<td></td>
			<td align="left">Date of admission with Class</td>
			<td align="center" >: </td>
			<td align="left"><?php echo date_format(date_create($data->date_of_admission), 'd-m-Y');
if (trim($data->admission_class <> ''))
	echo ' / Class-' . $data->admission_class; ?>
			</td>
	    </tr>
        <tr>
			<td></td>
			<td align="left">Previous School Attended</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->last_school_attended_standard; ?></td>
			
	    </tr>
        <tr>
			<td></td>
			<td align="left">Class in which last studied in</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->standard_studying; ?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left">School/ Board  Annual Exam last taken with result</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->last_exam; ?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left">Subjects Studied</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->subjects_studied; ?></td>
			
	    </tr>
		<tr>
			<td></td>
			<td align="left">Promoted to</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->promoted_to; ?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left">Attendance</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->attendance; ?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left">Month upto which the pupil/student has paid school fees</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->fee_month; ?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left">Whether Part of(NCC Cadet, Boy Scout, Girl Guide)</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->part_of; ?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left">Games played as extra  curricular  Activities</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->games; ?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left">Date of application for Certificate</td>
			<td align="center" >: </td>
			<td align="left"><?php echo date_format(date_create($data->application_date), 'd-m-Y'); ?></td>
	    </tr>
        <tr>
			<td></td>
			<td align="left">Date of Leaving school</td>
			<td align="center" >: </td>
			<td align="left"><?php echo date_format(date_create($data->leaving_date), 'd-m-Y'); ?></td>
			
	    </tr>
		<tr>
			<td></td>
			<td align="left">Conduct</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->conduct; ?></td>
	    </tr>
		<tr>
			<td></td>
			<td align="left">Reason for leaving school</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->reason_leaving; ?></td>
	    </tr>
		<tr>
			<td></td>
			<td align="left">Any other Remarks</td>
			<td align="center" >: </td>
			<td align="left"><?php echo $data->remark; ?></td>
	    </tr>
    </table>
	<p style="font-size:14px;text-align:left;margin-left:5%;">I hereby declare that the above information including Name of the Candidate, Father’s/ guardian Name, Mother’s Name and Date of Birth furnished above is correct as per school records.</p>
        <br>
    <p style="font-size:12px;padding-left: 25px;padding-right: 30px;">Date : <?php echo date_format(date_create($data->issue_date), 'd-m-Y'); ?><span style="margin-left:30%;">Signature of Principal </span></p>
	</div></center>
<!--	</div>-->
       </center>
<!--    Aparna 14-03-2020-->     
    </div>
    <!--Ends Here -->
    </body>
</html>
