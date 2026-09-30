<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name','nip','email','password','role',
        'lokasi_dinas','divisi','is_active','ttd_path',
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Role ──
    public const ROLE_ADMIN        = 'admin';         // Super admin — akses seluruh sistem
    public const ROLE_ADMIN_DIVISI = 'admin_divisi';  // Admin terbatas pada divisinya sendiri
    public const ROLE_OPERATOR     = 'operator';

    public const ROLE_LABEL = [
        self::ROLE_ADMIN        => 'Admin (Super Admin)',
        self::ROLE_ADMIN_DIVISI => 'Admin Divisi',
        self::ROLE_OPERATOR     => 'Operator',
    ];

    // ── Divisi ──
    public const DIVISI_TRANSMISI = 'transmisi';
    public const DIVISI_STUDIO    = 'studio';
    public const DIVISI_SARANA    = 'sarana';

    public const DIVISI_LABEL = [
        self::DIVISI_TRANSMISI => 'Transmisi',
        self::DIVISI_STUDIO    => 'Studio',
        self::DIVISI_SARANA    => 'Sarana & Prasarana',
    ];

    public function getDivisiLabelAttribute(): string
    {
        return self::DIVISI_LABEL[$this->divisi] ?? 'Transmisi';
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_LABEL[$this->role] ?? ucfirst($this->role);
    }

    /** Super admin — akses penuh seluruh sistem & semua divisi */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Admin divisi — admin terbatas hanya untuk divisinya sendiri */
    public function isAdminDivisi(): bool
    {
        return $this->role === self::ROLE_ADMIN_DIVISI;
    }

    /**
     * Punya hak admin (baik super admin maupun admin divisi).
     * Gunakan ini untuk middleware/akses menu admin secara umum;
     * scoping per-divisi dilakukan di masing-masing controller.
     */
    public function hasAdminAccess(): bool
    {
        return $this->isAdmin() || $this->isAdminDivisi();
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function isDivisi(string $divisi): bool
    {
        return $this->divisi === $divisi;
    }

    /**
     * Apakah user ini berhak mengelola data milik divisi $divisi?
     * - Super admin: semua divisi
     * - Admin divisi: hanya divisinya sendiri
     * - Operator: tidak ada hak kelola
     */
    public function canManageDivisi(string $divisi): bool
    {
        if ($this->isAdmin()) return true;
        if ($this->isAdminDivisi()) return $this->divisi === $divisi;
        return false;
    }

    /**
     * Apakah user berlokasi dinas di Way Kanan?
     * Operator Way Kanan mendapat akses tambahan ke menu Studio.
     */
    public function isWayKanan(): bool
    {
        return strcasecmp(trim($this->lokasi_dinas ?? ''), 'Way Kanan') === 0;
    }

    /**
     * Apakah user boleh akses menu Studio?
     * - Divisi Studio (semua)
     * - Super Admin
     * - Operator/Admin Divisi Transmisi berlokasi Way Kanan
     */
    public function canAccessStudio(): bool
    {
        if ($this->isAdmin()) return true;
        if ($this->isDivisi('studio')) return true;
        if ($this->isWayKanan()) return true;
        return false;
    }

    /**
     * URL publik tanda tangan digital user.
     * Null jika belum ada TTD.
     */
    public function getTtdUrlAttribute(): ?string
    {
       if (!$this->ttd_path) return null;

        // Coba via symlink storage (php artisan storage:link)
        $symlinkPath = public_path('storage/' . $this->ttd_path);
        if (file_exists($symlinkPath)) {
            return asset('storage/' . $this->ttd_path);
        }

        // Fallback: langsung dari public/uploads (shared hosting)
        $uploadsPath = public_path('uploads/' . $this->ttd_path);
        if (file_exists($uploadsPath)) {
            return asset('uploads/' . $this->ttd_path);
        }

        // Fallback terakhir: buat URL dari storage path langsung
        return asset('storage/' . $this->ttd_path);											   
    }

    /**
     * Path absolut TTD untuk embed di PDF (dompdf butuh path fisik).
     */
    public function getTtdAbsPathAttribute(): ?string
    {
        if (!$this->ttd_path) return null;

        // Lokasi 1: storage/app/public (via storage:link)
        $path1 = storage_path('app/public/' . $this->ttd_path);
        if (file_exists($path1)) return $path1;

        // Lokasi 2: public/storage (symlink langsung)
        $path2 = public_path('storage/' . $this->ttd_path);
        if (file_exists($path2)) return $path2;

        // Lokasi 3: public/uploads (shared hosting tanpa symlink)
        $path3 = public_path('uploads/' . $this->ttd_path);
        if (file_exists($path3)) return $path3;

        // Return lokasi utama meski tidak exist (agar error jelas)
        return $path1;				  
    }

    /**
     * TTD sebagai base64 untuk embed di PDF.
     */
    public function getTtdBase64Attribute(): ?string
    {
        $path = $this->ttd_abs_path;
        if (!$path || !file_exists($path)) return null;
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match($ext) { 'png'=>'image/png', 'webp'=>'image/webp', default=>'image/jpeg' };
        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }

    public function jadwalShifts()
    {
        return $this->hasMany(JadwalShift::class);
    }
}