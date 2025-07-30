<!DOCTYPE html>
<html>
<head>
    <title>Lesson Plan Status</title>
</head>
<body>
    <p>Dear Teacher,</p>

    <p>
        Lesson Plan for Class - <strong>{{ $class }} {{ $section }}</strong>,<br>
        Subject - <strong>{{ $subject }}</strong>,<br>
        Week - <strong>{{ $week }}</strong><br>
        has been <strong>approved</strong>.
    </p>

    <p>Regards,<br>{{$schoolname}}</p>
</body>
</html>