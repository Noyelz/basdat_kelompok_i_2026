<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Muzakki · Portal Zakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<header class="app-navbar">
    <div class="navbar-container">
        <a class="brand-wrapper" href="{{ route('muzakki.dashboard') }}">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <span class="brand-text">Portal Zakat</span>
                <span class="brand-badge ms-2">Muzakki</span>
            </div>
        </a>

        <ul class="app-nav-links">
            <li>
                <a class="nav-link {{ request()->routeIs('muzakki.dashboard') ? 'active' : '' }}" href="{{ route('muzakki.dashboard') }}">Dashboard</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('muzakki.riwayat') ? 'active' : '' }}" href="{{ route('muzakki.riwayat') }}">Riwayat Zakat</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('muzakki.profil') ? 'active' : '' }}" href="{{ route('muzakki.profil') }}">Profil Saya</a>
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
    @yield('content')
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
