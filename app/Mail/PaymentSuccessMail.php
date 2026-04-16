<?php

// app/Mail/PaymentSuccessMail.php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User         $user,
        public readonly Transaction  $transaction,
        public readonly Subscription $subscription,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pembayaran Berhasil — Selamat datang di Premium! 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.payment-success',
            with: [
                'userName'      => $this->user->name,
                'invoiceNumber' => $this->transaction->invoice_number,
                'planName'      => $this->transaction->plan->name,
                'amount'        => 'Rp ' . number_format((int) $this->transaction->amount, 0, ',', '.'),
                'paidAt'        => $this->transaction->paid_at?->format('d M Y, H:i') . ' WIB',
                'expiresAt'     => $this->subscription->expires_at?->format('d M Y'),
                'invoiceUrl'    => route('dashboard.transactions.invoice', $this->transaction->id),
            ],
        );
    }
}
