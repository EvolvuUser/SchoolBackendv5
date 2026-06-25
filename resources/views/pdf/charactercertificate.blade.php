@php
$school = getSchoolDetails();
$bgImage = getCharacterBgImage();

$bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
    ? asset($bgImage['file_path'])
    : asset('health3_bg.jpg');

$pageType = $bgImage['page_type'] ?? 'A4 landscape';
@endphp


<style>

    @page {

        @switch($pageType)

            @case('A4 portrait')
                size: A4 portrait;
                @break

            @case('A4 landscape')
                size: A4 landscape;
                @break

            @case('A5 portrait')
                size: A5 portrait;
                @break

            @case('A5 landscape')
                size: A5 landscape;
                @break

            @case('Letter portrait')
                size: letter portrait;
                @break

            @case('Letter landscape')
                size: letter landscape;
                @break

            @default
                size: A4 portrait;

        @endswitch

        margin-top: 0;
        margin-bottom: 0;
        margin-left: 0;
        margin-right: 0;
    }

  body{
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        background-image: url('{{ $bgImage['file_path'] }}');
        /* background-image: url('{{ $bgPath }}'); */
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
    }

    tr td{
        padding-top:3px;
        padding-bottom:3px;
        word-wrap:break-word;
        font-size:20px;
        font-family:Arial !important;
        text-align:left;
    }

    .statistics_line{
        width:100%;
        border-bottom:1px solid #000;
    }

</style>


<html>

<div class="pdfdiv"> <!--Ends Here -->
	<br/>
	
    <div style="width:80%;margin-top:23%;margin-left:5%;text-align:center;display: inline-block">
     <table border="0"  class="table-responsive" style="width:95%;margin-left:5%;margin-top:20%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top:5%;" cellpadding="1" cellspacing="10" >
             <tr>
                 <?php if ($student_image = '') { ?>
                <td style="font-size:15px;text-align:right;">BONAFIDE AND CHARACTER CERTIFICATE  
<?php
    $image_url = m;
    ?>
	<img src="<?php echo $image_url; ?>"  class="image_thumbnail studimg" width="50" height="50" style="margin-left:80px;"/>
	</td>
<?php } else { ?>
<td style="font-size:15px;text-align:center;">BONAFIDE AND CHARACTER CERTIFICATE  
<?php } ?><br></td>
               
            </tr>
          
            <tr>
                <td style="font-size:15px;text-align:center;">This is to certify that</td>
            </tr>
			<tr> 
                <td>
                    <!--<br>-->
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td class="cursive1" style="font-size:15px;width: 18%; word-wrap:break-word;">Master / Miss </td>
						<td style="font-size:15px;width: auto;text-align:center;"><div class="statistics_line"><?php echo $data->stud_name; ?></div></td>
						<td style="font-size:15px;width: 5%;text-align:center;">was</td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border-collapse: collapse;" cellpadding="0" cellspacing="0">
                        <td style="font-size:15px;width: 15%;white-space: nowrap;" class="cursive">a Bonafide student of our school studying in Std</td>
                        <td style="font-size:15px;width: 5%;text-align:center;"><div class="statistics_line"><?php echo $data->class_division; ?></div></td>
                        <td style="font-size:15px;width: 5%;padding-left:2%;white-space: nowrap;"> in the year </td>
						<td style="font-size:15px;width: 15%;text-align:center;"><div class="statistics_line"><?php echo $data->academic_yr; ?></div></td>
						
                    </table>
                </td>
                
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:15px;width: 1%;word-wrap:break-word;text-align: center;">Her / His date of birth as per the General Register of the school is</td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="width:20%;text-align:center;font-size:15px;"><div class="statistics_line">{{ \Carbon\Carbon::parse($data->dob)->format('d-m-Y') . ' [ ' . $data->dob_words . ' ]' }}</div></td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:15px;width: 1%;word-wrap:break-word;text-align: center;">She / He holds a good moral character.</td>
                    </table>
                    
                </td>
                <br>
            </tr>
             <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:15px;width: 1%;word-wrap:break-word;text-align: center;">She / He has passed her /his CBSE Std. <?php echo $data->class_division; ?> Examination of</td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:15px;width: 10%;word-wrap:break-word;text-align: center;"></td>
                        <td style="font-size:15px;width: 8%;word-wrap:break-word;text-align: center;"> Feb / March</td>
                        <?php
                       
                        $academic_yr_to = $data->academic_yr;
                        $acd_yr = explode('-', $academic_yr_to);
                        $to_year = date('Y', strtotime($acd_yr[1]));
                        
                        ?>
                        <td style=" width:5%;text-align:center;font-size:15px;"><div class="statistics_line"><?php echo $to_year; ?></div></td>
                        <td style=" width:9%;text-align:center;font-size:15px;">in the <?php echo $data->attempt; ?></td>
                        <td style="font-size:15px;width: 11%;word-wrap:break-word;text-align: center;"></td>
                    </table>
                    
                </td>
                <br>
            </tr>
            <tr><td><br></td></tr>
            <tr>
                 <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:15px;width: 10%;padding-top: 10px;word-wrap:break-word;text-align: center;">Date: <?php echo date_format(date_create($data->issue_date_bonafide), 'd-m-Y'); ?></td>
                        <td style=" width:10%;text-align:center;font-size:15px;"></td>
                        <td style="font-size:15px;width: 10%;padding-top: 10px;word-wrap:break-word;text-align: center;">Principal</td>
                    </table>
                    
                </td>
                </tr>
		</table>
	</div>   
    </div>
    <!--Ends Here -->
</html>