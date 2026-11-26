<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Bina Mandiri PS</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <script src="{{ asset('js/login.js') }}" defer></script>
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-story" aria-label="Bina Mandiri PS">
            <div class="login-brand-lockup">
                <span class="brand-icon" aria-label="Logo BM">BM</span>
                <h1 class="login-brand-name">BINA MANDIRI <span>PS</span></h1>
                <div class="login-shop-icon" aria-hidden="true">
                    <span></span>
                    <svg viewBox="0 0 48 48" fill="none">
                        <path d="M7 20h34l-3-11H10L7 20Z"/>
                        <path d="M9 20v20h30V20M18 40V28h12v12"/>
                        <path d="M7 20c0 3 2 5 5 5s5-2 5-5c0 3 2 5 5 5s5-2 5-5c0 3 2 5 5 5s5-2 5-5c0 3 2 5 5 5s5-2 5-5"/>
                    </svg>
                    <span></span>
                </div>
            </div>
            <div class="login-wave" aria-hidden="true">
                <svg viewBox="0 0 1000 220" preserveAspectRatio="none">
                    <path class="login-wave-back" d="M0 104C145 70 245 161 410 190s330-27 590-31v61H0Z"/>
                    <path class="login-wave-front" d="M0 142c142-30 215 36 360 64s390-32 640-56v70H0Z"/>
                    <path class="login-wave-gold" d="M0 113c142-31 228 46 378 78s375-29 622-48"/>
                </svg>
            </div>
        </section>
        <section class="login-form" aria-labelledby="login-title">
            <div class="login-form-inner">
                <h2 id="login-title">Masuk ke Sistem</h2>
                <p class="muted">Gunakan akun Owner atau Kasir Anda.</p>
                <span class="login-accent" aria-hidden="true"></span>
            @if($errors->any())
                <div class="notice error" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="post" action="{{ route('login.process') }}">
                @csrf
                <div class="login-field">
                    <label for="username">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/></svg>
                        Nama pengguna
                    </label>
                    <div class="login-input-wrap">
                        <svg class="login-input-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/></svg>
                        <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus maxlength="50" placeholder="Masukkan nama pengguna">
                    </div>
                </div>
                <div class="login-field">
                    <label for="password">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2Zm-7-2a2 2 0 1 1 4 0v2h-4V7Zm3 9.73V19h-2v-2.27a2 2 0 1 1 2 0Z"/></svg>
                        Kata sandi
                    </label>
                    <div class="login-input-wrap">
                        <svg class="login-input-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2Zm-7-2a2 2 0 1 1 4 0v2h-4V7Zm3 9.73V19h-2v-2.27a2 2 0 1 1 2 0Z"/></svg>
                        <input id="password" type="password" name="password" autocomplete="current-password" required placeholder="Masukkan kata sandi">
                        <button class="login-password-toggle" type="button" data-password-toggle="password" aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg data-eye-open viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c-5.5 0-9.5 5.2-10 7 .5 1.8 4.5 7 10 7s9.5-5.2 10-7c-.5-1.8-4.5-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
                            <svg data-eye-closed viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 2 19 19-1.4 1.4-3.1-3.1a11.8 11.8 0 0 1-5.5 1.4c-5.5 0-9.5-5.2-10-7 .3-1.2 2-3.7 4.6-5.4L1.6 3.4 3 2Zm9 5c5.5 0 9.5 5.2 10 7-.2.7-.9 1.9-2.1 3.1l-3-3a4 4 0 0 0-5.5-5.5l-2.1-2.1A11 11 0 0 1 12 7Zm-2 5a2 2 0 0 0 2 2l-2-2Z"/></svg>
                        </button>
                    </div>
                </div>
                <button class="login-submit" type="submit">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17v-2h4V9h-4V7l-5 5 5 5Zm9-14H5a2 2 0 0 0-2 2v4h2V5h14v14H5v-4H3v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Z"/></svg>
                    <span>Masuk ke Sistem</span>
                </button>
            </form>
            </div>
        </section>
    </main>
</body>
</html>
