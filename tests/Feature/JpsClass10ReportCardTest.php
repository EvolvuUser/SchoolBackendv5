<?php

namespace Tests\Feature;

use App\Services\ReportCard\JpsClass10ReportCardDataBuilder;
use Illuminate\Support\Facades\DB;
use PDF;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class JpsClass10ReportCardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        config(['database.default' => 'jps10_test', 'database.connections.jps10_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        foreach ([
            'CREATE TABLE student (student_id INTEGER, class_id INTEGER, section_id INTEGER, academic_yr TEXT, parent_id INTEGER, first_name TEXT, mid_name TEXT, last_name TEXT, roll_no INTEGER, reg_no INTEGER, dob TEXT)',
            'CREATE TABLE parent (parent_id INTEGER, father_name TEXT)',
            'CREATE TABLE class (class_id INTEGER, name TEXT)',
            'CREATE TABLE section (section_id INTEGER, name TEXT)',
            'CREATE TABLE exam (exam_id INTEGER, name TEXT, academic_yr TEXT, start_date TEXT)',
            'CREATE TABLE report_card_publish (term_id INTEGER, class_id INTEGER, section_id INTEGER, publish TEXT, reopen_date TEXT)',
            'CREATE TABLE marks_headings (marks_headings_id INTEGER, name TEXT, sequence INTEGER)',
            'CREATE TABLE allot_mark_headings (exam_id INTEGER, class_id INTEGER, academic_yr TEXT, sm_id INTEGER, marks_headings_id INTEGER, highest_marks INTEGER, reportcard_highest_marks INTEGER)',
            'CREATE TABLE subjects_on_report_card (class_id INTEGER, academic_yr TEXT, sub_rc_master_id INTEGER, subject_type TEXT)',
            'CREATE TABLE subjects_on_report_card_master (sub_rc_master_id INTEGER, name TEXT, sequence INTEGER)',
            'CREATE TABLE student_marks (student_id INTEGER, class_id INTEGER, section_id INTEGER, academic_yr TEXT, exam_id INTEGER, subject_id INTEGER, publish TEXT, present TEXT, mark_obtained TEXT, highest_marks TEXT, reportcard_marks TEXT, reportcard_highest_marks TEXT)',
            'CREATE TABLE grade (class_id INTEGER, subject_type TEXT, mark_from INTEGER, mark_upto INTEGER, name TEXT)',
            'CREATE TABLE settings (academic_yr TEXT, academic_yr_from TEXT, academic_yr_to TEXT)',
            'CREATE TABLE attendance (student_id INTEGER, academic_yr TEXT, only_date TEXT, attendance_status INTEGER)',
            'CREATE TABLE report_card_remarks (student_id INTEGER, term_id INTEGER, remark TEXT, promot TEXT)',
        ] as $sql) {
            DB::statement($sql);
        }
        DB::table('student')->insert(['student_id' => 10001, 'class_id' => 110, 'section_id' => 201, 'academic_yr' => '2026-2027', 'parent_id' => 1, 'first_name' => 'Student', 'mid_name' => null, 'last_name' => 'Test', 'roll_no' => 1, 'reg_no' => 90, 'dob' => '2012-05-01']);
        DB::table('parent')->insert(['parent_id' => 1, 'father_name' => 'Test Parent']);
        DB::table('class')->insert(['class_id' => 110, 'name' => '10']);
        DB::table('section')->insert(['section_id' => 201, 'name' => 'A']);
        foreach ([11 => 'PT - I', 12 => 'PT - II', 13 => 'PT - III', 21 => 'TERM I', 22 => 'TERM II'] as $id => $name) {
            DB::table('exam')->insert(['exam_id' => $id, 'name' => $name, 'academic_yr' => '2026-2027', 'start_date' => '2026-05-' . $id]);
            DB::table('report_card_publish')->insert(['term_id' => $id, 'class_id' => 110, 'section_id' => 201, 'publish' => 'Y']);
        }
        foreach ([101 => 'Periodic test', 102 => 'Multiple assessment', 103 => 'Subject enrichment', 104 => 'Portfolio', 105 => 'SA-1', 106 => 'SA-2', 201 => 'Practical', 202 => 'Oral/Viva', 301 => 'Total Marks'] as $id => $name) {
            DB::table('marks_headings')->insert(['marks_headings_id' => $id, 'name' => $name, 'sequence' => ([101 => 1, 102 => 2, 103 => 3, 104 => 4, 105 => 5, 106 => 5, 201 => 2, 202 => 3, 301 => 1][$id])]);
        }
        foreach ([1 => ['English', 'Scholastic'], 2 => ['IT', 'Scholastic'], 3 => ['Art Education', 'Co-Scholastic']] as $subject => [$name, $type]) {
            DB::table('subjects_on_report_card_master')->insert(['sub_rc_master_id' => $subject, 'name' => $name, 'sequence' => $subject]);
            DB::table('subjects_on_report_card')->insert(['class_id' => 110, 'academic_yr' => '2026-2027', 'sub_rc_master_id' => $subject, 'subject_type' => $type]);
        }
        foreach ([11, 12, 13] as $exam) {
            foreach ([1 => 40, 2 => 20] as $subject => $maximum) {
                $this->heading($exam, $subject, 101, $maximum, $subject === 1 ? 5 : 10);
                $raw = [11 => 32, 12 => 24, 13 => 36][$exam] / ($subject === 1 ? 1 : 2);
                $this->mark($exam, $subject, [101 => $raw], [101 => $maximum], ['Periodic test' => $raw / $maximum * ($subject === 1 ? 5 : 10)]);
                DB::table('student_marks')->where('exam_id', $exam)->where('subject_id', $subject)
                    ->update(['reportcard_highest_marks' => json_encode(['Periodic test' => $subject === 1 ? 5 : 10])]);
            }
        }
        foreach ([21, 22] as $exam) {
            $sa = $exam === 21 ? 105 : 106;
            foreach ([101 => 5, 102 => 5, 103 => 5, 104 => 5, $sa => 80] as $heading => $maximum) {
                $this->heading($exam, 1, $heading, $maximum, $maximum);
            }
            $this->mark($exam, 1, [], [], ['Periodic test' => 1, 'Multiple assessment' => 4, 'Subject enrichment' => 4, 'Portfolio' => 5, $exam === 21 ? 'SA-1' : 'SA-2' => 70]);
            foreach ([101 => 10, 104 => 10, 201 => 20, 202 => 10, $sa => 50] as $heading => $maximum) {
                $this->heading($exam, 2, $heading, $maximum, $maximum);
            }
            $this->mark($exam, 2, [], [], ['Periodic test' => 1, 'Practical' => 18, 'Oral/Viva' => 9, 'Portfolio' => 9, $exam === 21 ? 'SA-1' : 'SA-2' => 45]);
            $this->heading($exam, 3, 301, 50, 50);
            $this->mark($exam, 3, [], [], ['Total Marks' => 40]);
        }
        DB::table('grade')->insert([
            ['class_id' => 110, 'subject_type' => 'Co-Scholastic', 'mark_from' => 41, 'mark_upto' => 50, 'name' => 'A'],
            ['class_id' => 110, 'subject_type' => 'Co-Scholastic', 'mark_from' => 0, 'mark_upto' => 40, 'name' => 'B'],
            ['class_id' => 110, 'subject_type' => 'Scholastic', 'mark_from' => 0, 'mark_upto' => 100, 'name' => 'A1'],
        ]);
        DB::table('settings')->insert(['academic_yr' => '2026-2027', 'academic_yr_from' => '2026-04-01', 'academic_yr_to' => '2027-03-31']);
        DB::table('attendance')->insert(['student_id' => 10001, 'academic_yr' => '2026-2027', 'only_date' => '2026-05-01', 'attendance_status' => 0]);
        DB::table('report_card_remarks')->insert([
            ['student_id' => 10001, 'term_id' => 21, 'remark' => 'Good progress', 'promot' => ''],
            ['student_id' => 10001, 'term_id' => 22, 'remark' => 'Keep it up', 'promot' => '10'],
        ]);
        DB::table('report_card_publish')->update(['reopen_date' => '2027-04-01']);
        DB::table('marks_headings')->insert(['marks_headings_id' => 401, 'name' => 'Written', 'sequence' => 1]);
        foreach ([31 => 'Preboard 1', 32 => 'Preboard 2'] as $exam => $name) {
            DB::table('exam')->insert(['exam_id' => $exam, 'name' => $name, 'academic_yr' => '2026-2027', 'start_date' => '2027-01-' . ($exam - 30)]);
            DB::table('report_card_publish')->insert(['term_id' => $exam, 'class_id' => 110, 'section_id' => 201, 'publish' => 'Y']);
            foreach ([1 => 80, 2 => 50] as $subject => $maximum) {
                $this->heading($exam, $subject, 401, $maximum, $maximum);
                $value = $subject === 1 ? ($exam === 31 ? 69.5 : 75) : ($exam === 31 ? 40 : 47);
                $this->mark($exam, $subject, [401 => $value], [401 => $maximum], ['Written' => $value]);
                DB::table('student_marks')->where('exam_id', $exam)->where('subject_id', $subject)
                    ->update(['reportcard_highest_marks' => json_encode(['Written' => $maximum])]);
            }
        }
        JWTAuth::shouldReceive('getPayload')->andReturn(collect(['short_name' => 'JPS']));
        $this->app->instance('global_variables', ['parent_app_url' => '', 'codeigniter_app_url' => '']);
    }

    private function heading(int $exam, int $subject, int $heading, int $rawMax, int $reportMax): void
    {
        DB::table('allot_mark_headings')->insert(['exam_id' => $exam, 'class_id' => 110, 'academic_yr' => '2026-2027', 'sm_id' => $subject, 'marks_headings_id' => $heading, 'highest_marks' => $rawMax, 'reportcard_highest_marks' => $reportMax]);
    }

    private function mark(int $exam, int $subject, array $raw, array $highest, array $report): void
    {
        DB::table('student_marks')->insert(['student_id' => 10001, 'class_id' => 110, 'section_id' => 201, 'academic_yr' => '2026-2027', 'exam_id' => $exam, 'subject_id' => $subject, 'publish' => 'Y', 'present' => '{}', 'mark_obtained' => json_encode($raw), 'highest_marks' => json_encode($highest), 'reportcard_marks' => json_encode($report), 'reportcard_highest_marks' => json_encode(array_map(fn () => 5, $report))]);
    }

    private function build(): array
    {
        return app(JpsClass10ReportCardDataBuilder::class)->build(110, 10001, '2026-2027');
    }

    public function test_class10_assessment_order_and_totals_follow_the_reference(): void
    {
        $data = $this->build();
        $this->assertSame(['term', 'pt3', 'preboard1', 'preboard2'], array_column($data['blocks'], 'kind'));
        $english = $data['groups'][0]['rows'][0]['terms'];
        $it = $data['groups'][1]['rows'][0]['terms'];
        $this->assertSame(32.0, $english[0]['raw']['value']);
        $this->assertSame(4.0, $english[0]['components'][0]['value']);
        $this->assertSame(87.0, $english[0]['total']);
        $this->assertSame(100.0, $english[0]['maximum']);
        $this->assertSame(89.0, $it[0]['total']);
        $this->assertSame(100.0, $it[0]['maximum']);
        $this->assertSame([36.0, 70.0, 75.0], array_map(fn ($term) => $term['periodic'][0]['value'], array_slice($english, 1)));
        $this->assertSame([40.0, 80.0, 80.0], array_map(fn ($term) => $term['periodic'][0]['maximum'], array_slice($english, 1)));
        $this->assertSame([18.0, 40.0, 47.0], array_map(fn ($term) => $term['periodic'][0]['value'], array_slice($it, 1)));
        $this->assertCount(1, $data['co_scholastic'][0]['terms']);
    }

    public function test_best_pt1_pt2_is_scaled_once_and_pt3_does_not_affect_term1(): void
    {
        DB::table('student_marks')->where('exam_id', 12)->where('subject_id', 1)
            ->update(['mark_obtained' => '{"101":38}']);
        DB::table('student_marks')->where('exam_id', 13)->where('subject_id', 1)
            ->update(['mark_obtained' => '{"101":40}']);
        $term = $this->build()['groups'][0]['rows'][0]['terms'][0];
        $this->assertSame(38.0, $term['raw']['value']);
        $this->assertSame(5.0, $term['components'][0]['value']);
        $this->assertSame(88.0, $term['total']);
        $this->assertSame(100.0, $term['maximum']);
    }

    public function test_converted_pt_maximum_can_come_from_the_pt_configuration(): void
    {
        DB::table('allot_mark_headings')->where('exam_id', 21)->where('marks_headings_id', 101)->delete();
        $data = $this->build();
        $english = $data['groups'][0]['rows'][0]['terms'][0];
        $it = $data['groups'][1]['rows'][0]['terms'][0];
        $this->assertSame('On report card convert to', $english['components'][0]['label']);
        $this->assertSame(5.0, $english['components'][0]['maximum']);
        $this->assertSame(4.0, $english['components'][0]['value']);
        $this->assertSame(100.0, $english['maximum']);
        $this->assertSame(87.0, $english['total']);
        $this->assertSame(10.0, $it['components'][0]['maximum']);
        $this->assertSame(100.0, $it['maximum']);
        $this->assertSame(89.0, $it['total']);
    }

    public function test_unpublished_assessments_and_marks_are_excluded(): void
    {
        DB::table('report_card_publish')->where('term_id', 32)->update(['publish' => 'N']);
        $data = $this->build();
        $this->assertSame(['term', 'pt3', 'preboard1'], array_column($data['blocks'], 'kind'));
        DB::table('student_marks')->where('exam_id', 31)->where('subject_id', 1)->update(['publish' => 'N']);
        $this->assertSame('', $this->build()['groups'][0]['rows'][0]['terms'][2]['periodic'][0]['value']);
        DB::table('report_card_publish')->where('term_id', 11)->update(['publish' => 'N']);
        $this->assertSame(24.0, $this->build()['groups'][0]['rows'][0]['terms'][0]['raw']['value']);
    }

    public function test_zero_absence_and_saved_only_pt3_marks_are_supported(): void
    {
        $query = DB::table('student_marks')->where('exam_id', 13)->where('subject_id', 1);
        $query->update(['mark_obtained' => '{"101":0}', 'present' => '{"101":"Y"}']);
        $this->assertSame(0.0, $this->build()['groups'][0]['rows'][0]['terms'][1]['periodic'][0]['value']);
        $query->update(['present' => '{"101":"N"}']);
        $this->assertSame('Ab', $this->build()['groups'][0]['rows'][0]['terms'][1]['periodic'][0]['value']);
        $query->update(['mark_obtained' => '{}', 'present' => '{}', 'reportcard_marks' => '{"Periodic test":4.5}', 'reportcard_highest_marks' => '{"Periodic test":5}']);
        $this->assertSame(36.0, $this->build()['groups'][0]['rows'][0]['terms'][1]['periodic'][0]['value']);
    }

    public function test_class10_uses_the_class9_design_and_one_scholastic_table_without_render_queries(): void
    {
        preg_match('/<style[^>]*>(.*?)<\/style>/s', file_get_contents(resource_path('views/reportcard/JPS/class9_report_card_pdf.blade.php')), $nine);
        preg_match('/<style[^>]*>(.*?)<\/style>/s', file_get_contents(resource_path('views/reportcard/JPS/class10_report_card_pdf.blade.php')), $ten);
        $this->assertSame($nine[1], $ten[1]);
        $data = $this->build();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $html = view('reportcard.JPS.class10_report_card_pdf', ['reportCardData' => $data])->render();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        foreach (['Best of PT - I / PT - II', 'PT - III', 'Preboard 1', 'Preboard 2', 'Practical', 'Oral/Viva', '176/200'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringNotContainsString('TERM II', $html);
        $this->assertSame(1, substr_count($html, '<b>Scholastic Areas</b>'));
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $english = $xpath->query('//tr[td[1][normalize-space(.)="English"]]')->item(0);
        $it = $xpath->query('//tr[td[1][normalize-space(.)="IT"]]')->item(0);
        $this->assertTrue($xpath->query('ancestor::table[1]', $english)->item(0)->isSameNode($xpath->query('ancestor::table[1]', $it)->item(0)));
        $this->assertSame(
            ['English', '32', '4', '4', '4', '5', '70', '87', 'A1', '36', '70', '75'],
            array_map(fn ($cell) => trim($cell->textContent), iterator_to_array($xpath->query('./td', $english)))
        );
        $pdf = app('dompdf.wrapper')->loadHTML($html)->setPaper('a4', 'portrait');
        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertStringContainsString('/Subtype /Image', $output);
    }

    public function test_class10_route_uses_its_separate_builder_and_template(): void
    {
        PDF::shouldReceive('loadView')->once()->with('reportcard.JPS.class10_report_card_pdf', \Mockery::type('array'))->andReturnSelf();
        PDF::shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
        PDF::shouldReceive('stream')->once()->with('Class10_Report_Card_10001.pdf')->andReturn(response('PDF', 200));
        $this->getJson('/api/show_report_card?class_id=110&student_id=10001&acd_yr=2026-2027')->assertOk();
        $this->getJson('/api/show_report_card?class_id=110&student_id=10001')->assertUnprocessable()->assertJsonValidationErrors('acd_yr');
        $this->getJson('/api/show_report_card?class_id=110&student_id=10001&acd_yr=2025-2026')->assertNotFound();
    }
}
