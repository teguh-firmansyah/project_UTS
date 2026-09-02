<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAttachment extends Model
{
    use HasFactory;

    public $timestamps = false; // hanya created_at

    protected $fillable = [
        'report_id',
        'file_path',
        'file_type',
        'file_size',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    // Accessor untuk full URL file
    protected function url(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn() => asset('storage/' . $this->file_path),
        );
    }
}
