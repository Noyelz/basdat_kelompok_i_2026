<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $panitia = User::query()
            ->where('role', 'panitia')
            ->orderBy('nama')
            ->get(['id', 'nama', 'email', 'is_active', 'created_at']);
        $bank = DB::table('bank')->orderBy('nama')->get(['id', 'kode', 'nama', 'is_active']);

        return view('admin.dashboard', compact('panitia', 'bank'));
    }

    public function storePanitia(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:191', Rule::unique('user', 'email')],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $panitia = User::create([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'pass_hash' => Hash::make($data['password']),
                'role' => 'panitia',
                'is_active' => true,
                'must_change_password' => true,
                'created_by_admin_id' => $request->user()->id,
            ]);

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => 'panitia.dibuat',
                'jenis_objek' => 'user',
                'objek_id' => $panitia->id,
                'detail' => json_encode(['email' => $panitia->email], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Akun panitia dibuat. Berikan kata sandi sementara melalui jalur pribadi.');
    }

    public function setPanitiaActive(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'panitia', 404);

        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        DB::transaction(function () use ($user, $request, $data): void {
            $user->update(['is_active' => (bool) $data['is_active']]);

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => $user->is_active ? 'panitia.diaktifkan' : 'panitia.dinonaktifkan',
                'jenis_objek' => 'user',
                'objek_id' => $user->id,
                'detail' => json_encode(['email' => $user->email], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Status akun panitia diperbarui.');
    }

    public function storeBank(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('bank', 'kode')],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $bankId = DB::table('bank')->insertGetId([
                'kode' => $data['kode'],
                'nama' => $data['nama'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => 'bank.ditambahkan',
                'jenis_objek' => 'bank',
                'objek_id' => $bankId,
                'detail' => json_encode(['kode' => $data['kode'], 'nama' => $data['nama']], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Bank ditambahkan.');
    }
}
