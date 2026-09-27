@extends('muzakki.layout')

@section('content')
<div class="row justify-content-center"><div class="col-lg-6">
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
        <h1 class="h4">Konfirmasi pembayaran simulasi</h1>
        <p>Pesanan <strong>{{ $pesanan->kode_transaksi }}</strong> untuk {{ $pesanan->nama_panti }}.</p>
        <p>Total: <strong>Rp{{ number_format($pesanan->total_nominal, 2, ',', '.') }}</strong></p>
        <p>Status saat ini: <strong>{{ $pesanan->status_pembayaran }}</strong></p>
        <div class="alert alert-warning">Tombol ini hanya mencatat alur web. Tidak ada uang yang ditransfer.</div>
        @if ($pesanan->status_pembayaran === 'pending')
            <form method="POST" action="{{ route('muzakki.payment.pay', $token) }}">@csrf<button class="btn btn-success w-100" type="submit">Bayar simulasi</button></form>
        @else
            <a href="{{ route('muzakki.payment.show', $pesanan->id) }}" class="btn btn-outline-primary">Lihat status pesanan</a>
        @endif
    </div></div>
</div></div>
@endsection
