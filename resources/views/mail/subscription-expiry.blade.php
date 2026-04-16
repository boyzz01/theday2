<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f9f5ee; margin: 0; padding: 32px 16px; color: #2C2417; }
        .card { background: #fff; max-width: 520px; margin: 0 auto; border-radius: 16px; padding: 40px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); }
        .logo { font-size: 22px; font-weight: 700; color: #C8A26B; letter-spacing: -0.5px; margin-bottom: 24px; }
        .badge-warn { display: inline-block; background: #FEF3C7; color: #92400E; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 999px; margin-bottom: 24px; }
        .badge-exp { display: inline-block; background: #FEE2E2; color: #991B1B; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 999px; margin-bottom: 24px; }
        h1 { font-size: 20px; font-weight: 700; color: #2C2417; margin: 0 0 8px; }
        p { color: #6B5B3E; font-size: 15px; line-height: 1.6; margin: 0 0 12px; }
        .btn { display: block; text-align: center; background: #C8A26B; color: #fff; text-decoration: none; padding: 14px 24px; border-radius: 12px; font-weight: 600; font-size: 15px; margin: 24px 0 0; }
        .footer { text-align: center; color: #B0956C; font-size: 12px; margin-top: 32px; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">TheDay</div>

    @if($daysRemaining === 0)
        <span class="badge-exp">Paket Berakhir</span>
        <h1>Paket Premiummu Telah Berakhir</h1>
        <p>Hei {{ $userName }}, paket Premium kamu telah berakhir hari ini. Undanganmu masih bisa diakses, namun fitur premium sudah tidak aktif.</p>
        <p>Perpanjang sekarang untuk mengembalikan akses penuh ke semua fitur.</p>
    @elseif($daysRemaining === 1)
        <span class="badge-warn">⏰ Berakhir Besok</span>
        <h1>Paket Premiummu Berakhir Besok</h1>
        <p>Hei {{ $userName }}, paket Premiummu akan berakhir besok, <strong>{{ $expiresAt }}</strong>. Perpanjang sekarang agar tidak terputus.</p>
    @else
        <span class="badge-warn">⏰ Pengingat</span>
        <h1>Paket Premiummu Berakhir dalam {{ $daysRemaining }} Hari</h1>
        <p>Hei {{ $userName }}, paket Premiummu akan berakhir pada <strong>{{ $expiresAt }}</strong>. Pastikan kamu memperpanjang agar undangan tetap aktif dengan semua fitur.</p>
    @endif

    <a href="{{ $renewUrl }}" class="btn">Perpanjang Premium →</a>

    <div class="footer">
        <p style="margin:0">Pertanyaan? Hubungi kami di <strong>hello@theday.id</strong></p>
        <p style="margin:8px 0 0">TheDay — Undangan Pernikahan Digital</p>
    </div>
</div>
</body>
</html>
