<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat perubahan · Portal Zakat</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <header class="app-navbar">
        <div class="navbar-container">
            <a class="brand-wrapper" href="{{ route('admin.dashboard') }}">
                <span class="brand-icon" aria-hidden="true">PZ</span>
                <span><strong class="brand-text">Portal Zakat</strong> <span class="brand-badge">Admin</span></span>
            </a>
            <a class="btn-app btn-app-secondary" href="{{ route('admin.dashboard') }}">Kembali ke admin</a>
        </div>
    </header>
    <main class="app-container">
        <h1>Riwayat perubahan</h1>
        <p>Tindakan penting yang tercatat dalam aplikasi. Detail teknis seperti alamat IP dan data pribadi tidak ditampilkan di daftar ini.</p>
        <section class="app-card">
            <div class="app-card-body app-table-responsive">
                <table class="app-table">
                    <thead><tr><th>Waktu WIB</th><th>Tindakan</th><th>Pelaku</th><th>Objek</th></tr></thead>
                    <tbody>
                        @forelse ($riwayat as $item)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($item->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                                <td>{{ str_replace(['.', '_'], ' ', $item->aksi) }}</td>
                                <td>{{ $item->nama_pelaku ?? 'Sistem/akun dihapus' }} @if ($item->peran_pelaku) <small>({{ $item->peran_pelaku }})</small> @endif</td>
                                <td>{{ $item->jenis_objek }}{{ $item->objek_id ? ' #'.$item->objek_id : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Belum ada tindakan tercatat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if ($riwayat->hasPages())
            <nav class="app-pagination" aria-label="Halaman riwayat audit">
                @if ($riwayat->previousPageUrl())
                    <a class="btn-app btn-app-outline" href="{{ $riwayat->previousPageUrl() }}">Sebelumnya</a>
                @endif
                <span>Halaman {{ $riwayat->currentPage() }} dari {{ $riwayat->lastPage() }}</span>
                @if ($riwayat->nextPageUrl())
                    <a class="btn-app btn-app-outline" href="{{ $riwayat->nextPageUrl() }}">Berikutnya</a>
                @endif
            </nav>
        @endif
    </main>
</body>
</html>
