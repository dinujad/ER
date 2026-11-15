<!DOCTYPE html>
<html>
<head>
    <title>New Job Transfer - {{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <h3>Job passed to your section</h3>
    <p>Hello {{ $supervisorName }},</p>
    
    <p>A job has been passed to your section: <strong>{{ $sectionName }}</strong>. Please check your dashboard and take action.</p>
    
    <ul>
        <li><strong>Job No:</strong> {{ $job->job_number }}</li>
        <li><strong>Style:</strong> {{ $job->style_ref ?? '—' }}</li>
        <li><strong>Batch:</strong> {{ $job->batch_ref ?? '—' }}</li>
        <li><strong>Status:</strong> {{ $job->status }}</li>
    </ul>
    
    <p>Log in to your section dashboard to manage this job.</p>
    
    <p><a href="{{ route('supervisor.dashboard') }}">Go to Dashboard</a></p>
    
    <p>Thank you.</p>
</body>
</html>
