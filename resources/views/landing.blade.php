<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarginFlow — сервис юнит-экономики для селлеров</title>
    <style>
        :root {
            --bg: #f3f6fb;
            --card: #ffffff;
            --line: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #111827;
            --accent: #0ea5e9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .container { max-width: 1160px; margin: 0 auto; padding: 0 16px; }
        .top {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(243, 246, 251, 0.92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--line);
        }
        .top-inner {
            height: 64px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .logo { font-weight: 800; letter-spacing: -0.02em; }
        .logo span { color: #334155; font-weight: 600; }
        .actions { display: flex; gap: 8px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--text);
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        .hero { padding: 64px 0 38px; }
        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 18px;
            align-items: stretch;
        }
        .panel {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        }
        h1 {
            margin: 0 0 12px;
            font-size: 42px;
            line-height: 1.1;
            letter-spacing: -0.03em;
        }
        .lead {
            margin: 0 0 18px;
            color: #334155;
            line-height: 1.55;
            font-size: 16px;
        }
        .list { margin: 0; padding-left: 18px; color: #334155; }
        .list li { margin-bottom: 8px; }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #075985;
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .section { padding: 8px 0 22px; }
        .section-title {
            margin: 0 0 12px;
            font-size: 28px;
            letter-spacing: -0.02em;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
        }
        .card h3 {
            margin: 0 0 8px;
            font-size: 16px;
            letter-spacing: -0.01em;
        }
        .card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }
        .steps {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
        .step {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
        }
        .step .num {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .step h4 { margin: 0 0 6px; font-size: 14px; }
        .step p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.4; }
        .cta {
            margin: 26px 0 44px;
            background: linear-gradient(135deg, #111827, #1f2937);
            color: #fff;
            border-radius: 16px;
            padding: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .cta h3 { margin: 0 0 6px; font-size: 24px; }
        .cta p { margin: 0; color: #cbd5e1; }
        .cta .btn { border-color: #374151; background: #fff; color: #111827; }
        .footer { color: #94a3b8; font-size: 13px; padding-bottom: 24px; text-align: center; }
        @media (max-width: 980px) {
            .hero-grid { grid-template-columns: 1fr; }
            .cards { grid-template-columns: 1fr; }
            .steps { grid-template-columns: 1fr 1fr; }
            h1 { font-size: 34px; }
            .cta { flex-direction: column; align-items: flex-start; }
        }
        @media (max-width: 640px) {
            .steps { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<header class="top">
    <div class="container top-inner">
        <div class="logo">Margin<span>Flow</span></div>
        <div class="actions">
            @auth
                <a class="btn btn-primary" href="{{ route('workbook.products') }}">Открыть кабинет</a>
            @else
                <a class="btn" href="{{ route('cabinet.login-page') }}">Войти</a>
                <a class="btn btn-primary" href="{{ route('cabinet.register-page') }}">Начать бесплатно</a>
            @endauth
        </div>
    </div>
</header>

<main class="container">
    <section class="hero">
        <div class="hero-grid">
            <div class="panel">
                <span class="badge">Сервис для селлеров WB/Ozon</span>
                <h1>Юнит-экономика без Excel-хаоса</h1>
                <p class="lead">
                    MarginFlow помогает считать маржу, прибыль и риски по каждому SKU, сравнивать модели продаж
                    и быстро понимать, что нужно менять в цене и скидке.
                </p>
                <div class="actions">
                    @auth
                        <a class="btn btn-primary" href="{{ route('workbook.autopilot') }}">Перейти в автопилот</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('cabinet.register-page') }}">Создать аккаунт</a>
                        <a class="btn" href="{{ route('cabinet.login-page') }}">У меня уже есть аккаунт</a>
                    @endauth
                </div>
            </div>
            <div class="panel">
                <h3 style="margin-top:0;">Что вы получаете</h3>
                <ul class="list">
                    <li>Единую базу товаров и тарифов по моделям WB/Ozon.</li>
                    <li>Сравнение FBW/FBS/FBO по прибыли, марже и ROI.</li>
                    <li>Автопилот с приоритетами: что исправить сегодня.</li>
                    <li>Дашборды по эффективности ассортимента.</li>
                    <li>Понятные рекомендации вместо ручного анализа таблиц.</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">Почему сервис удобнее таблицы</h2>
        <div class="cards">
            <article class="card">
                <h3>Все SKU в одном кабинете</h3>
                <p>Данные не теряются и всегда доступны после входа. Не нужно искать “актуальную копию файла”.</p>
            </article>
            <article class="card">
                <h3>Решения, а не просто формулы</h3>
                <p>Автопилот показывает, какие товары убыточны и какую цену/скидку поставить для целевой маржи.</p>
            </article>
            <article class="card">
                <h3>Единый рабочий процесс</h3>
                <p>Товары → Модели → Сравнение → Дашборды → Автопилот. Быстро и без лишних вкладок.</p>
            </article>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">Как это работает</h2>
        <div class="steps">
            <article class="step">
                <span class="num">1</span>
                <h4>Добавьте товары</h4>
                <p>Укажите SKU, себестоимость, цену и скидку.</p>
            </article>
            <article class="step">
                <span class="num">2</span>
                <h4>Настройте модели</h4>
                <p>Комиссия, логистика, налог, реклама, выкуп.</p>
            </article>
            <article class="step">
                <span class="num">3</span>
                <h4>Сравните площадки</h4>
                <p>Посмотрите, где по SKU выше прибыль и маржа.</p>
            </article>
            <article class="step">
                <span class="num">4</span>
                <h4>Действуйте по автопилоту</h4>
                <p>Используйте готовые рекомендации по цене и скидке.</p>
            </article>
        </div>
    </section>

    <section class="cta">
        <div>
            <h3>Готовы навести порядок в экономике SKU?</h3>
            <p>Запустите кабинет и получите первые рекомендации по товарам за несколько минут.</p>
        </div>
        <div class="actions">
            @auth
                <a class="btn" href="{{ route('workbook.products') }}">Открыть Workbook</a>
            @else
                <a class="btn" href="{{ route('cabinet.register-page') }}">Создать аккаунт</a>
            @endauth
        </div>
    </section>

    <div class="footer">MarginFlow · {{ now()->format('Y') }}</div>
</main>
</body>
</html>
