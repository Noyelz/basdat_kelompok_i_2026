@extends('muzakki.layout')

<style>
    .fitrah-title {
        margin-bottom: 20px;
        color: #325c1c;
        font-family: Georgia, 'Times New Roman', serif !important;
        font-size: 40px;
        font-weight: 700;
        line-height: 1.12;
        letter-spacing: -0.04em;
    }
    .form-label {
        margin-bottom: 20px;
        color: #325c1c;
        font-family: Georgia, 'Times New Roman', serif !important;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.12;
        letter-spacing: -0.04em;
    }
    .h5-title {
        color: #325c1c;
        font-family: Georgia, 'Times New Roman', serif !important;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.12;
        letter-spacing: -0.04em;
    }
</style>

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
    <h1 class="fitrah-title">Pesan zakat fitrah</h1>
    <p class="text-muted">Berat acuan saat ini {{ $bobot ?? '—' }} kg per jiwa. Harga akhir dihitung ulang oleh server dari lot beras yang tersedia dan dikunci setelah pesanan dibuat.</p>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if ($beras->isEmpty() || $panti->isEmpty() || ! $bobot)
        <div class="alert alert-warning">Pesanan fitrah belum dibuka. Panitia perlu menyiapkan stok beras, memverifikasi panti, dan admin perlu mengaktifkan wilayah sebelum batas checkout.</div>
    @else
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <form method="POST" action="{{ route('muzakki.fitrah.store') }}" class="row g-3">
                @csrf
                <div class="col-12"><label class="form-label" for="jumlah-jiwa">Jumlah jiwa</label><input id="jumlah-jiwa" type="number" name="jumlah_jiwa" class="form-control" min="1" max="50" value="{{ old('jumlah_jiwa', 1) }}" required><small class="text-muted">Kebutuhan: <span id="kebutuhan-beras">{{ number_format((float) old('jumlah_jiwa', 1) * (float) $bobot, 2, ',', '.') }}</span> kg.</small></div>
                <fieldset class="col-12">
                    <legend class="h5-title">Pilih jenis beras</legend>
                    <p class="small text-muted">Urut dari harga katalog terendah. Beras termurah yang stoknya cukup akan dipilih otomatis. Harga akhir bisa berubah bila pesanan memakai beberapa lot.</p>
                    <div class="choice-grid" id="pilihan-beras">
                        @foreach ($beras as $item)
                            <label class="choice-card rice-choice" data-tersedia="{{ $item->tersedia_kg }}">
                                <input type="radio" name="katalog_beras_id" value="{{ $item->id }}" @checked(old('katalog_beras_id', $beras->first()->id) == $item->id) required>
                                <span><strong>{{ $item->nama_beras }}</strong><small class="d-block">Mulai Rp{{ number_format($item->harga_per_kg, 2, ',', '.') }}/kg · tersedia {{ number_format($item->tersedia_kg, 2, ',', '.') }} kg</small><small class="rice-status d-block"></small></span>
                            </label>
                        @endforeach
                    </div>
                    <p id="stok-tidak-cukup" class="text-danger small mt-2" hidden>Tidak ada jenis beras dengan stok cukup untuk jumlah jiwa ini. Kurangi jumlah jiwa atau tunggu panitia menambah stok.</p>
                </fieldset>
                <fieldset class="col-12">
                    <legend class="h5-title">Pilih panti tujuan</legend>
                    <p class="small text-muted">Hanya panti terverifikasi dengan wilayah, jarak rute, dan kapasitas penerimaan yang siap ditampilkan.</p>
                    <div class="choice-grid">
                        @foreach ($panti as $item)
                            @php($persen = (float) $item->fitrah_masuk > 0 ? (float) $item->fitrah_selesai / (float) $item->fitrah_masuk * 100 : null)
                            <label class="choice-card panti-choice" data-kapasitas="{{ $item->kapasitas_penerimaan_kg }}">
                                <input type="radio" name="yayasan_id" value="{{ $item->id }}" @checked(old('yayasan_id') == $item->id) required>
                                <span>
                                    <strong>{{ $item->nama }}</strong><small class="d-block">Terverifikasi · {{ $item->kota_kabupaten }}, {{ $item->provinsi }} · {{ $item->jumlah_mustahik_layak }} penerima layak</small>
                                    <small class="d-block">Kapasitas per kunjungan: {{ number_format($item->kapasitas_penerimaan_kg, 2, ',', '.') }} kg · maksimal {{ min(50, (int) bcdiv($item->kapasitas_penerimaan_kg, $bobot, 0)) }} jiwa per pesanan</small>
                                    <small class="panti-status d-block"></small>
                                    @if ($item->kebutuhan_saat_ini)<small class="d-block">Kebutuhan: {{ $item->kebutuhan_saat_ini }}</small>@endif
                                    <small class="d-block">Fitrah terkumpul {{ number_format($item->fitrah_masuk, 2, ',', '.') }} kg · selesai {{ number_format($item->fitrah_selesai, 2, ',', '.') }} kg · {{ $persen === null ? 'persentase belum ada data' : number_format($persen, 1, ',', '.') . '% selesai' }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p id="kapasitas-tidak-cukup" class="text-danger small mt-2" role="status" hidden></p>
                </fieldset>
                <div class="col-12"><label class="form-label">Titip doa (opsional)</label><textarea name="catatan_doa" class="form-control" rows="2" maxlength="1000">{{ old('catatan_doa') }}</textarea></div>
                <div class="col-12"><div class="alert alert-info mb-0">Rincian final memisahkan harga pokok beras, keuntungan, biaya admin, dan ongkos kirim. Pesanan berlaku 30 menit untuk konfirmasi pembayaran.</div></div>
                <div class="col-12"><button id="buat-pesanan-fitrah" type="submit" class="btn btn-success w-100">Buat pesanan & lihat rincian harga</button></div>
            </form>
        </div></div>
    @endif
</div></div>
@endsection

@push('scripts')
<script>
const jumlahJiwaFitrah = document.getElementById('jumlah-jiwa');
const pilihanBeras = [...document.querySelectorAll('.rice-choice')];
const pilihanPanti = [...document.querySelectorAll('.panti-choice')];
const tombolPesananFitrah = document.getElementById('buat-pesanan-fitrah');
const bobotFitrah = @json((float) $bobot);

function perbaruiPilihanFitrah() {
    const jiwa = Number(jumlahJiwaFitrah.value);
    const kebutuhan = Number.isInteger(jiwa) && jiwa >= 1 && jiwa <= 50 ? jiwa * bobotFitrah : Infinity;
    document.getElementById('kebutuhan-beras').textContent = Number.isFinite(kebutuhan)
        ? new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(kebutuhan)
        : '—';
    let pertamaCukup = null;
    for (const kartu of pilihanBeras) {
        const radio = kartu.querySelector('input');
        const cukup = Number(kartu.dataset.tersedia) >= kebutuhan;
        radio.disabled = !cukup;
        kartu.classList.toggle('choice-unavailable', !cukup);
        kartu.querySelector('.rice-status').textContent = cukup ? 'Stok cukup' : 'Stok belum cukup';
        if (cukup && !pertamaCukup) pertamaCukup = radio;
    }
    if (!pilihanBeras.some((kartu) => kartu.querySelector('input:checked:not(:disabled)')) && pertamaCukup) {
        pertamaCukup.checked = true;
    }
    let adaPantiCukup = false;
    let pantiTerpilihTidakCukup = false;
    for (const kartu of pilihanPanti) {
        const radio = kartu.querySelector('input');
        const cukup = Math.round(Number(kartu.dataset.kapasitas) * 100) >= Math.round(kebutuhan * 100);
        radio.disabled = !cukup;
        kartu.classList.toggle('choice-unavailable', !cukup);
        kartu.querySelector('.panti-status').textContent = cukup ? 'Kapasitas cukup' : 'Kapasitas tidak cukup untuk jumlah jiwa ini';
        adaPantiCukup ||= cukup;
        pantiTerpilihTidakCukup ||= radio.checked && !cukup;
    }
    const pesanKapasitas = document.getElementById('kapasitas-tidak-cukup');
    pesanKapasitas.hidden = adaPantiCukup && !pantiTerpilihTidakCukup;
    pesanKapasitas.textContent = adaPantiCukup
        ? 'Kapasitas panti yang dipilih tidak cukup. Kurangi jumlah jiwa atau pilih panti lain.'
        : 'Tidak ada panti dengan kapasitas cukup untuk jumlah jiwa ini. Kurangi jumlah jiwa atau hubungi panitia.';
    tombolPesananFitrah.disabled = !pertamaCukup || !adaPantiCukup || pantiTerpilihTidakCukup;
    document.getElementById('stok-tidak-cukup').hidden = !!pertamaCukup;
}

if (jumlahJiwaFitrah) {
    jumlahJiwaFitrah.addEventListener('input', perbaruiPilihanFitrah);
    jumlahJiwaFitrah.addEventListener('change', perbaruiPilihanFitrah);
    for (const kartu of pilihanPanti) {
        kartu.querySelector('input').addEventListener('change', perbaruiPilihanFitrah);
    }
    window.addEventListener('pageshow', perbaruiPilihanFitrah);
    perbaruiPilihanFitrah();
}
</script>
@endpush
