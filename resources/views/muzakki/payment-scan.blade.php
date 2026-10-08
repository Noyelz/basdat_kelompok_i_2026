<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>QR Pembayaran · Portal Zakat</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <header class="app-navbar"><div class="navbar-container"><a class="brand-wrapper" href="{{ route('login') }}"><span class="brand-icon" aria-hidden="true">PZ</span><span><strong class="brand-text">Portal Zakat</strong> <span class="brand-badge">QR</span></span></a></div></header>
    <main class="app-container" style="max-width: 640px; padding-top: 2rem;">
        <section class="app-card" style="padding: 1.5rem;">
            <h1 class="app-card-title">Konfirmasi pembayaran</h1>
            <p>Halaman ini dapat dibuka dari ponsel tanpa login. Konfirmasi di halaman ini <strong>tidak melakukan transfer uang</strong>.</p>

            @if (session('success'))
                <div class="app-alert app-alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="app-alert app-alert-error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            @if ($bisaDibayar)
                <p>Pesanan: <strong>{{ $pesanan->kode_transaksi }}</strong></p>
                <p>Tujuan: {{ $pesanan->nama_panti }}</p>
                <p>Total tagihan: <strong>Rp{{ number_format($pesanan->total_nominal, 2, ',', '.') }}</strong></p>
                <p>QR berlaku sampai {{ \Carbon\Carbon::parse($pesanan->qr_expired_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB.</p>
                <p>Pemegang tautan QR dapat mencatat konfirmasi pesanan ini.</p>
                <form method="POST" action="{{ route('muzakki.payment.pay', $token) }}">
                    @csrf
                    <button class="btn-app btn-app-primary" type="submit">Catat konfirmasi pembayaran</button>
                </form>
            @elseif ($sudahDibayar)
                <p>Pesanan <strong>{{ $pesanan->kode_transaksi }}</strong> sudah berstatus <strong>pembayaran tercatat</strong>.</p>
                <p>Penyaluran masih diproses secara terpisah oleh panitia. Pemilik akun dapat melihat rinciannya dari riwayat zakat setelah login.</p>
            @else
                <p>QR ini sudah tidak berlaku atau pesanannya dibatalkan. Pemilik pesanan dapat membuka riwayatnya untuk melihat status terbaru.</p>
            @endif
        </section>
    </main>
</body>
</html>
