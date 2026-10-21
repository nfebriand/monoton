#!/usr/bin/env bash
# =============================================================================
# MonOTOn - backup folder storage (foto eviden, TTD, logo, dll.) ke .tar.gz
#
# Pemakaian:
#   ./scripts/backup-storage.sh                  # backup storage/app/public -> /var/backups/monoton
#   ./scripts/backup-storage.sh --with-db        # + backup database (php artisan monoton:backup-db)
#   ./scripts/backup-storage.sh --no-thumbs      # lewati folder thumb/ (bisa dibuat ulang)
#   ./scripts/backup-storage.sh --keep 14        # simpan 14 backup terbaru (default 14)
#   ./scripts/backup-storage.sh --dest /mnt/usb  # folder tujuan (default /var/backups/monoton)
#   ./scripts/backup-storage.sh --dry-run        # tampilkan saja, tidak menjalankan
#
# Variabel opsional: APP_DIR (default /var/www/monoton), WEB_USER (www-data)
#
# Restore (contoh):
#   sudo tar -xzf /var/backups/monoton/monoton-storage-YYYYmmdd-HHMMSS.tar.gz -C /var/www/monoton
#   sudo chown -R www-data:www-data /var/www/monoton/storage/app/public
#
# Cron harian jam 02:00 (sudo crontab -e):
#   0 2 * * * /var/www/monoton/scripts/backup-storage.sh --with-db --no-thumbs >> /var/log/monoton-backup.log 2>&1
#
# Skrip ini hanya MEMBACA storage dan menulis arsip baru; hanya arsip lama (> --keep) yang dihapus.
# =============================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/monoton}"
WEB_USER="${WEB_USER:-www-data}"
DEST="/var/backups/monoton"
KEEP=14; WITH_DB=0; NO_THUMBS=0; DRY=0

while [ $# -gt 0 ]; do
  case "$1" in
    --with-db) WITH_DB=1 ;;
    --no-thumbs) NO_THUMBS=1 ;;
    --keep) KEEP="${2:?--keep butuh angka}"; shift ;;
    --dest) DEST="${2:?--dest butuh folder}"; shift ;;
    --dry-run) DRY=1 ;;
    -h|--help) sed -n '2,23p' "$0"; exit 0 ;;
    *) echo "Opsi tidak dikenal: $1 (lihat --help)"; exit 2 ;;
  esac
  shift
done

case "$KEEP" in ''|*[!0-9]*) echo "--keep harus angka"; exit 2 ;; esac
[ "$KEEP" -ge 1 ] || KEEP=1

SRC_REL="storage/app/public"
[ -d "$APP_DIR/$SRC_REL" ] || { echo "Folder tidak ada: $APP_DIR/$SRC_REL"; exit 1; }

STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="$DEST/monoton-storage-$STAMP.tar.gz"
run() { if [ "$DRY" = 1 ]; then echo "(dry-run) $*"; else "$@"; fi; }

echo "[$(date '+%F %T')] Backup storage -> $OUT"
run mkdir -p "$DEST"

EXCL=()
[ "$NO_THUMBS" = 1 ] && EXCL+=(--exclude='*/thumb/*')

# pigz (paralel) dipakai bila ada, jika tidak gzip biasa
if command -v pigz >/dev/null 2>&1; then COMP="pigz"; else COMP="gzip"; fi

if [ "$DRY" = 1 ]; then
  echo "(dry-run) tar -C $APP_DIR ${EXCL[*]:-} -I $COMP -cf $OUT $SRC_REL"
else
  tar -C "$APP_DIR" "${EXCL[@]}" -I "$COMP" -cf "$OUT.part" "$SRC_REL"
  mv "$OUT.part" "$OUT"
  tar -tzf "$OUT" >/dev/null   # uji arsip bisa dibaca
  chmod 640 "$OUT"
  echo "    selesai: $(du -h "$OUT" | cut -f1)"
fi

if [ "$WITH_DB" = 1 ]; then
  echo "[$(date '+%F %T')] Backup database (php artisan monoton:backup-db)"
  run sudo -u "$WEB_USER" php "$APP_DIR/artisan" monoton:backup-db --keep="$KEEP"
  # salin backup DB terbaru ke folder tujuan juga
  if [ "$DRY" != 1 ]; then
    LATEST="$(ls -1t "$APP_DIR"/storage/app/backups/db/monoton-db-*.sql.gz 2>/dev/null | head -n1 || true)"
    [ -n "$LATEST" ] && cp -p "$LATEST" "$DEST/" && echo "    disalin: $(basename "$LATEST")"
  fi
fi

# Retensi: sisakan $KEEP arsip terbaru (storage & db di folder tujuan)
for pat in 'monoton-storage-*.tar.gz' 'monoton-db-*.sql.gz'; do
  n=0
  while IFS= read -r f; do
    n=$((n+1))
    if [ "$n" -gt "$KEEP" ]; then echo "    hapus lama: $(basename "$f")"; run rm -f -- "$f"; fi
  done < <(ls -1t "$DEST"/$pat 2>/dev/null || true)
done

echo "[$(date '+%F %T')] OK"
