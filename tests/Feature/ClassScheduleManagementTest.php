<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_schedule_crud(): void
    {
        $section = $this->createCourseSection();

        $this->get(route('course-sections.schedules.index', $section))->assertRedirectToRoute('login');
        $this->get(route('course-sections.schedules.create', $section))->assertRedirectToRoute('login');
        $this->post(route('course-sections.schedules.store', $section), $this->scheduleData())->assertRedirectToRoute('login');
    }

    public function test_only_admin_can_access_schedule_crud(): void
    {
        $section = $this->createCourseSection();

        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('course-sections.schedules.index', $section))->assertForbidden();
            $this->actingAs($user)->post(route('course-sections.schedules.store', $section), $this->scheduleData())->assertForbidden();
        }
    }

    public function test_admin_can_view_create_and_edit_schedule(): void
    {
        $section = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $schedule = $this->createSchedule($section);

        $this->actingAs($admin)->get(route('course-sections.schedules.index', $section))
            ->assertOk()
            ->assertSeeText('Senin')
            ->assertSeeText('Ruang A');
        $this->actingAs($admin)->get(route('course-sections.schedules.create', $section))->assertOk();
        $this->actingAs($admin)->get(route('course-sections.schedules.edit', [$section, $schedule]))->assertOk();
    }

    public function test_admin_can_create_and_update_schedule_for_the_correct_section(): void
    {
        $section = $this->createCourseSection();
        $otherSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('course-sections.schedules.store', $section), $this->scheduleData())
            ->assertRedirectToRoute('course-sections.schedules.index', $section);
        $schedule = ClassSchedule::query()->firstOrFail();
        $this->assertSame($section->id, $schedule->course_section_id);

        $this->actingAs($admin)->put(route('course-sections.schedules.update', [$section, $schedule]), $this->scheduleData([
            'location' => 'Ruang B',
        ]))->assertRedirectToRoute('course-sections.schedules.index', $section);
        $this->assertDatabaseHas('class_schedules', ['id' => $schedule->id, 'location' => 'Ruang B']);

        $this->actingAs($admin)->get(route('course-sections.schedules.edit', [$otherSection, $schedule]))->assertNotFound();
        $this->actingAs($admin)->put(route('course-sections.schedules.update', [$otherSection, $schedule]), $this->scheduleData())->assertNotFound();
    }

    public function test_missing_section_or_schedule_returns_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $section = $this->createCourseSection();

        $this->actingAs($admin)->get(route('course-sections.schedules.index', 999999))->assertNotFound();
        $this->actingAs($admin)->get(route('course-sections.schedules.edit', [$section, 999999]))->assertNotFound();
    }

    public function test_delivery_mode_requires_the_relevant_location_or_url(): void
    {
        $section = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->from(route('course-sections.schedules.create', $section))
            ->post(route('course-sections.schedules.store', $section), $this->scheduleData(['location' => null]))
            ->assertRedirect(route('course-sections.schedules.create', $section))
            ->assertSessionHasErrors('location');
        $this->actingAs($admin)->from(route('course-sections.schedules.create', $section))
            ->post(route('course-sections.schedules.store', $section), $this->scheduleData([
                'delivery_mode' => 'online',
                'location' => null,
                'meeting_url' => null,
            ]))
            ->assertRedirect(route('course-sections.schedules.create', $section))
            ->assertSessionHasErrors('meeting_url');
        $this->actingAs($admin)->from(route('course-sections.schedules.create', $section))
            ->post(route('course-sections.schedules.store', $section), $this->scheduleData([
                'delivery_mode' => 'online',
                'location' => null,
                'meeting_url' => 'not-a-url',
            ]))
            ->assertRedirect(route('course-sections.schedules.create', $section))
            ->assertSessionHasErrors('meeting_url');
    }

    public function test_time_date_and_enum_validation_is_enforced(): void
    {
        $section = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->from(route('course-sections.schedules.create', $section))
            ->post(route('course-sections.schedules.store', $section), $this->scheduleData([
                'session_type' => 'kuliah',
                'delivery_mode' => 'hybrid',
            ]))
            ->assertRedirect(route('course-sections.schedules.create', $section))
            ->assertSessionHasErrors(['session_type', 'delivery_mode']);

        $this->actingAs($admin)->from(route('course-sections.schedules.create', $section))
            ->post(route('course-sections.schedules.store', $section), $this->scheduleData([
                'ends_at' => '08:00',
                'valid_from' => '2026-12-01',
                'valid_until' => '2026-11-01',
            ]))
            ->assertRedirect(route('course-sections.schedules.create', $section))
            ->assertSessionHasErrors(['ends_at', 'valid_until']);
    }

    public function test_valid_date_range_is_accepted_and_date_format_is_enforced(): void
    {
        $section = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('course-sections.schedules.store', $section), $this->scheduleData([
                'valid_from' => '2026-11-01',
                'valid_until' => '2027-01-31',
            ]))
            ->assertRedirectToRoute('course-sections.schedules.index', $section);

        $schedule = ClassSchedule::query()
            ->where('course_section_id', $section->id)
            ->firstOrFail();

        $this->assertSame('2026-11-01', $schedule->valid_from->format('Y-m-d'));
        $this->assertSame('2027-01-31', $schedule->valid_until->format('Y-m-d'));

        $this->actingAs($admin)
            ->from(route('course-sections.schedules.create', $section))
            ->post(route('course-sections.schedules.store', $section), $this->scheduleData([
                'day_of_week' => 2,
                'valid_from' => '2026-1-01',
                'valid_until' => '2026-10-01',
            ]))
            ->assertRedirect(route('course-sections.schedules.create', $section))
            ->assertSessionHasErrors(['valid_from']);
    }

    public function test_active_schedules_cannot_overlap_in_the_same_section(): void
    {
        $section = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createSchedule($section);

        $this->actingAs($admin)->from(route('course-sections.schedules.create', $section))
            ->post(route('course-sections.schedules.store', $section), $this->scheduleData(['location' => 'Ruang B']))
            ->assertRedirect(route('course-sections.schedules.create', $section))
            ->assertSessionHasErrors('starts_at');
    }

    public function test_active_onsite_schedules_cannot_overlap_in_the_same_room_across_sections(): void
    {
        $section = $this->createCourseSection();
        $otherSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createSchedule($section);

        $this->actingAs($admin)->from(route('course-sections.schedules.create', $otherSection))
            ->post(route('course-sections.schedules.store', $otherSection), $this->scheduleData())
            ->assertRedirect(route('course-sections.schedules.create', $otherSection))
            ->assertSessionHasErrors('starts_at');
    }

    public function test_non_overlapping_periods_and_inactive_schedules_do_not_conflict(): void
    {
        $section = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createSchedule($section, ['valid_until' => '2026-11-30']);

        $this->actingAs($admin)->post(route('course-sections.schedules.store', $section), $this->scheduleData([
            'valid_from' => '2026-12-01',
        ]))->assertRedirectToRoute('course-sections.schedules.index', $section);

        $otherSection = $this->createCourseSection();
        $this->createSchedule($otherSection, ['is_active' => false]);
        $this->actingAs($admin)->post(route('course-sections.schedules.store', $section), $this->scheduleData([
            'day_of_week' => 2,
            'location' => 'Ruang A',
        ]))->assertRedirectToRoute('course-sections.schedules.index', $section);
    }

    public function test_updating_a_schedule_does_not_conflict_with_itself(): void
    {
        $section = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $schedule = $this->createSchedule($section);

        $this->actingAs($admin)->put(route('course-sections.schedules.update', [$section, $schedule]), $this->scheduleData())
            ->assertRedirectToRoute('course-sections.schedules.index', $section);
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduleData(array $overrides = []): array
    {
        return array_merge([
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Ruang A',
            'meeting_url' => null,
            'valid_from' => '2026-10-01',
            'valid_until' => '2027-01-31',
            'is_active' => true,
        ], $overrides);
    }

    private function createSchedule(CourseSection $section, array $overrides = []): ClassSchedule
    {
        return $section->schedules()->create($this->scheduleData($overrides));
    }

    private function createCourseSection(): CourseSection
    {
        $course = Course::query()->create([
            'code' => 'CS'.fake()->unique()->numerify('###'),
            'name' => 'Mata Kuliah '.fake()->unique()->numerify('###'),
        ]);

        return CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
    }
}
