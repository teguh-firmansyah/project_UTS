<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function index(Request $request)
    {
        $query = SchoolClass::withCount('students');

        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }
        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $classes = $query->orderBy('grade')->orderBy('major')->orderBy('sequence')->paginate(20);

        return response()->json($classes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade' => ['required', 'string', 'max:10'],
            'major' => ['required', 'string', 'max:50'],
            'sequence' => ['required', 'integer', 'min:1'],
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
        ]);

        $class = SchoolClass::create($validated);

        return response()->json([
            'message' => 'Kelas berhasil ditambahkan.',
            'class' => $class,
        ], 201);
    }

    public function update(Request $request, SchoolClass $class)
    {
        $validated = $request->validate([
            'grade' => ['sometimes', 'string', 'max:10'],
            'major' => ['sometimes', 'string', 'max:50'],
            'sequence' => ['sometimes', 'integer', 'min:1'],
            'academic_year' => ['sometimes', 'regex:/^\d{4}\/\d{4}$/'],
        ]);

        $class->update($validated);
        // Update juga nama gabungannya
        $class->update(['name' => "{$class->grade} {$class->major} {$class->sequence}"]);

        return response()->json([
            'message' => 'Kelas berhasil diperbarui.',
            'class' => $class->fresh(),
        ]);
    }

    public function destroy(SchoolClass $class)
    {
        if ($class->students()->exists()) {
            return response()->json([
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki siswa terdaftar.',
            ], 422);
        }

        $class->delete();

        return response()->json(['message' => 'Kelas berhasil dihapus.']);
    }

    /**
     * Daftar kelas ringkas untuk dropdown (dipakai di form registrasi/manajemen user)
     */
    public function options(Request $request)
    {
        $query = SchoolClass::select('id', 'name', 'academic_year');

        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        $classes = $query->orderBy('grade')->orderBy('major')->orderBy('sequence')->get();

        return response()->json($classes);
    }

    public function academicYears()
    {
        $years = SchoolClass::select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        return response()->json($years);
    }
}
