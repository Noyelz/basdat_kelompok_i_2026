@extends('panitia.layout')

@section('content')
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Data Muzakki</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse($muzakki as $m)
                        <li class="list-group-item">
                            <strong>{{ $m->name }}</strong> (NIK: {{ $m->nik ?? '-' }})<br>
                            <small class="text-muted">{{ $m->email }} | {{ $m->kota_nama }}, {{ $m->provinsi_nama }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Belum ada data Muzakki.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Data Yayasan</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse($yayasan as $y)
                        <li class="list-group-item">
                            <strong>{{ $y->name }}</strong><br>
                            <small class="text-muted">{{ $y->email }} | {{ $y->kota_nama }}, {{ $y->provinsi_nama }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Belum ada data Yayasan.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection