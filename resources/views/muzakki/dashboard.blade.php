@extends('muzakki.layout')

<style>
    .dashboard-welcome {
        position: relative;
        overflow: hidden;
        min-height: 180px;
        padding: 30px 35px !important;
        background: linear-gradient(90deg, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0.98) 28%, rgba(255, 255, 255, 0.92) 43%, rgba(255, 255, 255, 0.60) 60%, rgba(255, 255, 255, 0.15) 78%, rgba(255, 255, 255, 0) 100%),
            url('{{ asset('images/masjid_2.png') }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        border-radius: 16px !important;
    }

    .dashboard-welcome h2,
    .dashboard-welcome p {
        position: relative;
        z-index: 2;
        max-width: 65%;
    }
    .dashboard-welcome h2 {
        margin-bottom: 14px;
        color: #31513a;
        font-family: Georgia, 'Times New Roman', serif !important;
        font-size: 40px;
        font-weight: 700;
        line-height: 1.12;
        letter-spacing: -0.04em;
    }
    .dashboard-welcome span {
        margin-bottom: 10px;
        color: #71806f !important;
        line-height: 1.7;
    }
    .dashboard-welcome p {
        margin-top: 50px;
    }
    .dashboard-idulfitri {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .dashboard-idulfitri-main {
        font-size: 14px;
        color: #31513a;
    }
    .dashboard-divider {
        color: #ffc933;
        font-weight: 900;
        margin: 0 6px;
    }
    .dashboard-idulfitri-note {
        font-size: 8px;
        color: rgba(113, 128, 111, 0.75);
        font-style: italic;
    }

    @media (max-width: 768px) {
        .dashboard-welcome {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 0.90) 55%, rgba(255, 255, 255, 0.45) 100%),
                url('{{ asset('images/masjid_2.png') }}');
            background-position: center;
        }

        .dashboard-welcome h2,
        .dashboard-welcome p {
            max-width: 100%;
        }
    }

    .dashboard-stat-card {
        border-radius: 16px !important;
        background: #fffdf7;
        transition: all 0.2s ease;
        overflow: hidden;
    }
    .dashboard-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(50, 92, 28, 0.12) !important;
    }
    .dashboard-stat-card .card-body {
        min-height: 140px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 18px;
        position: relative;
    }
    /* Icon bulat */
    .dashboard-stat-icon {
        width: 60px;
        height: 60px;
        min-width: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    /* SVG icon */
    .dashboard-stat-icon svg {
        width: 35px;
        height: 35px;
        fill: none;
        stroke: #325c1c;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    /* Warna background masing-masing icon */
    .dashboard-stat-icon-pending {
        background: #edf0c9;
    }
    .dashboard-stat-icon-paid {
        background: #ffc933;
    }
    .dashboard-stat-icon-success {
        background: #dce8ad;
    }
    /* Bagian teks */
    .dashboard-stat-content {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .dashboard-stat-content h3 {
        margin: 0 0 2px;
        color: #31513a;
        font-size: 16px;
        font-weight: 700;
    }
    .dashboard-stat-content strong {
        color: #164d45;
        font-size: 34px !important;
        line-height: 1.1;
        font-weight: 700;
    }
    .zakat-type-card {
        border-radius: 18px !important;
        background: #fffdf7;
        transition: all 0.2s ease;
    }
    .zakat-type-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(50, 92, 28, 0.12) !important;
    }
    .zakat-type-card .card-body {
        padding: 25px;
    }
    .zakat-type-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 18px;
    }
    .zakat-type-icon {
        width: 68px;
        height: 68px;
        min-width: 68px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .zakat-fitrah-icon {
        background: #edf0c9;
    }
    .zakat-fitrah-icon svg {
        width: 42px;
        height: 42px;
        fill: none;
        stroke: #325c1c;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .zakat-maal-icon {
        background: #ffc933;
    }
    .zakat-maal-icon span {
        color: #325c1c;
        font-size: 25px;
        font-weight: 800;
        line-height: 1;
    }
    .zakat-type-card .card-title {
        margin: 0;
        color: #325c1c;
        font-family: Georgia, 'Times New Roman', serif !important;
        font-size: 26px;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }
    .zakat-type-card .card-text { 
        color: #687763;
        line-height: 1.7;
        margin-bottom: 0;
    }
    .zakat-fitrah-card {
        background-image: url('/images/beras.png');
        background-size: cover;
        background-position: center;
    }
    .zakat-maal-card {
        background-image: url('/images/rupiah.png');
        background-size: cover;
        background-position: center;
    }
    .impact-card {
        position: relative;
        overflow: hidden;
        min-height: 260px;
        border-radius: 18px !important;
        background-color: #fffdf7;
        background-image: linear-gradient(90deg, rgba(255, 253, 247, 1) 0%, rgba(255, 253, 247, 0.98) 30%, rgba(255, 253, 247, 0.92) 43%, rgba(255, 253, 247, 0.68) 55%, rgba(255, 253, 247, 0.30) 68%, rgba(255, 253, 247, 0) 82%),
            url('/images/beras_1.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        transition: all 0.2s ease;
    }
    .impact-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(50, 92, 28, 0.12) !important;
    }
    .impact-card-body {
        position: relative;
        z-index: 2;
        min-height: 260px;
        padding: 24px 26px !important;
        background: transparent;
    }
    .impact-card-body > h4 {
        position: relative;
        z-index: 3;
        max-width: 55%;
        margin-bottom: 12px;
        color: #325c1c;
        font-family: Georgia, 'Times New Roman', serif !important;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }
    .impact-card-body .border-top {
        position: relative;
        z-index: 3;
        max-width: 58%;
        margin-top: 12px;
        border-color: rgba(50, 92, 28, 0.10) !important;
    }
    .impact-card-body strong {
        color: #31513a;
        font-weight: 700;
    }
    .impact-card-body small {
        color: #71806f;
        line-height: 1.6;
    }
    .impact-stats {
        margin-top: 50px;
    }
    .impact-card-1 {
        background-image: linear-gradient(90deg, rgba(255, 253, 247, 1) 0%, rgba(255, 253, 247, 0.98) 30%, rgba(255, 253, 247, 0.92) 43%, rgba(255, 253, 247, 0.68) 55%, rgba(255, 253, 247, 0.30) 68%, rgba(255, 253, 247, 0) 82%),
            url('/images/beras_1.png');
    }
    .impact-card-2 {
        background-image: linear-gradient(90deg, rgba(255, 253, 247, 1) 0%, rgba(255, 253, 247, 0.98) 30%, rgba(255, 253, 247, 0.92) 43%, rgba(255, 253, 247, 0.68) 55%, rgba(255, 253, 247, 0.30) 68%, rgba(255, 253, 247, 0) 82%),
            url('/images/beras_2.png');
    }
    .impact-card-3 {
        background-image: linear-gradient(90deg, rgba(255, 253, 247, 1) 0%, rgba(255, 253, 247, 0.98) 30%, rgba(255, 253, 247, 0.92) 43%, rgba(255, 253, 247, 0.68) 55%, rgba(255, 253, 247, 0.30) 68%, rgba(255, 253, 247, 0) 82%),
            url('/images/gold_1.png');
    }
    .impact-card-4 {
        background-image: linear-gradient(90deg, rgba(255, 253, 247, 1) 0%, rgba(255, 253, 247, 0.98) 30%, rgba(255, 253, 247, 0.92) 43%, rgba(255, 253, 247, 0.68) 55%, rgba(255, 253, 247, 0.30) 68%, rgba(255, 253, 247, 0) 82%),
            url('/images/money_1.png');
    }
    .impact-card-5 {
        background-image: linear-gradient(90deg, rgba(255, 253, 247, 1) 0%, rgba(255, 253, 247, 0.98) 30%, rgba(255, 253, 247, 0.92) 43%, rgba(255, 253, 247, 0.68) 55%, rgba(255, 253, 247, 0.30) 68%, rgba(255, 253, 247, 0) 82%),
            url('/images/zakat_2.jpg');
    }
</style>

@section('content')
<div class="p-4 bg-white rounded shadow-sm mb-4 dashboard-welcome">
    <h2>Selamat Datang, {{ Auth::user()->name }}! 👋</h2>
    <span class="text-muted mb-1">Pilih jenis zakat yang ingin Anda tunaikan hari ini.</span>
    @if ($sisaHari !== null && $sisaHari >= 0)
        <p class="dashboard-idulfitri mb-0"><span class="dashboard-idulfitri-main">Tanggal Idul Fitri: {{ \Carbon\Carbon::parse($tanggalIdulfitri)->format('d M Y') }}<span class="dashboard-divider">|</span>{{ $sisaHari }} hari lagi</span>
    <span class="dashboard-idulfitri-note">*Tenggat checkout fitrah berbeda tiap wilayah.</span></p>
    @endif
</div>

<div class="row g-3 mb-4 dashboard-stat-row">
    <div class="col-md-4"><div class="card border-0 shadow-sm dashboard-stat-card"><div class="card-body"><div class="dashboard-stat-icon dashboard-stat-icon-pending">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/></svg></div>
            <div class="dashboard-stat-content"><h3 class="h6">Menunggu pembayaran</h3><strong class="fs-3">{{ $ringkasan['menunggu_pembayaran'] }}</strong></div></div>
        </div>
    </div>
    <div class="col-md-4"><div class="card border-0 shadow-sm dashboard-stat-card"><div class="card-body"><div class="dashboard-stat-icon dashboard-stat-icon-paid">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6 2h9l4 4v16H6z"/><path d="M14 2v5h5"/><path d="M9 11h6"/><path d="M9 14h6"/><path d="M9 17h4"/></svg></div>
            <div class="dashboard-stat-content"><h3 class="h6">Pembayaran tercatat</h3><strong class="fs-3">{{ $ringkasan['dibayar_simulasi'] }}</strong></div></div>
        </div>
    </div>
    <div class="col-md-4"><div class="card border-0 shadow-sm dashboard-stat-card"><div class="card-body"><div class="dashboard-stat-icon dashboard-stat-icon-success">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 3l7 3v5c0 4.7-2.9 8.4-7 10-4.1-1.6-7-5.3-7-10V6l7-3z"/><path d="M8.5 12l2.2 2.2 4.8-5"/></svg></div>
            <div class="dashboard-stat-content"><h3 class="h6">Penyaluran selesai</h3><strong class="fs-3">{{ $ringkasan['selesai'] }}</strong></div></div>
        </div>
    </div>
</div>

@if ($notifikasi->isNotEmpty())
    <section class="card border-0 shadow-sm mb-4"><div class="card-body">
        <h3 class="h5">Pemberitahuan saya</h3>
        <p class="small text-muted">Pemberitahuan ini tampil di aplikasi; email belum dikirim otomatis.</p>
        @foreach ($notifikasi as $pesan)
            <div class="border-top py-3">
                <strong>{{ $pesan->dibaca_at ? 'Sudah dibaca' : 'Baru' }}</strong> ·
                @if ($pesan->jenis === 'penyaluran_selesai')
                    Penyaluran pesanan <a href="{{ route('muzakki.payment.show', $pesan->detail['transaksi_id'] ?? 0) }}">{{ $pesan->detail['kode_transaksi'] ?? 'lihat riwayat' }}</a> ke {{ $pesan->detail['nama_panti'] ?? 'panti' }} selesai.
                @else
                    {{ $pesan->detail['pesan'] ?? 'Ada pemberitahuan baru untuk Anda.' }}
                @endif
                @if (! $pesan->dibaca_at)
                    <form method="POST" action="{{ route('notifikasi.read', $pesan->id) }}" class="mt-2">@csrf<button type="submit" class="btn btn-sm btn-outline-primary">Tandai dibaca</button></form>
                @endif
            </div>
        @endforeach
    </div></section>
@endif

<div class="row">
    <!-- Card Zakat Fitrah -->
    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0 zakat-type-card zakat-fitrah-card"><div class="card-body"><div class="zakat-type-header"><div class="zakat-type-icon zakat-fitrah-icon">
            <svg width="52" height="52" viewBox="0 0 80 80" fill="none"><path d="M17 37H63L58 55C57 59 53 62 49 62H31C27 62 23 59 22 55L17 37Z" fill="#325C1C"/><path d="M22 37C23 29 30 24 40 24C50 24 57 29 58 37H22Z" fill="#FFFDF5"/><path d="M19 63H61" stroke="#FFC933" stroke-width="4" stroke-linecap="round"/></svg></div>
                <div><h4 class="card-title">Zakat Fitrah</h4></div>
            </div>
            <p class="card-text">Pesan beras dari stok gudang untuk panti terverifikasi. Berat per jiwa dan rincian biaya mengikuti pengaturan yang berlaku saat checkout.</p>
            <a href="{{ route('muzakki.fitrah') }}" class="btn btn-success w-100 mt-3">Mulai Zakat Fitrah</a>
            </div>
        </div>
    </div>
    <!-- Card Zakat Maal -->
    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0 zakat-type-card zakat-maal-card"><div class="card-body"><div class="zakat-type-header"><div class="zakat-type-icon zakat-maal-icon">
            <span>Rp</span></div>
                <div><h4 class="card-title">Zakat Maal</h4></div>
            </div>
            <p class="card-text">Zakat harta yang dikeluarkan dari hasil simpanan/aset (Emas, Investasi, Perdagangan, Piutang dikurangi Utang) yang telah mencapai nisab dengan besaran 2,5%.</p>
            <a href="{{ route('muzakki.maal') }}" class="btn btn-success w-100 mt-3">Hitung zakat maal</a>
            </div>
        </div>
    </div>
</div>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h3 class="h5">Pesanan terakhir saya</h3>
    @forelse ($terbaru as $item)
        <div class="border-bottom py-2"><a href="{{ route('muzakki.payment.show', $item->id) }}">{{ $item->kode_transaksi }}</a> · {{ $item->yayasan?->nama ?? 'Panti' }} · {{ $item->status_pembayaran === 'simulated_paid' ? 'Pembayaran tercatat' : str_replace('_', ' ', $item->status_pembayaran) }} / {{ $item->status_penyaluran }}</div>
    @empty
        <p class="text-muted mb-0">Belum ada pesanan. Pilih fitrah atau maal di atas untuk memulai.</p>
    @endforelse
</div></section>

<h3 class="h5">Dampak panti terverifikasi</h3>
<p class="text-muted">Data seluruh periode. Urutan memakai bantuan terkumpul per penerima layak yang tercatat panitia, sehingga ukuran panti ikut diperhitungkan. Panti tanpa jumlah penerima terverifikasi belum masuk peringkat. Fitrah dalam kg, maal dalam Rupiah. “Selesai” berarti ada foto panitia dan PJ dengan jumlah cocok; identitas donor tidak ditampilkan.</p>
<div class="row g-3">
    @foreach ([['judul' => 'Fitrah per penerima tertinggi', 'data' => $pantiFitrah, 'jenis' => 'fitrah'], ['judul' => 'Fitrah per penerima terendah', 'data' => $pantiFitrahRendah, 'jenis' => 'fitrah'], ['judul' => 'Maal per penerima tertinggi', 'data' => $pantiMaal, 'jenis' => 'maal'], ['judul' => 'Maal per penerima terendah', 'data' => $pantiMaalRendah, 'jenis' => 'maal'], ['judul' => 'Di provinsi Anda · fitrah per penerima terendah', 'data' => $pantiSekitar, 'jenis' => 'fitrah']] as $kelompok)
        <div class="col-lg-6"><section class="card border-0 shadow-sm h-100 impact-card impact-card-{{ $loop->index + 1 }}"><div class="card-body impact-card-body">
            <h4 class="h6">{{ $kelompok['judul'] }}</h4>
            @forelse ($kelompok['data'] as $item)
                @php($masuk = $kelompok['jenis'] === 'fitrah' ? $item->fitrah_masuk : $item->maal_masuk)
                @php($selesai = $kelompok['jenis'] === 'fitrah' ? $item->fitrah_selesai : $item->maal_selesai)
                @php($perPenerima = $kelompok['jenis'] === 'fitrah' ? $item->fitrah_per_penerima : $item->maal_per_penerima)
                <div class="border-top py-2">
                    <strong>{{ $item->nama }}</strong><br>
                    <small class="text-muted">{{ $item->kota_kabupaten }}, {{ $item->provinsi }} · {{ $item->jumlah_mustahik_layak }} penerima layak</small><div class="impact-stats">
                    {{ $kelompok['jenis'] === 'fitrah' ? '' : 'Rp' }}{{ number_format($masuk, 2, ',', '.') }} {{ $kelompok['jenis'] === 'fitrah' ? 'kg' : '' }} terkumpul · {{ $kelompok['jenis'] === 'fitrah' ? '' : 'Rp' }}{{ number_format($selesai, 2, ',', '.') }} {{ $kelompok['jenis'] === 'fitrah' ? 'kg' : '' }} selesai
                    <small class="d-block">{{ $kelompok['jenis'] === 'fitrah' ? '' : 'Rp' }}{{ number_format($perPenerima, 2, ',', '.') }} {{ $kelompok['jenis'] === 'fitrah' ? 'kg' : '' }} terkumpul per penerima layak</small>
                    <small class="d-block text-muted">{{ (float) $masuk > 0 ? number_format((float) $selesai / (float) $masuk * 100, 1, ',', '.') . '% selesai' : 'Belum ada data persentase' }}</small>
                </div>
            </div>
            @empty
                <p class="text-muted mb-0">Belum ada panti dalam kelompok ini.</p>
            @endforelse
        </div></section></div>
    @endforeach
</div>
@endsection
