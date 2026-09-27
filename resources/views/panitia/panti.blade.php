@extends('panitia.layout')

@section('content')
<h1 class="h3 mb-2">Panti & verifikasi</h1>
<p class="text-muted">Periksa izin, foto, penerima yang layak, dan jarak jalan dari gudang Surabaya sebelum menerima panti.</p>

@forelse ($panti as $item)
    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h2 class="h5 mb-1">{{ $item->nama }}</h2>
                    <p class="mb-1">{{ $item->alamat_lengkap }}, {{ $item->kota_kabupaten }}, {{ $item->provinsi }}</p>
                    <p class="mb-1">Izin: {{ $item->nomor_izin }} · PJ: {{ $item->nama_pj }}</p>
                    <p class="mb-1">Kontak: {{ $item->telp }} · {{ $item->email }}</p>
                    <p class="mb-2">Penerima diajukan: {{ $item->jumlah_mustahik_diajukan }} · Disetujui: {{ $item->jumlah_mustahik_layak }} · Jarak jalan: {{ $item->jarak_jalan_km ?? 'belum dicatat' }} km</p>
                    <p class="mb-2">Kebutuhan yang diajukan: {{ $item->kebutuhan_saat_ini ?? 'belum dicatat' }}</p>
                    <p class="mb-2">Kapasitas per kunjungan: {{ $item->kapasitas_penerimaan_kg ?? 'belum ditetapkan' }} kg</p>
                </div>
                <span class="badge {{ $item->status_verif === 'verified' ? 'bg-success' : ($item->status_verif === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $item->status_verif }}</span>
            </div>

            <div class="mb-3">
                @foreach ($foto->get($item->id, collect()) as $gambar)
                    <a href="{{ route('panti.foto', $gambar->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary me-1 mb-1">Lihat foto {{ $loop->iteration }}</a>
                @endforeach
            </div>

            @if ($item->status_verif === 'verified')
                <p class="small text-muted">{{ $item->wilayah_layanan_id ? 'Fitrah dan maal · wilayah ' . $item->nama_wilayah . '. ' . ($item->wilayah_aktif ? 'Zona layanan aktif.' : 'Zona layanan belum aktif untuk fitrah.') : 'Maal saja; panti tidak tampil untuk pesanan beras fitrah.' }}</p>
            @endif

            <form method="POST" action="{{ route('panitia.panti.update', $item->id) }}" class="row g-3 mb-3">
                @csrf
                @method('PATCH')
                <input type="hidden" name="hasil" value="verified">
                <div class="col-12"><input type="hidden" name="fitrah_aktif" value="0"><label><input type="checkbox" name="fitrah_aktif" value="1" @checked($item->wilayah_layanan_id)> Layani fitrah beras dari gudang Surabaya juga; kosongkan untuk panti maal saja</label></div>
                <div class="col-md-4">
                    <label class="form-label">Penerima zakat yang layak</label>
                    <input type="number" min="1" name="jumlah_mustahik_layak" class="form-control" value="{{ $item->jumlah_mustahik_layak ?: '' }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Wilayah layanan</label>
                    <select name="wilayah_layanan_id" class="form-select">
                        <option value="">Pilih wilayah</option>
                        @foreach ($wilayah->where('provinsi', $item->provinsi) as $zona)
                            <option value="{{ $zona->id }}" @selected($item->wilayah_layanan_id == $zona->id)>{{ $zona->provinsi }} {{ $zona->is_active ? '(aktif)' : '(belum aktif)' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jarak jalan gudang → panti (km)</label>
                    <input type="number" min="0.01" step="0.01" name="jarak_jalan_km" class="form-control" value="{{ $item->jarak_jalan_km }}">
                </div>
                <div class="col-md-4"><label class="form-label">Kapasitas penerimaan per kunjungan (kg)</label><input type="number" min="0.01" step="0.01" name="kapasitas_penerimaan_kg" class="form-control" value="{{ $item->kapasitas_penerimaan_kg }}"></div>
                <div class="col-md-9"><label class="form-label">Catatan pemeriksaan</label><textarea name="catatan" class="form-control" rows="2" maxlength="2000"></textarea></div>
                <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-success w-100">Simpan verifikasi</button></div>
            </form>

            <form method="POST" action="{{ route('panitia.panti.update', $item->id) }}" class="row g-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="hasil" value="rejected">
                <div class="col-md-9"><label class="form-label">Alasan penolakan</label><input name="catatan" class="form-control" maxlength="2000" required></div>
                <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-outline-danger w-100">Tolak / nonaktifkan</button></div>
            </form>
        </div>
    </section>
@empty
    <div class="alert alert-info">Belum ada panti yang terdaftar.</div>
@endforelse

{{ $panti->links('pagination::bootstrap-5') }}
@endsection
