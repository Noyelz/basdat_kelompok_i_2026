@extends('muzakki.layout')

@section('content')
<div class="p-4 bg-white rounded shadow-sm mb-4">
    <h2>Selamat Datang, {{ Auth::user()->name }}! 👋</h2>
    <p class="text-muted">Pilih jenis zakat yang ingin Anda tunaikan hari ini.</p>
</div>

<div class="row">
    <!-- Card Zakat Fitrah -->
    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title text-success fw-bold">Zakat Fitrah</h4>
                <p class="card-text">
                    Pesan beras dari stok gudang untuk panti terverifikasi. Berat per jiwa dan rincian biaya mengikuti pengaturan yang berlaku saat checkout.
                </p>
                <a href="{{ route('muzakki.fitrah') }}" class="btn btn-success w-100 mt-3">Mulai Zakat Fitrah</a>
            </div>
        </div>
    </div>

    <!-- Card Zakat Maal -->
    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title text-success fw-bold">Zakat Maal</h4>
                <p class="card-text">
                    Zakat harta yang dikeluarkan dari hasil simpanan/aset (Emas, Investasi, Perdagangan, Piutang dikurangi Utang) yang telah mencapai nisab dengan besaran 2,5%.
                </p>
                <a href="{{ route('muzakki.maal') }}" class="btn btn-outline-success w-100 mt-3">Lihat status pengembangan maal</a>
            </div>
        </div>
    </div>
</div>
@endsection
