#!/usr/bin/env bash
# =============================================================================
# MonOTOn - skrip deploy satu perintah
#
# Pemakaian (jalankan sebagai user SSH biasa, mis. itsupport; butuh sudo):
#   ./deploy.sh                 # bersihkan cache, perbaiki permission, restart PHP-FPM
#   ./deploy.sh --pull          # + git pull --ff-only dulu
#   ./deploy.sh --composer      # + composer install --no-dev -o
#   ./deploy.sh --migrate       # + php artisan migrate --force (tampilkan status dulu)
#   ./deploy.sh --pull --composer --migrate
#   ./deploy.sh --dry-run       # hanya tampilkan perintah, tidak menjalankan
#
# Variabel opsional: APP_DIR (default: /var/www/monoton), WEB_USER (www-data),
#                    PHP_FPM_SERVICE (otomatis dideteksi), RELOAD_NGINX=1
#
# Skrip ini TIDAK menghapus data/berkas dan TIDAK menjalankan migrasi kecuali diminta.
# =============================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/monoton}"
WEB_USER="${WEB_USER:-www-data}"
DO_PULL=0; DO_COMPOSER=0; DO_MIGRATE=0; DRY=0

for a in "$@"; do
  case "$a" in
    --pull) DO_PULL=1 ;;
    --composer) DO_COMPOSER=1 ;;
    --migrate) DO_MIGRATE=1 ;;
    --dry-run) DRY=1 ;;
    -h|--help) sed -n '2,17p' "$0"; exit 0 ;;
    *) echo "Opsi tidak dikenal: $a (lihat --help)"; exit 2 ;;
  esac
done

C_OK=$'\e[32m'; C_WARN=$'\e[33m'; C_ERR=$'\e[31m'; C_OFF=$'\e[0m'
step() { echo; echo "${C_OK}==>${C_OFF} $*"; }
warn() { echo "${C_WARN}[!]${C_OFF} $*"; }
run()  { if [ "$DRY" = 1 ]; then echo "    (dry-run) $*"; else echo "    \$ $*"; "$@"; fi; }
art()  { run sudo -u "$WEB_USER" php artisan "$@"; }

[ -d "$APP_DIR" ] || { echo "${C_ERR}APP_DIR tidak ditemukan: $APP_DIR${C_OFF}"; exit 1; }
cd "$APP_DIR"
[ -f artisan ] || { echo "${C_ERR}Bukan folder Laravel (artisan tidak ada): $APP_DIR${C_OFF}"; exit 1; }

step "Pemeriksaan .env"
if [ -f .env ]; then
  grep -Eq '^APP_ENV=production' .env || warn "APP_ENV bukan 'production'"
  if grep -Eq '^APP_DEBUG=true' .env; then warn "APP_DEBUG=true di .env  (WAJIB false di produksi)"; fi
else
  warn ".env tidak ditemukan"
fi

if [ "$DO_PULL" = 1 ]; then
  step "git pull"
  if [ -n "$(git status --porcelain --untracked-files=no 2>/dev/null)" ]; then
    warn "Ada perubahan lokal yang belum di-commit di server; pull --ff-only bisa gagal."
  fi
  run git pull --ff-only
fi

if [ "$DO_COMPOSER" = 1 ]; then
  step "composer install"
  run composer install --no-dev --optimize-autoloader --no-interaction
else
  step "composer dump-autoload (class baru terbaca)"
  run composer dump-autoload -o --no-interaction
fi

if [ "$DO_MIGRATE" = 1 ]; then
  step "Status migrasi (sebelum)"
  art migrate:status
  step "Menjalankan migrasi"
  art migrate --force
fi

step "Bersihkan cache Laravel"
art view:clear
art route:clear
art config:clear
art cache:clear || warn "cache:clear gagal (biasanya karena permission storage/framework/cache) - lanjut, permission diperbaiki di bawah"

step "Link storage"
if [ ! -L public/storage ]; then art storage:link; else echo "    public/storage sudah ada"; fi

step "Perbaiki permission storage & bootstrap/cache"
# user SSH harus anggota grup $WEB_USER agar bisa menulis (setgid 2775 / 664)
run sudo chgrp -R "$WEB_USER" storage bootstrap/cache
run sudo find storage bootstrap/cache -type d -exec chmod 2775 {} +
run sudo find storage bootstrap/cache -type f -exec chmod 664 {} +

step "Restart PHP-FPM (membersihkan OPcache)"
SVC="${PHP_FPM_SERVICE:-}"
if [ -z "$SVC" ]; then
  SVC="$(systemctl list-units --type=service --no-legend 'php*-fpm*' 2>/dev/null | awk '{print $1}' | head -n1 || true)"
fi
if [ -n "$SVC" ]; then
  run sudo systemctl restart "$SVC"
else
  warn "Service PHP-FPM tidak terdeteksi. Set PHP_FPM_SERVICE=php8.2-fpm lalu jalankan ulang."
fi
if [ "${RELOAD_NGINX:-0}" = 1 ]; then run sudo systemctl reload nginx; fi

step "Ringkasan"
if [ "$DRY" = 1 ]; then
  echo "    (dry-run: tidak ada perubahan yang dijalankan)"
else
  echo "    Versi Laravel : $(sudo -u "$WEB_USER" php artisan --version 2>/dev/null || echo '?')"
  echo "    Commit        : $(git rev-parse --short HEAD 2>/dev/null || echo '-')"
  echo "    PHP-FPM       : ${SVC:-tidak direstart}"
  echo "${C_OK}Selesai.${C_OFF} Hard refresh browser (Ctrl+F5) bila tampilan belum berubah - service worker diganti versinya."
fi
