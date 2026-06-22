<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - KaryaOne</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* ===== BACKGROUND ===== */
        .bg-fullscreen {
            position: fixed;
            inset: 0;
            z-index: 0;
        }

        .bg-fullscreen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* ===== WRAPPER ===== */
        .page-wrapper {
            position: fixed;
            inset: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 1.25rem 4vw 1.25rem 0;
        }

        /* ===== FORM CARD ===== */
        .form-card {
            width: 440px;
            max-height: calc(100vh - 2.5rem);
            overflow-y: auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 1.75rem 2.25rem;
            box-shadow: 0 4px 32px rgba(0, 0, 0, 0.10);
            display: flex;
            flex-direction: column;
            justify-content: center;

            /* Sembunyikan scrollbar tapi tetap bisa scroll */
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .form-card::-webkit-scrollbar {
            display: none;
        }

        /* ===== LOGO ===== */
        .card-logo {
            text-align: center;
            margin-bottom: 1rem;
        }

        .card-logo img {
            height: 110px;
            width: auto;
            display: block;
            margin: 0 auto;
        }

        .card-logo .tagline {
            font-size: 16px;
            color: #010102;
            margin: 0.25rem 0 0;
        }

        /* ===== DIVIDER ===== */
        .card-divider {
            border: none;
            border-top: 1px solid #f0f0f0;
            margin: 0 0 1rem;
        }

        /* ===== LABEL ===== */
        .lf-label {
            display: block;
            font-size: 12.5px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 5px;
        }

        /* ===== INPUT WRAP ===== */
        .lf-input-wrap {
            position: relative;
            margin-bottom: 0.85rem;
        }

        .lf-input-wrap .icon-left {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 12px;
            font-size: 15px;
            color: #93a3b8;
            pointer-events: none;
        }

        .lf-input-wrap .icon-right {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 12px;
            font-size: 15px;
            color: #93a3b8;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        /* ===== INPUT ===== */
        .lf-input {
            width: 100%;
            background: #eef2fb;
            border: 1.5px solid #e2e8f4;
            border-radius: 10px;
            padding: 0.65rem 1rem 0.65rem 2.4rem;
            font-size: 13.5px;
            color: #1e293b;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        .lf-input:focus {
            border-color: #1a4fd6;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(26, 79, 214, 0.08);
        }

        .lf-input::placeholder {
            color: #a0aec0;
            font-size: 13px;
        }

        .lf-input.has-eye {
            padding-right: 2.5rem;
        }

        /* ===== REMEMBER & LUPA ===== */
        .lf-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .lf-remember {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 12.5px;
            color: #6b7280;
            cursor: pointer;
        }

        .lf-remember input[type="checkbox"] {
            width: 14px;
            height: 14px;
            accent-color: #1a4fd6;
            cursor: pointer;
        }

        .lf-forgot {
            font-size: 12.5px;
            font-weight: 600;
            color: #F97316;
            text-decoration: none;
        }

        /* ===== TOMBOL MASUK ===== */
        .lf-btn {
            width: 100%;
            background: #1a4fd6;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: background 0.2s;
            margin-bottom: 0.85rem;
            letter-spacing: 0.03em;
        }

        .lf-btn:hover {
            background: #1440b8;
        }

        /* ===== DIVIDER ATAU ===== */
        .lf-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0.85rem;
        }

        .lf-divider div {
            flex: 1;
            border-top: 1px solid #efefef;
        }

        .lf-divider span {
            font-size: 12px;
            color: #9ca3af;
        }

        /* ===== GOOGLE ===== */
        .lf-google {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 1.5px solid #e2e8f4;
            border-radius: 10px;
            padding: 0.65rem;
            font-size: 13.5px;
            color: #374151;
            background: #fff;
            cursor: pointer;
            transition: background 0.2s;
        }

        .lf-google:hover {
            background: #f8faff;
        }

        /* ===== ERROR ===== */
        .lf-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 10px;
            padding: 0.6rem 0.85rem;
            margin-bottom: 0.85rem;
            font-size: 12.5px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        /* ===== FOOTER ===== */
        .lf-footer {
            text-align: center;
            font-size: 11px;
            color: #c4c9d4;
            margin-top: 1rem;
        }

        /* ===== RESPONSIVE MOBILE ===== */
        @media (max-width: 767px) {
            .bg-fullscreen {
                display: none;
            }

            .page-wrapper {
                position: relative;
                inset: auto;
                display: flex;
                justify-content: center;
                align-items: flex-start;
                padding: 0;
                min-height: 100vh;
                background: #fff;
            }

            .form-card {
                width: 100%;
                max-width: 100%;
                max-height: none;
                min-height: 100vh;
                border-radius: 0;
                box-shadow: none;
                padding: 2rem 1.5rem;
                justify-content: center;
            }

            .card-logo img {
                height: 80px;
            }

            .card-logo .tagline {
                font-size: 13px;
            }
        }

        /* ===== LAYAR SANGAT PENDEK (misal 768px height) ===== */
        @media (max-height: 800px) {
            .card-logo img {
                height: 80px;
            }

            .card-logo {
                margin-bottom: 0.75rem;
            }

            .card-divider {
                margin-bottom: 0.75rem;
            }

            .lf-input-wrap {
                margin-bottom: 0.65rem;
            }

            .lf-row {
                margin-bottom: 0.75rem;
            }

            .lf-btn {
                margin-bottom: 0.65rem;
            }

            .lf-divider {
                margin-bottom: 0.65rem;
            }

            .lf-footer {
                margin-top: 0.75rem;
            }
        }
    </style>
</head>

<body>

    {{-- Background Full Layar --}}
    <div class="bg-fullscreen">
        <img src="{{ asset('images/gambar_login.png') }}" alt="KaryaOne Background">
    </div>

    {{-- Page Wrapper --}}
    <div class="page-wrapper">

        {{-- Form Card --}}
        <div class="form-card">

            {{-- Logo --}}
            <div class="card-logo">
                <img src="{{ asset('images/logo_karyaone.png') }}" alt="KaryaOne Logo">
                <p class="tagline"><b>Work Seen. Effort Valued.</b></p>
            </div>

            <hr class="card-divider">

            @if ($errors->any())
                <div class="lf-error">
                    <i class="ti ti-alert-circle" style="font-size: 15px;"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf

                {{-- Email --}}
                <label class="lf-label">Email</label>
                <div class="lf-input-wrap">
                    <i class="ti ti-mail icon-left"></i>
                    <input type="text" name="email" value="{{ old('email') }}" class="lf-input"
                        placeholder="Masukkan email Anda">
                </div>

                {{-- Password --}}
                <label class="lf-label">Password</label>
                <div class="lf-input-wrap">
                    <i class="ti ti-lock icon-left"></i>
                    <input type="password" name="password" id="password" class="lf-input has-eye"
                        placeholder="Masukkan password Anda">
                    <button type="button" class="icon-right" onclick="togglePassword()"
                        aria-label="Tampilkan password">
                        <i class="ti ti-eye" id="eye-toggle"></i>
                    </button>
                </div>

                {{-- Remember & Lupa --}}
                <div class="lf-row">
                    <label class="lf-remember">
                        <input type="checkbox" name="remember"> Ingat saya
                    </label>
                    <a href="#" class="lf-forgot">Lupa password?</a>
                </div>

                {{-- Tombol Masuk --}}
                <button type="submit" class="lf-btn">
                    <i class="ti ti-login" style="font-size: 17px;"></i>
                    Masuk
                </button>

                {{-- Divider --}}
                <div class="lf-divider">
                    <div></div><span>atau</span>
                    <div></div>
                </div>

                {{-- Google --}}
                <button type="button" class="lf-google">
                    <svg width="17" height="17" viewBox="0 0 48 48">
                        <path fill="#4285F4"
                            d="M44.5 20H24v8.5h11.8C34.7 33.9 30.1 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.2-2.7-.5-4z" />
                        <path fill="#34A853"
                            d="M6.3 14.7l7 5.1C15 16.2 19.1 13 24 13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 16.3 2 9.6 7.4 6.3 14.7z" />
                        <path fill="#FBBC05"
                            d="M24 46c5.5 0 10.5-1.8 14.4-4.9l-6.7-5.5C29.6 37.3 26.9 38 24 38c-6 0-10.6-3-11.7-8.3l-7 5.4C9.4 42.5 16.2 46 24 46z" />
                        <path fill="#EA4335"
                            d="M44.5 20H24v8.5h11.8c-.9 2.7-2.8 4.9-5.3 6.5l6.7 5.5C41.6 37.4 45 31.2 45 24c0-1.3-.2-2.7-.5-4z" />
                    </svg>
                    Masuk dengan Google
                </button>
            </form>

            <p class="lf-footer">© {{ date('Y') }} KaryaOne. All rights reserved.</p>
        </div>

    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-toggle');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ti ti-eye-off';
            } else {
                input.type = 'password';
                icon.className = 'ti ti-eye';
            }
        }
    </script>
</body>

</html>
