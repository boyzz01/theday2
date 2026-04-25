<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $transaction->invoice_number }}</title>
    <style>
        :root {
            --brand-primary:       #92A89C;
            --brand-primary-hover: #73877C;
            --brand-primary-soft:  #B8C7BF;
            --brand-premium:       #C8A26B;
            --brand-premium-hover: #B8905A;
            --brand-text:          #2C2417;
            --brand-bg:            #F4F7F5;
            --brand-muted:         rgba(44, 36, 23, 0.45);
            --brand-border:        rgba(146, 168, 156, 0.25);
            --brand-border-warm:   rgba(200, 162, 107, 0.2);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: var(--brand-bg);
            color: var(--brand-text);
            padding: 32px 16px;
            font-size: 13px;
            line-height: 1.5;
        }

        .page {
            background: #fff;
            max-width: 680px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 20px rgba(44, 36, 23, 0.08);
        }

        /* Accent bar */
        .accent-bar {
            height: 4px;
            background: linear-gradient(90deg, var(--brand-premium) 0%, var(--brand-primary) 100%);
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 32px 40px 24px;
            border-bottom: 1px solid var(--brand-border);
        }

        .logo img   { height: 30px; display: block; }
        .logo-text  { font-size: 20px; font-weight: 800; color: var(--brand-premium); letter-spacing: -0.5px; }
        .brand-sub  { font-size: 11px; color: var(--brand-muted); margin-top: 4px; letter-spacing: 0.3px; }

        .invoice-meta { text-align: right; }
        .invoice-tag {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--brand-primary);
            margin-bottom: 6px;
        }
        .invoice-number { font-size: 16px; font-weight: 700; color: var(--brand-text); }
        .invoice-date   { font-size: 11px; color: var(--brand-muted); margin-top: 4px; }

        /* Status badge */
        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 12px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .badge-paid    {
            background: rgba(146, 168, 156, 0.15);
            color: var(--brand-primary-hover);
            border: 1px solid rgba(146, 168, 156, 0.3);
        }
        .badge-pending {
            background: rgba(200, 162, 107, 0.15);
            color: var(--brand-premium);
            border: 1px solid rgba(200, 162, 107, 0.3);
        }
        .badge-failed  {
            background: rgba(180, 50, 50, 0.08);
            color: #991B1B;
            border: 1px solid rgba(180, 50, 50, 0.15);
        }

        /* Body */
        .body { padding: 28px 40px; }

        .section { margin-bottom: 28px; }
        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--brand-primary);
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--brand-border);
        }

        /* Info grid */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .info-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--brand-muted); margin-bottom: 3px; }
        .info-value { font-size: 13px; font-weight: 600; color: var(--brand-text); }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        thead th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--brand-muted);
            padding: 10px 14px;
            text-align: left;
            background: var(--brand-bg);
            border-top: 1px solid var(--brand-border);
            border-bottom: 1px solid var(--brand-border);
        }
        tbody td {
            padding: 16px 14px;
            font-size: 13px;
            color: var(--brand-text);
            border-bottom: 1px solid var(--brand-border);
        }
        .item-sub { font-size: 11px; color: var(--brand-muted); margin-top: 3px; }

        /* Total */
        .total-box {
            margin-top: 12px;
            padding: 16px 14px;
            background: var(--brand-bg);
            border-radius: 10px;
            border: 1px solid var(--brand-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-label  { font-size: 12px; color: var(--brand-muted); font-weight: 500; }
        .total-amount { font-size: 18px; font-weight: 800; color: var(--brand-text); }

        /* Footer */
        .footer {
            padding: 20px 40px 28px;
            border-top: 1px solid var(--brand-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .footer-info { font-size: 11px; color: var(--brand-muted); line-height: 1.7; }
        .footer-info strong { color: var(--brand-text); }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--brand-primary);
            color: #fff;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            white-space: nowrap;
            flex-shrink: 0;
            transition: background 150ms;
        }
        .btn-print:hover { background: var(--brand-primary-hover); }

        @media print {
            body { background: #fff; padding: 0; }
            .page { box-shadow: none; border-radius: 0; }
            .accent-bar { display: none; }
            .btn-print  { display: none; }
        }
    </style>
</head>
<body>

@php
    $statusClass = match($transaction->status->value ?? $transaction->status) {
        'paid'    => 'badge-paid',
        'pending' => 'badge-pending',
        default   => 'badge-failed',
    };
    $statusLabel = match($transaction->status->value ?? $transaction->status) {
        'paid'    => 'Lunas',
        'pending' => 'Menunggu',
        default   => 'Gagal',
    };

    $rawMethod    = $transaction->getRawOriginal('payment_method') ?? '';
    $paymentLabel = \App\Enums\PaymentMethod::tryFrom($rawMethod)?->label()
        ?? (strlen($rawMethod) ? ucfirst($rawMethod) : '—');

    $paidAt    = $transaction->paid_at?->locale('id')->translatedFormat('d F Y') ?? '-';
    $createdAt = $transaction->created_at->locale('id')->translatedFormat('d F Y');
@endphp

<div class="page">
    <div class="accent-bar"></div>

    <!-- Header -->
    <div class="header">
        <div>
            <div class="logo">
                <img src="{{ asset('image/logo.svg') }}" alt="TheDay"
                     onerror="this.style.display='none';document.getElementById('logo-fallback').style.display='block'">
                <div id="logo-fallback" class="logo-text" style="display:none">TheDay</div>
            </div>
            <div class="brand-sub">Undangan Pernikahan Digital</div>
        </div>
        <div class="invoice-meta">
            <div class="invoice-tag">Invoice</div>
            <div class="invoice-number">{{ $transaction->invoice_number }}</div>
            <div class="invoice-date">{{ $createdAt }}</div>
            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>
    </div>

    <!-- Body -->
    <div class="body">

        <!-- Billing Info -->
        <div class="section">
            <div class="section-title">Informasi Pembayaran</div>
            <div class="info-grid">
                <div>
                    <div class="info-label">Tagihan Kepada</div>
                    <div class="info-value">{{ $transaction->user->name }}</div>
                </div>
                <div>
                    <div class="info-label">Email</div>
                    <div class="info-value">{{ $transaction->user->email }}</div>
                </div>
                <div>
                    <div class="info-label">Tanggal Pembayaran</div>
                    <div class="info-value">{{ $paidAt }}</div>
                </div>
                <div>
                    <div class="info-label">Metode Pembayaran</div>
                    <div class="info-value">{{ $paymentLabel }}</div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="section">
            <div class="section-title">Detail Transaksi</div>
            <table>
                <thead>
                    <tr>
                        <th>Deskripsi</th>
                        <th style="text-align:center;width:60px">Qty</th>
                        <th style="text-align:right;width:150px">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            @if($transaction->isAddonPurchase())
                                Tambah Undangan ×{{ $transaction->addon_quantity }}
                                <div class="item-sub">Add-on undangan tambahan — TheDay</div>
                            @else
                                Paket {{ $transaction->plan->name ?? '—' }}
                                <div class="item-sub">30 hari akses premium — TheDay</div>
                            @endif
                        </td>
                        <td style="text-align:center">1</td>
                        <td style="text-align:right;font-weight:600">
                            Rp {{ number_format((int) $transaction->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="total-box">
                <span class="total-label">Total Pembayaran</span>
                <span class="total-amount">Rp {{ number_format((int) $transaction->amount, 0, ',', '.') }}</span>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-info">
            <strong>TheDay</strong><br>
            hello@theday.id &nbsp;·&nbsp; theday.id<br>
            Dokumen ini diterbitkan secara otomatis.
        </div>
        <button class="btn-print" onclick="window.print()">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Cetak / Simpan PDF
        </button>
    </div>
</div>

</body>
</html>
