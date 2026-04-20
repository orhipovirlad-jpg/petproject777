<?php

namespace App\Http\Controllers;

use App\Models\ProPayment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\YooKassaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProSubscriptionController extends Controller
{
    private const PRO_PRICE = 790.00;

    public function __construct(
        private readonly YooKassaService $yooKassaService
    ) {
    }

    public function show(): View
    {
        $plan = SubscriptionPlan::query()->where('slug', 'pro')->first();

        return view('pro', [
            'price' => $plan !== null ? (float) $plan->price_monthly : self::PRO_PRICE,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'max:190',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!is_string($value)) {
                        $fail('Укажите корректный email.');

                        return;
                    }
                    if (strtolower(trim($value)) === 'test') {
                        return;
                    }
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail('Укажите корректный email.');
                    }
                },
            ],
        ]);

        if (strtolower(trim($validated['email'])) === 'test') {
            if (!$this->testActivationAllowed()) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'email' => 'Тестовая активация доступна только локально или при APP_DEBUG=true.',
                    ]);
            }

            return $this->activateTestSubscription($request);
        }

        if (!$this->isConfigured()) {
            return back()
                ->withInput()
                ->withErrors([
                    'payment' => 'ЮKassa не настроена. Укажите YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY в .env.',
                ]);
        }

        $idempotenceKey = (string) Str::uuid();

        $payload = [
            'amount' => [
                'value' => number_format(self::PRO_PRICE, 2, '.', ''),
                'currency' => 'RUB',
            ],
            'capture' => true,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => route('pro.return'),
            ],
            'description' => 'Подписка MarginFlow PRO на 1 месяц',
            'metadata' => [
                'email' => $validated['email'],
                'plan' => 'pro_monthly',
            ],
            'receipt' => [
                'customer' => [
                    'email' => $validated['email'],
                ],
                'items' => [[
                    'description' => 'MarginFlow PRO, 1 месяц',
                    'quantity' => '1.00',
                    'amount' => [
                        'value' => number_format(self::PRO_PRICE, 2, '.', ''),
                        'currency' => 'RUB',
                    ],
                    'vat_code' => 1,
                    'payment_mode' => 'full_payment',
                    'payment_subject' => 'service',
                ]],
            ],
        ];

        $response = $this->yooKassaService->createPayment($payload, $idempotenceKey);
        $json = $response->json();

        ProPayment::create([
            'email' => $validated['email'],
            'amount' => self::PRO_PRICE,
            'currency' => 'RUB',
            'provider' => 'yookassa',
            'provider_payment_id' => $json['id'] ?? null,
            'idempotence_key' => $idempotenceKey,
            'status' => $json['status'] ?? 'failed',
            'confirmation_url' => $json['confirmation']['confirmation_url'] ?? null,
            'raw_response' => $json,
        ]);

        if (!$response->successful() || empty($json['confirmation']['confirmation_url'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'payment' => 'Не удалось создать платеж. Проверьте ключи ЮKassa и попробуйте снова.',
                ]);
        }

        return redirect()->away($json['confirmation']['confirmation_url']);
    }

    public function handleReturn(Request $request): RedirectResponse
    {
        $paymentId = (string) $request->query('paymentId', '');

        if ($paymentId === '') {
            return redirect()->route('pro.show')
                ->withErrors(['payment' => 'Платеж не найден.']);
        }

        $payment = ProPayment::query()
            ->where('provider_payment_id', $paymentId)
            ->first();

        if ($payment === null) {
            return redirect()->route('pro.show')
                ->withErrors(['payment' => 'Платеж не найден в системе.']);
        }

        $response = $this->yooKassaService->getPayment($paymentId);
        $json = $response->json();
        $status = (string) ($json['status'] ?? 'unknown');

        $payment->update([
            'status' => $status,
            'raw_response' => $json,
            'paid_at' => $status === 'succeeded' ? now() : $payment->paid_at,
        ]);

        if ($status !== 'succeeded') {
            return redirect()->route('pro.show')
                ->withErrors(['payment' => 'Оплата не завершена. Статус: '.$status]);
        }

        $this->activateSubscription($payment);
        $targetRoute = Auth::check() ? 'workbook.products' : 'cabinet.show';

        return redirect()
            ->route($targetRoute)
            ->with('calculationInput', ['email' => $payment->email])
            ->withInput(['email' => $payment->email])
            ->with('success', 'PRO активирован. Безлимитные расчеты подключены до '.now()->addMonth()->format('d.m.Y'));
    }

    private function activateSubscription(ProPayment $payment): void
    {
        $plan = SubscriptionPlan::query()->where('slug', 'pro')->firstOrFail();

        $current = Subscription::query()
            ->where('email', $payment->email)
            ->first();

        $startsAt = now();
        $endsAt = now()->addMonth();

        if ($current !== null && $current->ends_at !== null && $current->ends_at->isFuture()) {
            $startsAt = $current->ends_at;
            $endsAt = $current->ends_at->copy()->addMonth();
        }

        Subscription::query()->updateOrCreate(
            ['email' => $payment->email],
            [
                'subscription_plan_id' => $plan->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'active',
                'last_payment_id' => $payment->id,
            ]
        );

        Cache::forget('marginflow_investor_pulse_v2');
    }

    private function isConfigured(): bool
    {
        return (string) config('services.yookassa.shop_id') !== ''
            && (string) config('services.yookassa.secret_key') !== '';
    }

    private function testActivationAllowed(): bool
    {
        return app()->environment('local') || (bool) config('app.debug');
    }

    private function activateTestSubscription(Request $request): RedirectResponse
    {
        $email = 'test@example.com';

        $payment = ProPayment::create([
            'email' => $email,
            'amount' => 0,
            'currency' => 'RUB',
            'provider' => 'test',
            'provider_payment_id' => null,
            'idempotence_key' => (string) Str::uuid(),
            'status' => 'succeeded',
            'confirmation_url' => null,
            'paid_at' => now(),
            'raw_response' => ['source' => 'keyword_test'],
        ]);

        $this->activateSubscription($payment);
        $targetRoute = Auth::check() ? 'workbook.products' : 'cabinet.show';

        $until = now()->addMonth()->format('d.m.Y');

        return redirect()
            ->route($targetRoute)
            ->with('calculationInput', ['email' => $email])
            ->withInput(['email' => $email])
            ->with(
                'success',
                'Тестовая PRO активирована. Email подставлен в калькулятор — безлимит до '.$until.'.'
            );
    }
}
