@extends('panitia.layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Verifikasi Pembayaran Muzakki</h5>
    </div>
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Muzakki</th>
                    <th>Jenis Zakat</th>
                    <th>Total Bayar</th>
                    <th>Bukti Transfer</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksi as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->nama_pembayar }}</strong><br>
                            <small class="text-muted">{{ $item->user->email }}</small>
                        </td>
                        <td><span class="badge bg-info text-dark">Zakat {{ ucfirst($item->jenis_zakat) }}</span></td>
                        <td>Rp{{ number_format($item->total_bayar, 0, ',', '.') }}</td>
                        <td>
                            <a href="{{ asset($item->bukti_pembayaran) }}" target="_blank" class="btn btn-sm btn-outline-primary">Buka Bukti JPG</a>
                        </td>
                        <td>
                            @if($item->status == 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($item->status == 'success')
                                <span class="badge bg-success">Success</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </td>
                        <td>
                            @if($item->status == 'pending')
                                <form action="{{ route('panitia.verifikasi', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="success">
                                    <button class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form action="{{ route('panitia.verifikasi', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="failed">
                                    <button class="btn btn-sm btn-danger">Tolak</button>
                                </form>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection