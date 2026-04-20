<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\CalculationRequest;
use App\Models\MarginSnapshot;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;

class SubscriptionEntitlementService
{
    private ?SubscriptionPlan $freePlan = null;

    public function resolvePlan(?string $email): SubscriptionPlan
    {
        $email = $this->normalizeEmail($email);
        if ($email === null) {
            return $this->freePlan();
        }

        $subscription = Subscription::query()
            ->where('email', $email)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->with('plan')
            ->first();

        if ($subscription !== null && $subscription->plan !== null) {
            return $subscription->plan;
        }

        return $this->freePlan();
    }

    /**
     * @return array<string, mixed>
     */
    public function contextForEmail(?string $email): array
    {
        $plan = $this->resolvePlan($email);
        $limits = is_array($plan->limits) ? $plan->limits : [];
        $email = $this->normalizeEmail($email);
        $snapshotLimit = (int) ($limits['margin_snapshots'] ?? 0);
        $snapshotCount = $email !== null
            ? MarginSnapshot::query()->where('email', $email)->count()
            : 0;

        return [
            'tier' => $plan->slug,
            'plan_name' => $plan->name,
            'daily_calculations' => (int) ($limits['daily_calculations'] ?? 25),
            'daily_ai' => (int) ($limits['daily_ai'] ?? 20),
            'margin_snapshots' => $snapshotLimit,
            'snapshot_count' => $snapshotCount,
            'can_save_snapshot' => $email !== null
                && $snapshotLimit > 0
                && $snapshotCount < $snapshotLimit,
            'export_csv' => (bool) ($limits['export_csv'] ?? false),
        ];
    }

    public function remainingCalculations(?string $ip, ?string $email): int
    {
        $plan = $this->resolvePlan($email);
        $limits = is_array($plan->limits) ? $plan->limits : [];
        $dailyLimit = (int) ($limits['daily_calculations'] ?? 25);

        if ($dailyLimit >= 9999) {
            return 9999;
        }

        if ($ip === null) {
            return $dailyLimit;
        }

        $count = CalculationRequest::query()
            ->where('ip_address', $ip)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return max(0, $dailyLimit - $count);
    }

    public function remainingAiActions(?string $ip, ?string $email): int
    {
        $plan = $this->resolvePlan($email);
        $limits = is_array($plan->limits) ? $plan->limits : [];
        $dailyLimit = (int) ($limits['daily_ai'] ?? 20);

        if ($dailyLimit >= 9999) {
            return 9999;
        }

        if ($ip === null) {
            return $dailyLimit;
        }

        $count = AiInsight::query()
            ->where('ip_address', $ip)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return max(0, $dailyLimit - $count);
    }

    private function freePlan(): SubscriptionPlan
    {
        if ($this->freePlan !== null) {
            return $this->freePlan;
        }

        $this->freePlan = SubscriptionPlan::query()->where('slug', 'free')->firstOrFail();

        return $this->freePlan;
    }

    private function normalizeEmail(?string $email): ?string
    {
        if (!is_string($email)) {
            return null;
        }
        $t = trim($email);

        return $t === '' ? null : $t;
    }
}
