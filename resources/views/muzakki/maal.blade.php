@extends('muzakki.layout')

<style>
    .maal-title {
        margin-bottom: 20px;
        color: #325c1c;
        font-family: Georgia, 'Times New Roman', serif !important;
        font-size: 40px;
        font-weight: 700;
        line-height: 1.12;
        letter-spacing: -0.04em;
    }
    .h5-title {
        color: #325c1c;
        font-family: Georgia, 'Times New Roman', serif !important;
        font-size: 25px;
        font-weight: 700;
        line-height: 1.12;
        letter-spacing: -0.04em;
    }
</style>

@section('content')
<div class="row justify-content-center"><div class="col-lg-10">
    <h1 class="maal-title">Kalkulator zakat maal keluarga</h1>
    <p class="text-muted">Harta setiap pemilik dihitung sendiri terhadap nisab dan pernyataan haul. Satu akun dapat membayar jumlah kewajiban yang dihasilkan bersama.</p>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($hargaEmas)
        <div class="alert {{ $hargaBerlaku ? 'alert-info' : 'alert-warning' }}">
            {{ $hargaEmas->jenis_sumber === 'api' ? 'Harga acuan emas dunia' : 'Harga emas acuan manual' }}: Rp{{ number_format($hargaEmas->harga_per_gram, 2, ',', '.') }}/gram.
            Sumber: {{ $hargaEmas->sumber }}. Nisab: 85 gram = Rp{{ number_format($hargaEmas->harga_per_gram * 85, 2, ',', '.') }}.
            @if ($hargaEmas->jenis_sumber === 'api')
                <span class="d-block">Data emas: {{ \Carbon\Carbon::parse($hargaEmas->waktu_harga_emas)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB; kurs tanggal {{ $hargaEmas->tanggal_kurs }}. Diambil server: {{ \Carbon\Carbon::parse($hargaEmas->diambil_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB.</span>
                <span class="d-block">Acuan ini berasal dari harga emas dunia yang dikonversi ke rupiah dan dapat berbeda dari harga jual ANTAM.</span>
            @else
                <span class="d-block">Tanggal sumber: {{ \Carbon\Carbon::parse($hargaEmas->tanggal_sumber)->timezone('Asia/Jakarta')->format('d M Y') }}.</span>
            @endif
            @unless ($hargaBerlaku)<span class="d-block">Data emas atau kurs lebih dari tujuh hari; checkout menunggu pembaruan otomatis atau acuan baru dari admin.</span>@endunless
        </div>
    @else
        <div class="alert alert-warning">Harga emas acuan belum tersedia. Tunggu pembaruan otomatis atau hubungi admin. Checkout maal belum tersedia.</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @php($subjekAwal = old('subjek', $draftSubjects ?: [['hubungan' => 'diri', 'nama_tampilan' => 'Hamba Allah', 'tabungan' => '0', 'emas_gram' => '0', 'investasi' => '0', 'aset_dagang' => '0', 'piutang' => '0', 'utang' => '0', 'sudah_haul' => '0']]))
    @if ($draftSubjects)
        <p class="small text-muted">Draft keluarga tersimpan. Isian ini dapat diubah dan disimpan kembali. Pernyataan haul perlu dicentang ulang; harga emas, nisab, dan zakat wajib dihitung ulang saat checkout.</p>
    @endif
        <form method="POST" action="{{ route('muzakki.maal.store') }}">
            @csrf
            <input type="hidden" name="harga_emas_id" value="{{ $hargaEmas->id ?? '' }}">
            <div id="daftar-subjek">
                @foreach ($subjekAwal as $nomor => $subjek)
                    <section class="card border-0 shadow-sm mb-3 subjek-card"><div class="card-body">
                        <h2 class="h5-title">Pemilik harta {{ $loop->iteration }}</h2>
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
                <fieldset>
                    <legend class="h5-title">Panti tujuan</legend>
                    <p class="small text-muted">Panti terverifikasi dapat dipilih langsung atau sistem memilih acak dari panti layak yang belum menerima maal. Bila semuanya pernah menerima, pilihan tetap diacak.</p>
                    <div class="choice-grid mb-3">
                        <label class="choice-card">
                            <input type="radio" name="yayasan_id" value="" @checked(old('yayasan_id', '') === '')>
                            <span><strong>Pilih otomatis</strong><small class="d-block">Acak di antara panti layak yang belum menerima maal.</small></span>
                        </label>
                        @foreach ($panti as $item)
                            @php($persen = (float) $item->maal_masuk > 0 ? (float) $item->maal_selesai / (float) $item->maal_masuk * 100 : null)
                            <label class="choice-card">
                                <input type="radio" name="yayasan_id" value="{{ $item->id }}" @checked(old('yayasan_id') == $item->id)>
                                <span>
                                    <strong>{{ $item->nama }}</strong><small class="d-block">Terverifikasi · {{ $item->kota_kabupaten }}, {{ $item->provinsi }} · {{ $item->jumlah_mustahik_layak }} penerima layak</small>
                                    @if ($item->kebutuhan_saat_ini)<small class="d-block">Kebutuhan: {{ $item->kebutuhan_saat_ini }}</small>@endif
                                    <small class="d-block">Maal terkumpul Rp{{ number_format($item->maal_masuk, 2, ',', '.') }} · selesai Rp{{ number_format($item->maal_selesai, 2, ',', '.') }} · {{ $persen === null ? 'persentase belum ada data' : number_format($persen, 1, ',', '.') . '% selesai' }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <button type="submit" class="btn btn-success w-100 mb-2" @disabled(! $hargaBerlaku || $panti->isEmpty())>Hitung dan buat pesanan maal jika wajib</button>
                <button type="submit" class="btn btn-outline-primary w-100" formaction="{{ route('muzakki.maal.draft') }}">Simpan draft keluarga tanpa membuat pesanan</button>
            </div></div>
        </form>
    @if ($draftSubjects)
        <section class="card border-0 shadow-sm mb-4"><div class="card-body">
            <h2 class="h5">Draft keluarga tersimpan</h2>
            <p class="small text-muted">Hapus seluruh draft keluarga milik akun ini jika sudah tidak diperlukan. Riwayat pesanan dan snapshot perhitungan transaksi tetap tersimpan.</p>
            <form method="POST" action="{{ route('muzakki.maal.draft.destroy') }}" onsubmit="return confirm('Hapus seluruh draft keluarga tersimpan? Isian yang belum disimpan akan ditinggalkan. Riwayat transaksi tetap tersimpan.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Hapus draft keluarga tersimpan</button>
            </form>
        </div></section>
    @endif
    @if ($panti->isEmpty())
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
