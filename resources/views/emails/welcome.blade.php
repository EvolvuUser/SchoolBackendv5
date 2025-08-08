<!DOCTYPE html>
<html>
<head>
    <title>Welcome to {{$schoolname}}</title>
</head>
<body>
    <p>Dear Sir/Madam,</p>
    <p>Welcome to {{$schoolname}} online application.</p>
    <p>You are registered with us. Your user id is {{ $userid }} and password is {{ $defaultpassword }}.</p>
    <p>The application can be accessed from school website by clicking 'Login' menu. You can also directly access it at <a href="{{ $websiteurl }}">{{ $websiteurl }}</a>.</p>
    <p>Please change your password and update your profile once you login.</p>
    <p>Regards,</p>
    <p>{{$schoolname}}</p>
</body>
</html>
