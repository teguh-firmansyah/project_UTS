<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'report_code',
        'reporter_id',
        'type',
        'title',
        'description',
        'status',
        'priority',
        'is_anonymous',
        'assigned_to',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    // Auto-generate report_code saat create
    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            $report->report_code = static::generateReportCode();
        });
    }

    protected static function generateReportCode(): string
    {
        $year = now()->year;
        $lastNumber = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('SAPA-%d-%04d', $year, $lastNumber);
    }

    // Relasi ke user pelapor (nullable kalau anonim)
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Relasi 1:1 ke detail per tipe
    public function aspirationDetail(): HasOne
    {
        return $this->hasOne(AspirationReportDetail::class);
    }

    public function facilityDetail(): HasOne
    {
        return $this->hasOne(FacilityReportDetail::class);
    }

    public function bullyingDetail(): HasOne
    {
        return $this->hasOne(BullyingReportDetail::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReportComment::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReportStatusLog::class)->latest();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    // Scope untuk query umum
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // Helper untuk load detail sesuai tipe secara dinamis
    public function loadTypeDetail(): static
    {
        return match ($this->type) {
            'aspiration' => $this->load('aspirationDetail'),
            'facility' => $this->load('facilityDetail'),
            'bullying' => $this->load('bullyingDetail'),
            default => $this,
        };
    }
}
