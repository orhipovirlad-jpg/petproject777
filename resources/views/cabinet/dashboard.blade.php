<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Кабинет — MarginFlow</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <style>
        :root { --line: #e5e7eb; --text: #111827; --muted: #6b7280; --primary: #111827; --ok: #16a34a; --warn: #d97706; }
        body { margin: 0; font-family: Inter, Arial, sans-serif; color: var(--text); background: #f6f7f9; }
        .wrap { max-width: 560px; margin: 40px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); margin-bottom: 14px; }
        .logo { font-weight: 900; font-size: 18px; margin-bottom: 8px; }
        .logo span { color: #374151; }
        h1 { margin: 0 0 6px; font-size: 24px; letter-spacing: -0.5px; }
        p.meta { color: var(--muted); font-size: 14px; margin: 0 0 14px; line-height: 1.45; }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .row:last-child { border-bottom: 0; }
        .row span:first-child { color: var(--muted); }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-warn { background: #fef3c7; color: #92400e; }
        .badge-off { background: #f3f4f6; color: #4b5563; }
        .actions { display: flex; flex-direction: column; gap: 10px; margin-top: 16px; }
        .btn { display: inline-flex; justify-content: center; align-items: center; border-radius: 12px; font-weight: 700; padding: 12px 16px; font-size: 14px; text-decoration: none; border: 0; cursor: pointer; width: 100%; box-sizing: border-box; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-ghost { background: #fff; color: var(--text); border: 1px solid var(--line); }
        .success { background: #dcfce7; color: #166534; padding: 10px 12px; border-radius: 12px; margin-bottom: 12px; font-size: 14px; border: 1px solid #86efac; }
        a { color: #4f46e5; font-weight: 600; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="wrap">
    @if(session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="logo">Margin<span>Flow</span></div>
        <h1>Мини-кабинет</h1>
        <p class="meta">Вы вошли как <strong>{{ $emailMasked }}</strong></p>

        @php
            $active = $subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->isFuture();
        @endphp

        <div class="row">
            <span>Тариф</span>
            <strong>{{ $subscription->plan?->name ?? '—' }}</strong>
        </div>
        <div class="row">
            <span>Статус</span>
            <span>
                @if($active)
                    <span class="badge badge-ok">Активна</span>
                @elseif($subscription->status === 'active')
                    <span class="badge badge-warn">Истекла</span>
                @else
                    <span class="badge badge-off">{{ $subscription->status }}</span>
                @endif
            </span>
        </div>
        <div class="row">
            <span>Действует до</span>
            <strong>{{ $subscription->ends_at?->format('d.m.Y H:i') ?? '—' }}</strong>
        </div>
        @if($active)
            <p class="meta" style="margin-top:12px;">Безлимит в калькуляторе при вводе того же email.</p>
        @else
            <p class="meta" style="margin-top:12px;">Подписка не активна — продлите, чтобы снова получить безлимит.</p>
        @endif

        <div class="actions">
            <a class="btn btn-primary" href="{{ route('mvp.index', ['prefill_email' => $email]) }}">Открыть калькулятор</a>
            <a class="btn btn-ghost" href="{{ route('pro.show') }}">Продлить / оформить PRO</a>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('cabinet.logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-ghost" style="width:100%;">Выйти</button>
        </form>
        <p class="meta" style="margin-top:12px; margin-bottom:0;"><a href="{{ route('mvp.index') }}">На главную</a></p>
    </div>
</div>
</body>
</html>
