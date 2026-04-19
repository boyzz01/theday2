<?php

// app/Console/Commands/ArchiveExpiredInvitations.php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invitation;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ArchiveExpiredInvitations extends Command
{
    protected $signature   = 'invitations:archive-expired';
    protected $description = 'Arsipkan undangan milik user yang grace period-nya sudah habis dan downgrade subscription ke expired';

    public function handle(): int
    {
        $fullyExpired = Subscription::where('status', 'grace')
            ->whereNotNull('grace_until')
            ->where('grace_until', '<', now())
            ->with('user', 'plan')
            ->get();

        if ($fullyExpired->isEmpty()) {
            $this->info('Tidak ada undangan yang perlu diarsipkan.');
            return self::SUCCESS;
        }

        $totalArchived = 0;

        foreach ($fullyExpired as $sub) {
            $totalArchived += $this->downgradeUser($sub);
        }

        $this->info("Selesai. Total {$totalArchived} undangan diarsipkan.");

        return self::SUCCESS;
    }

    private function downgradeUser(Subscription $sub): int
    {
        $publishedInvitations = Invitation::where('user_id', $sub->user_id)
            ->where('status', 'published')
            ->orderByDesc('updated_at')
            ->get();

        $archived = 0;

        // Archive all except the most recently updated one
        $publishedInvitations->skip(1)->each(function (Invitation $inv) use (&$archived) {
            $inv->update(['status' => 'archived']);
            $archived++;
        });

        // Downgrade features on the kept invitation
        $kept = $publishedInvitations->first();
        if ($kept) {
            $kept->update([
                'is_password_protected' => false,
                'password'              => null,
            ]);
        }

        // Mark subscription as fully expired
        $sub->update(['status' => 'expired']);

        $this->info("  User {$sub->user->email}: archived {$archived} invitation(s), subscription → expired.");

        return $archived;
    }
}
