<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Portal Zakat</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <header class="app-navbar">
        <div class="navbar-container">
            <a class="brand-wrapper" href="{{ route('admin.dashboard') }}">
                <span class="brand-icon" aria-hidden="true">PZ</span>
                <span><strong class="brand-text">Portal Zakat</strong> <span class="brand-badge">Admin</span></span>
            </a>
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
        <section id="pengaturan-simulasi" class="app-card" style="padding: 1.5rem; margin-top: 1.5rem;">
            <h2 class="app-card-title">Pengaturan simulasi</h2>
            <p><strong>Isi dan simpan tanggal Idulfitri sebelum mengaktifkan wilayah.</strong> Tanggal ini menentukan batas checkout H-5, H-7, atau H-10 sesuai wilayah. Wilayah yang tenggatnya lewat otomatis ditutup.</p>
            <form method="POST" action="{{ route('admin.pengaturan.update') }}">
                @csrf
                @method('PUT')
                <div class="app-form-group"><label class="app-form-label">Tanggal Idulfitri yang ditetapkan panitia</label><input class="app-form-control" type="date" name="tanggal_idulfitri" value="{{ old('tanggal_idulfitri', $pengaturan['tanggal_idulfitri'] ?? '') }}" required></div>
                <div class="app-form-group"><label class="app-form-label">Biaya admin per pesanan (Rp)</label><input class="app-form-control" type="number" min="0" step="1" name="biaya_admin_rupiah" value="{{ old('biaya_admin_rupiah', $pengaturan['biaya_admin_rupiah'] ?? 1000) }}" required></div>
                <div class="app-form-group"><label class="app-form-label">Keuntungan beras dari HPP (%)</label><input class="app-form-control" type="number" min="0" max="100" step="0.01" name="persen_keuntungan_beras" value="{{ old('persen_keuntungan_beras', $pengaturan['persen_keuntungan_beras'] ?? 5) }}" required></div>
                <div class="app-form-group"><label class="app-form-label">Beras per jiwa (kg)</label><input class="app-form-control" type="number" min="0.01" max="10" step="0.01" name="bobot_fitrah_per_jiwa_kg" value="{{ old('bobot_fitrah_per_jiwa_kg', $pengaturan['bobot_fitrah_per_jiwa_kg'] ?? 2.5) }}" required></div>
                <button type="submit" class="btn-app btn-app-primary">Simpan pengaturan</button>
            </form>
        </section>

        <section class="app-card" style="padding: 1.5rem; margin-top: 1.5rem;">
            <h2 class="app-card-title">Wilayah layanan & tarif ongkir simulasi</h2>
            <p>Aktifkan zona hanya ketika rute, kapasitas, dan waktu kirim siap. Biaya kirim dihitung dari biaya tetap + tarif × jarak jalan × berat.</p>
            @if (! ($pengaturan['tanggal_idulfitri'] ?? null))
                <div class="app-alert app-alert-error">Tanggal Idulfitri belum disimpan. <a href="#pengaturan-simulasi">Simpan pengaturan simulasi</a> terlebih dahulu agar tenggat setiap wilayah dihitung.</div>
            @endif
            @foreach ($wilayah as $zona)
                <form method="POST" action="{{ route('admin.wilayah.update', $zona->id) }}" style="padding: 1rem 0; border-top: 1px solid #ddd;">
                    @csrf
                    @method('PUT')
                    <h3 style="font-size: 1.1rem;">{{ $zona->nama }} · H-{{ $zona->hari_batas_fitrah }}</h3>
                    <p>Batas checkout: {{ $zona->batas_checkout_at ? \Carbon\Carbon::parse($zona->batas_checkout_at)->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB' : 'belum ditetapkan' }}</p>
                    @unless ($zona->dapat_diaktifkan)
                        <p>Zona belum bisa diaktifkan: tenggat checkout belum ditetapkan atau sudah lewat. Perbarui <a href="#pengaturan-simulasi">tanggal Idulfitri</a> terlebih dahulu.</p>
                    @endunless
                    <input type="hidden" name="is_active" value="0">
                    <div class="app-form-group"><label><input type="checkbox" name="is_active" value="1" @checked($zona->is_active) @disabled(! $zona->dapat_diaktifkan)> Zona aktif</label></div>
                    <div class="app-form-group"><label class="app-form-label">Biaya tetap (Rp)</label><input class="app-form-control" type="number" min="0" step="0.01" name="biaya_tetap" value="{{ $zona->tarif->biaya_tetap ?? 3000 }}" required></div>
                    <div class="app-form-group"><label class="app-form-label">Biaya per km per kg (Rp)</label><input class="app-form-control" type="number" min="0" step="0.01" name="biaya_per_km_kg" value="{{ $zona->tarif->biaya_per_km_kg ?? 8 }}" required></div>
                    <button type="submit" class="btn-app btn-app-primary">Simpan wilayah</button>
                </form>
            @endforeach
        </section>

        <section class="app-card" style="padding: 1.5rem; margin-top: 1.5rem;">
            <h2 class="app-card-title">Harga emas acuan maal</h2>
            <p>Masukkan harga 1 gram dari sumber resmi dengan tanggal publikasinya. Checkout maal memakai data paling baru yang belum lebih dari 7 hari.</p>
            @if ($hargaEmas)
                <p>Terakhir: Rp{{ number_format($hargaEmas->harga_per_gram, 2, ',', '.') }} / gram · {{ $hargaEmas->sumber }} · {{ \Carbon\Carbon::parse($hargaEmas->tanggal_sumber)->timezone('Asia/Jakarta')->format('d M Y') }}</p>
            @endif
            <form method="POST" action="{{ route('admin.harga-emas.store') }}">
                @csrf
                <div class="app-form-group"><label class="app-form-label">Harga per gram (Rp)</label><input class="app-form-control" type="number" min="0.01" step="0.01" name="harga_per_gram" required></div>
                <div class="app-form-group"><label class="app-form-label">Sumber publikasi</label><input class="app-form-control" name="sumber" placeholder="Contoh: ANTAM Logam Mulia, harga tanggal ..." required></div>
                <div class="app-form-group"><label class="app-form-label">Tanggal sumber</label><input class="app-form-control" type="date" name="tanggal_sumber" required></div>
                <button type="submit" class="btn-app btn-app-primary">Catat harga emas</button>
            </form>
        </section>
        <section class="app-card" style="padding: 1.5rem; margin-top: 1.5rem;">
            <h2 class="app-card-title">Konten halaman depan</h2>
            <p>Teks ini dapat dilihat publik sebelum login. Angka dampak dan daftar panti diambil langsung dari data simulasi dan tidak dapat diubah lewat formulir ini. <a href="{{ route('landing') }}">Lihat halaman depan</a>.</p>
            <form method="POST" action="{{ route('admin.konten.update') }}">
                @csrf
                @method('PUT')
                <div class="app-form-group"><label class="app-form-label" for="landing-judul">Judul utama</label><input class="app-form-control" id="landing-judul" name="landing_judul" maxlength="140" value="{{ old('landing_judul', $kontenPublik['landing_judul']) }}" required></div>
                <div class="app-form-group"><label class="app-form-label" for="landing-pengantar">Pengantar</label><textarea class="app-form-control" id="landing-pengantar" name="landing_pengantar" rows="3" maxlength="600" required>{{ old('landing_pengantar', $kontenPublik['landing_pengantar']) }}</textarea></div>
                <div class="app-form-group"><label class="app-form-label" for="landing-tentang">Tentang platform</label><textarea class="app-form-control" id="landing-tentang" name="landing_tentang" rows="4" maxlength="1200" required>{{ old('landing_tentang', $kontenPublik['landing_tentang']) }}</textarea></div>
                <div class="app-form-group"><label class="app-form-label" for="landing-faq-judul">Pertanyaan umum</label><input class="app-form-control" id="landing-faq-judul" name="landing_faq_judul" maxlength="180" value="{{ old('landing_faq_judul', $kontenPublik['landing_faq_judul']) }}" required></div>
                <div class="app-form-group"><label class="app-form-label" for="landing-faq-jawaban">Jawaban</label><textarea class="app-form-control" id="landing-faq-jawaban" name="landing_faq_jawaban" rows="3" maxlength="800" required>{{ old('landing_faq_jawaban', $kontenPublik['landing_faq_jawaban']) }}</textarea></div>
                <button type="submit" class="btn-app btn-app-primary">Simpan konten publik</button>
            </form>
        </section>
    </main>
</body>
</html>
