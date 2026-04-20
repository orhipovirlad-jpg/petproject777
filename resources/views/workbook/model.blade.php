@extends('workbook.layout')

@section('title', $title)
@section('subtitle', 'Проверьте юнит-экономику товаров в выбранной модели продаж и настройте параметры под ваш магазин')

@section('content')
    <div class="card" style="margin-bottom:14px;">
        <div class="card-head">Параметры модели</div>
        <div class="card-body">
            <form method="POST" action="{{ route('workbook.settings.update', ['model' => $model]) }}">
                @csrf
                <div class="table-wrap">
                    <table>
                        <tbody>
                        <tr>
                            <td>Комиссия, %</td><td><input class="input" type="number" step="0.01" name="commission_percent" value="{{ $settings['commission_percent'] }}"></td>
                            <td>Выкуп, %</td><td><input class="input" type="number" step="0.01" name="buyout_percent" value="{{ $settings['buyout_percent'] }}"></td>
                            <td>Базовая логистика, ₽</td><td><input class="input" type="number" step="0.01" name="logistics_base_cost" value="{{ $settings['logistics_base_cost'] }}"></td>
                        </tr>
                        <tr>
                            <td>Хранение/день, ₽</td><td><input class="input" type="number" step="0.01" name="storage_daily_cost" value="{{ $settings['storage_daily_cost'] }}"></td>
                            <td>Дней хранения</td><td><input class="input" type="number" step="0.01" name="storage_days" value="{{ $settings['storage_days'] }}"></td>
                            <td>Доп. расход, ₽</td><td><input class="input" type="number" step="0.01" name="extra_cost" value="{{ $settings['extra_cost'] }}"></td>
                        </tr>
                        <tr>
                            <td>Реклама, %</td><td><input class="input" type="number" step="0.01" name="ad_spend_percent" value="{{ $settings['ad_spend_percent'] }}"></td>
                            <td>Налог, %</td><td><input class="input" type="number" step="0.01" name="tax_percent" value="{{ $settings['tax_percent'] }}"></td>
                            <td>Скидка МП, %</td><td><input class="input" type="number" step="0.01" name="mp_discount_percent" value="{{ $settings['mp_discount_percent'] }}"></td>
                        </tr>
                        <tr>
                            <td>Эквайринг, %</td><td><input class="input" type="number" step="0.01" name="acquiring_percent" value="{{ $settings['acquiring_percent'] }}"></td>
                            <td>Последняя миля, ₽</td><td><input class="input" type="number" step="0.01" name="last_mile_cost" value="{{ $settings['last_mile_cost'] }}"></td>
                            <td colspan="2"><button class="btn btn-primary" type="submit">Применить настройки</button></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">Результат расчета по SKU</div>
        <div class="card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Товар</th>
                        <th class="num">Себест., ₽</th>
                        <th class="num">Цена до скидки, ₽</th>
                        <th class="num">Цена после скидки, ₽</th>
                        <th class="num">Прибыль, ₽</th>
                        <th class="num">Маржа, %</th>
                        <th class="num">ROI, %</th>
                        <th class="num">Расходы МП, ₽</th>
                        <th>Риск</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['product']['name'] }}<br><span style="color:#64748b;">{{ $row['product']['sku'] }}</span></td>
                            <td class="num">{{ number_format((float) $row['cost_total'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['product']['sale_price'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['calc']['sale_price_after_discount'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['calc']['net_profit'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['calc']['margin_percent'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['calc']['roi_percent'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) ($row['calc']['marketplace_costs_total'] ?? 0), 2, '.', ' ') }}</td>
                            <td>{{ $row['calc']['risk_level'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Список товаров пуст. Добавьте SKU на странице "Товары".</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
