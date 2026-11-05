<?php

namespace Tests\Feature;

use App\Services\ReportCard\HscsClass9To10BulkReportCardDataBuilder;
use Illuminate\Support\Facades\DB;
use PDF;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class HscsClass9To10BulkReportCardTest extends TestCase
{
    protected array $classIds = [109, 110];
    protected string $bulkView = 'reportcard.HSCS.class9to10_report_card_pdf_all';
    protected string $singleView = 'reportcard.HSCS.class9to10_report_card_pdf';
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
            'CREATE TABLE student (student_id INTEGER, class_id INTEGER, section_id INTEGER, academic_yr TEXT, IsDelete TEXT, roll_no INTEGER, reg_no INTEGER, first_name TEXT, mid_name TEXT, last_name TEXT, dob TEXT, parent_id INTEGER, house INTEGER, stud_id_no TEXT)',
            'CREATE TABLE user_master (user_id INTEGER, reg_id INTEGER, role_id TEXT)',
            'CREATE TABLE house (house_id INTEGER, house_name TEXT)',
            'CREATE TABLE parent (parent_id INTEGER, father_name TEXT)',
            'CREATE TABLE term (term_id INTEGER, name TEXT)',
            'CREATE TABLE report_card_publish (term_id INTEGER, class_id INTEGER, section_id INTEGER, publish TEXT, reopen_date TEXT)',
            'CREATE TABLE exam (exam_id INTEGER, name TEXT, term_id INTEGER, start_date TEXT, academic_yr TEXT)',
            'CREATE TABLE allot_mark_headings (class_id INTEGER, sm_id INTEGER, exam_id INTEGER, academic_yr TEXT, marks_headings_id INTEGER, highest_marks INTEGER)',
            'CREATE TABLE marks_headings (marks_headings_id INTEGER, name TEXT, sequence INTEGER)',
            'CREATE TABLE subjects_on_report_card (class_id INTEGER, sub_rc_master_id INTEGER, subject_type TEXT, academic_yr TEXT)',
            'CREATE TABLE subjects_on_report_card_master (sub_rc_master_id INTEGER, name TEXT, sequence INTEGER)',
            'CREATE TABLE student_marks (student_id INTEGER, exam_id INTEGER, subject_id INTEGER, academic_yr TEXT, publish TEXT, reportcard_marks TEXT, reportcard_highest_marks TEXT, total_marks INTEGER, highest_total_marks INTEGER)',
            'CREATE TABLE report_card_remarks (student_id INTEGER, term_id INTEGER, remark TEXT, promot TEXT)',
            'CREATE TABLE settings (academic_yr TEXT, academic_yr_from TEXT, academic_yr_to TEXT)',
            'CREATE TABLE grade (class_id INTEGER, subject_type TEXT, name TEXT, mark_from INTEGER, mark_upto INTEGER)',
            'CREATE TABLE attendance (student_id INTEGER, academic_yr TEXT, only_date TEXT, attendance_status INTEGER, class_id INTEGER, section_id INTEGER)',
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
            ['exam_id' => 1, 'name' => 'Term 1', 'term_id' => 1, 'start_date' => '2026-09-01', 'academic_yr' => '2026-2027'],
            ['exam_id' => 2, 'name' => 'Term 2', 'term_id' => 2, 'start_date' => '2027-03-01', 'academic_yr' => '2026-2027'],
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
        DB::table('user_master')->insert(['user_id' => 1, 'reg_id' => 1, 'role_id' => 'P']);
        JWTAuth::shouldReceive('getPayload')->andReturn(collect(['short_name' => 'HSCS']));
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
            DB::table('attendance')->insert(['student_id' => $id, 'academic_yr' => '2026-2027', 'only_date' => '2026-05-01', 'attendance_status' => 0, 'class_id' => $class, 'section_id' => 201]);
            $ids[] = $id;
        }
        return array_reverse($ids);
    }

    private function build(?int $class = null, $studCount = null): array
    {
        return app(HscsClass9To10BulkReportCardDataBuilder::class)->build($class ?? $this->classIds[0], 201, '2026-2027', $studCount);
    }

    public function test_classes_9_and_10_download_hscs_reports_and_validate_requests(): void
    {
        foreach ($this->classIds as $class) {
            $ids = $this->students($class, 2);
            PDF::shouldReceive('loadView')->once()->with($this->bulkView, \Mockery::on(function ($data) use ($ids) {
                $this->assertSame($ids, array_column($data['reportCardData']['students'], 'student_id'));
                return true;
            }))->andReturnSelf();
            PDF::shouldReceive('download')->once()->with(($class - 100) . 'A_All_Report_Cards.pdf')
                ->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));
            $this->getJson('/api/pdf_download_all?class_id=' . $class . '&section_id=201&academic_yr=2026-2027')
                ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
        $this->getJson('/api/pdf_download_all?class_id=109')->assertUnprocessable()
            ->assertJsonValidationErrors(['section_id', 'academic_yr']);
        $this->getJson('/api/pdf_download_all?class_id=109&section_id=999&academic_yr=2026-2027')->assertNotFound();
        $this->getJson('/api/pdf_download_all?class_id=110&section_id=201&academic_yr=2026&stud_count=0')
            ->assertUnprocessable()->assertJsonValidationErrors(['academic_yr', 'stud_count']);
    }

    public function test_batches_are_ordered_and_query_count_does_not_grow_with_students(): void
    {
        $ids = $this->students(109, 23);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame(array_slice($ids, 0, 10), array_column($this->build(109, 10)['students'], 'student_id'));
        $small = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->assertCount(23, $this->build()['students']);
        $this->assertSame($small, count(DB::getQueryLog()));
        DB::disableQueryLog();
        $this->assertSame(array_slice($ids, 10, 10), array_column($this->build(109, 20)['students'], 'student_id'));
        $this->assertSame(array_slice($ids, 20), array_column($this->build(109, 3)['students'], 'student_id'));
        $this->assertSame([], $this->build(109, 40)['students']);
    }

    public function test_render_preserves_hscs_format_marks_and_signatures_without_queries(): void
    {
        $ids = $this->students(109, 3);
        DB::table('student_marks')->where('student_id', $ids[2])->update(['reportcard_marks' => '{"Term":0}']);
        $data = $this->build();
        $this->assertSame(0, $data['marks_by_student'][$ids[2]][1][1]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $html = view($this->bulkView, ['reportCardData' => $data])->render();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        foreach (['27251501213', 'hscs_pmy.jpg', 'Test Parent', '9 A', 'Student1', 'Student2', '75.5', 'Ab', 'Term 1', 'Term 2', '(100)', 'Class Teacher', 'Parent', 'Principal'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertSame(1, substr_count($html, '<html'));
        $this->assertSame(3, substr_count($html, 'class="col-md-12 pdfdiv"'));
        $this->assertStringNotContainsString('reportcard/SACS', $html);
        $this->assertStringNotContainsString('https://sms.evolvu.in', $html);
        $pdf = app('dompdf.wrapper')->loadHTML($html)->output();
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('/Subtype /Image', $pdf);
    }

    public function test_only_published_exams_and_marks_for_the_requested_year_are_used(): void
    {
        $ids = $this->students(110, 1);
        DB::table('report_card_publish')->where('term_id', 2)->update(['publish' => 'N']);
        DB::table('exam')->insert(['exam_id' => 3, 'name' => 'Old Exam', 'term_id' => 1,
            'start_date' => '2025-09-01', 'academic_yr' => '2025-2026']);
        DB::table('report_card_publish')->insert(['class_id' => 110, 'section_id' => 201, 'term_id' => 3, 'publish' => 'Y']);
        DB::table('student_marks')->where('student_id', $ids[0])->where('exam_id', 1)->update(['publish' => 'N']);
        $data = $this->build(110);
        $this->assertSame([1], array_column($data['exam_list'], 'exam_id'));
        $this->assertSame([], $data['marks_by_student']);
        $this->assertStringNotContainsString('Old Exam', view($this->bulkView, ['reportCardData' => $data])->render());
    }
}

