@extends('workbook.layout')

@section('title', 'Сравнить')
@section('subtitle', 'Сравните прибыль и маржу по всем моделям, чтобы выбрать лучшую схему для каждого товара')

@section('content')
    <div class="card">
        <div class="card-head">Сравнительная таблица</div>
        <div class="card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Товар</th>
                        <th class="num">Себест., ₽</th>
                        <th class="num">WB FBW прибыль / маржа</th>
                        <th class="num">WB FBS прибыль / маржа</th>
                        <th class="num">Ozon FBO прибыль / маржа</th>
                        <th class="num">Ozon FBS прибыль / маржа</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['name'] }}<br><span style="color:#64748b;">{{ $row['sku'] }}</span></td>
                            <td class="num">{{ number_format((float) $row['cost_total'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $row['wb_fbw']['net_profit'], 2, '.', ' ') }} / {{ number_format((float) $row['wb_fbw']['margin_percent'], 2, '.', ' ') }}%</td>
                            <td class="num">{{ number_format((float) $row['wb_fbs']['net_profit'], 2, '.', ' ') }} / {{ number_format((float) $row['wb_fbs']['margin_percent'], 2, '.', ' ') }}%</td>
                            <td class="num">{{ number_format((float) $row['ozon_fbo']['net_profit'], 2, '.', ' ') }} / {{ number_format((float) $row['ozon_fbo']['margin_percent'], 2, '.', ' ') }}%</td>
                            <td class="num">{{ number_format((float) $row['ozon_fbs']['net_profit'], 2, '.', ' ') }} / {{ number_format((float) $row['ozon_fbs']['margin_percent'], 2, '.', ' ') }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Нет данных для сравнения. Добавьте товары.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
