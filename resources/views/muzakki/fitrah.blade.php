@extends('muzakki.layout')

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
    <h1 class="h3 mb-2">Pesan zakat fitrah</h1>
    <p class="text-muted">Berat acuan saat ini {{ $bobot ?? '—' }} kg per jiwa. Harga akhir dihitung ulang oleh server dari lot beras yang tersedia dan dikunci setelah pesanan dibuat.</p>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if ($beras->isEmpty() || $panti->isEmpty())
        <div class="alert alert-warning">Pesanan fitrah belum dibuka. Panitia perlu menyiapkan stok beras, memverifikasi panti, dan admin perlu mengaktifkan wilayah sebelum batas checkout.</div>
    @else
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <form method="POST" action="{{ route('muzakki.fitrah.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4"><label class="form-label">Jumlah jiwa</label><input type="number" name="jumlah_jiwa" class="form-control" min="1" max="50" value="{{ old('jumlah_jiwa', 1) }}" required></div>
                <div class="col-md-8"><label class="form-label">Jenis beras</label><select name="katalog_beras_id" class="form-select" required>@foreach ($beras as $item)<option value="{{ $item->id }}" @selected(old('katalog_beras_id', $beras->first()->id) == $item->id)>{{ $item->nama_beras }} · mulai Rp{{ number_format($item->harga_per_kg, 2, ',', '.') }}/kg · tersedia {{ number_format($item->tersedia_kg, 2, ',', '.') }} kg</option>@endforeach</select><small class="text-muted">Urut dari harga katalog terendah. Harga akhir bisa berubah bila pesanan memakai lebih dari satu lot.</small></div>
                <div class="col-12"><label class="form-label">Panti tujuan</label><select name="yayasan_id" class="form-select" required><option value="">Pilih panti yang terverifikasi dan terjangkau</option>@foreach ($panti as $item)<option value="{{ $item->id }}" @selected(old('yayasan_id') == $item->id)>{{ $item->nama }} · {{ $item->kota_kabupaten }}, {{ $item->provinsi }} · {{ $item->jumlah_mustahik_layak }} penerima layak</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Titip doa (opsional)</label><textarea name="catatan_doa" class="form-control" rows="2" maxlength="1000">{{ old('catatan_doa') }}</textarea></div>
                <div class="col-12"><div class="alert alert-info mb-0">Rincian final memisahkan harga pokok beras, keuntungan, biaya admin, dan ongkos kirim. Pesanan berlaku 30 menit untuk simulasi pembayaran.</div></div>
                <div class="col-12"><button type="submit" class="btn btn-success w-100">Buat pesanan & lihat rincian harga</button></div>
            </form>
        </div></div>
    @endif
</div></div>
@endsection
