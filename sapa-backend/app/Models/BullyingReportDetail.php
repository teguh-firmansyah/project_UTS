<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BullyingReportDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'reporter_relation',
        'incident_date',
        'handled_by_counselor_id',
        'handling_notes',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_counselor_id');
    }
}
