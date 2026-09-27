<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Panti - Zakatara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <header class="app-navbar">
        <div class="navbar-container">
            <strong class="brand-text">Zakatara · Penanggung jawab panti</strong>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-app-logout">Keluar</button>
            </form>
        </div>
    </header>

    <main class="app-container py-4">
        <h1 class="h3 mb-2">Halo, {{ Auth::user()->nama }}</h1>
        <p class="text-muted mb-4">Data panti akan muncul sebagai tujuan zakat setelah panitia memeriksa dan mengaktifkannya.</p>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($yayasan)
            <section class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5">{{ $yayasan->nama }}</h2>
                    <p class="mb-2">Nomor izin: {{ $yayasan->nomor_izin }}</p>
                    <p class="mb-2">Lokasi: {{ $yayasan->kota_kabupaten }}, {{ $yayasan->provinsi }}</p>
                    <p class="mb-2">Status pemeriksaan: <strong>{{ match ($yayasan->status_verif) { 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak', default => 'Menunggu pemeriksaan' } }}</strong></p>
                    @if ($yayasan->status_verif === 'verified')
                        <p class="mb-0">Layanan: {{ $yayasan->is_active ? 'Aktif' : 'Belum diaktifkan panitia' }}</p>
                    @endif
                    @if ($catatanVerifikasi)
                        <p class="mt-3 mb-0">Catatan panitia: {{ $catatanVerifikasi }}</p>
                    @endif
                </div>
            </section>
        @else
            <section class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Daftarkan panti asuhan</h2>
                    <form method="POST" action="{{ route('yayasan.panti.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="nama">Nama panti</label><input class="form-control" id="nama" name="nama" value="{{ old('nama') }}" required></div>
                            <div class="col-md-6"><label class="form-label" for="nomor_izin">Nomor izin</label><input class="form-control" id="nomor_izin" name="nomor_izin" value="{{ old('nomor_izin') }}" required></div>
                            <div class="col-md-6"><label class="form-label" for="telp">Telepon panti</label><input class="form-control" id="telp" name="telp" value="{{ old('telp') }}" required></div>
                            <div class="col-md-6"><label class="form-label" for="email">Email panti</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required></div>
                            <div class="col-md-6"><label class="form-label" for="provinsi">Provinsi</label><input class="form-control" id="provinsi" name="provinsi" value="{{ old('provinsi', Auth::user()->provinsi) }}" required></div>
                            <div class="col-md-6"><label class="form-label" for="kota_kabupaten">Kota/kabupaten</label><input class="form-control" id="kota_kabupaten" name="kota_kabupaten" value="{{ old('kota_kabupaten', Auth::user()->kota_kabupaten) }}" required></div>
                            <div class="col-md-8"><label class="form-label" for="alamat_lengkap">Alamat lengkap panti</label><textarea class="form-control" id="alamat_lengkap" name="alamat_lengkap" rows="2" required>{{ old('alamat_lengkap') }}</textarea></div>
                            <div class="col-md-4"><label class="form-label" for="kode_pos">Kode pos</label><input class="form-control" id="kode_pos" name="kode_pos" value="{{ old('kode_pos') }}" required></div>
                            <div class="col-md-4">
                                <label class="form-label" for="bank_id">Bank</label>
                                <select class="form-select" id="bank_id" name="bank_id" required>
                                    <option value="">Pilih bank</option>
                                    @foreach ($bank as $item)
                                        <option value="{{ $item->id }}" @selected(old('bank_id') == $item->id)>{{ $item->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label" for="no_rek">Nomor rekening</label><input class="form-control" id="no_rek" name="no_rek" value="{{ old('no_rek') }}" required></div>
                            <div class="col-md-4"><label class="form-label" for="atas_nama_rek">Nama pemilik rekening</label><input class="form-control" id="atas_nama_rek" name="atas_nama_rek" value="{{ old('atas_nama_rek') }}" required></div>
                            <div class="col-12"><label class="form-label" for="foto">Foto panti (1–5 foto, masing-masing maksimal 2 MB)</label><input class="form-control" id="foto" type="file" name="foto[]" accept="image/jpeg,image/png,image/webp" multiple required></div>
                        </div>
                        @if ($bank->isEmpty())
                            <p class="text-danger mt-3">Daftar bank belum tersedia. Admin perlu mengisi daftar bank sebelum panti dapat didaftarkan.</p>
                        @endif
                        <button type="submit" class="btn btn-success mt-4" @disabled($bank->isEmpty())>Kirim untuk diperiksa</button>
                    </form>
                </div>
            </section>
        @endif
    </main>
</body>
</html>
