<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportStatusLog extends Model
{
    public $timestamps = false; // hanya created_at

    protected $fillable = [
        'report_id',
        'old_status',
        'new_status',
        'changed_by',
        'note',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
