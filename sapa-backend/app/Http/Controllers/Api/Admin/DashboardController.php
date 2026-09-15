<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Statistik menyeluruh untuk dashboard admin.
     * KHUSUS bullying: hanya angka agregat, tidak ada isi laporan sama sekali.
     */
    public function analytics(Request $request)
    {
        // Ringkasan total per tipe
        $byType = Report::selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        // Ringkasan total per status (lintas semua tipe)
        $byStatus = Report::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Tren bulanan (6 bulan terakhir) — untuk line/bar chart
        $monthlyTrend = Report::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as total")
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($row) => ['month' => $row->month, 'total' => $row->total]);

        // Rata-rata waktu penyelesaian (dalam jam), hanya laporan resolved
        $avgResolutionHours = Report::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        // Resolve rate keseluruhan
        $total = Report::count();
        $resolved = Report::where('status', 'resolved')->count();

        return response()->json([
            'summary' => [
                'total_reports' => $total,
                'resolved' => $resolved,
                'resolve_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
                'avg_resolution_hours' => $avgResolutionHours ? round($avgResolutionHours, 1) : null,
            ],
            'by_type' => [
                'aspiration' => $byType['aspiration'] ?? 0,
                'facility' => $byType['facility'] ?? 0,
                // bullying: HANYA angka, tidak pernah expose detail apapun di endpoint ini
                'bullying' => $byType['bullying'] ?? 0,
            ],
            'by_status' => [
                'pending' => $byStatus['pending'] ?? 0,
                'reviewing' => $byStatus['reviewing'] ?? 0,
                'in_progress' => $byStatus['in_progress'] ?? 0,
                'resolved' => $byStatus['resolved'] ?? 0,
                'rejected' => $byStatus['rejected'] ?? 0,
            ],
            'monthly_trend' => $monthlyTrend,
            'user_counts' => [
                'total' => User::count(),
                'students' => User::role('student')->count(),
                'staff' => User::role('staff')->count(),
                'counselors' => User::role('counselor')->count(),
                'admins' => User::role('admin')->count(),
            ],
        ]);
    }

    /**
     * List semua laporan lintas tipe untuk halaman Manajemen Laporan.
     * Untuk baris bullying, field description/detail TIDAK di-load —
     * hanya metadata (status, tanggal, prioritas).
     */
    public function allReports(Request $request)
    {
        $query = Report::query()->with(['reporter:id,name']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('report_code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reports = $query->latest()->paginate(20);

        // Transform manual (bukan ReportResource) supaya eksplisit:
        // field description SENGAJA tidak pernah disertakan di sini,
        // untuk SEMUA tipe laporan (bukan hanya bullying) — halaman ini
        // adalah tabel manajemen, bukan tempat membaca isi laporan.
        $reports->getCollection()->transform(function ($report) {
            return [
                'id' => $report->id,
                'report_code' => $report->report_code,
                'type' => $report->type,
                'title' => $report->title,
                'status' => $report->status,
                'priority' => $report->priority,
                'is_anonymous' => $report->is_anonymous,
                'reporter' => ! $report->is_anonymous && $report->reporter
                    ? ['name' => $report->reporter->name]
                    : null,
                'created_at' => $report->created_at->toIso8601String(),
            ];
        });

        return response()->json($reports);
    }

    /**
     * Export laporan ke CSV — sederhana, tanpa dependency tambahan.
     * Untuk laporan bullying, kolom deskripsi/detail dikosongkan.
     */
    public function export(Request $request)
    {
        $query = Report::query()->with('reporter:id,name');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $reports = $query->latest()->get();

        $filename = 'laporan-sapa-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Kode Laporan', 'Tipe', 'Judul', 'Status', 'Prioritas', 'Pelapor', 'Tanggal Dibuat']);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->report_code,
                    $report->type,
                    $report->title,
                    $report->status,
                    $report->priority,
                    $report->is_anonymous ? 'Anonim' : ($report->reporter->name ?? '-'),
                    $report->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
