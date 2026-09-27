<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MuzakkiController;
use App\Http\Controllers\PanitiaController;
use App\Http\Controllers\PantiVerificationController;
use App\Http\Controllers\YayasanController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.post');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', function () {
        if (Auth::user()->must_change_password) {
            return redirect()->route('password.change');
        }

        $role = Auth::user()->role;
        if ($role === 'pemberi' || $role === 'muzakki') {
            return redirect()->route('muzakki.dashboard');
        } elseif ($role === 'panitia') {
            return redirect()->route('panitia.dashboard');
        } elseif ($role === 'pj' || $role === 'yayasan') {
            return redirect()->route('yayasan.dashboard');
        } elseif ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        abort(403);
    })->name('home');

    Route::get('/ganti-kata-sandi', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/ganti-kata-sandi', [AuthController::class, 'changePassword'])->name('password.change.store');
    Route::get('/panti/foto/{id}', [PantiVerificationController::class, 'foto'])->whereNumber('id')->name('panti.foto');

    // Group Route Muzakki
    Route::prefix('muzakki')->name('muzakki.')->middleware('role:pemberi')->group(function () {
        Route::get('/dashboard', [MuzakkiController::class, 'index'])->name('dashboard');
        Route::get('/zakat-fitrah', [MuzakkiController::class, 'showFitrah'])->name('fitrah');
        Route::get('/zakat-maal', [MuzakkiController::class, 'showMaal'])->name('maal');
        Route::post('/store', [MuzakkiController::class, 'store'])->name('store');
        Route::get('/riwayat', [MuzakkiController::class, 'riwayat'])->name('riwayat');
        Route::get('/profil', [MuzakkiController::class, 'profil'])->name('profil');
        Route::post('/profil', [MuzakkiController::class, 'updateProfil'])->name('profil.update');
    });

    // Group Route Panitia
    Route::prefix('panitia')->name('panitia.')->middleware('role:panitia')->group(function () {
        Route::get('/dashboard', [PanitiaController::class, 'index'])->name('dashboard');
        Route::get('/panti', [PantiVerificationController::class, 'index'])->name('panti.index');
        Route::patch('/panti/{id}', [PantiVerificationController::class, 'update'])->whereNumber('id')->name('panti.update');
    });

    // Group Route Yayasan
    Route::prefix('yayasan')->name('yayasan.')->middleware('role:pj')->group(function () {
        Route::get('/dashboard', [YayasanController::class, 'dashboard'])->name('dashboard');
        Route::post('/daftar-panti', [YayasanController::class, 'storePanti'])->name('panti.store');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::post('/panitia', [AdminController::class, 'storePanitia'])->name('panitia.store');
        Route::patch('/panitia/{user}/aktif', [AdminController::class, 'setPanitiaActive'])->name('panitia.active');
        Route::post('/bank', [AdminController::class, 'storeBank'])->name('bank.store');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
