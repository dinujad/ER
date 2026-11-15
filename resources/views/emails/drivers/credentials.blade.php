<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Driver Login Credentials</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #334155;">
    <h1>Hello, {{ $user->name }}!</h1>
    <p>You have been added as a Driver. Use the credentials below to log in and submit your daily run.</p>
    <p><strong>Login credentials:</strong></p>
    <ul>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Password:</strong> {{ $password }}</li>
    </ul>
    <p>Login URL: <a href="{{ url('/login') }}">{{ url('/login') }}</a></p>
    <p>After login you will see the Driver dashboard where you can enter the day's start/end KM, route, and upload meter photo, slip and fuel receipt.</p>
    <p>Thank you!</p>
</body>
</html>
