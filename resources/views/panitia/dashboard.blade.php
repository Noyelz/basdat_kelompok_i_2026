@extends('panitia.layout')

@section('content')
<h1 class="h3 mb-2">Tugas panitia</h1>
<p class="text-muted mb-4">Pembayaran QR di situs ini masih simulasi. Pengiriman beras memerlukan proses dan bukti terpisah.</p>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body"><h2 class="h6">Panti menunggu pemeriksaan</h2><strong class="fs-3">{{ $ringkasan['panti_pending'] }}</strong><p class="mb-0"><a href="{{ route('panitia.panti.index') }}">Periksa panti</a></p></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body"><h2 class="h6">Pesanan uji menunggu alokasi</h2><strong class="fs-3">{{ $ringkasan['pesanan_dibayar'] }}</strong><p class="small text-muted mb-0">Tidak menjadi perintah kirim otomatis.</p></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body"><h2 class="h6">Titik pengiriman tertunda</h2><strong class="fs-3">{{ $ringkasan['pengiriman_tertunda'] }}</strong><p class="small text-muted mb-0">Menunggu pengiriman atau konfirmasi.</p></div></div></div>
</div>
<div class="alert alert-info">Stok tercatat: {{ number_format($ringkasan['stok_kg'], 2, ',', '.') }} kg · Reservasi aktif: {{ number_format($ringkasan['reservasi_kg'], 2, ',', '.') }} kg · Tersedia: {{ number_format(max(0, $ringkasan['stok_kg'] - $ringkasan['reservasi_kg']), 2, ',', '.') }} kg.</div>
@if ($ringkasan['bukti_bermasalah'] > 0)
    <div class="alert alert-warning">Ada {{ $ringkasan['bukti_bermasalah'] }} alokasi dengan jumlah/bukti yang perlu ditinjau. <a href="{{ route('panitia.bukti.index') }}">Buka pemeriksaan bukti</a>.</div>
@endif
<div class="row g-3 mb-4">
    <div class="col-lg-6"><section class="card border-0 shadow-sm h-100"><div class="card-body">
        <h2 class="h5">Panti menunggu pemeriksaan</h2>
        @forelse ($pantiMenunggu as $item)
            <p class="border-top py-2 mb-0"><strong>{{ $item->nama }}</strong><br><small>{{ $item->kota_kabupaten }}, {{ $item->provinsi }}</small></p>
        @empty
            <p class="text-muted">Tidak ada panti menunggu.</p>
        @endforelse
        <a href="{{ route('panitia.panti.index') }}" class="btn btn-outline-primary">Periksa panti</a>
    </div></section></div>
    <div class="col-lg-6"><section class="card border-0 shadow-sm h-100"><div class="card-body">
        <h2 class="h5">Stok tersedia di bawah 25 kg</h2>
        <p class="small text-muted">Patokan awal untuk perhatian panitia, bukan batas pemesanan. Stok tersedia = stok lot di gudang dikurangi reservasi aktif.</p>
        @forelse ($stokRendah as $item)
            <p class="border-top py-2 mb-0"><strong>{{ $item->nama_beras }}</strong> · {{ number_format(max(0, $item->stock - $item->dipesan_kg), 2, ',', '.') }} kg tersedia</p>
        @empty
            <p class="text-muted">Tidak ada produk aktif di bawah patokan ini.</p>
        @endforelse
        <a href="{{ route('panitia.gudang.index') }}" class="btn btn-outline-primary">Buka gudang</a>
    </div></section></div>
    <div class="col-lg-6"><section class="card border-0 shadow-sm h-100"><div class="card-body">
        <h2 class="h5">Pesanan siap dialokasikan</h2>
        @forelse ($pesananSiap as $item)
            <p class="border-top py-2 mb-0"><strong>{{ $item->kode_transaksi }}</strong> · {{ $item->jenis_zakat }}<br><small>{{ $item->nama_panti }}</small></p>
        @empty
            <p class="text-muted">Tidak ada pesanan uji menunggu alokasi.</p>
        @endforelse
        <a href="{{ route('panitia.distribusi.index') }}" class="btn btn-outline-primary">Atur alokasi</a>
    </div></section></div>
    <div class="col-lg-6"><section class="card border-0 shadow-sm h-100"><div class="card-body">
        <h2 class="h5">Perjalanan mendekati jadwal</h2>
        <p class="small text-muted">Jadwal dalam tiga hari ke depan atau sudah lewat, selama perjalanan belum selesai.</p>
        @forelse ($perjalananMendesak as $item)
            <p class="border-top py-2 mb-0"><strong>{{ $item->kode_perjalanan }}</strong> · {{ $item->status }}<br><small>{{ \Carbon\Carbon::parse($item->jadwal_berangkat)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</small></p>
        @empty
            <p class="text-muted">Tidak ada perjalanan mendesak.</p>
        @endforelse
        <a href="{{ route('panitia.distribusi.index') }}" class="btn btn-outline-primary">Lihat perjalanan</a>
    </div></section></div>
    <div class="col-lg-6"><section class="card border-0 shadow-sm h-100"><div class="card-body">
        <h2 class="h5">Bukti masih tertunda</h2>
        @forelse ($buktiTertunda as $item)
            <p class="border-top py-2 mb-0"><strong>{{ $item->kode_transaksi }}</strong> · {{ $item->status }}<br><small>{{ $item->nama_panti }}</small></p>
        @empty
            <p class="text-muted">Tidak ada bukti tertunda.</p>
        @endforelse
        <a href="{{ route('panitia.bukti.index') }}" class="btn btn-outline-primary">Periksa bukti</a>
    </div></section></div>
</div>
<p><a href="{{ route('panitia.gudang.index') }}" class="btn btn-outline-primary">Kelola vendor & gudang</a></p>
<p><a href="{{ route('panitia.laporan.index') }}" class="btn btn-outline-primary">Lihat laporan operasional</a></p>
@endsection
