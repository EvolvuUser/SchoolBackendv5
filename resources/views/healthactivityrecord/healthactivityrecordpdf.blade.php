<html>
<style>
    .statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        padding:3px;
    }
    @page {
        size: A4;
        margin-top:0;
        margin-bottom:-1;
        margin-left:0;
        margin-right:0;
        padding: 0;
      }
    .first{
        background: url('https://sms.evolvu.in/public/health1_bg.jpg');
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: cover;
        object-fit: cover;
        background-repeat:no-repeat;
      }
</style>

<?php 
	$parent_info = get_student_parent_info($student_id,$customClaims);
    //  dd($parent_info);
    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id);
    $class = get_class_section_of_student($student_id);
    // dd($class);
    $class_array = explode(' ', $class);
    $class_name = $class_array[0];
    if($class_name >=1){
        $student_id_array=array($class_name=>$student_id);
        // dd($student_id_array);
        $temp_prev_stud_id = $student_id;
        $temp_student_id_array = array();
        for($i=($class_name-1); $i>=1;$i--){
            $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
            // dd($temp_prev_stud_id);
            $temp_student_id_array[$i] = $temp_prev_stud_id;
            $student_id_array = $student_id_array + $temp_student_id_array;
            
        }
        $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)),array_reverse(array_values($student_id_array)));
        // dd($student_id_array_new);
    }else{
        $student_id_array_new=''; 
    }
?>
<br>
<div class="first">
    <br>
    <br>
    <br>
    
    <table border="0" style="width:80%;margin-top: 20%;" align="center">
        
        <tr>
            <td>
                <table width="100%" border="0" style="">
                    <tr>
                        <td align="left" width="48%">Aadhar Card No. of Student (optional) : </td><td><div class="statistics_line"><?php  echo $parent_info[0]->stu_aadhaar_no ==null?'&nbsp;':$parent_info[0]->stu_aadhaar_no;   ?></div></td>
                    </tr>
                </table>
                <table width="100%" border="0" style="">
                    <tr>
                        <td align="left" width="10%">NAME :</td><td><div class="statistics_line"><?php  echo $parent_info[0]->first_name." ".$parent_info[0]->mid_name." ".$parent_info[0]->last_name;?></div></td>
                    </tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="25%">ADMISSION DATE :</td><td><div class="statistics_line"><?php echo date_format(date_create($parent_info[0] ->admission_date),'d-m-Y');?></div></td>
                        <td align="left" width="25%">DATE OF BIRTH : </td><td><div class="statistics_line"><?php echo date_format(date_create($parent_info[0]->dob),'d-m-Y');?></div></td>    
                    </tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="10%">M F T :</td><td><div class="statistics_line"><?php  echo $parent_info[0]->gender;?></div></td>
                        <td align="left" width="25%">BLOOD GROUP :</td><td><div class="statistics_line"><?php echo $parent_info[0]->blood_group;?></div></td>
                    </tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="25%">MOTHER'S NAME :</td><td><div class="statistics_line"> <?php echo $parent_info[0]->mother_name;?></div></td>  
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php 
                        if($parent_info[0]->m_dob!=''){
                            $m_year =  date('Y', strtotime($parent_info[0]->m_dob));
                        }else{
                            $m_year = '&nbsp;';
                        }
                         $m_weight = $health_activity_data[0]->m_weight ?: '&nbsp;';
                         $m_height = $health_activity_data[0]->m_height ?: '&nbsp;';
                         $m_blood_group = $parent_info[0]->m_blood_group ?: '&nbsp;';
                         
//                         $m_year = $parent_info[0]['m_year'] ?? '&nbsp;';
                        
//                        if($health_activity_data[0]['m_weight']!=''){
//                            $m_weight =  $health_activity_data[0]['m_weight'];
//                        }else{
//                            $m_weight = '-';
//                        }
//                        if($health_activity_data[0]['m_height']!=''){
//                            $m_height =  $health_activity_data[0]['m_height'];
//                        }else{
//                            $m_height = '-';
//                        }
//                        if($parent_info[0]['m_blood_group']!=''){
//                            $m_blood_group =  $parent_info[0]['m_blood_group'];
//                        }else{
//                            $m_blood_group = '-';
//                        }
                        ?>
                        <td align="left" width="8%">YOB :</td><td><div class="statistics_line"> <?php echo $m_year;?></div></td>
                        <td align="left" width="14%">WEIGHT :</td><td><div class="statistics_line"> <?php echo $m_weight;?></div></td>
                        <td align="left" width="12%">HEIGHT :</td><td><div class="statistics_line"> <?php echo $m_height;?></div></td>
                        <td align="left" width="25%">BLOOD GROUP :</td><td><div class="statistics_line"> <?php echo $m_blood_group;?></div></td>
                    </tr>
                    <tr></tr>
                </table>   
                <table width="100%" border="0">
                    <tr>
                      <?php  
//                        if($parent_info[0]['m_adhar_no']!=''){
//                            $m_adhar_no =  $parent_info[0]['m_adhar_no'];
//                        }else{
//                            $m_adhar_no = '-';
//                        }

                        
                        $m_adhar_no = $parent_info[0]->m_adhar_no ?: '&nbsp;';
                        ?>
                        <td align="left" width="27%">AADHAR CARD NO.: </td><td><div class="statistics_line"><?php echo $m_adhar_no;?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="25%">FATHER'S NAME :</td><td><div class="statistics_line"> <?php echo $parent_info[0]->father_name;?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php 
                        if($parent_info[0]->f_dob!=''){
                            $f_year = date('Y', strtotime($parent_info[0]->f_dob));
                        }else{
                            $f_year = '&nbsp;';
                        }
//                        if($health_activity_data[0]['f_height']!=''){
//                            $f_height = $health_activity_data[0]['f_height'];
//                        }else{
//                            $f_height = '-';
//                        }
//                        if($health_activity_data[0]['f_weight']!=''){
//                            $f_weight = $health_activity_data[0]['f_weight'];
//                        }else{
//                            $f_weight = '-';
//                        }
//                        if($parent_info[0]['f_blood_group']!=''){
//                            $f_blood_group = $parent_info[0]['f_blood_group'];
//                        }else{
//                            $f_blood_group = '-';
//                        }
                        //$space='&nbsp;';
                        $f_blood_group = $parent_info[0]->f_blood_group ?: '&nbsp;';
                        $f_weight = $health_activity_data[0]->f_weight ?: '&nbsp;';
                        $f_height = $health_activity_data[0]->f_height ?: '&nbsp;';
                        $f_blood_group = $parent_info[0]->f_blood_group ?: '&nbsp;';
                        ?>
                        <td align="left" width="8%">YOB :</td><td><div class="statistics_line">  <?php echo $f_year;?></div></td>
                        <td align="left" width="14%">WEIGHT :</td><td><div class="statistics_line">  <?php echo $f_weight;?></div></td>
                        <td align="left" width="12%">HEIGHT :</td><td><div class="statistics_line">  <?php echo $f_height;?></div></td>
                        <td align="left" width="25%">BLOOD GROUP :</td><td><div class="statistics_line">  <?php echo $f_blood_group;?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php  
//                        if($parent_info[0]['parent_adhar_no']!=''){
//                            $parent_adhar_no =  $parent_info[0]['parent_adhar_no'];
//                        }else{
//                            $parent_adhar_no = '-';
//                        }

                        $parent_adhar_no = $parent_info[0]->parent_adhar_no ?: '&nbsp;';
                        ?>
                        <td align="left" width="27%">AADHAR CARD NO.: </td><td><div class="statistics_line">  <?php echo $parent_adhar_no;?></div></td>
                    </tr>
                <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php  
//                        if($health_activity_data[0]['family_income']!=''){
//                            $family_income =  $health_activity_data[0]['family_income'];
//                        }else{
//                            $family_income = '-';
//                        }
                        $family_income = $health_activity_data[0]->family_income?: '&nbsp;';
                        ?>
                        <td align="left" width="37%">FAMILY MONTHLY INCOME :</td><td><div class="statistics_line">  <?php echo $family_income;?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="15%">ADDRESS:</td><td><div class="statistics_line">  <?php  echo $parent_info[0]->permant_add;?></div></td>
                    </tr>
                <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php
                        if(($parent_info[0]->f_mobile==NULL || $parent_info[0]->f_mobile =='' || $parent_info[0]->f_mobile =='NULL') && ($parent_info[0]->m_mobile ==NULL || $parent_info[0]->m_mobile =='' || $parent_info[0]->m_mobile =='NULL')){
                          $f_mobile = '&nbsp;';  
                        }elseif($parent_info[0]->f_mobile<>NULL){
                            $f_mobile = $parent_info[0]->f_mobile;
						}elseif($parent_info[0]->m_mobile<>NULL){
                            $f_mobile = $parent_info[0]->m_mobile;
                        }
                       
                        ?>
                        <td align="left" width="18%">PHONE NO.: </td><td><div class="statistics_line"> &nbsp; </div></td>
                        <td align="left" align="left" width="5%">(M): </td><td><div class="statistics_line">  <?php  echo $f_mobile;?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php  
                        $cwsn = $health_activity_data[0]->cwsn ?: '&nbsp;';
                        ?>
                        <td align="left" width="24%">CWSM, SPECIFY :</td><td><div class="statistics_line">  <?php echo $cwsn;?></div></td>
                    </tr>
                    <tr></tr>
                </table>
            </td>
        </tr>
    </table>
    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
</div>
</html>
<br>
<html>
<style>
    @page {
        size: A4;
        margin-top:0;
        margin-bottom:0;
        margin-left:0;
        margin-right:0;
        padding: 0;
      }
    .second {
        background: url('https://sms.evolvu.in/public/health2_bg.jpg');
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: cover;
        object-fit: cover;
        background-repeat:no-repeat;
      }
    .tdalign{
        text-align: center;
    }
    .measures_comp{
        font-size: 12px;
    }

</style>

<?php $parent_info = get_student_parent_info($student_id,$customClaims);
    $health_activity_data =check_health_activity_data_exist_for_studentid($student_id);
    //print_r($health_activity_data);
    $class = get_class_section_of_student($student_id);
    $class_array = explode(' ', $class);
    $class_name = $class_array[0];
    if($class_name >=1){
        $student_id_array=array($class_name=>$student_id);
        $temp_prev_stud_id = $student_id;
        $temp_student_id_array = array();
        for($i=($class_name-1); $i>=1;$i--){
            $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
            $temp_student_id_array[$i] = $temp_prev_stud_id;
            $student_id_array = $student_id_array + $temp_student_id_array;
        }
        $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)),array_reverse(array_values($student_id_array)));
    }else{
        $student_id_array_new=''; 
    }
?>
    <div style="page-break-before:always" class="second" align="center">  
        <br><br><br>
    <table class="" border="1" width="80%" style="margin-top: 15%;border-spacing: 0px;border-collapse:collapse;" align="center">
        <tr> 
            <td><b>Fitness Component</b></td>
            <td colspan="2"><b>Fitness Parameters</b></td>
            <td><b>Test Name</b></td>
            <td><b>What does it Measures</b></td>
            <td><b>Class 1st</b></td>
            <td><b>Class 2nd</b></td>
            <td><b>Class 3rd</b></td>
            <td><b>Class 4th</b></td>
            <td><b>Class 5th</b></td>
        </tr>
        <tr>
            <td rowspan="6">Health Components</td>
            <td>Body Compostion</td>
            <td></td>
            <td><b>BMI</b></td>
            <td class="measures_comp">Body Mass Index of specific Age and Gender</td>
            <?php 
            for($j=1;$j<=5;$j++)
            {
                ${'bmi_'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                         if(isset($health_activity_data[0])){
                            ${'bmi_'.$j} = $health_activity_data[0]->bmi;
                         }
                    }
                } ?>
            <td class="tdalign"><?php echo(${'bmi_'.$j}); ?> </td>
       <?php }   ?>
        </tr>        
        <tr>
            <td rowspan="2">Muscular Strength</td>
            <td>Core</td>
            <td><b>Partial Curl Up</b></td>
            <td class="measures_comp">Abdominal Muscular Endurance</td>
              <?php 
           for($j=1;$j<=5;$j++)
            {
               ${'partial_curl_up_'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'partial_curl_up_'.$j} = $health_activity_data[0]->partial_curl_up;
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'partial_curl_up_'.$j}; ?></td> 
        <?php } ?>    
        </tr>
        <tr>
            <td>Upper Body</td>
            <td><b>Flexed/Bent Arm Hang</b></td>
            <td class="measures_comp">Muscular Endurance/ Functional Strength</td>
    <?php for($j=1;$j<=5;$j++){
                ${'flex_bent_arm_hang'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if(isset($health_activity_data[0])){
                            ${'flex_bent_arm_hang'.$j} = $health_activity_data[0]->flex_bent_arm_hang;
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'flex_bent_arm_hang'.$j}; ?></td> 
        <?php } ?>   
        </tr>
        <tr>
            <td>Flexibility</td>
            <td></td>
            <td><b>Sit and Reach</b></td>
            <td class="measures_comp">Measure the flexibility of lower back and hamstring muscles</td>
               <?php 
           for($j=1;$j<=5;$j++)
            {
               ${'sit_n_reach'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'sit_n_reach'.$j} = $health_activity_data[0]->sit_n_reach;
                        }
                    }
                }?>
            <td class="tdalign"><?php  echo ${'sit_n_reach'.$j}; ?></td> 
        <?php } ?>  
        </tr>
        <tr>
            <td>Endurance</td>
            <td></td>
            <td><b>600 Mtr Run</b></td>
            <td class="measures_comp">Cardiovascular Fitness/ Cardiovascular Endurance</td>
               <?php 
           for($j=1;$j<=5;$j++)
            {
               ${'600m_run'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if(isset($health_activity_data[0])){
                            ${'600m_run'.$j} = $health_activity_data[0]->{'600m_run'};
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'600m_run'.$j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td>Balance</td>
            <td>Static Balance</td>
            <td><b>Flamingo Balance Test</b></td>
            <td class="measures_comp">Ability to balance successfully on a single leg</td>
                  <?php 
           for($j=1;$j<=5;$j++)
            {
               ${'flamingo_bel_test'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'flamingo_bel_test'.$j} = $health_activity_data[0]->flamingo_bel_test;
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'flamingo_bel_test'.$j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td rowspan="5">Skill Components</td>
            <td>Ability</td>
            <td></td>
            <td><b>Shuttle Run</b></td>
            <td class="measures_comp">Test of speed and agility </td>
                    <?php 
           for($j=1;$j<=5;$j++)
            {
               ${'shuttle_run'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'shuttle_run'.$j} = $health_activity_data[0]->shuttle_run;
                        }
                    }
                }?>
            <td class="tdalign"><?php  echo ${'shuttle_run'.$j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td>Speed</td>
            <td></td>
            <td><b>Sprint/ Dash</b></td>
            <td class="measures_comp">Determines acceleration and Speed</td>
            <?php for($j=1;$j<=5;$j++)
            {
                ${'sprint_dash'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'sprint_dash'.$j} = $health_activity_data[0]->sprint_dash;
                        }
                    }
                }?>
                <td class="tdalign"><?php echo ${'sprint_dash'.$j}; ?></td> 
    <?php   } ?> 
        </tr>
        <tr>
            <td>Power</td>
             <td></td>
            <td><b>Standing Vertical Jump</b></td>
            <td class="measures_comp">Measures the Leg Muscles Power</td>
            <?php for($j=1;$j<=5;$j++)
            {
                ${'standing_vertical_jump'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'standing_vertical_jump'.$j} = $health_activity_data[0]->standing_vertical_jump;
                        }
                    }
                }?>
                <td class="tdalign"><?php echo ${'standing_vertical_jump'.$j}; ?></td> 
    <?php   } ?> 
        </tr>
        <tr>
            <td>Coordination</td>
            <td></td>
            <td><b>Plate Tapping</b></td>
            <td class="measures_comp">Tests speed and coordination of limb movement</td>
            <?php for($j=1;$j<=5;$j++)
            {
                ${'plate_tapping'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if(isset($health_activity_data[0])){
                            ${'plate_tapping'.$j} = $health_activity_data[0]->plate_tapping;
                        }
                    }
                }?>
                <td class="tdalign"><?php  echo ${'plate_tapping'.$j}; ?></td> 
    <?php   } ?> 

        </tr>
        <tr>
            <td></td>
            <td></td>
            <td><b>Alternative Hand Wall Toss Test</b></td>
            <td class="measures_comp">Measures hand eye coordination</td>
            <?php for($j=1;$j<=5;$j++)
            {
                ${'alternative_handwall_toss'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'alternative_handwall_toss'.$j} = $health_activity_data[0]->alternative_handwall_toss;
                        }
                    }
                }?>
                <td class="tdalign"><?php  echo ${'alternative_handwall_toss'.$j}; ?></td> 
    <?php   } ?> 

        </tr>
    </table>
    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
</div>
</html>
<html>
<style>
    .statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        padding:3px;
    }
    @page {
        size: A4;
        margin-top:0;
        margin-bottom:-1;
        margin-left:0;
        margin-right:0;
        padding: 0;
      }
    .second {
        background: url('https://sms.evolvu.in/public/health2_bg.jpg');
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: cover;
        object-fit: cover;
        background-repeat:no-repeat;
      }
    .tdalign{
        text-align: center;
        }
    .measures_comp{
        font-size: 12px;
    }
</style>

<?php $parent_info = get_student_parent_info($student_id,$customClaims);
    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id);
    $class = get_class_section_of_student($student_id);
    $class_array = explode(' ', $class);
    $class_name = $class_array[0];
    if($class_name >=1){
        $student_id_array=array($class_name=>$student_id);
        $temp_prev_stud_id = $student_id;
        $temp_student_id_array = array();
        for($i=($class_name-1); $i>=1;$i--){
            $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
            $temp_student_id_array[$i] = $temp_prev_stud_id;
            $student_id_array = $student_id_array + $temp_student_id_array;
        }
        $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)),array_reverse(array_values($student_id_array)));
    }else{
        $student_id_array_new=''; 
    } 
    if($class_name>=6){
?>
<div style="page-break-before:always" class="second">
    <table class="table-responsive" border="1" width="80%" style="margin-top: 15%;border-spacing: 0px;border-collapse:collapse;" align="center">
        <tr> 
            <td><b>Fitness Component</b></td>
            <td colspan="2"><b>Fitness Parameters</b></td>
            <td><b>Test Name</b></td>
            <td><b>What does it Measures</b></td>
            <td><b>Class 6th</b></td>
            <td><b>Class 7th</b></td>
            <td><b>Class 8th</b></td>
            <td><b>Class 9th</b></td>
            <td><b>Class 10th</b></td>
            <td><b>Class 11th</b></td>
            <td><b>Class 12th</b></td>
         </tr>
        <tr>
            <td rowspan="6">Health Components</td>
            <td>Body Compostion</td>
            <td></td>
            <td><b>BMI</b></td>
            <td class="measures_comp">Body Mass Index of specific Age and Gender</td>

            <?php 
           for($j=6;$j<=12;$j++)
            {
               ${'bmi_'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if(isset($health_activity_data[0])){
                            ${'bmi_'.$j} = $health_activity_data[0]->bmi;
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'bmi_'.$j}; ?></td> 
            <?php } ?>    
        </tr>    
        <tr>
            <td rowspan="2">Muscular Strength</td>
            <td>Core</td>
            <td><b>Partial Curl Up</b></td>
            <td class="measures_comp">Abdominal Muscular Endurance</td>
              <?php 
           for($j=6;$j<=12;$j++)
            {
               ${'partial_curl_up_'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'partial_curl_up_'.$j} = $health_activity_data[0]->partial_curl_up;
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'partial_curl_up_'.$j}; ?></td> 
            <?php } ?>    
        </tr>
        <tr>
            <td>Upper Body</td>
            <td><b>Flexed/Bent Arm Hang</b></td>
            <td class="measures_comp">Muscular Endurance/ Functional Strength</td>

                <?php 
           for($j=6;$j<=12;$j++)
            {
               ${'flex_bent_arm_hang'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if(isset($health_activity_data[0])){
                            ${'flex_bent_arm_hang'.$j} = $health_activity_data[0]->flex_bent_arm_hang;
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'flex_bent_arm_hang'.$j}; ?></td> 
            <?php } ?>   
        </tr>
        <tr>
            <td>Flexibility</td>
            <td></td>
            <td><b>Sit and Reach</b></td>
            <td class="measures_comp">Measure the flexibility of lower back and hamstring muscles</td>
               <?php 
           for($j=6;$j<=12;$j++)
            {
               ${'sit_n_reach'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if(isset($health_activity_data[0])){
                            ${'sit_n_reach'.$j} = $health_activity_data[0]->sit_n_reach;
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'sit_n_reach'.$j}; ?></td> 
            <?php } ?>  
        </tr>
        <tr>
            <td>Endurance</td>
            <td></td>
            <td><b>600 Mtr Run</b></td>
            <td class="measures_comp">Cardiovascular Fitness/ Cardiovascular Endurance</td>
               <?php 
           for($j=6;$j<=12;$j++)
            {
               ${'600m_run'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if(isset($health_activity_data[0])){
                        ${'600m_run'.$j} = $health_activity_data[0]->{'600m_run'};
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'600m_run'.$j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td>Balance</td>
            <td>Static Balance</td>
            <td><b>Flamingo Balance Test</b></td>
            <td class="measures_comp">Ability to balance successfully on a single leg</td>
                  <?php 
           for($j=6;$j<=12;$j++)
            {
               ${'flamingo_bel_test'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'flamingo_bel_test'.$j} = $health_activity_data[0]->flamingo_bel_test;
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'flamingo_bel_test'.$j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td rowspan="5">Skill Components</td>
            <td>Ability</td>
            <td></td>
            <td><b>Shuttle Run</b></td>
            <td class="measures_comp">Test of speed and agility </td>
                    <?php 
           for($j=6;$j<=12;$j++)
            {
               ${'shuttle_run'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if(isset($health_activity_data[0])){
                            ${'shuttle_run'.$j} = $health_activity_data[0]->shuttle_run;
                        }
                    }
                }?>
            <td class="tdalign"><?php echo ${'shuttle_run'.$j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td>Speed</td>
            <td></td>
            <td><b>Sprint/ Dash</b></td>
            <td class="measures_comp">Determines acceleration and Speed</td>
            <?php for($j=6;$j<=12;$j++)
            {
                ${'sprint_dash'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'sprint_dash'.$j} = $health_activity_data[0]->sprint_dash;
                        }
                    }
                }?>
                <td class="tdalign"><?php echo ${'sprint_dash'.$j}; ?></td> 
    <?php   } ?> 
        </tr>
        <tr>
            <td>Power</td>
             <td></td>
            <td><b>Standing Vertical Jump</b></td>
            <td class="measures_comp">Measures the Leg Muscles Power</td>
            <?php for($j=6;$j<=12;$j++)
            {
               ${'standing_vertical_jump'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if(isset($health_activity_data[0])){
                            ${'standing_vertical_jump'.$j} = $health_activity_data[0]->standing_vertical_jump;
                        }
                    }
                }?>
                <td class="tdalign"><?php dd("Hello"); echo ${'standing_vertical_jump'.$j}; ?></td> 
    <?php   } ?> 
        </tr>
        <tr>
            <td>Coordination</td>
            <td></td>
            <td><b>Plate Tapping</b></td>
            <td class="measures_comp">Tests speed and coordination of limb movement</td>
            <?php for($j=6;$j<=12;$j++)
            {
               ${'plate_tapping'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if(isset($health_activity_data[0])){
                            ${'plate_tapping'.$j} = $health_activity_data[0]->plate_tapping;
                        }
                    }
                }?>
                <td class="tdalign"><?php echo ${'plate_tapping'.$j}; ?></td> 
    <?php   } ?> 

        </tr>
        <tr>
            <td></td>
            <td></td>
            <td><b>Alternative Hand Wall Toss Test</b></td>
            <td class="measures_comp">Measures hand eye coordination</td>
            <?php for($j=6;$j<=12;$j++)
            {
               ${'alternative_handwall_toss'.$j} = '';
                if(isset($student_id_array_new[$j])){
                    if($student_id_array_new[$j]!=0){
                       $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                        if(isset($health_activity_data[0])){
                            ${'alternative_handwall_toss'.$j} = $health_activity_data[0]->alternative_handwall_toss;
                        }
                    }
                }?>
                <td class="tdalign"><?php echo ${'alternative_handwall_toss'.$j}; ?></td> 
    <?php   } ?> 

        </tr>
    </table>
    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
</div>
    <?php }?>
</html>
<html>
<style>
    .statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        padding:3px;
    }
    @page {
        size: A4;
        margin-top:0;
        margin-bottom:-1;
        margin-left:0;
        margin-right:0;
        padding: 0;
      }
    .third {
        background: url('https://sms.evolvu.in/public/health2_bg.jpg');
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: cover;
        object-fit: cover;
        background-repeat:no-repeat;
      }
    .tdalign{
        text-align: center;
    }
</style>

<?php $parent_info = get_student_parent_info($student_id,$customClaims);
    // dd($parent_info);
    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id);
    $class = get_class_section_of_student($student_id);
    $class_array = explode(' ', $class);
    $class_name = $class_array[0];
    if($class_name >=1){
        $student_id_array=array($class_name=>$student_id);
        $temp_prev_stud_id = $student_id;
        $temp_student_id_array = array();
        for($i=($class_name-1); $i>=1;$i--){
            $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
            $temp_student_id_array[$i] = $temp_prev_stud_id;
            $student_id_array = $student_id_array + $temp_student_id_array;
        }
        $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)),array_reverse(array_values($student_id_array)));
    }else{
        $student_id_array_new=''; 
    }
?>
<div style="page-break-before:always" class="third">
    <table class="table-responsive col-md-12" border="1" width="80%" style="margin-top: 15%;border-spacing: 0px;border-collapse:collapse;" align="center">
        <tr> 
            <td><b>Components</b></td>
            <td><b>Parameters</b></td>
            <td><b>Class 1st</b></td>
            <td><b>Class 2nd</b></td>
            <td><b>Class 3rd</b></td>
            <td><b>Class 4th</b></td>
            <td><b>Class 5th</b></td>
        </tr>
        <tr>
            <td>Vision</td>
            <td>R.E/L.E</td>
        <?php for($j=1;$j<=5;$j++)
            {
                ${'vision_re'.$j} = '';
                ${'vision_le'.$j} = '';
                $vision_combine = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'vision_re'.$j} = $health_activity_data[0]->vision_re;
                        ${'vision_le'.$j} = $health_activity_data[0]->vision_le;
						if(${'vision_re'.$j}=="" && ${'vision_le'.$j}=="")
							$vision_combine ="";
						else
							$vision_combine = ${'vision_re'.$j}."/".${'vision_le'.$j};
                    }
                }
            }?>
            <td class="tdalign"><?php  echo $vision_combine; ?></td> 
<?php   } ?> 
        </tr>
<!--
        <tr>
            <td>Left Eye</td>
        <!?php for($j=1;$j<=5;$j++)
        {
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = $this->fitness_model->get_health_activity_report_for_students_by_student_id($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'vision_le'.$j} = $health_activity_data[0]['vision_le'];
                    }else{
                        ${'vision_le'.$j} = '';
                    }
                }else{
                    ${'vision_le'.$j} = '';
                }
            }else{
                ${'vision_le'.$j} = '';
            }?>
            <td class="tdalign"><-?php echo ${'vision_le'.$j}; ?></td> 
<?php  // } ?> 
        </tr>
-->
        <tr>
            <td>Ears</td>
            <td>Right/Left</td> 
    <?php for($j=1;$j<=5;$j++)
        {
            ${'ears_right'.$j} = '';
            ${'ears_left'.$j} = '';
            $ear_combine = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'ears_right'.$j} = $health_activity_data[0]->ears_right;
                        ${'ears_left'.$j} = $health_activity_data[0]->ears_left;
						if(${'ears_right'.$j}=="" && ${'ears_left'.$j}=="")	
							$ear_combine ="";
						else
							$ear_combine = ${'ears_right'.$j}."/".${'ears_left'.$j};
                    }
                }
            }?>
            <td class="tdalign"><?php echo $ear_combine; ?></td> 
<?php   } ?> 
        </tr>
<!--
        <tr>
            <td>Left Ear</td>
  <1?php for($j=1;$j<=5;$j++)
        {
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = $this->fitness_model->get_health_activity_report_for_students_by_student_id($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'ears_left'.$j} = $health_activity_data[0]['ears_left'];
                    }else{
                        ${'ears_left'.$j} = '';
                    }
                }else{
                    ${'ears_left'.$j} = '';
                }
            }else{
                ${'ears_left'.$j} = '';
            }?>
            <td class="tdalign"><-?php echo ${'ears_left'.$j}; ?></td> 
<-?php   } ?> 
        </tr>
-->
        <tr>
            <td rowspan="3">Teeth Occlusion</td>
            <td>Caries</td>
<?php for($j=1;$j<=5;$j++)
        {
            ${'teeth_caries'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'teeth_caries'.$j} = $health_activity_data[0]->teeth_caries;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'teeth_caries'.$j}; ?></td> 
<?php   } ?>    
            
        </tr>
        <tr>
            <td>Tonsils</td>
<?php 	for($j=1;$j<=5;$j++)
        {
			${'teeth_tonsils'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'teeth_tonsils'.$j} = $health_activity_data[0]->teeth_tonsils;
                    }
                }
             }?>
            <td class="tdalign"><?php echo ${'teeth_tonsils'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Gums</td>
        <?php 
		for($j=1;$j<=5;$j++)
        {
			${'teeth_gums'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'teeth_gums'.$j} = $health_activity_data[0]->teeth_gums;
                    }
                } 
            }?>
            <td class="tdalign"><?php echo ${'teeth_gums'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td rowspan="2"><b>General Body Measurements</b></td>
            <td>Height(cm)</td>
    <?php 
		for($j=1;$j<=5;$j++)
        {
			${'height'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'height'.$j} = $health_activity_data[0]->height;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'height'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Weight(kg)</td>
        <?php 
		for($j=1;$j<=5;$j++)
        {
			${'weight'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'weight'.$j} = $health_activity_data[0]->weight;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'weight'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td rowspan="2">Circumferences</td>
            <td>Hip(inches)</td>
<?php 	for($j=1;$j<=5;$j++)
        {
			${'hip'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'hip'.$j} = $health_activity_data[0]->hip;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'hip'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Waist(inches)</td>
<?php 	for($j=1;$j<=5;$j++)
        {
			${'waist'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'waist'.$j} = $health_activity_data[0]->waist;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'waist'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td rowspan="2">Health Status</td>
            <td>Pulse</td>
<?php 	for($j=1;$j<=5;$j++)
        {
			${'pulse'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'pulse'.$j} = $health_activity_data[0]->pulse;
                    }
                }
			}?>
            <td class="tdalign"><?php echo ${'pulse'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Blood Pressure</td>
<?php 	for($j=1;$j<=5;$j++)
        {
           ${'bp'.$j} = '';
		   if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'bp'.$j} = $health_activity_data[0]->bp;
                    }
                }
            }
?>
            <td class="tdalign"><?php echo ${'bp'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Posture Evaluation</td>
            <td><b>If Any:</b><br> Head Forward/Sunken Chest/Round Shoulder/ Kyphisis/Lordosis/Abdominal Ptosis/ Body Lean/ Tilted Head/ Shoulders Uneven/ Scoliosis/ Flat Feet/ Knock Knees/ Bow Legs</td>
<?php 	for($j=1;$j<=5;$j++)
        {
			${'posture_evaluation'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'posture_evaluation'.$j} = $health_activity_data[0]->posture_evaluation;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'posture_evaluation'.$j}; ?></td> 
<?php   } ?>
        </tr>
        <tr>
            <td rowspan="6">Sporting Activities</td>
            <td><b><u>Strand 1</u></b><br>1. Athlethics/ Swimming<br>2. Team Game<br>3. Individual Game<br>4. Adventure Game</td>
<?php 	for($j=1;$j<=5;$j++)
        {
			${'strd1'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'strd1'.$j} = $health_activity_data[0]->strd1;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'strd1'.$j}; ?></td> 
<?php   } ?>
        </tr>
        <tr>
            <td><b><u>Strand 2:</u><br> Health and Fitness</b><br>(Mass PT, Yoga, Dance, Calisthenics, Jogging, Cross Country Run, Working Outs using weights/ gym equipment, Tai Chi etc).</td>
<?php 
		for($j=1;$j<=5;$j++)
        {
			${'strd2_health_fitness'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'strd2_health_fitness'.$j} = $health_activity_data[0]->strd2_health_fitness;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'strd2_health_fitness'.$j}; ?></td> 
<?php   } ?>
        </tr>
        <tr>
            <td><b><u>Strand 3:</u><br> SEWA</b></td>
<?php 
		for($j=1;$j<=5;$j++)
        {
			${'strd3_sewa'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=0){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'strd3_sewa'.$j} = $health_activity_data[0]->strd3_sewa;
                    }
                }
            }
?>
            <td class="tdalign"><?php  echo ${'strd3_sewa'.$j}; ?></td> 
<?php   } ?>
        </tr>
    </table>
</div>
</html>
<html>
<style>
    .statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        padding:3px;
    }
    @page {
        size: A4;
        margin-top:0;
        margin-bottom:-1;
        margin-left:0;
        margin-right:0;
        padding: 0;
      }
    .third {
        background: url('https://sms.evolvu.in/public/health2_bg.jpg');
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: cover;
        object-fit: cover;
        background-repeat:no-repeat;
      }
    .tdalign{
        text-align: center;
    }
</style>

<?php 
	$parent_info = get_student_parent_info($student_id,$customClaims);
    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id);
    $class = get_class_section_of_student($student_id);
    $class_array = explode(' ', $class);
    $class_name = $class_array[0];
    if($class_name >=1){
        $student_id_array=array($class_name=>$student_id);
        $temp_prev_stud_id = $student_id;
        $temp_student_id_array = array();
        for($i=($class_name-1); $i>=1;$i--){
            $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
            $temp_student_id_array[$i] = $temp_prev_stud_id;
            $student_id_array = $student_id_array + $temp_student_id_array;
        }
        $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)),array_reverse(array_values($student_id_array)));
    }else{
        $student_id_array_new=''; 
    }
    if($class_name>=6){
?>
<div style="page-break-before:always" class="third">
    <table class="table-responsive" border="1" width="80%" style="margin-top: 15%;border-spacing: 0px;border-collapse:collapse;" align="center">
        <tr> 
            <td><b>Components</b></td>
            <td><b>Parameters</b></td>
            <td><b>Class 6th</b></td>
            <td><b>Class 7th</b></td>
            <td><b>Class 8th</b></td>
            <td><b>Class 9th</b></td>
            <td><b>Class 10th</b></td>
            <td><b>Class 11th</b></td>
            <td><b>Class 12th</b></td>
        </tr>
        <tr>
            <td>Vision</td>
            <td>R.E/L.E</td>
    <?php 
		for($j=6;$j<=12;$j++)
        {
			${'vision_re'.$j} = '';
            ${'vision_le'.$j} = '';
            $vision_combine = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'vision_re'.$j} = $health_activity_data[0]->vision_re;
                        ${'vision_le'.$j} = $health_activity_data[0]->vision_le;
                        $vision_combine = ${'vision_re'.$j}."/".${'vision_le'.$j};
                    }
                }
            }?>
            <td class="tdalign"><?php echo $vision_combine; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Ears</td>
            <td>Right/Left</td> 
    <?php for($j=6;$j<=12;$j++)
        {
			${'ears_right'.$j} = '';
            ${'ears_left'.$j} = '';
            $ear_combine = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
				   if(isset($health_activity_data[0])){
						${'ears_right'.$j} = $health_activity_data[0]->ears_right;
                        ${'ears_left'.$j} = $health_activity_data[0]->ears_left;
                       $ear_combine = ${'ears_right'.$j}."/".${'ears_left'.$j};
				   }
                }
            }?>
            <td class="tdalign"><?php echo $ear_combine; ?></td> 
<?php   } ?> 
        </tr>
<!--
        <tr>
            <td>Left Ear</td>
  <-?php for($j=6;$j<=12;$j++)
        {
			${'ears_left'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = $this->fitness_model->get_health_activity_report_for_students_by_student_id($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'ears_left'.$j} = $health_activity_data[0]['ears_left'];
                    }
                }
            }?>
            <td class="tdalign"><-?php echo ${'ears_left'.$j}; ?></td> 
<-?php   } ?> 
        </tr>
-->
        <tr>
            <td rowspan="3">Teeth Occlusion</td>
            <td>Caries</td>
<?php 	for($j=6;$j<=12;$j++)
        {
			${'teeth_caries'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'teeth_caries'.$j} = $health_activity_data[0]->teeth_caries;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'teeth_caries'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Tonsils</td>
<?php for($j=6;$j<=12;$j++)
        {
			${'teeth_tonsils'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'teeth_tonsils'.$j} = $health_activity_data[0]->teeth_tonsils;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'teeth_tonsils'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Gums</td>
        <?php 
		for($j=6;$j<=12;$j++)
        {
			${'teeth_gums'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'teeth_gums'.$j} = $health_activity_data[0]->teeth_gums;
                    }
                }
            }
			?>
            <td class="tdalign"><?php echo ${'teeth_gums'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td rowspan="2"><b>General Body Measurements</b></td>
            <td>Height(cm)</td>
<?php 
		for($j=6;$j<=12;$j++)
        {
			${'height'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'height'.$j} = $health_activity_data[0]->height;
                     }
                }
            }
	?>
            <td class="tdalign"><?php echo ${'height'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Weight(kg)</td>
        <?php 
		for($j=6;$j<=12;$j++)
        {
			${'weight'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'weight'.$j} = $health_activity_data[0]->weight;
                    }
                }
            }
			?>
            <td class="tdalign"><?php echo ${'weight'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td rowspan="2">Circumferences</td>
            <td>Hip(inches)</td>
<?php for($j=6;$j<=12;$j++)
        {
			${'hip'.$j} = '';
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'hip'.$j} = $health_activity_data[0]->hip;
                    }
                }
            }?>
            <td class="tdalign"><?php echo ${'hip'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Waist(inches)</td>
<?php for($j=6;$j<=12;$j++)
        {
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'waist'.$j} = $health_activity_data[0]->waist;
                    }else{
                        ${'waist'.$j} = '';
                    }
                }else{
                    ${'waist'.$j} = '';
                }
            }else{
                ${'waist'.$j} = '';
            }?>
            <td class="tdalign"><?php echo ${'waist'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td rowspan="2">Health Status</td>
            <td>Pulse</td>
<?php for($j=6;$j<=12;$j++)
        {
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]); 
                    if(isset($health_activity_data[0])){
                        ${'pulse'.$j} = $health_activity_data[0]->pulse;
                    }else{
                       ${'pulse'.$j} = ''; 
                    }
                }else{
                ${'pulse'.$j} = '';
            }
            }else{
                ${'pulse'.$j} = '';
            }?>
            <td class="tdalign"><?php echo ${'pulse'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Blood Pressure</td>
<?php for($j=6;$j<=12;$j++)
        {
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'bp'.$j} = $health_activity_data[0]->bp;
                    }else{
                        ${'bp'.$j} = '';
                    }
                }else{
                    ${'bp'.$j} = '';
                }
            }else{
                ${'bp'.$j} = '';
            }?>
            <td class="tdalign"><?php echo ${'bp'.$j}; ?></td> 
<?php   } ?> 
        </tr>
        <tr>
            <td>Posture Evaluation</td>
            <td><b>If Any:</b><br> Head Forward/Sunken Chest/Round Shoulder/ Kyphisis/Lordosis/Abdominal Ptosis/ Body Lean/ Tilted Head/ Shoulders Uneven/ Scoliosis/ Flat Feet/ Knock Knees/ Bow Legs</td>
<?php for($j=6;$j<=12;$j++)
        {
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'posture_evaluation'.$j} = $health_activity_data[0]->posture_evaluation;
                    }else{
                       ${'posture_evaluation'.$j} = ''; 
                    }
                }else{
                    ${'posture_evaluation'.$j} = '';
                }
            }else{
                ${'posture_evaluation'.$j} = '';
            }?>
            <td class="tdalign"><?php echo ${'posture_evaluation'.$j}; ?></td> 
<?php   } ?>
        </tr>
        <tr>
            <td rowspan="6">Sporting Activities</td>
            <td><b><u>Strand 1</u></b><br>1. Athlethics/ Swimming<br>2. Team Game<br>3. Individual Game<br>4. Adventure Game</td>
<?php for($j=6;$j<=12;$j++)
        {
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'strd1'.$j} = $health_activity_data[0]->strd1;
                    }else{
                       ${'strd1'.$j} = ''; 
                    }
                }else{
                    ${'strd1'.$j} = '';
                }
            }else{
                ${'strd1'.$j} = '';
            }?>
            <td class="tdalign"><?php echo ${'strd1'.$j}; ?></td> 
<?php   } ?>
        </tr>
        <tr>
            <td><b><u>Strand 2:</u><br> Health and Fitness</b><br>(Mass PT, Yoga, Dance, Calisthenics, Jogging, Cross Country Run, Working Outs using weights/ gym equipment, Tai Chi etc).</td>
<?php for($j=6;$j<=12;$j++)
        {
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'strd2_health_fitness'.$j} = $health_activity_data[0]->strd2_health_fitness;
                    }else{
                        ${'strd2_health_fitness'.$j} = '';
                    }
                }else{
                    ${'strd2_health_fitness'.$j} = '';
                }
            }else{
                ${'strd2_health_fitness'.$j} = '';
            }?>
            <td class="tdalign"><?php echo ${'strd2_health_fitness'.$j}; ?></td> 
<?php   } ?>
        </tr>
        <tr>
            <td><b><u>Strand 3:</u><br> SEWA</b></td>
<?php for($j=6;$j<=12;$j++)
        {
            if(isset($student_id_array_new[$j])){
                if($student_id_array_new[$j]!=''){
                   $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if(isset($health_activity_data[0])){
                        ${'strd3_sewa'.$j} = $health_activity_data[0]->strd3_sewa;
                    }else{
                        ${'strd3_sewa'.$j} = '';
                    }
                }else{
                    ${'strd3_sewa'.$j} = '';
                }
            }else{
                ${'strd3_sewa'.$j} = '';
            }?>
            <td class="tdalign"><?php echo ${'strd3_sewa'.$j}; ?></td> 
<?php   } ?>
        </tr>
    </table>
</div>
    <?php }?>
</html>