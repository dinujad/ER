<!DOCTYPE html>
<html>
<head>
    <title>Section Assignment</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <h3>Hello {{ $employee->name }},</h3>
    
    <p>You have been assigned as the Supervisor for the following section:</p>
    
    <h2 style="color: #2d3748;">{{ $section->name }}</h2>
    
    @if($password)
    <p>A new account has been created for you. Here are your credentials:</p>
    <ul>
        <li><strong>Email:</strong> {{ $employee->email }}</li>
        <li><strong>Password:</strong> {{ $password }}</li>
    </ul>
    @else
    <p>Please log in using your existing credentials.</p>
    @endif
    
    <p>Log in at: <a href="{{ route('supervisor.login') }}">{{ route('supervisor.login') }}</a></p>
    <p>After login you will see your section dashboard to manage manufacturing and section employees.</p>
    
    <p>Please manage your team and production tasks from this dashboard.</p>
    
    <p>Thank you,<br>{{ config('app.name') }} Admin</p>
</body>
</html>
