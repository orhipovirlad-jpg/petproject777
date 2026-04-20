@extends('workbook.layout')

@section('title', 'Дашборд. ТОП товаров в разрезе')
@section('subtitle', 'Рейтинг лучших товаров по выбранной метрике в каждой модели продаж')

@section('content')
    <div class="card" style="margin-bottom:14px;">
        <div class="card-head">Параметры топа</div>
        <div class="card-body">
            <form method="GET" action="{{ route('workbook.dashboard-top') }}">
                <div class="row-actions">
                    <select class="select" name="metric" style="max-width:280px;">
                        <option value="margin_percent" @selected($metric === 'margin_percent')>Маржинальность, %</option>
                        <option value="net_profit" @selected($metric === 'net_profit')>Прибыль на шт, ₽</option>
                        <option value="roi_percent" @selected($metric === 'roi_percent')>ROI, %</option>
                    </select>
                    <input class="input" type="number" min="1" max="50" name="limit" value="{{ $limit }}" style="max-width:180px;">
                    <button class="btn btn-primary" type="submit">Обновить</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">ТОП по моделям</div>
        <div class="card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>WB FBW</th>
                        <th>WB FBS</th>
                        <th>Ozon FBO</th>
                        <th>Ozon FBS</th>
                    </tr>
                    </thead>
                    <tbody>
                    @for($i = 0; $i < $limit; $i++)
                        <tr>
                            @foreach(['wb_fbw', 'wb_fbs', 'ozon_fbo', 'ozon_fbs'] as $model)
                                @php $row = $tops[$model][$i] ?? null; @endphp
                                <td>
                                    @if($row)
                                        {{ $i + 1 }}. {{ $row['name'] }} — <strong>{{ number_format((float) $row['value'], 2, '.', ' ') }}</strong>
                                    @else
                                        —
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
