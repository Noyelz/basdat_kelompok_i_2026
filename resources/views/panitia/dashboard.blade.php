@extends('panitia.layout')

@section('content')
<h3 class="mb-4">Ringkasan Fund & Gudang Zakat</h3>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body">
                <h6>Fund Zakat Maal (Tersedia)</h6>
                <h4>Rp{{ number_format($fundMaalTersedia, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body">
                <h6>Fund Zakat Fitrah (Uang)</h6>
                <h4>Rp{{ number_format($fundFitrahTersedia, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark shadow-sm">
            <div class="card-body">
                <h6>Stok Gudang Beras</h6>
                <h4>{{ number_format($gudang->stok_beras_kg, 1, ',', '.') }} kg</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white shadow-sm">
            <div class="card-body">
                <h6>Total Pengguna</h6>
                <h4>{{ $totalMuzakki }} Muzakki / {{ $totalYayasan }} Yayasan</h4>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Konversi Fund Fitrah ke Beras Gudang</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('panitia.konversi') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-md-5">
                <label class="form-label">Nominal Uang dari Fund Fitrah (Rp)</label>
                <input type="number" name="nominal_uang" class="form-control" placeholder="Contoh: 350000" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Jumlah Beras yang Dibeli (kg)</label>
                <input type="number" step="0.1" name="jumlah_kg" class="form-control" placeholder="Contoh: 30" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-warning w-100 fw-bold">Beli & Masuk Gudang</button>
            </div>
        </form>
        <small class="text-muted mt-2 d-block">
            * Total Uang Fund Fitrah yang sudah dikucurkan untuk beli beras: <strong>Rp{{ number_format($gudang->total_uang_dikonversi, 0, ',', '.') }}</strong>
        </small>
    </div>
</div>
@endsection