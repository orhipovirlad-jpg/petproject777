<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class AiPricingAdvisorService
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function buildInsight(array $input, array $result): array
    {
        if ($this->isMockEnabled()) {
            return $this->mockInsight($input, $result);
        }

        if (!$this->isConfigured()) {
            return $this->fallbackInsight($result);
        }

        try {
            $response = Http::withToken((string) config('services.openai.api_key'))
                ->timeout(25)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => (string) config('services.openai.model', 'gpt-4o-mini'),
                    'temperature' => 0.3,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Ты финансовый аналитик e-commerce. Дай конкретные советы по цене, марже и рискам. Отвечай строго JSON.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->buildPrompt($input, $result),
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                return $this->fallbackInsight($result);
            }

            $content = (string) data_get($response->json(), 'choices.0.message.content', '');
            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                return $this->fallbackInsight($result);
            }

            return [
                'summary' => (string) ($decoded['summary'] ?? 'AI-обзор готов.'),
                'recommended_action' => (string) ($decoded['recommended_action'] ?? 'Проведите тест цены на 7 дней.'),
                'safe_discount_percent' => (float) ($decoded['safe_discount_percent'] ?? 0),
                'next_steps' => array_values(array_filter(array_map('strval', (array) ($decoded['next_steps'] ?? [])))),
                'source' => 'ai',
            ];
        } catch (Throwable) {
            return $this->fallbackInsight($result);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function buildLaunchPlan(array $input, array $result): array
    {
        if ($this->isMockEnabled()) {
            return $this->mockLaunchPlan($input, $result);
        }

        if (!$this->isConfigured()) {
            return $this->fallbackLaunchPlan($result);
        }

        try {
            $response = Http::withToken((string) config('services.openai.api_key'))
                ->timeout(25)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => (string) config('services.openai.model', 'gpt-4o-mini'),
                    'temperature' => 0.35,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Ты операционный руководитель e-commerce. Дай 7-дневный план запуска SKU в формате JSON.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'task' => 'Сформируй пошаговый 7-дневный план запуска SKU',
                                'input' => $input,
                                'result' => $result,
                                'format' => [
                                    'goal' => 'краткая цель',
                                    'days' => [
                                        ['day' => 'Day 1', 'task' => 'что сделать', 'metric' => 'что контролировать'],
                                    ],
                                    'guardrails' => ['ограничение 1', 'ограничение 2'],
                                ],
                                'language' => 'ru',
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                return $this->fallbackLaunchPlan($result);
            }

            $content = (string) data_get($response->json(), 'choices.0.message.content', '');
            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                return $this->fallbackLaunchPlan($result);
            }

            $days = [];
            foreach ((array) ($decoded['days'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $days[] = [
                    'day' => (string) ($item['day'] ?? ''),
                    'task' => (string) ($item['task'] ?? ''),
                    'metric' => (string) ($item['metric'] ?? ''),
                ];
            }

            return [
                'goal' => (string) ($decoded['goal'] ?? 'Запустить SKU с контролируемым риском и целевой маржой.'),
                'days' => $days,
                'guardrails' => array_values(array_filter(array_map('strval', (array) ($decoded['guardrails'] ?? [])))),
                'source' => 'ai',
            ];
        } catch (Throwable) {
            return $this->fallbackLaunchPlan($result);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $result
     */
    private function buildPrompt(array $input, array $result): string
    {
        return json_encode([
            'task' => 'Сформируй краткий actionable совет по цене и рискам для селлера',
            'input' => $input,
            'result' => $result,
            'format' => [
                'summary' => '1-2 предложения по текущей ситуации',
                'recommended_action' => 'одно приоритетное действие',
                'safe_discount_percent' => 'безопасная скидка в процентах',
                'next_steps' => ['3 коротких шага на неделю'],
            ],
            'language' => 'ru',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function fallbackInsight(array $result): array
    {
        $margin = (float) ($result['margin_percent'] ?? 0);
        $recommendedPrice = (float) ($result['recommended_price'] ?? 0);
        $risk = (string) ($result['risk_level'] ?? 'Средний');

        $safeDiscount = 7.0;
        if ($margin >= 25) {
            $safeDiscount = 12.0;
        } elseif ($margin <= 14) {
            $safeDiscount = 5.0;
        }

        return [
            'summary' => "Текущая маржа {$margin}% и риск {$risk}. Сохраняйте цену около {$recommendedPrice} ₽ и аккуратно тестируйте скидки.",
            'recommended_action' => 'Запустите A/B тест: текущая цена против цены +3-5% на 5-7 дней.',
            'safe_discount_percent' => $safeDiscount,
            'next_steps' => [
                'Ограничьте рекламный бюджет на тест до фиксированного значения.',
                'Проверьте конверсию и выкуп в каждом ценовом сценарии.',
                'Оставьте вариант с максимальной прибылью на 1 заказ.',
            ],
            'source' => 'fallback',
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function fallbackLaunchPlan(array $result): array
    {
        $margin = (float) ($result['margin_percent'] ?? 0);

        return [
            'goal' => 'Выйти на стабильные продажи SKU за 7 дней без просадки маржи.',
            'days' => [
                ['day' => 'День 1', 'task' => 'Проведите soft-launch карточки без агрессивной скидки.', 'metric' => 'CTR, показы и первые заказы'],
                ['day' => 'День 2', 'task' => 'Запустите 2-3 рекламных связки с фиксированным бюджетом.', 'metric' => 'CPC, CR и доля рекламных расходов'],
                ['day' => 'День 3', 'task' => 'Отключите слабые связки и перераспределите бюджет.', 'metric' => 'Цена заказа и маржа по заказам'],
                ['day' => 'День 4', 'task' => 'Проверьте A/B цену: базовая vs +3%.', 'metric' => 'Выручка/заказ и количество заказов'],
                ['day' => 'День 5', 'task' => 'Проведите тест скидки в безопасном диапазоне.', 'metric' => 'Изменение конверсии и прибыли/шт'],
                ['day' => 'День 6', 'task' => 'Зафиксируйте лучшую цену и рекламный сет.', 'metric' => 'Стабильность маржи и ROI'],
                ['day' => 'День 7', 'task' => 'Сформируйте итоговый отчет и масштабируйте победителя.', 'metric' => 'Плановая месячная прибыль'],
            ],
            'guardrails' => [
                'Не повышайте рекламный бюджет более чем на 20% в день.',
                "Сохраняйте маржу не ниже {$margin}% до завершения тестов.",
                'Не запускайте глубокую скидку без повторного пересчета экономики.',
            ],
            'source' => 'fallback',
        ];
    }

    private function isConfigured(): bool
    {
        return (string) config('services.openai.api_key') !== '';
    }

    private function isMockEnabled(): bool
    {
        return (bool) config('services.openai.mock', false);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function mockInsight(array $input, array $result): array
    {
        $recommendedPrice = (float) ($result['recommended_price'] ?? 0);
        $margin = (float) ($result['margin_percent'] ?? 0);
        $platform = (string) ($input['platform'] ?? 'маркетплейс');

        return [
            'summary' => "MOCK: для {$platform} оптимально удерживать цену около ".round($recommendedPrice)." ₽ при текущих вводных.",
            'recommended_action' => 'MOCK: протестируйте повышение цены на 3% без изменения рекламного бюджета в течение 5 дней.',
            'safe_discount_percent' => max(4, min(15, round($margin / 2, 1))),
            'next_steps' => [
                'MOCK: зафиксируйте текущую конверсию и выкуп за 3 последних дня.',
                'MOCK: запустите два ценовых сценария и сравните прибыль на заказ.',
                'MOCK: оставьте вариант с лучшей маржой и стабильным объемом заказов.',
            ],
            'source' => 'mock',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function mockLaunchPlan(array $input, array $result): array
    {
        $platform = (string) ($input['platform'] ?? 'маркетплейс');
        $monthlyProfit = (float) ($result['monthly_profit'] ?? 0);

        return [
            'goal' => "MOCK: стабилизировать SKU на {$platform} и выйти на план прибыли ".round($monthlyProfit)." ₽/мес.",
            'days' => [
                ['day' => 'День 1', 'task' => 'MOCK: проверить контент карточки и подготовить 2 варианта цены.', 'metric' => 'CTR и конверсия в корзину'],
                ['day' => 'День 2', 'task' => 'MOCK: запустить рекламу на фиксированном бюджете.', 'metric' => 'CPC и доля рекламных расходов'],
                ['day' => 'День 3', 'task' => 'MOCK: отключить нерентабельные связки.', 'metric' => 'Прибыль на заказ'],
                ['day' => 'День 4', 'task' => 'MOCK: протестировать безопасную скидку.', 'metric' => 'Рост заказов и маржа'],
                ['day' => 'День 5', 'task' => 'MOCK: обновить цену по итогам теста.', 'metric' => 'Чистая прибыль/шт'],
                ['day' => 'День 6', 'task' => 'MOCK: масштабировать лучший сценарий.', 'metric' => 'Объем заказов в день'],
                ['day' => 'День 7', 'task' => 'MOCK: зафиксировать регламент работы с ценой.', 'metric' => 'План-факт по прибыли'],
            ],
            'guardrails' => [
                'MOCK: не увеличивать рекламный бюджет более чем на 20% в день.',
                'MOCK: не опускаться ниже целевой маржи.',
                'MOCK: не запускать скидку без пересчета unit-экономики.',
            ],
            'source' => 'mock',
        ];
    }
}
