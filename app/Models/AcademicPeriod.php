<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicPeriod extends Model
{
    protected $fillable = [
        'name',
        'academic_year',
        'semester',
        'starts_on',
        'ends_on',
        'ats_starts_on',
        'ats_ends_on',
        'uas_starts_on',
        'uas_ends_on',
        'replacement_deadline',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'ats_starts_on' => 'date',
            'ats_ends_on' => 'date',
            'uas_starts_on' => 'date',
            'uas_ends_on' => 'date',
            'replacement_deadline' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CourseSection, $this>
     */
    public function courseSections(): HasMany
    {
        return $this->hasMany(CourseSection::class);
    }
}
