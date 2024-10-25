<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            background-image: url('http://103.159.85.174:8500/public/bonafide.jpg');
            background-size: cover;
            background-repeat: no-repeat;
        }
        tr td {
            padding-top: 3px; 
            padding-bottom: 3px;
            word-wrap: break-word;
            font-size: 14px;
        }
        .pdfdiv {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .image_thumbnail {
            padding-left: 70%;
        }
    </style>
</head>
<body>
    <div class="pdfdiv">
        <center>
            <?php 
            $image_url = 'http://localhost/laravel/evolvu/storage/app/public/csv_rejected/bonafide.jpg';
            ?>
            <div style="width: 95%; margin-top: 20%;">
                <img src="<?php echo $image_url; ?>" class="image_thumbnail studimg" width="100" height="100" />
                <center><p style="font-size:20px"><b>BONAFIDE CERTIFICATE</b></p></center>
                <center><p style="font-size:20px"><b>To whomsoever it may concern</b></p></center>
                <p style="font-size:15px;"><span style="margin-left:10px;"><b>Ref. No: 110</b></span></p>
                <p style="font-size:15px"><span style="margin-left:20px;">This is to certify that Mst/Miss <b></b>.</span></p>
                <p style="font-size:15px"><span style="">According to our record, her date of birth is manish.</span></p>
                <br>
                <p style="font-size:18px"><span style="">Date: <span style="margin-left:50%">Fr. Sunil Memezes</span></span></p>
            </div>
        </center>
    </div>
</body>
</html>