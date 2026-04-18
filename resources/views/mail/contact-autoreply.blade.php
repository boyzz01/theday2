<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    .wrap { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e8e0d5; }
    .header { background: linear-gradient(135deg, #2C1A0E, #4A2C18); padding: 32px; text-align: center; }
    .logo-icon { width: 48px; height: 48px; background: #C8A26B; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; }
    .header h1 { color: #fff; font-size: 22px; margin: 0 0 8px; }
    .header p { color: rgba(255,255,255,0.65); font-size: 14px; margin: 0; }
    .body { padding: 36px 32px; }
    .body p { font-size: 15px; color: #4A3728; line-height: 1.75; margin: 0 0 16px; }
    .highlight { background: #FFFCF7; border-left: 3px solid #C8A26B; border-radius: 0 8px 8px 0; padding: 14px 18px; margin: 20px 0; }
    .highlight p { margin: 0; font-size: 14px; color: #6B5040; }
    .cta { text-align: center; margin: 28px 0; }
    .cta a { display: inline-block; padding: 13px 32px; background: #C8A26B; color: #fff; text-decoration: none; border-radius: 10px; font-size: 14px; font-weight: 600; }
    .footer { background: #f9f6f2; padding: 20px 32px; text-align: center; border-top: 1px solid #F0E8DC; }
    .footer p { font-size: 12px; color: #aaa; margin: 0; }
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div>
            <div class="logo-icon" style="display:inline-flex;">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>
        </div>
        <h1>Pesan kamu sudah kami terima 🤍</h1>
        <p>Halo {{ $senderName }}, terima kasih sudah menghubungi TheDay!</p>
    </div>

    <div class="body">
        <p>Hei <strong>{{ $senderName }}</strong>,</p>
        <p>Kami sudah menerima pesanmu dengan topik <strong>"{{ $topic }}"</strong>. Tim kami akan segera meninjau dan membalasnya.</p>

        <div class="highlight">
            <p>⏰ <strong>Jam layanan:</strong> Senin–Sabtu, 09.00–21.00 WIB</p>
            <p style="margin-top:6px;">Biasanya kami membalas dalam <strong>1×24 jam</strong> pada hari kerja.</p>
        </div>

        <p>Sambil menunggu, kamu bisa eksplorasi fitur-fitur TheDay atau cek halaman FAQ kami.</p>

        <div class="cta">
            <a href="https://theday.id">Kembali ke TheDay →</a>
        </div>

        <p style="font-size:13px; color:#aaa;">Jika kamu tidak merasa mengirim pesan ini, abaikan email ini.</p>
    </div>

    <div class="footer">
        <p>TheDay — Platform Undangan Pernikahan Digital Indonesia</p>
        <p style="margin-top:4px;"><a href="https://theday.id" style="color:#C8A26B;">theday.id</a> · <a href="mailto:hello@theday.id" style="color:#C8A26B;">hello@theday.id</a></p>
    </div>
</div>
</body>
</html>
