<?php

// app/Mail/SubscriptionExpiryMail.php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User         $user,
        public readonly Subscription $subscription,
        public readonly int          $daysRemaining, // 7, 1, or 0 (expired today)
    ) {}

    public function envelope(): Envelope
    {
        $subject = match (true) {
            $this->daysRemaining === 0 => 'Paket Premiummu telah berakhir hari ini',
            $this->daysRemaining === 1 => '⏰ Paket Premiummu berakhir besok!',
            default                    => "⏰ Paket Premiummu berakhir dalam {$this->daysRemaining} hari",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.subscription-expiry',
            with: [
                'userName'      => $this->user->name,
                'daysRemaining' => $this->daysRemaining,
                'expiresAt'     => $this->subscription->expires_at?->format('d M Y'),
                'renewUrl'      => route('dashboard.paket'),
            ],
        );
    }
}
