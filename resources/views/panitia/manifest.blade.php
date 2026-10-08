<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manifest {{ $perjalanan->kode_perjalanan }}</title>
    <style>body{font-family:Arial,sans-serif;max-width:900px;margin:2rem auto;color:#222}table{width:100%;border-collapse:collapse;margin:1rem 0 2rem}th,td{padding:.5rem;border:1px solid #aaa;text-align:left}h1{font-size:1.6rem}@media print{button{display:none}body{margin:1cm}}</style>
</head>
<body>
    <button onclick="window.print()">Cetak manifest</button>
    <h1>Manifest perjalanan {{ $perjalanan->kode_perjalanan }}</h1>
    <p>Gudang: {{ $perjalanan->nama_gudang }} · Kendaraan: {{ $perjalanan->kendaraan }} · Kapasitas: {{ $perjalanan->kapasitas_kg }} kg</p>
    <p>Jadwal: {{ \Carbon\Carbon::parse($perjalanan->jadwal_berangkat)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB · Status: {{ $perjalanan->status }}</p>
    <p><strong>Dokumen pengiriman.</strong> Tidak memuat NIK atau identitas anak.</p>

    @foreach ($titik as $stop)
        <h2>{{ $stop->urutan }}. {{ $stop->nama_panti }}</h2>
        <p>{{ $stop->kota_kabupaten }}, {{ $stop->provinsi }} · Muatan {{ $stop->jumlah_kg }} kg</p>
        <table><thead><tr><th>Kode pesanan</th><th>Jumlah beras</th></tr></thead><tbody>
            @foreach ($alokasi->get($stop->id, collect()) as $item)
                <tr><td>{{ $item->kode_transaksi }}</td><td>{{ $item->jumlah_kg }} kg</td></tr>
            @endforeach
        </tbody></table>
    @endforeach
</body>
</html>
