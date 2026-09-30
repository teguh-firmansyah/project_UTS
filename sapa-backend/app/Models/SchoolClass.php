<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'grade',
        'major',
        'name',
        'sequence',
        'academic_year',
    ];

    protected static function booted(): void
    {
        static::creating(function (SchoolClass $class) {
            if (! $class->name) {
                $class->name = "{$class->grade} {$class->major} {$class->sequence}";
            }
        });
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }
}
