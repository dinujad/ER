<!DOCTYPE html>
<html>
<head>
    <title>Welcome to the Team</title>
</head>
<body>
    <h1>Hello, {{ $user->name }}!</h1>
    <p>You have been added as a Sales Person to the system.</p>
    <p>Here are your login credentials:</p>
    <ul>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Password:</strong> {{ $password }}</li>
    </ul>
    <p>Please login at: <a href="{{ route('sales-person.login') }}">{{ route('sales-person.login') }}</a></p>
    <p>Thank you!</p>
</body>
</html>
