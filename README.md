<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

<h1 align="center">SICO — Sistem Informasi Clearing Online</h1>

<p align="center">
  Backend REST API untuk sistem pengajuan clearing mahasiswa di IPB University.<br>
  Dibangun dengan Laravel 13 + PHP 8.3.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-red" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/MySQL-8-4479A1" alt="MySQL">
  <img src="https://img.shields.io/badge/status-development-yellow" alt="Status">
</p>

---

## 📖 Tentang Proyek

**SICO (Sistem Informasi Clearing Online)** adalah sistem yang mendigitalisasi proses pengurusan surat clearing mahasiswa — mulai dari pengecekan bebas pustaka, verifikasi dokumen, hingga penerbitan surat resmi ber-QR Code yang bisa diverifikasi keasliannya secara publik.

Backend ini dibangun sebagai **REST API murni** (JSON), dikonsumsi oleh frontend React yang berjalan terpisah.

### Alur Sistem Singkat

```
Mahasiswa
   │
   ├─▶ Ajukan Bebas Pustaka (upload skripsi)
   │        │
   │        ▼
   │   Pustakawan / Atasan review
   │        │
   │   ┌────┼────┐
   │ Setuju  Revisi  Tolak
   │    │      │
   │    │      └──▶ Mahasiswa ajukan ulang
   │    ▼
   ├─▶ Ajukan Pengajuan Clearing (upload KTM, SPP, Distribusi Skripsi)
   │        │
   │        ▼
   │   Admin verifikasi dokumen
   │        │
   │   ┌────┼────┐
   │ Setuju  Revisi  Tolak
   │    │      │
   │    │      └──▶ Mahasiswa ajukan ulang
   │    ▼
   │   Atasan tanda tangan digital
   │        │
   │        ▼
   │   Surat PDF + QR Code diterbitkan otomatis
   │        │
   └─▶ Mahasiswa unduh surat
```

---

## 🧩 Role & Kewenangan

| Role | Kewenangan Utama |
|---|---|
| **Mahasiswa** | Login pakai NIM, ajukan bebas pustaka, ajukan pengajuan clearing, unduh surat |
| **Pustakawan** | Verifikasi bebas pustaka (cek status pinjaman buku) |
| **Admin** | Verifikasi dokumen pengajuan clearing (KTM, SPP, distribusi skripsi) |
| **Atasan** | Tanda tangan digital & approval final. **Juga punya akses ke tahap Pustakawan & Admin** untuk membantu proses jika dibutuhkan |

> Staff (Admin, Pustakawan, Atasan) login menggunakan **email**, sedangkan Mahasiswa login menggunakan **NIM** — keduanya lewat satu field `login` yang sama di endpoint autentikasi.

---

## 🛠️ Tech Stack

| Kebutuhan | Teknologi |
|---|---|
| Framework | Laravel 13 |
| Bahasa | PHP 8.3 |
| Database | MySQL |
| Autentikasi | Laravel Sanctum (token-based) |
| Role & Permission | Spatie Laravel Permission |
| Generate PDF | barryvdh/laravel-dompdf |
| Generate QR Code | bacon/bacon-qr-code |
| Response Format | JSON konsisten via trait `ApiResponse` |

---

## ⚙️ Instalasi & Setup Lokal

### 1. Clone Repository

```bash
git clone <url-repo-ini>
cd sico-backend
```

### 2. Install Dependency

```bash
composer install
```

### 3. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi database di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3308
DB_DATABASE=sico_db
DB_USERNAME=root
DB_PASSWORD=

APP_URL=http://192.168.0.108:8000
APP_FRONTEND_URL=http://localhost:5173
```

> ⚠️ **Penting:** `APP_URL` dipakai untuk membangun URL QR Code pada surat yang diterbitkan. Jika berjalan di jaringan lokal (bukan `localhost`), isi dengan IP address yang bisa diakses device lain (misalnya `http://192.168.1.10:8000`). Untuk kebutuhan akses publik (QR bisa discan dari mana saja), lihat bagian [Deployment](#-catatan-deployment).

### 4. Migrasi & Seeder

```bash
php artisan migrate --seed
```

Perintah ini otomatis membuat:
- 4 role: `mahasiswa`, `admin`, `atasan`, `pustakawan`
- Permission verifikasi: `verifikasi-pustaka`, `verifikasi-admin`, `verifikasi-atasan`
- Akun testing untuk setiap role

### 5. Link Storage

```bash
php artisan storage:link
```

### 6. Jalankan Server

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Gunakan `--host=0.0.0.0` agar backend bisa diakses dari device lain dalam satu jaringan (misalnya untuk testing dari HP atau laptop frontend).

---

## 🔑 Akun Testing Default

| Role | Login | Password |
|---|---|---|
| Admin | `admin@sico.test` | `password123` |
| Atasan | `atasan@sico.test` | `password123` |
| Pustakawan | `pustakawan@sico.test` | `password123` |
| Mahasiswa | `J0304211001` (NIM) | `password123` |

---

## 📡 Format Response API

Seluruh endpoint mengembalikan format JSON yang konsisten:

**Sukses:**
```json
{
    "success": true,
    "message": "Pesan sukses.",
    "data": { ... }
}
```

**Gagal:**
```json
{
    "success": false,
    "message": "Pesan error.",
    "errors": { "field": ["Detail error."] }
}
```

---

## 🔐 Autentikasi

Semua endpoint yang butuh login menggunakan header:

```
Authorization: Bearer {token}
Accept: application/json
```

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/auth/register` | Registrasi Mahasiswa (NIM, nama, password) |
| POST | `/api/auth/login` | Login pakai `login` (email/NIM) + `password` |
| POST | `/api/auth/logout` | Logout (revoke token aktif) |
| GET | `/api/auth/me` | Data user yang sedang login |

---

## 📚 Endpoint: Bebas Pustaka

| Method | Endpoint | Role | Keterangan |
|---|---|---|---|
| POST | `/api/bebas-pustaka` | Mahasiswa | Ajukan bebas pustaka + upload skripsi (PDF, maks 5MB) |
| GET | `/api/bebas-pustaka` | Semua | List pengajuan (mahasiswa hanya lihat miliknya) |
| POST | `/api/bebas-pustaka/{id}/review` | Pustakawan, Atasan | Setujui / minta revisi / tolak |
| POST | `/api/bebas-pustaka/{id}/ajukan-ulang` | Mahasiswa | Upload ulang file (saat status `revisi` **atau** `disetujui`) |
| GET | `/api/bebas-pustaka/{id}/preview-skripsi` | Pemilik, Pustakawan, Atasan | Lihat file skripsi |

> Mahasiswa tetap bisa mengganti file skripsi meski statusnya sudah `disetujui` — mengantisipasi kasus salah unggah yang baru disadari belakangan. Namun jika bebas pustaka tersebut **sudah dipakai** untuk pengajuan clearing, perubahan akan ditolak demi menjaga konsistensi data.

---

## 📄 Endpoint: Pengajuan Clearing

| Method | Endpoint | Role | Keterangan |
|---|---|---|---|
| POST | `/api/pengajuan-clearing` | Mahasiswa | Ajukan clearing (KTM, bukti SPP, distribusi skripsi) |
| GET | `/api/pengajuan-clearing` | Semua | List pengajuan |
| GET | `/api/pengajuan-clearing/{id}` | Semua | Detail pengajuan |
| POST | `/api/pengajuan-clearing/{id}/ajukan-ulang` | Mahasiswa | Upload ulang dokumen (saat status `revisi_admin`) |
| POST | `/api/pengajuan-clearing/{id}/review-admin` | Admin | Setujui / minta revisi / tolak |
| POST | `/api/pengajuan-clearing/{id}/review-atasan` | Atasan | Setujui (generate surat) / tolak |
| GET | `/api/pengajuan-clearing/{id}/dokumen/{jenis}` | Pemilik, Admin, Atasan | Preview dokumen (`ktm`, `spp`, `distribusi`) |
| GET | `/api/pengajuan-clearing/{id}/preview-surat` | Pemilik, Admin, Atasan | Preview draf surat |
| GET | `/api/pengajuan-clearing/{id}/download-surat` | Pemilik, Admin, Atasan | Unduh surat final |

### Status Pengajuan Clearing

```
menunggu → diverifikasi_admin → disetujui
    │              │
    └─ revisi_admin ┘
    │
    └─ ditolak
```

---

## 🖨️ Surat & QR Code Verifikasi

Saat Atasan menyetujui pengajuan, sistem otomatis:
1. Membuat nomor surat resmi
2. Membuat token verifikasi unik (`qr_token`)
3. Merender surat ke PDF (kop surat, isi, tanda tangan, QR Code)
4. Menyimpan file ke storage

**Endpoint verifikasi publik** (tidak perlu login, bisa diakses siapa saja lewat scan QR):

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/surat/file/{token}` | Menampilkan file PDF surat langsung |
| GET | `/api/surat/verify/{token}` | Menampilkan data verifikasi surat (JSON) |

---

## 📊 Dashboard

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/dashboard` | Data ringkasan sesuai role user yang login |

---

## 🗂️ Struktur Proyek

```
app/
├── Http/
│   ├── Controllers/Api/     → Controller REST API
│   └── Requests/            → Form Request (validasi per modul)
├── Models/                  → Eloquent Model
├── Services/                → Business logic (Service Layer)
├── Traits/
│   ├── ApiResponse.php      → Format response JSON konsisten
│   └── LogsActivity.php     → Pencatatan activity log otomatis
└── Enums/                   → Status pengajuan (type-safe)
```

---

## 🚧 Catatan Deployment

Untuk kebutuhan **testing di jaringan lokal**, backend bisa dijalankan dengan `php artisan serve --host=0.0.0.0`, namun perlu diperhatikan:

- IP lokal jaringan **dapat berubah-ubah** setiap kali perangkat reconnect ke WiFi, sehingga `APP_URL` di `.env` perlu diperbarui secara manual dan surat yang sudah diterbitkan sebelumnya bisa kehilangan validitas QR Code-nya.
- Untuk kebutuhan **demo atau akses dari luar jaringan lokal**, gunakan tunnel sementara seperti [Ngrok](https://ngrok.com).
- Untuk **production**, backend wajib di-deploy ke server dengan domain tetap agar QR Code pada surat yang sudah dicetak tidak pernah kedaluwarsa.

---

## 🧪 Testing Manual

Gunakan Postman/Insomnia dengan koleksi endpoint di atas. Pastikan header berikut selalu disertakan:

```
Content-Type: application/json   (untuk body JSON)
Accept: application/json
Authorization: Bearer {token}    (untuk endpoint yang butuh login)
```

Untuk endpoint dengan upload file, gunakan **Body → form-data**, bukan raw JSON.

---

## 👥 Tim Pengembang

| Nama | Tanggung Jawab |
|---|---|
| Person A | Fondasi, Autentikasi, Role & Permission, Manajemen User, Bebas Pustaka |
| Person B | Pengajuan Clearing, Generate Surat PDF & QR, Laporan |

---

<p align="center">
  Dibangun untuk keperluan Tugas Akhir — <b>SICO x IPB University</b>
</p>
