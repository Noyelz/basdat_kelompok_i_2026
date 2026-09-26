@extends('muzakki.layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Kalkulator & Form Pembayaran Zakat Maal</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('muzakki.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="jenis_zakat" value="maal">

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
                    <h6>2. Kalkulator Aset Zakat Maal</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nilai Emas / Tabungan (Rp)</label>
                            <input type="number" id="nilai_emas" name="nilai_emas" class="form-control calc-input" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nilai Investasi / Surat Berharga (Rp)</label>
                            <input type="number" id="nilai_investasi" name="nilai_investasi" class="form-control calc-input" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Aset Dagang (Rp)</label>
                            <input type="number" id="aset_dagang" name="aset_dagang" class="form-control calc-input" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Piutang Lancar (Rp)</label>
                            <input type="number" id="piutang" name="piutang" class="form-control calc-input" value="0">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-danger">Utang / Kewajiban Jatuh Tempo (Rp)</label>
                            <input type="number" id="utang" name="utang" class="form-control calc-input" value="0">
                        </div>
                    </div>

                    <div class="alert alert-success">
                        <div><strong>Total Aset Bersih:</strong> Rp<span id="text_bersih">0</span></div>
                        <div><strong>Zakat yang Wajib Dibayar (2.5%):</strong> Rp<span id="text_total">0</span></div>
                        <input type="hidden" name="total_bayar" id="total_bayar" value="0">
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

                    <button type="submit" class="btn btn-success w-100">Kirim Pembayaran Zakat Maal</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $('.calc-input').on('input', function() {
        let emas = parseFloat($('#nilai_emas').val()) || 0;
        let investasi = parseFloat($('#nilai_investasi').val()) || 0;
        let dagang = parseFloat($('#aset_dagang').val()) || 0;
        let piutang = parseFloat($('#piutang').val()) || 0;
        let utang = parseFloat($('#utang').val()) || 0;

        let totalAsetBersih = (emas + investasi + dagang + piutang) - utang;
        if(totalAsetBersih < 0) totalAsetBersih = 0;

        let totalZakat = totalAsetBersih * 0.025;

        $('#text_bersih').text(new Intl.NumberFormat('id-ID').format(totalAsetBersih));
        $('#text_total').text(new Intl.NumberFormat('id-ID').format(totalZakat));
        $('#total_bayar').val(totalZakat);
    });
</script>
@endpush