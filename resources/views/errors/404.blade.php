<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Halaman Tidak Ditemukan | {{ config('app.name', 'TheDay') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|playfair-display:400,600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Figtree', sans-serif;
            background-color: #FFFDF7;
            color: #1c1917;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }

        .card {
            background: #fff;
            border: 1px solid #e7e5e4;
            border-radius: 1.5rem;
            padding: 3rem 2.5rem;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }

        /* Logo */
        .logo {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 2rem;
            text-decoration: none;
        }
        .logo-icon {
            width: 2rem; height: 2rem;
            background: #92A89C;
            border-radius: .5rem;
            display: flex; align-items: center; justify-content: center;
        }
        .logo-icon svg { width: 1rem; height: 1rem; color: #fff; }
        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.125rem;
            font-weight: 600;
            color: #1c1917;
            letter-spacing: -.01em;
        }

        /* Illustration area */
        .illustration {
            width: 5rem; height: 5rem;
            background: #EFF2F0;
            border-radius: 1.25rem;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .illustration svg { width: 2.5rem; height: 2.5rem; color: #73877C; }

        /* Number */
        .number {
            font-family: 'Playfair Display', serif;
            font-size: 5rem;
            font-weight: 600;
            line-height: 1;
            color: #92A89C;
            margin-bottom: .75rem;
            letter-spacing: -.03em;
        }

        /* Decorative line */
        .divider {
            width: 3rem; height: 1px;
            background: #92A89C;
            margin: 0 auto 1.25rem;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1c1917;
            margin-bottom: .5rem;
        }

        p {
            font-size: .875rem;
            color: #78716c;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        /* Buttons */
        .btn-primary {
            display: inline-block;
            padding: .75rem 1.75rem;
            background: #92A89C;
            color: #fff;
            border-radius: .75rem;
            font-size: .875rem;
            font-weight: 600;
            text-decoration: none;
            transition: opacity .15s;
            margin-right: .5rem;
        }
        .btn-primary:hover { opacity: .88; }

        .btn-secondary {
            display: inline-block;
            padding: .75rem 1.75rem;
            background: transparent;
            color: #78716c;
            border: 1px solid #e7e5e4;
            border-radius: .75rem;
            font-size: .875rem;
            font-weight: 500;
            text-decoration: none;
            transition: background .15s;
        }
        .btn-secondary:hover { background: #f5f5f4; }
        button.btn-secondary { cursor: pointer; font-family: inherit; }

        .actions { display: flex; gap: .5rem; justify-content: center; flex-wrap: wrap; }

        footer {
            margin-top: 2rem;
            font-size: .75rem;
            color: #a8a29e;
        }
    </style>
</head>
<body>

    <div class="card">

        <!-- Logo -->
        <a href="/" class="logo">
            <div class="logo-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>
            <span class="logo-text">TheDay</span>
        </a>

        <!-- Illustration -->
        <div class="illustration">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <!-- 404 -->
        <div class="number">404</div>
        <div class="divider"></div>

        <h1>Halaman Tidak Ditemukan</h1>
        <p>
            Maaf, halaman yang kamu cari tidak ada atau sudah dipindahkan.<br>
            Mungkin URL-nya salah, atau undangan ini sudah tidak aktif.
        </p>

        <div class="actions">
            <a href="/" class="btn-primary">Ke Beranda</a>
            <button onclick="history.back()" class="btn-secondary">Kembali</button>
        </div>

    </div>

    <footer>© {{ date('Y') }} TheDay — Undangan Digital Pernikahan</footer>

</body>
</html>
