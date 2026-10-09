# Panduan Lengkap Portal Zakat: Alur, Database, dan Kode

**Tanggal acuan: 9 Oktober 2026 (WIB).** Proyek: `/var/www/basdatkeli`. Database yang diperiksa: **`zakat_db`**, melalui koneksi Laravel `mysql`, server MariaDB 10.6.23. Framework terpasang: Laravel 13.32.0; PHP 8.3. Panduan ini menjelaskan kode dan metadata database yang ada saat penulisan.

**Tujuan:** membantu Anda memahami hubungan antara tombol di website, fungsi PHP, tabel database, dan hasil yang ditampilkan. Contoh nama, ID, harga, dan pesanan di bawah adalah **contoh belajar**, bukan salinan data pribadi dari database.

**Batas panduan:** inventaris mencakup file aplikasi milik proyek, konfigurasi, migration, view, aset, serta penjelasan kelompok file alat pengembangan. Isi ribuan file `vendor`, editor, dan dependensi tidak dijelaskan satu per satu karena merupakan kode pihak lain. Penjelasan fungsi di sini bukan komentar yang telah dimasukkan ke kode. Pembuatan awal panduan hanya berupa dokumentasi. Pembaruan implementasi berikutnya dijelaskan pada catatan API emas, kapasitas PJ, dan Bagian 17 tentang CRUD.

> **Keadaan implementasi:** pembayaran dan persediaan yang dicatat alur baru adalah simulasi pengembangan. QR bukan QRIS bank. Pada tugas ini, bukti panitia dan bukti PJ yang sesuai dianggap cukup untuk menandai penyaluran ke penerima akhir selesai. Panduan tidak menyatakan sistem siap menerima uang sungguhan.
>
> Beberapa file tampilan sedang memiliki perubahan lokal saat panduan dibuat. Penjelasan memakai isi yang terbaca di workspace; keberadaan kode bukan bukti bahwa seluruh skenario sudah diuji.

> **Cleanup skema — 9 Oktober 2026:** tujuh tabel infrastruktur dihapus pada batch 6, lalu dua tabel profil pemberi lama dihapus pada batch 7. Skema aktif kini **33 tabel, 373 kolom, 53 FK, dan 2 CHECK JSON**. `mustahik`, seluruh 32 tabel domain yang tersisa, dan isi data bisnis tetap utuh. Izin DROP sementara sudah dicabut. Lihat [catatan cleanup profil](%21wajib_baca.md#18-penghapusan-dua-tabel-profil-pemberi-lama-9-oktober-2026).

## Daftar isi

1. [Cara membaca dan istilah dasar](#1-cara-membaca-dan-istilah-dasar)
2. [Gambaran sistem dan pembagian peran](#2-gambaran-sistem-dan-pembagian-peran)
3. [Alur lengkap dari persiapan sampai selesai](#3-alur-lengkap-dari-persiapan-sampai-selesai)
4. [Status, stok, dan perhitungan](#4-status-stok-dan-perhitungan)
5. [Bagaimana Laravel memproses tombol](#5-bagaimana-laravel-memproses-tombol)
6. [Cara memahami struktur database](#6-cara-memahami-struktur-database)
7. [Kamus seluruh tabel dan kolom](#7-kamus-seluruh-tabel-dan-kolom)
8. [Peta route aktif](#8-peta-route-aktif)
9. [Controller: fungsi setiap file dan method](#9-controller-fungsi-setiap-file-dan-method)
10. [Model, middleware, provider, dan command](#10-model-middleware-provider-dan-command)
11. [Migration, seeder, dan factory](#11-migration-seeder-dan-factory)
12. [View dan tampilan per file](#12-view-dan-tampilan-per-file)
13. [Konfigurasi, entry point, dan file pendukung](#13-konfigurasi-entry-point-dan-file-pendukung)
14. [Latihan membaca kode dan menulis komentar](#14-latihan-membaca-kode-dan-menulis-komentar)
15. [Batas implementasi dan hal yang perlu diperhatikan](#15-batas-implementasi-dan-hal-yang-perlu-diperhatikan)
16. [Urutan belajar dan catatan dokumentasi](#16-urutan-belajar-dan-catatan-dokumentasi)
17. [CRUD dan penghapusan terbatas](#17-crud-dan-penghapusan-terbatas)

## 1. Cara membaca dan istilah dasar

Jangan mulai dengan menghafal 33 tabel. Mulai dari satu pesanan fitrah, lalu lihat tabel yang terlibat. Setelah itu pelajari maal, persediaan, dan pengiriman. Tabel pendukung dipelajari terakhir.

| Istilah | Penjelasan sederhana | Contoh di proyek |
| --- | --- | --- |
| Frontend | Bagian yang tampil dan berjalan di browser | Form fitrah, kartu beras, tombol |
| Backend | Bagian di server yang memeriksa dan menyimpan data | PHP dalam controller |
| Database | Penyimpanan data dalam tabel | Akun, pesanan, lot beras |
| Tabel | Kumpulan data dengan struktur sama | `user` berisi akun |
| Baris / record | Satu data dalam tabel | Satu akun muzakki |
| Kolom / field | Satu jenis informasi dalam sebuah baris | `email`, `role` |
| Route | Pemetaan alamat dan jenis permintaan ke fungsi | POST pesanan fitrah menuju `store()` |
| Controller | Pengatur satu pekerjaan web | Validasi, hitung harga, simpan pesanan |
| Model | Class PHP yang mewakili tabel dan relasinya | `User`, `TransaksiZakat` |
| View / Blade | Template HTML yang menerima data PHP | `muzakki/riwayat.blade.php` |
| Middleware | Pemeriksaan sebelum fungsi tujuan berjalan | Login dan role panitia |
| Migration | Riwayat kode pembentukan/perubahan tabel | Membuat `reservasi_stok` |
| Seeder | Kode pengisi data awal | Bank dan gudang Surabaya |
| Factory | Pembuat data contoh untuk pengembangan/pengujian | `UserFactory` |
| Snapshot | Salinan nilai saat suatu kejadian | Harga saat checkout tetap meski katalog berubah |
| Lot | Satu kelompok beras yang masuk bersama dengan harga beli sendiri | Lot A dibeli Rp15.000/kg |
| Reservasi | Bagian stok yang ditahan untuk suatu pesanan | 5 kg belum keluar gudang, tetapi tidak bisa dipesan orang lain |
| Alokasi | Hubungan pesanan dengan rencana penyalurannya | Pesanan X masuk tujuan pertama truk |
| Manifest | Daftar tujuan, pesanan, dan muatan perjalanan | Cetakan untuk panitia |
| Audit log | Catatan siapa melakukan perubahan penting | Panitia menyetujui perubahan rekening |
| Scheduler | Pemicu pekerjaan pada waktu tertentu | Melepas pesanan kedaluwarsa tiap menit |

**Dua arti “transaksi” yang berbeda:**

- **Transaksi zakat** berarti pesanan bisnis milik pengguna, disimpan di `transaksi_zakat`.
- **Transaksi database** berarti sekumpulan perubahan database yang harus berhasil bersama. Contohnya, membuat pesanan, detail fitrah, reservasi, dan token QR. Jika salah satu langkah gagal, perubahan database dalam kelompok itu dibatalkan.

## 2. Gambaran sistem dan pembagian peran

Website ini mempertemukan muzakki, pengelola panti, dan panitia. Admin mengatur akun internal dan aturan operasional. Fitrah memakai beras dari gudang pusat Surabaya; maal memakai nominal dana uji tanpa mengambil stok beras.

| Pengguna | Nilai `user.role` | Cara memperoleh akun | Pekerjaan utama |
| --- | --- | --- | --- |
| Muzakki | `pemberi` | Daftar melalui halaman publik | Menghitung zakat, memilih panti, membuat pesanan, melihat bukti |
| PJ panti / yayasan | `pj` | Daftar melalui halaman publik | Mendaftarkan panti, memperbaiki pengajuan ditolak, meminta perubahan rekening, mengonfirmasi penerimaan |
| Panitia | `panitia` | Dibuat admin | Verifikasi panti, stok/vendor, pengiriman, alokasi maal, bukti, laporan |
| Admin | `admin` | Admin pertama melalui command khusus | Akun panitia, bank, tarif wilayah, pengaturan, harga emas, teks publik, audit |

Pilihan `muzakki` di formulir registrasi disimpan sebagai `pemberi`. Perbedaan nama ini disengaja dalam `AuthController::register()`. Panitia dan admin tidak bisa dipilih lewat registrasi publik.

Admin merupakan role internal yang tidak ditawarkan pada halaman daftar. Halaman admin tetap dilindungi login dan pemeriksaan role. Hak admin pada route saat ini **tidak otomatis mencakup semua halaman panitia**; panel pengaturan dan panel operasional menggunakan role berbeda.

~~~mermaid
flowchart TD
    A["Admin: aturan, akun panitia, harga emas"] --> B["Panitia: gudang dan panti layak"]
    C["PJ: daftar panti dan foto"] --> B
    B --> D["Muzakki: buat pesanan fitrah atau maal"]
    D --> E["QR publik: bayar simulasi"]
    E --> F["Panitia: alokasi dan pengiriman"]
    F --> G["Panitia dan PJ: bukti masing-masing"]
    G --> H["Penyaluran selesai, laporan dan notifikasi"]
~~~

## 3. Alur lengkap dari persiapan sampai selesai

### 3.1 Persiapan awal oleh admin dan panitia

Urutan ini menjelaskan pekerjaan yang dibutuhkan ketika database baru belum mempunyai data operasional.

1. Migration membentuk tabel. Seeder mengisi daftar bank, gudang Surabaya, enam wilayah Jawa, tarif awal, dan tiga pengaturan numerik.
2. Admin pertama dibuat melalui `CreateInitialAdmin`. Command meminta nama, email, dan kata sandi; kata sandi disimpan sebagai hash.
3. Admin login dan membuat akun panitia. Kata sandi sementara diisi admin, minimum 12 karakter dengan huruf dan angka. Panitia harus menggantinya sebelum memakai panel.
4. Admin mengisi tanggal Idulfitri untuk simulasi. Sistem menghitung batas checkout masing-masing wilayah.
5. Admin mengaktifkan wilayah yang batas waktunya masih di masa depan dan mengatur tarif.
6. Untuk maal, command `app:sync-gold-price` mengambil harga emas dunia dan kurs rupiah otomatis setiap 09.30 WIB. Input manual admin tersedia sebagai cadangan. Data emas atau kurs lebih dari tujuh hari ditolak pada checkout.
7. Panitia menambahkan vendor, jenis beras, dan penerimaan stok. Harga jenis beras baru belum bermakna sebelum lot pertama masuk.
8. PJ mendaftarkan panti; panitia memeriksanya sebelum muzakki dapat memilihnya.

**Seeder tidak membuat semua hal otomatis:** tidak membuat admin/panitia, panti, vendor, stok contoh, tanggal Idulfitri, atau harga emas. Pengaturan awal bukan jaminan bahwa sebuah wilayah sudah bisa menerima pesanan.

### 3.2 Registrasi, login, dan pemilihan dashboard

**Registrasi:**

1. Browser membuka `auth/register.blade.php`.
2. Form dikirim ke `AuthController::register()`.
3. Server memeriksa nama, email unik, NIK 16 digit unik, telepon, tanggal lahir sebelum hari ini, gender, domisili, serta password dan konfirmasinya. Alamat lengkap wajib untuk PJ.
4. Password registrasi minimum 10 karakter dengan huruf dan angka.
5. Baris akun dibuat di tabel `user`, password menjadi `pass_hash`.
6. Pengguna langsung login; session diperbarui; route `home` mengarahkan ke dashboard berdasarkan role.

**Login:** `Auth::attempt()` menggunakan email, password, dan syarat `is_active=true`. Model `User` memberitahu Laravel bahwa kolom hash bernama `pass_hash`, bukan `password`.

**Logout:** session diakhiri dan token CSRF diperbarui. Akun/database tidak dihapus.

### 3.3 Pendaftaran dan verifikasi panti

1. PJ masuk ke dashboard yayasan. Jika belum punya panti, tampil formulir pendaftaran.
2. PJ mengisi izin, kontak, alamat, rekening, jumlah calon penerima, kebutuhan, dan 1–5 foto. Foto maksimum 2 MB masing-masing.
3. `YayasanController::storePanti()` membuat `yayasan` dengan `status_verif=pending` dan `is_active=false`.
4. Foto disimpan sebagai berkas privat; tabel `foto_yayasan` menyimpan alamat berkas dan pengunggah.
5. Panitia membuka halaman Panti & Verifikasi dan memeriksa pengajuan.
6. Jika disetujui, panitia mencatat jumlah mustahik layak. Jumlahnya tidak boleh melebihi jumlah diajukan.
7. Agar menerima **fitrah**, panitia juga mengisi wilayah sesuai provinsi, jarak jalan, dan kapasitas penerimaan per kunjungan. Gudang wilayah harus aktif.
8. Hasil pemeriksaan disimpan di `verifikasi_panti`; nilai keadaan terbaru diperbarui di `yayasan`; tindakan dicatat di `audit_log`.
9. Jika ditolak, PJ dapat memperbaiki data lalu mengajukan kembali. Status kembali `pending`; data kelayakan/rute lama dibersihkan. Foto baru opsional; jika diberikan, menggantikan kelompok foto sebelumnya.

**Satu akun PJ hanya memiliki paling banyak satu panti:** `yayasan.pj_user_id` mempunyai UNIQUE. Panti diverifikasi tetapi tanpa rute fitrah dapat tetap layak untuk maal.

Jumlah mustahik layak saat ini merupakan angka agregat yang disetujui panitia. Alur ini tidak otomatis membuat baris `mustahik` untuk tiap orang.

### 3.4 Vendor, pembelian, dan beras masuk

Contoh: panitia menerima 100 kg beras jenis A dari vendor X dengan harga beli Rp15.000/kg.

1. `vendor` menyimpan pemasok, sedangkan `katalog_beras` menyimpan **jenis** beras.
2. Form penerimaan memuat vendor, gudang, jenis beras, berat, harga beli, tanggal masuk, dan opsional faktur/kedaluwarsa.
3. `InventoryController::receive()` membuat satu `pembelian_beras`.
4. Dibuat satu `lot_beras` dengan `jumlah_masuk_kg=100`, `sisa_kg=100`, dan harga beli Rp15.000.
5. `mutasi_stok` mencatat masuknya 100 kg.
6. `katalog_beras.stock` bertambah 100 kg.
7. Harga katalog diperbarui menurut lot paling awal yang masih memiliki sisa dan persentase keuntungan.
8. Jika harga penawaran berubah, `riwayat_harga_katalog` menyimpan riwayatnya.

Satu pengiriman vendor berikutnya dengan harga berbeda membuat **lot baru**. Harga lot lama tetap. Penerimaan lewat form saat ini membuat pembelian dan satu lot sekaligus; skema memungkinkan satu pembelian mempunyai banyak lot, tetapi belum ada form pembelian dengan banyak baris barang.

Faktur boleh PDF/gambar maksimum 5 MB. Faktur bisa dilengkapi belakangan jika belum ada. Endpoint saat ini menolak penggantian faktur yang sudah tersimpan.

### 3.5 Fitrah: memilih, membuat pesanan, dan menahan stok

1. Muzakki membuka halaman fitrah.
2. Controller terlebih dahulu memproses sebagian pesanan pending yang kedaluwarsa.
3. Katalog menampilkan jenis aktif yang stok tersedianya positif. Stok tersedia dihitung dari stok gudang dikurangi reservasi aktif.
4. Pilihan panti hanya yang aktif, verified, punya mustahik layak, kapasitas dan jarak, serta wilayah aktif dengan batas checkout belum lewat.
5. Muzakki memilih jumlah jiwa 1–50, beras, panti, dan opsional catatan doa.
6. JavaScript membantu menandai jenis beras yang cukup. Server tetap memeriksa ulang karena orang lain dapat membuat pesanan bersamaan.
7. `FitrahCheckoutController::store()` mengunci produk dan lot, memilih lot melalui FIFO, lalu menghitung biaya.
8. Jika stok cukup, dibuat `transaksi_zakat`, `transaksi_fitrah`, satu atau beberapa `reservasi_stok`, dan `percobaan_pembayaran`.
9. Harga, nama beras, bobot per jiwa, dan jarak disimpan sebagai snapshot.
10. Muzakki diarahkan ke halaman pembayaran miliknya. Status awal pembayaran `pending`; penyaluran `menunggu_alokasi`.

**Pada tahap ini stok belum keluar gudang.** Reservasi hanya mengurangi bagian yang ditawarkan kepada orang lain. Satu pesanan bisa memakai beberapa lot bila lot tertua tidak mencukupi.

### 3.6 QR simulasi, termasuk scan dari HP tanpa login

Ada dua halaman yang berbeda:

| Halaman | Akses | Isi |
| --- | --- | --- |
| Detail pesanan `muzakki.payment.show` | Login muzakki pemilik | Rincian harga/harta, status, QR, bukti dan pembatalan |
| Scan QR `muzakki.payment.scan` | Publik, memakai token acak | Kode pesanan, panti, total, status, tombol bayar simulasi |

1. Token acak 64 karakter dibuat saat checkout.
2. Tabel `percobaan_pembayaran` menyimpan SHA-256 token. `transaksi_zakat.qr_payload` menyimpan token terenkripsi agar QR dapat dibuat kembali.
3. Library Endroid menghasilkan QR berbentuk SVG yang berisi tautan halaman scan.
4. HP dapat membuka halaman scan tanpa session login dari komputer.
5. Membuka QR dengan GET hanya menampilkan informasi. Tombol bayar mengirim **POST** dengan CSRF; ada pembatasan frekuensi.
6. Server mengunci pesanan dan percobaan pembayaran serta memeriksa mode simulasi, status, dan waktu berlaku.
7. Jika sah, pembayaran menjadi `simulated_paid`, waktu bayar dicatat, dan percobaan pembayaran ditandai selesai.
8. Penyaluran tetap `menunggu_alokasi`. Stok dan reservasi tidak dikurangi pada pembayaran.
9. Klik ulang QR yang sudah dibayar tidak menghasilkan pembayaran tambahan.

**Token QR adalah akses khusus pesanan simulasi:** orang yang memegang tautan dapat menekan bayar simulasi. Halaman publik sengaja tidak menampilkan profil/NIK atau rincian harta keluarga. Tombol bukan integrasi bank ataupun bukti uang masuk.

### 3.7 Pembatalan dan kedaluwarsa

- Pemilik dapat membatalkan pesanan **pending**. Pembayaran dan percobaannya menjadi `cancelled`; reservasi aktif menjadi `cancelled`.
- Jika tidak dibayar dalam 30 menit, proses kedaluwarsa menjadikannya `expired` dan melepas reservasi.
- Pelepasan berarti reservasi tidak lagi `active`; baris historisnya tetap ada.
- Pesanan `simulated_paid` tidak terkena kedaluwarsa pending, sehingga stok pesanannya tetap ditahan sampai dikirim.
- Kode saat ini tidak menyediakan tombol pembatalan pesanan setelah dibayar simulasi.
- Membuka halaman QR yang sudah kedaluwarsa mencegah pembayaran, tetapi pelepasan stok memerlukan pemrosesan kedaluwarsa oleh command atau halaman yang memanggilnya. Scan GET sendiri tidak menjalankan fungsi pelepasan.

### 3.8 Maal: tiap pemilik harta dihitung sendiri

1. Halaman maal memuat harga emas terbaru, panti layak, dan draft keluarga milik akun.
2. Isi tepat satu subjek `diri`, paling banyak satu `istri`, dan tambahan `anak`; jumlah seluruh subjek maksimum 10.
3. Per subjek, isi tabungan, emas gram, investasi, aset dagang, piutang, utang, tanggal haul opsional, serta pernyataan haul.
4. Tombol **simpan draft** menyimpan data editable di `subjek_zakat_maal` tanpa membuat pesanan.
5. Saat checkout, server memeriksa bahwa ID acuan yang tampil masih sama dengan catatan terakhir dan data sumbernya masih dalam tujuh hari. Jika berubah, isian dikembalikan agar pengguna meninjau harga/nisab terbaru sebelum mengirim ulang; pesanan belum dibuat.
6. Nisab dan kewajiban diuji **per pemilik**, lalu zakat wajib tiap pemilik dijumlahkan.
7. Jika tidak ada zakat wajib positif, pesanan tidak dibuat.
8. Jika pengguna memilih panti, pilihan diperiksa ulang. Jika otomatis, sistem mengacak panti layak yang belum mempunyai pesanan maal `simulated_paid`; jika semuanya pernah, mengacak seluruh panti layak.
9. Dibuat master transaksi, `transaksi_mal`, snapshot per pemilik, dan token QR.
10. Pembayaran QR memakai alur yang sama. Maal tidak membuat reservasi beras.
11. Nilai tagihan maal saat ini adalah jumlah zakat wajib; snapshot admin, keuntungan, ongkir, dan harga pokok beras semuanya nol.

**Draft berbeda dengan snapshot:** draft dapat diganti lewat simpan draft. Snapshot pada transaksi lama menjelaskan perhitungan saat pesanan itu dibuat. Checkout tidak menghapus draft. Saat draft ditampilkan kembali, checkbox “sudah haul” disetel false supaya dinyatakan lagi.

Pilihan otomatis menggunakan riwayat pembayaran simulasi, bukan jumlah mustahik individu yang telah menerima transfer bank. Perhitungan ini adalah aturan aplikasi tugas saat ini; kalender haul menggunakan tanggal yang diisi, bukan mesin pembuktian kepemilikan harta.

### 3.9 Alokasi fitrah, perjalanan truk, dan keberangkatan

1. Panitia membuka Pesanan & Distribusi. Pesanan yang dibayar simulasi dan masih `menunggu_alokasi` tersedia untuk dipilih.
2. Buat `perjalanan_pengiriman`: gudang, kendaraan, kapasitas kg, jadwal di masa depan. Status `direncanakan`.
3. Tambahkan satu panti sebagai `titik_pengiriman`, lalu pilih pesanan fitrah milik panti itu.
4. Sistem memastikan tidak ada pesanan ganda, panti pada gudang yang sama, kapasitas panti cukup, dan total muatan tidak melewati kapasitas kendaraan.
5. Setiap pesanan dibuatkan `alokasi_transaksi`. Status pesanan/alokasi menjadi `dijadwalkan`.
6. Satu perjalanan dapat punya banyak titik; satu panti tidak boleh ditambahkan dua kali pada perjalanan yang sama lewat fungsi ini.
7. Manifest menampilkan tujuan, urutan, dan pesanan.
8. Panitia menekan **berangkat**. Reservasi harus cocok dengan berat alokasi.
9. Dalam transaksi database: sisa lot dan stok katalog dikurangi, mutasi keluar dibuat, reservasi berubah `fulfilled`, lalu perjalanan/titik/alokasi/pesanan menjadi `dikirim`.
10. Harga penawaran katalog dapat berubah jika lot yang menjadi acuan berganti.

**Sekarang satu pesanan dialokasikan utuh.** Belum ada alur membagi satu pesanan ke dua truk/panti. Tombol berangkat mencatat tindakan operator; tidak memanggil GPS, jasa ekspedisi, atau menggerakkan kendaraan.

### 3.10 Alokasi maal

Panitia menekan alokasi untuk pesanan maal yang sudah dibayar simulasi. `alokasi_transaksi` memuat nominal pesanan dan status `menunggu_bukti`. Tidak dibuat perjalanan, titik, ataupun mutasi beras. Panitia dan PJ kemudian memberikan bukti penyerahan dana **uji**.

### 3.11 Dua bukti dan penyelesaian

1. Panitia mengunggah foto miliknya untuk satu alokasi.
2. PJ panti tujuan mengunggah foto berbeda miliknya.
3. Fitrah meminta jumlah kg; maal meminta nominal rupiah. Foto maksimum 2 MB.
4. Tabel `bukti_serah_terima` menyimpan pihak `panitia` atau `yayasan`, pengunggah, lokasi berkas, nilai, catatan, dan hasil kesesuaian.
5. Jika ada nilai yang tidak sama dengan alokasi, status menjadi `perlu_tinjauan`.
6. Jika baru satu bukti dan nilainya sesuai, status menjadi `menunggu_bukti`.
7. Jika kedua pihak lengkap dan nilainya sesuai, alokasi dan transaksi menjadi `selesai`.
8. Dibuat satu catatan `penyaluran` bila belum ada, serta notifikasi untuk muzakki dan PJ.
9. Untuk fitrah, titik menjadi `diterima` setelah semua alokasinya selesai. Perjalanan menjadi `selesai` setelah semua titik diterima.
10. Sebelum selesai, bukti pihak yang sama bisa diperbaiki; file lamanya dihapus setelah perubahan berhasil. Setelah selesai, unggah ulang ditolak.

Pencocokan yang dilakukan kode adalah **kelengkapan pihak dan kesamaan angka**. Foto belum diperiksa otomatis dengan AI atau ditandatangani secara digital. Sesuai lingkup tugas, penerimaan oleh panti dianggap penyelesaian penerima akhir.

### 3.12 Perubahan rekening, laporan, dan pengingat

**Rekening:** PJ mengajukan bank, nomor, dan atas nama baru. Rekening aktif tidak berubah dahulu. Panitia memutuskan `approved` atau `rejected`, dengan catatan wajib bila ditolak. Jika disetujui, rekening yayasan diperbarui. Pengajuan pending kedua ditolak sampai yang pertama selesai. Tidak ada transfer uang otomatis setelah persetujuan.

**Laporan:** membandingkan stok katalog, total sisa lot, reservasi, status pesanan, biaya ongkir ditagih dan aktual, serta jumlah terkumpul/selesai per panti. Ongkir aktual dicatat per titik setelah keberangkatan; jumlah perjalanan baru lengkap jika semua titik mempunyai ongkir aktual. Tagihan pesanan lama tidak berubah.

**Pengingat:** preferensi profil menentukan pengingat fitrah dan peninjauan haul di aplikasi. Fitrah muncul pada H-14 dari tanggal Idulfitri yang disetel. Haul melihat pengulangan bulan/tanggal draft pemilik harta. Scheduler harus berjalan. Pilihan email sudah dapat disimpan, tetapi email bukti belum dikirim oleh kode aktif.

## 4. Status, stok, dan perhitungan

### 4.1 Status pembayaran dan status penyaluran adalah dua kolom

| Kolom | Nilai yang digunakan alur aktif | Arti |
| --- | --- | --- |
| `status_pembayaran` | `pending` | Belum menekan bayar simulasi |
| | `simulated_paid` | Simulasi pembayaran berhasil |
| | `cancelled` | Pemilik membatalkan sebelum bayar |
| | `expired` | Batas pembayaran habis |
| `status_penyaluran` | `menunggu_alokasi` | Belum dipasangkan dengan penyerahan |
| | `dijadwalkan` | Fitrah telah masuk rencana perjalanan |
| | `dikirim` | Perjalanan fitrah sudah berangkat |
| | `menunggu_bukti` | Menunggu bukti pihak lainnya, atau alokasi maal baru |
| | `perlu_tinjauan` | Nilai bukti tidak sesuai |
| | `selesai` | Dua bukti lengkap dan sesuai |

Pesanan cancelled/expired dapat tetap mempunyai status penyaluran awal `menunggu_alokasi`; jangan menafsirkannya sebagai antrean kirim. Antrean operasional juga memfilter status pembayaran.

~~~mermaid
stateDiagram-v2
    [*] --> pending
    pending --> simulated_paid: Bayar simulasi sebelum batas
    pending --> cancelled: Pemilik membatalkan
    pending --> expired: Batas habis dan diproses
    simulated_paid --> [*]
    cancelled --> [*]
    expired --> [*]
~~~

Diagram ini hanya **pembayaran**. Akhir diagram `simulated_paid` belum berarti penyaluran selesai.

~~~mermaid
flowchart LR
    A["menunggu_alokasi"] -->|Fitrah| B["dijadwalkan"]
    B --> C["dikirim"]
    A -->|Maal| D["menunggu_bukti"]
    C --> E["Bukti panitia dan yayasan"]
    D --> E
    E -->|Satu sesuai| D
    E -->|Ada selisih| F["perlu_tinjauan"]
    F -->|Perbaiki bukti| E
    E -->|Keduanya sesuai| G["selesai"]
~~~

### 4.2 Stok fisik tercatat, reservasi, dan stok tersedia

Di lingkungan tugas, “stok fisik” berikut berarti **saldo persediaan yang dicatat di database pengembangan**, bukan klaim penghitungan gudang sungguhan.

`stok tersedia = katalog_beras.stock - SUM(reservasi_stok.jumlah_kg yang statusnya active)`

| Kejadian | Stok katalog | Reservasi aktif | Tersedia |
| --- | ---: | ---: | ---: |
| Stok awal | 100 kg | 0 kg | 100 kg |
| A memesan 5 kg, pending | 100 kg | 5 kg | 95 kg |
| A membayar simulasi | 100 kg | 5 kg | 95 kg |
| B memesan 10 kg | 100 kg | 15 kg | 85 kg |
| B dibatalkan | 100 kg | 5 kg | 95 kg |
| Panitia berangkat membawa A | 95 kg | 0 kg | 95 kg |
| Dua bukti A lengkap | 95 kg | 0 kg | 95 kg |

Kedua perubahan pada keberangkatan penting: stok turun 5 dan reservasi aktif turun 5. Jika reservasi tetap active setelah stok dikurangi, 5 kg terhitung dikurangi dua kali.

- `lot_beras.sisa_kg`: saldo per lot.
- `katalog_beras.stock`: saldo gabungan per jenis beras.
- `reservasi_stok`: rincian saldo yang terikat pesanan dan lot.
- `mutasi_stok`: riwayat perubahan saldo, bukan saldo tambahan.
- `penyaluran.jumlah`: jumlah yang selesai diserahterimakan, bukan stok tersedia.

Koreksi stok memerlukan alasan; sisa lot tidak boleh menjadi lebih kecil daripada reservasi aktifnya.

### 4.3 Fitrah dan harga beberapa lot

Default seeder: bobot 2,5 kg/jiwa, keuntungan 5%, admin Rp1.000, ongkir tetap Rp3.000 ditambah Rp8 per km per kg. Admin bisa mengubahnya; contoh ini tidak mengklaim nilai database hari ini masih sama.

~~~text
berat = jumlah_jiwa × bobot_per_jiwa
harga_pokok = jumlah seluruh (kg yang diambil dari lot × harga beli lot)
keuntungan = harga_pokok × persen_keuntungan / 100
biaya_beras = harga_pokok + keuntungan
ongkir = biaya_tetap + biaya_per_km_kg × jarak_jalan_km × berat
total = biaya_beras + biaya_admin + ongkir
~~~

**Contoh satu lot:** 2 jiwa = 5 kg; harga beli Rp15.000/kg; jarak 20 km.

| Komponen | Perhitungan | Hasil |
| --- | --- | ---: |
| Pokok beras | 5 × 15.000 | Rp75.000 |
| Keuntungan | 5% × 75.000 | Rp3.750 |
| Admin | Nilai pengaturan | Rp1.000 |
| Ongkir | 3.000 + 8 × 20 × 5 | Rp3.800 |
| Total | Semua komponen | **Rp83.550** |

Harga katalog pada contoh adalah Rp15.750/kg, yaitu pokok + keuntungan. Pada checkout, controller menghitung ulang **pokok lot**, kemudian menambahkan keuntungan sekali. Tidak mengalikan harga katalog yang sudah bermargin lalu menambahkan margin lagi.

**Contoh lot lama dan baru:** butuh 10 kg; lot lama tersedia 4 kg × Rp15.000 dan lot baru 6 kg × Rp17.000. Pokok = Rp60.000 + Rp102.000 = Rp162.000; keuntungan 5% = Rp8.100; beras ditagih = Rp170.100; snapshot harga beras rata-rata = Rp17.010/kg, di luar admin dan ongkir.

FIFO berarti mengambil dari lot dengan `tanggal_masuk` paling awal, kemudian ID terkecil jika tanggal sama. Harga lot tidak ditimpa harga pasar baru. Katalog mengacu lot tertua bersisa, sedangkan checkout dapat mengambil kombinasi lot; akibatnya total akhir bisa berbeda dari perkiraan memakai satu harga katalog.

Perhitungan uang memakai BCMath (`bcadd`, `bcmul`, `bcdiv`, `bccomp`) dan nilai DECIMAL. Kode membulatkan pokok/keuntungan ke dua desimal dan ongkir ke rupiah bulat. Tampilan bisa mengubah format angka menjadi rupiah tanpa mengubah nilai yang disimpan.

### 4.4 Maal dan pemisahan pemilik harta

~~~text
nilai_emas = emas_gram × harga_emas_per_gram
harta_bersih = max(0, tabungan + nilai_emas + investasi + aset_dagang + piutang - utang)
nisab = 85 × harga_emas_per_gram
zakat_pemilik = 2,5% × harta_bersih
               hanya jika sudah_haul dan harta_bersih >= nisab
total_pesanan = SUM(zakat_pemilik)
~~~

Contoh harga emas **fiktif** Rp1.000.000/gram → nisab Rp85.000.000.

| Pemilik | Harta bersih | Haul dinyatakan | Zakat menurut kode |
| --- | ---: | --- | ---: |
| Diri | Rp100.000.000 | Ya | Rp2.500.000 |
| Istri | Rp60.000.000 | Ya | Rp0 |
| Anak | Rp20.000.000 | Tidak | Rp0 |

Total pesanan Rp2.500.000. Harta keluarga tidak terlebih dahulu digabung menjadi Rp180.000.000 untuk menguji kewajiban. Baris ringkasan `transaksi_mal` tetap menyimpan jumlah aset keluarga, sedangkan bukti perhitungan per orang ada di `subjek_zakat_maal`.

### 4.5 Tenggat, waktu, dan ongkir

Aplikasi memakai UTC dalam konfigurasi dasar. Input tanggal/jadwal yang disebut WIB dikonversi dari `Asia/Jakarta` ke UTC; view tertentu mengubah kembali ke WIB.

Default hari batas sebelum Idulfitri: Jawa Timur 5, Jawa Tengah/DI Yogyakarta 7, Jawa Barat/DKI Jakarta/Banten 10. `setOperationalSettings()` menghitung akhir hari WIB pada tanggal Idulfitri dikurangi hari batas, lalu menyimpan UTC.

Mengisi tanggal Idulfitri menghitung tenggat tetapi tidak otomatis mengaktifkan semua wilayah. Mengaktifkan wilayah tanpa tenggat masa depan ditolak dengan pesan validasi. Jarak jalan saat ini diisi panitia; tidak ada API peta yang menghitungnya otomatis.

Ongkir checkout adalah taksiran tarif per pesanan. Ongkir aktual perjalanan dicatat belakangan per titik dan digunakan laporan selisih. Belum ada pengembalian selisih ongkir atau perhitungan pembagian biaya truk otomatis.

## 5. Bagaimana Laravel memproses tombol

~~~mermaid
sequenceDiagram
    participant U as Browser
    participant R as Route dan middleware
    participant C as Controller
    participant D as Database
    participant V as Blade
    U->>R: POST form fitrah dan CSRF
    R->>R: Periksa login dan role pemberi
    R->>C: store(Request)
    C->>C: Validasi dan perhitungan
    C->>D: Kunci produk, simpan pesanan dan reservasi
    D-->>C: ID transaksi
    C-->>U: Redirect halaman pembayaran
    U->>R: GET pembayaran milik pengguna
    R->>C: show(Request, id)
    C->>D: Ambil pesanan dan detail
    C->>V: pesanan, QR, bukti
    V-->>U: HTML halaman pembayaran
~~~

### 5.1 Tiga jenis file yang paling sering Anda hubungkan

Contoh klik “buat pesanan fitrah”:

1. [resources/views/muzakki/fitrah.blade.php](resources/views/muzakki/fitrah.blade.php) mempunyai form dan nama input.
2. [routes/web.php](routes/web.php) menghubungkan route `muzakki.fitrah.store` ke controller.
3. [app/Http/Controllers/FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php) method `store()` menentukan validasi, harga, dan penyimpanan.
4. Tabel yang berubah: master transaksi, detail fitrah, reservasi lot, dan percobaan pembayaran.
5. [resources/views/muzakki/payment.blade.php](resources/views/muzakki/payment.blade.php) menampilkan hasil melalui controller pembayaran.

Mengubah teks tombol dilakukan di view. Mengubah aturan perhitungan dilakukan pada backend terkait, sedangkan nilai margin yang sudah dapat diatur lewat admin tidak perlu diubah di source. Menambah kolom database dilakukan dengan migration baru.

### 5.2 Membaca potongan kode tanpa menghafal sintaks

~~~php
$data = $request->validate([
    'jumlah_jiwa' => ['required', 'integer', 'min:1', 'max:50'],
]);
~~~

Artinya: ambil input `jumlah_jiwa`, wajib ada, bilangan bulat, 1–50. Jika gagal, Laravel mengembalikan kesalahan validasi dan proses penyimpanan tidak dilanjutkan.

~~~php
$produk = DB::table('katalog_beras')
    ->where('id', $data['katalog_beras_id'])
    ->lockForUpdate()
    ->first();
~~~

Artinya: cari satu jenis beras menurut ID dan kunci barisnya dalam transaksi database supaya proses lain tidak memakai perubahan saldo yang belum selesai.

~~~php
return view('muzakki.fitrah', compact('beras', 'panti', 'bobot'));
~~~

Artinya: render `resources/views/muzakki/fitrah.blade.php`, kirim variabel `$beras`, `$panti`, `$bobot`. `compact` menyusun array nama variabel dan nilainya.

### 5.3 Eloquent dan Query Builder sama-sama dipakai

- `User::create(...)` memakai **Eloquent model**. Model punya nama tabel, fillable, casts, dan relasi.
- `DB::table('user')->insert(...)` memakai **Query Builder**. Hasilnya langsung ke tabel; tidak melalui accessor/mutator atau fillable model.
- Proyek memakai Eloquent untuk autentikasi, yayasan, dan sebagian riwayat; proses operasional banyak memakai Query Builder.
- Karena itu, tabel baru tidak selalu memiliki file model tersendiri. `reservasi_stok` tetap aktif walaupun tidak ada `ReservasiStok.php`.
- Mengubah model belum tentu mengubah perilaku query langsung. Cari pemanggilnya dahulu.

### 5.4 Blade dan JavaScript

| Bentuk | Arti |
| --- | --- |
| `{{ $item->nama }}` | Menampilkan nilai dengan escaping HTML |
| `@if ... @endif` | Menampilkan bagian jika kondisi benar |
| `@foreach ... @endforeach` | Mengulang baris/kartu |
| `@extends('muzakki.layout')` | Memakai kerangka halaman muzakki |
| `@section('content')` | Menyediakan isi halaman |
| `@yield('content')` | Tempat kerangka memasukkan isi |
| `@csrf` | Token form untuk perlindungan permintaan |
| `@method('PATCH')` | Form HTML POST diperlakukan sebagai PATCH oleh Laravel |
| `route('nama.route', $id)` | Menghasilkan alamat sesuai route bernama |
| `old('field')` | Mengisi kembali input ketika validasi gagal |
| `$errors` | Daftar kesalahan validasi |
| `session('success')` | Pesan hasil tindakan pada redirect berikutnya |

JavaScript dalam view membantu interaksi: membuka password, pilihan domisili, menambah pemilik harta, atau memeriksa stok tampilan. Pemeriksaan backend tetap menentukan hasil akhir.

## 6. Cara memahami struktur database

### 6.1 Fakta skema aktif

Metadata yang diperiksa: **33 tabel, 373 kolom, 53 foreign key, dan 2 CHECK untuk validitas JSON**. Koneksi bernama `mysql` dapat berbicara dengan server MariaDB; nama driver tidak berarti aplikasi sedang memakai SQLite.

[ERD_zakat_db.md](ERD_zakat_db.md) berisi diagram konseptual, physical per kelompok, serta rincian teknis indeks/default/FK. [ERD_zakat_db_physical.mmd](ERD_zakat_db_physical.mmd) adalah sumber Mermaid diagram physical lengkap. File `.mmd` berisi teks diagram, bukan data database.

| Kelompok | Jumlah | Contoh |
| --- | ---: | --- |
| Akun, panti, notifikasi | 9 | user, yayasan, verifikasi, preferensi |
| Persediaan | 7 | vendor, lot, mutasi |
| Pesanan, pembayaran, harga emas | 7 | transaksi, snapshot maal, reservasi |
| Penyaluran, tarif, pengaturan, audit | 9 | perjalanan, bukti, penyaluran |
| Infrastruktur Laravel | 1 | migrations |

### 6.2 PK, FK, UNIQUE, dan indeks

- **PK / primary key:** identitas satu baris. `user.id=7` menunjuk satu akun. Beberapa tabel pendukung memakai string sebagai PK.
- **FK / foreign key:** kolom yang menunjuk baris tabel lain. `transaksi_zakat.user_id=7` berarti pesanan milik akun ID 7.
- **UNIQUE:** nilai/kombinasi tidak boleh berulang. `email` unik; satu `transaksi_id` hanya punya satu detail fitrah.
- **Indeks biasa:** membantu pencarian/pengelompokan; tidak melarang duplikasi.
- **NULL:** nilai belum diisi/tidak berlaku. Tidak sama dengan nol atau teks kosong. Misalnya `titik_pengiriman_id=NULL` pada alokasi maal.
- **Default:** nilai saat INSERT tidak menyebut kolom; controller dapat memberi nilai lain.
- **Auto increment:** ID angka dibuat database; ID tidak harus berurutan tanpa celah.
- **CHECK JSON:** memeriksa isi JSON sah. Tidak memeriksa kebenaran bisnis nominal/jumlah penerima.

**Perilaku ketika induk dihapus:** RESTRICT mencegah penghapusan jika masih dirujuk; CASCADE menghapus baris anak; SET NULL membuat rujukan anak kosong. Ini berlaku pada DELETE sungguhan. Mengubah status ke `cancelled` tidak menjalankan CASCADE.

Banyak status dan role berupa VARCHAR, bukan ENUM/CHECK. Aturan nilai dijaga kode validasi. FK memastikan akun/panti ada, tetapi tidak memastikan role akun benar ataupun panti layak menerima zakat.

### 6.3 Relasi inti yang perlu Anda hafal

~~~text
user → transaksi_zakat → transaksi_fitrah → katalog_beras
                     ↘ transaksi_mal → harga_emas
                     ↘ subjek_zakat_maal (snapshot per pemilik)
                     ↘ reservasi_stok → lot_beras → pembelian_beras → vendor/gudang
                     ↘ percobaan_pembayaran
                     ↘ alokasi_transaksi → titik_pengiriman → perjalanan_pengiriman
                                         ↘ bukti_serah_terima
                                         ↘ penyaluran
yayasan → transaksi_zakat, verifikasi_panti, foto_yayasan, permohonan_rekening
~~~

Panah berarti arah pemahaman hubungan; FK sebenarnya ada pada tabel anak. Detail fitrah dan maal adalah cabang alternatif menurut `jenis_zakat`; sebuah pesanan bisnis seharusnya hanya memiliki satu cabang. Skema menyediakan UNIQUE per tabel detail, tetapi tidak sendirian mencegah kedua detail ada pada master yang sama atau master tanpa detail.

### 6.4 Mengapa tabelnya banyak

Satu tabel besar akan mencampur akun, harga, stok, QR, rute, dan bukti. Pemisahan membuat satu hal punya tempat yang jelas.

Contoh perbedaan yang paling sering tertukar:

| Tabel A | Tabel B | Perbedaan |
| --- | --- | --- |
| katalog_beras | lot_beras | Jenis produk versus kelompok stok dengan harga beli/tanggal sendiri |
| pembelian_beras | transaksi_zakat | Pembelian panitia dari vendor versus pesanan muzakki |
| reservasi_stok | mutasi_stok | Menahan stok versus perubahan stok yang sudah dicatat |
| transaksi_zakat | alokasi_transaksi | Pesanan/tagihan versus rencana penyerahan pesanan |
| perjalanan_pengiriman | titik_pengiriman | Satu perjalanan kendaraan versus satu tujuan dalam perjalanan |
| bukti_serah_terima | penyaluran | Bukti masing-masing pihak versus hasil penyaluran selesai |
| yayasan | verifikasi_panti | Keadaan panti terbaru versus riwayat pemeriksaan |
| harga_emas | harga_emas_snapshot | Harga acuan baru versus harga yang dipakai transaksi lama |
| subjek_zakat_maal draft | subjek_zakat_maal snapshot | Data editable versus rincian historis transaksi |
| migrations | file migration PHP | Catatan nama yang sudah dijalankan versus instruksi perubahan skema |

### 6.5 Migration, database lama, dan Git

Migration adalah resep tabel. Database adalah tabel dan data hasil resep. File migration tercatat Git, tetapi isi MariaDB tidak otomatis tersimpan di Git.

- Database aktif baru bernama `zakat_db`; bagian lama `!wajib_baca.md` yang menjelaskan `zakat` bukan kamus skema aktif.
- Nama migration `create_users_table` tetap nama bawaan; isinya membuat tabel **`user`**, tunggal.
- Mengubah migration yang sudah dijalankan tidak otomatis mengubah database.
- Migration baru dipakai untuk perubahan berikutnya; `migrations` mencatat nama dan batch.
- `git revert` mengembalikan perubahan source, tidak mengembalikan isi database.
- `migrate:fresh` menghapus tabel pada koneksi yang dipilih; bukan langkah membaca atau belajar skema.
- Backup data, berkas foto/faktur privat, dan source code adalah tiga hal yang berbeda.

## 7. Kamus seluruh tabel dan kolom

Bagian ini menerangkan fungsi bisnis serta arti **setiap kolom** skema aktif. Jenis data, kebolehan NULL, dan hubungan ditulis dari metadata. Nilai status diterangkan menurut penggunaan source; nilai yang mungkin ada pada database tidak diklaim sebagai daftar data aktual.

Cara membaca contoh baris kamus: `jumlah_kg | decimal(...) | Tidak | Berat... ` berarti kolom menyimpan angka desimal, wajib mempunyai nilai pada level skema, dan satuannya kilogram. Kolom NULL tetap dapat diwajibkan oleh suatu form untuk kondisi tertentu.

Semua tabel di bawah dibaca sebagai struktur; panduan tidak menyalin isi akun, nomor rekening, hash password, ataupun token pembayaran.

### 7.1 Akun, panti, profil, dan notifikasi

#### Tabel `user` — Akun dan hak akses

Identitas muzakki, PJ, panitia, dan admin. AuthController menulis akun publik; AdminController membuat panitia; User menghubungkan login ke pass_hash. Kolom nama dipakai UI, sedangkan role/is_active/must_change_password mengontrol akses. Biodata sebagian boleh NULL di skema untuk akun internal, tetapi registrasi publik mewajibkan lebih banyak field.

**Kode yang menyebut tabel ini secara langsung:** [User.php](app/Models/User.php), [CreateZakatReminders.php](app/Console/Commands/CreateZakatReminders.php), [CreateInitialAdmin.php](app/Console/Commands/CreateInitialAdmin.php), [InventoryController.php](app/Http/Controllers/InventoryController.php), [PantiVerificationController.php](app/Http/Controllers/PantiVerificationController.php), [AuthController.php](app/Http/Controllers/AuthController.php), [AdminController.php](app/Http/Controllers/AdminController.php), [MaalCheckoutController.php](app/Http/Controllers/MaalCheckoutController.php), [BankChangeRequestController.php](app/Http/Controllers/BankChangeRequestController.php), [MuzakkiController.php](app/Http/Controllers/MuzakkiController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `nik` | `varchar(16)` | Ya | Nomor identitas 16 digit, bukan bilangan untuk perhitungan. |
| `nama` | `varchar(255)` | Tidak | Nama yang ditampilkan untuk entitas tabel ini. |
| `telp` | `varchar(20)` | Ya | Nomor kontak telepon, disimpan sebagai teks agar nol depan tidak hilang. |
| `email` | `varchar(191)` | Tidak | Alamat email kontak; pada user juga dipakai login. |
| `email_verified_at` | `timestamp` | Ya | Waktu verifikasi email bila dilakukan; tersedia tetapi belum ada alur verifikasi email aktif. |
| `pass_hash` | `varchar(255)` | Tidak | Hash kata sandi untuk pemeriksaan login; bukan password asli yang dapat dibaca kembali. |
| `tggl_lahir` | `date` | Ya | Tanggal lahir pengguna/penerima. |
| `gender` | `varchar(1)` | Ya | Kode gender akun L atau P menurut validasi registrasi. |
| `kota_kabupaten` | `varchar(100)` | Ya | Nama kota/kabupaten; disimpan sebagai teks. |
| `provinsi` | `varchar(100)` | Ya | Nama provinsi; digunakan juga untuk mencocokkan wilayah panti. |
| `alamat_lengkap` | `text` | Ya | Alamat rinci entitas. |
| `npwp` | `varchar(25)` | Ya | NPWP opsional akun; belum memicu proses pajak otomatis. |
| `role` | `varchar(20)` | Tidak | Peran akses akun: pemberi, pj, panitia, admin menurut kode. |
| `is_active` | `tinyint(1)` | Tidak | Penanda aktif 1/true atau nonaktif 0/false; makna operasional mengikuti jenis tabel. |
| `must_change_password` | `tinyint(1)` | Tidak | Memaksa pergantian kata sandi sebelum memakai halaman ber-role. |
| `created_by_admin_id` | `bigint(20) unsigned` | Ya | Rujukan akun admin pembuat. |
| `remember_token` | `varchar(100)` | Ya | Token mekanisme remember login Laravel; bukan token QR. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `email`; `nik`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `created_by_admin_id` → `user.id`; saat induk dihapus: **SET NULL**.
- Indeks nonunik: `created_by_admin_id`.

#### Tabel `bank` — Daftar bank pilihan

Master bank yang diisi seeder/admin. Yayasan dan permohonan rekening mengacu ke ID bank. Menonaktifkan bank menghentikan pilihan/persetujuan baru, tidak menghapus rekening yang sudah tercatat. Tidak menyimpan saldo atau berkomunikasi dengan API bank.

**Kode yang menyebut tabel ini secara langsung:** [YayasanController.php](app/Http/Controllers/YayasanController.php), [AdminController.php](app/Http/Controllers/AdminController.php), [BankChangeRequestController.php](app/Http/Controllers/BankChangeRequestController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `kode` | `varchar(20)` | Tidak | Kode singkat unik untuk membedakan entitas. |
| `nama` | `varchar(255)` | Tidak | Nama yang ditampilkan untuk entitas tabel ini. |
| `is_active` | `tinyint(1)` | Tidak | Penanda aktif 1/true atau nonaktif 0/false; makna operasional mengikuti jenis tabel. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `kode`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- Tidak ada foreign key eksplisit pada tabel ini.

#### Tabel `yayasan` — Panti mitra dan keadaan terbarunya

Dibuat PJ lalu diperiksa panitia. Menggabungkan identitas, rekening aktif, jumlah penerima agregat, dan rute fitrah. status_verif dan is_active bukan hal yang sama: verified adalah hasil pemeriksaan, aktif adalah keadaan layanan. nama_bank tersimpan sebagai teks selain bank_id; persetujuan rekening memperbarui keduanya.

**Kode yang menyebut tabel ini secara langsung:** [Yayasan.php](app/Models/Yayasan.php), [PantiVerificationController.php](app/Http/Controllers/PantiVerificationController.php), [SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php), [YayasanController.php](app/Http/Controllers/YayasanController.php), [MaalCheckoutController.php](app/Http/Controllers/MaalCheckoutController.php), [LandingController.php](app/Http/Controllers/LandingController.php), [HandoverEvidenceController.php](app/Http/Controllers/HandoverEvidenceController.php), [BankChangeRequestController.php](app/Http/Controllers/BankChangeRequestController.php), [PanitiaController.php](app/Http/Controllers/PanitiaController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php), [OperationalReportController.php](app/Http/Controllers/OperationalReportController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php), [MuzakkiController.php](app/Http/Controllers/MuzakkiController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `nama` | `varchar(255)` | Tidak | Nama yang ditampilkan untuk entitas tabel ini. |
| `nomor_izin` | `varchar(100)` | Tidak | Nomor izin panti yang wajib unik. |
| `telp` | `varchar(20)` | Tidak | Nomor kontak telepon, disimpan sebagai teks agar nol depan tidak hilang. |
| `email` | `varchar(191)` | Tidak | Alamat email kontak; pada user juga dipakai login. |
| `kota_kabupaten` | `varchar(100)` | Tidak | Nama kota/kabupaten; disimpan sebagai teks. |
| `provinsi` | `varchar(100)` | Tidak | Nama provinsi; digunakan juga untuk mencocokkan wilayah panti. |
| `alamat_lengkap` | `text` | Tidak | Alamat rinci entitas. |
| `kode_pos` | `varchar(10)` | Ya | Kode pos alamat panti. |
| `bank_id` | `bigint(20) unsigned` | Ya | Rujukan bank pilihan. |
| `nama_bank` | `varchar(100)` | Ya | Nama bank tersimpan pada rekening panti; diperbarui bersama bank_id. |
| `no_rek` | `varchar(50)` | Ya | Nomor rekening berupa teks, bukan nominal atau saldo. |
| `atas_nama_rek` | `varchar(255)` | Ya | Nama pemilik rekening. |
| `status_verif` | `varchar(20)` | Tidak | Hasil keadaan pemeriksaan panti: pending, verified, rejected. |
| `is_active` | `tinyint(1)` | Tidak | Penanda aktif 1/true atau nonaktif 0/false; makna operasional mengikuti jenis tabel. |
| `pj_user_id` | `bigint(20) unsigned` | Tidak | Rujukan akun PJ panti. |
| `wilayah_layanan_id` | `bigint(20) unsigned` | Ya | Rujukan zona fitrah. |
| `jarak_jalan_km` | `decimal(10,2)` | Ya | Jarak perjalanan jalan dari gudang ke panti yang dicatat panitia. |
| `jumlah_mustahik_layak` | `int(10) unsigned` | Tidak | Jumlah penerima agregat yang disetujui panitia. |
| `pict_id` | `varchar(255)` | Ya | Kolom referensi gambar rancangan lama; bukan FK foto aktif. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |
| `jumlah_mustahik_diajukan` | `int(10) unsigned` | Tidak | Jumlah calon penerima yang dinyatakan PJ. |
| `kebutuhan_saat_ini` | `text` | Ya | Deskripsi kebutuhan dari PJ, bukan target kuantitas periode yang dihitung otomatis. |
| `kapasitas_penerimaan_kg` | `decimal(12,2)` | Ya | Batas beras per kunjungan yang disetujui panitia. |
| `kapasitas_diajukan_kg` | `decimal(12,2)` | Ya | Kapasitas beras per kunjungan yang diajukan PJ; wajib pada pengajuan fitrah baru. NULL untuk maal saja atau data lama yang belum mengajukan. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `nomor_izin`; `pj_user_id`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `bank_id` → `bank.id`; saat induk dihapus: **RESTRICT**.
- FK `pj_user_id` → `user.id`; saat induk dihapus: **RESTRICT**.
- FK `wilayah_layanan_id` → `wilayah_layanan.id`; saat induk dihapus: **SET NULL**.
- Indeks nonunik: `bank_id`; `status_verif` + `is_active`; `wilayah_layanan_id`.

#### Tabel `mustahik` — Calon/penerima individu

Struktur untuk data individu dan kategori asnaf. Model relasinya tersedia, tetapi alur panti aktif memakai jumlah_mustahik_layak di yayasan dan belum menyediakan CRUD mustahik individu. Penyaluran tugas dapat selesai dengan mustahik_id kosong. is_verified di tabel ini tidak otomatis dihitung dari jumlah penerima yayasan.

**Kode yang menyebut tabel ini secara langsung:** [Mustahik.php](app/Models/Mustahik.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `yayasan_id` | `bigint(20) unsigned` | Tidak | Rujukan panti terkait. |
| `nama` | `varchar(255)` | Ya | Nama yang ditampilkan untuk entitas tabel ini. |
| `kelamin` | `varchar(1)` | Ya | Jenis kelamin pada struktur penerima lama. |
| `tggl_lahir` | `date` | Ya | Tanggal lahir pengguna/penerima. |
| `kategori_asnaf` | `varchar(30)` | Tidak | Kategori penerima dalam struktur individu. |
| `is_verified` | `tinyint(1)` | Tidak | Penanda verifikasi individu; tidak otomatis sama dengan verifikasi panti. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `yayasan_id` → `yayasan.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `yayasan_id`.

#### Tabel `foto_yayasan` — Foto pendukung pendaftaran panti

YayasanController menulis satu baris per foto. Berkas tersimpan di storage/app/private/panti; path_private adalah lokasi relatif, bukan foto dalam database. PantiVerificationController::foto memeriksa hak panitia/admin atau PJ pemilik sebelum menyajikan berkas.

**Kode yang menyebut tabel ini secara langsung:** [PantiVerificationController.php](app/Http/Controllers/PantiVerificationController.php), [YayasanController.php](app/Http/Controllers/YayasanController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `yayasan_id` | `bigint(20) unsigned` | Tidak | Rujukan panti terkait. |
| `diunggah_oleh` | `bigint(20) unsigned` | Tidak | Rujukan akun pengunggah. |
| `path_private` | `varchar(255)` | Tidak | Lokasi relatif berkas pada disk local privat; database menyimpan path, bukan byte gambar. |
| `keterangan` | `varchar(255)` | Ya | Teks penjelasan tambahan foto. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `diunggah_oleh` → `user.id`; saat induk dihapus: **RESTRICT**.
- FK `yayasan_id` → `yayasan.id`; saat induk dihapus: **CASCADE**.
- Indeks nonunik: `diunggah_oleh`; `yayasan_id`.

#### Tabel `verifikasi_panti` — Riwayat keputusan pemeriksaan

Satu panti dapat diperiksa berulang kali. Setiap update pemeriksaan menambah baris yang memuat pemeriksa, hasil, jumlah layak, kebutuhan saat diperiksa, dan catatan. Nilai terbaru juga disalin ke yayasan. kapasitas yang dicatat berlaku per kunjungan; bukan target kumulatif tahunan.

**Kode yang menyebut tabel ini secara langsung:** [PantiVerificationController.php](app/Http/Controllers/PantiVerificationController.php), [YayasanController.php](app/Http/Controllers/YayasanController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `yayasan_id` | `bigint(20) unsigned` | Tidak | Rujukan panti terkait. |
| `panitia_id` | `bigint(20) unsigned` | Tidak | Rujukan akun panitia pencatat/pemeriksa. |
| `hasil` | `varchar(20)` | Tidak | Keputusan pemeriksaan: verified/rejected pada verifikasi panti. |
| `jumlah_mustahik_layak` | `int(10) unsigned` | Tidak | Jumlah penerima agregat yang disetujui panitia. |
| `catatan` | `text` | Ya | Catatan tambahan tindakan/bukti/pemeriksaan. |
| `diperiksa_at` | `timestamp` | Tidak | Waktu pemeriksa memberikan keputusan. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |
| `kapasitas_penerimaan_kg` | `decimal(12,2)` | Ya | Batas beras yang dapat diterima panti per kunjungan. |
| `kebutuhan_snapshot` | `text` | Ya | Salinan kebutuhan panti ketika pemeriksaan dilakukan. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `panitia_id` → `user.id`; saat induk dihapus: **RESTRICT**.
- FK `yayasan_id` → `yayasan.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `panitia_id`; `yayasan_id` + `diperiksa_at`.

#### Tabel `permohonan_perubahan_rekening` — Permintaan pergantian rekening panti

BankChangeRequestController menyimpan pengajuan PJ dan keputusan panitia. Data di sini adalah rekening yang diminta, belum tentu rekening aktif. Persetujuan menyalin bank/no_rek/atas_nama_rek ke yayasan. status: pending, approved, rejected.

**Kode yang menyebut tabel ini secara langsung:** [YayasanController.php](app/Http/Controllers/YayasanController.php), [BankChangeRequestController.php](app/Http/Controllers/BankChangeRequestController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `yayasan_id` | `bigint(20) unsigned` | Tidak | Rujukan panti terkait. |
| `diajukan_oleh` | `bigint(20) unsigned` | Tidak | Rujukan akun PJ pengaju. |
| `bank_id` | `bigint(20) unsigned` | Tidak | Rujukan bank pilihan. |
| `no_rek` | `varchar(50)` | Tidak | Nomor rekening berupa teks, bukan nominal atau saldo. |
| `atas_nama_rek` | `varchar(255)` | Tidak | Nama pemilik rekening. |
| `status` | `varchar(20)` | Tidak | pending, approved, rejected. |
| `diperiksa_oleh` | `bigint(20) unsigned` | Ya | Rujukan akun panitia pemeriksa. |
| `catatan` | `text` | Ya | Catatan tambahan tindakan/bukti/pemeriksaan. |
| `diperiksa_at` | `timestamp` | Ya | Waktu pemeriksa memberikan keputusan. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `bank_id` → `bank.id`; saat induk dihapus: **RESTRICT**.
- FK `diajukan_oleh` → `user.id`; saat induk dihapus: **RESTRICT**.
- FK `diperiksa_oleh` → `user.id`; saat induk dihapus: **SET NULL**.
- FK `yayasan_id` → `yayasan.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `bank_id`; `diajukan_oleh`; `diperiksa_oleh`; `yayasan_id`.

#### Tabel `preferensi_notifikasi` — Pilihan pengingat per akun

MuzakkiController menyimpan satu baris per akun lewat upsert. Command pengingat membaca pilihan fitrah/haul. Tidak ada baris berarti command tidak menganggap pengguna telah memilih pengingat. Pilihan email tersedia sebagai rencana pengiriman, tetapi implementasi email belum aktif.

**Kode yang menyebut tabel ini secara langsung:** [CreateZakatReminders.php](app/Console/Commands/CreateZakatReminders.php), [MuzakkiController.php](app/Http/Controllers/MuzakkiController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `user_id` | `bigint(20) unsigned` | Tidak | Rujukan akun terkait. |
| `ingatkan_fitrah` | `tinyint(1)` | Tidak | Pilihan menerima pengingat fitrah di aplikasi. |
| `ingatkan_haul_maal` | `tinyint(1)` | Tidak | Pilihan menerima pengingat peninjauan haul maal di aplikasi. |
| `email_bukti_penyaluran` | `tinyint(1)` | Tidak | Pilihan email bukti yang tersimpan; pengiriman email belum aktif. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `user_id`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `user_id` → `user.id`; saat induk dihapus: **CASCADE**.

#### Tabel `notifikasi_aplikasi` — Pesan dalam dashboard

Dibuat saat penyaluran selesai dan oleh command pengingat. payload memuat JSON keterangan; dibaca_at ditandai hanya oleh akun pemilik. dikirim_at disediakan untuk pencatatan pengiriman, belum diisi alur email aktif. Pesan di database tidak berarti email/push sudah terkirim.

**Kode yang menyebut tabel ini secara langsung:** [CreateZakatReminders.php](app/Console/Commands/CreateZakatReminders.php), [YayasanController.php](app/Http/Controllers/YayasanController.php), [HandoverEvidenceController.php](app/Http/Controllers/HandoverEvidenceController.php), [InAppNotificationController.php](app/Http/Controllers/InAppNotificationController.php), [MuzakkiController.php](app/Http/Controllers/MuzakkiController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `user_id` | `bigint(20) unsigned` | Tidak | Rujukan akun terkait. |
| `jenis` | `varchar(50)` | Tidak | Nama jenis kejadian/pesan; daftar bergantung tabel. |
| `payload` | `longtext` | Tidak | JSON keterangan pesan, misalnya kode transaksi atau teks pengingat. |
| `dibaca_at` | `timestamp` | Ya | Waktu pesan ditandai dibaca; NULL berarti belum dibaca. |
| `dikirim_at` | `timestamp` | Ya | Waktu pengiriman yang disediakan; tidak membuktikan email sudah aktif. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `user_id` → `user.id`; saat induk dihapus: **CASCADE**.
- Indeks nonunik: `user_id` + `dibaca_at`.
- CHECK memeriksa bahwa kolom JSON berisi JSON yang sah; tidak membuktikan isi bisnis benar.

### 7.2 Persediaan dan harga beras

#### Tabel `gudang` — Tempat asal persediaan dan rute

Seeder membuat Gudang Pusat Surabaya. Pembelian dan perjalanan mengacu gudang; wilayah_layanan menentukan gudang asal panti fitrah. Skema menyediakan banyak gudang, tetapi operasi awal dirancang satu pusat. Katalog stok saat ini gabungan per jenis, belum dipisah per gudang pada checkout.

**Kode yang menyebut tabel ini secara langsung:** [InventoryController.php](app/Http/Controllers/InventoryController.php), [PantiVerificationController.php](app/Http/Controllers/PantiVerificationController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `kode` | `varchar(30)` | Tidak | Kode singkat unik untuk membedakan entitas. |
| `nama` | `varchar(255)` | Tidak | Nama yang ditampilkan untuk entitas tabel ini. |
| `provinsi` | `varchar(100)` | Tidak | Nama provinsi; digunakan juga untuk mencocokkan wilayah panti. |
| `kota_kabupaten` | `varchar(100)` | Tidak | Nama kota/kabupaten; disimpan sebagai teks. |
| `alamat_lengkap` | `text` | Ya | Alamat rinci entitas. |
| `is_active` | `tinyint(1)` | Tidak | Penanda aktif 1/true atau nonaktif 0/false; makna operasional mengikuti jenis tabel. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `kode`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- Tidak ada foreign key eksplisit pada tabel ini.

#### Tabel `vendor` — Pemasok beras

InventoryController membuat pemasok yang dapat dipilih saat penerimaan. Nama/kontak/alamat membantu menelusuri pembelian. Tidak menyediakan akun login vendor atau portal vendor terpisah.

**Kode yang menyebut tabel ini secara langsung:** [InventoryController.php](app/Http/Controllers/InventoryController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `nama` | `varchar(255)` | Tidak | Nama yang ditampilkan untuk entitas tabel ini. |
| `telp` | `varchar(20)` | Ya | Nomor kontak telepon, disimpan sebagai teks agar nol depan tidak hilang. |
| `email` | `varchar(191)` | Ya | Alamat email kontak; pada user juga dipakai login. |
| `alamat` | `text` | Ya | Alamat vendor. |
| `is_active` | `tinyint(1)` | Tidak | Penanda aktif 1/true atau nonaktif 0/false; makna operasional mengikuti jenis tabel. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- Tidak ada foreign key eksplisit pada tabel ini.

#### Tabel `pembelian_beras` — Dokumen beras diterima dari vendor

Dibuat bersama lot dan mutasi masuk oleh InventoryController::receive. status yang ditulis adalah diterima; saat ini belum ada siklus purchase order belum tiba atau pembayaran utang vendor. nomor_faktur dan bukti_path opsional; berkas faktur disimpan privat.

**Kode yang menyebut tabel ini secara langsung:** [InventoryController.php](app/Http/Controllers/InventoryController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `kode_pembelian` | `varchar(50)` | Tidak | Kode unik dokumen penerimaan dari vendor, prefix BELI. |
| `vendor_id` | `bigint(20) unsigned` | Tidak | Rujukan vendor pemasok. |
| `gudang_id` | `bigint(20) unsigned` | Tidak | Rujukan gudang asal. |
| `dicatat_oleh` | `bigint(20) unsigned` | Tidak | Rujukan akun pencatat. |
| `tanggal_pembelian` | `date` | Tidak | Tanggal pembelian/penerimaan yang form aktif isi dari tanggal masuk. |
| `nomor_faktur` | `varchar(100)` | Ya | Nomor faktur vendor opsional. |
| `bukti_path` | `varchar(255)` | Ya | Path privat berkas faktur vendor. |
| `status` | `varchar(20)` | Tidak | Status dokumen penerimaan; alur form menulis diterima. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `kode_pembelian`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `dicatat_oleh` → `user.id`; saat induk dihapus: **RESTRICT**.
- FK `gudang_id` → `gudang.id`; saat induk dihapus: **RESTRICT**.
- FK `vendor_id` → `vendor.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `dicatat_oleh`; `gudang_id`; `vendor_id`.

#### Tabel `katalog_beras` — Jenis beras, penawaran, dan saldo gabungan

Satu baris per nama jenis. stock merupakan saldo gudang tercatat, belum dikurangi reservasi aktif. harga_per_kg adalah penawaran pokok lot tertua bersisa ditambah margin, bukan dasar harga beli semua lot. Checkout menghitung pokok dari lot secara terpisah. pict_id adalah field lama; katalog aktif belum memakainya untuk pengelolaan foto produk.

**Kode yang menyebut tabel ini secara langsung:** [KatalogBeras.php](app/Models/KatalogBeras.php), [InventoryController.php](app/Http/Controllers/InventoryController.php), [AdminController.php](app/Http/Controllers/AdminController.php), [PanitiaController.php](app/Http/Controllers/PanitiaController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php), [OperationalReportController.php](app/Http/Controllers/OperationalReportController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `nama_beras` | `varchar(100)` | Tidak | Nama jenis/varian beras; unik di katalog. |
| `harga_per_kg` | `decimal(15,2)` | Tidak | Harga penawaran katalog per kg, termasuk margin beras; belum admin/ongkir. |
| `stock` | `decimal(12,2)` | Tidak | Saldo beras gabungan jenis dalam kg, sebelum mengurangi reservasi aktif. |
| `pict_id` | `varchar(255)` | Ya | Kolom referensi gambar rancangan lama; bukan FK foto aktif. |
| `is_active` | `tinyint(1)` | Tidak | Penanda aktif 1/true atau nonaktif 0/false; makna operasional mengikuti jenis tabel. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- Tidak ada foreign key eksplisit pada tabel ini.

#### Tabel `lot_beras` — Kelompok stok dengan harga beli tetap

Dibuat tiap penerimaan. jumlah_masuk_kg mencatat awal, sisa_kg berubah ketika keluar/koreksi. FIFO checkout memilih tanggal masuk lalu ID. tanggal_kedaluwarsa dapat disimpan tetapi belum dipakai filter FIFO aktif; penjelasan ini tidak menyatakan barang kedaluwarsa sudah otomatis diblokir.

**Kode yang menyebut tabel ini secara langsung:** [InventoryController.php](app/Http/Controllers/InventoryController.php), [AdminController.php](app/Http/Controllers/AdminController.php), [PanitiaController.php](app/Http/Controllers/PanitiaController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php), [OperationalReportController.php](app/Http/Controllers/OperationalReportController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `kode_lot` | `varchar(50)` | Tidak | Kode unik kelompok stok, prefix LOT. |
| `pembelian_beras_id` | `bigint(20) unsigned` | Tidak | Rujukan dokumen penerimaan vendor. |
| `katalog_beras_id` | `bigint(20) unsigned` | Tidak | Rujukan jenis beras. |
| `jumlah_masuk_kg` | `decimal(12,2)` | Tidak | Jumlah awal yang diterima pada lot. |
| `sisa_kg` | `decimal(12,2)` | Tidak | Saldo lot yang masih dicatat di gudang; sebelum pengurangan reservasi. |
| `harga_beli_per_kg` | `decimal(15,2)` | Tidak | Harga beli per kg lot; tetap untuk riwayat lot. |
| `tanggal_masuk` | `date` | Tidak | Tanggal lot masuk; dipakai urutan FIFO. |
| `tanggal_kedaluwarsa` | `date` | Ya | Tanggal kedaluwarsa opsional; belum difilter oleh checkout FIFO. |
| `is_simulation` | `tinyint(1)` | Tidak | Penanda data proses ini simulasi pengembangan. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `kode_lot`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `katalog_beras_id` → `katalog_beras.id`; saat induk dihapus: **RESTRICT**.
- FK `pembelian_beras_id` → `pembelian_beras.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `katalog_beras_id` + `tanggal_masuk`; `pembelian_beras_id`.

#### Tabel `mutasi_stok` — Riwayat masuk, keluar, dan koreksi

Jenis alur aktif: masuk_pembelian, keluar_pengiriman, koreksi_stok. perubahan_kg positif atau negatif; saldo_setelah_kg adalah sisa lot setelah tindakan. referensi berupa teks yang menghubungkan kejadian, bukan FK tambahan. Reservasi pesanan tidak membuat mutasi keluar.

**Kode yang menyebut tabel ini secara langsung:** [InventoryController.php](app/Http/Controllers/InventoryController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `lot_beras_id` | `bigint(20) unsigned` | Tidak | Rujukan lot yang digunakan. |
| `dicatat_oleh` | `bigint(20) unsigned` | Tidak | Rujukan akun pencatat. |
| `jenis` | `varchar(30)` | Tidak | Nama jenis kejadian/pesan; daftar bergantung tabel. |
| `perubahan_kg` | `decimal(12,2)` | Tidak | Perubahan stok lot, positif masuk dan negatif keluar/koreksi turun. |
| `saldo_setelah_kg` | `decimal(12,2)` | Tidak | Saldo lot setelah mutasi dilakukan. |
| `alasan` | `text` | Ya | Alasan koreksi persediaan. |
| `referensi` | `varchar(100)` | Ya | Rujukan teks kejadian seperti perjalanan_pengiriman:ID; bukan FK. |
| `is_simulation` | `tinyint(1)` | Tidak | Penanda data proses ini simulasi pengembangan. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `dicatat_oleh` → `user.id`; saat induk dihapus: **RESTRICT**.
- FK `lot_beras_id` → `lot_beras.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `dicatat_oleh`; `lot_beras_id` + `created_at`.

#### Tabel `riwayat_harga_katalog` — Riwayat pembentukan harga penawaran

Ditulis ketika penerimaan/koreksi/pengiriman/pengaturan margin membuat harga penawaran berubah. Menjelaskan pokok per kg, persen keuntungan, penawaran, waktu, dan penetap. Transaksi lama tetap memakai snapshotnya sendiri, bukan harga terbaru dari tabel ini.

**Kode yang menyebut tabel ini secara langsung:** [InventoryController.php](app/Http/Controllers/InventoryController.php), [AdminController.php](app/Http/Controllers/AdminController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `katalog_beras_id` | `bigint(20) unsigned` | Tidak | Rujukan jenis beras. |
| `harga_pokok_per_kg` | `decimal(15,2)` | Tidak | Harga beli lot acuan saat menetapkan katalog. |
| `persen_keuntungan` | `decimal(5,2)` | Tidak | Persentase margin beras untuk harga penawaran. |
| `harga_penawaran_per_kg` | `decimal(15,2)` | Tidak | Harga pokok per kg setelah ditambah keuntungan. |
| `berlaku_mulai` | `timestamp` | Tidak | Waktu mulai berlakunya tarif/harga. |
| `ditetapkan_oleh` | `bigint(20) unsigned` | Ya | Rujukan akun penetap aturan/harga. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `ditetapkan_oleh` → `user.id`; saat induk dihapus: **SET NULL**.
- FK `katalog_beras_id` → `katalog_beras.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `ditetapkan_oleh`; `katalog_beras_id` + `berlaku_mulai`.

### 7.3 Pesanan, perhitungan, dan pembayaran

#### Tabel `transaksi_zakat` — Master pesanan dan status

Header bersama fitrah dan maal: siapa memesan, panti mana, total, komponen harga, status pembayaran dan penyaluran. jenis_zakat memakai fitrah atau mal; UI sering mengeja maal. QR acak disimpan terenkripsi di qr_payload, sedangkan payment_gateway_ref belum dipakai integrasi nyata.

**Kode yang menyebut tabel ini secara langsung:** [TransaksiZakat.php](app/Models/TransaksiZakat.php), [PantiVerificationController.php](app/Http/Controllers/PantiVerificationController.php), [SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php), [YayasanController.php](app/Http/Controllers/YayasanController.php), [MaalCheckoutController.php](app/Http/Controllers/MaalCheckoutController.php), [LandingController.php](app/Http/Controllers/LandingController.php), [HandoverEvidenceController.php](app/Http/Controllers/HandoverEvidenceController.php), [PanitiaController.php](app/Http/Controllers/PanitiaController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php), [OperationalReportController.php](app/Http/Controllers/OperationalReportController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php), [MuzakkiController.php](app/Http/Controllers/MuzakkiController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `kode_transaksi` | `varchar(50)` | Tidak | Kode pesanan unik; ZF untuk fitrah dan ZM untuk maal. |
| `user_id` | `bigint(20) unsigned` | Tidak | Rujukan akun terkait. |
| `yayasan_id` | `bigint(20) unsigned` | Tidak | Rujukan panti terkait. |
| `jenis_zakat` | `varchar(10)` | Tidak | fitrah atau mal menurut penyimpanan alur aktif. |
| `harga_pokok_snapshot` | `decimal(18,2)` | Tidak | Pokok beras seluruh pesanan pada checkout; nol untuk maal. |
| `keuntungan_snapshot` | `decimal(18,2)` | Tidak | Keuntungan beras saat checkout; nol untuk maal. |
| `biaya_admin_snapshot` | `decimal(18,2)` | Tidak | Biaya admin checkout fitrah; nol pada maal aktif. |
| `ongkir_snapshot` | `decimal(18,2)` | Tidak | Ongkir checkout fitrah yang tetap pada transaksi lama. |
| `total_nominal` | `decimal(18,2)` | Tidak | Total tagihan rupiah: seluruh komponen fitrah atau total zakat wajib maal. |
| `mode_pembayaran` | `varchar(20)` | Tidak | Mode alur aktif simulation; belum ada gateway uang nyata. |
| `metode_pembayaran` | `varchar(50)` | Ya | Label metode QR simulasi pada alur saat ini. |
| `status_pembayaran` | `varchar(25)` | Tidak | pending, simulated_paid, cancelled, expired pada alur aktif. |
| `status_penyaluran` | `varchar(30)` | Tidak | Keadaan pekerjaan penyaluran, terpisah dari pembayaran. |
| `payment_gateway_ref` | `varchar(100)` | Ya | Tempat referensi gateway yang belum digunakan integrasi nyata. |
| `qr_payload` | `text` | Ya | Token acak terenkripsi untuk membentuk tautan QR, bukan gambar/foto pembayaran. |
| `catatan_doa` | `text` | Ya | Catatan doa opsional muzakki ketika pesan fitrah. |
| `expired_at` | `timestamp` | Ya | Waktu berakhir masa berlaku pembayaran pending/QR. |
| `paid_at` | `timestamp` | Ya | Waktu pembayaran simulasi dicatat. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `kode_transaksi`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `user_id` → `user.id`; saat induk dihapus: **RESTRICT**.
- FK `yayasan_id` → `yayasan.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `user_id` + `created_at`; `yayasan_id` + `jenis_zakat` + `status_pembayaran`.

#### Tabel `transaksi_fitrah` — Detail pesanan fitrah

Satu detail maksimum per master melalui transaksi_id unik. Menjelaskan produk, nama/harga/bobot/jarak snapshot, jiwa, berat, dan biaya beras. total_biaya_fitrah berisi pokok+keuntungan beras, belum termasuk admin/ongkir. Bukti aktif berada di bukti_serah_terima, bukan bukti_pict_id.

**Kode yang menyebut tabel ini secara langsung:** [TransaksiFitrah.php](app/Models/TransaksiFitrah.php), [SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php), [OperationalReportController.php](app/Http/Controllers/OperationalReportController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php), [MuzakkiController.php](app/Http/Controllers/MuzakkiController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `transaksi_id` | `bigint(20) unsigned` | Tidak | Rujukan master pesanan. |
| `katalog_beras_id` | `bigint(20) unsigned` | Tidak | Rujukan jenis beras. |
| `nama_beras_snapshot` | `varchar(100)` | Tidak | Nama beras ketika pesanan dibuat, tidak mengikuti perubahan katalog berikutnya. |
| `harga_per_kg_snapshot` | `decimal(15,2)` | Tidak | Rata-rata biaya beras (pokok+keuntungan) dibagi berat saat checkout, di luar admin/ongkir. |
| `bobot_per_jiwa_kg` | `decimal(6,2)` | Tidak | Beras per jiwa ketika checkout; snapshot pengaturan. |
| `jumlah_jiwa` | `int(10) unsigned` | Tidak | Jumlah orang yang dibayarkan pada pesanan fitrah. |
| `total_beras_kg` | `decimal(12,2)` | Tidak | Total berat fitrah untuk pesanan. |
| `total_biaya_fitrah` | `decimal(18,2)` | Tidak | Pokok+keuntungan beras; belum admin dan ongkir. |
| `jarak_jalan_km_snapshot` | `decimal(10,2)` | Ya | Jarak yang dipakai menghitung ongkir checkout. |
| `bukti_pict_id` | `varchar(255)` | Ya | Kolom bukti gambar lama, belum dipakai alur bukti dua pihak. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `transaksi_id`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `katalog_beras_id` → `katalog_beras.id`; saat induk dihapus: **RESTRICT**.
- FK `transaksi_id` → `transaksi_zakat.id`; saat induk dihapus: **CASCADE**.
- Indeks nonunik: `katalog_beras_id`.

#### Tabel `transaksi_mal` — Ringkasan perhitungan maal satu pesanan

Berisi jumlah aset dari seluruh subjek, acuan emas, nisab, dan jumlah zakat wajib yang sudah diuji per orang. Jangan menguji ulang kewajiban hanya memakai aset agregat: rincian per orang ada pada subjek_zakat_maal. Persentase 2,5 digunakan kode checkout; kolom persentase_zakat mempunyai default tersendiri.

**Kode yang menyebut tabel ini secara langsung:** [TransaksiMal.php](app/Models/TransaksiMal.php), [SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php), [MaalCheckoutController.php](app/Http/Controllers/MaalCheckoutController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `transaksi_id` | `bigint(20) unsigned` | Tidak | Rujukan master pesanan. |
| `harga_emas_id` | `bigint(20) unsigned` | Ya | Rujukan harga acuan emas. |
| `nilai_tabungan` | `decimal(18,2)` | Tidak | Jumlah tabungan rupiah seluruh subjek pada transaksi maal. |
| `emas_gram` | `decimal(10,2)` | Tidak | Berat emas dalam gram; pada ringkasan mal jumlah seluruh subjek. |
| `harga_emas_per_gram_snapshot` | `decimal(15,2)` | Tidak | Harga acuan emas per gram pada checkout, tidak berubah mengikuti harga baru. |
| `sumber_harga_emas_snapshot` | `varchar(255)` | Ya | Teks sumber harga emas pada saat checkout. |
| `nilai_investasi` | `decimal(18,2)` | Tidak | Jumlah investasi rupiah seluruh subjek pada transaksi maal. |
| `nilai_aset_dagang` | `decimal(18,2)` | Tidak | Nilai aset perdagangan rupiah. |
| `nilai_piutang` | `decimal(18,2)` | Tidak | Jumlah piutang rupiah seluruh subjek. |
| `nilai_utang` | `decimal(18,2)` | Tidak | Jumlah utang rupiah seluruh subjek. |
| `total_harta_bersih` | `decimal(18,2)` | Tidak | Ringkasan nilai aset bersih setelah utang; pada transaksi mal dijumlah dari tiap subjek. |
| `nilai_nisab` | `decimal(18,2)` | Tidak | Nilai ambang 85 gram emas saat checkout. |
| `wajib_zakat` | `tinyint(1)` | Tidak | Penanda ada kewajiban yang dihitung pada pesanan; penilaian dilakukan per subjek. |
| `persentase_zakat` | `decimal(5,2)` | Tidak | Persentase zakat maal; kode checkout menggunakan 2,5 persen. |
| `total_zakat_wajib` | `decimal(18,2)` | Tidak | Jumlah zakat wajib dari pemilik yang memenuhi syarat, bukan 2,5% agregat tanpa pemeriksaan per orang. |
| `bukti_pict_id` | `varchar(255)` | Ya | Kolom bukti gambar lama, belum dipakai alur bukti dua pihak. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `transaksi_id`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `harga_emas_id` → `harga_emas.id`; saat induk dihapus: **RESTRICT**.
- FK `transaksi_id` → `transaksi_zakat.id`; saat induk dihapus: **CASCADE**.
- Indeks nonunik: `harga_emas_id`.

#### Tabel `subjek_zakat_maal` — Draft keluarga dan snapshot pemilik harta

Dua penggunaan dalam satu tabel: draft transaksi_id=NULL dan is_snapshot=false; riwayat pesanan transaksi_id terisi dan is_snapshot=true. Pembuat draft mengganti seluruh draft akun dalam transaksi database. Checkout membuat baris snapshot baru per pemilik; aset istri/anak tidak langsung diuji nisab sebagai satu aset milik diri.

**Kode yang menyebut tabel ini secara langsung:** [CreateZakatReminders.php](app/Console/Commands/CreateZakatReminders.php), [SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php), [MaalCheckoutController.php](app/Http/Controllers/MaalCheckoutController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `user_id` | `bigint(20) unsigned` | Tidak | Rujukan akun terkait. |
| `transaksi_id` | `bigint(20) unsigned` | Ya | Rujukan master pesanan. |
| `hubungan` | `varchar(20)` | Tidak | diri, istri, atau anak menurut validasi. |
| `nama_tampilan` | `varchar(255)` | Tidak | Nama pemilik yang ditampilkan; kosong memakai Hamba Allah. |
| `tabungan` | `decimal(18,2)` | Tidak | Tabungan rupiah pemilik ini. |
| `emas_gram` | `decimal(10,2)` | Tidak | Berat emas dalam gram; pada ringkasan mal jumlah seluruh subjek. |
| `investasi` | `decimal(18,2)` | Tidak | Investasi rupiah pemilik ini. |
| `aset_dagang` | `decimal(18,2)` | Tidak | Aset dagang rupiah pemilik ini. |
| `piutang` | `decimal(18,2)` | Tidak | Nilai uang yang menjadi piutang pemilik. |
| `utang` | `decimal(18,2)` | Tidak | Nilai utang yang dikurangkan dari aset menurut rumus aplikasi. |
| `harga_emas_snapshot` | `decimal(15,2)` | Ya | Harga emas per gram yang dipakai snapshot pemilik. |
| `nisab_snapshot` | `decimal(18,2)` | Ya | Nilai nisab saat snapshot pemilik dihitung. |
| `harta_bersih` | `decimal(18,2)` | Tidak | Aset bersih pemilik ini; minimum nol. |
| `zakat_wajib` | `decimal(18,2)` | Tidak | Zakat yang dihitung untuk pemilik ini setelah uji nisab dan haul. |
| `tanggal_haul` | `date` | Ya | Tanggal acuan haul yang dicatat pemilik; pengingat draft memakai pengulangan bulan/hari. |
| `sudah_haul` | `tinyint(1)` | Tidak | Pernyataan pengguna bahwa haul terpenuhi, bukan hasil pembuktian otomatis. |
| `is_snapshot` | `tinyint(1)` | Tidak | true berarti riwayat transaksi; false berarti draft keluarga. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `transaksi_id` → `transaksi_zakat.id`; saat induk dihapus: **CASCADE**.
- FK `user_id` → `user.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `transaksi_id`; `user_id` + `is_snapshot`.

#### Tabel `reservasi_stok` — Stok lot yang terikat pesanan

Dibuat pada checkout fitrah, bukan pada pembayaran. Status active berarti tetap menahan stok untuk pending maupun simulated_paid. Pembatalan menjadi cancelled; kedaluwarsa menjadi expired; berangkat menjadi fulfilled. released_at mencatat saat berhenti menahan. Kombinasi transaksi dan lot unik.

**Kode yang menyebut tabel ini secara langsung:** [TransaksiZakat.php](app/Models/TransaksiZakat.php), [InventoryController.php](app/Http/Controllers/InventoryController.php), [SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php), [PanitiaController.php](app/Http/Controllers/PanitiaController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php), [OperationalReportController.php](app/Http/Controllers/OperationalReportController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `transaksi_id` | `bigint(20) unsigned` | Tidak | Rujukan master pesanan. |
| `lot_beras_id` | `bigint(20) unsigned` | Tidak | Rujukan lot yang digunakan. |
| `jumlah_kg` | `decimal(12,2)` | Tidak | Berat kg untuk reservasi, alokasi, titik, atau bukti sesuai tabel. |
| `harga_beli_per_kg_snapshot` | `decimal(15,2)` | Tidak | Harga beli lot yang dicadangkan saat checkout. |
| `status` | `varchar(20)` | Tidak | active menahan, fulfilled sudah keluar, cancelled/expired dilepas. |
| `released_at` | `timestamp` | Ya | Waktu reservasi berhenti active karena batal, expired, atau fulfilled. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `transaksi_id` + `lot_beras_id`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `lot_beras_id` → `lot_beras.id`; saat induk dihapus: **RESTRICT**.
- FK `transaksi_id` → `transaksi_zakat.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `lot_beras_id` + `status`.

#### Tabel `percobaan_pembayaran` — Token dan keadaan QR simulasi

Dibuat bersamaan dengan pesanan. token_hash adalah SHA-256 token acak; tidak sama dengan token mentah ataupun QR SVG. Controller scan/pay mencarinya untuk akses publik. status aktif: pending, simulated_paid, cancelled, expired. Tidak memuat nomor kartu bank.

**Kode yang menyebut tabel ini secara langsung:** [TransaksiZakat.php](app/Models/TransaksiZakat.php), [SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php), [MaalCheckoutController.php](app/Http/Controllers/MaalCheckoutController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `transaksi_id` | `bigint(20) unsigned` | Tidak | Rujukan master pesanan. |
| `token_hash` | `varchar(64)` | Tidak | SHA-256 token acak pembayaran; bukan token mentah. |
| `status` | `varchar(25)` | Tidak | pending, simulated_paid, cancelled, expired. |
| `expired_at` | `timestamp` | Tidak | Waktu berakhir masa berlaku pembayaran pending/QR. |
| `completed_at` | `timestamp` | Ya | Waktu percobaan pembayaran berhasil dicatat. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `token_hash`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `transaksi_id` → `transaksi_zakat.id`; saat induk dihapus: **CASCADE**.
- Indeks nonunik: `transaksi_id`.

#### Tabel `harga_emas` — Riwayat acuan harga emas

Command otomatis menambahkan harga emas dunia dan kurs USD/IDR setiap 09.30 WIB, dikonversi menjadi Rp/gram menggunakan BCMath. Input manual admin tersedia sebagai cadangan. Checkout mengambil ID catatan terakhir dan mensyaratkan kedua sumber tidak lebih tua dari tujuh hari; perubahan ID saat submit meminta pengguna meninjau ulang. Pesanan lama tetap menyimpan harga dan sumber snapshot. Detail operasional ada di [!wajib_baca.md Bagian 12](%21wajib_baca.md#12-harga-acuan-emas-dunia-otomatis-8-oktober-2026).

**Kode yang menyebut tabel ini secara langsung:** [AdminController.php](app/Http/Controllers/AdminController.php), [MaalCheckoutController.php](app/Http/Controllers/MaalCheckoutController.php), [SyncGoldPrice.php](app/Console/Commands/SyncGoldPrice.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `harga_per_gram` | `decimal(15,2)` | Tidak | Harga emas acuan rupiah per gram. |
| `sumber` | `varchar(255)` | Tidak | Nama/rujukan sumber harga acuan otomatis atau manual. |
| `tanggal_sumber` | `timestamp` | Tidak | Waktu acuan untuk menilai usia; pada API memakai waktu yang lebih tua dari emas dan awal tanggal kurs UTC. Acuan aktif dipilih berdasarkan ID terakhir. |
| `dicatat_oleh` | `bigint(20) unsigned` | Ya | Rujukan akun pencatat. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |
| `jenis_sumber` | `varchar(20)` | Tidak | Jenis acuan: manual atau api. |
| `harga_usd_per_troy_ounce` | `decimal(20,8)` | Ya | Harga emas dunia dalam USD per troy ounce, sebelum konversi. |
| `kurs_usd_idr` | `decimal(20,8)` | Ya | Rupiah per satu USD yang dipakai mengonversi harga emas. |
| `sumber_kurs` | `varchar(255)` | Ya | Nama sumber kurs, terpisah dari sumber emas. |
| `waktu_harga_emas` | `timestamp` | Ya | Timestamp sumber emas, disimpan UTC. |
| `tanggal_kurs` | `date` | Ya | Tanggal publikasi/data kurs, bukan waktu pengambilan. |
| `diambil_at` | `timestamp` | Ya | Timestamp pengambilan dan penyimpanan data oleh server, UTC. |
| `tanggal_sinkronisasi` | `date` | Ya | Tanggal WIB eksekusi otomatis; unik, NULL untuk manual. |

**Kunci dan hubungan:**

- Primary key: `id`.
- Unique: `tanggal_sinkronisasi` (NULL berulang diizinkan untuk input manual).
- FK `dicatat_oleh` → `user.id`; saat induk dihapus: **SET NULL**.
- Indeks nonunik: `dicatat_oleh`; `tanggal_sumber`.

### 7.4 Penyaluran dan aturan operasional

#### Tabel `perjalanan_pengiriman` — Satu perjalanan kendaraan fitrah

Dibuat panitia berisi gudang asal, kendaraan, kapasitas, jadwal, dan penanggung jawab pencatat. Status direncanakan → dikirim → selesai. Kapasitas adalah berat maksimum keseluruhan titik. ongkir_aktual merupakan total biaya titik setelah semuanya terisi, bukan tarif checkout.

**Kode yang menyebut tabel ini secara langsung:** [HandoverEvidenceController.php](app/Http/Controllers/HandoverEvidenceController.php), [PanitiaController.php](app/Http/Controllers/PanitiaController.php), [OperationalReportController.php](app/Http/Controllers/OperationalReportController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `kode_perjalanan` | `varchar(50)` | Tidak | Kode unik perjalanan, prefix KIRIM. |
| `gudang_id` | `bigint(20) unsigned` | Tidak | Rujukan gudang asal. |
| `panitia_id` | `bigint(20) unsigned` | Tidak | Rujukan akun panitia pencatat/pemeriksa. |
| `kendaraan` | `varchar(100)` | Tidak | Identitas/keterangan kendaraan yang diisi panitia. |
| `kapasitas_kg` | `decimal(12,2)` | Tidak | Batas muatan kendaraan seluruh tujuan. |
| `ongkir_aktual` | `decimal(18,2)` | Ya | Biaya operasional nyata yang dicatat untuk simulasi; terpisah dari ongkir tagihan. |
| `jadwal_berangkat` | `timestamp` | Tidak | Rencana waktu keberangkatan, input WIB disimpan UTC. |
| `berangkat_at` | `timestamp` | Ya | Waktu operator mencatat perjalanan berangkat. |
| `status` | `varchar(30)` | Tidak | direncanakan, dikirim, selesai. |
| `is_simulation` | `tinyint(1)` | Tidak | Penanda data proses ini simulasi pengembangan. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `kode_perjalanan`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `gudang_id` → `gudang.id`; saat induk dihapus: **RESTRICT**.
- FK `panitia_id` → `user.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `gudang_id`; `panitia_id`.

#### Tabel `titik_pengiriman` — Satu panti tujuan dalam perjalanan

Mengelompokkan beberapa alokasi pesanan untuk panti yang sama pada perjalanan. urutan menentukan urutan manifest, bukan hasil optimasi peta. jumlah_kg adalah total muatan tujuan. Status dijadwalkan → dikirim → diterima. tiba_at dicatat saat bukti panitia; diterima_at ketika semua alokasi titik selesai.

**Kode yang menyebut tabel ini secara langsung:** [HandoverEvidenceController.php](app/Http/Controllers/HandoverEvidenceController.php), [PanitiaController.php](app/Http/Controllers/PanitiaController.php), [OperationalReportController.php](app/Http/Controllers/OperationalReportController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `perjalanan_pengiriman_id` | `bigint(20) unsigned` | Tidak | Rujukan perjalanan kendaraan. |
| `yayasan_id` | `bigint(20) unsigned` | Tidak | Rujukan panti terkait. |
| `urutan` | `int(10) unsigned` | Tidak | Urutan tujuan pada manifest satu perjalanan. |
| `jumlah_kg` | `decimal(12,2)` | Tidak | Berat kg untuk reservasi, alokasi, titik, atau bukti sesuai tabel. |
| `ongkir_aktual` | `decimal(18,2)` | Ya | Biaya operasional nyata yang dicatat untuk simulasi; terpisah dari ongkir tagihan. |
| `status` | `varchar(30)` | Tidak | dijadwalkan, dikirim, diterima. |
| `tiba_at` | `timestamp` | Ya | Waktu kedatangan yang dicatat ketika panitia mengunggah bukti. |
| `diterima_at` | `timestamp` | Ya | Waktu seluruh alokasi tujuan dinyatakan selesai. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `perjalanan_pengiriman_id` + `urutan`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `perjalanan_pengiriman_id` → `perjalanan_pengiriman.id`; saat induk dihapus: **RESTRICT**.
- FK `yayasan_id` → `yayasan.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `yayasan_id`.

#### Tabel `alokasi_transaksi` — Hubungan pesanan dengan penyerahan

Fitrah mengisi titik_pengiriman_id dan jumlah_kg; maal mengisi nominal dengan titik kosong. Alur aktif menolak alokasi kedua untuk satu pesanan melalui controller, tetapi transaksi_id di tabel bukan UNIQUE tunggal. status mengikuti proses penyaluran; is_simulation=true pada pembuatan.

**Kode yang menyebut tabel ini secara langsung:** [SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php), [YayasanController.php](app/Http/Controllers/YayasanController.php), [LandingController.php](app/Http/Controllers/LandingController.php), [HandoverEvidenceController.php](app/Http/Controllers/HandoverEvidenceController.php), [PanitiaController.php](app/Http/Controllers/PanitiaController.php), [OperationalReportController.php](app/Http/Controllers/OperationalReportController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `transaksi_id` | `bigint(20) unsigned` | Tidak | Rujukan master pesanan. |
| `yayasan_id` | `bigint(20) unsigned` | Tidak | Rujukan panti terkait. |
| `titik_pengiriman_id` | `bigint(20) unsigned` | Ya | Rujukan tujuan dalam perjalanan. |
| `jumlah_kg` | `decimal(12,2)` | Ya | Berat kg untuk reservasi, alokasi, titik, atau bukti sesuai tabel. |
| `nominal` | `decimal(18,2)` | Ya | Nilai rupiah alokasi/bukti maal; tidak untuk berat fitrah. |
| `status` | `varchar(30)` | Tidak | dijadwalkan, dikirim, menunggu_bukti, perlu_tinjauan, selesai. |
| `is_simulation` | `tinyint(1)` | Tidak | Penanda data proses ini simulasi pengembangan. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `titik_pengiriman_id` → `titik_pengiriman.id`; saat induk dihapus: **RESTRICT**.
- FK `transaksi_id` → `transaksi_zakat.id`; saat induk dihapus: **RESTRICT**.
- FK `yayasan_id` → `yayasan.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `titik_pengiriman_id`; `transaksi_id` + `status`; `yayasan_id`.

#### Tabel `bukti_serah_terima` — Bukti dari panitia dan yayasan

Satu alokasi maksimum satu baris per pihak melalui UNIQUE(alokasi_transaksi_id,pihak). Pihak PJ disimpan dengan nilai yayasan. jumlah_sesuai hasil membandingkan angka form dengan alokasi, bukan pemeriksaan isi gambar. Foto ada di storage/app/private/bukti-serah-terima.

**Kode yang menyebut tabel ini secara langsung:** [SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php), [YayasanController.php](app/Http/Controllers/YayasanController.php), [HandoverEvidenceController.php](app/Http/Controllers/HandoverEvidenceController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `alokasi_transaksi_id` | `bigint(20) unsigned` | Tidak | Rujukan alokasi penyerahan. |
| `diunggah_oleh` | `bigint(20) unsigned` | Tidak | Rujukan akun pengunggah. |
| `pihak` | `varchar(20)` | Tidak | Pemilik bukti: panitia atau yayasan; PJ menggunakan nilai yayasan. |
| `path_private` | `varchar(255)` | Tidak | Lokasi relatif berkas pada disk local privat; database menyimpan path, bukan byte gambar. |
| `jumlah_kg` | `decimal(12,2)` | Ya | Berat kg untuk reservasi, alokasi, titik, atau bukti sesuai tabel. |
| `nominal` | `decimal(18,2)` | Ya | Nilai rupiah alokasi/bukti maal; tidak untuk berat fitrah. |
| `jumlah_sesuai` | `tinyint(1)` | Ya | Hasil perbandingan angka bukti terhadap alokasi. |
| `catatan` | `text` | Ya | Catatan tambahan tindakan/bukti/pemeriksaan. |
| `is_simulation` | `tinyint(1)` | Tidak | Penanda data proses ini simulasi pengembangan. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `alokasi_transaksi_id` + `pihak`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `alokasi_transaksi_id` → `alokasi_transaksi.id`; saat induk dihapus: **RESTRICT**.
- FK `diunggah_oleh` → `user.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `diunggah_oleh`.

#### Tabel `penyaluran` — Hasil penyaluran yang dinyatakan selesai

Ditulis HandoverEvidenceController setelah bukti panitia dan yayasan lengkap dan sesuai. Fitrah jumlah memakai kg; mal memakai rupiah. mustahik_id opsional sesuai agregasi tugas. Status alur baru selesai; accessor status pada model lama berbeda, sehingga laporan aktif memakai query tabel langsung.

**Kode yang menyebut tabel ini secara langsung:** [Penyaluran.php](app/Models/Penyaluran.php), [MaalCheckoutController.php](app/Http/Controllers/MaalCheckoutController.php), [LandingController.php](app/Http/Controllers/LandingController.php), [HandoverEvidenceController.php](app/Http/Controllers/HandoverEvidenceController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php), [OperationalReportController.php](app/Http/Controllers/OperationalReportController.php), [MuzakkiController.php](app/Http/Controllers/MuzakkiController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `yayasan_id` | `bigint(20) unsigned` | Tidak | Rujukan panti terkait. |
| `mustahik_id` | `bigint(20) unsigned` | Ya | Rujukan penerima individu jika diisi. |
| `alokasi_transaksi_id` | `bigint(20) unsigned` | Ya | Rujukan alokasi penyerahan. |
| `jenis_zakat` | `varchar(10)` | Tidak | fitrah atau mal menurut penyimpanan alur aktif. |
| `barang` | `varchar(100)` | Tidak | Nama bantuan: beras fitrah atau dana maal simulasi. |
| `jumlah` | `decimal(18,2)` | Tidak | Besar bantuan; baca satuan untuk membedakan kg dan rupiah. |
| `satuan` | `varchar(20)` | Tidak | Satuan bantuan, kg atau rupiah pada alur aktif. |
| `status` | `varchar(30)` | Tidak | Keadaan hasil penyaluran; controller aktif menulis selesai. |
| `bukti_pict_id` | `varchar(255)` | Ya | Kolom bukti gambar lama, belum dipakai alur bukti dua pihak. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `alokasi_transaksi_id` → `alokasi_transaksi.id`; saat induk dihapus: **RESTRICT**.
- FK `mustahik_id` → `mustahik.id`; saat induk dihapus: **RESTRICT**.
- FK `yayasan_id` → `yayasan.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `alokasi_transaksi_id`; `mustahik_id`; `yayasan_id`.

#### Tabel `wilayah_layanan` — Zona pengiriman fitrah dari gudang

Enam provinsi Jawa awal diisi seeder dalam keadaan nonaktif. hari_batas_fitrah menentukan H-berapa checkout ditutup; batas_checkout_at hasil pengaturan Idulfitri. Satu zona bukan semua kota sudah pasti terlayani; panti juga harus diverifikasi rute/jarak/kapasitasnya.

**Kode yang menyebut tabel ini secara langsung:** [PantiVerificationController.php](app/Http/Controllers/PantiVerificationController.php), [AdminController.php](app/Http/Controllers/AdminController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `gudang_id` | `bigint(20) unsigned` | Tidak | Rujukan gudang asal. |
| `nama` | `varchar(100)` | Tidak | Nama yang ditampilkan untuk entitas tabel ini. |
| `provinsi` | `varchar(100)` | Tidak | Nama provinsi; digunakan juga untuk mencocokkan wilayah panti. |
| `hari_batas_fitrah` | `tinyint(3) unsigned` | Tidak | Jumlah hari sebelum tanggal Idulfitri untuk menutup checkout zona. |
| `batas_checkout_at` | `timestamp` | Ya | Batas waktu pemesanan fitrah zona. |
| `is_active` | `tinyint(1)` | Tidak | Penanda aktif 1/true atau nonaktif 0/false; makna operasional mengikuti jenis tabel. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `gudang_id` + `provinsi`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `gudang_id` → `gudang.id`; saat induk dihapus: **RESTRICT**.

#### Tabel `tarif_pengiriman` — Riwayat tarif per zona

Rumus biaya_tetap + biaya_per_km_kg × jarak × berat. Saat tarif berubah, tarif terbuka lama ditutup dengan berlaku_sampai dan ditambah baris baru. Checkout memilih tarif yang sudah mulai dan belum berakhir. Tidak ada API ongkir ekspedisi.

**Kode yang menyebut tabel ini secara langsung:** [AdminController.php](app/Http/Controllers/AdminController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `wilayah_layanan_id` | `bigint(20) unsigned` | Tidak | Rujukan zona fitrah. |
| `biaya_tetap` | `decimal(15,2)` | Tidak | Komponen tarif tetap per pesanan fitrah. |
| `biaya_per_km_kg` | `decimal(15,2)` | Tidak | Tarif rupiah per kilometer per kilogram. |
| `berlaku_mulai` | `timestamp` | Tidak | Waktu mulai berlakunya tarif/harga. |
| `berlaku_sampai` | `timestamp` | Ya | Waktu akhir tarif lama; NULL berarti interval masih terbuka. |
| `ditetapkan_oleh` | `bigint(20) unsigned` | Ya | Rujukan akun penetap aturan/harga. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `ditetapkan_oleh` → `user.id`; saat induk dihapus: **SET NULL**.
- FK `wilayah_layanan_id` → `wilayah_layanan.id`; saat induk dihapus: **RESTRICT**.
- Indeks nonunik: `ditetapkan_oleh`; `wilayah_layanan_id` + `berlaku_mulai`.

#### Tabel `pengaturan_operasional` — Nilai aturan dan teks publik

Pola key-value: kunci unik, nilai berupa string. Berisi biaya_admin_rupiah, persen_keuntungan_beras, bobot_fitrah_per_jiwa_kg, tanggal_idulfitri, serta landing_judul/pengantar/tentang/faq_judul/faq_jawaban. Controller mengubahnya melalui upsert. Tipe kolom nilai tidak otomatis memastikan isinya angka; validasi form menentukan aturan.

**Kode yang menyebut tabel ini secara langsung:** [CreateZakatReminders.php](app/Console/Commands/CreateZakatReminders.php), [InventoryController.php](app/Http/Controllers/InventoryController.php), [AdminController.php](app/Http/Controllers/AdminController.php), [LandingController.php](app/Http/Controllers/LandingController.php), [FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php), [DistributionController.php](app/Http/Controllers/DistributionController.php), [MuzakkiController.php](app/Http/Controllers/MuzakkiController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `kunci` | `varchar(100)` | Tidak | Nama pengaturan yang unik. |
| `nilai` | `text` | Tidak | Isi pengaturan berbentuk string; controller menentukan cara membacanya. |
| `satuan` | `varchar(30)` | Ya | Label satuan pengaturan, misalnya kg/persen/rupiah/tanggal; opsional. |
| `diubah_oleh` | `bigint(20) unsigned` | Ya | Rujukan akun pengubah. |
| `created_at` | `timestamp` | Ya | Waktu baris dibuat. |
| `updated_at` | `timestamp` | Ya | Waktu baris terakhir diperbarui. |

**Kunci dan hubungan:**

- Primary key: `id`.
- UNIQUE: `kunci`. Jika beberapa kolom digabung, yang harus unik adalah kombinasinya.
- FK `diubah_oleh` → `user.id`; saat induk dihapus: **SET NULL**.
- Indeks nonunik: `diubah_oleh`.

#### Tabel `audit_log` — Catatan tindakan penting

Berisi pelaku opsional, aksi, tipe/ID objek, JSON detail dan IP. Pembayaran QR publik memakai actor_user_id=NULL karena tidak memerlukan akun. jenis_objek/objek_id adalah rujukan generik berbentuk teks/ID, bukan FK ke tiap tabel. Tidak semua perubahan memiliki catatan audit khusus; ini bukan salinan seluruh riwayat SQL.

**Kode yang menyebut tabel ini secara langsung:** [InventoryController.php](app/Http/Controllers/InventoryController.php), [PantiVerificationController.php](app/Http/Controllers/PantiVerificationController.php), [SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php), [AdminController.php](app/Http/Controllers/AdminController.php), [MaalCheckoutController.php](app/Http/Controllers/MaalCheckoutController.php), [BankChangeRequestController.php](app/Http/Controllers/BankChangeRequestController.php), [MuzakkiController.php](app/Http/Controllers/MuzakkiController.php). Daftar ini mencakup pembacaan maupun penulisan; relasi Eloquent dapat mengaksesnya juga tanpa menyebut string tabel.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `bigint(20) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `actor_user_id` | `bigint(20) unsigned` | Ya | Rujukan akun pelaku bila ada. |
| `aksi` | `varchar(100)` | Tidak | Nama tindakan audit, misalnya panti.diverifikasi. |
| `jenis_objek` | `varchar(100)` | Tidak | Nama jenis objek yang diperiksa audit. |
| `objek_id` | `bigint(20) unsigned` | Ya | ID objek audit, rujukan generik tanpa FK ke setiap jenis objek. |
| `detail` | `longtext` | Ya | Rincian JSON tindakan audit. |
| `ip_address` | `varchar(45)` | Ya | IP permintaan/perangkat; konteks tergantung tabel. |
| `created_at` | `timestamp` | Tidak | Waktu baris dibuat. |

**Kunci dan hubungan:**

- Primary key: `id`.
- FK `actor_user_id` → `user.id`; saat induk dihapus: **SET NULL**.
- Indeks nonunik: `actor_user_id`; `jenis_objek` + `objek_id`.
- CHECK memeriksa bahwa kolom JSON berisi JSON yang sah; tidak membuktikan isi bisnis benar.

### 7.5 Infrastruktur Laravel

Hanya `migrations` yang tetap tersedia. Session/cache memakai file, queue memakai `sync`, dan penyimpanan failed jobs memakai `null`. Tujuh tabel backend database yang tidak dipakai telah dihapus melalui migration batch 6; buat kembali tabel pendukung sebelum mengaktifkan backend database atau batching.

#### Tabel `migrations` — Riwayat migration yang sudah dijalankan

Dikelola Laravel berisi nama migration dan batch. Tidak menyimpan source migration atau isi tabel. ID/batch ini membantu menentukan yang pending dan kelompok rollback; bukan kode pesanan.

| Kolom | Tipe aktual | Boleh NULL | Arti dan penggunaan |
| --- | --- | --- | --- |
| `id` | `int(10) unsigned` | Tidak | Identitas unik baris dalam tabel ini; bukan nama atau urutan proses. ID dibuat otomatis oleh database. |
| `migration` | `varchar(255)` | Tidak | Nama file migration yang sudah dijalankan tanpa ekstensi. |
| `batch` | `int(11)` | Tidak | Kelompok eksekusi migration untuk rollback. |

**Kunci dan hubungan:**

- Primary key: `id`.
- Tidak ada foreign key eksplisit pada tabel ini.

## 8. Peta route aktif

Route berikut diperoleh dari `php artisan route:list --json --except-vendor` pada tanggal acuan. GET dipakai membuka halaman; POST membuat/melakukan tindakan; PATCH/PUT memperbarui. HEAD ikut tersedia pada route GET. Tabel memakai path relatif terhadap domain aplikasi sehingga tidak mengunci domain tertentu.

`web` menyediakan session dan CSRF. `auth` mensyaratkan login. `guest` untuk yang belum login. `role:...` diperiksa EnsureRole. `throttle:5,1` membatasi percobaan route terkait dalam jendela satu menit. Ownership/foto/validitas token juga diperiksa controller, bukan hanya nama URL.

| Metode | Path relatif | Nama route | Fungsi | Pemeriksaan middleware |
| --- | --- | --- | --- | --- |
| GET / HEAD | `/` | `landing` | `LandingController@index` | `web` |
| GET / HEAD | `/admin` | `admin.dashboard` | `AdminController@index` | `web`, `auth`, `role:admin` |
| GET / HEAD | `/admin/audit` | `admin.audit.index` | `AdminController@auditLog` | `web`, `auth`, `role:admin` |
| POST | `/admin/bank` | `admin.bank.store` | `AdminController@storeBank` | `web`, `auth`, `role:admin` |
| PATCH | `/admin/bank/{id}/aktif` | `admin.bank.active` | `AdminController@setBankActive` | `web`, `auth`, `role:admin` |
| POST | `/admin/harga-emas` | `admin.harga-emas.store` | `AdminController@storeGoldPrice` | `web`, `auth`, `role:admin` |
| PUT | `/admin/konten-publik` | `admin.konten.update` | `AdminController@setLandingContent` | `web`, `auth`, `role:admin` |
| POST | `/admin/panitia` | `admin.panitia.store` | `AdminController@storePanitia` | `web`, `auth`, `role:admin` |
| PATCH | `/admin/panitia/{user}/aktif` | `admin.panitia.active` | `AdminController@setPanitiaActive` | `web`, `auth`, `role:admin` |
| PUT | `/admin/pengaturan` | `admin.pengaturan.update` | `AdminController@setOperationalSettings` | `web`, `auth`, `role:admin` |
| PUT | `/admin/wilayah/{id}` | `admin.wilayah.update` | `AdminController@setRegion` | `web`, `auth`, `role:admin` |
| GET / HEAD | `/bukti/foto/{id}` | `bukti.foto` | `HandoverEvidenceController@foto` | `web`, `auth` |
| GET / HEAD | `/ganti-kata-sandi` | `password.change` | `AuthController@showChangePassword` | `web`, `auth` |
| POST | `/ganti-kata-sandi` | `password.change.store` | `AuthController@changePassword` | `web`, `auth` |
| GET / HEAD | `/home` | `home` | `Closure` | `web`, `auth` |
| GET / HEAD | `/login` | `login` | `AuthController@showLogin` | `web`, `guest` |
| POST | `/login` | `login.post` | `AuthController@login` | `web`, `guest`, `throttle:5,1` |
| POST | `/logout` | `logout` | `AuthController@logout` | `web`, `auth` |
| GET / HEAD | `/muzakki/bayar-uji/{token}` | `muzakki.payment.scan` | `SimulationPaymentController@scan` | `web` |
| POST | `/muzakki/bayar-uji/{token}` | `muzakki.payment.pay` | `SimulationPaymentController@pay` | `web`, `throttle:5,1` |
| GET / HEAD | `/muzakki/dashboard` | `muzakki.dashboard` | `MuzakkiController@index` | `web`, `auth`, `role:pemberi` |
| GET / HEAD | `/muzakki/pembayaran/{id}` | `muzakki.payment.show` | `SimulationPaymentController@show` | `web`, `auth`, `role:pemberi` |
| POST | `/muzakki/pembayaran/{id}/batalkan` | `muzakki.payment.cancel` | `SimulationPaymentController@cancel` | `web`, `auth`, `role:pemberi` |
| GET / HEAD | `/muzakki/profil` | `muzakki.profil` | `MuzakkiController@profil` | `web`, `auth`, `role:pemberi` |
| POST | `/muzakki/profil` | `muzakki.profil.update` | `MuzakkiController@updateProfil` | `web`, `auth`, `role:pemberi` |
| POST | `/muzakki/profil/preferensi-notifikasi` | `muzakki.profil.notifikasi` | `MuzakkiController@updateNotificationPreferences` | `web`, `auth`, `role:pemberi` |
| GET / HEAD | `/muzakki/riwayat` | `muzakki.riwayat` | `MuzakkiController@riwayat` | `web`, `auth`, `role:pemberi` |
| GET / HEAD | `/muzakki/zakat-fitrah` | `muzakki.fitrah` | `FitrahCheckoutController@index` | `web`, `auth`, `role:pemberi` |
| POST | `/muzakki/zakat-fitrah` | `muzakki.fitrah.store` | `FitrahCheckoutController@store` | `web`, `auth`, `role:pemberi` |
| GET / HEAD | `/muzakki/zakat-maal` | `muzakki.maal` | `MaalCheckoutController@index` | `web`, `auth`, `role:pemberi` |
| POST | `/muzakki/zakat-maal` | `muzakki.maal.store` | `MaalCheckoutController@store` | `web`, `auth`, `role:pemberi` |
| POST | `/muzakki/zakat-maal/draft` | `muzakki.maal.draft` | `MaalCheckoutController@saveDraft` | `web`, `auth`, `role:pemberi` |
| DELETE | `/muzakki/zakat-maal/draft` | `muzakki.maal.draft.destroy` | `MaalCheckoutController@destroyDraft` | `web`, `auth`, `role:pemberi` |
| POST | `/notifikasi/{id}/dibaca` | `notifikasi.read` | `InAppNotificationController@markRead` | `web`, `auth` |
| GET / HEAD | `/panitia/bukti` | `panitia.bukti.index` | `HandoverEvidenceController@index` | `web`, `auth`, `role:panitia` |
| POST | `/panitia/bukti/{id}` | `panitia.bukti.store` | `HandoverEvidenceController@submitPanitia` | `web`, `auth`, `role:panitia` |
| GET / HEAD | `/panitia/dashboard` | `panitia.dashboard` | `PanitiaController@index` | `web`, `auth`, `role:panitia` |
| GET / HEAD | `/panitia/distribusi` | `panitia.distribusi.index` | `DistributionController@index` | `web`, `auth`, `role:panitia` |
| GET / HEAD | `/panitia/gudang` | `panitia.gudang.index` | `InventoryController@index` | `web`, `auth`, `role:panitia` |
| POST | `/panitia/katalog` | `panitia.katalog.store` | `InventoryController@storeKatalog` | `web`, `auth`, `role:panitia` |
| DELETE | `/panitia/katalog/{id}` | `panitia.katalog.destroy` | `InventoryController@destroyKatalog` | `web`, `auth`, `role:panitia` |
| GET / HEAD | `/panitia/laporan` | `panitia.laporan.index` | `OperationalReportController@index` | `web`, `auth`, `role:panitia` |
| PATCH | `/panitia/lot/{id}/koreksi` | `panitia.lot.adjust` | `InventoryController@adjust` | `web`, `auth`, `role:panitia` |
| POST | `/panitia/maal/{id}/alokasi` | `panitia.maal.allocate` | `DistributionController@allocateMaal` | `web`, `auth`, `role:panitia` |
| GET / HEAD | `/panitia/panti` | `panitia.panti.index` | `PantiVerificationController@index` | `web`, `auth`, `role:panitia` |
| PATCH | `/panitia/panti/{id}` | `panitia.panti.update` | `PantiVerificationController@update` | `web`, `auth`, `role:panitia` |
| POST | `/panitia/pembelian/terima` | `panitia.pembelian.receive` | `InventoryController@receive` | `web`, `auth`, `role:panitia` |
| POST | `/panitia/pembelian/{id}/faktur` | `panitia.pembelian.faktur.store` | `InventoryController@uploadInvoice` | `web`, `auth`, `role:panitia` |
| GET / HEAD | `/panitia/pembelian/{id}/faktur` | `panitia.pembelian.faktur.show` | `InventoryController@invoice` | `web`, `auth`, `role:panitia` |
| POST | `/panitia/perjalanan` | `panitia.perjalanan.store` | `DistributionController@createTrip` | `web`, `auth`, `role:panitia` |
| DELETE | `/panitia/perjalanan/{id}` | `panitia.perjalanan.destroy` | `DistributionController@destroyTrip` | `web`, `auth`, `role:panitia` |
| POST | `/panitia/perjalanan/{id}/berangkat` | `panitia.perjalanan.dispatch` | `DistributionController@dispatch` | `web`, `auth`, `role:panitia` |
| GET / HEAD | `/panitia/perjalanan/{id}/manifest` | `panitia.perjalanan.manifest` | `DistributionController@manifest` | `web`, `auth`, `role:panitia` |
| GET / HEAD | `/panitia/rekening` | `panitia.rekening.index` | `BankChangeRequestController@index` | `web`, `auth`, `role:panitia` |
| PATCH | `/panitia/rekening/{id}` | `panitia.rekening.review` | `BankChangeRequestController@review` | `web`, `auth`, `role:panitia` |
| POST | `/panitia/titik` | `panitia.titik.store` | `DistributionController@addStop` | `web`, `auth`, `role:panitia` |
| PATCH | `/panitia/titik/{id}/ongkir` | `panitia.titik.ongkir` | `DistributionController@setActualShipping` | `web`, `auth`, `role:panitia` |
| POST | `/panitia/vendor` | `panitia.vendor.store` | `InventoryController@storeVendor` | `web`, `auth`, `role:panitia` |
| PATCH | `/panitia/vendor/{id}` | `panitia.vendor.update` | `InventoryController@updateVendor` | `web`, `auth`, `role:panitia` |
| DELETE | `/panitia/vendor/{id}` | `panitia.vendor.destroy` | `InventoryController@destroyVendor` | `web`, `auth`, `role:panitia` |
| GET / HEAD | `/panti/foto/{id}` | `panti.foto` | `PantiVerificationController@foto` | `web`, `auth` |
| GET / HEAD | `/register` | `register` | `AuthController@showRegister` | `web`, `guest` |
| POST | `/register` | `register.post` | `AuthController@register` | `web`, `guest`, `throttle:5,1` |
| POST | `/yayasan/bukti/{id}` | `yayasan.bukti.store` | `HandoverEvidenceController@submitPj` | `web`, `auth`, `role:pj` |
| POST | `/yayasan/daftar-panti` | `yayasan.panti.store` | `YayasanController@storePanti` | `web`, `auth`, `role:pj` |
| PATCH | `/yayasan/daftar-panti` | `yayasan.panti.resubmit` | `YayasanController@resubmitPanti` | `web`, `auth`, `role:pj` |
| GET / HEAD | `/yayasan/dashboard` | `yayasan.dashboard` | `YayasanController@dashboard` | `web`, `auth`, `role:pj` |
| POST | `/yayasan/rekening` | `yayasan.rekening.store` | `BankChangeRequestController@store` | `web`, `auth`, `role:pj` |

Route `/home` adalah closure pengarah menurut role, bukan render file home.blade.php. Health check `/up` dikonfigurasi bootstrap sebagai pemeriksaan aplikasi; tidak muncul dalam inventaris di atas yang mengecualikan route vendor. Route foto membutuhkan login dan pemeriksaan hak objek. Akun admin tidak langsung lolos `role:panitia`.

## 9. Controller: fungsi setiap file dan method

Controller menerima request setelah route/middleware lolos. Pada proyek ini validasi banyak ditulis langsung dalam controller. Angka pada tautan menunjuk awal method saat panduan dibuat; bila file berubah, cari nama methodnya.

### AdminController.php

**File:** [app/Http/Controllers/AdminController.php](app/Http/Controllers/AdminController.php).

Panel pengaturan internal. Mengelola panitia, bank, aturan biaya/tenggat, harga emas, dan konten landing. Operasi gudang/panti/pengiriman berada di controller panitia lainnya.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/AdminController.php:18) | Membaca panitia, bank, wilayah dan tarif, pengaturan, harga emas terbaru, serta teks publik. Menghitung apakah zona dapat diaktifkan. Merender admin.dashboard. |
| [`auditLog()`](app/Http/Controllers/AdminController.php:46) | Membaca catatan audit dengan nama/role pelaku, paginasi 30 baris. View tidak menampilkan seluruh detail JSON/identitas pribadi secara otomatis. |
| [`setLandingContent()`](app/Http/Controllers/AdminController.php:57) | Memvalidasi lima field teks publik, melakukan upsert pengaturan dan audit dalam satu transaksi, kemudian kembali ke dashboard. |
| [`storePanitia()`](app/Http/Controllers/AdminController.php:93) | Memvalidasi nama/email/password, membuat User role panitia dengan hash dan must_change_password=true, menghubungkan pembuat admin, lalu mencatat audit. |
| [`setPanitiaActive()`](app/Http/Controllers/AdminController.php:126) | Memastikan target User role panitia, mengubah is_active dan mencatat audit. Menonaktifkan tidak menghapus akun atau riwayatnya. |
| [`storeBank()`](app/Http/Controllers/AdminController.php:149) | Menambah kode bank unik dengan karakter huruf kapital/angka/_/- dan nama; membuat audit. |
| [`setBankActive()`](app/Http/Controllers/AdminController.php:179) | Mengunci bank, mengubah aktif/nonaktif, mencatat audit. Rekening panti yang sudah ada tetap tercatat. |
| [`setOperationalSettings()`](app/Http/Controllers/AdminController.php:206) | Memvalidasi tanggal Idulfitri, admin, margin, dan bobot; upsert semua pengaturan; menghitung ulang batas checkout UTC; menonaktifkan zona lewat; memperbarui harga katalog berdasarkan lot tertua dan riwayat harga. Tidak mengubah snapshot pesanan lama. |
| [`setRegion()`](app/Http/Controllers/AdminController.php:275) | Memvalidasi status dan tarif; menolak aktivasi zona tanpa tenggat masa depan. Mengubah aktif dan hanya membuat versi tarif baru jika nilainya berbeda, dengan menutup periode tarif lama. |
| [`storeGoldPrice()`](app/Http/Controllers/AdminController.php:320) | Memvalidasi harga/sumber/tanggal tidak di masa depan; menambah riwayat emas, tanggal sumber jam 12 WIB dikonversi UTC. Tidak mengambil harga dari internet. |

### AuthController.php

**File:** [app/Http/Controllers/AuthController.php](app/Http/Controllers/AuthController.php).

Registrasi, autentikasi, logout, dan pergantian kata sandi. Eloquent User diperlukan karena nama kolom akun berbeda dari bawaan Laravel.

| Method | Tugas dan hasil |
| --- | --- |
| [`showLogin()`](app/Http/Controllers/AuthController.php:16) | Mengirim template auth.login. |
| [`showRegister()`](app/Http/Controllers/AuthController.php:21) | Mengirim template auth.register. |
| [`register()`](app/Http/Controllers/AuthController.php:26) | Memvalidasi biodata/akun, menyimpan hash password dan role yang diizinkan, login otomatis, regenerate session, redirect home. |
| [`login()`](app/Http/Controllers/AuthController.php:64) | Memvalidasi email/password, Auth::attempt dengan is_active=true. Jika gagal mengembalikan pesan umum; jika berhasil memperbarui session dan redirect home. |
| [`logout()`](app/Http/Controllers/AuthController.php:80) | Keluar dari Auth, invalidate session, regenerate token CSRF, redirect login. |
| [`showChangePassword()`](app/Http/Controllers/AuthController.php:89) | Menampilkan auth.change-password untuk akun login. |
| [`changePassword()`](app/Http/Controllers/AuthController.php:94) | Memeriksa password sekarang dan password baru minimal 12 karakter, menyimpan hash baru, menghapus kewajiban must_change_password, memperbarui session, redirect home. |

### BankChangeRequestController.php

**File:** [app/Http/Controllers/BankChangeRequestController.php](app/Http/Controllers/BankChangeRequestController.php).

Mengatur proses permohonan rekening supaya PJ tidak langsung menimpa rekening aktif.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/BankChangeRequestController.php:13) | Membaca permohonan beserta rekening lama panti, bank baru dan PJ; yang pending didahulukan; paginasi 20. Merender panitia.rekening. |
| [`store()`](app/Http/Controllers/BankChangeRequestController.php:27) | Memastikan PJ punya panti, bank aktif, tidak ada permintaan pending, dan rekening berbeda. Menyimpan permohonan dan audit, tidak mengganti rekening aktif. |
| [`review()`](app/Http/Controllers/BankChangeRequestController.php:67) | Mengunci permohonan/panti dan memastikan pending. Jika approved, memeriksa bank masih aktif dan menyalin rekening ke yayasan. Menyimpan keputusan, pemeriksa/waktu/catatan dan audit. |

### Controller.php

**File:** [app/Http/Controllers/Controller.php](app/Http/Controllers/Controller.php).

Class dasar abstract yang diwarisi semua controller proyek. File saat ini kosong; tidak menyimpan alur zakat.

Tidak mempunyai method tambahan pada source sekarang.

### DistributionController.php

**File:** [app/Http/Controllers/DistributionController.php](app/Http/Controllers/DistributionController.php).

Mengatur pesanan yang siap dialokasikan, perjalanan fitrah, titik, keberangkatan, manifest, dan ongkir aktual.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/DistributionController.php:16) | Membaca gudang, pesanan fitrah/maal simulated_paid yang belum dialokasikan, 30 perjalanan terbaru dan titiknya; merender panitia.distribusi. |
| [`createTrip()`](app/Http/Controllers/DistributionController.php:45) | Memvalidasi gudang aktif, kendaraan, kapasitas, jadwal WIB masa depan; membuat perjalanan direncanakan dengan penanda simulasi. |
| [`destroyTrip()`](app/Http/Controllers/DistributionController.php) | Mengunci perjalanan, mensyaratkan direncanakan, belum berangkat, dan tanpa titik pengiriman. Menghapus trip kosong dan mencatat audit; tidak mengubah pesanan, reservasi, atau stok. |
| [`addStop()`](app/Http/Controllers/DistributionController.php:72) | Memvalidasi ID pesanan tidak ganda; mengunci perjalanan, memeriksa panti pada gudang sama, status/panti pesanan, kapasitas per kunjungan dan kendaraan. Membuat titik serta alokasi tiap pesanan dan status dijadwalkan; tidak mengurangi stok. |
| [`allocateMaal()`](app/Http/Controllers/DistributionController.php:146) | Memastikan pesanan mal dibayar simulasi dan belum dialokasikan. Membuat alokasi nominal menunggu_bukti serta memperbarui penyaluran. Tidak membuat perjalanan beras. |
| [`dispatch()`](app/Http/Controllers/DistributionController.php:168) | Mengunci perjalanan/produk/lot; memastikan berat reservasi sama dengan alokasi dan saldo cukup. Mengurangi lot/katalog, membuat mutasi keluar, menandai reservasi fulfilled, memperbarui harga katalog, lalu status dikirim. Ini titik pemotongan stok. |
| [`setActualShipping()`](app/Http/Controllers/DistributionController.php:251) | Mencatat ongkir aktual satu titik hanya setelah perjalanan dikirim/selesai. Total aktual perjalanan NULL bila masih ada titik belum diisi; jika lengkap, jumlahkan. Tidak memperbarui tagihan muzakki. |
| [`manifest()`](app/Http/Controllers/DistributionController.php:275) | Membaca gudang/perjalanan, titik berurutan, dan alokasi/kode pesanan per titik. Merender panitia.manifest untuk cetak. |

### FitrahCheckoutController.php

**File:** [app/Http/Controllers/FitrahCheckoutController.php](app/Http/Controllers/FitrahCheckoutController.php).

Pembentukan pesanan fitrah beserta harga berdasarkan lot dan reservasi persediaan.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/FitrahCheckoutController.php:17) | Memanggil expirePending; membaca katalog dengan jumlah reservasi aktif, menghitung tersedia, menyaring jenis stok positif dan mengurutkan harga. Membaca panti layak/wilayah belum lewat serta statistik dan bobot. Merender muzakki.fitrah. |
| [`store()`](app/Http/Controllers/FitrahCheckoutController.php:77) | Memvalidasi jiwa/beras/panti, memproses expired, mengunci stok dan memilih lot FIFO. Memeriksa panti/tarif/pengaturan/kapasitas, menghitung komponen biaya BCMath, membuat master/detail/reservasi/token QR bersama. Redirect pembayaran dengan ID baru; retry transaksi DB sampai 3 percobaan untuk konflik yang dapat dicoba ulang. |

### HandoverEvidenceController.php

**File:** [app/Http/Controllers/HandoverEvidenceController.php](app/Http/Controllers/HandoverEvidenceController.php).

Unggah bukti kedua pihak, kesesuaian angka, penyelesaian, dan akses foto privat. Method submit menjadi pusat proses supaya panitia dan PJ memakai aturan sama.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/HandoverEvidenceController.php:16) | Membaca 100 alokasi terbaru berikut panti, jenis pesanan, serta ID bukti panitia/yayasan. Merender panitia.bukti. |
| [`submitPanitia()`](app/Http/Controllers/HandoverEvidenceController.php:34) | Meneruskan unggahan ke submit dengan pihak panitia; route role panitia mengatur aksesnya. |
| [`submitPj()`](app/Http/Controllers/HandoverEvidenceController.php:39) | Meneruskan unggahan ke submit dengan pihak yayasan; submit memastikan PJ adalah pemilik panti tujuan. |
| [`foto()`](app/Http/Controllers/HandoverEvidenceController.php:44) | Mencari path foto dan hubungan pesanan/panti. Mengizinkan akun aktif panitia/admin, muzakki pemilik, atau PJ tujuan. Mengirim file privat, no-store; tidak menaruh foto di URL public/uploads. |
| [`submit()`](app/Http/Controllers/HandoverEvidenceController.php:62) | Memilih field jumlah_kg/nominal menurut jenis, validasi foto/angka, simpan file privat. Dalam transaksi DB, periksa pembayaran, pihak/status, tulis/ganti bukti dan cocokkan dua pihak. Ubah status; bila selesai buat penyaluran sekali dan notifikasi; perbarui titik/perjalanan. Jika DB gagal file baru dihapus; jika penggantian berhasil file lama dihapus. |

### InAppNotificationController.php

**File:** [app/Http/Controllers/InAppNotificationController.php](app/Http/Controllers/InAppNotificationController.php).

Tindakan kecil untuk notifikasi milik akun.

| Method | Tugas dan hasil |
| --- | --- |
| [`markRead()`](app/Http/Controllers/InAppNotificationController.php:11) | Mencari notifikasi dengan ID dan user_id login. Menandai dibaca_at bila masih NULL, lalu kembali dengan pesan. Tidak boleh menandai pesan milik akun lain. |

### InventoryController.php

**File:** [app/Http/Controllers/InventoryController.php](app/Http/Controllers/InventoryController.php).

Persediaan: vendor, katalog, penerimaan lot/faktur dan koreksi. Harga beli lot tetap; saldo/penawaran dapat berubah.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/InventoryController.php:18) | Membaca vendor/gudang/katalog dan reservasi, 30 lot/pembelian terbaru, 20 koreksi. Merender panitia.gudang. |
| [`storeVendor()`](app/Http/Controllers/InventoryController.php:60) | Memvalidasi kontak pemasok dan menambah vendor aktif. |
| [`updateVendor()`](app/Http/Controllers/InventoryController.php) | Memvalidasi nama/kontak/status aktif, mengunci vendor, memperbarui data dan mencatat audit. Isian gagal validasi ditandai vendor_edit_id agar tampil pada form vendor yang benar. |
| [`destroyVendor()`](app/Http/Controllers/InventoryController.php) | Mengunci vendor, menolak jika ada pembelian, lalu menghapus permanen vendor yang belum digunakan dan mencatat audit dalam transaksi yang sama. |
| [`storeKatalog()`](app/Http/Controllers/InventoryController.php:79) | Membuat jenis beras dengan nama unik, harga/stok nol, aktif. Penerimaan stok menentukan harga. |
| [`destroyKatalog()`](app/Http/Controllers/InventoryController.php) | Mengunci jenis beras dan menolak jika stok bukan nol atau memiliki lot/transaksi_fitrah/riwayat harga. Menghapus hanya katalog yang belum digunakan dan mencatat audit. |
| [`receive()`](app/Http/Controllers/InventoryController.php:97) | Memvalidasi pemasok/gudang/jenis/berat/harga/tanggal/faktur, menyimpan berkas bila ada. Dalam transaksi: buat pembelian, lot, mutasi masuk, tambah stock, refresh harga. Hapus file baru jika gagal. |
| [`uploadInvoice()`](app/Http/Controllers/InventoryController.php:181) | Menambah faktur privat untuk pembelian yang belum mempunyai faktur, audit. Bila sudah punya, ditolak 409; file baru dihapus jika transaksi gagal. |
| [`invoice()`](app/Http/Controllers/InventoryController.php:218) | Membaca path faktur, memeriksa file ada, mengirim respons privat. Hak panitia datang dari middleware route. |
| [`adjust()`](app/Http/Controllers/InventoryController.php:226) | Memvalidasi koreksi positif/negatif bukan nol dan alasan. Mengunci lot/katalog; melarang saldo di bawah reservasi/negatif/batas angka. Mengubah dua saldo, mencatat mutasi dan refresh harga. |
| [`refreshCatalogPrice()`](app/Http/Controllers/InventoryController.php:273) | Helper privat memilih lot tertua bersisa, menghitung pokok+margin. Tidak ada lot berarti harga nol. Jika harga berubah, memperbarui katalog dan menambah riwayat. |

### LandingController.php

**File:** [app/Http/Controllers/LandingController.php](app/Http/Controllers/LandingController.php).

Halaman publik dan statistik simulasi. DEFAULT_CONTENT menyediakan lima teks cadangan bila admin belum menyimpannya.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/LandingController.php:18) | Mengambil teks override, paling banyak 6 panti aktif verified, jumlah panti, total penyaluran selesai yang terhubung pembayaran simulation/simulated_paid, dan bobot fitrah. Merender landing; tidak membutuhkan login. |

### MaalCheckoutController.php

**File:** [app/Http/Controllers/MaalCheckoutController.php](app/Http/Controllers/MaalCheckoutController.php).

Draft keluarga, perhitungan maal per pemilik dan pemilihan panti manual/acak.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/MaalCheckoutController.php:17) | Membaca acuan emas dan usia, panti layak/statistik, draft keluarga akun. Memaksa checkbox haul tampil false ketika draft dibuka. Merender muzakki.maal. |
| [`store()`](app/Http/Controllers/MaalCheckoutController.php:58) | Memvalidasi subjek/panti; memeriksa acuan emas, memilih panti, menghitung nisab/kewajiban per subjek lalu agregat. Menolak total wajib nol. Membuat transaksi master/detail/snapshot/QR, audit bila panti otomatis. Tidak mengambil stok beras. |
| [`saveDraft()`](app/Http/Controllers/MaalCheckoutController.php:210) | Memvalidasi subjek, mengunci user, menghapus draft lama saja (transaksi NULL, bukan snapshot), lalu menyimpan draft baru. Redirect form maal tanpa membuat pesanan. |
| [`destroyDraft()`](app/Http/Controllers/MaalCheckoutController.php) | Mengunci user login seperti saveDraft; menghapus hanya draft akun sendiri dengan transaksi_id NULL dan is_snapshot false. Snapshot/riwayat pesanan tetap ada. Audit hanya memuat jumlah draft yang dihapus. |
| [`validateSubjects()`](app/Http/Controllers/MaalCheckoutController.php:247) | Helper privat membatasi jumlah/angka/hubungan/tanggal. Tepat satu diri dan maksimum satu istri; seluruh subjek maksimum 10. Dipakai draft maupun checkout. |

### MuzakkiController.php

**File:** [app/Http/Controllers/MuzakkiController.php](app/Http/Controllers/MuzakkiController.php).

Dashboard, riwayat, profil, dan preferensi muzakki. Pembentukan pesanan telah dipisah ke dua checkout controller.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/MuzakkiController.php:15) | Memproses expired, membaca ringkasan dan 5 transaksi/notifikasi terbaru akun. Menghitung bantuan per panti/per penerima, rekomendasi terbanyak/terendah/provinsi sama dan countdown Idulfitri. Merender dashboard. |
| [`showMaal()`](app/Http/Controllers/MuzakkiController.php:94) | Sisa method lama yang hanya membuka view maal tanpa data. Route maal aktif memakai MaalCheckoutController::index; jangan memakai method ini sebagai acuan alur aktif. |
| [`riwayat()`](app/Http/Controllers/MuzakkiController.php:99) | Memproses expired; membaca pesanan akun beserta panti/detail lewat eager loading, paginate 20. Merender riwayat. |
| [`profil()`](app/Http/Controllers/MuzakkiController.php:111) | Membaca preferensi akun; mengirim User dan preferensi ke view profil. |
| [`updateNotificationPreferences()`](app/Http/Controllers/MuzakkiController.php:120) | Memvalidasi tiga boolean, upsert preferensi unik user, mencatat audit. Pesan hasil menjelaskan email belum aktif. |
| [`updateProfil()`](app/Http/Controllers/MuzakkiController.php:152) | Memvalidasi biodata serta NIK unik selain akun sendiri, lalu update user login. Tidak mengganti email/role/password melalui form profil ini. |

### OperationalReportController.php

**File:** [app/Http/Controllers/OperationalReportController.php](app/Http/Controllers/OperationalReportController.php).

Laporan rekonsiliasi dari tabel operasional, tidak melalui accessor status model Penyaluran lama.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/OperationalReportController.php:10) | Mengelompokkan status transaksi, membandingkan stok katalog/lot/reservasi, ongkir ditagih/aktual, jumlah terkumpul/selesai per panti dan tugas pending. Merender panitia.laporan; belum ada filter tahun/periode atau export file. |

### PanitiaController.php

**File:** [app/Http/Controllers/PanitiaController.php](app/Http/Controllers/PanitiaController.php).

Dashboard pekerjaan harian panitia, bukan semua operasi dalam satu controller.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/PanitiaController.php:10) | Membaca ringkasan pending/alokasi/stock/reservasi/bukti; menampilkan panti menunggu, stok tersedia <25 kg, pesanan siap, perjalanan dalam 3 hari, dan bukti tertunda. Merender panitia.dashboard. |

### PantiVerificationController.php

**File:** [app/Http/Controllers/PantiVerificationController.php](app/Http/Controllers/PantiVerificationController.php).

Pemeriksaan kelayakan panti dan penyajian foto privat panti.

| Method | Tugas dan hasil |
| --- | --- |
| [`index()`](app/Http/Controllers/PantiVerificationController.php:16) | Membaca panti/pengaju/wilayah paginasi 10, foto per panti, dan zona gudang aktif. Merender panitia.panti. |
| [`update()`](app/Http/Controllers/PantiVerificationController.php:41) | Validasi verified/rejected, jumlah layak, pilihan fitrah, rute dan catatan. Menolak penonaktifan jika pesanan terbuka; mencocokkan jumlah diajukan/kebutuhan, kapasitas persetujuan terhadap kapasitas PJ, dan provinsi wilayah. Penolakan bisnis memakai ValidationException sehingga pesan tampil di form dan isian dipertahankan, bukan halaman error generik. Mengubah yayasan, menambah verifikasi dan audit hanya setelah seluruh syarat terpenuhi. |
| [`foto()`](app/Http/Controllers/PantiVerificationController.php:141) | Mengizinkan panitia/admin aktif atau PJ pemilik melihat foto, memastikan file privat ada, mengirim berkas. Muzakki tidak diberikan akses foto pendaftaran ini. |

### SimulationPaymentController.php

**File:** [app/Http/Controllers/SimulationPaymentController.php](app/Http/Controllers/SimulationPaymentController.php).

Detail pembayaran pemilik, QR scan publik, bayar simulasi, pembatalan. Semua endpoint pembentuk uang nyata belum ada.

| Method | Tugas dan hasil |
| --- | --- |
| [`show()`](app/Http/Controllers/SimulationPaymentController.php:19) | Memproses expired, memanggil pesananMilikUser, membentuk URL/QR SVG dari token terenkripsi jika pending, membaca bukti dan snapshot subjek maal. Merender muzakki.payment. |
| [`scan()`](app/Http/Controllers/SimulationPaymentController.php:50) | Hash token dan cari percobaan+master+panti mode simulation. Hitung tombol bisa bayar/sudah dibayar. Merender halaman publik ringkas dengan no-store/no-referrer. Tidak login dan tidak mengubah status pembayaran. |
| [`pay()`](app/Http/Controllers/SimulationPaymentController.php:80) | Cari token lalu kunci pesanan/percobaan. Jika sudah dibayar, kembalikan hasil yang sama tanpa penggandaan. Jika sah/belum lewat, tandai simulated_paid dan waktu/audit dengan actor kosong. Redirect halaman scan. Tidak mengubah stok/penyaluran. |
| [`cancel()`](app/Http/Controllers/SimulationPaymentController.php:133) | Pastikan pesanan milik user dan pending; tandai master/percobaan cancelled, lepas reservasi, catat audit. Redirect detail. |
| [`pesananMilikUser()`](app/Http/Controllers/SimulationPaymentController.php:158) | Helper privat menggabungkan master, panti, detail fitrah/maal dengan filter user_id login; pesanan orang lain menghasilkan 404. |

### YayasanController.php

**File:** [app/Http/Controllers/YayasanController.php](app/Http/Controllers/YayasanController.php).

Dashboard PJ, pendaftaran satu panti, dan koreksi pengajuan ditolak.

| Method | Tugas dan hasil |
| --- | --- |
| [`dashboard()`](app/Http/Controllers/YayasanController.php:17) | Membaca panti akun, bank aktif, catatan pemeriksaan, pengajuan rekening terakhir, alokasi/bukti milik panti dan 5 notifikasi penyaluran. Merender yayasan.dashboard. |
| [`storePanti()`](app/Http/Controllers/YayasanController.php:60) | Menolak panti kedua, memvalidasi identitas/rekening/kebutuhan/foto, pilihan layanan dan kapasitas beras wajib untuk fitrah, membuat Yayasan pending nonaktif. Menyimpan foto privat dan foto_yayasan; membersihkan file bila gagal. |
| [`resubmitPanti()`](app/Http/Controllers/YayasanController.php:134) | Untuk status pending atau rejected. Memvalidasi identitas/kebutuhan, pilihan layanan, kapasitas beras wajib bila memilih fitrah, dan foto opsional, mengunci panti, kembali pending nonaktif dan menghapus kelayakan/rute lama. Jika foto baru ada mengganti foto; membersihkan file sesuai hasil transaksi. |


## 10. Model, middleware, provider, dan command

### 10.1 Cara membaca model

Contoh `protected $table = 'user'` menentukan nama tabel. `$fillable` menentukan field yang dapat diisi massal melalui model. `$hidden` mengatur serialisasi. `casts()` menentukan bentuk nilai ketika dibaca. `belongsTo` berarti FK biasanya ada di model ini; `hasOne/hasMany` berarti FK ada di tabel terkait.

File model tidak sama dengan tabel database. Beberapa tabel operasional baru belum mempunyai model, sementara beberapa model lama menunjuk tabel yang sudah tidak dipakai.

#### User.php

**File:** [app/Models/User.php](app/Models/User.php); tabel `user`.

Aktif untuk login, registrasi, akun panitia dan profil. Menerjemahkan nama/password bawaan Laravel ke nama/pass_hash. $hidden menyembunyikan hash saat serialisasi, bukan mengganti otorisasi. Relasi profil lama tetap ada.

| Method | Fungsi |
| --- | --- |
| `casts()` | Menyatakan cara konversi nilai database ke PHP (tanggal, boolean, decimal). Tidak menciptakan kolom atau memvalidasi input. |
| `getAuthPasswordName()` | Memberitahu autentikasi nama kolom hash adalah pass_hash. |
| `getAuthPassword()` | Mengembalikan hash pass_hash untuk verifikasi login. |
| `getNameAttribute()` | Saat kode membaca name, mengembalikan isi kolom nama. |
| `setNameAttribute()` | Saat kode menulis name, menyimpan ke atribut nama. |
| `setPasswordAttribute()` | Saat kode mengisi atribut password, mengarahkannya ke pass_hash dan menjalankan hashing bila perlu; create akun aktif menulis pass_hash dengan Hash::make secara eksplisit. |
| `getKotaNamaAttribute()` | Alias baca kota_nama ke kota_kabupaten. |
| `getProvinsiNamaAttribute()` | Alias baca provinsi_nama ke provinsi. |
| `getAlamatAttribute()` | Alias baca alamat ke alamat_lengkap. |
| `yayasan()` | Relasi ke panti; pada User hasOne melalui pj_user_id, pada detail lain belongsTo melalui yayasan_id. |
| `transaksiZakat()` | HasMany pesanan milik akun atau panti melalui FK yang sesuai. |

#### Yayasan.php

**File:** [app/Models/Yayasan.php](app/Models/Yayasan.php); tabel `yayasan`.

Aktif untuk panti milik PJ dan relasi transaksi. Fillable memuat data pendaftaran/kelayakan. Accessor nama/domisili memudahkan template lama.

| Method | Fungsi |
| --- | --- |
| `casts()` | Menyatakan cara konversi nilai database ke PHP (tanggal, boolean, decimal). Tidak menciptakan kolom atau memvalidasi input. |
| `getNameAttribute()` | Saat kode membaca name, mengembalikan isi kolom nama. |
| `getKotaNamaAttribute()` | Alias baca kota_nama ke kota_kabupaten. |
| `getProvinsiNamaAttribute()` | Alias baca provinsi_nama ke provinsi. |
| `pjUser()` | BelongsTo akun penanggung jawab melalui pj_user_id. |
| `mustahik()` | Pada Yayasan hasMany individu; pada Penyaluran belongsTo individu opsional. |
| `transaksiZakat()` | HasMany pesanan milik akun atau panti melalui FK yang sesuai. |
| `penyaluran()` | HasMany catatan penyaluran dari panti atau mustahik. |

#### TransaksiZakat.php

**File:** [app/Models/TransaksiZakat.php](app/Models/TransaksiZakat.php); tabel `transaksi_zakat`.

Aktif untuk riwayat dan kedaluwarsa. Eloquent membaca relasi detail/panti; proses checkout memakai Query Builder sehingga tidak bergantung semua field masuk fillable.

| Method | Fungsi |
| --- | --- |
| `expirePending()` | Fungsi static mengambil maksimum 100 pesanan pending lewat batas; per pesanan mengunci dan memeriksa ulang, mengubah master/percobaan expired, melepas reservasi active. Mengembalikan jumlah yang diproses. |
| `casts()` | Menyatakan cara konversi nilai database ke PHP (tanggal, boolean, decimal). Tidak menciptakan kolom atau memvalidasi input. |
| `getNamaPembayarAttribute()` | Alias tampilan nama_pembayar dari user.nama, cadangan Muzakki. |
| `getTotalBayarAttribute()` | Alias total_bayar ke total_nominal. |
| `getStatusAttribute()` | Accessor kompatibilitas; TransaksiZakat mengubah paid lama menjadi success dan selain itu mengembalikan status_pembayaran. Penyaluran menghitung disalurkan/pending dari bukti_pict_id; berbeda dari kolom status baru selesai. |
| `getBuktiPembayaranAttribute()` | Accessor lama mengembalikan qr_payload atau path default; qr_payload sekarang token terenkripsi sehingga bukan foto bukti pembayaran nyata. |
| `user()` | BelongsTo pemilik akun melalui user_id. |
| `yayasan()` | Relasi ke panti; pada User hasOne melalui pj_user_id, pada detail lain belongsTo melalui yayasan_id. |
| `detailFitrah()` | Pada TransaksiZakat hasOne detail melalui transaksi_id; pada KatalogBeras hasMany detail lewat katalog_beras_id. |
| `detailMal()` | HasOne detail maal melalui transaksi_id. |

#### TransaksiFitrah.php

**File:** [app/Models/TransaksiFitrah.php](app/Models/TransaksiFitrah.php); tabel `transaksi_fitrah`.

Aktif dibaca sebagai detail riwayat fitrah; pembuatan detail memakai Query Builder. Decimal casts menjaga representasi field harga/berat.

| Method | Fungsi |
| --- | --- |
| `casts()` | Menyatakan cara konversi nilai database ke PHP (tanggal, boolean, decimal). Tidak menciptakan kolom atau memvalidasi input. |
| `transaksi()` | BelongsTo master transaksi melalui transaksi_id. |
| `katalogBeras()` | BelongsTo jenis beras melalui katalog_beras_id. |

#### TransaksiMal.php

**File:** [app/Models/TransaksiMal.php](app/Models/TransaksiMal.php); tabel `transaksi_mal`.

Aktif dibaca sebagai detail maal. Model nama Mal mengikuti tabel, sedangkan controller/view menulis Maal. Field baru yang ditulis query langsung tidak selalu tercantum dalam fillable model.

| Method | Fungsi |
| --- | --- |
| `casts()` | Menyatakan cara konversi nilai database ke PHP (tanggal, boolean, decimal). Tidak menciptakan kolom atau memvalidasi input. |
| `transaksi()` | BelongsTo master transaksi melalui transaksi_id. |

#### KatalogBeras.php

**File:** [app/Models/KatalogBeras.php](app/Models/KatalogBeras.php); tabel `katalog_beras`.

Struktur model produk; operasi gudang dan checkout aktif banyak mengakses tabel langsung. Relasi detailFitrah menghubungkan produk ke detail pesanan.

| Method | Fungsi |
| --- | --- |
| `casts()` | Menyatakan cara konversi nilai database ke PHP (tanggal, boolean, decimal). Tidak menciptakan kolom atau memvalidasi input. |
| `detailFitrah()` | Pada TransaksiZakat hasOne detail melalui transaksi_id; pada KatalogBeras hasMany detail lewat katalog_beras_id. |

#### Mustahik.php

**File:** [app/Models/Mustahik.php](app/Models/Mustahik.php); tabel `mustahik`.

Model struktur individu, berhubungan panti dan penyaluran. Belum ada controller CRUD individu aktif. Tanda is_verified di skema tidak ikut tercantum dalam fillable saat ini.

| Method | Fungsi |
| --- | --- |
| `casts()` | Menyatakan cara konversi nilai database ke PHP (tanggal, boolean, decimal). Tidak menciptakan kolom atau memvalidasi input. |
| `yayasan()` | Relasi ke panti; pada User hasOne melalui pj_user_id, pada detail lain belongsTo melalui yayasan_id. |
| `penyaluran()` | HasMany catatan penyaluran dari panti atau mustahik. |

#### Penyaluran.php

**File:** [app/Models/Penyaluran.php](app/Models/Penyaluran.php); tabel `penyaluran`.

Model kompatibilitas tampilan lama. Controller bukti dan laporan baru memakai DB::table agar status tabel baru tidak tertimpa accessor lama. Jangan menyimpulkan proses selesai dari accessor getStatusAttribute yang masih melihat bukti_pict_id.

| Method | Fungsi |
| --- | --- |
| `casts()` | Menyatakan cara konversi nilai database ke PHP (tanggal, boolean, decimal). Tidak menciptakan kolom atau memvalidasi input. |
| `yayasan()` | Relasi ke panti; pada User hasOne melalui pj_user_id, pada detail lain belongsTo melalui yayasan_id. |
| `mustahik()` | Pada Yayasan hasMany individu; pada Penyaluran belongsTo individu opsional. |
| `getJumlahUangDimintaAttribute()` | Accessor lama mengembalikan jumlah jika jenis mal atau satuan Rupiah, selain itu nol. |
| `getJumlahBerasDimintaKgAttribute()` | Accessor lama mengembalikan jumlah jika jenis fitrah atau satuan Kg, selain itu nol. |
| `getKeteranganAttribute()` | Alias keterangan ke kolom barang. |
| `getStatusAttribute()` | Accessor kompatibilitas; TransaksiZakat mengubah paid lama menjadi success dan selain itu mengembalikan status_pembayaran. Penyaluran menghitung disalurkan/pending dari bukti_pict_id; berbeda dari kolom status baru selesai. |

#### GudangBeras.php

**File:** [app/Models/GudangBeras.php](app/Models/GudangBeras.php); tabel `gudang_beras`.

Sisa rancangan konversi uang menjadi stok. Tabel gudang_beras tidak ada pada skema zakat_db yang diperiksa dan route baru tidak memakai model ini. Gudang baru memakai gudang, lot_beras dan mutasi_stok.

Tidak ada method khusus; file mendefinisikan tabel dan field yang dapat diisi.

#### PengajuanYayasan.php

**File:** [app/Models/PengajuanYayasan.php](app/Models/PengajuanYayasan.php); tabel `pengajuan_yayasans`.

Sisa proposal program lama. Tabel pengajuan_yayasans tidak ada pada skema zakat_db; route baru memakai pendaftaran yayasan dan pemeriksaan panti, bukan model ini.

| Method | Fungsi |
| --- | --- |
| `user()` | BelongsTo pemilik akun melalui user_id. |

### 10.2 Middleware dan provider

| File | Method | Tugas |
| --- | --- | --- |
| [app/Http/Middleware/EnsureRole.php](app/Http/Middleware/EnsureRole.php) | `handle()` | Memastikan akun ada, aktif, dan role termasuk daftar route; jika tidak, 403. Jika wajib mengganti password, redirect form pergantian. Jika lolos, lanjutkan request melalui `$next`. |
| [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) | `register()` | Tempat pendaftaran layanan/dependensi; belum menambahkan layanan khusus. |
| File provider di atas | `boot()` | Memaksa root URL mengikuti konfigurasi app.url dan scheme HTTPS bila app.url berawalan https. Penting agar QR/link tidak menunjuk localhost yang hanya bisa diakses server. |
| [bootstrap/providers.php](bootstrap/providers.php) | Array provider | Mendaftarkan AppServiceProvider agar dijalankan saat aplikasi boot. |

Provider bukan controller halaman. Middleware bukan perhitungan harga. Keduanya dapat memengaruhi banyak route sekaligus.

### 10.3 Command dan penjadwalan

| File | Signature dan method | Tugas dan batas |
| --- | --- | --- |
| [app/Console/Commands/CreateInitialAdmin.php](app/Console/Commands/CreateInitialAdmin.php) | `app:create-initial-admin`; `handle()` | Pembuatan admin pertama melalui prompt nama/email/password tersembunyi dan validasi. Menolak jika sudah ada akun role admin, termasuk jika akun itu tidak aktif. Bukan fitur reset password/admin kedua. |
| [app/Console/Commands/ExpirePendingOrders.php](app/Console/Commands/ExpirePendingOrders.php) | `app:expire-pending-orders`; `handle()` | Memanggil TransaksiZakat::expirePending berulang selama satu batch mengembalikan 100, lalu menampilkan jumlah diproses. Membuat perubahan status database, bukan hapus riwayat. |
| [app/Console/Commands/CreateZakatReminders.php](app/Console/Commands/CreateZakatReminders.php) | `app:create-zakat-reminders`; `handle()` | Memeriksa H-14 fitrah atau ulang tanggal haul draft, akun aktif dan preferensi. Membuat notifikasi dalam aplikasi; tidak mengirim email. |
| File pengingat di atas | `buatJikaBelumAda()` | Helper privat mengunci preferensi, memeriksa pesan jenis yang sama sudah ada pada hari WIB tersebut, lalu membuat pesan sekali. |
| [app/Console/Commands/SyncGoldPrice.php](app/Console/Commands/SyncGoldPrice.php) | `app:sync-gold-price`; `handle()` | Mengunci proses, melewati sinkronisasi yang sudah sukses hari ini, mengambil emas/kurs, validasi, konversi BCMath, menambah riwayat harga. Kegagalan mempertahankan harga terakhir. |
| File sinkronisasi di atas | `httpClient()`, `positiveDecimal()`, `goldTimestamp()`, `exchangeDate()` | Timeout/retry HTTP yang terbatas serta validasi angka positif, timestamp UTC dan tanggal kurs. |
| [routes/console.php](routes/console.php) | Schedule dan `inspire` | Kedaluwarsa tiap menit; pengingat 08.00 dan harga emas 09.30 Asia/Jakarta; ketiganya withoutOverlapping. inspire adalah contoh kutipan bawaan, bukan proses zakat. |

Definisi jadwal tidak otomatis membuat sistem operasi memanggilnya. Scheduler/cron harus hidup. Pada 8 Oktober 2026, crontab akun keli sudah dipasang untuk memanggil schedule:run setiap menit, dengan daemon cron active/enabled. Menyalakan web via systemd dan menjalankan jadwal merupakan dua pekerjaan terpisah. Petunjuk sistem operasi tersedia di [!wajib_baca.md](%21wajib_baca.md) Bagian 10 dan 11.15.

## 11. Migration, seeder, dan factory

### 11.1 Cara membaca migration

`up()` membangun perubahan; `down()` berusaha membalik skema. `Schema::create` membuat tabel; `Schema::table` mengubah tabel yang ada. `foreignId(...)->constrained(...)` menambahkan kolom dan FK. `decimal` menentukan kapasitas angka; `unique`/ `index` menentukan indeks.

Nama file berawalan waktu menentukan urutan. Tabel yang menjadi induk harus dibuat lebih dahulu. File ini adalah resep schema, bukan berkas data untuk diisi manual. Untuk melihat struktur aktual, gunakan metadata database dan ERD; isi migration saja belum membuktikan seluruh resep persis sudah diterapkan.

| File | Yang dibuat/diubah | Penjelasan |
| --- | --- | --- |
| [0001_01_01_000000_create_users_table.php](database/migrations/0001_01_01_000000_create_users_table.php) | `user`, `password_reset_tokens`, `sessions` | Meskipun nama users, membuat tabel user tunggal, password_reset_tokens, sessions. Ada pemulihan terbatas tabel user parsial yang kosong; menolak tabel tidak cocok atau berisi data. Bukan importer database lama. down menghapus tabel terkait. |
| [0001_01_01_000001_create_cache_table.php](database/migrations/0001_01_01_000001_create_cache_table.php) | `cache`, `cache_locks` | Membuat cache dan cache_locks untuk backend cache database; down menghapus keduanya. |
| [0001_01_01_000002_create_jobs_table.php](database/migrations/0001_01_01_000002_create_jobs_table.php) | `jobs`, `job_batches`, `failed_jobs` | Membuat jobs, job_batches, failed_jobs untuk queue; down menghapus ketiganya. |
| [2026_09_27_042021_create_a_zakat_reference_tables.php](database/migrations/2026_09_27_042021_create_a_zakat_reference_tables.php) | `bank`, `gudang`, `wilayah_layanan`, `tarif_pengiriman`, `pengaturan_operasional`, `harga_emas`, `yayasan`, `foto_yayasan`, `verifikasi_panti`, `permohonan_perubahan_rekening`, `mustahik` | Master bank, gudang, wilayah/tarif, pengaturan, harga emas, yayasan, foto/verifikasi/permohonan rekening, mustahik. Mengatur FK, indeks, nilai default. |
| [2026_09_27_042022_create_b_zakat_inventory_tables.php](database/migrations/2026_09_27_042022_create_b_zakat_inventory_tables.php) | `vendor`, `katalog_beras`, `pembelian_beras`, `lot_beras`, `mutasi_stok`, `riwayat_harga_katalog` | Vendor, katalog, pembelian, lot, mutasi, riwayat harga. Harga beli di lot dan riwayat penawaran terpisah. |
| [2026_09_27_042023_create_c_zakat_transaction_tables.php](database/migrations/2026_09_27_042023_create_c_zakat_transaction_tables.php) | `pemberi_zakat_fitrah`, `pemberi_zakat_mal`, `transaksi_zakat`, `transaksi_fitrah`, `transaksi_mal`, `subjek_zakat_maal`, `reservasi_stok`, `percobaan_pembayaran` | Profil pemberi lama, master transaksi, detail fitrah/mal, subjek maal, reservasi, percobaan pembayaran. UNIQUE per detail/reservasi/token menjaga duplikasi tertentu. |
| [2026_09_27_042024_create_d_zakat_distribution_tables.php](database/migrations/2026_09_27_042024_create_d_zakat_distribution_tables.php) | `perjalanan_pengiriman`, `titik_pengiriman`, `alokasi_transaksi`, `bukti_serah_terima`, `penyaluran`, `audit_log`, `notifikasi_aplikasi` | Perjalanan, titik, alokasi, bukti, penyaluran, audit dan notifikasi. Bukti unik per alokasi dan pihak. down menghapus tabel dalam urutan dependensi. |
| [2026_09_27_062149_add_panti_need_fields_to_yayasan.php](database/migrations/2026_09_27_062149_add_panti_need_fields_to_yayasan.php) | `yayasan`, `verifikasi_panti` | Menambah jumlah_mustahik_diajukan, kebutuhan_saat_ini, kapasitas_penerimaan_kg pada yayasan serta kapasitas/kebutuhan_snapshot pada verifikasi_panti. Bukan membuat panti kedua. |
| [2026_09_27_124232_create_notification_preferences_table.php](database/migrations/2026_09_27_124232_create_notification_preferences_table.php) | `preferensi_notifikasi` | Membuat preferensi_notifikasi satu baris per user dengan tiga boolean. |
| [2026_10_08_082353_add_api_source_fields_to_harga_emas.php](database/migrations/2026_10_08_082353_add_api_source_fields_to_harga_emas.php) | `harga_emas` | Menambah delapan kolom metadata API emas/kurs dan indeks unik tanggal sinkronisasi. Riwayat lama tetap ada dan bertipe manual. down menghapus metadata tambahan, bukan nominal/snapshot transaksi. |
| [2026_10_08_090750_add_proposed_capacity_to_yayasan.php](database/migrations/2026_10_08_090750_add_proposed_capacity_to_yayasan.php) | `yayasan` | Menambah kapasitas_diajukan_kg nullable untuk pengajuan kapasitas beras PJ, terpisah dari kapasitas_penerimaan_kg panitia. down menghapus kolom dan isi pengajuannya. |
| [2026_10_09_071031_remove_unused_framework_tables.php](database/migrations/2026_10_09_071031_remove_unused_framework_tables.php) | Tujuh tabel infrastruktur selain migrations | Memeriksa driver dan seluruh isi tabel sebelum DROP; down membuat kembali tabel kosong. Ran, batch 6 pada 9 Oktober 2026: tujuh tabel kosong dihapus dengan izin DROP sementara yang kemudian dicabut. |
| [2026_10_09_080247_remove_unused_payer_profile_tables.php](database/migrations/2026_10_09_080247_remove_unused_payer_profile_tables.php) | Dua profil pemberi lama | Ran batch 7. Memeriksa kedua tabel kosong sebelum DROP; menghapus pemberi_zakat_fitrah dan pemberi_zakat_mal. Rollback membuat tabel kosong dengan indeks dan FK; mustahik tetap dipertahankan. |

### 11.2 Data awal dan data contoh

| File | Method | Tugas |
| --- | --- | --- |
| [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php) | `run()` | Memanggil OperationalSeeder; tidak membuat akun default. |
| [database/seeders/OperationalSeeder.php](database/seeders/OperationalSeeder.php) | `run()` | Mengisi 7 bank, gudang SBY-PUSAT, 6 zona Jawa nonaktif, tarif Rp3.000+Rp8/km/kg jika belum ada, admin Rp1.000, margin 5%, bobot 2,5 kg. Memakai insertOrIgnore/exists supaya data tertentu tidak diulang. |
| [database/factories/UserFactory.php](database/factories/UserFactory.php) | `definition()` | Membuat contoh User/biodata acak role pemberi; hash password contoh untuk pengujian. Tidak dijalankan otomatis ketika pengguna membuka login. |
| File factory di atas | `unverified()` | State factory yang membuat email_verified_at kosong. Bukan route konfirmasi email. |
| [database/.gitignore](database/.gitignore) | Aturan Git | Mengecualikan file database lokal pada folder ini, bukan mengatur koneksi MariaDB. |

Seeder tidak mengisi tanggal Idulfitri, harga emas, panti, akun panitia, atau stock beras. Jalankan ulang seeder hanya dengan memahami koneksi dan perubahan datanya; panduan ini tidak menjalankannya.

Jika menemukan SQLite di konfigurasi pengujian/factory contoh Laravel, itu tidak berarti database web aktif menggunakan SQLite. [phpunit.xml](phpunit.xml) memang memilih SQLite memory untuk pengujian; database aktif yang dibaca panduan adalah zakat_db pada MariaDB.

## 12. View dan tampilan per file

View mengatur penyajian dan form; controller menyiapkan datanya. Nama `muzakki.payment` berarti file `resources/views/muzakki/payment.blade.php`. Pada inventaris saat ini ada **29 file Blade**. Beberapa mempunyai CSS/JavaScript inline; jangan menganggap semua perilaku frontend ada di resources/js/app.js.

### landing.blade.php

**File:** [resources/views/landing.blade.php](resources/views/landing.blade.php). **Status:** Aktif, publik. **Dibuka oleh:** `LandingController::index`.

Halaman depan: teks admin, dampak simulasi selesai, panti aktif, kalkulator perkiraan berat fitrah. JavaScript hanya menghitung perkiraan jiwa × bobot; tidak membuat pesanan.

**Nama route yang dipanggil template:** `landing`, `home`, `login`, `register`, `muzakki.fitrah`. Tautan/form lama yang ditandai di atas bukan route aktif.

### home.blade.php

**File:** [resources/views/home.blade.php](resources/views/home.blade.php). **Status:** Lama, tidak dirender route home aktif. **Dibuka oleh:** `Tidak ada pemanggil route aktif`.

Halaman sambutan/profil bawaan rancangan lama. Route /home sekarang redirect menurut role sehingga mengedit file ini tidak mengubah dashboard muzakki.

**Nama route yang dipanggil template:** `logout`. Tautan/form lama yang ditandai di atas bukan route aktif.

### welcome.blade.php

**File:** [resources/views/welcome.blade.php](resources/views/welcome.blade.php). **Status:** Bawaan, tidak digunakan landing aktif. **Dibuka oleh:** `Tidak ada pemanggil route aktif`.

Contoh halaman awal Laravel dan integrasi Vite. Route / sekarang merender landing, bukan welcome.

**Nama route yang dipanggil template:** `login`, `register`. Tautan/form lama yang ditandai di atas bukan route aktif.

### auth/login.blade.php

**File:** [resources/views/auth/login.blade.php](resources/views/auth/login.blade.php). **Status:** Aktif, guest. **Dibuka oleh:** `AuthController::showLogin`.

Form email/password dan pesan gagal. Tema login memiliki CSS dalam file serta tombol tampil/sembunyikan password. Login ditentukan AuthController, bukan JavaScript.

**Nama route yang dipanggil template:** `login`, `register`, `landing`. Tautan/form lama yang ditandai di atas bukan route aktif.

### auth/register.blade.php

**File:** [resources/views/auth/register.blade.php](resources/views/auth/register.blade.php). **Status:** Aktif, guest. **Dibuka oleh:** `AuthController::showRegister`.

Form akun/biodata dan pilihan muzakki/PJ. JavaScript mengubah kewajiban alamat PJ, menampilkan password, dan pilihan provinsi/kota dari daftar statis. Backend memvalidasi kembali.

**Nama route yang dipanggil template:** `register.post`, `login`. Tautan/form lama yang ditandai di atas bukan route aktif.

### auth/change-password.blade.php

**File:** [resources/views/auth/change-password.blade.php](resources/views/auth/change-password.blade.php). **Status:** Aktif, auth. **Dibuka oleh:** `AuthController::showChangePassword`.

Form password sekarang, baru, konfirmasi dan logout. Dipakai terutama akun panitia yang wajib mengganti password sementara.

**Nama route yang dipanggil template:** `password.change.store`, `logout`. Tautan/form lama yang ditandai di atas bukan route aktif.

### admin/dashboard.blade.php

**File:** [resources/views/admin/dashboard.blade.php](resources/views/admin/dashboard.blade.php). **Status:** Aktif, admin. **Dibuka oleh:** `AdminController::index`.

Satu panel memuat pembuatan/aktivasi panitia, bank, Idulfitri/bobot/admin/margin, wilayah/tarif, harga emas, teks landing. Tiap form menuju method admin berbeda; angka statistik tidak diisi manual sebagai teks.

**Nama route yang dipanggil template:** `admin.dashboard`, `logout`, `admin.audit.index`, `admin.panitia.store`, `admin.panitia.active`, `admin.bank.store`, `admin.bank.active`, `admin.pengaturan.update`, `admin.wilayah.update`, `admin.harga-emas.store`, `landing`, `admin.konten.update`. Tautan/form lama yang ditandai di atas bukan route aktif.

### admin/audit.blade.php

**File:** [resources/views/admin/audit.blade.php](resources/views/admin/audit.blade.php). **Status:** Aktif, admin. **Dibuka oleh:** `AdminController::auditLog`.

Tabel paginasi riwayat tindakan beserta pelaku/role/objek/waktu. Membaca data audit, tidak mengedit kejadian.

**Nama route yang dipanggil template:** `admin.dashboard`. Tautan/form lama yang ditandai di atas bukan route aktif.

### muzakki/layout.blade.php

**File:** [resources/views/muzakki/layout.blade.php](resources/views/muzakki/layout.blade.php). **Status:** Aktif, kerangka. **Dibuka oleh:** `@extends dari halaman muzakki`.

Navbar, menu dashboard/riwayat/profil, identitas akun, logout, CSS dan area @yield content. Mengubah kerangka memengaruhi view yang mewarisinya.

**Nama route yang dipanggil template:** `muzakki.dashboard`, `muzakki.riwayat`, `muzakki.profil`, `logout`. Tautan/form lama yang ditandai di atas bukan route aktif.

### muzakki/dashboard.blade.php

**File:** [resources/views/muzakki/dashboard.blade.php](resources/views/muzakki/dashboard.blade.php). **Status:** Aktif, pemberi. **Dibuka oleh:** `MuzakkiController::index`.

Ringkasan pending/dibayar/selesai, notifikasi, tombol fitrah/maal, pesanan terbaru, statistik panti tertinggi/terendah/provinsi sama. Data akun difilter controller.

**Kerangka:** `muzakki.layout`.

**Nama route yang dipanggil template:** `muzakki.payment.show`, `notifikasi.read`, `muzakki.fitrah`, `muzakki.maal`. Tautan/form lama yang ditandai di atas bukan route aktif.

### muzakki/fitrah.blade.php

**File:** [resources/views/muzakki/fitrah.blade.php](resources/views/muzakki/fitrah.blade.php). **Status:** Aktif, pemberi. **Dibuka oleh:** `FitrahCheckoutController::index`.

Form jumlah jiwa, kartu jenis beras dengan stok tersedia, panti dan catatan. JavaScript menonaktifkan jenis beras yang tidak cukup dan menampilkan berat; keputusan harga/stok final tetap backend.

**Kerangka:** `muzakki.layout`.

**Nama route yang dipanggil template:** `muzakki.fitrah.store`. Tautan/form lama yang ditandai di atas bukan route aktif.

### muzakki/maal.blade.php

**File:** [resources/views/muzakki/maal.blade.php](resources/views/muzakki/maal.blade.php). **Status:** Aktif, pemberi. **Dibuka oleh:** `MaalCheckoutController::index`.

Form pemilik harta diri/istri/anak, aset/utang/haul, pilihan panti, tombol simpan draft atau buat pesanan. JavaScript menambah/menghapus kartu maksimal 10; angka wajib dihitung di controller.

**Kerangka:** `muzakki.layout`.

**Nama route yang dipanggil template:** `muzakki.maal.store`, `muzakki.maal.draft`, `muzakki.maal.draft.destroy`. Tautan/form lama yang ditandai di atas bukan route aktif.

### muzakki/payment.blade.php

**File:** [resources/views/muzakki/payment.blade.php](resources/views/muzakki/payment.blade.php). **Status:** Aktif, pemilik pesanan. **Dibuka oleh:** `SimulationPaymentController::show`.

Tagihan komponen fitrah atau rincian per pemilik maal, status pembayaran/penyaluran, QR pending, masa berlaku, bukti dan tombol batal pending. Bukan halaman publik hasil scan.

**Kerangka:** `muzakki.layout`.

**Nama route yang dipanggil template:** `bukti.foto`, `muzakki.payment.cancel`, `muzakki.riwayat`. Tautan/form lama yang ditandai di atas bukan route aktif.

### muzakki/payment-scan.blade.php

**File:** [resources/views/muzakki/payment-scan.blade.php](resources/views/muzakki/payment-scan.blade.php). **Status:** Aktif, publik dengan token. **Dibuka oleh:** `SimulationPaymentController::scan`.

Template mandiri tanpa layout akun. Ringkasan kode/panti/total, tombol bayar simulasi jika sah, atau pesan sudah dibayar/tidak berlaku. CSRF tetap disertakan; tidak menampilkan rincian harta.

**Nama route yang dipanggil template:** `login`, `muzakki.payment.pay`. Tautan/form lama yang ditandai di atas bukan route aktif.

### muzakki/riwayat.blade.php

**File:** [resources/views/muzakki/riwayat.blade.php](resources/views/muzakki/riwayat.blade.php). **Status:** Aktif, pemberi. **Dibuka oleh:** `MuzakkiController::riwayat`.

Daftar transaksi akun, jenis, detail/status, tombol detail dan paginasi. Tidak menggabungkan semua akun muzakki.

**Kerangka:** `muzakki.layout`.

**Nama route yang dipanggil template:** `muzakki.payment.show`. Tautan/form lama yang ditandai di atas bukan route aktif.

### muzakki/profil.blade.php

**File:** [resources/views/muzakki/profil.blade.php](resources/views/muzakki/profil.blade.php). **Status:** Aktif, pemberi. **Dibuka oleh:** `MuzakkiController::profil`.

Identitas dan form edit biodata, timestamp akun, tiga preferensi notifikasi. JavaScript membantu domisili. Form profil tidak mengubah role, email atau password.

**Kerangka:** `muzakki.layout`.

**Nama route yang dipanggil template:** `muzakki.profil.update`, `muzakki.profil.notifikasi`. Tautan/form lama yang ditandai di atas bukan route aktif.

### panitia/layout.blade.php

**File:** [resources/views/panitia/layout.blade.php](resources/views/panitia/layout.blade.php). **Status:** Aktif, kerangka. **Dibuka oleh:** `@extends dari halaman panitia`.

Menu pekerjaan panitia, CSS, pesan hasil/validasi, @yield content dan logout. View lama yang masih extends file ini belum tentu mempunyai route aktif.

**Nama route yang dipanggil template:** `panitia.dashboard`, `panitia.panti.index`, `panitia.rekening.index`, `panitia.gudang.index`, `panitia.distribusi.index`, `panitia.bukti.index`, `panitia.laporan.index`, `logout`. Tautan/form lama yang ditandai di atas bukan route aktif.

### panitia/dashboard.blade.php

**File:** [resources/views/panitia/dashboard.blade.php](resources/views/panitia/dashboard.blade.php). **Status:** Aktif, panitia. **Dibuka oleh:** `PanitiaController::index`.

Pekerjaan menunggu: panti, alokasi, stok rendah, perjalanan dekat, bukti. Tautan menuju halaman operasional masing-masing.

**Kerangka:** `panitia.layout`.

**Nama route yang dipanggil template:** `panitia.panti.index`, `panitia.bukti.index`, `panitia.gudang.index`, `panitia.distribusi.index`, `panitia.laporan.index`. Tautan/form lama yang ditandai di atas bukan route aktif.

### panitia/panti.blade.php

**File:** [resources/views/panitia/panti.blade.php](resources/views/panitia/panti.blade.php). **Status:** Aktif, panitia. **Dibuka oleh:** `PantiVerificationController::index`.

Pengajuan panti, foto privat, kebutuhan/jumlah penerima, form persetujuan dengan rute/kapasitas dan penolakan dengan catatan. Kapasitas beras PJ dan panitia ditampilkan terpisah; jarak tetap ditetapkan panitia. Input persetujuan dibatasi maksimal jumlah pengajuan PJ; ketika gagal, isian dikembalikan hanya pada panti dan jenis form yang dikirim. Detail perbaikan ada di [!wajib_baca.md Bagian 13](%21wajib_baca.md#13-perbaikan-pesan-verifikasi-panti-8-oktober-2026).

**Kerangka:** `panitia.layout`.

**Nama route yang dipanggil template:** `panti.foto`, `panitia.panti.update`. Tautan/form lama yang ditandai di atas bukan route aktif.

### panitia/rekening.blade.php

**File:** [resources/views/panitia/rekening.blade.php](resources/views/panitia/rekening.blade.php). **Status:** Aktif, panitia. **Dibuka oleh:** `BankChangeRequestController::index`.

Menampilkan rekening lama dan permintaan baru, status bank, form approve/reject dan catatan. Bukan transfer dana.

**Kerangka:** `panitia.layout`.

**Nama route yang dipanggil template:** `panitia.rekening.review`. Tautan/form lama yang ditandai di atas bukan route aktif.

### panitia/gudang.blade.php

**File:** [resources/views/panitia/gudang.blade.php](resources/views/panitia/gudang.blade.php). **Status:** Aktif, panitia. **Dibuka oleh:** `InventoryController::index`.

Tambah vendor/jenis, catat penerimaan/faktur, tabel stok/reservasi, lot dan form koreksi beralasan. Pengaturan ini menulis beberapa tabel lewat backend.

**Kerangka:** `panitia.layout`.

**Nama route yang dipanggil template:** `panitia.vendor.store`, `panitia.vendor.update`, `panitia.vendor.destroy`, `panitia.katalog.store`, `panitia.katalog.destroy`, `panitia.pembelian.receive`, `panitia.pembelian.faktur.show`, `panitia.pembelian.faktur.store`, `panitia.lot.adjust`. Tautan/form lama yang ditandai di atas bukan route aktif.

### panitia/distribusi.blade.php

**File:** [resources/views/panitia/distribusi.blade.php](resources/views/panitia/distribusi.blade.php). **Status:** Aktif, panitia. **Dibuka oleh:** `DistributionController::index`.

Form perjalanan, pemilihan titik/pesanan fitrah, daftar perjalanan, manifest/berangkat, ongkir aktual dan alokasi maal. Semua tindakan butuh status yang sesuai.

**Kerangka:** `panitia.layout`.

**Nama route yang dipanggil template:** `panitia.perjalanan.store`, `panitia.perjalanan.destroy`, `panitia.titik.store`, `panitia.titik.ongkir`, `panitia.perjalanan.manifest`, `panitia.perjalanan.dispatch`, `panitia.maal.allocate`. Tautan/form lama yang ditandai di atas bukan route aktif.

### panitia/manifest.blade.php

**File:** [resources/views/panitia/manifest.blade.php](resources/views/panitia/manifest.blade.php). **Status:** Aktif, panitia. **Dibuka oleh:** `DistributionController::manifest`.

Template cetak perjalanan berisi gudang, kendaraan, tujuan/urutan, berat dan kode pesanan. Tidak mengubah keberangkatan ketika dicetak.

### panitia/bukti.blade.php

**File:** [resources/views/panitia/bukti.blade.php](resources/views/panitia/bukti.blade.php). **Status:** Aktif, panitia. **Dibuka oleh:** `HandoverEvidenceController::index`.

Daftar alokasi dan tautan foto kedua pihak; unggah foto panitia berikut kg/nominal/catatan saat alokasi siap. Penyelesaian ditentukan controller.

**Kerangka:** `panitia.layout`.

**Nama route yang dipanggil template:** `bukti.foto`, `panitia.bukti.store`. Tautan/form lama yang ditandai di atas bukan route aktif.

### panitia/laporan.blade.php

**File:** [resources/views/panitia/laporan.blade.php](resources/views/panitia/laporan.blade.php). **Status:** Aktif, panitia. **Dibuka oleh:** `OperationalReportController::index`.

Tabel status pesanan, rekonsiliasi stok, ongkir ditagih/aktual, dampak per panti dan tugas tertunda. Belum export atau filter tahun.

**Kerangka:** `panitia.layout`.

### panitia/pengguna.blade.php

**File:** [resources/views/panitia/pengguna.blade.php](resources/views/panitia/pengguna.blade.php). **Status:** Lama, tidak dihubungkan route aktif. **Dibuka oleh:** `Tidak ada controller aktif yang merendernya`.

Tabel daftar muzakki/yayasan rancangan lama. Tidak menjadi halaman pengelolaan akun admin baru.

**Kerangka:** `panitia.layout`.

### panitia/transaksi.blade.php

**File:** [resources/views/panitia/transaksi.blade.php](resources/views/panitia/transaksi.blade.php). **Status:** Lama, tidak dihubungkan route aktif. **Dibuka oleh:** `Tidak ada controller aktif yang merendernya`.

Verifikasi pembayaran manual lama dengan route panitia.verifikasi yang tidak ada pada route aktif. Alur baru membayar QR simulasi tanpa persetujuan per pembayaran.

**Kerangka:** `panitia.layout`.

**Nama route yang dipanggil template:** `panitia.verifikasi`. Tautan/form lama yang ditandai di atas bukan route aktif.

### panitia/pengajuan.blade.php

**File:** [resources/views/panitia/pengajuan.blade.php](resources/views/panitia/pengajuan.blade.php). **Status:** Lama, tidak dihubungkan route aktif. **Dibuka oleh:** `Tidak ada controller aktif yang merendernya`.

Proposal/penyaluran lama dengan route panitia.prosesPengajuan yang tidak tersedia. Pengiriman baru berada pada distribusi dan bukti.

**Kerangka:** `panitia.layout`.

**Nama route yang dipanggil template:** `panitia.prosesPengajuan`. Tautan/form lama yang ditandai di atas bukan route aktif.

### yayasan/dashboard.blade.php

**File:** [resources/views/yayasan/dashboard.blade.php](resources/views/yayasan/dashboard.blade.php). **Status:** Aktif, pj. **Dibuka oleh:** `YayasanController::dashboard`.

Template mandiri PJ dengan formulir daftar atau koreksi panti sesuai keadaan, status pemeriksaan, rekening/perubahan, alokasi/bukti PJ, dan notifikasi. Tidak memakai layout muzakki/panitia.

**Nama route yang dipanggil template:** `yayasan.dashboard`, `logout`, `notifikasi.read`, `yayasan.rekening.store`, `yayasan.panti.resubmit`, `bukti.foto`, `yayasan.bukti.store`, `yayasan.panti.store`. Tautan/form lama yang ditandai di atas bukan route aktif.

### 12.1 CSS, JavaScript, gambar, dan bundling

| File | Fungsi |
| --- | --- |
| [public/css/style.css](public/css/style.css) | Stylesheet yang dipanggil banyak halaman aktif: warna, navbar, kartu, input, tombol, tabel, radius, spacing dan penyesuaian komponen Bootstrap. Dalam workspace saat penulisan, variabel warna utamanya hijau dengan aksen kuning/oranye dan latar krem. File ini sedang memiliki perubahan lokal. |
| [resources/css/app.css](resources/css/app.css) | Entry CSS pipeline Vite/Tailwind: import, sumber class, tema font. Tidak otomatis menggantikan public/css/style.css bila template aktif tidak memanggil @vite. |
| [resources/js/app.js](resources/js/app.js) | Entry JS Vite; saat ini hanya placeholder komentar. Interaksi nyata seperti menambah subjek maal ditulis di view. |
| [vite.config.js](vite.config.js) | Menentukan input resources/css/app.css dan resources/js/app.js, plugin Laravel/Tailwind, refresh, font Instrument Sans, serta mengabaikan compiled views pada file watcher. |
| [public/favicon.ico](public/favicon.ico) | Ikon tab browser. |
| [public/images/beras.png](public/images/beras.png), [beras_1.png](public/images/beras_1.png), [beras_2.png](public/images/beras_2.png) | Aset ilustrasi beras pada workspace; bukan data lot atau bukti pengiriman. |
| [public/images/gold_1.png](public/images/gold_1.png) | Ilustrasi emas. |
| [public/images/masjid_1.jpg](public/images/masjid_1.jpg), [masjid_2.png](public/images/masjid_2.png) | Ilustrasi/foto masjid. |
| [public/images/money_1.png](public/images/money_1.png), [rupiah.png](public/images/rupiah.png) | Ilustrasi uang/rupiah. |
| [public/images/zakat_2.jpg](public/images/zakat_2.jpg) | Ilustrasi zakat. |

Gambar di public/images masih belum tracked pada saat dokumentasi dibuat; panduan tidak memasukkannya ke commit ini. Gambar publik tersebut berbeda dengan foto panti, faktur, dan bukti serah terima yang disimpan privat.

Beberapa template memakai Bootstrap/jQuery dari CDN. Tampilan login/register juga mempunyai style sendiri. Mengubah CSS publik dapat langsung memengaruhi template pemakainya; perubahan entry Vite memerlukan build/dev Vite untuk template yang memakai @vite. Keberadaan Tailwind dalam paket tidak berarti seluruh view aktif ditulis memakai Tailwind.

## 13. Konfigurasi, entry point, dan file pendukung

### 13.1 Tempat aplikasi dimulai

| File | Tugas |
| --- | --- |
| [public/index.php](public/index.php) | Entry request web: memuat maintenance bila ada, autoload Composer, bootstrap aplikasi, lalu handle request. Web server diarahkan ke public, bukan semua folder proyek. |
| [artisan](artisan) | Entry perintah terminal Laravel. Memuat autoload dan bootstrap lalu menangani command. Berbeda dari index.php yang menangani browser. |
| [bootstrap/app.php](bootstrap/app.php) | Menyusun aplikasi Laravel: route web/console, health /up, alias middleware role, trust proxy, aturan respons error JSON untuk api atau expectsJson. Tidak mendefinisikan harga zakat. |
| [bootstrap/providers.php](bootstrap/providers.php) | Daftar provider aplikasi, termasuk AppServiceProvider. |
| [public/.htaccess](public/.htaccess) | Aturan Apache rewrite menuju index.php, header auth/CSRF dan trailing slash. Tidak digunakan dengan cara yang sama oleh php artisan serve atau Nginx. |
| [public/robots.txt](public/robots.txt) | Instruksi crawler. Disallow kosong saat ini; bukan sistem perlindungan halaman privat. Login/otorisasi tetap wajib di backend. |
| [routes/web.php](routes/web.php) | Seluruh route web, kelompok guest/auth/role, QR publik, rate limiting, dan closure home. Peta lengkap ada di Bagian 8. |
| [routes/console.php](routes/console.php) | Command contoh inspire dan dua jadwal aplikasi. |

### 13.2 File config satu per satu

| File | Yang diatur | Hubungannya dengan website |
| --- | --- | --- |
| [config/app.php](config/app.php) | Nama, environment, debug, URL, timezone UTC, locale, key enkripsi, maintenance | Nama Portal Zakat dan APP_URL untuk tautan/QR; APP_KEY dipakai mengenkripsi token. |
| [config/auth.php](config/auth.php) | Guard session web, provider Eloquent User, reset broker | Nama provider users tidak berarti tabel harus users; model User menentukan tabel user. Konfigurasi reset bukan bukti route lupa password sudah ada. |
| [config/database.php](config/database.php) | Koneksi SQLite/MySQL/MariaDB/PostgreSQL/SQL Server dan Redis | Default fallback source adalah sqlite, tetapi environment aktif yang diperiksa memilih mysql menuju zakat_db. Tidak semua driver yang tersedia dipakai. |
| [config/cache.php](config/cache.php) | Store cache dan lock serta prefix | Default source file, dapat dioverride env. Cache/lock memakai filesystem, termasuk kebutuhan scheduler dan Laravel Boost. |
| [config/filesystems.php](config/filesystems.php) | Disk local/private, public, S3 dan link storage | Disk local berakar storage/app/private, dipakai foto/faktur; public berakar storage/app/public. storage:link hanya untuk public, tidak membuat bukti privat menjadi publik. |
| [config/logging.php](config/logging.php) | Channel log, level, tujuan/rotasi | Tempat error aplikasi dicatat, terpisah dari audit_log bisnis dan journal systemd/MariaDB. |
| [config/mail.php](config/mail.php) | Driver dan koneksi pengiriman email | Default source log; belum ada kode pengiriman bukti aktif walaupun kolom preferensi tersedia. |
| [config/queue.php](config/queue.php) | Backend antrean, retry, batches, failed jobs | Default source sync; pekerjaan dijalankan langsung, dan penyimpanan failed jobs default null. Batching tidak digunakan aplikasi. |
| [config/services.php](config/services.php) | Kredensial/opsi layanan pihak ketiga yang disediakan kerangka | Bukan implementasi payment gateway. Tidak ada Tripay callback aktif dalam route. |
| [config/session.php](config/session.php) | Driver session, umur, cookie, domain, secure, same-site | Default source file; sesi login disimpan di storage/framework/sessions. Sesi HP terpisah; QR publik sengaja tidak memerlukan sesi akun. |

File `.env` lokal memasok environment yang dibaca config; tidak dimasukkan Git dan isinya tidak disalin ke panduan. Config yang di-cache dapat membuat edit env belum terasa sampai cache diperbarui. APP_KEY jangan diganti sembarang pada database dengan token terenkripsi: token lama memerlukan key yang sesuai.

**Perhatian saat menyalin .env.example:** template masih memilih DB_CONNECTION=sqlite dan APP_URL localhost port 8000. Itu template bawaan, bukan pengaturan yang dipakai web zakat_db sekarang. Untuk proyek ini, konfigurasi koneksi harus sesuai MariaDB dan URL yang dapat dibuka perangkat pengguna; jangan menganggap port contoh 8000 adalah port proyek ini (8020).

### 13.3 Paket, pengaturan alat, dan dokumentasi

| File | Fungsi |
| --- | --- |
| [composer.json](composer.json) | Daftar paket PHP dan script setup/dev/test/lifecycle. Paket utama framework dan endroid/qr-code. Script setup juga memasang dependency, membuat env/key bila perlu, menjalankan migration, memasang/build JS; bukan hanya membaca kode. |
| [composer.lock](composer.lock) | Mengunci versi tepat paket PHP agar composer install memakai versi yang konsisten. Jangan diedit manual. |
| [package.json](package.json) | Daftar paket JS dan script dev/build. Versi rentang Tailwind 4, Vite 8, plugin Laravel 3; versi tepat PHP diperiksa dari instalasi, bukan diasumsikan dari rentang composer.json. |
| [phpunit.xml](phpunit.xml) | Konfigurasi suite pengujian dan env testing, termasuk SQLite :memory:, cache/session array dan queue sync. Berbeda dengan koneksi web aktif MariaDB. |
| [.env.example](.env.example) | Contoh nama environment dengan placeholder, bukan salinan kredensial aktif. |
| [.gitignore](.gitignore) | Menentukan file lokal/rahasia/dependency/generated yang diabaikan Git. Tidak otomatis menghapus file yang sudah tracked sebelumnya. |
| [.gitattributes](.gitattributes) | Line ending LF dan cara diff menurut jenis file; bukan aturan aplikasi. |
| [.editorconfig](.editorconfig) | Format editor: UTF-8, LF, spasi indent, newline akhir. |
| [.npmrc](.npmrc) | Pengaturan npm, termasuk ignore-scripts=true dan audit=true. Tidak mengatur PHP/database. |
| [.mcp.json](.mcp.json) | Menjalankan server alat Laravel Boost melalui php artisan boost:mcp untuk asisten pengembangan. Bukan route website publik. |
| [boost.json](boost.json) | Pengaturan integrasi Boost, agent dan skill yang diaktifkan. Tidak menyimpan stok/pesanan. |
| [AGENTS.md](AGENTS.md) | Panduan untuk agen yang mengerjakan repository: framework, aturan kerja, dokumentasi, format dan tool. Bukan kode runtime. |
| [CLAUDE.md](CLAUDE.md) | Panduan untuk alat asisten lain. Bukan controller dan tidak dipanggil pengunjung web. |
| [README.md](README.md) | Pengenalan repository/kerangka; jangan menggantikannya sebagai sumber lengkap skema bisnis aktif. |
| [!wajib_baca.md](%21wajib_baca.md) | Keputusan, rencana/checklist, catatan implementasi, commit/rollback dan petunjuk menjalankan. Bagian awal historis tidak seluruhnya sama dengan implementasi baru. |
| [ERD_zakat_db.md](ERD_zakat_db.md) | Dokumentasi skema aktual, diagram dan indeks/default/FK terperinci. |
| [ERD_zakat_db_conceptual.mmd](ERD_zakat_db_conceptual.mmd) | Sumber Mermaid hubungan konseptual bisnis. |
| [ERD_zakat_db_physical.mmd](ERD_zakat_db_physical.mmd) | Sumber Mermaid seluruh tabel/kolom dan FK physical. |
| [PANDUAN_LENGKAP_PORTAL_ZAKAT.md](PANDUAN_LENGKAP_PORTAL_ZAKAT.md) | Panduan belajar yang sedang Anda baca: alur, arti database, dan tugas per file. |

### 13.4 File pengujian yang tersedia

| File | Method/tugas | Cakupan sebenarnya |
| --- | --- | --- |
| [tests/TestCase.php](tests/TestCase.php) | Class dasar test aplikasi | Mewarisi test case Laravel, belum menambah helper khusus. |
| [tests/Feature/ExampleTest.php](tests/Feature/ExampleTest.php) | test_the_application_returns_a_successful_response | Meminta GET / lalu assert HTTP 200. Tidak membuktikan checkout, reservasi, role, pengiriman atau bukti bekerja. |
| [tests/Unit/ExampleTest.php](tests/Unit/ExampleTest.php) | test_that_true_is_true | Contoh assertTrue(true), bukan pengujian rumus bisnis. |

Panduan ini tidak menjalankan test aplikasi atau membuat data uji. Pemeriksaan dokumentasi hanya memastikan inventaris/cakupan, tautan dan sintaks diagram konsisten.

### 13.5 Folder hasil proses dan file penahan direktori

| File / folder | Fungsi |
| --- | --- |
| [bootstrap/cache/.gitignore](bootstrap/cache/.gitignore) | Menjaga folder cache bootstrap terdaftar, mengabaikan hasil cache. |
| [storage/app/.gitignore](storage/app/.gitignore) | Aturan file aplikasi yang tersimpan lokal. |
| [storage/app/private/.gitignore](storage/app/private/.gitignore) | Mengabaikan berkas privat seperti panti, faktur dan bukti; data berkas harus dicadangkan terpisah. |
| [storage/app/public/.gitignore](storage/app/public/.gitignore) | Mengabaikan upload pada disk publik. |
| [storage/framework/.gitignore](storage/framework/.gitignore) | Aturan penyimpanan hasil kerja framework. |
| [storage/framework/cache/.gitignore](storage/framework/cache/.gitignore) | Mengabaikan cache framework. |
| [storage/framework/cache/data/.gitignore](storage/framework/cache/data/.gitignore) | Mengabaikan isi cache file. |
| [storage/framework/sessions/.gitignore](storage/framework/sessions/.gitignore) | Mengabaikan session file jika driver file dipakai. |
| [storage/framework/testing/.gitignore](storage/framework/testing/.gitignore) | Mengabaikan hasil sementara pengujian. |
| [storage/framework/views/.gitignore](storage/framework/views/.gitignore) | Mengabaikan PHP hasil kompilasi Blade; edit template resources/views, bukan file hasil ini. |
| [storage/logs/.gitignore](storage/logs/.gitignore) | Mengabaikan log yang bertambah selama aplikasi berjalan. |
| [public/uploads/bukti/.gitkeep](public/uploads/bukti/.gitkeep) | File kosong penahan direktori upload lama di Git; bukti alur baru memakai storage privat. |
| vendor/ | Paket Composer dan autoload yang dihasilkan instalasi; bukan tempat memperbaiki aturan aplikasi sendiri. |
| node_modules/, public/build/, public/hot | Paket JS, hasil build, penanda dev server; bisa dihasilkan ulang, tidak menjadi sumber data zakat. |
| .vscode-server/, .codex/, folder editor/cache lokal | Perangkat kerja pengguna/asisten; bukan fitur website, dapat menghabiskan disk, diabaikan Git sesuai aturan. |
| .git/ | Riwayat repository; bukan data MySQL dan bukan lokasi upload bukti. |

### 13.6 File skill asisten

Folder `.agents/skills/` dan salinannya `.claude/skills/` memuat panduan kerja asisten. Semua `SKILL.md` di sana menjelaskan cara menerapkan skill; file `rules/*.md`, `reference/*.md` atau `references/*.md` memberikan rincian. Tidak dijalankan Laravel saat pengguna membuka web.

| Kelompok skill | Fungsi dokumen di dalamnya |
| --- | --- |
| laravel-best-practices/ | Panduan security, validasi, routing, Eloquent, migration, performa query, queue, cache, scheduling, konfigurasi, Blade dan arsitektur. |
| testing-best-practices/ | Panduan cakupan, assertions, endpoint, data/factory, isolasi, penamaan, performa dan review test. |
| tailwindcss-development/ | Panduan menulis UI dengan Tailwind bila dipakai. |
| infer-conventions/ | Checklist pengenalan pola kode; hanya digunakan saat diminta untuk mendokumentasikan konvensi. |
| deploying-to-cloud/ | Panduan deployment Laravel Cloud dan checklist, bukan konfigurasi layanan LXC saat ini. |

Kelompok dokumen alat ini sengaja diringkas menurut fungsinya; inventaris **file kode aplikasi** tetap dijelaskan satu per satu pada Bagian 9–13.

## 14. Latihan membaca kode dan menulis komentar

### 14.1 Mulai dari satu tombol, bukan seluruh folder

Latihan pertama: buka halaman fitrah. Cari `<form`, lihat `action`, temukan nama route di routes/web.php, buka method store di controller. Catat input, pemeriksaan, tabel yang berubah, lalu redirect/view hasil.

Urutan membaca:

1. `resources/views/muzakki/fitrah.blade.php`: apa yang pengguna isi?
2. `routes/web.php`: form mengirim ke siapa dan middleware apa?
3. `FitrahCheckoutController::store()`: input sah seperti apa?
4. Bagian lot/reservasi: bagaimana stok dipilih dan ditahan?
5. Bagian snapshot/insert: harga dan histori disimpan di mana?
6. `SimulationPaymentController::show()` dan view payment: hasilnya dilihat bagaimana?

Untuk perubahan profil, urutannya sama tetapi lebih pendek: view profil → route update → MuzakkiController::updateProfil → user → redirect profil.

### 14.2 Kata kunci PHP/Laravel yang sering ditemukan

| Kode | Cara membacanya |
| --- | --- |
| `namespace`, `use` | Lokasi class dan class lain yang dipakai, bukan mengambil isi tabel. |
| `public function store(Request $request): RedirectResponse` | Method dapat dipanggil, menerima permintaan, mengembalikan redirect. |
| `$request->user()` | Akun yang sedang login. |
| `$request->file('foto')` | Upload dari input bernama foto. |
| `DB::table('...')` | Mulai query ke tabel. |
| `where`, `whereIn`, `whereNull` | Syarat pencarian; beberapa syarat biasa digabung AND. |
| `join`, `leftJoin` | Menggabungkan tabel terkait; left join mempertahankan baris meski pasangan kosong. |
| `select`, `selectRaw` | Pilih kolom atau hasil rumus SQL. |
| `first`, `get`, `value`, `pluck` | Satu baris, banyak baris, satu nilai, kumpulan kolom/peta nilai. |
| `insertGetId`, `insert`, `update`, `upsert` | Tambah baris+ID, tambah, ubah, tambah atau ubah menurut kunci. |
| `DB::transaction(function ...)` | Kelompok perubahan DB yang commit/rollback bersama. |
| `lockForUpdate` | Kunci baris agar perubahan yang bersamaan tidak berebut saldo. |
| `abort_unless`, `abort_if` | Hentikan request jika syarat tidak terpenuhi/terpenuhi. |
| `ValidationException::withMessages` | Kesalahan aturan yang dikembalikan sebagai pesan field form. |
| `Hash::make` | Hash password satu arah. |
| `Crypt::encryptString/decryptString` | Enkripsi/dekripsi dengan key aplikasi, dipakai token QR. |
| `hash('sha256', $token)` | Sidik token untuk pencarian; bukan enkripsi yang dibuka kembali. |
| `Str::ulid` | Membuat kode identitas unik untuk pesanan/lot/perjalanan. |
| `Storage::disk('local')` | Akses disk privat yang diatur config/filesystems. |
| `CarbonImmutable`, `now()` | Operasi tanggal/waktu; timezone tetap perlu diperhatikan. |
| `with('yayasan')` | Eager loading relasi Eloquent untuk mengambil data terkait. |
| `with('success', ...)` pada redirect | Flash pesan session; berbeda dari eager loading di model. |
| `map`, `filter`, `groupBy` collection | Mengubah, menyaring, mengelompokkan hasil PHP. groupBy query builder menghasilkan pengelompokan SQL. |
| `?->`, `??` | Akses aman jika objek kosong, dan nilai cadangan jika NULL. |

### 14.3 Menulis komentar yang membantu

Komentar berguna menjelaskan **mengapa** suatu aturan diperlukan. Menjelaskan tiap operator sederhana akan membuat kode sulit dibaca.

Contoh komentar yang bisa Anda pertimbangkan nanti pada pemilihan lot:

~~~php
// Beras yang sudah dipesan tetap berada di gudang,
// tetapi tidak boleh dialokasikan lagi untuk pesanan baru.
$tersedia = bcsub($item->sisa_kg, $terpesan[$item->id] ?? '0', 2);
~~~

Contoh untuk snapshot:

~~~php
// Simpan nilai pada saat checkout agar perubahan katalog berikutnya
// tidak mengubah jumlah tagihan pesanan ini.
~~~

Contoh untuk pembayaran:

~~~php
// Pembayaran simulasi hanya memperbarui status pembayaran.
// Persediaan baru dikurangi saat panitia mencatat keberangkatan.
~~~

Potongan di atas adalah **contoh belajar dalam Markdown**, bukan edit yang telah diterapkan. Saat Anda menambahkan komentar, cocokan dengan fungsi file sekarang; jangan menyalin penjelasan alur lama seperti stok turun saat paid ke checkout baru.

### 14.4 Contoh penelusuran data satu pesanan

Misalkan akun ID 10 membuat pesanan ID 501 menuju panti ID 20, fitrah 5 kg.

| Tahap | Baris penting | Cara menelusuri |
| --- | --- | --- |
| Checkout | transaksi_zakat ID 501 | user_id=10, yayasan_id=20 |
| Detail | transaksi_fitrah | transaksi_id=501, total_beras_kg=5 |
| Stok ditahan | reservasi_stok | transaksi_id=501; lot_beras_id menunjuk lot tertentu |
| QR | percobaan_pembayaran | transaksi_id=501; token_hash untuk tautan |
| Alokasi | alokasi_transaksi misal ID 701 | transaksi_id=501; titik_pengiriman_id menunjuk tujuan |
| Rute | titik_pengiriman misal ID 801 | perjalanan_pengiriman_id menunjuk perjalanan |
| Bukti | Dua bukti_serah_terima | alokasi_transaksi_id=701; pihak panitia/yayasan |
| Selesai | penyaluran | alokasi_transaksi_id=701, jumlah=5, satuan=kg |

Nama/ID ini fiktif. Cara belajar relasi: ikuti **nilai FK**, jangan menyamakan ID pesanan dengan ID bukti atau ID perjalanan. Dua tabel sama-sama memiliki id=1 tidak berarti kedua baris saling berhubungan.

### 14.5 Kalau halaman/error membingungkan, periksa lapisannya

| Gejala | Tempat awal yang dipelajari |
| --- | --- |
| Scan HP menuju login | Route QR harus scan publik bertoken; lihat URL pembentuk QR dan auth middleware. Detail pemilik memang perlu login. |
| Aktivasi wilayah ditolak | Admin setOperationalSettings/setRegion, tanggal Idulfitri dan batas_checkout_at. |
| Tidak ada panti fitrah | Verified/aktif/jumlah layak/rute/jarak/kapasitas/wilayah/tenggat, bukan hanya nama panti. |
| Tidak ada beras | Stok katalog, sisa lot, reservasi aktif, dan status jenis. |
| Harga total tidak sama dengan kartu | Harga lot yang benar-benar diambil, margin, admin, ongkir, dan snapshot. |
| Halaman 403 | Middleware role/akun aktif atau pemeriksaan hak foto/objek. |
| Halaman 404 | Route/ID/token tidak ada atau pesanan bukan milik akun; bukan otomatis masalah tabel. |
| Form 419 | Session/CSRF/cookie atau asal URL; bukan kesalahan rumus zakat. |
| Validasi 422 | Aturan input atau kondisi bisnis; baca pesan field dan method terkait. |
| Database connection error | MariaDB, koneksi env/config, socket/jaringan/disk. Tidak diperbaiki dengan mengubah warna view. |
| Putih/HTTP 500 | Log aplikasi dan controller/view yang dipanggil; tidak semua layar putih punya penyebab yang sama. |

## 15. Batas implementasi dan hal yang perlu diperhatikan

Bagian ini memisahkan perilaku yang sudah ada di source dari hal yang belum dipenuhi. Ini bukan perubahan kode yang dijalankan oleh dokumentasi.

| Bagian | Keadaan yang terbaca | Dampak atau pengembangan berikutnya |
| --- | --- | --- |
| Pembayaran nyata | Hanya QR token simulasi, bukan gateway | Gateway/webhook/rekonsiliasi uang/otorisasi produksi memerlukan implementasi tersendiri. |
| Batal setelah dibayar, gagal kirim, retur | Belum tersedia siklus lengkap | Reservasi pesanan dibayar dapat tertahan jika pengiriman tidak diselesaikan; perlu prosedur pemulihan yang menjaga riwayat. |
| Pecah pesanan atau pindah tujuan | Satu pesanan dialokasikan utuh ke satu tujuan lewat controller | Belum bisa sebagian dikirim atau dialihkan ke panti lain lewat UI. |
| Multi gudang | Skema gudang ada; checkout lot dan katalog global per jenis | Jangan langsung menambah gudang kedua dan menganggap saldo checkout sudah terpisah per gudang. |
| Kedaluwarsa lot | Field disimpan, FIFO belum memfilter tanggal ini | Perlu aturan/penyaringan sebelum menjadi operasi persediaan nyata. |
| Kebutuhan per periode | Kebutuhan teks dan kapasitas per kunjungan | Belum batas kuota kumulatif penerimaan bulanan/tahunan/Idulfitri. |
| Pemilihan panti terdekat | Banyak daftar menurut provinsi/kota; dashboard provinsi sama | Belum pengurutan jarak pengguna→panti melalui geolokasi. Jangan menyebut rekomendasi provinsi sebagai jarak terdekat terukur. |
| Harga katalog vs banyak lot | Penawaran lot tertua; checkout dapat kombinasi | Perlu pratinjau harga akhir lebih jelas jika pengguna harus tahu sebelum pesanan dibuat. |
| Harga emas harian | API emas dunia + kurs harian, input manual cadangan, batas usia 7 hari | Pengambilan pertama berhasil. Pengujian otomatis kegagalan dan alur browser pergantian acuan belum dijalankan. |
| Haul | Pernyataan pemilik dan tanggal draft, pengingat bulan/hari kalender | Belum perhitungan kalender Hijriah atau pembuktian historis kepemilikan. |
| Mustahik individu | Tabel/model ada, operasi baru agregat | Penyelesaian tugas menganggap diterima panti = selesai penerima akhir, sesuai keputusan lingkup. |
| Bukti | Dua pihak dan pencocokan angka | Foto belum diverifikasi isi/keasliannya otomatis; selisih perlu ditangani manusia. |
| Email/forgot password | Preferensi/tabel pendukung tersedia | Tidak ada pengiriman email bukti atau route pemulihan password lengkap. Ganti password dengan password sekarang sudah ada. |
| Statistik periode | Banyak query agregat seluruh riwayat | Belum pemisahan tahun/periode; percobaan beberapa tahun bisa memengaruhi pilihan otomatis dan statistik. |
| Otomatisasi | Schedule didefinisikan | Perlu scheduler berjalan agar pengingat/expired diproses tepat waktu; keberadaan definisi bukan bukti service hidup. |
| Audit | Beberapa tindakan penting dicatat | Bukan audit lengkap semua SQL; pembuatan/pengiriman tidak semuanya menulis audit_log tambahan, mutasi juga merupakan riwayat tersendiri. |
| Model/halaman lama | Masih tersimpan | Jangan membuka route hilang atau memakai accessor lama sebagai status baru. |
| Pengujian bisnis | Hanya contoh test di inventaris | Belum suite membuktikan seluruh alur, otorisasi objek, stok bersamaan, idempotensi, bukti dan pemulihan. |
| Tema | CSS publik dan inline beberapa view; ada perubahan lokal | Perlu review visual terpisah untuk keseragaman; dokumentasi ini tidak mengklaim tema sudah seragam semua. |
| Template environment | .env.example masih sqlite/localhost:8000 | Setup baru perlu memilih koneksi/URL yang benar; template bukan konfigurasi live. |
| Rollback | Commit menyimpan source/docs | Database dan berkas privat perlu cadangan sendiri; revert tidak memulihkan data. |

**Hal yang sudah jelas dari implementasi:** role terpisah, pembentukan pesanan, QR publik simulasi, perhitungan snapshot, reservasi lot, keberangkatan yang mengurangi stok, bukti dua pihak, pengelolaan rekening, pengaturan admin, dan laporan memiliki kode aktif. Selesai menulis kode bukan berarti seluruh rencana telah memenuhi semua skenario pemakaian.

## 16. Urutan belajar dan catatan dokumentasi

### 16.1 Rekomendasi urutan belajar

1. **Sesi pertama:** Bagian 1–4. Gambar satu pesanan dan catat kapan bayar, stok, dan penyaluran berubah.
2. **Sesi kedua:** Bagian 5, routes/web.php, AuthController, User dan EnsureRole. Pahami cara akun masuk ke dashboard.
3. **Sesi ketiga:** FitrahCheckoutController, SimulationPaymentController, view fitrah/payment, dan tabel transaksi/reservasi/lot.
4. **Sesi keempat:** InventoryController, DistributionController, HandoverEvidenceController dan relasi alokasi/bukti/penyaluran.
5. **Sesi kelima:** MaalCheckoutController dan subjek_zakat_maal; uji di kertas perbedaan diri/istri/anak dan draft/snapshot.
6. **Sesi keenam:** AdminController, YayasanController, pemeriksaan panti/rekening, command dan konfigurasi.
7. **Terakhir:** Migration, indeks, tabel pendukung dan testing. Baca ERD physical ketika sudah mengenal perannya.

Checklist pemahaman (bukan checklist implementasi):

- [ ] Saya dapat menjelaskan bedanya pembayaran dan penyaluran.
- [ ] Saya dapat menghitung stok tersedia sebelum/ sesudah berangkat.
- [ ] Saya tahu perbedaan katalog, lot, reservasi, dan mutasi.
- [ ] Saya tahu kenapa harga transaksi lama tetap.
- [ ] Saya dapat mengikuti form → route → middleware → controller → tabel → view.
- [ ] Saya dapat menghubungkan satu pesanan ke bukti melalui alokasi.
- [ ] Saya tahu file mana aktif dan mana sisa rancangan lama.
- [ ] Saya tahu migration/commit tidak mencadangkan isi MySQL atau foto.
- [ ] Saya tahu formula maal dihitung per pemilik.
- [ ] Saya tahu scheduler/worker/web server mempunyai peran berbeda.

### 16.2 Cara menjaga panduan tetap berguna

Saat menambah fitur nanti, perbarui: langkah alur, status yang mungkin, tabel/kolom baru, route, method/file, dan batas implementasi. Kalau template lama tidak dipakai, tandai atau rapikan dalam perubahan kode tersendiri; jangan menghapusnya hanya karena ada keterangan dalam panduan.

**Acuan utama penulisan:** source app/routes/resources/database/config/tests dalam workspace, hasil route:list, versi paket terpasang, serta metadata zakat_db melalui Laravel Boost. Dokumentasi tidak menjalankan checkout, seed, migration, penghapusan akun, atau pembayaran simulasi.

**Catatan perubahan dokumentasi 8 Oktober 2026:** dibuat panduan baru untuk alur seluruh role, contoh hitungan/stok, kamus 42 tabel/432 kolom, peta 63 route aplikasi, penjelasan 17 controller dan seluruh methodnya, 12 model, middleware/provider/command, 9 migration, seeder/factory, 29 Blade, konfigurasi serta file pendukung. Ditautkan dekat bagian atas wajib baca. Perubahan aplikasi/aset yang sudah ada di workspace tidak termasuk perubahan dokumentasi ini.


**Pembaruan kapasitas PJ 8 Oktober 2026:** Pilihan layanan dan kapasitas beras untuk fitrah wajib diisi PJ pada pengajuan; pending/rejected dapat dilengkapi. Panitia menyetujui kapasitas dan menentukan jarak jalan. Lihat [wajib baca Bagian 14](%21wajib_baca.md#14-kapasitas-beras-dari-pj-dan-jarak-yang-diperiksa-panitia-8-oktober-2026).


## 17. CRUD dan penghapusan terbatas

Pembaruan 8 Oktober 2026 menambahkan lima route, sehingga total menjadi **68 route aplikasi**, termasuk **empat route DELETE**. Pada pembaruan CRUD 8 Oktober 2026, database tetap **42 tabel, 432 kolom, 55 foreign key**; tidak ada migration baru untuk fitur CRUD tersebut. Snapshot aktif setelah kedua cleanup 9 Oktober 2026 adalah **33 tabel, 373 kolom, 53 foreign key**. Penghapusan berlangsung hanya saat pengguna menekan tombol dan mengonfirmasi; pemasangan fitur tidak menghapus data.

### 17.1 CRUD lengkap vendor

| CRUD | Cara memakai | Penjelasan kode/database |
| --- | --- | --- |
| Create | Panitia → Vendor & gudang → Tambah vendor | storeVendor memvalidasi data pemasok lalu menambah vendor aktif. |
| Read | Bagian Daftar vendor | index membaca kontak, alamat, status, dan jumlah pembelian lewat subquery; tidak ada query database dalam Blade. |
| Update | Buka Edit vendor, ubah data/status, Simpan perubahan vendor | updateVendor mengunci vendor dan mengubah nama, telepon, email, alamat, is_active, updated_at. Status nonaktif menghentikan pilihan untuk penerimaan baru. |
| Delete | Hapus vendor pada vendor tanpa pembelian | destroyVendor memeriksa ulang pembelian_beras dalam transaksi. Jika sudah dipakai, tampil pesan agar menonaktifkan vendor. Jika belum, baris vendor dihapus permanen. |

Riwayat pembelian tidak dihapus ketika vendor nonaktif. Kontak vendor tidak mempunyai akun login terpisah. Pembaruan nama vendor mengubah nama yang ditampilkan melalui join pada laporan pembelian; ini bukan snapshot nama vendor pada setiap faktur. Faktur privat dan nilai lot tidak diubah oleh edit vendor.

### 17.2 Delete pada data lainnya

| Tombol | Lokasi | Syarat server |
| --- | --- | --- |
| Hapus jenis beras | Panitia → Vendor & gudang → Katalog & ketersediaan | Stok tepat nol, tanpa lot, transaksi fitrah, dan riwayat harga. Semua lot termasuk lot habis tetap melindungi riwayat. |
| Hapus draft keluarga tersimpan | Muzakki → Kalkulator zakat maal, ketika ada draft | Hanya user_id akun login, transaksi_id NULL, is_snapshot false. Menghapus seluruh draft keluarga tersimpan, bukan snapshot atau pesanan. |
| Hapus perjalanan kosong | Panitia → Pesanan & alokasi → Perjalanan terakhir | Status direncanakan, berangkat_at NULL, dan tidak memiliki satu pun titik pengiriman. Trip yang memiliki alokasi atau sudah berangkat tetap ada. |

Tombol menampilkan konfirmasi. Penghapusan draft memakai form terpisah sehingga tidak menuntut harta memenuhi nisab atau panti terpilih. Setelah draft dihapus, halaman dimuat ulang dengan form awal; isian yang belum disimpan ditinggalkan. Hapus pemilik ini pada form maal berbeda: hanya menghapus isian sementara, kemudian perlu Simpan draft untuk memperbarui draft database.

### 17.3 Hubungan keamanan dan kode

1. Route baru berada dalam grup auth dan role yang sudah ada: panitia atau pemberi. Akun nonaktif dan panitia yang belum mengganti password sementara tidak lolos middleware.
2. HTML memakai POST + @method('DELETE') atau @method('PATCH') dan @csrf. Tidak ada penghapusan melalui GET.
3. Controller memeriksa ulang kondisi database; tombol disabled hanya petunjuk tampilan dan bukan pengaman tunggal.
4. Vendor dan katalog dikunci saat hapus; penerimaan memeriksa kembali vendor/katalog di dalam transaksi. Trip memakai kunci yang juga dipakai addStop/dispatch. Draft memakai kunci user yang juga dipakai saveDraft.
5. Pelanggaran syarat bisnis memakai ValidationException sehingga kembali ke form dengan pesan. ID objek yang sudah tidak ada menghasilkan 404.
6. Update vendor serta seluruh penghapusan dicatat pada audit_log dalam transaksi yang sama. Audit draft menyimpan jumlah, bukan isi aset. Audit tidak dihapus bersama objek.
7. Foreign key RESTRICT tetap aktif sebagai pengaman tambahan. Tidak ada penghapusan berantai riwayat.

**Batas fitur:** vendor mempunyai CRUD lengkap. Katalog dan perjalanan mendapat Delete terbatas; ini bukan klaim bahwa semua tabel punya CRUD lengkap. Pembatalan pesanan dan penonaktifan akun/bank/vendor adalah Update status, bukan Delete. Transaksi, mutasi stok, bukti selesai, dan penyaluran tidak mendapatkan tombol hapus umum.

**Cara demonstrasi:** tambah vendor uji → lihat daftar → edit nama/telepon → simpan → hapus sebelum memiliki pembelian. Untuk data terpakai, gunakan nonaktif. Contoh lain: jenis beras baru sebelum penerimaan stok; draft keluarga milik sendiri; perjalanan baru sebelum penambahan titik.

**Pemeriksaan dan pemulihan:** format PHP diperbaiki dengan Pint dan route/diff ditinjau. Tes otomatis dan submit browser belum dijalankan. Commit lokal mengembalikan kode; pemulihan baris yang dihapus permanen memerlukan backup MySQL. Tidak ada push otomatis atau perubahan skema/database lama.
