@extends('workbook.layout')

@section('title', 'Инструкция')
@section('subtitle', 'Простая инструкция по сервису: что и в каком порядке делать, чтобы быстро получать рабочие решения')

@section('content')
    <style>
        .guide-grid { display:grid; gap:14px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        .guide-card { background:#fff; border:1px solid #dbe1ea; border-radius:12px; padding:14px; }
        .guide-card h3 { margin:0 0 8px; font-size:18px; }
        .guide-card p { margin:0; color:#475569; line-height:1.5; font-size:14px; }
        .step { display:flex; gap:10px; margin-bottom:10px; }
        .step-badge { min-width:28px; height:28px; border-radius:999px; background:#111827; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; margin-top:1px; }
        .step-text { color:#334155; line-height:1.5; font-size:14px; }
        .tip { background:#ecfeff; border:1px solid #a5f3fc; color:#0f766e; border-radius:10px; padding:10px 12px; font-size:14px; }
        .warn { background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; border-radius:10px; padding:10px 12px; font-size:14px; margin-top:10px; }
        .faq { margin:0; padding-left:18px; color:#334155; }
        .faq li { margin-bottom:7px; }
    </style>

    <div class="card" style="margin-bottom:14px;">
        <div class="card-head">Быстрый старт за 5 минут</div>
        <div class="card-body">
            <div class="step">
                <div class="step-badge">1</div>
                <div class="step-text"><strong>Откройте страницу “Товары”.</strong> Добавьте SKU: группа, название, артикул, себестоимость, цена продажи и скидка.</div>
            </div>
            <div class="step">
                <div class="step-badge">2</div>
                <div class="step-text"><strong>Проверьте вкладки моделей:</strong> `WB FBW`, `WB FBS`, `Ozon FBO`, `Ozon FBS` — это расчеты по разным схемам продаж.</div>
            </div>
            <div class="step">
                <div class="step-badge">3</div>
                <div class="step-text"><strong>Обновите параметры модели</strong> (комиссия, выкуп, логистика, налог, реклама), чтобы расчеты соответствовали вашим тарифам.</div>
            </div>
            <div class="step">
                <div class="step-badge">4</div>
                <div class="step-text"><strong>Откройте “Сравнить”</strong> и посмотрите, где у конкретного SKU выше прибыль и маржинальность.</div>
            </div>
            <div class="step">
                <div class="step-badge">5</div>
                <div class="step-text"><strong>Откройте “Автопилот”.</strong> Получите список SKU со статусами `critical / warning / ok` и конкретными рекомендациями по цене и скидке.</div>
            </div>
            <div class="step">
                <div class="step-badge">6</div>
                <div class="step-text"><strong>Используйте дашборды</strong> для контроля результата по всему ассортименту после применения рекомендаций.</div>
            </div>
            <div class="tip">Рекомендуемый порядок работы: <strong>Товары → Модели → Сравнить → Автопилот → Дашборды</strong>.</div>
        </div>
    </div>

    <div class="guide-grid" style="margin-bottom:14px;">
        <div class="guide-card">
            <h3>Товары</h3>
            <p>База SKU для всего сервиса. Любое изменение здесь автоматически влияет на страницы моделей и дашбордов.</p>
        </div>
        <div class="guide-card">
            <h3>WB/Ozon модели</h3>
            <p>Показывают экономику по каждой схеме. Здесь лучше всего калибровать расходы под реальные условия вашего магазина.</p>
        </div>
        <div class="guide-card">
            <h3>Сравнить</h3>
            <p>Сводная таблица для выбора оптимальной модели продаж под конкретный SKU.</p>
        </div>
        <div class="guide-card">
            <h3>Автопилот</h3>
            <p>Ежедневная приоритизация SKU: где падает маржа, какую цену/скидку лучше поставить и что делать в первую очередь.</p>
        </div>
        <div class="guide-card">
            <h3>Дашборды</h3>
            <p>Глобальный взгляд на ассортимент: средние метрики, рейтинг товаров, приоритетные позиции для действий.</p>
        </div>
    </div>

    <div class="card" style="margin-bottom:14px;">
        <div class="card-head">Как интерпретировать ключевые метрики</div>
        <div class="card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Метрика</th>
                        <th>Что означает</th>
                        <th>Как использовать</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Прибыль на штуку</td>
                        <td>Сколько остается с одной продажи после всех расходов.</td>
                        <td>Если низкая/отрицательная — корректируйте цену или модель продаж.</td>
                    </tr>
                    <tr>
                        <td>Маржинальность, %</td>
                        <td>Доля прибыли в цене продажи.</td>
                        <td>Сравнивайте SKU между собой и отбирайте самые эффективные.</td>
                    </tr>
                    <tr>
                        <td>ROI, %</td>
                        <td>Окупаемость себестоимости товара.</td>
                        <td>Полезно для оценки, куда выгоднее вкладывать оборотные средства.</td>
                    </tr>
                    <tr>
                        <td>Расходы МП</td>
                        <td>Комиссия, логистика, реклама и прочие издержки площадки.</td>
                        <td>Ищите, какой блок затрат больше всего “съедает” прибыль.</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="warn"><strong>Важно:</strong> данные в моделях должны отражать ваши реальные тарифы. Неверные настройки комиссий/логистики дадут искаженный результат.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">FAQ</div>
        <div class="card-body">
            <ul class="faq">
                <li><strong>Почему разные результаты между FBW и FBS?</strong> Из-за разной структуры логистики и дополнительных расходов.</li>
                <li><strong>С какой страницы начинать каждый день?</strong> С `Дашборд` или `Дашборд. ТОП товаров в разрезе`.</li>
                <li><strong>Где смотреть приоритет действий на сегодня?</strong> На странице `Автопилот` — она показывает самые проблемные SKU вверху списка.</li>
                <li><strong>Где менять тарифы маркетплейса?</strong> На странице конкретной модели (`WB FBW`, `Ozon FBO` и т.д.).</li>
                <li><strong>Если добавил товар, но не вижу его в дашборде?</strong> Проверьте, что цена продажи и себестоимость заполнены корректно.</li>
            </ul>
        </div>
    </div>
@endsection
