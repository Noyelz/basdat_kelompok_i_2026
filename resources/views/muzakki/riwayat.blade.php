@extends('muzakki.layout')

@section('content')
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">Riwayat Pembayaran Zakat Saya</h5>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis Zakat</th>
                    <th>Total Bayar</th>
                    <th>Bukti Pembayaran</th>
                    <th>Status Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksi as $item)
                    <tr>
                        <td>{{ $item->created_at->format('d M Y H:i') }}</td>
                        <td><span class="badge bg-info text-dark">Zakat {{ ucfirst($item->jenis_zakat) }}</span></td>
                        <td>Rp{{ number_format($item->total_bayar, 0, ',', '.') }}</td>
                        <td>
                            <a href="{{ asset($item->bukti_pembayaran) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat Bukti</a>
                        </td>
                        <td>
                            @if($item->status == 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($item->status == 'success')
                                <span class="badge bg-success">Success (Terverifikasi)</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Belum ada riwayat transaksi zakat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Informasi Penyaluran ke Yayasan -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-info text-dark">
        <h5 class="mb-0">Daftar Yayasan Penerima Zakat</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Berikut adalah daftar Yayasan yang telah menerima penyaluran zakat (Uang/Beras) dari Panitia:</p>
        <ul class="list-group">
            @php
                $penyaluran = \App\Models\Penyaluran::with(['yayasan', 'mustahik'])->latest()->get();
            @endphp
            @forelse($penyaluran as $p)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $p->yayasan->nama ?? 'Yayasan Mitra' }}</strong> ({{ $p->yayasan->kota_kabupaten ?? '-' }})<br>
                        <small class="text-muted">Penerima: {{ $p->mustahik->nama ?? 'Mustahik Binaan' }} (Bantuan: {{ $p->barang ?? 'Zakat' }})</small>
                    </div>
                    <span class="badge bg-success rounded-pill">
                        @if($p->jenis_zakat == 'mal')
                            Rp{{ number_format($p->jumlah, 0, ',', '.') }}
                        @else
                            {{ number_format($p->jumlah, 1, ',', '.') }} {{ $p->satuan ?? 'Kg' }}
                        @endif
                    </span>
                </li>
            @empty
                <li class="list-group-item text-muted">Belum ada penyaluran ke yayasan.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection