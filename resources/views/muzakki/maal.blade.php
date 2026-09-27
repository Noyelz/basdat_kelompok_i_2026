@extends('muzakki.layout')

@section('content')
<div class="row justify-content-center"><div class="col-lg-10">
    <h1 class="h3 mb-2">Kalkulator zakat maal keluarga</h1>
    <p class="text-muted">Harta setiap pemilik dihitung sendiri terhadap nisab dan pernyataan haul. Satu akun dapat membayar jumlah kewajiban yang dihasilkan bersama.</p>
    @if ($hargaEmas)
        <div class="alert {{ $hargaBerlaku ? 'alert-info' : 'alert-warning' }}">Harga emas acuan: Rp{{ number_format($hargaEmas->harga_per_gram, 2, ',', '.') }}/gram, sumber {{ $hargaEmas->sumber }}, tanggal {{ \Carbon\Carbon::parse($hargaEmas->tanggal_sumber)->timezone('Asia/Jakarta')->format('d M Y') }}. Nisab: 85 gram = Rp{{ number_format($hargaEmas->harga_per_gram * 85, 2, ',', '.') }}. {{ $hargaBerlaku ? '' : 'Harga lebih dari tujuh hari; admin perlu memperbaruinya sebelum checkout.' }}</div>
    @else
        <div class="alert alert-warning">Harga emas acuan belum dicatat oleh admin. Checkout maal belum tersedia.</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if ($hargaBerlaku && $panti->isNotEmpty())
        @php($subjekAwal = old('subjek', [['hubungan' => 'diri', 'nama_tampilan' => 'Hamba Allah', 'tabungan' => '0', 'emas_gram' => '0', 'investasi' => '0', 'aset_dagang' => '0', 'piutang' => '0', 'utang' => '0', 'sudah_haul' => '0']]))
        <form method="POST" action="{{ route('muzakki.maal.store') }}">
            @csrf
            <div id="daftar-subjek">
                @foreach ($subjekAwal as $nomor => $subjek)
                    <section class="card border-0 shadow-sm mb-3 subjek-card"><div class="card-body">
                        <h2 class="h5">Pemilik harta {{ $loop->iteration }}</h2>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Hubungan</label><select name="subjek[{{ $nomor }}][hubungan]" class="form-select" required><option value="diri" @selected(($subjek['hubungan'] ?? '') === 'diri')>Diri sendiri</option><option value="istri" @selected(($subjek['hubungan'] ?? '') === 'istri')>Istri</option><option value="anak" @selected(($subjek['hubungan'] ?? '') === 'anak')>Anak</option></select></div>
                            <div class="col-md-4"><label class="form-label">Nama tampilan</label><input name="subjek[{{ $nomor }}][nama_tampilan]" class="form-control" value="{{ $subjek['nama_tampilan'] ?? 'Hamba Allah' }}" maxlength="255"></div>
                            <div class="col-md-4"><label class="form-label">Tabungan / kas (Rp)</label><input type="number" min="0" step="0.01" name="subjek[{{ $nomor }}][tabungan]" class="form-control" value="{{ $subjek['tabungan'] ?? 0 }}" required></div>
                            <div class="col-md-4"><label class="form-label">Emas (gram)</label><input type="number" min="0" step="0.01" name="subjek[{{ $nomor }}][emas_gram]" class="form-control" value="{{ $subjek['emas_gram'] ?? 0 }}" required></div>
                            <div class="col-md-4"><label class="form-label">Investasi (Rp)</label><input type="number" min="0" step="0.01" name="subjek[{{ $nomor }}][investasi]" class="form-control" value="{{ $subjek['investasi'] ?? 0 }}" required></div>
                            <div class="col-md-4"><label class="form-label">Aset dagang (Rp)</label><input type="number" min="0" step="0.01" name="subjek[{{ $nomor }}][aset_dagang]" class="form-control" value="{{ $subjek['aset_dagang'] ?? 0 }}" required></div>
                            <div class="col-md-4"><label class="form-label">Piutang lancar (Rp)</label><input type="number" min="0" step="0.01" name="subjek[{{ $nomor }}][piutang]" class="form-control" value="{{ $subjek['piutang'] ?? 0 }}" required></div>
                            <div class="col-md-4"><label class="form-label">Utang jatuh tempo (Rp)</label><input type="number" min="0" step="0.01" name="subjek[{{ $nomor }}][utang]" class="form-control" value="{{ $subjek['utang'] ?? 0 }}" required></div>
                            <div class="col-md-4"><label class="form-label">Tanggal mulai haul (jika dicatat)</label><input type="date" name="subjek[{{ $nomor }}][tanggal_haul]" class="form-control" value="{{ $subjek['tanggal_haul'] ?? '' }}"></div>
                            <div class="col-12"><input type="hidden" name="subjek[{{ $nomor }}][sudah_haul]" value="0"><label><input type="checkbox" name="subjek[{{ $nomor }}][sudah_haul]" value="1" @checked($subjek['sudah_haul'] ?? false)> Saya menyatakan haul pemilik ini sudah terpenuhi</label></div>
                        </div>
                        @if ($nomor > 0)<button type="button" class="btn btn-sm btn-outline-danger mt-3 hapus-subjek">Hapus pemilik ini</button>@endif
                    </div></section>
                @endforeach
            </div>
            <button type="button" id="tambah-subjek" class="btn btn-outline-primary mb-4">Tambah istri / anak</button>
            <div class="card border-0 shadow-sm mb-4"><div class="card-body">
                <label class="form-label" for="yayasan_id">Panti tujuan</label>
                <select id="yayasan_id" name="yayasan_id" class="form-select"><option value="">Pilih otomatis secara acak di antara panti yang belum menerima maal</option>@foreach ($panti as $item)<option value="{{ $item->id }}" @selected(old('yayasan_id') == $item->id)>{{ $item->nama }} · {{ $item->kota_kabupaten }}, {{ $item->provinsi }}</option>@endforeach</select>
                <p class="small text-muted mt-2">Jika semua panti pernah menerima maal, pilihan otomatis tetap acak di antara panti layak. Kalkulasi dan panti pilihan disimpan sebagai snapshot.</p>
                <button type="submit" class="btn btn-success w-100">Hitung dan buat pesanan maal jika wajib</button>
            </div></div>
        </form>
    @elseif ($panti->isEmpty())
        <div class="alert alert-warning">Belum ada panti terverifikasi yang aktif untuk menerima zakat maal.</div>
    @endif
</div></div>
@endsection

@push('scripts')
<script>
document.getElementById('tambah-subjek')?.addEventListener('click', () => {
    const container = document.getElementById('daftar-subjek');
    if (container.querySelectorAll('.subjek-card').length >= 10) return;
    const nomor = Date.now();
    const card = document.createElement('section');
    card.className = 'card border-0 shadow-sm mb-3 subjek-card';
    card.innerHTML = `<div class="card-body"><h2 class="h5">Pemilik harta tambahan</h2><div class="row g-3">
        <div class="col-md-4"><label class="form-label">Hubungan</label><select class="form-select" name="subjek[${nomor}][hubungan]"><option value="istri">Istri</option><option value="anak">Anak</option></select></div>
        <div class="col-md-4"><label class="form-label">Nama tampilan</label><input class="form-control" name="subjek[${nomor}][nama_tampilan]" value="Hamba Allah"></div>
        ${['tabungan','emas_gram','investasi','aset_dagang','piutang','utang'].map((field) => `<div class="col-md-4"><label class="form-label">${field.replaceAll('_',' ')}</label><input class="form-control" type="number" min="0" step="0.01" name="subjek[${nomor}][${field}]" value="0" required></div>`).join('')}
        <div class="col-md-4"><label class="form-label">Tanggal mulai haul</label><input class="form-control" type="date" name="subjek[${nomor}][tanggal_haul]"></div>
        <div class="col-12"><input type="hidden" name="subjek[${nomor}][sudah_haul]" value="0"><label><input type="checkbox" name="subjek[${nomor}][sudah_haul]" value="1"> Saya menyatakan haul sudah terpenuhi</label></div>
        </div><button type="button" class="btn btn-sm btn-outline-danger mt-3 hapus-subjek">Hapus pemilik ini</button></div>`;
    container.appendChild(card);
});
document.getElementById('daftar-subjek')?.addEventListener('click', (event) => {
    if (event.target.classList.contains('hapus-subjek')) event.target.closest('.subjek-card').remove();
});
document.getElementById('daftar-subjek')?.addEventListener('focusin', (event) => {
    if (event.target.type === 'number' && event.target.value === '0') event.target.value = '';
});
</script>
@endpush
