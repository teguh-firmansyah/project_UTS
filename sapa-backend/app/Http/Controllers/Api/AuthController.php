<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrasi akun baru — default role: student.
     * Staff/counselor/admin dibuat lewat seeder atau admin panel,
     * BUKAN lewat endpoint register publik ini (keamanan).
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'identity_number' => $validated['identity_number'],
            'class_id' => $validated['class_id'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);

        $user->assignRole('student');

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user' => new UserResource($user->load('schoolClass')),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $loginField = filter_var($validated['login'], FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'identity_number';

        $credentials = [
            $loginField => $validated['login'],
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials, remember: true)) {
            throw ValidationException::withMessages([
                'login' => ['Email/NIS atau password salah.'],
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => ['Akun kamu telah dinonaktifkan. Hubungi admin.'],
            ]);
        }

        $request->user()->load('schoolClass');

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Logout — hapus session, invalidate token.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Ambil data user yang sedang login — dipanggil frontend
     * saat inisialisasi app untuk cek status auth & role.
     */
    public function me(Request $request)
    {
        $request->user()->load('schoolClass');

        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
