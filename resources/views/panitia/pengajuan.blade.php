@extends('panitia.layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Penyaluran Zakat ke Yayasan</h5>
    </div>
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Yayasan</th>
                    <th>Jenis Zakat</th>
                    <th>Permintaan</th>
                    <th>Keterangan</th>
                    <th>Status</th>
                    <th>Aksi Penyaluran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengajuan as $item)
                    <tr>
                        <td><strong>{{ $item->yayasan->name }}</strong></td>
                        <td><span class="badge bg-info text-dark">Zakat {{ ucfirst($item->jenis_zakat) }}</span></td>
                        <td>
                            @if($item->jenis_zakat == 'maal')
                                Rp{{ number_format($item->jumlah_uang_diminta, 0, ',', '.') }}
                            @else
                                {{ number_format($item->jumlah_beras_diminta_kg, 1, ',', '.') }} kg Beras
                            @endif
                        </td>
                        <td>{{ $item->keterangan ?? '-' }}</td>
                        <td>
                            @if($item->status == 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($item->status == 'disalurkan')
                                <span class="badge bg-success">Sudah Disalurkan</span>
                            @else
                                <span class="badge bg-danger">Ditolak</span>
                            @endif
                        </td>
                        <td>
                            @if($item->status == 'pending')
                                <form action="{{ route('panitia.prosesPengajuan', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="disalurkan">
                                    <button class="btn btn-sm btn-success">Salurkan / Centang Sukses</button>
                                </form>
                                <form action="{{ route('panitia.prosesPengajuan', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected">
                                    <button class="btn btn-sm btn-danger">Tolak</button>
                                </form>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada pengajuan dari yayasan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection