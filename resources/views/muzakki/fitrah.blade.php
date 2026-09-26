@extends('muzakki.layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Form Pembayaran Zakat Fitrah</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('muzakki.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="jenis_zakat" value="fitrah">

                    <h6>1. Data Pembayar</h6>
                    <div class="mb-3">
                        <label class="form-label">Nama Pembayar</label>
                        <input type="text" name="nama_pembayar" class="form-control" value="{{ Auth::user()->name }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Umur (Tahun)</label>
                            <input type="number" name="umur" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Pekerjaan</label>
                            <input type="text" name="pekerjaan" class="form-control" required>
                        </div>
                    </div>

                    <hr>
                    <h6>2. Perhitungan Jiwa</h6>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Tanggungan / Anggota Keluarga (Jiwa)</label>
                        <input type="number" id="jumlah_keluarga" name="jumlah_keluarga" class="form-control" min="1" value="1" required>
                        <small class="text-muted">Tarif: Rp{{ number_format($hargaPerJiwa, 0, ',', '.') }} / jiwa</small>
                    </div>

                    <div class="alert alert-info">
                        <strong>Total Tagihan:</strong> Rp<span id="text_total">{{ number_format($hargaPerJiwa, 0, ',', '.') }}</span>
                        <input type="hidden" name="total_bayar" id="total_bayar" value="{{ $hargaPerJiwa }}">
                    </div>

                    <hr>
                    <h6>3. Pembayaran & Upload Bukti</h6>
                    <div class="text-center my-3">
                        <p class="mb-1 fw-bold">Scan QRIS Berikut untuk Pembayaran:</p>
                        <img src="https://via.placeholder.com/200x200?text=QRIS+ZAKAT" class="img-thumbnail" width="200">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload Bukti Transfer (Format JPG/JPEG)</label>
                        <input type="file" name="bukti_pembayaran" class="form-control" accept="image/jpeg,image/jpg" required>
                    </div>

                    <button type="submit" class="btn btn-success w-100">Kirim Pembayaran Zakat Fitrah</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $('#jumlah_keluarga').on('input', function() {
        let jiwa = $(this).val();
        let harga = {{ $hargaPerJiwa }};
        let total = jiwa * harga;
        $('#total_bayar').val(total);
        $('#text_total').text(new Intl.NumberFormat('id-ID').format(total));
    });
</script>
@endpush