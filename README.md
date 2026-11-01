# Omni-Presence

> **Sistem Manajemen dan Presensi Perkuliahan Terintegrasi Berbasis IoT**<br>
> Project Based Learning (PBL) — Semester 3

---

## 1. Tentang Proyek

**Omni-Presence** adalah platform web manajemen akademik dan absensi perkuliahan yang dirancang untuk menyederhanakan, mengamankan, dan mengotomatisasi pencatatan kehadiran mahasiswa. Sistem ini mengintegrasikan jadwal perkuliahan mingguan langsung dengan sesi absensi kelas, mencegah manipulasi kehadiran, serta menyediakan fondasi skema data untuk integrasi perangkat pintar (*QR Code* dan *Face Recognition*).

Aplikasi dibangun dengan arsitektur berbasis peran (*Role-Based Access Control*) yang memisahkan hak akses secara ketat antara **Admin**, **Dosen**, dan **Mahasiswa**.

---

## 2. Gambaran Sistem & Alur Kerja Absensi

```
[Admin: Buat Periode, MK, Kelas & Jadwal Mingguan]
                         │
                         ▼
[Mahasiswa: Enrollment Mandiri ke Kelas Mata Kuliah]
                         │
                         ▼
[Dosen: Buka Sesi Absensi dari Jadwal Aktif]
                         │
         ┌───────────────┴───────────────┐
         ▼                               ▼
 [Sesi Reguler]                  [Sesi Pengganti]
 (Maks. 24 jam aktif)            (Untuk kelas pengganti)
         │                               │
         └───────────────┬───────────────┘
                         ▼
[Pencatatan Presensi (Manual / Data Siap-IoT)]
                         │
                         ▼
[Tutup Sesi & Rekapitulasi Kehadiran Otomatis]
```

### Aturan & Kebijakan Absensi:
1. **Berbasis Jadwal**: Dosen membuka sesi perkuliahan langsung dari jadwal mingguan yang telah ditetapkan tanpa perlu mengisi ulang tanggal dan jam secara manual.
2. **Siklus Hidup 24 Jam**: Sesi absensi yang telah dibuka otomatis kedaluwarsa dan ditutup setelah 24 jam dari waktu mulai historisnya untuk menjaga validitas data.
3. **Pencegahan Sesi Tergantung**: Sesi berstatus `terjadwal` yang tidak pernah dibuka oleh dosen akan otomatis ditutup setelah melewati batas 24 jam dari jadwal mulai perkuliahan.
4. **Proteksi Integritas Data**: Sesi yang sudah ditutup atau dibatalkan tidak dapat dibuka kembali. Jadwal perkuliahan yang diubah di kemudian hari tidak akan memodifikasi riwayat sesi yang sudah terlaksana.
5. **Hak Koreksi Kehadiran**: Dosen dapat mencatat kehadiran pada hari pelaksanaan sesi. Jika tanggal pelaksanaan sesi telah terlewati, koreksi presensi hanya dapat dilakukan oleh Admin.
6. **Enrollment Mandiri**: Mahasiswa mendaftar (*enroll*) ke kelas mata kuliah secara mandiri melalui dashboard masing-masing. Admin tidak mendaftarkan mahasiswa secara manual, namun dapat mengelola status keaktifan akun mahasiswa.

---

## 3. Status Integrasi IoT, QR Code, dan Face Recognition

Sistem dirancang untuk mendukung ekosistem absensi cerdas berbasis IoT:

- **Dukungan Backend & Skema Database**: Tabel `attendance_sessions` dan `attendance_records` telah mendukung kolom `allowed_methods` dan `method` dengan opsi `manual`, `qr_code`, dan `face_recognition`.
- **Implementasi Saat Ini (Web Core)**: Repositori ini mencakup modul backend web, API session, otorisasi, pencatatan presensi oleh dosen/admin, dan visualisasi status kesiapan pada dashboard.
- **Batasan Perangkat Keras**: Pemrosesan citra kamera (*computer vision face recognition pipeline*) dan pemindaian perangkat keras IoT fisik (*scanner barcode/QR microcontroller*) merupakan subsistem terpisah yang berkomunikasi melalui endpoint pencatatan presensi. Pencatatan operasional harian pada antarmuka web saat ini menggunakan form presensi terverifikasi.

---

## 4. Teknologi yang Digunakan

- **Bahasa Pemrograman**: PHP 8.2+ (Teruji pada PHP 8.5)
- **Framework Web**: [Laravel 13](https://laravel.com)
- **Database**: SQLite (Bawaan pengembangan & pengujian) / Kompatibel dengan MySQL & PostgreSQL
- **Frontend & Styling**: Blade Templates, [Tailwind CSS v4](https://tailwindcss.com), [Vite](https://vite.dev)
- **Testing Framework**: [PHPUnit 12](https://phpunit.de) (335 automated feature & unit tests)
- **Code Formatter**: [Laravel Pint](https://laravel.com/docs/pint)

---

## 5. Struktur Direktori Penting

```
Omni-Presence/
├── app/
│   ├── Enums/               # Definisi Enum (misal: UserRole)
│   ├── Http/
│   │   ├── Controllers/     # Controller modul akademik, absensi, pengguna, dan dashboard
│   │   └── Middleware/      # Middleware autentikasi, pengecekan role, dan ganti password
│   ├── Models/              # Model Eloquent (User, Course, CourseSection, AttendanceSession, dll.)
│   ├── Policies/            # Kebijakan otorisasi aksi absensi dan kelas
│   └── Services/            # Business logic khusus (misal: AttendanceRecapService)
├── config/                  # Berkas konfigurasi aplikasi Laravel
├── database/
│   ├── factories/           # Factory model untuk pengujian otomatis
│   ├── migrations/          # 17 berkas migrasi database
│   └── seeders/             # DatabaseSeeder & DevelopmentTestingSeeder
├── resources/
│   ├── css/                 # Berkas stylesheet Tailwind CSS
│   ├── js/                  # JavaScript frontend
│   └── views/               # Blade templates tampilan antarmuka
├── routes/
│   ├── console.php          # Perintah terjadwal (misal: auto-close sesi 24 jam)
│   └── web.php              # Definisi rute web & middleware proteksi
└── tests/
    ├── Feature/             # 33 berkas feature test (335 skenario pengujian)
    └── TestCase.php         # Base setup test Laravel
```

---

## 6. Persyaratan Sistem

Pastikan komputer atau server telah terpasang:
- **PHP** >= 8.2 (disarankan PHP 8.3 atau PHP 8.5) dengan ekstensi: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `curl`, `xml`, `bcmath`.
- **Composer** >= 2.2
- **Node.js** >= 18.x dan **NPM**
- **Git**

---

## 7. Panduan Instalasi & Menjalankan Aplikasi

### Langkah 1: Kloning Repositori
```bash
git clone https://github.com/DarrenHafidzNajendra/Omni-Presence-Optimasi-Manajemen-Absensi-Berbasis-IoT.git
cd Omni-Presence
```

### Langkah 2: Pasang Dependensi PHP & JavaScript
```bash
composer install
npm install
```

### Langkah 3: Konfigurasi Lingkungan (`.env`)
Salin berkas template lingkungan:
```bash
cp .env.example .env
```
Buat kunci aplikasi (*application key*):
```bash
php artisan key:generate
```

Secara default, berkas `.env.example` telah menggunakan SQLite:
```ini
DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### Langkah 4: Migrasi & Seeding Database
Buat berkas database SQLite lokal (jika belum ada):
```bash
# Di Windows PowerShell:
New-Item -ItemType File -Path database\database.sqlite -Force

# Di Linux / macOS / Bash:
touch database/database.sqlite
```

Jalankan migrasi skema dan pengisian data awal pengembangan:
```bash
php artisan migrate:fresh --seed
```

### Langkah 5: Kompilasi Aset Frontend
Untuk mode pengembangan (*development watcher*):
```bash
npm run dev
```
Atau kompilasi untuk mode siap pakai (*production build*):
```bash
npm run build
```

### Langkah 6: Jalankan Server Lokal & Scheduler
Jalankan server pengembangan web:
```bash
php artisan serve
```
Akses aplikasi melalui peramban web pada alamat: `http://localhost:8000`

*(Opsional)* Untuk mengaktifkan penutupan otomatis sesi kedaluwarsa secara berkala di latar belakang:
```bash
php artisan schedule:work
```

---

## 8. Akun Uji Coba Default (Hasil Seeder)

Data awal hasil seeding (`DevelopmentTestingSeeder`) menyediakan akun percontohan untuk seluruh peran pengguna. Pada halaman login, pilih peran yang sesuai pada dropdown:

| Peran Dipilih | Identitas Login (Input) | Kata Sandi Default | Keterangan |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@omnipresence.local` (Email) | `password123` | Akses penuh dashboard Admin, manajemen master data & koreksi |
| **Dosen** | `0001018501` (NIDN) | `0001018501` | Dr. Budi Santoso (Wajib ganti password pada login pertama) |
| **Dosen** | `0002028802` (NIDN) | `0002028802` | Dewi Lestari (Wajib ganti password pada login pertama) |
| **Mahasiswa** | `3312501001` (NIM) | `3312501001` | Ahmad Fauzi (Wajib ganti password pada login pertama) |
| **Mahasiswa** | `3312501002` (NIM) | `3312501002` | Bella Safitri (Wajib ganti password pada login pertama) |

> [!NOTE]
> Pengguna berstatus **Dosen** dan **Mahasiswa** memiliki flag `must_change_password = true` secara bawaan dan akan langsung diarahkan ke form penggantian kata sandi saat pertama kali login demi memenuhi standar keamanan akun.

---

## 9. Menjalankan Pengujian Otomatis (*Testing*)

Aplikasi dilengkapi dengan test suite lengkap berbasis PHPUnit:

```bash
# Menjalankan seluruh pengujian otomatis
php artisan test

# Atau menggunakan binary runner langsung:
vendor/bin/phpunit

# Memeriksa standar kode dengan Laravel Pint:
vendor/bin/pint --test
```

Hasil verifikasi pengujian:
- **335 Tests**
- **1.838 Assertions**
- **0 Failures / 0 Errors**

---

## 10. Fitur yang Tersedia & Batasan Saat Ini

### Fitur yang Sudah Berfungsi:
- [x] Autentikasi multi-role (Admin, Dosen, Mahasiswa) berbasis sesi aman.
- [x] Alur pergantian password wajib saat login pertama.
- [x] Manajemen Periode Akademik (tanggal mulai/selesai, jadwal ATS, UAS, dan batas kelas pengganti).
- [x] Manajemen Mata Kuliah & Kelas dengan pengelompokan accordion ringkas dan pagination mahasiswa.
- [x] Penugasan Dosen (teori & praktikum) ke kelas mata kuliah.
- [x] Manajemen Jadwal Mingguan dengan mode perkuliahan (*onsite* / *online*).
- [x] Pembukaan sesi absensi instan langsung dari jadwal perkuliahan aktif.
- [x] Pembatasan durasi sesi absensi maksimal 24 jam dan penutupan otomatis sesi kedaluwarsa.
- [x] Pembuatan Sesi Pengganti (*Replacement Session*) dengan kategori alasan resmi.
- [x] Form pencatatan absensi terintegrasi bagi dosen dan proteksi koreksi oleh admin setelah hari pelaksanaan.
- [x] Rekapitulasi kehadiran mahasiswa dengan kalkulasi persentase dan status kehadiran (*hadir, terlambat, izin, sakit, alpa*).
- [x] Pendaftaran kelas mandiri (*self-enrollment*) oleh mahasiswa.
- [x] Proteksi integritas data: riwayat absensi tidak terhapus saat relasi induk diubah/dihapus; mahasiswa dengan rekap absensi tidak dapat dihapus sembarangan.

### Batasan Saat Ini:
- Komunikasi serial/socket langsung ke mikroprosesor IoT fisik dan kamera pendeteksi wajah berbasis hardware eksternal belum diimplementasikan di repositori web ini (data absensi saat ini diverifikasi dan dicatat melalui antarmuka web).
- Pengunggahan berkas bukti surat izin/sakit pada rekapitulasi kehadiran masih berupa penanda status administratif dan belum dilengkapi modul upload file lampiran dokumen.

---

## 11. Lisensi

Proyek ini dikembangkan untuk keperluan akademik dan perkuliahan PBL Politeknik Negeri Batam di bawah lisensi terbuka [MIT](LICENSE).
