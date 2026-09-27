<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · Portal Zakat</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #325C1C;
            --primary-hover: #274916;
            --primary-light: #eef4e9;
            --yellow: #FFC933;
            --yellow-light: #fff7dc;
            --text-dark: #325C1C;
            --text-muted: #8b9585;
            --border: #e2e5df;
            --white: #ffffff;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --radius-lg: 22px;
            --radius-md: 12px;
            --shadow:
                0 25px 50px -12px rgba(50, 92, 28, 0.13),
                0 10px 25px -10px rgba(50, 92, 28, 0.08);
            --transition: all 0.25s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 40px 24px;

            /* Background utama */
            background:
                radial-gradient(
                    circle at 12% 22%,
                    rgba(255, 201, 51, 0.17) 0,
                    rgba(255, 201, 51, 0.08) 15%,
                    transparent 38%
                ),
                radial-gradient(
                    circle at 88% 25%,
                    rgba(50, 92, 28, 0.08) 0,
                    rgba(50, 92, 28, 0.03) 20%,
                    transparent 42%
                ),
                linear-gradient(
                    135deg,
                    #fffdf7 0%,
                    #f9f8f0 50%,
                    #f4f3e8 100%
                );

            color: var(--text-dark);
        }

        /* Cahaya/fade di background */
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            left: -180px;
            top: -170px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 201, 51, 0.12) 0%, rgba(255, 201, 51, 0.05) 35%, transparent 70%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            width: 550px;
            height: 550px;
            right: -220px;
            top: -150px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(50, 92, 28, 0.08) 0%, rgba(50, 92, 28, 0.03) 40%, transparent 72%);
            pointer-events: none;
        }

        /* Dekorasi garis sudut kiri atas */
        body > .auth-card::before {
            content: '';
            position: fixed;
            width: 150px;
            height: 150px;
            left: -55px;
            top: -55px;
            border: 2px solid rgba(255, 201, 51, 0.35);
            border-radius: 35px;
            transform: rotate(45deg);
            pointer-events: none;
            z-index: -1;
        }

        /* Card Login */
        .auth-card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 515px;
            padding: 36px 38px 32px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(255, 255, 255, 0.85);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            animation: fadeIn 0.45s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .brand-header {
            text-align: center;
            margin-bottom: 30px;
        }

        /* Logo / badge */
        .brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 92px;
            height: 92px;
            margin-bottom: 12px;
            background: #fffdf5;
            border: 4px solid var(--yellow);
            border-radius: 50%;
            color: var(--primary);
            box-shadow:
                0 5px 15px rgba(255, 201, 51, 0.12),
                inset 0 0 0 5px rgba(255, 201, 51, 0.05);
        }

        .brand-badge svg {
            width: 48px;
            height: 48px;
            stroke-width: 1.8;
        }

        /* Judul */
        .brand-title {
            margin-bottom: 8px;
            color: var(--primary);
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }

        .brand-subtitle {
            max-width: 300px;
            margin: 0 auto;
            color: #7e8979;
            font-size: 0.88rem;
            font-weight: 400;
            line-height: 1.6;
        }

        /* Error message */
        .alert-error {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 12px 15px;
            background: var(--danger-bg);
            border: 1px solid #fecaca;
            border-radius: var(--radius-md);
            color: var(--danger);
            font-size: 0.84rem;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 0.88rem;
            font-weight: 700;
        }

        /* Input wrapper */
        .input-wrapper {
            position: relative;
            width: 100%;
        }

        /* Input */
        .form-input {
            width: 100%;
            height: 53px;
            padding: 0 16px 0 58px;
            background: #ffffff;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            outline: none;
            color: #35452f;
            font-size: 0.88rem;
            font-weight: 500;
            transition: var(--transition);
            box-shadow:
                0 2px 5px rgba(50, 92, 28, 0.02);
        }

        /* Icon email */
        .form-group:first-of-type .input-wrapper::before {
            content: '✉';
            position: absolute;
            left: 17px;
            top: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateY(-50%);
            background: #f7faf5;
            border-radius: 50%;
            color: var(--primary);
            font-size: 14px;
            z-index: 2;
            pointer-events: none;
        }

        /* Icon password */
        .form-group:nth-of-type(2) .input-wrapper::before {
            content: '🔒︎';
            position: absolute;
            left: 17px;
            top: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateY(-50%);
            background: #f7faf5;
            border-radius: 50%;
            color: var(--primary);
            font-size: 16px;
            z-index: 2;
            pointer-events: none;
        }

        /* Placeholder */
        .form-input::placeholder {
            color: #b2bab0;
            font-weight: 400;
        }

        /* Focus */
        .form-input:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow:
                0 0 0 3px rgba(50, 92, 28, 0.08);
        }

        /* Password input memiliki tombol eye */
        .form-group:nth-of-type(2) .form-input {
            padding-right: 52px !important;
        }

        /* Tombol lihat password */
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            padding: 4px;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #7e8979;
            cursor: pointer;
            transition: var(--transition);
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
        }

        /* Tombol Login */
        .btn-submit {
            width: 100%;
            height: 52px;
            margin-top: 4px;
            padding: 0 20px;
            background: var(--primary);
            border: none;
            border-radius: var(--radius-md);
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            box-shadow:
                0 8px 18px rgba(50, 92, 28, 0.16);
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow:
                0 10px 22px rgba(50, 92, 28, 0.22);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Footer */
        .auth-footer {
            margin-top: 25px;
            text-align: center;
            color: #90998c;
            font-size: 0.86rem;
            font-weight: 500;
        }

        /* Link daftar */
        .auth-link {
            color: #e9a900;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
        }

        .auth-link:hover {
            color: #c99100;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 600px) {
            body {
                padding: 24px 16px;
                overflow-y: auto;
            }
            .auth-card {
                max-width: 100%;

                padding: 30px 24px 27px;

                border-radius: 20px;
            }
            .brand-badge {
                width: 78px;
                height: 78px;
            }
            .brand-badge svg {
                width: 42px;
                height: 42px;
            }
            .brand-title {
                font-size: 1.65rem;
            }
            .brand-subtitle {
                font-size: 0.83rem;
            }
        }

        @media (max-width: 400px) {
            .auth-card {
                padding: 26px 18px 24px;
            }
            .brand-title {
                font-size: 1.5rem;
            }
            .form-input {
                height: 50px;
            }
            .btn-submit {
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="brand-header">
            <div class="brand-badge">
                <svg xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 64 64"
                    fill="currentColor"
                    aria-hidden="true">
                    <!-- Menara kiri -->
                    <path d="M9 25 L13 19 L17 25 V51 H9 Z"/>
                    <!-- Atap menara kiri -->
                    <path d="M10 19 L13 15 L16 19 Z"/>
                    <!-- Menara kanan -->
                    <path d="M47 25 L51 19 L55 25 V51 H47 Z"/>
                    <!-- Atap menara kanan -->
                    <path d="M48 19 L51 15 L54 19 Z"/>
                    <!-- Bangunan utama masjid -->
                    <path d="M16 31 L32 22 L48 31 V51 H16 Z"/>
                    <!-- Kubah masjid -->
                    <path d="M22 30 A10 10 0 0 1 42 30 Z"/>
                    <!-- Ornamen puncak kubah -->
                    <rect x="31" y="17" width="2" height="5" fill="#FFC933"/>
                    <circle cx="32" cy="16" r="2" fill="#FFC933"/>
                    <!-- Pintu masjid -->
                    <path d="M26 51 V39 A6 6 0 0 1 38 39 V51 Z" fill="#fffdf5"/>
                    <!-- Garis dasar masjid -->
                    <rect x="5" y="51" width="54" height="3" rx="1.5" fill="#FFC933"/>
                </svg>
            </div>
            <h1 class="brand-title">Login</h1>
            <p class="brand-subtitle">Silakan masuk ke akun Anda</p>
        </div>

        @if(session('error'))
            <div class="alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="width:20px; height:20px; flex-shrink:0;" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('login', [], false) }}" method="POST">
            @csrf
            
            <!-- Email Input -->
            <div class="form-group">
                <label class="form-label" for="email">Alamat Email</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-input" required autofocus placeholder="nama@email.com">
                </div>
            </div>

            <!-- Password Input -->
            <div class="form-group">
                <label class="form-label" for="password">Kata Sandi</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-input" style="padding-right: 46px;" required placeholder="Masukkan kata sandi">
                    <button type="button" class="toggle-password" onclick="togglePassword('password', 'eye-icon')" title="Lihat password">
                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Masuk Sekarang
            </button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a href="{{ route('register', [], false) }}" class="auth-link">Daftar Akun Baru</a>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === "password") {
                input.type = "text";
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.563-3.029m5.858-5.908a10.047 10.047 0 013.121-.263c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-2.112 3.826M3 3l18 18" />`;
            } else {
                input.type = "password";
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-4.477 0-8.268-2.943-9.542-7z" />`;
            }
        }
    </script>
</body>
</html>
