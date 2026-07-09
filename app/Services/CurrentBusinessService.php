<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;

class CurrentBusinessService
{
    public function resolveForUser(?User $user): ?Business
    {
        if (! $user) {
            return null;
        }

        $sessionBusinessId = session('current_business_id');

        if ($sessionBusinessId) {
            $business = $user->businesses()
                ->where('businesses.id', $sessionBusinessId)
                ->first();

            if ($business) {
                return $business;
            }
        }

        $business = $user->businesses()->orderBy('businesses.name')->first();

        if ($business) {
            session(['current_business_id' => $business->id]);
        }

        return $business;
    }

    public function switchForUser(User $user, int $businessId): ?Business
    {
        $business = $user->businesses()
            ->where('businesses.id', $businessId)
            ->first();

        if ($business) {
            session(['current_business_id' => $business->id]);
        }

        return $business;
    }
}
