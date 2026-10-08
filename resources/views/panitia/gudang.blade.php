@extends('panitia.layout')

@section('content')
<h1 class="h3 mb-2">Vendor & gudang Surabaya</h1>
<p class="text-muted mb-4">Setiap penerimaan beras membuat lot dengan harga beli tetap. Stok tersedia adalah stok gudang dikurangi pesanan yang masih reservasi.</p>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <section class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h5">Tambah vendor</h2>
            <form method="POST" action="{{ route('panitia.vendor.store') }}" class="row g-2">
                @csrf
                <div class="col-12"><label class="form-label">Nama vendor</label><input name="nama" class="form-control" value="{{ old('vendor_edit_id') ? '' : old('nama') }}" required></div>
                <div class="col-md-6"><label class="form-label">Telepon</label><input name="telp" class="form-control" value="{{ old('vendor_edit_id') ? '' : old('telp') }}"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('vendor_edit_id') ? '' : old('email') }}"></div>
                <div class="col-12"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2">{{ old('vendor_edit_id') ? '' : old('alamat') }}</textarea></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Simpan vendor</button></div>
            </form>
        </div></section>
    </div>
    <div class="col-lg-6">
        <section class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h5">Tambah jenis beras</h2>
            <form method="POST" action="{{ route('panitia.katalog.store') }}" class="row g-2">
                @csrf
                <div class="col-12"><label class="form-label">Jenis / merek beras</label><input name="nama_beras" class="form-control" value="{{ old('nama_beras') }}" required></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Simpan jenis beras</button></div>
            </form>
            <p class="small text-muted mt-3 mb-0">Harga katalog mengikuti harga beli lot terlama yang masih tersedia ditambah persentase keuntungan yang diatur admin.</p>
        </div></section>
    </div>
</div>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Daftar vendor</h2>
    <p class="small text-muted">Vendor yang belum memiliki pembelian dapat dihapus. Jika sudah digunakan, ubah status menjadi nonaktif melalui Edit vendor.</p>
    @forelse ($vendor as $item)
        @php($editVendorIni = (int) old('vendor_edit_id', 0) === (int) $item->id)
        <div class="border rounded p-3 mb-3">
            <h3 class="h6">{{ $item->nama }} · {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</h3>
            <p class="small mb-2">Telepon: {{ $item->telp ?? '—' }} · Email: {{ $item->email ?? '—' }} · {{ $item->pembelian_count }} pembelian</p>
            <p class="small text-muted">Alamat: {{ $item->alamat ?? '—' }}</p>
            <details @if ($editVendorIni) open @endif>
                <summary class="btn btn-sm btn-outline-primary mb-2">Edit vendor</summary>
                <form method="POST" action="{{ route('panitia.vendor.update', $item->id) }}" class="row g-2 mb-3">
                    @csrf
                    @method('PATCH')
                    <div class="col-md-6"><label class="form-label" for="vendor-nama-{{ $item->id }}">Nama vendor</label><input id="vendor-nama-{{ $item->id }}" name="nama" class="form-control" maxlength="255" value="{{ $editVendorIni ? old('nama') : $item->nama }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="vendor-telp-{{ $item->id }}">Telepon</label><input id="vendor-telp-{{ $item->id }}" name="telp" class="form-control" maxlength="20" value="{{ $editVendorIni ? old('telp') : $item->telp }}"></div>
                    <div class="col-md-6"><label class="form-label" for="vendor-email-{{ $item->id }}">Email</label><input id="vendor-email-{{ $item->id }}" name="email" type="email" class="form-control" maxlength="191" value="{{ $editVendorIni ? old('email') : $item->email }}"></div>
                    <div class="col-md-6"><label class="form-label" for="vendor-aktif-{{ $item->id }}">Status vendor</label><select id="vendor-aktif-{{ $item->id }}" name="is_active" class="form-select" required><option value="1" @selected($editVendorIni ? old('is_active') == 1 : $item->is_active)>Aktif</option><option value="0" @selected($editVendorIni ? old('is_active') == 0 : ! $item->is_active)>Nonaktif</option></select></div>
                    <div class="col-12"><label class="form-label" for="vendor-alamat-{{ $item->id }}">Alamat</label><textarea id="vendor-alamat-{{ $item->id }}" name="alamat" class="form-control" maxlength="2000" rows="2">{{ $editVendorIni ? old('alamat') : $item->alamat }}</textarea></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Simpan perubahan vendor</button></div>
                </form>
            </details>
            <form method="POST" action="{{ route('panitia.vendor.destroy', $item->id) }}" onsubmit="return confirm('Hapus vendor ini secara permanen? Vendor hanya dapat dihapus jika belum memiliki pembelian.');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" type="submit" @disabled($item->pembelian_count > 0)>Hapus vendor</button>
                @if ($item->pembelian_count > 0)<span class="small text-muted">Riwayat pembelian harus tetap tersimpan.</span>@endif
            </form>
        </div>
    @empty
        <p class="text-muted">Belum ada vendor. Tambahkan melalui formulir di atas.</p>
    @endforelse
</div></section>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Catat beras diterima di gudang</h2>
    <p class="small text-muted">Gunakan hanya setelah barang benar-benar tiba di gudang.</p>
    <form method="POST" action="{{ route('panitia.pembelian.receive') }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-md-4"><label class="form-label">Vendor</label><select name="vendor_id" class="form-select" required><option value="">Pilih vendor</option>@foreach ($vendor->where('is_active', true) as $item)<option value="{{ $item->id }}" @selected(old('vendor_id') == $item->id)>{{ $item->nama }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Gudang</label><select name="gudang_id" class="form-select" required><option value="">Pilih gudang</option>@foreach ($gudang as $item)<option value="{{ $item->id }}" @selected(old('gudang_id') == $item->id)>{{ $item->nama }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Jenis beras</label><select name="katalog_beras_id" class="form-select" required><option value="">Pilih beras</option>@foreach ($katalog->where('is_active', true) as $item)<option value="{{ $item->id }}" @selected(old('katalog_beras_id') == $item->id)>{{ $item->nama_beras }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Jumlah masuk (kg)</label><input type="number" name="jumlah_kg" min="0.01" step="0.01" class="form-control" value="{{ old('jumlah_kg') }}" required></div>
        <div class="col-md-3"><label class="form-label">Harga beli per kg (Rp)</label><input type="number" name="harga_beli_per_kg" min="0.01" step="0.01" class="form-control" value="{{ old('harga_beli_per_kg') }}" required></div>
        <div class="col-md-3"><label class="form-label">Tanggal masuk</label><input type="date" name="tanggal_masuk" class="form-control" value="{{ old('tanggal_masuk', now()->toDateString()) }}" required></div>
        <div class="col-md-3"><label class="form-label">Kedaluwarsa (jika ada)</label><input type="date" name="tanggal_kedaluwarsa" class="form-control" value="{{ old('tanggal_kedaluwarsa') }}"></div>
        <div class="col-md-6"><label class="form-label">Nomor faktur (jika ada)</label><input name="nomor_faktur" class="form-control" value="{{ old('nomor_faktur') }}"></div>
        <div class="col-md-6"><label class="form-label">Bukti faktur (PDF/foto, opsional, maksimal 5 MB)</label><input type="file" name="bukti_faktur" accept="application/pdf,image/jpeg,image/png,image/webp" class="form-control"></div>
        <div class="col-md-6 d-flex align-items-end"><button class="btn btn-success w-100" type="submit" @disabled($vendor->where('is_active', true)->isEmpty() || $gudang->isEmpty() || $katalog->where('is_active', true)->isEmpty())>Catat penerimaan</button></div>
    </form>
</div></section>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Pembelian & bukti faktur</h2>
    <p class="small text-muted">Dokumen tersimpan privat dan hanya bisa dibuka akun panitia. Faktur yang belum ada dapat dilengkapi setelah penerimaan.</p>
    <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Pembelian</th><th>Vendor</th><th>Gudang</th><th>Tanggal</th><th>Nomor faktur</th><th>Bukti</th></tr></thead><tbody>
        @forelse ($pembelian as $item)
            <tr>
                <td>{{ $item->kode_pembelian }}</td><td>{{ $item->nama_vendor }}</td><td>{{ $item->nama_gudang }}</td><td>{{ $item->tanggal_pembelian }}</td><td>{{ $item->nomor_faktur ?? '—' }}</td>
                <td>
                    @if ($item->bukti_path)
                        <a href="{{ route('panitia.pembelian.faktur.show', $item->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Lihat faktur</a>
                    @else
                        <form method="POST" action="{{ route('panitia.pembelian.faktur.store', $item->id) }}" enctype="multipart/form-data" class="d-flex flex-column gap-1" style="min-width: 210px;">
                            @csrf
                            <input type="file" name="bukti_faktur" accept="application/pdf,image/jpeg,image/png,image/webp" class="form-control form-control-sm" required>
                            <button class="btn btn-sm btn-outline-primary" type="submit">Unggah faktur</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted">Belum ada pembelian beras.</td></tr>
        @endforelse
    </tbody></table></div>
</div></section>

<section class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h5">Katalog & ketersediaan</h2>
    <p class="small text-muted">Jenis beras dapat dihapus hanya sebelum digunakan. Lot lama tetap dihitung sebagai riwayat meskipun sisa stoknya nol.</p>
    <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Beras</th><th>Harga penawaran/kg</th><th>Stok gudang</th><th>Reservasi aktif</th><th>Tersedia</th><th>Hapus</th></tr></thead><tbody>
        @forelse ($katalog as $item)
            <tr><td>{{ $item->nama_beras }}</td><td>Rp{{ number_format($item->harga_per_kg, 2, ',', '.') }}</td><td>{{ number_format($item->stock, 2, ',', '.') }} kg</td><td>{{ number_format($item->reservasi_kg, 2, ',', '.') }} kg</td><td>{{ number_format(max(0, $item->stock - $item->reservasi_kg), 2, ',', '.') }} kg</td><td>
                @php($katalogBolehDihapus = bccomp((string) $item->stock, '0', 2) === 0 && $item->lot_count == 0 && $item->transaksi_count == 0 && $item->riwayat_harga_count == 0)
                <form method="POST" action="{{ route('panitia.katalog.destroy', $item->id) }}" onsubmit="return confirm('Hapus jenis beras ini secara permanen? Jenis beras hanya dapat dihapus jika belum digunakan.');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit" @disabled(! $katalogBolehDihapus)>Hapus jenis beras</button>
                </form>
                @unless ($katalogBolehDihapus)<small class="text-muted">Memiliki stok atau riwayat terkait.</small>@endunless
            </td></tr>
        @empty
            <tr><td colspan="6" class="text-muted">Belum ada jenis beras.</td></tr>
        @endforelse
    </tbody></table></div>
</div></section>

<section class="card border-0 shadow-sm"><div class="card-body">
    <h2 class="h5">Lot terbaru</h2>
    <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Lot</th><th>Vendor</th><th>Beras</th><th>Masuk awal</th><th>Sisa fisik</th><th>Harga beli/kg</th><th>Koreksi</th></tr></thead><tbody>
        @forelse ($lot as $item)
            <tr><td>{{ $item->kode_lot }}</td><td>{{ $item->nama_vendor }}</td><td>{{ $item->nama_beras }}</td><td>{{ $item->jumlah_masuk_kg }} kg</td><td>{{ $item->sisa_kg }} kg</td><td>Rp{{ number_format($item->harga_beli_per_kg, 2, ',', '.') }}</td><td>
                <form method="POST" action="{{ route('panitia.lot.adjust', $item->id) }}" class="d-flex flex-column gap-1" style="min-width: 220px">
                    @csrf
                    @method('PATCH')
                    <input type="number" step="0.01" name="perubahan_kg" class="form-control form-control-sm" placeholder="+ / − kg" required>
                    <input name="alasan" class="form-control form-control-sm" maxlength="2000" placeholder="Alasan wajib" required>
                    <button class="btn btn-sm btn-outline-warning" type="submit">Simpan koreksi</button>
                </form>
            </td></tr>
        @empty
            <tr><td colspan="7" class="text-muted">Belum ada lot yang diterima.</td></tr>
        @endforelse
    </tbody></table></div>
</div></section>

<section class="card border-0 shadow-sm mt-4"><div class="card-body">
    <h2 class="h5">Riwayat koreksi stok</h2>
    <p class="small text-muted">Koreksi mengubah stok fisik dan tidak boleh mengurangi jumlah yang sudah dipesan.</p>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Waktu</th><th>Lot</th><th>Perubahan</th><th>Sisa setelah</th><th>Alasan</th><th>Panitia</th></tr></thead><tbody>
        @forelse ($koreksi as $item)
            <tr><td>{{ $item->created_at }}</td><td>{{ $item->kode_lot }}</td><td>{{ $item->perubahan_kg }} kg</td><td>{{ $item->saldo_setelah_kg }} kg</td><td>{{ $item->alasan }}</td><td>{{ $item->nama_panitia }}</td></tr>
        @empty
            <tr><td colspan="6" class="text-muted">Belum ada koreksi stok.</td></tr>
        @endforelse
    </tbody></table></div>
</div></section>
@endsection
