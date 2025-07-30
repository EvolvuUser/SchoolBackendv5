<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
</head>
<body>
    <p>Dear EvolvU User,</p>

    <p>
        The password for login ID <strong>{{ $userId }}</strong> has been reset to <strong>{{ $newPassword }}</strong>.
        <br>
        Login at: <a href="{{ $loginUrl }}">{{ $loginUrl }}/</a>
    </p>

    <p>
        Please READ THE INSTRUCTION on the login page and refer to the help once you login into the application.<br><br>
        Make sure your email ID and mobile number are correctly added into your profile.
    </p>

    <p>Regards,<br>{{$shortName}} Support</p>
</body>
</html>