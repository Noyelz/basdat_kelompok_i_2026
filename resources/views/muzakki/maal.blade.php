@extends('muzakki.layout')

@section('content')
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h1 class="h3">Zakat maal</h1>
    <p>Form transaksi maal sedang disesuaikan dengan perhitungan per pemilik harta, harga emas acuan bertanggal, dan nisab. Checkout maal dibuka setelah kalkulasi server siap.</p>
    <a href="{{ route('muzakki.dashboard') }}" class="btn btn-outline-primary">Kembali ke dashboard</a>
</div></div>
@endsection
