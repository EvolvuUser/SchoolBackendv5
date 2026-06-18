@php
    $school = getSchoolDetails();
    $bgImage = getCasteBgImage();

    $bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
        ? asset($bgImage['file_path'])
        : asset('health3_bg.jpg');

    $pageType = $bgImage['page_type'] ?? 'A4 portrait';

    $contentMarginTop = match ($pageType) {
        'A4 portrait'      => '20%',
        'A4 landscape'     => '12%',
        'A5 portrait'      => '15%',
        'A5 landscape'     => '10%',
        'Letter portrait'  => '18%',
        'Letter landscape' => '12%',
        default            => '20%'
    };
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

        margin: 0;
    }

    * {
        box-sizing: border-box;
    }

    html,
    body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        width: 100%;
        height: auto;
    }

    body {
        background-image: url('{{ $bgPath }}');
        background-repeat: no-repeat;
        background-position: center center;
        background-size: 100% 100%;
    }

    .pdfdiv {
        width: 100%;
        padding: 0;
        margin: 0;
    }

    .main-container {

        /* left-right content space */
        width: 94%;
        margin-left: 3%;
        margin-right: 3%;

        /* top gap according to page type */
        margin-top: {{ $contentMarginTop }};

        padding: 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
    }

    tr {
        page-break-inside: avoid;
    }

    td {
        font-size: 13px;
        line-height: 1.25;
        vertical-align: middle;
        word-wrap: break-word;

        /* reduced padding */
        padding-top: 4px;
        padding-bottom: 4px;
        padding-left: 6px;
        padding-right: 6px;
    }

    .table-border {
        border: 1px solid #000;
    }

    .header-table td {
        padding-top: 2px;
        padding-bottom: 2px;
    }

    .details-table td {
        border: 1px solid #000;
        padding: 5px 7px;
    }

    .signature-table {
        width: 100%;
        margin-top: 15px;
    }

    .signature-table td {
        padding-top: 8px;
        padding-bottom: 8px;
    }

    img {
        max-width: 100%;
    }

    p {
        margin: 0;
        padding: 0;
        line-height: 1.3;
    }

    br {
        line-height: 6px;
    }

    center {
        margin: 0;
        padding: 0;
    }

</style>
<html>
<body>
	<div class="pdfdiv"> <!--Ends Here -->
		<div class="main-container">

			<center>
			<br/><center>
				<br>
				<br>
				
					<img src="url('http://103.159.85.174/SchoolBackendv5/public/bonafide.jpg')"  class="image_thumbnail studimg" width="100" height="100" style="padding-left: 70%;"/>
				<table width="100%" border="0">
					<tr>
					<td width="10%"></td>
					<td align="left" width="25%"></td>
					<td align="left" align="left" width="28%"></td>
					<td align="left" align="left"width="25%">REF. NO.: <?php echo $data->academic_yr . '/' . $data->sr_no; ?></td>
					<td align="left" align="left"></td>
				</tr>
					<tr>
					<td width="2%"></td>
					<td width="" colspan="4">This is to certify <?php echo $data->stud_name; ?> was a student of St. Arnolds Central School in class <?php echo $data->class_division; ?> for the academic session <?php echo $data->academic_yr; ?> .as per the school record her details are as follows</td>
				
				    </tr>
				
				</table>
				
			<table width="100%" border="0" style="border-collapse: collapse;">
				<tr>
					<td width="2%"></td>
					<td align="left" width="45%"  style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;">GR No.</td>
					<!--<td align="center" width="8%">:</td>-->
					<td align="left" align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;"><?php echo $data->reg_no; ?></td>
				</tr>
				<tr>
					<td width="5%"></td>
					<td align="left" width="45%" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;">Student name with Student ID and UID</td>
					<!--<td align="center" width="8%">:</td>-->
					<td align="left" width="45%" align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;"><?php echo $data->stud_name; ?><br><?php echo $data->stud_id_no; ?><br><?php echo $data->stu_aadhaar_no; ?></td>
				</tr>

				<tr>
					<td></td>	
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;">Mother’s Name</td>
					<!--<td align="center" >:</td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;"><?php echo $data->mother_name; ?></td>
				</tr>
				<tr>
					<td></td>
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;">Nationality</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;"><?php echo $data->nationality; ?></td>
					
				</tr>
				<tr>
					<td></td>
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;">Mother Tongue</td>
					<!--<td align="center" >:</td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 7px;"><?php echo $data->mother_tongue; ?></td>
				</tr>
				<tr>
					<td></td>	
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Religion</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->religion; ?></td>
				</tr>
				<tr>
					<td></td>	
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Caste</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->caste; ?></td>
				</tr>
				<tr>
					<td></td>	
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Sub Caste</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->subcaste; ?></td>
				</tr>
				<tr>
					<td></td>
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Birth Place</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->birth_place; ?></td>
					
				</tr>
				<tr>
					<td></td>
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Date of Birth</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo date_format(date_create($data->dob), 'd-m-Y') . ' ( ' . $data->dob_words . ')'; ?></td>
					
				</tr>
				<tr>
					<td></td>
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Previous School And Class</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->prev_school_class; ?></td>
					
				</tr>
				<tr>
					<td></td>
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Date of admission</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;"><?php echo date_format(date_create($data->admission_date), 'd-m-Y'); ?>
					</td>
				</tr>
			
				<tr>
					<td></td>
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">In which class and when was he/she was learning from</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->class_when_learning; ?></td>
				</tr>
				<tr>
					<td></td>
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Progress Report</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->progress; ?></td>
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
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;"><?php echo $data->leaving_reason; ?></td>
				</tr>
				<tr>
					<td></td>
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">Date of Leaving Certificate</td>
					<!--<td align="center" >: </td>-->
					<td align="left" style="border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;border-right:1px solid black;padding: 5px;">{{ \Carbon\Carbon::parse($data->lc_date_n_no)->format('d-m-Y') }}</td>
				</tr>
			</table>

				<br>
				<table width="90%" border="0">
					<tbody>
					<tr>
					<td width="2%"></td>
					<td align="left" style=""></td>
					<!--<td align="center" >: </td>-->
					<td align="center" style="">Fr. Sunil Menezes</td>
					</tr>
					<tr>
						<td></td>
					<td align="left" style="">Date : <?php echo date_format(date_create($data->issue_date_bonafide), 'd-m-Y'); ?></td>
					<!--<td align="center" >: </td>-->
					<td align="center" style="padding:20px;">Principal</td>
					</tr>
					</tbody>
					</table>
			
			<!--<p style="font-size:15px;padding-left: 5%;"> <span style="">Date : <?php echo date_format(date_create($data->issue_date_bonafide), 'd-m-Y'); ?></span><span style="margin-left:10%;padding-left: 20%;">Principal </span></p>-->
			</div></center>

			</center>
		</div>
<  
    </div>
    
    </body>
</html>