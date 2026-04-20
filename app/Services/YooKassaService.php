<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class YooKassaService
{
    private const BASE_URL = 'https://api.yookassa.ru/v3';

    /**
     * @param array<string, mixed> $payload
     */
    public function createPayment(array $payload, string $idempotenceKey): Response
    {
        return Http::withBasicAuth(
            (string) config('services.yookassa.shop_id'),
            (string) config('services.yookassa.secret_key')
        )
            ->withHeaders([
                'Idempotence-Key' => $idempotenceKey,
            ])
            ->post(self::BASE_URL.'/payments', $payload);
    }

    public function getPayment(string $paymentId): Response
    {
        return Http::withBasicAuth(
            (string) config('services.yookassa.shop_id'),
            (string) config('services.yookassa.secret_key')
        )->get(self::BASE_URL.'/payments/'.$paymentId);
    }
}
