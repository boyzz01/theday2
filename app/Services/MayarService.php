<?php

// app/Services/MayarService.php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class MayarService
{
    public function createInvoice(Transaction $transaction, User $user, string $itemName): array
    {
        $quantity = $transaction->addon_quantity ?? 1;
        $rate     = $transaction->addon_quantity ? 15000 : (int) $transaction->amount;

        $response = Http::withToken(config('mayar.api_key'))
            ->post(config('mayar.base_url') . '/invoice/create', [
                'name'        => $user->name,
                'email'       => $user->email,
                'mobile'      => $user->phone ?? '',
                'redirectUrl' => url('/payment/return') . '?txn=' . $transaction->id,
                'expiredAt'   => now()->addHours(24)->utc()->toIso8601String(),
                'items'       => [
                    [
                        'quantity'    => $quantity,
                        'rate'        => $rate,
                        'description' => $itemName,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Mayar API error: ' . $response->body());
        }

        $data = $response->json('data');

        return [
            'payment_url'      => $data['link'],
            'mayar_invoice_id' => $data['id'],
        ];
    }

    public function getInvoice(string $mayarInvoiceId): array
    {
        $response = Http::withToken(config('mayar.api_key'))
            ->get(config('mayar.base_url') . '/invoice/' . $mayarInvoiceId);

        if (! $response->successful()) {
            throw new \RuntimeException('Mayar API error: ' . $response->body());
        }

        return $response->json('data') ?? [];
    }
}
