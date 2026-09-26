@extends('muzakki.layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <!-- Header Halaman Profil -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h3 class="fw-bold mb-1" style="color: var(--dark);">Profil Saya</h3>
                <p class="text-muted mb-0">Kelola informasi data diri dan riwayat pembaruan akun Anda.</p>
            </div>
            <span class="badge px-3 py-2" style="background: rgba(5, 150, 105, 0.15); color: var(--primary); font-size: 0.85rem; font-weight: 700; border-radius: 8px;">
                Muzakki (Pemberi Zakat)
            </span>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="fw-semibold">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <strong class="d-block mb-2">Terjadi kesalahan input:</strong>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Kolom Kiri: Ringkasan Akun & Catatan Waktu (created_at & updated_at) -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                    <div class="card-body text-center p-4">
                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 76px; height: 76px; border-radius: 50%; background: linear-gradient(135deg, var(--primary) 0%, #047857 100%); font-size: 1.85rem;">
                            {{ strtoupper(substr($user->nama, 0, 1)) }}
                        </div>
                        <h5 class="fw-bold mb-1" style="color: var(--slate-800);">{{ $user->nama }}</h5>
                        <p class="text-muted small mb-3">{{ $user->email }}</p>
                        <hr class="my-3 opacity-10">
                        <div class="text-start">
                            <div class="mb-3">
                                <label class="text-muted small d-block mb-1">Status Akun</label>
                                <span class="badge bg-success px-2.5 py-1.5" style="font-size: 0.775rem;">Aktif Terverifikasi</span>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small d-block mb-1">Domisili</label>
                                <strong style="color: var(--slate-700); font-size: 0.9rem;">
                                    {{ $user->kota_kabupaten ?? '-' }}, {{ $user->provinsi ?? '-' }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Catatan Waktu (created_at & updated_at) -->
                <div class="card shadow-sm border-0" style="border-radius: 14px; background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: var(--slate-800);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: var(--primary);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Informasi Waktu Akun
                        </h6>
                        <div class="p-3 bg-white rounded border mb-3 shadow-none">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted fw-semibold">Akun Dibuat</span>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">created_at</span>
                            </div>
                            <div class="fw-bold text-dark" style="font-size: 0.925rem;">
                                {{ $user->created_at ? $user->created_at->format('d M Y, H:i') . ' WIB' : '-' }}
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                {{ $user->created_at ? $user->created_at->diffForHumans() : '' }}
                            </small>
                        </div>

                        <div class="p-3 bg-white rounded border shadow-none">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted fw-semibold">Terakhir Diperbarui</span>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">updated_at</span>
                            </div>
                            <div class="fw-bold text-dark" style="font-size: 0.925rem;">
                                {{ $user->updated_at ? $user->updated_at->format('d M Y, H:i') . ' WIB' : '-' }}
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                {{ $user->updated_at ? $user->updated_at->diffForHumans() : '' }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Formulir Pembaruan Profil -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0" style="border-radius: 14px;">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 fw-bold" style="color: var(--slate-800);">Formulir Perbarui Data Profil</h5>
                        <span class="small text-muted"><span class="text-danger fw-bold">*</span> Menandakan kolom wajib</span>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('muzakki.profil.update') }}" method="POST">
                            @csrf

                            <!-- Nama Lengkap & Email -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control" value="{{ old('nama', $user->nama) }}" required placeholder="Nama lengkap">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Alamat Email</label>
                                    <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled title="Email login tidak dapat diubah">
                                    <small class="text-muted" style="font-size: 0.75rem;">Email digunakan sebagai identitas akun dan login.</small>
                                </div>
                            </div>

                            <!-- NIK & No. Telepon -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">NIK (KTP 16 Digit) <span class="text-danger">*</span></label>
                                    <input type="text" name="nik" class="form-control" value="{{ old('nik', $user->nik) }}" maxlength="16" pattern="[0-9]{16}" required placeholder="16 digit angka NIK KTP">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">No. Telepon / WhatsApp <span class="text-danger">*</span></label>
                                    <input type="text" name="telp" class="form-control" value="{{ old('telp', $user->telp) }}" required placeholder="Contoh: 08123456789">
                                </div>
                            </div>

                            <!-- Tanggal Lahir & Jenis Kelamin -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tanggal Lahir <span class="text-danger">*</span></label>
                                    <input type="date" name="tggl_lahir" class="form-control" value="{{ old('tggl_lahir', $user->tggl_lahir ? $user->tggl_lahir->format('Y-m-d') : '') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-select" required>
                                        <option value="">-- Pilih Jenis Kelamin --</option>
                                        <option value="L" {{ old('gender', $user->gender) === 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                                        <option value="P" {{ old('gender', $user->gender) === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Provinsi & Kota/Kabupaten (Chained Dropdown) -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Provinsi <span class="text-danger">*</span></label>
                                    <select name="provinsi" id="provinsi-select" class="form-select" required>
                                        <option value="">-- Pilih Provinsi --</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kota / Kabupaten <span class="text-danger">*</span></label>
                                    <select name="kota_kabupaten" id="kota-select" class="form-select" required>
                                        <option value="">-- Pilih Provinsi Dahulu --</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Alamat Lengkap -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Alamat Lengkap <span class="text-muted fw-normal">(Opsional)</span></label>
                                <textarea name="alamat_lengkap" rows="2" class="form-control" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan...">{{ old('alamat_lengkap', $user->alamat_lengkap) }}</textarea>
                            </div>

                            <!-- NPWP -->
                            <div class="mb-4">
                                <label class="form-label small fw-bold">NPWP (Nomor Pokok Wajib Pajak) <span class="text-muted fw-normal">(Opsional)</span></label>
                                <input type="text" name="npwp" class="form-control" value="{{ old('npwp', $user->npwp) }}" maxlength="25" placeholder="Contoh: 01.234.567.8-901.000">
                                <small class="text-muted" style="font-size: 0.75rem;">Digunakan untuk pencatatan pengurang pajak pada bukti pembayaran zakat.</small>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm" style="border-radius: 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="me-1 d-inline-block">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Simpan Perubahan Profil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Data Provinsi & Kota/Kabupaten Seluruh Indonesia
    const dataWilayah = {
        "Aceh": ["Banda Aceh", "Langsa", "Lhokseumawe", "Sabang", "Subulussalam", "Kab. Aceh Barat", "Kab. Aceh Barat Daya", "Kab. Aceh Besar", "Kab. Aceh Jaya", "Kab. Aceh Selatan", "Kab. Aceh Singkil", "Kab. Aceh Tamiang", "Kab. Aceh Tengah", "Kab. Aceh Tenggara", "Kab. Aceh Timur", "Kab. Aceh Utara", "Kab. Bener Meriah", "Kab. Bireuen", "Kab. Gayo Lues", "Kab. Nagan Raya", "Kab. Pidie", "Kab. Pidie Jaya", "Kab. Simeulue"],
        "Sumatera Utara": ["Kota Medan", "Kota Binjai", "Kota Gunungsitoli", "Kota Padangsidimpuan", "Kota Pematangsiantar", "Kota Sibolga", "Kota Tanjungbalai", "Kota Tebing Tinggi", "Kab. Asahan", "Kab. Batubara", "Kab. Dairi", "Kab. Deli Serdang", "Kab. Humbang Hasundutan", "Kab. Karo", "Kab. Labuhanbatu", "Kab. Labuhanbatu Selatan", "Kab. Labuhanbatu Utara", "Kab. Langkat", "Kab. Mandailing Natal", "Kab. Nias", "Kab. Nias Barat", "Kab. Nias Selatan", "Kab. Nias Utara", "Kab. Padang Lawas", "Kab. Padang Lawas Utara", "Kab. Pakpak Bharat", "Kab. Samosir", "Kab. Serdang Bedagai", "Kab. Simalungun", "Kab. Tapanuli Selatan", "Kab. Tapanuli Tengah", "Kab. Tapanuli Utara", "Kab. Toba"],
        "Sumatera Barat": ["Kota Padang", "Kota Bukittinggi", "Kota Padang Panjang", "Kota Pariaman", "Kota Payakumbuh", "Kota Sawahlunto", "Kota Solok", "Kab. Agam", "Kab. Dharmasraya", "Kab. Kepulauan Mentawai", "Kab. Lima Puluh Kota", "Kab. Padang Pariaman", "Kab. Pasaman", "Kab. Pasaman Barat", "Kab. Pesisir Selatan", "Kab. Sijunjung", "Kab. Solok", "Kab. Solok Selatan", "Kab. Tanah Datar"],
        "Riau": ["Kota Pekanbaru", "Kota Dumai", "Kab. Bengkalis", "Kab. Indragiri Hilir", "Kab. Indragiri Hulu", "Kab. Kampar", "Kab. Kepulauan Meranti", "Kab. Kuantan Singingi", "Kab. Pelalawan", "Kab. Rokan Hilir", "Kab. Rokan Hulu", "Kab. Siak"],
        "Kepulauan Riau": ["Kota Batam", "Kota Tanjungpinang", "Kab. Bintan", "Kab. Karimun", "Kab. Kepulauan Anambas", "Kab. Lingga", "Kab. Natuna"],
        "Jambi": ["Kota Jambi", "Kota Sungai Penuh", "Kab. Batanghari", "Kab. Bungo", "Kab. Kerinci", "Kab. Merangin", "Kab. Muaro Jambi", "Kab. Sarolangun", "Kab. Tanjung Jabung Barat", "Kab. Tanjung Jabung Timur", "Kab. Tebo"],
        "Sumatera Selatan": ["Kota Palembang", "Kota Lubuklinggau", "Kota Pagar Alam", "Kota Prabumulih", "Kab. Banyuasin", "Kab. Empat Lawang", "Kab. Lahat", "Kab. Muara Enim", "Kab. Musi Banyuasin", "Kab. Musi Rawas", "Kab. Musi Rawas Utara", "Kab. Ogan Ilir", "Kab. Ogan Komering Ilir", "Kab. Ogan Komering Ulu", "Kab. Ogan Komering Ulu Selatan", "Kab. Ogan Komering Ulu Timur", "Kab. Penukal Abab Lematang Ilir"],
        "Kepulauan Bangka Belitung": ["Kota Pangkalpinang", "Kab. Bangka", "Kab. Bangka Barat", "Kab. Bangka Selatan", "Kab. Bangka Tengah", "Kab. Belitung", "Kab. Belitung Timur"],
        "Bengkulu": ["Kota Bengkulu", "Kab. Bengkulu Selatan", "Kab. Bengkulu Tengah", "Kab. Bengkulu Utara", "Kab. Kaur", "Kab. Kepahiang", "Kab. Lebong", "Kab. Mukomuko", "Kab. Rejang Lebong", "Kab. Seluma"],
        "Lampung": ["Kota Bandar Lampung", "Kota Metro", "Kab. Lampung Barat", "Kab. Lampung Selatan", "Kab. Lampung Tengah", "Kab. Lampung Timur", "Kab. Lampung Utara", "Kab. Mesuji", "Kab. Pesawaran", "Kab. Pesisir Barat", "Kab. Pringsewu", "Kab. Tanggamus", "Kab. Tulang Bawang", "Kab. Tulang Bawang Barat", "Kab. Way Kanan"],
        "DKI Jakarta": ["Kota Jakarta Pusat", "Kota Jakarta Barat", "Kota Jakarta Selatan", "Kota Jakarta Timur", "Kota Jakarta Utara", "Kab. Kepulauan Seribu"],
        "Jawa Barat": ["Kota Bandung", "Kota Bekasi", "Kota Bogor", "Kota Cimahi", "Kota Cirebon", "Kota Depok", "Kota Sukabumi", "Kota Tasikmalaya", "Kota Banjar", "Kab. Bandung", "Kab. Bandung Barat", "Kab. Bekasi", "Kab. Bogor", "Kab. Ciamis", "Kab. Cianjur", "Kab. Cirebon", "Kab. Garut", "Kab. Indramayu", "Kab. Karawang", "Kab. Kuningan", "Kab. Majalengka", "Kab. Pangandaran", "Kab. Purwakarta", "Kab. Subang", "Kab. Sukabumi", "Kab. Sumedang", "Kab. Tasikmalaya"],
        "Banten": ["Kota Serang", "Kota Cilegon", "Kota Tangerang", "Kota Tangerang Selatan", "Kab. Lebak", "Kab. Pandeglang", "Kab. Serang", "Kab. Tangerang"],
        "Jawa Tengah": ["Kota Semarang", "Kota Magelang", "Kota Pekalongan", "Kota Salatiga", "Kota Surakarta", "Kota Tegal", "Kab. Banjarnegara", "Kab. Banyumas", "Kab. Batang", "Kab. Blora", "Kab. Boyolali", "Kab. Brebes", "Kab. Cilacap", "Kab. Demak", "Kab. Grobogan", "Kab. Jepara", "Kab. Karanganyar", "Kab. Kebumen", "Kab. Kendal", "Kab. Klaten", "Kab. Kudus", "Kab. Magelang", "Kab. Pati", "Kab. Pekalongan", "Kab. Pemalang", "Kab. Purbalingga", "Kab. Purworejo", "Kab. Rembang", "Kab. Semarang", "Kab. Sragen", "Kab. Sukoharjo", "Kab. Tegal", "Kab. Temanggung", "Kab. Wonogiri", "Kab. Wonosobo"],
        "DI Yogyakarta": ["Kota Yogyakarta", "Kab. Bantul", "Kab. Gunungkidul", "Kab. Kulon Progo", "Kab. Sleman"],
        "Jawa Timur": ["Kota Surabaya", "Kota Batu", "Kota Blitar", "Kota Kediri", "Kota Madiun", "Kota Malang", "Kota Mojokerto", "Kota Pasuruan", "Kota Probolinggo", "Kab. Bangkalan", "Kab. Banyuwangi", "Kab. Bojonegoro", "Kab. Bondowoso", "Kab. Gresik", "Kab. Jember", "Kab. Jombang", "Kab. Kediri", "Kab. Lamongan", "Kab. Lumajang", "Kab. Madiun", "Kab. Magetan", "Kab. Malang", "Kab. Mojokerto", "Kab. Nganjuk", "Kab. Ngawi", "Kab. Pacitan", "Kab. Pamekasan", "Kab. Pasuruan", "Kab. Ponorogo", "Kab. Probolinggo", "Kab. Sampang", "Kab. Sidoarjo", "Kab. Situbondo", "Kab. Sumenep", "Kab. Trenggalek", "Kab. Tuban", "Kab. Tulungagung"],
        "Bali": ["Kota Denpasar", "Kab. Badung", "Kab. Bangli", "Kab. Buleleng", "Kab. Gianyar", "Kab. Jembrana", "Kab. Karangasem", "Kab. Klungkung", "Kab. Tabanan"],
        "Nusa Tenggara Barat": ["Kota Mataram", "Kota Bima", "Kab. Bima", "Kab. Dompu", "Kab. Lombok Barat", "Kab. Lombok Tengah", "Kab. Lombok Timur", "Kab. Lombok Utara", "Kab. Sumbawa", "Kab. Sumbawa Barat"],
        "Nusa Tenggara Timur": ["Kota Kupang", "Kab. Alor", "Kab. Belu", "Kab. Ende", "Kab. Flores Timur", "Kab. Kupang", "Kab. Lembata", "Kab. Malaka", "Kab. Manggarai", "Kab. Manggarai Barat", "Kab. Manggarai Timur", "Kab. Nagekeo", "Kab. Ngada", "Kab. Rote Ndao", "Kab. Sabu Raijua", "Kab. Sikka", "Kab. Sumba Barat", "Kab. Sumba Barat Daya", "Kab. Sumba Tengah", "Kab. Sumba Timur", "Kab. Timor Tengah Selatan", "Kab. Timor Tengah Utara"],
        "Kalimantan Barat": ["Kota Pontianak", "Kota Singkawang", "Kab. Bengkayang", "Kab. Kapuas Hulu", "Kab. Kayong Utara", "Kab. Ketapang", "Kab. Kubu Raya", "Kab. Landak", "Kab. Melawi", "Kab. Mempawah", "Kab. Sambas", "Kab. Sanggau", "Kab. Sekadau", "Kab. Sintang"],
        "Kalimantan Tengah": ["Kota Palangka Raya", "Kab. Barito Selatan", "Kab. Barito Timur", "Kab. Barito Utara", "Kab. Gunung Mas", "Kab. Kapuas", "Kab. Katingan", "Kab. Kotawaringin Barat", "Kab. Kotawaringin Timur", "Kab. Lamandau", "Kab. Murung Raya", "Kab. Pulang Pisau", "Kab. Seruyan", "Kab. Sukamara"],
        "Kalimantan Selatan": ["Kota Banjarmasin", "Kota Banjarbaru", "Kab. Balangan", "Kab. Banjar", "Kab. Barito Kuala", "Kab. Hulu Sungai Selatan", "Kab. Hulu Sungai Tengah", "Kab. Hulu Sungai Utara", "Kab. Kotabaru", "Kab. Tabalong", "Kab. Tanah Bumbu", "Kab. Tanah Laut", "Kab. Tapin"],
        "Kalimantan Timur": ["Kota Samarinda", "Kota Balikpapan", "Kota Bontang", "Kab. Berau", "Kab. Kutai Barat", "Kab. Kutai Kartanegara", "Kab. Kutai Timur", "Kab. Mahakam Ulu", "Kab. Paser", "Kab. Penajam Paser Utara"],
        "Kalimantan Utara": ["Kota Tarakan", "Kab. Bulungan", "Kab. Malinau", "Kab. Nunukan", "Kab. Tana Tidung"],
        "Sulawesi Utara": ["Kota Manado", "Kota Bitung", "Kota Kotamobagu", "Kota Tomohon", "Kab. Bolaang Mongondow", "Kab. Bolaang Mongondow Selatan", "Kab. Bolaang Mongondow Timur", "Kab. Bolaang Mongondow Utara", "Kab. Kepulauan Sangihe", "Kab. Kepulauan Siau Tagulandang Biaro", "Kab. Kepulauan Talaud", "Kab. Minahasa", "Kab. Minahasa Selatan", "Kab. Minahasa Tenggara", "Kab. Minahasa Utara"],
        "Gorontalo": ["Kota Gorontalo", "Kab. Boalemo", "Kab. Bone Bolango", "Kab. Gorontalo", "Kab. Gorontalo Utara", "Kab. Pohuwato"],
        "Sulawesi Tengah": ["Kota Palu", "Kab. Banggai", "Kab. Banggai Kepulauan", "Kab. Banggai Laut", "Kab. Buol", "Kab. Donggala", "Kab. Morowali", "Kab. Morowali Utara", "Kab. Parigi Moutong", "Kab. Poso", "Kab. Sigi", "Kab. Tojo Una-Una", "Kab. Toli-Toli"],
        "Sulawesi Barat": ["Kab. Majene", "Kab. Mamasa", "Kab. Mamuju", "Kab. Mamuju Tengah", "Kab. Pasangkayu", "Kab. Polewali Mandar"],
        "Sulawesi Selatan": ["Kota Makassar", "Kota Palopo", "Kota Parepare", "Kab. Bantaeng", "Kab. Barru", "Kab. Bone", "Kab. Bulukumba", "Kab. Enrekang", "Kab. Gowa", "Kab. Jeneponto", "Kab. Kepulauan Selayar", "Kab. Luwu", "Kab. Luwu Timur", "Kab. Luwu Utara", "Kab. Maros", "Kab. Pangkajene dan Kepulauan", "Kab. Pinrang", "Kab. Sidenreng Rappang", "Kab. Sinjai", "Kab. Soppeng", "Kab. Takalar", "Kab. Tana Toraja", "Kab. Toraja Utara", "Kab. Wajo"],
        "Sulawesi Tenggara": ["Kota Kendari", "Kota Baubau", "Kab. Bombana", "Kab. Buton", "Kab. Buton Selatan", "Kab. Buton Tengah", "Kab. Buton Utara", "Kab. Kolaka", "Kab. Kolaka Timur", "Kab. Kolaka Utara", "Kab. Konawe", "Kab. Konawe Kepulauan", "Kab. Konawe Selatan", "Kab. Konawe Utara", "Kab. Muna", "Kab. Muna Barat", "Kab. Wakatobi"],
        "Maluku": ["Kota Ambon", "Kota Tual", "Kab. Buru", "Kab. Buru Selatan", "Kab. Kepulauan Aru", "Kab. Kepulauan Tanimbar", "Kab. Maluku Barat Daya", "Kab. Maluku Tengah", "Kab. Maluku Tenggara", "Kab. Seram Bagian Barat", "Kab. Seram Bagian Timur"],
        "Maluku Utara": ["Kota Ternate", "Kota Tidore Kepulauan", "Kab. Halmahera Barat", "Kab. Halmahera Selatan", "Kab. Halmahera Tengah", "Kab. Halmahera Timur", "Kab. Halmahera Utara", "Kab. Kepulauan Sula", "Kab. Pulau Morotai", "Kab. Pulau Taliabu"],
        "Papua": ["Kota Jayapura", "Kab. Biak Numfor", "Kab. Jayapura", "Kab. Keerom", "Kab. Kepulauan Yapen", "Kab. Mamberamo Raya", "Kab. Sarmi", "Kab. Supiori", "Kab. Waropen"],
        "Papua Barat": ["Kab. Manokwari", "Kab. Fakfak", "Kab. Kaimana", "Kab. Manokwari Selatan", "Kab. Pegunungan Arfak", "Kab. Teluk Bintuni", "Kab. Teluk Wondama"],
        "Papua Selatan": ["Kab. Merauke", "Kab. Asmat", "Kab. Boven Digoel", "Kab. Mappi"],
        "Papua Tengah": ["Kab. Nabire", "Kab. Deiyai", "Kab. Dogiyai", "Kab. Intan Jaya", "Kab. Mimika", "Kab. Paniai", "Kab. Puncak", "Kab. Puncak Jaya"],
        "Papua Pegunungan": ["Kab. Jayawijaya", "Kab. Lanny Jaya", "Kab. Mamberamo Tengah", "Kab. Nduga", "Kab. Pegunungan Bintang", "Kab. Tolikara", "Kab. Yahukimo", "Kab. Yalimo"],
        "Papua Barat Daya": ["Kota Sorong", "Kab. Maybrat", "Kab. Raja Ampat", "Kab. Sorong", "Kab. Sorong Selatan", "Kab. Tambrauw"]
    };

    const provinsiSelect = document.getElementById('provinsi-select');
    const kotaSelect = document.getElementById('kota-select');
    const selectedProvinsi = "{{ old('provinsi', $user->provinsi) }}";
    const selectedKota = "{{ old('kota_kabupaten', $user->kota_kabupaten) }}";

    // Isi Opsi Provinsi
    Object.keys(dataWilayah).sort().forEach(prov => {
        const opt = document.createElement('option');
        opt.value = prov;
        opt.textContent = prov;
        if (prov === selectedProvinsi) {
            opt.selected = true;
        }
        provinsiSelect.appendChild(opt);
    });

    function updateKotaList(provinsi, preselectedKota = '') {
        kotaSelect.innerHTML = '<option value="">-- Pilih Kota / Kabupaten --</option>';
        if (provinsi && dataWilayah[provinsi]) {
            kotaSelect.disabled = false;
            dataWilayah[provinsi].sort().forEach(kota => {
                const opt = document.createElement('option');
                opt.value = kota;
                opt.textContent = kota;
                if (kota === preselectedKota) {
                    opt.selected = true;
                }
                kotaSelect.appendChild(opt);
            });
        } else {
            kotaSelect.disabled = true;
            kotaSelect.innerHTML = '<option value="">-- Pilih Provinsi Dahulu --</option>';
        }
    }

    provinsiSelect.addEventListener('change', function() {
        updateKotaList(this.value);
    });

    // Inisialisasi awal dengan data pengguna yang sedang login
    if (selectedProvinsi) {
        updateKotaList(selectedProvinsi, selectedKota);
    }
</script>
@endpush