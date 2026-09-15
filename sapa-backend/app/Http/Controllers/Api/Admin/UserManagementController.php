<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    /**
     * List semua user, dengan filter role dan search.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('roles:name');

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identity_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->latest()->paginate(20);

        return UserResource::collection($users);
    }

    public function show(User $user)
    {
        $user->load('roles:name');
        return new UserResource($user);
    }

    /**
     * Buat user baru — SATU-SATUNYA jalur resmi untuk membuat
     * staff/counselor/admin (selain seeder), karena register publik
     * hanya bisa menghasilkan role student.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'identity_number' => ['required', 'string', 'max:30', 'unique:users,identity_number'],
            'class_name' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['student', 'staff', 'counselor', 'admin'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'identity_number' => $validated['identity_number'],
            'class_name' => $validated['class_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'Akun berhasil dibuat.',
            'user' => new UserResource($user->load('roles:name')),
        ], 201);
    }

    /**
     * Update data user oleh admin (bukan update profil sendiri).
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'identity_number' => ['sometimes', 'string', 'max:30', Rule::unique('users')->ignore($user->id)],
            'class_name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Data user berhasil diperbarui.',
            'user' => new UserResource($user->fresh()->load('roles:name')),
        ]);
    }

    /**
     * Aktif/nonaktifkan akun — bukan hapus permanen.
     * User dinonaktifkan tidak bisa login (lihat AuthController::login).
     */
    public function toggleActive(Request $request, User $user)
    {
        // Cegah admin menonaktifkan akunnya sendiri secara tidak sengaja
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.',
            ], 422);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'message' => $user->is_active ? 'Akun diaktifkan kembali.' : 'Akun dinonaktifkan.',
            'user' => new UserResource($user->fresh()->load('roles:name')),
        ]);
    }

    /**
     * Ubah role user. Satu user = satu role utama (sesuai desain kita),
     * jadi sync (replace), bukan tambah role baru.
     */
    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['student', 'staff', 'counselor', 'admin'])],
        ]);

        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'Role user berhasil diperbarui.',
            'user' => new UserResource($user->fresh()->load('roles:name')),
        ]);
    }

    /**
     * Reset password user oleh admin (untuk kasus lupa password).
     */
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'new_password' => ['required', 'string', Password::min(8)],
        ]);

        $user->update(['password' => Hash::make($validated['new_password'])]);

        return response()->json([
            'message' => 'Password user berhasil direset.',
        ]);
    }

    /**
     * Hapus user permanen — dipakai jarang, hanya untuk data invalid/test.
     * Laporan milik user ini TIDAK ikut terhapus (reporter_id jadi null
     * karena foreign key nullOnDelete di migration reports).
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus.',
        ]);
    }
}
