<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Kata Sandi - Zakatara</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body style="background: #f7f8f2; min-height: 100vh; display: grid; place-items: center; padding: 1rem;">
    <main class="app-card" style="width: min(100%, 460px); padding: 1.5rem;">
        <h1 style="font-size: 1.5rem;">Ganti kata sandi sementara</h1>
        <p>Kata sandi panitia harus diganti sebelum membuka halaman lain.</p>

        @if ($errors->any())
            <div class="app-alert app-alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.change.store') }}">
            @csrf
            <div class="app-form-group">
                <label for="current_password" class="app-form-label">Kata sandi sementara</label>
                <input id="current_password" type="password" name="current_password" class="app-form-control" autocomplete="current-password" required>
            </div>
            <div class="app-form-group">
                <label for="password" class="app-form-label">Kata sandi baru</label>
                <input id="password" type="password" name="password" class="app-form-control" autocomplete="new-password" minlength="12" required>
                <small>Minimal 12 karakter, termasuk huruf dan angka.</small>
            </div>
            <div class="app-form-group">
                <label for="password_confirmation" class="app-form-label">Ulangi kata sandi baru</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="app-form-control" autocomplete="new-password" minlength="12" required>
            </div>
            <button type="submit" class="btn-app btn-app-primary">Simpan kata sandi</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" style="margin-top: 1rem;">
            @csrf
            <button type="submit" class="btn-app btn-app-secondary">Keluar</button>
        </form>
    </main>
</body>
</html>
