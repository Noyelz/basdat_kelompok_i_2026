@extends('panitia.layout')

@section('content')
<h1 class="h3 mb-2">Pesanan & alokasi</h1>
<p class="text-muted mb-4">Hanya pesanan dengan konfirmasi pembayaran yang tercatat dapat dialokasikan. Pemberangkatan perjalanan dilakukan secara terpisah oleh panitia.</p>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Buat perjalanan fitrah</h2>
    <form method="POST" action="{{ route('panitia.perjalanan.store') }}" class="row g-3">
        @csrf
        <div class="col-md-3"><label class="form-label">Gudang</label><select name="gudang_id" class="form-select" required><option value="">Pilih gudang</option>@foreach ($gudang as $item)<option value="{{ $item->id }}">{{ $item->nama }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Kendaraan</label><input name="kendaraan" class="form-control" placeholder="Truk 1" required></div>
        <div class="col-md-3"><label class="form-label">Kapasitas (kg)</label><input name="kapasitas_kg" type="number" min="0.01" step="0.01" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Jadwal berangkat (WIB)</label><input name="jadwal_berangkat" type="datetime-local" class="form-control" required></div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Buat perjalanan</button></div>
    </form>
</div></section>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Alokasi fitrah ke satu titik kirim</h2>
    <p class="small text-muted">Pilih beberapa pesanan yang menuju panti yang sama. Jumlahnya tidak boleh melewati kapasitas tersisa kendaraan.</p>
    <form method="POST" action="{{ route('panitia.titik.store') }}">
        @csrf
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">Perjalanan direncanakan</label><select name="perjalanan_id" class="form-select" required><option value="">Pilih perjalanan</option>@foreach ($perjalanan->where('status', 'direncanakan') as $trip)<option value="{{ $trip->id }}">{{ $trip->kode_perjalanan }} · kapasitas {{ $trip->kapasitas_kg }} kg</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Panti tujuan</label><select name="yayasan_id" class="form-select" required><option value="">Pilih panti</option>@foreach ($pesananFitrah->unique('yayasan_id') as $item)<option value="{{ $item->yayasan_id }}">{{ $item->nama_panti }}</option>@endforeach</select></div>
        </div>
        <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Pilih</th><th>Pesanan</th><th>Panti</th><th>Berat</th></tr></thead><tbody>
            @forelse ($pesananFitrah as $item)
                <tr><td><input type="checkbox" name="transaksi_ids[]" value="{{ $item->id }}"></td><td>{{ $item->kode_transaksi }}</td><td>{{ $item->nama_panti }}</td><td>{{ $item->total_beras_kg }} kg</td></tr>
            @empty
                <tr><td colspan="4" class="text-muted">Belum ada pesanan fitrah dengan pembayaran tercatat yang menunggu alokasi.</td></tr>
            @endforelse
        </tbody></table></div>
        <button class="btn btn-primary" type="submit" @disabled($pesananFitrah->isEmpty() || $perjalanan->where('status', 'direncanakan')->isEmpty())>Tambahkan titik & alokasi</button>
    </form>
</div></section>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Perjalanan terakhir</h2>
    @forelse ($perjalanan as $trip)
        <div class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between"><strong>{{ $trip->kode_perjalanan }} · {{ $trip->kendaraan }}</strong><span class="badge bg-secondary">{{ $trip->status }}</span></div>
            <p class="mb-1">Kapasitas {{ $trip->kapasitas_kg }} kg · berangkat {{ \Carbon\Carbon::parse($trip->jadwal_berangkat)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</p>
            <ol class="mb-2">
                @forelse ($titik->get($trip->id, collect()) as $stop)
                    <li class="mb-2">
                        {{ $stop->nama_panti }} · {{ $stop->jumlah_kg }} kg · {{ $stop->status }}
                        @if (in_array($trip->status, ['dikirim', 'selesai'], true))
                            <form method="POST" action="{{ route('panitia.titik.ongkir', $stop->id) }}" class="d-flex gap-2 align-items-center mt-1">
                                @csrf
                                @method('PATCH')
                                <label class="small">Ongkir aktual titik (Rp)</label>
                                <input type="number" name="ongkir_aktual" min="0" step="0.01" value="{{ $stop->ongkir_aktual }}" class="form-control form-control-sm" style="max-width: 180px" required>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Simpan</button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="text-muted">Belum ada titik pengiriman.</li>
                @endforelse
            </ol>
            @if ($titik->has($trip->id))<a href="{{ route('panitia.perjalanan.manifest', $trip->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary mb-2">Manifest cetak</a>@endif
            @if ($trip->status === 'direncanakan' && $titik->has($trip->id))
                <form method="POST" action="{{ route('panitia.perjalanan.dispatch', $trip->id) }}">@csrf<button class="btn btn-warning" type="submit">Catat berangkat & keluarkan stok lot</button></form>
            @endif
            @if ($trip->status === 'direncanakan')
                <form method="POST" action="{{ route('panitia.perjalanan.destroy', $trip->id) }}" class="mt-2" onsubmit="return confirm('Hapus perjalanan kosong ini secara permanen? Perjalanan yang sudah memiliki titik pengiriman tidak dapat dihapus.');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit" @disabled($trip->berangkat_at !== null || $titik->has($trip->id))>Hapus perjalanan kosong</button>
                </form>
                @if ($titik->has($trip->id))<p class="small text-muted mt-1 mb-0">Sudah memiliki titik pengiriman/alokasi; perjalanan tidak dapat dihapus.</p>@endif
            @endif
        </div>
    @empty
        <p class="text-muted">Belum ada perjalanan.</p>
    @endforelse
</div></section>

<section class="card border-0 shadow-sm"><div class="card-body">
    <h2 class="h5">Alokasi maal</h2>
    <p class="small text-muted">Alokasi maal tidak memakai truk atau stok beras. Bukti serah terima tetap dibutuhkan dari panitia dan PJ.</p>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Pesanan</th><th>Panti</th><th>Nominal</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($pesananMaal as $item)
            <tr><td>{{ $item->kode_transaksi }}</td><td>{{ $item->nama_panti }}</td><td>Rp{{ number_format($item->total_nominal, 2, ',', '.') }}</td><td><form method="POST" action="{{ route('panitia.maal.allocate', $item->id) }}">@csrf<button class="btn btn-sm btn-primary" type="submit">Alokasikan</button></form></td></tr>
        @empty
            <tr><td colspan="4" class="text-muted">Belum ada pesanan maal menunggu alokasi.</td></tr>
        @endforelse
    </tbody></table></div>
</div></section>
@endsection
