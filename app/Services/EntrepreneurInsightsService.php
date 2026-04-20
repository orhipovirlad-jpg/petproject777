<?php

namespace App\Services;

use App\Models\CalculationRequest;

class EntrepreneurInsightsService
{
    private const PEER_WINDOW_DAYS = 90;

    private const MIN_SAMPLE_FOR_BENCHMARK = 8;

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function build(array $input, array $result): array
    {
        $health = $this->healthScore($result);
        $peer = $this->peerVsAverage((string) ($input['platform'] ?? 'wildberries'), (float) ($result['net_profit'] ?? 0));
        $pitch = $this->elevatorPitch($input, $result);

        return [
            'health_score' => $health['score'],
            'health_grade' => $health['grade'],
            'health_label' => $health['label'],
            'health_hints' => $health['hints'],
            'peer' => $peer,
            'elevator_pitch' => $pitch,
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @return array{score: int, grade: string, label: string, hints: list<string>}
     */
    private function healthScore(array $result): array
    {
        $margin = (float) ($result['margin_percent'] ?? 0);
        $risk = (string) ($result['risk_level'] ?? 'Средний');
        $monthly = (float) ($result['monthly_profit'] ?? 0);
        $delta = (float) ($result['price_delta_vs_competitor_percent'] ?? 0);

        $score = 52;

        if ($margin >= 28) {
            $score += 22;
        } elseif ($margin >= 18) {
            $score += 14;
        } elseif ($margin >= 12) {
            $score += 6;
        } else {
            $score -= 18;
        }

        $score += match ($risk) {
            'Низкий' => 16,
            'Средний' => 0,
            default => -18,
        };

        if ($monthly >= 250_000) {
            $score += 14;
        } elseif ($monthly >= 80_000) {
            $score += 10;
        } elseif ($monthly >= 25_000) {
            $score += 5;
        } elseif ($monthly < 8_000) {
            $score -= 8;
        }

        if ($delta >= -4 && $delta <= 5) {
            $score += 10;
        } elseif ($delta > 8) {
            $score -= 12;
        } elseif ($delta < -12) {
            $score += 6;
        }

        $score = (int) max(0, min(100, round($score)));

        $grade = match (true) {
            $score >= 88 => 'A',
            $score >= 72 => 'B',
            $score >= 52 => 'C',
            default => 'D',
        };

        $label = match ($grade) {
            'A' => 'Сильная юнит-экономика',
            'B' => 'Рабочая модель, есть куда расти',
            'C' => 'Точка входа в плюс есть, усилите маржу или объём',
            default => 'Высокая чувствительность к расходам и цене',
        };

        $hints = [];
        if ($margin < 15) {
            $hints[] = 'Подтяните маржу: цена, комиссия или доля рекламы.';
        }
        if ($risk !== 'Низкий') {
            $hints[] = 'Снизьте риск: проверьте позицию относительно конкурентов и возвраты.';
        }
        if ($monthly < 30_000) {
            $hints[] = 'Масштабируйте объём или добавьте смежные SKU.';
        }
        if (count($hints) === 0) {
            $hints[] = 'Фиксируйте сценарий и тестируйте рост цены малыми шагами.';
        }

        return [
            'score' => $score,
            'grade' => $grade,
            'label' => $label,
            'hints' => array_slice($hints, 0, 3),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function peerVsAverage(string $platform, float $netProfit): ?array
    {
        $row = CalculationRequest::query()
            ->where('platform', $platform)
            ->where('created_at', '>=', now()->subDays(self::PEER_WINDOW_DAYS))
            ->selectRaw('COUNT(*) as sample_size, AVG(net_profit) as avg_profit')
            ->first();

        if ($row === null) {
            return [
                'available' => false,
                'sample_size' => 0,
                'message' => 'Собираем анонимную базу по площадке — скоро покажем сравнение с другими селлерами.',
            ];
        }

        $n = (int) $row->sample_size;
        if ($n < self::MIN_SAMPLE_FOR_BENCHMARK) {
            return [
                'available' => false,
                'sample_size' => $n,
                'message' => 'Собираем анонимную базу по площадке — скоро покажем сравнение с другими селлерами.',
            ];
        }

        $avg = (float) $row->avg_profit;
        if (abs($avg) < 0.01) {
            return [
                'available' => false,
                'sample_size' => $n,
                'message' => 'Недостаточно разброса данных для сравнения — повторите расчёт позже.',
            ];
        }

        $diffPct = (($netProfit - $avg) / abs($avg)) * 100;
        $platformLabel = match ($platform) {
            'wildberries' => 'Wildberries',
            'ozon' => 'Ozon',
            'kaspi' => 'Kaspi',
            default => $platform,
        };

        if ($diffPct >= 5) {
            $tone = 'strong';
            $message = sprintf(
                'Прибыль на единицу выше среднего по %s среди пользователей MarginFlow примерно на %s%%.',
                $platformLabel,
                number_format(abs($diffPct), 0, ',', ' ')
            );
        } elseif ($diffPct <= -5) {
            $tone = 'watch';
            $message = sprintf(
                'Ниже среднего по %s на ~%s%% — есть потенциал подтянуть цену, скидку или закуп.',
                $platformLabel,
                number_format(abs($diffPct), 0, ',', ' ')
            );
        } else {
            $tone = 'inline';
            $message = sprintf(
                'В зоне типичной прибыли на единицу для %s в нашей выборке — хорошая база для тестов роста.',
                $platformLabel
            );
        }

        return [
            'available' => true,
            'sample_size' => $n,
            'avg_net_profit_peer' => round($avg, 2),
            'diff_percent_vs_peer' => round($diffPct, 1),
            'tone' => $tone,
            'message' => $message,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $result
     */
    private function elevatorPitch(array $input, array $result): string
    {
        $platform = match ((string) ($input['platform'] ?? '')) {
            'wildberries' => 'WB',
            'ozon' => 'Ozon',
            'kaspi' => 'Kaspi',
            default => 'МП',
        };
        $units = (int) ($input['planned_units'] ?? 0);
        $sale = (float) ($result['sale_price_after_discount'] ?? 0);
        $monthly = (float) ($result['monthly_profit'] ?? 0);
        $margin = (float) ($result['margin_percent'] ?? 0);
        $risk = (string) ($result['risk_level'] ?? '');

        return sprintf(
            'SKU на %s: цена после скидки %s ₽, маржа %s%%, план %s шт/мес → ~%s ₽ чистыми в месяц. Риск: %s. (MarginFlow)',
            $platform,
            number_format($sale, 0, ',', ' '),
            number_format($margin, 1, ',', ' '),
            number_format($units, 0, ',', ' '),
            number_format($monthly, 0, ',', ' '),
            $risk
        );
    }
}
