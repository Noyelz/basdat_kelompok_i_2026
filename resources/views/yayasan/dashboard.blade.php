<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Panti · Portal Zakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <header class="app-navbar">
        <div class="navbar-container">
            <a class="brand-wrapper" href="{{ route('yayasan.dashboard') }}">
                <span class="brand-icon" aria-hidden="true">PZ</span>
                <span><strong class="brand-text">Portal Zakat</strong> <span class="brand-badge">PJ panti</span></span>
            </a>
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

        @if ($notifikasi->isNotEmpty())
            <section class="card border-0 shadow-sm mb-4"><div class="card-body">
                <h2 class="h5">Pemberitahuan penyaluran</h2>
                <p class="small text-muted">Pemberitahuan ini hanya tampil di aplikasi; email belum dikirim otomatis.</p>
                @foreach ($notifikasi as $pesan)
                    <div class="border-top py-3">
                        <strong>{{ $pesan->dibaca_at ? 'Sudah dibaca' : 'Baru' }}</strong> · Penyaluran pesanan {{ $pesan->detail['kode_transaksi'] ?? 'zakat' }} untuk {{ $pesan->detail['nama_panti'] ?? 'panti' }} selesai.
                        @if (! $pesan->dibaca_at)
                            <form method="POST" action="{{ route('notifikasi.read', $pesan->id) }}" class="mt-2">@csrf<button type="submit" class="btn btn-sm btn-outline-primary">Tandai dibaca</button></form>
                        @endif
                    </div>
                @endforeach
            </div></section>
        @endif

        @if ($yayasan)
            <section class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5">{{ $yayasan->nama }}</h2>
                    <p class="mb-2">Nomor izin: {{ $yayasan->nomor_izin }}</p>
                    <p class="mb-2">Lokasi: {{ $yayasan->kota_kabupaten }}, {{ $yayasan->provinsi }}</p>
                    <p class="mb-2">Penerima diajukan: {{ $yayasan->jumlah_mustahik_diajukan }} · Penerima disetujui: {{ $yayasan->jumlah_mustahik_layak }}</p>
                    <p class="mb-2">Kapasitas beras diajukan: {{ $yayasan->kapasitas_diajukan_kg ?? 'belum diajukan' }} kg per kunjungan</p>
                    <p class="mb-2">Kapasitas disetujui per kunjungan: {{ $yayasan->kapasitas_penerimaan_kg ?? 'belum ditetapkan' }} kg</p>
                    <p class="mb-2">Kebutuhan saat ini: {{ $yayasan->kebutuhan_saat_ini ?? 'belum dicatat' }}</p>
                    <p class="mb-2">Status pemeriksaan: <strong>{{ match ($yayasan->status_verif) { 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak', default => 'Menunggu pemeriksaan' } }}</strong></p>
                    @if ($yayasan->status_verif === 'verified')
                        <p class="mb-0">Layanan: {{ $yayasan->is_active ? 'Aktif' : 'Belum diaktifkan panitia' }}</p>
                    @endif
                    @if ($catatanVerifikasi)
                        <p class="mt-3 mb-0">Catatan panitia: {{ $catatanVerifikasi }}</p>
                    @endif
                </div>
            </section>
            <section class="card border-0 shadow-sm mt-4"><div class="card-body">
                <h2 class="h5">Rekening panti</h2>
                <p class="mb-2">Rekening aktif: <strong>{{ $yayasan->nama_bank }} · {{ $yayasan->no_rek }} · {{ $yayasan->atas_nama_rek }}</strong></p>
                @if ($permohonanRekening)
                    <p class="mb-2">Usulan terakhir: {{ $permohonanRekening->nama_bank }} · {{ $permohonanRekening->no_rek }} · {{ $permohonanRekening->atas_nama_rek }} · <strong>{{ $permohonanRekening->status }}</strong></p>
                    @if ($permohonanRekening->catatan)<p class="mb-2">Catatan panitia: {{ $permohonanRekening->catatan }}</p>@endif
                @endif
                @if (! $permohonanRekening || $permohonanRekening->status !== 'pending')
                    <form method="POST" action="{{ route('yayasan.rekening.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-4"><label class="form-label">Bank baru</label><select name="bank_id" class="form-select" required><option value="">Pilih bank</option>@foreach ($bank as $item)<option value="{{ $item->id }}" @selected(old('bank_id') == $item->id)>{{ $item->nama }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Nomor rekening baru</label><input name="no_rek" class="form-control" inputmode="numeric" pattern="[0-9]{6,30}" maxlength="30" value="{{ old('no_rek') }}" required></div>
                        <div class="col-md-4"><label class="form-label">Nama pemilik rekening</label><input name="atas_nama_rek" class="form-control" maxlength="255" value="{{ old('atas_nama_rek') }}" required></div>
                        <div class="col-12"><button class="btn btn-outline-primary" type="submit">Ajukan perubahan rekening</button></div>
                    </form>
                @else
                    <p class="text-muted mb-0">Permohonan sedang diperiksa. Rekening aktif tetap dapat digunakan sesuai proses yang berlaku.</p>
                @endif
            </div></section>
            @if (in_array($yayasan->status_verif, ['pending', 'rejected'], true))
                <section class="card border-0 shadow-sm mt-4"><div class="card-body">
                    <h2 class="h5">Lengkapi atau perbaiki pengajuan panti</h2>
                    <p class="text-muted">Pengajuan yang pending atau ditolak dapat dilengkapi sebelum panitia menyetujui. Pilih layanan dan isi kapasitas jika ingin menerima beras fitrah. Foto pengganti opsional; jika diunggah, foto lama diganti.</p>
                    <form method="POST" action="{{ route('yayasan.panti.resubmit') }}" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        @method('PATCH')
                        <div class="col-md-6"><label class="form-label">Nama panti</label><input name="nama" class="form-control" value="{{ old('nama', $yayasan->nama) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Nomor izin</label><input name="nomor_izin" class="form-control" value="{{ old('nomor_izin', $yayasan->nomor_izin) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Telepon</label><input name="telp" class="form-control" value="{{ old('telp', $yayasan->telp) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $yayasan->email) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Provinsi</label><input name="provinsi" class="form-control" value="{{ old('provinsi', $yayasan->provinsi) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Kota/kabupaten</label><input name="kota_kabupaten" class="form-control" value="{{ old('kota_kabupaten', $yayasan->kota_kabupaten) }}" required></div>
                        <div class="col-md-8"><label class="form-label">Alamat lengkap</label><textarea name="alamat_lengkap" class="form-control" required>{{ old('alamat_lengkap', $yayasan->alamat_lengkap) }}</textarea></div>
                        <div class="col-md-4"><label class="form-label">Kode pos</label><input name="kode_pos" class="form-control" value="{{ old('kode_pos', $yayasan->kode_pos) }}" required></div>
                        <div class="col-md-4"><label class="form-label">Calon penerima zakat</label><input name="jumlah_mustahik_diajukan" type="number" min="1" class="form-control" value="{{ old('jumlah_mustahik_diajukan', $yayasan->jumlah_mustahik_diajukan) }}" required></div>
                        <div class="col-md-8"><label class="form-label">Kebutuhan saat ini dan kriteria penerima</label><textarea name="kebutuhan_saat_ini" class="form-control" rows="2" required>{{ old('kebutuhan_saat_ini', $yayasan->kebutuhan_saat_ini) }}</textarea></div>

                        <div class="col-md-6">
                            <label class="form-label" for="layanan_diminta">Layanan yang diajukan</label>
                            <select id="layanan_diminta" name="layanan_diminta" class="form-select" required>
                                <option value="">Pilih layanan</option>
                                <option value="maal" @selected(old('layanan_diminta', $yayasan?->kapasitas_diajukan_kg !== null ? 'fitrah_maal' : '') === 'maal')>Maal saja</option>
                                <option value="fitrah_maal" @selected(old('layanan_diminta', $yayasan?->kapasitas_diajukan_kg !== null ? 'fitrah_maal' : '') === 'fitrah_maal')>Fitrah beras dan maal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="kapasitas_diajukan_kg">Kapasitas beras yang diajukan per kunjungan (kg)</label>
                            <input id="kapasitas_diajukan_kg" name="kapasitas_diajukan_kg" type="number" min="0.01" max="9999999999.99" step="0.01" class="form-control" value="{{ old('kapasitas_diajukan_kg', $yayasan?->kapasitas_diajukan_kg) }}">
                            <small>Wajib untuk fitrah: jumlah maksimal beras yang dapat diterima dan disimpan dalam satu kunjungan. Panitia akan memeriksa kapasitas dan jarak dari gudang.</small>
                        </div>
                        <div class="col-12"><label class="form-label">Foto pengganti (opsional, maksimal 5)</label><input name="foto[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="form-control"></div>
                        <div class="col-12"><button class="btn btn-success" type="submit">Kirim ulang untuk diperiksa</button></div>
                    </form>
                </div></section>
            @endif
            <section class="card border-0 shadow-sm mt-4"><div class="card-body">
                <h2 class="h5">Alokasi dan konfirmasi penerimaan</h2>
                <p class="text-muted">Periksa jumlah yang diterima. Foto panitia dan foto PJ diperlukan sebelum penyaluran dianggap selesai dalam tugas ini.</p>
                @forelse ($alokasi as $item)
                    <div class="border rounded p-3 mb-3">
                        <strong>{{ $item->kode_transaksi }}</strong> · {{ $item->jenis_zakat === 'fitrah' ? $item->jumlah_kg . ' kg beras' : 'Rp' . number_format($item->nominal, 2, ',', '.') . ' dana maal' }}
                        <p class="mb-1">Status: {{ $item->status }} · Foto panitia: {{ $item->bukti_panitia_id ? 'ada' : 'belum' }} · Foto PJ: {{ $item->bukti_pj_id ? 'ada' : 'belum' }}</p>
                        @if ($item->bukti_panitia_id)<a href="{{ route('bukti.foto', $item->bukti_panitia_id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Lihat foto panitia</a>@endif
                        @if ($item->bukti_pj_id)<a href="{{ route('bukti.foto', $item->bukti_pj_id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Lihat foto PJ</a>@endif
                        @if (in_array($item->status, ['dikirim', 'menunggu_bukti', 'perlu_tinjauan'], true))
                            <form method="POST" action="{{ route('yayasan.bukti.store', $item->id) }}" enctype="multipart/form-data" class="row g-2 mt-2">
                                @csrf
                                <div class="col-md-3"><label class="form-label">Jumlah diterima ({{ $item->jenis_zakat === 'fitrah' ? 'kg' : 'Rp' }})</label><input type="number" step="0.01" min="0.01" name="{{ $item->jenis_zakat === 'fitrah' ? 'jumlah_kg' : 'nominal' }}" class="form-control" value="{{ $item->jenis_zakat === 'fitrah' ? $item->jumlah_kg : $item->nominal }}" required></div>
                                <div class="col-md-4"><label class="form-label">Foto penerimaan</label><input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-control" required></div>
                                <div class="col-md-3"><label class="form-label">Catatan (opsional)</label><input name="catatan" class="form-control"></div>
                                <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-success w-100">Konfirmasi</button></div>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada alokasi untuk panti ini.</p>
                @endforelse
            </div></section>
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
                            <div class="col-md-4"><label class="form-label" for="jumlah_mustahik_diajukan">Jumlah calon penerima zakat yang diajukan</label><input class="form-control" id="jumlah_mustahik_diajukan" type="number" min="1" name="jumlah_mustahik_diajukan" value="{{ old('jumlah_mustahik_diajukan') }}" required></div>
                            <div class="col-md-8"><label class="form-label" for="kebutuhan_saat_ini">Kebutuhan panti saat ini dan kriteria penerima</label><textarea class="form-control" id="kebutuhan_saat_ini" name="kebutuhan_saat_ini" rows="2" maxlength="2000" required>{{ old('kebutuhan_saat_ini') }}</textarea></div>

                        <div class="col-md-6">
                            <label class="form-label" for="layanan_diminta">Layanan yang diajukan</label>
                            <select id="layanan_diminta" name="layanan_diminta" class="form-select" required>
                                <option value="">Pilih layanan</option>
                                <option value="maal" @selected(old('layanan_diminta', $yayasan?->kapasitas_diajukan_kg !== null ? 'fitrah_maal' : '') === 'maal')>Maal saja</option>
                                <option value="fitrah_maal" @selected(old('layanan_diminta', $yayasan?->kapasitas_diajukan_kg !== null ? 'fitrah_maal' : '') === 'fitrah_maal')>Fitrah beras dan maal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="kapasitas_diajukan_kg">Kapasitas beras yang diajukan per kunjungan (kg)</label>
                            <input id="kapasitas_diajukan_kg" name="kapasitas_diajukan_kg" type="number" min="0.01" max="9999999999.99" step="0.01" class="form-control" value="{{ old('kapasitas_diajukan_kg', $yayasan?->kapasitas_diajukan_kg) }}">
                            <small>Wajib untuk fitrah: jumlah maksimal beras yang dapat diterima dan disimpan dalam satu kunjungan. Panitia akan memeriksa kapasitas dan jarak dari gudang.</small>
                        </div>
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
    <script>
        document.querySelectorAll('select[name="layanan_diminta"]').forEach(function (select) {
            const capacity = select.closest('form').querySelector('input[name="kapasitas_diajukan_kg"]');
            function updateCapacityRequirement() {
                const needsRice = select.value === 'fitrah_maal';
                capacity.required = needsRice;
                capacity.disabled = !needsRice;
            }
            select.addEventListener('change', updateCapacityRequirement);
            updateCapacityRequirement();
        });
    </script>
</body>
</html>
