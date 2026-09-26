<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MuzakkiController;
use App\Http\Controllers\PanitiaController;
use App\Http\Controllers\YayasanController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', function () {
        $role = Auth::user()->role;
        if ($role === 'pemberi' || $role === 'muzakki') {
            return redirect()->route('muzakki.dashboard');
        } elseif ($role === 'panitia') {
            return redirect()->route('panitia.dashboard');
        } elseif ($role === 'pj' || $role === 'yayasan') {
            return redirect()->route('yayasan.dashboard');
        }
        return view('home');
    })->name('home');

    // Group Route Muzakki
    Route::prefix('muzakki')->name('muzakki.')->group(function () {
        Route::get('/dashboard', [MuzakkiController::class, 'index'])->name('dashboard');
        Route::get('/zakat-fitrah', [MuzakkiController::class, 'showFitrah'])->name('fitrah');
        Route::get('/zakat-maal', [MuzakkiController::class, 'showMaal'])->name('maal');
        Route::post('/store', [MuzakkiController::class, 'store'])->name('store');
        Route::get('/riwayat', [MuzakkiController::class, 'riwayat'])->name('riwayat');
        Route::get('/profil', [MuzakkiController::class, 'profil'])->name('profil');
        Route::post('/profil', [MuzakkiController::class, 'updateProfil'])->name('profil.update');
    });

    // Group Route Panitia
    Route::prefix('panitia')->name('panitia.')->group(function () {
        Route::get('/dashboard', [PanitiaController::class, 'index'])->name('dashboard');
        Route::get('/transaksi', [PanitiaController::class, 'transaksiMuzakki'])->name('transaksi');
        Route::post('/verifikasi/{id}', [PanitiaController::class, 'verifikasiTransaksi'])->name('verifikasi');
        Route::post('/konversi', [PanitiaController::class, 'konversiBeras'])->name('konversi');
        Route::get('/pengajuan', [PanitiaController::class, 'pengajuanYayasan'])->name('pengajuan');
        Route::post('/proses-pengajuan/{id}', [PanitiaController::class, 'prosesPengajuan'])->name('prosesPengajuan');
        Route::get('/pengguna', [PanitiaController::class, 'dataPengguna'])->name('pengguna');
    });

    // Group Route Yayasan
    Route::prefix('yayasan')->name('yayasan.')->group(function () {
        Route::get('/dashboard', [YayasanController::class, 'dashboard'])->name('dashboard');
        Route::post('/proposal/store', [YayasanController::class, 'storeProposal'])->name('proposal.store');
        Route::post('/proposal/confirm/{id}', [YayasanController::class, 'confirmReceipt'])->name('proposal.confirm');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});