<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSectionLecturer extends Model
{
    protected $fillable = [
        'course_section_id',
        'user_id',
        'teaching_type',
    ];

    /**
     * @return BelongsTo<CourseSection, $this>
     */
    public function courseSection(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
