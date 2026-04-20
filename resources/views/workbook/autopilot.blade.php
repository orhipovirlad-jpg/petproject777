@extends('workbook.layout')

@section('title', 'Автопилот')
@section('subtitle', 'Приоритетные действия по SKU: где падает маржа и какую цену или скидку лучше поставить')

@section('content')
    <style>
        .kpis { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-bottom:14px; }
        .kpi { background:#fff; border:1px solid #dbe1ea; border-radius:10px; padding:10px 12px; }
        .kpi span { display:block; color:#64748b; font-size:12px; margin-bottom:4px; }
        .kpi strong { font-size:20px; line-height:1.1; }
        .status { display:inline-flex; align-items:center; border-radius:999px; padding:4px 10px; font-size:12px; font-weight:700; }
        .status-ok { background:#dcfce7; color:#166534; }
        .status-warning { background:#fef3c7; color:#92400e; }
        .status-critical { background:#fee2e2; color:#991b1b; }
        .filters { margin-bottom:14px; }
    </style>

    @php
        $criticalCount = count(array_filter($rows, fn(array $r): bool => $r['status'] === 'critical'));
        $warningCount = count(array_filter($rows, fn(array $r): bool => $r['status'] === 'warning'));
        $okCount = count(array_filter($rows, fn(array $r): bool => $r['status'] === 'ok'));
    @endphp

    <div class="card filters">
        <div class="card-head">Настройки автопилота</div>
        <div class="card-body">
            <form method="GET" action="{{ route('workbook.autopilot') }}">
                <div class="table-wrap">
                    <table>
                        <tbody>
                        <tr>
                            <td>Целевая маржа, %</td>
                            <td><input class="input" type="number" step="0.1" min="5" max="60" name="target_margin" value="{{ $targetMargin }}"></td>
                            <td>Порог critical, %</td>
                            <td><input class="input" type="number" step="0.1" min="0" max="40" name="critical_margin" value="{{ $criticalMargin }}"></td>
                            <td>Порог warning, %</td>
                            <td><input class="input" type="number" step="0.1" min="0" max="60" name="warning_margin" value="{{ $warningMargin }}"></td>
                            <td>План продаж, шт/мес</td>
                            <td><input class="input" type="number" min="1" max="50000" name="planned_units" value="{{ $plannedUnits }}"></td>
                            <td><button class="btn btn-primary" type="submit">Пересчитать</button></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <div class="kpis">
        <div class="kpi"><span>SKU всего</span><strong>{{ count($rows) }}</strong></div>
        <div class="kpi"><span>Critical</span><strong style="color:#991b1b;">{{ $criticalCount }}</strong></div>
        <div class="kpi"><span>Warning</span><strong style="color:#92400e;">{{ $warningCount }}</strong></div>
        <div class="kpi"><span>OK</span><strong style="color:#166534;">{{ $okCount }}</strong></div>
    </div>

    <div class="card">
        <div class="card-head">Рекомендации по SKU</div>
        <div class="card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Модель</th>
                        <th>Статус</th>
                        <th class="num">Текущая маржа, %</th>
                        <th class="num">Целевая маржа, %</th>
                        <th class="num">Текущая цена, ₽</th>
                        <th class="num">Текущая скидка, %</th>
                        <th class="num">Реком. цена после скидки, ₽</th>
                        <th class="num">Реком. скидка, %</th>
                        <th class="num">Безопасная скидка, %</th>
                        <th class="num">Прибыль/шт, ₽</th>
                        <th class="num">Прибыль/мес, ₽</th>
                        <th>Действие</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['name'] }}</strong><br>
                                <span style="color:#64748b;">{{ $row['sku'] }}</span>
                            </td>
                            <td>{{ strtoupper(str_replace('_', ' ', $row['model'])) }}</td>
                            <td>
                                @if($row['status'] === 'critical')
                                    <span class="status status-critical">critical</span>
                                @elseif($row['status'] === 'warning')
                                    <span class="status status-warning">warning</span>
                                @else
                                    <span class="status status-ok">ok</span>
                                @endif
                            </td>
                            <td class="num">{{ number_format((float) $row['current_margin'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['target_margin'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['current_price'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['current_discount'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['recommended_price'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['recommended_discount'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['safe_discount'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['current_profit'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['monthly_profit'], 2, '.', ' ') }}</td>
                            <td style="min-width:280px;">{{ $row['action'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="13">Нет товаров для автопилота. Добавьте SKU на странице "Товары".</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
