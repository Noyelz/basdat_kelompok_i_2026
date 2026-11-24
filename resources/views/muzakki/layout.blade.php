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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="currentColor">
                    <path d="M9 25 L13 19 L17 25 V51 H9 Z"/>
                    <path d="M10 19 L13 15 L16 19 Z"/>
                    <path d="M47 25 L51 19 L55 25 V51 H47 Z"/>
                    <path d="M48 19 L51 15 L54 19 Z"/>
                    <path d="M16 31 L32 22 L48 31 V51 H16 Z"/>
                    <path d="M22 30 A10 10 0 0 1 42 30 Z"/>
                    <rect x="31" y="17" width="2" height="5" fill="#FFC933"/>
                    <circle cx="32" cy="16" r="2" fill="#FFC933"/>
                    <path d="M26 51 V39 A6 6 0 0 1 38 39 V51 Z" fill="#fffdf5"/>
                    <rect x="5" y="51" width="54" height="3" rx="1.5" fill="#FFC933"/>
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
