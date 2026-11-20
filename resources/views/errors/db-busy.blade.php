<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="10">
    <title>Server busy — DERMAS ERP</title>
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f4f7fb;color:#1e293b;display:flex;min-height:100vh;align-items:center;justify-content:center}
        .box{background:#fff;border:1px solid #d9e3ef;border-radius:14px;box-shadow:0 4px 18px rgba(15,39,68,.08);padding:32px 36px;max-width:440px;text-align:center}
        h1{font-size:1.25rem;margin:0 0 8px}
        p{color:#475569;margin:0 0 18px;line-height:1.5}
        a{display:inline-block;background:#1e3a5f;color:#fff;text-decoration:none;padding:9px 22px;border-radius:9px;font-weight:600}
        .spin{width:34px;height:34px;border:4px solid #d9e3ef;border-top-color:#1e3a5f;border-radius:50%;margin:0 auto 16px;animation:s 1s linear infinite}
        @keyframes s{to{transform:rotate(360deg)}}
    </style>
</head>
<body>
<div class="box">
    <div class="spin"></div>
    <h1>The system is busy right now</h1>
    <p>Too many people are using the system at the same moment. This page tries again by itself in 10 seconds — nothing you entered was saved twice.</p>
    <a href="">Try again now</a>
</div>
</body>
</html>
