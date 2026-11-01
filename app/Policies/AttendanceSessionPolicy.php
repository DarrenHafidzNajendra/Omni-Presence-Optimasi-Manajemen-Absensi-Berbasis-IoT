<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AttendanceSession;
use App\Models\CourseSection;
use App\Models\User;

class AttendanceSessionPolicy
{
    /**
     * Determine whether the user can manage the attendance session.
     */
    public function viewAny(User $user, ?CourseSection $courseSection = null): bool
    {
        return $this->create($user, $courseSection);
    }

    /**
     * Determine whether the user can manage the attendance session.
     */
    public function manage(User $user, AttendanceSession $attendanceSession, ?CourseSection $courseSection = null): bool
    {
        $section = $attendanceSession->courseSection;

        if ($section === null) {
            return false;
        }

        if ($courseSection !== null && $section->getKey() !== $courseSection->getKey()) {
            return false;
        }

        if ((int) $attendanceSession->course_section_id !== (int) $section->getKey()) {
            return false;
        }

        if ($user->role === UserRole::Admin->value) {
            return true;
        }

        return $user->role === UserRole::Dosen->value
            && $user->is_active
            && $section->lecturers()
                ->where('user_id', $user->getKey())
                ->exists();
    }

    /**
     * Determine whether the user can manage attendance for the session.
     */
    public function manageAttendance(User $user, AttendanceSession $attendanceSession, ?CourseSection $courseSection = null): bool
    {
        return $this->manage($user, $attendanceSession, $courseSection);
    }

    /**
     * Determine whether the user can view the attendance session.
     */
    public function view(User $user, AttendanceSession $attendanceSession, ?CourseSection $courseSection = null): bool
    {
        return $this->manage($user, $attendanceSession, $courseSection);
    }

    /**
     * Determine whether the user can create attendance sessions for the course section.
     */
    public function create(User $user, ?CourseSection $courseSection = null): bool
    {
        if ($user->role === UserRole::Admin->value) {
            return true;
        }

        if ($courseSection === null) {
            return false;
        }

        return $user->role === UserRole::Dosen->value
            && $user->is_active
            && $courseSection->lecturers()
                ->where('user_id', $user->getKey())
                ->exists();
    }

    /**
     * Determine whether the user can update the attendance session.
     */
    public function update(User $user, AttendanceSession $attendanceSession, ?CourseSection $courseSection = null): bool
    {
        return $this->manage($user, $attendanceSession, $courseSection);
    }

    /**
     * Determine whether the user can delete the attendance session.
     */
    public function delete(User $user, AttendanceSession $attendanceSession, ?CourseSection $courseSection = null): bool
    {
        return $this->manage($user, $attendanceSession, $courseSection);
    }

    /**
     * Determine whether the user can open the attendance session.
     */
    public function open(User $user, AttendanceSession $attendanceSession, ?CourseSection $courseSection = null): bool
    {
        return $this->manage($user, $attendanceSession, $courseSection);
    }

    /**
     * Determine whether the user can close the attendance session.
     */
    public function close(User $user, AttendanceSession $attendanceSession, ?CourseSection $courseSection = null): bool
    {
        return $this->manage($user, $attendanceSession, $courseSection);
    }

    /**
     * Determine whether the user can cancel the attendance session.
     */
    public function cancel(User $user, AttendanceSession $attendanceSession, ?CourseSection $courseSection = null): bool
    {
        return $this->manage($user, $attendanceSession, $courseSection);
    }
}
