<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function checkAdmin()
    {
        if (!auth()->user()->isAdmin()) abort(403);
    }

    public function index()
    {
        $this->checkAdmin();
        $users = User::orderBy('role')->orderBy('name')->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->checkAdmin();
        return view('users.create');
    }

    public function store(Request $request)
    {
        $this->checkAdmin();
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'nip'          => 'nullable|string|max:30|unique:users',
            'email'        => 'required|email|unique:users',
            'password'     => 'required|min:6|confirmed',
            'role'         => 'required|in:admin,operator',
            'lokasi_dinas' => 'nullable|in:Gedung Air,Sukarame,Bakauheni,Way Kanan,Relay',
        ]);
        $validated['password']  = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);
        User::create($validated);
        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->checkAdmin();
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->checkAdmin();
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'nip'          => 'nullable|string|max:30|unique:users,nip,'.$user->id,
            'email'        => 'required|email|unique:users,email,'.$user->id,
            'role'         => 'required|in:admin,operator',
            'lokasi_dinas' => 'nullable|in:Gedung Air,Sukarame,Bakauheni,Way Kanan,Relay',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6|confirmed']);
            $validated['password'] = Hash::make($request->password);
        }
        $user->update($validated);
        return redirect()->route('users.index')->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->checkAdmin();
        if ($user->id === auth()->id()) return back()->withErrors(['user' => 'Tidak bisa menghapus akun sendiri.']);
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}
