<?php

namespace App\Http\Controllers;

use App\Models\WorkbookModelSetting;
use App\Models\WorkbookProduct;
use App\Services\MarketplaceMarginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkbookController extends Controller
{
    public function __construct(
        private readonly MarketplaceMarginService $marginService
    ) {
    }

    public function products(Request $request): View
    {
        return view('workbook.products', [
            'products' => $this->productsFromDatabase(),
            'menu' => $this->menuItems(),
        ]);
    }

    public function guide(): View
    {
        return view('workbook.guide', [
            'menu' => $this->menuItems(),
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:190'],
            'sku' => ['required', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'agent_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'defect_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'delivery_cost' => ['nullable', 'numeric', 'min:0'],
            'marking_cost' => ['nullable', 'numeric', 'min:0'],
            'storage_cost' => ['nullable', 'numeric', 'min:0'],
            'packaging_cost' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:70'],
        ]);

        $normalized = $this->normalizeProduct($validated);
        WorkbookProduct::query()->create([
            ...$normalized,
            'user_id' => $this->authUserId(),
        ]);

        return redirect()->route('workbook.products')->with('success', 'Товар добавлен в таблицу.');
    }

    public function deleteProduct(Request $request, int $index): RedirectResponse
    {
        $product = WorkbookProduct::query()
            ->where('user_id', $this->authUserId())
            ->orderBy('id')
            ->skip($index)
            ->first();
        if ($product === null) {
            return redirect()->route('workbook.products')->withErrors(['product' => 'Товар не найден.']);
        }

        $product->delete();

        return redirect()->route('workbook.products')->with('success', 'Товар удален.');
    }

    public function wbFbw(Request $request): View
    {
        return $this->renderModelPage($request, 'wb_fbw', 'WB FBW');
    }

    public function wbFbs(Request $request): View
    {
        return $this->renderModelPage($request, 'wb_fbs', 'WB FBS');
    }

    public function ozonFbo(Request $request): View
    {
        return $this->renderModelPage($request, 'ozon_fbo', 'Ozon FBO');
    }

    public function ozonFbs(Request $request): View
    {
        return $this->renderModelPage($request, 'ozon_fbs', 'Ozon FBS');
    }

    public function updateModelSettings(Request $request, string $model): RedirectResponse
    {
        if (!in_array($model, ['wb_fbw', 'wb_fbs', 'ozon_fbo', 'ozon_fbs'], true)) {
            return back()->withErrors(['model' => 'Неизвестная модель.']);
        }

        $validated = $request->validate([
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:60'],
            'buyout_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'logistics_base_cost' => ['required', 'numeric', 'min:0'],
            'storage_daily_cost' => ['required', 'numeric', 'min:0'],
            'storage_days' => ['required', 'numeric', 'min:0'],
            'extra_cost' => ['required', 'numeric', 'min:0'],
            'ad_spend_percent' => ['required', 'numeric', 'min:0', 'max:40'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:30'],
            'mp_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'acquiring_percent' => ['required', 'numeric', 'min:0', 'max:10'],
            'last_mile_cost' => ['required', 'numeric', 'min:0'],
        ]);

        WorkbookModelSetting::query()->updateOrCreate(
            [
                'user_id' => $this->authUserId(),
                'model' => $model,
            ],
            $validated
        );

        return back()->with('success', 'Настройки модели обновлены.');
    }

    public function compare(Request $request): View
    {
        $products = $this->productsFromDatabase();
        $settings = $this->settingsFromDatabase();
        $rows = [];
        foreach ($products as $product) {
            $entry = [
                'group' => $product['group'],
                'name' => $product['name'],
                'sku' => $product['sku'],
                'cost_total' => $this->totalCost($product),
            ];
            foreach (['wb_fbw', 'wb_fbs', 'ozon_fbo', 'ozon_fbs'] as $model) {
                $calc = $this->marginService->calculate($this->payloadFor($product, $model, $settings[$model]));
                $entry[$model] = [
                    'net_profit' => (float) $calc['net_profit'],
                    'margin_percent' => (float) $calc['margin_percent'],
                    'roi_percent' => (float) $calc['roi_percent'],
                ];
            }
            $rows[] = $entry;
        }

        return view('workbook.compare', [
            'rows' => $rows,
            'menu' => $this->menuItems(),
        ]);
    }

    public function dashboardModels(Request $request): View
    {
        $metric = (string) $request->query('metric', 'margin_percent');
        if (!in_array($metric, ['net_profit', 'margin_percent', 'roi_percent'], true)) {
            $metric = 'margin_percent';
        }

        $products = $this->productsFromDatabase();
        $settings = $this->settingsFromDatabase();
        $rows = [];
        foreach ($products as $product) {
            $modelData = [];
            foreach (['wb_fbw', 'wb_fbs', 'ozon_fbo', 'ozon_fbs'] as $model) {
                $calc = $this->marginService->calculate($this->payloadFor($product, $model, $settings[$model]));
                $modelData[$model] = (float) $calc[$metric];
            }
            $rows[] = [
                'name' => $product['name'],
                'model_data' => $modelData,
            ];
        }

        return view('workbook.dashboard-models', [
            'metric' => $metric,
            'rows' => $rows,
            'menu' => $this->menuItems(),
        ]);
    }

    public function dashboardTop(Request $request): View
    {
        $metric = (string) $request->query('metric', 'margin_percent');
        if (!in_array($metric, ['net_profit', 'margin_percent', 'roi_percent'], true)) {
            $metric = 'margin_percent';
        }
        $limit = max(1, min(50, (int) $request->query('limit', 5)));

        $products = $this->productsFromDatabase();
        $settings = $this->settingsFromDatabase();
        $tops = [];
        foreach (['wb_fbw', 'wb_fbs', 'ozon_fbo', 'ozon_fbs'] as $model) {
            $rows = [];
            foreach ($products as $product) {
                $calc = $this->marginService->calculate($this->payloadFor($product, $model, $settings[$model]));
                $rows[] = [
                    'name' => $product['name'],
                    'value' => (float) $calc[$metric],
                ];
            }
            usort($rows, static fn (array $a, array $b): int => $b['value'] <=> $a['value']);
            $tops[$model] = array_slice($rows, 0, $limit);
        }

        return view('workbook.dashboard-top', [
            'metric' => $metric,
            'limit' => $limit,
            'tops' => $tops,
            'menu' => $this->menuItems(),
        ]);
    }

    public function dashboard(Request $request): View
    {
        $products = $this->productsFromDatabase();
        $settings = $this->settingsFromDatabase();

        $summary = [
            'products_count' => count($products),
            'avg_margin' => [],
            'avg_profit' => [],
        ];
        foreach (['wb_fbw', 'wb_fbs', 'ozon_fbo', 'ozon_fbs'] as $model) {
            $margins = [];
            $profits = [];
            foreach ($products as $product) {
                $calc = $this->marginService->calculate($this->payloadFor($product, $model, $settings[$model]));
                $margins[] = (float) $calc['margin_percent'];
                $profits[] = (float) $calc['net_profit'];
            }
            $summary['avg_margin'][$model] = count($margins) ? array_sum($margins) / count($margins) : 0.0;
            $summary['avg_profit'][$model] = count($profits) ? array_sum($profits) / count($profits) : 0.0;
        }

        return view('workbook.dashboard', [
            'summary' => $summary,
            'menu' => $this->menuItems(),
        ]);
    }

    public function autopilot(Request $request): View
    {
        $products = $this->productsFromDatabase();
        $settings = $this->settingsFromDatabase();

        $targetMargin = max(5.0, min(60.0, (float) $request->query('target_margin', 20)));
        $criticalMargin = max(0.0, min(40.0, (float) $request->query('critical_margin', 10)));
        $warningMargin = max($criticalMargin, min(60.0, (float) $request->query('warning_margin', 18)));
        $plannedUnits = max(1, min(50000, (int) $request->query('planned_units', 300)));

        $rows = [];
        foreach ($products as $product) {
            $bestModel = null;
            $bestCalc = null;
            foreach (['wb_fbw', 'wb_fbs', 'ozon_fbo', 'ozon_fbs'] as $model) {
                $calc = $this->marginService->calculate($this->payloadFor($product, $model, $settings[$model], $plannedUnits));
                if ($bestCalc === null || (float) $calc['net_profit'] > (float) $bestCalc['net_profit']) {
                    $bestCalc = $calc;
                    $bestModel = $model;
                }
            }
            if ($bestModel === null || $bestCalc === null) {
                continue;
            }

            $currentMargin = (float) $bestCalc['margin_percent'];
            $currentDiscount = (float) ($product['discount_percent'] ?? 0);
            $currentPrice = (float) ($product['sale_price'] ?? 0);
            $currentAfterDiscount = (float) $bestCalc['sale_price_after_discount'];
            $breakEvenPrice = (float) $bestCalc['break_even_price'];
            $safeDiscount = (float) ($bestCalc['max_safe_discount_percent'] ?? $currentDiscount);
            $recommendedPrice = $this->recommendedPriceForTargetMargin(
                $currentAfterDiscount,
                $currentMargin,
                $targetMargin
            );
            $recommendedDiscount = $this->recommendedDiscountForTargetMargin(
                $currentPrice,
                $recommendedPrice
            );

            $status = 'ok';
            if ($currentMargin < $criticalMargin || (float) $bestCalc['net_profit'] <= 0) {
                $status = 'critical';
            } elseif ($currentMargin < $warningMargin) {
                $status = 'warning';
            }

            $action = $this->buildAutopilotAction(
                $status,
                $currentMargin,
                $targetMargin,
                $recommendedPrice,
                $recommendedDiscount,
                $safeDiscount
            );

            $rows[] = [
                'group' => $product['group'],
                'name' => $product['name'],
                'sku' => $product['sku'],
                'model' => $bestModel,
                'status' => $status,
                'current_margin' => round($currentMargin, 2),
                'target_margin' => round($targetMargin, 2),
                'current_price' => round($currentPrice, 2),
                'current_discount' => round($currentDiscount, 2),
                'recommended_price' => round($recommendedPrice, 2),
                'recommended_discount' => round($recommendedDiscount, 2),
                'safe_discount' => round($safeDiscount, 2),
                'current_profit' => round((float) $bestCalc['net_profit'], 2),
                'monthly_profit' => round((float) $bestCalc['monthly_profit'], 2),
                'action' => $action,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            $order = ['critical' => 0, 'warning' => 1, 'ok' => 2];
            $byStatus = ($order[$a['status']] ?? 99) <=> ($order[$b['status']] ?? 99);
            if ($byStatus !== 0) {
                return $byStatus;
            }

            return $a['current_margin'] <=> $b['current_margin'];
        });

        return view('workbook.autopilot', [
            'rows' => $rows,
            'targetMargin' => $targetMargin,
            'criticalMargin' => $criticalMargin,
            'warningMargin' => $warningMargin,
            'plannedUnits' => $plannedUnits,
            'menu' => $this->menuItems(),
        ]);
    }

    private function renderModelPage(Request $request, string $model, string $title): View
    {
        $products = $this->productsFromDatabase();
        $settings = $this->settingsFromDatabase();
        $rows = [];
        foreach ($products as $product) {
            $calc = $this->marginService->calculate($this->payloadFor($product, $model, $settings[$model]));
            $rows[] = [
                'product' => $product,
                'cost_total' => $this->totalCost($product),
                'calc' => $calc,
            ];
        }

        return view('workbook.model', [
            'title' => $title,
            'model' => $model,
            'rows' => $rows,
            'settings' => $settings[$model],
            'menu' => $this->menuItems(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productsFromDatabase(): array
    {
        $products = WorkbookProduct::query()
            ->where('user_id', $this->authUserId())
            ->orderBy('id')
            ->get()
            ->map(fn (WorkbookProduct $row): array => $this->normalizeProduct($row->toArray()))
            ->all();
        if ($products === []) {
            return [$this->normalizeProduct([
                'group' => 'Одежда',
                'name' => 'Куртка демо',
                'sku' => 'SKU-001',
                'barcode' => '000000000001',
                'purchase_price' => 2400,
                'agent_percent' => 2,
                'defect_percent' => 3,
                'delivery_cost' => 100,
                'marking_cost' => 20,
                'storage_cost' => 15,
                'packaging_cost' => 45,
                'stock' => 50,
                'sale_price' => 6800,
                'discount_percent' => 25,
            ])];
        }

        return array_values(array_filter($products, static fn (mixed $row): bool => is_array($row)));
    }

    /**
     * @return array<string, array<string, float>>
     */
    private function settingsFromDatabase(): array
    {
        $default = [
            'wb_fbw' => [
                'commission_percent' => 19.0,
                'buyout_percent' => 95.0,
                'logistics_base_cost' => 19.0,
                'storage_daily_cost' => 0.09,
                'storage_days' => 60.0,
                'extra_cost' => 15.0,
                'ad_spend_percent' => 10.0,
                'tax_percent' => 7.0,
                'mp_discount_percent' => 15.0,
                'acquiring_percent' => 0.0,
                'last_mile_cost' => 0.0,
            ],
            'wb_fbs' => [
                'commission_percent' => 19.0,
                'buyout_percent' => 95.0,
                'logistics_base_cost' => 19.0,
                'storage_daily_cost' => 0.0,
                'storage_days' => 0.0,
                'extra_cost' => 15.0,
                'ad_spend_percent' => 10.0,
                'tax_percent' => 7.0,
                'mp_discount_percent' => 15.0,
                'acquiring_percent' => 0.0,
                'last_mile_cost' => 0.0,
            ],
            'ozon_fbo' => [
                'commission_percent' => 17.0,
                'buyout_percent' => 95.0,
                'logistics_base_cost' => 19.0,
                'storage_daily_cost' => 0.0,
                'storage_days' => 0.0,
                'extra_cost' => 35.0,
                'ad_spend_percent' => 10.0,
                'tax_percent' => 7.0,
                'mp_discount_percent' => 0.0,
                'acquiring_percent' => 1.5,
                'last_mile_cost' => 0.0,
            ],
            'ozon_fbs' => [
                'commission_percent' => 17.0,
                'buyout_percent' => 95.0,
                'logistics_base_cost' => 19.0,
                'storage_daily_cost' => 0.0,
                'storage_days' => 0.0,
                'extra_cost' => 60.0,
                'ad_spend_percent' => 10.0,
                'tax_percent' => 7.0,
                'mp_discount_percent' => 0.0,
                'acquiring_percent' => 1.5,
                'last_mile_cost' => 20.0,
            ],
        ];

        $stored = WorkbookModelSetting::query()
            ->where('user_id', $this->authUserId())
            ->get()
            ->keyBy('model')
            ->map(static fn (WorkbookModelSetting $row): array => [
                'commission_percent' => (float) $row->commission_percent,
                'buyout_percent' => (float) $row->buyout_percent,
                'logistics_base_cost' => (float) $row->logistics_base_cost,
                'storage_daily_cost' => (float) $row->storage_daily_cost,
                'storage_days' => (float) $row->storage_days,
                'extra_cost' => (float) $row->extra_cost,
                'ad_spend_percent' => (float) $row->ad_spend_percent,
                'tax_percent' => (float) $row->tax_percent,
                'mp_discount_percent' => (float) $row->mp_discount_percent,
                'acquiring_percent' => (float) $row->acquiring_percent,
                'last_mile_cost' => (float) $row->last_mile_cost,
            ])
            ->all();

        foreach ($default as $model => $values) {
            $row = $stored[$model] ?? null;
            if (!is_array($row)) {
                $stored[$model] = $values;
                continue;
            }
            $stored[$model] = array_merge($values, $row);
        }

        return $stored;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeProduct(array $input): array
    {
        return [
            'group' => trim((string) ($input['group'] ?? '')),
            'name' => trim((string) ($input['name'] ?? '')),
            'sku' => trim((string) ($input['sku'] ?? '')),
            'barcode' => trim((string) ($input['barcode'] ?? '')),
            'purchase_price' => (float) ($input['purchase_price'] ?? 0),
            'agent_percent' => (float) ($input['agent_percent'] ?? 0),
            'defect_percent' => (float) ($input['defect_percent'] ?? 0),
            'delivery_cost' => (float) ($input['delivery_cost'] ?? 0),
            'marking_cost' => (float) ($input['marking_cost'] ?? 0),
            'storage_cost' => (float) ($input['storage_cost'] ?? 0),
            'packaging_cost' => (float) ($input['packaging_cost'] ?? 0),
            'stock' => (int) ($input['stock'] ?? 0),
            'sale_price' => (float) ($input['sale_price'] ?? 0),
            'discount_percent' => (float) ($input['discount_percent'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $product
     */
    private function totalCost(array $product): float
    {
        $purchase = (float) ($product['purchase_price'] ?? 0);
        $agent = (float) ($product['agent_percent'] ?? 0) / 100;
        $defect = (float) ($product['defect_percent'] ?? 0) / 100;
        $delivery = (float) ($product['delivery_cost'] ?? 0);
        $marking = (float) ($product['marking_cost'] ?? 0);
        $storage = (float) ($product['storage_cost'] ?? 0);
        $packaging = (float) ($product['packaging_cost'] ?? 0);

        return $purchase * (1 + $agent + $defect) + $delivery + $marking + $storage + $packaging;
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function payloadFor(array $product, string $model, array $settings, int $plannedUnits = 300): array
    {
        $salePrice = (float) ($product['sale_price'] ?? 0);
        $competitorPrice = $salePrice > 0 ? $salePrice : 1.0;
        $totalCost = $this->totalCost($product);

        return [
            'sales_model' => $model,
            'platform' => str_starts_with($model, 'ozon') ? 'ozon' : 'wildberries',
            'cost_price' => $totalCost,
            'packaging_cost' => 0.0,
            'logistics_cost' => 0.0,
            'sale_price' => $salePrice,
            'discount_percent' => (float) ($product['discount_percent'] ?? 0),
            'commission_percent' => (float) ($settings['commission_percent'] ?? 0),
            'buyout_percent' => (float) ($settings['buyout_percent'] ?? 95),
            'logistics_base_cost' => (float) ($settings['logistics_base_cost'] ?? 0),
            'storage_daily_cost' => (float) ($settings['storage_daily_cost'] ?? 0),
            'storage_days' => (float) ($settings['storage_days'] ?? 0),
            'extra_cost' => (float) ($settings['extra_cost'] ?? 0),
            'ad_spend_percent' => (float) ($settings['ad_spend_percent'] ?? 0),
            'tax_percent' => (float) ($settings['tax_percent'] ?? 7),
            'mp_discount_percent' => (float) ($settings['mp_discount_percent'] ?? 0),
            'acquiring_percent' => (float) ($settings['acquiring_percent'] ?? 0),
            'last_mile_cost' => (float) ($settings['last_mile_cost'] ?? 0),
            'planned_units' => $plannedUnits,
            'competitor_price' => $competitorPrice,
            'returns_percent' => 0.0,
            'desired_margin_percent' => 20.0,
            'email' => null,
        ];
    }

    /**
     * @return list<array{key:string,title:string,route:string}>
     */
    private function menuItems(): array
    {
        return [
            ['key' => 'guide', 'title' => 'Инструкция', 'route' => 'workbook.guide'],
            ['key' => 'products', 'title' => 'Товары', 'route' => 'workbook.products'],
            ['key' => 'wb-fbw', 'title' => 'WB FBW', 'route' => 'workbook.wb-fbw'],
            ['key' => 'wb-fbs', 'title' => 'WB FBS', 'route' => 'workbook.wb-fbs'],
            ['key' => 'ozon-fbo', 'title' => 'Ozon FBO', 'route' => 'workbook.ozon-fbo'],
            ['key' => 'ozon-fbs', 'title' => 'Ozon FBS', 'route' => 'workbook.ozon-fbs'],
            ['key' => 'compare', 'title' => 'Сравнить', 'route' => 'workbook.compare'],
            ['key' => 'dashboard-models', 'title' => 'Дашборд. Сравнить модели продаж', 'route' => 'workbook.dashboard-models'],
            ['key' => 'dashboard-top', 'title' => 'Дашборд. ТОП товаров в разрезе', 'route' => 'workbook.dashboard-top'],
            ['key' => 'dashboard', 'title' => 'Дашборд', 'route' => 'workbook.dashboard'],
            ['key' => 'autopilot', 'title' => 'Автопилот', 'route' => 'workbook.autopilot'],
        ];
    }

    private function recommendedPriceForTargetMargin(float $salePriceAfterDiscount, float $currentMargin, float $targetMargin): float
    {
        if ($salePriceAfterDiscount <= 0) {
            return 0.0;
        }
        $currentRetention = max(0.01, 1 - ($currentMargin / 100));
        $targetRetention = max(0.01, 1 - ($targetMargin / 100));
        $costBase = $salePriceAfterDiscount * $currentRetention;

        return $costBase / $targetRetention;
    }

    private function recommendedDiscountForTargetMargin(float $currentPriceBeforeDiscount, float $recommendedPriceAfterDiscount): float
    {
        if ($currentPriceBeforeDiscount <= 0) {
            return 0.0;
        }

        return max(0.0, min(70.0, (1 - ($recommendedPriceAfterDiscount / $currentPriceBeforeDiscount)) * 100));
    }

    private function buildAutopilotAction(
        string $status,
        float $currentMargin,
        float $targetMargin,
        float $recommendedPrice,
        float $recommendedDiscount,
        float $safeDiscount
    ): string {
        if ($status === 'critical') {
            return sprintf(
                'Критично: маржа %.1f%%. Поднимите цену после скидки до %.2f ₽ и удерживайте скидку не выше %.1f%%.',
                $currentMargin,
                $recommendedPrice,
                min($recommendedDiscount, $safeDiscount)
            );
        }
        if ($status === 'warning') {
            return sprintf(
                'Внимание: маржа %.1f%% ниже цели %.1f%%. Рекомендуемая цена после скидки %.2f ₽.',
                $currentMargin,
                $targetMargin,
                $recommendedPrice
            );
        }

        return sprintf(
            'ОК: маржа %.1f%%. Текущая стратегия рабочая, безопасная скидка до %.1f%%.',
            $currentMargin,
            $safeDiscount
        );
    }

    private function authUserId(): int
    {
        /** @var int|null $id */
        $id = Auth::id();
        if ($id === null) {
            abort(401);
        }

        return $id;
    }
}
