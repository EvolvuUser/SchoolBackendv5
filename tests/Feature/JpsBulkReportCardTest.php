<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PDF;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class JpsBulkReportCardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        config(['database.default' => 'jps_test', 'database.connections.jps_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::statement('CREATE TABLE class (class_id INTEGER, name TEXT)');
        DB::statement('CREATE TABLE section (section_id INTEGER, name TEXT)');
        DB::statement('CREATE TABLE student (student_id INTEGER, class_id INTEGER, section_id INTEGER, academic_yr TEXT, IsDelete TEXT, roll_no INTEGER, reg_no INTEGER, first_name TEXT, mid_name TEXT, last_name TEXT, dob TEXT, parent_id INTEGER)');
        DB::table('class')->insert([['class_id' => 101, 'name' => '1'], ['class_id' => 102, 'name' => '2']]);
        DB::table('section')->insert(['section_id' => 201, 'name' => 'A']);
        JWTAuth::shouldReceive('getPayload')->andReturn(collect(['short_name' => 'JPS']));
    }

    private function students(int $classId, int $count): array
    {
        $students = [];
        // Insert in reverse roll order to verify the downloaded order.
        for ($i = $count; $i >= 1; $i--) {
            $students[] = [
                'student_id' => $classId * 100 + $i, 'class_id' => $classId, 'section_id' => 201,
                'academic_yr' => '2026-2027', 'IsDelete' => 'N', 'roll_no' => $i, 'reg_no' => $i,
                'first_name' => 'Student' . $i, 'mid_name' => null, 'last_name' => 'Test',
                'dob' => '2020-01-01', 'parent_id' => 1,
            ];
        }
        DB::table('student')->insert($students);
        return array_reverse($students);
    }

    private function expectDownload(array $ids): void
    {
        PDF::shouldReceive('loadView')->once()
            ->with('reportcard.JPS.class1to2_report_card_pdf_all', \Mockery::on(function ($data) use ($ids) {
                $this->assertSame($ids, array_column($data['student_info'], 'student_id'));
                $this->assertSame('2026-2027', $data['academic_yr']);
                return true;
            }))->andReturnSelf();
        PDF::shouldReceive('download')->once()->with(\Mockery::pattern('/^[12]A_All_Report_Card\.pdf$/'))
            ->andReturn(response('PDF download', 200, ['Content-Type' => 'application/pdf']));
    }

    public function test_both_primary_classes_download_all_students_in_roll_order(): void
    {
        foreach ([101, 102] as $classId) {
            $students = $this->students($classId, 12);
            $excluded = $students[0];
            foreach ([['student_id' => $classId * 100 + 91, 'section_id' => 999],
                ['student_id' => $classId * 100 + 92, 'academic_yr' => '2025-2026'],
                ['student_id' => $classId * 100 + 93, 'IsDelete' => 'Y']] as $changes) {
                DB::table('student')->insert(array_replace($excluded, $changes));
            }
            $this->expectDownload(array_column($students, 'student_id'));
            $this->getJson('/api/pdf_download_all?class_id=' . $classId . '&section_id=201&academic_yr=2026-2027')
                ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
    }

    public function test_existing_ten_student_and_remainder_batches_are_supported(): void
    {
        $students = $this->students(101, 23);
        foreach ([10 => [0, 10], 20 => [10, 10], 3 => [20, 3]] as $studCount => [$offset, $length]) {
            $this->expectDownload(array_column(array_slice($students, $offset, $length), 'student_id'));
            $this->getJson('/api/pdf_download_all?class_id=101&section_id=201&academic_yr=2026-2027&stud_count=' . $studCount)
                ->assertOk();
        }
    }

    public function test_missing_section_and_empty_selection_have_clear_responses(): void
    {
        PDF::shouldReceive('loadView')->never();
        $this->getJson('/api/pdf_download_all?class_id=101&academic_yr=2026-2027')
            ->assertUnprocessable()->assertJsonValidationErrors('section_id');
        $this->getJson('/api/pdf_download_all?class_id=101&section_id=201&academic_yr=2026-2027')
            ->assertNotFound();
    }

    public function test_bulk_reuses_single_layout_and_starts_each_student_on_a_new_page(): void
    {
        $students = $this->students(101, 2);
        // Minimal published-term fixtures exercise the real shared Blade and helpers offline.
        foreach ([
            'CREATE TABLE parent (parent_id INTEGER, father_name TEXT)',
            'CREATE TABLE term (term_id INTEGER, name TEXT)',
            'CREATE TABLE report_card_publish (term_id INTEGER, class_id INTEGER, section_id INTEGER, publish TEXT)',
            'CREATE TABLE allot_mark_headings (exam_id INTEGER, class_id INTEGER, academic_yr TEXT, sm_id INTEGER, marks_headings_id INTEGER, highest_marks INTEGER)',
            'CREATE TABLE exam (exam_id INTEGER, name TEXT, term_id INTEGER, start_date TEXT)',
            'CREATE TABLE marks_headings (marks_headings_id INTEGER, name TEXT, sequence INTEGER)',
            'CREATE TABLE student_marks (student_id INTEGER, exam_id INTEGER, subject_id INTEGER, academic_yr TEXT, publish TEXT, reportcard_marks TEXT, reportcard_highest_marks TEXT, total_marks INTEGER, highest_total_marks INTEGER)',
            'CREATE TABLE subjects_on_report_card (sub_rc_master_id INTEGER, class_id INTEGER, subject_type TEXT, academic_yr TEXT)',
            'CREATE TABLE subjects_on_report_card_master (sub_rc_master_id INTEGER, name TEXT, sequence INTEGER)',
            'CREATE TABLE grade (class_id INTEGER, subject_type TEXT, mark_from INTEGER, mark_upto INTEGER, name TEXT)',
            'CREATE TABLE report_card_remarks (student_id INTEGER, term_id INTEGER, remark TEXT)',
            'CREATE TABLE settings (academic_yr TEXT, academic_yr_from TEXT, academic_yr_to TEXT)',
            'CREATE TABLE attendance (student_id INTEGER, attendance_status INTEGER, only_date TEXT, academic_yr TEXT)',
        ] as $sql) {
            DB::statement($sql);
        }
        DB::connection()->getPdo()->sqliteCreateFunction('if', fn ($condition, $yes, $no) => $condition ? $yes : $no, 3);
        DB::table('parent')->insert(['parent_id' => 1, 'father_name' => 'Test Parent']);
        DB::table('term')->insert(['term_id' => 1, 'name' => 'TERM 1']);
        DB::table('report_card_publish')->insert(['term_id' => 1, 'class_id' => 101, 'section_id' => 201, 'publish' => 'Y']);
        DB::table('settings')->insert(['academic_yr' => '2026-2027', 'academic_yr_from' => '2026-04-01', 'academic_yr_to' => '2027-03-31']);
        DB::table('exam')->insert(['exam_id' => 1, 'name' => 'Assessment', 'term_id' => 1, 'start_date' => '2026-09-01']);
        DB::table('marks_headings')->insert(['marks_headings_id' => 1, 'name' => 'Assessment', 'sequence' => 1]);
        foreach ([11 => ['English', 'Scholastic'], 12 => ['Sports', 'Co-Scholastic']] as $subjectId => [$name, $type]) {
            DB::table('subjects_on_report_card_master')->insert(['sub_rc_master_id' => $subjectId, 'name' => $name, 'sequence' => $subjectId]);
            DB::table('subjects_on_report_card')->insert(['sub_rc_master_id' => $subjectId, 'class_id' => 101, 'subject_type' => $type, 'academic_yr' => '2026-2027']);
            DB::table('allot_mark_headings')->insert(['exam_id' => 1, 'class_id' => 101, 'academic_yr' => '2026-2027', 'sm_id' => $subjectId, 'marks_headings_id' => 1, 'highest_marks' => 50]);
            DB::table('grade')->insert(['class_id' => 101, 'subject_type' => $type, 'mark_from' => 0, 'mark_upto' => 100, 'name' => 'A']);
        }
        foreach ($students as $index => $student) {
            $mark = ($index + 1) * 10;
            DB::table('student_marks')->insert([
                'student_id' => $student['student_id'], 'exam_id' => 1, 'subject_id' => 11,
                'academic_yr' => '2026-2027', 'publish' => 'Y',
                'reportcard_marks' => json_encode(['Assessment' => $mark]),
                'reportcard_highest_marks' => json_encode(['Assessment' => 50]),
                'total_marks' => $mark, 'highest_total_marks' => 50,
            ]);
        }

        $single = view('reportcard.JPS.class1to2_report_card_pdf', [
            'student_id' => $students[0]['student_id'], 'class_id' => 101, 'academic_yr' => '2026-2027',
        ])->render();
        $bulk = view('reportcard.JPS.class1to2_report_card_pdf_all', ['student_info' => $students])->render();
        $document = new \DOMDocument;
        @$document->loadHTML($bulk);
        $this->assertSame(1, $document->getElementsByTagName('body')->length);
        $this->assertSame(2, substr_count($bulk, 'class="col-md-12 pdfdiv"'));
        $this->assertStringContainsString('Student1  Test', $bulk);
        $this->assertStringContainsString('Student2  Test', $bulk);
        $this->assertStringContainsString('page-break-before: always', $bulk);
        $xpath = new \DOMXPath($document);
        $markCells = $xpath->query('//table[@class="scholastic-table"]/tbody/tr[1]/td[2]');
        $this->assertSame(['10', '20'], array_map(fn ($cell) => trim($cell->textContent), iterator_to_array($markCells)));

        $render = function ($html) {
            $pdf = app('dompdf.wrapper')->loadHTML($html)->setPaper('a4');
            $output = $pdf->output();
            $this->assertStringStartsWith('%PDF-', $output);
            $this->assertStringContainsString('/Subtype /Image', $output);
            return $pdf->getDomPDF()->getCanvas()->get_page_count();
        };
        $singlePages = $render($single);
        $this->assertSame($singlePages * 2, $render($bulk));
    }
}
