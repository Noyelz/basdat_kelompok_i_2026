<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:191', Rule::unique('user', 'email')],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'role' => ['required', Rule::in(['muzakki', 'pj'])],
            'nik' => ['required', 'digits:16', Rule::unique('user', 'nik')],
            'telp' => ['required', 'string', 'max:20'],
            'tggl_lahir' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'kota_kabupaten' => ['required', 'string', 'max:100'],
            'provinsi' => ['required', 'string', 'max:100'],
            'alamat_lengkap' => ['required_if:role,pj', 'nullable', 'string'],
            'npwp' => ['nullable', 'string', 'max:25'],
        ]);

        $user = User::create([
            'nama' => $data['name'],
            'email' => $data['email'],
            'pass_hash' => Hash::make($data['password']),
            'role' => $data['role'] === 'pj' ? 'pj' : 'pemberi',
            'nik' => $data['nik'],
            'telp' => $data['telp'],
            'tggl_lahir' => $data['tggl_lahir'],
            'gender' => $data['gender'],
            'kota_kabupaten' => $data['kota_kabupaten'],
            'provinsi' => $data['provinsi'],
            'alamat_lengkap' => $data['alamat_lengkap'] ?? null,
            'npwp' => $data['npwp'] ?? null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true])) {
            return back()->withInput($request->only('email'))->with('error', 'Email atau kata sandi salah, atau akun tidak aktif.');
        }

        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        $request->user()->forceFill([
            'pass_hash' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Kata sandi berhasil diubah.');
    }
}
