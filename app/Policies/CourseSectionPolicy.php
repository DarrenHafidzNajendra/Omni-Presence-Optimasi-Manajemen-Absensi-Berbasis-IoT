<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CourseSection;
use App\Models\User;

class CourseSectionPolicy
{
    public function manageAttendance(User $user, CourseSection $courseSection): bool
    {
        if ($user->role === UserRole::Admin->value) {
            return true;
        }

        return $user->role === UserRole::Dosen->value
            && $user->is_active
            && $courseSection->lecturers()
                ->where('user_id', $user->getKey())
                ->exists();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CourseSection $courseSection): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CourseSection $courseSection): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CourseSection $courseSection): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CourseSection $courseSection): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CourseSection $courseSection): bool
    {
        return false;
    }
}
