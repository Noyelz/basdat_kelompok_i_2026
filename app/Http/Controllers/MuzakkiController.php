<?php

namespace App\Http\Controllers;

use App\Models\TransaksiZakat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MuzakkiController extends Controller
{
    public function index(): View
    {
        return view('muzakki.dashboard');
    }

    public function showMaal(): View
    {
        return view('muzakki.maal');
    }

    public function riwayat(Request $request): View
    {
        TransaksiZakat::expirePending();
        $transaksi = TransaksiZakat::query()
            ->with(['yayasan', 'detailFitrah', 'detailMal'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('muzakki.riwayat', compact('transaksi'));
    }

    public function profil(Request $request): View
    {
        return view('muzakki.profil', ['user' => $request->user()]);
    }

    public function updateProfil(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'digits:16', Rule::unique('user', 'nik')->ignore($request->user()->id)],
            'telp' => ['required', 'string', 'max:20'],
            'tggl_lahir' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'provinsi' => ['required', 'string', 'max:100'],
            'kota_kabupaten' => ['required', 'string', 'max:100'],
            'alamat_lengkap' => ['nullable', 'string', 'max:2000'],
            'npwp' => ['nullable', 'string', 'max:25'],
        ]);

        $request->user()->update($data);

        return redirect()->route('muzakki.profil')->with('success', 'Profil Anda diperbarui.');
    }
}
