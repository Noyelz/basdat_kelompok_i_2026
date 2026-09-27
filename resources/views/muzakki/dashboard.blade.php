@extends('muzakki.layout')

@section('content')
<div class="p-4 bg-white rounded shadow-sm mb-4">
    <h2>Selamat Datang, {{ Auth::user()->name }}! 👋</h2>
    <p class="text-muted mb-1">Pilih jenis zakat yang ingin Anda tunaikan hari ini. Semua angka pembayaran saat ini berasal dari simulasi internal.</p>
    @if ($sisaHari !== null && $sisaHari >= 0)
        <p class="mb-0">Tanggal Idulfitri yang ditetapkan admin: {{ \Carbon\Carbon::parse($tanggalIdulfitri)->format('d M Y') }} · {{ $sisaHari }} hari lagi. Tenggat checkout fitrah berbeda tiap wilayah.</p>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><h3 class="h6">Menunggu pembayaran uji</h3><strong class="fs-3">{{ $ringkasan['menunggu_pembayaran'] }}</strong></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><h3 class="h6">Dibayar simulasi</h3><strong class="fs-3">{{ $ringkasan['dibayar_simulasi'] }}</strong></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><h3 class="h6">Penyaluran selesai</h3><strong class="fs-3">{{ $ringkasan['selesai'] }}</strong></div></div></div>
</div>

<div class="row">
    <!-- Card Zakat Fitrah -->
    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title text-success fw-bold">Zakat Fitrah</h4>
                <p class="card-text">
                    Pesan beras dari stok gudang untuk panti terverifikasi. Berat per jiwa dan rincian biaya mengikuti pengaturan yang berlaku saat checkout.
                </p>
                <a href="{{ route('muzakki.fitrah') }}" class="btn btn-success w-100 mt-3">Mulai Zakat Fitrah</a>
            </div>
        </div>
    </div>

    <!-- Card Zakat Maal -->
    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title text-success fw-bold">Zakat Maal</h4>
                <p class="card-text">
                    Zakat harta yang dikeluarkan dari hasil simpanan/aset (Emas, Investasi, Perdagangan, Piutang dikurangi Utang) yang telah mencapai nisab dengan besaran 2,5%.
                </p>
                <a href="{{ route('muzakki.maal') }}" class="btn btn-success w-100 mt-3">Hitung & bayar simulasi maal</a>
            </div>
        </div>
    </div>
</div>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h3 class="h5">Pesanan terakhir saya</h3>
    @forelse ($terbaru as $item)
        <div class="border-bottom py-2"><a href="{{ route('muzakki.payment.show', $item->id) }}">{{ $item->kode_transaksi }}</a> · {{ $item->yayasan?->nama ?? 'Panti' }} · {{ $item->status_pembayaran }} / {{ $item->status_penyaluran }}</div>
    @empty
        <p class="text-muted mb-0">Belum ada pesanan. Pilih fitrah atau maal di atas untuk memulai.</p>
    @endforelse
</div></section>

<h3 class="h5">Dampak panti terverifikasi</h3>
<p class="text-muted">Seluruh periode database pengembangan. Urutan memakai bantuan simulasi terkumpul per penerima layak yang tercatat panitia, sehingga ukuran panti ikut diperhitungkan. Panti tanpa jumlah penerima terverifikasi belum masuk peringkat. Fitrah dalam kg, maal dalam Rupiah. “Selesai” berarti ada foto panitia dan PJ dengan jumlah cocok; identitas donor tidak ditampilkan.</p>
<div class="row g-3">
    @foreach ([['judul' => 'Fitrah per penerima tertinggi', 'data' => $pantiFitrah, 'jenis' => 'fitrah'], ['judul' => 'Fitrah per penerima terendah', 'data' => $pantiFitrahRendah, 'jenis' => 'fitrah'], ['judul' => 'Maal per penerima tertinggi', 'data' => $pantiMaal, 'jenis' => 'maal'], ['judul' => 'Maal per penerima terendah', 'data' => $pantiMaalRendah, 'jenis' => 'maal'], ['judul' => 'Di provinsi Anda · fitrah per penerima terendah', 'data' => $pantiSekitar, 'jenis' => 'fitrah']] as $kelompok)
        <div class="col-lg-6"><section class="card border-0 shadow-sm h-100"><div class="card-body">
            <h4 class="h6">{{ $kelompok['judul'] }}</h4>
            @forelse ($kelompok['data'] as $item)
                @php($masuk = $kelompok['jenis'] === 'fitrah' ? $item->fitrah_masuk : $item->maal_masuk)
                @php($selesai = $kelompok['jenis'] === 'fitrah' ? $item->fitrah_selesai : $item->maal_selesai)
                @php($perPenerima = $kelompok['jenis'] === 'fitrah' ? $item->fitrah_per_penerima : $item->maal_per_penerima)
                <div class="border-top py-2">
                    <strong>{{ $item->nama }}</strong><br>
                    <small class="text-muted">{{ $item->kota_kabupaten }}, {{ $item->provinsi }} · {{ $item->jumlah_mustahik_layak }} penerima layak</small><br>
                    {{ $kelompok['jenis'] === 'fitrah' ? '' : 'Rp' }}{{ number_format($masuk, 2, ',', '.') }} {{ $kelompok['jenis'] === 'fitrah' ? 'kg' : '' }} terkumpul · {{ $kelompok['jenis'] === 'fitrah' ? '' : 'Rp' }}{{ number_format($selesai, 2, ',', '.') }} {{ $kelompok['jenis'] === 'fitrah' ? 'kg' : '' }} selesai
                    <small class="d-block">{{ $kelompok['jenis'] === 'fitrah' ? '' : 'Rp' }}{{ number_format($perPenerima, 2, ',', '.') }} {{ $kelompok['jenis'] === 'fitrah' ? 'kg' : '' }} terkumpul per penerima layak</small>
                    <small class="d-block text-muted">{{ (float) $masuk > 0 ? number_format((float) $selesai / (float) $masuk * 100, 1, ',', '.') . '% selesai' : 'Belum ada data persentase' }}</small>
                </div>
            @empty
                <p class="text-muted mb-0">Belum ada panti dalam kelompok ini.</p>
            @endforelse
        </div></section></div>
    @endforeach
</div>
@endsection
