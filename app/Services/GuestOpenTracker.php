<?php

// app/Services/GuestOpenTracker.php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GuestSendStatus;
use App\Models\GuestList;
use Carbon\Carbon;

class GuestOpenTracker
{
    /**
     * Record that a guest opened their personal invitation URL.
     * - Sets first_opened_at on first open.
     * - Always updates last_opened_at.
     * - Upgrades send_status to 'opened' if not already.
     */
    public function track(GuestList $guest): void
    {
        $now  = Carbon::now();
        $data = ['last_opened_at' => $now];

        if ($guest->first_opened_at === null) {
            $data['first_opened_at'] = $now;
        }

        if ($guest->send_status !== GuestSendStatus::Opened) {
            $data['send_status'] = GuestSendStatus::Opened;
        }

        $guest->update($data);
    }
}
