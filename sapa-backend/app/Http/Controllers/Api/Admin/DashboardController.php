<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function analytics(Request $request)
    {
        $byType = Report::selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $byStatus = Report::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $monthlyTrendRaw = Report::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, type, count(*) as total")
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get();

        $months = collect(range(5, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));
        $monthlyTrend = [
            'labels' => $months->map(fn($m) => \Carbon\Carbon::createFromFormat('Y-m', $m)->translatedFormat('M'))->values(),
            'facility' => $months->map(fn($m) => $monthlyTrendRaw->where('month', $m)->where('type', 'facility')->first()->total ?? 0)->values(),
            'aspiration' => $months->map(fn($m) => $monthlyTrendRaw->where('month', $m)->where('type', 'aspiration')->first()->total ?? 0)->values(),
            'bullying' => $months->map(fn($m) => $monthlyTrendRaw->where('month', $m)->where('type', 'bullying')->first()->total ?? 0)->values(),
        ];

        $avgResolutionHours = Report::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        $total = Report::count();
        $resolved = Report::where('status', 'resolved')->count();
        $pending = Report::whereIn('status', ['pending', 'reviewing', 'in_progress'])->count();

        $recentActivities = \App\Models\ReportStatusLog::with(['report:id,report_code,type,title'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($log) {
                $type = $log->report->type;
                $isBullying = $type === 'bullying';

                $typeLabel = match ($type) {
                    'facility' => 'Fasilitas',
                    'aspiration' => 'Aspirasi',
                    'bullying' => 'Perundungan',
                };

                $text = $isBullying
                    ? "Kasus perundungan {$log->report->report_code} diperbarui — detail hanya dapat diakses Guru BK"
                    : "{$log->report->title} diperbarui menjadi status " . strtoupper($log->new_status);

                return [
                    'id' => $log->id,
                    'code' => $log->report->report_code,
                    'type' => $typeLabel,
                    'text' => $text,
                    'time' => $log->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'summary' => [
                'total_reports' => $total,
                'resolved' => $resolved,
                'pending' => $pending,
                'resolve_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
                'avg_resolution_days' => $avgResolutionHours ? round($avgResolutionHours / 24, 1) : 0,
            ],
            'by_type' => [
                'aspiration' => $byType['aspiration'] ?? 0,
                'facility' => $byType['facility'] ?? 0,
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
            'recent_activities' => $recentActivities,
            'user_counts' => [
                'total' => User::count(),
                'students' => User::role('student')->count(),
                'staff' => User::role('staff')->count(),
                'counselors' => User::role('counselor')->count(),
                'admins' => User::role('admin')->count(),
            ],
        ]);
    }

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

    public function export(Request $request)
    {
        $query = Report::query()->with('reporter:id,name');

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $reports = $query->latest()->get();

        $filename = 'laporan-sapa-' . now()->format('Y-m-d') . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Kode Laporan', 'Judul', 'Tipe', 'Pelapor', 'Status', 'Prioritas', 'Tanggal']);
            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->report_code,
                    $report->type === 'bullying' ? '[TERKUNCI] Laporan Perundungan' : $report->title,
                    $report->type,
                    $report->is_anonymous ? 'Anonim' : ($report->reporter->name ?? '-'),
                    $report->status,
                    $report->priority,
                    $report->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bullyingMetadata(Request $request)
    {
        $stats = Report::ofType('bullying')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'total' => $stats->sum(),
            'waiting' => $stats['pending'] ?? 0,
            'in_process' => ($stats['reviewing'] ?? 0) + ($stats['in_progress'] ?? 0),
            'resolved' => $stats['resolved'] ?? 0,
        ]);
    }

    public function aspirations(Request $request)
    {
        $query = Report::query()
            ->ofType('aspiration')
            ->with(['reporter:id,name,class_name', 'aspirationDetail'])
            ->withCount('comments');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->whereHas('aspirationDetail', function ($q) use ($request) {
                $q->where('category', $request->category);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $aspirations = $query->latest()->paginate(20);

        $aspirations->getCollection()->transform(function ($report) {
            return [
                'id' => $report->id,
                'report_code' => $report->report_code,
                'title' => $report->title,
                'description_excerpt' => str($report->description)->limit(150)->toString(),
                'status' => $report->status,
                'is_anonymous' => $report->is_anonymous,
                'reporter' => ! $report->is_anonymous && $report->reporter
                    ? ['name' => $report->reporter->name, 'class_name' => $report->reporter->class_name]
                    : null,
                'category' => $report->aspirationDetail?->category,
                'upvotes_count' => $report->aspirationDetail?->upvotes_count ?? 0,
                'is_public' => $report->aspirationDetail?->is_public ?? true,
                'comments_count' => $report->comments_count,
                'created_at' => $report->created_at->toIso8601String(),
            ];
        });

        return response()->json($aspirations);
    }
}
