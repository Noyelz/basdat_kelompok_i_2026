<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\FitrahCheckoutController;
use App\Http\Controllers\HandoverEvidenceController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaalCheckoutController;
use App\Http\Controllers\MuzakkiController;
use App\Http\Controllers\PanitiaController;
use App\Http\Controllers\PantiVerificationController;
use App\Http\Controllers\SimulationPaymentController;
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
    Route::get('/bukti/foto/{id}', [HandoverEvidenceController::class, 'foto'])->whereNumber('id')->name('bukti.foto');

    // Group Route Muzakki
    Route::prefix('muzakki')->name('muzakki.')->middleware('role:pemberi')->group(function () {
        Route::get('/dashboard', [MuzakkiController::class, 'index'])->name('dashboard');
        Route::get('/zakat-fitrah', [FitrahCheckoutController::class, 'index'])->name('fitrah');
        Route::post('/zakat-fitrah', [FitrahCheckoutController::class, 'store'])->name('fitrah.store');
        Route::get('/zakat-maal', [MaalCheckoutController::class, 'index'])->name('maal');
        Route::post('/zakat-maal', [MaalCheckoutController::class, 'store'])->name('maal.store');
        Route::get('/pembayaran/{id}', [SimulationPaymentController::class, 'show'])->whereNumber('id')->name('payment.show');
        Route::post('/pembayaran/{id}/batalkan', [SimulationPaymentController::class, 'cancel'])->whereNumber('id')->name('payment.cancel');
        Route::get('/bayar-uji/{token}', [SimulationPaymentController::class, 'scan'])->name('payment.scan');
        Route::post('/bayar-uji/{token}', [SimulationPaymentController::class, 'pay'])->middleware('throttle:5,1')->name('payment.pay');
        Route::get('/riwayat', [MuzakkiController::class, 'riwayat'])->name('riwayat');
        Route::get('/profil', [MuzakkiController::class, 'profil'])->name('profil');
        Route::post('/profil', [MuzakkiController::class, 'updateProfil'])->name('profil.update');
    });

    // Group Route Panitia
    Route::prefix('panitia')->name('panitia.')->middleware('role:panitia')->group(function () {
        Route::get('/dashboard', [PanitiaController::class, 'index'])->name('dashboard');
        Route::get('/panti', [PantiVerificationController::class, 'index'])->name('panti.index');
        Route::patch('/panti/{id}', [PantiVerificationController::class, 'update'])->whereNumber('id')->name('panti.update');
        Route::get('/gudang', [InventoryController::class, 'index'])->name('gudang.index');
        Route::post('/vendor', [InventoryController::class, 'storeVendor'])->name('vendor.store');
        Route::post('/katalog', [InventoryController::class, 'storeKatalog'])->name('katalog.store');
        Route::post('/pembelian/terima', [InventoryController::class, 'receive'])->name('pembelian.receive');
        Route::get('/distribusi', [DistributionController::class, 'index'])->name('distribusi.index');
        Route::post('/perjalanan', [DistributionController::class, 'createTrip'])->name('perjalanan.store');
        Route::post('/perjalanan/{id}/berangkat', [DistributionController::class, 'dispatch'])->whereNumber('id')->name('perjalanan.dispatch');
        Route::post('/titik', [DistributionController::class, 'addStop'])->name('titik.store');
        Route::post('/maal/{id}/alokasi', [DistributionController::class, 'allocateMaal'])->whereNumber('id')->name('maal.allocate');
        Route::get('/bukti', [HandoverEvidenceController::class, 'index'])->name('bukti.index');
        Route::post('/bukti/{id}', [HandoverEvidenceController::class, 'submitPanitia'])->whereNumber('id')->name('bukti.store');
    });

    // Group Route Yayasan
    Route::prefix('yayasan')->name('yayasan.')->middleware('role:pj')->group(function () {
        Route::get('/dashboard', [YayasanController::class, 'dashboard'])->name('dashboard');
        Route::post('/daftar-panti', [YayasanController::class, 'storePanti'])->name('panti.store');
        Route::post('/bukti/{id}', [HandoverEvidenceController::class, 'submitPj'])->whereNumber('id')->name('bukti.store');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::post('/panitia', [AdminController::class, 'storePanitia'])->name('panitia.store');
        Route::patch('/panitia/{user}/aktif', [AdminController::class, 'setPanitiaActive'])->name('panitia.active');
        Route::post('/bank', [AdminController::class, 'storeBank'])->name('bank.store');
        Route::put('/pengaturan', [AdminController::class, 'setOperationalSettings'])->name('pengaturan.update');
        Route::put('/wilayah/{id}', [AdminController::class, 'setRegion'])->whereNumber('id')->name('wilayah.update');
        Route::post('/harga-emas', [AdminController::class, 'storeGoldPrice'])->name('harga-emas.store');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
