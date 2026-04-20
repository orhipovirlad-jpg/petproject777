<?php

namespace App\Services;

class MarketplaceMarginService
{
    /**
     * @param array<string, float|int|string> $data
     * @return array<string, mixed>
     */
    public function calculate(array $data): array
    {
        if (isset($data['sales_model']) && is_string($data['sales_model']) && $data['sales_model'] !== '') {
            return $this->calculateExcelModel($data);
        }

        return $this->calculateClassic($data);
    }

    /**
     * @param array<string, float|int|string> $data
     * @return array<string, mixed>
     */
    private function calculateClassic(array $data): array
    {
        $costPrice = (float) $data['cost_price'];
        $packagingCost = (float) $data['packaging_cost'];
        $logisticsCost = (float) $data['logistics_cost'];
        $desiredMarginPercent = (float) $data['desired_margin_percent'];
        $adSpendPercent = (float) $data['ad_spend_percent'];
        $taxPercent = (float) $data['tax_percent'];
        $returnsPercent = (float) $data['returns_percent'];
        $commissionPercent = (float) $data['commission_percent'];
        $plannedUnits = (int) $data['planned_units'];
        $competitorPrice = (float) $data['competitor_price'];
        $discountPercent = (float) $data['discount_percent'];

        $fixedCosts = $costPrice + $packagingCost + $logisticsCost;
        $variablePercent = $commissionPercent + $adSpendPercent + $taxPercent + $returnsPercent;

        // Protect against impossible inputs where costs exceed 100% of revenue.
        $breakEvenDenominator = max(1.0, 100 - $variablePercent);
        $breakEvenPrice = $fixedCosts / ($breakEvenDenominator / 100);

        $targetDenominator = max(1.0, 100 - $variablePercent - $desiredMarginPercent);
        $recommendedPrice = $fixedCosts / ($targetDenominator / 100);

        $salePriceAfterDiscount = $recommendedPrice * (1 - ($discountPercent / 100));
        $unitBase = $this->calculateUnitEconomy(
            $recommendedPrice,
            $fixedCosts,
            $commissionPercent,
            $adSpendPercent,
            $taxPercent,
            $returnsPercent
        );
        $unitDiscounted = $this->calculateUnitEconomy(
            $salePriceAfterDiscount,
            $fixedCosts,
            $commissionPercent,
            $adSpendPercent,
            $taxPercent,
            $returnsPercent
        );

        $priceDeltaVsCompetitor = $competitorPrice > 0
            ? (($salePriceAfterDiscount - $competitorPrice) / $competitorPrice) * 100
            : 0.0;
        $monthlyProfit = $unitDiscounted['profit'] * $plannedUnits;

        $scenarios = [
            $this->calculateScenario('Базовый', $salePriceAfterDiscount, $fixedCosts, $commissionPercent, $adSpendPercent, $taxPercent, $returnsPercent),
            $this->calculateScenario('Консервативный', $salePriceAfterDiscount, $fixedCosts, $commissionPercent, $adSpendPercent + 3, $taxPercent, $returnsPercent + 2),
            $this->calculateScenario('Агрессивный', $salePriceAfterDiscount, $fixedCosts, $commissionPercent, $adSpendPercent + 6, $taxPercent, $returnsPercent + 4),
        ];

        $riskLevel = $this->resolveRiskLevel($unitDiscounted['margin_percent'], $priceDeltaVsCompetitor);
        $advice = $this->buildAdvice($unitDiscounted['margin_percent'], $priceDeltaVsCompetitor, $monthlyProfit, $discountPercent);

        $maxSafeDiscount = $this->maxSafeDiscountPercentFromRecommended(
            $recommendedPrice,
            $fixedCosts,
            $commissionPercent,
            $adSpendPercent,
            $taxPercent,
            $returnsPercent
        );

        $stressTests = $this->buildStressTests(
            $salePriceAfterDiscount,
            $competitorPrice,
            $plannedUnits,
            $fixedCosts,
            $commissionPercent,
            $adSpendPercent,
            $taxPercent,
            $returnsPercent
        );

        return [
            'break_even_price' => round($breakEvenPrice, 2),
            'recommended_price' => round($recommendedPrice, 2),
            'net_profit' => round($unitDiscounted['profit'], 2),
            'net_profit_base' => round($unitBase['profit'], 2),
            'margin_percent' => round($unitDiscounted['margin_percent'], 2),
            'margin_percent_base' => round($unitBase['margin_percent'], 2),
            'roi_percent' => round($unitDiscounted['roi_percent'], 2),
            'roi_percent_base' => round($unitBase['roi_percent'], 2),
            'commission_cost' => round($unitDiscounted['commission'], 2),
            'ads_cost' => round($unitDiscounted['ads'], 2),
            'tax_cost' => round($unitDiscounted['tax'], 2),
            'returns_cost' => round($unitDiscounted['returns'], 2),
            'fixed_costs' => round($fixedCosts, 2),
            'platform' => (string) $data['platform'],
            'sale_price_after_discount' => round($salePriceAfterDiscount, 2),
            'price_delta_vs_competitor_percent' => round($priceDeltaVsCompetitor, 2),
            'monthly_profit' => round($monthlyProfit, 2),
            'planned_units' => $plannedUnits,
            'risk_level' => $riskLevel,
            'advice' => $advice,
            'scenarios' => $scenarios,
            'max_safe_discount_percent' => $maxSafeDiscount,
            'stress_tests' => $stressTests,
        ];
    }

    /**
     * Формулы, приближенные к листам WB/Ozon (FBO/FBS) из wb.xlsx.
     *
     * @param array<string, float|int|string> $data
     * @return array<string, mixed>
     */
    private function calculateExcelModel(array $data): array
    {
        $model = (string) $data['sales_model'];
        $platform = str_starts_with($model, 'ozon') ? 'ozon' : 'wildberries';

        $costPrice = (float) $data['cost_price'];
        $packagingCost = (float) $data['packaging_cost'];
        $logisticsCost = (float) $data['logistics_cost'];
        $fixedCosts = $costPrice + $packagingCost + $logisticsCost;

        $salePriceBeforeDiscount = (float) ($data['sale_price'] ?? 0);
        $discountPercent = (float) ($data['discount_percent'] ?? 0);
        $salePriceAfterDiscount = $salePriceBeforeDiscount * (1 - $discountPercent / 100);

        $commissionPercent = (float) ($data['commission_percent'] ?? 0);
        $adSpendPercent = (float) ($data['ad_spend_percent'] ?? 0);
        $taxPercent = (float) ($data['tax_percent'] ?? 7);
        $mpDiscountPercent = (float) ($data['mp_discount_percent'] ?? 0);
        $buyoutPercent = max(0.1, min(100.0, (float) ($data['buyout_percent'] ?? 95)));
        $buyoutRate = $buyoutPercent / 100.0;
        $logisticsBaseCost = (float) ($data['logistics_base_cost'] ?? 0);
        $storageDailyCost = (float) ($data['storage_daily_cost'] ?? 0);
        $storageDays = (float) ($data['storage_days'] ?? 0);
        $extraCost = (float) ($data['extra_cost'] ?? 0);
        $acquiringPercent = (float) ($data['acquiring_percent'] ?? 0);
        $lastMileCost = (float) ($data['last_mile_cost'] ?? 0);
        $plannedUnits = (int) ($data['planned_units'] ?? 300);
        $competitorPrice = (float) ($data['competitor_price'] ?? 0);

        $logisticsAdjusted = $logisticsBaseCost + ($logisticsBaseCost * (1 - $buyoutRate) / $buyoutRate);
        $storageTotal = $storageDailyCost * $storageDays;
        $commissionCost = $salePriceAfterDiscount * ($commissionPercent / 100);
        $adCost = $salePriceAfterDiscount * ($adSpendPercent / 100);
        $acquiringCost = $salePriceAfterDiscount * ($acquiringPercent / 100);

        $taxBase = $salePriceAfterDiscount;
        if (str_starts_with($model, 'wb_')) {
            $taxBase = $salePriceAfterDiscount * (1 - ($mpDiscountPercent / 100));
        }
        $taxCost = $taxBase * ($taxPercent / 100);

        $marketplaceCosts = $commissionCost + $logisticsAdjusted + $adCost;
        if ($model === 'wb_fbw') {
            $marketplaceCosts += $storageTotal + $extraCost;
        } elseif ($model === 'wb_fbs') {
            $marketplaceCosts += $extraCost;
        } elseif ($model === 'ozon_fbo') {
            $marketplaceCosts += $acquiringCost + $extraCost;
        } elseif ($model === 'ozon_fbs') {
            $marketplaceCosts += $acquiringCost + $extraCost + $lastMileCost;
        }

        $netProfit = $salePriceAfterDiscount - $marketplaceCosts - $fixedCosts - $taxCost;
        $marginPercent = $salePriceAfterDiscount > 0 ? ($netProfit / $salePriceAfterDiscount) * 100 : 0.0;
        $roiPercent = $fixedCosts > 0 ? ($netProfit / $fixedCosts) * 100 : 0.0;
        $monthlyProfit = $netProfit * $plannedUnits;

        $priceDeltaVsCompetitor = $competitorPrice > 0
            ? (($salePriceAfterDiscount - $competitorPrice) / $competitorPrice) * 100
            : 0.0;

        $riskLevel = $this->resolveRiskLevel($marginPercent, $priceDeltaVsCompetitor);
        $advice = $this->buildAdvice($marginPercent, $priceDeltaVsCompetitor, $monthlyProfit, $discountPercent);

        $stressTests = $this->buildStressTests(
            $salePriceAfterDiscount,
            $competitorPrice,
            $plannedUnits,
            $fixedCosts,
            $commissionPercent,
            $adSpendPercent,
            $taxPercent,
            0.0
        );

        $scenarioBase = [
            'name' => 'Текущий тариф',
            'ad_spend_percent' => round($adSpendPercent, 2),
            'returns_percent' => 0.0,
            'profit' => round($netProfit, 2),
            'margin_percent' => round($marginPercent, 2),
        ];
        $scenarioAdUp = [
            'name' => 'Реклама +3 п.п.',
            'ad_spend_percent' => round($adSpendPercent + 3, 2),
            'returns_percent' => 0.0,
            'profit' => round($netProfit - ($salePriceAfterDiscount * 0.03), 2),
            'margin_percent' => round(
                $salePriceAfterDiscount > 0 ? (($netProfit - ($salePriceAfterDiscount * 0.03)) / $salePriceAfterDiscount) * 100 : 0.0,
                2
            ),
        ];
        $scenarioFeeUp = [
            'name' => 'Комиссия +1 п.п.',
            'ad_spend_percent' => round($adSpendPercent, 2),
            'returns_percent' => 0.0,
            'profit' => round($netProfit - ($salePriceAfterDiscount * 0.01), 2),
            'margin_percent' => round(
                $salePriceAfterDiscount > 0 ? (($netProfit - ($salePriceAfterDiscount * 0.01)) / $salePriceAfterDiscount) * 100 : 0.0,
                2
            ),
        ];

        return [
            'break_even_price' => round($fixedCosts + $marketplaceCosts + $taxCost, 2),
            'recommended_price' => round($salePriceBeforeDiscount, 2),
            'net_profit' => round($netProfit, 2),
            'net_profit_base' => round($netProfit, 2),
            'margin_percent' => round($marginPercent, 2),
            'margin_percent_base' => round($marginPercent, 2),
            'roi_percent' => round($roiPercent, 2),
            'roi_percent_base' => round($roiPercent, 2),
            'commission_cost' => round($commissionCost, 2),
            'ads_cost' => round($adCost, 2),
            'tax_cost' => round($taxCost, 2),
            'returns_cost' => 0.0,
            'fixed_costs' => round($fixedCosts, 2),
            'platform' => $platform,
            'sale_price_after_discount' => round($salePriceAfterDiscount, 2),
            'price_delta_vs_competitor_percent' => round($priceDeltaVsCompetitor, 2),
            'monthly_profit' => round($monthlyProfit, 2),
            'planned_units' => $plannedUnits,
            'risk_level' => $riskLevel,
            'advice' => $advice,
            'scenarios' => [$scenarioBase, $scenarioAdUp, $scenarioFeeUp],
            'max_safe_discount_percent' => round($discountPercent, 1),
            'stress_tests' => $stressTests,
            'excel_model' => $model,
            'logistics_adjusted_cost' => round($logisticsAdjusted, 2),
            'storage_total_cost' => round($storageTotal, 2),
            'marketplace_costs_total' => round($marketplaceCosts, 2),
        ];
    }

    /**
     * Максимальная суммарная скидка от рекомендованной цены, при которой прибыль на штуку ещё > 0.
     */
    private function maxSafeDiscountPercentFromRecommended(
        float $recommendedPrice,
        float $fixedCosts,
        float $commissionPercent,
        float $adSpendPercent,
        float $taxPercent,
        float $returnsPercent
    ): float {
        if ($recommendedPrice <= 0) {
            return 0.0;
        }

        $low = 0.0;
        $high = 99.0;
        $best = 0.0;

        for ($i = 0; $i < 56; $i++) {
            $mid = ($low + $high) / 2.0;
            $price = $recommendedPrice * (1.0 - ($mid / 100.0));
            $unit = $this->calculateUnitEconomy(
                $price,
                $fixedCosts,
                $commissionPercent,
                $adSpendPercent,
                $taxPercent,
                $returnsPercent
            );

            if ($unit['profit'] > 0.005) {
                $best = $mid;
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        return round($best, 1);
    }

    /**
     * @return list<array<string, float|int|string>>
     */
    private function buildStressTests(
        float $salePriceAfterDiscount,
        float $competitorPrice,
        int $plannedUnits,
        float $fixedCosts,
        float $commissionPercent,
        float $adSpendPercent,
        float $taxPercent,
        float $returnsPercent
    ): array {
        $tests = [];

        $uAds = $this->calculateUnitEconomy(
            $salePriceAfterDiscount,
            $fixedCosts,
            $commissionPercent,
            min(40.0, $adSpendPercent + 3.0),
            $taxPercent,
            $returnsPercent
        );
        $tests[] = [
            'id' => 'ads_up',
            'title' => 'Реклама +3 п.п.',
            'hint' => 'Типичный рост ставки в сезон',
            'net_profit' => round($uAds['profit'], 2),
            'margin_percent' => round($uAds['margin_percent'], 2),
            'monthly_profit' => round($uAds['profit'] * $plannedUnits, 2),
        ];

        $uRet = $this->calculateUnitEconomy(
            $salePriceAfterDiscount,
            $fixedCosts,
            $commissionPercent,
            $adSpendPercent,
            $taxPercent,
            min(25.0, $returnsPercent + 2.0)
        );
        $tests[] = [
            'id' => 'returns_up',
            'title' => 'Возвраты +2 п.п.',
            'hint' => 'Просадка качества или категории',
            'net_profit' => round($uRet['profit'], 2),
            'margin_percent' => round($uRet['margin_percent'], 2),
            'monthly_profit' => round($uRet['profit'] * $plannedUnits, 2),
        ];

        $uFee = $this->calculateUnitEconomy(
            $salePriceAfterDiscount,
            $fixedCosts,
            min(60.0, $commissionPercent + 1.0),
            $adSpendPercent,
            $taxPercent,
            $returnsPercent
        );
        $tests[] = [
            'id' => 'commission_up',
            'title' => 'Комиссия МП +1 п.п.',
            'hint' => 'Изменение тарифа площадки',
            'net_profit' => round($uFee['profit'], 2),
            'margin_percent' => round($uFee['margin_percent'], 2),
            'monthly_profit' => round($uFee['profit'] * $plannedUnits, 2),
        ];

        if ($competitorPrice > 0) {
            $uMatch = $this->calculateUnitEconomy(
                $competitorPrice,
                $fixedCosts,
                $commissionPercent,
                $adSpendPercent,
                $taxPercent,
                $returnsPercent
            );
            $tests[] = [
                'id' => 'match_competitor',
                'title' => 'Цена как у конкурента',
                'hint' => 'Если встать в цену «среднего» конкурента',
                'net_profit' => round($uMatch['profit'], 2),
                'margin_percent' => round($uMatch['margin_percent'], 2),
                'monthly_profit' => round($uMatch['profit'] * $plannedUnits, 2),
            ];
        }

        return $tests;
    }

    /**
     * @return array<string, float>
     */
    private function calculateUnitEconomy(
        float $price,
        float $fixedCosts,
        float $commissionPercent,
        float $adSpendPercent,
        float $taxPercent,
        float $returnsPercent
    ): array {
        $commission = $price * ($commissionPercent / 100);
        $ads = $price * ($adSpendPercent / 100);
        $tax = $price * ($taxPercent / 100);
        $returns = $price * ($returnsPercent / 100);
        $profit = $price - ($fixedCosts + $commission + $ads + $tax + $returns);
        $marginPercent = $price > 0 ? ($profit / $price) * 100 : 0.0;
        $roiPercent = $fixedCosts > 0 ? ($profit / $fixedCosts) * 100 : 0.0;

        return [
            'commission' => $commission,
            'ads' => $ads,
            'tax' => $tax,
            'returns' => $returns,
            'profit' => $profit,
            'margin_percent' => $marginPercent,
            'roi_percent' => $roiPercent,
        ];
    }

    private function calculateScenario(
        string $name,
        float $price,
        float $fixedCosts,
        float $commissionPercent,
        float $adSpendPercent,
        float $taxPercent,
        float $returnsPercent
    ): array {
        $commission = $price * ($commissionPercent / 100);
        $ads = $price * ($adSpendPercent / 100);
        $tax = $price * ($taxPercent / 100);
        $returns = $price * ($returnsPercent / 100);
        $profit = $price - ($fixedCosts + $commission + $ads + $tax + $returns);
        $marginPercent = $price > 0 ? ($profit / $price) * 100 : 0.0;

        return [
            'name' => $name,
            'ad_spend_percent' => round($adSpendPercent, 2),
            'returns_percent' => round($returnsPercent, 2),
            'profit' => round($profit, 2),
            'margin_percent' => round($marginPercent, 2),
        ];
    }

    private function resolveRiskLevel(float $marginPercent, float $priceDeltaVsCompetitor): string
    {
        if ($marginPercent < 12 || $priceDeltaVsCompetitor > 8) {
            return 'Высокий';
        }

        if ($marginPercent < 20 || $priceDeltaVsCompetitor > 3) {
            return 'Средний';
        }

        return 'Низкий';
    }

    /**
     * @return list<string>
     */
    private function buildAdvice(
        float $marginPercent,
        float $priceDeltaVsCompetitor,
        float $monthlyProfit,
        float $discountPercent
    ): array {
        $advice = [];

        if ($marginPercent < 15) {
            $advice[] = 'Маржа ниже целевого уровня. Увеличьте цену или снизьте рекламный бюджет на 2-3 п.п.';
        } else {
            $advice[] = 'Маржа в рабочей зоне. Можно масштабировать трафик и тестировать новые связки креативов.';
        }

        if ($priceDeltaVsCompetitor > 5) {
            $advice[] = 'Цена выше конкурентов более чем на 5%. Подумайте о комплекте, бонусе или улучшенном контенте карточки.';
        } elseif ($priceDeltaVsCompetitor < -7) {
            $advice[] = 'Вы заметно дешевле рынка. Есть запас для роста цены без резкой просадки конверсии.';
        } else {
            $advice[] = 'Цена близка к конкурентам. Делайте акцент на выкупе и рейтинге карточки.';
        }

        if ($monthlyProfit < 50000) {
            $advice[] = 'Плановая прибыль пока низкая. Для роста увеличьте план продаж или расширьте ассортимент.';
        } else {
            $advice[] = 'Плановая прибыль достаточна для реинвеста в рекламу и запуск 1-2 новых SKU.';
        }

        if ($discountPercent > 20) {
            $advice[] = 'Скидка выше 20%: высокий риск съедания прибыли. Проверяйте экономику акций отдельно.';
        }

        return $advice;
    }

    /**
     * Одна цена и себестоимость «в полку» — три маркетплейса с типовыми долями комиссии, рекламы, налога и возвратов.
     *
     * @param array<string, float|int> $params
     * @return array{rows: list<array<string, float|int|string>>, winner: string|null, sale_price_after_discount: float, hint: string}
     */
    public function comparePlatforms(array $params): array
    {
        $totalCost = (float) $params['total_unit_cost'];
        $salePrice = (float) $params['sale_price'];
        $plannedUnits = (int) ($params['planned_units'] ?? 300);
        $discountPercent = (float) ($params['discount_percent'] ?? 0);
        $plannedUnits = max(1, min(50000, $plannedUnits));
        $discountPercent = max(0.0, min(70.0, $discountPercent));

        $afterDiscount = $salePrice * (1 - $discountPercent / 100);

        $presets = [
            'wildberries' => ['label' => 'Wildberries', 'commission' => 19.0, 'ad' => 8.0, 'tax' => 6.0, 'returns' => 3.0],
            'ozon' => ['label' => 'Ozon', 'commission' => 22.0, 'ad' => 10.0, 'tax' => 6.0, 'returns' => 4.0],
            'kaspi' => ['label' => 'Kaspi', 'commission' => 12.0, 'ad' => 6.0, 'tax' => 6.0, 'returns' => 2.0],
        ];

        $rows = [];
        foreach ($presets as $slug => $p) {
            $u = $this->calculateUnitEconomy(
                $afterDiscount,
                $totalCost,
                $p['commission'],
                $p['ad'],
                $p['tax'],
                $p['returns']
            );
            $rows[] = [
                'platform' => $slug,
                'label' => $p['label'],
                'net_profit' => round($u['profit'], 2),
                'margin_percent' => round($u['margin_percent'], 2),
                'monthly_profit' => round($u['profit'] * $plannedUnits, 2),
                'commission_percent' => $p['commission'],
                'ad_spend_percent' => $p['ad'],
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return $b['net_profit'] <=> $a['net_profit'];
        });

        $winner = $rows[0]['platform'] ?? null;

        return [
            'rows' => $rows,
            'winner' => is_string($winner) ? $winner : null,
            'sale_price_after_discount' => round($afterDiscount, 2),
            'hint' => 'Доли комиссии и рекламы — ориентиры по типовой карточке; в полном калькуляторе подставьте свои проценты.',
        ];
    }
}
