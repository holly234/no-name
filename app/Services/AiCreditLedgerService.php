<?php

namespace App\Services;

use App\Data\AiCreditReservation;
use App\Exceptions\InsufficientAiCredits;
use App\Models\AiCreditTransaction;
use App\Models\AiCreditWallet;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiCreditLedgerService
{
    public function grant(Business $business, int $credits, string $description, ?string $reference = null, array $metadata = []): AiCreditTransaction
    {
        if ($credits <= 0) {
            throw new \InvalidArgumentException('Granted credits must be greater than zero.');
        }

        return DB::transaction(function () use ($business, $credits, $description, $reference, $metadata) {
            $wallet = $this->lockedWallet($business);
            $wallet->increment('balance', $credits);
            $wallet->refresh();

            return AiCreditTransaction::create([
                'business_id' => $business->id,
                'type' => 'grant',
                'credits' => $credits,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'reference' => $reference,
                'metadata' => $metadata ?: null,
            ]);
        });
    }

    public function reserve(Business $business, int $credits, array $metadata = []): AiCreditReservation
    {
        if ($credits <= 0) {
            throw new \InvalidArgumentException('Reserved credits must be greater than zero.');
        }

        return DB::transaction(function () use ($business, $credits, $metadata) {
            $wallet = $this->lockedWallet($business);

            if ($wallet->balance < $credits) {
                throw new InsufficientAiCredits('Not enough AI credits.');
            }

            $reference = 'ai_res_'.Str::uuid();
            $wallet->decrement('balance', $credits);
            $wallet->refresh();

            AiCreditTransaction::create([
                'business_id' => $business->id,
                'type' => 'reservation',
                'credits' => -$credits,
                'balance_after' => $wallet->balance,
                'description' => 'AI reply credit reservation',
                'reference' => $reference,
                'metadata' => $metadata ?: null,
            ]);

            return new AiCreditReservation($reference, $credits);
        });
    }

    public function settle(Business $business, AiCreditReservation $reservation, int $actualCredits, array $metadata = []): int
    {
        return DB::transaction(function () use ($business, $reservation, $actualCredits, $metadata) {
            $existing = AiCreditTransaction::query()
                ->where('reference', $reservation->reference.':settled')
                ->first();

            if ($existing) {
                return (int) ($existing->metadata['charged_credits'] ?? $reservation->credits);
            }

            $wallet = $this->lockedWallet($business);
            $charged = max(1, $actualCredits);
            $adjustment = $reservation->credits - $charged;

            if ($adjustment < 0) {
                $extraAvailable = min($wallet->balance, abs($adjustment));
                $charged = $reservation->credits + $extraAvailable;
                $adjustment = -$extraAvailable;
            }

            if ($adjustment > 0) {
                $wallet->increment('balance', $adjustment);
            } elseif ($adjustment < 0) {
                $wallet->decrement('balance', abs($adjustment));
            }

            $wallet->increment('lifetime_used', $charged);
            $wallet->refresh();

            AiCreditTransaction::create([
                'business_id' => $business->id,
                'type' => 'usage_settlement',
                'credits' => $adjustment,
                'balance_after' => $wallet->balance,
                'description' => 'AI reply credit settlement',
                'reference' => $reservation->reference.':settled',
                'metadata' => array_merge($metadata, [
                    'reservation_reference' => $reservation->reference,
                    'reserved_credits' => $reservation->credits,
                    'charged_credits' => $charged,
                ]),
            ]);

            return $charged;
        });
    }

    public function release(Business $business, AiCreditReservation $reservation, string $reason): void
    {
        DB::transaction(function () use ($business, $reservation, $reason) {
            if (AiCreditTransaction::where('reference', $reservation->reference.':released')->exists()
                || AiCreditTransaction::where('reference', $reservation->reference.':settled')->exists()) {
                return;
            }

            $wallet = $this->lockedWallet($business);
            $wallet->increment('balance', $reservation->credits);
            $wallet->refresh();

            AiCreditTransaction::create([
                'business_id' => $business->id,
                'type' => 'reservation_release',
                'credits' => $reservation->credits,
                'balance_after' => $wallet->balance,
                'description' => 'Released failed AI reply reservation',
                'reference' => $reservation->reference.':released',
                'metadata' => [
                    'reservation_reference' => $reservation->reference,
                    'reason' => $reason,
                ],
            ]);
        });
    }

    public function creditsForTokens(int $inputTokens, int $outputTokens): int
    {
        return max(1, (int) ceil(($inputTokens + $outputTokens) / max(1, (int) config('ai.tokens_per_credit', 100))));
    }

    private function lockedWallet(Business $business): AiCreditWallet
    {
        AiCreditWallet::firstOrCreate(['business_id' => $business->id]);

        return AiCreditWallet::where('business_id', $business->id)->lockForUpdate()->firstOrFail();
    }
}
