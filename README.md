# 📡 RadioOps — Aplikasi Manajemen Pemancar Radio

Sistem manajemen operasional pemancar radio berbasis Laravel 10 + MariaDB.

---

## 🏗️ Fitur Utama

- **Manajemen Pemancar** — Data lengkap aset pemancar beserta foto multiple
- **Log Operasional** — Pencatatan parameter teknis dengan kalkulasi VSWR otomatis
- **Penjadwalan Shift** — 3 shift/hari, login dibatasi sesuai shift aktif
- **Alarm Pencatatan** — Notifikasi setiap 3 jam untuk pencatatan rutin
- **Monitoring Suhu** — Grafik suhu ruang + laporan bulanan
- **Laporan PDF** — Ekspor professional dengan filter waktu & operator

---

## ⚙️ Persyaratan Sistem

- PHP >= 8.1
- Laravel 10.x
- MariaDB >= 10.6
- Composer
- Node.js & NPM (untuk assets)
- Extension PHP: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD

---

## 🚀 Instalasi

### 1. Clone & Install Dependensi

```bash
git clone <repo-url> radio-ops
cd radio-ops
composer install
npm install && npm run build
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="RadioOps"
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=radio_ops
DB_USERNAME=root
DB_PASSWORD=your_password

FILESYSTEM_DISK=public
```

### 3. Setup Database

```bash
# Buat database di MariaDB
mysql -u root -p -e "CREATE DATABASE radio_ops CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Jalankan migrasi & seeder
php artisan migrate --seed

# Link storage untuk foto
php artisan storage:link
```

### 4. Jadwalkan Cron Job (untuk alarm & notifikasi)

Tambahkan di crontab server:
```bash
* * * * * cd /path/to/radio-ops && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Jalankan Aplikasi

```bash
php artisan serve
```

---

## 👤 Akun Default (setelah seeder)

| Role  | Email                   | Password  |
|-------|-------------------------|-----------|
| Admin | admin@radioops.id       | password  |
| Operator | operator1@radioops.id | password  |

---

## 📁 Struktur Direktori Penting

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/LoginController.php
│   │   ├── PemancarController.php
│   │   ├── OperasionalController.php
│   │   ├── JadwalController.php
│   │   ├── LaporanController.php
│   │   └── DashboardController.php
│   └── Middleware/
│       └── ShiftLoginMiddleware.php
├── Models/
│   ├── User.php
│   ├── Pemancar.php
│   ├── OperasionalLog.php
│   ├── JadwalShift.php
│   └── SuhuLog.php
└── Services/
    ├── VswrCalculator.php
    └── PdfReportService.php
database/migrations/
resources/views/
routes/web.php
```

---

## 📊 Formula VSWR

```
VSWR = (1 + √(Reflect/Forward)) / (1 - √(Reflect/Forward))
Return Loss (dB) = -10 × log₁₀(Reflect/Forward)
```

---

## 🕐 Jadwal Shift

| Shift | Waktu         |
|-------|---------------|
| 1     | 00.15 – 07.45 |
| 2     | 07.45 – 15.45 |
| 3     | 15.45 – 23.45 |

Login hanya diizinkan pada shift yang telah ditentukan admin.

---

## 📄 Lisensi

Internal Use — Direktorat Penyiaran
