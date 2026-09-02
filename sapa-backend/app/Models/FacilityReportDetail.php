<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityReportDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'location',
        'category',
        'damage_level',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
