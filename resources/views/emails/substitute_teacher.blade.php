
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Substitution Notification</title>
</head>
<body>
    <!-- dd("Hello"); -->
    <p>Dear Teacher,</p>
    <p>You have been scheduled for a substitution on <strong>{{ $substitution['date'] }}</strong>.</p>
    <p>Following are the details:</p>
    <ul>
        <li><strong>Subject:</strong> {{ $substitution['subject_name'] }}</li>
        <li><strong>Class:</strong> {{ $substitution['class_name'] }} - {{ $substitution['section_name'] }}</li>
        <li><strong>Period:</strong> {{ $substitution['period'] }}</li>
    </ul>
    <p>Regards, <br> School Support</p>
</body>
</html>