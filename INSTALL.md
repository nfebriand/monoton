# 🚀 PANDUAN INSTALASI MonOTOn
## XAMPP 8.2.12 (PHP 8.2) + Laravel 10

---

## LANGKAH 1 — Aktifkan Extension PHP di php.ini

Buka: `C:\xamppkhanza\php\php.ini`
Cari dan HAPUS titik-koma (;) dari baris berikut:

```
extension=gd
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=fileinfo
extension=zip
extension=exif
```

Simpan, lalu **Restart Apache & MySQL** di XAMPP Control Panel.

---

## LANGKAH 2 — Buat Database

Buka phpMyAdmin: http://localhost/phpmyadmin
- Klik **New**
- Nama database: `monoton`
- Collation: `utf8mb4_unicode_ci`
- Klik **Create**

---

## LANGKAH 3 — Setting .env

Copy `.env.example` menjadi `.env` lalu sesuaikan:

```env
APP_NAME=MonOTOn
APP_URL=http://localhost/monoton/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monoton
DB_USERNAME=root
DB_PASSWORD=
```

> Jika port MySQL bukan 3306, cek di XAMPP Control Panel > MySQL > Config

---

## LANGKAH 4 — Install Dependencies

Buka CMD di folder project:

```bash
cd C:\xamppkhanza\htdocs\monoton
composer install
```

---

## LANGKAH 5 — Generate Key & Migrate

```bash
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
```

---

## LANGKAH 6 — Akses Aplikasi

Buka browser: http://localhost/monoton/public

Login admin:
- Email: admin@monoton.id
- Password: password

---

## TROUBLESHOOTING PDF

Jika PDF tidak ter-generate:

1. Pastikan extension `gd` dan `mbstring` aktif di php.ini
2. Jalankan: `composer require barryvdh/laravel-dompdf`
3. Pastikan folder `storage/` dan `bootstrap/cache/` writable

---

## TROUBLESHOOTING 500 Error

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

