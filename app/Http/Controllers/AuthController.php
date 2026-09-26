<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|string|email|max:191|unique:user,email',
            'password'       => 'required|string|min:8|confirmed',
            'role'           => 'required|in:muzakki,yayasan,pemberi,pj',
            'nik'            => 'required|digits:16',
            'telp'           => 'required|string|max:20',
            'tggl_lahir'     => 'required|date',
            'gender'         => 'required|in:L,P',
            'kota_kabupaten' => 'required|string|max:100',
            'provinsi'       => 'required|string|max:100',
            'alamat_lengkap' => 'required_if:role,yayasan,pj|nullable|string',
            'npwp'           => 'required_if:role,yayasan,pj|nullable|string|max:25',
        ], [
            'nik.required'               => 'NIK (Nomor Induk Kependudukan) wajib diisi.',
            'nik.digits'                 => 'NIK harus terdiri dari 16 digit angka.',
            'telp.required'              => 'Nomor telepon wajib diisi.',
            'tggl_lahir.required'        => 'Tanggal lahir wajib diisi.',
            'gender.required'            => 'Jenis kelamin wajib dipilih.',
            'gender.in'                  => 'Pilihan jenis kelamin tidak valid.',
            'kota_kabupaten.required'    => 'Kota / Kabupaten wajib dipilih.',
            'provinsi.required'          => 'Provinsi wajib dipilih.',
            'alamat_lengkap.required_if' => 'Alamat lengkap wajib diisi untuk pendaftaran Yayasan.',
            'npwp.required_if'           => 'NPWP wajib diisi untuk pendaftaran Yayasan.',
        ]);

        // Pemetaan peran ke database enum ('pemberi', 'pj', 'panitia')
        $dbRole = 'pemberi';
        if ($request->role === 'yayasan' || $request->role === 'pj') {
            $dbRole = 'pj';
        }

        $user = User::create([
            'nama'           => $request->name,
            'email'          => $request->email,
            'pass_hash'      => Hash::make($request->password),
            'nik'            => $request->nik ?: null,
            'telp'           => $request->telp,
            'tggl_lahir'     => $request->tggl_lahir ?: null,
            'gender'         => $request->gender ?: null,
            'kota_kabupaten' => $request->kota_kabupaten,
            'provinsi'       => $request->provinsi,
            'alamat_lengkap' => $request->alamat_lengkap ?: null,
            'npwp'           => $request->npwp ?: null,
            'role'           => $dbRole,
        ]);

        if ($dbRole === 'pj') {
            \App\Models\Yayasan::firstOrCreate(
                ['pj_user_id' => $user->id],
                [
                    'nama'           => 'Yayasan ' . $user->nama,
                    'nomor_izin'     => 'YYS/' . date('Y') . '/' . $user->id,
                    'telp'           => $user->telp,
                    'email'          => $user->email,
                    'kota_kabupaten' => $user->kota_kabupaten,
                    'provinsi'       => $user->provinsi,
                    'alamat_lengkap' => $user->alamat_lengkap ?? '-',
                    'nama_bank'      => 'BSI',
                    'no_rek'         => '1234567890',
                    'atas_nama_rek'  => $user->nama,
                    'status_verif'   => 'pending',
                    'is_active'      => true,
                ]
            );
        }

        Auth::login($user);

        if ($user->role === 'pemberi' || $user->role === 'muzakki') {
            return redirect()->route('muzakki.dashboard');
        } elseif ($user->role === 'pj' || $user->role === 'yayasan') {
            return redirect()->route('yayasan.dashboard');
        }

        return redirect()->route('home');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->role === 'panitia') {
                return redirect()->route('panitia.dashboard');
            } elseif ($user->role === 'pemberi' || $user->role === 'muzakki') {
                return redirect()->route('muzakki.dashboard');
            } elseif ($user->role === 'pj' || $user->role === 'yayasan') {
                return redirect()->route('yayasan.dashboard');
            }

            return redirect()->route('home');
        }

        return back()->with('error', 'Email atau password salah.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}