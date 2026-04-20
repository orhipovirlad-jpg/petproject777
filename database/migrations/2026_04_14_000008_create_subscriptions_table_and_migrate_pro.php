<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $freeLimits = [
            'daily_calculations' => 25,
            'daily_ai' => 20,
            'margin_snapshots' => 0,
            'export_csv' => false,
        ];
        $proLimits = [
            'daily_calculations' => 9999,
            'daily_ai' => 9999,
            'margin_snapshots' => 50,
            'export_csv' => true,
        ];
        $teamLimits = [
            'daily_calculations' => 9999,
            'daily_ai' => 9999,
            'margin_snapshots' => 500,
            'export_csv' => true,
        ];

        DB::table('subscription_plans')->insert([
            [
                'slug' => 'free',
                'name' => 'Free',
                'tagline' => 'Старт без карты',
                'price_monthly' => 0,
                'currency' => 'RUB',
                'limits' => json_encode($freeLimits),
                'features' => json_encode(['calculator', 'basic_ai']),
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'pro',
                'name' => 'PRO',
                'tagline' => 'Безлимит и сценарии',
                'price_monthly' => 790,
                'currency' => 'RUB',
                'limits' => json_encode($proLimits),
                'features' => json_encode(['unlimited', 'snapshots', 'csv_export', 'ai_unlimited']),
                'sort_order' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'team',
                'name' => 'Team',
                'tagline' => 'Для команды и агентств',
                'price_monthly' => 2490,
                'currency' => 'RUB',
                'limits' => json_encode($teamLimits),
                'features' => json_encode(['unlimited', 'snapshots', 'csv_export', 'ai_unlimited', 'shared_library']),
                'sort_order' => 20,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $proPlanId = (int) DB::table('subscription_plans')->where('slug', 'pro')->value('id');

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('RUB');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->index();
            $table->string('status', 32)->default('active')->index();
            $table->unsignedBigInteger('last_payment_id')->nullable();
            $table->timestamps();

            $table->foreign('last_payment_id')->references('id')->on('pro_payments')->nullOnDelete();
        });

        if (Schema::hasTable('pro_subscriptions')) {
            $rows = DB::table('pro_subscriptions')->get();
            foreach ($rows as $row) {
                $planId = $proPlanId;
                if (isset($row->plan) && is_string($row->plan)) {
                    $mapped = DB::table('subscription_plans')->where('slug', $row->plan)->value('id');
                    if ($mapped !== null) {
                        $planId = (int) $mapped;
                    }
                }

                DB::table('subscriptions')->insert([
                    'email' => $row->email,
                    'subscription_plan_id' => $planId,
                    'amount' => $row->amount ?? null,
                    'currency' => $row->currency ?? 'RUB',
                    'starts_at' => $row->starts_at,
                    'ends_at' => $row->ends_at,
                    'status' => $row->status ?? 'active',
                    'last_payment_id' => $row->last_payment_id,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }

            Schema::drop('pro_subscriptions');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');

        Schema::create('pro_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('plan', 32)->default('pro');
            $table->decimal('amount', 10, 2)->default(790);
            $table->string('currency', 3)->default('RUB');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->index();
            $table->string('status', 32)->default('active');
            $table->unsignedBigInteger('last_payment_id')->nullable();
            $table->timestamps();
        });

        DB::table('subscription_plans')->whereIn('slug', ['free', 'pro', 'team'])->delete();
    }
};
