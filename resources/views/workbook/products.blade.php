@extends('workbook.layout')

@section('title', 'Товары')
@section('subtitle', 'Добавляйте и редактируйте товары: отсюда берутся данные для всех расчетов и дашбордов')

@section('content')
    <div class="card" style="margin-bottom:14px;">
        <div class="card-head">Добавить товар</div>
        <div class="card-body">
            <form method="POST" action="{{ route('workbook.products.store') }}">
                @csrf
                <div class="table-wrap">
                    <table>
                        <tbody>
                        <tr>
                            <td>Группа</td><td><input class="input" name="group" required></td>
                            <td>Наименование</td><td><input class="input" name="name" required></td>
                            <td>Артикул</td><td><input class="input" name="sku" required></td>
                        </tr>
                        <tr>
                            <td>Штрих-код</td><td><input class="input" name="barcode"></td>
                            <td>Закупка, ₽</td><td><input class="input" type="number" step="0.01" min="0" name="purchase_price" required></td>
                            <td>Посредник, %</td><td><input class="input" type="number" step="0.01" min="0" max="100" name="agent_percent" value="0"></td>
                        </tr>
                        <tr>
                            <td>Брак, %</td><td><input class="input" type="number" step="0.01" min="0" max="100" name="defect_percent" value="0"></td>
                            <td>Доставка, ₽</td><td><input class="input" type="number" step="0.01" min="0" name="delivery_cost" value="0"></td>
                            <td>Маркировка, ₽</td><td><input class="input" type="number" step="0.01" min="0" name="marking_cost" value="0"></td>
                        </tr>
                        <tr>
                            <td>Хранение, ₽</td><td><input class="input" type="number" step="0.01" min="0" name="storage_cost" value="0"></td>
                            <td>Упаковка, ₽</td><td><input class="input" type="number" step="0.01" min="0" name="packaging_cost" value="0"></td>
                            <td>Остатки</td><td><input class="input" type="number" min="0" name="stock" value="0"></td>
                        </tr>
                        <tr>
                            <td>Цена продажи, ₽</td><td><input class="input" type="number" step="0.01" min="1" name="sale_price" required></td>
                            <td>Скидка, %</td><td><input class="input" type="number" step="0.01" min="0" max="70" name="discount_percent" value="0"></td>
                            <td colspan="2"><button type="submit" class="btn btn-primary">Добавить SKU</button></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">Список товаров</div>
        <div class="card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Группа</th>
                        <th>Товар</th>
                        <th>Артикул</th>
                        <th>Штрих-код</th>
                        <th class="num">Себест. итого, ₽</th>
                        <th class="num">Цена, ₽</th>
                        <th class="num">Скидка, %</th>
                        <th class="num">Остатки</th>
                        <th>Действие</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($products as $index => $product)
                        @php
                            $total = (float) $product['purchase_price'] * (1 + ((float) $product['agent_percent'] / 100) + ((float) $product['defect_percent'] / 100))
                                + (float) $product['delivery_cost']
                                + (float) $product['marking_cost']
                                + (float) $product['storage_cost']
                                + (float) $product['packaging_cost'];
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $product['group'] }}</td>
                            <td>{{ $product['name'] }}</td>
                            <td>{{ $product['sku'] }}</td>
                            <td>{{ $product['barcode'] }}</td>
                            <td class="num">{{ number_format($total, 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $product['sale_price'], 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) $product['discount_percent'], 2, '.', ' ') }}</td>
                            <td class="num">{{ (int) $product['stock'] }}</td>
                            <td>
                                <form method="POST" action="{{ route('workbook.products.delete', ['index' => $index]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10">Добавьте товары, чтобы расчеты появились на остальных страницах.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
