<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Zakat - Portal Yayasan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<header class="app-navbar">
    <div class="navbar-container">
        <a class="brand-wrapper" href="{{ route('yayasan.dashboard') }}">
            <div class="brand-icon" style="background: rgba(59, 130, 246, 0.2); border-color: rgba(59, 130, 246, 0.4); color: #60a5fa;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <span class="brand-text">Portal Zakat</span>
                <span class="brand-badge ms-2" style="background: rgba(59, 130, 246, 0.25); color: #bfdbfe;">Yayasan</span>
            </div>
        </a>

        <ul class="app-nav-links">
            <li>
                <a class="nav-link active" href="{{ route('yayasan.dashboard') }}">Dashboard & Proposal</a>
            </li>
        </ul>

        <div class="app-nav-user">
            <span class="user-greeting">Halo, <strong class="user-name">{{ Auth::user()->name }}</strong></span>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn-app-logout" type="submit">Logout</button>
            </form>
        </div>
    </div>
</header>

<main class="app-container">
    @if(session('success'))
        <div class="app-alert app-alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="app-alert app-alert-error mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Card Form Pengajuan Proposal Baru -->
    <div class="app-card">
        <div class="app-card-header">
            <h2 class="app-card-title">Ajukan Proposal Bantuan Zakat Baru</h2>
        </div>
        <div class="app-card-body">
            <form action="{{ route('yayasan.proposal.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="app-form-label">Nama Program / Kegiatan</label>
                        <input type="text" name="judul_program" class="app-form-control" placeholder="Contoh: Santunan Anak Yatim & Dhuafa" required>
                    </div>
                    <div class="col-md-4">
                        <label class="app-form-label">Kebutuhan Dana Uang (Rp)</label>
                        <input type="number" name="kebutuhan_dana" class="app-form-control" placeholder="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="app-form-label">Kebutuhan Beras (Kg)</label>
                        <input type="number" step="0.1" name="kebutuhan_beras_kg" class="app-form-control" placeholder="0" required>
                    </div>
                </div>

                <div class="app-form-group">
                    <label class="app-form-label">Deskripsi Program & Sasaran Penerima</label>
                    <textarea name="deskripsi" rows="3" class="app-form-textarea" placeholder="Jelaskan detail peruntukan bantuan, sasaran mustahik, dan urgensi kegiatan..." required></textarea>
                </div>

                <button type="submit" class="btn-app btn-app-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Kirim Proposal ke Panitia
                </button>
            </form>
        </div>
    </div>

    <!-- Card Tabel Status Proposal -->
    <div class="app-card">
        <div class="app-card-header">
            <h2 class="app-card-title">Daftar Status Proposal Pengajuan</h2>
        </div>
        
        <div class="app-table-responsive">
            <table class="app-table">
                <thead>
                    <tr>
                        <th style="min-width: 250px;">Program / Kegiatan</th>
                        <th>Bantuan Dana (Uang)</th>
                        <th>Bantuan Beras</th>
                        <th>Status Pengajuan</th>
                        <th style="min-width: 260px;">Aksi / Konfirmasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposals as $p)
                    <tr>
                        <td>
                            <strong style="display: block; color: var(--slate-800); font-size: 0.95rem; margin-bottom: 4px;">{{ $p->judul_program }}</strong>
                            <span style="font-size: 0.775rem; color: var(--slate-500);">
                                Diajukan: {{ $p->created_at ? $p->created_at->format('d M Y H:i') : '-' }}
                            </span>
                        </td>
                        <td>
                            <strong style="color: var(--primary);">Rp{{ number_format($p->kebutuhan_dana, 0, ',', '.') }}</strong>
                        </td>
                        <td>
                            <strong>{{ number_format($p->kebutuhan_beras_kg, 1, ',', '.') }} Kg</strong>
                        </td>
                        <td>
                            @if($p->status == 'pending')
                                <span class="app-badge app-badge-warning">Menunggu Verifikasi</span>
                            @elseif($p->status == 'approved')
                                <span class="app-badge app-badge-info">Disetujui Panitia</span>
                            @elseif($p->status == 'completed')
                                <span class="app-badge app-badge-success">Selesai Disalurkan</span>
                            @else
                                <span class="app-badge app-badge-danger">Ditolak</span>
                            @endif
                        </td>
                        <td>
                            @if($p->status == 'approved')
                                <form action="{{ route('yayasan.proposal.confirm', $p->id) }}" method="POST" enctype="multipart/form-data" class="p-3 bg-light rounded border">
                                    @csrf
                                    <p class="small fw-bold text-primary mb-2">Konfirmasi Barang/Dana Diterima:</p>
                                    <input type="file" name="bukti_penerimaan" class="form-control form-control-sm mb-2" required>
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="is_jumlah_sesuai" value="1" id="sesuai_{{ $p->id }}" class="form-check-input" required>
                                        <label for="sesuai_{{ $p->id }}" class="form-check-label small">Jumlah Sesuai</label>
                                    </div>
                                    <input type="text" name="catatan_penerimaan" class="form-control form-control-sm mb-2" placeholder="Catatan opsional...">
                                    <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">Konfirmasi Selesai</button>
                                </form>
                            @elseif($p->status == 'completed')
                                <div style="color: var(--success); font-weight: 700; font-size: 0.85rem; display: flex; flex-direction: column; gap: 4px;">
                                    <span>✓ Penerimaan Terverifikasi</span>
                                    @if($p->bukti_penerimaan)
                                        <a href="{{ asset('storage/' . $p->bukti_penerimaan) }}" target="_blank" style="color: var(--primary); font-size: 0.8rem; text-decoration: underline;">Lihat Bukti Foto</a>
                                    @endif
                                </div>
                            @else
                                <span style="color: var(--slate-400); font-size: 0.85rem;">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center p-4 text-muted">
                            Belum ada riwayat pengajuan proposal bantuan dari yayasan Anda.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>