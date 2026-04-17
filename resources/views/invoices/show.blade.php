<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $transaction->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #fff; color: #2C2417; padding: 40px; max-width: 680px; margin: 0 auto; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 24px; border-bottom: 2px solid #E8DFD0; }
        .brand { font-size: 24px; font-weight: 800; color: #C8A26B; letter-spacing: -0.5px; }
        .brand-sub { font-size: 12px; color: #9C8B72; margin-top: 2px; }
        .invoice-meta { text-align: right; }
        .invoice-meta h2 { font-size: 22px; font-weight: 700; color: #2C2417; }
        .invoice-meta p { font-size: 13px; color: #9C8B72; margin-top: 4px; }

        .status-badge { display: inline-block; padding: 4px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; }
        .status-paid { background: #D1FAE5; color: #065F46; }
        .status-pending { background: rgba(200,162,107,0.15); color: #C8A26B; }
        .status-failed { background: #FEE2E2; color: #991B1B; }

        .section { margin-bottom: 32px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9C8B72; margin-bottom: 12px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .info-block p:first-child { font-size: 11px; color: #9C8B72; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .info-block p:last-child { font-size: 14px; font-weight: 600; color: #2C2417; }

        table { width: 100%; border-collapse: collapse; }
        thead th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #9C8B72; padding: 10px 12px; text-align: left; border-bottom: 1px solid #E8DFD0; }
        tbody td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid #F3EFE8; }
        .total-row { font-weight: 700; font-size: 15px; }

        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #E8DFD0; display: flex; justify-content: space-between; align-items: center; }
        .footer-left { font-size: 12px; color: #9C8B72; line-height: 1.6; }
        .print-btn { display: inline-block; background: #C8A26B; color: #fff; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; }

        @media print {
            body { padding: 20px; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div>
            <div class="brand">TheDay</div>
            <div class="brand-sub">Undangan Pernikahan Digital</div>
        </div>
        <div class="invoice-meta">
            <h2>INVOICE</h2>
            <p>{{ $transaction->invoice_number }}</p>
            <p style="margin-top:8px">
                @php
                    $statusClass = match($transaction->status->value ?? $transaction->status) {
                        'paid'    => 'status-paid',
                        'pending' => 'status-pending',
                        default   => 'status-failed',
                    };
                    $statusLabel = match($transaction->status->value ?? $transaction->status) {
                        'paid'    => 'Lunas',
                        'pending' => 'Menunggu',
                        default   => 'Gagal',
                    };
                @endphp
                <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
            </p>
        </div>
    </div>

    <!-- Billing Info -->
    <div class="section">
        <div class="section-title">Informasi Pembayaran</div>
        <div class="info-grid">
            <div class="info-block">
                <p>Tagihan Kepada</p>
                <p>{{ $transaction->user->name }}</p>
            </div>
            <div class="info-block">
                <p>Email</p>
                <p>{{ $transaction->user->email }}</p>
            </div>
            <div class="info-block">
                <p>Tanggal Pembayaran</p>
                <p>{{ $transaction->paid_at?->format('d M Y') ?? '-' }}</p>
            </div>
            <div class="info-block">
                <p>Metode Pembayaran</p>
                <p>Midtrans</p>
            </div>
        </div>
    </div>

    <!-- Item Table -->
    <div class="section">
        <div class="section-title">Detail Transaksi</div>
        <table>
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th style="text-align:center">Qty</th>
                    <th style="text-align:right">Harga</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Paket {{ $transaction->plan->name }} TheDay<br>
                        <span style="font-size:12px;color:#9C8B72">30 hari akses premium</span>
                    </td>
                    <td style="text-align:center">1</td>
                    <td style="text-align:right">Rp {{ number_format((int) $transaction->amount, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" style="text-align:right;color:#9C8B72;font-weight:400;font-size:13px">Total</td>
                    <td style="text-align:right">Rp {{ number_format((int) $transaction->amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-left">
            <strong>TheDay</strong><br>
            hello@theday.id<br>
            theday.id
        </div>
        <button class="print-btn" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

</body>
</html>
