<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <style>
        @page {
            size: A4 portrait;
            margin: 20px 25px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", DejaVu Serif, serif;
            color: #000;
            font-size: 16px;
        }

        .page {
            position: relative;
            width: 100%;
            height: 100%;
        }

        /* =====================================
           HEADER
        ===================================== */

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 18%;
            text-align: center;
        }

        .logo {
            width: 90px;
            height: auto;
        }

        .school-details {
            width: 64%;
            text-align: center;
            font-weight: bold;
            line-height: 1.25;
        }

        .foundation-name {
            font-size: 17px;
            margin-bottom: 2px;
        }

        .school-name {
            font-size: 20px;
            margin-bottom: 2px;
        }

        .address {
            font-size: 16px;
            margin-bottom: 2px;
        }

        .phone {
            font-size: 16px;
            margin-bottom: 2px;
        }

        .udise {
            font-size: 16px;
            margin-bottom: 2px;
        }

        .affiliation {
            font-size: 15px;
        }

        .right-empty {
            width: 18%;
        }

        .header-line {
            border: 0;
            border-top: 1px solid #000;
            margin-top: 8px;
        }

        /* =====================================
           REFERENCE NUMBER
        ===================================== */

        .reference-number {
            width: 100%;
            text-align: right;
            padding-right: 85px;
            margin-top: 18px;
            font-size: 13px;
        }

        /* =====================================
           TITLE
        ===================================== */

        .certificate-title {
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 75px;
        }

        /* =====================================
           CERTIFICATE TEXT
        ===================================== */

        .certificate-content {
            margin-left: 38px;
            margin-right: 35px;
            margin-top: 70px;

            font-size: 16px;
            line-height: 1.65;
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        /* =====================================
           BOTTOM SECTION
        ===================================== */

        .bottom-section {
            position: relative;
            width: 100%;
            height: 300px;
            margin-top: 70px;
        }

        /*
            STAMP BOX
            Positioned like your latest screenshot
        */
        .stamp-box {
            position: absolute;

            left: 515px;
            top: 0;

            width: 110px;
            height: 155px;

            border: 1px solid #555;
        }

        /*
            DATE - LEFT SIDE
        */
        .date {
            position: absolute;

            left: 28px;
            top: 190px;

            font-size: 16px;
            font-weight: normal;
        }

        /*
            SCHOOL NAME - RIGHT SIDE
        */
        .school-sign {
            position: absolute;

            left: 485px;
            top: 190px;

            width: 230px;

            text-align: center;
            font-size: 16px;
            font-weight: bold;

            white-space: nowrap;
        }

        /*
            PRINCIPAL
        */
        .principal {
            position: absolute;

            left: 515px;
            top: 270px;

            width: 110px;

            text-align: center;

            font-size: 17px;
            font-weight: bold;
        }
        
        
    </style>
</head>

<body>
<div class="page">
    @php
        $school = getSchoolDetails();
        $bgImage = getBonafideBgImage();
    
        $bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
            ? asset($bgImage['file_path'])
            : asset('health3_bg.jpg');
    
        $pageType = $bgImage['page_type'] ?? 'A4 landscape';
    @endphp
    <!-- =====================================
         HEADER
    ====================================== -->

    <table class="header-table">
        <tr>

            <!-- LOGO -->
            <td class="logo-cell">

                <img
                    src="https://sms.evolvu.in/public/jpslogo.jpg"
                    class="logo"
                    alt="Logo"
                >

            </td>


            <!-- SCHOOL DETAILS -->
            <td class="school-details">

                <div class="foundation-name">
                    JAYWANT EDUCATION FOUNDATION'S
                </div>

                <div class="school-name">
                    {{ $school['school_name'] ?? '' }}
                </div>
                
                <div class="address">
                    {{ $school['address'] ?? '' }}
                </div>
                
                <div class="phone">
                    Ph. {{ $school['phone'] ?? '' }}
                </div>
                
                <div class="udise">
                    UDISE - {{ $school['udise_no'] ?? '' }}
                </div>
                
                <div class="affiliation">
                    CBSE Affiliation No. {{ $school['affiliation_no'] ?? '' }}
                </div>

            </td>


            <!-- EMPTY RIGHT SIDE -->
            <td class="right-empty">
            </td>

        </tr>
    </table>


    <hr class="header-line">


    <!-- =====================================
         REFERENCE NUMBER
    ====================================== -->

    <div class="reference-number">
        JPS/2026-2027/{{$data->sr_no}}
    </div>


    <!-- =====================================
         TITLE
    ====================================== -->

    <div class="certificate-title">
        TO WHOMSOEVER IT MAY CONCERN
    </div>


    <!-- =====================================
         CERTIFICATE BODY
    ====================================== -->

    <div class="certificate-content">

        This is to certify that

        <span class="bold">
           {{$data->stud_name}}
        </span>
        is a bonafide student of this school and studying
        in Std. {{$data->class_division}}&nbsp;&nbsp;
        Academic Year {{$data->academic_yr}}.
        His/Her birth date as per our record
        <span class="bold">
            {{ \Carbon\Carbon::parse($data->dob)->format('d-m-Y') }} .
        </span>
        He/She bear a good moral character.

    </div>


    <!-- =====================================
         BOTTOM SECTION
    ====================================== -->

    <div class="bottom-section">


        <!-- STAMP / SIGNATURE BOX -->
        <div class="stamp-box">
        </div>


        <!-- DATE -->
        <div class="date">
            Date:{{\Carbon\Carbon::parse($data->issue_date_bonafide)->format('M j, Y')}}
        </div>


        <!-- SCHOOL NAME -->
        <div class="school-sign">
            Jaywant Public School
        </div>


        <!-- PRINCIPAL -->
        <div class="principal">
            Principal
        </div>


    </div>


</div>

</body>
</html>