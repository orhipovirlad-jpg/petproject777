<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подключить PRO — MarginFlow</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />
    <style>
        :root {
            --line: #e5e7eb;
            --text: #111827;
            --muted: #6b7280;
            --primary: #111827;
            --primary-2: #1f2937;
        }
        body {
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            color: var(--text);
            background: #f6f7f9;
        }
        .container {
            max-width: 760px;
            margin: 46px auto;
            padding: 0 16px;
        }
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }
        .logo {
            font-weight: 900;
            font-size: 20px;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        .logo span { color: #374151; }
        h1 {
            margin: 0 0 10px;
            font-size: 36px;
            letter-spacing: -0.8px;
            line-height: 1.03;
        }
        p {
            color: var(--muted);
            margin: 6px 0;
            line-height: 1.45;
        }
        .price {
            font-size: 42px;
            font-weight: 900;
            margin: 10px 0 8px;
            letter-spacing: -0.8px;
        }
        .list {
            margin: 10px 0;
            padding-left: 18px;
        }
        .list li {
            margin: 7px 0;
            color: #334155;
        }
        .field {
            margin-top: 14px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 11px 12px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background: #fff;
        }
        input:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.18);
        }
        button {
            width: 100%;
            margin-top: 14px;
            border: 0;
            background: var(--primary);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            padding: 13px;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.2s ease;
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(17, 24, 39, 0.16);
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px 11px;
            border-radius: 12px;
            margin-top: 10px;
            border: 1px solid #fca5a5;
        }
        .meta {
            font-size: 12px;
            color: #64748b;
            margin-top: 10px;
        }
        .trust {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .pill {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            color: #4b5563;
            font-weight: 500;
        }
        a {
            color: #111827;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="logo">Margin<span>Flow</span></div>
        <h1>Подключить PRO</h1>
        <p>Современный платежный поток через ЮKassa. Доступ активируется автоматически после подтверждения оплаты.</p>
        <div class="price">{{ number_format((float) $price, 0, ',', ' ') }} ₽ / месяц</div>
        <ul class="list">
            <li>Безлимитные расчеты и сохранение истории</li>
            <li>Расширенный сценарный анализ SKU</li>
            <li>Приоритетные рекомендации по риску и марже</li>
        </ul>
        <div class="trust">
            <span class="pill">Оплата в РФ</span>
            <span class="pill">Карта / СБП / SberPay</span>
            <span class="pill">Автоактивация подписки</span>
        </div>

        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('pro.checkout') }}">
            @csrf
            <div class="field">
                <label for="email">Email для активации подписки</label>
                <input id="email" type="text" name="email" value="{{ old('email') }}" placeholder="seller@shop.ru или test" inputmode="email" autocomplete="email" required>
            </div>
            <button type="submit">Перейти к оплате</button>
        </form>

        <p class="meta">После оплаты вы вернетесь на сайт, и подписка активируется на этот email автоматически.</p>
        <p class="meta" style="padding:10px 12px;border-radius:12px;border:1px solid #e5e7eb;background:#f9fafb;"><strong>Уже оформляли PRO?</strong> <a href="{{ route('cabinet.show') }}">Войти в кабинет</a> по email и паролю.</p>
        <p class="meta">Тест: введите <strong>test</strong> вместо email (локально или при APP_DEBUG=true) — подключится PRO на email <strong>test@example.com</strong>.</p>
        <p class="meta"><a href="{{ route('cabinet.show') }}">На главную</a></p>
    </div>
</div>
</body>
</html>
