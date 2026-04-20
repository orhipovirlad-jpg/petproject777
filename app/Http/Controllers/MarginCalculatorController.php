<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\CalculationRequest;
use App\Services\AiPricingAdvisorService;
use App\Services\MarketplaceMarginService;
use App\Services\ProductMetricsService;
use App\Services\SubscriptionEntitlementService;
use App\Services\EntrepreneurInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarginCalculatorController extends Controller
{
    public function __construct(
        private readonly MarketplaceMarginService $marginService,
        private readonly AiPricingAdvisorService $aiPricingAdvisor,
        private readonly SubscriptionEntitlementService $entitlements,
        private readonly ProductMetricsService $productMetrics,
        private readonly EntrepreneurInsightsService $entrepreneurInsights
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $authUser = Auth::user();
        $authEmail = $authUser !== null && is_string($authUser->email) && filter_var($authUser->email, FILTER_VALIDATE_EMAIL)
            ? strtolower(trim($authUser->email))
            : null;
        if ($authEmail === null) {
            return redirect()->route('cabinet.show');
        }

        $input = $request->session()->get('calculationInput');
        $email = is_array($input) && isset($input['email']) && is_string($input['email'])
            ? $input['email']
            : $authEmail;
        $ctx = $this->entitlements->contextForEmail($email);

        return view('mvp', [
            'result' => session('result'),
            'aiInsight' => session('aiInsight'),
            'aiPlan' => session('aiPlan'),
            'authEmail' => $authEmail,
            'dailyLimit' => (int) $ctx['daily_calculations'],
            'remainingToday' => $this->entitlements->remainingCalculations($request->ip(), $email),
            'remainingAiToday' => $this->entitlements->remainingAiActions($request->ip(), $email),
            'aiDailyLimit' => (int) $ctx['daily_ai'],
            'pulse' => $this->productMetrics->investorPulse(),
            'subscriptionContext' => $ctx,
            'entrepreneurInsights' => session('entrepreneurInsights'),
        ]);
    }

    public function calculate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'in:wildberries,ozon,kaspi'],
            'sales_model' => ['nullable', 'string', 'in:wb_fbw,wb_fbs,ozon_fbo,ozon_fbs'],
            'email' => ['nullable', 'email', 'max:190'],
            'cost_price' => ['required', 'numeric', 'min:1'],
            'packaging_cost' => ['required', 'numeric', 'min:0'],
            'logistics_cost' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:1'],
            'mp_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'buyout_percent' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'logistics_base_cost' => ['nullable', 'numeric', 'min:0'],
            'storage_daily_cost' => ['nullable', 'numeric', 'min:0'],
            'storage_days' => ['nullable', 'numeric', 'min:0'],
            'extra_cost' => ['nullable', 'numeric', 'min:0'],
            'acquiring_percent' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'last_mile_cost' => ['nullable', 'numeric', 'min:0'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:60'],
            'ad_spend_percent' => ['required', 'numeric', 'min:0', 'max:40'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:30'],
            'returns_percent' => ['required', 'numeric', 'min:0', 'max:25'],
            'desired_margin_percent' => ['required', 'numeric', 'min:1', 'max:50'],
            'planned_units' => ['required', 'integer', 'min:1', 'max:50000'],
            'competitor_price' => ['required', 'numeric', 'min:1'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:70'],
        ]);

        $variablePercent = (float) $validated['commission_percent']
            + (float) $validated['ad_spend_percent']
            + (float) $validated['tax_percent']
            + (float) $validated['returns_percent'];
        $targetPercent = $variablePercent + (float) $validated['desired_margin_percent'];

        $isExcelModel = isset($validated['sales_model']) && is_string($validated['sales_model']) && $validated['sales_model'] !== '';
        if (
            $isExcelModel
            && (!isset($validated['sale_price']) || !is_numeric($validated['sale_price']) || (float) $validated['sale_price'] <= 0)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'sale_price' => 'Для режима Excel укажите цену продажи до скидки.',
                ]);
        }

        if (!$isExcelModel && $variablePercent >= 100) {
            return back()
                ->withInput()
                ->withErrors([
                    'percent_sum' => 'Сумма комиссии, рекламы, налога и возвратов должна быть меньше 100%.',
                ]);
        }

        if (!$isExcelModel && $targetPercent >= 100) {
            return back()
                ->withInput()
                ->withErrors([
                    'target_sum' => 'С учетом желаемой маржи сумма процентов должна быть меньше 100%. Уменьшите маржу или переменные расходы.',
                ]);
        }
        $validated['platform'] = $this->platformFromSalesModel($validated);

        if ($this->entitlements->remainingCalculations($request->ip(), $validated['email'] ?? null) <= 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'limit' => 'Бесплатный лимит на сегодня исчерпан. Для безлимита подключите PRO-план за 790 руб/мес.',
                ]);
        }

        $result = $this->marginService->calculate($validated);
        $entrepreneurInsights = $this->entrepreneurInsights->build($validated, $result);

        CalculationRequest::create([
            'ip_address' => $request->ip(),
            'platform' => $validated['platform'],
            'email' => $validated['email'] ?? null,
            'cost_price' => $validated['cost_price'],
            'packaging_cost' => $validated['packaging_cost'],
            'logistics_cost' => $validated['logistics_cost'],
            'commission_percent' => $validated['commission_percent'],
            'ad_spend_percent' => $validated['ad_spend_percent'],
            'tax_percent' => $validated['tax_percent'],
            'returns_percent' => $validated['returns_percent'],
            'desired_margin_percent' => $validated['desired_margin_percent'],
            'planned_units' => $validated['planned_units'],
            'break_even_price' => $result['break_even_price'],
            'recommended_price' => $result['recommended_price'],
            'net_profit' => $result['net_profit'],
        ]);

        Cache::forget('marginflow_investor_pulse_v2');

        return redirect()
            ->route('mvp.index')
            ->with('result', $result)
            ->with('calculationInput', $validated)
            ->with('aiInsight', null)
            ->with('aiPlan', null)
            ->with('entrepreneurInsights', $entrepreneurInsights)
            ->with('success', 'Расчет готов. Можно запускать карточку товара с понятной ценой.');
    }

    public function calculateAjax(Request $request): JsonResponse
    {
        $validated = $this->validateCalculationPayload($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $result = $this->marginService->calculate($validated);
        $entrepreneurInsights = $this->entrepreneurInsights->build($validated, $result);

        CalculationRequest::create([
            'ip_address' => $request->ip(),
            'platform' => $validated['platform'],
            'email' => $validated['email'] ?? null,
            'cost_price' => $validated['cost_price'],
            'packaging_cost' => $validated['packaging_cost'],
            'logistics_cost' => $validated['logistics_cost'],
            'commission_percent' => $validated['commission_percent'],
            'ad_spend_percent' => $validated['ad_spend_percent'],
            'tax_percent' => $validated['tax_percent'],
            'returns_percent' => $validated['returns_percent'],
            'desired_margin_percent' => $validated['desired_margin_percent'],
            'planned_units' => $validated['planned_units'],
            'break_even_price' => $result['break_even_price'],
            'recommended_price' => $result['recommended_price'],
            'net_profit' => $result['net_profit'],
        ]);

        Cache::forget('marginflow_investor_pulse_v2');

        $email = $validated['email'] ?? null;

        return response()->json([
            'ok' => true,
            'message' => 'Расчет готов.',
            'result' => $result,
            'input' => $validated,
            'remainingToday' => $this->entitlements->remainingCalculations($request->ip(), $email),
            'remainingAiToday' => $this->entitlements->remainingAiActions($request->ip(), $email),
            'subscription' => $this->entitlements->contextForEmail($email),
            'entrepreneurInsights' => $entrepreneurInsights,
            'pulse' => $this->productMetrics->investorPulse(),
        ]);
    }

    public function comparePlatforms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'total_unit_cost' => ['required', 'numeric', 'min:1'],
            'sale_price' => ['required', 'numeric', 'min:1'],
            'planned_units' => ['nullable', 'integer', 'min:1', 'max:50000'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:70'],
        ]);

        $payload = [
            'total_unit_cost' => (float) $validated['total_unit_cost'],
            'sale_price' => (float) $validated['sale_price'],
            'planned_units' => isset($validated['planned_units']) ? (int) $validated['planned_units'] : 300,
            'discount_percent' => isset($validated['discount_percent']) ? (float) $validated['discount_percent'] : 0,
        ];

        $data = $this->marginService->comparePlatforms($payload);

        return response()->json([
            'ok' => true,
            'compare' => $data,
        ]);
    }

    public function exportCsv(Request $request): JsonResponse|StreamedResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'input' => ['required', 'array'],
            'result' => ['required', 'array'],
        ]);

        $ctx = $this->entitlements->contextForEmail($validated['email']);
        if (!$ctx['export_csv']) {
            return response()->json([
                'ok' => false,
                'message' => 'Экспорт CSV доступен на PRO-плане.',
            ], 422);
        }

        $input = $validated['input'];
        $result = $validated['result'];

        $filename = 'marginflow-export-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($input, $result): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Поле', 'Значение']);
            fputcsv($out, ['Площадка', (string) ($input['platform'] ?? '')]);
            fputcsv($out, ['План продаж, шт/мес', (string) ($input['planned_units'] ?? '')]);
            fputcsv($out, ['Цена безубыточности', (string) ($result['break_even_price'] ?? '').' ₽']);
            fputcsv($out, ['Рекомендованная цена', (string) ($result['recommended_price'] ?? '').' ₽']);
            fputcsv($out, ['Прибыль / шт (после скидки)', (string) ($result['net_profit'] ?? '').' ₽']);
            fputcsv($out, ['Месячная прибыль (план)', (string) ($result['monthly_profit'] ?? '').' ₽']);
            fputcsv($out, ['Маржа %', (string) ($result['margin_percent'] ?? '')]);
            fputcsv($out, ['Риск', (string) ($result['risk_level'] ?? '')]);
            fputcsv($out, ['Отклонение от конкурентов %', (string) ($result['price_delta_vs_competitor_percent'] ?? '')]);
            fputcsv($out, ['Красная линия скидки от реком. цены, %', (string) ($result['max_safe_discount_percent'] ?? '')]);
            foreach ($result['stress_tests'] ?? [] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                fputcsv($out, [
                    'Стресс: '.(string) ($row['title'] ?? ''),
                    'прибыль/шт '.(string) ($row['net_profit'] ?? ''),
                    'маржа '.(string) ($row['margin_percent'] ?? '').'%',
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function generateAiInsight(Request $request): RedirectResponse
    {
        $result = $request->session()->get('result');
        $input = $request->session()->get('calculationInput');

        if (!is_array($result) || !is_array($input)) {
            return redirect()
                ->route('mvp.index')
                ->withErrors(['ai' => 'Сначала выполните расчет, затем запросите AI-совет.']);
        }

        if ($this->entitlements->remainingAiActions($request->ip(), $input['email'] ?? null) <= 0) {
            return redirect()
                ->route('mvp.index')
                ->withErrors(['ai_limit' => 'Лимит AI-запросов на сегодня исчерпан. Подключите PRO для безлимита.']);
        }

        $insight = $this->aiPricingAdvisor->buildInsight($input, $result);
        $this->storeAiResult($request, 'pricing_advice', $input, $insight);

        return redirect()
            ->route('mvp.index')
            ->with('result', $result)
            ->with('calculationInput', $input)
            ->with('aiInsight', $insight)
            ->with('aiPlan', $request->session()->get('aiPlan'))
            ->with('success', 'AI-совет сформирован.');
    }

    public function generateAiInsightAjax(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'input' => ['required', 'array'],
            'result' => ['required', 'array'],
        ]);

        $input = (array) $payload['input'];
        $result = (array) $payload['result'];

        if ($this->entitlements->remainingAiActions($request->ip(), $input['email'] ?? null) <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Лимит AI-запросов на сегодня исчерпан. Подключите PRO для безлимита.',
            ], 422);
        }

        $insight = $this->aiPricingAdvisor->buildInsight($input, $result);
        $this->storeAiResult($request, 'pricing_advice', $input, $insight);

        return response()->json([
            'ok' => true,
            'message' => 'AI-совет сформирован.',
            'aiInsight' => $insight,
            'remainingAiToday' => $this->entitlements->remainingAiActions($request->ip(), $input['email'] ?? null),
            'subscription' => $this->entitlements->contextForEmail($input['email'] ?? null),
        ]);
    }

    public function generateAiLaunchPlan(Request $request): RedirectResponse
    {
        $result = $request->session()->get('result');
        $input = $request->session()->get('calculationInput');

        if (!is_array($result) || !is_array($input)) {
            return redirect()
                ->route('mvp.index')
                ->withErrors(['ai' => 'Сначала выполните расчет, затем запросите AI-план.']);
        }

        if ($this->entitlements->remainingAiActions($request->ip(), $input['email'] ?? null) <= 0) {
            return redirect()
                ->route('mvp.index')
                ->withErrors(['ai_limit' => 'Лимит AI-запросов на сегодня исчерпан. Подключите PRO для безлимита.']);
        }

        $plan = $this->aiPricingAdvisor->buildLaunchPlan($input, $result);
        $this->storeAiResult($request, 'launch_plan', $input, $plan);

        return redirect()
            ->route('mvp.index')
            ->with('result', $result)
            ->with('calculationInput', $input)
            ->with('aiInsight', $request->session()->get('aiInsight'))
            ->with('aiPlan', $plan)
            ->with('success', 'AI-план на 7 дней сформирован.');
    }

    public function generateAiLaunchPlanAjax(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'input' => ['required', 'array'],
            'result' => ['required', 'array'],
        ]);

        $input = (array) $payload['input'];
        $result = (array) $payload['result'];

        if ($this->entitlements->remainingAiActions($request->ip(), $input['email'] ?? null) <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Лимит AI-запросов на сегодня исчерпан. Подключите PRO для безлимита.',
            ], 422);
        }

        $plan = $this->aiPricingAdvisor->buildLaunchPlan($input, $result);
        $this->storeAiResult($request, 'launch_plan', $input, $plan);

        return response()->json([
            'ok' => true,
            'message' => 'AI-план на 7 дней сформирован.',
            'aiPlan' => $plan,
            'remainingAiToday' => $this->entitlements->remainingAiActions($request->ip(), $input['email'] ?? null),
            'subscription' => $this->entitlements->contextForEmail($input['email'] ?? null),
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $output
     */
    private function storeAiResult(Request $request, string $type, array $input, array $output): void
    {
        AiInsight::create([
            'type' => $type,
            'source' => (string) ($output['source'] ?? 'fallback'),
            'email' => isset($input['email']) && is_string($input['email']) ? $input['email'] : null,
            'ip_address' => $request->ip(),
            'status' => 'success',
            'input_payload' => $input,
            'output_payload' => $output,
        ]);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function validateCalculationPayload(Request $request): array|JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'in:wildberries,ozon,kaspi'],
            'sales_model' => ['nullable', 'string', 'in:wb_fbw,wb_fbs,ozon_fbo,ozon_fbs'],
            'email' => ['nullable', 'email', 'max:190'],
            'cost_price' => ['required', 'numeric', 'min:1'],
            'packaging_cost' => ['required', 'numeric', 'min:0'],
            'logistics_cost' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:1'],
            'mp_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'buyout_percent' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'logistics_base_cost' => ['nullable', 'numeric', 'min:0'],
            'storage_daily_cost' => ['nullable', 'numeric', 'min:0'],
            'storage_days' => ['nullable', 'numeric', 'min:0'],
            'extra_cost' => ['nullable', 'numeric', 'min:0'],
            'acquiring_percent' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'last_mile_cost' => ['nullable', 'numeric', 'min:0'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:60'],
            'ad_spend_percent' => ['required', 'numeric', 'min:0', 'max:40'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:30'],
            'returns_percent' => ['required', 'numeric', 'min:0', 'max:25'],
            'desired_margin_percent' => ['required', 'numeric', 'min:1', 'max:50'],
            'planned_units' => ['required', 'integer', 'min:1', 'max:50000'],
            'competitor_price' => ['required', 'numeric', 'min:1'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:70'],
        ]);

        $variablePercent = (float) $validated['commission_percent']
            + (float) $validated['ad_spend_percent']
            + (float) $validated['tax_percent']
            + (float) $validated['returns_percent'];
        $targetPercent = $variablePercent + (float) $validated['desired_margin_percent'];

        $isExcelModel = isset($validated['sales_model']) && is_string($validated['sales_model']) && $validated['sales_model'] !== '';
        if (
            $isExcelModel
            && (!isset($validated['sale_price']) || !is_numeric($validated['sale_price']) || (float) $validated['sale_price'] <= 0)
        ) {
            return response()->json([
                'ok' => false,
                'message' => 'Для режима Excel укажите цену продажи до скидки.',
            ], 422);
        }

        if (!$isExcelModel && $variablePercent >= 100) {
            return response()->json([
                'ok' => false,
                'message' => 'Сумма комиссии, рекламы, налога и возвратов должна быть меньше 100%.',
            ], 422);
        }

        if (!$isExcelModel && $targetPercent >= 100) {
            return response()->json([
                'ok' => false,
                'message' => 'С учетом желаемой маржи сумма процентов должна быть меньше 100%.',
            ], 422);
        }

        $validated['platform'] = $this->platformFromSalesModel($validated);

        if ($this->entitlements->remainingCalculations($request->ip(), $validated['email'] ?? null) <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Бесплатный лимит на сегодня исчерпан. Для безлимита подключите PRO-план за 790 руб/мес.',
            ], 422);
        }

        return $validated;
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function platformFromSalesModel(array $validated): string
    {
        $model = isset($validated['sales_model']) && is_string($validated['sales_model']) ? $validated['sales_model'] : null;
        if ($model === null || $model === '') {
            return (string) ($validated['platform'] ?? 'wildberries');
        }

        return str_starts_with($model, 'ozon') ? 'ozon' : 'wildberries';
    }
}
