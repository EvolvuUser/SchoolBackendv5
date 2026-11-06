<?php

namespace Tests\Feature;

use App\Services\ReportCard\HscsClass6To8BulkReportCardDataBuilder;
use Illuminate\Support\Facades\DB;
use PDF;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class HscsClass6To8BulkReportCardTest extends TestCase
{
    protected array $classIds = [106, 107, 108];
    protected string $bulkView = 'reportcard.HSCS.class6to8_report_card_pdf_all';
    protected string $singleView = 'reportcard.HSCS.class6to8_report_card_pdf';
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
            'CREATE TABLE exam (exam_id INTEGER, name TEXT, term_id INTEGER, start_date TEXT)',
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
        return app(HscsClass6To8BulkReportCardDataBuilder::class)->build($class ?? $this->classIds[0], 201, '2026-2027', $studCount);
    }

    public function test_classes_6_7_and_8_download_the_hscs_format(): void
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
        $this->getJson('/api/pdf_download_all?class_id=106')->assertUnprocessable()
            ->assertJsonValidationErrors(['section_id', 'academic_yr']);
        $this->getJson('/api/pdf_download_all?class_id=106&section_id=999&academic_yr=2026-2027')->assertNotFound();
        $this->getJson('/api/pdf_download_all?class_id=106&section_id=201&academic_yr=2026')
            ->assertUnprocessable()->assertJsonValidationErrors('academic_yr');
    }

    public function test_october_attendance_and_class_working_days_apply_to_every_batch(): void
    {
        $ids = $this->students(106, 23);
        DB::table('report_card_publish')->where('term_id', 2)->update(['publish' => 'N']);
        foreach (['2026-10-01', '2026-10-02', '2026-11-01'] as $date) {
            DB::table('attendance')->insert([
                'student_id' => $ids[22], 'class_id' => 106, 'section_id' => 201,
                'academic_yr' => '2026-2027', 'only_date' => $date, 'attendance_status' => 0,
            ]);
        }
        $data = $this->build(106, 10);
        $this->assertSame('2026-10-31', $data['date_to']);
        $this->assertCount(10, $data['students']);
        $this->assertSame(['present' => 1, 'working' => 3], $data['attendance_by_student'][$ids[0]]);
        $this->assertSame(array_slice($ids, 10, 10), array_column($this->build(106, 20)['students'], 'student_id'));
        $this->assertSame(array_slice($ids, 20), array_column($this->build(106, 3)['students'], 'student_id'));
        $this->assertSame('2026-09-30', app(\App\Services\ReportCard\Class3To5BulkReportCardDataBuilder::class)
            ->build(106, 201, '2026-2027')['date_to']);
    }

    public function test_render_keeps_hscs_identity_marks_absence_and_remarks_without_queries(): void
    {
        $ids = $this->students(106, 2);
        foreach ($ids as $id) {
            DB::table('student_marks')->insert([
                'student_id' => $id, 'exam_id' => 1, 'subject_id' => 8, 'academic_yr' => '2026-2027', 'publish' => 'Y',
                'reportcard_marks' => '{"Term":40}', 'reportcard_highest_marks' => '{"Term":50}',
                'total_marks' => 40, 'highest_total_marks' => 50,
            ]);
        }
        $data = $this->build();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $html = view($this->bulkView, ['reportCardData' => $data])->render();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        foreach (['27250504625', 'hscs_pmy.jpg', 'Test Parent', '6 A', 'Student1', 'Student2', 'Remark 1', 'Remark 2', 'Next Class', 'Ab', '>75.5<'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertSame(1, substr_count($html, '<html'));
        $this->assertSame(2, substr_count($html, 'class="col-md-12 pdfdiv"'));
        $this->assertStringContainsString('margin-top:16%', $html);
        $this->assertStringNotContainsString('reportcard/SACS', $html);
        $this->assertStringNotContainsString('https://sms.evolvu.in', $html);
        $pdf = app('dompdf.wrapper')->loadHTML($html);
        $this->assertStringStartsWith('%PDF-', $pdf->output());
        $this->assertStringContainsString('/Subtype /Image', $pdf->output());
    }

    public function test_bulk_query_count_does_not_grow_with_students(): void
    {
        $this->students(106, 23);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->build(106, 10);
        $small = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->build();
        $this->assertSame($small, count(DB::getQueryLog()));
        DB::disableQueryLog();
    }
}
