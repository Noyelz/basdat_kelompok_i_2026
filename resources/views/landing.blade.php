<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Pelajari alur zakat fitrah dan maal di Portal Zakat. Pembayaran saat ini hanya simulasi internal.">
    <title>Beranda · Portal Zakat</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <header class="app-navbar">
        <div class="navbar-container">
            <a class="brand-wrapper" href="{{ route('landing') }}">
                <span class="brand-icon" aria-hidden="true">PZ</span>
                <span><strong class="brand-text">Portal Zakat</strong> <span class="brand-badge">Simulasi</span></span>
            </a>
            <nav class="landing-nav" aria-label="Navigasi utama">
                <a href="#cara-kerja">Cara kerja</a>
                <a href="#mitra">Mitra panti</a>
                <a href="#kalkulator">Kalkulator</a>
                @auth
                    <a class="btn-app btn-app-secondary" href="{{ route('home') }}">Dashboard</a>
                @else
                    <a class="btn-app btn-app-secondary" href="{{ route('login') }}">Masuk</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="app-container landing-main">
        <section class="landing-hero" aria-labelledby="landing-title">
            <div>
                <p class="landing-eyebrow">Portal Zakat · pengembangan internal</p>
                <h1 id="landing-title">{{ $konten['landing_judul'] }}</h1>
                <p class="landing-lead">{{ $konten['landing_pengantar'] }}</p>
                <div class="landing-actions">
                    @auth
                        <a class="btn-app btn-app-primary" href="{{ route('home') }}">Buka dashboard</a>
                    @else
                        <a class="btn-app btn-app-primary" href="{{ route('register') }}">Buat akun</a>
                        <a class="btn-app btn-app-outline" href="{{ route('login') }}">Sudah punya akun</a>
                    @endauth
                </div>
            </div>
            <div class="landing-hero-card">
                <span class="landing-mark" aria-hidden="true">✦</span>
                <h2>Transparansi sejak awal</h2>
                <p>Harga pokok beras, biaya admin, keuntungan, dan ongkos kirim ditampilkan terpisah sebelum checkout.</p>
                <p class="landing-small">Pembayaran dan angka dampak pada situs ini merupakan simulasi internal.</p>
            </div>
        </section>

        <section class="landing-section" aria-labelledby="dampak-title">
            <div class="landing-heading">
                <div><p class="landing-eyebrow">Data pengembangan</p><h2 id="dampak-title">Dampak yang tercatat</h2></div>
                <p>Akumulasi seluruh periode pada database simulasi. “Selesai” berarti panitia dan panti sudah mengunggah bukti yang cocok.</p>
            </div>
            <div class="landing-grid landing-grid-three">
                <div class="landing-stat"><strong>{{ number_format($jumlahPanti, 0, ',', '.') }}</strong><span>Panti terverifikasi aktif</span></div>
                <div class="landing-stat"><strong>{{ number_format((float) ($selesai->fitrah_kg ?? 0), 2, ',', '.') }} kg</strong><span>Beras fitrah selesai disalurkan dalam simulasi</span></div>
                <div class="landing-stat"><strong>Rp{{ number_format((float) ($selesai->maal_rupiah ?? 0), 0, ',', '.') }}</strong><span>Maal selesai disalurkan dalam simulasi</span></div>
            </div>
        </section>

        <section id="cara-kerja" class="landing-section" aria-labelledby="cara-kerja-title">
            <div class="landing-heading"><div><p class="landing-eyebrow">Alur layanan</p><h2 id="cara-kerja-title">Cara kerja</h2></div></div>
            <div class="landing-grid landing-grid-three">
                <article class="landing-card"><span class="landing-step">01</span><h3>Hitung & pilih</h3><p>Hitung kebutuhan fitrah atau kewajiban maal per pemilik harta, lalu pilih panti yang terverifikasi.</p></article>
                <article class="landing-card"><span class="landing-step">02</span><h3>Tinjau rincian</h3><p>Periksa jumlah beras atau maal dan rincian biaya. QR hanya membuka pembayaran uji, tanpa uang sungguhan.</p></article>
                <article class="landing-card"><span class="landing-step">03</span><h3>Ikuti penyaluran</h3><p>Panitia mengalokasikan pesanan secara terpisah. Status selesai dicatat setelah bukti panitia dan panti sesuai.</p></article>
            </div>
        </section>

        <section id="mitra" class="landing-section" aria-labelledby="mitra-title">
            <div class="landing-heading"><div><p class="landing-eyebrow">Mitra terverifikasi</p><h2 id="mitra-title">Panti yang aktif</h2></div><p>Hanya informasi umum yang ditampilkan. Data anak dan rekening tetap privat.</p></div>
            <div class="landing-grid landing-grid-three">
                @forelse ($panti as $item)
                    <article class="landing-card">
                        <span class="landing-verified">Terverifikasi</span>
                        <h3>{{ $item->nama }}</h3>
                        <p>{{ $item->kota_kabupaten }}, {{ $item->provinsi }}</p>
                        <p>{{ number_format($item->jumlah_mustahik_layak, 0, ',', '.') }} penerima layak tercatat</p>
                        @if ($item->kebutuhan_saat_ini)
                            <p class="landing-small">Kebutuhan: {{ $item->kebutuhan_saat_ini }}</p>
                        @endif
                    </article>
                @empty
                    <div class="landing-card"><h3>Belum ada mitra aktif</h3><p>Panti akan tampil setelah diverifikasi panitia.</p></div>
                @endforelse
            </div>
            @if ($jumlahPanti > $panti->count())
                <p class="landing-small">Menampilkan {{ $panti->count() }} dari {{ $jumlahPanti }} panti aktif. Daftar lengkap tersedia saat memilih tujuan zakat.</p>
            @endif
        </section>

        <section id="kalkulator" class="landing-section landing-calculator" aria-labelledby="kalkulator-title">
            <div><p class="landing-eyebrow">Perkiraan cepat</p><h2 id="kalkulator-title">Kalkulator fitrah</h2><p>Berat contoh mengikuti pengaturan {{ number_format((float) $bobotPerJiwa, 2, ',', '.') }} kg per jiwa. Harga dan ketersediaan beras dihitung ulang saat checkout.</p></div>
            <div class="landing-calc-box">
                <label for="jumlah-jiwa">Jumlah jiwa</label>
                <input id="jumlah-jiwa" class="app-form-control" type="number" min="1" max="100" value="1" inputmode="numeric">
                <p>Perkiraan beras: <strong id="hasil-fitrah">{{ number_format((float) $bobotPerJiwa, 2, ',', '.') }} kg</strong></p>
                @if (auth()->user()?->role === 'pemberi')
                    <a class="btn-app btn-app-primary" href="{{ route('muzakki.fitrah') }}">Lanjut ke fitrah</a>
                @elseif (auth()->check())
                    <a class="btn-app btn-app-primary" href="{{ route('home') }}">Buka dashboard</a>
                @else
                    <a class="btn-app btn-app-primary" href="{{ route('register') }}">Daftar untuk melanjutkan</a>
                @endif
            </div>
        </section>

        <section class="landing-section landing-info" aria-labelledby="tentang-title">
            <div><p class="landing-eyebrow">Tentang platform</p><h2 id="tentang-title">Yang perlu diketahui</h2><p>{{ $konten['landing_tentang'] }}</p></div>
            <div><p class="landing-eyebrow">Pertanyaan umum</p><h2>{{ $konten['landing_faq_judul'] }}</h2><p>{{ $konten['landing_faq_jawaban'] }}</p></div>
        </section>
    </main>
    <footer class="landing-footer">Portal Zakat · simulasi internal pengembangan</footer>
    <script>
        const jumlahJiwa = document.getElementById('jumlah-jiwa');
        const hasilFitrah = document.getElementById('hasil-fitrah');
        const bobotPerJiwa = @json((float) $bobotPerJiwa);
        jumlahJiwa.addEventListener('input', () => {
            const jumlah = Math.min(100, Math.max(0, Number(jumlahJiwa.value) || 0));
            hasilFitrah.textContent = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(jumlah * bobotPerJiwa) + ' kg';
        });
    </script>
</body>
</html>
