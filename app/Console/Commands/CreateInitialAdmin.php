<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

#[Signature('app:create-initial-admin')]
#[Description('Buat akun admin pertama secara interaktif tanpa menyimpan kata sandi di repository')]
class CreateInitialAdmin extends Command
{
    public function handle(): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->error('Akun admin sudah ada. Perintah ini hanya untuk admin pertama.');

            return self::FAILURE;
        }

        $name = $this->ask('Nama lengkap admin');
        $email = $this->ask('Email admin');
        $password = $this->secret('Kata sandi admin (minimal 12 karakter, huruf dan angka)');
        $confirmation = $this->secret('Ulangi kata sandi admin');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:191', Rule::unique('user', 'email')],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'nama' => $name,
            'email' => $email,
            'pass_hash' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->info('Admin pertama dibuat. Kata sandi tidak disimpan di berkas proyek.');

        return self::SUCCESS;
    }
}
