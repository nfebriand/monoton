<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // ── Daftar user ──
    public function index()
    {
        $auth = auth()->user();
        if (!$auth->hasAdminAccess()) abort(403);

        $query = User::orderBy('role')->orderBy('name');

        // Admin Divisi hanya melihat user di divisinya sendiri
        if ($auth->isAdminDivisi()) {
            $query->where('divisi', $auth->divisi);
        }

        $users = $query->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $auth = auth()->user();
        if (!$auth->hasAdminAccess()) abort(403);
        return view('users.create');
    }

    public function store(Request $request)
    {
        $auth = auth()->user();
        if (!$auth->hasAdminAccess()) abort(403);

        $allowedRoles = $this->allowedRolesFor($auth);

        $v = $request->validate([
            'name'         => 'required|string|max:100',
            'nip'          => 'nullable|string|max:30|unique:users,nip',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|min:8|confirmed',
            'role'         => 'required|in:'.implode(',',$allowedRoles),
            'divisi'       => 'required|in:transmisi,studio,sarana',
            'lokasi_dinas' => 'required|string|max:100',
            'is_active'    => 'nullable|boolean',
        ]);

        // Admin Divisi hanya bisa membuat user di divisinya sendiri
        if ($auth->isAdminDivisi() && $v['divisi'] !== $auth->divisi) {
            return back()->withErrors(['divisi'=>'Anda hanya dapat menambahkan user untuk divisi Anda sendiri.'])->withInput();
        }

        $v['password']  = Hash::make($v['password']);
        $v['is_active'] = $request->boolean('is_active', true);
        User::create($v);

        return redirect()->route('users.index')->with('success','User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $auth = auth()->user();
        if (!$auth->hasAdminAccess()) abort(403);

        // Admin Divisi hanya bisa edit user di divisinya sendiri
        if ($auth->isAdminDivisi() && $user->divisi !== $auth->divisi) {
            abort(403,'Anda tidak memiliki akses untuk mengedit user ini.');
        }

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $auth = auth()->user();
        if (!$auth->hasAdminAccess()) abort(403);

        if ($auth->isAdminDivisi() && $user->divisi !== $auth->divisi) {
            abort(403,'Anda tidak memiliki akses untuk mengedit user ini.');
        }

        $allowedRoles = $this->allowedRolesFor($auth);

        $v = $request->validate([
            'name'         => 'required|string|max:100',
            'nip'          => 'nullable|string|max:30|unique:users,nip,'.$user->id,
            'email'        => 'required|email|unique:users,email,'.$user->id,
            'role'         => 'required|in:'.implode(',',$allowedRoles),
            'divisi'       => 'required|in:transmisi,studio,sarana',
            'lokasi_dinas' => 'required|string|max:100',
            'is_active'    => 'nullable|boolean',
            'ttd'          => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        if ($auth->isAdminDivisi() && $v['divisi'] !== $auth->divisi) {
            return back()->withErrors(['divisi'=>'Anda hanya dapat mengatur user untuk divisi Anda sendiri.'])->withInput();
        }

        $v['is_active'] = $request->boolean('is_active', true);

        if ($request->filled('password')) {
            $request->validate(['password'=>'min:8|confirmed']);
            $v['password'] = Hash::make($request->password);
        }

        // Upload TTD baru
        if ($request->hasFile('ttd') && $request->file('ttd')->isValid()) {
            // Hapus TTD lama jika ada
            if ($user->ttd_path) {
                $this->deleteTtdFile($user->ttd_path);
            }
            $v['ttd_path'] = $this->storeTtdFile($request->file('ttd'));
        }

        // Hapus TTD
        if ($request->boolean('hapus_ttd') && $user->ttd_path) {
            Storage::disk('public')->delete($user->ttd_path);
            $v['ttd_path'] = null;
        }

        unset($v['ttd']);
        $user->update($v);
        return redirect()->route('users.index')->with('success','User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $auth = auth()->user();
        if (!$auth->hasAdminAccess()) abort(403);

        if ($auth->isAdminDivisi() && $user->divisi !== $auth->divisi) {
            abort(403,'Anda tidak memiliki akses untuk menghapus user ini.');
        }
        if ($user->id === $auth->id) {
            return back()->withErrors(['user'=>'Tidak bisa menghapus akun sendiri.']);
        }
        // Admin Divisi tidak bisa menghapus Super Admin
        if ($auth->isAdminDivisi() && $user->isAdmin()) {
            abort(403,'Tidak memiliki akses untuk menghapus Super Admin.');
        }

        $user->delete();
        return back()->with('success','User berhasil dihapus.');
    }

    /**
     * Role yang boleh dipilih saat membuat/edit user, berdasarkan
     * role akun yang sedang login.
     * - Super Admin: bisa assign semua role (termasuk admin & admin_divisi)
     * - Admin Divisi: hanya bisa assign admin_divisi & operator (tidak bisa membuat super admin)
     */
    private function allowedRolesFor(User $auth): array
    {
        if ($auth->isAdmin()) {
            return [User::ROLE_ADMIN, User::ROLE_ADMIN_DIVISI, User::ROLE_OPERATOR];
        }
        return [User::ROLE_ADMIN_DIVISI, User::ROLE_OPERATOR];
    }

    // ── Profil Diri Sendiri ──
    public function profile()
    {
        $user = auth()->user();

        // Jadwal shift mendatang (7 hari ke depan)
        $jadwalMendatang = \App\Models\JadwalShift::where('user_id', $user->id)
            ->where('tanggal', '>=', now()->toDateString())
            ->where('tanggal', '<=', now()->addDays(7)->toDateString())
            ->orderBy('tanggal')
            ->orderBy('shift')
            ->get();

        // Statistik aktivitas 30 hari (disesuaikan per divisi)
        $statistik = [];
        $sejak = now()->subDays(30);

        if ($user->divisi === 'transmisi' || $user->isAdmin()) {
            $statistik['LOG OPERASIONAL'] = \App\Models\OperasionalLog::where('user_id',$user->id)
                ->where('dicatat_pada','>=',$sejak)->count();
        }
        if ($user->divisi === 'sarana' || $user->divisi === 'transmisi' || $user->isAdmin()) {
            if (class_exists(\App\Models\GensetLog::class)) {
                $statistik['LOG GENSET'] = \App\Models\GensetLog::where('user_id',$user->id)
                    ->where('tanggal','>=',$sejak)->count();
            }
        }
        $statistik['EVIDEN DIBUAT'] = \App\Models\Eviden::where('user_id',$user->id)
            ->where('tanggal','>=',$sejak)->count();
        $statistik['EVIDEN TERLIBAT'] = $user->id
            ? \App\Models\Eviden::whereHas('operators', fn($q)=>$q->where('users.id',$user->id))
                ->where('tanggal','>=',$sejak)->count()
            : 0;

        return view('users.profile', compact('user','jadwalMendatang','statistik'));
    }

    // ── Update TTD sendiri dari halaman profil ──
    public function updateTtdSelf(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'ttd' => 'required|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        if ($request->hasFile('ttd') && $request->file('ttd')->isValid()) {
            // Hapus TTD lama
            if ($user->ttd_path) {
                $this->deleteTtdFile($user->ttd_path);
            }

            // Simpan file baru
            $newPath = $this->storeTtdFile($request->file('ttd'));
            $user->update(['ttd_path' => $newPath]);
        }

        return redirect()->route('users.profile')->with('success', 'Tanda tangan berhasil disimpan.');
    }

    /**
     * Simpan file TTD ke disk yang tersedia.
     * Shared hosting biasanya tidak punya symlink storage,
     * sehingga kita simpan langsung di public/storage atau public/uploads.
     */
    private function storeTtdFile($file): string
    {
        $filename = 'users/ttd/' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Coba storage disk 'public' (butuh storage:link)
        try {
            $stored = $file->store('users/ttd', 'public');
            // Verifikasi file benar-benar tersimpan
            if (file_exists(storage_path('app/public/' . $stored))) {
                return $stored;
            }
        } catch (\Throwable $e) {
            // Fallback ke public/uploads
        }

        // Fallback: simpan langsung ke public/uploads/users/ttd/
        $dir = public_path('uploads/users/ttd');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $fname = uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $fname);
        return 'users/ttd/' . $fname;
    }

    /**
     * Hapus file TTD dari semua lokasi yang mungkin.
     */
    private function deleteTtdFile(string $path): void
    {
        // Coba hapus dari storage disk
        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {}

        // Coba hapus dari public/uploads
        $uploadsPath = public_path('uploads/' . $path);
        if (file_exists($uploadsPath)) @unlink($uploadsPath);
    }

    // ── Hapus TTD sendiri dari halaman profil ──
    public function hapusTtdSelf()
    {
        $user = auth()->user();
        if ($user->ttd_path) {
            $this->deleteTtdFile($user->ttd_path);
            $user->update(['ttd_path' => null]);
        }
        return redirect()->route('users.profile')->with('success', 'Tanda tangan berhasil dihapus.');
    }

    // ── Ganti Password (semua user) ──
    public function changePasswordForm()
    {
        return view('users.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.'])->withInput();
        }
        if (Hash::check($request->new_password, $user->password)) {
            return back()->withErrors(['new_password' => 'Password baru harus berbeda dengan password saat ini.'])->withInput();
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return redirect()->route('dashboard')
            ->with('success','Password berhasil diperbarui. Silakan login kembali jika diperlukan.');
    }
}