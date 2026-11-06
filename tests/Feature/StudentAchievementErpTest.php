<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class StudentAchievementErpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        config(['database.default' => 'achievement_test', 'database.connections.achievement_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::statement('CREATE TABLE student (student_id INTEGER, class_id INTEGER, section_id INTEGER, academic_yr TEXT, first_name TEXT, mid_name TEXT, last_name TEXT, roll_no INTEGER)');
        DB::statement('CREATE TABLE class (class_id INTEGER, name TEXT)');
        DB::statement('CREATE TABLE section (section_id INTEGER, name TEXT)');
        DB::statement('CREATE TABLE student_achievements (id INTEGER, student_id INTEGER, academic_year TEXT, title TEXT, achievement_date TEXT, is_verified INTEGER, is_external INTEGER)');
        DB::statement('CREATE TABLE achievement_files (id INTEGER, achievement_id INTEGER, file_url TEXT, file_type TEXT)');

        DB::table('class')->insert(['class_id' => 1, 'name' => 'Class 1']);
        DB::table('section')->insert(['section_id' => 2, 'name' => 'A']);
        foreach ([[10, 1, 2, '2026-2027'], [11, 1, 2, '2026-2027'], [12, 1, 3, '2026-2027'], [13, 2, 2, '2026-2027'], [14, 1, 2, '2025-2026']] as [$id, $class, $section, $year]) {
            DB::table('student')->insert([
                'student_id' => $id, 'class_id' => $class, 'section_id' => $section,
                'academic_yr' => $year, 'first_name' => 'Student', 'mid_name' => null,
                'last_name' => (string) $id, 'roll_no' => $id,
            ]);
        }
        foreach ([[1, 10, '2026-2027', '2026-09-01'], [2, 10, '2026-2027', '2026-10-01'], [3, 11, '2026-2027', '2026-10-02'], [4, 12, '2026-2027', '2026-10-01'], [5, 13, '2026-2027', '2026-10-01'], [6, 14, '2025-2026', '2025-10-01'], [7, 10, '2025-2026', '2025-10-01']] as [$id, $student, $year, $date]) {
            DB::table('student_achievements')->insert([
                'id' => $id, 'student_id' => $student, 'academic_year' => $year,
                'title' => 'Achievement ' . $id, 'achievement_date' => $date,
                'is_verified' => 0, 'is_external' => 1,
            ]);
        }
        DB::table('achievement_files')->insert([
            'id' => 1, 'achievement_id' => 2, 'file_url' => 'school/achievements/certificate.pdf', 'file_type' => 'pdf',
        ]);
        $this->token();
    }

    private function token(string $role = 'A', ?string $year = '2026-2027'): void
    {
        JWTAuth::swap(\Mockery::mock(\Tymon\JWTAuth\JWTAuth::class));
        JWTAuth::shouldReceive('parseToken')->andReturnSelf();
        JWTAuth::shouldReceive('authenticate')->andReturn((object) ['role_id' => $role]);
        JWTAuth::shouldReceive('getPayload')->andReturn(collect(['role_id' => $role, 'academic_year' => $year]));
    }

    public function test_class_and_section_are_required_and_student_filter_is_validated(): void
    {
        $this->getJson('/api/student/achievements/erp')
            ->assertStatus(422)->assertJsonValidationErrors(['class_id', 'section_id']);
        $this->getJson('/api/student/achievements/erp?class_id=1&section_id=2&student_id=abc')
            ->assertStatus(422)->assertJsonValidationErrors(['student_id']);
    }

    public function test_listing_is_scoped_sorted_and_enriched_with_attachments(): void
    {
        $response = $this->getJson('/api/student/achievements/erp?class_id=1&section_id=2')
            ->assertOk()->assertJsonPath('meta.total', 3)
            ->assertJsonPath('filters.academic_year', '2026-2027')
            ->assertJsonPath('data.0.student_name', 'Student 10')
            ->assertJsonPath('data.0.class_name', 'Class 1')
            ->assertJsonPath('data.0.section_name', 'A')
            ->assertJsonPath('data.0.is_external', true)
            ->assertJsonPath('data.0.is_verified', false)
            ->assertJsonPath('data.0.files_count', 1)
            ->assertJsonPath('data.0.files.0.file_url', asset('storage/school/achievements/certificate.pdf'))
            ->assertJsonPath('data.1.files', []);
        $this->assertSame([2, 1, 3], array_column($response->json('data'), 'id'));
    }

    public function test_optional_student_filter_returns_all_matches_and_empty_results(): void
    {
        $this->getJson('/api/student/achievements/erp?class_id=1&section_id=2&student_id=10&per_page=1&page=2')
            ->assertOk()->assertJsonPath('meta.total', 2)
            ->assertJsonMissingPath('meta.current_page')->assertJsonMissingPath('meta.per_page')
            ->assertJsonMissingPath('meta.last_page')
            ->assertJsonPath('data.0.id', 2)->assertJsonPath('data.1.id', 1)->assertJsonCount(2, 'data');
        $this->getJson('/api/student/achievements/erp?class_id=1&section_id=2&student_id=12')
            ->assertOk()->assertJsonPath('meta.total', 0)->assertJsonPath('data', []);
    }

    public function test_academic_year_can_be_selected_explicitly(): void
    {
        $this->getJson('/api/student/achievements/erp?class_id=1&section_id=2&academic_year=2025-2026')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', 6);
    }

    public function test_parent_and_student_tokens_cannot_access_erp_listing(): void
    {
        foreach (['P', 'S'] as $role) {
            $this->token($role);
            $this->getJson('/api/student/achievements/erp?class_id=1&section_id=2')->assertForbidden();
        }
    }

    public function test_missing_academic_year_does_not_return_all_years(): void
    {
        $this->token('A', null);
        $this->getJson('/api/student/achievements/erp?class_id=1&section_id=2')
            ->assertStatus(422)->assertJsonValidationErrors(['academic_year']);
    }
}
