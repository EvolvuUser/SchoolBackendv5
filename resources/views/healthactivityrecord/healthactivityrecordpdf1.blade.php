<?php

$class = get_class_section_of_student($student_id);
// dd($class);
$class_array = !empty($class) ? explode(' ', $class) : [];
// dd($class_array);
$classname = isset($class_array[0]) ? (int)$class_array[0] : 0;
// dd($classname);

?>



 @if ($classname >= 1 && $classname <= 5)
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
        height:100%;
      }

        

    </style>
 <?php
$parent_info = get_student_parent_info($student_id, $customClaims);
 dd($parent_info);
$health_activity_data = check_health_activity_data_exist_for_studentid($student_id);
// dd($health_activity_data);
$class = get_class_section_of_student($student_id);
// dd($class);
$class_array = explode(' ', $class);
// dd($class_array);
// $class_name = $class_array[0];
$class_name = isset($class_array[0]) ? (int)$class_array[0] : 0;  //mahima
// dd($class_name);
if ($class_name >= 1) {
    $student_id_array = array($class_name => $student_id);
    // dd($student_id_array);
    $temp_prev_stud_id = $student_id;
    $temp_student_id_array = array();
    for ($i = ($class_name - 1); $i >= 1; $i--) {
        $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
        // dd($temp_prev_stud_id);
        $temp_student_id_array[$i] = $temp_prev_stud_id;
        $student_id_array = $student_id_array + $temp_student_id_array;
    }
    $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)), array_reverse(array_values($student_id_array)));
    // dd($student_id_array_new);
} else {
    $student_id_array_new = '';
}
?>
<div class="first" >
    <br><br><br><br>
    <table border="0" style="width:80%;margin-top:20%" align="center">
        
        <tr>
            <td>
                <table width="100%" border="0" style="">
                    <tr>
                        <td align="left" width="48%">Aadhar Card No. of Student (optional) : </td><td><div class="statistics_line"><?php echo $parent_info[0]->stu_aadhaar_no == null ? '&nbsp;' : $parent_info[0]->stu_aadhaar_no; ?></div></td>
                    </tr>
                </table>
                <table width="100%" border="0" style="">
                    <tr>
                        <td align="left" width="10%">NAME :</td><td><div class="statistics_line"><?php echo $parent_info[0]->first_name . ' ' . $parent_info[0]->mid_name . ' ' . $parent_info[0]->last_name; ?></div></td>
                    </tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="25%">ADMISSION DATE :</td><td><div class="statistics_line"><?php echo date_format(date_create($parent_info[0]->admission_date), 'd-m-Y'); ?></div></td>
                        <td align="left" width="25%">DATE OF BIRTH : </td><td><div class="statistics_line"><?php echo date_format(date_create($parent_info[0]->dob), 'd-m-Y'); ?></div></td>    
                    </tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="10%">M F T :</td><td><div class="statistics_line"><?php echo $parent_info[0]->gender; ?></div></td>
                        <td align="left" width="25%">BLOOD GROUP :</td><td><div class="statistics_line"><?php echo $parent_info[0]->blood_group; ?></div></td>
                    </tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="25%">MOTHER'S NAME :</td><td><div class="statistics_line"> <?php echo $parent_info[0]->mother_name; ?></div></td>  
                    </tr>
                    <tr></tr>
                </table>
                01-04-2026
                <table width="100%" border="0">
                    <tr>
                        <?php
                        if ($parent_info[0]->m_dob != '') {
                            $m_year = date('Y', strtotime($parent_info[0]->m_dob));
                        } else {
                            $m_year = '&nbsp;';
                        }
                        $m_weight = $health_activity_data[0]->m_weight ?: '&nbsp;';
                        $m_height = $health_activity_data[0]->m_height ?: '&nbsp;';
                        $m_blood_group = $parent_info[0]->m_blood_group ?: '&nbsp;';
                        ?>
                        <td align="left" width="8%">YOB :</td><td><div class="statistics_line"> <?php echo $m_year; ?></div></td>
                        <td align="left" width="14%">WEIGHT :</td><td><div class="statistics_line"> <?php echo $m_weight; ?></div></td>
                        <td align="left" width="12%">HEIGHT :</td><td><div class="statistics_line"> <?php echo $m_height; ?></div></td>
                        <td align="left" width="25%">BLOOD GROUP :</td><td><div class="statistics_line"> <?php echo $m_blood_group; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>   
              
                <table width="100%" border="0">
                    <tr>
                      <?php

   $m_adhar_no = $parent_info[0]->m_adhar_no ?: '&nbsp;';
?>
                        <td align="left" width="27%">AADHAR CARD NO.: </td><td><div class="statistics_line"><?php echo $m_adhar_no; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="25%">FATHER'S NAME :</td><td><div class="statistics_line"> <?php echo $parent_info[0]->father_name; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php
                        if ($parent_info[0]->f_dob != '') {
                            $f_year = date('Y', strtotime($parent_info[0]->f_dob));
                        } else {
                            $f_year = '&nbsp;';
                        }                       
                        $f_blood_group = $parent_info[0]->f_blood_group ?: '&nbsp;';
                        $f_weight = $health_activity_data[0]->f_weight ?: '&nbsp;';
                        $f_height = $health_activity_data[0]->f_height ?: '&nbsp;';
                        $f_blood_group = $parent_info[0]->f_blood_group ?: '&nbsp;';
                        ?>
                        <td align="left" width="8%">YOB :</td><td><div class="statistics_line">  <?php echo $f_year; ?></div></td>
                        <td align="left" width="14%">WEIGHT :</td><td><div class="statistics_line">  <?php echo $f_weight; ?></div></td>
                        <td align="left" width="12%">HEIGHT :</td><td><div class="statistics_line">  <?php echo $f_height; ?></div></td>
                        <td align="left" width="25%">BLOOD GROUP :</td><td><div class="statistics_line">  <?php echo $f_blood_group; ?></div></td>
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
                        <td align="left" width="27%">AADHAR CARD NO.: </td><td><div class="statistics_line">  <?php echo $parent_adhar_no; ?></div></td>
                    </tr>
                <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php
                      
                        $family_income = $health_activity_data[0]->family_income ?: '&nbsp;';
                        ?>
                        <td align="left" width="37%">FAMILY MONTHLY INCOME :</td><td><div class="statistics_line">  <?php echo $family_income; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="15%">ADDRESS:</td><td><div class="statistics_line">  <?php echo $parent_info[0]->permant_add; ?></div></td>
                    </tr>
                <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php
                        if (($parent_info[0]->f_mobile == NULL || $parent_info[0]->f_mobile == '' || $parent_info[0]->f_mobile == 'NULL') && ($parent_info[0]->m_mobile == NULL || $parent_info[0]->m_mobile == '' || $parent_info[0]->m_mobile == 'NULL')) {
                            $f_mobile = '&nbsp;';
                        } elseif ($parent_info[0]->f_mobile <> NULL) {
                            $f_mobile = $parent_info[0]->f_mobile;
                        } elseif ($parent_info[0]->m_mobile <> NULL) {
                            $f_mobile = $parent_info[0]->m_mobile;
                        }

                        ?>
                        <td align="left" width="18%">PHONE NO.: </td><td><div class="statistics_line"> &nbsp; </div></td>
                        <td align="left" align="left" width="5%">(M): </td><td><div class="statistics_line">  <?php echo $f_mobile; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php
                        $cwsn = $health_activity_data[0]->cwsn ?: '&nbsp;';
                        ?>
                        <td align="left" width="24%">CWSM, SPECIFY :</td><td><div class="statistics_line">  <?php echo $cwsn; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</html>
<br>
<html>
<style>
     .second {
    background: url('https://sms.evolvu.in/public/health2_bg.jpg');
    background-size: cover; /* Ensure the image covers the entire area */
    background-repeat: no-repeat;
    background-position: center center; /* Ensure it's centered */
    object-fit: cover;
  }
  .second {
    height: 100%;
    background: url('https://sms.evolvu.in/public/health2_bg.jpg');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;
  }
    .tdalign{
        text-align: center;
    }
    .measures_comp{
        font-size: 12px;
    }
</style>

<?php
$parent_info = get_student_parent_info($student_id, $customClaims);
$health_activity_data = check_health_activity_data_exist_for_studentid($student_id);
// print_r($health_activity_data);
$class = get_class_section_of_student($student_id);
$class_array = explode(' ', $class);
// $class_name = $class_array[0];
$class_name = isset($class_array[0]) ? (int)$class_array[0] : 0;  //mahima
if ($class_name >= 1) {
    $student_id_array = array($class_name => $student_id);
    $temp_prev_stud_id = $student_id;
    $temp_student_id_array = array();
    for ($i = ($class_name - 1); $i >= 1; $i--) {
        $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
        $temp_student_id_array[$i] = $temp_prev_stud_id;
        $student_id_array = $student_id_array + $temp_student_id_array;
    }
    $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)), array_reverse(array_values($student_id_array)));
} else {
    $student_id_array_new = '';
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
            for ($j = 1; $j <= 5; $j++) {
                ${'bmi_' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'bmi_' . $j} = $health_activity_data[0]->bmi;
                        }
                    }
                }
                ?>
            <td class="tdalign"><?php echo (${'bmi_' . $j}); ?> </td>
       <?php } ?>
        </tr>        
        <tr>
            <td rowspan="2">Muscular Strength</td>
            <td>Core</td>
            <td><b>Partial Curl Up</b></td>
            <td class="measures_comp">Abdominal Muscular Endurance</td>
              <?php
for ($j = 1; $j <= 5; $j++) {
    ${'partial_curl_up_' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'partial_curl_up_' . $j} = $health_activity_data[0]->partial_curl_up;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'partial_curl_up_' . $j}; ?></td> 
        <?php } ?>    
        </tr>
        <tr>
            <td>Upper Body</td>
            <td><b>Flexed/Bent Arm Hang</b></td>
            <td class="measures_comp">Muscular Endurance/ Functional Strength</td>
    <?php for ($j = 1; $j <= 5; $j++) {
        ${'flex_bent_arm_hang' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != 0) {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'flex_bent_arm_hang' . $j} = $health_activity_data[0]->flex_bent_arm_hang;
                }
            }
        } ?>
            <td class="tdalign"><?php echo ${'flex_bent_arm_hang' . $j}; ?></td> 
        <?php } ?>   
        </tr>
        <tr>
            <td>Flexibility</td>
            <td></td>
            <td><b>Sit and Reach</b></td>
            <td class="measures_comp">Measure the flexibility of lower back and hamstring muscles</td>
               <?php
for ($j = 1; $j <= 5; $j++) {
    ${'sit_n_reach' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'sit_n_reach' . $j} = $health_activity_data[0]->sit_n_reach;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'sit_n_reach' . $j}; ?></td> 
        <?php } ?>  
        </tr>
        <tr>
            <td>Endurance</td>
            <td></td>
            <td><b>600 Mtr Run</b></td>
            <td class="measures_comp">Cardiovascular Fitness/ Cardiovascular Endurance</td>
               <?php
for ($j = 1; $j <= 5; $j++) {
    ${'600m_run' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'600m_run' . $j} = $health_activity_data[0]->{'600m_run'};
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'600m_run' . $j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td>Balance</td>
            <td>Static Balance</td>
            <td><b>Flamingo Balance Test</b></td>
            <td class="measures_comp">Ability to balance successfully on a single leg</td>
                  <?php
for ($j = 1; $j <= 5; $j++) {
    ${'flamingo_bel_test' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'flamingo_bel_test' . $j} = $health_activity_data[0]->flamingo_bel_test;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'flamingo_bel_test' . $j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td rowspan="5">Skill Components</td>
            <td>Ability</td>
            <td></td>
            <td><b>Shuttle Run</b></td>
            <td class="measures_comp">Test of speed and agility </td>
                    <?php
                    for ($j = 1; $j <= 5; $j++) {
                        ${'shuttle_run' . $j} = '';
                        if (isset($student_id_array_new[$j])) {
                            if ($student_id_array_new[$j] != 0) {
                                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                                if (isset($health_activity_data[0])) {
                                    ${'shuttle_run' . $j} = $health_activity_data[0]->shuttle_run;
                                }
                            }
                        }
                        ?>
            <td class="tdalign"><?php echo ${'shuttle_run' . $j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td>Speed</td>
            <td></td>
            <td><b>Sprint/ Dash</b></td>
            <td class="measures_comp">Determines acceleration and Speed</td>
            <?php for ($j = 1; $j <= 5; $j++) {
                ${'sprint_dash' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'sprint_dash' . $j} = $health_activity_data[0]->sprint_dash;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'sprint_dash' . $j}; ?></td> 
    <?php } ?> 
        </tr>
        <tr>
            <td>Power</td>
             <td></td>
            <td><b>Standing Vertical Jump</b></td>
            <td class="measures_comp">Measures the Leg Muscles Power</td>
            <?php for ($j = 1; $j <= 5; $j++) {
                ${'standing_vertical_jump' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'standing_vertical_jump' . $j} = $health_activity_data[0]->standing_vertical_jump;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'standing_vertical_jump' . $j}; ?></td> 
    <?php } ?> 
        </tr>
        <tr>
            <td>Coordination</td>
            <td></td>
            <td><b>Plate Tapping</b></td>
            <td class="measures_comp">Tests speed and coordination of limb movement</td>
            <?php for ($j = 1; $j <= 5; $j++) {
                ${'plate_tapping' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'plate_tapping' . $j} = $health_activity_data[0]->plate_tapping;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'plate_tapping' . $j}; ?></td> 
    <?php } ?> 

        </tr>
        <tr>
            <td></td>
            <td></td>
            <td><b>Alternative Hand Wall Toss Test</b></td>
            <td class="measures_comp">Measures hand eye coordination</td>
            <?php for ($j = 1; $j <= 5; $j++) {
                ${'alternative_handwall_toss' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'alternative_handwall_toss' . $j} = $health_activity_data[0]->alternative_handwall_toss;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'alternative_handwall_toss' . $j}; ?></td> 
    <?php } ?> 

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
            background-size: cover; /* Ensure the image covers the entire area */
            background-repeat: no-repeat;
            background-position: center center; /* Ensure it's centered */
            object-fit: cover;
        }
        .third {
            height: 100%;
            background: url('https://sms.evolvu.in/public/health2_bg.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center center;
        }
    
    .tdalign{
        text-align: center;
    }
</style>

<?php
$parent_info = get_student_parent_info($student_id, $customClaims);
// dd($parent_info);
$health_activity_data = check_health_activity_data_exist_for_studentid($student_id);
$class = get_class_section_of_student($student_id);
$class_array = explode(' ', $class);
// $class_name = $class_array[0];
$class_name = isset($class_array[0]) ? (int)$class_array[0] : 0;  //mahima
if ($class_name >= 1) {
    $student_id_array = array($class_name => $student_id);
    $temp_prev_stud_id = $student_id;
    $temp_student_id_array = array();
    for ($i = ($class_name - 1); $i >= 1; $i--) {
        $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
        $temp_student_id_array[$i] = $temp_prev_stud_id;
        $student_id_array = $student_id_array + $temp_student_id_array;
    }
    $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)), array_reverse(array_values($student_id_array)));
} else {
    $student_id_array_new = '';
}
?>
<div style="page-break-before:always" class="third">
<br><br><br>
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
        <?php for ($j = 1; $j <= 5; $j++) {
            ${'vision_re' . $j} = '';
            ${'vision_le' . $j} = '';
            $vision_combine = '';
            if (isset($student_id_array_new[$j])) {
                if ($student_id_array_new[$j] != 0) {
                    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if (isset($health_activity_data[0])) {
                        ${'vision_re' . $j} = $health_activity_data[0]->vision_re;
                        ${'vision_le' . $j} = $health_activity_data[0]->vision_le;
                        if (${'vision_re' . $j} == '' && ${'vision_le' . $j} == '')
                            $vision_combine = '';
                        else
                            $vision_combine = ${'vision_re' . $j} . '/' . ${'vision_le' . $j};
                    }
                }
            } ?>
            <td class="tdalign"><?php echo $vision_combine; ?></td> 
<?php } ?> 
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
<?php // } ?> 
        </tr>
-->
        <tr>
            <td>Ears</td>
            <td>Right/Left</td> 
    <?php for ($j = 1; $j <= 5; $j++) {
        ${'ears_right' . $j} = '';
        ${'ears_left' . $j} = '';
        $ear_combine = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != 0) {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'ears_right' . $j} = $health_activity_data[0]->ears_right;
                    ${'ears_left' . $j} = $health_activity_data[0]->ears_left;
                    if (${'ears_right' . $j} == '' && ${'ears_left' . $j} == '')
                        $ear_combine = '';
                    else
                        $ear_combine = ${'ears_right' . $j} . '/' . ${'ears_left' . $j};
                }
            }
        } ?>
            <td class="tdalign"><?php echo $ear_combine; ?></td> 
<?php } ?> 
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
<?php for ($j = 1; $j <= 5; $j++) {
    ${'teeth_caries' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'teeth_caries' . $j} = $health_activity_data[0]->teeth_caries;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'teeth_caries' . $j}; ?></td> 
<?php } ?>    
            
        </tr>
        <tr>
            <td>Tonsils</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'teeth_tonsils' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'teeth_tonsils' . $j} = $health_activity_data[0]->teeth_tonsils;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'teeth_tonsils' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Gums</td>
        <?php
        for ($j = 1; $j <= 5; $j++) {
            ${'teeth_gums' . $j} = '';
            if (isset($student_id_array_new[$j])) {
                if ($student_id_array_new[$j] != 0) {
                    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if (isset($health_activity_data[0])) {
                        ${'teeth_gums' . $j} = $health_activity_data[0]->teeth_gums;
                    }
                }
            }
            ?>
            <td class="tdalign"><?php echo ${'teeth_gums' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td rowspan="2"><b>General Body Measurements</b></td>
            <td>Height(cm)</td>
    <?php
    for ($j = 1; $j <= 5; $j++) {
        ${'height' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != 0) {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'height' . $j} = $health_activity_data[0]->height;
                }
            }
        }
        ?>
            <td class="tdalign"><?php echo ${'height' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Weight(kg)</td>
        <?php
        for ($j = 1; $j <= 5; $j++) {
            ${'weight' . $j} = '';
            if (isset($student_id_array_new[$j])) {
                if ($student_id_array_new[$j] != 0) {
                    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if (isset($health_activity_data[0])) {
                        ${'weight' . $j} = $health_activity_data[0]->weight;
                    }
                }
            }
            ?>
            <td class="tdalign"><?php echo ${'weight' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td rowspan="2">Circumferences</td>
            <td>Hip(inches)</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'hip' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'hip' . $j} = $health_activity_data[0]->hip;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'hip' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Waist(inches)</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'waist' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'waist' . $j} = $health_activity_data[0]->waist;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'waist' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td rowspan="2">Health Status</td>
            <td>Pulse</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'pulse' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'pulse' . $j} = $health_activity_data[0]->pulse;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'pulse' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Blood Pressure</td>
<?php
for ($j = 1; $j <= 5; $j++) {
    ${'bp' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'bp' . $j} = $health_activity_data[0]->bp;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'bp' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Posture Evaluation</td>
            <td><b>If Any:</b><br> Head Forward/Sunken Chest/Round Shoulder/ Kyphisis/Lordosis/Abdominal Ptosis/ Body Lean/ Tilted Head/ Shoulders Uneven/ Scoliosis/ Flat Feet/ Knock Knees/ Bow Legs</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'posture_evaluation' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'posture_evaluation' . $j} = $health_activity_data[0]->posture_evaluation;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'posture_evaluation' . $j}; ?></td> 
<?php } ?>
        </tr>
        <tr>
            <td rowspan="6">Sporting Activities</td>
            <td><b><u>Strand 1</u></b><br>1. Athlethics/ Swimming<br>2. Team Game<br>3. Individual Game<br>4. Adventure Game</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'strd1' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'strd1' . $j} = $health_activity_data[0]->strd1;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'strd1' . $j}; ?></td> 
<?php } ?>
        </tr>
        <tr>
            <td><b><u>Strand 2:</u><br> Health and Fitness</b><br>(Mass PT, Yoga, Dance, Calisthenics, Jogging, Cross Country Run, Working Outs using weights/ gym equipment, Tai Chi etc).</td>
<?php
for ($j = 1; $j <= 5; $j++) {
    ${'strd2_health_fitness' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'strd2_health_fitness' . $j} = $health_activity_data[0]->strd2_health_fitness;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'strd2_health_fitness' . $j}; ?></td> 
<?php } ?>
        </tr>
        <tr>
            <td><b><u>Strand 3:</u><br> SEWA</b></td>
<?php
for ($j = 1; $j <= 5; $j++) {
    ${'strd3_sewa' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'strd3_sewa' . $j} = $health_activity_data[0]->strd3_sewa;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'strd3_sewa' . $j}; ?></td> 
<?php } ?>
        </tr>
    </table>
</div>
</html>
    @elseif ($classname >= 6 && $classname <= 12)
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
        height:100%;
      }

        

    </style>
 <?php
$parent_info = get_student_parent_info($student_id, $customClaims);
//  dd($parent_info);
$health_activity_data = check_health_activity_data_exist_for_studentid($student_id);
$class = get_class_section_of_student($student_id);
// dd($class);
$class_array = explode(' ', $class);
// $class_name = $class_array[0];
$class_name = isset($class_array[0]) ? (int)$class_array[0] : 0;  //mahima
if ($class_name >= 1) {
    $student_id_array = array($class_name => $student_id);
    // dd($student_id_array);
    $temp_prev_stud_id = $student_id;
    $temp_student_id_array = array();
    for ($i = ($class_name - 1); $i >= 1; $i--) {
        $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
        // dd($temp_prev_stud_id);
        $temp_student_id_array[$i] = $temp_prev_stud_id;
        $student_id_array = $student_id_array + $temp_student_id_array;
    }
    $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)), array_reverse(array_values($student_id_array)));
    // dd($student_id_array_new);
} else {
    $student_id_array_new = '';
}
?>
<div class="first" >
    <br><br><br><br>
    <table border="0" style="width:80%;margin-top:20%" align="center">
        
        <tr>
            <td>
                <table width="100%" border="0" style="">
                    <tr>
                        <td align="left" width="48%">Aadhar Card No. of Student (optional) : </td><td><div class="statistics_line"><?php echo $parent_info[0]->stu_aadhaar_no == null ? '&nbsp;' : $parent_info[0]->stu_aadhaar_no; ?></div></td>
                    </tr>
                </table>
                <table width="100%" border="0" style="">
                    <tr>
                        <td align="left" width="10%">NAME :</td><td><div class="statistics_line"><?php echo $parent_info[0]->first_name . ' ' . $parent_info[0]->mid_name . ' ' . $parent_info[0]->last_name; ?></div></td>
                    </tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="25%">ADMISSION DATE :</td><td><div class="statistics_line"><?php echo date_format(date_create($parent_info[0]->admission_date), 'd-m-Y'); ?></div></td>
                        <td align="left" width="25%">DATE OF BIRTH : </td><td><div class="statistics_line"><?php echo date_format(date_create($parent_info[0]->dob), 'd-m-Y'); ?></div></td>    
                    </tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="10%">M F T :</td><td><div class="statistics_line"><?php echo $parent_info[0]->gender; ?></div></td>
                        <td align="left" width="25%">BLOOD GROUP :</td><td><div class="statistics_line"><?php echo $parent_info[0]->blood_group; ?></div></td>
                    </tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="25%">MOTHER'S NAME :</td><td><div class="statistics_line"> <?php echo $parent_info[0]->mother_name; ?></div></td>  
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php
                        if ($parent_info[0]->m_dob != '') {
                            $m_year = date('Y', strtotime($parent_info[0]->m_dob));
                        } else {
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
                        <td align="left" width="8%">YOB :</td><td><div class="statistics_line"> <?php echo $m_year; ?></div></td>
                        <td align="left" width="14%">WEIGHT :</td><td><div class="statistics_line"> <?php echo $m_weight; ?></div></td>
                        <td align="left" width="12%">HEIGHT :</td><td><div class="statistics_line"> <?php echo $m_height; ?></div></td>
                        <td align="left" width="25%">BLOOD GROUP :</td><td><div class="statistics_line"> <?php echo $m_blood_group; ?></div></td>
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
                        <td align="left" width="27%">AADHAR CARD NO.: </td><td><div class="statistics_line"><?php echo $m_adhar_no; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="25%">FATHER'S NAME :</td><td><div class="statistics_line"> <?php echo $parent_info[0]->father_name; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php
                        if ($parent_info[0]->f_dob != '') {
                            $f_year = date('Y', strtotime($parent_info[0]->f_dob));
                        } else {
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
                        // $space='&nbsp;';
                        $f_blood_group = $parent_info[0]->f_blood_group ?: '&nbsp;';
                        $f_weight = $health_activity_data[0]->f_weight ?: '&nbsp;';
                        $f_height = $health_activity_data[0]->f_height ?: '&nbsp;';
                        $f_blood_group = $parent_info[0]->f_blood_group ?: '&nbsp;';
                        ?>
                        <td align="left" width="8%">YOB :</td><td><div class="statistics_line">  <?php echo $f_year; ?></div></td>
                        <td align="left" width="14%">WEIGHT :</td><td><div class="statistics_line">  <?php echo $f_weight; ?></div></td>
                        <td align="left" width="12%">HEIGHT :</td><td><div class="statistics_line">  <?php echo $f_height; ?></div></td>
                        <td align="left" width="25%">BLOOD GROUP :</td><td><div class="statistics_line">  <?php echo $f_blood_group; ?></div></td>
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
                        <td align="left" width="27%">AADHAR CARD NO.: </td><td><div class="statistics_line">  <?php echo $parent_adhar_no; ?></div></td>
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
                        $family_income = $health_activity_data[0]->family_income ?: '&nbsp;';
                        ?>
                        <td align="left" width="37%">FAMILY MONTHLY INCOME :</td><td><div class="statistics_line">  <?php echo $family_income; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <td align="left" width="15%">ADDRESS:</td><td><div class="statistics_line">  <?php echo $parent_info[0]->permant_add; ?></div></td>
                    </tr>
                <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php
                        if (($parent_info[0]->f_mobile == NULL || $parent_info[0]->f_mobile == '' || $parent_info[0]->f_mobile == 'NULL') && ($parent_info[0]->m_mobile == NULL || $parent_info[0]->m_mobile == '' || $parent_info[0]->m_mobile == 'NULL')) {
                            $f_mobile = '&nbsp;';
                        } elseif ($parent_info[0]->f_mobile <> NULL) {
                            $f_mobile = $parent_info[0]->f_mobile;
                        } elseif ($parent_info[0]->m_mobile <> NULL) {
                            $f_mobile = $parent_info[0]->m_mobile;
                        }

                        ?>
                        <td align="left" width="18%">PHONE NO.: </td><td><div class="statistics_line"> &nbsp; </div></td>
                        <td align="left" align="left" width="5%">(M): </td><td><div class="statistics_line">  <?php echo $f_mobile; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>
                <table width="100%" border="0">
                    <tr>
                        <?php
                        $cwsn = $health_activity_data[0]->cwsn ?: '&nbsp;';
                        ?>
                        <td align="left" width="24%">CWSM, SPECIFY :</td><td><div class="statistics_line">  <?php echo $cwsn; ?></div></td>
                    </tr>
                    <tr></tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</html>
<br>
<html>
<style>
     .second {
    background: url('https://sms.evolvu.in/public/health2_bg.jpg');
    background-size: cover; /* Ensure the image covers the entire area */
    background-repeat: no-repeat;
    background-position: center center; /* Ensure it's centered */
    object-fit: cover;
}
.second {
    height: 100%;
    background: url('https://sms.evolvu.in/public/health2_bg.jpg');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;
}
    .tdalign{
        text-align: center;
    }
    .measures_comp{
        font-size: 12px;
    }
</style>

<?php
$parent_info = get_student_parent_info($student_id, $customClaims);
$health_activity_data = check_health_activity_data_exist_for_studentid($student_id);
$class = get_class_section_of_student($student_id);
$class_array = explode(' ', $class);
// $class_name = $class_array[0];
$class_name = isset($class_array[0]) ? (int)$class_array[0] : 0;  //mahima
if ($class_name >= 1) {
    $student_id_array = array($class_name => $student_id);
    $temp_prev_stud_id = $student_id;
    $temp_student_id_array = array();
    for ($i = ($class_name - 1); $i >= 1; $i--) {
        $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
        $temp_student_id_array[$i] = $temp_prev_stud_id;
        $student_id_array = $student_id_array + $temp_student_id_array;
    }
    $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)), array_reverse(array_values($student_id_array)));
} else {
    $student_id_array_new = '';
}
if ($class_name >= 6) {
    ?>
    {{-- Mahima  --}}
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
            for ($j = 1; $j <= 5; $j++) {
                ${'bmi_' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'bmi_' . $j} = $health_activity_data[0]->bmi;
                        }
                    }
                }
                ?>
            <td class="tdalign"><?php echo (${'bmi_' . $j}); ?> </td>
       <?php } ?>
        </tr>        
        <tr>
            <td rowspan="2">Muscular Strength</td>
            <td>Core</td>
            <td><b>Partial Curl Up</b></td>
            <td class="measures_comp">Abdominal Muscular Endurance</td>
              <?php
for ($j = 1; $j <= 5; $j++) {
    ${'partial_curl_up_' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'partial_curl_up_' . $j} = $health_activity_data[0]->partial_curl_up;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'partial_curl_up_' . $j}; ?></td> 
        <?php } ?>    
        </tr>
        <tr>
            <td>Upper Body</td>
            <td><b>Flexed/Bent Arm Hang</b></td>
            <td class="measures_comp">Muscular Endurance/ Functional Strength</td>
    <?php for ($j = 1; $j <= 5; $j++) {
        ${'flex_bent_arm_hang' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != 0) {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'flex_bent_arm_hang' . $j} = $health_activity_data[0]->flex_bent_arm_hang;
                }
            }
        } ?>
            <td class="tdalign"><?php echo ${'flex_bent_arm_hang' . $j}; ?></td> 
        <?php } ?>   
        </tr>
        <tr>
            <td>Flexibility</td>
            <td></td>
            <td><b>Sit and Reach</b></td>
            <td class="measures_comp">Measure the flexibility of lower back and hamstring muscles</td>
               <?php
for ($j = 1; $j <= 5; $j++) {
    ${'sit_n_reach' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'sit_n_reach' . $j} = $health_activity_data[0]->sit_n_reach;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'sit_n_reach' . $j}; ?></td> 
        <?php } ?>  
        </tr>
        <tr>
            <td>Endurance</td>
            <td></td>
            <td><b>600 Mtr Run</b></td>
            <td class="measures_comp">Cardiovascular Fitness/ Cardiovascular Endurance</td>
               <?php
for ($j = 1; $j <= 5; $j++) {
    ${'600m_run' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'600m_run' . $j} = $health_activity_data[0]->{'600m_run'};
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'600m_run' . $j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td>Balance</td>
            <td>Static Balance</td>
            <td><b>Flamingo Balance Test</b></td>
            <td class="measures_comp">Ability to balance successfully on a single leg</td>
                  <?php
for ($j = 1; $j <= 5; $j++) {
    ${'flamingo_bel_test' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'flamingo_bel_test' . $j} = $health_activity_data[0]->flamingo_bel_test;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'flamingo_bel_test' . $j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td rowspan="5">Skill Components</td>
            <td>Ability</td>
            <td></td>
            <td><b>Shuttle Run</b></td>
            <td class="measures_comp">Test of speed and agility </td>
                    <?php
                    for ($j = 1; $j <= 5; $j++) {
                        ${'shuttle_run' . $j} = '';
                        if (isset($student_id_array_new[$j])) {
                            if ($student_id_array_new[$j] != 0) {
                                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                                if (isset($health_activity_data[0])) {
                                    ${'shuttle_run' . $j} = $health_activity_data[0]->shuttle_run;
                                }
                            }
                        }
                        ?>
            <td class="tdalign"><?php echo ${'shuttle_run' . $j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td>Speed</td>
            <td></td>
            <td><b>Sprint/ Dash</b></td>
            <td class="measures_comp">Determines acceleration and Speed</td>
            <?php for ($j = 1; $j <= 5; $j++) {
                ${'sprint_dash' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'sprint_dash' . $j} = $health_activity_data[0]->sprint_dash;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'sprint_dash' . $j}; ?></td> 
    <?php } ?> 
        </tr>
        <tr>
            <td>Power</td>
             <td></td>
            <td><b>Standing Vertical Jump</b></td>
            <td class="measures_comp">Measures the Leg Muscles Power</td>
            <?php for ($j = 1; $j <= 5; $j++) {
                ${'standing_vertical_jump' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'standing_vertical_jump' . $j} = $health_activity_data[0]->standing_vertical_jump;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'standing_vertical_jump' . $j}; ?></td> 
    <?php } ?> 
        </tr>
        <tr>
            <td>Coordination</td>
            <td></td>
            <td><b>Plate Tapping</b></td>
            <td class="measures_comp">Tests speed and coordination of limb movement</td>
            <?php for ($j = 1; $j <= 5; $j++) {
                ${'plate_tapping' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'plate_tapping' . $j} = $health_activity_data[0]->plate_tapping;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'plate_tapping' . $j}; ?></td> 
    <?php } ?> 

        </tr>
        <tr>
            <td></td>
            <td></td>
            <td><b>Alternative Hand Wall Toss Test</b></td>
            <td class="measures_comp">Measures hand eye coordination</td>
            <?php for ($j = 1; $j <= 5; $j++) {
                ${'alternative_handwall_toss' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'alternative_handwall_toss' . $j} = $health_activity_data[0]->alternative_handwall_toss;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'alternative_handwall_toss' . $j}; ?></td> 
    <?php } ?> 

        </tr>
    </table>
</div>
<div style="page-break-before:always" class="second">
<br><br><br>
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
            for ($j = 6; $j <= 12; $j++) {
                ${'bmi_' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'bmi_' . $j} = $health_activity_data[0]->bmi;
                        }
                    }
                }
                ?>
            <td class="tdalign"><?php echo ${'bmi_' . $j}; ?></td> 
            <?php } ?>    
        </tr>    
        <tr>
            <td rowspan="2">Muscular Strength</td>
            <td>Core</td>
            <td><b>Partial Curl Up</b></td>
            <td class="measures_comp">Abdominal Muscular Endurance</td>
              <?php
    for ($j = 6; $j <= 12; $j++) {
        ${'partial_curl_up_' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != 0) {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'partial_curl_up_' . $j} = $health_activity_data[0]->partial_curl_up;
                }
            }
        }
        ?>
            <td class="tdalign"><?php echo ${'partial_curl_up_' . $j}; ?></td> 
            <?php } ?>    
        </tr>
        <tr>
            <td>Upper Body</td>
            <td><b>Flexed/Bent Arm Hang</b></td>
            <td class="measures_comp">Muscular Endurance/ Functional Strength</td>

                <?php
                for ($j = 6; $j <= 12; $j++) {
                    ${'flex_bent_arm_hang' . $j} = '';
                    if (isset($student_id_array_new[$j])) {
                        if ($student_id_array_new[$j] != 0) {
                            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                            if (isset($health_activity_data[0])) {
                                ${'flex_bent_arm_hang' . $j} = $health_activity_data[0]->flex_bent_arm_hang;
                            }
                        }
                    }
                    ?>
            <td class="tdalign"><?php echo ${'flex_bent_arm_hang' . $j}; ?></td> 
            <?php } ?>   
        </tr>
        <tr>
            <td>Flexibility</td>
            <td></td>
            <td><b>Sit and Reach</b></td>
            <td class="measures_comp">Measure the flexibility of lower back and hamstring muscles</td>
               <?php
    for ($j = 6; $j <= 12; $j++) {
        ${'sit_n_reach' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != 0) {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'sit_n_reach' . $j} = $health_activity_data[0]->sit_n_reach;
                }
            }
        }
        ?>
            <td class="tdalign"><?php echo ${'sit_n_reach' . $j}; ?></td> 
            <?php } ?>  
        </tr>
        <tr>
            <td>Endurance</td>
            <td></td>
            <td><b>600 Mtr Run</b></td>
            <td class="measures_comp">Cardiovascular Fitness/ Cardiovascular Endurance</td>
               <?php
    for ($j = 6; $j <= 12; $j++) {
        ${'600m_run' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != 0) {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'600m_run' . $j} = $health_activity_data[0]->{'600m_run'};
                }
            }
        }
        ?>
            <td class="tdalign"><?php echo ${'600m_run' . $j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td>Balance</td>
            <td>Static Balance</td>
            <td><b>Flamingo Balance Test</b></td>
            <td class="measures_comp">Ability to balance successfully on a single leg</td>
                  <?php
    for ($j = 6; $j <= 12; $j++) {
        ${'flamingo_bel_test' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != 0) {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'flamingo_bel_test' . $j} = $health_activity_data[0]->flamingo_bel_test;
                }
            }
        }
        ?>
            <td class="tdalign"><?php echo ${'flamingo_bel_test' . $j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td rowspan="5">Skill Components</td>
            <td>Ability</td>
            <td></td>
            <td><b>Shuttle Run</b></td>
            <td class="measures_comp">Test of speed and agility </td>
                    <?php
                    for ($j = 6; $j <= 12; $j++) {
                        ${'shuttle_run' . $j} = '';
                        if (isset($student_id_array_new[$j])) {
                            if ($student_id_array_new[$j] != 0) {
                                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                                if (isset($health_activity_data[0])) {
                                    ${'shuttle_run' . $j} = $health_activity_data[0]->shuttle_run;
                                }
                            }
                        }
                        ?>
            <td class="tdalign"><?php echo ${'shuttle_run' . $j}; ?></td> 
            <?php } ?> 
        </tr>
        <tr>
            <td>Speed</td>
            <td></td>
            <td><b>Sprint/ Dash</b></td>
            <td class="measures_comp">Determines acceleration and Speed</td>
            <?php for ($j = 6; $j <= 12; $j++) {
                ${'sprint_dash' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'sprint_dash' . $j} = $health_activity_data[0]->sprint_dash;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'sprint_dash' . $j}; ?></td> 
    <?php } ?> 
        </tr>
        <tr>
            <td>Power</td>
             <td></td>
            <td><b>Standing Vertical Jump</b></td>
            <td class="measures_comp">Measures the Leg Muscles Power</td>
            <?php for ($j = 6; $j <= 12; $j++) {
                ${'standing_vertical_jump' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'standing_vertical_jump' . $j} = $health_activity_data[0]->standing_vertical_jump;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'standing_vertical_jump' . $j}; ?></td> 
    <?php } ?> 
        </tr>
        <tr>
            <td>Coordination</td>
            <td></td>
            <td><b>Plate Tapping</b></td>
            <td class="measures_comp">Tests speed and coordination of limb movement</td>
            <?php for ($j = 6; $j <= 12; $j++) {
                ${'plate_tapping' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'plate_tapping' . $j} = $health_activity_data[0]->plate_tapping;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'plate_tapping' . $j}; ?></td> 
    <?php } ?> 

        </tr>
        <tr>
            <td></td>
            <td></td>
            <td><b>Alternative Hand Wall Toss Test</b></td>
            <td class="measures_comp">Measures hand eye coordination</td>
            <?php for ($j = 6; $j <= 12; $j++) {
                ${'alternative_handwall_toss' . $j} = '';
                if (isset($student_id_array_new[$j])) {
                    if ($student_id_array_new[$j] != 0) {
                        $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                        if (isset($health_activity_data[0])) {
                            ${'alternative_handwall_toss' . $j} = $health_activity_data[0]->alternative_handwall_toss;
                        }
                    }
                } ?>
                <td class="tdalign"><?php echo ${'alternative_handwall_toss' . $j}; ?></td> 
    <?php } ?> 

        </tr>
    </table>
</div>
    <?php } ?>
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
            background-size: cover; /* Ensure the image covers the entire area */
            background-repeat: no-repeat;
            background-position: center center; /* Ensure it's centered */
            object-fit: cover;
        }
        .third {
            height: 100%;
            background: url('https://sms.evolvu.in/public/health2_bg.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center center;
        }
    
    .tdalign{
        text-align: center;
    }
</style>

<?php
$parent_info = get_student_parent_info($student_id, $customClaims);
$health_activity_data = check_health_activity_data_exist_for_studentid($student_id);
$class = get_class_section_of_student($student_id);
$class_array = explode(' ', $class);
// $class_name = $class_array[0];
$class_name = isset($class_array[0]) ? (int)$class_array[0] : 0; //mahima
if ($class_name >= 1) {
    $student_id_array = array($class_name => $student_id);
    $temp_prev_stud_id = $student_id;
    $temp_student_id_array = array();
    for ($i = ($class_name - 1); $i >= 1; $i--) {
        $temp_prev_stud_id = get_previous_student_id($temp_prev_stud_id);
        $temp_student_id_array[$i] = $temp_prev_stud_id;
        $student_id_array = $student_id_array + $temp_student_id_array;
    }
    $student_id_array_new = array_combine(array_reverse(array_keys($student_id_array)), array_reverse(array_values($student_id_array)));
} else {
    $student_id_array_new = '';
}
if ($class_name >= 6) {
    ?>

    {{-- chnage inside this --}}
    <div style="page-break-before:always" class="third">
<br><br><br>
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
        <?php for ($j = 1; $j <= 5; $j++) {
            ${'vision_re' . $j} = '';
            ${'vision_le' . $j} = '';
            $vision_combine = '';
            if (isset($student_id_array_new[$j])) {
                if ($student_id_array_new[$j] != 0) {
                    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if (isset($health_activity_data[0])) {
                        ${'vision_re' . $j} = $health_activity_data[0]->vision_re;
                        ${'vision_le' . $j} = $health_activity_data[0]->vision_le;
                        if (${'vision_re' . $j} == '' && ${'vision_le' . $j} == '')
                            $vision_combine = '';
                        else
                            $vision_combine = ${'vision_re' . $j} . '/' . ${'vision_le' . $j};
                    }
                }
            } ?>
            <td class="tdalign"><?php echo $vision_combine; ?></td> 
<?php } ?> 
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
<?php // } ?> 
        </tr>
-->
        <tr>
            <td>Ears</td>
            <td>Right/Left</td> 
    <?php for ($j = 1; $j <= 5; $j++) {
        ${'ears_right' . $j} = '';
        ${'ears_left' . $j} = '';
        $ear_combine = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != 0) {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'ears_right' . $j} = $health_activity_data[0]->ears_right;
                    ${'ears_left' . $j} = $health_activity_data[0]->ears_left;
                    if (${'ears_right' . $j} == '' && ${'ears_left' . $j} == '')
                        $ear_combine = '';
                    else
                        $ear_combine = ${'ears_right' . $j} . '/' . ${'ears_left' . $j};
                }
            }
        } ?>
            <td class="tdalign"><?php echo $ear_combine; ?></td> 
<?php } ?> 
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
<?php for ($j = 1; $j <= 5; $j++) {
    ${'teeth_caries' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'teeth_caries' . $j} = $health_activity_data[0]->teeth_caries;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'teeth_caries' . $j}; ?></td> 
<?php } ?>    
            
        </tr>
        <tr>
            <td>Tonsils</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'teeth_tonsils' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'teeth_tonsils' . $j} = $health_activity_data[0]->teeth_tonsils;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'teeth_tonsils' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Gums</td>
        <?php
        for ($j = 1; $j <= 5; $j++) {
            ${'teeth_gums' . $j} = '';
            if (isset($student_id_array_new[$j])) {
                if ($student_id_array_new[$j] != 0) {
                    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if (isset($health_activity_data[0])) {
                        ${'teeth_gums' . $j} = $health_activity_data[0]->teeth_gums;
                    }
                }
            }
            ?>
            <td class="tdalign"><?php echo ${'teeth_gums' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td rowspan="2"><b>General Body Measurements</b></td>
            <td>Height(cm)</td>
    <?php
    for ($j = 1; $j <= 5; $j++) {
        ${'height' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != 0) {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'height' . $j} = $health_activity_data[0]->height;
                }
            }
        }
        ?>
            <td class="tdalign"><?php echo ${'height' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Weight(kg)</td>
        <?php
        for ($j = 1; $j <= 5; $j++) {
            ${'weight' . $j} = '';
            if (isset($student_id_array_new[$j])) {
                if ($student_id_array_new[$j] != 0) {
                    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if (isset($health_activity_data[0])) {
                        ${'weight' . $j} = $health_activity_data[0]->weight;
                    }
                }
            }
            ?>
            <td class="tdalign"><?php echo ${'weight' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td rowspan="2">Circumferences</td>
            <td>Hip(inches)</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'hip' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'hip' . $j} = $health_activity_data[0]->hip;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'hip' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Waist(inches)</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'waist' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'waist' . $j} = $health_activity_data[0]->waist;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'waist' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td rowspan="2">Health Status</td>
            <td>Pulse</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'pulse' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'pulse' . $j} = $health_activity_data[0]->pulse;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'pulse' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Blood Pressure</td>
<?php
for ($j = 1; $j <= 5; $j++) {
    ${'bp' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'bp' . $j} = $health_activity_data[0]->bp;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'bp' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Posture Evaluation</td>
            <td><b>If Any:</b><br> Head Forward/Sunken Chest/Round Shoulder/ Kyphisis/Lordosis/Abdominal Ptosis/ Body Lean/ Tilted Head/ Shoulders Uneven/ Scoliosis/ Flat Feet/ Knock Knees/ Bow Legs</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'posture_evaluation' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'posture_evaluation' . $j} = $health_activity_data[0]->posture_evaluation;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'posture_evaluation' . $j}; ?></td> 
<?php } ?>
        </tr>
        <tr>
            <td rowspan="6">Sporting Activities</td>
            <td><b><u>Strand 1</u></b><br>1. Athlethics/ Swimming<br>2. Team Game<br>3. Individual Game<br>4. Adventure Game</td>
<?php for ($j = 1; $j <= 5; $j++) {
    ${'strd1' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'strd1' . $j} = $health_activity_data[0]->strd1;
            }
        }
    } ?>
            <td class="tdalign"><?php echo ${'strd1' . $j}; ?></td> 
<?php } ?>
        </tr>
        <tr>
            <td><b><u>Strand 2:</u><br> Health and Fitness</b><br>(Mass PT, Yoga, Dance, Calisthenics, Jogging, Cross Country Run, Working Outs using weights/ gym equipment, Tai Chi etc).</td>
<?php
for ($j = 1; $j <= 5; $j++) {
    ${'strd2_health_fitness' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'strd2_health_fitness' . $j} = $health_activity_data[0]->strd2_health_fitness;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'strd2_health_fitness' . $j}; ?></td> 
<?php } ?>
        </tr>
        <tr>
            <td><b><u>Strand 3:</u><br> SEWA</b></td>
<?php
for ($j = 1; $j <= 5; $j++) {
    ${'strd3_sewa' . $j} = '';
    if (isset($student_id_array_new[$j])) {
        if ($student_id_array_new[$j] != 0) {
            $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
            if (isset($health_activity_data[0])) {
                ${'strd3_sewa' . $j} = $health_activity_data[0]->strd3_sewa;
            }
        }
    }
    ?>
            <td class="tdalign"><?php echo ${'strd3_sewa' . $j}; ?></td> 
<?php } ?>
        </tr>
    </table>
</div>
<div style="page-break-before:always" class="third">
    <br><br><br>
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
    for ($j = 6; $j <= 12; $j++) {
        ${'vision_re' . $j} = '';
        ${'vision_le' . $j} = '';
        $vision_combine = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'vision_re' . $j} = $health_activity_data[0]->vision_re;
                    ${'vision_le' . $j} = $health_activity_data[0]->vision_le;
                    $vision_combine = ${'vision_re' . $j} . '/' . ${'vision_le' . $j};
                }
            }
        }
        ?>
            <td class="tdalign"><?php echo $vision_combine; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Ears</td>
            <td>Right/Left</td> 
    <?php for ($j = 6; $j <= 12; $j++) {
        ${'ears_right' . $j} = '';
        ${'ears_left' . $j} = '';
        $ear_combine = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'ears_right' . $j} = $health_activity_data[0]->ears_right;
                    ${'ears_left' . $j} = $health_activity_data[0]->ears_left;
                    $ear_combine = ${'ears_right' . $j} . '/' . ${'ears_left' . $j};
                }
            }
        } ?>
            <td class="tdalign"><?php echo $ear_combine; ?></td> 
<?php } ?> 
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
<?php for ($j = 6; $j <= 12; $j++) {
        ${'teeth_caries' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'teeth_caries' . $j} = $health_activity_data[0]->teeth_caries;
                }
            }
        } ?>
            <td class="tdalign"><?php echo ${'teeth_caries' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Tonsils</td>
<?php for ($j = 6; $j <= 12; $j++) {
        ${'teeth_tonsils' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'teeth_tonsils' . $j} = $health_activity_data[0]->teeth_tonsils;
                }
            }
        } ?>
            <td class="tdalign"><?php echo ${'teeth_tonsils' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Gums</td>
        <?php
        for ($j = 6; $j <= 12; $j++) {
            ${'teeth_gums' . $j} = '';
            if (isset($student_id_array_new[$j])) {
                if ($student_id_array_new[$j] != '') {
                    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if (isset($health_activity_data[0])) {
                        ${'teeth_gums' . $j} = $health_activity_data[0]->teeth_gums;
                    }
                }
            }
            ?>
            <td class="tdalign"><?php echo ${'teeth_gums' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td rowspan="2"><b>General Body Measurements</b></td>
            <td>Height(cm)</td>
<?php
    for ($j = 6; $j <= 12; $j++) {
        ${'height' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'height' . $j} = $health_activity_data[0]->height;
                }
            }
        }
        ?>
            <td class="tdalign"><?php echo ${'height' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Weight(kg)</td>
        <?php
        for ($j = 6; $j <= 12; $j++) {
            ${'weight' . $j} = '';
            if (isset($student_id_array_new[$j])) {
                if ($student_id_array_new[$j] != '') {
                    $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                    if (isset($health_activity_data[0])) {
                        ${'weight' . $j} = $health_activity_data[0]->weight;
                    }
                }
            }
            ?>
            <td class="tdalign"><?php echo ${'weight' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td rowspan="2">Circumferences</td>
            <td>Hip(inches)</td>
<?php for ($j = 6; $j <= 12; $j++) {
        ${'hip' . $j} = '';
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'hip' . $j} = $health_activity_data[0]->hip;
                }
            }
        } ?>
            <td class="tdalign"><?php echo ${'hip' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Waist(inches)</td>
<?php for ($j = 6; $j <= 12; $j++) {
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'waist' . $j} = $health_activity_data[0]->waist;
                } else {
                    ${'waist' . $j} = '';
                }
            } else {
                ${'waist' . $j} = '';
            }
        } else {
            ${'waist' . $j} = '';
        } ?>
            <td class="tdalign"><?php echo ${'waist' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td rowspan="2">Health Status</td>
            <td>Pulse</td>
<?php for ($j = 6; $j <= 12; $j++) {
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'pulse' . $j} = $health_activity_data[0]->pulse;
                } else {
                    ${'pulse' . $j} = '';
                }
            } else {
                ${'pulse' . $j} = '';
            }
        } else {
            ${'pulse' . $j} = '';
        } ?>
            <td class="tdalign"><?php echo ${'pulse' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Blood Pressure</td>
<?php for ($j = 6; $j <= 12; $j++) {
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'bp' . $j} = $health_activity_data[0]->bp;
                } else {
                    ${'bp' . $j} = '';
                }
            } else {
                ${'bp' . $j} = '';
            }
        } else {
            ${'bp' . $j} = '';
        } ?>
            <td class="tdalign"><?php echo ${'bp' . $j}; ?></td> 
<?php } ?> 
        </tr>
        <tr>
            <td>Posture Evaluation</td>
            <td><b>If Any:</b><br> Head Forward/Sunken Chest/Round Shoulder/ Kyphisis/Lordosis/Abdominal Ptosis/ Body Lean/ Tilted Head/ Shoulders Uneven/ Scoliosis/ Flat Feet/ Knock Knees/ Bow Legs</td>
<?php for ($j = 6; $j <= 12; $j++) {
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'posture_evaluation' . $j} = $health_activity_data[0]->posture_evaluation;
                } else {
                    ${'posture_evaluation' . $j} = '';
                }
            } else {
                ${'posture_evaluation' . $j} = '';
            }
        } else {
            ${'posture_evaluation' . $j} = '';
        } ?>
            <td class="tdalign"><?php echo ${'posture_evaluation' . $j}; ?></td> 
<?php } ?>
        </tr>
        <tr>
            <td rowspan="6">Sporting Activities</td>
            <td><b><u>Strand 1</u></b><br>1. Athlethics/ Swimming<br>2. Team Game<br>3. Individual Game<br>4. Adventure Game</td>
<?php for ($j = 6; $j <= 12; $j++) {
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'strd1' . $j} = $health_activity_data[0]->strd1;
                } else {
                    ${'strd1' . $j} = '';
                }
            } else {
                ${'strd1' . $j} = '';
            }
        } else {
            ${'strd1' . $j} = '';
        } ?>
            <td class="tdalign"><?php echo ${'strd1' . $j}; ?></td> 
<?php } ?>
        </tr>
        <tr>
            <td><b><u>Strand 2:</u><br> Health and Fitness</b><br>(Mass PT, Yoga, Dance, Calisthenics, Jogging, Cross Country Run, Working Outs using weights/ gym equipment, Tai Chi etc).</td>
<?php for ($j = 6; $j <= 12; $j++) {
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'strd2_health_fitness' . $j} = $health_activity_data[0]->strd2_health_fitness;
                } else {
                    ${'strd2_health_fitness' . $j} = '';
                }
            } else {
                ${'strd2_health_fitness' . $j} = '';
            }
        } else {
            ${'strd2_health_fitness' . $j} = '';
        } ?>
            <td class="tdalign"><?php echo ${'strd2_health_fitness' . $j}; ?></td> 
<?php } ?>
        </tr>
        <tr>
            <td><b><u>Strand 3:</u><br> SEWA</b></td>
<?php for ($j = 6; $j <= 12; $j++) {
        if (isset($student_id_array_new[$j])) {
            if ($student_id_array_new[$j] != '') {
                $health_activity_data = check_health_activity_data_exist_for_studentid($student_id_array_new[$j]);
                if (isset($health_activity_data[0])) {
                    ${'strd3_sewa' . $j} = $health_activity_data[0]->strd3_sewa;
                } else {
                    ${'strd3_sewa' . $j} = '';
                }
            } else {
                ${'strd3_sewa' . $j} = '';
            }
        } else {
            ${'strd3_sewa' . $j} = '';
        } ?>
            <td class="tdalign"><?php echo ${'strd3_sewa' . $j}; ?></td> 
<?php } ?>
        </tr>
    </table>
</div>
    <?php } ?>
</html>
    @else
        <p>Class not recognized. Please provide a valid class number.</p>
    @endif

</html>














horizal code



@php
$school = getSchoolDetails();
$bgImage = getHealthBgImage();

$class = get_class_section_of_student($student_id);
$class_array = !empty($class) ? explode(' ', $class) : [];
$class_name = (!empty($class_array) && isset($class_array[0])) ? (int)$class_array[0] : 0;


$parent_info = get_student_parent_info($student_id, $customClaims);
$health_activity_data = check_health_activity_data_exist_for_studentid($student_id);


$parent = $parent_info[0] ?? null;
$health = $health_activity_data ?? [];

$student_id_array_new = [];

if ($class_name >= 1) {
    $student_id_array_new[$class_name] = $student_id;
    $temp = $student_id;

    for ($i = $class_name - 1; $i >= 1; $i--) {
        $temp = get_previous_student_id($temp);
        if (!$temp) break;
        $student_id_array_new[$i] = $temp;
    }
    ksort($student_id_array_new);
}

$val         = $health['value']       ?? [];
$groupData   = $health['group_data']  ?? [];
$paramData   = $health['param_data']  ?? [];
$description = $health['description'] ?? [];

$basicInfo = [];
foreach ($val as $key => $value) {
    $groupName = $groupData[$key]['group_name'] ?? '';
    if (strtolower(trim($groupName)) === 'basic information') {
        if ($value === '' || $value === null) continue;
        $basicInfo[] = ['label' => $key, 'value' => $value];
    }
}

/* ── Flatten param tree ── */
function flattenParamTree($nodes, $prefix = []) {
    $result = [];
    foreach ($nodes ?? [] as $node) {
        $current = array_merge($prefix, [$node['label'] ?? '']);
        if (!empty($node['children'])) {
            $result = array_merge($result, flattenParamTree($node['children'], $current));
        } else {
            $result[] = $current;
        }
    }
    return $result;
}

/* ── Build $tableData ── */
$tableData = [];
foreach ($val as $key => $value) {
    $groupName = $groupData[$key]['group_name'] ?? 'Other';
    if (strtolower(trim($groupName)) === 'basic information') continue;

    $paths = flattenParamTree($paramData[$key] ?? []);

    if (empty($paths)) {
        $tableData[] = [
            'group'         => $groupName,
            'sub_group'     => '',
            'sub_sub_group' => '',
            'test'          => $key,
            'desc'          => $description[$key] ?? '',
        ];
    } else {
        foreach ($paths as $p) {
            $tableData[] = [
                'group'         => $groupName,
                'sub_group'     => $p[0] ?? '',
                'sub_sub_group' => $p[1] ?? '',
                'test'          => $p[2] ?? $key,
                'desc'          => $description[$key] ?? '',
            ];
        }
    }
}

/* ── STEP 1: Build $grouped from $tableData ── */
$grouped = [];
foreach ($tableData as $row) {
    $g   = $row['group'];
    $sg  = $row['sub_group'];
    $ssg = $row['sub_sub_group'];
    $grouped[$g][$sg][$ssg][] = $row;
}

/* ── STEP 2: Flatten $grouped into $flatRows ── */
$flatRows = [];
foreach ($grouped as $groupName => $subGroups) {
    foreach ($subGroups as $subGroupName => $subSubs) {
        foreach ($subSubs as $subSubName => $items) {
            foreach ($items as $item) {
                $flatRows[] = [
                    'group'     => $groupName,
                    'sub_group' => $subGroupName,
                    'sub_sub'   => $subSubName,
                    'test'      => $item['test'],
                    'desc'      => $item['desc'],
                ];
            }
        }
    }
}

/* ── STEP 3: Chunk and calculate rowspans per page ── */
$rowsPerPage = 22;
$chunks      = array_chunk($flatRows, $rowsPerPage);
$pages       = [];

// foreach ($chunks as $pageRows) {
//     $groupCounts = [];
//     $subCounts   = [];

//     foreach ($pageRows as $row) {
//         $gKey  = $row['group'];
//         $sgKey = $row['group'] . '||' . $row['sub_group'];
//         $groupCounts[$gKey]  = ($groupCounts[$gKey]  ?? 0) + 1;
//         $subCounts[$sgKey]   = ($subCounts[$sgKey]   ?? 0) + 1;
//     }

//     $seenGroups = [];
//     $seenSubs   = [];
//     $processed  = [];

//     foreach ($pageRows as $row) {
//         $gKey  = $row['group'];
//         $sgKey = $row['group'] . '||' . $row['sub_group'];

//         $row['show_group']    = !isset($seenGroups[$gKey]);
//         $row['group_rowspan'] = $row['show_group'] ? $groupCounts[$gKey] : 0;

//         $row['show_sub']      = !isset($seenSubs[$sgKey]);
//         $row['sub_rowspan']   = $row['show_sub'] ? $subCounts[$sgKey] : 0;

//         $seenGroups[$gKey] = true;
//         $seenSubs[$sgKey]  = true;

//         $processed[] = $row;
//     }

//     $pages[] = $processed;
// }

/* ── STEP 3: Chunk by estimated height, not row count ── */
$pageHeightLimit = 480; // tune this (usable px inside page-content)
$pages           = [];
$currentPage     = [];
$currentHeight   = 0;

foreach ($flatRows as $row) {

    // Estimate row height based on description length
    $descLength  = strlen($row['desc'] ?? '');
    $descLines   = max(1, ceil($descLength / 30)); // ~30 chars per line in your font/column width
    $baseHeight  = 22;  // minimum row height in px
    $lineHeight  = 14;  // extra px per extra line
    $rowHeight   = $baseHeight + (($descLines - 1) * $lineHeight);

    // Also account for sub_sub wrapping (if long)
    $subSubLength = strlen($row['sub_sub'] ?? '');
    $subSubLines  = max(1, ceil($subSubLength / 20));
    $rowHeight    = max($rowHeight, $baseHeight + (($subSubLines - 1) * $lineHeight));

    // If adding this row exceeds page, start new page
    if ($currentHeight + $rowHeight > $pageHeightLimit && count($currentPage) > 0) {
        $pages[]       = $currentPage;
        $currentPage   = [];
        $currentHeight = 0;
    }

    $currentPage[] = $row;
    $currentHeight += $rowHeight;
}

// Add last page
if (!empty($currentPage)) {
    $pages[] = $currentPage;
}

/* ── Recalculate rowspans per page ── */
$finalPages = [];
foreach ($pages as $pageRows) {
    $groupCounts = [];
    $subCounts   = [];

    foreach ($pageRows as $row) {
        $gKey  = $row['group'];
        $sgKey = $row['group'] . '||' . $row['sub_group'];
        $groupCounts[$gKey]  = ($groupCounts[$gKey]  ?? 0) + 1;
        $subCounts[$sgKey]   = ($subCounts[$sgKey]   ?? 0) + 1;
    }

    $seenGroups = [];
    $seenSubs   = [];
    $processed  = [];

    foreach ($pageRows as $row) {
        $gKey  = $row['group'];
        $sgKey = $row['group'] . '||' . $row['sub_group'];

        $row['show_group']    = !isset($seenGroups[$gKey]);
        $row['group_rowspan'] = $row['show_group'] ? $groupCounts[$gKey] : 0;

        $row['show_sub']    = !isset($seenSubs[$sgKey]);
        $row['sub_rowspan'] = $row['show_sub'] ? $subCounts[$sgKey] : 0;

        $seenGroups[$gKey] = true;
        $seenSubs[$sgKey]  = true;

        $processed[] = $row;
    }

    $finalPages[] = $processed;
}

/* ── STEP 4: All class health data ── */
$allClassHealth = [];
foreach ($student_id_array_new as $cls => $id) {
    $h = check_health_activity_data_exist_for_studentid($id);
    $allClassHealth[$cls] = $h['value'] ?? [];
}
@endphp

<html>
<head>
<style>
/* ================= PAGE SETUP ================= */
@page {
    size: A4 landscape;
    margin: 0;
}

html, body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

/* Prevent overflow issues */
* {
    box-sizing: border-box;
}
  /* .statistics_line {
    display: block;              
    width: 100%;
    border-bottom: 1px solid #000;
    padding: 2px 0;
    min-height: 16px;  
 } */
/* ================= COMMON BACKGROUND ================= */
/* .bg-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
} */

.bg-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 210mm;
    z-index: 1;
}

/* ================= FIRST PAGE ================= */
.first {
    position: relative;
    width: 297mm;
    height: 210mm;
    overflow: hidden;
    page-break-after: always;
}

/* Wrapper to avoid absolute stacking issues */
.page-inner {
    position: relative;
    z-index: 2;
    padding: 30px 40px;
}



.school-header table {
    width: 100%;
} 


.school-name {
    font-size: 26px;
    font-weight: bold;
    color: #0b5fa5;
}

.school-address {
    font-size: 14px;
    margin-top: 3px;
}

.school-phone {
    font-size: 13px;
} 

.school-header {
    position: relative;
    width: 100%;
    height: 80px;
}

/* LOGO */
.logo-box {
    position: absolute;
    left: 0;
    top: 0;
}

.school-logo {
    height: 70px;
    margin-left: 15px;
}

/* CENTER TEXT (TRUE CENTER) */
.school-text {
    position: absolute;
    width: 100%;
    text-align: center;
    top: 0;
}

/* ===== TITLE ===== */
.certificate-title {
    text-align: center;
    margin-top: 10px;
    margin-bottom: 20px;
}

.main-title {
    font-size: 20px;
    font-weight: bold;
    letter-spacing: 1px;
    color: #1f2c7c;
}

.sub-title {
    font-size: 16px;
    font-weight: bold;
    margin-top: 5px;
    color: #1f2c7c;
}

/* ===== FIRST PAGE CONTENT ===== */
.first-content table {
    width: 85%;
    margin: auto;
    border-collapse: collapse;
    /* font-size: 14px; */
}

.first-content td {
    padding: 6px 8px;
    vertical-align: top;
}

.first-content td:first-child {
    white-space: nowrap;
    /* font-weight: bold; */
    width: 18%;
}

.statistics_line {
    display: block;
    width: 100%;
    border-bottom: 1px solid #000;
    padding: 2px 4px;
    min-height: 18px;
}

/* ================= TABLE PAGES ================= */
.health-page {
    position: relative;
    width: 297mm;
    min-height: 210mm;
    page-break-after: always;
    break-after: page;
}

.health-page:last-child {
    page-break-after: auto;
}

/* Content above background */
.page-content {
    position: relative;
    z-index: 2;
    width: 92%;
    margin: auto;
    padding-top: 30px;
    padding-right: 1px;
    padding-left: 1px;
    
}

/* ================= TABLE ================= */

/* .record-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    table-layout: fixed;
} */

.record-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    table-layout: fixed;
    page-break-after: auto;
    break-after: auto;
}

/* HEADER */
.record-table thead {
    display: table-header-group;
}

/* CELLS */
/* .record-table th {
    border: 1px solid #000;
    padding: 6px;
    background-color: #f2f2f2;
    font-weight: bold;
    text-align: center;
    vertical-align: middle;
}

.record-table td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
} */


/* GROUP ROW */
/* .group-cell {
    font-weight: bold;
    background-color: #efefef;
    text-align: left;
    padding-left: 6px;
} */

/* SUB GROUP ROW */
/* .subgroup-cell {
    background-color: #fafafa;
    text-align: left;
}

.bgcolor {
    background-color: #fafafa;

} */

/* REMOVE ANY GLOBAL TABLE OVERRIDE ISSUES */
/* table {
    width: 100%;
    border-collapse: collapse;
} */

/* REMOVE PAGE CUTTING ISSUE */
/* body {
    overflow: visible;
    
} */


/* TABLE BASE */
.record-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #f0f7ff; /* light blue base */
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08); /* 3D effect */
}

/* HEADER */
.record-table th {
    padding: 10px 14px;
    font-size: 14px;
    font-weight: 600;
    /* text-transform: uppercase; */
    letter-spacing: 0.05em;
    color: #000000;

    background: linear-gradient(145deg, #dbeafe, #bfdbfe); /* light blue gradient */
    border: 1px solid #cfe3f5;
    border-bottom: 2px solid #93c5fd;

    text-align: center;
    white-space: nowrap;

    box-shadow: inset 0 -2px 4px rgba(0,0,0,0.05); /* depth */
}

.record-table th.class-col {
    white-space: normal;
    line-height: 1.2;
}

/* CELLS */
.record-table td {
    border: 1px solid #d6e6f5; /* light border */
    padding: 6px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;

    background: #ffffff;
    transition: all 0.2s ease;
}

/* ROW HOVER EFFECT */
.record-table tr:hover td {
    background: #eaf4ff;
    transform: scale(1.002); /* slight lift */
}

/* GROUP CELL */
.group-cell {
    font-weight: bold;
    background: linear-gradient(145deg, #e0f2fe, #bae6fd);
    text-align: left;
    padding-left: 8px;
    color: #080808;

    box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05);
}

/* SUB GROUP */
.subgroup-cell {
    background-color: #f0f9ff;
    text-align: left;
    color: #000000;
}

/* NORMAL ALT BG */
.bgcolor {
    background-color: #f8fbff;
}

/* TABLE BORDER RADIUS FIX */
.record-table tr:first-child th:first-child {
    border-top-left-radius: 8px;
}
.record-table tr:first-child th:last-child {
    border-top-right-radius: 8px;
}

/* SOFT OUTER BORDER */
.record-table {
    border: 1px solid #cfe3f5;
}

/* BODY FIX */
body {
    overflow: visible;
}


/* OPTIONAL: BETTER PRINT CONTROL */
@media print {
    .record-table {
        font-size: 9px;
    }

    .record-table tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    thead {
        display: table-header-group;
    }
}
</style>
</head>

<body>

{{-- ================= FIRST PAGE ================= --}}
<div class="first">
    {{-- <img src="{{ public_path('health3_bg.jpg') }}" class="bg-img"> --}}
    <img src="{{$bgImage['file_path'] }}" class="bg-img">

    <div class="page-inner">

        {{-- HEADER --}}
        {{-- <div class="school-header">
            <table width="100%">
                <tr>
                    <td width="15%">
                        <img src="{{ $school['logo'] ?? '' }}" class="school-logo">
                    </td>
                    <td align="center">
                        <div class="school-name">{{ $school['school_name'] ?? '' }}</div>
                        <div class="school-address">{{ $school['address'] ?? '' }}</div>
                        <div class="school-phone">Phone: {{ $school['phone'] ?? '' }}</div>
                    </td>
                </tr>
            </table>
        </div> --}}
    {{-- <div class="bgcolor"> --}}
         <div class="school-header">

        <div class="logo-box">
           <img src="{{ $school['logo'] ?? '' }}" class="school-logo">
        </div>

        <div class="school-text">
           <div class="school-name">{{ $school['school_name'] ?? '' }}</div>
           <div class="school-address">{{ $school['address'] ?? '' }}</div>
        <div class="school-phone">Phone: {{ $school['phone'] ?? '' }}</div>
    </div>

   </div> 
        <div class="certificate-title">
            <div class="main-title">HEALTH AND ACTIVITY CARD</div>
            <div class="sub-title">GENERAL INFORMATION</div>
        </div>    
        <div class="first-content">
         <table>

       
          <tr>
            <td>NAME :</td>
            <td colspan="3">
                <span class="statistics_line">
                    {{ ($parent->first_name ?? '') . ' ' . ($parent->mid_name ?? '') . ' ' . ($parent->last_name ?? '') }}
                </span>
            </td>
          </tr>

       
          <tr>
            <td>ADMISSION DATE :</td>
            <td>
                <span class="statistics_line">
                    {{ !empty($parent->admission_date) ? date('d-m-Y', strtotime($parent->admission_date)) : '' }}
                </span>
            </td>

            <td>DATE OF BIRTH :</td>
            <td>
                <span class="statistics_line">
                    {{ !empty($parent->dob) ? date('d-m-Y', strtotime($parent->dob)) : '' }}
                </span>
            </td>
          </tr>

          <tr>
            <td>M F T :</td>
            <td>
                <span class="statistics_line">{{ $parent->gender ?? '' }}</span>
            </td>

            <td>BLOOD GROUP :</td>
            <td>
                <span class="statistics_line">{{ $parent->blood_group ?? '' }}</span>
            </td>
          </tr>

        
          <tr>
            <td>MOTHER'S NAME :</td>
            <td colspan="3">
                <span class="statistics_line">{{ $parent->mother_name ?? '' }}</span>
            </td>
          </tr>

          <tr>
            <td>FATHER'S NAME :</td>
            <td colspan="3">
                <span class="statistics_line">{{ $parent->father_name ?? '' }}</span>
            </td>
           </tr>

        @php $chunks = array_chunk($basicInfo, 2); @endphp
        @foreach($chunks as $rowItem)
        <tr>
            @foreach($rowItem as $item)
                <td>{{ strtoupper($item['label']) }} :</td>
                <td>
                    <span class="statistics_line">{{ $item['value'] }}</span>
                </td>
            @endforeach

            @if(count($rowItem) < 2)
                <td></td><td></td>
            @endif
        </tr>
        @endforeach

        <!-- FULL WIDTH ADDRESS -->
        <tr>
            <td>ADDRESS :</td>
            <td colspan="3">
                <span class="statistics_line">{{ $parent->permant_add ?? '' }}</span>
            </td>
        </tr>

        <tr>
            <td>PHONE NO :</td>
            <td colspan="3">
                <span class="statistics_line">
                    {{ $parent->f_mobile ?? $parent->m_mobile ?? '' }}
                </span>
            </td>
        </tr>

           </table>
      </div>
    </div>
        
    {{-- </div> --}}

  
</div>

{{-- ================= TABLE ================= --}}
{{-- @foreach($pages as $pageIndex => $pageRows)
<div class="health-page">
    <img src="{{ public_path('health3_bg.jpg') }}" class="bg-img">
    <div class="page-content">
          <h2 style="text-align: center; font-size: 20px; font-weight: bold; font-family: Georgia, 'Times New Roman', Times, serif; margin-bottom: 10px; letter-spacing: 1px; color: #1f2c7c;">
             HEALTH AND ACTIVITY RECORD
          </h2>
        <table class="record-table">
            <thead>
                <tr>
                    <th>Fitness</th>
                    <th>Sub</th>
                    <th>Sub Sub</th>
                    <th>Test</th>
                    <th>Description</th>
                    @foreach($student_id_array_new as $cls => $id)
                        <th>Class {{ $cls }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach($pageRows as $row)
                <tr>
                    @if($row['show_group'])
                        <td class="group-cell" rowspan="{{ $row['group_rowspan'] }}">
                            {{ $row['group'] }}
                        </td>
                    @endif

                    @if($row['show_sub'])
                        <td class="subgroup-cell" rowspan="{{ $row['sub_rowspan'] }}">
                            {{ $row['sub_group'] }}
                        </td>
                    @endif

                    <td>{{ $row['sub_sub'] }}</td>
                    <td>{{ $row['test'] }}</td>
                    <td>{{ $row['desc'] }}</td>

                    @foreach($student_id_array_new as $cls => $id)
                        <td>{{ $allClassHealth[$cls][$row['test']] ?? '' }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach --}}

@foreach($finalPages as $pageIndex => $pageRows)
<div class="health-page">
    {{-- <img src="{{ public_path('health3_bg.jpg') }}" class="bg-img"> --}}
    <img src="{{ $bgImage['file_path']}}" class="bg-img">
    <div class="page-content">

        {{-- @if($pageIndex === 0) --}}
        <h2 style="text-align: center; font-size: 20px; font-weight: bold; font-family: Georgia, 'Times New Roman', Times, serif; margin-bottom: 10px; letter-spacing: 1px; color: #1f2c7c;">
            HEALTH AND ACTIVITY RECORD
        </h2>
        {{-- @endif --}}

        <table class="record-table">
            <thead>
                <tr>
                    <th>Fitness</th>
                    <th>Sub</th>
                    <th>Sub Sub</th>
                    <th>Test</th>
                    <th>Description</th>
                    @foreach($student_id_array_new as $cls => $id)
                        <th class="class-col">Class {{ $cls }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach($pageRows as $row)
                <tr>
                    @if($row['show_group'])
                        <td class="group-cell" rowspan="{{ $row['group_rowspan'] }}">
                            {{ $row['group'] }}
                        </td>
                    @endif

                    @if($row['show_sub'])
                        <td class="subgroup-cell" rowspan="{{ $row['sub_rowspan'] }}">
                            {{ $row['sub_group'] }}
                        </td>
                    @endif

                    <td class="bgcolor">{{ $row['sub_sub'] }}</td>
                    <td class="bgcolor">{{ $row['test'] }}</td>
                    <td class="bgcolor">{{ $row['desc'] }}</td>

                    @foreach($student_id_array_new as $cls => $id)
                        <td class="bgcolor">{{ $allClassHealth[$cls][$row['test']] ?? '' }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

</body>
</html>