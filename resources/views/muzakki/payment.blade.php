@extends('muzakki.layout')

@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
    <h1 class="h3 mb-3">Pesanan {{ $pesanan->kode_transaksi }}</h1>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="alert alert-warning">Pembayaran ini hanya simulasi internal. Status “dibayar” bukan bukti uang sungguhan diterima dan tidak memicu pengiriman beras.</div>
    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <p>Tujuan: <strong>{{ $pesanan->nama_panti }}</strong></p>
        @if ($pesanan->jenis_zakat === 'fitrah')
            <p>Beras: {{ $pesanan->nama_beras_snapshot }} · {{ $pesanan->jumlah_jiwa }} jiwa · {{ $pesanan->total_beras_kg }} kg</p>
            <p>Harga penawaran saat pesanan dibuat: Rp{{ number_format($pesanan->harga_per_kg_snapshot, 2, ',', '.') }}/kg.</p>
        @else
            <p>Harga emas acuan: Rp{{ number_format($pesanan->harga_emas_per_gram_snapshot, 2, ',', '.') }}/gram · Nisab: Rp{{ number_format($pesanan->nilai_nisab, 2, ',', '.') }} · Sumber: {{ $pesanan->sumber_harga_emas_snapshot }}</p>
        @endif
        <table class="table"><tbody>
            @if ($pesanan->jenis_zakat === 'fitrah')
                <tr><td>Harga pokok beras</td><td class="text-end">Rp{{ number_format($pesanan->harga_pokok_snapshot, 2, ',', '.') }}</td></tr>
                <tr><td>Keuntungan beras</td><td class="text-end">Rp{{ number_format($pesanan->keuntungan_snapshot, 2, ',', '.') }}</td></tr>
                <tr><td>Biaya admin</td><td class="text-end">Rp{{ number_format($pesanan->biaya_admin_snapshot, 2, ',', '.') }}</td></tr>
                <tr><td>Ongkos kirim</td><td class="text-end">Rp{{ number_format($pesanan->ongkir_snapshot, 2, ',', '.') }}</td></tr>
            @else
                <tr><td>Zakat maal wajib seluruh pemilik yang memenuhi syarat</td><td class="text-end">Rp{{ number_format($pesanan->total_zakat_wajib, 2, ',', '.') }}</td></tr>
            @endif
            <tr class="fw-bold"><td>Total tagihan simulasi</td><td class="text-end">Rp{{ number_format($pesanan->total_nominal, 2, ',', '.') }}</td></tr>
        </tbody></table>
        @if ($pesanan->jenis_zakat === 'mal' && $pemilikMaal->isNotEmpty())
            <h2 class="h5 mt-4">Perhitungan tiap pemilik saat checkout</h2>
            <p class="small text-muted">Angka ini adalah snapshot saat pesanan dibuat. Perubahan draft berikutnya tidak mengubah riwayat ini.</p>
            @foreach ($pemilikMaal as $pemilik)
                <section class="border-top py-3">
                    <h3 class="h6">{{ ucfirst($pemilik->hubungan) }} · {{ $pemilik->nama_tampilan }}</h3>
                    <div class="table-responsive"><table class="table table-sm mb-2"><tbody>
                        <tr><td>Tabungan / kas</td><td class="text-end">Rp{{ number_format($pemilik->tabungan, 2, ',', '.') }}</td></tr>
                        <tr><td>Emas</td><td class="text-end">{{ number_format($pemilik->emas_gram, 2, ',', '.') }} gram</td></tr>
                        <tr><td>Investasi</td><td class="text-end">Rp{{ number_format($pemilik->investasi, 2, ',', '.') }}</td></tr>
                        <tr><td>Aset dagang</td><td class="text-end">Rp{{ number_format($pemilik->aset_dagang, 2, ',', '.') }}</td></tr>
                        <tr><td>Piutang lancar</td><td class="text-end">Rp{{ number_format($pemilik->piutang, 2, ',', '.') }}</td></tr>
                        <tr><td>Utang jatuh tempo</td><td class="text-end">Rp{{ number_format($pemilik->utang, 2, ',', '.') }}</td></tr>
                        <tr><td>Harta bersih / nisab</td><td class="text-end">Rp{{ number_format($pemilik->harta_bersih, 2, ',', '.') }} / Rp{{ number_format($pemilik->nisab_snapshot, 2, ',', '.') }}</td></tr>
                        <tr><td>Haul</td><td class="text-end">{{ $pemilik->sudah_haul ? 'Dinyatakan terpenuhi' : 'Belum dinyatakan terpenuhi' }}@if ($pemilik->tanggal_haul) · {{ \Carbon\Carbon::parse($pemilik->tanggal_haul)->format('d M Y') }} @endif</td></tr>
                        <tr class="fw-bold"><td>Zakat wajib pemilik</td><td class="text-end">Rp{{ number_format($pemilik->zakat_wajib, 2, ',', '.') }}</td></tr>
                    </tbody></table></div>
                </section>
            @endforeach
        @endif
        <p class="mb-1">Status pembayaran: <strong>{{ $pesanan->status_pembayaran }}</strong></p>
        <p>Status penyaluran: <strong>{{ $pesanan->status_penyaluran }}</strong></p>
        @if ($bukti->isNotEmpty())
            <p class="mb-1">Bukti serah terima:</p>
            @foreach ($bukti as $foto)
                <a href="{{ route('bukti.foto', $foto->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary me-1 mb-2">Foto {{ $foto->pihak === 'panitia' ? 'panitia' : 'panti' }}</a>
            @endforeach
        @endif
        @if ($pesanan->status_pembayaran === 'pending')
            <p>Berlaku sampai {{ \Carbon\Carbon::parse($pesanan->expired_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB.</p>
            <div class="text-center"><img src="{{ $qrDataUri }}" width="300" height="300" alt="QR menuju halaman pembayaran simulasi"><p><a href="{{ $scanUrl }}" rel="noreferrer">Buka halaman QR simulasi</a></p></div>
            <form method="POST" action="{{ route('muzakki.payment.cancel', $pesanan->id) }}" class="text-end">@csrf<button class="btn btn-outline-danger" type="submit">Batalkan pesanan</button></form>
        @endif
    </div></div>
    <a href="{{ route('muzakki.riwayat') }}" class="btn btn-outline-secondary">Lihat riwayat saya</a>
</div></div>
@endsection
