@extends('panitia.layout')

<style>
    .h3-title {
        margin-bottom: 10px;
        color: #325c1c;
        font-family: Georgia, 'Times New Roman', serif !important;
        font-size: 40px;
        font-weight: 700;
        line-height: 1.12;
        letter-spacing: -0.04em;
    }
</style>

@section('content')
<h1 class="h3-title">Permohonan rekening panti</h1>
<p class="text-muted">Rekening aktif panti baru berubah setelah permohonan disetujui. Cocokkan kepemilikan rekening dengan dokumen PJ sebelum menyetujui.</p>

@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

@forelse ($permohonan as $item)
    <section class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="d-flex justify-content-between gap-3">
            <h2 class="h5">{{ $item->nama_panti }} · {{ $item->nama_pj }}</h2>
            <span class="badge {{ $item->status === 'pending' ? 'bg-warning text-dark' : ($item->status === 'approved' ? 'bg-success' : 'bg-danger') }}">{{ $item->status }}</span>
        </div>
        <p class="mb-1">Diajukan: {{ $item->created_at }}</p>
        <p class="mb-1">Rekening aktif saat ini: {{ $item->bank_lama }} · {{ $item->rekening_lama }} · {{ $item->pemilik_lama }}</p>
        <p class="mb-2">Rekening usulan: <strong>{{ $item->bank_baru }} · {{ $item->no_rek }} · {{ $item->atas_nama_rek }}</strong></p>
        @if ($item->catatan)<p class="mb-2">Catatan: {{ $item->catatan }}</p>@endif
        @if ($item->status === 'pending')
            @unless ($item->bank_aktif)<p class="text-danger">Bank usulan sudah tidak aktif; permohonan ini tidak dapat disetujui.</p>@endunless
            <form method="POST" action="{{ route('panitia.rekening.review', $item->id) }}" class="row g-2">
                @csrf
                @method('PATCH')
                <div class="col-md-7"><label class="form-label">Catatan pemeriksaan</label><input name="catatan" class="form-control" maxlength="2000" placeholder="Wajib bila ditolak"></div>
                <div class="col-md-2 d-flex align-items-end"><button name="hasil" value="approved" class="btn btn-success w-100" @disabled(! $item->bank_aktif)>Setujui</button></div>
                <div class="col-md-3 d-flex align-items-end"><button name="hasil" value="rejected" class="btn btn-outline-danger w-100">Tolak</button></div>
            </form>
        @endif
    </div></section>
@empty
    <div class="alert alert-info">Belum ada permohonan perubahan rekening.</div>
@endforelse

{{ $permohonan->links('pagination::bootstrap-5') }}
@endsection
