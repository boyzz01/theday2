<?php

// app/Console/Commands/CleanExpiredGuestDrafts.php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GuestDraft;
use Illuminate\Console\Command;

class CleanExpiredGuestDrafts extends Command
{
    protected $signature = 'guest-drafts:clean';

    protected $description = 'Hapus semua guest draft yang sudah expired';

    public function handle(): int
    {
        $count = GuestDraft::where('expires_at', '<', now())->count();

        GuestDraft::where('expires_at', '<', now())->delete();

        $this->info("Berhasil menghapus {$count} guest draft yang expired.");

        return Command::SUCCESS;
    }
}
