<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DM Sans', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    .wrap { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e8e0d5; }
    .header { background: linear-gradient(135deg, #2C1A0E, #4A2C18); padding: 28px 32px; }
    .header-logo { display: flex; align-items: center; gap: 10px; }
    .logo-icon { width: 32px; height: 32px; background: #C8A26B; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; }
    .logo-text { color: #fff; font-size: 18px; font-weight: 600; }
    .header h1 { color: #fff; font-size: 20px; margin: 16px 0 4px; }
    .header p { color: rgba(255,255,255,0.6); font-size: 13px; margin: 0; }
    .body { padding: 32px; }
    .field { margin-bottom: 20px; }
    .field-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #9B7B5A; margin-bottom: 4px; }
    .field-value { font-size: 15px; color: #2C2417; line-height: 1.6; }
    .message-box { background: #FFFCF7; border: 1px solid #F0E8DC; border-radius: 8px; padding: 16px; margin-top: 4px; }
    .divider { height: 1px; background: #F0E8DC; margin: 24px 0; }
    .meta { font-size: 12px; color: #aaa; }
    .footer { background: #f9f6f2; padding: 20px 32px; text-align: center; }
    .footer p { font-size: 12px; color: #aaa; margin: 0; }
    .reply-btn { display: inline-block; margin-top: 12px; padding: 10px 24px; background: #C8A26B; color: #fff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 600; }
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="header-logo">
            <span class="logo-icon">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </span>
            <span class="logo-text">TheDay</span>
        </div>
        <h1>Pesan Kontak Baru</h1>
        <p>Diterima {{ $submittedAt }}</p>
    </div>

    <div class="body">
        <div class="field">
            <div class="field-label">Nama</div>
            <div class="field-value">{{ $senderName }}</div>
        </div>
        <div class="field">
            <div class="field-label">Email</div>
            <div class="field-value">
                <a href="mailto:{{ $senderEmail }}" style="color: #C8A26B;">{{ $senderEmail }}</a>
            </div>
        </div>
        <div class="field">
            <div class="field-label">Topik</div>
            <div class="field-value">{{ $topic }}</div>
        </div>
        <div class="field">
            <div class="field-label">Pesan</div>
            <div class="message-box field-value">{{ $messageBody }}</div>
        </div>

        <div class="divider"></div>

        <div style="text-align: center;">
            <a href="mailto:{{ $senderEmail }}?subject=Re: {{ urlencode($topic) }}" class="reply-btn">
                Balas Pesan →
            </a>
        </div>

        <div class="divider"></div>
        <div class="meta">Dikirim dari halaman /kontak TheDay pada {{ $submittedAt }}</div>
    </div>

    <div class="footer">
        <p>TheDay — Platform Undangan Pernikahan Digital Indonesia</p>
        <p style="margin-top:4px;"><a href="https://theday.id" style="color:#C8A26B;">theday.id</a></p>
    </div>
</div>
</body>
</html>
