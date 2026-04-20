<?php

namespace App\Services;

use App\Models\CalculationRequest;
use App\Models\Subscription;
use App\Models\MarginSnapshot;
use Illuminate\Support\Facades\Cache;

class ProductMetricsService
{
    private const CACHE_TTL_SECONDS = 60;

    /** Верхняя граница прибыли на штуку при агрегации (защита от мусорных вводов). */
    private const PULSE_MAX_NET_PROFIT_PER_UNIT = 200_000.0;

    /** План продаж в метрике не выше этого (снижает влияние max-значений в форме). */
    private const PULSE_MAX_PLANNED_UNITS = 5_000;

    /** Максимальный вклад одного расчёта в сумму (₽/мес). */
    private const PULSE_MAX_MONTHLY_PER_ROW = 50_000_000.0;

    /**
     * @return array<string, float|int|string>
     */
    public function investorPulse(): array
    {
        return Cache::remember('marginflow_investor_pulse_v2', self::CACHE_TTL_SECONDS, function (): array {
            $totalCalculations = CalculationRequest::query()->count();
            $calculations7d = CalculationRequest::query()
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            $snapshotsTotal = MarginSnapshot::query()->count();

            $activePaidSubscriptions = Subscription::query()
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->whereHas('plan', fn ($q) => $q->whereIn('slug', ['pro', 'team']))
                ->count();

            $volume30d = $this->modeledMonthlyProfitVolume30dRobust();

            return [
                'total_calculations' => $totalCalculations,
                'calculations_7d' => $calculations7d,
                'snapshots_total' => $snapshotsTotal,
                'active_paid_subscriptions' => $activePaidSubscriptions,
                'modeled_monthly_profit_volume_30d' => round((float) $volume30d, 0),
            ];
        });
    }

    /**
     * Сумма «смоделированной месячной прибыли» без раздувания от спама:
     * вклад одной строки ограничен; для одного IP в календарный день берётся максимум вклада (не сумма повторов).
     */
    private function modeledMonthlyProfitVolume30dRobust(): float
    {
        $from = now()->subDays(30);
        $byIpDay = [];

        CalculationRequest::query()
            ->where('created_at', '>=', $from)
            ->whereNotNull('planned_units')
            ->orderBy('id')
            ->chunkById(2000, function ($rows) use (&$byIpDay): void {
                foreach ($rows as $row) {
                    $contrib = $this->pulseMonthlyContribution(
                        (float) $row->net_profit,
                        (int) $row->planned_units
                    );
                    if ($contrib <= 0) {
                        continue;
                    }
                    $day = $row->created_at->format('Y-m-d');
                    $key = $row->ip_address.'|'.$day;
                    $byIpDay[$key] = max($byIpDay[$key] ?? 0.0, $contrib);
                }
            });

        return (float) array_sum($byIpDay);
    }

    private function pulseMonthlyContribution(float $netProfitPerUnit, int $plannedUnits): float
    {
        $perUnit = min(max(0.0, $netProfitPerUnit), self::PULSE_MAX_NET_PROFIT_PER_UNIT);
        $units = max(1, min($plannedUnits, self::PULSE_MAX_PLANNED_UNITS));

        return min($perUnit * (float) $units, self::PULSE_MAX_MONTHLY_PER_ROW);
    }
}
