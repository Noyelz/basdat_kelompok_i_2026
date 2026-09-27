<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Zakat - Dashboard Panitia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<header class="app-navbar">
    <div class="navbar-container">
        <a class="brand-wrapper" href="{{ route('panitia.dashboard') }}">
            <div class="brand-icon" style="background: rgba(245, 158, 11, 0.2); border-color: rgba(245, 158, 11, 0.4); color: #fbbf24;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <span class="brand-text">Portal Zakat</span>
                <span class="brand-badge ms-2" style="background: rgba(245, 158, 11, 0.25); color: #fef08a;">Panitia</span>
            </div>
        </a>

        <ul class="app-nav-links">
            <li>
                <a class="nav-link {{ request()->routeIs('panitia.dashboard') ? 'active' : '' }}" href="{{ route('panitia.dashboard') }}">Dashboard</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('panitia.panti.*') ? 'active' : '' }}" href="{{ route('panitia.panti.index') }}">Panti & Verifikasi</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('panitia.gudang.*') ? 'active' : '' }}" href="{{ route('panitia.gudang.index') }}">Vendor & Gudang</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('panitia.distribusi.*') ? 'active' : '' }}" href="{{ route('panitia.distribusi.index') }}">Pesanan & Distribusi</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('panitia.bukti.*') ? 'active' : '' }}" href="{{ route('panitia.bukti.index') }}">Bukti Serah Terima</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('panitia.laporan.*') ? 'active' : '' }}" href="{{ route('panitia.laporan.index') }}">Laporan</a>
            </li>
        </ul>

        <div class="app-nav-user">
            <span class="user-greeting">Halo, <strong class="user-name">{{ Auth::user()->name }}</strong></span>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn-app-logout" type="submit">Logout</button>
            </form>
        </div>
    </div>
</header>

<main class="app-container">
    @if(session('success'))
        <div class="app-alert app-alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="app-alert app-alert-error mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @yield('content')
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
