<?php

namespace Tests\Feature;

use App\Services\ReportCard\JpsClass3To5BulkReportCardDataBuilder;
use Illuminate\Support\Facades\DB;
use PDF;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class JpsClass3To5BulkReportCardTest extends TestCase
{
    protected array $classIds = [103, 104, 105];
    protected string $bulkView = 'reportcard.JPS.class3to5_report_card_pdf_all';
    protected string $singleView = 'reportcard.JPS.class3to5_report_card_pdf';
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        config(['database.default' => 'jps_bulk_test', 'database.connections.jps_bulk_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        foreach ([
            'CREATE TABLE class (class_id INTEGER, name TEXT)',
            'CREATE TABLE section (section_id INTEGER, name TEXT)',
            'CREATE TABLE student (student_id INTEGER, class_id INTEGER, section_id INTEGER, academic_yr TEXT, IsDelete TEXT, roll_no INTEGER, reg_no INTEGER, first_name TEXT, mid_name TEXT, last_name TEXT, dob TEXT, parent_id INTEGER)',
            'CREATE TABLE parent (parent_id INTEGER, father_name TEXT)',
            'CREATE TABLE term (term_id INTEGER, name TEXT)',
            'CREATE TABLE report_card_publish (term_id INTEGER, class_id INTEGER, section_id INTEGER, publish TEXT, reopen_date TEXT)',
            'CREATE TABLE exam (exam_id INTEGER, name TEXT, term_id INTEGER, start_date TEXT)',
            'CREATE TABLE allot_mark_headings (class_id INTEGER, sm_id INTEGER, exam_id INTEGER, academic_yr TEXT, marks_headings_id INTEGER, highest_marks INTEGER)',
            'CREATE TABLE marks_headings (marks_headings_id INTEGER, name TEXT, sequence INTEGER)',
            'CREATE TABLE subjects_on_report_card (class_id INTEGER, sub_rc_master_id INTEGER, subject_type TEXT, academic_yr TEXT)',
            'CREATE TABLE subjects_on_report_card_master (sub_rc_master_id INTEGER, name TEXT, sequence INTEGER)',
            'CREATE TABLE student_marks (student_id INTEGER, exam_id INTEGER, subject_id INTEGER, academic_yr TEXT, publish TEXT, reportcard_marks TEXT, reportcard_highest_marks TEXT, total_marks INTEGER, highest_total_marks INTEGER)',
            'CREATE TABLE report_card_remarks (student_id INTEGER, term_id INTEGER, remark TEXT, promot TEXT)',
            'CREATE TABLE settings (academic_yr TEXT, academic_yr_from TEXT, academic_yr_to TEXT)',
            'CREATE TABLE grade (class_id INTEGER, subject_type TEXT, name TEXT, mark_from INTEGER, mark_upto INTEGER)',
            'CREATE TABLE attendance (student_id INTEGER, academic_yr TEXT, only_date TEXT, attendance_status INTEGER)',
        ] as $sql) {
            DB::statement($sql);
        }
        DB::connection()->getPdo()->sqliteCreateFunction('if', fn ($condition, $yes, $no) => $condition ? $yes : $no, 3);
        DB::table('class')->insert(array_map(fn ($id) => ['class_id' => $id, 'name' => (string) ($id - 100)], $this->classIds));
        DB::table('section')->insert(['section_id' => 201, 'name' => 'A']);
        DB::table('parent')->insert(['parent_id' => 1, 'father_name' => 'Test Parent']);
        DB::table('term')->insert([['term_id' => 1, 'name' => 'TERM 1'], ['term_id' => 2, 'name' => 'TERM 2']]);
        DB::table('settings')->insert(['academic_yr' => '2026-2027', 'academic_yr_from' => '2026-04-01', 'academic_yr_to' => '2027-03-31']);
        DB::table('marks_headings')->insert(['marks_headings_id' => 1, 'name' => 'Term', 'sequence' => 1]);
        DB::table('subjects_on_report_card_master')->insert([
            ['sub_rc_master_id' => 1, 'name' => 'English', 'sequence' => 1],
            ['sub_rc_master_id' => 8, 'name' => 'Art', 'sequence' => 2],
        ]);
        DB::table('exam')->insert([
            ['exam_id' => 1, 'name' => 'Term 1', 'term_id' => 1, 'start_date' => '2026-09-01'],
            ['exam_id' => 2, 'name' => 'Term 2', 'term_id' => 2, 'start_date' => '2027-03-01'],
        ]);
        foreach ($this->classIds as $class) {
            foreach ([1, 2] as $term) {
                DB::table('report_card_publish')->insert(['class_id' => $class, 'section_id' => 201, 'term_id' => $term, 'publish' => 'Y', 'reopen_date' => '2027-04-01']);
                DB::table('allot_mark_headings')->insert(['class_id' => $class, 'sm_id' => 1, 'exam_id' => $term, 'academic_yr' => '2026-2027', 'marks_headings_id' => 1, 'highest_marks' => 100]);
            }
            foreach ([1 => 'Scholastic', 8 => 'Co-Scholastic'] as $subject => $type) {
                DB::table('subjects_on_report_card')->insert(['class_id' => $class, 'sub_rc_master_id' => $subject, 'subject_type' => $type, 'academic_yr' => '2026-2027']);
                DB::table('grade')->insert(['class_id' => $class, 'subject_type' => $type, 'name' => 'A', 'mark_from' => 0, 'mark_upto' => 100]);
            }
        }
        JWTAuth::shouldReceive('getPayload')->andReturn(collect(['short_name' => 'JPS']));
    }

    private function students(int $class, int $count): array
    {
        $ids = [];
        for ($roll = $count; $roll >= 1; $roll--) {
            $id = $class * 100 + $roll;
            DB::table('student')->insert([
                'student_id' => $id, 'class_id' => $class, 'section_id' => 201, 'academic_yr' => '2026-2027',
                'IsDelete' => 'N', 'roll_no' => $roll, 'reg_no' => $roll, 'first_name' => 'Student' . $roll,
                'mid_name' => null, 'last_name' => 'Test', 'dob' => '2018-01-01', 'parent_id' => 1,
            ]);
            foreach ([1, 2] as $exam) {
                $mark = $roll === 1 ? '75.5' : 'Ab';
                DB::table('student_marks')->insert([
                    'student_id' => $id, 'exam_id' => $exam, 'subject_id' => 1, 'academic_yr' => '2026-2027', 'publish' => 'Y',
                    'reportcard_marks' => json_encode(['Term' => $mark]), 'reportcard_highest_marks' => '{"Term":100}',
                    'total_marks' => $roll === 1 ? 76 : 0, 'highest_total_marks' => 100,
                ]);
            }
            DB::table('report_card_remarks')->insert(['student_id' => $id, 'term_id' => 2, 'remark' => 'Remark ' . $roll, 'promot' => 'Next Class']);
            DB::table('attendance')->insert(['student_id' => $id, 'academic_yr' => '2026-2027', 'only_date' => '2026-05-01', 'attendance_status' => 0]);
            $ids[] = $id;
        }
        return array_reverse($ids);
    }

    private function build(?int $class = null, $studCount = null): array
    {
        return app(JpsClass3To5BulkReportCardDataBuilder::class)->build($class ?? $this->classIds[0], 201, '2026-2027', $studCount);
    }

    public function test_all_three_classes_download_the_jps_pdf(): void
    {
        foreach ($this->classIds as $class) {
            $ids = $this->students($class, 2);
            PDF::shouldReceive('loadView')->once()
                ->with($this->bulkView, \Mockery::on(function ($data) use ($ids) {
                    $this->assertSame($ids, array_column($data['reportCardData']['students'], 'student_id'));
                    return true;
                }))->andReturnSelf();
            PDF::shouldReceive('download')->once()->with(($class - 100) . 'A_All_Report_Cards.pdf')
                ->andReturn(response('PDF download', 200, ['Content-Type' => 'application/pdf']));
            $this->getJson('/api/pdf_download_all?class_id=' . $class . '&section_id=201&academic_yr=2026-2027')
                ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
    }

    public function test_selection_is_scoped_sorted_and_batches_are_supported(): void
    {
        $ids = $this->students($this->classIds[0], 23);
        DB::table('student')->insert([
            ['student_id' => 9991, 'class_id' => $this->classIds[0], 'section_id' => 999, 'academic_yr' => '2026-2027', 'IsDelete' => 'N'],
            ['student_id' => 9992, 'class_id' => $this->classIds[0], 'section_id' => 201, 'academic_yr' => '2025-2026', 'IsDelete' => 'N'],
            ['student_id' => 9993, 'class_id' => $this->classIds[0], 'section_id' => 201, 'academic_yr' => '2026-2027', 'IsDelete' => 'Y'],
        ]);
        $this->assertSame($ids, array_column($this->build()['students'], 'student_id'));
        foreach ([10 => [0, 10], 20 => [10, 10], 3 => [20, 3]] as $batch => [$offset, $length]) {
            $this->assertSame(array_slice($ids, $offset, $length), array_column($this->build($this->classIds[0], $batch)['students'], 'student_id'));
        }
    }

    public function test_query_count_is_constant_and_bulk_rendering_runs_no_queries(): void
    {
        $this->students($this->classIds[0], 1);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->build();
        $singleQueryCount = count(DB::getQueryLog());

        $this->students($this->classIds[1], 23);
        DB::flushQueryLog();
        $data = $this->build($this->classIds[1]);
        $this->assertSame($singleQueryCount, count(DB::getQueryLog()));
        $this->assertLessThanOrEqual(12, $singleQueryCount);
        DB::flushQueryLog();
        $html = view($this->bulkView, ['reportCardData' => $data])->render();
        $this->assertCount(0, DB::getQueryLog());
        $this->assertSame(23, substr_count($html, 'class="col-md-12 pdfdiv"'));
        DB::disableQueryLog();
    }

    public function test_bulk_matches_single_format_and_renders_independent_student_pages(): void
    {
        $ids = $this->students($this->classIds[0], 2);
        $data = $this->build();
        $bulk = view($this->bulkView, ['reportCardData' => $data])->render();
        $single = view($this->singleView, ['student_id' => $ids[0], 'academic_yr' => '2026-2027'])->render();
        $blocks = function ($html) {
            $document = new \DOMDocument;
            @$document->loadHTML($html);
            $xpath = new \DOMXPath($document);
            return [$document, $xpath->query('//div[@class="col-md-12 pdfdiv"]')];
        };
        [$document, $bulkBlocks] = $blocks($bulk);
        [$singleDocument, $singleBlocks] = $blocks($single);
        $this->assertSame(1, $document->getElementsByTagName('body')->length);
        $this->assertSame(2, $bulkBlocks->length);
        $this->assertSame($singleBlocks->item(0)->textContent, $bulkBlocks->item(0)->textContent);
        $this->assertStringContainsString('76', $bulkBlocks->item(0)->textContent);
        $this->assertStringContainsString('Ab', $bulkBlocks->item(1)->textContent);
        $this->assertStringNotContainsString('Remark 1', $bulkBlocks->item(1)->textContent);
        $this->assertStringContainsString('Remark 2', $bulkBlocks->item(1)->textContent);
        $this->assertStringContainsString('27251207823', $bulk);
        $render = function ($html) {
            $pdf = app('dompdf.wrapper')->loadHTML($html)->setPaper('a4');
            $output = $pdf->output();
            $this->assertStringStartsWith('%PDF-', $output);
            $this->assertStringContainsString('/Subtype /Image', $output);
            return $pdf->getDomPDF()->getCanvas()->get_page_count();
        };
        $this->assertSame($render($single) * 2, $render($bulk));
    }

    public function test_validation_and_empty_batches_return_clear_errors(): void
    {
        PDF::shouldReceive('loadView')->never();
        $this->getJson('/api/pdf_download_all?class_id=' . $this->classIds[0] . '&academic_yr=2026-2027')
            ->assertUnprocessable()->assertJsonValidationErrors('section_id');
        $this->getJson('/api/pdf_download_all?class_id=' . $this->classIds[0] . '&section_id=201&academic_yr=2026-2027&stud_count=0')
            ->assertUnprocessable()->assertJsonValidationErrors('stud_count');
        $this->getJson('/api/pdf_download_all?class_id=' . $this->classIds[0] . '&section_id=201&academic_yr=2026-2027')
            ->assertNotFound();
    }
}
