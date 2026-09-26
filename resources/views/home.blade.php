<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Homepage - Sistem Zakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">Sistem Zakat</a>
        <form action="{{ route('logout') }}" method="POST" class="d-flex">
            @csrf
            <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
        </form>
    </div>
</nav>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3>Selamat Datang, {{ Auth::user()->name }}!</h3>
            <p class="text-muted">Role Anda: <strong>{{ ucfirst(Auth::user()->role) }}</strong></p>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h5>Detail Akun:</h5>
                    <ul>
                        <li>Email: {{ Auth::user()->email }}</li>
                        @if(Auth::user()->nik)
                            <li>NIK: {{ Auth::user()->nik }}</li>
                        @endif
                        <li>Wilayah: {{ Auth::user()->kota_nama }}, {{ Auth::user()->provinsi_nama }}</li>
                        <li>Alamat: {{ Auth::user()->alamat ?? '-' }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>