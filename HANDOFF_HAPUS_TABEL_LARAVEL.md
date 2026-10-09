# Handout admin/root database — selesai

## Status pelaksanaan — 9 Oktober 2026 (WIB)

**Selesai.** Migration `2026_10_09_071031_remove_unused_framework_tables` dijalankan sebagai pemilik aplikasi `keli` dan tercatat **Ran, batch 6**, pada pukul 14.24 WIB. Tujuh tabel kosong yang dihapus: `sessions`, `password_reset_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`, dan `failed_jobs`.

- Skema akhir `zakat_db`: **35 tabel, 393 kolom, 55 foreign key, 2 CHECK JSON**. `migrations` tetap ada (12 baris); seluruh 34 tabel domain tetap ada.
- Jumlah baris setiap tabel domain tidak berubah, total **192 baris**. Isi INSERT data bisnis pada dump sesudah migration cocok persis dengan backup sebelum perubahan.
- Backup terbaru: `/var/tmp/basdatkeli-backups/zakat_db-before-framework-cleanup-20261009-072353.sql`; izin **0600**, ukuran **94.482 byte**, memuat seluruh 42 definisi tabel sebelum cleanup dan penanda selesai. SHA-256: `f3e595d8198b9c90d92e729036c4744c5bcc58fa6bd438ce84c7d9b74cf19eb3`.
- Catatan izin awal disimpan privat di `/var/tmp/basdatkeli-backups/keli-grants-before-framework-cleanup-20261009-072353.txt`; jumlah baris sebelum perubahan di `zakat_db-row-counts-before-20261009-072353.json` pada direktori yang sama. File-file ini tidak masuk Git.
- Admin MariaDB melalui socket lokal memberi DROP hanya pada tujuh tabel sasaran. Seluruh izin sementara dicabut setelah migration; hasil hak akun `'keli'@'%'` cocok persis dengan keadaan awal.
- Konfigurasi aktif: session **file**, cache **file**, queue **sync**, failed-job storage **null**. `.env`, APP_KEY, dependensi, database lama `zakat`, dan perubahan lokal pengguna tidak diubah.
- Test cleanup/rollback dan regresi checkout: **16 test, 138 assertion lulus**, memakai SQLite `:memory:` terpisah.
- Beranda dan login melalui HTTP: **200**. Dashboard empat peran melalui kernel aplikasi, autentikasi akun yang sudah ada, dan transaksi database read-only: **200**. Pemeriksaan ini memverifikasi respons/render server, bukan interaksi visual browser.
- Cache file tulis/baca dan lock berhasil. Tiga jadwal Artisan tetap terdaftar, termasuk harga emas; sinkronisasi emas tidak dijalankan. Query dan metadata Laravel Boost tetap berfungsi.
- ERD physical/kamus aktif dan panduan sudah diperbarui. ERD konseptual tidak terpengaruh. Perubahan dokumentasi terkait disimpan sebagai commit lokal tanpa push; source migration/test/config yang disiapkan sebelumnya tetap dipertahankan.

## Arsip prompt sebelum pelaksanaan

Bagian di bawah mempertahankan keadaan awal dan prosedur yang sudah dilaksanakan sebagai riwayat. Status Pending, hitungan 42/432, dan instruksi pemberian izin di arsip bukan keadaan aktif atau pekerjaan yang perlu diulang.

Salin seluruh bagian **Prompt siap diberikan** di bawah ke akun/asisten root yang menangani database. Root Linux dan root MySQL adalah akun berbeda: menjalankan Artisan sebagai root Linux tetap memakai akun database dari konfigurasi Laravel. Yang dibutuhkan di sini adalah akses admin MySQL/MariaDB untuk memberikan izin DROP terbatas.

## Prompt siap diberikan

Tolong selesaikan penghapusan **tujuh tabel infrastruktur Laravel yang tidak dipakai** pada aplikasi berikut. Penghapusan tujuh tabel ini sudah diminta dan disetujui pemilik proyek. Kode migration sudah dibuat dan diuji; lanjutkan dari keadaan sekarang.

### 1. Lokasi, koneksi, dan status

- Repository aplikasi: `/var/www/basdatkeli`.
- Akun Linux pemilik aplikasi: `keli`; grup file aplikasi: `www-data`.
- Laravel: **13.32.0**; PHP: **8.3**.
- Koneksi aplikasi: `mysql`.
- Database sasaran: **`zakat_db`** pada **`192.168.1.50:3306`**, MariaDB **10.6.23**.
- Akun database aplikasi yang dikonfirmasi lewat `CURRENT_USER()`: **`'keli'@'%'`**.
- Skema aktif saat pemeriksaan 9 Oktober 2026: **42 tabel, 432 kolom, 55 foreign key, 2 CHECK JSON**.
- Percobaan migration berhenti pada DROP pertama: `DROP command denied ... zakat_db.sessions`.
- **Belum ada tabel terhapus.** Migration masih `Pending`; sebelas migration terdahulu sudah `Ran`. Jumlah baris seluruh 42 tabel tetap sama setelah percobaan gagal.

Jangan mengubah database lama `zakat`, database lain, kredensial `.env`, APP_KEY, dependensi, controller, atau tampilan untuk pekerjaan ini. Ada perubahan lokal milik pengguna pada controller, Blade, CSS, aset, PDF, dan test checkout; pertahankan semuanya dan jangan masukkan ke commit pekerjaan database.

### 2. Tabel yang boleh dihapus

| Tabel | Baris saat pemeriksaan | Kolom | Alasan tidak diperlukan |
| --- | ---: | ---: | --- |
| `sessions` | 0 | 6 | Session aktif memakai driver `file`, tersimpan di `storage/framework/sessions`. |
| `password_reset_tokens` | 0 | 3 | Fitur lupa/reset password dengan token belum tersedia pada route/controller aplikasi. Penggantian password setelah login adalah alur terpisah. |
| `cache` | 0 | 3 | Cache aktif memakai driver `file`. |
| `cache_locks` | 0 | 3 | Lock harga emas, scheduler, dan cache mengikuti store file. |
| `jobs` | 0 | 7 | Queue default `sync`; aplikasi tidak memakai antrean database. |
| `job_batches` | 0 | 10 | Aplikasi tidak memakai job batching. |
| `failed_jobs` | 0 | 7 | `queue.failed.driver` sudah disetel default `null`, sehingga tidak menyimpan kegagalan job ke database. |

**Pertahankan `migrations`.** Tabel ini menyimpan riwayat perubahan skema Laravel dan diperlukan oleh Artisan migration. Pertahankan **seluruh 34 tabel domain**, termasuk `user`, `bank`, `yayasan`, persediaan, transaksi, reservasi, pembayaran, pengiriman, notifikasi, dan audit.

Tujuh tabel sasaran mempunyai **39 kolom** dan tidak menerima foreign key dari tabel lain pada pemeriksaan terakhir. Hasil yang diharapkan setelah berhasil: **35 tabel, 393 kolom, 55 foreign key, 2 CHECK JSON**. Data bisnis tidak berubah; tabel `migrations` bertambah satu baris riwayat cleanup.

Tabel tersebut adalah infrastruktur bawaan Laravel, bukan tabel khusus MCP Boost. Laravel Boost memakai layanan cache aplikasi sesuai driver yang dipilih; driver file tetap bekerja tanpa tabel cache database.

### 3. Pekerjaan yang sudah disiapkan

Migration yang harus dijalankan:

`database/migrations/2026_10_09_071031_remove_unused_framework_tables.php`

- `up()` menolak konfigurasi session/cache/queue database, queue failover, dan failed-job storage berbasis database.
- `up()` memeriksa **semua tujuh tabel sebelum DROP pertama**. Jika salah satunya berisi data, proses ditolak sebelum menghapus tabel mana pun.
- `up()` menghapus hanya tujuh tabel dalam daftar sasaran dengan `Schema::dropIfExists()`.
- `down()` membuat kembali tujuh tabel kosong beserta kolom/indeksnya. Rollback skema tidak memulihkan isi data dari cadangan.
- File migration lama tetap dipertahankan. Instalasi baru menjalankan migration lama, kemudian cleanup ini; jangan menghapus atau mengedit migration yang sudah diterapkan.

Konfigurasi source dan `.env.example` sudah disesuaikan:

```dotenv
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
QUEUE_FAILED_DRIVER=null
```

Default `queue.failed.driver` di source adalah `null` jika env tidak diisi. Session/cache/queue aktif telah diperiksa dan sesuai; `.env` lokal tidak diubah. Definisi backend database/batching masih merupakan opsi konfigurasi bawaan yang tidak digunakan. Jangan mengaktifkan fitur tersebut tanpa membuat kembali tabel pendukungnya.

Test khusus: `tests/Feature/RemoveUnusedFrameworkTablesTest.php`. Test cleanup, rollback, penolakan tabel berisi data, penolakan driver database, dan regresi checkout telah dijalankan pada SQLite `:memory:` terpisah: **16 test, 138 assertion lulus**. Pint juga lulus.

### 4. Cadangan dan pemeriksaan sebelum penghapusan

Cadangan lengkap sebelumnya berada **pada host aplikasi**, di luar repository:

`/var/tmp/basdatkeli-backups/zakat_db-before-framework-cleanup-20261009-071030.sql`

- Ukuran: **94.385 byte**.
- Izin file: **0600**; direktori: **0700**.
- Dump terverifikasi memuat definisi seluruh 42 tabel dan penanda selesai.
- SHA-256: `8f682252d12b186f74957a39a13b4f211042fdf6afef59d6b585820f9f4dfbe4`.

Sebelum melakukan perubahan skema, buat cadangan terbaru database `zakat_db` di luar repository dengan izin 0600. Gunakan kredensial admin melalui mekanisme aman yang tersedia; jangan menulis password dalam prompt, argumen command, output, atau commit. Catat jumlah baris tabel domain untuk perbandingan setelah migration.

Periksa ulang bahwa database yang dipilih benar dan tujuh tabel tetap kosong:

```sql
SELECT DATABASE(), CURRENT_USER();

SELECT 'sessions' AS table_name, COUNT(*) AS row_count FROM zakat_db.sessions
UNION ALL SELECT 'password_reset_tokens', COUNT(*) FROM zakat_db.password_reset_tokens
UNION ALL SELECT 'cache', COUNT(*) FROM zakat_db.cache
UNION ALL SELECT 'cache_locks', COUNT(*) FROM zakat_db.cache_locks
UNION ALL SELECT 'jobs', COUNT(*) FROM zakat_db.jobs
UNION ALL SELECT 'job_batches', COUNT(*) FROM zakat_db.job_batches
UNION ALL SELECT 'failed_jobs', COUNT(*) FROM zakat_db.failed_jobs;

SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = 'zakat_db'
  AND REFERENCED_TABLE_NAME IN (
    'sessions', 'password_reset_tokens', 'cache', 'cache_locks',
    'jobs', 'job_batches', 'failed_jobs'
  );
```

Jika salah satu tabel berisi data atau ada FK baru yang merujuknya, hentikan penghapusan dan laporkan perubahan keadaan. Jangan menghapus data agar pemeriksaan migration lolos.

### 5. Berikan izin DROP sementara, lalu jalankan migration

Gunakan **sesi admin database** pada server MariaDB yang benar. Catat izin awal akun `'keli'@'%'` agar pencabutan berikutnya hanya membatalkan izin yang baru diberikan untuk pekerjaan ini.

Berikan izin DROP hanya pada tujuh tabel sasaran, tanpa menambah hak global atau hak DROP pada seluruh database:

```sql
GRANT DROP ON `zakat_db`.`sessions` TO 'keli'@'%';
GRANT DROP ON `zakat_db`.`password_reset_tokens` TO 'keli'@'%';
GRANT DROP ON `zakat_db`.`cache` TO 'keli'@'%';
GRANT DROP ON `zakat_db`.`cache_locks` TO 'keli'@'%';
GRANT DROP ON `zakat_db`.`jobs` TO 'keli'@'%';
GRANT DROP ON `zakat_db`.`job_batches` TO 'keli'@'%';
GRANT DROP ON `zakat_db`.`failed_jobs` TO 'keli'@'%';
```

Pada **host aplikasi**, jalankan dari `/var/www/basdatkeli` sebagai pemilik aplikasi, bukan mengganti password/koneksi permanen menjadi root. Jika sesi terminal adalah root Linux, gunakan `sudo -u keli` untuk menjalankan command di bawah.

```bash
cd /var/www/basdatkeli
php artisan config:show session.driver
php artisan config:show cache.default
php artisan config:show queue.default
php artisan config:show queue.failed.driver
php artisan migrate:status --no-interaction
php artisan migrate --path=database/migrations/2026_10_09_071031_remove_unused_framework_tables.php --pretend --force --no-interaction
php artisan migrate --path=database/migrations/2026_10_09_071031_remove_unused_framework_tables.php --force --no-interaction
```

Hasil konfigurasi yang diharapkan: `file`, `file`, `sync`, `null`. Jika konfigurasi lama masih di-cache, bersihkan cache config sebagai pemilik aplikasi dan periksa ulang sebelum melanjutkan. Jangan menjalankan `migrate:fresh`, `migrate:reset`, atau menjatuhkan seluruh database. Gunakan migration yang telah disiapkan supaya riwayat Laravel tercatat; jangan menjalankan DROP manual lalu mengisi riwayat migration secara manual.

Setelah percobaan migration selesai, cabut hanya izin DROP sementara yang baru ditambahkan; lakukan juga bila migration gagal. Jika ketujuh izin tidak ada sebelumnya dan seluruhnya baru diberikan, pencabutannya adalah:

```sql
REVOKE DROP ON `zakat_db`.`sessions` FROM 'keli'@'%';
REVOKE DROP ON `zakat_db`.`password_reset_tokens` FROM 'keli'@'%';
REVOKE DROP ON `zakat_db`.`cache` FROM 'keli'@'%';
REVOKE DROP ON `zakat_db`.`cache_locks` FROM 'keli'@'%';
REVOKE DROP ON `zakat_db`.`jobs` FROM 'keli'@'%';
REVOKE DROP ON `zakat_db`.`job_batches` FROM 'keli'@'%';
REVOKE DROP ON `zakat_db`.`failed_jobs` FROM 'keli'@'%';
```

Jangan mencabut hak lain yang sudah ada sebelum pekerjaan ini.

### 6. Verifikasi hasil dan perbarui dokumentasi

Periksa bahwa tujuh tabel tidak ada dan `migrations` tetap ada:

```sql
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'zakat_db'
  AND TABLE_NAME IN (
    'migrations', 'sessions', 'password_reset_tokens', 'cache',
    'cache_locks', 'jobs', 'job_batches', 'failed_jobs'
  );

SELECT COUNT(*) AS tables_count
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'zakat_db' AND TABLE_TYPE = 'BASE TABLE';

SELECT COUNT(*) AS columns_count
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'zakat_db';

SELECT COUNT(*) AS foreign_keys_count
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'zakat_db';

SELECT COUNT(*) AS checks_count
FROM information_schema.CHECK_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'zakat_db';

SELECT migration, batch
FROM zakat_db.migrations
WHERE migration = '2026_10_09_071031_remove_unused_framework_tables';
```

Query pertama harus mengembalikan hanya `migrations`. Hitungan berikutnya harus **35 / 393 / 55 / 2**, dan riwayat cleanup harus ada. Bandingkan jumlah baris seluruh tabel bisnis dengan catatan sebelum migration; perubahan bisnis oleh pekerjaan ini harus nol.

Pada host aplikasi, jalankan pengujian dan pastikan migration berstatus Ran:

```bash
php artisan migrate:status --no-interaction
php artisan test --compact tests/Feature/RemoveUnusedFrameworkTablesTest.php tests/Feature/FitrahCheckoutControllerTest.php
php artisan schedule:list --no-interaction
```

Periksa beranda, login, dan dashboard aplikasi. Cache/session file, scheduler harga emas, serta MCP Boost harus tetap berjalan. Jangan menjalankan sinkronisasi harga emas hanya untuk pemeriksaan ini karena akan menambah data bisnis.

**Setelah penghapusan terbukti berhasil**, perbarui snapshot menjadi 35 tabel/393 kolom dan hapus tujuh entitas/kamus aktif dari:

- `ERD_zakat_db.md` dan `ERD_zakat_db_physical.mmd`.
- `PENJELASAN_KODE_DAN_DATABASE.md`.
- `PANDUAN_LENGKAP_PORTAL_ZAKAT.md`.
- Catatan pekerjaan di `!wajib_baca.md` dan status handout ini.

Pertahankan penjelasan migration lama sebagai riwayat. ERD konseptual hanya perlu diubah bila memang ada isi yang terkena dampak. Jangan menyatakan database sudah 35 tabel jika migration gagal.

Simpan perubahan dokumentasi yang terkait sebagai commit lokal setelah memeriksa diff. Jangan push otomatis dan jangan ikutkan perubahan lokal milik pengguna. Jangan memasukkan dump, `.env`, atau kredensial ke Git.

Laporkan: hasil migration, tabel yang dihapus, tabel yang dipertahankan, hitungan skema akhir, data bisnis yang tetap utuh, hasil test, lokasi backup terbaru, pencabutan izin sementara, dan commit lokal. Jika gagal, laporkan langkah yang gagal dan keadaan tabel sebenarnya; jangan mengklaim selesai.
