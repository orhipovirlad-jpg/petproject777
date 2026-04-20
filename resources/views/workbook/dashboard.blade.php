@extends('workbook.layout')

@section('title', 'Дашборд')
@section('subtitle', 'Краткая сводка по ассортименту: средняя маржа и прибыль по всем моделям')

@section('content')
    <div class="card">
        <div class="card-head">Сводка</div>
        <div class="card-body">
            <div class="table-wrap">
                <table>
                    <tbody>
                    <tr>
                        <th>Количество товаров</th>
                        <td>{{ $summary['products_count'] }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <div class="card-head">Средняя маржа по моделям, %</div>
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
                    <tr>
                        <td class="num">{{ number_format((float) $summary['avg_margin']['wb_fbw'], 2, '.', ' ') }}</td>
                        <td class="num">{{ number_format((float) $summary['avg_margin']['wb_fbs'], 2, '.', ' ') }}</td>
                        <td class="num">{{ number_format((float) $summary['avg_margin']['ozon_fbo'], 2, '.', ' ') }}</td>
                        <td class="num">{{ number_format((float) $summary['avg_margin']['ozon_fbs'], 2, '.', ' ') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <div class="card-head">Средняя прибыль на штуку, ₽</div>
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
                    <tr>
                        <td class="num">{{ number_format((float) $summary['avg_profit']['wb_fbw'], 2, '.', ' ') }}</td>
                        <td class="num">{{ number_format((float) $summary['avg_profit']['wb_fbs'], 2, '.', ' ') }}</td>
                        <td class="num">{{ number_format((float) $summary['avg_profit']['ozon_fbo'], 2, '.', ' ') }}</td>
                        <td class="num">{{ number_format((float) $summary['avg_profit']['ozon_fbs'], 2, '.', ' ') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
