<?php

use App\Http\Controllers\AcademicPeriodController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseSectionController;
use App\Http\Controllers\CourseSectionLecturerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'role:admin,dosen,mahasiswa'])
    ->name('dashboard');

Route::get('/users', [UserController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('users.index');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/academic-periods', [AcademicPeriodController::class, 'index'])->name('academic-periods.index');
    Route::get('/academic-periods/create', [AcademicPeriodController::class, 'create'])->name('academic-periods.create');
    Route::post('/academic-periods', [AcademicPeriodController::class, 'store'])->name('academic-periods.store');
    Route::get('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'show'])->name('academic-periods.show');
    Route::get('/academic-periods/{academicPeriod}/edit', [AcademicPeriodController::class, 'edit'])->name('academic-periods.edit');
    Route::put('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'update'])->name('academic-periods.update');
    Route::patch('/academic-periods/{academicPeriod}/status', [AcademicPeriodController::class, 'updateStatus'])->name('academic-periods.status');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::patch('/courses/{course}/status', [CourseController::class, 'updateStatus'])->name('courses.status');

    Route::get('/course-sections', [CourseSectionController::class, 'index'])->name('course-sections.index');
    Route::get('/course-sections/create', [CourseSectionController::class, 'create'])->name('course-sections.create');
    Route::post('/course-sections', [CourseSectionController::class, 'store'])->name('course-sections.store');
    Route::get('/course-sections/{courseSection}', [CourseSectionController::class, 'show'])->name('course-sections.show');
    Route::get('/course-sections/{courseSection}/edit', [CourseSectionController::class, 'edit'])->name('course-sections.edit');
    Route::put('/course-sections/{courseSection}', [CourseSectionController::class, 'update'])->name('course-sections.update');
    Route::patch('/course-sections/{courseSection}/status', [CourseSectionController::class, 'updateStatus'])->name('course-sections.status');
    Route::post('/course-sections/{courseSection}/lecturers', [CourseSectionLecturerController::class, 'store'])->name('course-sections.lecturers.store');
    Route::scopeBindings()->group(function (): void {
        Route::get('/course-sections/{courseSection}/enrollments', [EnrollmentController::class, 'index'])->name('course-sections.enrollments.index');
        Route::post('/course-sections/{courseSection}/enrollments', [EnrollmentController::class, 'store'])->name('course-sections.enrollments.store');
        Route::patch('/course-sections/{courseSection}/enrollments/{enrollment}/status', [EnrollmentController::class, 'updateStatus'])->name('course-sections.enrollments.status');
        Route::delete('/course-sections/{courseSection}/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('course-sections.enrollments.destroy');
        Route::get('/course-sections/{courseSection}/schedules', [ClassScheduleController::class, 'index'])->name('course-sections.schedules.index');
        Route::get('/course-sections/{courseSection}/schedules/create', [ClassScheduleController::class, 'create'])->name('course-sections.schedules.create');
        Route::post('/course-sections/{courseSection}/schedules', [ClassScheduleController::class, 'store'])->name('course-sections.schedules.store');
        Route::get('/course-sections/{courseSection}/schedules/{schedule}/edit', [ClassScheduleController::class, 'edit'])->name('course-sections.schedules.edit');
        Route::put('/course-sections/{courseSection}/schedules/{schedule}', [ClassScheduleController::class, 'update'])->name('course-sections.schedules.update');
    });

    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
});
