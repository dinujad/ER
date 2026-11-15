<!DOCTYPE html>
<html>
<head>
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <h2>Hello Supervisor,</h2>
    <p>A new account has been created for you to manage production sections.</p>
    
    <p><strong>Your Login Credentials:</strong></p>
    <ul>
        <li><strong>Email:</strong> {{ $email }}</li>
        <li><strong>Password:</strong> {{ $password }}</li>
    </ul>
    
    <p>Please login at: <a href="{{ url('/erp/login') }}">{{ url('/erp/login') }}</a></p>
    
    <p>Thank you,<br>{{ config('app.name') }} Admin</p>
</body>
</html>
