@extends('muzakki.layout')

@section('content')
<h1 class="h3 mb-3">Riwayat zakat saya</h1>
<p class="text-muted">Status pembayaran dan penyaluran ditampilkan terpisah. Buka rincian pesanan untuk melihat perkembangannya.</p>
<div class="card border-0 shadow-sm"><div class="card-body">
    <div class="table-responsive"><table class="table table-hover align-middle">
        <thead><tr><th>Tanggal</th><th>Pesanan</th><th>Tujuan</th><th>Rincian</th><th>Total</th><th>Pembayaran</th><th>Penyaluran</th><th></th></tr></thead>
        <tbody>
        @forelse ($transaksi as $item)
            <tr>
                <td>{{ $item->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
                <td>{{ $item->kode_transaksi }}<br><span class="text-muted">{{ $item->jenis_zakat === 'fitrah' ? 'Fitrah' : 'Maal' }}</span></td>
                <td>{{ $item->yayasan?->nama ?? 'Panti tidak tersedia' }}</td>
                <td>
                    @if ($item->detailFitrah)
                        {{ $item->detailFitrah->jumlah_jiwa }} jiwa · {{ $item->detailFitrah->total_beras_kg }} kg {{ $item->detailFitrah->nama_beras_snapshot }}
                    @elseif ($item->detailMal)
                        Nisab Rp{{ number_format($item->detailMal->nilai_nisab, 0, ',', '.') }}
                    @endif
                </td>
                <td>Rp{{ number_format($item->total_nominal, 2, ',', '.') }}</td>
                <td>{{ $item->status_pembayaran === 'simulated_paid' ? 'Pembayaran tercatat' : str_replace('_', ' ', $item->status_pembayaran) }}</td>
                <td>{{ $item->status_penyaluran }}</td>
                <td><a href="{{ route('muzakki.payment.show', $item->id) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted">Belum ada pesanan.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $transaksi->links('pagination::bootstrap-5') }}
</div></div>
@endsection
