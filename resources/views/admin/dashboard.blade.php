<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Zakatara</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <header class="app-navbar">
        <div class="navbar-container">
            <strong class="brand-text">Zakatara · Admin</strong>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-app-logout">Keluar</button>
            </form>
        </div>
    </header>

    <main class="app-container">
        <h1>Kelola panitia</h1>
        <p>Akun panitia dibuat oleh admin dan tidak tersedia melalui pendaftaran umum.</p>

        @if (session('success'))
            <div class="app-alert app-alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="app-alert app-alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="app-card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
            <h2 class="app-card-title">Buat akun panitia</h2>
            <form method="POST" action="{{ route('admin.panitia.store') }}">
                @csrf
                <div class="app-form-group">
                    <label class="app-form-label" for="nama">Nama lengkap</label>
                    <input class="app-form-control" id="nama" name="nama" value="{{ old('nama') }}" autocomplete="name" required>
                </div>
                <div class="app-form-group">
                    <label class="app-form-label" for="email">Email</label>
                    <input class="app-form-control" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                </div>
                <div class="app-form-group">
                    <label class="app-form-label" for="password">Kata sandi sementara</label>
                    <input class="app-form-control" id="password" type="password" name="password" autocomplete="new-password" minlength="12" required>
                    <small>Minimal 12 karakter, termasuk huruf dan angka. Panitia wajib menggantinya saat masuk.</small>
                </div>
                <div class="app-form-group">
                    <label class="app-form-label" for="password_confirmation">Ulangi kata sandi</label>
                    <input class="app-form-control" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required>
                </div>
                <button type="submit" class="btn-app btn-app-primary">Buat panitia</button>
            </form>
        </section>

        <section class="app-card" style="padding: 1.5rem;">
            <h2 class="app-card-title">Daftar panitia</h2>
            <div class="app-table-responsive">
                <table class="app-table">
                    <thead><tr><th>Nama</th><th>Email</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($panitia as $anggota)
                            <tr>
                                <td>{{ $anggota->nama }}</td>
                                <td>{{ $anggota->email }}</td>
                                <td>{{ $anggota->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.panitia.active', $anggota) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $anggota->is_active ? 0 : 1 }}">
                                        <button type="submit" class="btn-app btn-app-primary">{{ $anggota->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada panitia.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="app-card" style="padding: 1.5rem; margin-top: 1.5rem;">
            <h2 class="app-card-title">Daftar bank untuk panti</h2>
            <form method="POST" action="{{ route('admin.bank.store') }}">
                @csrf
                <div class="app-form-group">
                    <label class="app-form-label" for="kode_bank">Kode internal</label>
                    <input class="app-form-control" id="kode_bank" name="kode" value="{{ old('kode') }}" placeholder="Contoh: BSI" pattern="[A-Z0-9_-]+" required>
                </div>
                <div class="app-form-group">
                    <label class="app-form-label" for="nama_bank">Nama bank</label>
                    <input class="app-form-control" id="nama_bank" name="nama" value="{{ old('nama') }}" required>
                </div>
                <button type="submit" class="btn-app btn-app-primary">Tambahkan bank</button>
            </form>
            <div class="app-table-responsive" style="margin-top: 1rem;">
                <table class="app-table">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($bank as $item)
                            <tr><td>{{ $item->kode }}</td><td>{{ $item->nama }}</td><td>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</td></tr>
                        @empty
                            <tr><td colspan="3">Belum ada bank. Tambahkan sebelum PJ mendaftarkan panti.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
