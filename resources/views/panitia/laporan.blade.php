@extends('panitia.layout')

<style>
    .h3-title {
        margin-bottom: 10px;
        color: #325c1c;
        font-family: Georgia, 'Times New Roman', serif !important;
        font-size: 40px;
        font-weight: 700;
        line-height: 1.12;
        letter-spacing: -0.04em;
    }
</style>

@section('content')
<h1 class="h3-title">Laporan operasional</h1>
<p class="text-muted mb-4">Periode: seluruh data yang tercatat. Laporan menampilkan status pesanan, persediaan, pengiriman, dan bukti penerimaan.</p>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card shadow-sm border-0"><div class="card-body"><h2 class="h6">Panti menunggu pemeriksaan</h2><strong class="fs-3">{{ $tugas['panti_pending'] }}</strong></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0"><div class="card-body"><h2 class="h6">Selisih bukti/jumlah</h2><strong class="fs-3">{{ $tugas['alokasi_bermasalah'] }}</strong></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0"><div class="card-body"><h2 class="h6">Bukti tertunda</h2><strong class="fs-3">{{ $tugas['bukti_tertunda'] }}</strong></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm border-0"><div class="card-body"><h2 class="h6">Ongkir aktual belum lengkap</h2><strong class="fs-3">{{ $tugas['ongkir_belum_dicatat'] }}</strong></div></div></div>
</div>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Status pesanan</h2>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Jenis</th><th>Pembayaran</th><th>Penyaluran</th><th>Jumlah pesanan</th></tr></thead><tbody>
        @forelse ($status as $item)
            <tr><td>{{ $item->jenis_zakat }}</td><td>{{ $item->status_pembayaran === 'simulated_paid' ? 'Pembayaran tercatat' : str_replace('_', ' ', $item->status_pembayaran) }}</td><td>{{ $item->status_penyaluran }}</td><td>{{ $item->jumlah }}</td></tr>
        @empty
            <tr><td colspan="4" class="text-muted">Belum ada transaksi.</td></tr>
        @endforelse
    </tbody></table></div>
</div></section>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Rekonsiliasi stok beras</h2>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Beras</th><th>Stok katalog</th><th>Jumlah lot</th><th>Reservasi aktif</th><th>Tersedia</th><th>Selisih katalog vs lot</th></tr></thead><tbody>
        @forelse ($stok as $item)
            <tr><td>{{ $item->nama_beras }}</td><td>{{ number_format($item->stock, 2, ',', '.') }} kg</td><td>{{ number_format($item->lot_kg, 2, ',', '.') }} kg</td><td>{{ number_format($item->reservasi_kg, 2, ',', '.') }} kg</td><td>{{ number_format(max(0, $item->stock - $item->reservasi_kg), 2, ',', '.') }} kg</td><td>{{ number_format(bcsub($item->stock, $item->lot_kg, 2), 2, ',', '.') }} kg</td></tr>
        @empty
            <tr><td colspan="6" class="text-muted">Belum ada beras.</td></tr>
        @endforelse
    </tbody></table></div>
</div></section>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Ongkos kirim per perjalanan</h2>
    <p class="small text-muted">Selisih = biaya aktual dikurangi ongkir yang sudah dibebankan di pesanan. Pesanan lama tidak ditagih ulang.</p>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Perjalanan</th><th>Status</th><th>Ongkir ditagih</th><th>Ongkir aktual</th><th>Selisih</th></tr></thead><tbody>
        @forelse ($perjalanan as $trip)
            @php($ditagih = $ongkirDitagih[$trip->id] ?? '0.00')
            <tr><td>{{ $trip->kode_perjalanan }}</td><td>{{ $trip->status }}</td><td>Rp{{ number_format($ditagih, 2, ',', '.') }}</td><td>{{ $trip->ongkir_aktual === null ? 'belum lengkap' : 'Rp' . number_format($trip->ongkir_aktual, 2, ',', '.') }}</td><td>{{ $trip->ongkir_aktual === null ? '—' : 'Rp' . number_format(bcsub($trip->ongkir_aktual, $ditagih, 2), 2, ',', '.') }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-muted">Belum ada perjalanan.</td></tr>
        @endforelse
    </tbody></table></div>
</div></section>

<section class="card border-0 shadow-sm"><div class="card-body">
    <h2 class="h5">Dampak per panti</h2>
    <p class="small text-muted">“Tersalurkan” mengikuti asumsi tugas: kedua pihak mengunggah bukti dengan jumlah cocok. Fitrah dalam kg dan maal dalam Rupiah tidak dijumlahkan.</p>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Panti</th><th>Penerima layak</th><th>Fitrah terkumpul</th><th>Fitrah selesai</th><th>Maal terkumpul</th><th>Maal selesai</th></tr></thead><tbody>
        @forelse ($panti as $item)
            <tr><td>{{ $item->nama }}<br><small class="text-muted">{{ $item->kota_kabupaten }}, {{ $item->provinsi }}</small></td><td>{{ $item->jumlah_mustahik_layak }}</td><td>{{ number_format($fitrahTerkumpul[$item->id] ?? 0, 2, ',', '.') }} kg</td><td>{{ number_format($fitrahSelesai[$item->id] ?? 0, 2, ',', '.') }} kg</td><td>Rp{{ number_format($maalTerkumpul[$item->id] ?? 0, 2, ',', '.') }}</td><td>Rp{{ number_format($maalSelesai[$item->id] ?? 0, 2, ',', '.') }}</td></tr>
        @empty
            <tr><td colspan="6" class="text-muted">Belum ada panti terverifikasi.</td></tr>
        @endforelse
    </tbody></table></div>
</div></section>
@endsection
