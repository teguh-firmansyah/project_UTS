<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AspirationReportDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'category',
        'upvotes_count',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
