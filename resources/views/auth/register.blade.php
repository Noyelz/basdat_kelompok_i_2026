<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Pengelolaan Zakat</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #059669;
            --primary-hover: #047857;
            --primary-light: #ecfdf5;
            --dark: #0f172a;
            --slate-600: #475569;
            --slate-400: #94a3b8;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --white: #ffffff;
            --radius-lg: 16px;
            --radius-md: 10px;
            --shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #064e3b 0%, #0f172a 100%);
            padding: 32px 24px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.18) 0%, rgba(0, 0, 0, 0) 70%);
            top: -100px;
            right: -100px;
            border-radius: 50%;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
            bottom: -100px;
            left: -100px;
            border-radius: 50%;
            pointer-events: none;
        }

        .auth-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 620px;
            padding: 40px 36px;
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .brand-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 14px;
            margin-bottom: 14px;
        }

        .brand-badge svg {
            width: 28px;
            height: 28px;
        }

        .brand-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.025em;
            margin-bottom: 6px;
        }

        .brand-subtitle {
            color: var(--slate-600);
            font-size: 0.9rem;
        }

        .alert-error {
            background-color: var(--danger-bg);
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            margin-bottom: 20px;
        }

        .alert-error ul {
            padding-left: 20px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        @media (max-width: 540px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        .form-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 7px;
        }

        .required-badge {
            color: var(--danger);
            font-weight: 700;
        }

        .optional-badge {
            color: var(--slate-400);
            font-size: 0.775rem;
            font-weight: 400;
        }

        .conditional-badge {
            font-size: 0.775rem;
            color: #d97706;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 11px 16px;
            font-size: 0.925rem;
            color: var(--dark);
            background-color: var(--slate-100);
            border: 1.5px solid transparent;
            border-radius: var(--radius-md);
            transition: var(--transition);
            outline: none;
            box-sizing: border-box;
        }

        .form-textarea {
            resize: vertical;
            min-height: 70px;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            background-color: var(--white);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.12);
        }

        .form-input::placeholder {
            color: var(--slate-400);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--slate-400);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .toggle-password:hover {
            color: var(--dark);
        }

        .toggle-password svg {
            width: 19px;
            height: 19px;
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--primary) 0%, #047857 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.975rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
            margin-top: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.35);
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
        }

        .auth-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.875rem;
            color: var(--slate-600);
        }

        .auth-link {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
        }

        .auth-link:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="brand-header">
            <div class="brand-badge">
                <!-- Icon Registrasi -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <h1 class="brand-title">Daftar Akun Baru</h1>
            <p class="brand-subtitle">Bergabung dengan Sistem Pengelolaan Zakat</p>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.post', [], false) }}" method="POST">
            @csrf
            
            <!-- Nama Lengkap & Email -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <span>Nama Lengkap / Yayasan</span>
                        <span class="required-badge">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" required placeholder="Nama lengkap">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span>Alamat Email</span>
                        <span class="required-badge">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" required placeholder="nama@email.com">
                </div>
            </div>

            <!-- Peran / Role -->
            <div class="form-group">
                <label class="form-label">
                    <span>Daftar Sebagai</span>
                    <span class="required-badge">*</span>
                </label>
                <select name="role" id="role-select" class="form-select" required>
                    <option value="muzakki" {{ old('role') === 'muzakki' ? 'selected' : '' }}>Muzakki (Pemberi Zakat)</option>
                    <option value="yayasan" {{ old('role') === 'yayasan' ? 'selected' : '' }}>Yayasan (Penerima & Penyalur Zakat)</option>
                </select>
            </div>

            <!-- NIK & Nomor Telepon -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <span>NIK (KTP 16 Digit)</span>
                        <span class="required-badge">*</span>
                    </label>
                    <input type="text" name="nik" value="{{ old('nik') }}" maxlength="16" pattern="[0-9]{16}" class="form-input" required placeholder="16 digit angka NIK">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span>No. Telepon / WhatsApp</span>
                        <span class="required-badge">*</span>
                    </label>
                    <input type="text" name="telp" value="{{ old('telp') }}" class="form-input" required placeholder="Contoh: 08123456789">
                </div>
            </div>

            <!-- Tanggal Lahir & Jenis Kelamin -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <span>Tanggal Lahir</span>
                        <span class="required-badge">*</span>
                    </label>
                    <input type="date" name="tggl_lahir" value="{{ old('tggl_lahir') }}" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span>Jenis Kelamin</span>
                        <span class="required-badge">*</span>
                    </label>
                    <select name="gender" class="form-select" required>
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L" {{ old('gender') === 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                        <option value="P" {{ old('gender') === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>
                </div>
            </div>

            <!-- Provinsi & Kota/Kabupaten (Chained Dropdown) -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <span>Provinsi</span>
                        <span class="required-badge">*</span>
                    </label>
                    <select name="provinsi" id="provinsi-select" class="form-select" required>
                        <option value="">-- Pilih Provinsi --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span>Kota / Kabupaten</span>
                        <span class="required-badge">*</span>
                    </label>
                    <select name="kota_kabupaten" id="kota-select" class="form-select" required disabled>
                        <option value="">-- Pilih Provinsi Dahulu --</option>
                    </select>
                </div>
            </div>

            <!-- Alamat Lengkap -->
            <div class="form-group">
                <label class="form-label">
                    <span>Alamat Lengkap</span>
                    <span id="alamat-badge" class="conditional-badge">(Wajib untuk Yayasan)</span>
                </label>
                <textarea name="alamat_lengkap" id="alamat_lengkap" rows="2" class="form-textarea" placeholder="Alamat jalan, nomor, RT/RW, kelurahan...">{{ old('alamat_lengkap') }}</textarea>
            </div>

            <!-- NPWP -->
            <div class="form-group">
                <label class="form-label">
                    <span>NPWP</span>
                    <span id="npwp-badge" class="conditional-badge">(Wajib untuk Yayasan)</span>
                </label>
                <input type="text" name="npwp" id="npwp" value="{{ old('npwp') }}" maxlength="25" class="form-input" placeholder="Contoh: 01.234.567.8-901.000">
            </div>

            <!-- Password & Konfirmasi Password -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <span>Kata Sandi</span>
                        <span class="required-badge">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="reg_password" name="password" class="form-input" style="padding-right: 44px;" required placeholder="Minimal 8 karakter">
                        <button type="button" class="toggle-password" onclick="togglePassword('reg_password', 'eye-icon-1')" title="Lihat password">
                            <svg id="eye-icon-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span>Konfirmasi Sandi</span>
                        <span class="required-badge">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="reg_password_confirmation" name="password_confirmation" class="form-input" style="padding-right: 44px;" required placeholder="Ulangi kata sandi">
                        <button type="button" class="toggle-password" onclick="togglePassword('reg_password_confirmation', 'eye-icon-2')" title="Lihat password">
                            <svg id="eye-icon-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Daftar Sekarang
            </button>
        </form>

        <div class="auth-footer">
            Sudah punya akun? <a href="{{ route('login', [], false) }}" class="auth-link">Masuk di sini</a>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === "password") {
                input.type = "text";
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.563-3.029m5.858-5.908a10.047 10.047 0 013.121-.263c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-2.112 3.826M3 3l18 18" />`;
            } else {
                input.type = "password";
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-4.477 0-8.268-2.943-9.542-7z" />`;
            }
        }

        // Penyesuaian Kebutuhan Field Berdasarkan Role
        const roleSelect = document.getElementById('role-select');
        const alamatBadge = document.getElementById('alamat-badge');
        const npwpBadge = document.getElementById('npwp-badge');
        const alamatInput = document.getElementById('alamat_lengkap');
        const npwpInput = document.getElementById('npwp');

        function updateRoleRequirements() {
            if (roleSelect.value === 'yayasan') {
                alamatBadge.innerHTML = '<span class="required-badge">* Wajib untuk Yayasan</span>';
                npwpBadge.innerHTML = '<span class="required-badge">* Wajib untuk Yayasan</span>';
                alamatInput.setAttribute('required', 'required');
                npwpInput.setAttribute('required', 'required');
            } else {
                alamatBadge.innerHTML = '<span class="optional-badge">(Opsional untuk Muzakki)</span>';
                npwpBadge.innerHTML = '<span class="optional-badge">(Opsional untuk Muzakki)</span>';
                alamatInput.removeAttribute('required');
                npwpInput.removeAttribute('required');
            }
        }

        roleSelect.addEventListener('change', updateRoleRequirements);
        updateRoleRequirements();

        // Data Provinsi & Kota/Kabupaten Seluruh Indonesia
        const dataWilayah = {
            "Aceh": ["Banda Aceh", "Langsa", "Lhokseumawe", "Sabang", "Subulussalam", "Kab. Aceh Barat", "Kab. Aceh Barat Daya", "Kab. Aceh Besar", "Kab. Aceh Jaya", "Kab. Aceh Selatan", "Kab. Aceh Singkil", "Kab. Aceh Tamiang", "Kab. Aceh Tengah", "Kab. Aceh Tenggara", "Kab. Aceh Timur", "Kab. Aceh Utara", "Kab. Bener Meriah", "Kab. Bireuen", "Kab. Gayo Lues", "Kab. Nagan Raya", "Kab. Pidie", "Kab. Pidie Jaya", "Kab. Simeulue"],
            "Sumatera Utara": ["Kota Medan", "Kota Binjai", "Kota Gunungsitoli", "Kota Padangsidimpuan", "Kota Pematangsiantar", "Kota Sibolga", "Kota Tanjungbalai", "Kota Tebing Tinggi", "Kab. Asahan", "Kab. Batubara", "Kab. Dairi", "Kab. Deli Serdang", "Kab. Humbang Hasundutan", "Kab. Karo", "Kab. Labuhanbatu", "Kab. Labuhanbatu Selatan", "Kab. Labuhanbatu Utara", "Kab. Langkat", "Kab. Mandailing Natal", "Kab. Nias", "Kab. Nias Barat", "Kab. Nias Selatan", "Kab. Nias Utara", "Kab. Padang Lawas", "Kab. Padang Lawas Utara", "Kab. Pakpak Bharat", "Kab. Samosir", "Kab. Serdang Bedagai", "Kab. Simalungun", "Kab. Tapanuli Selatan", "Kab. Tapanuli Tengah", "Kab. Tapanuli Utara", "Kab. Toba"],
            "Sumatera Barat": ["Kota Padang", "Kota Bukittinggi", "Kota Padang Panjang", "Kota Pariaman", "Kota Payakumbuh", "Kota Sawahlunto", "Kota Solok", "Kab. Agam", "Kab. Dharmasraya", "Kab. Kepulauan Mentawai", "Kab. Lima Puluh Kota", "Kab. Padang Pariaman", "Kab. Pasaman", "Kab. Pasaman Barat", "Kab. Pesisir Selatan", "Kab. Sijunjung", "Kab. Solok", "Kab. Solok Selatan", "Kab. Tanah Datar"],
            "Riau": ["Kota Pekanbaru", "Kota Dumai", "Kab. Bengkalis", "Kab. Indragiri Hilir", "Kab. Indragiri Hulu", "Kab. Kampar", "Kab. Kepulauan Meranti", "Kab. Kuantan Singingi", "Kab. Pelalawan", "Kab. Rokan Hilir", "Kab. Rokan Hulu", "Kab. Siak"],
            "Kepulauan Riau": ["Kota Batam", "Kota Tanjungpinang", "Kab. Bintan", "Kab. Karimun", "Kab. Kepulauan Anambas", "Kab. Lingga", "Kab. Natuna"],
            "Jambi": ["Kota Jambi", "Kota Sungai Penuh", "Kab. Batanghari", "Kab. Bungo", "Kab. Kerinci", "Kab. Merangin", "Kab. Muaro Jambi", "Kab. Sarolangun", "Kab. Tanjung Jabung Barat", "Kab. Tanjung Jabung Timur", "Kab. Tebo"],
            "Sumatera Selatan": ["Kota Palembang", "Kota Lubuklinggau", "Kota Pagar Alam", "Kota Prabumulih", "Kab. Banyuasin", "Kab. Empat Lawang", "Kab. Lahat", "Kab. Muara Enim", "Kab. Musi Banyuasin", "Kab. Musi Rawas", "Kab. Musi Rawas Utara", "Kab. Ogan Ilir", "Kab. Ogan Komering Ilir", "Kab. Ogan Komering Ulu", "Kab. Ogan Komering Ulu Selatan", "Kab. Ogan Komering Ulu Timur", "Kab. Penukal Abab Lematang Ilir"],
            "Kepulauan Bangka Belitung": ["Kota Pangkalpinang", "Kab. Bangka", "Kab. Bangka Barat", "Kab. Bangka Selatan", "Kab. Bangka Tengah", "Kab. Belitung", "Kab. Belitung Timur"],
            "Bengkulu": ["Kota Bengkulu", "Kab. Bengkulu Selatan", "Kab. Bengkulu Tengah", "Kab. Bengkulu Utara", "Kab. Kaur", "Kab. Kepahiang", "Kab. Lebong", "Kab. Mukomuko", "Kab. Rejang Lebong", "Kab. Seluma"],
            "Lampung": ["Kota Bandar Lampung", "Kota Metro", "Kab. Lampung Barat", "Kab. Lampung Selatan", "Kab. Lampung Tengah", "Kab. Lampung Timur", "Kab. Lampung Utara", "Kab. Mesuji", "Kab. Pesawaran", "Kab. Pesisir Barat", "Kab. Pringsewu", "Kab. Tanggamus", "Kab. Tulang Bawang", "Kab. Tulang Bawang Barat", "Kab. Way Kanan"],
            "DKI Jakarta": ["Kota Jakarta Pusat", "Kota Jakarta Barat", "Kota Jakarta Selatan", "Kota Jakarta Timur", "Kota Jakarta Utara", "Kab. Kepulauan Seribu"],
            "Jawa Barat": ["Kota Bandung", "Kota Bekasi", "Kota Bogor", "Kota Cimahi", "Kota Cirebon", "Kota Depok", "Kota Sukabumi", "Kota Tasikmalaya", "Kota Banjar", "Kab. Bandung", "Kab. Bandung Barat", "Kab. Bekasi", "Kab. Bogor", "Kab. Ciamis", "Kab. Cianjur", "Kab. Cirebon", "Kab. Garut", "Kab. Indramayu", "Kab. Karawang", "Kab. Kuningan", "Kab. Majalengka", "Kab. Pangandaran", "Kab. Purwakarta", "Kab. Subang", "Kab. Sukabumi", "Kab. Sumedang", "Kab. Tasikmalaya"],
            "Banten": ["Kota Serang", "Kota Cilegon", "Kota Tangerang", "Kota Tangerang Selatan", "Kab. Lebak", "Kab. Pandeglang", "Kab. Serang", "Kab. Tangerang"],
            "Jawa Tengah": ["Kota Semarang", "Kota Magelang", "Kota Pekalongan", "Kota Salatiga", "Kota Surakarta", "Kota Tegal", "Kab. Banjarnegara", "Kab. Banyumas", "Kab. Batang", "Kab. Blora", "Kab. Boyolali", "Kab. Brebes", "Kab. Cilacap", "Kab. Demak", "Kab. Grobogan", "Kab. Jepara", "Kab. Karanganyar", "Kab. Kebumen", "Kab. Kendal", "Kab. Klaten", "Kab. Kudus", "Kab. Magelang", "Kab. Pati", "Kab. Pekalongan", "Kab. Pemalang", "Kab. Purbalingga", "Kab. Purworejo", "Kab. Rembang", "Kab. Semarang", "Kab. Sragen", "Kab. Sukoharjo", "Kab. Tegal", "Kab. Temanggung", "Kab. Wonogiri", "Kab. Wonosobo"],
            "DI Yogyakarta": ["Kota Yogyakarta", "Kab. Bantul", "Kab. Gunungkidul", "Kab. Kulon Progo", "Kab. Sleman"],
            "Jawa Timur": ["Kota Surabaya", "Kota Batu", "Kota Blitar", "Kota Kediri", "Kota Madiun", "Kota Malang", "Kota Mojokerto", "Kota Pasuruan", "Kota Probolinggo", "Kab. Bangkalan", "Kab. Banyuwangi", "Kab. Bojonegoro", "Kab. Bondowoso", "Kab. Gresik", "Kab. Jember", "Kab. Jombang", "Kab. Kediri", "Kab. Lamongan", "Kab. Lumajang", "Kab. Madiun", "Kab. Magetan", "Kab. Malang", "Kab. Mojokerto", "Kab. Nganjuk", "Kab. Ngawi", "Kab. Pacitan", "Kab. Pamekasan", "Kab. Pasuruan", "Kab. Ponorogo", "Kab. Probolinggo", "Kab. Sampang", "Kab. Sidoarjo", "Kab. Situbondo", "Kab. Sumenep", "Kab. Trenggalek", "Kab. Tuban", "Kab. Tulungagung"],
            "Bali": ["Kota Denpasar", "Kab. Badung", "Kab. Bangli", "Kab. Buleleng", "Kab. Gianyar", "Kab. Jembrana", "Kab. Karangasem", "Kab. Klungkung", "Kab. Tabanan"],
            "Nusa Tenggara Barat": ["Kota Mataram", "Kota Bima", "Kab. Bima", "Kab. Dompu", "Kab. Lombok Barat", "Kab. Lombok Tengah", "Kab. Lombok Timur", "Kab. Lombok Utara", "Kab. Sumbawa", "Kab. Sumbawa Barat"],
            "Nusa Tenggara Timur": ["Kota Kupang", "Kab. Alor", "Kab. Belu", "Kab. Ende", "Kab. Flores Timur", "Kab. Kupang", "Kab. Lembata", "Kab. Malaka", "Kab. Manggarai", "Kab. Manggarai Barat", "Kab. Manggarai Timur", "Kab. Nagekeo", "Kab. Ngada", "Kab. Rote Ndao", "Kab. Sabu Raijua", "Kab. Sikka", "Kab. Sumba Barat", "Kab. Sumba Barat Daya", "Kab. Sumba Tengah", "Kab. Sumba Timur", "Kab. Timor Tengah Selatan", "Kab. Timor Tengah Utara"],
            "Kalimantan Barat": ["Kota Pontianak", "Kota Singkawang", "Kab. Bengkayang", "Kab. Kapuas Hulu", "Kab. Kayong Utara", "Kab. Ketapang", "Kab. Kubu Raya", "Kab. Landak", "Kab. Melawi", "Kab. Mempawah", "Kab. Sambas", "Kab. Sanggau", "Kab. Sekadau", "Kab. Sintang"],
            "Kalimantan Tengah": ["Kota Palangka Raya", "Kab. Barito Selatan", "Kab. Barito Timur", "Kab. Barito Utara", "Kab. Gunung Mas", "Kab. Kapuas", "Kab. Katingan", "Kab. Kotawaringin Barat", "Kab. Kotawaringin Timur", "Kab. Lamandau", "Kab. Murung Raya", "Kab. Pulang Pisau", "Kab. Seruyan", "Kab. Sukamara"],
            "Kalimantan Selatan": ["Kota Banjarmasin", "Kota Banjarbaru", "Kab. Balangan", "Kab. Banjar", "Kab. Barito Kuala", "Kab. Hulu Sungai Selatan", "Kab. Hulu Sungai Tengah", "Kab. Hulu Sungai Utara", "Kab. Kotabaru", "Kab. Tabalong", "Kab. Tanah Bumbu", "Kab. Tanah Laut", "Kab. Tapin"],
            "Kalimantan Timur": ["Kota Samarinda", "Kota Balikpapan", "Kota Bontang", "Kab. Berau", "Kab. Kutai Barat", "Kab. Kutai Kartanegara", "Kab. Kutai Timur", "Kab. Mahakam Ulu", "Kab. Paser", "Kab. Penajam Paser Utara"],
            "Kalimantan Utara": ["Kota Tarakan", "Kab. Bulungan", "Kab. Malinau", "Kab. Nunukan", "Kab. Tana Tidung"],
            "Sulawesi Utara": ["Kota Manado", "Kota Bitung", "Kota Kotamobagu", "Kota Tomohon", "Kab. Bolaang Mongondow", "Kab. Bolaang Mongondow Selatan", "Kab. Bolaang Mongondow Timur", "Kab. Bolaang Mongondow Utara", "Kab. Kepulauan Sangihe", "Kab. Kepulauan Siau Tagulandang Biaro", "Kab. Kepulauan Talaud", "Kab. Minahasa", "Kab. Minahasa Selatan", "Kab. Minahasa Tenggara", "Kab. Minahasa Utara"],
            "Gorontalo": ["Kota Gorontalo", "Kab. Boalemo", "Kab. Bone Bolango", "Kab. Gorontalo", "Kab. Gorontalo Utara", "Kab. Pohuwato"],
            "Sulawesi Tengah": ["Kota Palu", "Kab. Banggai", "Kab. Banggai Kepulauan", "Kab. Banggai Laut", "Kab. Buol", "Kab. Donggala", "Kab. Morowali", "Kab. Morowali Utara", "Kab. Parigi Moutong", "Kab. Poso", "Kab. Sigi", "Kab. Tojo Una-Una", "Kab. Toli-Toli"],
            "Sulawesi Barat": ["Kab. Majene", "Kab. Mamasa", "Kab. Mamuju", "Kab. Mamuju Tengah", "Kab. Pasangkayu", "Kab. Polewali Mandar"],
            "Sulawesi Selatan": ["Kota Makassar", "Kota Palopo", "Kota Parepare", "Kab. Bantaeng", "Kab. Barru", "Kab. Bone", "Kab. Bulukumba", "Kab. Enrekang", "Kab. Gowa", "Kab. Jeneponto", "Kab. Kepulauan Selayar", "Kab. Luwu", "Kab. Luwu Timur", "Kab. Luwu Utara", "Kab. Maros", "Kab. Pangkajene dan Kepulauan", "Kab. Pinrang", "Kab. Sidenreng Rappang", "Kab. Sinjai", "Kab. Soppeng", "Kab. Takalar", "Kab. Tana Toraja", "Kab. Toraja Utara", "Kab. Wajo"],
            "Sulawesi Tenggara": ["Kota Kendari", "Kota Baubau", "Kab. Bombana", "Kab. Buton", "Kab. Buton Selatan", "Kab. Buton Tengah", "Kab. Buton Utara", "Kab. Kolaka", "Kab. Kolaka Timur", "Kab. Kolaka Utara", "Kab. Konawe", "Kab. Konawe Kepulauan", "Kab. Konawe Selatan", "Kab. Konawe Utara", "Kab. Muna", "Kab. Muna Barat", "Kab. Wakatobi"],
            "Maluku": ["Kota Ambon", "Kota Tual", "Kab. Buru", "Kab. Buru Selatan", "Kab. Kepulauan Aru", "Kab. Kepulauan Tanimbar", "Kab. Maluku Barat Daya", "Kab. Maluku Tengah", "Kab. Maluku Tenggara", "Kab. Seram Bagian Barat", "Kab. Seram Bagian Timur"],
            "Maluku Utara": ["Kota Ternate", "Kota Tidore Kepulauan", "Kab. Halmahera Barat", "Kab. Halmahera Selatan", "Kab. Halmahera Tengah", "Kab. Halmahera Timur", "Kab. Halmahera Utara", "Kab. Kepulauan Sula", "Kab. Pulau Morotai", "Kab. Pulau Taliabu"],
            "Papua": ["Kota Jayapura", "Kab. Biak Numfor", "Kab. Jayapura", "Kab. Keerom", "Kab. Kepulauan Yapen", "Kab. Mamberamo Raya", "Kab. Sarmi", "Kab. Supiori", "Kab. Waropen"],
            "Papua Barat": ["Kab. Manokwari", "Kab. Fakfak", "Kab. Kaimana", "Kab. Manokwari Selatan", "Kab. Pegunungan Arfak", "Kab. Teluk Bintuni", "Kab. Teluk Wondama"],
            "Papua Selatan": ["Kab. Merauke", "Kab. Asmat", "Kab. Boven Digoel", "Kab. Mappi"],
            "Papua Tengah": ["Kab. Nabire", "Kab. Deiyai", "Kab. Dogiyai", "Kab. Intan Jaya", "Kab. Mimika", "Kab. Paniai", "Kab. Puncak", "Kab. Puncak Jaya"],
            "Papua Pegunungan": ["Kab. Jayawijaya", "Kab. Lanny Jaya", "Kab. Mamberamo Tengah", "Kab. Nduga", "Kab. Pegunungan Bintang", "Kab. Tolikara", "Kab. Yahukimo", "Kab. Yalimo"],
            "Papua Barat Daya": ["Kota Sorong", "Kab. Maybrat", "Kab. Raja Ampat", "Kab. Sorong", "Kab. Sorong Selatan", "Kab. Tambrauw"]
        };

        // Chained Dropdown Provinsi & Kota/Kabupaten
        const provinsiSelect = document.getElementById('provinsi-select');
        const kotaSelect = document.getElementById('kota-select');
        const selectedProvinsiOld = "{{ old('provinsi') }}";
        const selectedKotaOld = "{{ old('kota_kabupaten') }}";

        // Isi Opsi Provinsi secara alfabetis
        Object.keys(dataWilayah).sort().forEach(prov => {
            const opt = document.createElement('option');
            opt.value = prov;
            opt.textContent = prov;
            if (prov === selectedProvinsiOld) {
                opt.selected = true;
            }
            provinsiSelect.appendChild(opt);
        });

        function updateKotaList(provinsi, preselectedKota = '') {
            kotaSelect.innerHTML = '<option value="">-- Pilih Kota / Kabupaten --</option>';
            if (provinsi && dataWilayah[provinsi]) {
                kotaSelect.disabled = false;
                dataWilayah[provinsi].sort().forEach(kota => {
                    const opt = document.createElement('option');
                    opt.value = kota;
                    opt.textContent = kota;
                    if (kota === preselectedKota) {
                        opt.selected = true;
                    }
                    kotaSelect.appendChild(opt);
                });
            } else {
                kotaSelect.disabled = true;
                kotaSelect.innerHTML = '<option value="">-- Pilih Provinsi Dahulu --</option>';
            }
        }

        provinsiSelect.addEventListener('change', function() {
            updateKotaList(this.value);
        });

        // Inisialisasi awal saat reload halaman (jika ada nilai old)
        if (selectedProvinsiOld) {
            updateKotaList(selectedProvinsiOld, selectedKotaOld);
        }
    </script>
</body>
</html>