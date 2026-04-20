<?php

namespace App\Http\Controllers;

use App\Models\MarginSnapshot;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MarginSnapshotController extends Controller
{
    public function __construct(
        private readonly SubscriptionEntitlementService $entitlements
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        $snapshots = MarginSnapshot::query()
            ->where('email', $validated['email'])
            ->orderByDesc('id')
            ->limit(25)
            ->get(['id', 'title', 'platform', 'created_at']);

        return response()->json([
            'ok' => true,
            'snapshots' => $snapshots,
            'subscription' => $this->entitlements->contextForEmail($validated['email']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'title' => ['required', 'string', 'max:180'],
            'input' => ['required', 'array'],
            'result' => ['required', 'array'],
        ]);

        $ctx = $this->entitlements->contextForEmail($validated['email']);
        if (!$ctx['can_save_snapshot']) {
            return response()->json([
                'ok' => false,
                'message' => 'Сохранение сценариев доступно на PRO или лимит библиотеки исчерпан.',
            ], 422);
        }

        $snapshot = MarginSnapshot::create([
            'email' => $validated['email'],
            'title' => $validated['title'],
            'platform' => (string) ($validated['input']['platform'] ?? 'wildberries'),
            'input_payload' => $validated['input'],
            'result_payload' => $validated['result'],
        ]);

        Cache::forget('marginflow_investor_pulse_v2');

        return response()->json([
            'ok' => true,
            'message' => 'Сценарий сохранен в библиотеке.',
            'snapshot' => $snapshot,
            'subscription' => $this->entitlements->contextForEmail($validated['email']),
        ]);
    }

    public function destroy(Request $request, MarginSnapshot $snapshot): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        if ($snapshot->email !== $validated['email']) {
            return response()->json(['ok' => false, 'message' => 'Нет доступа.'], 403);
        }

        $snapshot->delete();
        Cache::forget('marginflow_investor_pulse_v2');

        return response()->json([
            'ok' => true,
            'subscription' => $this->entitlements->contextForEmail($validated['email']),
        ]);
    }
}
