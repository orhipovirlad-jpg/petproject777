@extends('workbook.layout')

@section('title', 'Дашборд. Сравнить модели продаж')
@section('subtitle', 'Посмотрите, как меняются ключевые метрики товаров между WB и Ozon в разных моделях продаж')

@section('content')
    <div class="card" style="margin-bottom:14px;">
        <div class="card-head">Фильтр метрики</div>
        <div class="card-body">
            <form method="GET" action="{{ route('workbook.dashboard-models') }}">
                <div class="row-actions">
                    <select class="select" name="metric" style="max-width:280px;">
                        <option value="margin_percent" @selected($metric === 'margin_percent')>Маржинальность, %</option>
                        <option value="net_profit" @selected($metric === 'net_profit')>Прибыль на шт, ₽</option>
                        <option value="roi_percent" @selected($metric === 'roi_percent')>ROI, %</option>
                    </select>
                    <button class="btn btn-primary" type="submit">Показать</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">Сравнение по товарам</div>
        <div class="card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Товар</th>
                        <th class="num">WB FBW</th>
                        <th class="num">WB FBS</th>
                        <th class="num">Ozon FBO</th>
                        <th class="num">Ozon FBS</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="num">{{ number_format((float) $row['model_data']['wb_fbw'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['model_data']['wb_fbs'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['model_data']['ozon_fbo'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['model_data']['ozon_fbs'], 2, '.', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Нет данных.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
