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
<p><a href="{{ route('panitia.gudang.index') }}" class="btn btn-outline-primary">Kelola vendor & gudang</a></p>
<p><a href="{{ route('panitia.laporan.index') }}" class="btn btn-outline-primary">Lihat laporan operasional</a></p>
@endsection
