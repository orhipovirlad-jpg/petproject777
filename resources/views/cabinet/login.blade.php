<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Вход — MarginFlow</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <style>
        :root { --line: #e5e7eb; --text: #111827; --muted: #6b7280; --primary: #111827; --accent: #4f46e5; }
        body { margin: 0; font-family: Inter, Arial, sans-serif; color: var(--text); background: #f6f7f9; }
        .wrap { max-width: 440px; margin: 48px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); }
        .logo { font-weight: 900; font-size: 18px; margin-bottom: 8px; }
        .logo span { color: #374151; }
        h1 { margin: 0 0 8px; font-size: 24px; letter-spacing: -0.5px; }
        p.meta { color: var(--muted); font-size: 14px; line-height: 1.45; margin: 0 0 16px; }
        label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        input[type="email"], input[type="text"], input[type="password"] {
            width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 12px; padding: 11px 12px; font-size: 14px;
        }
        input:focus { outline: none; border-color: #818cf8; box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.2); }
        .btn { width: 100%; margin-top: 14px; border: 0; background: var(--primary); color: #fff; font-size: 14px; font-weight: 700; padding: 13px; border-radius: 12px; cursor: pointer; }
        .btn:hover { opacity: 0.95; }
        .btn-ghost { background: #fff; color: var(--text); border: 1px solid var(--line); margin-top: 10px; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px 12px; border-radius: 12px; margin-bottom: 12px; font-size: 14px; border: 1px solid #fca5a5; }
        .success { background: #dcfce7; color: #166534; padding: 10px 12px; border-radius: 12px; margin-bottom: 12px; font-size: 14px; border: 1px solid #86efac; }
        .hint { font-size: 12px; color: #64748b; margin-top: 12px; line-height: 1.4; }
        a { color: var(--accent); font-weight: 600; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .code-row { display: grid; grid-template-columns: 1fr; gap: 10px; }
        hr.sep { border: 0; border-top: 1px solid var(--line); margin: 20px 0; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="logo">Margin<span>Flow</span></div>
        <h1>Вход</h1>
        <p class="meta">Войдите в кабинет по email и паролю.</p>

        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('cabinet.login') }}">
            @csrf
            <label for="email_login">Email</label>
            <input id="email_login" type="email" name="email" value="{{ old('email') }}" placeholder="seller@shop.ru" required autocomplete="email">
            <label for="password_login" style="margin-top:10px;">Пароль</label>
            <input id="password_login" type="password" name="password" required autocomplete="current-password">
            <button type="submit" class="btn">Войти</button>
        </form>

        <hr class="sep">
        <p class="meta" style="margin-bottom:0;">Нет аккаунта? <a href="{{ route('cabinet.register-page') }}">Перейти к регистрации</a></p>

        <p class="hint"><a href="{{ route('home') }}">На главную</a></p>
    </div>
</div>
</body>
</html>
