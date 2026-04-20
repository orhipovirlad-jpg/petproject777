<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Калькулятор юнит-экономики — MarginFlow</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --line: #d9e0ec;
            --text: #111827;
            --muted: #64748b;
            --primary: #111827;
            --ok: #14532d;
            --ok-bg: #dcfce7;
            --bad: #7f1d1d;
            --bad-bg: #fee2e2;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 24px auto 40px;
            padding: 0 16px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .title {
            margin: 0;
            font-size: 26px;
            line-height: 1.2;
        }
        .subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .card-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            font-weight: 700;
            background: #f8fafc;
        }
        .card-body {
            padding: 16px;
        }
        .stack {
            display: grid;
            gap: 14px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .data-table th,
        .data-table td {
            border: 1px solid var(--line);
            padding: 10px;
            vertical-align: middle;
        }
        .data-table th {
            text-align: left;
            background: #f8fafc;
            font-size: 13px;
            color: #334155;
        }
        .input,
        .select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
        }
        .input:focus,
        .select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18);
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 14px;
            flex-wrap: wrap;
        }
        .btn {
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-ghost {
            background: #fff;
            border: 1px solid var(--line);
            color: var(--text);
        }
        .notice {
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .notice-ok { background: var(--ok-bg); color: var(--ok); border: 1px solid #86efac; }
        .notice-bad { background: var(--bad-bg); color: var(--bad); border: 1px solid #fca5a5; }
        .muted { color: var(--muted); }
        .pill {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            background: #eef2ff;
            color: #3730a3;
        }
        @media (max-width: 900px) {
            .data-table { font-size: 13px; }
            .data-table th, .data-table td { padding: 8px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div>
            <h1 class="title">Калькулятор юнит-экономики</h1>
            <p class="subtitle">Интерфейс в табличном формате, приближенный к `wb.xlsx` (WB/Ozon, FBO/FBS).</p>
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            <a class="btn btn-ghost" href="{{ route('workbook.products') }}">Workbook</a>
            <form method="POST" action="{{ route('cabinet.logout') }}">
                @csrf
                <button class="btn btn-ghost" type="submit">Выйти</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">Таблица ввода данных</div>
        <div class="card-body">
            <div id="okBox" class="notice notice-ok" style="{{ session('success') ? '' : 'display:none;' }}">
                {{ session('success') }}
            </div>
            <div id="errBox" class="notice notice-bad" style="{{ $errors->any() ? '' : 'display:none;' }}">
                @if($errors->any())
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                @endif
            </div>

            <form id="calcForm" method="POST" action="{{ route('mvp.calculate') }}" data-ajax-url="{{ route('mvp.calculate-ajax') }}">
                @csrf

                <table class="data-table">
                    <thead>
                    <tr>
                        <th style="width: 34%;">Параметр</th>
                        <th style="width: 18%;">Значение</th>
                        <th>Комментарий</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Модель продаж</td>
                        <td>
                            <select id="sales_model" name="sales_model" class="select">
                                <option value="wb_fbw" @selected(old('sales_model', 'wb_fbw') === 'wb_fbw')>WB FBW</option>
                                <option value="wb_fbs" @selected(old('sales_model') === 'wb_fbs')>WB FBS</option>
                                <option value="ozon_fbo" @selected(old('sales_model') === 'ozon_fbo')>Ozon FBO</option>
                                <option value="ozon_fbs" @selected(old('sales_model') === 'ozon_fbs')>Ozon FBS</option>
                            </select>
                            <input id="platform" type="hidden" name="platform" value="{{ old('platform', 'wildberries') }}">
                        </td>
                        <td class="muted">Определяет формулы расчета затрат маркетплейса.</td>
                    </tr>
                    <tr>
                        <td>Email пользователя</td>
                        <td><input class="input" type="email" name="email" value="{{ old('email', $authEmail ?? '') }}" required></td>
                        <td class="muted">Используется для лимитов и сохранения расчетов.</td>
                    </tr>
                    <tr>
                        <td>Себестоимость товара, ₽</td>
                        <td><input class="input" type="number" step="0.01" min="1" name="cost_price" value="{{ old('cost_price', 500) }}" required></td>
                        <td class="muted">Закупка/производство.</td>
                    </tr>
                    <tr>
                        <td>Упаковка, ₽</td>
                        <td><input class="input" type="number" step="0.01" min="0" name="packaging_cost" value="{{ old('packaging_cost', 40) }}" required></td>
                        <td class="muted">Расходы на упаковку.</td>
                    </tr>
                    <tr>
                        <td>Логистика до МП, ₽</td>
                        <td><input class="input" type="number" step="0.01" min="0" name="logistics_cost" value="{{ old('logistics_cost', 90) }}" required></td>
                        <td class="muted">Доставка до склада/пункта.</td>
                    </tr>
                    <tr>
                        <td>Цена продажи до скидки, ₽</td>
                        <td><input class="input" type="number" step="0.01" min="1" name="sale_price" value="{{ old('sale_price', 1490) }}" required></td>
                        <td class="muted">Колонка F в листах модели.</td>
                    </tr>
                    <tr>
                        <td>Скидка, %</td>
                        <td><input class="input" type="number" step="0.01" min="0" max="70" name="discount_percent" value="{{ old('discount_percent', 10) }}" required></td>
                        <td class="muted">Колонка G, цена после скидки рассчитывается автоматически.</td>
                    </tr>
                    <tr>
                        <td>Комиссия МП, %</td>
                        <td><input class="input" type="number" step="0.01" min="0" max="60" name="commission_percent" value="{{ old('commission_percent', 19) }}" required></td>
                        <td class="muted">Доля комиссии от цены после скидки.</td>
                    </tr>
                    <tr>
                        <td>Выкуп, %</td>
                        <td><input class="input" type="number" step="0.01" min="1" max="100" name="buyout_percent" value="{{ old('buyout_percent', 95) }}"></td>
                        <td class="muted">Влияет на корректировку логистики.</td>
                    </tr>
                    <tr>
                        <td>Базовая ставка логистики, ₽</td>
                        <td><input class="input" type="number" step="0.01" min="0" name="logistics_base_cost" value="{{ old('logistics_base_cost', 19) }}"></td>
                        <td class="muted">Колонка “Базовая ставка”.</td>
                    </tr>
                    <tr>
                        <td>Хранение за сутки, ₽</td>
                        <td><input class="input" type="number" step="0.01" min="0" name="storage_daily_cost" value="{{ old('storage_daily_cost', 0.09) }}"></td>
                        <td class="muted">Используется в WB FBW.</td>
                    </tr>
                    <tr>
                        <td>Среднее кол-во дней хранения</td>
                        <td><input class="input" type="number" step="0.01" min="0" name="storage_days" value="{{ old('storage_days', 60) }}"></td>
                        <td class="muted">Хранение = ставка * дни.</td>
                    </tr>
                    <tr>
                        <td>Доп. фикс-расходы МП, ₽</td>
                        <td><input class="input" type="number" step="0.01" min="0" name="extra_cost" value="{{ old('extra_cost', 15) }}"></td>
                        <td class="muted">Прочие расходы по схеме.</td>
                    </tr>
                    <tr>
                        <td>Реклама, %</td>
                        <td><input class="input" type="number" step="0.01" min="0" max="40" name="ad_spend_percent" value="{{ old('ad_spend_percent', 8) }}" required></td>
                        <td class="muted">Продвижение от цены после скидки.</td>
                    </tr>
                    <tr>
                        <td>Эквайринг, % (Ozon)</td>
                        <td><input class="input" type="number" step="0.01" min="0" max="10" name="acquiring_percent" value="{{ old('acquiring_percent', 1.5) }}"></td>
                        <td class="muted">Используется для Ozon-моделей.</td>
                    </tr>
                    <tr>
                        <td>Последняя миля, ₽ (Ozon FBS)</td>
                        <td><input class="input" type="number" step="0.01" min="0" name="last_mile_cost" value="{{ old('last_mile_cost', 20) }}"></td>
                        <td class="muted">Фикс-расход для Ozon FBS.</td>
                    </tr>
                    <tr>
                        <td>Налог, %</td>
                        <td><input class="input" type="number" step="0.01" min="0" max="30" name="tax_percent" value="{{ old('tax_percent', 7) }}" required></td>
                        <td class="muted">УСН/налоговая нагрузка.</td>
                    </tr>
                    <tr>
                        <td>Скидка МП для налоговой базы, % (WB)</td>
                        <td><input class="input" type="number" step="0.01" min="0" max="100" name="mp_discount_percent" value="{{ old('mp_discount_percent', 0) }}"></td>
                        <td class="muted">Для WB налоговая база корректируется на скидку МП.</td>
                    </tr>
                    <tr>
                        <td>План продаж, шт/мес</td>
                        <td><input class="input" type="number" min="1" max="50000" name="planned_units" value="{{ old('planned_units', 300) }}" required></td>
                        <td class="muted">Для расчета месячной прибыли.</td>
                    </tr>
                    <tr>
                        <td>Цена конкурента, ₽</td>
                        <td><input class="input" type="number" step="0.01" min="1" name="competitor_price" value="{{ old('competitor_price', 1490) }}" required></td>
                        <td class="muted">Для сравнения с рынком.</td>
                    </tr>
                    </tbody>
                </table>

                <input type="hidden" name="returns_percent" value="{{ old('returns_percent', 0) }}">
                <input type="hidden" name="desired_margin_percent" value="{{ old('desired_margin_percent', 20) }}">

                <div class="actions">
                    <button type="submit" class="btn btn-primary">Рассчитать</button>
                    <a class="btn btn-ghost" href="{{ route('pro.show') }}">PRO</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <div class="card-head">Таблица результата</div>
        <div class="card-body">
            <div id="resultRoot" class="muted">Сделайте расчет, чтобы увидеть результат.</div>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('calcForm');
    const resultRoot = document.getElementById('resultRoot');
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const platform = document.getElementById('platform');
    const salesModel = document.getElementById('sales_model');
    const okBox = document.getElementById('okBox');
    const errBox = document.getElementById('errBox');
    const initialResult = @json($result);

    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[ch]));

    const num = (v) => Number(v || 0);
    const fmt = (v) => new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num(v));
    const renderResult = (r) => {
        if (!r) {
            resultRoot.innerHTML = '<span class="muted">Сделайте расчет, чтобы увидеть результат.</span>';
            return;
        }
        resultRoot.innerHTML = `
            <div style="overflow:auto;">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Показатель</th>
                        <th>Значение</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr><td>Модель</td><td><span class="pill">${esc(r.excel_model || 'classic')}</span></td></tr>
                    <tr><td>Цена после скидки, ₽</td><td>${fmt(r.sale_price_after_discount)}</td></tr>
                    <tr><td>Цена безубыточности, ₽</td><td>${fmt(r.break_even_price)}</td></tr>
                    <tr><td>Комиссия, ₽</td><td>${fmt(r.commission_cost)}</td></tr>
                    <tr><td>Реклама, ₽</td><td>${fmt(r.ads_cost)}</td></tr>
                    <tr><td>Налог, ₽</td><td>${fmt(r.tax_cost)}</td></tr>
                    <tr><td>Скорректированная логистика, ₽</td><td>${fmt(r.logistics_adjusted_cost)}</td></tr>
                    <tr><td>Хранение всего, ₽</td><td>${fmt(r.storage_total_cost)}</td></tr>
                    <tr><td>Расходы МП всего, ₽</td><td>${fmt(r.marketplace_costs_total)}</td></tr>
                    <tr><td>Себестоимость всего, ₽</td><td>${fmt(r.fixed_costs)}</td></tr>
                    <tr><td>Прибыль за штуку, ₽</td><td>${fmt(r.net_profit)}</td></tr>
                    <tr><td>Маржа, %</td><td>${fmt(r.margin_percent)}</td></tr>
                    <tr><td>ROI, %</td><td>${fmt(r.roi_percent)}</td></tr>
                    <tr><td>Месячная прибыль, ₽</td><td>${fmt(r.monthly_profit)}</td></tr>
                    <tr><td>Отклонение от конкурента, %</td><td>${fmt(r.price_delta_vs_competitor_percent)}</td></tr>
                    <tr><td>Уровень риска</td><td>${esc(r.risk_level || '—')}</td></tr>
                    </tbody>
                </table>
            </div>
        `;
    };

    const syncPlatform = () => {
        if (!salesModel || !platform) {
            return;
        }
        if (salesModel.value.startsWith('ozon')) {
            platform.value = 'ozon';
        } else {
            platform.value = 'wildberries';
        }
    };

    const setError = (message) => {
        if (!errBox) return;
        if (!message) {
            errBox.style.display = 'none';
            errBox.textContent = '';
            return;
        }
        errBox.style.display = '';
        errBox.textContent = message;
    };

    const setOk = (message) => {
        if (!okBox) return;
        if (!message) {
            okBox.style.display = 'none';
            okBox.textContent = '';
            return;
        }
        okBox.style.display = '';
        okBox.textContent = message;
    };

    salesModel?.addEventListener('change', syncPlatform);
    syncPlatform();
    renderResult(initialResult);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        syncPlatform();
        setError('');
        setOk('');

        const payload = Object.fromEntries(new FormData(form).entries());
        try {
            const response = await fetch(form.dataset.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                setError(data.message || 'Ошибка расчета.');
                return;
            }
            renderResult(data.result);
            setOk('Расчет выполнен.');
        } catch (error) {
            setError('Сеть недоступна. Попробуйте снова.');
        }
    });
})();
</script>
</body>
</html>
