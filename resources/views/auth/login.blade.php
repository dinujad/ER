<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($supervisorLogin) && $supervisorLogin ? 'Supervisor Login' : 'Login' }} - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .login-wrap {
            display: flex;
            min-height: 100vh;
        }
        .login-left {
            flex: 0 0 50%;
            max-width: 50%;
            background: #fdfbf7;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-right {
            flex: 0 0 50%;
            max-width: 50%;
            background: #f5f3ef;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-right img {
            max-width: 100%;
            height: auto;
            max-height: 85vh;
            object-fit: contain;
        }
        .login-form-box {
            width: 100%;
            max-width: 380px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 2rem;
        }
        .brand-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .brand-text {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1a1a2e;
            letter-spacing: -0.02em;
        }
        .login-form-box h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.35rem;
        }
        .login-form-box .subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 1.75rem;
        }
        .form-label {
            font-weight: 500;
            color: #374151;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.65rem 1rem;
            border: 1px solid #e5e7eb;
        }
        .form-control:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }
        .btn-signin {
            width: 100%;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            background: #f59e0b;
            border: none;
            color: #1a1a2e;
        }
        .btn-signin:hover {
            background: #d97706;
            color: #fff;
        }
        .password-wrap { position: relative; }
        .password-wrap .form-control { padding-right: 2.5rem; }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
        }
        .password-toggle:hover { color: #374151; }
        .login-links {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.875rem;
        }
        .login-links a { color: #6b7280; text-decoration: none; }
        .login-links a:hover { color: #1a1a2e; text-decoration: underline; }
        @media (max-width: 991.98px) {
            .login-wrap { flex-direction: column; }
            .login-left, .login-right { max-width: 100%; flex: 1 1 100%; min-height: auto; }
            .login-right { order: -1; padding: 1.5rem; max-height: 30vh; }
            .login-right img { max-height: 25vh; }
        }
        @media (max-width: 575.98px) {
            .login-left { padding: 1.5rem 1.25rem; align-items: flex-start; margin-top: 1rem; }
            .login-right { padding: 1rem; max-height: 25vh; display: none; } /* optionally hidden on very small screens for quick login, or we can keep it with smaller height */
            .brand { margin-bottom: 1.25rem; }
            .brand-logo { width: 40px; height: 40px; }
            .brand-text { font-size: 1.15rem; }
            .login-form-box h1 { font-size: 1.35rem; }
            .login-form-box .subtitle { font-size: 0.85rem; margin-bottom: 1.25rem; }
            .btn-signin { padding: 0.6rem 1.25rem; }
        }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-left">
        <div class="login-form-box">
            <div class="brand">
                @if(file_exists(public_path('images/logo.png')))
                    <img src="{{ asset('images/logo.png') }}" alt="" class="brand-logo">
                @else
                    <span style="width:48px;height:48px;background:#dc2626;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.25rem;">D</span>
                @endif
                <span class="brand-text">DERMAS APPAREL</span>
            </div>
            <h1>Login to your account</h1>
            <p class="subtitle">Sign in to access the ERP</p>

            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ (isset($supervisorLogin) && $supervisorLogin) ? route('supervisor.login.post') : route('login.post') }}"
                  autocomplete="off">
                @csrf
                {{-- Hidden dummy fields trick some browsers into not autofilling the real fields below --}}
                <input type="text"     style="display:none" name="_fake_email"    tabindex="-1" aria-hidden="true" autocomplete="username">
                <input type="password" style="display:none" name="_fake_password" tabindex="-1" aria-hidden="true" autocomplete="current-password">

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="text"
                           name="email"
                           class="form-control"
                           placeholder="your@email.com"
                           value="{{ old('email') }}"
                           autocomplete="off"
                           required
                           autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="password-wrap">
                        <input type="password"
                               name="password"
                               id="loginPassword"
                               class="form-control"
                               placeholder="••••••••"
                               autocomplete="new-password"
                               required>
                        <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                @if(isset($supervisorLogin) && $supervisorLogin)
                    <p class="text-muted small mb-3">Use the same email as on your employee record. Can't log in? Ask admin to set your password from <strong>Employees → Supervisors</strong>.</p>
                @endif
                <button type="submit" class="btn btn-signin">Sign In</button>
            </form>

            <div class="login-links mt-3">
                @if(isset($supervisorLogin) && $supervisorLogin)
                    <a href="{{ route('login') }}">Admin / Staff Login</a>
                @else
                    <a href="{{ route('supervisor.login') }}" class="me-2">Supervisor Login</a>
                    <span class="text-muted">|</span>
                    <a href="/" class="ms-2">Back to Home</a>
                @endif
            </div>
        </div>
    </div>
    <div class="login-right">
        @if(file_exists(public_path('images/login-illustration.png')))
            <img src="{{ asset('images/login-illustration.png') }}" alt="Fashion design and apparel">
        @elseif(file_exists(public_path('images/login-illustration.jpg')))
            <img src="{{ asset('images/login-illustration.jpg') }}" alt="Fashion design and apparel">
        @else
            <div class="text-center text-muted py-5 px-4">
                <i class="bi bi-image fs-1 d-block mb-2"></i>
                <p class="small mb-0">Place <strong>login-illustration.png</strong> or <strong>login-illustration.jpg</strong> in <code>public/images/</code> for the right-side illustration.</p>
            </div>
        @endif
    </div>
</div>

<script>
(function() {
    var toggle = document.getElementById('togglePassword');
    var input = document.getElementById('loginPassword');
    var icon = document.getElementById('toggleIcon');
    if (toggle && input && icon) {
        toggle.addEventListener('click', function() {
            var isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !isPass);
            icon.classList.toggle('bi-eye-slash', isPass);
        });
    }
})();
</script>
</body>
</html>
