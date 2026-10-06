<?php

namespace Tests\Unit;

use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentReportTest extends TestCase
{
    public function test_report_batches_queries_and_preserves_marks_attendance_and_order(): void
    {
        config(['database.default' => 'report_test', 'database.connections.report_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        $db = DB::connection();
        $db->getPdo()->sqliteCreateFunction('IF', fn ($condition, $yes, $no) => $condition ? $yes : $no, 3);
        foreach ([
            'CREATE TABLE student (student_id INTEGER PRIMARY KEY, parent_id INTEGER, first_name TEXT, class_id INTEGER, section_id INTEGER, house INTEGER, isDelete TEXT, academic_yr TEXT, roll_no INTEGER)',
            'CREATE TABLE parent (parent_id INTEGER PRIMARY KEY)',
            'CREATE TABLE class (class_id INTEGER PRIMARY KEY, name TEXT)',
            'CREATE TABLE section (section_id INTEGER PRIMARY KEY, name TEXT)',
            'CREATE TABLE house (house_id INTEGER PRIMARY KEY, house_name TEXT)',
            'CREATE TABLE student_marks (student_id INTEGER, subject_id INTEGER, class_id INTEGER, exam_id INTEGER, total_marks REAL, highest_total_marks REAL, publish TEXT)',
            'CREATE TABLE subjects_on_report_card (sub_rc_master_id INTEGER, class_id INTEGER, subject_type TEXT)',
            'CREATE TABLE exam (exam_id INTEGER, name TEXT)',
            'CREATE TABLE attendance (student_id INTEGER, academic_yr TEXT, attendance_status INTEGER)',
        ] as $sql) {
            $db->statement($sql);
        }
        DB::table('section')->insert([['section_id' => 560, 'name' => 'A'], ['section_id' => 561, 'name' => 'B']]);
        DB::table('exam')->insert([['exam_id' => 1, 'name' => 'Final Exam'], ['exam_id' => 2, 'name' => 'Term Exam']]);
        foreach ([1 => '9', 2 => '11', 3 => '12', 4 => '8'] as $id => $name) {
            DB::table('class')->insert(['class_id' => $id, 'name' => $name]);
            DB::table('parent')->insert(['parent_id' => $id]);
            $student = ['parent_id' => $id, 'first_name' => 'Student ' . $id, 'class_id' => $id,
                'section_id' => 560, 'isDelete' => 'N', 'academic_yr' => '2025-2026', 'roll_no' => $id];
            DB::table('student')->insert(['student_id' => $id] + $student);
            $student['academic_yr'] = '2026-2027';
            DB::table('student')->insert(['student_id' => 100 + $id] + $student);
            DB::table('subjects_on_report_card')->insert([
                ['sub_rc_master_id' => $id, 'class_id' => $id, 'subject_type' => 'Scholastic'],
                ['sub_rc_master_id' => 10 + $id, 'class_id' => $id, 'subject_type' => 'Co-Scholastic_hsc'],
            ]);
            foreach ([[1, 80, $id, 'Y'], [2, 20, $id, 'Y'], [1, 100, 10 + $id, 'Y'], [1, 0, $id, 'N']] as [$exam, $marks, $subject, $publish]) {
                DB::table('student_marks')->insert(['student_id' => $id, 'class_id' => $id,
                    'subject_id' => $subject, 'exam_id' => $exam, 'total_marks' => $marks,
                    'highest_total_marks' => 100, 'publish' => $publish]);
            }
            if ($id !== 4) {
                foreach ([0, 0, 1] as $status) {
                    DB::table('attendance')->insert(['student_id' => $id, 'academic_yr' => '2025-2026', 'attendance_status' => $status]);
                }
            }
        }
        JWTAuth::shouldReceive('parseToken')->andReturnSelf();
        JWTAuth::shouldReceive('authenticate')->andReturn((object) ['id' => 1]);
        JWTAuth::shouldReceive('getPayload')->andReturn(collect(['academic_year' => '2026-2027']));
        $controller = (new \ReflectionClass(ReportController::class))->newInstanceWithoutConstructor();
        $request = new Request(['class_section' => '4^560,3^560,2^560,1^560,1^561']);
        $db->enableQueryLog();
        $runReport = function () use ($controller, $request, $db) {
            $db->flushQueryLog();
            $response = $controller->getStudentReport($request);
            $this->assertSame(200, $response->getStatusCode());
            return [json_decode($response->getContent(), true)['data'], count($db->getQueryLog())];
        };
        [$rows, $queryCount] = $runReport();
        $this->assertSame([101, 102, 103, 104], array_column($rows, 'student_id'));
        $this->assertEquals([80, 80, 50, 50], array_column($rows, 'total_percent'));
        $this->assertSame(['2/3', '2/3', '2/3', ''], array_column($rows, 'total_attendance'));
        $this->assertSame(7, $queryCount);

        // More current students must not cause more queries, even when they have history.
        for ($id = 200; $id < 230; $id++) {
            DB::table('student')->insert(['student_id' => $id, 'parent_id' => 1, 'first_name' => 'Student 1',
                'class_id' => 1, 'section_id' => 561, 'isDelete' => 'N', 'academic_yr' => '2026-2027', 'roll_no' => 230 - $id]);
        }
        DB::table('student')->insert(['student_id' => 300, 'parent_id' => 1, 'first_name' => 'New student',
            'class_id' => 1, 'section_id' => 560, 'isDelete' => 'N', 'academic_yr' => '2026-2027', 'roll_no' => 2]);
        [$rows, $expandedCount] = $runReport();
        $this->assertCount(35, $rows);
        $this->assertSame($queryCount, $expandedCount);
        $this->assertSame([101, 300, 229, 228], array_slice(array_column($rows, 'student_id'), 0, 4));
        $this->assertNull($rows[1]['total_percent']);
        $this->assertNull($rows[1]['total_attendance']);
        $this->assertEquals(80, $rows[2]['total_percent']);
        $this->assertSame('2/3', $rows[2]['total_attendance']);
    }
}
