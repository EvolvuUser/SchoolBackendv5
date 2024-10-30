<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Certificate</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            background-image: url('http://103.159.85.174/SchoolBackendv5/public/bonafide.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: Arial, sans-serif !important; 
            margin: 0; /* Ensure no default margin */
            display: flex; /* Use flexbox to center content */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            height: 100vh; /* Full viewport height */
            padding: 20px; /* Add padding to avoid edges */
        }
        .pdfdiv {
            margin:50px;
            width: 80%;
            max-width: 800px; /* Set a maximum width */
            background-color: rgba(255, 255, 255, 0.9); /* Semi-transparent background for readability */
            padding: 20px; /* Added padding for spacing */
            border-radius: 10px; /* Rounded corners */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); /* Subtle shadow effect */
            overflow: hidden; /* Prevent overflow */
        }
        tr td {
            padding: 8px; /* Increased padding for better spacing */
            word-wrap: break-word;
            font-size: 20px;
            text-align: left;
        }
        .statistics_line {
            width: 100%;
            border-bottom: 1px solid #000;
            padding: 5px 0; /* Added padding for clarity */
        }
        h2 {
            text-align: center; /* Center the title */
            font-size: 24px; /* Increased font size */
            margin-bottom: 20px; /* Spacing below the title */
        }
        .image_thumbnail {
            margin-left: 80px;
        }
    </style>
</head>
<body>

<div class="pdfdiv">
    <h2>BONAFIDE AND CHARACTER CERTIFICATE</h2>

    <div style="text-align:center;">
        
    </div>

    <p style="text-align:center; font-style: italic;">This is to certify that</p>

    <table border="0" class="table-responsive" style="width:100%; margin: auto; border-spacing: 0;">
        <tr>
            <td class="cursive1" style="font-style: italic; width: 20%;">Master / Miss</td>
            <td style="text-align:center;">
                <div class="statistics_line"><?php echo $data->stud_name ?></div>
            </td>
            <td style="text-align:center;">was</td>
        </tr>
        <tr>
            <td class="cursive" style="font-style: italic; width: 30%;">a Bonafide student of our school studying in Std</td>
            <td style="text-align:center;">
                <div class="statistics_line"><?php echo $data->class_division; ?></div>
            </td>
            <td>in the year</td>
            <td style="text-align:center;">
                <div class="statistics_line"><?php echo $data->academic_yr; ?></div>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center; font-style: italic;">
                Her / His date of birth as per the General Register of the school is
            </td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center;">
                <div class="statistics_line"><?php echo $data->dob." [ ".$data->dob_words."]"; ?></div>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center; font-style: italic;">
                She / He holds a good moral character.
            </td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center; font-style: italic;">
                She / He has passed her/his CBSE Std. <?php echo $data->class_division; ?> Examination of
            </td>
        </tr>
        <tr>
            <td style="text-align:center;"></td>
            <td style="text-align:center;">Feb / March</td>
            <td style="text-align:center;">
                <div class="statistics_line"></div>
            </td>
            <td style="text-align:center;">in the <?php echo $data->attempt; ?></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center; padding-top: 20px;">
                Date: <?php echo date_format(date_create($data->issue_date_bonafide), 'd-m-Y'); ?>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center;">
                Principal
            </td>
        </tr>
    </table>
</div>

</body>
</html>
