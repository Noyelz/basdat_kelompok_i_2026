@extends('panitia.layout')

@section('content')
<h1 class="h3 mb-2">Bukti serah terima</h1>
<p class="text-muted mb-4">Foto panitia dan PJ disimpan privat. Jumlah yang dicatat kedua pihak harus cocok dengan alokasi agar status menjadi selesai.</p>

@forelse ($alokasi as $item)
    <section class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="d-flex justify-content-between gap-2"><h2 class="h5">{{ $item->kode_transaksi }} · {{ $item->nama_panti }}</h2><span class="badge bg-secondary">{{ $item->status }}</span></div>
        <p class="mb-2">{{ $item->jenis_zakat === 'fitrah' ? $item->jumlah_kg . ' kg beras' : 'Rp' . number_format($item->nominal, 2, ',', '.') . ' dana maal' }} · Bukti panitia: {{ $item->bukti_panitia_id ? 'ada' : 'belum' }} · Bukti PJ: {{ $item->bukti_pj_id ? 'ada' : 'belum' }}</p>
        @if ($item->bukti_panitia_id)<a href="{{ route('bukti.foto', $item->bukti_panitia_id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary me-2">Foto panitia</a>@endif
        @if ($item->bukti_pj_id)<a href="{{ route('bukti.foto', $item->bukti_pj_id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Foto PJ</a>@endif
        @if (in_array($item->status, ['dikirim', 'menunggu_bukti', 'perlu_tinjauan'], true))
            <form method="POST" action="{{ route('panitia.bukti.store', $item->id) }}" enctype="multipart/form-data" class="row g-2 mt-2">
                @csrf
                <div class="col-md-3"><label class="form-label">Jumlah {{ $item->jenis_zakat === 'fitrah' ? 'beras (kg)' : 'dana (Rp)' }}</label><input type="number" step="0.01" min="0.01" name="{{ $item->jenis_zakat === 'fitrah' ? 'jumlah_kg' : 'nominal' }}" class="form-control" value="{{ $item->jenis_zakat === 'fitrah' ? $item->jumlah_kg : $item->nominal }}" required></div>
                <div class="col-md-4"><label class="form-label">Foto serah terima</label><input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Catatan (opsional)</label><input name="catatan" class="form-control"></div>
                <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Simpan bukti</button></div>
            </form>
        @endif
    </div></section>
@empty
    <div class="alert alert-info">Belum ada alokasi yang memerlukan bukti.</div>
@endforelse
@endsection
